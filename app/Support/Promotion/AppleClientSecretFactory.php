<?php

namespace App\Support\Promotion;

use Carbon\CarbonImmutable;
use RuntimeException;

final class AppleClientSecretFactory
{
    /** @return array{secret: string, expires_at: CarbonImmutable} */
    public function make(string $servicesId, string $teamId, string $keyId, string $privateKey): array
    {
        $issuedAt = CarbonImmutable::now('UTC');
        $expiresAt = $issuedAt->addDays(150);
        $header = $this->base64Url(json_encode(['alg' => 'ES256', 'kid' => trim($keyId)], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url(json_encode([
            'iss' => trim($teamId),
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'aud' => 'https://appleid.apple.com',
            'sub' => trim($servicesId),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $unsigned = $header.'.'.$payload;

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Die Apple-.p8-Datei ist kein gültiger privater EC-Schlüssel.');
        }

        try {
            $details = openssl_pkey_get_details($key);
            $curve = is_array($details) && is_array($details['ec'] ?? null)
                ? mb_strtolower((string) ($details['ec']['curve_name'] ?? ''))
                : '';
            if (! is_array($details)
                || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_EC
                || ! in_array($curve, ['prime256v1', 'secp256r1', 'p-256'], true)) {
                throw new RuntimeException('Die Apple-.p8-Datei muss einen privaten P-256-EC-Schlüssel enthalten.');
            }

            if (! openssl_sign($unsigned, $derSignature, $key, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Das Apple-Client-Secret konnte nicht signiert werden.');
            }
        } finally {
            openssl_pkey_free($key);
        }

        return [
            'secret' => $unsigned.'.'.$this->base64Url($this->derToJose($derSignature)),
            'expires_at' => $expiresAt,
        ];
    }

    private function derToJose(string $der): string
    {
        $offset = 0;
        if (ord($der[$offset++] ?? "\0") !== 0x30) {
            throw new RuntimeException('Die Apple-Signatur besitzt kein gültiges DER-Format.');
        }

        $this->readLength($der, $offset);
        if (ord($der[$offset++] ?? "\0") !== 0x02) {
            throw new RuntimeException('Die Apple-Signatur enthält keine gültige R-Komponente.');
        }
        $rLength = $this->readLength($der, $offset);
        $r = substr($der, $offset, $rLength);
        $offset += $rLength;

        if (ord($der[$offset++] ?? "\0") !== 0x02) {
            throw new RuntimeException('Die Apple-Signatur enthält keine gültige S-Komponente.');
        }
        $sLength = $this->readLength($der, $offset);
        $s = substr($der, $offset, $sLength);

        return $this->normalizeCoordinate($r).$this->normalizeCoordinate($s);
    }

    private function readLength(string $der, int &$offset): int
    {
        $length = ord($der[$offset++] ?? "\0");
        if (($length & 0x80) === 0) {
            return $length;
        }

        $bytes = $length & 0x7F;
        if ($bytes < 1 || $bytes > 2) {
            throw new RuntimeException('Die Apple-Signatur verwendet eine nicht unterstützte DER-Länge.');
        }

        $length = 0;
        for ($index = 0; $index < $bytes; $index++) {
            $length = ($length << 8) | ord($der[$offset++] ?? "\0");
        }

        return $length;
    }

    private function normalizeCoordinate(string $value): string
    {
        $value = ltrim($value, "\0");
        if (strlen($value) > 32) {
            throw new RuntimeException('Die Apple-Signatur enthält eine zu große EC-Komponente.');
        }

        return str_pad($value, 32, "\0", STR_PAD_LEFT);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
