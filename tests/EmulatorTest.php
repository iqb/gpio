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

    /**
     * @test
     */
    public function testAssert()
    {
        $emulator = new Emulator();
        $emulator->setAssertMask(Emulator::ACTION_CHANGE_VALUE);

        $pin1 = $emulator->createPin(1);
        $pin1->enable();
        $pin1->setDirection(Pin::DIRECTION_OUT);

        $emulator->assert([
            [1, Emulator::ACTION_CHANGE_VALUE, true],
            [1, Emulator::ACTION_CHANGE_VALUE, false],
            [1, Emulator::ACTION_CHANGE_VALUE, true],
        ]);

        $pin1->setValue(true);
        $pin1->setValue(false);
        $pin1->setValue(true);

        // Verify ASSERT_IGNORE_MISSING works with empty assert list
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_MISSING);
        $pin1->setValue(true);

        // Verify ASSERT_FAIL_MISSING throws on empty assert list
        $emulator->setAssertMode(Emulator::ASSERT_FAIL_MISSING);

        try {
            $pin1->setValue(true);
            $this->fail('ASSERT_FAIL_MISSING not working!');
        } catch (Exception $e) {
        }

        // Fail an assert
        $emulator->assert([
            [1, Emulator::ACTION_CHANGE_VALUE, true],
        ]);

        try {
            $pin1->setValue(false);
            $this->fail('Faulty assertion was ignored!');
        } catch (Exception $e) {
            $emulator->assert([]);
        }

        // Verify ASSERT_FAIL_EXCESS throws on remaining expected actions
        $emulator2 = clone $emulator;
        $emulator2->setAssertMode(Emulator::ASSERT_FAIL_EXCESS);
        $emulator2->assert([
            [1, Emulator::ACTION_CHANGE_VALUE, true],
        ]);

        try {
            unset($emulator2);
            $this->fail('ASSERT_FAIL_EXCESS not working!');
        } catch (Exception $e) {
        }

        // Verify ASSERT_IGNORE_EXCESS works
        $emulator->setAssertMode(Emulator::ASSERT_IGNORE_EXCESS);
        $emulator->assert([
            [1, Emulator::ACTION_CHANGE_VALUE, true],
        ]);
        unset($emulator);
    }

}