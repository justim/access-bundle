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

use Access\AccessBundle\ProfilerMode;
use Access\Migrations\MigrationEntity;
use Override;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    #[Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('access');

        $rootNode = $treeBuilder->getRootNode();

        /**
         * Method does exist, but psalm cannot see it due to "magical" nature of the class
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress MixedMethodCall
         */
        // prettier-ignore
        $rootNode
            ->children()
                ->arrayNode('connection')
                    ->addDefaultsIfNotSet()
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
                            ->defaultValue('website')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('username')
                            ->info('The username to connect with')
                            ->defaultValue('mysql')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('password')
                            ->info('The password to connect with')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('profiler')
                    ->addDefaultsIfNotSet()
                    ->info('Database profiler settings')
                    ->children()
                        ->enumNode('mode')
                            ->info('The profiler mode')
                            ->enumFqcn(ProfilerMode::class)
                            ->defaultValue(ProfilerMode::Regular)
                        ->end()
                    ->end()
                ->end() // profiler
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->info('Database migration settings')
                    ->children()
                        ->scalarNode('migration_entity_class')
                            ->defaultValue(MigrationEntity::class)
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('migrations_namespace')
                            ->defaultValue('App\\Migrations')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('migrations_path')
                            ->defaultValue('%kernel.project_dir%/migrations')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
        ;

        return $treeBuilder;
    }
}
