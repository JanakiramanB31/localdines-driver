<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryOrder extends Model
{
  public function order_items(){
    return $this->hasMany(FoodDeliveryOrdersItem::class, 'order_id', 'id');
  }

}

?>