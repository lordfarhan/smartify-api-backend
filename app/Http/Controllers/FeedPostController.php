<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FeedPostController extends Controller
{

    /**
     * get feed list
     * @param $user_id
     */
    public function get(Request $request) {
        $user_id = $request->input('user_id');

        $all_ids = array();
        array_push($all_ids, (int) $user_id);
        $friend_ids = FriendshipController::getFriendIds($user_id);
        foreach($friend_ids as $friend_id){
            array_push($all_ids, $friend_id);
        }
        $status = '';
        $posts = DB::table('feed_posts')->whereIn('feed_posts.user_id', $all_ids)->select('feed_posts.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_posts.image', 'feed_posts.caption', 'feed_posts.created_at', 'feed_posts.updated_at')
            ->addSelect(['likes' => DB::table('feed_likes')->select(DB::raw('count(feed_likes.id)'))->whereColumn('feed_post_id', 'feed_posts.id')])
            ->addSelect(['comments' => DB::table('feed_comments')->select(DB::raw('count(feed_comments.id)'))->whereColumn('feed_post_id', 'feed_posts.id')])
            ->leftJoin('users', 'users.id', '=', 'feed_posts.user_id')
            ->where('feed_posts.status', 'posted')
            ->groupBy('feed_posts.id')
            ->orderBy('feed_posts.created_at', 'desc')
            ->get();
        if ($friend_ids->count() == 0) {
            $status = 'no_friend';
        } else if ($posts->count() == 0) {
            $status = 'no_post';
        } else {
            $status = 'ok';
        }
        return response()->json([
            'status' => $status,
            'data' =>  $posts
        ]);
    }

    /**
     * Get feed by user only
     */
    public function getMine(Request $request) {
        $user_id = $request->input('user_id');

        $status = '';
        $posts = DB::table('feed_posts')->where('feed_posts.user_id', $user_id)->select('feed_posts.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_posts.image', 'feed_posts.caption', 'feed_posts.created_at', 'feed_posts.updated_at')
            ->addSelect(['likes' => DB::table('feed_likes')->select(DB::raw('count(feed_likes.id)'))->whereColumn('feed_post_id', 'feed_posts.id')])
            ->addSelect(['comments' => DB::table('feed_comments')->select(DB::raw('count(feed_comments.id)'))->whereColumn('feed_post_id', 'feed_posts.id')])
            ->leftJoin('users', 'users.id', '=', 'feed_posts.user_id')
            ->where('feed_posts.status', 'posted')
            ->groupBy('feed_posts.id')
            ->orderBy('feed_posts.created_at', 'desc')
            ->get();
        if ($posts->count() == 0) {
            $status = 'no_post';
        } else {
            $status = 'ok';
        }
        return response()->json([
            'status' => $status,
            'data' =>  $posts
        ]);
    }

    /**
     * create feed
     */
    public function create(Request $request) {
        $user_id = $request->input('user_id');
        $caption = $request->input('caption');
        $image = $request->file('image');

        // $destination_path = '/home/havanah/smartify/smartify-web-app/storage/app/public/feed_posts/' . Carbon::now()->format('FY');
        $destination_path = '/storage/feed_posts/' . Carbon::now()->format('FY');

        if ($image != null) {
            $name = $user_id . "-" . Carbon::now()->format('YmdHis') . '.' . $image->getClientOriginalExtension();
            // $image->move($destination_path, $name);
            $image->move(public_path($destination_path), $name);
            // $db_path = 'users/'.$name;
            $db_path = $destination_path. '/'.$name;
            try {
                $id = DB::table('feed_posts')->insertGetId(['user_id' => $user_id, 'caption' => $caption, 'image' => $db_path, 'created_at' => Carbon::now()]);
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
        } else {
            try {
                $id = DB::table('feed_posts')->insertGetId(['user_id' => $user_id, 'caption' => $caption]);
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

    /**
     * Delete feed
     */
    public function delete(Request $request) {
        $id = $request->input('feed_post_id');
        // $image = DB::table('feed_posts')->where('id', $id)->pluck('image')->first();
        try {
            DB::table('feed_posts')->where('id', $id)->update(['status' => 'deleted']);
            // File::delete(public_path($image));
            return response()->json([
                'status' => 'ok',
            ]);
        } catch(Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e
            ]);
        }
    }

    /**
     * Update feed
     */
    public function edit(Request $request) {
        $id = $request->input('feed_post_id');
        $caption = $request->input('caption');

        try {
            $id = DB::table('feed_posts')->where('id', $id)->update(['caption' => $caption]);
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

    /**
     * Give like a feed
     */
    public function like(Request $request) {
        $feed_post_id = $request->input('feed_post_id');
        $user_id = $request->input('user_id');

        $data = DB::table('feed_likes')->where(['feed_post_id' => $feed_post_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data == null) {
            try {
                $id = DB::table('feed_likes')->insertGetId(['feed_post_id' => $feed_post_id, 'user_id' => $user_id, 'created_at' => Carbon::now()]);
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

    /**
     * Unlike a feed
     */
    public function unlike(Request $request) {
        $feed_post_id = $request->input('feed_post_id');
        $user_id = $request->input('user_id');

        $data = DB::table('feed_likes')->where(['feed_post_id' => $feed_post_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data != null) {
            try {
                $id = DB::table('feed_likes')->where(['feed_post_id' => $feed_post_id, 'user_id' => $user_id])->delete();
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

    /**
     * Check is feed liked
     */
    public function isLiked(Request $request) {
        $feed_post_id = $request->input('feed_post_id');
        $user_id = $request->input('user_id');

        $data = DB::table('feed_likes')->where(['feed_post_id' => $feed_post_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data != null) {
            return response()->json([
                'status' => 'liked'
            ]);
        } else {
            return response()->json([
                'status' => 'not_liked'
            ]);
        }
    }

    /**
     * Get comments
     */
    public function getComments(Request $request) {
        $feed_post_id = $request->input('feed_post_id');
        $order_by = $request->input('order_by');

        if($order_by == null) {
            $order_by = 'feed_comments.created_at';
        } else if($order_by == 'likes') {
            $order_by = 'likes';
        }

        $comments = DB::table('feed_comments')->where('feed_post_id', $feed_post_id)->select('feed_comments.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_comments.comment', 'feed_comments.created_at', 'feed_comments.updated_at')
            ->addSelect(['likes' => DB::table('feed_comment_likes')->select(DB::raw('count(feed_comment_likes.id)'))->whereColumn('feed_comment_id', 'feed_comments.id')])
            ->addSelect(['replies' => DB::table('feed_comment_replies')->select(DB::raw('count(feed_comment_replies.id)'))->whereColumn('feed_comment_id', 'feed_comments.id')])
            ->leftJoin('users', 'users.id', '=', 'feed_comments.user_id')
            ->where('feed_comments.status', 'posted')
            ->groupBy('feed_comments.id')
            ->orderBy($order_by, 'desc')
            ->orderBy('replies', 'desc')
            ->get();
        return response()->json([
            'status' => 'ok',
            'data' => $comments
        ]);
    }

    /**
     * Do comment
     */
    public function comment(Request $request) {
        $user_id = $request->input('user_id');
        $feed_post_id = $request->input('feed_post_id');
        $comment = $request->input('comment');

        try {
            $id = DB::table('feed_comments')->insertGetId(['user_id' => $user_id, 'feed_post_id' => $feed_post_id, 'comment' => $comment, 'created_at' => Carbon::now()]);
            $data = DB::table('feed_comments')->where('feed_comments.id', $id)->select('feed_comments.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_comments.comment', 'feed_comments.created_at', 'feed_comments.updated_at')
                ->addSelect(['likes' => DB::table('feed_comment_likes')->select(DB::raw('count(feed_comment_likes.id)'))->whereColumn('feed_comment_id', 'feed_comments.id')])
                ->addSelect(['replies' => DB::table('feed_comment_replies')->select(DB::raw('count(feed_comment_replies.id)'))->whereColumn('feed_comment_id', 'feed_comments.id')])    
                ->leftJoin('users', 'users.id', '=', 'feed_comments.user_id')
                ->where('status', 'posted')
                ->groupBy('feed_comments.id')
                ->get();
            return response()->json([
                'status' => 'ok',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e
            ]);
        }
    }

    /**
     * Like a comment
     */
    public function likeComment(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_id = $request->input('feed_comment_id');

        $data = DB::table('feed_comment_likes')->where(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data == null) {
            try {
                $id = DB::table('feed_comment_likes')->insertGetId(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id, 'created_at' => Carbon::now()]);
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

    /**
     * unlikeComment
     */
    public function unlikeComment(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_id = $request->input('feed_comment_id');

        $data = DB::table('feed_comment_likes')->where(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data != null) {
            try {
                $id = DB::table('feed_comment_likes')->where(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id])->delete();
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

    /**
     * Is liked comment
     */
    public function isLikedComment(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_id = $request->input('feed_comment_id');

        $data = DB::table('feed_comment_likes')->where(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id])->pluck('id')->first();
        if ($data != null) {
            return response()->json([
                'status' => 'liked'
            ]);
        } else {
            return response()->json([
                'status' => 'not_liked'
            ]);
        }
    }

    /**
     * Get comment replies
     */
    public function getCommentReplies(Request $request) {
        $feed_comment_id = $request->input('feed_comment_id');

        $replies = DB::table('feed_comment_replies')->where('feed_comment_id', $feed_comment_id)->select('feed_comment_replies.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_comment_replies.reply', 
        DB::raw('count(feed_comment_reply_likes.id) as likes'), 'feed_comment_replies.created_at', 'feed_comment_replies.updated_at')
            ->leftJoin('users', 'users.id', '=', 'feed_comment_replies.user_id')
            ->leftJoin('feed_comment_reply_likes', 'feed_comment_reply_likes.feed_comment_reply_id', '=', 'feed_comment_replies.id')
            ->where('status', 'posted')
            ->groupBy('feed_comment_replies.id')
            ->orderBy('feed_comment_replies.created_at', 'desc')
            ->get();
        return response()->json([
            'status' => 'ok',
            'data' => $replies
        ]);
    }

    /**
     * Reply a comment
     */
    public function replyComment(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_id = $request->input('feed_comment_id');
        $reply = $request->input('reply');

        try {
            $id = DB::table('feed_comment_replies')->insertGetId(['feed_comment_id' => $feed_comment_id, 'user_id' => $user_id, 'reply' => $reply, 'created_at' => Carbon::now()]);
            $data = DB::table('feed_comment_replies')->where('feed_comment_replies.id', $id)->select('feed_comment_replies.id', 'users.id as user_id', 'users.avatar', 'users.name', 'feed_comment_replies.reply', 
                DB::raw('count(feed_comment_reply_likes.id) as likes'), 'feed_comment_replies.created_at', 'feed_comment_replies.updated_at')
                    ->leftJoin('users', 'users.id', '=', 'feed_comment_replies.user_id')
                    ->leftJoin('feed_comment_reply_likes', 'feed_comment_reply_likes.feed_comment_reply_id', '=', 'feed_comment_replies.id')
                    ->where('status', 'posted')
                    ->groupBy('feed_comment_replies.id')
                    ->get();
            return response()->json([
                'status' => 'ok',
                'data' => $data
            ]);
        } catch(Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e
            ]);
        }
    }

    /**
     * Like comment reply
     */
    public function likeCommentReply(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_reply_id = $request->input('feed_comment_reply_id');

        $data = DB::table('feed_comment_reply_likes')->where(['user_id' => $user_id, 'feed_comment_reply_id' => $feed_comment_reply_id])->pluck('id')->first();
        if($data == null) {
            try {
                $id = DB::table('feed_comment_reply_likes')->insertGetId(['user_id' => $user_id, 'feed_comment_reply_id' => $feed_comment_reply_id, 'created_at' => Carbon::now()]);
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

    /**
     * Unlike comment reply
     */
    public function unlikeCommentReply(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_reply_id = $request->input('feed_comment_reply_id');

        $data = DB::table('feed_comment_reply_likes')->where(['user_id' => $user_id, 'feed_comment_reply_id' => $feed_comment_reply_id])->pluck('id')->first();
        if ($data != null) {
            try {
                $id = DB::table('feed_comment_likes')->where(['feed_comment_reply_id' => $feed_comment_reply_id, 'user_id' => $user_id])->delete();
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

    /**
     * isLiked reply comment
     */
    public function isLikedCommentReply(Request $request) {
        $user_id = $request->input('user_id');
        $feed_comment_reply_id = $request->input('feed_comment_reply_id');

        $data = DB::table('feed_comment_reply_likes')->where(['user_id' => $user_id, 'feed_comment_reply_id' => $feed_comment_reply_id])->pluck('id')->first();
        if ($data != null) {
            return response()->json([
                'status' => 'liked'
            ]);
        } else {
            return response()->json([
                'status' => 'not_liked'
            ]);
        }
    }
}