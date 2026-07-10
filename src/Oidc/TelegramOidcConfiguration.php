<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\Oidc;

final class TelegramOidcConfiguration
{
    public function __construct(
        private readonly int $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $label = 'Telegram',
    ) {
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://oauth.telegram.org/auth';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://oauth.telegram.org/token';
    }

    public function getJwksUri(): string
    {
        return 'https://oauth.telegram.org/.well-known/jwks.json';
    }

    public function isEnabled(): bool
    {
        return $this->clientId > 0 && '' !== $this->clientSecret && '' !== $this->redirectUri;
    }
}
