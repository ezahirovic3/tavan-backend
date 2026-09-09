<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPhone
{
    /**
     * Accept ONLY Bosnia and Herzegovina mobile numbers in E.164 form:
     * +387 6X XXXXXX(X)  ->  +3876 followed by 6–8 digits.
     *
     * Landlines and every other country are rejected on purpose — the OTP
     * flow is BiH-only and foreign numbers on our Twilio bill are bots.
     */
    public const BA_PHONE_REGEX = '/^\+3876\d{6,8}$/';

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => self::normalizePhone((string) $this->phone)]);
        }
    }

    /**
     * Validation rules for a BiH mobile number. Share across every request
     * that takes a `phone` so the accepted shape can never drift apart.
     *
     * @return array<int, string>
     */
    protected static function bosnianPhoneRules(): array
    {
        return ['required', 'string', 'regex:'.self::BA_PHONE_REGEX];
    }

    private static function normalizePhone(string $phone): string
    {
        // Strip whitespace, dashes, parentheses, dots
        $phone = preg_replace('/[\s\-().]+/', '', $phone);

        // 00 international prefix → +
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        // Local BA format: 06x/03x → +387...
        if (preg_match('/^0[1-9]\d+$/', $phone)) {
            $phone = '+387' . substr($phone, 1);
        }

        // Bare digits, no +: prepend +
        if (preg_match('/^\d+$/', $phone)) {
            $phone = '+' . $phone;
        }

        // Deduplicate a repeated country code: +387387... → +387...
        $phone = preg_replace('/^\+(\d{1,4})\1/', '+$1', $phone);

        // National trunk zero left in after the country code: +3870601... → +387601...
        $phone = preg_replace('/^\+3870+/', '+387', $phone);

        return $phone;
    }
}
