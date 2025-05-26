<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryPartnersLoginOtp extends Model
{

  /**
   * The attributes that are mass assignable.
   *
   * @var array
 */
  protected $fillable = [
    'phone_number'
  ];

  /**
   * The attributes excluded from the model's JSON form.
   *
   * @var array
   */
}
