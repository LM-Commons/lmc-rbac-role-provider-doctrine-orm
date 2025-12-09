<?php

declare(strict_types=1);

namespace Lmc\Rbac\Role\Doctrine;

final class Module
{
    public function getConfig(): array
    {
        $configProvider = new ConfigProvider();
        return [
            'service_manager' => $configProvider->getDependencies(),
        ];
    }
}
