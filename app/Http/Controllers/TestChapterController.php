<?php

namespace App\Http\Controllers;

use App\Chapter;
use App\ChapterEnrollment;
use App\Course;
use App\CourseEnrollment;
use App\Subject;
use App\TestChapterEnrollment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestChapterController extends Controller
{
    public function get(Request $request) {
        $course_id = $request->input('course_id');
        $subject_id = Course::where('id', $course_id)->pluck('subject_id')->first();

        $chapter_ids = Chapter::where('course_id', $course_id)->pluck('id');
        return DB::table('test_chapters')
            ->select('test_chapters.id', 'test_chapters.chapter_id', 'test_chapters.type', 'test_chapters.description', 'test_chapters.image', 'chapters.course_id', 'chapters.chapter', 'chapters.title', 'subjects.subject', 'grades.grade')
            ->leftJoin('chapters', 'chapters.id', '=', 'test_chapters.chapter_id')
            ->leftJoin('courses', 'courses.id', '=', 'chapters.course_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'courses.subject_id')
            ->leftJoin('grades', 'grades.id', '=', 'courses.grade_id')
            ->whereIn('chapter_id', $chapter_ids)
            ->get();
    }

    /**
     * A function to get test status
     * @param $user_id, $course_id, $test_chapter_id
     * @return $id, $status
     */
    public function getStatus(Request $request) {
        // Needed variable
        $user_id = $request->input('user_id');
        $course_id = $request->input('course_id');
        $test_chapter_id = $request->input('test_chapter_id');

        // Processing to get data
        // First phase
        $course_enrollment_id = CourseEnrollment::where(['user_id' => $user_id, 'course_id' => $course_id])->pluck('id')->first();
        $chapter_id = DB::table('test_chapters')->where('id', $test_chapter_id)->pluck('chapter_id')->first();

        // Second phase
        $chapter_enrollment_id = ChapterEnrollment::where(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $chapter_id])->pluck('id')->first();
        
        // Final phase
        $data['id'] = TestChapterEnrollment::where(['chapter_enrollment_id' => $chapter_enrollment_id, 'test_chapter_id' => $test_chapter_id])
            ->pluck('id')->first();
        $data['status'] = TestChapterEnrollment::where('id', $data['id'])
            ->pluck('status')->first();
        return json_encode($data);
    }

    /**
     * A function to enroll a test
     * @param $chapter_enrollment_id, $test_chapter_id
     * @return $chapter_enrollment_id, $message
     */
    public static function enroll($chapter_id, $chapter_enrollment_id) {
        $test_chapter_ids = DB::table('test_chapters')->where('chapter_id', $chapter_id)->pluck('id');
        foreach($test_chapter_ids as $test_chapter_id) {
            try {
                $data['id'] = TestChapterEnrollment::insertGetId(['chapter_enrollment_id' => $chapter_enrollment_id, 'test_chapter_id' => $test_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
                $data['message'] = 200;
            } catch(QueryException $e) {
                $data['message'] = 500;
            }
        }
        return json_encode($data);
    }

    /**
     * A function to finish a test
     * @param $user_id
     */

    public function finish(Request $request) {
        $user_id = $request->input('user_id');
        $course_id = $request->input('course_id');
        $chapter_id = $request->input('chapter_id');
        $test_chapter_id = $request->input('test_chapter_id');
        $right_answer_amount = $request->input('right_answer_amount');

        $course_enrollment_id = CourseEnrollment::where(['course_id' => $course_id, 'user_id' => $user_id])->pluck('id')->first();
        $chapter_enrollment_id = ChapterEnrollment::where(['chapter_id' => $chapter_id, 'course_enrollment_id' => $course_enrollment_id])->pluck('id')->first();

        $id = TestChapterEnrollment::where(['chapter_enrollment_id' => $chapter_enrollment_id, 'test_chapter_id' => $test_chapter_id])->pluck('id')->first();
        $status = TestChapterEnrollment::where('id', $id)->pluck('status')->first();
        if ($id != null) {
            try {
                // If the sub chapter has been finished, it won't add the points and experiences, and also won't enroll the next sub chapter
                if($status != '1') {
                    // add points and experience
                    $wrong_answer_amount = 25 - $right_answer_amount;
                    $subject_id = Course::where('id', $course_id)->pluck('subject_id')->first();
    
                    $experience_amount = 5 * 25;
                    $right_point_amount = $right_answer_amount * 5;
                    $wrong_point_amount = $wrong_answer_amount * 1;
                    $point_amount = $right_point_amount + $wrong_point_amount;
                    $score_amount = $right_answer_amount * 4;
    
                    $data['experiences'] = UserExperienceController::add($user_id, 2, $test_chapter_id, $experience_amount);
                    $data['points'] = UserPointController::add($user_id, 4, $test_chapter_id, $point_amount);
                    $data['scores'] = UserScoreController::add($user_id, 0, $subject_id, $score_amount);
                } else {
                    $data['experiences'] = null;
                    $data['points'] = null;
                }
                $data['message'] = 200;
                $data['sub_chapter_enrollment_id'] = TestChapterEnrollment::where('id', $id)->update(['status' => '1']);
            } catch (QueryException $e) {
                $data['message'] = 500;
                $data['error_message'] = $e;
            }
        } else {
            $data['message'] = 500;
            $data['error_message'] = 'data unavailable';
        }
        return json_encode($data);
    }

    /**
     * A function to retrieve test chapter questions
     * @param $test_chapter_id
     */
    public function getQuestions(Request $request) {
        $test_chapter_id = $request->input('test_chapter_id');

        $results = array();
        $question_ids = DB::table('test_chapter_questions')->where('test_chapter_id', $test_chapter_id)->pluck('id');    
        if ($question_ids->count() < 25) {
            return response()->json([
                'response_code' => 0,
                'results' => null
            ]);
        } else {
            foreach($question_ids as $question_id) {
                $data['id'] = DB::table('test_chapter_questions')->where(['id' => $question_id])->pluck('id')->first();
                $data['test_chapter_id'] = $test_chapter_id;
                $data['type'] = DB::table('test_chapter_questions')->where(['id' => $question_id])->pluck('type')->first();
                $data['question'] = DB::table('test_chapter_questions')->where(['id' => $question_id])->pluck('question')->first();
                $data['correct_answer'] = DB::table('test_chapter_questions')->where(['id' => $question_id])->pluck('correct_answer')->first();
                $incorrect_answers = DB::table('test_chapter_questions')->where(['id' => $question_id])->pluck('incorrect_answers')->first();
                $data['incorrect_answers'] = explode("; ", ltrim($incorrect_answers));
                array_push($results, $data);
            }
            return response()->json([
                'response_code' => 1,
                'results' => $results
            ]);
        }
    }
}
