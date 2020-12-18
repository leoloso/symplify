<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Rules\PreventDuplicateClassMethodRule\Fixture;

class FirstClass
{
    public function __construct()
    {
        echo '__construct';
    }

    public function someMethod()
    {
        (new SmartFinder())->run('.php');
    }
}
