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
use Access\Driver\DriverInterface;
use Access\EntityProvider;
use Access\Profiler;
use Access\Profiler\BlackholeProfiler;
use Access\Query;
use Access\Query\Raw;
use Access\StatementPool;
use DateTimeImmutable;
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
    // we go the lazy route
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private \PDO $connection;

    private bool $isConnected = false;

    public function __construct(
        private string $driver,
        private string $host,
        private int $port,
        private string $databaseName,
        private string $username,
        #[SensitiveParameter] private ?string $password,
        private Stopwatch $stopwatch,
        private LoggerInterface $logger,
        private ClockInterface $clock,
        private ProfilerMode $profilerMode,
    ) {
        // the parent constructor is not called here because we want to delay the connection
        // the initialization of the bundle should succeed without a database connection,
        // like directly after running `composer require access-bundle`
    }

    private function setup(): void
    {
        if ($this->isConnected) {
            return;
        }

        $this->connection = $this->connect();

        $profiler = match ($this->profilerMode) {
            ProfilerMode::Regular => new AccessProfiler($this->stopwatch, $this->logger),
            ProfilerMode::Blackhole => new BlackholeProfiler(),
        };

        parent::__construct($this->connection, $profiler, $this->clock);

        $this->isConnected = true;
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
    public function getConnection(): \PDO
    {
        $this->setup();

        return parent::getConnection();
    }

    #[Override]
    public function getStatementPool(): StatementPool
    {
        $this->setup();

        return parent::getStatementPool();
    }

    #[Override]
    public function getProfiler(): Profiler
    {
        $this->setup();

        return parent::getProfiler();
    }

    #[Override]
    public function getDriver(): DriverInterface
    {
        $this->setup();

        return parent::getDriver();
    }

    #[Override]
    public function selectWithEntityProvider(
        EntityProvider $entityProvider,
        Query\Select $query,
    ): \Generator {
        $this->setup();

        return parent::selectWithEntityProvider($entityProvider, $query);
    }

    #[Override]
    public function now(): DateTimeImmutable
    {
        $this->setup();

        return parent::now();
    }

    #[Override]
    public function withIncludeSoftDeleted(bool $includeSoftDeleted): static
    {
        $this->setup();

        return parent::withIncludeSoftDeleted($includeSoftDeleted);
    }

    #[Override]
    public function reset(): void
    {
        $this->getProfiler()->clear();

        $this->keepAlive();
    }

    private function keepAlive(): void
    {
        try {
            $this->query(new Raw('SELECT 1'));
        } catch (Exception $e) {
            if (mb_stripos($e->getMessage(), '2006 MySQL server has gone away') !== false) {
                $this->logger->info('Connection has gone away, reconnecting..');

                $connection = $this->connect();
                $this->setConnection($connection);
                return;
            }

            throw $e;
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
