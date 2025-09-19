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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'access:migrations:generate')]
final class GenerateCommand
{
    public function __construct(
        private string $migrationsNamespace,
        private string $migrationsPath,
    ) {}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Description of the migration')] ?string $description = null,
    ): int {
        $io = new SymfonyStyle($input, $output);

        while ($description === null) {
            /** @var string|null $description */
            $description = $io->ask('Description of the migration');
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->migrationsPath, 0644);

        $className = sprintf('Version%s', (new \DateTime())->format('YmdHis'));

        $filePath = sprintf('%s/%s.php', $this->migrationsPath, $className);

        $description = var_export($description, true);

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$this->migrationsNamespace};

        use Access\Migrations\Migration;
        use Access\Migrations\SchemaChanges;

        class {$className} extends Migration
        {
            public function getDescription(): string
            {
                return {$description};
            }

            public function constructive(SchemaChanges \$schemaChanges): void
            {
            }

            public function revertConstructive(SchemaChanges \$schemaChanges): void
            {
            }
        }
        PHP;

        $filesystem->dumpFile($filePath, $content);

        $io->success(
            sprintf(
                'Migration %s\\%s generated at %s',
                $this->migrationsNamespace,
                $className,
                $filePath,
            ),
        );

        return Command::SUCCESS;
    }
}
