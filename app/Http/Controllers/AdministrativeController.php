<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministrativeController extends Controller
{
    public function getProvinces(Request $request) {            
        return DB::table('provinces')->select('id', 'name')->get();
    }

    public function getRegencies(Request $request) {
        $province_id = $request->input('province_id');
        return DB::table('regencies')->where('province_id', $province_id)->select('id', 'name')->get();
    }

    public function getDistricts(Request $request) {
        $regency_id = $request->input('regency_id');
        return DB::table('districts')->where('regency_id', $regency_id)->select('id', 'name')->get();
    }

    public function getVillages(Request $request) {
        $district_id = $request->input('district_id');
        return DB::table('villages')->where('district_id', $district_id)->select('id', 'name')->get();
    }

    public function getHobbies() {
        return DB::table('hobbies')->get();
    }

    public function getFutureGoals() {
        return DB::table('future_goals')->get();
    }
}
