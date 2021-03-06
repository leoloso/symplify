<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Symplify\Astral\Naming\SimpleNameResolver;

final class ClassReflectionResolver
{
    public function __construct(
        private SimpleNameResolver $simpleNameResolver,
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function resolve(Scope $scope, Node $node): ?ClassReflection
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection) {
            return $classReflection;
        }

        if (! $node instanceof ClassLike) {
            return null;
        }

        $className = $this->simpleNameResolver->getName($node);
        if ($className === null) {
            return null;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        return $this->reflectionProvider->getClass($className);
    }
}
