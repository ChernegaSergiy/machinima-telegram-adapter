<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\DependencyInjection\Compiler;

use Morfeditorial\MachinimaTelegramAdapter\Oidc\TelegramOidcConfiguration;
use Morfeditorial\MachinimaTelegramAdapter\Oidc\TelegramOidcProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TelegramOidcCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('env(TELEGRAM_OIDC_CLIENT_ID)')) {
            return;
        }

        $clientId = $container->getParameter('env(TELEGRAM_OIDC_CLIENT_ID)');

        if ('0' === $clientId || '' === $clientId) {
            return;
        }

        $configDef = new Definition(TelegramOidcConfiguration::class, [
            '$clientId' => $clientId,
            '$clientSecret' => $container->getParameter('env(TELEGRAM_OIDC_CLIENT_SECRET)'),
            '$redirectUri' => $container->getParameter('env(TELEGRAM_OIDC_REDIRECT_URI)'),
            '$label' => 'Telegram',
        ]);
        $container->setDefinition(TelegramOidcConfiguration::class, $configDef);

        $providerDef = new Definition(TelegramOidcProvider::class);
        $providerDef->addTag('app.identity_provider');
        $container->setDefinition(TelegramOidcProvider::class, $providerDef);
    }
}
