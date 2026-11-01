<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class LoginEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate syntax locally; DNS cannot prove that a mailbox belongs to a user.
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL) ||
            !preg_match('/@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/iD', $value)) {
            $fail('Enter a valid email address, for example name@example.com.');
        }
    }
}
