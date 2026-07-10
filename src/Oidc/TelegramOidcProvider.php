<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\Oidc;

use App\Contract\IdentityAssertion;
use App\Contract\IdentityProviderMetadataProvider;
use App\Contract\IdentityProviderPort;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramOidcProvider implements IdentityProviderPort, IdentityProviderMetadataProvider
{
    public function __construct(
        private TelegramOidcConfiguration $config,
        private TelegramOidcTokenValidator $tokenValidator,
        private HttpClientInterface $httpClient,
        private UrlGeneratorInterface $router,
    ) {
    }

    public function getProviderName(): string
    {
        return 'telegram';
    }

    public function validateAssertion(string $rawAssertion): IdentityAssertion
    {
        $claims = $this->tokenValidator->validate($rawAssertion, $this->config->getClientId());

        return new IdentityAssertion(
            providerName: $this->getProviderName(),
            providerSubjectId: (string) $claims['id'],
            displayName: $claims['name'],
            avatarUrl: $claims['picture'] ?? null,
            claims: $claims,
        );
    }

    public function getMetadata(): array
    {
        $metadata = [
            'label' => $this->config->getLabel(),
            'icon' => 'send',
        ];

        if ($this->config->isEnabled()) {
            $metadata['login_url'] = $this->router->generate('telegram_oidc_login');
        }

        return $metadata;
    }

    public function getConfig(): TelegramOidcConfiguration
    {
        return $this->config;
    }

    public function getAuthorizationUrl(string $state, string $codeChallenge): string
    {
        $params = http_build_query([
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid profile',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return $this->config->getAuthorizationEndpoint() . '?' . $params;
    }

    public function exchangeCodeForToken(string $code, string $codeVerifier): string
    {
        $credentials = base64_encode($this->config->getClientId() . ':' . $this->config->getClientSecret());

        $response = $this->httpClient->request('POST', $this->config->getTokenEndpoint(), [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->config->getRedirectUri(),
                'client_id' => $this->config->getClientId(),
                'code_verifier' => $codeVerifier,
            ]),
        ]);

        $data = $response->toArray();

        if (!isset($data['id_token'])) {
            throw new \RuntimeException('No id_token in response.');
        }

        return $data['id_token'];
    }
}
