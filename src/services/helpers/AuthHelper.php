<?php

namespace App\Services\Helpers;

class AuthHelper
{
  private const SEQ_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  public static function generate_random_seq($seq_count = 10): string
  {
    $random_seq = '';
    for ($i = 0; $i < $seq_count; $i++) {
      $random_seq .= self::SEQ_CHARS[rand(0, strlen(self::SEQ_CHARS) - 1)];
    }
    return $random_seq;
  }
}
