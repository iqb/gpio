<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this code, see the bundled LICENSE file.
 */

namespace saw\gpio;

/**
 * Represents one GPIO pin
 * Access to
 *
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class Pin
{
    /**
     * @var int
     */
    protected $number;

    /**
     * @var type
     */
    protected $fd;


    /**
     * File handle to /sys/class/gpio/gpio$number/value to get/change direction
     * @var value
     */
    public $fd_value;

    /**
     * Current (cached) direction of the pin.
     *
     * @var in|out
     */
    protected $direction;

    /**
     * Base dir for all pin specific files in /sys
     */
    protected $sysdir;

    public function __construct($number)
    {
        $this->number = $number;
        $this->sysdir = '/sys/class/gpio/gpio' . $this->number;
        $this->enable();
        $this->direction = $this->readDirection();
        $this->fd_value = \fopen($this->sysdir . '/value', 'r+');
    }

    /**
     * Read the actual direction of the GPIO pin.
     *
     * @return in|out
     */
    public function readDirection()
    {
        $direction = \file_get_contents($this->sysdir . '/direction');
        return \trim($direction);
    }

    public function setDirection($newDirection)
    {
        if (($newDirection != 'in') && ($newDirection != 'out')) {
            throw new \InvalidArgumentException('GPIO pin direction can only be in or out');
        }

        \file_put_contents($this->sysdir . '/direction', $newDirection . "\n");
        $this->direction = $newDirection;
        return $this;
    }


    public function getValue()
    {
        \fseek($this->fd_value, 0);
        $data = \fread($this->fd_value, 2);
        return (int)\trim($data);
    }

    /**
     * Set the value of the GPIO pin (only if in output mode)
     *
     * @param bool $value
     * @return \saw\gpio\Pin $this for chaining
     */
    public function setValue($value)
    {
        if ($this->direction !== 'out') {
            throw new \RuntimeException('Can not set value of GPIO pin in input mode.');
        }

        if (!is_bool($value)) {
            throw new \InvalidArgumentException('GPIO pin value can only be 1 or 0');
        }

        if (2 !== \fwrite($this->fd_value, ($value ? '1' : '0') . "\n")) {
            throw new \RuntimeException('Could not write value ' . ($value ? '1' : '0') . ' to gpio port ' . $this->number);
        }
        \fflush($this->fd_value);

        return $this;
    }

    /**
     * Enable the GPIO pin via sysfs
     */
    public function enable()
    {
        return $this->changeState(true);
    }

    /**
     * Disable the GPIO pin via sysfs
     */
    public function disable()
    {
        return $this->changeState(false);
    }


    /**
     * Enable or disable the pin
     */
    private function changeState($enable)
    {
        // Check if we are already done
        \clearstatcache();
        if (\is_dir($this->sysdir) === $enable) {
            return true;
        }

        // Try to change state
        @\file_put_contents('/sys/class/gpio/' . ($enable ? '' : 'un') . 'export', $this->number . "\n");

        // Recheck
        \clearstatcache();
        if (\is_dir($this->sysdir) !== $enable) {
            throw new \RuntimeException('Failed to ' . ($enable ? 'enable' : 'disable') . ' GPIO pin "' . $this->number . '"');
        }

        return true;
    }
    
    public function __destruct()
    {
        $this->disable();
    }
}
