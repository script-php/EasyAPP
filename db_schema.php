<?php 

 # USERS TABLE
$tables[] = [
    'name'  => 'user',
    'field' => [
        [
            'name'              => 'user_id', 
            'type'              => 'bigint(22)',
            'auto_increment'    => true
        ],
        [
            'name'      => 'test_id', 
            'type'      => 'int(9)',
            'not_null'  => true,
            'default'   => 1
        ],
        [
            'name'  => 'username', 
            'type'  => 'varchar(30)',
            'not_null'  => true,
        ]
    ],
    'primary' => [
        'user_id'
    ],
    'engine'  => 'InnoDB',
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_general_ci'
];


