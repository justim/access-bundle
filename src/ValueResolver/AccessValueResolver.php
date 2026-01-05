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

use Access\Database;
use Access\Entity;
use Access\Exception;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessValueResolver implements ValueResolverInterface
{
    public function __construct(private Database $db) {}

    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $className = $argument->getType();

        if ($className === null) {
            return [];
        }

        try {
            // does an assertion on the class name and throws when invalid
            Database::assertValidEntityClass($className);
        } catch (Exception) {
            return [];
        }

        $errorMessage = sprintf('Could not load "%s" entity', $className);

        $id = $this->getId($request, $argument);

        /** @var class-string<Entity> $className */
        $repo = $this->db->getRepository($className);

        if ($id === null) {
            // only allow nullable arguments for query params, regular attributes must be non-null
            if ($this->allowQueryParam($argument) && $argument->isNullable()) {
                yield null;
                return;
            }

            throw new NotFoundHttpException($errorMessage);
        } elseif (is_array($id)) {
            $entity = $repo->findOneBy($id);
        } else {
            $entity = $repo->findOne($id);
        }

        if ($entity === null) {
            throw new NotFoundHttpException($errorMessage);
        }

        yield $entity;
    }

    private function getId(Request $request, ArgumentMetadata $argument): null|array|int
    {
        $attributeName = $argument->getName();

        $validateId = function (mixed $id): null|int {
            if (ctype_digit((string) $id)) {
                return (int) $id;
            }

            return null;
        };

        if ($request->attributes->has($attributeName)) {
            /** @var mixed $value */
            $value = $request->attributes->get($attributeName);

            /** @var array<string, string> $routeMapping */
            $routeMapping = $request->attributes->get('_route_mapping') ?? [];

            foreach ($routeMapping as $routeMappingParameterName => $routeMappingAttributeName) {
                if ($attributeName === $routeMappingAttributeName) {
                    return [$routeMappingParameterName => $value];
                }
            }

            if (is_array($value)) {
                return $value;
            } else {
                return $validateId($value);
            }
        }

        if ($this->allowQueryParam($argument) && $request->query->has($attributeName)) {
            $value = $request->query->get($attributeName);

            return $validateId($value);
        }

        $attributeName = $attributeName . 'Id';

        if ($request->attributes->has($attributeName)) {
            return $validateId($request->attributes->get($attributeName));
        }

        if ($this->allowQueryParam($argument) && $request->query->has($attributeName)) {
            return $validateId($request->query->get($attributeName));
        }

        return null;
    }

    private function allowQueryParam(ArgumentMetadata $argument): bool
    {
        return count($argument->getAttributesOfType(AccessMapQueryParameter::class)) > 0;
    }
}
