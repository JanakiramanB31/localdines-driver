<?php

namespace App\Helpers;

use Exception;
use App\Models\FoodDeliveryConfig;

class FirebaseHelper
{
  /**
   * Get Firestore document using REST API
   *
   * @param string $collection Collection name
   * @param string $documentId Document ID
   * @return array|null
   */
  public static function getFirestoreDocument($collection, $documentId)
  {
    try {
      $projectId = env('FIREBASE_PROJECT_ID');

      if (!$projectId) {
        return null;
      }

      // Get access token for Firestore
      $accessToken = self::getAccessToken('https://www.googleapis.com/auth/datastore');

      if (!$accessToken) {
        return null;
      }

      $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$documentId}";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
      ]);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if (curl_error($ch)) {
        curl_close($ch);
        return null;
      }

      curl_close($ch);

      if ($httpCode !== 200) {
        return null;
      }

      $data = json_decode($response, true);

      if (!isset($data['fields'])) {
        return null;
      }

      // Convert Firestore format to simple array
      return self::parseFirestoreFields($data['fields']);

    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Create a new document in Firestore using REST API
   *
   * @param string $collection Collection name
   * @param string $documentId Document ID
   * @param array $data Data to store
   * @return array
   */
  public static function createFirestoreDocument($collection, $documentId, $data)
  {
    try {
      $projectId = env('FIREBASE_PROJECT_ID');

      if (!$projectId) {
        return ['success' => false, 'error' => 'Firebase project ID not configured'];
      }

      // Get access token for Firestore
      $accessToken = self::getAccessToken('https://www.googleapis.com/auth/datastore');

      if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to get Firebase access token'];
      }

      $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}?documentId={$documentId}";

      // Convert PHP array to Firestore format
      $firestoreData = [
        'fields' => self::convertToFirestoreFormat($data)
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firestoreData));
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
          'success' => false,
          'error' => $error,
          'error_code' => 'CURL_ERROR'
        ];
      }

      curl_close($ch);

      if ($httpCode === 200) {
        return ['success' => true, 'data' => json_decode($response, true)];
      } else {
        $responseData = json_decode($response, true);
        $errorMsg = isset($responseData['error']['message'])
          ? $responseData['error']['message']
          : "HTTP error: {$httpCode}";

        $errorCode = $responseData['error']['status'] ?? 'UNKNOWN';

        return [
          'success' => false,
          'error' => $errorMsg,
          'error_code' => $errorCode,
          'http_code' => $httpCode
        ];
      }

    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'EXCEPTION'
      ];
    }
  }

  /**
   * Update an existing document in Firestore using REST API
   *
   * @param string $collection Collection name
   * @param string $documentId Document ID
   * @param array $data Data to update
   * @return array
   */
  public static function updateFirestoreDocument($collection, $documentId, $data)
  {
    try {
      $projectId = env('FIREBASE_PROJECT_ID');

      if (!$projectId) {
        return ['success' => false, 'error' => 'Firebase project ID not configured'];
      }

      // Get access token for Firestore
      $accessToken = self::getAccessToken('https://www.googleapis.com/auth/datastore');

      if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to get Firebase access token'];
      }

      // Build update mask for partial updates
      $updateMask = array_keys($data);
      $updateMaskQuery = implode('&', array_map(function($field) {
        return 'updateMask.fieldPaths=' . urlencode($field);
      }, $updateMask));

      $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$documentId}?{$updateMaskQuery}";

      // Convert PHP array to Firestore format
      $firestoreData = [
        'fields' => self::convertToFirestoreFormat($data)
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firestoreData));
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
          'success' => false,
          'error' => $error,
          'error_code' => 'CURL_ERROR'
        ];
      }

      curl_close($ch);

      if ($httpCode === 200) {
        return ['success' => true, 'data' => json_decode($response, true)];
      } else {
        $responseData = json_decode($response, true);
        $errorMsg = isset($responseData['error']['message'])
          ? $responseData['error']['message']
          : "HTTP error: {$httpCode}";

        $errorCode = $responseData['error']['status'] ?? 'UNKNOWN';

        return [
          'success' => false,
          'error' => $errorMsg,
          'error_code' => $errorCode,
          'http_code' => $httpCode
        ];
      }

    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'EXCEPTION'
      ];
    }
  }

  /**
   * Delete a document from Firestore using REST API
   *
   * @param string $collection Collection name
   * @param string $documentId Document ID
   * @return array
   */
  public static function deleteFirestoreDocument($collection, $documentId)
  {
    try {
      $projectId = env('FIREBASE_PROJECT_ID');

      if (!$projectId) {
        return ['success' => false, 'error' => 'Firebase project ID not configured', 'error_code' => 'CONFIG_ERROR'];
      }

      // Get access token for Firestore
      $accessToken = self::getAccessToken('https://www.googleapis.com/auth/datastore');

      if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to get Firebase access token', 'error_code' => 'AUTH_ERROR'];
      }

      $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$documentId}";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
      ]);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
          'success' => false,
          'error' => $error,
          'error_code' => 'CURL_ERROR'
        ];
      }

      curl_close($ch);

      if ($httpCode === 200) {
        return ['success' => true];
      } else {
        $responseData = json_decode($response, true);
        $errorMsg = isset($responseData['error']['message'])
          ? $responseData['error']['message']
          : "HTTP error: {$httpCode}";

        $errorCode = $responseData['error']['status'] ?? 'UNKNOWN';

        return [
          'success' => false,
          'error' => $errorMsg,
          'error_code' => $errorCode,
          'http_code' => $httpCode
        ];
      }

    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'EXCEPTION'
      ];
    }
  }

  /**
   * Convert PHP array to Firestore field format
   *
   * @param array $data
   * @return array
   */
  private static function convertToFirestoreFormat($data)
  {
    $fields = [];

    foreach ($data as $key => $value) {
      $fields[$key] = self::convertValueToFirestore($value);
    }

    return $fields;
  }

  /**
   * Convert a single PHP value to Firestore format
   *
   * @param mixed $value
   * @return array
   */
  private static function convertValueToFirestore($value)
  {
    if (is_null($value)) {
      return ['nullValue' => null];
    }
    if (is_bool($value)) {
      return ['booleanValue' => $value];
    }
    if (is_int($value)) {
      return ['integerValue' => (string)$value];
    }
    if (is_float($value)) {
      return ['doubleValue' => $value];
    }
    if (is_string($value)) {
      return ['stringValue' => $value];
    }
    if (is_array($value)) {
      // Check if it's an associative array (map) or indexed array
      if (empty($value) || array_keys($value) !== range(0, count($value) - 1)) {
        // Associative array -> mapValue
        return [
          'mapValue' => [
            'fields' => self::convertToFirestoreFormat($value)
          ]
        ];
      } else {
        // Indexed array -> arrayValue
        $arrayValues = [];
        foreach ($value as $item) {
          $arrayValues[] = self::convertValueToFirestore($item);
        }
        return [
          'arrayValue' => [
            'values' => $arrayValues
          ]
        ];
      }
    }

    // Default to string
    return ['stringValue' => (string)$value];
  }

  /**
   * Send push notification via FCM HTTP v1 API
   *
   * @param string $fcmToken Device FCM token
   * @param int $orderId Order ID
   * @param string $message Notification message
   * @param array|null $orderData Order data for notification content
   * @return array
   */
  public static function sendPushNotification($fcmToken, $orderId, $message, $orderData = null)
  {
    try {
      if (!$fcmToken) {
        return ['success' => false, 'error' => 'No FCM token found'];
      }

      $projectId = env('FIREBASE_PROJECT_ID');
      if (!$projectId) {
        return ['success' => false, 'error' => 'Firebase project ID not configured'];
      }

      // Get OAuth 2.0 access token for FCM
      $accessToken = self::getAccessToken('https://www.googleapis.com/auth/firebase.messaging');
      if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to get Firebase access token'];
      }

      // Enhanced notification body with order info
      $notificationBody = $message;
      $paymentStatus = '';
      $pickupLocation = '';
      $deliveryLocation = '';
      $distanceMiles = '';

      if ($orderData) {
        $pickupTime = isset($orderData['order_pickup_time']) ? date('H:i', strtotime($orderData['order_pickup_time'])) : 'N/A';
        // Build full delivery address
        $deliveryParts = array_filter([
          $orderData['d_address_1'] ?? '',
          $orderData['d_address_2'] ?? '',
          $orderData['d_city'] ?? '',
          $orderData['d_zip'] ?? $orderData['post_code'] ?? ''
        ], fn($v) => trim($v) !== '');
        $deliveryLocation = implode(', ', $deliveryParts) ?: 'N/A';
        $paymentStatus = ($orderData['payment_status'] ?? 0) == 1 ? "Paid" : "Unpaid";

        // Get pickup postal code from config
        $pickupPostalCode = FoodDeliveryConfig::getValue('PICKUP_POSTAL_CODE', '');
        $pickupParts = array_filter([
          $orderData['p_name'] ?? '',
          $pickupPostalCode
        ], fn($v) => trim($v) !== '');
        $pickupLocation = implode(', ', $pickupParts) ?: 'N/A';

        $distanceMiles = self::calculateDistanceMiles(
          $orderData['p_latitude'] ?? 0,
          $orderData['p_longitude'] ?? 0,
          $orderData['d_latitude'] ?? 0,
          $orderData['d_longitude'] ?? 0
        );

        $notificationBody = "Pickup: {$pickupLocation}\n";
        $notificationBody .= "Pickup Time: {$pickupTime}\n";
        $notificationBody .= "Drop: {$deliveryLocation}\n";
        $notificationBody .= "Distance: {$distanceMiles}\n";
        $notificationBody .= "Charge: " . ($orderData['delivery_charges'] ?? '');
      }

      // FCM HTTP v1 API payload structure
      $notificationData = [
        'message' => [
          'token' => $fcmToken,
          'data' => [
            'order_id' => (string)$orderId,
            'type' => 'new_order',
            'status' => (string)$paymentStatus,
            'p_addr' => (string)$pickupLocation,
            'd_addr' => (string)$deliveryLocation,
            'delivery_charge' => (string)($orderData['delivery_charges'] ?? ''),
            'p_lat' => (string)($orderData['p_latitude'] ?? ''),
            'p_lng' => (string)($orderData['p_longitude'] ?? ''),
            'distance_km' => (string)$distanceMiles,
          ],
          'android' => [
            'priority' => "HIGH",
            'notification' => [
              'channel_id' => 'orders_channel',
              'sound' => 'order_bell',
              'tag' => "order-" . ($orderData['order_id'] ?? $orderId)
            ]
          ],
          'apns' => [
            'headers' => [
              'apns-priority' => '10'
            ],
            'payload' => [
              'aps' => [
                'sound' => 'order_bell.wav',
                'content-available' => 1
              ]
            ]
          ]
        ]
      ];

      $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
      ];

      $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
          'success' => false,
          'error' => $error,
          'error_code' => 'CURL_ERROR',
          'distance' => $distanceMiles,
        ];
      }

      curl_close($ch);

      if ($httpCode === 200) {
        return [
          'success' => true,
          'distance' => $distanceMiles,
        ];
      } else {
        $responseData = json_decode($response, true);
        $errorMsg = isset($responseData['error']['message'])
          ? $responseData['error']['message']
          : "HTTP error: {$httpCode}";

        $errorCode = $responseData['error']['status'] ?? 'UNKNOWN';

        return [
          'success' => false,
          'error' => $errorMsg,
          'error_code' => $errorCode,
          'http_code' => $httpCode,
          'distance' => $distanceMiles,
        ];
      }

    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'EXCEPTION',
        'distance' => $distanceMiles ?? null,
      ];
    }
  }

  /**
   * Parse Firestore field format to simple PHP array
   *
   * @param array $fields
   * @return array
   */
  private static function parseFirestoreFields($fields)
  {
    $result = [];

    foreach ($fields as $key => $value) {
      $result[$key] = self::parseFirestoreValue($value);
    }

    return $result;
  }

  /**
   * Parse a single Firestore value
   *
   * @param array $value
   * @return mixed
   */
  private static function parseFirestoreValue($value)
  {
    if (isset($value['stringValue'])) {
      return $value['stringValue'];
    }
    if (isset($value['integerValue'])) {
      return (int)$value['integerValue'];
    }
    if (isset($value['doubleValue'])) {
      return (float)$value['doubleValue'];
    }
    if (isset($value['booleanValue'])) {
      return $value['booleanValue'];
    }
    if (isset($value['nullValue'])) {
      return null;
    }
    if (isset($value['timestampValue'])) {
      return $value['timestampValue'];
    }
    if (isset($value['geoPointValue'])) {
      return [
        'lat' => $value['geoPointValue']['latitude'] ?? null,
        'lng' => $value['geoPointValue']['longitude'] ?? null
      ];
    }
    if (isset($value['mapValue'])) {
      return self::parseFirestoreFields($value['mapValue']['fields'] ?? []);
    }
    if (isset($value['arrayValue'])) {
      $arr = [];
      foreach ($value['arrayValue']['values'] ?? [] as $item) {
        $arr[] = self::parseFirestoreValue($item);
      }
      return $arr;
    }

    return null;
  }

  /**
   * Calculate distance in miles using Haversine formula
   *
   * @param float $lat1
   * @param float $lon1
   * @param float $lat2
   * @param float $lon2
   * @return float
   */
  private static function calculateDistanceMiles($lat1, $lon1, $lat2, $lon2)
  {
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos($lat1) * cos($lat2) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $earthRadiusMiles = 3959;
    $distance = $earthRadiusMiles * $c;

    return round($distance, 2);
  }

  /**
   * Get Firebase access token using service account
   *
   * @param string $scope The OAuth scope to request
   * @return string|null
   */
  private static function getAccessToken($scope)
  {
    try {
      $privateKey = env('FIREBASE_PRIVATE_KEY');
      $clientEmail = env('FIREBASE_CLIENT_EMAIL');

      if (!$privateKey || !$clientEmail) {
        return null;
      }

      // Replace escaped newlines in private key
      $privateKey = str_replace('\\n', "\n", $privateKey);

      // Create JWT
      $now = time();
      $expiry = $now + 3600;

      $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
      ];

      $claimSet = [
        'iss' => $clientEmail,
        'scope' => $scope,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $expiry
      ];

      $encodedHeader = self::base64UrlEncode(json_encode($header));
      $encodedClaimSet = self::base64UrlEncode(json_encode($claimSet));
      $signatureInput = $encodedHeader . '.' . $encodedClaimSet;

      $signature = '';
      openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
      $encodedSignature = self::base64UrlEncode($signature);

      $jwt = $signatureInput . '.' . $encodedSignature;

      // Exchange JWT for access token
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
      ]));

      $response = curl_exec($ch);

      if (curl_error($ch)) {
        curl_close($ch);
        return null;
      }

      curl_close($ch);

      $responseData = json_decode($response, true);

      if (!isset($responseData['access_token'])) {
        return null;
      }

      return $responseData['access_token'];

    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Base64 URL encode
   *
   * @param string $data
   * @return string
   */
  private static function base64UrlEncode($data)
  {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }
}
