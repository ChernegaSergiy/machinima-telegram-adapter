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
        $clientId = getenv('TELEGRAM_OIDC_CLIENT_ID') ?: $container->getParameter('env(TELEGRAM_OIDC_CLIENT_ID)');

        if (!$clientId || '0' === $clientId || '' === $clientId) {
            $container->removeDefinition(TelegramOidcConfiguration::class);
            $container->removeDefinition(TelegramOidcProvider::class);

            return;
        }

        $definition = new Definition(TelegramOidcConfiguration::class, [
            '$clientId' => $clientId,
            '$clientSecret' => getenv('TELEGRAM_OIDC_CLIENT_SECRET') ?: $container->getParameter('env(TELEGRAM_OIDC_CLIENT_SECRET)'),
            '$redirectUri' => getenv('TELEGRAM_OIDC_REDIRECT_URI') ?: $container->getParameter('env(TELEGRAM_OIDC_REDIRECT_URI)'),
            '$label' => 'Telegram',
        ]);

        $container->setDefinition(TelegramOidcConfiguration::class, $definition);

        $providerDef = new Definition(TelegramOidcProvider::class);
        $providerDef->addTag('app.identity_provider');
        $container->setDefinition(TelegramOidcProvider::class, $providerDef);
    }
}
