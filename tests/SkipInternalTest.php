<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace;
use bdk\Backtrace\Normalizer;
use bdk\Backtrace\SkipInternal;
use bdk\BacktraceTests\PolyFill\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \bdk\Backtrace\SkipInternal
 */
class SkipInternalTest extends TestCase
{
    use AssertionTrait;

    public function setUp(): void
    {
        $internalClassesRef = new \ReflectionProperty('bdk\\Backtrace\\SkipInternal', 'internalClasses');
        $internalClassesRef->setAccessible(true);
        $internalClassesRef->setValue(array(
            'classes' => array(),
            'levelCurrent' => null,
            'levels' => array(),
            'regex' => null,
        ));
        SkipInternal::addInternalClass('bdk\\Backtrace');
        SkipInternal::addInternalClass('ReflectionMethod');
        SkipInternal::addInternalClass('PHPUnit', 1);
    }

    public function testAddInternalClass()
    {
        SkipInternal::addInternalClass('foo\\bar');

        $internalClassesRef = new \ReflectionProperty('bdk\\Backtrace\\SkipInternal', 'internalClasses');
        $internalClassesRef->setAccessible(true);
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
            SkipInternal::addInternalClass('foo\\bar', false);
        } catch (\InvalidArgumentException $e) {
            // meh
        }
        $this->assertInstanceOf('InvalidArgumentException', $e);
        $this->assertSame('level must be an integer', $e->getMessage());
    }

    public function testRemoveInternalFrames()
    {
        SkipInternal::addInternalClass('bdk\\BacktraceTests\\Fixture\\SkipMe');

        $line = __LINE__ + 2;
        $closure = static function ($php) {
            eval($php);
        };
        $closure('
            $thing = new \bdk\BacktraceTests\Fixture\SkipMe\Thing();
            $thing->a();
        ');
        $trace = $GLOBALS['debug_backtrace'];
        $trace = Normalizer::normalize($trace);
        $trace = SkipInternal::removeInternalFrames($trace);

        self::assertSame(array(
            'args' => array(),
            'evalLine' => $line,
            'file' => __FILE__,
            'function' => 'bdk\BacktraceTests\Fixture\SkipMe\Thing->a',
            'line' => 3,
        ), \array_diff_key($trace[0], \array_flip(array('object'))));
        self::assertInstanceOf('bdk\BacktraceTests\Fixture\SkipMe\Thing', $trace[0]['object']);
    }

    public function testRemoveInternalFramesSubclass()
    {
        SkipInternal::addInternalClass('bdk\\BacktraceTests\\Fixture\\SkipMe\\Thing');

        $line = __LINE__ + 2;
        $closure = static function ($php) {
            eval($php);
        };
        $closure('
            $thing = new \bdk\BacktraceTests\Fixture\Thing2();
            $thing->a();
        ');
        $trace = $GLOBALS['debug_backtrace'];
        $trace = Normalizer::normalize($trace);
        $trace = SkipInternal::removeInternalFrames($trace);

        self::assertSame(array(
            'args' => array(),
            'evalLine' => $line,
            'file' => __FILE__,
            'function' => 'bdk\BacktraceTests\Fixture\Thing2->a',
            'line' => 3,
        ), \array_diff_key($trace[0], \array_flip(array('object'))));
        self::assertInstanceOf('bdk\BacktraceTests\Fixture\Thing2', $trace[0]['object']);
    }

    public function testRemoveInternalFramesAllInternal()
    {
        SkipInternal::addInternalClass('bdk\\BacktraceTests');

        $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = Normalizer::normalize($trace);

        // github actions last frame looks like the below
        $trace[] = array(
            'args' => array(
                '/home/runner/work/Backtrace/Backtrace/vendor/phpunit/phpunit/phpunit',
            ),
            'evalLine' => null,
            'file' => '/home/runner/work/Backtrace/Backtrace/vendor/bin/phpunit',
            'function' => 'include',
            'line' => 122,
            'object' => null,
        );

        $trace = SkipInternal::removeInternalFrames($trace);

        self::assertSame(__CLASS__ . '->' . __FUNCTION__, $trace[0]['function']);
    }

    public function testIsSkippableMagic()
    {
        $magic = new \bdk\BacktraceTests\Fixture\Magic();
        $magic->test();

        $trace = $GLOBALS['debug_backtrace'];
        $trace = Normalizer::normalize($trace);
        $trace = SkipInternal::removeInternalFrames($trace, 5);

        self::assertSame('bdk\BacktraceTests\Fixture\Magic->__call', $trace[0]['function']);
    }

    public function testIsSkippableInvoke()
    {
        $magic = new \bdk\BacktraceTests\Fixture\Magic();
        $refMethod = new \ReflectionMethod($magic, 'secret');
        $refMethod->setAccessible(true);
        $refMethod->invoke($magic);

        $trace = $GLOBALS['debug_backtrace'];
        $trace = Normalizer::normalize($trace);
        $trace = SkipInternal::removeInternalFrames($trace);

        self::assertSame('bdk\BacktraceTests\Fixture\Magic->secret', $trace[0]['function']);
    }

    protected static function DumpTrace($label, $trace, $limit = 0)
    {
        if ($limit > 0) {
            $trace = \array_slice($trace, 0, $limit);
        }
        \bdk\Debug::varDump($label, \array_map(function ($frame) {
            \ksort($frame);
            unset($frame['args']);
            unset($frame['object']);
            return $frame;
        }, $trace));
    }
}
