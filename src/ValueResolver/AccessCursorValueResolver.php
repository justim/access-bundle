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

use Access\Query\Cursor\CurrentIdsCursor;
use Access\Query\Cursor\Cursor;
use Access\Query\Cursor\MaxValueCursor;
use Access\Query\Cursor\MinValueCursor;
use Access\Query\Cursor\PageCursor;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class AccessCursorValueResolver implements ValueResolverInterface
{
    private const int DEFAULT_PAGE_SIZE = 50;

    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $className = $argument->getType();

        if ($className === null) {
            return [];
        }

        if (!is_subclass_of($className, Cursor::class, true) || $className === Cursor::class) {
            return [];
        }

        $currentIds = $request->request->get('currentIds');
        $page = $request->query->get('page');
        $maxValue = $request->query->get('maxValue');
        $minValue = $request->query->get('minValue');

        $pageSize = $this->getPageSize($argument);
        $cursor = new PageCursor(pageSize: $pageSize);

        if ($currentIds !== null || $className === CurrentIdsCursor::class) {
            $cursor = new CurrentIdsCursor([], $pageSize);

            if (is_string($currentIds) && !empty($currentIds)) {
                if (!preg_match('/^[0-9,]+$/', $currentIds)) {
                    throw new BadRequestHttpException('Invalid currentIds parameter');
                }

                $currentIds = array_map('intval', explode(',', $currentIds));
                $cursor->setCurrentIds($currentIds);
            }
        } elseif ($page !== null || $className === PageCursor::class) {
            $cursor = new PageCursor(1, $pageSize);

            if ($page !== null) {
                if (!ctype_digit($page)) {
                    throw new BadRequestHttpException('Invalid page parameter');
                }

                $cursor->setPage((int) $page);
            }
        } elseif ($maxValue !== null || $className === MaxValueCursor::class) {
            $cursor = new MaxValueCursor(pageSize: $pageSize);

            if ($maxValue !== null) {
                if (!ctype_digit($maxValue)) {
                    throw new BadRequestHttpException('Invalid max value parameter');
                }

                $cursor->setOffset((int) $maxValue);
            }
        } elseif ($minValue !== null || $className === MinValueCursor::class) {
            $cursor = new MinValueCursor(pageSize: $pageSize);

            if ($minValue !== null) {
                if (!ctype_digit($minValue)) {
                    throw new BadRequestHttpException('Invalid min value parameter');
                }

                $cursor->setOffset((int) $minValue);
            }
        }

        yield $cursor;
    }

    private function getPageSize(ArgumentMetadata $argument): int
    {
        $attributes = $argument->getAttributesOfType(AccessCursorMapQueryParameter::class);

        /** @var AccessCursorMapQueryParameter $attribute */
        foreach ($attributes as $attribute) {
            $pageSize = $attribute->getPageSize();

            if ($pageSize !== null) {
                return $pageSize;
            }
        }

        return self::DEFAULT_PAGE_SIZE;
    }
}
