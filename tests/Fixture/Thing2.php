<?php

namespace bdk\BacktraceTests\Fixture;

use bdk\BacktraceTests\Fixture\SkipMe\Thing;

class Thing2 extends Thing
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
        $GLOBALS['xdebug_trace'] = \xdebug_get_function_stack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
	}
}