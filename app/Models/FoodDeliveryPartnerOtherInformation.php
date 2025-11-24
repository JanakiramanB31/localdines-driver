<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryPartnerOtherInformation extends Model
{

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'partner_id',
    'requires_work_permit',
    'uses_car',
    'uses_motorcycle',
    'uses_bicycle',
    'has_motoring_convictions',
    'is_uk_licence',
    'licence_country_of_issue',
    'has_medical_condition',
    'can_be_used_as_reference',
    'is_agreed_privacy_policy'
  ];

  /**
   * The attributes excluded from the model's JSON form.
   *
   * @var array
   */
  // protected $hidden = [
  //   'password',
  // ];
}
