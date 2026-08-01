<?php

namespace App\Services;

class ClubmanProfileResponse
{
    public static function primaryEmail(string $responseBody): ?string
    {
        if (! preg_match(
            '/"EMAIL"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/i',
            $responseBody,
            $matches
        )) {
            return null;
        }

        $decoded = json_decode('"' . $matches[1] . '"');
        $email = trim(is_string($decoded) ? $decoded : $matches[1]);

        return $email === '' ? null : $email;
    }
}
