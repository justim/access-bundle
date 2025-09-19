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

namespace Access\AccessBundle\Migrations\Command;

use Access\Database;
use Access\Migrations\Exception\MigrationFailedException;
use Access\Migrations\Migration;
use Access\Migrations\MigrationEntity;
use Access\Migrations\Migrator;
use Access\Migrations\SchemaChanges;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[
    AsCommand(
        name: 'access:migrations:run-all',
        description: 'Run all available migrations (constructive only)',
    ),
]
final class RunAllCommand
{
    /**
     * @param class-string<MigrationEntity> $migrationEntity
     */
    public function __construct(
        private ContainerInterface $container,
        private Database $db,
        private string $migrationsNamespace,
        private string $migrationsPath,
        private string $migrationEntity,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option] bool $dryRun = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $migrator = new Migrator($this->db, $this->migrationEntity);
        $migrator->init();
        $migrator->setDryRun($dryRun);

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->migrationsPath, 0644);

        $migrationFiles = scandir($this->migrationsPath);

        if ($migrationFiles === false) {
            $io->error(sprintf('Failed to read migrations directory: %s', $this->migrationsPath));
            return Command::FAILURE;
        }

        $migrations = [];

        foreach ($migrationFiles as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            $migration = sprintf(
                '%s\\%s',
                $this->migrationsNamespace,
                Path::getFilenameWithoutExtension($file),
            );

            if (!class_exists($migration)) {
                $io->error(
                    sprintf(
                        'Migration class %s does not exist, is it included in the autoload config?',
                        $migration,
                    ),
                );

                return Command::FAILURE;
            }

            if (!is_subclass_of($migration, Migration::class)) {
                $io->error(
                    sprintf('Migration class %s does not extend %s', $migration, Migration::class),
                );

                return Command::FAILURE;
            }

            $migrations[] = $migration;
        }

        if ($migrations === []) {
            $io->warning('No migrations found');

            return Command::SUCCESS;
        }

        usort($migrations, fn(string $a, string $b) => strcmp($a, $b));

        $formatter = new SchemaChangesFormatter($this->db, $io);

        foreach ($migrations as $version) {
            $io->section(sprintf('Running migration %s', $version));

            /** @var Migration $migration */
            $migration = $this->container->get($version);

            $description = $migration->getDescription();

            if (!empty($description)) {
                $io->text($description);
            }

            try {
                $result = $migrator->constructive($migration);

                if ($result->isSuccess()) {
                    $changes = $result->getChanges();
                    assert($changes instanceof SchemaChanges);
                    $formatter->showQueries($changes);

                    if ($dryRun) {
                        $io->note('Dry run mode - no changes were applied to the database');
                    }

                    $io->success(
                        sprintf('Migrated constructive part of %s successfully', $version),
                    );
                } elseif ($result->isWarning()) {
                    $io->warning(
                        sprintf('Migration %s skipped: %s', $version, $result->getMessage()),
                    );
                } else {
                    $io->error(sprintf('Migration %s failed: %s', $version, $result->getMessage()));

                    return Command::FAILURE;
                }
            } catch (MigrationFailedException $e) {
                $changes = $e->getChanges();
                assert($changes instanceof SchemaChanges);
                $formatter->showQueries($changes);
                throw $e;
            }
        }

        return Command::SUCCESS;
    }
}
