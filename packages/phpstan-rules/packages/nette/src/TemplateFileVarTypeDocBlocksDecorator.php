<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Nette;

use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use stdClass;
use Symplify\PHPStanRules\LattePHPStanPrinter\Latte\Tokens\PhpToLatteLineNumbersResolver;
use Symplify\PHPStanRules\LattePHPStanPrinter\LatteToPhpCompiler;
use Symplify\PHPStanRules\LattePHPStanPrinter\ValueObject\PhpFileContentsWithLineMap;
use Symplify\PHPStanRules\LattePHPStanPrinter\ValueObject\VariableAndType;
use Symplify\PHPStanRules\Symfony\TypeAnalyzer\TemplateVariableTypesResolver;

final class TemplateFileVarTypeDocBlocksDecorator
{
    public function __construct(
        private LatteToPhpCompiler $latteToPhpCompiler,
        private TemplateVariableTypesResolver $templateVariableTypesResolver,
        private PhpToLatteLineNumbersResolver $phpToLatteLineNumbersResolver,
    ) {
    }

    public function decorate(string $latteFilePath, Array_ $array, Scope $scope): PhpFileContentsWithLineMap
    {
        $variablesAndTypes = $this->resolveLatteVariablesAndTypes($array, $scope);
        $phpContent = $this->latteToPhpCompiler->compileFilePath($latteFilePath, $variablesAndTypes);

        $phpLinesToLatteLines = $this->phpToLatteLineNumbersResolver->resolve($phpContent);
        return new PhpFileContentsWithLineMap($phpContent, $phpLinesToLatteLines);
    }

    /**
     * @return VariableAndType[]
     */
    public function resolveTwigVariablesAndTypes(Array_ $array, Scope $scope): array
    {
        // traverse nodes to add types after \DummyTemplateClass::main()
        $variablesAndTypes = $this->templateVariableTypesResolver->resolveArray($array, $scope);
        return $variablesAndTypes;
        // $defaultNetteVariablesAndTypes = $this->createDefaultNetteVariablesAndTypes();
        // return array_merge($variablesAndTypes, $defaultNetteVariablesAndTypes);
    }

    /**
     * @return VariableAndType[]
     */
    private function resolveLatteVariablesAndTypes(Array_ $array, Scope $scope): array
    {
        // traverse nodes to add types after \DummyTemplateClass::main()
        $variablesAndTypes = $this->templateVariableTypesResolver->resolveArray($array, $scope);
        $defaultNetteVariablesAndTypes = $this->createDefaultNetteVariablesAndTypes();

        return array_merge($variablesAndTypes, $defaultNetteVariablesAndTypes);
    }

    /**
     * @return VariableAndType[]
     */
    private function createDefaultNetteVariablesAndTypes(): array
    {
        $variablesAndTypes = [];
        $variablesAndTypes[] = new VariableAndType('baseUrl', new StringType());
        $variablesAndTypes[] = new VariableAndType('basePath', new StringType());

        // nette\security bridge
        $variablesAndTypes[] = new VariableAndType('user', new ObjectType('Nette\Security\User'));

        // nette\application bridge
        $variablesAndTypes[] = new VariableAndType('presenter', new ObjectType('Nette\Application\UI\Presenter'));
        $variablesAndTypes[] = new VariableAndType('control', new ObjectType('Nette\Application\UI\Control'));
        $variablesAndTypes[] = new VariableAndType('flashes', new ObjectType(stdClass::class));

        return $variablesAndTypes;
    }
}
