<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPhone
{
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => self::normalizePhone((string) $this->phone)]);
        }
    }

    private static function normalizePhone(string $phone): string
    {
        // Strip whitespace, dashes, parentheses
        $phone = preg_replace('/[\s\-().]+/', '', $phone);

        // 00 prefix → +
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        // Local BA format: 06x/07x → +387...
        if (preg_match('/^0[1-9]\d+$/', $phone)) {
            $phone = '+387' . substr($phone, 1);
        }

        // Bare digits with no +: prepend +
        if (preg_match('/^\d+$/', $phone)) {
            $phone = '+' . $phone;
        }

        // Deduplicate repeated country code: +387387... → +387...
        $phone = preg_replace('/^\+(\d{1,4})\1/', '+$1', $phone);

        return $phone;
    }
}
