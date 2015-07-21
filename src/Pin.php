<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this code, see the bundled LICENSE file.
 */

namespace iqb\gpio;

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
     * Current (cached) direction of the pin.
     *
     * @var self::DIRECTION_IN|self::DIRECTION_OUT
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
     * List of file handles required to work the GPIO pin
     * @var resource[]
     */
    protected $handles = [];


    /**
     *
     * @param int $number
     */
    public function __construct($number)
    {
        $this->number = $number;
        $this->sysdir = $this->sys_gpio_base . '/gpio' . $this->number;
    }

    /**
     * Check if the PIN is enabled or not
     *
     * @return bool
     */
    public function isEnabled()
    {
        return \is_resource($this->handles['value']);
    }

    /**
     * Get the file handle for the value file.
     * You can select() on this file handle in the exceptional array to get notified on changes.
     * Edge must be set other than none for this to work.
     *
     * @return resource
     */
    public function getValueHandle()
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('GPIO pin is not enabled.');
        }
        return $this->handles['value'];
    }

    /**
     * Read the actual direction of the GPIO pin.
     *
     * @return self::DIRECTION_IN|self::DIRECTION_OUT
     */
    public function readDirection()
    {
        return $this->readFromHandle('direction');
    }

    /**
     * Change the direction of the GPIO pin.
     *
     * @param self::DIRECTION_IN|self::DIRECTION_OUT $newDirection
     * @return \iqb\gpio\Pin
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
            if ($this->getEdge() !== self::EDGE_NONE) {
                $this->setEdge(self::EDGE_NONE);
            }
        }

        else {
            throw new \InvalidArgumentException('GPIO pin direction can only be "in" or "out"');
        }

        $this->writeToHandle('direction', $newDirection . "\n");

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
        $this->readFromHandle('edge');
    }

    /**
     * Set the edge of the GPIO pin (only if in output mode)
     *
     * @param self::EDGE_NONE|self::EDGE_RISING|self::EDGE_FALLING||self::EDGE_BOTH $newEdge
     * @return \iqb\gpio\Pin $this for chaining
     */
    public function setEdge($newEdge)
    {
        if ($this->direction !== self::DIRECTION_OUT) {
            throw new \RuntimeException('Can only set edge of GPIO pin in output mode.');
        }

        if (($newEdge !== self::EDGE_NONE) && ($newEdge !== self::EDGE_RISING) && ($newEdge !== self::EDGE_FALLING) && ($newEdge !== self::EDGE_BOTH)) {
            throw new \InvalidArgumentException('GPIO pin edge mode must be one of EDGE_NONE|EDGE_RISING|EDGE_FALLING|EDGE_BOTH');
        }

        $this->writeToHandle('edge', $newEdge . "\n");
        return $this;
    }

    /**
     * Get the current value of the pin
     *
     * @return bool
     */
    public function getValue()
    {
        return ($this->readFromHandle('value') != '0');
    }

    /**
     * Set the value of the GPIO pin (only if in output mode)
     *
     * @param bool $value
     * @return \iqb\gpio\Pin $this for chaining
     */
    public function setValue($value)
    {
        if ($this->direction !== 'out') {
            throw new \RuntimeException('Can not set value of GPIO pin in input mode.');
        }

        if (!is_bool($value)) {
            throw new \InvalidArgumentException('GPIO pin value can only be 1 or 0');
        }

        $this->writeToHandle('value', ($value ? '1' : '0') . "\n");
        return $this;
    }

    /**
     * Enable the GPIO pin via sysfs
     *
     * @return \iqb\gpio\Pin $this for chaining
     */
    public function enable()
    {
        return $this->changeState(true);
    }

    /**
     * Disable the GPIO pin via sysfs
     *
     * @return \iqb\gpio\Pin $this for chaining
     */
    public function disable()
    {
        return $this->changeState(false);
    }


    /**
     * Enable or disable the pin
     *
     * @return \iqb\gpio\Pin $this for chaining
     */
    private function changeState($enable)
    {
        // Clean up open file handles
        if (!$enable) {
            foreach (['direction', 'edge', 'value'] as $file) {
                $this->closeFile($file);
            }
        }

        // Check if we are already done
        if ($this->checkSysDir() !== $enable) {
            $this->openFile(($enable ? '' : 'un') . 'export', $this->sys_gpio_base . '/' . ($enable ? '' : 'un') . 'export');
            $this->writeToHandle(($enable ? '' : 'un') . 'export', $this->number . "\n");

            // Recheck
            if ($this->checkSysDir() !== $enable) {
                throw new \RuntimeException('Failed to ' . ($enable ? 'enable' : 'disable') . ' GPIO pin "' . $this->number . '"');
            }
        }

        // Open file handles
        if ($enable) {
            foreach (['direction', 'edge', 'value'] as $file) {
                $this->openFile($file, $this->sysdir . '/' . $file);
            }
        }

        return $this;
    }

    public function __destruct()
    {
        $this->disable();
        foreach ($this->handles as $handle) {
            @\fclose($handle);
        }
    }

    /**
     * Open a file handle to a sysfs file and store it in the supplied handle name
     *
     * @param string $handleName
     * @param $filename
     */
    protected function openFile($handleName, $fileName)
    {
        // File already opened
        if (isset($this->handles[$handleName]) && \is_resource($this->handles[$handleName])) {
            return;
        }

        if (false === ($fh = @\fopen($fileName, 'r+'))) {
            throw new \RuntimeException('Can not open file "' . $fileName . '", error: ' . \error_get_last()['message']);
        }

        $this->handles[$handleName] = $fh;
    }

    /**
     * Close a file handle to a sysfs file
     *
     * @param string $handleName
     */
    protected function closeFile($handleName)
    {
        if (!isset($this->handles[$handleName]) || ($this->handles[$handleName] === null)) {
            return;
        }

        @\fclose($this->handles[$handleName]);
        unset($this->handles[$handleName]);
    }

    /**
     * Try to read data from the named handle
     *
     * @param string $handleName
     * @param int $bytesToRead
     * @return string
     */
    protected function readFromHandle($handleName, $bytesToRead = 1024)
    {
        if (!isset($this->handles[$handleName]) || !is_resource($this->handles[$handleName])) {
            throw new \RuntimeException('Invalid handle "' . $handleName . '".');
        }

        // Try to seek to the beginning of the file, may be unnecessary
        @\fseek($this->handles[$handleName], 0);

        if (false === ($data = @\fread($this->handles[$handleName], $bytesToRead))) {
            throw new \RuntimeException('Failed to read from handle "' . $handleName . '", error: ' . \error_get_last()['message']);
        }

        return \trim($data);
    }

    /**
     * Try to write data to the named handle
     *
     * @param string $handleName
     * @param string $data
     */
    protected function writeToHandle($handleName, $data)
    {
        if (!isset($this->handles[$handleName]) || !is_resource($this->handles[$handleName])) {
            throw new \RuntimeException('Invalid handle "' . $handleName . '".');
        }

        if (false === ($written = @\fwrite($this->handles[$handleName], $data))) {
            throw new \RuntimeException('Failed to write to handle "' . $handleName . '", error: ' . \error_get_last()['message']);
        }

        if ($written !== \strlen($data)) {
            throw new \RuntimeException('Could not write ' . \strlen($data) . ' bytes to handle "' . $handleName . '", only ' . $written . ' bytes written!');
        }
        \fflush($this->handles[$handleName]);
    }

    /**
     * Checks if the GPIO pin dir in sys exists or not
     * @return bool
     */
    protected function checkSysDir()
    {
        \clearstatcache();
        return \is_dir($this->sysdir);
    }
}
