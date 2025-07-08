<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuthHelper;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnerAddress;
use App\Models\FoodDeliveryPartnerBankAccInfo;
use App\Models\FoodDeliveryPartnerDocument;
use App\Models\FoodDeliveryPartnerKinInfo;
use App\Models\FoodDeliveryPartnerOtherInfo;
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

  /**
   * @OA\Post(
   *     path="/signup",
   *     summary="Register a new delivery user",
   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={
   *                 "f_name", "s_name", "email", "phone_number", "password", "dob",
   *                 "nationality", "is_non_british", "street_name", "city", "post_code", "home_phone"
   *             },
   *             @OA\Property(property="f_name", type="string", minLength=3),
   *             @OA\Property(property="m_name", type="string", minLength=1),
   *             @OA\Property(property="s_name", type="string", minLength=3),
   *             @OA\Property(property="email", type="string", format="email"),
   *             @OA\Property(property="phone_number", type="string"),
   *             @OA\Property(property="password", type="string", minLength=8),
   *             @OA\Property(property="dob", type="string", format="date"),
   *             @OA\Property(property="nationality", type="string"),
   *             @OA\Property(property="is_non_british", type="boolean"),
   *             
   *             @OA\Property(property="home_no", type="string"),
   *             @OA\Property(property="home_name", type="string"),
   *             @OA\Property(property="street_name", type="string"),
   *             @OA\Property(property="city", type="string"),
   *             @OA\Property(property="county", type="string"),
   *             @OA\Property(property="post_code", type="string"),
   *             @OA\Property(property="home_phone", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="User Created Successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=201),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="User Created Successfully")
   *         )
   *     )
   * )
   */

  public function signup(Request $request) {
    
    $this->validate($request, [
      'f_name' => 'required|min:3',
      //'m_name' => 'required|min:3',
      's_name' => 'required|min:3',
      'phone_number' => 'required',
      'email' => 'required|email|unique:food_delivery_partners,email',
      'password' => 'required|min:8',
      'dob' => 'required|date',
      'nationality' => 'required',
      'is_non_british' => 'required|boolean',
      //'has_full_uk_driving_licence' => 'required|boolean',
     
      // 'gender' => 'required|in:male,female',
      // 'vehicle_name' => 'required',
      // 'vehicle_number' => 'required',
      // 'national_id' => 'required',
      // 'address' => 'required',

      /* Address Info */
      'home_no' => 'required_without:home_name',
      'home_name' => 'required_without:home_no',
      'street_name' => 'required',
      'city' => 'required',
      //'county' => 'required',
      'post_code' => 'required',
      'home_phone' => 'required',
    ]);

    $deliveryPartner = new FoodDeliveryPartner();
    $deliveryPartner->title = $request->title ?? 'Mr.';
    $deliveryPartner->f_name = $request->f_name;
    $deliveryPartner->m_name = $request->m_name;
    $deliveryPartner->s_name = $request->s_name;
    //$deliveryPartner->name = $request->name;
    $deliveryPartner->phone_number = $request->phone_number;
    $deliveryPartner->email = $request->email;
    $deliveryPartner->password = Hash::make($request->password);
    $deliveryPartner->dob = $request->dob;
    $deliveryPartner->nationality = $request->nationality;
    //$deliveryPartner->gender = $request->gender;
    $deliveryPartner->is_non_british  = $request->is_non_british;
   // $deliveryPartner->vehicle_number = $request->vehicle_number;
    // $deliveryPartner->national_id = $request->national_id;
    // $deliveryPartner->address = $request->address;
    $deliveryPartner->admin_approval = 'pending';
    $deliveryPartner->save();

    $deliveryPartnerAddress = new FoodDeliveryPartnerAddress();
    $deliveryPartnerAddress->partner_id = $deliveryPartner->id;
    $deliveryPartnerAddress->home_no = $request->home_no;
    $deliveryPartnerAddress->home_name = $request->home_no;
    $deliveryPartnerAddress->street_name = $request->street_name;
    $deliveryPartnerAddress->city = $request->city;
    $deliveryPartnerAddress->county = $request->county;
    $deliveryPartnerAddress->post_code = $request->post_code;
    $deliveryPartnerAddress->home_phone = $request->home_phone;
    $deliveryPartnerAddress->save();

    // foreach ($request->docs as $doc) {
    //   $deliveryPartnerDocs = new FoodDeliveryPartnerDocument(); 
    //   $deliveryPartnerDocs->partner_id = $deliveryPartner->id;
    //   $deliveryPartnerDocs->doc_type = $doc['doc_type'];
    //   $deliveryPartnerDocs->doc_number = $doc['doc_number'];
    //   $deliveryPartnerDocs->doc_expiry = $doc['doc_expiry'];
      
    //   $deliveryPartnerDocs->save();
    // }

    return response()->json([
      'code' => '201',
      'success' => true,
      'message'=> "User Created Succesfully",
      'data' => [
        'user_id' => $deliveryPartner->id
      ]
    ], 201);
  }

  /**
   * @OA\Post(
   *     path="/login",
   *     summary="Login existing user",
   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"email", "password"},
   *             @OA\Property(property="email", type="string", format="email"),
   *             @OA\Property(property="password", type="string", minLength=8)
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Login Successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Login Successful"),
   *             @OA\Property(property="accessToken", type="string"),
   *             @OA\Property(property="refreshToken", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid Credentials",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=400),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Invalid Credentials")
   *         )
   *     )
   * )
  */

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
      'refreshToken' => $refreshToken,
      'userID' => $deliveryPartner->id
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/send-otp",
   *     summary="Send OTP to user's phone number",
   *     tags={"OTP"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"phone_number"},
   *             @OA\Property(property="phone_number", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="OTP Sent Successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="OTP Sent Successfully")
   *         )
   *     ),
   *     @OA\Response(
   *         response=429,
   *         description="Too Many Requests",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=429),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Too Many Requests Sent")
   *         )
   *     )
   * )
  */

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

  /**
   * @OA\Post(
   *     path="/verify-otp",
   *     summary="Verify received OTP",
   *     tags={"OTP"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"phone_number", "otp"},
   *             @OA\Property(property="phone_number", type="string"),
   *             @OA\Property(property="otp", type="string", minLength=4)
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="OTP Verified",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="OTP Verified")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid OTP or Expired",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=400),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Invalid OTP")
   *         )
   *     )
   * )
  */

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

  /**
   * @OA\Post(
   *     path="/auth",
   *     summary="Get new access token using refresh token",
   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"refresh_token"},
   *             @OA\Property(property="refresh_token", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="New access token generated",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Login Successful"),
   *             @OA\Property(property="accessToken", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Missing parameters",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=400),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Missing Parameters")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Token expired",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=401),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Token Expired")
   *         )
   *     )
   * )
  */


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
