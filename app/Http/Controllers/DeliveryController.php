<?php

namespace App\Http\Controllers;

use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnerTakenOrder;
use Carbon\Carbon;
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
    $this->middleware('auth');
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
      ], 200);
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

    $ordersData = FoodDeliveryPartnerTakenOrder::with([
    'order' => function($query) {
      $query->select('id', 'order_id', 'first_name', 'surname', 'phone_no', 'd_address_1', 'd_address_2', 'd_city', 'd_state', 'd_zip', 'd_notes', 'post_code', 'price', 'price_packing', 'price_delivery', 'discount', 'subtotal', 'tax', 'total', 'customer_paid');
    },
    'order.order_items' => function($query) {
      $query->select('id', 'order_id', 'type', 'foreign_id', 'special_instruction', 'custom_special_instruction');
    },
    ])->where('user_id', $userId)->where('order_status', 'accepted')->get();

    //$ordersData = FoodDeliveryPartnerTakenOrder::with('order')->where('user_id', $userId)->where('order_status', 'accepted')->get();


    $updatedOrderData = $ordersData->map(function($order_data, $key) use ($productData) {
      $order = $order_data->order;

      // Map and enhance order_items
      $order->order_items =  $order->order_items->map(function($item, $key) use ($productData) {
        $productName = $productData->firstWhere('foreign_id', $item->foreign_id)['name'] ?? 'N/A';
        $productDesc = $productData->firstWhere('foreign_id', $item->foreign_id)['description'] ?? 'N/A';
        $item['special_instruction'] = json_decode($item['special_instruction']);
        $item['custom_special_instruction'] = json_decode($item['custom_special_instruction']);
        $item['name'] = $productName;
        $item['description'] = $productDesc;
        unset($item['foreign_id']);
        unset($item['type']);
        return $item; 
      });
      return $order;
    });

    if(count($updatedOrderData) == 0) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'No Active Orders Found',
      ], 200);
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Fetched Successful',
      'data' => $updatedOrderData
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

    $ordersData = FoodDeliveryPartnerTakenOrder::with([
    'order' => function($query) {
      $query->select('id', 'order_id', 'first_name', 'surname', 'phone_no', 'd_address_1', 'd_address_2', 'd_city', 'd_state', 'd_zip', 'd_notes', 'post_code', 'price', 'price_packing', 'price_delivery', 'discount', 'subtotal', 'tax', 'total', 'customer_paid');
    },
    'order.order_items' => function($query) {
      $query->select('id', 'order_id', 'type', 'foreign_id', 'special_instruction', 'custom_special_instruction');
    },
    ])->where('user_id', $userId)->select('id', 'order_id', 'order_status', 'user_id', 'd_at')->get();


    //$ordersData = FoodDeliveryPartnerTakenOrder::with('order')->where('user_id', $userId)->get();
     
    $updatedOrderData = $ordersData->map(function($order_data, $key) use ($productData) {
      $order = $order_data;

      // Map and enhance order_items
      $order->order->order_items =  $order->order->order_items->map(function($item, $key) use ($productData) {
        $productName = $productData->firstWhere('foreign_id', $item->foreign_id)['name'] ?? 'N/A';
        $productDesc = $productData->firstWhere('foreign_id', $item->foreign_id)['description'] ?? 'N/A';
        $item['special_instruction'] = json_decode($item['special_instruction']);
        $item['custom_special_instruction'] = json_decode($item['custom_special_instruction']);
        $item['name'] = $productName;
        $item['description'] = $productDesc;
        unset($item['foreign_id']);
        unset($item['type']);
        return $item; 
      });
      return $order;
    });

    if(count($updatedOrderData) == 0) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'No Orders Found',
      ], 200);
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Order Fetched Successful',
      'data' => $updatedOrderData
    ], 200);
  }
}
