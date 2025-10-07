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