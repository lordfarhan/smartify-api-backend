<?php

namespace App\Utils;

class RomanNumber {

    public static function romanToDecimal($rom) {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $dec = 0;

        foreach ($map as $key => $value) {
            while (strpos($rom, $key) === 0) {
                $dec += $value;
                $rom = substr($rom, strlen($key));
            }
        }
        return $dec;
    }

    public static function decimalToRoman($dec) {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $rom = '';
        while ($dec > 0) {
            foreach ($map as $key => $decimal) {
                if ($dec >= $decimal) {
                    $dec -= $decimal;
                    $rom .= $key;
                    break;
                }
            }
        }
        return $rom;
    }

}

?>