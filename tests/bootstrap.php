<?php

namespace bdk\Backtrace;

$GLOBALS['functionReturn'] = array(
    'error_get_last' => null,
    'extension_loaded' => null,
    'phpversion' => null,
);

function error_get_last()
{
    return isset($GLOBALS['functionReturn']['error_get_last'])
        ? $GLOBALS['functionReturn']['error_get_last']
        : \error_get_last();
}

function extension_loaded($extensionName)
{
    return isset($GLOBALS['functionReturn']['extension_loaded'])
        ? $GLOBALS['functionReturn']['extension_loaded']
        : \extension_loaded($extensionName);
}

function phpversion($extensionName)
{
    return isset($GLOBALS['functionReturn']['phpversion'])
        ? $GLOBALS['functionReturn']['phpversion']
        : \phpversion($extensionName);
}

namespace bdk\BacktraceTests;

// backward compatibility
$classMap = array(
    'PHPUnit_Framework_Exception' => 'PHPUnit\Framework\Exception',
    'PHPUnit_Framework_TestCase' => 'PHPUnit\Framework\TestCase',
    'PHPUnit_Framework_TestSuite' => 'PHPUnit\Framework\TestSuite',
);
foreach ($classMap as $old => $new) {
    if (\class_exists($new) === false) {
        \class_alias($old, $new);
    }
}

require __DIR__ . '/../vendor/autoload.php';

$modifyTests = new \bdk\DevUtil\ModifyTests();
$modifyTests->modify(__DIR__);
