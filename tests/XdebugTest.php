<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace\Normalizer;
use bdk\Backtrace\Xdebug;
use bdk\PhpUnitPolyfill\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Backtrace class
 *
 * @covers \bdk\Backtrace\Xdebug
 */
class XdebugTest extends TestCase
{
    use AssertionTrait;

    /*
    public function testIsXdebugFuncStackAvail()
    {
        self::assertTrue(Xdebug::isXdebugFuncStackAvail());
    }
    */

    public function testXdebugGetFunctionStack()
    {
        $propRef = new \ReflectionProperty('bdk\\Backtrace\\Xdebug', 'isXdebugAvail');
        $propRef->setAccessible(true);
        $propRef->setValue(null);

        $GLOBALS['functionReturn']['phpversion'] = '2.5.9'; // call fix
        $GLOBALS['functionReturn']['error_get_last'] = array(
            'file' => '/path/to/file.php',
            'line' => 666,
            'message' => 'dang',
            'type' => E_ERROR,
        );
        $line = __LINE__ + 2;
        $magic = new \bdk\BacktraceTests\Fixture\Magic();
        $magic->foo;
        $stack = $GLOBALS['xdebug_stack'];
        $GLOBALS['functionReturn']['error_get_last'] = null;

        self::assertIsArray($stack);
        self::assertSame(array(
            array(
                'class' => __CLASS__,
                'file' => $stack[\count($stack) - 4]['file'], // TestCase.php
                'function' => __FUNCTION__,
                'line' => $stack[\count($stack) - 4]['line'],
                'params' => array(),
                'type' => 'dynamic',
            ),
            array(
                'class' => 'bdk\BacktraceTests\Fixture\Magic',
                'file' => $stack[\count($stack) - 3]['file'], // __FILE__, but we've activated xdebugfix
                'function' => '__get',
                'line' => $line,
                'params' => array(
                    'name' => '\'foo\'',
                ),
                'type' => 'dynamic',
            ),
            array(
                'class' => 'bdk\\Backtrace\\Xdebug',
                'file' => $stack[\count($stack) - 2]['file'], // TestCase.php
                'function' => 'getFunctionStack',
                'line' => $stack[\count($stack) - 2]['line'],
                'params' => array(
                    'maxDepth' => '???',
                ),
                'type' => 'static',
            ),
            array(
                'file' => '/path/to/file.php',
                'line' => 666,
            ),
        ), \array_slice($stack, -4));

        $line = __LINE__ + 1;
        require __DIR__ . '/Fixture/include_get.php';
        $stack = array_reverse($GLOBALS['xdebug_stack']);
        $stack = Normalizer::normalize($stack);
        // $stack = \array_map(function ($frame) {
            // unset($frame['object']);
            // return $frame;
        // }, $stack);
        self::assertIsArray($stack);
        self::assertSame(array(
            array(
                'args' => array(
                    'maxDepth' => '???',
                ),
                // 'class' => 'bdk\Backtrace',
                'file' => __DIR__ . '/Fixture/Magic.php',
                'function' => 'bdk\\Backtrace\\Xdebug::getFunctionStack',
                'line' => $stack[0]['line'],
                // 'type' => 'static',
                'object' => null,
            ),
            array(
                'args' => array(
                    'name' => 'foo',
                ),
                // 'class' => 'bdk\BacktraceTests\Fixture\Magic',
                'file' => __DIR__ . '/Fixture/include_get.php',
                'function' => 'bdk\BacktraceTests\Fixture\Magic->__get',
                'line' => 5,
                // 'type' => 'dynamic',
                'object' => null,
            ),
            array(
                'args' => array(
                    __DIR__ . '/Fixture/include_get.php',
                ),
                'file' => __FILE__,
                'function' => 'include or require',
                'line' => $line,
                'object' => null,
            ),
            array(
                'args' => array(),
                // 'class' => __CLASS__,
                'file' => $stack[3]['file'], // TestCase.php
                'function' => __CLASS__ . '->' . __FUNCTION__,
                'line' => $stack[3]['line'],
                // 'type' => 'dynamic',
                'object' => null,
            ),
        ), \array_slice($stack, 0, 4));

        $GLOBALS['functionReturn']['extension_loaded'] = false;
        $GLOBALS['functionReturn']['phpversion'] = null;

        $propRef = new \ReflectionProperty('bdk\\Backtrace\\Xdebug', 'isXdebugAvail');
        $propRef->setAccessible(true);
        $propRef->setValue(null);
        self::assertFalse(Xdebug::getFunctionStack());
        $propRef->setValue(true);
    }
}
