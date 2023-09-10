<?php

namespace bdk\BacktraceTests\Fixture;

use bdk\Backtrace;

class Magic
{
	public function __call($method, $args)
	{
        $GLOBALS['xdebug_trace'] = Backtrace::xdebugGetFunctionStack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
        return $args;
	}

	public function __get($name)
	{
		$GLOBALS['xdebug_stack'] = Backtrace::xdebugGetFunctionStack();
		return $name;
	}

	private function secret()
	{
        $GLOBALS['xdebug_trace'] = Backtrace::xdebugGetFunctionStack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
	}
}
