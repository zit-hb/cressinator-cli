<?php

namespace App\Exception;

use Exception;

class RecordingSourceNotFoundException extends Exception
{
    /**
     * RecordingSourceNotFoundException constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct('Recording source ' . $name . ' not found');
    }
}
