<?php

declare(strict_types=1);

namespace Lmc\Rbac\Role\Doctrine;

use Doctrine\Persistence\ObjectRepository;
use Laminas\Permissions\Rbac\RoleInterface;
use Lmc\Rbac\Role\RoleFactoryInterface;
use Lmc\Rbac\Role\RoleProviderInterface;
use Override;

use function array_filter;
use function count;
use function implode;

/**
 * Role provider that uses Doctrine object repository to fetch roles
 */
final class ObjectRepositoryRoleProvider implements RoleProviderInterface
{
    /** @var array<string, RoleInterface[]>  */
    private array $roleCache = [];

    public function __construct(
        private readonly ObjectRepository $objectRepository,
        private readonly string $roleNameProperty,
        private readonly RoleFactoryInterface $roleFactory,
    ) {
    }

    public function clearRoleCache(): void
    {
        $this->roleCache = [];
    }

    #[Override]
    public function getRoles(iterable $roleNames): iterable
    {
        $key = implode('&', $roleNames);

        // We already have roles loaded for this role names
        if (isset($this->roleCache[$key])) {
            return $this->roleCache[$key];
        }

        /** @var RoleInterface[] $roles */
        $roles = $this->objectRepository->findBy([$this->roleNameProperty => $roleNames]);

        // We allow more roles to be loaded than asked (although this should not happen because
        // role names should have a UNIQUE constraint in the database... but just in case ;))
        if (count($roles) >= count($roleNames)) {
            $this->roleCache[$key] = $roles;
        } else {
            // Not all roles were found
            $this->roleCache[$key] = [];
            foreach ($roleNames as $roleName) {
                $r = array_filter($roles, function (RoleInterface $role) use ($roleName): bool {
                    return $role->getName() === $roleName;
                });
                if (count($r) >= 1) {
                    $this->roleCache[$key][] = $r[0];
                } else {
                    $this->roleCache[$key][] = $this->roleFactory->createRole($roleName);
                }
            }
        }
        return $this->roleCache[$key];
    }
}
