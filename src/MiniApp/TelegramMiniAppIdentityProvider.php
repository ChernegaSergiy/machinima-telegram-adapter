<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\MiniApp;

use Morfeditorial\MachinimaCoreBundle\Contract\BootstrapOnlyIdentityProvider;
use Morfeditorial\MachinimaCoreBundle\Contract\IdentityAssertion;

/**
 * Validates the raw `initData` string a Telegram Mini App receives from
 * `window.Telegram.WebApp.initData`, per Telegram's documented algorithm:
 * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 *
 * Deliberately registered under a DIFFERENT registry name ('telegram_mini_app')
 * than TelegramOidcProvider ('telegram') — they validate two structurally
 * different assertion formats (raw signed query string vs. an OIDC id_token)
 * and IdentityProviderRegistry resolves providers by a single name, so they
 * cannot share one. The resulting IdentityAssertion still carries
 * providerName 'telegram', which is the actual identity-linking key used by
 * AuthSubscriber/UserIdentity — so a user ends up in the same account
 * regardless of which of the two flows they logged in through.
 *
 * BootstrapOnlyIdentityProvider hides it from the /login provider list: it
 * is never a button, only ever reached via AuthBootstrapController.
 */
final class TelegramMiniAppIdentityProvider implements BootstrapOnlyIdentityProvider
{
    /**
     * @param int $maxAuthAgeSeconds reject initData older than this, to limit
     *                               the blast radius if a captured initData
     *                               string ever leaked (e.g. via logs)
     */
    public function __construct(
        private string $botToken,
        private int $maxAuthAgeSeconds = 86400,
    ) {
    }

    public function getProviderName(): string
    {
        return 'telegram_mini_app';
    }

    public function validateAssertion(string $rawAssertion): IdentityAssertion
    {
        parse_str($rawAssertion, $data);

        if (!isset($data['hash']) || !is_string($data['hash'])) {
            throw new \RuntimeException('Telegram initData is missing a hash.');
        }

        $receivedHash = $data['hash'];
        unset($data['hash']);

        ksort($data);
        $dataCheckString = implode("\n", array_map(
            static fn (string $key, string $value): string => "{$key}={$value}",
            array_keys($data),
            $data,
        ));

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($computedHash, $receivedHash)) {
            throw new \RuntimeException('Telegram initData signature is invalid.');
        }

        $authDate = isset($data['auth_date']) ? (int) $data['auth_date'] : 0;
        if ($authDate <= 0 || (time() - $authDate) > $this->maxAuthAgeSeconds) {
            throw new \RuntimeException('Telegram initData has expired.');
        }

        $user = [];
        if (isset($data['user']) && is_string($data['user'])) {
            $decoded = json_decode($data['user'], true);
            if (is_array($decoded)) {
                $user = $decoded;
            }
        }

        $telegramId = $user['id'] ?? null;
        if (null === $telegramId) {
            throw new \RuntimeException('Telegram initData is missing the user id.');
        }

        $nameParts = array_filter([$user['first_name'] ?? '', $user['last_name'] ?? '']);

        return new IdentityAssertion(
            providerName: 'telegram',
            providerSubjectId: (string) $telegramId,
            displayName: $nameParts ? implode(' ', $nameParts) : ($user['username'] ?? null),
            avatarUrl: $user['photo_url'] ?? null,
            claims: $data,
            issuedAt: new \DateTimeImmutable('@' . $authDate),
        );
    }
}
