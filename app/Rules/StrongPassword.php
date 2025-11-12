<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('Password tidak valid.');
            return;
        }

        if (! preg_match('/[A-Za-z]/', $value) || ! preg_match('/\d/', $value)) {
            $fail('Password terlalu lemah. Gunakan kombinasi huruf dan angka.');
            return;
        }
    }
}
