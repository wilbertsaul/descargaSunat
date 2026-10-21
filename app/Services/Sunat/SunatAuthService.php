<?php

namespace App\Services\Sunat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SunatAuthService
{
    private string $oauthUrl;

    private string $clientId;

    private string $clientSecret;

    private string $ruc;

    private string $solUser;

    private string $solPassword;

    public function __construct()
    {
        $this->oauthUrl = rtrim(config('services.sunat.oauth_url', 'https://api-seguridad.sunat.gob.pe'), '/');
        $this->clientId = config('services.sunat.client_id', '');
        $this->clientSecret = config('services.sunat.client_secret', '');
        $this->ruc = config('services.sunat.ruc', '');
        $this->solUser = config('services.sunat.sol_user', '');
        $this->solPassword = config('services.sunat.sol_password', '');
    }

    public function getToken(string $scope = 'sire'): string
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('Credenciales de API SUNAT no configuradas. Genéralas en SOL → Gestión Credenciales de API SUNAT.');
        }

        if (empty($this->solUser) || empty($this->solPassword) || empty($this->ruc)) {
            throw new \RuntimeException('RUC o credenciales SOL no configuradas.');
        }

        $scopeUrl = $scope === 'cpe'
            ? config('services.sunat.scope_cpe', 'https://api-cpe.sunat.gob.pe')
            : config('services.sunat.scope_sire', 'https://api-sire.sunat.gob.pe');

        $cacheKey = 'sunat_token_'.$scope.'_'.md5($this->clientId.$this->solUser);

        return Cache::remember($cacheKey, 3300, function () use ($scopeUrl) {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->timeout(30)->asForm()->post(
                "{$this->oauthUrl}/v1/clientessol/{$this->clientId}/oauth2/token/",
                [
                    'grant_type' => 'password',
                    'scope' => $scopeUrl,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'username' => $this->ruc.$this->solUser,
                    'password' => $this->solPassword,
                ]
            );

            if ($response->failed()) {
                $body = $response->json();
                $errorMsg = $body['error_description'] ?? $body['error'] ?? 'HTTP '.$response->status();
                throw new \RuntimeException("Autenticación SUNAT falló: {$errorMsg}");
            }

            $token = $response->json('access_token');

            if (empty($token)) {
                throw new \RuntimeException('SUNAT no devolvió access_token.');
            }

            return $token;
        });
    }

    public function getTokenSire(): string
    {
        return $this->getToken('sire');
    }

    public function getTokenCpe(): string
    {
        return $this->getToken('cpe');
    }

    public function clearCache(): void
    {
        Cache::forget('sunat_token_sire_'.md5($this->clientId.$this->solUser));
        Cache::forget('sunat_token_cpe_'.md5($this->clientId.$this->solUser));
    }
}
