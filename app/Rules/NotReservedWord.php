<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotReservedWord implements ValidationRule
{
    // Checked case-insensitively; also matches substrings for the `name` field
    // when $exact is false (e.g. "Tavan Admin" contains "admin").
    private const RESERVED = [
        'admin', 'administrator', 'adminstracija',
        'support', 'podrska', 'pomoc',
        'tavan', 'tavanstore', 'tavan_store',
        'moderator', 'mod',
        'system', 'sistem',
        'official', 'sluzbeno', 'sluzbeni',
        'staff', 'osoblje',
        'root', 'superadmin', 'super_admin',
        'help', 'info', 'contact', 'kontakt',
        'security', 'sigurnost',
    ];

    public function __construct(private bool $exact = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $lower = mb_strtolower((string) $value);

        foreach (self::RESERVED as $word) {
            $match = $this->exact
                ? ($lower === $word)
                : str_contains($lower, $word);

            if ($match) {
                $fail('Ovo ime nije dostupno.');
                return;
            }
        }
    }
}
