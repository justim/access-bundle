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

use Access\AccessBundle\Exception\InvalidConnectionException;
use Access\Database;
use Access\Exception\ConnectionGoneException;
use Access\Profiler\BlackholeProfiler;
use Access\Query;
use Access\Transaction;
use Exception;
use Monolog\Attribute\WithMonologChannel;
use Override;
use PDO;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Pre-configured Access database
 *
 * @psalm-api
 */
#[WithMonologChannel('access')]
class AccessDatabase extends Database implements ResetInterface
{
    private \PDO $connection;

    public function __construct(
        private string $driver,
        private string $host,
        private int $port,
        private string $databaseName,
        private string $username,
        #[SensitiveParameter] private ?string $password,
        Stopwatch $stopwatch,
        private LoggerInterface $logger,
        ClockInterface $clock,
        ProfilerMode $profilerMode,
    ) {
        $this->connection = $this->connect();

        $profiler = match ($profilerMode) {
            ProfilerMode::Regular => new AccessProfiler($stopwatch, $logger),
            ProfilerMode::Blackhole => new BlackholeProfiler(),
        };

        parent::__construct($this->connection, $profiler, $clock);
    }

    private function connect(): \PDO
    {
        $connectionString = $this->buildConnectionString(
            $this->driver,
            $this->host,
            $this->port,
            $this->databaseName,
        );

        try {
            return new \PDO($connectionString, $this->username, $this->password);
        } catch (\Exception $e) {
            throw new InvalidConnectionException('Invalid connection: ' . $e->getMessage(), 0, $e);
        }
    }

    #[Override]
    public function reset(): void
    {
        $this->getProfiler()->clear();
    }

    private function reconnect(): void
    {
        $connection = $this->connect();
        $this->setConnection($connection);
    }

    #[Override]
    public function beginTransaction(): Transaction
    {
        try {
            return parent::beginTransaction();
        } catch (ConnectionGoneException) {
            $this->logger->info(
                'Connection has gone away when beginning transaction, reconnecting..',
            );
            $this->reconnect();

            return $this->beginTransaction();
        }
    }

    #[Override]
    public function executeStatement(Query $query): \Generator
    {
        try {
            $generator = parent::executeStatement($query);

            yield from $generator;

            return $generator->getReturn();
        } catch (ConnectionGoneException) {
            $this->logger->info('Connection has gone away during query, reconnecting..');
            $this->reconnect();

            return $this->executeStatement($query);
        }
    }

    private function buildConnectionString(
        string $driver,
        string $host,
        int $port,
        string $databaseName,
    ): string {
        if ($driver === 'mysql') {
            return sprintf('mysql:host=%s;port=%d;dbname=%s', $host, $port, $databaseName);
        } elseif ($driver === 'sqlite') {
            return sprintf('sqlite:%s', $databaseName);
        }

        throw new \InvalidArgumentException('Unsupported database driver: ' . $driver);
    }
}
