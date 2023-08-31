<?php

namespace bdk\BacktraceTests\Fixture;

class Magic
{
	public function __call($method, $args)
	{
        $GLOBALS['xdebug_trace'] = \xdebug_get_function_stack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
        return $args;
	}

	private function secret()
	{
        $GLOBALS['xdebug_trace'] = \xdebug_get_function_stack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
	}
}
