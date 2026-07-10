<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext;

use App\Contract\PlatformUiContext;

class TelegramPlatformUiContext implements PlatformUiContext
{
    /**
     * @param string        $initData
     * @param string        $colorScheme 'dark' or 'light'
     * @param array<string, string> $themeParams
     */
    public function __construct(
        private string $initData,
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
        return 'telegram';
    }

    public function getTheme(): string
    {
        return $this->colorScheme;
    }

    public function getInitData(): ?string
    {
        return $this->initData;
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
