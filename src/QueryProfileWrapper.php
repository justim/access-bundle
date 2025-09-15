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

use Access\Profiler\QueryProfile;
use Access\Query;
use Override;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Wrapper for Access query profile
 *
 * Passes all calls to wrapped query profile, with addition of stopwatch calls
 * for additional timings
 */
final class QueryProfileWrapper extends QueryProfile
{
    private const string STOPWATCH_CATEGORY = 'access';
    private const string STOPWATCH_NAME_PREPARE = 'access.prepare';
    private const string STOPWATCH_NAME_EXECUTE = 'access.execute';
    private const string STOPWATCH_NAME_HYDRATE = 'access.hydrate';

    /**
     * {@inheritdoc}
     */
    public function __construct(private Stopwatch $stopwatch, private QueryProfile $queryProfile) {}

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getQuery(): Query
    {
        return $this->queryProfile->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function startPrepare(): void
    {
        $this->queryProfile->startPrepare();

        $this->stopwatch->start(self::STOPWATCH_NAME_PREPARE, self::STOPWATCH_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function endPrepare(): void
    {
        $this->queryProfile->endPrepare();

        $this->stopwatch->stop(self::STOPWATCH_NAME_PREPARE);
    }

    #[Override]
    public function getPrepareDuration(): float
    {
        return $this->queryProfile->getPrepareDuration();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function startExecute(): void
    {
        $this->queryProfile->startExecute();

        $this->stopwatch->start(self::STOPWATCH_NAME_EXECUTE, self::STOPWATCH_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function endExecute(): void
    {
        $this->queryProfile->endExecute();

        $this->stopwatch->stop(self::STOPWATCH_NAME_EXECUTE);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getExecuteDuration(): float
    {
        return $this->queryProfile->getExecuteDuration();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function startHydrate(): void
    {
        $this->queryProfile->startHydrate();

        $this->stopwatch->start(self::STOPWATCH_NAME_HYDRATE, self::STOPWATCH_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function endHydrate(): void
    {
        $this->queryProfile->endHydrate();

        $this->stopwatch->stop(self::STOPWATCH_NAME_HYDRATE);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getHydrateDuration(): float
    {
        return $this->queryProfile->getHydrateDuration();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function setNumberOfResults(null|int $numberOfResults): void
    {
        $this->queryProfile->setNumberOfResults($numberOfResults);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getNumberOfResults(): null|int
    {
        return $this->queryProfile->getNumberOfResults();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getTotalDuration(): float
    {
        return $this->queryProfile->getTotalDuration();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getTotalDurationWithHydrate(): float
    {
        return $this->queryProfile->getTotalDurationWithHydrate();
    }
}
