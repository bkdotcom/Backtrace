<?php

// backward compatibility
$classMap = array(
    'PHPUnit_Framework_Exception' => 'PHPUnit\Framework\Exception',
    'PHPUnit_Framework_TestCase' => 'PHPUnit\Framework\TestCase',
    'PHPUnit_Framework_TestSuite' => 'PHPUnit\Framework\TestSuite',
);
foreach ($classMap as $old => $new) {
    if (!class_exists($new)) {
        class_alias($old, $new);
    }
}

require __DIR__ . '/../vendor/autoload.php';

$modifyTests = new \bdk\BacktraceTests\ModifyTests();
$modifyTests->modify(__DIR__);
