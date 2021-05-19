<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Rules\ForbiddenParamTypeRemovalRule\Fixture;

use Symplify\PHPStanRules\Tests\Rules\ForbiddenParamTypeRemovalRule\Source\NoTypeInterface;

final class SkipNoType implements NoTypeInterface
{
    public function noType($node)
    {
    }
}
