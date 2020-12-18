<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Rules\PreventDuplicateClassMethodRule;

use Iterator;
use PHPStan\Rules\Rule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;
use Symplify\PHPStanRules\Rules\PreventDuplicateClassMethodRule;

final class PreventDuplicateClassMethodRuleTest extends AbstractServiceAwareRuleTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function testRule(string $filePath, array $expectedErrorMessagesWithLines): void
    {
        $this->analyse([$filePath], $expectedErrorMessagesWithLines);
    }

    public function provideData(): Iterator
    {
        yield [__DIR__ . '/Fixture/FirstClass.php', []];
        yield [__DIR__ . '/Fixture/DuplicateFirstClassMethodInPrivate.php', []];
        yield [__DIR__ . '/Fixture/ATest.php', []];
        yield [__DIR__ . '/Fixture/SecondClassDuplicateFirstClassMethod.php', [
            [
                sprintf(
                    PreventDuplicateClassMethodRule::ERROR_MESSAGE,
                    'someMethod',
                    'Symplify\PHPStanRules\Tests\Rules\PreventDuplicateClassMethodRule\Fixture\FirstClass'
                ),
                14,
            ],
        ]];
    }

    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(
            PreventDuplicateClassMethodRule::class,
            __DIR__ . '/../../../config/symplify-rules.neon'
        );
    }
}
