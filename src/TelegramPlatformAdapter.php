<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use App\Contract\PlatformAdapterInterface;
use App\Contract\PlatformUiContext;
use Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext\TelegramPlatformUiContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Telegram Mini App platform adapter.
 *
 * Auth is handled entirely by TelegramMiniAppIdentityProvider (validated via
 * AuthBootstrapController, driven by telegram-bootstrap.js). This class is
 * purely presentational: which UI context a Telegram session gets, and
 * where its bootstrap/UI-hints modules live. It no longer sniffs any
 * request header, cookie, or query parameter to guess the platform.
 */
#[AutoconfigureTag('app.platform_adapter')]
final class TelegramPlatformAdapter implements PlatformAdapterInterface
{
    public function getPlatformName(): string
    {
        return 'telegram';
    }

    public function getUiContext(Request $request): PlatformUiContext
    {
        // Purely cosmetic, adapter-owned cookies (set by telegram-ui-hints.js
        // itself) — not used for auth or platform detection, only to avoid a
        // flash of the wrong theme on the next SSR render.
        return new TelegramPlatformUiContext(
            colorScheme: $request->cookies->get('tma_color_scheme', 'dark'),
        );
    }

    public function getBootstrapModulePath(): ?string
    {
        return 'bundles/machinimatelegramadapter/js/telegram-bootstrap.js';
    }

    public function getUiHintsModulePath(): ?string
    {
        return 'bundles/machinimatelegramadapter/js/telegram-ui-hints.js';
    }
}
