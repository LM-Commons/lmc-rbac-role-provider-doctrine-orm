<?php

declare(strict_types=1);

namespace Lmc\Rbac\Role\Doctrine\Exception;

use RuntimeException;

final class InvalidConfigurationException extends RuntimeException implements ExceptionInterface
{
}
