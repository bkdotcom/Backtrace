<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace\Normalizer;
use bdk\BacktraceTests\PolyFill\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \bdk\Backtrace\Normalizer
 */
class NormalizerTest extends TestCase
{
    use AssertionTrait;

    public function testNormalize()
    {
        func1();

        $trace = $GLOBALS['debug_backtrace'];
        /*
        \bdk\Debug::varDump('debug_backtrace', \array_map(function ($frame) {
            ksort($frame);
            unset($frame['object']);
            return $frame;
        }, \array_slice($trace, 0, 6)));
        */
        $trace = Normalizer::normalize($trace);
        self::assertSame('{closure}', $trace[0]['function']);
        self::assertSame('bdk\BacktraceTests\func2', $trace[1]['function']);
        self::assertSame(array(
            "they're",
            '"quotes"',
            42,
            null,
            true,
        ), $trace[1]['args']);
        self::assertSame('bdk\BacktraceTests\func1', $trace[2]['function']);

        $trace = \array_reverse($GLOBALS['xdebug_trace']);
        /*
        \bdk\Debug::varDump('xdebug_get_function_stack', \array_map(function ($frame) {
            ksort($frame);
            // unset($frame['args']);
            return $frame;
        }, \array_slice($trace, 0, 6)));
        */
        $trace = Normalizer::normalize($trace);
        self::assertSame('{closure}', $trace[0]['function']);
        self::assertSame('bdk\BacktraceTests\func2', $trace[1]['function']);
        self::assertSame(array(
            "they're",
            '"quotes"',
            42,
            null,
            true,
        ), $trace[1]['args']);
        self::assertSame('bdk\BacktraceTests\func1', $trace[2]['function']);
    }

    public function testNormalizeInclude()
    {
        self::assertTrue(true);

        require __DIR__ . '/Fixture/include.php';

        $trace = $GLOBALS['debug_backtrace'];
        /*
        \bdk\Debug::varDump('debug_backtrace', \array_map(function ($frame) {
            ksort($frame);
            unset($frame['object']);
            return $frame;
        }, \array_slice($trace, 0, 6)));
        */
        $trace = Normalizer::normalize($trace);
        self::assertSame('{closure}', $trace[0]['function']);
        self::assertSame('func4', $trace[1]['function']);
        self::assertSame(array(
            "they're",
            '"quotes"',
            42,
            null,
            true,
        ), $trace[1]['args']);
        self::assertSame('func3', $trace[2]['function']);
        self::assertSame('eval', $trace[3]['function']);
        self::assertSame('require', $trace[4]['function']);

        $trace = \array_reverse($GLOBALS['xdebug_trace']);
        /*
        \bdk\Debug::varDump('xdebug_get_function_stack', \array_map(function ($frame) {
            ksort($frame);
            // unset($frame['args']);
            return $frame;
        }, \array_slice($trace, 0, 6)));
        */
        $trace = Normalizer::normalize($trace);
        self::assertSame('{closure}', $trace[0]['function']);
        self::assertSame('func4', $trace[1]['function']);
        self::assertSame(array(
            "they're",
            '"quotes"',
            42,
            null,
            true,
        ), $trace[1]['args']);
        self::assertSame('func3', $trace[2]['function']);
        self::assertSame('eval', $trace[3]['function']);
        self::assertSame('include or require', $trace[4]['function']);
    }
}

function func1()
{
    call_user_func_array('bdk\BacktraceTests\func2', array("they're", '"quotes"', 42, null, true));
}

function func2()
{
    $closure = function () {
        $GLOBALS['xdebug_trace'] = \xdebug_get_function_stack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
    };
    $closure();
}
