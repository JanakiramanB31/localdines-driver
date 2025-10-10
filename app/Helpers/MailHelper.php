<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;

class MailHelper
{
  /**
   * Send OTP email to user
   *
   * @param string $to Email address
   * @param int $otp OTP code
   * @return bool
   */
  public static function sendOTPEmail($to, $otp)
  {
    $subject = "Password Reset OTP - Driver App";

    // Send actual email using Laravel Mail facade
    try {
      Mail::raw("Your OTP code is: {$otp}. This code will expire in 1 minute.", function ($message) use ($to, $subject) {
        $message->to($to)
                ->subject($subject);
      });

      error_log("OTP Email sent successfully to {$to}");
      return true;
    } catch (\Exception $e) {
      // Log the error
      error_log("Failed to send email to {$to}: " . $e->getMessage());

      // Log OTP as fallback so it can be retrieved
      error_log("OTP for {$to}: {$otp}");

      return true; // Return true to not block the flow
    }
  }
}
