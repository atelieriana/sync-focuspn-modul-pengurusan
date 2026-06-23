<?php

return [
    'oracle_focuspn' => [
        'driver' => 'oracle',
        'tns' => env('DB_TNS_FOCUSPN', ''),
        'host' => env('DB_HOST_FOCUSPN', ''),
        'port' => env('DB_PORT_FOCUSPN', '1521'),
        'database' => env('DB_DATABASE_FOCUSPN', ''),
        'service_name' => env('DB_SERVICE_NAME_FOCUSPN', ''),
        'username' => env('DB_USERNAME_FOCUSPN', ''),
        'password' => env('DB_PASSWORD_FOCUSPN', ''),
        'charset' => env('DB_CHARSET_FOCUSPN', 'AL32UTF8'),
        'prefix' => env('DB_PREFIX_FOCUSPN', ''),
        'prefix_schema' => env('DB_SCHEMA_PREFIX_FOCUSPN', ''),
        'edition' => env('DB_EDITION_FOCUSPN', 'ora$base'),
        'server_version' => env('DB_SERVER_VERSION_FOCUSPN', '11g'),
        'load_balance' => env('DB_LOAD_BALANCE_FOCUSPN', 'yes'),
        'max_name_len' => env('ORA_MAX_NAME_LEN_FOCUSPN', 30),
        'dynamic' => [],
        'sessionVars' => [
            'NLS_TIME_FORMAT' => 'HH24:MI:SS',
            'NLS_DATE_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
            'NLS_TIMESTAMP_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
            'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS TZH:TZM',
            'NLS_NUMERIC_CHARACTERS' => '.,',
        ],
        'options' => [
            PDO::ATTR_CASE => PDO::CASE_UPPER,
        ]
    ],
];
