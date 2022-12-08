<?php

namespace App\Http\Controllers;

use App\Chapter;
use App\ChapterEnrollment;
use App\Course;
use App\CourseEnrollment;
use App\Grade;
use App\Http\Controllers\Controller;
use App\SubChapterEnrollment;
use App\Subject;
use App\TestChapterEnrollment;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller {
    public function get(Request $request) {
        $grade_id = $request->input('grade_id');
        return Course::select('courses.id', 'grade_id', 'status', 'vendor', 'courses.image')
            ->addSelect(['author' => User::select('name')->whereColumn('id', 'courses.author_id')])
            ->addSelect(['grade' => Grade::select('grade')->whereColumn('id', 'courses.grade_id')])
            ->addSelect(['subject' => Subject::select('subject')->whereColumn('id', 'courses.subject_id')])
            ->where('grade_id', $grade_id)
            ->groupBy('courses.id')
            ->get();
    }

    public function getStatus(Request $request) {
        $user_id = $request->input('user_id');
        $course_id = $request->input('course_id');
        $data['course_enrollment_id'] = CourseEnrollment::where(['user_id' => $user_id, 'course_id' => $course_id])->pluck('id')->first();
        
        $chapter_enrollment_ids = ChapterEnrollment::where('course_enrollment_id', $data['course_enrollment_id'])->pluck('id');
        $chapter_ids = Chapter::where('course_id', $course_id)->pluck('id');

        $data['chapter_amount'] = Chapter::where('course_id', $course_id)->count();
        $data['test_amount'] = DB::table('test_chapters')->whereIn('chapter_id', $chapter_ids)->count();

        $data['chapter_finished'] = ChapterEnrollment::where(['course_enrollment_id' => $data['course_enrollment_id'], 'status' => '1'])->count();
        $data['test_finished'] = TestChapterEnrollment::whereIn('chapter_enrollment_id', $chapter_enrollment_ids)->where('status', '1')->count();

        return json_encode($data);
    }

    public function enroll(Request $request) {
        $user_id = $request->input('user_id');
        $course_id = $request->input('course_id');

        $course_enrollment_id = CourseEnrollment::where(['user_id' => $user_id, 'course_id' => $course_id])->pluck('id')->first();
        if ($course_enrollment_id == null) {
            try {
                $data['course_enrollment_id'] = CourseEnrollment::insertGetId(['user_id' => $user_id, 'course_id' => $course_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
                $chapter = new ChapterController();
                $chapter->enrollFirstChapter($course_id, $data['course_enrollment_id']);
                $data['message'] = 200;
            } catch(QueryException $e) {
                $data['message'] = 500;
            }
        } else {
            $data['message'] = 400;
        }
        return json_encode($data);
    }

    public function finishCourse($id) {
        try {
            $data['course_enrollment_id'] = CourseEnrollment::where('id', $id)->update('status', '1');
            $data['message'] = 200;
        } catch(QueryException $e) {
            $data['message'] = 500;
        }
        return json_encode($data);
    }
}