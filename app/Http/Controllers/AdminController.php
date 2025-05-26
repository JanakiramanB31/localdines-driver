<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuthHelper;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnersLoginOtp;
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
    $this->validate($request,[
      'user_id' => 'required',
    ]);

    $deliveryPartner = FoodDeliveryPartner::where('id', $request->user_id)->first();

    if ($deliveryPartner) {

      $deliveryPartner->admin_approval == "accepted";
      $deliveryPartner->save();

      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'User approved by admin'
      ], 200);
    }

    return response()->json([
      'code' => 401,
      'success' => true,
      'message' => 'User not approved by admin'
    ], 401);

  }

  public function rejectUser(Request $request) {
    $this->validate($request,[
      'user_id' => 'required',
    ]);

    $deliveryPartner = FoodDeliveryPartner::where('id', $request->user_id)->first();

    if ($deliveryPartner) {

      $deliveryPartner->admin_approval == "rejected";
      $deliveryPartner->save();

      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'User Rejected by admin'
      ], 200);
    }

    return response()->json([
      'code' => 401,
      'success' => true,
      'message' => 'User not approved by admin'
    ], 401);

  }
    
}
