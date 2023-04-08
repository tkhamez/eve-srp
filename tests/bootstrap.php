<?php

use Test\TestHelper;

const ROOT_DIR = __DIR__ . '/..';

try {
    TestHelper::bootstrap([
        'APP_ENV' => 'dev',
        # 'DB_URL' => 'sqlite:///:memory:',
        'DB_URL' => 'mysql://root:eve_srp@eve_srp_db/eve_srp_test',
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
