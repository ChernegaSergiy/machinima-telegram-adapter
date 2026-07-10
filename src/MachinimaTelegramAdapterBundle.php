<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Symfony bundle that auto-registers all Telegram adapter services.
 * No configuration in the host app is needed — just install the package.
 */
final class MachinimaTelegramAdapterBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Morfeditorial\\MachinimaTelegramAdapter\\', '../src/')
                ->exclude('../src/MachinimaTelegramAdapterBundle.php')
            ->load('Morfeditorial\\MachinimaTelegramAdapter\\Oidc\\', '../src/Oidc/');

        $container->parameters()
            ->set('telegram_oidc.client_id', '%env(TELEGRAM_OIDC_CLIENT_ID)%')
            ->set('telegram_oidc.client_secret', '%env(TELEGRAM_OIDC_CLIENT_SECRET)%')
            ->set('telegram_oidc.redirect_uri', '%env(TELEGRAM_OIDC_REDIRECT_URI)%');
    }
}
