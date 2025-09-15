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
use Access\Migrations\MigrationEntity;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'access:migrations:redo')]
final class RedoCommand
{
    /**
     * @param class-string<MigrationEntity> $migrationEntity
     */
    public function __construct(private Database $db, private string $migrationEntity) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Version to migrate to')] string $version,
        #[Option] bool $dryRun = false,
        #[Option(description: 'Exectute destructive part of migration')] bool $destructive = false,
    ): int {
        $revert = new RevertCommand($this->db, $this->migrationEntity);
        $code = $revert($input, $output, $version, $dryRun, $destructive);

        if ($code !== Command::SUCCESS) {
            return $code;
        }

        $run = new RunCommand($this->db, $this->migrationEntity);
        return $run($input, $output, $version, $dryRun, $destructive);
    }
}
