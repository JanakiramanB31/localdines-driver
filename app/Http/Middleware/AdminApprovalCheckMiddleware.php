<?php

namespace App\Http\Middleware;

use App\Models\FoodDeliveryPartner;
use Closure;

class AdminApprovalCheckMiddleware
{
  public function handle($request, Closure $next)
  {
    $userID = $request->auth->sub;
    
    if (!$userID) {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => 'User ID required'
      ], 401);
    }

    try {
      $userData = FoodDeliveryPartner::find($userID);

      if(!$userData) {
        return response()->json([
          'code' => 401,
          'success' => true,
          'message' => 'User Not Found',
        ], 401);
      }

      if ($userData->is_admin_approved == 1) {
        return $next($request);
      }

      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => 'User not approved by admin'
      ], 401);
      
    } catch (\Exception $e) {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => $e->getMessage()
      ], 401);
    }
  }
}