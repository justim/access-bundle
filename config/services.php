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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Access\AccessBundle\AccessDatabase;
use Access\AccessBundle\DataCollector\AccessDataCollector;
use Access\AccessBundle\Migrations\Command\InitCommand;
use Access\AccessBundle\Migrations\Command\RedoCommand;
use Access\AccessBundle\Migrations\Command\RevertCommand;
use Access\AccessBundle\Migrations\Command\RunAllCommand;
use Access\AccessBundle\Migrations\Command\RunCommand;
use Access\AccessBundle\Twig\AccessExtension as TwigAccessExtension;
use Access\AccessBundle\ValueResolver\AccessValueResolver;
use Access\Database;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Stopwatch\Stopwatch;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
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
            param('access.profiler_mode'),
        ])
        ->public();

    $container
        ->services()
        ->set('access.access_data_collector', AccessDataCollector::class)
        ->args([service(Database::class)])
        ->tag('data_collector', [
            'template' => '@Access/access-data-collector.html.twig',
            'id' => 'access',
        ]);

    $container
        ->services()
        ->set('access.twig_extension', TwigAccessExtension::class)
        ->tag('twig.extension');

    $container
        ->services()
        ->set('access.value_resolver.access_value_resolver', AccessValueResolver::class)
        ->args([service(Database::class)])
        ->tag('controller.argument_value_resolver', ['priority' => 101]);

    $container
        ->services()
        ->set('access.migrations.init_command', InitCommand::class)
        ->args([service(Database::class), param('access.migrations.migration_entity_class')])
        ->tag('console.command');

    $container
        ->services()
        ->set('access.migrations.run_command', RunCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command');

    $container
        ->services()
        ->set('access.migrations.run_all_command', RunAllCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migrations_path'),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command');

    $container
        ->services()
        ->set('access.migrations.revert_command', RevertCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command');

    $container
        ->services()
        ->set('access.migrations.redo_command', RedoCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command');
};
