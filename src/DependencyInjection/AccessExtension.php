<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Access\AccessBundle\DependencyInjection;

use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class AccessExtension extends Extension
{
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // database

        $container->setParameter('access.connection_driver', $config['connection']['driver']);
        $container->setParameter('access.connection_host', $config['connection']['host']);
        $container->setParameter('access.connection_port', $config['connection']['port']);
        $container->setParameter('access.connection_database', $config['connection']['database']);
        $container->setParameter('access.connection_username', $config['connection']['username']);
        $container->setParameter(
            'access.connection_password',
            $config['connection']['password'] ?? null, // only one without a default
        );

        // profiler

        $container->setParameter('access.profiler_mode', $config['profiler']['mode']);

        // migrations

        $container->setParameter(
            'access.migrations.migrations_namespace',
            $config['migrations']['migrations_namespace'],
        );
        $container->setParameter(
            'access.migrations.migrations_path',
            $config['migrations']['migrations_path'],
        );
        $container->setParameter(
            'access.migrations.migration_entity_class',
            $config['migrations']['migration_entity_class'],
        );

        $locator = new FileLocator(__DIR__ . '/../../config');
        $loader = new PhpFileLoader($container, $locator);

        $loader->load('services.php');
    }
}
