<?php

// patterns
$router->pattern('id', '[0-9]+');
$router->pattern('page', '[0-9]+');

# GET
$router->get('/', 'home');

$router->get('/error', 'not_found');


# POST

# PUT

# DELETE

# PATCH

# Fallback route
$router->fallback('not_found'); 

// Grouped routes
// $router->prefix('/api/v1', function($router) {
//     $router->get('/users', 'api/users|index');
//     $router->post('/users', 'api/users|create');
//     $router->get('/users/{id}', 'api/users|show');
//     $router->put('/users/{id}', 'api/users|update');
//     $router->delete('/users/{id}', 'api/users|delete');
// });

// Nested prefixes also work
// $router->prefix('/admin', function($router) {
//     $router->prefix('/settings', function($router) {
//         $router->get('/general', 'admin/settings|general');
//         $router->get('/security', 'admin/settings|security');
//     });
// });


// pre($router->getRoutes());