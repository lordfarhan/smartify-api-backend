<?php

namespace App\Http\Controllers;

use App\UserExperience;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserExperienceController extends Controller
{
    public static function add($user_id, $code, $referral_id, $amount) {
        $id = UserExperience::insertGetId(['user_id' => $user_id, 'code' => $code, 'referral_id' => $referral_id, 'amount' => $amount]);
        if ($id != null) {
            return $amount;
        }
    }

    public function get(Request $request) {
        $user_id = $request->input('user_id');
        return UserExperience::where('user_id', $user_id)->get();
    }
}
