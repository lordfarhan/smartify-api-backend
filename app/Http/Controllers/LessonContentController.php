<?php

namespace App\Http\Controllers;

use App\LessonContent;
use Illuminate\Http\Request;

class LessonContentController extends Controller
{
    public function getLessonContents(Request $request) {
        $lesson_page_id = $request->input('lesson_page_id');
        return LessonContent::where('lesson_page_id', $lesson_page_id)->get();
    }
}
