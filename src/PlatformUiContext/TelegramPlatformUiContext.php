<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext;

use App\Contract\PlatformUiContext;

class TelegramPlatformUiContext implements PlatformUiContext
{
    /**
     * @param string                 $colorScheme 'dark' or 'light'
     * @param array<string, string>  $themeParams
     */
    public function __construct(
        private string $colorScheme = 'dark',
        private array $themeParams = [],
    ) {
    }

    public function isEmbedded(): bool
    {
        return true;
    }

    public function getPlatformName(): string
    {
        return 'telegram_mini_app';
    }

    public function getTheme(): string
    {
        return $this->colorScheme;
    }

    public function getUserId(): ?string
    {
        return null;
    }

    public function getBotLink(): ?string
    {
        return null;
    }

    public function getCapabilities(): array
    {
        return ['tma', 'notifications', 'back_button'];
    }

    public function getSystemThemeParams(): array
    {
        return $this->themeParams;
    }
}
