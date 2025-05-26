<?php
use Illuminate\Support\Facades\DB;

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/* New User Signup Route */
$router->post('/signup', 'AuthController@signup');

/* Existing User Login Route */
$router->post('/login', 'AuthController@login');

/* Send OTP Route */
$router->post('/send-otp', 'AuthController@sendOTP');

/* Verify OTP Route */
$router->post('/verify-otp', 'AuthController@verifyOTP');

/* Generating New Access Token for Valid Refresh Token Route */
$router->post('/auth', 'AuthController@verifyRefreshToken');

$router->post('/admin-approve', 'AdminController@approveUser');
$router->post('/admin-reject', 'AdminController@rejectUser');


$router->group(['middleware' => 'auth'], function () use ($router) {
  
  /* Dashboard Route */
  $router->get('/', 'HomeController@home');

  /* Fetching Assigned Order Route */
  $router->post('/fetch-assigned-order', 'DeliveryController@fetchAssignedOrder');

  /* Updating Order Status Route */
  $router->post('/update-order-status', 'DeliveryController@updateOrderStatus');

  /* Sending Delivery OTP Route */
  $router->post('/send-delivery-otp', 'DeliveryController@sendDeliveryOTP');

  /* Completing Delivery Route */
  $router->post('/complete-order', 'DeliveryController@completeOrder');

  /* Fetching Order History */
  $router->post('/order-history', 'DeliveryController@OrderHistory');

  /* Updating Duty Status */
  $router->post('/duty-status', 'DeliveryController@dutyStatusUpdate');

});