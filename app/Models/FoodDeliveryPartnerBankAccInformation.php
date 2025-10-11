<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryPartnerBankAccInformation extends Model
{

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'partner_id',
    'acc_number',
    'sort_code',
    'is_account_in_your_name',
    'name_on_the_account'
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
