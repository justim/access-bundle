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

use Access\AccessBundle\DependencyInjection\AccessExtension;
use Access\AccessBundle\DependencyInjection\Compiler\AutowireMigrationsPass;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @psalm-api
 */
final class AccessBundle extends AbstractBundle
{
    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AccessExtension();
    }

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AutowireMigrationsPass());
    }
}
