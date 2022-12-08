<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogPostController extends Controller
{
    public function getBlogPosts(Request $request) {
        return DB::table('blog_posts')
            ->addSelect(['author' => User::select('name')->whereColumn('id', 'blog_posts.author_id')])
            ->get();
    }
}
