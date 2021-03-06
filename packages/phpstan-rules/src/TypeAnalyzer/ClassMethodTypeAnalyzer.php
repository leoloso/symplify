<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\TypeAnalyzer;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;

final class ClassMethodTypeAnalyzer
{
    /**
     * @param string[] $methodNames
     */
    public function isClassMethodOfNamesAndType(
        ClassMethod $classMethod,
        Scope $scope,
        array $methodNames,
        string $classType
    ): bool {
        $classMethodName = (string) $classMethod->name;
        if (! in_array($classMethodName, $methodNames, true)) {
            return false;
        }

        return $this->isClassType($scope, $classType);
    }

    private function isClassType(Scope $scope, string $classType): bool
    {
        $classReflection = $scope->getClassReflection();
        if (! $classReflection instanceof ClassReflection) {
            return false;
        }

        if ($classReflection->isSubclassOf($classType)) {
            return true;
        }

        return $classReflection->hasTraitUse($classType);
    }
}
