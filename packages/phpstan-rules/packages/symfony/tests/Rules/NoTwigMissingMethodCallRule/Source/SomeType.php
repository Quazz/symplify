<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Symfony\Tests\Rules\NoTwigMissingMethodCallRule\Source;

final class SomeType
{
    public $some_property;

    public function getExistingMethod()
    {
    }
}
