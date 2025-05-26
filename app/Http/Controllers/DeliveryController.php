<?php

namespace App\Http\Controllers;

use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnersTakenOrder;
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

  public function fetchAssignedOrder(Request $request) {
    $userId = 33; //$request->auth->sub;

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

    $ordersData = FoodDeliveryPartnersTakenOrder::with([
    'order' => function($query) {
      $query->select('id', 'order_id', 'first_name', 'surname', 'phone_no', 'd_address_1', 'd_address_2', 'd_city', 'd_state', 'd_zip', 'd_notes', 'post_code', 'price', 'price_packing', 'price_delivery', 'discount', 'subtotal', 'tax', 'total', 'customer_paid');
    },
    'order.order_items' => function($query) {
      $query->select('id', 'order_id', 'type', 'foreign_id', 'special_instruction', 'custom_special_instruction');
    },
    ])->where('user_id', $userId)->where('order_status', 'accepted')->get();

    $orderData1 = $ordersData->map(function($order_data, $key) use ($productData) {
      return $order_data->order->order_items->map(function($item, $key) use ($productData) {
        $productName = $productData->firstWhere('foreign_id', $item->foreign_id)['name'] ?? 'N/A';
        $productDesc = $productData->firstWhere('foreign_id', $item->foreign_id)['description'] ?? 'N/A';
        $item['special_instruction'] = json_decode($item['special_instruction']);
        $item['custom_special_instruction'] = json_decode($item['custom_special_instruction']);
        $item['name'] = $productName;
        $item['description'] = $productDesc;
        return $item; 
      });
    });

    if(count($ordersData) == 0) {
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
      'data' => $ordersData
    ], 200);
  }

  public function updateOrderStatus(Request $request) {
    $this->validate($request, [
      'order_id' => 'required',
      'order_status' => 'required|in:collected'
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnersTakenOrder::find($orderID);
     
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

  public function sendDeliveryOTP(Request $request) {

    $this->validate($request,[
      'order_id' => 'required',
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnersTakenOrder::find($orderID);
    
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

  public function completeOrder(Request $request){
    $this->validate($request,[
      'order_id' => 'required',
      'otp' => 'required|min:4'
    ]);

    $orderID = $request->order_id;
    $orderData = FoodDeliveryPartnersTakenOrder::find($orderID);
    
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

  public function orderHistory(Request $request) {
    $userId = $request->auth->sub;

    $ordersData = FoodDeliveryPartnersTakenOrder::with('order')->where('user_id', $userId)->get();
     //return $getOrder;

    if(count($ordersData) == 0) {
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
      'data' => $ordersData
    ], 200);
  }
}
