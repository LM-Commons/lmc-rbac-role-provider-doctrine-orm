<?php

declare(strict_types=1);

namespace Lmc\Rbac\Role\Doctrine;

use Doctrine\Persistence\ObjectManager;
use Lmc\Rbac\Options\ModuleOptions;
use Lmc\Rbac\Role\Doctrine\Exception\InvalidConfigurationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Factory used to create an object repository role provider
 */
final class ObjectRepositoryRoleProviderFactory
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ObjectRepositoryRoleProvider
    {
        /** @var ModuleOptions $moduleOptions */
        $moduleOptions = $container->get(ModuleOptions::class);

        /** @var string[]|class-string[] $options */
        $options = $moduleOptions->getRoleProvider()[ObjectRepositoryRoleProvider::class] ?? [];

        if (! isset($options['role_name_property'])) {
            throw new InvalidConfigurationException('The "role_name_property" option is missing');
        }

        if (! isset($options['role_factory'])) {
            throw new InvalidConfigurationException('The "role_factory" option is missing');
        } elseif (! $container->has($options['role_factory'])) {
            throw new InvalidConfigurationException('The "role_factory" does not map to service');
        }

        if (isset($options['object_repository'])) {
            if ($container->has($options['object_repository'])) {
                /** @psalm-suppress MixedArgument */
                return new ObjectRepositoryRoleProvider(
                    $container->get($options['object_repository']),
                    $options['role_name_property'],
                    $container->get($options['role_factory'])
                );
            } else {
                throw new InvalidConfigurationException('The "object_repository" does not map to service');
            }
        }

        if (isset($options['object_manager'], $options['class_name'])) {
            if (! $container->has($options['object_manager'])) {
                throw new InvalidConfigurationException('The "object_manager" does not map to service');
            }
            /** @var ObjectManager $objectManager */
            $objectManager = $container->get($options['object_manager']);
            /** @psalm-suppress  ArgumentTypeCoercion */
            $objectRepository = $objectManager->getRepository($options['class_name']);

            /** @psalm-suppress MixedArgument */
            return new ObjectRepositoryRoleProvider(
                $objectRepository,
                $options['role_name_property'],
                $container->get($options['role_factory'])
            );
        }

        throw new InvalidConfigurationException(
            'No "object_repository" or no "object_manager" option was found while creating the object '
            . 'repository role provider.'
        );
    }
}
