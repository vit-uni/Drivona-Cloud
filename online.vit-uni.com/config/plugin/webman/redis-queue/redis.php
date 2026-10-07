<?php
return [
    'default' => [
        'host' => 'redis://47.87.135.97:6379',
        'options' => [
            'auth' => '5623fdsg',
            'db' => 1,
            'prefix' => '',
            'max_attempts'  => 5,
            'retry_seconds' => 5,
        ],
        // Connection pool, supports only Swoole or Swow drivers.
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ]
    ],
];
