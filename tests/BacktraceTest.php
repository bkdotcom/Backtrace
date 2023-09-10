<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace;
use bdk\Backtrace\Normalizer;
use bdk\BacktraceTests\Fixture\ChildObj;
use bdk\BacktraceTests\Fixture\ParentObj;
use bdk\BacktraceTests\PolyFill\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Backtrace class
 *
 * @covers \bdk\Backtrace
 */
class BacktraceTest extends TestCase
{
    use AssertionTrait;

    public static function setUpBeforeClass(): void
    {
        $xdebugVer = \phpversion('xdebug');
        if (\version_compare($xdebugVer, '3.0.0', '<')) {
            \ini_set('xdebug.collect_params', '1');
        }
    }

    public function testAddInternalClass()
    {
        Backtrace::addInternalClass('hello');
        Backtrace::addInternalClass(array('world'));
        self::assertTrue(true); // simply testing that the above did not raise an error
    }

    /**
     * Test
     *
     * @return void
     */
    public function testGet()
    {
        $line = __LINE__ + 1;
        $backtrace = Backtrace::get(null, 5);
        $haveArgs = false;
        foreach ($backtrace as $frame) {
            if (!empty($frame['args'])) {
                $haveArgs = true;
                break;
            }
        }
        self::assertCount(5, $backtrace);
        self::assertFalse($haveArgs);
        self::assertSame(__FILE__, $backtrace[0]['file']);
        self::assertSame($line, $backtrace[0]['line']);

        $line = __LINE__ + 1;
        $backtrace = Backtrace::get(Backtrace::INCL_ARGS, 5);
        $haveArgs = false;
        foreach ($backtrace as $frame) {
            if (!empty($frame['args'])) {
                $haveArgs = true;
                break;
            }
        }
        self::assertCount(5, $backtrace);
        self::assertTrue($haveArgs);
        self::assertSame(__FILE__, $backtrace[0]['file']);
        self::assertSame($line, $backtrace[0]['line']);
    }

    public function testGetFromException()
    {
        $line = __LINE__ + 1;
        $exception = new \Exception('this is a test');
        $backtrace = Backtrace::get(null, 3, $exception);
        self::assertCount(3, $backtrace);
        self::assertSame(__FILE__, $backtrace[0]['file']);
        self::assertSame($line, $backtrace[0]['line']);
    }

    public function testGetFromExceptionParseError()
    {
        if (\class_exists('ParseError') === false) {
            $this->markTestSkipped('ParseError class not available');
        }
        $exception = new \ParseError('parse error');
        $backtrace = Backtrace::get(null, 3, $exception);
        self::assertCount(0, $backtrace);
    }

    /**
     * Test
     *
     * @return void
     */
    public function testGetCallerInfo()
    {
        $callerInfo = $this->getCallerInfoHelper();
        $line = __LINE__ - 1;
        $expect = array(
            'args' => array(),
            'class' => __CLASS__,
            'classCalled' => 'bdk\BacktraceTests\BacktraceTest',
            'classContext' => 'bdk\BacktraceTests\BacktraceTest',
            'evalLine' => null,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'line' => $line,
            'type' => '->',
        );
        self::assertSame($expect, $callerInfo);

        // @phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedGlobalFunctions
        $callerInfo = call_user_func(array($this, 'getCallerInfoHelper'));
        $line = __LINE__ - 1;
        self::assertSame(array(
            'args' => array(),
            'class' => __CLASS__,
            'classCalled' => 'bdk\BacktraceTests\BacktraceTest',
            'classContext' => 'bdk\BacktraceTests\BacktraceTest',
            'evalLine' => null,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'line' => $line,
            'type' => '->',
        ), $callerInfo);
    }

