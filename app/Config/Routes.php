<?php

use CodeIgniter\Router\RouteCollection;
use App\Models\UserModel;
use App\Controllers\User;


/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('register', 'User::register');
$routes->post('login', 'User::login');
$routes->post('saveResult', 'Quiz::saveResult');

