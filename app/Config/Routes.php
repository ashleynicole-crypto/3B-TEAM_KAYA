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
$routes->post('booking/(:num)/reschedule', 'Portal::requestReschedule/$1');
$routes->post('profile', 'Portal::profile');
$routes->get('dashboard/password', 'Portal::customerPasswordForm');
$routes->post('dashboard/password', 'Portal::changeCustomerPassword');
$routes->post('support', 'Portal::support');
$routes->post('quote', 'Portal::quote');
$routes->post('testimonial', 'Portal::testimonial');
$routes->get('staff', 'Portal::staff');
$routes->get('staff/password', 'Portal::employeePasswordForm');
$routes->post('staff/password', 'Portal::changeEmployeePassword');
$routes->get('staff/profile', 'Portal::employeeProfile');
$routes->post('staff/profile', 'Portal::updateEmployeeProfile');
$routes->get('staff/users', 'Portal::users');
$routes->get('staff/report.csv', 'Portal::reportCsv');
$routes->post('staff/testimonial/(:num)', 'Portal::moderateTestimonial/$1');
$routes->post('staff/users/create', 'Auth::createEmployee');
$routes->post('staff/users/(:num)', 'Portal::updateEmployee/$1');
$routes->post('staff/users/(:num)/deactivate', 'Portal::deactivate/$1');
$routes->post('staff/users/(:num)/status', 'Portal::setEmployeeStatus/$1');
$routes->post('staff/users/(:num)/reset-password', 'Portal::resetEmployeePassword/$1');
$routes->post('staff/customers/(:num)/status', 'Portal::setCustomerStatus/$1');
$routes->post('staff/customers/(:num)/reset-password', 'Portal::resetCustomerPassword/$1');
$routes->post('staff/booking/(:num)', 'Portal::updateBooking/$1');
$routes->post('staff/booking/(:num)/status', 'Portal::technicianUpdateBooking/$1');
$routes->post('staff/announcement', 'Portal::announcement');
$routes->post('staff/settings', 'Portal::saveSettings');
$routes->post('staff/service/(:num)', 'Portal::updateService/$1');
$routes->post('staff/service', 'Portal::createService');
$routes->post('staff/product', 'Portal::createProduct');
$routes->post('staff/product/(:num)', 'Portal::updateProduct/$1');
$routes->post('staff/payment', 'Portal::recordPayment');
$routes->post('staff/checkin', 'Portal::checkin');
$routes->post('staff/report', 'Portal::report');
$routes->post('staff/ticket/(:num)', 'Portal::updateTicket/$1');
