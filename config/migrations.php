<?php
return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],

    'migrations_paths' => [
        'EveSrp\Migrations' => 'src/Migrations',
    ],

    'all_or_nothing' => true,
    'transactional' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
    'connection' => null,
    'em' => null,
];
