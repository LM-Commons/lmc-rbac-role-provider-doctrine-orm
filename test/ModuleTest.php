<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine;

use Lmc\Rbac\Role\Doctrine\ConfigProvider;
use Lmc\Rbac\Role\Doctrine\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
final class ModuleTest extends TestCase
{
    public function testProvidesExpectedConfiguration(): void
    {
        $provider = new ConfigProvider();
        $module   = new Module();
        $expected = [
            'service_manager' => $provider->getDependencies(),
        ];
        $this->assertEquals($expected, $module->getConfig());
    }
}
