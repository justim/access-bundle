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
use Access\DebugQuery;
use Access\Migrations\Checkpoint;
use Access\Migrations\SchemaChanges;
use Doctrine\SqlFormatter\CliHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SchemaChangesFormatter
{
    private SqlFormatter $sqlFormatter;

    public function __construct(private Database $db, private SymfonyStyle $io)
    {
        $this->sqlFormatter = new SqlFormatter(new CliHighlighter());
    }

    public function showQueries(
        SchemaChanges $changes,
        Checkpoint $checkpoint = new Checkpoint(),
    ): void {
        foreach ($changes->getQueries($checkpoint) as $query) {
            $debugQuery = new DebugQuery($query);
            $debugQuery = $debugQuery->toRunnableQuery($this->db->getDriver());

            if ($debugQuery === null) {
                $this->io->writeln(sprintf('(dropped "%s" query)', get_class($query)));
            } else {
                $this->io->writeln($this->sqlFormatter->format($debugQuery));
            }
        }
    }
}
