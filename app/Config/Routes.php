<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes = Services::routes();
$routes->setDefaultNamespace('App\\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index');
$routes->get('login', 'Auth::loginForm');
$routes->post('login', 'Auth::login');
$routes->get('register', 'Auth::registerForm');
$routes->post('register', 'Auth::register');
$routes->get('logout', 'Auth::logout');
$routes->get('dashboard', 'Portal::customer');
$routes->post('booking', 'Portal::book');
$routes->post('booking/(:num)/cancel', 'Portal::cancel/$1');
$routes->post('profile', 'Portal::profile');
$routes->post('support', 'Portal::support');
$routes->get('staff', 'Portal::staff');
$routes->get('staff/users', 'Portal::users');
$routes->post('staff/users/create', 'Auth::createEmployee');
$routes->post('staff/users/(:num)', 'Portal::updateEmployee/$1');
$routes->post('staff/users/(:num)/deactivate', 'Portal::deactivate/$1');
$routes->post('staff/booking/(:num)', 'Portal::updateBooking/$1');
$routes->post('staff/announcement', 'Portal::announcement');
$routes->post('staff/service/(:num)', 'Portal::updateService/$1');
$routes->post('staff/service', 'Portal::createService');
$routes->post('staff/payment', 'Portal::recordPayment');
$routes->post('staff/checkin', 'Portal::checkin');
$routes->post('staff/report', 'Portal::report');
$routes->post('staff/ticket/(:num)', 'Portal::updateTicket/$1');
