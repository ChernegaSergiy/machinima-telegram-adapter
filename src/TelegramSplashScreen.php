<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use Morfeditorial\MachinimaCoreBundle\Contract\SplashScreenInterface;

final class TelegramSplashScreen implements SplashScreenInterface
{
    public function getPlatformName(): string
    {
        return 'telegram';
    }

    public function getTemplatePath(): string
    {
        return '@MachinimaTelegramAdapter/splash/index.html.twig';
    }

    public function getDisplayCondition(): ?string
    {
        return "window.location.hash.includes('tgWebAppData=') || document.cookie.includes('tma_theme_params=')";
    }
}
