<?php

namespace App\Http\Controllers;

use App\UserExperience;
use App\UserPoint;
use Illuminate\Http\Request;

class ScopeController extends Controller
{
    public function getScopeData(Request $request) {
        $user_id = $request->input('user_id');
        $data['user_points'] = UserPoint::where('user_id', $user_id)->sum('amount');
        $data['user_experiences'] = UserExperience::where('user_id', $user_id)->sum('amount');
        return json_encode($data);
    }
}
