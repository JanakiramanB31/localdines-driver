<?php

namespace App\Helpers;

use App\Models\FoodDeliveryPartner;

class UserValidationHelper
{
  /**
   * Check if user exists, not rejected, and is active (allows pending and approved users)
   *
   * @param int $userId The user ID to check
   * @return array Returns ['success' => bool, 'user' => User|null, 'response' => JsonResponse|null]
   */
  public static function checkUserExists($userId)
  {
    $user = FoodDeliveryPartner::findOrFail($userId);

    if (!$user) {
      return [
        'success' => false,
        'user' => null,
        'response' => response()->json([
          'code' => 404,
          'success' => false,
          'message' => 'User Not Found',
        ], 404)
      ];
    }

    // Check if user was rejected by admin
    if ($user->admin_approval == "rejected") {
      return [
        'success' => false,
        'user' => $user,
        'response' => response()->json([
          'code' => 403,
          'success' => false,
          'message' => 'User Rejected by admin'
        ], 403)
      ];
    }

    // Check if user is active
    if ($user->is_active == 0) {
      return [
        'success' => false,
        'user' => $user,
        'response' => response()->json([
          'code' => 403,
          'success' => false,
          'message' => 'User status is Inactive'
        ], 403)
      ];
    }

    return [
      'success' => true,
      'user' => $user,
      'response' => null
    ];
  }

  /**
   * Check if user exists and validate admin approval status
   *
   * @param int $userId The user ID to validate
   * @param bool $checkActive Whether to check if user is active (default: true)
   * @return array Returns ['success' => bool, 'user' => User|null, 'response' => JsonResponse|null]
   */
  public static function validateUserAndApproval($userId, $checkActive = true)
  {
    // First check if user exists, not rejected, and is active
    $validation = self::checkUserExists($userId);
    if (!$validation['success']) {
      return $validation;
    }

    $user = $validation['user'];

    // Check if user is not yet approved by admin
    if ($user->admin_approval != "accepted") {
      return [
        'success' => false,
        'user' => $user,
        'response' => response()->json([
          'code' => 403,
          'success' => false,
          'message' => 'User not approved by admin'
        ], 403)
      ];
    }

    // All validations passed
    return [
      'success' => true,
      'user' => $user,
      'response' => null
    ];
  }

  /**
   * Check if user exists and is in pending status (for profile updates)
   * Only pending users are allowed to update their profile information
   *
   * @param int $userId The user ID to check
   * @return array Returns ['success' => bool, 'user' => User|null, 'response' => JsonResponse|null]
   */
  public static function checkUserForProfileUpdate($userId, $checkAdminApproval = true)
  {
    // First check if user exists, not rejected, and is active
    $validation = self::checkUserExists($userId);
    if (!$validation['success']) {
      return $validation;
    }

    $user = $validation['user'];

    // Check if user is already accepted by admin
    if ($checkAdminApproval && $user->admin_approval == "accepted") {
      return [
        'success' => false,
        'user' => $user,
        'response' => response()->json([
          'code' => 403,
          'success' => false,
          'message' => 'User already approved by admin. Profile updates are not allowed.'
        ], 403)
      ];
    }

    // Only pending users can update their profile
    return [
      'success' => true,
      'user' => $user,
      'response' => null
    ];
  }
}
