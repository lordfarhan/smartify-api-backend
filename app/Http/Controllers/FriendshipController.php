<?php

namespace App\Http\Controllers;

use App\User;
use App\UserDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendshipController extends Controller
{

    public function get(Request $request) {
        $user_id = $request->input('user_id');
        return DB::table('friendships as f1')->select('users.id', 'users.name', 'users.avatar', 'users.email')
            ->where('f1.user_id', $user_id)
            ->join('friendships as f2', function ($join) {
                $join->on('f1.user_id', '=', 'f2.friend_id');
                $join->on('f1.friend_id', '=', 'f2.user_id');
            })
            ->join('users', 'users.id', '=', 'f2.user_id')
            ->get();
    }

    public static function getFriendIds($user_id) {
        return DB::table('friendships as f1')
            ->where('f1.user_id', $user_id)
            ->join('friendships as f2', function ($join) {
                $join->on('f1.user_id', '=', 'f2.friend_id');
                $join->on('f1.friend_id', '=', 'f2.user_id');
            })
            ->join('users', 'users.id', '=', 'f2.user_id')
            ->pluck('users.id');
    }

    public function add(Request $request) {
        $user_id = $request->input('user_id');
        $friend_id = $request->input('friend_id');

        if($user_id == $friend_id) {
            return response()->json([
                'status' => 'error'
            ]);
        } else {
            $existed = DB::table('friendships')->where(['user_id' => $user_id, 'friend_id' => $friend_id])->pluck('id')->first();
            if ($existed == null) {
                $id = DB::table('friendships')->insertGetId(['user_id' => $user_id, 'friend_id' => $friend_id]);
                if($id != null) {
                    return response()->json([
                        'status' => 'ok',
                        'id' => $id
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error'
                    ]);
                }
            }
        }
    }

    public function accept(Request $request) {
        $user_id = $request->input('user_id');
        $friend_id = $request->input('friend_id');
        $id = DB::table('friendships')->where(['user_id' => $friend_id, 'friend_id' => $user_id])->pluck('id')->first();
        if ($id != null) {
            $existed = DB::table('friendships')->where(['user_id' => $user_id, 'friend_id' => $friend_id])->pluck('id')->first();
            if($existed == null) {
                $id = DB::table('friendships')->insertGetId(['user_id' => $user_id, 'friend_id' => $friend_id]);
                if($id != null) {
                    return response()->json([
                        'status' => 'ok',
                        'id' => $id
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error'
                    ]);
                }
            }
        }
    }

    public function reject(Request $request) {
        $user_id = $request->input('user_id');
        $friend_id = $request->input('friend_id');
        try {
            DB::table('friendships')->where(['user_id' => $friend_id, 'friend_id' => $user_id])->delete();
            return response()->json([
                'status' => 'ok'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e
            ]);
        }
    }

    public function requests(Request $request) {
        $user_id = $request->input('user_id');
        return DB::table('friendships')->select('users.id', 'users.name', 'users.avatar', 'users.email')
            ->where('friend_id', $user_id)
            ->leftJoin('user_details', 'user_details.user_id', '=', 'friendships.user_id')
            ->leftJoin('users', 'users.id', '=', 'friendships.user_id')
            ->get();
    }

    public function search(Request $request) {
        $serial_id = $request->input('serial_id');
        $name = $request->input('name');

        if($serial_id != null) {
            return UserDetail::where('serial_id', $serial_id)
                ->select('users.id', 'users.name', 'users.avatar', 'users.email')
                ->rightJoin('users', 'users.id', '=', 'user_details.user_id')
                ->get();
        } else {
            return User::where('name', 'like', '%'.$name.'%')
                ->where('role_id', 4)
                ->select('users.id', 'users.name', 'users.avatar', 'users.email')
                ->leftJoin('user_details', 'user_details.user_id', '=', 'users.id')
                ->get();
        }
    }

    public function users(Request $request) {
        return UserDetail::select('users.id', 'users.name', 'users.avatar', 'users.email')
            ->where('users.role_id', 4)
            ->rightJoin('users', 'users.id', '=', 'user_details.user_id')
            ->orderBy('users.created_at', 'desc')
            ->get();
    }

    public function detail(Request $request) {
        $user_id = $request->input('friend_id');

        $user_detail_id = UserDetail::where('user_id', $user_id)->pluck('id')->first();
        $user_detail = UserDetail::where('id', $user_detail_id)->get();
        
        $user['id'] = $user_detail->pluck('user_id')->first();
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

    public function status(Request $request) {
        $user_id = $request->input('user_id');
        $friend_id = $request->input('friend_id');

        $friend = DB::table('friendships as f1')
            ->where(['f1.user_id' => $user_id, 'f1.friend_id' => $friend_id])
            ->join('friendships as f2', function ($join) {
                $join->on('f1.user_id', '=', 'f2.friend_id');
                $join->on('f1.friend_id', '=', 'f2.user_id');
            })
            ->first();

        if($user_id == $friend_id) {
            return response()->json([
                'status' => 'yourself'
            ]);
        } else if ($friend != null) {
            return response()->json([
                'status' => 'friend'
            ]);
        } else if (DB::table('friendships')->where(['user_id' => $user_id, 'friend_id' => $friend_id])->pluck('id')->first() != null) {
            return response()->json([
                'status' => 'requesting'
            ]);
        } else if (DB::table('friendships')->where(['user_id' => $friend_id, 'friend_id' => $user_id])->pluck('id')->first() != null) {
            return response()->json([
                'status' => 'requested'
            ]);
        } else {
            return response()->json([
                'status' => 'not_requested'
            ]);
        }
    }
}
