<?php

namespace App\Http\Controllers;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnersLoginOtp;
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
    $this->validate($request,[
      'email' => 'required'
    ]);

    $deliveryPartner = FoodDeliveryPartner::whre('email',$request->email)->first();

    if (!$deliveryPartner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User not found'
      ], 404);
    }

    $deliveryPartner->admin_approval = "accepted";
    $deliveryPartner->approved_at = Carbon::now('UTC');;
    $deliveryPartner->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'User approved by admin'
    ], 200);
  }

  public function rejectUser(Request $request) {
    $this->validate($request,[
      'email' => 'required'
    ]);

    $deliveryPartner = FoodDeliveryPartner::whre('email',$request->email)->first();

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
