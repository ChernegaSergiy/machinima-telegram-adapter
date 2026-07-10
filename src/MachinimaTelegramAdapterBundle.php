<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use Morfeditorial\MachinimaTelegramAdapter\DependencyInjection\Compiler\TelegramOidcCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Symfony bundle that auto-registers all Telegram adapter services.
 * No configuration in the host app is needed — just install the package.
 */
final class MachinimaTelegramAdapterBundle extends AbstractBundle
{
    public function build(ContainerBuilder $builder): void
    {
        parent::build($builder);
        $builder->addCompilerPass(new TelegramOidcCompilerPass());
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Morfeditorial\\MachinimaTelegramAdapter\\', '../src/')
                ->exclude('../src/MachinimaTelegramAdapterBundle.php');
    }
}
