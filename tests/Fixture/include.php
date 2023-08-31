<?php

$php = <<<'EOD'
func3();

function func3()
{
    call_user_func_array('func4', array("they're", '"quotes"', 42, null, true));
}

function func4()
{
    $closure = static function () {
        $GLOBALS['xdebug_trace'] = \xdebug_get_function_stack();
        $GLOBALS['debug_backtrace'] = \debug_backtrace();
    };
    $closure();
}
EOD;

eval($php);
