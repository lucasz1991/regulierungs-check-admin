<?php

namespace App\Support\Promotion;

use App\Models\PromotionParticipation;
use Illuminate\Support\Str;

final class ParticipationId
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function generate(string $campaignCode): string
    {
        $code = Str::of($campaignCode)->upper()->replaceMatches('/[^A-Z0-9]/', '')->substr(0, 8)->value();
        $code = $code !== '' ? $code : 'PROMO';

        do {
            $random = '';
            for ($index = 0; $index < 12; $index++) {
                $random .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $body = 'RC-'.$code.'-'.implode('-', str_split($random, 4));
            $publicId = $body.'-'.self::checksum($body);
        } while (PromotionParticipation::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }

    public static function isValid(string $publicId): bool
    {
        if (! preg_match('/\A(RC-[A-Z0-9]{1,8}(?:-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}){3})-([23456789ABCDEFGHJKLMNPQRSTUVWXYZ])\z/', $publicId, $matches)) {
            return false;
        }

        return hash_equals(self::checksum($matches[1]), $matches[2]);
    }

    private static function checksum(string $body): string
    {
        $digest = hash('sha256', $body, true);
        $number = unpack('n', substr($digest, 0, 2))[1];

        return self::ALPHABET[$number % strlen(self::ALPHABET)];
    }
}
