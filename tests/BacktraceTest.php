<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace;
use bdk\BacktraceTests\Fixture\ChildObj;
use bdk\BacktraceTests\Fixture\ParentObj;
use bdk\BacktraceTests\PolyFill\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Backtrace class
 *
 * @covers \bdk\Backtrace
 * @covers \bdk\Backtrace\Normalizer
 * @covers \bdk\Backtrace\SkipInternal
 */
class BacktraceTest extends TestCase
{
    use AssertionTrait;

    public function testAddInternalClass()
    {
        $internalClassesRef = new \ReflectionProperty('bdk\\Backtrace\\SkipInternal', 'internalClasses');
        $internalClassesRef->setAccessible(true);
        Backtrace::addInternalClass('foo\\bar');
        $internalClasses = $internalClassesRef->getValue();
        $this->assertSame(0, $internalClasses['classes']['foo\\bar']);
        Backtrace::addInternalClass(array(
            'foo\\bar',
            'ding\\dong',
        ), 1);
        $internalClasses = $internalClassesRef->getValue();
        $this->assertSame(1, $internalClasses['classes']['foo\\bar']);
        $this->assertSame(1, $internalClasses['classes']['ding\\dong']);
        $e = null;
        try {
            Backtrace::addInternalClass('foo\\bar', false);
        } catch (\InvalidArgumentException $e) {
        }
        $this->assertInstanceOf('InvalidArgumentException', $e);
        $this->assertSame('level must be an integer', $e->getMessage());
    }

    public function testAddContext()
    {
        $line = __LINE__ - 2;
        $backtrace = Backtrace::get(0, 1);
        $backtrace = Backtrace::addContext($backtrace, 0);
        // echo 'backtrace = ' . print_r($backtrace, true) . "\n";
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
        // echo 'backtrace = ' . print_r($backtrace, true) . "\n";
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
        $this->assertSame(array(
            'class' => __CLASS__,
            'classCalled' => 'bdk\BacktraceTests\BacktraceTest',
            'classContext' => 'bdk\BacktraceTests\BacktraceTest',
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'line' => $line,
            'type' => '->',
        ), $callerInfo);
        $callerInfo = \call_user_func(array($this, 'getCallerInfoHelper'));
        $line = __LINE__ - 1;
        $this->assertSame(array(
            'class' => __CLASS__,
            'classCalled' => 'bdk\BacktraceTests\BacktraceTest',
            'classContext' => 'bdk\BacktraceTests\BacktraceTest',
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
                'class' => __CLASS__,
                'classCalled' => __CLASS__,
                'classContext' => __CLASS__,
                'file' => __FILE__,
                'function' => __FUNCTION__,
                'line' => $line,
                'type' => '->',
            ),
            array(
                'class' => \get_class($child),
                'classCalled' => \get_class($child),
                'classContext' => \get_class($child),
                'file' => $childRef->getFileName(),
                'function' => 'extendMe',
                // 'line' => 10,
                'type' => '->',
            ),
            array(
                'class' => \get_class($parent),
                'classCalled' => \get_class($parent),
                'classContext' => \get_class($child),
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
                'class' => __CLASS__,
                'classCalled' => __CLASS__,
                'classContext' => __CLASS__,
                'file' => __FILE__,
                'function' => __FUNCTION__,
                'line' => $line,
                'type' => '->',
            ),
            array(
                'class' => \get_class($parent),
                'classCalled' => \get_class($child),
                'classContext' => \get_class($child),
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

    private function getCallerInfoHelper()
    {
        return Backtrace::getCallerInfo();
    }
}
