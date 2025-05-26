<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtAuthHelper
{
  public static function generateJWTAccessToken($payload) {

    $jwtSecretKey = env('JWT_ACCESS_TOKEN_SECRET_KEY');
    $accessToken = JWT::encode($payload, $jwtSecretKey, 'HS256');

    return $accessToken;

    // return response()->json([
    //   'code' => 200,
    //   'success' => true,
    //   'message' => 'Success',
    //   'token' => $accessToken
    // ]);
  }

  public static function generateJWTRefreshToken($payload) {

    $jwtRefreshKey = env('JWT_REFRESH_TOKEN_SECRET_KEY');
    $refreshToken = JWT::encode($payload, $jwtRefreshKey, 'HS256');

    return $refreshToken;

    // return response()->json([
    //   'code' => 200,
    //   'success' => true,
    //   'message' => 'Success',
    //   'token' => $refreshToken
    // ]);
  }

  

  public static function verifyAccessToken ($token) {
    try {

      $jwtSecretKey = env('JWT_ACCESS_TOKEN_SECRET_KEY');
      $decodedAccessToken = JWT::decode($token, new Key($jwtSecretKey, 'HS256'));

      return $decodedAccessToken;

      // return response()->json([
      //   'code' => 200,
      //   'success' => true,
      //   'message' => 'Valid Token',
      //   'token' => $decodedAccessToken
      // ]);

    } catch (ExpiredException $e) {
      throw new \Exception('Token expired');
      //return response()->json(['code' =>401,  'success' => false, 'message' => 'Token expired']);
    } catch (\Exception $e) {
      throw new \Exception('Invalid token');
      //return response()->json(['code' =>401,  'success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    }
  }

  public static function verifyRefreshToken ($refToken) {
    try {
      //$refToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJsb2NhbGhvc3QiLCJzdWIiOjMzLCJuYW1lIjoiR29waSIsImlhdCI6MTc0Nzc0OTU1MywiZXhwIjoxNzUwMzQxNTUzfQ.nFuYI5jPBMFfpI8V-aw5-KF9QI42R8R-FqQDJnZikDM';
      
      $jwtRefreshKey = env('JWT_REFRESH_TOKEN_SECRET_KEY');
      $decodedRefreshToken = JWT::decode($refToken, new Key($jwtRefreshKey, 'HS256'));

        $newAccessTokenPayload = [
        'iss' => $decodedRefreshToken->iss,
        'sub' => $decodedRefreshToken->sub,
        'name' => $decodedRefreshToken->name,
        'iat' => time(),
        'exp' => time()+10,
      ];

      $newAccessToken = self::generateJWTAccessToken($newAccessTokenPayload);

      return $newAccessToken;

      // return response()->json([
      //   'code' => 200,
      //   'success' => true,
      //   'message' => 'Valid Token',
      //   'token' => $newAccessToken
      // ]);

    } catch (ExpiredException $e) {
      throw new \Exception('Token expired');
      //return response()->json(['code' =>401,  'success' => false, 'message' => 'Refresh Token expired']);
    } catch (\Exception $e) {
      throw new \Exception('Invalid token');
      //return response()->json(['code' =>401,  'success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    }
  }
}