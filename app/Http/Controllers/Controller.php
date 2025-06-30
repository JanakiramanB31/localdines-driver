<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
  /**
   * @OA\OpenApi(
   *     @OA\Info(
   *         title="Food Delivery API",
   *         version="1.0.0",
   *         description="API documentation for the food delivery system."
   *     ),
   *     @OA\Components(
   *         @OA\SecurityScheme(
   *             securityScheme="bearerAuth",
   *             type="http",
   *             scheme="bearer",
   *             bearerFormat="JWT"
   *         )
   *     )
   * )
  */

  public function pr($arrayValues) {
    echo '<pre>';
    print_r($arrayValues);
    echo'</pre>';
  }
}
