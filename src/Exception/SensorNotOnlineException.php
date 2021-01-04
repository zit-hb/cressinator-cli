<?php

namespace App\Exception;

use Exception;

class SensorNotOnlineException extends Exception
{
    /**
     * SensorNotOnlineException constructor.
     * @param string $serial
     */
    public function __construct(string $serial)
    {
        parent::__construct('Sensor ' . $serial . ' not online');
    }
}
