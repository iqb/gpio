<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;

class Emulator
{
    const ACTION_ENABLE             = 1<<0;
    const ACTION_DISABLE            = 1<<1;
    const ACTION_CHANGE_DIRECTION   = 1<<2;
    const ACTION_CHANGE_EDGE        = 1<<3;
    const ACTION_CHANGE_VALUE       = 1<<4;

    const ACTION_ALL                = self::ACTION_ENABLE
                                    + self::ACTION_DISABLE
                                    + self::ACTION_CHANGE_DIRECTION
                                    + self::ACTION_CHANGE_EDGE
                                    + self::ACTION_CHANGE_VALUE;

    /**
     * List of events that should occur
     *
     * @var array
     */
    protected $expect;

    /**
     * Events that will be logged and compared to the expect list
     *
     * @var
     */
    protected $actions;

    public $log = [];

    /**
     */
    public function __construct(array $expect = null, $actions = self::ACTION_CHANGE_VALUE)
    {
        $this->expect = $expect;
        $this->actions = $actions;
    }

    /**
     * Create a new Pin emulation that reports actions back to this emulator
     *
     * @param int $number
     * @return PinEmulation
     */
    public function createPin($number)
    {
        return new PinEmulation($number, $this);
    }

    public function reportFileOpen(PinEmulation $pin, $fileHandle)
    {
    }

    public function reportFileClose(PinEmulation $pin, $fileHandle)
    {
    }

    public function reportFileRead(PinEmulation $pin, $fileHandle, $value)
    {
    }

    public function reportFileWrite(PinEmulation $pin, $fileHandle, $data)
    {
        if (($fileHandle == 'export') && ($data === $pin->getNumber()."\n")) {
            $this->logAction($pin, self::ACTION_ENABLE, null);
        }

        elseif (($fileHandle == 'unexport') && ($data === $pin->getNumber()."\n")) {
            $this->logAction($pin, self::ACTION_DISABLE, null);
        }

        elseif ($fileHandle == 'direction') {
            $this->logAction($pin, self::ACTION_CHANGE_DIRECTION, trim($data));
        }

        elseif ($fileHandle == 'edge') {
            $this->logAction($pin, self::ACTION_CHANGE_EDGE, trim($data));
        }

        elseif ($fileHandle == 'value') {
            $this->logAction($pin, self::ACTION_CHANGE_VALUE, (trim($data) !== '0'));
        }
    }

    protected function logAction(PinEmulation $pin, $action, $data = null)
    {
        // Ignore unmonitored actions
        if (($action & $this->actions) === 0) {
            return;
        }

        $gpio = $pin->getNumber();
        $log = [$gpio, $action, $data];

        // Same event as before, duplicate events are ignored
        if ((\count($this->log) > 0) && ($this->log[\count($this->log)-1] == $log)) {
            return;
        }

        if ($this->expect !== null) {
            @list($expect_gpio, $expect_action, $expect_data) = \array_shift($this->expect);

            if (($gpio !== $expect_gpio) || ($action !== $expect_action) || ($data !== $expect_data)) {
                throw new \RuntimeException(
                    "\nExpected: " . $this->formatEntry($expect_gpio, $expect_action, $expect_data) . "\n"
                    . "Actual:   " . $this->formatEntry($gpio, $action, $data)
                );
            }
        }

        $this->log[] = $log;
    }


    public function printLog()
    {
        foreach ($this->log as list($gpio, $action, $data)) {
            echo $this->formatEntry($gpio, $action, $data) . "\n";
        }
    }

    protected function formatEntry($gpio, $action, $data)
    {
        return sprintf('GPIO: %2u, action: %-24s', $gpio, $this->resolveConstant($action) . ($data !== null ? ',' : '')) . ($data !== null ? ' data: ' . var_export($data, true) : '');
    }

    protected function resolveConstant($constantValue)
    {
        $r = new \ReflectionClass($this);
        $constantMapping = $r->getConstants();

        if (false === ($constantName = array_search($constantValue, $constantMapping))) {
            return 'UNKNOWN';
        } else {
            return $constantName;
        }
    }
}