<?php

namespace GS\Command\Trait;

use function Symfony\Component\String\u;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
use Symfony\Component\Console\Attribute\{
    AsCommand
};
use Symfony\Component\Console\Input\{
    InputArgument,
    InputOption,
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};
use GS\Service\Service\{
    ConfigService,
    DumpInfoService,
    RegexService,
    ArrayService,
    StringService,
    FilesystemService
};
use GS\Command\Contracts\{
    ConstructedFromToCommandInterface,
    AbstractConstructedFromToPathsDataSupplier
};
use GS\Command\Trait\{
    OverrideAbleTrait
};

/* extend it if you want use the CONCEPT: DUMP BEFORE, THEN EXECUTE

    | __SOURCE__ |=> constructedFromToPaths <=| __SOURCE__ |

    setConstructedFromToPaths
    dumpConstructedFromToPaths
    isOk
        makeConstructedFromToPaths
*/
trait ConstructedFromToCommandTrait
{
    /*
        [
            [
                'from'              => '<string>',
                'to'                => '<string>',
            ],
            ...
        ]
    */
    private array $constructedFromToPaths           = [];
    private ?string $fromForFinder                  = null;
    private ?Finder $finder                         = null;
    private ?AbstractConstructedFromToPathsDataSupplier $dataSupplierForConstructedFromToPaths = null;
    private int $quantityConstructedFromToPaths     = 0;

    protected function constructedFromToCommandDuringConfigure(): void
    {
        $this->configureOverrideOptions();
    }

    protected function constructedFromToCommandDuringInitialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        $this->initializeOverrideOptions(
            $input,
            $output,
            command: $this,
        );
    }
	

    //###> ABSTRACT ###

    /* AbstractCommand */
    protected function command(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $dataSuppliers = $this->getDataSuppliersForConstructedFromToPaths();

        // DATA SUPPLIERS
        foreach ($dataSuppliers as $dataSupplier) {
            $this->setDataSupplierForConstructedFromToPaths(
                $dataSupplier,
            );

            // important
            $this->clearCycleStateInTheBeginning();

            $this->setConstructedFromToPaths(
				$input,
				$output,
                $dataSupplier,
            );

            if ($this->isConstructedFromToPathsEmpty()) {
                $this->makeWillNotBe(
                    $input,
                    $output,
                    $dataSupplier,
                );
                continue;
            }

            $this->dumpConstructedFromToPaths(
                $input,
                $output,
                $dataSupplier,
            );

            //###>
            $operationWasMade = false;
            $madeQuantity = 0;
            if (
                $this->isOk(
                    default:        $dataSupplier->getDefaultIsOk(),
                )
            ) {
                $madeQuantity = $this->makeConstructedFromToPaths(
                    $input,
                    $output,
                    $dataSupplier,
                );
                $operationWasMade = true;
            }

            //###> hook
            $this->afterDataSupplierCycleExecute(
                $input,
                $output,
                $operationWasMade,
                $dataSupplier,
                $madeQuantity,
                madeQuantityEqualsAllFilesFrom: $madeQuantity === $this->getQuantityConstructedFromToPaths(),
            );
        }

        //###> hook
        $this->afterDataSupplierExecute(
            $input,
            $output,
            $dataSupplier,
        );

        return Command::SUCCESS;
    }

    //###< ABSTRACT ###


    //###> API ###

    protected function getAlertStringForDataSupplier(
		string $title,
		AbstractConstructedFromToPathsDataSupplier $dataSupplier,
	): string {
		return ''. $title .' "' . $dataSupplier->getInfo() . '"';
	}
	
    protected function tryToRemovePaths(
        /* MESSAGE IS BASED ON from IN constructedFromToPaths */
        string $whatFromIsInConstructedFromToPaths,
        array $pathsForRemove,
    ): void {
        $longestCommon  = Path::getLongestCommonBasePath(...$pathsForRemove);

        $longestCommon  = $this->getDirIfFile($longestCommon);

        $fromDirPartMessage = 'from ' . '[' . $longestCommon . '] directory';
        $message        = ''
            . 'REMOVE: ' . $whatFromIsInConstructedFromToPaths . ''
            . ' ' . $fromDirPartMessage . '?'
        . '';

        $infoMessage = (string) u(u($whatFromIsInConstructedFromToPaths)->ensureEnd(' ') . \trim($fromDirPartMessage))->ensureStart(' ');
        if ($this->isOk($message)) {
            $this->io->info([
                'Процесс удаления' . $infoMessage . '...',
            ]);
            foreach ($pathsForRemove as $pathForRemove) {
                $this->filesystemService->deleteByAbsPathIfExists(
                    $pathForRemove,
                );
            }
            //###>
            $this->io->note([
                'Удалены' . $infoMessage,
            ]);
        } else {
            //###>
            $this->io->note([
                'Не удалены' . $infoMessage,
            ]);
        }
    }

    protected function getFromForFinder(): ?string
    {
        return $this->fromForFinder;
    }

    protected function isConstructedFromToPathsEmpty(): bool
    {
        return empty(\array_filter($this->constructedFromToPaths));
    }

    protected function getConstructedFromToPaths(): array
    {
        return $this->constructedFromToPaths;
    }

    protected function getReadyFinder(): Finder
    {
        return $this->finder;
    }

    protected function getQuantityConstructedFromToPaths(): int
    {
        return $this->quantityConstructedFromToPaths;
    }

    //###< API ###

    //###> ABSTRACT ###

    /* AbstractConstructedFromToCommand
        create your own ConstructedFromToPathsDataSupplier for a certain command
        extends AbstractConstructedFromToPathsDataSupplier
    */
    abstract protected function getDataSuppliersForConstructedFromToPaths(): \Traversable|array;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]

        ###>READY:
            getFromForFinder
    */
    abstract protected function getFinder(): Finder;

    /* AbstractConstructedFromToCommand
		[INTO CYCLE]
	*/
    abstract protected function initScanningConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
		AbstractConstructedFromToPathsDataSupplier $dataSupplier,
	): void;

    /* AbstractConstructedFromToCommand
        [CYCLE INTO CYCLE]
    */
    abstract protected function scanningCycleForConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [CYCLE INTO CYCLE]

        FILTER
    */
    abstract protected function isSkipForConstructedFromToPaths(
        SplFileInfo $finderSplFileInfo,
        string $from,
        string $to,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): bool;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function endScanningForConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function clearStateWhenStartCycle(): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function makeWillNotBe(
        InputInterface $input,
        OutputInterface $output,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function beforeDumpInfoConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function isDumpInfoOnlyDirname(): bool;

    /* AbstractConstructedFromToCommand
        RETURN NULL IF YOU DON'T WANNT CONSIDER IT

        [INTO CYCLE]
    */
    abstract protected function isDumpInfoOnlyFrom(): ?bool;

    /* AbstractConstructedFromToCommand
        RETURN NULL IF YOU DON'T WANNT CONSIDER IT

        [INTO CYCLE]
    */
    abstract protected function isDumpInfoOnlyTo(): ?bool;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]

        ###>READY:
            getFromForFinder
            isConstructedFromToPathsEmpty
            getQuantityConstructedFromToPaths
    */
    abstract protected function beforeMakeFromToAlgorithm(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE] 
    */
    abstract protected function beforeMakeFromToAlgorithmAndAfterStartProgressBar(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [CYCLE INTO CYCLE]
    */
    abstract protected function beforeMakeCycle(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [CYCLE INTO CYCLE]
    */
    abstract protected function makeFromToAlgorithm(
        string $from,
        string $to,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): ?array;

    /* AbstractConstructedFromToCommand
        [CYCLE INTO CYCLE]
    */
    abstract protected function afterMakeCycle(
        InputInterface $input,
        OutputInterface $output,
    ): void;

    /* AbstractConstructedFromToCommand
        [INTO CYCLE]
    */
    abstract protected function afterDataSupplierCycleExecute(
        InputInterface $input,
        OutputInterface $output,
        bool $operationWasMade,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
        int $madeQuantity,
        bool $madeQuantityEqualsAllFilesFrom,
    ): void;

    /* AbstractConstructedFromToCommand

        ###>READY:
            getFromForFinder IN LAST ITERATION
            isConstructedFromToPathsEmpty IN LAST ITERATION
            getQuantityConstructedFromToPaths IN LAST ITERATION
    */
    abstract protected function afterDataSupplierExecute(
        InputInterface $input,
        OutputInterface $output,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): void;

    //###< ABSTRACT ###
	

    //###> HELPER ###

    private function clearCycleStateInTheBeginning(): void
    {
        $this->clearStateWhenStartCycle();
        $this->clearConstructedFromToPaths();
        $this->quantityConstructedFromToPaths = 0;
    }

    private function setConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): void {
        $this->fromForFinder = $dataSupplier->getFromForFinder(
            currentFromForFinder:   $this->getFromForFinder(),
            command:                $this,
        );

        $this->checkFromForFinder();

        $this->finder               = $this->getFinder()
            ->in($this->fromForFinder)
            ->files()
        ;

        $this->setConstructedFromToPathsByFinder(			
			$input,
			$output,
			$dataSupplier,
		);
    }

    private function dumpConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): void {
		$this->io->title(
			$this->getAlertStringForDataSupplier(
				'Будет выполнено',
				$dataSupplier,
			),		
		);

        $this->beforeDumpInfoConstructedFromToPaths(
            $input,
            $output,
        );

        $this->dumpInfoService->dumpInfo(
            $this,
            $this->constructedFromToPaths,
            dirname:        $this->isDumpInfoOnlyDirname(),
            onlyFrom:       $this->isDumpInfoOnlyFrom(),
            onlyTo:         $this->isDumpInfoOnlyTo(),
        );
    }

    private function setDataSupplierForConstructedFromToPaths(
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): void {
        $this->dataSupplierForConstructedFromToPaths = $dataSupplier;
    }

    private function makeConstructedFromToPaths(
        InputInterface $input,
        OutputInterface $output,
        AbstractConstructedFromToPathsDataSupplier $dataSupplier,
    ): int {
        //###> INIT ###
        $counter                = 0;
        $updateProgressBar      = function (
            bool $force = false,
        ) use (&$counter) {
            if ($force || ++$counter > ConstructedFromToCommandInterface::PROGRESS_BAR_DISPLAY_FREQUENCY) {
                $counter = 0;
                $this->progressBar->advance();
                $this->progressBar->display();
            }
        };
        //###< INIT ###

        $this->beforeMakeFromToAlgorithm(
            $input,
            $output,
        );

        //###>
        $this->progressBar->setMaxSteps($this->getMaxSteps());
        $this->progressBar->start();
		$this->beforeMakeFromToAlgorithmAndAfterStartProgressBar(
            $input,
            $output,
        );

        $madeQuantity = 0;
        foreach ($this->constructedFromToPaths as [ 'from' => $from, 'to' => $to ]) {
            $this->beforeMakeCycle(
                $input,
                $output,
            );
            $made = $this->makeFromToAlgorithm(
                from:           $from,
                to:             $to,
                dataSupplier:   $dataSupplier,
            );
            $updateProgressBar();
            $this->afterMakeCycle(
                $input,
                $output,
            );

            if (!empty($made)) {
                ++$madeQuantity;
            }
        }
        $updateProgressBar(force: true);
		$this->progressBar->finish();
        $this->progressBar->clear();

        return $madeQuantity;
    }

    private function getLongestCommonFromWithConstructedFromToPaths(): string
    {
        return Path::getLongestCommonBasePath(
            ...\array_filter(
                \array_map(
                    static fn($v) => $v['from'] ?? null,
                    $this->constructedFromToPaths,
                ),
            ),
        );
    }

    private function setConstructedFromToPathsByFinder(
        InputInterface $input,
        OutputInterface $output,
		AbstractConstructedFromToPathsDataSupplier $dataSupplier,
	): void {
		$this->initScanningConstructedFromToPaths(
			$input,
			$output,
			$dataSupplier,
		);
        // FINDER
        foreach ($this->finder as $finderSplFileInfo) {
            // FROM:    FIRST
            $from           = $this->dataSupplierForConstructedFromToPaths->getFrom(
                $finderSplFileInfo,
            );

            // after from before to
            if (!$this->isFromExistingFileWithAbsPath($from)) {
                continue;
            }

            // TO:      SECOND
            $to             = $this->dataSupplierForConstructedFromToPaths->getTo(
                $finderSplFileInfo,
            );
			
			$this->scanningCycleForConstructedFromToPaths(
				$input,
				$output,
			);

            //###>
            if (
                $this->isSkipForConstructedFromToPaths(
                    $finderSplFileInfo,
                    $from,
                    $to,
                    $this->dataSupplierForConstructedFromToPaths,
                )
            ) {
                continue;
            }

            $this->constructedFromToPaths [] = [
                'from'              => $from,
                'to'                => $to,
            ];
            ++$this->quantityConstructedFromToPaths;
        }
		$this->endScanningForConstructedFromToPaths(
			$input,
			$output,
		);
    }

    private function clearConstructedFromToPaths(): void
    {
        $this->constructedFromToPaths       = [];
    }

    private function isFromExistingFileWithAbsPath(
        ?string $from,
    ): bool {
        // faster
        if ($from === null) {
            return false;
        }

        return empty(
            $this->filesystemService->getErrorsIfNot(
                [
                    'exists',
                    'isAbsolutePath',
                    'isFile',
                ],
                $from,
            )
        );
    }

    private function checkFromForFinder(): void
    {
        $this->filesystemService->throwIfNot(
            [
                'exists',
                'isAbsolutePath',
                'isDir',
            ],
            $this->fromForFinder,
        );
    }

    private function getMaxSteps(): int
    {
        $f = self::PROGRESS_BAR_DISPLAY_FREQUENCY;
        if ($f <= 0) {
            $f = 1;
        }

        $maxSteps = \floor($this->quantityConstructedFromToPaths / $f);
        if ($maxSteps <= 0) {
            $maxSteps = 1;
        }

        return $maxSteps;
    }

    private function getDirIfFile(
        string $path,
    ): string {
        if (\is_file($path)) {
            return $this->stringService->getDirectory($path);
        }
        return $path;
    }

    //###< HELPER ###
}
