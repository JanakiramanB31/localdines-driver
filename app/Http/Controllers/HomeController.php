<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuthHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

class HomeController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth');
    //$this->middleware('admin_approval');
  }

  public function home(Request $request) {

    // $verifyToken = JwtAuthHelper::verifyAccessToken($authToken)->getData();

    // if ($verifyToken->message == "Token expired") {
    //   $verifyRefreshToken = JwtAuthHelper::verifyRefreshToken();

    //   $newAccessToken = $verifyRefreshToken->getData()->token;

    //   return response()->json([
    //     'code' => 200,
    //     'success' => true,
    //     'message' => 'Login Successful',
    //     'accessToken' => $newAccessToken,
    //   ]);
    // };

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Login Successful',
    ]);
  }
    
}
