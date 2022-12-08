<?php

namespace App\Http\Controllers;

use App\Chapter;
use App\ChapterEnrollment;
use App\SubChapter;
use App\SubChapterEnrollment;
use App\Utils\RomanNumber;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChapterController extends Controller
{
    public function get(Request $request) {
        $course_id = $request->input('course_id');
        return Chapter::select('chapters.id', 'course_id', 'chapter', 'chapters.title', 'image', 
            DB::raw('count(sub_chapters.id) as sub_chapter_amount'))
            ->where('course_id', $course_id)
            ->join('sub_chapters', 'chapters.id', '=', 'sub_chapters.chapter_id', 'left outer')
            ->groupBy('chapters.id')
            ->get();
    }
    
    public function getStatus(Request $request) {
        $course_enrollment_id = $request->input('course_enrollment_id');
        $chapter_id = $request->input('chapter_id');

        $data['id'] = ChapterEnrollment::where(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $chapter_id])
            ->pluck('id')->first();
        $data['status'] = ChapterEnrollment::where('id', $data['id'])
            ->pluck('status')->first();
        $data['sub_chapter_finished'] = SubChapterEnrollment::where(['chapter_enrollment_id' => $data['id'], 'status' => '1'])
            ->count();
        return json_encode($data);
    }

    public function enrollFirstChapter($course_id, $course_enrollment_id) {
        $first_chapter_id = Chapter::where(['course_id' => $course_id, 'chapter' => 'I'])->pluck('id')->first();
        $data['chapter_enrollment_id'] = ChapterEnrollment::insertGetId(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $first_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
        $sub_chapter = new SubChapterController();
        $sub_chapter->enrollFirstSubChapter($first_chapter_id, $data['chapter_enrollment_id']);
        return json_encode($data);
    }

    public function enrollChapter(Request $request) {
        $course_enrollment_id = $request->input('course_enrollment_id');
        $chapter_id = $request->input('chapter_id');

        $chapter_enrollment_id = ChapterEnrollment::where(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $chapter_id]);
        if($chapter_enrollment_id == null) {
            try {
                $data['chapter_enrollment_id'] = ChapterEnrollment::insertGetId(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
                $data['message'] = 200;
            } catch(QueryException $e) {
                $data['message'] = 500;
            }
        } else {
            $data['message'] = 400;
        }
        return json_encode($data);
    }

    public function finishChapter($id) {
        $status = ChapterEnrollment::where('id', $id)->pluck('status')->first();
        try {
            if($status != '1') {
                $course_enrollment_id = ChapterEnrollment::where('id', $id)->pluck('course_enrollment_id')->first();
                $chapter_id = ChapterEnrollment::where('id', $id)->pluck('chapter_id')->first();
                $this->enrollNextChapter($chapter_id, $course_enrollment_id);
                TestChapterController::enroll($chapter_id, $id);
            }
            $data['message'] = 200;
            $data['chapter_enrollment_id'] = ChapterEnrollment::where('id', $id)->update(['status' => '1']);
        } catch (QueryException $e) {
            $data['message'] = 500;
        }
        return json_encode($data);
    }

    public function enrollNextChapter($id, $course_enrollment_id) {

        // get chapter and course id from table chapters
        $chapter = Chapter::where('id', $id)->pluck('chapter')->first();
        $course_id = Chapter::where('id', $id)->pluck('course_id')->first();

        // get next data
        $chapter_decimal = RomanNumber::romanToDecimal($chapter); // decimal
        $next_chapter_decimal = ++$chapter_decimal;
        $next_chapter = RomanNumber::decimalToRoman($next_chapter_decimal); // roman
        $next_chapter_id = Chapter::where(['chapter' => $next_chapter, 'course_id' => $course_id])->pluck('id')->first();

        // create new chapter enrollment
        if($next_chapter_id != null) {
            $data['new_chapter_enrollment_id'] = ChapterEnrollment::insertGetId(['course_enrollment_id' => $course_enrollment_id, 'chapter_id' => $next_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
            $sub_chapter = new SubChapterController();
            $sub_chapter->enrollFirstSubChapter($next_chapter_id, $data['new_chapter_enrollment_id']);
            return json_encode($data);
        } else {
            // if no next data, finish current course
            $course = new CourseController();
            return $course->finishCourse($course_enrollment_id);
        }
    }
}
