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


    public function __construct($number)
    {
        $this->number = $number;

        // Enable the GPIO pin
        \file_put_contents('/sys/class/gpio/export', $this->number . "\n");

        if (!\is_dir('/sys/class/gpio/gpio' . $this->number)) {
            throw new \RuntimeException('Failed to enable GPIO pin "' . $this->number . '"');
        }

        $this->direction = $this->readDirection();
        $this->fd_value = \fopen('/sys/class/gpio/gpio' . $number . '/value', 'r+');
        \file_put_contents('/sys/class/gpio/gpio' . $this->number , '/edge', "both\n");
    }

    /**
     * Read the actual direction of the GPIO pin.
     *
     * @return in|out
     */
    public function readDirection()
    {
        $direction = \file_get_contents('/sys/class/gpio/gpio' . $number . '/direction');
        return \trim($direction);
    }

    public function setDirection($newDirection)
    {
        if (($newDirection != 'in') && ($newDirection != 'out')) {
            throw new \InvalidArgumentException('GPIO pin direction can only be in or out');
        }

        \file_put_contents('/sys/class/gpio/gpio' . $number . '/direction', $newDirection . "\n");
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
     * @param int $value 1|0
     * @return \saw\gpio\Pin $this for chaining
     */
    public function setValue($value)
    {
        if ($this->direction !== 'out') {
            throw new \RuntimeException('Can not set value of GPIO pin in input mode.');
        }

        if (($value !== 0) && ($value !== 1)) {
            throw new \InvalidArgumentException('GPIO pin value can only be 1 or 0');
        }

        \fwrite($this->fd_value, "$value\n");
        \fflush($this->fd_value);

        return $this;
    }
}
