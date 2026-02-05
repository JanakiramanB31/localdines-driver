<?php

namespace App\Http\Controllers;
use App\Constants;
use App\Models\FoodDeliveryPartner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
  */
  public function __construct()
  {
      //
  }

  public function approveUser(Request $request) {
    $email = $request->query('email');

    if (!$email) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Email is required'
      ], 400);
    }

    if (!preg_match(Constants::EMAIL_REGEX, $email)) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Invalid email format'
      ], 400);
    }

    $deliveryPartner = FoodDeliveryPartner::where('email', $email)->first();

    if (!$deliveryPartner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User not found'
      ], 404);
    }

    $deliveryPartner->admin_approval = "accepted";
    $deliveryPartner->approved_at = Carbon::now('UTC');
    $deliveryPartner->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'User approved by admin'
    ], 200);
  }

  public function rejectUser(Request $request) {
    $email = $request->query('email');

    if (!$email) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Email is required'
      ], 400);
    }

    if (!preg_match(Constants::EMAIL_REGEX, $email)) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Invalid email format'
      ], 400);
    }

    $deliveryPartner = FoodDeliveryPartner::where('email', $email)->first();

    if (!$deliveryPartner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User not found'
      ], 404);
    }

    $deliveryPartner->admin_approval = "rejected";
    $deliveryPartner->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'User Rejected by admin'
    ], 200);
  }

  public function checkOnlineDrivers() {
    $onlineDriversCount = FoodDeliveryPartner::where('duty_status', true)
      ->where('admin_approval', 'accepted')
      ->where('is_active', 1)
      ->where('updated_at', '>=', Carbon::now()->subHours(5))
      ->count();

    if ($onlineDriversCount === 0) {
      return response()->json([
        'code' => 200,
        'success' => false,
        'message' => 'No online drivers found',
        'data' => [
          'is_delivery_available' => false
        ]
      ], 200);
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => $onlineDriversCount . ' online driver(s) found',
      'data' => [
        'is_delivery_available' => true,
      ]
    ], 200);
  }

}
