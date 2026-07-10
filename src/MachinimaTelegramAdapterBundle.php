<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class MachinimaTelegramAdapterBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Morfeditorial\\MachinimaTelegramAdapter\\', '../src/')
            ->exclude('../src/MachinimaTelegramAdapterBundle.php');

        $container->import(__DIR__.'/../config/services.yaml');
    }

    public function getPath(): string
    {
        return __DIR__ . '/..';
    }
}
