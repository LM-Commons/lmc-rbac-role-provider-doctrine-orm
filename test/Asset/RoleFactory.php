<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine\Asset;

use Laminas\Permissions\Rbac\RoleInterface;
use Lmc\Rbac\Role\RoleFactoryInterface;
use Override;

final class RoleFactory implements RoleFactoryInterface
{
    #[Override]
    public function createRole(string $roleName): RoleInterface
    {
        return new Role($roleName);
    }
}
