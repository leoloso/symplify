<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Rules\Complexity\ForbiddenComplexForeachIfExprRule\Fixture;

class SkipWithoutMethodOrStaticCall
{
    public function execute($arg)
    {
        $data = [];
        foreach ($data as $key => $item) {

        }
    }
}
