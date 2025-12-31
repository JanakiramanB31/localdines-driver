<?php

namespace App;

class Constants
{
  /**
   * Currency symbol used throughout the application
   */
  const CURRENCY_SYMBOL = '£';

  /**
   * Email validation regex pattern
   */
  const EMAIL_REGEX = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

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
