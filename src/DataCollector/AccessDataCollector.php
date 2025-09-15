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

namespace Access\AccessBundle\DataCollector;

use Access\Database;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

final class AccessDataCollector extends DataCollector
{
    public function __construct(private Database $db) {}

    #[Override]
    public function collect(
        Request $request,
        Response $response,
        ?Throwable $exception = null,
    ): void {
        $this->data = $this->cloneVar($this->db->getProfiler()->export());
    }

    #[Override]
    public function reset(): void
    {
        $this->data = [];
    }

    #[Override]
    public function getName(): string
    {
        return 'access';
    }

    /**
     * @return float
     */
    public function getDuration()
    {
        return $this->data['duration'];
    }

    /**
     * @return float
     */
    public function getDurationWithHydrate()
    {
        return $this->data['durationWithHydrate'];
    }

    /**
     * @return array<array-key, array{sql: ?string, values: mixed[], runnableSql: ?string, duration: float, durationWithHydrate: float, numberOfResults: ?int}>
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }
}
