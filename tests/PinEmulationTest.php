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
}