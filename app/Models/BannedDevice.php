<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class BannedDevice extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['device_id', 'banned_at', 'reason'];

    protected function casts(): array
    {
        return [
            'banned_at' => 'datetime',
        ];
    }
}
