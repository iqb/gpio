<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;


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
     * Verify pin must be enabled before value can be read
     * @test
     */
    public function testValueRequiresEnabled()
    {
        $pin = new PinEmulation(12);
        $pin->disable();
        $this->assertFalse($pin->isEnabled());

        try {
            $pin->getValue();
            $this->fail('Value must not be readable if pin is disabled.');
        } catch (Exception $e) {
        }
    }
}