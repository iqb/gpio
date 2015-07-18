<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this code, see the bundled LICENSE file.
 */

namespace saw\gpio;

/**
 * Represents one GPIO pin
 * Uses the /sys gpio interface of the linux kernel {@link https://www.kernel.org/doc/Documentation/gpio/sysfs.txt}.
 * 
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class Pin
{
    /**
     * Direction of the pin is input
     */
    const DIRECTION_IN = 'in';
    
    /**
     * Direction of the pin is output
     */
    const DIRECTION_OUT = 'out';
    
    /**
     * Edge detection is disabled, select() will not be able to detect changes
     */
    const EDGE_NONE = 'none';
    
    /**
     * Changes can be detected on rising edge only
     */
    const EDGE_RISING = 'rising';
    
    /**
     * Changes can be detected on falling edge only
     */
    const EDGE_FALLING = 'falling';
    
    /**
     * Changes can be detected on rising and falling edges
     */
    const EDGE_BOTH = 'both';
    
    
    /**
     * The GPIO number. This equals to the "BCM" value if you call "gpio readall" on a Raspberry
     * @var int
     */
    protected $number;

    /**
     * File handle to /sys/class/gpio/gpio$number/direction
     * @var resource
     */
    protected $fd_direction;
    
    /**
     * File handle to /sys/class/gpio/gpio$number/edge
     * @var resource
     */
    protected $fd_edge;
    
    /**
     * File handle to /sys/class/gpio/gpio$number/value to get/change value
     * Public so you can use stream_select() on it to check for value changes.
     * @var resource
     */
    public $fd_value;

    /**
     * Current (cached) direction of the pin.
     *
     * @var in|out
     */
    protected $direction;
    
    /**
     * The base directory for all GPIO related files.
     * To mock the sysfs files, change this directory.
     * 
     * @var string
     */
    protected $sys_gpio_base = '/sys/class/gpio';
    
    /**
     * Base dir for all pin specific files in /sys
     */
    protected $sysdir;
    
    /**
     * 
     * @param int $number
     * @param self::DIRECTION_IN|self::DIRECTION_OUT $direction
     * @param self::EDGE_NONE|self::EDGE_RISING|self::EDGE_FALLING||self::EDGE_BOTH $edge
     */
    public function __construct($number, $direction = null, $edge = null)
    {
        $this->number = $number;
        $this->sysdir = $this->sys_gpio_base . '/gpio' . $this->number;
        $this->enable();
        $this->direction = $this->readDirection();
        
        if ($direction !== null) {
            $this->setDirection($direction);
        }
        
        if ($edge !== null) {
            $this->setEdge($edge);
        }
    }

    /**
     * Read the actual direction of the GPIO pin.
     *
     * @return in|out
     */
    public function readDirection()
    {
        \fseek($this->fd_direction, 0);
        if (false === ($direction = \fread($this->fd_direction, 32))) {
            throw new \RuntimeException('Failed to get direction, error: ' . \error_get_last()['message']);
        }
        
        return \trim($direction);
    }
    
    /**
     * Change the direction of the GPIO pin.
     * 
     * @param in|out $newDirection
     * @return \saw\gpio\Pin
     */
    public function setDirection($newDirection)
    {
        // Nothing to do
        if ($newDirection == $this->direction) {
            return $this;
        }
        
        if ($newDirection == self::DIRECTION_IN) {
        }
        
        // Edge must be none, otherwise output can not be selected
        elseif ($newDirection == self::DIRECTION_OUT) {
            $this->setEdge(self::EDGE_NONE);
        }
        
        else {
            throw new \InvalidArgumentException('GPIO pin direction can only be "in" or "out"');
        }
        
        if ((false === ($written = \fwrite($this->fd_direction, $newDirection . "\n"))) || ($written != (\strlen($newDirection)+1))) {
            throw new \RuntimeException('Failed to change direction, error: ' . \error_get_last()['message']);
        }
        
        \fflush($this->fd_direction);
        
        $this->direction = $newDirection;
        return $this;
    }
    
    /**
     * Get the current edge setting of the pin.
     * Edge enables detection of changes via select()
     * 
     * @return self::EDGE_NONE|self::EDGE_RISING|self::EDGE_FALLING||self::EDGE_BOTH
     */
    public function getEdge()
    {
        \fseek($this->fd_edge, 0);
        if (false === ($edge = \fread($this->fd_edge, 32))) {
            throw new \RuntimeException('Failed to get edge, error: ' . \error_get_last()['message']);
        }
        
        return \trim($edge);
    }

    /**
     * Set the edge of the GPIO pin (only if in output mode)
     *
     * @param self::EDGE_NONE|self::EDGE_RISING|self::EDGE_FALLING||self::EDGE_BOTH $newEdge
     * @return \saw\gpio\Pin $this for chaining
     */
    public function setEdge($newEdge)
    {
        if ($this->direction !== self::DIRECTION_OUT) {
            throw new \RuntimeException('Can only set edge of GPIO pin in output mode.');
        }
        
        if (($newEdge !== self::EDGE_NONE) && ($newEdge !== self::EDGE_RISING) && ($newEdge !== self::EDGE_FALLING) && ($newEdge !== self::EDGE_BOTH)) {
            throw new \InvalidArgumentException('GPIO pin edge mode must be one of EDGE_NONE|EDGE_RISING|EDGE_FALLING|EDGE_BOTH');
        }

        if ((false === ($written = @\fwrite($this->fd_edge, $newEdge . "\n"))) || ($written != (\strlen($newEdge)+1))) {
            throw new \RuntimeException('Could not change edge to ' . $newEdge . ', error: ' . \error_get_last()['message']);
        }
        \fflush($this->fd_edge);

        return $this;
    }
    
    /**
     * Get the current value of the pin
     * 
     * @return bool
     */
    public function getValue()
    {
        \fseek($this->fd_value, 0);
        if (false === ($value = \fread($this->fd_value, 2))) {
            throw new \RuntimeException('Failed to get value, error: ' . \error_get_last()['message']);
        }
        
        return (bool)\trim($value);
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
        // Clean up open file handles
        if (!$enable) {
            @\fclose($this->fd_direction);
            $this->fd_direction = null;
            @\fclose($this->fd_edge);
            $this->fd_edge = null;
            @\fclose($this->fd_value);
            $this->fd_value = null;
        }
        
        // Check if we are already done
        \clearstatcache();
        if (\is_dir($this->sysdir) !== $enable) {
            // Try to change state
            @\file_put_contents($this->sys_gpio_base . '/' . ($enable ? '' : 'un') . 'export', $this->number . "\n");
        }
        
        // Recheck
        \clearstatcache();
        if (\is_dir($this->sysdir) !== $enable) {
            throw new \RuntimeException('Failed to ' . ($enable ? 'enable' : 'disable') . ' GPIO pin "' . $this->number . '"');
        }
        
        // Open file handles
        if ($enable) {
            if (!\is_resource($this->fd_direction)) {
               if (false === ($this->fd_direction = @\fopen($this->sysdir . '/direction', 'r+'))) {
                   throw new \RuntimeException('Can not open file ' . $this->sysdir . '/direction' . ', error: ' . \error_get_last()['message']);
               }
            }
            
            if (!\is_resource($this->fd_edge)) {
               if (false === ($this->fd_edge = @\fopen($this->sysdir . '/edge', 'r+'))) {
                   throw new \RuntimeException('Can not open file ' . $this->sysdir . '/edge' . ', error: ' . \error_get_last()['message']);
               }
            }
            
            if (!\is_resource($this->fd_value)) {
               if (false === ($this->fd_value = @\fopen($this->sysdir . '/value', 'r+'))) {
                   throw new \RuntimeException('Can not open file ' . $this->sysdir . '/value' . ', error: ' . \error_get_last()['message']);
               }
            }
        }

        return true;
    }
    
    public function __destruct()
    {
        $this->disable();
    }
}
