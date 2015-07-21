<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;

/**
 * @covers \iqb\gpio\Pin
 * @covers \iqb\gpio\PinEmulation
 */
class PinEmulationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testEnableDisable()
    {
        $pin = new PinEmulation(12);

        $this->assertFalse($pin->isEnabled());
        $pin->enable();
        $this->assertTrue($pin->isEnabled());
        $pin->disable();
        $this->assertFalse($pin->isEnabled());
    }

    /**
     *
     */
    public function testPropertyChanges()
    {
        $pin = new PinEmulation(12);
        $pin->enable();

        $pin->setDirection(Pin::DIRECTION_IN);
        $this->assertEquals(Pin::DIRECTION_IN, $pin->getDirection());
        $this->assertEquals(Pin::DIRECTION_IN, $pin->readDirection());
        $this->assertEquals(Pin::EDGE_NONE, $pin->getEdge());

        $pin->setEdge(Pin::EDGE_BOTH);
        $this->assertEquals(Pin::EDGE_BOTH, $pin->getEdge());

        $pin->setDirection(Pin::DIRECTION_OUT);
        $this->assertEquals(Pin::DIRECTION_OUT, $pin->getDirection());
        $this->assertEquals(Pin::DIRECTION_OUT, $pin->readDirection());

        // Change of the direction should clear the edge setting
        $this->assertEquals(Pin::EDGE_NONE, $pin->getEdge());

        $pin->setDirection(Pin::DIRECTION_IN);
        $this->assertEquals(Pin::DIRECTION_IN, $pin->getDirection());
        $this->assertEquals(Pin::DIRECTION_IN, $pin->readDirection());
    }

    /**
     * Verify edge can only be set in input mode
     * @test
     */
    public function testDenyEdgeChangeInOutputMode()
    {
        $pin = new PinEmulation(12);
        $pin->enable();

        $pin->setDirection(Pin::DIRECTION_IN);
        $this->assertSame(Pin::DIRECTION_IN, $pin->getDirection());
        $this->assertSame(Pin::EDGE_NONE, $pin->getEdge());

        foreach ([Pin::EDGE_FALLING, Pin::EDGE_NONE, Pin::EDGE_RISING, Pin::EDGE_BOTH] as $edge) {
            $pin->setEdge($edge);
            $this->assertSame($edge, $pin->getEdge());
        }

        $pin->setDirection(Pin::DIRECTION_OUT);
        $this->assertSame(Pin::DIRECTION_OUT, $pin->getDirection());
        $this->assertSame(Pin::EDGE_NONE, $pin->getEdge());

        try {
            $pin->setEdge(Pin::EDGE_BOTH);
            $this->fail('Edge must not be changeable if pin is in output mode.');
        } catch (Exception $e) {
        }
    }

    /**
     * Verify value getting/setting is handled correctly:
     * - get/set only if pin is enabled
     * - set only if in input mode
     * - set only boolean values
     * - get returns the same that was set
     *
     * @test
     */
    public function testValue()
    {
        $pin = new PinEmulation(12);
        $pin->disable();
        $this->assertFalse($pin->isEnabled());

        try {
            $pin->getValue();
            $this->fail('Value must not be readable if pin is disabled.');
        } catch (Exception $e) {
        }

        $pin = new PinEmulation(13);
        $pin->disable();
        $this->assertFalse($pin->isEnabled());

        try {
            $pin->setValue(false);
            $this->fail('Value must not be settable if pin is disabled.');
        } catch (Exception $e) {
        }

        $pin = new PinEmulation(14);
        $pin->enable();
        $this->assertTrue($pin->isEnabled());

        $pin->setDirection(Pin::DIRECTION_IN);
        $this->assertSame(Pin::DIRECTION_IN, $pin->getDirection());

        try {
            $pin->setValue(false);
            $this->fail('Value must not be settable if pin is in input mode.');
        } catch (Exception $e) {
        }

        $pin = new PinEmulation(15);
        $pin->enable();
        $this->assertTrue($pin->isEnabled());

        $pin->setDirection(Pin::DIRECTION_OUT);
        $this->assertSame(Pin::DIRECTION_OUT, $pin->getDirection());

        $pin->setValue(true);
        $this->assertTrue($pin->getValue());

        $pin->setValue(false);
        $this->assertFalse($pin->getValue());

        $pin->setValue(true);
        $this->assertTrue($pin->getValue());

        try {
            $pin->setValue((int)false);
            $this->fail('Only boolean values allowed.');
        } catch (Exception $e) {
        }

        try {
            $pin->setValue(null);
            $this->fail('Only boolean values allowed.');
        } catch (Exception $e) {
        }
    }
}