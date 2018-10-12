<?php declare(strict_types=1);

namespace Symplify\TokenRunner\Wrapper\FixerWrapper;

use Nette\Utils\Strings;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use Symplify\PackageBuilder\Reflection\PrivatesCaller;
use Symplify\PackageBuilder\Types\ClassLikeExistenceChecker;
use Symplify\TokenRunner\Analyzer\FixerAnalyzer\DocBlockFinder;
use Symplify\TokenRunner\Naming\Name\NameFactory;
use function Safe\class_implements;
use function Safe\class_parents;

final class ClassWrapper
{
    /**
     * Static cache
     *
     * @var string[][]
     */
    private static $parentInterfacesPerInterface = [];

    /**
     * @var string|null
     */
    private $className;

    /**
     * @var int
     */
    private $startBracketIndex;

    /**
     * @var int
     */
    private $endBracketIndex;

    /**
     * @var TokensAnalyzer
     */
    private $tokensAnalyzer;

    /**
     * @var Tokens
     */
    private $tokens;

    /**
     * @var Token
     */
    private $classToken;

    /**
     * @var int
     */
    private $startIndex;

    /**
     * @var mixed[]
     */
    private $classyElements = [];

    /**
     * @var PropertyWrapperFactory
     */
    private $propertyWrapperFactory;

    /**
     * @var MethodWrapperFactory
     */
    private $methodWrapperFactory;

    /**
     * @var DocBlockFinder
     */
    private $docBlockFinder;

    /**
     * @var PropertyAccessWrapperFactory
     */
    private $propertyAccessWrapperFactory;

    /**
     * @var NameFactory
     */
    private $nameFactory;

    /**
     * @var mixed[]
     *
     * Rich information about methods, e.g.:
     *
     *  0 => array (6)
     *     |  start => 18
     *     |  visibility => "public" (6)
     *     |  static => FALSE
     *     |  type => "method" (6)
     *     |  name => "secondMethod" (12)
     *     |  end => 29
     */
    private $methodElements = [];

    /**
     * @var string[]
     */
    private $classTypes = [];

    /**
     * @var ClassLikeExistenceChecker
     */
    private $classLikeExistenceChecker;

    public function __construct(
        Tokens $tokens,
        int $startIndex,
        PropertyWrapperFactory $propertyWrapperFactory,
        MethodWrapperFactory $methodWrapperFactory,
        DocBlockFinder $docBlockFinder,
        PropertyAccessWrapperFactory $propertyAccessWrapperFactory,
        NameFactory $nameFactory,
        ClassLikeExistenceChecker $classLikeExistenceChecker
    ) {
        $this->classToken = $tokens[$startIndex];
        $this->startBracketIndex = $tokens->getNextTokenOfKind($startIndex, ['{']);
        $this->endBracketIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $this->startBracketIndex);

