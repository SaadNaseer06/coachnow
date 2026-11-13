<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Parents and coaches call or text each other about sessions, so the number has to be
 * complete: 10 to 15 digits, with any spaces, dashes, brackets or a leading +.
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $digits = strlen(preg_replace('/\D/', '', (string) $value));
        $allowed = preg_match('/^\+?[0-9\s().-]+$/', trim((string) $value)) === 1;

        if (! $allowed || $digits < 10 || $digits > 15) {
            $fail('Enter a full phone number, including the area code.');
        }
    }
}
