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
 * @covers \bdk\Backtrace\SkipInternal
 */
class BacktraceTest extends TestCase
{
    use AssertionTrait;

    public function testAddInternalClass()
    {
        Backtrace::addInternalClass('hello');
        Backtrace::addInternalClass(array('world'));
        self::assertTrue(true); // simply testing that the above did not raise an error
    }

    public function testAddContext()
    {
        $line = __LINE__ - 2;
        $backtrace = Backtrace::get(0, 1);
        $backtrace = Backtrace::addContext($backtrace, 0);
        $this->assertCount(1, $backtrace);
        $this->assertIsArray($backtrace[0]['context']);
        $this->assertCount(19, $backtrace[0]['context']);
        $this->assertSame('    public function testAddContext()' . "\n", $backtrace[0]['context'][$line]);
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
        $this->assertCount(5, $backtrace);
        $this->assertFalse($haveArgs);
        $this->assertSame(__FILE__, $backtrace[0]['file']);
        $this->assertSame($line, $backtrace[0]['line']);

        $line = __LINE__ + 1;
        $backtrace = Backtrace::get(Backtrace::INCL_ARGS, 5);
        // echo 'backtrace = ' . print_r($backtrace, true) . "\n";
        $haveArgs = false;
        foreach ($backtrace as $frame) {
            if (!empty($frame['args'])) {
                $haveArgs = true;
                break;
            }
        }
        $this->assertCount(5, $backtrace);
        $this->assertTrue($haveArgs);
        $this->assertSame(__FILE__, $backtrace[0]['file']);
        $this->assertSame($line, $backtrace[0]['line']);
    }

    public function testGetFileLines()
    {
        $this->assertFalse(Backtrace::getFileLines('/no/such/file.php'));
        $this->assertSame(array(
            1 => "<?php\n",
        ), Backtrace::getFileLines(__FILE__, 0, 1));
    }

    public function testGetFromException()
    {
        $line = __LINE__ + 1;
        $exception = new \Exception('this is a test');
        $backtrace = Backtrace::get(null, 3, $exception);
        $this->assertCount(3, $backtrace);
        $this->assertSame(__FILE__, $backtrace[0]['file']);
        $this->assertSame($line, $backtrace[0]['line']);
    }

    public function testGetFromExceptionParseError()
    {
        if (\class_exists('ParseError') === false) {
            $this->markTestSkipped('ParseError class does not available');
        }
        $exception = new \ParseError('parse error');
        $backtrace = Backtrace::get(null, 3, $exception);
        $this->assertCount(0, $backtrace);
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
        $this->assertSame($expect, $callerInfo);

        // @phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedGlobalFunctions
        $callerInfo = call_user_func(array($this, 'getCallerInfoHelper'));
        $line = __LINE__ - 1;
        $this->assertSame(array(
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
        $this->assertSame(array(
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
        $this->assertSame(array(
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

    public function testIsXdebugFuncStackAvail()
    {
        $isAvail = Backtrace::isXdebugFuncStackAvail();
        $this->assertIsBool($isAvail);
    }

    public function testXdebugGetFunctionStack()
    {
        $line = __LINE__ + 1;
        $stack = Backtrace::xdebugGetFunctionStack();
        self::assertIsArray($stack);
        self::assertSame(array(
            array(
                'class' => __CLASS__,
                'file' => $stack[\count($stack) - 2]['file'], // TestCase.php
                'function' => 'testXdebugGetFunctionStack',
                'line' => $stack[\count($stack) - 2]['line'],
                'params' => array(),
                'type' => 'dynamic',
            ),
            array(
                'class' => 'bdk\Backtrace',
                'file' => __FILE__,
                'function' => 'xdebugGetFunctionStack',
                'line' => $line,
                'params' => array(),
                'type' => 'static',
            ),
        ), \array_slice($stack, -2));
    }

    private function getCallerInfoHelper()
    {
        return Backtrace::getCallerInfo();
    }
}
