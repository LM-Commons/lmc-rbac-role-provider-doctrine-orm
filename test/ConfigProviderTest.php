<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine;

use Lmc\Rbac\Role\Doctrine\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testInvoke(): void
    {
        $configProvider = new ConfigProvider();
        $this->assertIsArray($configProvider());
    }
}
