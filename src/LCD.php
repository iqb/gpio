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

    /**
     * Enable all pins and make the display usable
     */
    public function initialize()
    {
        $this->pinRS->enable()->setDirection('out');
        $this->pinE->enable()->setDirection('out');
        $this->pinD4->enable()->setDirection('out');
        $this->pinD5->enable()->setDirection('out');
        $this->pinD6->enable()->setDirection('out');
        $this->pinD7->enable()->setDirection('out');

        $this->writeByte(0x33, true); // 110011 Initialise
        $this->writeByte(0x32, true); // 110010 Initialise
        $this->entryMode(true, false);
        $this->displayOnOffControl(true, false, false);
        $this->functionSet(false, true, false);
        $this->clearDisplay();

        usleep($this->delay);
    }

    /**
     * Write the supplied string to the display.
     * The string should not exceed 40 characters and the character can not contain multi byte characters (e.g. from utf-8).
     * View the datasheet of the display for valid characters.
     * $line specifies the line to use for multi line displays
     *
     * @param char[] $string
     * @param int $line
     */
    public function writeString($string, $line = 1)
    {
        $string = \str_pad($string, 16);

        $this->setDDRAMAddress(($line-1) * 40);
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

    /**
     * Clears the display (overwrites all chars with spaces).
     * Moves to position 0 and resets shift and cursor position.
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function clearDisplay()
    {
        $this->writeByte(0x01, true);
        return $this;
    }

    /**
     * Moves to position 0 and resets shift and cursor position.
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function returnHome()
    {
        $this->writeByte(0x02, true);
        return $this;
    }

    /**
     * Sets cursor move direction and specifies display shift.
     * These operations are performed during data write and read.
     */
    public function entryMode($increment = true, $shift = false)
    {
        $byte = 0x04;
        if ($increment) { $byte |= 0x02; }
        if ($shift) { $byte |= 0x01; }
        $this->writeByte($byte, true);
        return $this;
    }

    /**
     * Control display, cursor and blink
     *
     * @param bool $displayOn
     * @param bool $cursorOn
     * @param bool $blinkOn
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function displayOnOffControl($displayOn = true, $cursorOn = true, $blinkOn = true)
    {
        $byte = 0x08;
        if ($displayOn) { $byte |= 0x04; }
        if ($cursorOn) { $byte |= 0x02; }
        if ($blinkOn) { $byte |= 0x01; }
        $this->writeByte($byte, true);
        return $this;
    }

    /**
     * Set interface to 8 bit or 4 bit.
     * Display has 2 lines or not.
     * Use 5x10 dots font or 5x8 dots font.
     *
     * @param bool $dataLength8Bit
     * @param bool $twoDisplayLines
     * @param bool $largeFont
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function functionSet($dataLength8Bit = true, $twoDisplayLines = true, $largeFont = true)
    {
        $byte = 0x20;
        if ($dataLength8Bit) { $byte |= 0x10; }
        if ($twoDisplayLines) { $byte |= 0x08; }
        if ($largeFont) { $byte |= 0x04; }
        $this->writeByte($byte, true);
        return $this;
    }

    /**
     * Set the address of the CGROM
     *
     * @param int $address
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function setCGROMAddress($address)
    {
        $byte = 0x40;
        $byte |= ($address & 0x3F);
        $this->writeByte($byte, true);
        return $this;
    }

    /**
     * Set the address of the DDRAM, used for reading and writing
     *
     * @param int $address
     *
     * @return \saw\gpio\LCD $this for chaining
     */
    public function setDDRAMAddress($address)
    {
        $byte = 0x80;
        $byte |= ($address & 0x7F);
        $this->writeByte($byte, true);
        return $this;
    }
}
