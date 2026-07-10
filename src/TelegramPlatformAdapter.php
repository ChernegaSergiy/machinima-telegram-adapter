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
        // Telegram Mini App passes initData via header, cookie, or query param
        return $request->headers->has('X-Init-Data')
            || $request->cookies->has('tma_init_data')
            || $request->query->has('initData');
    }

    public function getContext(Request $request): PlatformUiContext
    {
        $initData = $request->headers->get('X-Init-Data')
            ?? $request->cookies->get('tma_init_data')
            ?? $request->query->get('initData');

        return new TelegramPlatformUiContext($initData);
    }
}
