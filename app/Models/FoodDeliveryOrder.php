<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryOrder extends Model
{
  protected $table = 'food_delivery_orders';
  public $timestamps = true;
  
  public function order_items(){
    return $this->hasMany(FoodDeliveryOrdersItem::class, 'order_id', 'id');
  }

}

?>