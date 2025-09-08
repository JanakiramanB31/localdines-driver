<?php

namespace App\Http\Controllers;

use App\Models\FoodDeliveryOrder;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnerTakenOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth', ['except' => ['sendOrderNotification']]);
  }

  /**
   * @OA\Post(
   *     path="/duty-status",
   *     summary="Update delivery person duty status",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"duty_status"},
   *             @OA\Property(property="duty_status", type="string", enum={"on", "off"})
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Duty status updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Duty Status Updated Successful")
   *         )
   *     )
   * )
  */


  public function dutyStatusUpdate(Request $request) {
    $this->validate($request, [
      'duty_status' => 'required|in:on,off'
    ]);

    $userId = $request->auth->sub;

    $userData = FoodDeliveryPartner::find($userId);

    if(!$userData) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'User Not Found',
      ], 400);
    }

    $userData->duty_status = $request->duty_status == "on" ? true : false;
    $userData->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Duty Status Updated Successful',
    ], 200);

  }

  /**
   * @OA\Post(
   *     path="/fetch-assigned-order",
   *     summary="Fetch orders assigned to the delivery person",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Orders fetched or none found",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order Fetched Successful"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     )
   * )
  */

  public function fetchAssignedOrder(Request $request) {
    $userId = $request->auth->sub;

    $assignedOrders = FoodDeliveryPartnerTakenOrder::where('user_id', $userId)
    ->where('order_status', 'accepted')->get();

    if($assignedOrders->isEmpty()) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'No Active Orders Found',
      ], 200);
    }

    $updatedOrderData = $assignedOrders->map(function($assignedOrder) {
      $orderData = $this->getOrderDetailsForNotification($assignedOrder->order_id, false, 'accepted');
      if ($orderData) {
        $orderData['order_status'] = $assignedOrder->order_status;
      }
      return $orderData;
    })->filter(); // Remove null values

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Fetched Successful',
      'data' => $updatedOrderData->values()
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/update-order-status",
   *     summary="Update the current status of an order",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id", "order_status"},
   *             @OA\Property(property="order_id", type="integer"),
   *             @OA\Property(property="order_status", type="string", example="collected")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order status updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order Status Updated Successfully")
   *         )
   *     )
   * )
  */

  public function updateOrderStatus(Request $request) {
    $this->validate($request, [
      'order_id' => 'required',
      'order_status' => 'required|in:collected'
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnerTakenOrder::find($orderID);
     
    if(!$orderData) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Order Not Found',
      ], 200);
    }

    $orderData->order_status = $request->order_status;
    $orderData->save();

     return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Status Updated Successfully',
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/send-delivery-otp",
   *     summary="Send delivery OTP to customer",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id"},
   *             @OA\Property(property="order_id", type="integer")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="OTP sent to customer",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="OTP has been successfully sent to the customer."),
   *             @OA\Property(property="otp", type="string")
   *         )
   *     )
   * )
  */

  public function sendDeliveryOTP(Request $request) {

    $this->validate($request,[
      'order_id' => 'required',
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnerTakenOrder::find($orderID);
    
    if(!$orderData) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Order Not Found',
      ], 200);
    }

    $phoneNumber = $orderData->phone_no;

    $otp = mt_rand(1000, 9999);

    $orderData->d_otp = $otp;
    $orderData->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'OTP has been successfully sent to the customer.',
      'otp'=> $otp
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/complete-order",
   *     summary="Mark order as delivered using OTP verification",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id", "otp"},
   *             @OA\Property(property="order_id", type="integer"),
   *             @OA\Property(property="otp", type="string", minLength=4, maxLength=4)
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order marked as delivered",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order Delivered Successfully")
   *         )
   *     )
   * )
  */

  public function completeOrder(Request $request){
    $this->validate($request,[
      'order_id' => 'required',
      'otp' => 'required|min:4'
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnerTakenOrder::find($orderID);
    
    if(!$orderData) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Order Not Found',
      ], 200);
    }

    if($orderData->order_status == 'delivered') {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Order Already Delivered',
      ], 200);
    }

    if($orderData->d_otp != $request->otp) {
      return response()->json([
        'code' => 400,
        'success' => true,
        'message' => 'Invalid OTP',
      ], 400);
    }

    $orderData->order_status = 'delivered';
    $orderData->d_at = Carbon::now();
    $orderData->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Delivered Successfully',
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/order-history",
   *     summary="Retrieve past delivery orders",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Order history response",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order Fetched Successful")
   *         )
   *     )
   * )
  */

  public function orderHistory(Request $request) {
    $userId = $request->auth->sub;

    $orderHistory = FoodDeliveryPartnerTakenOrder::where('user_id', $userId)
    ->select('id', 'order_id', 'order_status', 'user_id', 'd_at')->get();

    if($orderHistory->isEmpty()) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'No Orders Found',
      ], 200);
    }

    $updatedOrderData = $orderHistory->map(function($historyOrder) {
      $orderData = $this->getOrderDetailsForNotification($historyOrder->order_id, true);
      if ($orderData) {
        // Format to match existing orderHistory structure
        return [
          'id' => $historyOrder->id,
          'order_id' => $historyOrder->order_id,
          'order_status' => $historyOrder->order_status,
          'user_id' => $historyOrder->user_id,
          'd_at' => $historyOrder->d_at,
          'order' => $orderData
        ];
      }
      return null;
    })->filter(); // Remove null values

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Fetched Successful',
      'data' => $updatedOrderData->values()
    ], 200);
  }

  private function getCoordinatesFromZipCode($postcode)
  {
    try {
      /* Clean and encode the postcode */
      $cleanPostcode = trim(strtoupper($postcode));
      $encodedPostcode = urlencode($cleanPostcode);
      
      /* API URL */
      $url = "https://findthatpostcode.uk/postcodes/{$encodedPostcode}.json";
      
      /* Make HTTP request */
      $context = stream_context_create([
        'http' => [
          'method' => 'GET',
          'timeout' => 5,
        ]
      ]);
      
      $response = file_get_contents($url, false, $context);
      
      if ($response === false) {
        return null;
      }
      
      $data = json_decode($response, true);
      
      
      /* Check if we have valid coordinate data */
      if (isset($data['data']['attributes']['location']['lat']) && isset($data['data']['attributes']['location']['lon'])) {
        return [
          'latitude' => (float)$data['data']['attributes']['location']['lat'],
          'longitude' => (float)$data['data']['attributes']['location']['lon'],
        ];
      }
      
    } catch (Exception $e) {
      // Log error if needed, but continue with fallback
      error_log("Postcode API error: " . $e->getMessage());
    }
    
    return null;
  }

  /**
   * @OA\Post(
   *     path="/order-details",
   *     summary="Fetch detailed information about a specific order",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id"},
   *             @OA\Property(property="order_id", type="integer", example=123),
   *             @OA\Property(property="user_details", type="boolean", example=true, description="Include assigned partner details")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order details fetched successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order details fetched successfully"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=404),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Order not found")
   *         )
   *     )
   * )
   */
  public function getOrderDetails(Request $request) {
    $this->validate($request, [
      'order_id' => 'required|integer',
    ]);

    $userId = $request->auth->sub;
    $orderId = $request->order_id;

    // Check if user exists
    $partner = FoodDeliveryPartner::find($userId);
    if (!$partner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User Not Found',
      ], 404);
    }
        
    $orderData = $this->getOrderDetailsForNotification($orderId, true);
    
    if (!$orderData) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'Order not found',
      ], 404);
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order details fetched successfully',
      'data' => $orderData
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/accept-order",
   *     summary="Accept an order assignment (first partner to accept gets the order)",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id"},
   *             @OA\Property(property="order_id", type="integer", example=123)
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order accepted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order accepted successfully"),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="order_id", type="integer", example=123),
   *                 @OA\Property(property="assigned_at", type="string", example="2023-12-01T10:30:00Z")
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=409,
   *         description="Order already accepted by another partner",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=409),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Order already accepted by another partner")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=404),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Order not found")
   *         )
   *     )
   * )
   */
  public function acceptOrder(Request $request) {
    $this->validate($request, [
      'order_id' => 'required|integer'
    ]);

    $userId = $request->auth->sub;
    $orderId = $request->order_id;

    // Check if user exists
    $partner = FoodDeliveryPartner::find($userId);
    if (!$partner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User Not Found',
      ], 404);
    }

    // Check if order exists
    $order = DB::table('food_delivery_orders')->where('id', $orderId)->first();
    if (!$order) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'Order not found',
      ], 404);
    }

    // Check if this partner already has this order assigned first
    $partnerOrder = FoodDeliveryPartnerTakenOrder::where('user_id', $userId)
    ->where('order_id', $orderId)->first();

    // Check if order is already accepted by someone else
    $existingAssignment = FoodDeliveryPartnerTakenOrder::where('order_id', $orderId)
    ->where('order_status', 'accepted')
    ->where('user_id', '!=', $userId)
    ->first();

    if ($existingAssignment) {
      return response()->json([
        'code' => 409,
        'success' => false,
        'message' => 'Order already accepted by another partner',
      ], 409);
    }

    if ($partnerOrder) {
      if ($partnerOrder->order_status == 'accepted') {
        return response()->json([
          'code' => 200,
          'success' => true,
          'message' => 'Order already accepted by you',
          'data' => [
            'order_id' => $orderId,
            'assigned_at' => $partnerOrder->created_at
          ]
        ], 200);
      } else {
        // Update existing record
        $partnerOrder->order_status = 'accepted';
        $partnerOrder->updated_at = Carbon::now();
        $partnerOrder->save();
      }
    } else {
      // Create new assignment
      $partnerOrder = new FoodDeliveryPartnerTakenOrder();
      $partnerOrder->user_id = $userId;
      $partnerOrder->order_id = $orderId;
      $partnerOrder->order_status = 'accepted';
      $partnerOrder->created_at = Carbon::now();
      $partnerOrder->updated_at = Carbon::now();
      $partnerOrder->save();
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order accepted successfully',
      'data' => [
        'order_id' => $orderId,
      ]
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/reject-order",
   *     summary="Reject an order assignment",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"order_id"},
   *             @OA\Property(property="order_id", type="integer", example=123),
   *             @OA\Property(property="reason", type="string", example="Unable to deliver at this time")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order rejected successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Order rejected successfully"),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="order_id", type="integer", example=123),
   *                 @OA\Property(property="rejected_at", type="string", example="2023-12-01T10:30:00Z")
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found or not assigned to this partner",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=404),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Order not found or not assigned to you")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Order cannot be rejected in current status",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=400),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Order cannot be rejected as it's already in progress")
   *         )
   *     )
   * )
   */
  public function rejectOrder(Request $request) {
    $this->validate($request, [
      'order_id' => 'required|integer',
    ]);

    $userId = $request->auth->sub;
    $orderId = $request->order_id;

    $partner = FoodDeliveryPartner::find($userId);
    if (!$partner) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User Not Found',
      ], 404);
    }

    $partnerOrder = FoodDeliveryPartnerTakenOrder::where('user_id', $userId)
    ->where('order_id', $orderId)->first();

    if (!$partnerOrder) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'Order not found or not assigned to you',
      ], 404);
    }

    if ($partnerOrder->order_status == 'collected' || $partnerOrder->order_status == 'delivered') {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Order cannot be rejected as it\'s already in progress',
      ], 400);
    }

    if ($partnerOrder->order_status == 'rejected') {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'Order already rejected by you',
        'data' => [
          'order_id' => $orderId,
          'rejected_at' => $partnerOrder->updated_at
        ]
      ], 200);
    }

    $partnerOrder->order_status = 'rejected';
    $partnerOrder->updated_at = Carbon::now();
    $partnerOrder->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order rejected successfully'
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/update-fcm-token",
   *     summary="Update delivery partner's FCM token for push notifications",
   *     tags={"Delivery"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"fcm_token"},
   *             @OA\Property(property="fcm_token", type="string", example="dGVzdF90b2tlbl9oZXJl")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="FCM token updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="FCM token updated successfully")
   *         )
   *     )
   * )
   */
  public function updateFcmToken(Request $request) {
    $this->validate($request, [
      'fcm_token' => 'required|string'
    ]);

    $userId = $request->auth->sub;
    $userData = FoodDeliveryPartner::find($userId);

    if(!$userData) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'User Not Found',
      ], 404);
    }

    $userData->fcm_token = $request->fcm_token;
    $userData->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'FCM token updated successfully',
    ], 200);
  }

    /**
   * @OA\Get(
   *     path="/send-order-notification",
   *     summary="Send new order notification to all online delivery partners",
   *     tags={"Admin"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(
   *         name="order_id",
   *         in="query",
   *         required=true,
   *         description="The ID of the order to send notification for",
   *         @OA\Schema(type="integer", example=123)
   *     ),
   *     @OA\Parameter(
   *         name="message",
   *         in="query",
   *         required=false,
   *         description="Custom message for the notification (optional)",
   *         @OA\Schema(type="string", example="New order available for pickup")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Notification sent successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Notification sent to X online partners"),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="partners_notified", type="integer", example=5),
   *                 @OA\Property(property="order_id", type="integer", example=123)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid or missing order ID",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=400),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Valid order_id is required in query parameters")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="No online partners found or order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=404),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="No online delivery partners found")
   *         )
   *     )
   * )
   */
  public function sendOrderNotification(Request $request) {
    // Get order_id from query parameter
    $order_id = $request->query('order_id');
    
    // Validate that order_id is provided and numeric
    if (!$order_id || !is_numeric($order_id)) {
      return response()->json([
        'code' => 400,
        'success' => false,
        'message' => 'Valid order_id is required in query parameters'
      ], 400);
    }
    
    $order_id = (int) $order_id;
    
    // Use default message or get from query parameter
    $message = $request->query('message', 'New order available for pickup');

    // Get order details with pickup and delivery information
    $orderData = $this->getOrderDetailsForNotification($order_id);
    
    if (!$orderData) {
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => 'Order not found'
      ], 404);
    }

    // Get partners who have previously rejected this order
    $rejectedPartnerIds = FoodDeliveryPartnerTakenOrder::where('order_id', $order_id)
    ->where('order_status', 'rejected')->pluck('user_id')->toArray();

    $onlinePartners = FoodDeliveryPartner::where('duty_status', true)
    ->where('admin_approval', 'accepted')->whereNotNull('fcm_token')
    ->whereNotIn('id', $rejectedPartnerIds)->get();

    if ($onlinePartners->isEmpty()) {
      $totalRejected = count($rejectedPartnerIds);
      $message = $totalRejected > 0 
        ? "No available delivery partners found (excluding {$totalRejected} partners who previously rejected this order)"
        : 'No online delivery partners found';
      
      return response()->json([
        'code' => 404,
        'success' => false,
        'message' => $message
      ], 404);
    }

    $partnersNotified = 0;
    $failedNotifications = 0;
    $notificationResults = [];
    
    foreach ($onlinePartners as $partner) {
      $result = $this->sendPushNotification($partner, $order_id, $message, $orderData);
      
      if ($result['success']) {
        $partnersNotified++;
      } else {
        $failedNotifications++;
      }
      
      $notificationResults[] = [
        'partner_id' => $partner->id,
        'partner_name' => $partner->name,
        'status' => $result['success'] ? 'sent' : 'failed',
        'error' => $result['error'] ?? null
      ];
    }

    $totalRejected = count($rejectedPartnerIds);
    $message = $totalRejected > 0 
      ? "Notification sent to {$partnersNotified} partners, {$failedNotifications} failed (excluding {$totalRejected} partners who previously rejected this order)"
      : "Notification sent to {$partnersNotified} partners, {$failedNotifications} failed";

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => $message,
      'data' => [
        'partners_notified' => $partnersNotified,
        'failed_notifications' => $failedNotifications,
        'excluded_rejected_partners' => $totalRejected,
        'order_id' => $order_id,
        'order_details' => $orderData,
        'notification_results' => $notificationResults
      ]
    ], 200);
  }

  private function getOrderDetailsForNotification($orderId, $includeUserDetails = false, $orderStatus='all') {
    try {
      $productData = Cache::remember('product_data', Carbon::now()->addDay(), function() {
        return DB::table('food_delivery_plugin_base_multi_lang')
        ->where('model', 'pjProduct')->select('foreign_id', 'field', 'content')->get()
        ->groupBy('foreign_id')
        ->map(fn($items, $foreignId) => array_merge(
            ['foreign_id' => $foreignId],
            $items->pluck('content', 'field')->toArray()
        ))
        ->values();
      });

      $orderQuery = FoodDeliveryOrder::with('order_items');
      $order = $orderQuery->find($orderId);
      
      if (!$order) {
        return null;
      }

      // Get assigned partner details if requested
      $assignedPartner = null;
      if ($includeUserDetails) {
        $partnerTakenOrderQuery = FoodDeliveryPartnerTakenOrder::with(['order' => function($query) {
          $query->select('id', 'is_paid', 'price_delivery', 'order_id', 'first_name', 'surname', 'phone_no');
        }])->where('order_id', $orderId);
        
        if($orderStatus !== 'all') {
          $partnerTakenOrderQuery->where('order_status', $orderStatus);
        }
        
        $partnerTakenOrder = $partnerTakenOrderQuery->first();
        
        if ($partnerTakenOrder) {
          $partner = FoodDeliveryPartner::find($partnerTakenOrder->user_id);
          if ($partner) {
            $assignedPartner = [
              'partner_id' => $partner->id,
              'partner_name' => $partner->name,
              'partner_phone' => $partner->mobile_no,
              'order_status' => $partnerTakenOrder->order_status,
              'assigned_at' => $partnerTakenOrder->created_at,
              'updated_at' => $partnerTakenOrder->updated_at
            ];
          }
        }
      }

      $pickupLocation = DB::table('food_delivery_plugin_base_multi_lang')
        ->select('field', 'content')
        ->where('model', 'pjLocation')
        ->where('locale', 1)
        ->pluck('content', 'field');
      
      $pickupLocationCoOrdinates = DB::table('food_delivery_locations')
        ->select('lat', 'lng')
        ->where('id', 1)
        ->first();

      $orderItems = $order->order_items->map(function($item) use ($productData) {
        $productName = $productData->firstWhere('foreign_id', $item->foreign_id)['name'] ?? 'N/A';
        return [
          'name' => $productName,
          'quantity' => $item->cnt,
          'price' => $item->price,
        ];
      });

      $latitude = $order->d_latitude;
      $longitude = $order->d_longitude;
      $zipCode = $order->d_zip;
      if (is_null($latitude) || is_null($longitude)) {
        if ($zipCode !== null) {
          $locationCoordinates = $this->getCoordinatesFromZipCode($zipCode);
          if ($locationCoordinates !== null) {
            $latitude = $locationCoordinates['latitude'];
            $longitude = $locationCoordinates['longitude'];
            
            DB::table('food_delivery_orders')
              ->where('id', $order->id)
              ->update([
                'd_latitude' => $latitude,
                'd_longitude' => $longitude
              ]);
          }
        }
      }

      $orderData = [
        'id' => $order->id,
        'order_id' => $order->order_id,
        'payment_status' => $order->is_paid,
        'delivery_charges' => $order->price_delivery,
        'first_name' => $order->first_name,
        'surname' => $order->surname,
        'phone_no' => $order->phone_no,
        'p_name' => $pickupLocation['name'] ?? 'N/A',
        'p_address' => $pickupLocation['address'] ?? 'N/A',
        'p_latitude' => $pickupLocationCoOrdinates->lat ?? 'N/A',
        'p_longitude' => $pickupLocationCoOrdinates->lng ?? 'N/A',
        'p_notes' => $order->p_notes,
        'd_latitude' => $latitude,
        'd_longitude' => $longitude,
        'd_address_1' => $order->d_address_1,
        'd_address_2' => $order->d_address_2,
        'd_city' => $order->d_city,
        'd_state' => $order->d_state,
        'd_zip' => $order->d_zip,
        'd_notes' => $order->d_notes,
        'post_code' => $order->post_code,
        'subtotal' => $order->subtotal,
        'total' => $order->total,
        'customer_paid' => $order->customer_paid,
        'order_items' => $orderItems,
      ];

      // Add assigned partner details if requested and available
      if ($includeUserDetails && $assignedPartner) {
        $orderData['assigned_partner'] = $assignedPartner;
      }

      return $orderData;

    } catch (Exception $e) {
      return null;
    }
  }

  private function sendPushNotification($partner, $orderId, $message, $orderData = null) {
    try {
      $fcmToken = $partner->fcm_token;
      
      if (!$fcmToken) {
        return ['success' => false, 'error' => 'No FCM token found'];
      }

      $serverKey = env('FCM_SERVER_KEY');
      if (!$serverKey) {
        return ['success' => false, 'error' => 'FCM server key not configured'];
      }

      // Enhanced notification body with order info
      $notificationBody = $message;
      if ($orderData) {
        $notificationBody = "Order #{$orderData['order_id']} - £{$orderData['total_amount']} - {$orderData['total_items']} items";
      }

      $notificationData = [
        'to' => $fcmToken,
        'notification' => [
          'title' => 'New Order Available!',
          'body' => $notificationBody,
          'icon' => 'ic_notification',
          'sound' => 'default',
          'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ],
        'data' => [
          'order_id' => (string) $orderId,
          'type' => 'new_order',
          'timestamp' => Carbon::now()->toISOString(),
          'order_details' => json_encode($orderData)
        ]
      ];

      $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
      if (curl_error($ch)) {
        curl_close($ch);
        return ['success' => false, 'error' => 'cURL error: ' . curl_error($ch)];
      }
      
      curl_close($ch);

      if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success'] > 0) {
          return ['success' => true];
        } else {
          return ['success' => false, 'error' => 'FCM response indicates failure'];
        }
      } else {
        return ['success' => false, 'error' => "HTTP error: {$httpCode}"];
      }

    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
  }
}
