<?php 

$tables[] = [
    'name'  => 'user',
    'field' => [
        [
            'name'              => 'user_idd', 
            'type'              => 'bigint(21)',
            'auto_increment'    => true
        ],
        [
            'name'      => 'test_id', 
            'type'      => 'int',
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
        'user_idd'
    ],
    'engine'  => 'InnoDB',
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_general_ci'
];


// $tables[] = [
//     'name'  => 'user2',
//     'field' => [
//         [
//             'name'              => 'id', 
//             'type'              => 'bigint(21)',
//             'auto_increment'    => true
//         ],
//         [
//             'name'      => 'user_group_id', 
//             'type'      => 'in',
//             'not_null'  => true,
//             'default'   => 1
//         ],
//         [
//             'name'  => 'username', 
//             'type'  => 'varchar(30)',
//             'not_null'  => true,
//         ]
//     ],
//     'primary' => [
//         'id'
//     ],
//     'foreign' => [
//         [
//             'key'   => 'user_group_id',
//             'table' => 'user',
//             'field' => 'test_id'
//         ]
//     ],
//     'engine'  => 'InnoDB',
//     'charset' => 'utf8mb4',
//     'collate' => 'utf8mb4_general_ci'
// ];




// DROP TABLE IF EXISTS `user`;
// CREATE TABLE `user` (
//  `user_id` int(11) AUTO_INCREMENT,
//  `user_group_id` int(11) NOT NULL DEFAULT '1',

//  `username` varchar(20) NOT NULL,
//  `password` varchar(255) NOT NULL,
//  `firstname` varchar(32) NOT NULL,
//  `lastname` varchar(32) NOT NULL,
//  `email` varchar(96) NOT NULL,
//  `image` varchar(255) NOT NULL,
//  `code` varchar(40) NOT NULL,
//  `ip` varchar(40) NOT NULL,
//  `status` tinyint(1) NOT NULL,
//  `date_added` datetime NOT NULL DEFAULT NOW(),
//  PRIMARY KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;