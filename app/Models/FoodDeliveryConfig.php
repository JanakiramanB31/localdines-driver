<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryConfig extends Model
{
  protected $table = 'food_delivery_configs';

  protected $fillable = [
    'key',
    'value',
    'type',
    'is_active'
  ];

  /**
   * Get config value by key
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public static function getValue($key, $default = null)
  {
    $config = self::where('key', $key)->where('is_active', 1)->first();
    return $config ? $config->value : $default;
  }
}
