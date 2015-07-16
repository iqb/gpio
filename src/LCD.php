<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this code, see the bundled LICENSE file.
 */

namespace saw\gpio;

/**
 * Abstraction to write data to HD44780 style 16x2 displays attached via GPIO
 * Based on the lcd_16x2.py script by Matt Hawkins, see http://www.raspberrypi-spy.co.uk/
 *
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class LCD
{
    /**
     * @var Pin
     */
    protected $pinRS;

    /**
     * @var Pin
     */
    protected $pinE;

    /**
     * @var Pin
     */
    protected $pinD4;

    /**
     * @var Pin
     */
    protected $pinD5;

    /**
     * @var Pin
     */
    protected $pinD6;

    /**
     * @var Pin
     */
    protected $pinD7;

    /**
     * Timeout in microseconds to wait for the display to respond
     *
     * @var int
     */
    protected $delay = 50;

    /**
     * Timeout in microseconds to wait until the display received a change to pin E
     *
     * @var int
     */
    protected $pulse = 50;


    public function __construct(Pin $rs, Pin $e, Pin $d4, Pin $d5, Pin $d6, Pin $d7)
    {
        $this->pinRS = $rs;
        $this->pinE  = $e;
        $this->pinD4 = $d4;
        $this->pinD5 = $d5;
        $this->pinD6 = $d6;
        $this->pinD7 = $d7;
    }

    public function initialize()
    {
        $this->pinRS->setDirection('out');
        $this->pinE->setDirection('out');
        $this->pinD4->setDirection('out');
        $this->pinD5->setDirection('out');
        $this->pinD6->setDirection('out');
        $this->pinD7->setDirection('out');

        $this->writeByte(0x33, true); // 110011 Initialise
        $this->writeByte(0x32, true); // 110010 Initialise
        $this->writeByte(0x06, true); // 000110 Cursor move direction
        $this->writeByte(0x0C, true); // 001100 Display On,Cursor Off, Blink Off
        $this->writeByte(0x28, true); // 101000 Data length, number of lines, font size
        $this->writeByte(0x01, true); // 000001 Clear display

        usleep($this->delay);
    }

    public function writeString($string, $line)
    {
        $string = str_pad($string, 16);

        $this->writeByte($line, true);

        for ($i=0; $i<16; $i++) {
            $this->writeByte(\ord($string[$i]));
        }
    }

    /**
     * Write a single byte to the display.
     *
     * @param int $byte Numerical value of the byte to write (0..255)
     * @param bool $isCommand Write to command or character storage
     */
    protected function writeByte($byte, $isCommand = false)
    {
        $this->pinRS->setValue(($isCommand == false));

        // Set high bits
        $this->pinD4->setValue(($byte & 0x10) == 0x10);
        $this->pinD5->setValue(($byte & 0x20) == 0x20);
        $this->pinD6->setValue(($byte & 0x40) == 0x40);
        $this->pinD7->setValue(($byte & 0x80) == 0x80);
        $this->toggleEnable();

        // Set low bits
        $this->pinD4->setValue(($byte & 0x01) == 0x01);
        $this->pinD5->setValue(($byte & 0x02) == 0x02);
        $this->pinD6->setValue(($byte & 0x04) == 0x04);
        $this->pinD7->setValue(($byte & 0x08) == 0x08);
        $this->toggleEnable();
    }

    /**
     * Signal that the display should read values from the PINs
     */
    protected function toggleEnable()
    {
        usleep($this->delay);
        $this->pinE->setValue(true);
        usleep($this->pulse);
        $this->pinE->setValue(false);
        usleep($this->delay);
    }
}
