<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        $at = strrpos($value, '@');

        if ($at === false) {
            return;
        }

        $domain = strtolower(trim(substr($value, $at + 1)));

        if (in_array($domain, config('disposable_email_domains', []), true)) {
            $fail('Please use a permanent email address — disposable or temporary email providers are not accepted.');
        }
    }
}
