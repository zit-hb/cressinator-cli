<?php

namespace App\Exception;

use Exception;

class OptionNotFoundException extends Exception
{
    /** @var string[] */
    private $missingOptions = [];

    /**
     * OptionNotFoundException constructor.
     * @param array $missingOptions
     */
    public function __construct(array $missingOptions)
    {
        parent::__construct('Option not found');
        $this->missingOptions = $missingOptions;
    }

    /**
     * @return string[]
     */
    public function getMissingOptions(): array
    {
        return $this->missingOptions;
    }

    /**
     * @param string[] $missingOptions
     */
    public function setMissingOptions(array $missingOptions): void
    {
        $this->missingOptions = $missingOptions;
    }
}
