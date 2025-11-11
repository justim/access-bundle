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

namespace Access\AccessBundle\ValueResolver;

use Attribute;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class AccessCursorMapQueryParameter extends ValueResolver
{
    public function __construct(private readonly ?int $pageSize = null)
    {
        parent::__construct(AccessCursorValueResolver::class);
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }
}
