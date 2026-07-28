#!/usr/bin/env php
<?php
// ABOUTME: Test runner for the WooCommerce Sample plugin suite — loads the
// ABOUTME: bootstrap stubs and executes every test_* method in the test classes.

require __DIR__ . '/tests/bootstrap.php';
require __DIR__ . '/tests/test-stock-reduction.php';

$red = "\033[31m";
$green = "\033[32m";
$reset = "\033[0m";

$passed = 0;
$failed = 0;
$failures = [];

$test_classes = ['Test_Sample_Stock_Reduction'];

foreach ($test_classes as $class) {
    foreach (get_class_methods($class) as $method) {
        if (strpos($method, 'test_') !== 0) {
            continue;
        }
        $test = new $class();
        try {
            if (method_exists($test, 'setUp')) {
                $test->setUp();
            }
            $test->$method();
            echo "{$green}✓{$reset} {$class}::{$method}\n";
            $passed++;
        } catch (Throwable $e) {
            echo "{$red}✗{$reset} {$class}::{$method}\n";
            $failed++;
            $failures[] = "{$class}::{$method}\n  " . $e->getMessage();
        }
    }
}

echo "\nResults: {$passed} passed, {$failed} failed\n";

if ($failed > 0) {
    echo "\n{$red}FAILURES:{$reset}\n" . implode("\n", $failures) . "\n";
    exit(1);
}
exit(0);