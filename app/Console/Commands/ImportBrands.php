<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportBrands extends Command
{
    protected $signature = 'brands:import {path : Absolute path to directory containing PNG brand logos}
                                          {--dry-run : Preview what would be imported without writing anything}';

    protected $description = 'Upload PNG brand logos to R2 and create Brand records from filenames';

    public function handle(): int
    {
        $dir = rtrim($this->argument('path'), '/');
        $dryRun = $this->option('dry-run');

        if (! is_dir($dir)) {
            $this->error("Directory not found: {$dir}");
            return self::FAILURE;
        }

        $files = collect(glob("{$dir}/*.png"))
            ->merge(glob("{$dir}/*.PNG"))
            ->unique()
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->warn('No PNG files found in the given directory.');
            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} PNG files." . ($dryRun ? ' (dry-run)' : ''));
        $this->newLine();

        $created = 0;
        $skipped = 0;

        foreach ($files as $index => $filepath) {
            $filename  = pathinfo($filepath, PATHINFO_FILENAME);
            $brandName = $filename;
            $slug      = Str::slug($brandName);

            if (! $slug) {
                $this->warn("  Skipping '{$filename}' — could not generate a slug.");
                $skipped++;
                continue;
            }

            $existing = Brand::where('slug', $slug)->first();

            if ($existing) {
                $this->line("  <fg=yellow>SKIP</>  {$brandName} (slug '{$slug}' already exists)");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  <fg=cyan>DRY</>   {$brandName} → brands/{$slug}.png");
                $created++;
                continue;
            }

            $r2Path  = "brands/{$slug}.png";
            $content = file_get_contents($filepath);

            Storage::disk('r2')->put($r2Path, $content, [
                'visibility'  => 'public',
                'ContentType' => 'image/png',
            ]);

            $logoUrl = rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $r2Path;

            Brand::create([
                'name'       => $brandName,
                'slug'       => $slug,
                'logo_url'   => $logoUrl,
                'is_active'  => true,
                'is_other'   => false,
                'sort_order' => $index + 1,
            ]);

            $this->line("  <fg=green>OK</>    {$brandName} → {$logoUrl}");
            $created++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Dry-run complete. Would create {$created} brand(s), skip {$skipped}.");
        } else {
            $this->info("Done. Created {$created} brand(s), skipped {$skipped}.");
        }

        return self::SUCCESS;
    }
}
