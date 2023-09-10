<?php

namespace bdk\BacktraceTests\Fixture\SkipMe;

use bdk\Backtrace;

class Thing
{
	public function a()
	{
		$this->b();
	}

	public function b()
	{
		$this->c();
	}

	public function c()
	{
        $GLOBALS['xdebug_trace'] = Backtrace::xdebugGetFunctionStack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
	}
}
