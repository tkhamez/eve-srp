<?php

use Test\TestHelper;

const ROOT_DIR = __DIR__ . '/..';

try {
    /** @noinspection PhpUnhandledExceptionInspection */
    TestHelper::bootstrap();
} catch (Exception $e) {
    echo $e->getMessage();
}
