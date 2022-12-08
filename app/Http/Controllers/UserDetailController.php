<?php

namespace App\Http\Controllers;

use App\SubDistrict;
use App\User;
use App\UserDetail;
use App\UserHobby;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDetailController extends Controller
{
    public function generate($user_id) {
        $part1 = mt_rand(1000, 9999);
        $part2 = mt_rand(1000, 9999);
        $part3 = mt_rand(1000, 9999);
        $serial_id = $part1 . "-" . $part2 . "-" . $part3;
        $user = UserDetail::where('serial_id', $serial_id)->get()->first();
        if($user == null) {
            $data['id'] = UserDetail::insertGetId(['user_id' =>  $user_id, 'serial_id' => $serial_id]);
            return json_encode($data);
        } else {
            $this->generate($user_id);
        }
    }

    public function get(Request $request) {
        $user_id = $request->input('user_id');

        $user_detail_id = UserDetail::where('user_id', $user_id)->pluck('id')->first();
        $user_detail = UserDetail::where('id', $user_detail_id)->get();
        
        $user['id'] = $user_detail->pluck('id')->first();
        $user['user_id'] = $user_detail->pluck('user_id')->first();
        $user['serial_id'] = $user_detail->pluck('serial_id')->first();
        $user['name'] = User::where('id', $user_id)->pluck('name')->first();
        $user['email'] = User::where('id', $user_id)->pluck('email')->first();
        $user['avatar'] = User::where('id', $user_id)->pluck('avatar')->first();
        $user['about'] = $user_detail->pluck('about')->first();
        $user['phone'] = $user_detail->pluck('phone')->first();
        $user['birth_date'] = $user_detail->pluck('birth_date')->first();
        $user['sex'] = $user_detail->pluck('sex')->first();

	    $user['village'] = DB::table('villages')
            ->where('id', $user_detail->pluck('village_id')->first())
            ->pluck('name')->first();

        $district_id = DB::table('villages')
            ->where('id', $user_detail->pluck('village_id')->first())
            ->pluck('district_id')->first();
        $user['district'] = DB::table('districts')
            ->where('id', $district_id)
            ->pluck('name')->first();

        $regency_id = DB::table('districts')
            ->where('id', $district_id)
            ->pluck('regency_id')->first();
        $user['regency'] = DB::table('regencies')
            ->where('id', $regency_id)
            ->pluck('name')->first();

        $province_id = DB::table('regencies')
            ->where('id', $regency_id)
            ->pluck('province_id')->first();
        $user['province'] = DB::table('provinces')
            ->where('id', $province_id)
            ->pluck('name')->first();

        $user['education_level'] = UserDetail::where('id', $user_detail_id)->pluck('education_level')->first();

        $user['future_goal'] = DB::table('future_goals')
            ->where('id', $user_detail->pluck('future_goal_id')->first())
            ->pluck('name')->first();

        $hobby_ids = DB::table('user_hobbies')->where('user_detail_id', $user_detail_id)->pluck('hobby_id');
        $user['hobbies'] = DB::table('hobbies')->whereIn('id', $hobby_ids)->pluck('name');

        $user['created_at'] = UserDetail::where('id', $user_detail_id)->pluck('created_at')->first();
        $user['updated_at'] = UserDetail::where('id', $user_detail_id)->pluck('updated_at')->first();

        return json_encode($user);
    }

    public function edit(Request $request) {
        $user_id = $request->input('user_id');
        $field = $request->input('field');
        $record = $request->input('record');

        if ($field == 'name' || $field == 'email') {
            try {
                $data['id'] = User::where('id', $user_id)->update([$field => $record]);
                $data['status'] = "ok";
            } catch (Exception $e) {
                $data['status'] = "error";
            }
        } else {
            try {
                $data['id'] = UserDetail::where('user_id', $user_id)->update([$field => $record]);
                $data['status'] = "ok";
            } catch (Exception $e) {
                $data['status'] = "error";
            }
        }
        return json_encode($data);
    }

    public function editHobby(Request $request) {
        $user_id = $request->input('user_id');
        $hobby_string = $request->input('hobby_string');

        $user_detail_id = UserDetail::where('user_id', $user_id)->pluck('id')->first();
        $hobby_ids = explode("-", $hobby_string);
        
        // Delete all existing data if its not added as hobby by user
        DB::table('user_hobbies')->whereNotIn('hobby_id', $hobby_ids)->where('user_detail_id', $user_detail_id)->delete();

        $data = array();
        
        foreach($hobby_ids as $hobby_id) {
            $hobby_existed = DB::table('user_hobbies')->where(['user_detail_id' => $user_detail_id, 'hobby_id' => $hobby_id])->get()->first();
            if ($hobby_existed == null) {
                $hobby = DB::table('user_hobbies')->insertGetId(['user_detail_id' => $user_detail_id, 'hobby_id' => $hobby_id]);
                array_push($data, $hobby);
            }
        }

        return response()->json([
            'status' => 'ok',
            'data' => $data
        ]);

    }

    public function changeAvatar(Request $request) {
        $id = $request->input('user_id');
        $destination_path = '/home/havanah/smartify/smartify-web-app/storage/app/public/users';
        // $destination_path = '/storage/users';
        $image = $request->file('image');
        $user_name = preg_replace('/\s+/', '', User::where('id', $id)->pluck('name')->first());
        $name = $id . "-" . $user_name . '.' . $image->getClientOriginalExtension();
        $image->move($destination_path, $name);
        // $image->move(public_path($destination_path), $name);

        $db_path = 'users/'.$name;
        // $db_path = $destination_path. '/'.$name;
        try {
            $id = User::where('id', $id)->update(['avatar' => $db_path]);
            return response()->json([
                'status' => 'ok',
                'id' => $id
            ]);
        } catch(Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e
            ]);
        }
    }
}
