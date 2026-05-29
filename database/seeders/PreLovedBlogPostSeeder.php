<?php

namespace Database\Seeders;

use App\Models\BlogAuthor;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PreLovedBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = BlogAuthor::firstOrCreate(
            ['name' => 'Edib Zahirović'],
            ['bio' => 'CEO i suosnivač Tavana']
        );

        // Note: cover_image intentionally omitted — upload via admin panel.
        // Re-running this seeder will not overwrite a cover_image set via admin.
        BlogPost::updateOrCreate(
            ['slug' => 'odjeca-ne-gubi-vrijednost-nakon-jednog-nosenja'],
            [
                'title' => 'Odjeća ne gubi vrijednost nakon jednog nošenja',
                'tag' => 'Moda',
                'excerpt' => 'Vrijednost odjeće nije u tome koliko je puta obučena. Vrijednost je u tome koliko će još puta biti obučena.',
                'cover_color' => '#F1F8E9',
                'read_time' => '4 min',
                'blog_author_id' => $author->id,
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-29 10:00:00'),
                'blocks' => $this->blocks(),
            ]
        );

        $this->command->info('Seeded "pre-loved" blog post.');
    }

    private function blocks(): array
    {
        return [
            ['type' => 'paragraph', 'text' => 'Čudna je stvar kako određujemo vrijednost odjeće.'],
            ['type' => 'paragraph', 'text' => 'Majica kupljena jutros vrijedi. Jakna iz prodavnice vrijedi. Patike koje su upravo izašle iz kutije vrijede.'],
            ['type' => 'paragraph', 'text' => 'Ali čim ih neko obuče jednom ili dva puta, odjednom postaju “polovne”. Kao da se nešto magično desi između prvog i drugog nošenja.'],
            ['type' => 'paragraph', 'text' => 'Realno, nije. Ako je jakna i dalje kvalitetna, ako dobro izgleda i ako će nekome služiti godinama, zašto bi vrijedila manje samo zato što je već bila u nečijem ormaru?'],

            ['type' => 'heading', 'text' => 'Novo nije isto što i vrijedno'],
            ['type' => 'paragraph', 'text' => 'Nekako smo navikli da riječ “novo” automatski povezujemo s vrijednošću. Ali kada malo bolje razmislimo, većina stvari koje zaista volimo nema veze s tim jesu li nove.'],
            ['type' => 'paragraph', 'text' => 'Volimo ih jer su kvalitetne. Jer nam dobro stoje. Jer imaju priču. Jer ih rado nosimo. Sve te stvari ostaju iste i nakon jednog nošenja.'],
            ['type' => 'paragraph', 'text' => 'Odjeća ne prestaje biti dobra čim skinemo etiketu.'],

            ['type' => 'heading', 'text' => 'Budimo realni'],
            ['type' => 'paragraph', 'text' => 'Većina nas ima barem nekoliko komada koje više ne nosimo. Možda su nam dosadili. Možda smo promijenili stil. Možda ih čuvamo za neku priliku koja nikako da dođe. I tako stoje. Mjesecima. Godinama.'],
            ['type' => 'paragraph', 'text' => 'Na Balkanu smo poznati po tome da čuvamo stvari “za svaki slučaj”. Kutije od telefona. Kese. Stare kablove. A često i odjeću koju više ne nosimo.'],
            ['type' => 'paragraph', 'text' => 'Problem je što taj “svaki slučaj” rijetko dođe. U međuvremenu, neko drugi možda upravo traži taj komad koji kod nas skuplja prašinu.'],

            ['type' => 'heading', 'text' => 'Najbolji komadi često nisu novi'],
            ['type' => 'paragraph', 'text' => 'Vintage jakne, stare kožne torbe, kvalitetni kaputi, farmerke koje su već dobile svoj karakter kroz nošenje. Mnogi od najzanimljivijih komada u ormarima širom svijeta nisu kupljeni novi.'],
            ['type' => 'paragraph', 'text' => 'Zapravo, dio njihove vrijednosti upravo dolazi iz toga što nisu isti kao sve ostalo u izlogu. Danas ljudi sve češće biraju pre-loved odjeću jer žele pronaći nešto drugačije. Nešto što nema svako.'],
            ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DYPt0FlMqXi/'],

            ['type' => 'heading', 'text' => 'Odjeća zaslužuje više od nekoliko izlazaka'],
            ['type' => 'paragraph', 'text' => 'Iskreno, šteta je kada dobar komad odjeće završi zaboravljen na dnu ormara nakon nekoliko nošenja. Ne zato što je loš. Nego zato što je jednostavno došlo vrijeme da ga nosi neko drugi.'],
            ['type' => 'paragraph', 'text' => 'Svaki komad ima potencijal za mnogo više od nekoliko izlazaka, nekoliko fotografija i nekoliko mjeseci u ormaru. Odjeća je napravljena da se nosi.'],

            ['type' => 'heading', 'text' => 'Vrijeme je da promijenimo pogled'],
            ['type' => 'paragraph', 'text' => 'Možda je vrijeme da prestanemo gledati polovnu odjeću kao nešto manje vrijedno. Jer vrijednost nije u tome koliko je puta nešto obučeno. Vrijednost je u tome koliko će još puta biti obučeno.'],
            ['type' => 'paragraph', 'text' => 'Zato vjerujemo da dobra odjeća zaslužuje drugi život. A možda baš sada u tvom ormaru postoji komad koji više ne nosiš, a nekome bi mogao postati omiljeni dio garderobe.'],
            ['type' => 'paragraph', 'text' => 'I to je sasvim dovoljan razlog da ga pustiš dalje.'],
        ];
    }
}
