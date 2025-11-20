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

$router->group(['prefix' => 'api/v1'], function () use ($router) {

  /* Auth Routes */
  $router->group(['prefix' => 'auth'], function () use ($router) {

    /* New User Signup Route */
    $router->post('/signup', 'AuthController@signup');

    /* Existing User Login Route */
    $router->post('/login', 'AuthController@login');

    /* Send OTP Route */
    $router->post('/otp/send', 'AuthController@sendOTP');

    /* Verify OTP Route */
    $router->post('/otp/verify', 'AuthController@verifySMSOTP');

    /* Generating New Access Token for Valid Refresh Token Route */
    $router->post('/refresh', 'AuthController@verifyRefreshToken');

    /* Forgot Password Route */
    $router->post('/forgot-password', 'AuthController@ForgotPassword');

    /* Verify Email OTP Route */
    $router->post('/otp/verify/email', 'AuthController@VerifyEmailOTP');

    /* Reset Password Route */
    $router->post('/reset-password', 'AuthController@ResetPassword');

    /* Logout Route - Requires Authentication */
    $router->post('/logout', ['middleware' => 'auth', 'uses' => 'AuthController@logout']);

  });

  /* Admin Routes */
  $router->group(['prefix' => 'admin'], function () use ($router) {

    /* Approve an User */
    $router->post('/approve', 'AdminController@approveUser');

    /* Reject an User */
    $router->post('/reject', 'AdminController@rejectUser');

  });
  
  /* Send Order Notification to Online Partners - No Auth Required */
  $router->get('/order/send-notification', 'DeliveryController@sendOrderNotification');

  /* Auto Send Notifications for All Pending Orders - No Auth Required */
  $router->get('/order/send-notification/pending', 'DeliveryController@autoSendPendingNotifications');

  $router->group(['middleware' => 'auth'], function () use ($router) {

    /* Dashboard Route */
    $router->get('/dashboard', 'HomeController@home');

    /* Order Routes */
    $router->group(['prefix' => 'order'], function () use ($router) {
      /* Fetching Assigned Order Route */
      $router->get('/assigned', 'DeliveryController@fetchAssignedOrder');

      /* Fetching Order Details Route */
      $router->get('/details/{order_id}', 'DeliveryController@getOrderDetails');

      /* Updating Order Status Route */
      $router->post('/status', 'DeliveryController@updateOrderStatus');

      /* Sending Delivery OTP Route */
      $router->post('/otp/send', 'DeliveryController@sendDeliveryOTP');

      /* Completing Delivery Route */
      $router->post('/complete', 'DeliveryController@completeOrder');

      /* Fetching Order History */
      $router->get('/history', 'DeliveryController@OrderHistory');

      /* Accept Order Route */
      $router->post('/accept', 'DeliveryController@acceptOrder');

      /* Reject Order Route */
      $router->post('/reject', 'DeliveryController@rejectOrder');

    });


    /* Duty Routes */
    $router->group(['prefix' => 'duty'], function () use ($router) {
      /* FCM Token Route */
      $router->post('/update-fcm-token', 'DeliveryController@updateFcmToken');

      /* Updating Duty Status */
      $router->post('/status', 'DeliveryController@dutyStatusUpdate');

      /* Retrieve Duty Status */
      $router->get('/status', 'ProfileController@getDutyStatus');

    });

    /* Profile Routes */
    $router->group(['prefix' => 'profile'], function () use ($router) {

      /* Delete User Document */
      $router->delete('/document/{id}', 'ProfileController@deleteUserDocument');

      /* Retrieve Profile Info */
      $router->get('/', 'ProfileController@getProfileInfo');

      /* Check Account Status */
      $router->get('/status', 'ProfileController@checkAccountStatus');

      /* Update Additional Info */
      $router->post('/update', 'ProfileController@updateProfileInfo');

      /* Update Personal Info */
      $router->post('/personal/update', 'ProfileController@updatePersonalInfo');

      /* Update Bank Account Info */
      $router->post('/bank/update', 'ProfileController@updateBankAccInfo');

      /* Get Bank Account Info */
      $router->get('/bank/info', 'ProfileController@GetBankAccInfo');

      /* Get Update Profile Info */
      $router->get('/other/info', 'ProfileController@getOtherProfileInfo');

    });

    /* Update Kin Info */
    //$router->post('/kin-info-update', 'ProfileController@updateKinInfo');

  });

});

/* Render Track Page */
$router->get('/track', function () {
    require base_path('public/track.php');
});

$router->get('/docs', function () {
    return view('swagger');
});

$router->get('/api-docs.json', function () {
    return response()->file(base_path('public/api-docs.json'));
});
