<?php

namespace GS\Command\Contracts;

use Symfony\Component\Finder\SplFileInfo;
use GS\Command\Command\AbstractConstructedFromToCommand;

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
        for AbstractConstructedFromToCommand
    */
    public function getInfo(): string
    {
        return static::INFO;
    }

    /*
        for AbstractConstructedFromToCommand
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
        AbstractConstructedFromToCommand $command,
    ): string;

    /* AbstractConstructedFromToPathsDataSupplier */
    abstract public function getTo(
        SplFileInfo $finderSplFileInfo,
    ): string;

    //###< ABSTRACT ###
}
