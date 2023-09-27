<?php

namespace GS\Command\Contracts;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Command\Command;

abstract class AbstractConstructedFromToPathsDataSupplier
{
    public const INFO = '? DIRECTION ?';

    public function __construct()
    {
    }

    //###> PUBLIC API ###

    public function getFrom(
        SplFileInfo $finderSplFileInfo,
    ): string {
        return $finderSplFileInfo->getRealPath();
    }

    /*
        for Command
    */
    public function getInfo(): string
    {
        return static::INFO;
    }

    /*
        for Command
    */
    public function getDefaultIsOk(): bool
    {
        return true;
    }

    //###< PUBLIC API ###


    //###> API ###

    /*
        Get year month and board number by from or default values
    */
    protected function getYearMonthBoardNumber(
        SplFileInfo $finderSplFileInfo,
    ): array {

        $relativePath = $finderSplFileInfo->getRelativePath();

        $yearMonthAndBoardNumber = [
            $this->partOfPathService->getYear($relativePath),
            $this->partOfPathService->getMonth($relativePath),
            $this->partOfPathService->getBoardNumberFromPath(
                $finderSplFileInfo,
            ),
        ];

        return $yearMonthAndBoardNumber;
    }

    //###< API ###

    //###> ABSTRACT ###

    /* AbstractConstructedFromToPathsDataSupplier */
    abstract public function getFromForFinder(
        ?string $currentFromForFinder,
        Command $command,
    ): string;

    /* AbstractConstructedFromToPathsDataSupplier */
    abstract public function getTo(
        SplFileInfo $finderSplFileInfo,
    ): string;

    //###< ABSTRACT ###
}
