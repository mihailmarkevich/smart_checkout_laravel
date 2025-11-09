<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class JwtService
{
    public function generateToken(User $user): string
    {
        $now = time();
        $ttl = (int) config('jwt.ttl', 3600);

        $payload = [
            'sub'   => $user->id,
            'email'=> $user->email,
            'iat'  => $now,
            'exp'  => $now + $ttl,
        ];

        return $this->encode($payload);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;

        $header = json_decode($this->b64decode($header64), true);
        $payload = json_decode($this->b64decode($payload64), true);
        $signature = $this->b64decode($signature64, raw: true);

        if (! is_array($header) || ! is_array($payload) || $signature === null) {
            return null;
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $secret = (string) config('jwt.secret');
        $validSig = hash_hmac('sha256', $header64.'.'.$payload64, $secret, true);

        if (! hash_equals($validSig, $signature)) {
            return null;
        }

        $exp = Arr::get($payload, 'exp');
        if (is_int($exp) && $exp < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $header64 = $this->b64encode(json_encode($header, JSON_THROW_ON_ERROR));
        $payload64 = $this->b64encode(json_encode($payload, JSON_THROW_ON_ERROR));

        $secret = (string) config('jwt.secret');
        $signature = hash_hmac('sha256', $header64.'.'.$payload64, $secret, true);
        $signature64 = $this->b64encode($signature);

        return $header64.'.'.$payload64.'.'.$signature64;
    }

    private function b64encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64decode(string $data, bool $raw = false): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            return null;
        }

        return $decoded;
    }
}
