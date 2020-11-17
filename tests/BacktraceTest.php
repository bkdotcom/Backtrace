<?php

namespace bdk\BacktraceTests;

use bdk\Backtrace;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Backtrace class
 */
class BacktraceTest extends TestCase
{

    /**
     * Test
     *
     * @return void
     */
    public function testGet()
    {
        $line = __LINE__ + 1;
        $backtrace = Backtrace::get(null, 3);
        $this->assertCount(3, $backtrace);
        $this->assertSame(__FILE__, $backtrace[0]['file']);
        $this->assertSame($line, $backtrace[0]['line']);
    }

    /**
     * Test
     *
     * @return void
     */
    public function testGetCallerInfo()
    {
        $callerInfo = $this->getCallerInfoHelper();
        $this->assertSame(array(
            'class' => __CLASS__,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'line' => __LINE__ - 5,
            'type' => '->',
        ), $callerInfo);
        $callerInfo = \call_user_func(array($this, 'getCallerInfoHelper'));
        $this->assertSame(array(
            'class' => __CLASS__,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'line' => __LINE__ - 5,
            'type' => '->',
        ), $callerInfo);
    }

    private function getCallerInfoHelper()
    {
        return Backtrace::getCallerInfo();
    }
}
