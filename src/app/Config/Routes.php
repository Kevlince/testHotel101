<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Comments::index');
$routes->get('comments/list', 'Comments::list');
$routes->post('comments/add', 'Comments::add');
$routes->get('comments/delete/(:num)', 'Comments::delete/$1');
