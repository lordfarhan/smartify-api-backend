<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserScoreController extends Controller
{
    public static function add($user_id, $code, $referral_id, $amount) {
        $id = DB::table('user_scores')->insertGetId(['user_id' => $user_id, 'code' => $code, 'referral_id' => $referral_id, 'amount' => $amount]);
        if ($id != null) {
            return $amount;
        }
    }

    public function get(Request $request) {
        $user_id = $request->input('user_id');
        return DB::table('user_scores')->where('user_id', $user_id)->get();
    }
}
