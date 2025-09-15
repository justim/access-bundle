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
use Access\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'access:migrations:init')]
final class InitCommand
{
    /**
     * @param class-string<MigrationEntity> $migrationEntity
     */
    public function __construct(private Database $db, private string $migrationEntity) {}

    public function __invoke(): int
    {
        $migrator = new Migrator($this->db, $this->migrationEntity);
        $migrator->init();

        return Command::SUCCESS;
    }
}
