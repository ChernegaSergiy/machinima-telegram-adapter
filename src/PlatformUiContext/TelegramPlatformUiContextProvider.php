<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext;

use App\Contract\NullPlatformUiContext;
use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider as PlatformUiContextProviderInterface;
use Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext\TelegramPlatformUiContext;
use Symfony\Component\HttpFoundation\RequestStack;

class TelegramPlatformUiContextProvider implements PlatformUiContextProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return new NullPlatformUiContext();
        }

        $initData = $request->headers->get('X-Init-Data')
            ?? $request->cookies->get('tma_init_data')
            ?? $request->query->get('initData');

        if ($initData) {
            return new TelegramPlatformUiContext($initData);
        }

        return new NullPlatformUiContext();
    }
}
