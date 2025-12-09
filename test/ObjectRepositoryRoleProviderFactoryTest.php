<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Laminas\ServiceManager\ServiceManager;
use Lmc\Rbac\Options\ModuleOptions;
use Lmc\Rbac\Role\Doctrine\Exception\InvalidConfigurationException;
use Lmc\Rbac\Role\Doctrine\ObjectRepositoryRoleProvider;
use Lmc\Rbac\Role\Doctrine\ObjectRepositoryRoleProviderFactory;
use LmcTest\Rbac\Role\Doctrine\Asset\RoleFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(ObjectRepositoryRoleProviderFactory::class)]
final class ObjectRepositoryRoleProviderFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryUsingObjectRepository(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'object_repository'  => 'RoleObjectRepository',
                    'role_factory'       => RoleFactory::class,
                ],
            ],
        ]));
        $container->setService('RoleObjectRepository', $this->getMockBuilder(ObjectRepository::class)->getMock());
        $container->setService(RoleFactory::class, new RoleFactory());

        $roleProvider = (new ObjectRepositoryRoleProviderFactory())($container);
        $this->assertInstanceOf(ObjectRepositoryRoleProvider::class, $roleProvider);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryUsingObjectManager(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'object_manager'     => 'ObjectManager',
                    'class_name'         => 'Role',
                    'role_factory'       => RoleFactory::class,
                ],
            ],
        ]));
        $objectManager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with('Role')
            ->willReturn($this->getMockBuilder(ObjectRepository::class)->getMock());

        $container->setService('ObjectManager', $objectManager);
        $container->setService(RoleFactory::class, new RoleFactory());

        $roleProvider = (new ObjectRepositoryRoleProviderFactory())($container);
        $this->assertInstanceOf(ObjectRepositoryRoleProvider::class, $roleProvider);
    }

    /**
     * This is required due to the fact that the ServiceManager catches ALL exceptions and throws it's own...
     */
    public function testThrowExceptionIfNoRoleNamePropertyIsSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "role_name_property" option is missing');

        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [],
            ],
        ]));
        (new ObjectRepositoryRoleProviderFactory())($container);
    }

    public function testThrowExceptionIfNoObjectManagerNorObjectRepositoryIsSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('No "object_repository" or no "object_manager" option was found while creating '
            . 'the object repository role provider.');
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'role_factory'       => RoleFactory::class,
                ],
            ],
        ]));
        $container->setService(RoleFactory::class, new RoleFactory());
        (new ObjectRepositoryRoleProviderFactory())($container);
    }

    public function testThrowExceptionIfNoRoleFactory(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                ],
            ],
        ]));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "role_factory" option is missing');
        (new ObjectRepositoryRoleProviderFactory())($container);
    }

    public function testThrowExceptionIfNoRoleFactoryInContainer(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'role_factory'       => 'NonExistingRoleFactory',
                ],
            ],
        ]));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "role_factory" does not map to service');
        (new ObjectRepositoryRoleProviderFactory())($container);
    }

    public function testThrowExceptionIfNoObjectRepositoryInContainer(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'object_repository'  => 'NonExistingObjectRepository',
                    'role_factory'       => RoleFactory::class,
                ],
            ],
        ]));
        $container->setService(RoleFactory::class, new RoleFactory());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "object_repository" does not map to service');
        (new ObjectRepositoryRoleProviderFactory())($container);
    }

    public function testThrowExceptionIfNoObjectManagerInContainer(): void
    {
        $container = new ServiceManager();
        $container->setService(ModuleOptions::class, new ModuleOptions([
            'role_provider' => [
                ObjectRepositoryRoleProvider::class => [
                    'role_name_property' => 'name',
                    'role_factory'       => RoleFactory::class,
                    'object_manager'     => 'NonExistingObjectManager',
                    'class_name'         => 'Role',
                ],
            ],
        ]));
        $container->setService(RoleFactory::class, new RoleFactory());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "object_manager" does not map to service');
        (new ObjectRepositoryRoleProviderFactory())($container);
    }
}
