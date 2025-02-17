<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer\ArrayNotation;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
use Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer\ArrayAnalyzer;
use Symplify\CodingStandard\TokenRunner\Traverser\ArrayBlockInfoFinder;
use Symplify\CodingStandard\TokenRunner\ValueObject\BlockInfo;
use Symplify\CodingStandard\TokenRunner\ValueObject\TokenKinds;
use Symplify\RuleDocGenerator\Contract\DocumentedRuleInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Symplify\CodingStandard\Tests\Fixer\ArrayNotation\ArrayListItemNewlineFixer\ArrayListItemNewlineFixerTest
 */
final class ArrayListItemNewlineFixer extends AbstractSymplifyFixer implements DocumentedRuleInterface
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Indexed PHP array item has to have one line per item';

    public function __construct(
        private ArrayAnalyzer $arrayAnalyzer,
        private WhitespacesFixerConfig $whitespacesFixerConfig,
        private ArrayBlockInfoFinder $arrayBlockInfoFinder
    ) {
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, []);
    }

    public function getPriority(): int
    {
        return 40;
    }

    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        if (! $tokens->isAnyTokenKindsFound(TokenKinds::ARRAY_OPEN_TOKENS)) {
            return false;
        }

        return $tokens->isTokenKindFound(T_DOUBLE_ARROW);
    }

    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $arrayBlockInfos = $this->arrayBlockInfoFinder->findArrayOpenerBlockInfos($tokens);
        foreach ($arrayBlockInfos as $arrayBlockInfo) {
            $this->fixArrayOpener($tokens, $arrayBlockInfo);
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
$value = ['simple' => 1, 'easy' => 2];
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$value = ['simple' => 1,
'easy' => 2];
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @param Tokens<Token> $tokens
     */
    private function fixArrayOpener(Tokens $tokens, BlockInfo $blockInfo): void
    {
        if (! $this->arrayAnalyzer->isIndexedList($tokens, $blockInfo)) {
            return;
        }

        $this->arrayAnalyzer->traverseArrayWithoutNesting(
            $tokens,
            $blockInfo,
            function (Token $token, int $position, Tokens $tokens): void {
                if ($token->getContent() !== ',') {
                    return;
                }

                $nextTokenPosition = $position + 1;
                $nextToken = $tokens[$nextTokenPosition] ?? null;
                if (! $nextToken instanceof Token) {
                    return;
                }

                if (\str_contains($nextToken->getContent(), "\n")) {
                    return;
                }

                $tokens->ensureWhitespaceAtIndex($nextTokenPosition, 0, $this->whitespacesFixerConfig->getLineEnding());
            }
        );
    }
}
