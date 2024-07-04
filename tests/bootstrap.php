<?php

namespace bdk\Test\Backtrace;

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
require __DIR__ . '/Backtrace/bootstrapFunctionReplace.php';

$modifyTests = new \bdk\DevUtil\ModifyTests();
$modifyTests->modify(__DIR__);
