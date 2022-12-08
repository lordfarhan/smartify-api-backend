<?php

namespace App\Http\Controllers;

use App\LessonExercise;
use App\UserPoint;
use Illuminate\Http\Request;

class LessonExerciseController extends Controller
{
    public function getLessonExercise(Request $request) {
        $id = $request->input('lesson_page_id');
        return LessonExercise::where('id', $id)->get()->first();
    }

    public function getReward(Request $request) {
        $user_id = $request->input('user_id');
        $status = $request->input('status');
        $lesson_exercise_id = $request->input('lesson_exercise_id');
        
        $point = UserPoint::where(['user_id' => $user_id, 'code' => 1, 'referral_id' => $lesson_exercise_id])->pluck('id')->first();

        if ($point == null) {
            if ($status == 1) {
                $data['point'] = UserPointController::add($user_id, 1, $lesson_exercise_id, 4);
            } else {
                $data['point'] = UserPointController::add($user_id, 1, $lesson_exercise_id, 2);
            }
        } else {
            $data['point'] = null;
        }
        return json_encode($data);
    }
}