    public function testGetCallerInfoClassContext()
    {
        /*
        \bdk\BacktraceTests\Fixture\ChildObj::methodStatic();
        $callerInfo = \bdk\BacktraceTests\Fixture\ChildObj::$callerInfo;
        echo \print_r($callerInfo, true) . "\n";
        */

        $child = new ChildObj();
        $parent = new ParentObj();
        $childRef = new \ReflectionObject($child);
        $parentRef = new \ReflectionObject($parent);

        ChildObj::$callerInfoStack = array();
        $child->extendMe();
        $line = __LINE__ - 1;
        $callerInfoStack = ChildObj::$callerInfoStack;
        unset($callerInfoStack[1]['line'], $callerInfoStack[2]['line']);
        // echo 'callerInfoStack = ' . \print_r($callerInfoStack, true) . "\n";
        self::assertSame(array(
            array(
                'args' => array(),
                'class' => __CLASS__,
                'classCalled' => __CLASS__,
                'classContext' => __CLASS__,
                'evalLine' => null,
                'file' => __FILE__,
                'function' => __FUNCTION__,
                'line' => $line,
                'type' => '->',
            ),
            array(
                'args' => array(),
                'class' => \get_class($child),
                'classCalled' => \get_class($child),
                'classContext' => \get_class($child),
                'evalLine' => null,
                'file' => $childRef->getFileName(),
                'function' => 'extendMe',
                // 'line' => 10,
                'type' => '->',
            ),
            array(
                'args' => array(),
                'class' => \get_class($parent),
                'classCalled' => \get_class($parent),
                'classContext' => \get_class($child),
                'evalLine' => null,
                'file' => $parentRef->getFileName(),
                'function' => 'extendMe',
                // 'line' => 12
                'type' => '->',
            ),
        ), $callerInfoStack);

        /*
        \bdk\BacktraceTests\Fixture\ChildObj::method2Static();
        $callerInfo = \bdk\BacktraceTests\Fixture\ChildObj::$callerInfo;
        echo \print_r($callerInfo, true) . "\n";
        */

        ChildObj::$callerInfoStack = array();
        $child->inherited();
        $line = __LINE__ - 1;
        $callerInfoStack = ChildObj::$callerInfoStack;
        unset($callerInfoStack[1]['line']);
        // echo 'callerInfoStack = ' . \print_r($callerInfoStack, true) . "\n";
        self::assertSame(array(
            array(
                'args' => array(),
                'class' => __CLASS__,
                'classCalled' => __CLASS__,
                'classContext' => __CLASS__,
                'evalLine' => null,
                'file' => __FILE__,
                'function' => __FUNCTION__,
                'line' => $line,
                'type' => '->',
            ),
            array(
                'args' => array(),
                'class' => \get_class($parent),
                'classCalled' => \get_class($child),
                'classContext' => \get_class($child),
                'evalLine' => null,
                'file' => $parentRef->getFileName(),
                'function' => 'inherited',
                // 'line' => 10,
                'type' => '->',
            ),
        ), $callerInfoStack);
    }

    /*
    public function testIsXdebugFuncStackAvail()
    {
        self::assertTrue(Backtrace::isXdebugFuncStackAvail());
    }
    */

    public function testXdebugGetFunctionStack()
    {
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
                'class' => 'bdk\Backtrace',
                'file' => $stack[\count($stack) - 2]['file'], // TestCase.php
                'function' => 'xdebugGetFunctionStack',
                'line' => $stack[\count($stack) - 2]['line'],
                'params' => array(),
                'type' => 'static',
            ),
            array(
                'file' => '/path/to/file.php',
                'line' => 666,
            ),
        ), \array_slice($stack, -4));

        $line = __LINE__ + 1;
        require __DIR__ . '/fixture/include_get.php';
        $stack = array_reverse($GLOBALS['xdebug_stack']);
        $stack = Normalizer::normalize($stack);
        // $stack = \array_map(function ($frame) {
            // unset($frame['object']);
            // return $frame;
        // }, $stack);
        self::assertIsArray($stack);
        self::assertSame(array(
            array(
                'args' => array(),
                // 'class' => 'bdk\Backtrace',
                'file' => __DIR__ . '/Fixture/Magic.php',
                'function' => 'bdk\Backtrace::xdebugGetFunctionStack',
                'line' => $stack[0]['line'],
                // 'type' => 'static',
                'object' => null,
            ),
            array(
                'args' => array(
                    'name' => 'foo',
                ),
                // 'class' => 'bdk\BacktraceTests\Fixture\Magic',
                'file' => __DIR__ . '/fixture/include_get.php',
                'function' => 'bdk\BacktraceTests\Fixture\Magic->__get',
                'line' => 5,
                // 'type' => 'dynamic',
                'object' => null,
            ),
            array(
                'args' => array(
                    __DIR__ . '/fixture/include_get.php',
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

        $propRef = new \ReflectionProperty('bdk\\Backtrace', 'isXdebugAvail');
        $propRef->setAccessible(true);
        $propRef->setValue(null);
        self::assertFalse(Backtrace::xdebugGetFunctionStack());
        $propRef->setValue(true);
    }

    private function getCallerInfoHelper()
    {
        return Backtrace::getCallerInfo();
    }
}
