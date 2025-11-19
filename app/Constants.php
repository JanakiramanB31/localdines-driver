<?php

namespace App;

class Constants
{
  /**
   * Currency symbol used throughout the application
   */
  const CURRENCY_SYMBOL = '£';

  /**
   * Duty status constants as object
   */
  const DUTY_STATUS = [
    'ON' => 'on',
    'OFF' => 'off',
    'ONLINE' => 'online',
    'OFFLINE' => 'offline'
  ];
}
