<?php

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
    public function testGetCallerInfo()
    {
        $callerInfo = $this->getCallerInfoHelper();
        $this->assertSame(array(
            'file' => __FILE__,
            'line' => __LINE__ - 3,
            'function' => __FUNCTION__,
            'class' => __CLASS__,
            'type' => '->',
        ), $callerInfo);
        $callerInfo = call_user_func(array($this, 'getCallerInfoHelper'));
        $this->assertSame(array(
            'file' => __FILE__,
            'line' => __LINE__ - 3,
            'function' => __FUNCTION__,
            'class' => __CLASS__,
            'type' => '->',
        ), $callerInfo);
    }

    private function getCallerInfoHelper()
    {
        return \bdk\Backtrace::getCallerInfo();
    }
}
