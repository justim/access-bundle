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

namespace Access\AccessBundle\DependencyInjection\Compiler;

use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class AutowireMigrationsPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $parameters = $container->getParameterBag();

        /** @var string $namespace */
        $namespace = $parameters->resolveValue(
            $container->getParameter('access.migrations.migrations_namespace'),
        );

        /** @var string $path */
        $path = $parameters->resolveValue(
            $container->getParameter('access.migrations.migrations_path'),
        );

        if (!is_dir($path)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $className = $this->getClassNameFromFile($namespace, $relativePath);

            if (!$className || !class_exists($className)) {
                continue;
            }

            if ($container->hasDefinition($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            $definition = $container->register($className, $className);
            $definition->setAutowired(true);
            $definition->setPublic(true);
        }
    }

    private function getClassNameFromFile(string $namespace, string $relativePath): string
    {
        $classPath = substr($relativePath, 0, -4);
        $classPath = str_replace(['/', '\\'], '\\', $classPath);

        return $namespace . '\\' . $classPath;
    }
}
