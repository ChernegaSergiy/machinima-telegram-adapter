<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use App\Contract\PlatformAdapterInterface;
use App\Contract\PlatformUiContext;
use Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext\TelegramPlatformUiContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app.platform_adapter')]
final class TelegramPlatformAdapter implements PlatformAdapterInterface
{
    public function supports(Request $request): bool
    {
        return $request->headers->has('X-Telegram-Init-Data')
            || $request->headers->has('X-Init-Data')
            || $request->cookies->has('tma_init_data')
            || $request->query->has('initData');
    }

    public function getContext(Request $request): PlatformUiContext
    {
        $initData = $request->headers->get('X-Telegram-Init-Data')
            ?? $request->headers->get('X-Init-Data')
            ?? $request->cookies->get('tma_init_data')
            ?? $request->query->get('initData');

        $colorScheme = $request->cookies->get('tma_color_scheme', 'dark');

        return new TelegramPlatformUiContext(
            initData: (string) $initData,
            colorScheme: $colorScheme,
        );
    }

    public function getBridgeTemplatePath(): ?string
    {
        return '@MachinimaTelegramAdapter/bridge/telegram.html.twig';
    }

    public function getZeroClickLoginUrl(): ?string
    {
        return '/telegram/zero-click';
    }

    public function getLoginRouteName(): ?string
    {
        return 'app_login';
    }
}
