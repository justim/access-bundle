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
use Access\Migrations\Checkpoint;
use Access\Migrations\Exception\MigrationFailedException;
use Access\Migrations\Migration;
use Access\Migrations\Migrator;
use Access\Migrations\SchemaChanges;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Access\Migrations\MigrationEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'access:migrations:revert')]
final class RevertCommand
{
    /**
     * @param class-string<MigrationEntity> $migrationEntity
     */
    public function __construct(private Database $db, private string $migrationEntity) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Version to revert')] string $version,
        #[Option] bool $dryRun = false,
        #[Option(description: 'Revert destructive part of migration')] bool $destructive = false,
        #[Option(description: 'Start reverting migration from checkpoint')] int $checkpoint = 0,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if (!is_subclass_of($version, Migration::class)) {
            $io->error(sprintf('Class %s not found or is not a valid migration', $version));
            return Command::FAILURE;
        }

        $migrator = new Migrator($this->db, $this->migrationEntity);
        $migrator->init();
        $migrator->setDryRun(true);

        $io->section(sprintf('Reverting migration %s', $version));

        $initialCheckpoint = new Checkpoint($checkpoint);

        if ($initialCheckpoint->getStep() > 0) {
            $io->note(
                sprintf('Resuming reverting from checkpoint %d', $initialCheckpoint->getStep()),
            );
        }

        /** @psalm-suppress UnsafeInstantiation */
        $migration = new $version();

        $formatter = new SchemaChangesFormatter($this->db, $io);

        try {
            if ($destructive) {
                $result = $migrator->revertDestructive($migration, $initialCheckpoint);
            } else {
                $result = $migrator->revertConstructive($migration, $initialCheckpoint);
            }

            if ($result->isSuccess()) {
                $changes = $result->getChanges();
                assert($changes instanceof SchemaChanges);
                $formatter->showQueries($changes, $initialCheckpoint);

                if ($dryRun) {
                    $io->note('Dry run mode - no changes were applied to the database');
                } else {
                    if ($io->confirm('Apply these changes to the database?', false) === false) {
                        $io->warning('Reverting migration cancelled');
                        return Command::FAILURE;
                    }

                    $migrator->setDryRun(false);

                    if ($destructive) {
                        $migrator->revertDestructive($migration, $initialCheckpoint);
                    } else {
                        $migrator->revertConstructive($migration, $initialCheckpoint);
                    }

                    // no need to check result again, if it fails an exception will be thrown
                }

                $io->success(sprintf('Reverted %s successfully', $version));
            } elseif ($result->isWarning()) {
                $io->warning(sprintf('Reverting %s skipped: %s', $version, $result->getMessage()));
            } else {
                $io->error(sprintf('Reverting %s failed: %s', $version, $result->getMessage()));
                return Command::FAILURE;
            }
        } catch (MigrationFailedException $e) {
            $checkpoint = $e->getCheckpoint();

            $io->info(
                sprintf(
                    'Reverting migration failed at checkpoint %d, pass the checkpoint with `--checkpoint=%1$d` to continue',
                    $checkpoint->getStep(),
                ),
            );

            throw $e;
        }

        return Command::SUCCESS;
    }
}
