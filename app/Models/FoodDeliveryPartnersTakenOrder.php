<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryPartnersTakenOrder extends Model
{

  public function order() {
    return $this->belongsTo(FoodDeliveryOrder::class, 'order_id');
  }

  // public function food_delivery_clients(){
  //   return $this->belongsTo(FoodDeliveryClient::class, 'client_id');
  // }
}

?>