<?php

namespace App\Http\Middleware;

use App\Helpers\JwtAuthHelper;
use Closure;

class JwtAuthMiddleware
{
  public function handle($request, Closure $next)
  {
    $token = $request->header('Authorization');
    
    if (!$token) {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => 'Authorization token required'
      ], 401);
    }

    try {
      $decoded = JwtAuthHelper::verifyAccessToken($token);
      $request->auth = $decoded;
      return $next($request);
    } catch (\Exception $e) {
      return response()->json([
        'code' => 401,
        'success' => false,
        'message' => $e->getMessage()
      ], 401);
    }
  }
}