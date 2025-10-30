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
use Access\AccessBundle\AccessDatabaseFactory;
use Access\AccessBundle\DataCollector\AccessDataCollector;
use Access\AccessBundle\Form\Type\EntityType;
use Access\AccessBundle\Migrations\Command\GenerateCommand;
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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Stopwatch\Stopwatch;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container
        ->services()
        ->set(AccessDatabaseFactory::class)
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
        ->lazy()
        ->public();

    $container
        ->services()
        ->set(Database::class, AccessDatabase::class)
        ->factory([new Reference(AccessDatabaseFactory::class), 'create'])
        ->lazy()
        ->public();

    $container
        ->services()
        ->set(AccessDataCollector::class)
        ->args([service(Database::class)])
        ->tag('data_collector', [
            'template' => '@Access/access-data-collector.html.twig',
            'id' => 'access',
        ])
        ->alias('access.access_data_collector', AccessDataCollector::class);

    $container
        ->services()
        ->set('access.twig_extension', TwigAccessExtension::class)
        ->tag('twig.extension');

    $container
        ->services()
        ->set(AccessValueResolver::class)
        ->args([service(Database::class)])
        ->tag('controller.argument_value_resolver', ['priority' => 101])
        ->alias('access.value_resolver.access_value_resolver', AccessValueResolver::class);

    $container
        ->services()
        ->set(EntityType::class)
        ->args([service(Database::class)])
        ->tag('form.type')
        ->alias('access.form_type.access_entity_type', EntityType::class);

    $container
        ->services()
        ->set(InitCommand::class)
        ->args([service(Database::class), param('access.migrations.migration_entity_class')])
        ->tag('console.command')
        ->set('access.migrations.init_command', InitCommand::class);

    $container
        ->services()
        ->set(RunCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command')
        ->set('access.migrations.run_command', RunCommand::class);

    $container
        ->services()
        ->set(GenerateCommand::class)
        ->args([
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migrations_path'),
        ])
        ->tag('console.command')
        ->set('access.migrations.generate_command', GenerateCommand::class);

    $container
        ->services()
        ->set(RunAllCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migrations_path'),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command')
        ->set('access.migrations.run_all_command', RunAllCommand::class);

    $container
        ->services()
        ->set(RevertCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command')
        ->set('access.migrations.revert_command', RevertCommand::class);

    $container
        ->services()
        ->set(RedoCommand::class)
        ->args([
            service('service_container'),
            service(Database::class),
            param('access.migrations.migrations_namespace'),
            param('access.migrations.migration_entity_class'),
        ])
        ->tag('console.command')
        ->set('access.migrations.redo_command', RedoCommand::class);
};
