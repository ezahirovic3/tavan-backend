<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'payload',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
