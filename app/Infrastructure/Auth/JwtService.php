<?php

namespace App\Infrastructure\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class JwtService
{
    public function createToken(User $user): array
    {
        $now = time();
        $ttl = (int) config('security.jwt.ttl', 3600);
        $refreshTtl = (int) config('security.jwt.refresh_ttl', 604800);

        $accessPayload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => (string) Str::uuid(),
        ];

        $refreshPayload = [
            'sub' => $user->id,
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + $refreshTtl,
            'jti' => (string) Str::uuid(),
        ];

        $privateKey = file_get_contents(storage_path('jwt/private.pem'));

        return [
            'access_token' => JWT::encode($accessPayload, $privateKey, 'RS256'),
            'refresh_token' => JWT::encode($refreshPayload, $privateKey, 'RS256'),
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
        ];
    }

    public function verifyToken(string $token): ?object
    {
        try {
            $publicKey = file_get_contents(storage_path('jwt/public.pem'));

            return JWT::decode($token, new Key($publicKey, 'RS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function refreshToken(string $refreshToken): ?array
    {
        $payload = $this->verifyToken($refreshToken);

        if (! $payload || ($payload->type ?? null) !== 'refresh') {
            return null;
        }

        $user = User::find($payload->sub);

        if (! $user) {
            return null;
        }

        return $this->createToken($user);
    }
}
