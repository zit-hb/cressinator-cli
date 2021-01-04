<?php

namespace App\Exception;

use Exception;

class SensorTypeNotFoundException extends Exception
{
    /**
     * SensorTypeNotFoundException constructor.
     * @param string $type
     */
    public function __construct(string $type)
    {
        parent::__construct('Sensor type ' . $type . ' not found');
    }
}
