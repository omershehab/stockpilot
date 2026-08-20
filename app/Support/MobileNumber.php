<?php

namespace App\Support;

class MobileNumber
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return str_starts_with($value, '+') ? '+'.$digits : $digits;
    }

    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^\+?[0-9]{8,15}$/',
            'max:16',
        ];
    }
}
