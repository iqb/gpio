<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;


class EmulatorTest extends \PHPUnit_Framework_TestCase
{
    protected $actions = [
        Emulator::ACTION_ENABLE,
        Emulator::ACTION_DISABLE,
        Emulator::ACTION_CHANGE_DIRECTION,
        Emulator::ACTION_CHANGE_EDGE,
        Emulator::ACTION_CHANGE_VALUE
    ];

    /**
     * Verify all the bitmask action works
     *
     * @test
     */
    public function testAssertMaskSetter()
    {
        $emulator = new Emulator();

        $emulator->setAssertMask(Emulator::ACTION_ALL);
        $this->assertSame(Emulator::ACTION_ALL, ($emulator->getAssertMask() & Emulator::ACTION_ALL));

        foreach ($this->actions as $action) {
            $this->assertSame($action, ($emulator->getAssertMask() & $action));
        }

        foreach ($this->actions as $action) {
            $emulator->setAssertMask($action);

            foreach ($this->actions as $testaction) {
                $ref = ($action === $testaction ? $testaction : 0);
                $this->assertSame($ref, ($emulator->getAssertMask() & $testaction));
            }
        }
    }

    /**
     * Verify all the bitmask action works
     *
     * @test
     */
    public function testAssertModeSetter()
    {
        $emulator = new Emulator();

        $emulator->setAssertMode(Emulator::ASSERT_FAIL_MISSING);
        $emulator->setAssertMode(Emulator::ASSERT_FAIL_EXCESS);
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_EXCESS);
        $this->assertSame(Emulator::ASSERT_FAIL_MISSING, ($emulator->getAssertMode() & Emulator::ASSERT_FAIL_MISSING));
        $this->assertSame(0, ($emulator->getAssertMode() & Emulator::ASSERT_IGNORE_MISSING));

        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_MISSING);
        $emulator->setAssertMode(Emulator::ASSERT_FAIL_EXCESS);
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_EXCESS);
        $this->assertSame(Emulator::ASSERT_IGNORE_MISSING, ($emulator->getAssertMode() & Emulator::ASSERT_IGNORE_MISSING));
        $this->assertSame(0, ($emulator->getAssertMode() & Emulator::ASSERT_FAIL_MISSING));

        $emulator->setAssertMode(Emulator::ASSERT_FAIL_EXCESS);
        $emulator->setAssertMode(Emulator::ASSERT_FAIL_MISSING);
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_MISSING);
        $this->assertSame(Emulator::ASSERT_FAIL_EXCESS, ($emulator->getAssertMode() & Emulator::ASSERT_FAIL_EXCESS));
        $this->assertSame(0, ($emulator->getAssertMode() & Emulator::ASSERT_IGNORE_EXCESS));

        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_EXCESS);
        $emulator->setAssertMode(Emulator::ASSERT_FAIL_MISSING);
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_MISSING);
        $this->assertSame(Emulator::ASSERT_IGNORE_EXCESS, ($emulator->getAssertMode() & Emulator::ASSERT_IGNORE_EXCESS));
        $this->assertSame(0, ($emulator->getAssertMode() & Emulator::ASSERT_FAIL_EXCESS));
    }

    /**
     * Verify all the bitmask action works
     *
     * @test
     */
    public function testLogMaskSetter()
    {
        $emulator = new Emulator();

        $emulator->setLogMask(Emulator::ACTION_ALL);
        $this->assertSame(Emulator::ACTION_ALL, ($emulator->getLogMask() & Emulator::ACTION_ALL));

        foreach ($this->actions as $action) {
            $this->assertSame($action, ($emulator->getLogMask() & $action));
        }

        foreach ($this->actions as $action) {
            $emulator->setLogMask($action);

            foreach ($this->actions as $testaction) {
                $ref = ($action === $testaction ? $testaction : 0);
                $this->assertSame($ref, ($emulator->getLogMask() & $testaction));
            }
        }
    }



}