        $this->tokens = $tokens;
        $this->tokensAnalyzer = new TokensAnalyzer($tokens);
        $this->startIndex = $startIndex;
        $this->propertyWrapperFactory = $propertyWrapperFactory;
        $this->methodWrapperFactory = $methodWrapperFactory;
        $this->docBlockFinder = $docBlockFinder;
        $this->propertyAccessWrapperFactory = $propertyAccessWrapperFactory;
        $this->nameFactory = $nameFactory;
        $this->classLikeExistenceChecker = $classLikeExistenceChecker;
    }

    public function getClassName(): ?string
    {
        if ($this->className) {
            return $this->className;
        }

        if (! $this->getNamePosition()) {
            return null;
        }

        $className = $this->nameFactory->createFromTokensAndStart($this->tokens, $this->getNamePosition());

        return $this->className = $className->getName();
    }

    public function getParentClassName(): ?string
    {
        $extendsTokens = $this->tokens->findGivenKind(T_EXTENDS, $this->startIndex);
        if (! $extendsTokens) {
            return null;
        }

        reset($extendsTokens);
        $extendsPosition = key($extendsTokens);

        /** @var Token[] $stringTokens */
        $stringTokens = $this->tokens->findGivenKind(T_STRING, $extendsPosition, $this->startBracketIndex);
        if (count($stringTokens) === 0) {
            return null;
        }

        $parentClassNamePosition = key($stringTokens);
        $parentClassName = $this->nameFactory->createFromTokensAndStart($this->tokens, $parentClassNamePosition);

        return $parentClassName->getName();
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->filterClassyTokens($this->getClassyElements(), ['property']);
    }

    public function renameEveryPropertyOccurrence(string $oldName, string $newName): void
    {
        for ($i = $this->startBracketIndex + 1; $i < $this->endBracketIndex; ++$i) {
            $token = $this->tokens[$i];

            if ($token->isGivenKind(T_VARIABLE) === false) {
                continue;
            }

            if ($token->getContent() !== '$this') {
                continue;
            }

            $propertyAccessWrapper = $this->propertyAccessWrapperFactory->createFromTokensAndPosition(
                $this->tokens,
                $i
            );

            if ($propertyAccessWrapper->getName() === $oldName) {
                $propertyAccessWrapper->changeName($newName);
            }
        }
    }

    /**
     * @return PropertyWrapper[]
     */
    public function getPropertyWrappers(): array
    {
        $propertyWrappers = [];

        foreach (array_keys($this->getProperties()) as $propertyPosition) {
            $propertyWrappers[] = $this->propertyWrapperFactory->createFromTokensAndPosition(
                $this->tokens,
                $propertyPosition
            );
        }

        return $propertyWrappers;
    }

    /**
     * @return MethodWrapper[]
     */
    public function getMethodWrappers(): array
    {
        $methodWrappers = [];

        foreach (array_keys($this->getMethods()) as $methodPosition) {
            $methodWrappers[] = $this->methodWrapperFactory->createFromTokensAndPosition(
                $this->tokens,
                $methodPosition
            );
        }

        return $methodWrappers;
    }

    /**
     * @param int[] $tokenKinds
     */
    public function isGivenKind(array $tokenKinds): bool
    {
        return $this->classToken->isGivenKind($tokenKinds);
    }

    public function implementsInterface(): bool
    {
        return (bool) $this->getInterfaceNames();
    }

    public function isFinal(): bool
    {
        return (bool) $this->tokens->findGivenKind(T_FINAL, 0, $this->startIndex);
    }

    public function isAbstract(): bool
    {
        return (bool) $this->tokens->findGivenKind(T_ABSTRACT, 0, $this->startIndex);
    }

    public function isDoctrineEntity(): bool
    {
        $docCommentPosition = $this->docBlockFinder->findPreviousPosition($this->tokens, $this->startIndex);
        if (! $docCommentPosition) {
            return false;
        }

        return Strings::contains($this->tokens[$docCommentPosition]->getContent(), 'Entity');
    }

    /**
     * @return string[]
     */
    public function getInterfaceNames(): array
    {
        $implementTokens = $this->tokens->findGivenKind(T_IMPLEMENTS, $this->startIndex, $this->startBracketIndex);
        if (! $implementTokens) {
            return [];
        }

        reset($implementTokens);

        $implementPosition = key($implementTokens);

        $interfacePartialNameTokens = $this->tokens->findGivenKind(
            T_STRING,
            $implementPosition,
            $this->startBracketIndex
        );

        $interfaceNames = [];
        foreach (array_keys($interfacePartialNameTokens) as $position) {
            $interfaceNames[] = $this->nameFactory->createFromTokensAndStart($this->tokens, $position)->getName();
        }

        // non-direct parent interfaces via autoload
        foreach ($interfaceNames as $interfaceName) {
            if (isset(self::$parentInterfacesPerInterface[$interfaceName])) {
                $parentInterfaces = self::$parentInterfacesPerInterface[$interfaceName];
                $interfaceNames = array_merge($interfaceNames, $parentInterfaces);
            } elseif (interface_exists($interfaceName)) {
                $parentInterfaces = class_implements($interfaceName);
                $interfaceNames = array_merge($interfaceNames, $parentInterfaces);
                self::$parentInterfacesPerInterface[$interfaceName] = $parentInterfaces;
            }
        }

        return array_unique($interfaceNames);
    }

    /**
     * @return mixed[]
     */
    public function getMethodElements(): array
    {
        if ($this->methodElements) {
            return $this->methodElements;
        }

        $elements = (new PrivatesCaller())->callPrivateMethod(
            new OrderedClassElementsFixer(),
            'getElements',
            $this->tokens,
            $this->startBracketIndex
        );

        $methodElements = array_filter($elements, function (array $element) {
            return $element['type'] === 'method';
        });

        // re-index from 0
        $methodElements = array_values($methodElements);

        return $this->methodElements = $methodElements;
    }

    /**
     * @return string[]
     */
    public function getClassTypes(): array
    {
        if ($this->classTypes) {
            return $this->classTypes;
        }

        // we can't handle anonymous classes
        if ($this->getClassName() === null) {
            return [];
        }

        // class it not autoloaded, so we can't give more types than just a name
        if (! $this->classLikeExistenceChecker->exists($this->getClassName())) {
            return [$this->getClassName()];
        }

        $classTypes = array_merge(
            [$this->getClassName()],
            class_parents($this->getClassName()),
            class_implements($this->getClassName())
        );

        // unique + reindex from 0
        return $this->classTypes = array_values(array_unique($classTypes));
    }

    private function getNamePosition(): ?int
    {
        if ((new TokensAnalyzer($this->tokens))->isAnonymousClass($this->startIndex)) {
            return null;
        }

        $stringTokens = $this->tokens->findGivenKind(T_STRING, $this->startIndex);
        if (! count($stringTokens)) {
            return null;
        }
        reset($stringTokens);

        return (int) key($stringTokens);
    }

    /**
     * @return mixed[]
     */
    private function getMethods(): array
    {
        return $this->filterClassyTokens($this->getClassyElements(), ['method']);
    }

    /**
     * @param mixed[] $classyElements
     * @param string[] $types
     * @return mixed[]
     */
    private function filterClassyTokens(array $classyElements, array $types): array
    {
        $filteredClassyTokens = [];

        foreach ($classyElements as $index => $classyToken) {
            if (! $this->isInClassRange($index)) {
                continue;
            }

            if (! in_array($classyToken['type'], $types, true)) {
                continue;
            }

            $filteredClassyTokens[$index] = $classyToken;
        }

        return $filteredClassyTokens;
    }

    private function isInClassRange(int $index): bool
    {
        if ($index < $this->startBracketIndex) {
            return false;
        }

        if ($index > $this->endBracketIndex) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    private function getClassyElements(): array
    {
        if ($this->classyElements) {
            return $this->classyElements;
        }

        return $this->classyElements = $this->tokensAnalyzer->getClassyElements();
    }
}
