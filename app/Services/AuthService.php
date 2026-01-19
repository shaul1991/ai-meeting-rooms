<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class AuthService
{
    /**
     * 회원가입
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => false,
        ]);

        $token = $this->createToken($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ];
    }

    /**
     * 로그인
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $this->createToken($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ];
    }

    /**
     * 로그아웃
     */
    public function logout(User $user): void
    {
        // 현재 액세스 토큰 취소
        $token = $user->token();

        if ($token) {
            $token->revoke();

            // 연관된 리프레시 토큰도 취소
            RefreshToken::where('access_token_id', $token->id)->update(['revoked' => true]);
        }
    }

    /**
     * 토큰 갱신 (Token Rotation)
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            // OAuth2 refresh token grant 사용
            $response = Http::asForm()->post(url('/oauth/token'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('passport.personal_access_client.id'),
                'client_secret' => config('passport.personal_access_client.secret'),
                'scope' => '',
            ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return [
                'token' => [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $data['expires_in'],
                ],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Personal Access Token 생성
     */
    private function createToken(User $user): array
    {
        $tokenResult = $user->createToken('Personal Access Token');

        return [
            'access_token' => $tokenResult->accessToken,
            'refresh_token' => $this->createRefreshToken($tokenResult->token),
            'token_type' => 'Bearer',
            'expires_in' => config('passport.tokens_expire_in', 3600),
        ];
    }

    /**
     * Refresh Token 생성
     */
    private function createRefreshToken(Token $accessToken): string
    {
        $refreshToken = RefreshToken::create([
            'id' => bin2hex(random_bytes(40)),
            'access_token_id' => $accessToken->id,
            'revoked' => false,
            'expires_at' => now()->addDays(config('passport.refresh_tokens_expire_in', 14)),
        ]);

        return $refreshToken->id;
    }
}
