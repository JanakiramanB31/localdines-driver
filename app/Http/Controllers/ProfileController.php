<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Helpers\UserValidationHelper;
use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnerAddress;
use App\Models\FoodDeliveryPartnerBankAccInformation;
use App\Models\FoodDeliveryPartnerDocument;
use App\Models\FoodDeliveryPartnerOtherInformation;
use App\Models\FoodDeliveryPartnerUserReference;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfileController extends Controller
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

  public function getProfileInfo(Request $request) {

    $userId = $request->auth->sub;

    // Validate user existence, admin approval, and active status
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId, false);
    if (!$validation['success']) {
      return $validation['response'];
    }
    $userData = $validation['user'];

    $data = $userData->toArray();

    $address = FoodDeliveryPartnerAddress::where('partner_id', $userId)->first();
    
    unset(
      $address['id'],
      $address['partner_id'],
      $address['is_active'], 
      $address['created_at'],
      $address['updated_at']
    );

    $data['is_admin_approved'] = $data['admin_approval'] == 'accepted' ? true : false;

    $documents = FoodDeliveryPartnerDocument::select('id', 'doc_type', 'doc_number', 'doc_expiry', 'doc_url')
    ->where('partner_id', $userId)->get()
     ->map(function ($doc) {
        $doc->doc_expiry = $doc->doc_expiry 
            ? Carbon::parse($doc->doc_expiry)->format('d-m-Y') 
            : null;
        return $doc;
    });


    unset(
      $data['admin_approval'],
      $data['is_active'], 
      $data['updated_at'], 
      $data['approved_at'],
      $data['fcm_token']
    );
    $data['duty_status'] = $data['duty_status'] == 1 ? Constants::DUTY_STATUS['ONLINE'] : Constants::DUTY_STATUS['OFFLINE'];
    $data['is_non_british'] = $data['is_non_british'] == 1 ? true : false;
    $data['acc_created_at'] = Carbon::parse($data['created_at'])->format('d-m-Y');
    unset($data['created_at']);

    $data['address_info'] = $address;
    $data['documents'] = $documents;

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Profile Info retrieved Successfully',
      'data' => $data
    ], 200);
  }

  public function deleteUserDocument(Request $request, $id) {
    $userId = $request->auth->sub;
    $docId = $id;

    // Validate user existence, admin approval, and active status
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId, true);
    if (!$validation['success']) {
      return $validation['response'];
    }

    $document = FoodDeliveryPartnerDocument::find($docId);

    if (!$document || $document->partner_id !== $userId) {
      return response()->json([
        'code' => 403,
        'success' => false,
        'message' => 'This document does not belong to your account.'
      ], 403);
    }

    $document->delete($docId);

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Document deleted successfully.'
    ], 200);
  }

  public function getDutyStatus(Request $request) {

    $userId = $request->auth->sub;

    // Validate user existence, admin approval, and active status
    $validation = UserValidationHelper::validateUserAndApproval($userId, true);
    if (!$validation['success']) {
      return $validation['response'];
    }
    $userData = $validation['user'];

    $dutyStatus = $userData->duty_status == true ? Constants::DUTY_STATUS['ONLINE'] : Constants::DUTY_STATUS['OFFLINE'];

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Duty Status retrieved Successfully',
      'data' => ([
        'duty_status' => $dutyStatus
      ])
    ], 200);
  }

  public function checkAccountStatus(Request $request) {

    $userId = $request->auth->sub;

    // Check if user exists only (no approval check for account status)
    $validation = UserValidationHelper::checkUserExists($userId);
    if (!$validation['success']) {
      return $validation['response'];
    }
    $userData = $validation['user'];

    $data = $userData->toArray();

    unset(
      $data['title'],
      $data['phone_number'],
      $data['gender'],
      $data['email'],
      $data['is_active'],
      $data['nationality'],
      $data['f_name'],
      $data['s_name'],
      $data['m_name'],
      $data['dob'],
      $data['is_non_british']
    );
    $data['duty_status'] = $data['duty_status'] == 1 ? Constants::DUTY_STATUS['ONLINE'] : Constants::DUTY_STATUS['OFFLINE'];
    $data['approved_at'] = $data['approved_at'] !== null ? Carbon::parse($data['approved_at'])->format('d-m-Y') : null;
    $data['acc_created_at'] = Carbon::parse($data['created_at'])->format('d-m-Y');
    unset($data['created_at'], $data['updated_at']);

    $bankInfo = FoodDeliveryPartnerBankAccInformation::where('partner_id', $userId)->first();
    $otherInfo = FoodDeliveryPartnerOtherInformation::where('partner_id', $userId)->first();

    $data['is_bank_info_completed'] = $bankInfo ? true : false;
    $data['is_other_info_completed'] = $otherInfo ? true : false;

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Account Status retrieved Successfully',
      'data' => $data
    ], 200);
  }

  // public function updateprofileInfo(Request $request) {
  //   $this->validate($request, [
  //     'has_motoring_convictions' => 'required|boolean',
  //     'requires_work_permit' => 'required|boolean',
  //     /* Documents array validation */
  //     'docs' => 'required|array',
  //     'docs.*.doc_type' => 'required|in:visa,passport,ni',
  //     'docs.*.doc_number' => 'required',
  //     'docs.*.doc_expiry' => 'required|date',
  //     'uses_car' => 'required|boolean',
  //     'uses_motorcycle' => 'required|boolean',
  //     'uses_bicycle' => 'required|boolean',
  //   ]);

  //   $userId = $request->auth->sub;

  //   $userData = FoodDeliveryPartner::find($userId);

  //   if(!$userData) {
  //     return response()->json([
  //       'code' => 200,
  //       'success' => true,
  //       'message' => 'User Not Found',
  //     ], 200);
  //   }

  //   if ($userData->admin_approval != "accepted" ) {
  //     return response()->json([
  //       'code' => 401,
  //       'success' => true,
  //       'message' => 'User not approved by admin'
  //     ], 401);
  //   }
    
  //   $deliveryPartner = new FoodDeliveryPartner();
  //   $deliveryPartner->requires_work_permit  = $request->requires_work_permit ;
  //   $deliveryPartner->has_full_uk_driving_licence  = $request->has_full_uk_driving_licence ;
  //   $deliveryPartner->has_motoring_convictions  = $request->has_motoring_convictions ;
  //   $deliveryPartner->uses_car = $request->uses_car;
  //   $deliveryPartner->uses_motorcycle = $request->uses_motorcycle;
  //   $deliveryPartner->uses_bicycle = $request->uses_bicycle;
  //   $deliveryPartner->save();

  //   return response()->json([
  //     'code' => 200,
  //     'success' => true,
  //     'message' => 'Kin Info Updated Successful',
  //   ], 200);

  // }

  /**
   * @OA\Post(
   *     path="/profile/update-kin-info",
   *     summary="Update next of kin information",
   *     tags={"Profile"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name", "phone", "relationship", "address"},
   *             @OA\Property(property="name", type="string"),
   *             @OA\Property(property="phone", type="string"),
   *             @OA\Property(property="relationship", type="string"),
   *             @OA\Property(property="address", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Kin Info Updated Successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Kin Info Updated Successful")
   *         )
   *     )
   * )
   */

  // public function updateKinInfo(Request $request) {
  //   $this->validate($request, [
  //     'name' => 'required',
  //     'phone' => 'required',
  //     'relationship' => 'required',
  //     'address' => 'required'
  //   ]);

  //   $userId = $request->auth->sub;

  //   $userData = FoodDeliveryPartner::find($userId);

  //   if(!$userData) {
  //     return response()->json([
  //       'code' => 200,
  //       'success' => true,
  //       'message' => 'User Not Found',
  //     ], 200);
  //   }

  //   if ($userData->admin_approval != "accepted" ) {
  //     return response()->json([
  //       'code' => 401,
  //       'success' => true,
  //       'message' => 'User not approved by admin'
  //     ], 401);
  //   }

  //   $deliveryPartnerKinInfo = new FoodDeliveryPartnerKinInformation();
  //   $deliveryPartnerKinInfo->partner_id = $userId;
  //   $deliveryPartnerKinInfo->name = $request->name;
  //   $deliveryPartnerKinInfo->phone = $request->phone;
  //   $deliveryPartnerKinInfo->relationship = $request->relationship;
  //   $deliveryPartnerKinInfo->address = $request->address;
  //   $deliveryPartnerKinInfo->save();

  //   return response()->json([
  //     'code' => 200,
  //     'success' => true,
  //     'message' => 'Kin Info Updated Successful',
  //   ], 200);

  // }

  /**
   * @OA\Post(
   *     path="/profile/update-bank-info",
   *     summary="Update bank account information",
   *     tags={"Profile"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name", "acc_number", "sort_code", "is_account_in_your_name", "name_on_the_account"},
   *             @OA\Property(property="name", type="string"),
   *             @OA\Property(property="acc_number", type="string"),
   *             @OA\Property(property="sort_code", type="string"),
   *             @OA\Property(property="is_account_in_your_name", type="boolean"),
   *             @OA\Property(property="name_on_the_account", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Bank Account Info Updated Successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Bank Account Info Updated Successful")
   *         )
   *     )
   * )
   */

  public function updateBankAccInfo(Request $request) {
    $this->validate($request, [
      'name' => 'required',
      'acc_number' => 'required',
      'sort_code' => 'required',
      'is_account_in_your_name' => 'required|boolean',
      'name_on_the_account' => 'required'
    ]);

    $userId = $request->auth->sub;

    // Check if user is pending (only pending users can update bank info)
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId);
    if (!$validation['success']) {
      return $validation['response'];
    }

    // Update existing record or create new one
    FoodDeliveryPartnerBankAccInformation::updateOrCreate(
      ['partner_id' => $userId],
      [
        'partner_id' => $userId,
        'name' => $request->name,
        'acc_number' => $request->acc_number,
        'sort_code' => $request->sort_code,
        'is_account_in_your_name' => $request->is_account_in_your_name,
        'name_on_the_account' => $request->name_on_the_account
      ]
    );

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Bank Account Info Updated Successfully',
    ], 200);

  }

  public function GetBankAccInfo(Request $request) {

    $userId = $request->auth->sub;

    // Validate user existence, admin approval, and active status
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId, false);
    if (!$validation['success']) {
      return $validation['response'];
    }

    $bankAccData = FoodDeliveryPartnerBankAccInformation::where('partner_id', $userId)->first();

    $bankAccData['is_admin_approved'] = $validation['user']['admin_approval'] == 'accepted' ? true : false;

    unset(
      $bankAccData['id'],
      $bankAccData['is_active'], 
      $bankAccData['created_at'], 
      $bankAccData['updated_at']
    );

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Bank Info retrieved Successfully',
      'data' => $bankAccData
    ], 200);
  }

  /**
   * @OA\Post(
   *     path="/profile/update-additional-info",
   *     summary="Update additional personal and document information",
   *     tags={"Profile"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={
   *                 "requires_work_permit", "uses_car", "uses_motorcycle", "uses_bicycle",
   *                 "has_motoring_convictions", "is_uk_licence", "licence_country_of_issue", "is_agreed_privacy_policy", "docs"
   *             },
   *             @OA\Property(property="requires_work_permit", type="boolean"),
   *             @OA\Property(property="uses_car", type="boolean"),
   *             @OA\Property(property="uses_motorcycle", type="boolean"),
   *             @OA\Property(property="uses_bicycle", type="boolean"),
   *             @OA\Property(property="has_motoring_convictions", type="boolean"),
   *             @OA\Property(property="is_uk_licence", type="boolean"),
   *             @OA\Property(property="licence_country_of_issue", type="string"),
   *             @OA\Property(property="has_medical_condition", type="boolean"),
   *             @OA\Property(property="can_be_used_as_reference", type="boolean"),
   *             @OA\Property(property="is_agreed_privacy_policy", type="boolean"),
   *
   *             @OA\Property(
   *                 property="docs",
   *                 type="array",
   *                 @OA\Items(
   *                     required={"doc_type"},
   *                     @OA\Property(property="doc_type", type="string", enum={"visa", "passport", "ni", "license", "sign"}),
   *                     @OA\Property(property="doc_number", type="string"),
   *                     @OA\Property(property="doc_expiry", type="string", format="date"),
   *                     @OA\Property(property="doc_url", type="string")
   *                 )
   *             ),
   *
   *             @OA\Property(
   *                 property="user_references",
   *                 type="array",
   *                 @OA\Items(
   *                     @OA\Property(property="title", type="string"),
   *                     @OA\Property(property="f_name", type="string"),
   *                     @OA\Property(property="s_name", type="string"),
   *                     @OA\Property(property="company", type="string"),
   *                     @OA\Property(property="phone", type="string"),
   *                     @OA\Property(property="email", type="string", format="email")
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Profile Info Updated Successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Profile Info Updated Successful")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="User not approved by admin",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=401),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="User not approved by admin")
   *         )
   *     )
   * )
   */

  public function updateProfileInfo(Request $request) {
    $this->validate($request, [
      'requires_work_permit' => 'required|boolean',
      /* Documents array validation */
      'docs' => 'required|array',
      'docs.*.doc_type' => 'required|in:visa,passport,ni,license,sign',
      'docs.*.doc_number' => 'nullable|string',
      'docs.*.doc_expiry' => 'nullable|date|after:today',
      'docs.*.doc_url' => 'required|file|mimes:jpeg,png,jpg,svg,pdf|max:2048',

      'uses_car' => 'required|boolean',
      'uses_motorcycle' => 'required|boolean',
      'uses_bicycle' => 'required|boolean',

      'has_motoring_convictions' => 'required_if:uses_car,true|required_if:uses_motorcycle,true|boolean',

      'is_uk_licence' => 'required|boolean',
      'licence_country_of_issue' => 'required_if:is_uk_licence,true',
      //'has_medical_condition' => 'required|boolean',
      //'can_be_used_as_reference' => 'required|boolean',
      'is_agreed_privacy_policy' => 'required|boolean|accepted',
    ], [
      'is_agreed_privacy_policy.accepted' => 'Please accept the privacy policy.',
    ]);

    $userId = $request->auth->sub;

    // Check if user is pending (only pending users can update profile)
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId);
    if (!$validation['success']) {
      return $validation['response'];
    }

    // Additional validation for document expiry dates
    if ($request->has('docs')) {
      foreach ($request->docs as $index => $doc) {
        if (isset($doc['doc_expiry'])) {
          $expiryDate = Carbon::parse($doc['doc_expiry'])->format('Y-m-d');
          if (Carbon::parse($expiryDate)->isPast()) {
            return response()->json([
              'code' => 422,
              'success' => false,
              'message' => "Document expiry date for {$doc['doc_type']} must be a future date"
            ], 422);
          }
        }
      }
    }

    $deliveryPartnerOtherInfo = new FoodDeliveryPartnerOtherInformation();
    $deliveryPartnerOtherInfo->partner_id = $userId;
    $deliveryPartnerOtherInfo->requires_work_permit  = $request->requires_work_permit ;

    $deliveryPartnerOtherInfo->uses_car = $request->uses_car;
    $deliveryPartnerOtherInfo->uses_motorcycle = $request->uses_motorcycle;
    $deliveryPartnerOtherInfo->uses_bicycle = $request->uses_bicycle;

    $deliveryPartnerOtherInfo->has_motoring_convictions  = $request->has_motoring_convictions ;

    $deliveryPartnerOtherInfo->is_uk_licence = $request->is_uk_licence;
    $deliveryPartnerOtherInfo->licence_country_of_issue = $request->licence_country_of_issue;

    $deliveryPartnerOtherInfo->has_medical_condition = $request->has_medical_condition;
    $deliveryPartnerOtherInfo->can_be_used_as_reference  = $request->can_be_used_as_reference;

    $deliveryPartnerOtherInfo->is_agreed_privacy_policy  = $request->is_agreed_privacy_policy;

    $deliveryPartnerOtherInfo->save();

    foreach ($request->docs as $index => $doc) {
      $deliveryPartnerDocs = new FoodDeliveryPartnerDocument(); 
      $deliveryPartnerDocs->partner_id = $userId;
      $deliveryPartnerDocs->doc_type = $doc['doc_type'] ?? null;
      $deliveryPartnerDocs->doc_number = $doc['doc_number'] ?? null;
      $deliveryPartnerDocs->doc_expiry = $doc['doc_expiry'] ?? null;
      if ($request->hasFile("docs.$index.doc_url")) {
        $document = $request->file("docs.$index.doc_url");
        $docName = time() . '_' . uniqid() . '.' . $document->getClientOriginalExtension();  

        $uploadPath = base_path("public/users/$userId");
        // echo $uploadPath;  

        if (!file_exists($uploadPath)) {
          mkdir($uploadPath, 0777, true);
        }

        $document->move($uploadPath, $docName);
        $deliveryPartnerDocs->doc_url = "images/users/$userId/$docName";
      } else {
        $deliveryPartnerDocs->doc_url = null;
      }
      
      $deliveryPartnerDocs->save();
    }

    if (is_array($request->user_references)) {
      foreach($request->user_references as $userRef) {
        if (
          !empty($userRef['title']) ||
          !empty($userRef['f_name']) ||
          !empty($userRef['s_name']) ||
          !empty($userRef['company']) ||
          !empty($userRef['phone']) ||
          !empty($userRef['email'])
        ) {
          $deliveryPartnerUserRefInfo = new FoodDeliveryPartnerUserReference();
          $deliveryPartnerUserRefInfo->partner_id = $userId;
          $deliveryPartnerUserRefInfo->title = $userRef['title'] ?? null;
          $deliveryPartnerUserRefInfo->f_name = $userRef['f_name'] ?? null;
          $deliveryPartnerUserRefInfo->s_name = $userRef['s_name'] ?? null;
          $deliveryPartnerUserRefInfo->company = $userRef['company'] ?? null;
          $deliveryPartnerUserRefInfo->phone = $userRef['phone'] ?? null;
          $deliveryPartnerUserRefInfo->email = $userRef['email'] ?? null;
          $deliveryPartnerUserRefInfo->save();
        }
      }
    }

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Profile Info Updated Successfully',
    ], 200);

  }

  /**
   * @OA\Get(
   *     path="/profile/update-info",
   *     summary="Get update profile information data",
   *     tags={"Profile"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Update Profile Info retrieved Successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Update Profile Info retrieved Successfully"),
   *             @OA\Property(property="data", type="object", description="Other information data")
   *         )
   *     )
   * )
   */
  public function getOtherProfileInfo(Request $request) {

    $userId = $request->auth->sub;

    // Validate user existence, admin approval, and active status
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId, false);
    if (!$validation['success']) {
      return $validation['response'];
    }

    $otherInfo = FoodDeliveryPartnerOtherInformation::where('partner_id', $userId)->first();

    $otherInfo['is_admin_approved'] = $validation['user']['admin_approval'] == 'accepted' ? true : false;

    unset(
      $otherInfo['id'],
      $otherInfo['is_active'], 
      $otherInfo['created_at'], 
      $otherInfo['updated_at']
    );

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Update Profile Info retrieved Successfully',
      'data' => $otherInfo
    ], 200);
  }

  // public function updateUserReferences(Request $request) {
  //   $this->validate($request, [
  //     'title' => 'required',
  //     'f_name' => 'required',
  //     's_name' => 'required',
  //     'company' => 'required',
  //     'phone' => 'required',
  //     'email' => 'required'
  //   ]);

  //   $userId = $request->auth->sub;

  //   $userData = FoodDeliveryPartner::find($userId);

  //   if(!$userData) {
  //     return response()->json([
  //       'code' => 200,
  //       'success' => true,
  //       'message' => 'User Not Found',
  //     ], 200);
  //   }

  //   if ($userData->admin_approval != "accepted" ) {
  //     return response()->json([
  //       'code' => 401,
  //       'success' => true,
  //       'message' => 'User not approved by admin'
  //     ], 401);
  //   }

  //   $deliveryPartnerKinInfo = new FoodDeliveryPartnerUserReference();
  //   $deliveryPartnerKinInfo->partner_id = $userId;
  //   $deliveryPartnerKinInfo->title = $request->title;
  //   $deliveryPartnerKinInfo->f_name = $request->f_name;
  //   $deliveryPartnerKinInfo->s_name = $request->s_name;
  //   $deliveryPartnerKinInfo->company = $request->company;
  //   $deliveryPartnerKinInfo->phone = $request->phone;
  //   $deliveryPartnerKinInfo->email = $request->email;
  //   $deliveryPartnerKinInfo->save();

  //   return response()->json([
  //     'code' => 200,
  //     'success' => true,
  //     'message' => 'User Reference Updated Successful',
  //   ], 200);

  // }

  /**
   * @OA\Post(
   *     path="/profile/update-personal-info",
   *     summary="Update user personal information",
   *     tags={"Profile"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="title", type="string"),
   *             @OA\Property(property="f_name", type="string", minLength=3),
   *             @OA\Property(property="m_name", type="string", minLength=1),
   *             @OA\Property(property="s_name", type="string", minLength=3),
   *             @OA\Property(property="email", type="string", format="email"),
   *             @OA\Property(property="phone_number", type="string"),
   *             @OA\Property(property="dob", type="string", format="date"),
   *             @OA\Property(property="nationality", type="string"),
   *             @OA\Property(property="is_non_british", type="boolean"),
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
   *         response=200,
   *         description="Personal Info Updated Successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=200),
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Personal Info Updated Successfully")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="User not approved by admin",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=401),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="User not approved by admin")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User Not Found",
   *         @OA\JsonContent(
   *             @OA\Property(property="code", type="integer", example=404),
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="User Not Found")
   *         )
   *     )
   * )
   */

  public function updatePersonalInfo(Request $request) {
    $this->validate($request, [
      'title' => 'nullable|string',
      'f_name' => 'nullable|min:3',
      's_name' => 'nullable|min:3',
      'm_name' => 'nullable|min:1',
      'phone_number' => 'nullable|string',
      'email' => 'nullable|regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
      'dob' => 'nullable|date|before:today',
      'nationality' => 'nullable|string',
      'is_non_british' => 'nullable|boolean',

      /* Address Info */
      'home_no' => 'nullable|string|required_without:home_name',
      'home_name' => 'nullable|string|required_without:home_no',
      'street_name' => 'nullable|string',
      'city' => 'nullable|string',
      'county' => 'nullable|string',
      'post_code' => 'nullable|string',
      'home_phone' => 'nullable|string',
    ]);

    $userId = $request->auth->sub;

    // Check if user is pending (only pending users can update personal info)
    $validation = UserValidationHelper::checkUserForProfileUpdate($userId);
    if (!$validation['success']) {
      return $validation['response'];
    }
    $deliveryPartner = $validation['user'];

    if ($request->has('dob') && $request->dob) {
      $dobDate = Carbon::parse($request->dob)->format('Y-m-d');
      if (!Carbon::parse($dobDate)->isPast()) {
        return response()->json([
          'code' => 422,
          'success' => false,
          'message' => 'Date of birth must be a past date'
        ], 422);
      }
    }

    // Only check email existence if it's being changed
    if ($request->email && $request->email !== $deliveryPartner->email) {
      $checkEmailExistence = FoodDeliveryPartner::where('email', $request->email)->where('id', '!=', $userId)->first();

      if ($checkEmailExistence) {
        return response()->json([
          'code' => 409,
          'success' => false,
          'message' => 'Email already taken by another user',
        ], 409);
      }
    }

    // Only check phone existence if it's being changed
    if ($request->phone_number && $request->phone_number !== $deliveryPartner->phone_number) {
      $checkPhoneExistence = FoodDeliveryPartner::where('phone_number', $request->phone_number)->where('id', '!=', $userId)->first();

      if ($checkPhoneExistence) {
        return response()->json([
          'code' => 409,
          'success' => false,
          'message' => 'Phone number already taken by another user',
        ], 409);
      }
    }

    $deliveryPartner->title = $request->title ?? $deliveryPartner->title;
    $deliveryPartner->f_name = $request->f_name ?? $deliveryPartner->f_name;
    $deliveryPartner->m_name = $request->m_name ?? $deliveryPartner->m_name;
    $deliveryPartner->s_name = $request->s_name ?? $deliveryPartner->s_name;
    $deliveryPartner->phone_number = $request->phone_number ?? $deliveryPartner->phone_number;
    $deliveryPartner->email = $request->email ?? $deliveryPartner->email;
    $deliveryPartner->dob = $request->dob ? Carbon::parse($request->dob)->format('Y-m-d') : $deliveryPartner->dob;
    $deliveryPartner->nationality = $request->nationality ?? $deliveryPartner->nationality;
    $deliveryPartner->is_non_british = $request->is_non_british ?? $deliveryPartner->is_non_british;
    $deliveryPartner->save();

    $deliveryPartnerAddress = FoodDeliveryPartnerAddress::where('partner_id', $userId)->first();

    $deliveryPartnerAddress->home_no = $request->home_no;
    $deliveryPartnerAddress->home_name = $request->home_name;
    $deliveryPartnerAddress->street_name = $request->street_name;
    $deliveryPartnerAddress->city = $request->city;
    $deliveryPartnerAddress->county = $request->county;
    $deliveryPartnerAddress->post_code = $request->post_code;
    $deliveryPartnerAddress->home_phone = $request->home_phone;

    $deliveryPartnerAddress->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Personal Info Updated Successfully',
    ], 200);
  }
}