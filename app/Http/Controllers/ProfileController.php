<?php

namespace App\Http\Controllers;

use App\Models\FoodDeliveryPartner;
use App\Models\FoodDeliveryPartnerBankAccInformation;
use App\Models\FoodDeliveryPartnerDocument;
use App\Models\FoodDeliveryPartnerKinInformation;
use App\Models\FoodDeliveryPartnerOtherInformation;
use App\Models\FoodDeliveryPartnersTakenOrder;
use App\Models\FoodDeliveryPartnerUserReference;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    $userData = FoodDeliveryPartner::find($userId);

    if(!$userData) {
      return response()->json([
        'code' => 200,
        'success' => true,
        'message' => 'User Not Found',
      ], 200);
    }

    if ($userData->admin_approval != "accepted" ) {
      return response()->json([
        'code' => 401,
        'success' => true,
        'message' => 'User not approved by admin'
      ], 401);
    }

    $deliveryPartnerBankAccInfo = new FoodDeliveryPartnerBankAccInformation();
    $deliveryPartnerBankAccInfo->partner_id = $userId;
    $deliveryPartnerBankAccInfo->name = $request->name;
    $deliveryPartnerBankAccInfo->acc_number = $request->acc_number;
    $deliveryPartnerBankAccInfo->sort_code = $request->sort_code;
    $deliveryPartnerBankAccInfo->is_account_in_your_name  = $request->is_account_in_your_name;
    $deliveryPartnerBankAccInfo->name_on_the_account  = $request->name_on_the_account;
    $deliveryPartnerBankAccInfo->save();

    return response()->json([
      'code' => 200,
      'success' => true,
      'message' => 'Bank Account Info Updated Successfully',
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
      'docs.*.doc_expiry' => 'nullable|date',
      'docs.*.doc_url' => 'file|mimes:jpeg,png,jpg,svg,pdf|max:2048',

      'uses_car' => 'required|boolean',
      'uses_motorcycle' => 'required|boolean',
      'uses_bicycle' => 'required|boolean',
    
      'has_motoring_convictions' => 'required_if:uses_car,true|required_if:uses_motorcycle,true|boolean',

      'is_uk_licence' => 'required|boolean',
      'licence_country_of_issue' => 'required_if:is_uk_licence,true',
      //'has_medical_condition' => 'required|boolean',
      //'can_be_used_as_reference' => 'required|boolean',
      'is_agreed_privacy_policy' => 'required|boolean',
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

    if ($userData->admin_approval != "accepted" ) {
      return response()->json([
        'code' => 401,
        'success' => true,
        'message' => 'User not approved by admin'
      ], 401);
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
        $publicPath = base_path('public');
        $document->move($publicPath . '/images/users/', $docName);
        $deliveryPartnerDocs->doc_url = $docName;
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
}