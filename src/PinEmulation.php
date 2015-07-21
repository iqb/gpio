<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;


/**
 * Class to simulate GPIO interaction without real access to /sys/class/gpio
 */
class PinEmulation extends Pin
{
    /**
     * Store the last value written to a handle so it can be read back
     * @var string[]
     */
    protected $handleData = [];

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var Emulator
     */
    protected $emulator;


    public function __construct($number, Emulator $emulator = null)
    {
        parent::__construct($number);
        $this->emulator = $emulator;
    }


    protected function openFile($handleName, $fileName)
    {
        $this->handles[$handleName] = $fileName;

        if ($this->emulator !== null) {
            $this->emulator->reportFileOpen($this, $handleName);
        }
    }

    protected function closeFile($handleName)
    {
        unset($this->handles[$handleName]);
        if ($this->emulator !== null) {
            $this->emulator->reportFileClose($this, $handleName);
        }
    }

    protected function readFromHandle($handleName, $bytesToRead = 1024)
    {
        if (isset($this->handleData[$handleName])) {
            if ($this->emulator !== null) {
                $this->emulator->reportFileRead($this, $handleName, $this->handleData[$handleName]);
            }
            return trim($this->handleData[$handleName]);
        }

        else {
            throw new Exception('Can not read from handle "' . $handleName . '"');
        }
    }

    protected function writeToHandle($handleName, $data)
    {
        if (($handleName == 'export') && ($data == $this->number . "\n")) {
            $this->enabled = true;
            $this->handleData['direction'] = Pin::DIRECTION_IN;
            $this->handleData['edge'] = Pin::EDGE_NONE;
            $this->handleData['value'] = '0';
        }

        elseif (($handleName == 'unexport') && ($data == $this->number . "\n")) {
            $this->enabled = false;
        }

        else {
            $this->handleData[$handleName] = $data;
        }

        if ($this->emulator !== null) {
            $this->emulator->reportFileWrite($this, $handleName, $data);
        }
    }

    protected function checkSysDir()
    {
        return $this->enabled;
    }
}