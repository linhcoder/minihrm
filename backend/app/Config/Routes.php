<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('/', 'Api::dispatch/health');
$routes->match(['GET','POST','PUT','PATCH','DELETE'],'api/v1/(:any)','Api::dispatch/$1');
