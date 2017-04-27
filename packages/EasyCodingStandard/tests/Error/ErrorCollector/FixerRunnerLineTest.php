<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Tests\Error\ErrorCollector;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symplify\EasyCodingStandard\Application\Command\RunCommand;
use Symplify\EasyCodingStandard\ChangedFilesDetector\Contract\ChangedFilesDetectorInterface;
use Symplify\EasyCodingStandard\Error\Error;
use Symplify\EasyCodingStandard\Error\ErrorCollector;
use Symplify\EasyCodingStandard\FixerRunner\Application\FileProcessor;
use Symplify\EasyCodingStandard\Tests\ContainerFactoryWithCustomConfig;

final class FixerRunnerLineTest extends TestCase
{
    /**
     * @var ErrorCollector
     */
    private $errorDataCollector;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    protected function setUp(): void
    {
        $container = (new ContainerFactoryWithCustomConfig)->createWithConfig(
            __DIR__ . '/FixerRunnerSource/easy-coding-standard.neon'
        );

        $this->errorDataCollector = $container->getByType(ErrorCollector::class);
        $this->fileProcessor = $container->getByType(FileProcessor::class);

        /** @var ChangedFilesDetectorInterface $changedFilesDetector */
        $changedFilesDetector = $container->getByType(ChangedFilesDetectorInterface::class);
        $changedFilesDetector->clearCache();
    }

    public function test(): void
    {
        $this->runFileProcessor();

        $this->assertSame(1, $this->errorDataCollector->getErrorCount());

        $errorMessages = $this->errorDataCollector->getAllErrors();

        /** @var Error $error */
        $error = array_pop($errorMessages)[0];
        $this->assertInstanceOf(Error::class, $error);

        $this->assertSame(8, $error->getLine());
    }

    private function runFileProcessor(): void
    {
        $runCommand = RunCommand::createForSourceFixerAndClearCache(
            [__DIR__ . '/ErrorCollectorSource/ConstantWithoutPublicDeclaration.php.inc'],
            false,
            true
        );

        $fileInfo = new SplFileInfo(__DIR__ . '/ErrorCollectorSource/ConstantWithoutPublicDeclaration.php.inc');

        $this->fileProcessor->setupWithCommand($runCommand);
        $this->fileProcessor->processFile($fileInfo);
    }
}
