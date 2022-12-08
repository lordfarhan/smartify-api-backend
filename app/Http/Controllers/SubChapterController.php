<?php

namespace App\Http\Controllers;

use App\ChapterEnrollment;
use App\CourseEnrollment;
use App\SubChapter;
use App\SubChapterEnrollment;
use App\UserExperience;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubChapterController extends Controller
{
    public function getSubChapters(Request $request) {
        $chapter_id = $request->input('chapter_id');
        return SubChapter::select('id', 'chapter_id', 'sub_chapter', 'title')
            ->where('chapter_id', $chapter_id)
            ->get();
    }

    public function getSubChapterStatus(Request $request) {
        $chapter_enrollment_id = $request->input('chapter_enrollment_id');
        $sub_chapter_id = $request->input('sub_chapter_id');

        $data['id'] = SubChapterEnrollment::where(['chapter_enrollment_id' => $chapter_enrollment_id, 'sub_chapter_id' => $sub_chapter_id])
            ->pluck('id')->first();
        $data['status'] = SubChapterEnrollment::where('id', $data['id'])
            ->pluck('status')->first();
        return json_encode($data);
    }

    public function enrollFirstSubChapter($chapter_id, $chapter_enrollment_id) {
        $first_sub_chapter_id = SubChapter::where(['chapter_id' => $chapter_id, 'sub_chapter' => 'A'])->pluck('id')->first();
        $data['sub_chapter_enrollment_id'] = SubChapterEnrollment::insertGetId(['chapter_enrollment_id' => $chapter_enrollment_id, 'sub_chapter_id' => $first_sub_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
        return json_encode($data);
    }

    public function enrollSubChapter(Request $request) {
        $chapter_enrollment_id = $request->input('chapter_enrollment_id');
        $sub_chapter_id = $request->input('sub_chapter_id');

        $chapter_enrollment_id = SubChapterEnrollment::where(['chapter_enrollment_id' => $chapter_enrollment_id, 'sub_chapter_id' => $sub_chapter_id]);
        if($chapter_enrollment_id == null) {
            try {
                $data['chapter_enrollment_id'] = SubChapterEnrollment::insertGetId(['chapter_enrollment_id' => $chapter_enrollment_id, 'sub_chapter_id' => $sub_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
                $data['message'] = 200;
            } catch(QueryException $e) {
                $data['message'] = 500;
            }
        } else {
            $data['message'] = 400;
        }
        return json_encode($data);
    }

    public function finishSubChapter(Request $request) {
        $user_id = $request->input('user_id');
        $chapter_enrollment_id = $request->input('chapter_enrollment_id');
        $sub_chapter_id = $request->input('sub_chapter_id');

        $id = SubChapterEnrollment::where(['chapter_enrollment_id' => $chapter_enrollment_id, 'sub_chapter_id' => $sub_chapter_id])->pluck('id')->first();
        $status = SubChapterEnrollment::where('id', $id)->pluck('status')->first();
        if ($id != null) {
            try {
                // If the sub chapter has been finished, it won't add the points and experiences fully, and won't enroll the next sub chapter
                if($status != '1') {
                    $chapter_enrollment_id = SubChapterEnrollment::where('id', $id)->pluck('chapter_enrollment_id')->first();
                    $sub_chapter_id = SubChapterEnrollment::where('id', $id)->pluck('sub_chapter_id')->first();
    
                    // add points and experience
                    $lesson_page_amount = LessonPageController::getLessonPageAmount($sub_chapter_id);
                    $experience_amount = $lesson_page_amount * 4;
                    $point_amount = $lesson_page_amount * 2;
                    $data['experiences'] = UserExperienceController::add($user_id, 0, $sub_chapter_id, $experience_amount);
                    $data['points'] = UserPointController::add($user_id, 0, $sub_chapter_id, $point_amount);
    
                    $this->enrollNextSubChapter($sub_chapter_id, $chapter_enrollment_id);
                } else {
                    $data['experiences'] = UserExperienceController::add($user_id, 1, $sub_chapter_id, 4);
                    $data['points'] = UserPointController::add($user_id, 3, $sub_chapter_id, 2);
                }
                $data['message'] = 200;
                $data['sub_chapter_enrollment_id'] = SubChapterEnrollment::where('id', $id)->update(['status' => '1']);
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

    public function enrollNextSubChapter($id, $chapter_enrollment_id) {

        $sub_chapter = SubChapter::where('id', $id)->pluck('sub_chapter')->first();
        $chapter_id = SubChapter::where('id', $id)->pluck('chapter_id')->first();

        // get next data
        $next_sub_chapter = ++$sub_chapter;
        $next_sub_chapter_id = SubChapter::where(['sub_chapter' => $next_sub_chapter, 'chapter_id' => $chapter_id])->pluck('id')->first();

        // create new subchapter enrollment
        if ($next_sub_chapter_id != null) {
            $data['new_sub_chapter_enrollment_id'] = SubChapterEnrollment::insertGetId(['chapter_enrollment_id' => $chapter_enrollment_id,'sub_chapter_id' => $next_sub_chapter_id, 'status' => '0', 'created_at' => Carbon::now()->toDateTimeString()]);
            return json_encode($data);
        } else {
            // if no next data, finish current chapter
            $chapter_enrollment = new ChapterController();
            return $chapter_enrollment->finishChapter($chapter_enrollment_id);
        }

        // if (strlen($next_sub_chapter) > 1) {
        //     $next_sub_chapter = $next_sub_chapter[0];
        // }
    }

    // get recent opened
    public function getRecentSubChapter(Request $request) {
        $user_id = $request->input('user_id');
        $course_enrollment_ids = CourseEnrollment::where('user_id', $user_id)->pluck('id');
        $chapter_enrollment_ids = ChapterEnrollment::whereIn('course_enrollment_id', $course_enrollment_ids)->pluck('id');
        $sub_chapter_ids = SubChapterEnrollment::whereIn('chapter_enrollment_id', $chapter_enrollment_ids)->where('status', '1')->pluck('sub_chapter_id');
        return SubChapterEnrollment::select('sub_chapter_enrollments.id as sub_chapter_enrollment_id', 'courses.id as course_id',
         'courses.image as image', 'chapters.id as chapter_id', 'chapters.title as chapter_title',
         'sub_chapters.id as sub_chapter_id', 'sub_chapters.sub_chapter', 'sub_chapters.title as sub_chapter_title', DB::raw('count(sch.id) as sub_chapter_amount'),
         'sub_chapter_enrollments.updated_at')
            ->whereIn('chapter_enrollment_id', $chapter_enrollment_ids)->where('sub_chapter_enrollments.status', '1')
            ->leftJoin('sub_chapters', 'sub_chapter_enrollments.sub_chapter_id', '=', 'sub_chapters.id')
            ->leftJoin('chapters', 'chapters.id', '=', 'sub_chapters.chapter_id')
            ->leftJoin('courses', 'courses.id', '=', 'chapters.course_id')
            ->leftJoin('sub_chapters as sch', 'sch.chapter_id', '=', 'chapters.id')
            ->orderBy('sub_chapter_enrollments.updated_at', 'desc')
            ->groupBy('sub_chapters.id')
            ->limit(10)
            ->get();
    }
}
