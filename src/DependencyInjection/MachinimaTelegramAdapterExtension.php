<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class MachinimaTelegramAdapterExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config'),
        );

        $loader->load('services.yaml');

        $container->setParameter('telegram_oidc.client_id', $container->getParameter('env(TELEGRAM_OIDC_CLIENT_ID)'));
        $container->setParameter('telegram_oidc.client_secret', $container->getParameter('env(TELEGRAM_OIDC_CLIENT_SECRET)'));
        $container->setParameter('telegram_oidc.redirect_uri', $container->getParameter('env(TELEGRAM_OIDC_REDIRECT_URI)'));
    }
}
