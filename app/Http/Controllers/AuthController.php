<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuthHelper;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnersLoginOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
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

  public function signup(Request $request) {
    
    $this->validate($request,[
      'name' => 'required|min:3',
      'phone_number' => 'required',
      'email' => 'required|email|unique:food_delivery_partners,email',
      'password' => 'required|min:8',
      'gender' => 'required|in:male,female',
      'vehicle_name' => 'required',
      'vehicle_number' => 'required',
      'national_id' => 'required',
      'address' => 'required'
    ]);

    $deliveryPartner = new FoodDeliveryPartner();
    $deliveryPartner->name = $request->name;
    $deliveryPartner->phone_number = $request->phone_number;
    $deliveryPartner->email = $request->email;
    $deliveryPartner->password = Hash::make($request->password);
    $deliveryPartner->gender = $request->gender;
    $deliveryPartner->vehicle_name = $request->vehicle_name;
    $deliveryPartner->vehicle_number = $request->vehicle_number;
    $deliveryPartner->national_id = $request->national_id;
    $deliveryPartner->address = $request->address;
    $deliveryPartner->admin_approval = 'pending';
    $deliveryPartner->save();

    return response()->json([
      'code' => '201',
      'success' => true,
      'message'=> "User Created Succesfully"
    ], 201);
  }

  public function login(Request $request) {

    $this->validate($request,[
      'email' => 'required|email',
      'password' => 'required|min:8'
    ]);

    $deliveryPartner = FoodDeliveryPartner::where('email', $request->email)->first();
    if(!$deliveryPartner) return response()->json([
      'code' => 400,
      'success' => false,
      'message' => 'Invalid Credentials'
    ], 400);

    if (!Hash::check($request->password, $deliveryPartner->password)) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Invalid Credentials'
      ], 400);
    }

    if ($deliveryPartner->admin_approval == "pending") {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => 'User not approved by admin'
      ], 401);
    }

    if ($deliveryPartner->admin_approval == "rejected") {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => 'User request was rejected by the admin'
      ], 401);
    }
    
    $accessTokenPayload = [
      'iss' => 'localhost',
      'sub' => $deliveryPartner->id,
      'name' => $deliveryPartner->name,
      'iat' => time(),
      'exp' => time()+ (10* 60),
    ];

    $refreshTokenPayload = [
      'iss' => 'localhost',
      'sub' => $deliveryPartner->id,
      'name' => $deliveryPartner->name,
      'iat' => time(),
      'exp' => time()+ (60 * 60 * 24 * 30),
    ];
    
    $accessToken = JwtAuthHelper::generateJWTAccessToken($accessTokenPayload);
    $refreshToken = JwtAuthHelper::generateJWTRefreshToken($refreshTokenPayload);

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Login Successful',
      'accessToken' => $accessToken,
      'refreshToken' => $refreshToken
    ], 200);
  }

  public function sendOTP(Request $request) {

    $this->validate($request,[
      'phone_number' => 'required|integer',
    ]);

    $otpData = FoodDeliveryPartnersLoginOtp::where('phone_number', $request->phone_number)->OrderBy('created_at', 'desc')->first();

    if ($otpData && $otpData->otp && $otpData->expires_at > time()) {
      return response()->json([
        'code' => 429,
        'success' => false,
        'message' => 'Too Many Requests Sent',
      ], 429);
    }

    $otp = mt_rand(1000, 9999);

    $newOTP = new FoodDeliveryPartnersLoginOtp();
    $newOTP->phone_number = $request->phone_number;
    $newOTP->otp = $otp;
    $newOTP->expires_at = time() + 1*60;
    $newOTP->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'OTP Sent Successfully',
      'otp'=> $otp
    ], 200);
  }

  public function verifyOTP(Request $request) {

    $this->validate($request,[
      'phone_number' => 'required',
      'otp' => 'required|min:4'
    ]);

    $otpData = FoodDeliveryPartnersLoginOtp::where('phone_number', $request->phone_number)->whereNull('status')->OrderBy('created_at', 'desc')->first();

    if(!$otpData) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Invalid Mobile Number',
      ], 400);
    }

    if (($request->phone_number != $otpData->phone_number) || ($request->otp != $otpData->otp)) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Invalid OTP',
      ], 400);
    }

    if ($otpData->expires_at < time()) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'OTP Expired',
      ], 400);
    }

    $otpData->status = 'verified';
    $otpData->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'OTP Verified',
    ], 200);
  }

  public function verifyRefreshToken(Request $request){
    $grant_type = $request->header('grant_type');

    if($grant_type != "refresh_token") {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Missing Parameters',
      ], 400);
    }

    $this->validate($request, [
      'refresh_token' => 'required'
    ]);

    $refreshToken = $request->refresh_token;

    try {
      $newAccessToken = JwtAuthHelper::verifyRefreshToken($refreshToken);
      
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Login Successful',
        'accessToken' => $newAccessToken,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => $e->getMessage()
      ], 401);
    }
  }
    
}
