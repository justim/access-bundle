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

use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Pre-configured Access database factory
 *
 * @psalm-api
 */
class AccessDatabaseFactory
{
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
    ) {}

    public function create(): AccessDatabase
    {
        return new AccessDatabase(
            $this->driver,
            $this->host,
            $this->port,
            $this->databaseName,
            $this->username,
            $this->password,
            $this->stopwatch,
            $this->logger,
            $this->clock,
            $this->profilerMode,
        );
    }
}
