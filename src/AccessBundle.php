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

namespace Access\AccessBundle;

use Access\Database;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Stopwatch\Stopwatch;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @psalm-api
 */
final class AccessBundle extends AbstractBundle
{
    #[Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        /**
         * Method does exist, but psalm cannot see it due to "magical" nature of the class
         * @psalm-suppress UndefinedMethod
         */
        // prettier-ignore
        $definition
            ->rootNode()
                ->children()
                    ->arrayNode('connection')
                    ->info('Database connection settings')
                    ->children()
                        ->enumNode('driver')
                            ->info('The driver to use for the connection')
                            ->values(['mysql', 'sqlite'])
                            ->defaultValue('mysql')
                        ->end()
                        ->scalarNode('host')
                            ->info('The host to connect to')
                            ->defaultValue('localhost')
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('port')
                            ->info('The port to connect to')
                            ->defaultValue(3306)
                        ->end()
                        ->scalarNode('database')
                            ->info('The name of the database')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('username')
                            ->info('The username to connect with')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('password')
                            ->info('The password to connect with')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('profiler')
                    ->info('Database profiler settings')
                    ->children()
                        ->enumNode('mode')
                            ->info('The profiler mode')
                            ->values(['regular', 'blackhole'])
                            ->defaultValue('regular')
                        ->end()
                    ->end()
                ->end() // profiler
            ->end()
        ;
    }

    #[Override]
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        if (!isset($config['connection'])) {
            throw new \InvalidArgumentException('The "connection" configuration is required.');
        }

        $profilerMode = ProfilerMode::from($config['profiler']['mode'] ?? 'regular');

        $container
            ->parameters()
            ->set('access.connection_driver', $config['connection']['driver'] ?? 'mysql')
            ->set('access.connection_host', $config['connection']['host'] ?? 'localhost')
            ->set('access.connection_port', $config['connection']['port'] ?? 3306)
            ->set('access.connection_database', $config['connection']['database'] ?? null)
            ->set('access.connection_username', $config['connection']['username'] ?? null)
            ->set('access.connection_password', $config['connection']['password'] ?? null)
            ->set('access.profiler_mode', $config['profiler']['mode'] ?? 'regular');

        $container
            ->services()
            ->set(Database::class, AccessDatabase::class)
            ->args([
                param('access.connection_driver'),
                param('access.connection_host'),
                param('access.connection_port'),
                param('access.connection_database'),
                param('access.connection_username'),
                param('access.connection_password'),
                service(Stopwatch::class),
                service(LoggerInterface::class),
                service(ClockInterface::class),
                $profilerMode,
            ])
            ->public();
    }
}
