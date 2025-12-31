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
    
}
