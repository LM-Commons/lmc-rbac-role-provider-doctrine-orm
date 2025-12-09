<?php

declare(strict_types=1);

namespace Lmc\Rbac\Role\Doctrine;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                ObjectRepositoryRoleProvider::class => ObjectRepositoryRoleProviderFactory::class,
            ],
        ];
    }
}
