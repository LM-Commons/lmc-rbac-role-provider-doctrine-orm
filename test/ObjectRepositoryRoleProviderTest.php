<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine;

use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Generator;
use Laminas\Permissions\Rbac\Role;
use Laminas\Permissions\Rbac\RoleInterface;
use Lmc\Rbac\Role\Doctrine\ObjectRepositoryRoleProvider;
use Lmc\Rbac\Role\RoleFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function count;
use function is_array;
use function sys_get_temp_dir;

use const PHP_VERSION_ID;

#[CoversClass(ObjectRepositoryRoleProvider::class)]
final class ObjectRepositoryRoleProviderTest extends TestCase
{
    /** @return iterable<array-key, array<array-key, mixed>>  */
    public static function roleProvider(): array
    {
        return [
            'one-role-flat'            => [
                'rolesConfig'  => [
                    'admin',
                ],
                'rolesToCheck' => ['admin'],
            ],
            '2-roles-flat'             => [
                'rolesConfig'  => [
                    'admin',
                    'member',
                ],
                'rolesToCheck' => ['admin', 'member'],
            ],
            '3-roles-with-permissions' => [
                'rolesConfig'  => [
                    'admin' => [
                        'permissions' => ['manage', 'write', 'read'],
                    ],
                ],
                'rolesToCheck' => ['admin'],
            ],
            '4-roles-with-children'    => [
                'rolesConfig'  => [
                    'admin' => [
                        'children' => ['member'],
                    ],
                ],
                'rolesToCheck' => ['admin', 'member'],
            ],
        ];
    }

    public function testObjectRepositoryProviderGetRoles(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $memberRole       = new Role('member');
        $provider         = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );
        $result           = [$memberRole];

        $objectRepository->expects($this->once())->method('findBy')->willReturn($result);

        $this->assertEquals($result, $provider->getRoles(['member']));
    }

    public function testRoleCacheOnConsecutiveCalls(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $memberRole       = new Role('member');
        $provider         = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );
        $result           = [$memberRole];

        // note exactly once, consecutive call come from cache
        $objectRepository->expects($this->exactly(1))->method('findBy')->willReturn($result);

        $provider->getRoles(['member']);
        $provider->getRoles(['member']);
    }

    public function testClearRoleCache(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $memberRole       = new Role('member');
        $provider         = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );
        $result           = [$memberRole];

        // note exactly twice, as cache is cleared
        $objectRepository->expects($this->exactly(2))->method('findBy')->willReturn($result);

        $provider->getRoles(['member']);
        $provider->clearRoleCache();
        $provider->getRoles(['member']);
    }

    public function testRoleFactoryIsCalledIfAskedRoleIsNotFound(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $memberRole       = new Role('member');
        $roleFactory      = $this->createMock(RoleFactoryInterface::class);
        $roleFactory->expects($this->once())->method('createRole')
            ->with('guest')
            ->willReturn(new Role('guest'));
        $provider = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $roleFactory
        );

        $objectRepository->expects($this->once())->method('findBy')
            ->with(['name' => ['guest', 'member']])
            ->willReturn([$memberRole]);
        $provider->getRoles(['guest', 'member']);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[DataProvider('roleProvider')]
    public function testObjectRepositoryProviderForRole(array $rolesConfig, array $rolesToCheck): void
    {
        $objectManager = $this->getObjectManager();
        /**
         * @var string $name
         * @var array|string $roleConfig
         */
        foreach ($rolesConfig as $name => $roleConfig) {
            if (is_array($roleConfig)) {
                $role = new Asset\Role($name);
                if (isset($roleConfig['children'])) {
                    /** @var string $child */
                    foreach ($roleConfig['children'] as $child) {
                        $role->addChild(new Asset\Role($child));
                    }
                }
                if (isset($roleConfig['permissions'])) {
                    /** @var string $permission */
                    foreach ($roleConfig['permissions'] as $permission) {
                        $role->addPermission($permission);
                    }
                }
            } else {
                $role = new Asset\Role($roleConfig);
            }
            $objectManager->persist($role);
        }
        $objectManager->flush();

        $objectRepository             = $objectManager->getRepository(Asset\Role::class);
        $objectRepositoryRoleProvider = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );

        $roles = $objectRepositoryRoleProvider->getRoles($rolesToCheck);

        $this->assertIsArray($roles);
        $this->assertCount(count($rolesToCheck), $roles);

        $i = 0;
        foreach ($roles as $role) {
            $this->assertInstanceOf(RoleInterface::class, $role);
            $this->assertEquals($rolesToCheck[$i], $role->getName());
            $i++;
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testObjectRepositoryProviderForFlatRoleWithPermissions(): void
    {
        $objectManager = $this->getObjectManager();

        // Let's create a role
        $adminRole = new Asset\Role('admin');
        $adminRole->addPermission('manage');
        $adminRole->addPermission('write');
        $adminRole->addPermission('read');
        $objectManager->persist($adminRole);
        $objectManager->flush();

        $objectRepository = $objectManager->getRepository(Asset\Role::class);

        $objectRepositoryRoleProvider = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );

        // Get only the role
        $roles = $objectRepositoryRoleProvider->getRoles(['admin']);

        $this->assertCount(1, $roles);
        $this->assertIsArray($roles);

        $this->assertInstanceOf(RoleInterface::class, $roles[0]);
        $this->assertEquals('admin', $roles[0]->getName());
        $this->assertTrue($roles[0]->hasPermission('manage'));
        $this->assertTrue($roles[0]->hasPermission('read'));
        $this->assertTrue($roles[0]->hasPermission('write'));
        $this->assertFalse($roles[0]->hasPermission('foo'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testObjectRepositoryProviderForHierarchicalRole(): void
    {
        $objectManager = $this->getObjectManager();

        // Let's add some roles
        $guestRole = new Asset\Role('guest');
        $objectManager->persist($guestRole);

        $memberRole = new Asset\Role('member');
        $memberRole->addChild($guestRole);
        $objectManager->persist($memberRole);

        $adminRole = new Asset\Role('admin');
        $adminRole->addChild($memberRole);
        $objectManager->persist($adminRole);

        $objectManager->flush();

        $objectRepository = $objectManager->getRepository(Asset\Role::class);

        $objectRepositoryRoleProvider = new ObjectRepositoryRoleProvider(
            $objectRepository,
            'name',
            $this->createMock(RoleFactoryInterface::class)
        );

        // Get only the admin role
        $roles = $objectRepositoryRoleProvider->getRoles(['admin']);

        $this->assertCount(1, $roles);
        $this->assertIsArray($roles);

        $this->assertInstanceOf(RoleInterface::class, $roles[0]);
        $this->assertEquals('admin', $roles[0]->getName());

        $childRolesString = '';

        foreach ($this->flattenRoles($roles[0]->getChildren()) as $childRole) {
            $this->assertInstanceOf(RoleInterface::class, $childRole);
            $childRolesString .= $childRole->getName();
        }

        $this->assertEquals('memberguest', $childRolesString);
    }

    private function getObjectManager(): ObjectManager|EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/Asset'],
            isDevMode: true
        );

        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('Proxies');
        }

        $connection    = DriverManager::getConnection([
            'driverClass' => Driver::class,
            'memory'      => true,
            'dbname'      => 'test',
        ], $config);
        $entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($objectManager = $entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $objectManager;
    }

    private function flattenRoles(iterable $roles): Generator
    {
        foreach ($roles as $role) {
            yield $role;

            if ($role->hasChildren()) {
                yield from $this->flattenRoles($role->getChildren());
            }
        }
    }
}
