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
     * Assert mode: fail if no expected action is left but more actions occur
     */
    const ASSERT_FAIL_MISSING       = 1<<0;

    /**
     * Assert mode: disable asserting if no expected action is left
     */
    const ASSERT_IGNORE_MISSING     = 1<<1;

    /**
     * Assert mode: fail if actions are excepted but are not coming
     */
    const ASSERT_FAIL_EXCESS        = 1<<2;

    /**
     * Assert mode: ignore if some expected actions remain unfulfilled
     */
    const ASSERT_IGNORE_EXCESS      = 1<<3;


    /**
     * Logged actions
     * @var array
     */
    protected $log = [];

    /**
     * Mask of actions to log
     * @var int
     */
    protected $logMask = self::ACTION_ALL;

    /**
     * List of events that should occur
     * @var array
     */
    protected $assert = [];

    /**
     * Mask of actions that are compared to the list of expected actions
     * @var int
     */
    protected $assertMask = self::ACTION_ALL;

    /**
     * Mode to handle if no further asserts are available
     * @var int
     */
    protected $assertMode = self::ASSERT_FAIL_MISSING|self::ASSERT_FAIL_EXCESS;


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

    /**
     * @return int
     */
    public function getLogMask()
    {
        return $this->logMask;
    }

    /**
     * Change the mask of logged actions
     *
     * @param int $logMask
     * @return $this
     */
    public function setLogMask($logMask)
    {
        $this->logMask = 0;
        $this->logMask |= ($logMask & self::ACTION_ENABLE);
        $this->logMask |= ($logMask & self::ACTION_DISABLE);
        $this->logMask |= ($logMask & self::ACTION_CHANGE_DIRECTION);
        $this->logMask |= ($logMask & self::ACTION_CHANGE_EDGE);
        $this->logMask |= ($logMask & self::ACTION_CHANGE_VALUE);
        return $this;
    }

    /**
     * @return int
     */
    public function getAssertMask()
    {
        return $this->assertMask;
    }

    /**
     * Change the mask of actions compared with the list of expected actions
     *
     * @param int $assertMask
     * @return $this
     */
    public function setAssertMask($assertMask)
    {
        $this->assertMask = 0;
        $this->assertMask |= ($assertMask & self::ACTION_ENABLE);
        $this->assertMask |= ($assertMask & self::ACTION_DISABLE);
        $this->assertMask |= ($assertMask & self::ACTION_CHANGE_DIRECTION);
        $this->assertMask |= ($assertMask & self::ACTION_CHANGE_EDGE);
        $this->assertMask |= ($assertMask & self::ACTION_CHANGE_VALUE);
        return $this;
    }

    /**
     * @return int
     */
    public function getAssertMode()
    {
        return $this->assertMode;
    }

    /**
     * Change the assert mode
     *
     * @param int $assertMode
     * @return $this
     */
    public function setAssertMode($assertMode)
    {
        if (($assertMode & self::ASSERT_FAIL_MISSING) !== 0) {
            $this->assertMode &= ~self::ASSERT_IGNORE_MISSING;
            $this->assertMode |= self::ASSERT_FAIL_MISSING;
        } elseif (($assertMode & self::ASSERT_IGNORE_MISSING) !== 0) {
            $this->assertMode &= ~self::ASSERT_FAIL_MISSING;
            $this->assertMode |= self::ASSERT_IGNORE_MISSING;
        }

        if (($assertMode & self::ASSERT_FAIL_EXCESS) !== 0) {
            $this->assertMode &= ~self::ASSERT_IGNORE_EXCESS;
            $this->assertMode |= self::ASSERT_FAIL_EXCESS;
        } elseif (($assertMode & self::ASSERT_IGNORE_EXCESS) !== 0) {
            $this->assertMode &= ~self::ASSERT_FAIL_EXCESS;
            $this->assertMode |= self::ASSERT_IGNORE_EXCESS;
        }

        return $this;
    }

    /**
     * Set the list of expected actions, clearing all previously set actions.
     *
     * @param array $expected
     * @return $this
     */
    public function assert(array $expected = [])
    {
        $this->assert = $expected;
        return $this;
    }

    /**
     * Callback called by a PinEmulator to notify the emulator if a file is opened
     *
     * @param PinEmulation $pin The caller
     * @param string $fileHandle
     */
    public function reportFileOpen(PinEmulation $pin, $fileHandle)
    {
    }

    /**
     * Callback called by a PinEmulator to notify the emulator if a file is closed
     *
     * @param PinEmulation $pin The caller
     * @param string $fileHandle
     */
    public function reportFileClose(PinEmulation $pin, $fileHandle)
    {
    }

    /**
     * Callback called by a PinEmulator to notify the emulator if data is read from a file
     *
     * @param PinEmulation $pin The caller
     * @param string $fileHandle
     * @param string $value
     */
    public function reportFileRead(PinEmulation $pin, $fileHandle, $value)
    {
    }

    /**
     * Callback called by a PinEmulator to notify the emulator if data is written to a file
     *
     * @param PinEmulation $pin The caller
     * @param string $fileHandle
     * @param string $data
     */
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

    /**
     * Write an action to the log and compare it to the expected actions
     *
     * @param PinEmulation $pin
     * @param int $action
     * @param mixed $data
     */
    protected function logAction(PinEmulation $pin, $action, $data = null)
    {
        $gpio = $pin->getNumber();

        // Log data
        if (($action & $this->logMask) !== 0) {
            $log = [$gpio, $action, $data];

            // Same event as before, duplicate events are ignored
            if ((\count($this->log) === 0) || ($this->log[\count($this->log)-1] != $log)) {
                $this->log[] = $log;
            }
        }

        if (($action & $this->assertMask) !== 0) {
            if (\count($this->assert) === 0) {
                if (($this->assertMode & self::ASSERT_IGNORE_MISSING) === 0) {
                    throw new Exception('Unexpected: ' . $this->formatEntry($gpio, $action, $data));
                }
            }

            else {
                @list($assert_gpio, $assert_action, $assert_data) = \array_shift($this->assert);

                if (($gpio !== $assert_gpio) || ($action !== $assert_action) || ($data !== $assert_data)) {
                    throw new Exception(
                        "\nExpected: " . $this->formatEntry($assert_gpio, $assert_action, $assert_data) . "\n"
                        . "Actual:   " . $this->formatEntry($gpio, $action, $data)
                    );
                }
            }
        }
    }

    public function __destruct()
    {
        if (((($this->assertMode & self::ASSERT_FAIL_EXCESS) !== 0)) && (\count($this->assert) > 0)) {
            throw new Exception('Expecting another ' . \count($this->assert) . ' actions.');
        }
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    public function printLog()
    {
        foreach ($this->log as list($gpio, $action, $data)) {
            echo $this->formatEntry($gpio, $action, $data) . "\n";
        }
    }

    /**
     * Common format for an action entry
     *
     * @param int $gpio
     * @param int $action
     * @param mixed $data
     * @return string
     */
    protected function formatEntry($gpio, $action, $data)
    {
        return sprintf('GPIO: %2u, action: %-24s', $gpio, $this->resolveConstant($action) . ($data !== null ? ',' : '')) . ($data !== null ? ' data: ' . var_export($data, true) : '');
    }

    /**
     * Helper method to get the name of a class constant with the supplied value
     *
     * @param mixed $constantValue
     * @return string
     */
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