<?php

use Test\TestHelper;

const ROOT_DIR = __DIR__ . '/..';

try {
    /** @noinspection PhpUnhandledExceptionInspection */
    TestHelper::bootstrap([
        'APP_ENV' => 'dev',
        #'DB_URL' => 'sqlite:///:memory:',
        'DB_URL' => 'mysql://root:eve_srp@eve_srp_db/eve_srp_test',
        #'DB_URL' => 'postgres://postgres:eve_srp@eve_srp_db_postgres/test',
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
