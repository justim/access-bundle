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

use Access\DebugQuery;
use Access\Profiler;
use Access\Query;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * {@inheritdoc}
 */
final class AccessProfiler extends Profiler
{
    /**
     * {@inheritdoc}
     */
    public function __construct(private Stopwatch $stopwatch, private LoggerInterface $logger) {}

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function createForQuery(Query $query): QueryProfileWrapper
    {
        $debugQuery = new DebugQuery($query);
        $this->logger->debug('Running query {query}', [
            'query' =>
                $debugQuery->toRunnableQuery() ??
                sprintf('(dropped "%s" query)', get_class($query)),
        ]);

        $queryProfile = parent::createForQuery($query);
        $queryProfileWrapper = new QueryProfileWrapper($this->stopwatch, $queryProfile);

        return $queryProfileWrapper;
    }
}
