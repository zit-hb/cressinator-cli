<?php

namespace App\Exception;

use Exception;

class MeasurementSourceNotFoundException extends Exception
{
    /**
     * MeasurementSourceNotFoundException constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct('Measurement source ' . $name . ' not found');
    }
}
