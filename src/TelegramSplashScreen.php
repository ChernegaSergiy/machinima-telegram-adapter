<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use App\Contract\SplashScreenInterface;

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
}
