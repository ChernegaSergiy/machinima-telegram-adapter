<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext;

use App\Contract\PlatformUiContext;

class TelegramPlatformUiContext implements PlatformUiContext
{
    public function __construct(
        private string $initData,
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
        return 'dark';
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
}
