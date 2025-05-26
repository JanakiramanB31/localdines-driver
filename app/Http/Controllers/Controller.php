<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
  public function pr($arrayValues) {
    echo '<pre>';
    print_r($arrayValues);
    echo'</pre>';
  }
}
