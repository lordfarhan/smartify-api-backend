<?php

namespace App\Http\Controllers;

use App\LessonExercise;
use App\LessonPage;
use Illuminate\Http\Request;

class LessonPageController extends Controller
{
    public function getLessonPages(Request $request) {
        $sub_chapter_id = $request->input('sub_chapter_id');
        $lesson_exercises = LessonExercise::select("id", "sub_chapter_id", "page_type", "page_order")
            ->where('sub_chapter_id', $sub_chapter_id);
        return LessonPage::select("id", "sub_chapter_id", "page_type", "page_order")
            ->union($lesson_exercises)
            ->where('sub_chapter_id', $sub_chapter_id)
            ->orderBy('page_order')
            ->get();
    }

    public static function getLessonPageAmount($sub_chapter_id) {
        $lesson_exercises = LessonExercise::select("*")
            ->where('sub_chapter_id', $sub_chapter_id)
            ->get();
        $lesson_page = LessonPage::select("*")
            ->where('sub_chapter_id', $sub_chapter_id)
            ->get();

        return $lesson_exercises->count() + $lesson_page->count();
    }
}
