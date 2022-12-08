<?php

use App\Http\Controllers\FeedPostController;
use Dingo\Api\Routing\Router;
use Illuminate\Support\Facades\Route;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\Auth\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\Auth\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\Auth\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\Auth\\ResetPasswordController@resetPassword');

        $api->post('logout', 'App\\Api\\V1\\Controllers\\Auth\\LogoutController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\Auth\\RefreshController@refresh');
        $api->get('me', 'App\\Api\\V1\\Controllers\\Auth\\UserController@me');
        $api->post('detail', 'App\\Http\\Controllers\\UserDetailController@get');
        $api->post('edit', 'App\\Http\\Controllers\\UserDetailController@edit');
        $api->post('edit-avatar', 'App\\Http\\Controllers\\UserDetailController@changeAvatar');
        $api->post('edit-hobby','App\\Http\\Controllers\\UserDetailController@editHobby');
        $api->post('check', 'App\\Api\\V1\\Controllers\\Auth\\SignUpController@check');
    });

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('protected', function() {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('hello', function() {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });
});

Route::prefix('friends')->group(function () {
    Route::post('add', 'FriendshipController@add');
    Route::post('accept', 'FriendshipController@accept');
    Route::post('reject', 'FriendshipController@reject');
    Route::post('get', 'FriendshipController@get');
    Route::post('requests', 'FriendshipController@requests');
    Route::post('search', 'FriendshipController@search'); // get searched user
    Route::get('all', 'FriendshipController@users'); // get user list
    Route::post('detail', 'FriendshipController@detail');
    Route::post('status', 'FriendshipController@status');
});

Route::prefix('administrative')->group(function () {
    Route::prefix('area')->group(function () {
        Route::post('provinces', 'AdministrativeController@getProvinces');
        Route::post('regencies', 'AdministrativeController@getRegencies');
        Route::post('districts', 'AdministrativeController@getDistricts');
        Route::post('villages', 'AdministrativeController@getVillages');
    });
    Route::prefix('personality')->group(function () {
        Route::get('hobbies', 'AdministrativeController@getHobbies');
        Route::get('future-goals', 'AdministrativeController@getFutureGoals');
    });
});

Route::post('/courses', 'CourseController@get');
Route::post('/course-status', 'CourseController@getStatus');
Route::post('/enroll-course', 'CourseController@enroll');

Route::post('/chapters', 'ChapterController@get');
Route::post('/chapter-status', 'ChapterController@getStatus');

Route::post('/test-chapters', 'TestChapterController@get');
Route::post('/test-chapter-status', 'TestChapterController@getStatus');
Route::post('/test-chapter-questions', 'TestChapterController@getQuestions');
Route::post('/finish-test-chapter', 'TestChapterController@finish');

Route::post('/sub-chapters', 'SubChapterController@getSubChapters');
Route::post('/sub-chapter-status', 'SubChapterController@getSubChapterStatus');
Route::post('/finish-sub-chapter', 'SubChapterController@finishSubChapter');
Route::post('/recent-sub-chapters', 'SubChapterController@getRecentSubChapter');

Route::post('/lesson-pages', 'LessonPageController@getLessonPages');
Route::post('/lesson-exercise', 'LessonExerciseController@getLessonExercise');
Route::post('/lesson-exercise-reward', 'LessonExerciseController@getReward');
Route::post('/lesson-contents', 'LessonContentController@getLessonContents');

Route::post('/get-scope-data', 'ScopeController@getScopeData');
Route::post('/get-points', 'UserPointController@get');
Route::post('/get-experiences', 'UserExperienceController@get');

Route::get('/blog-posts', 'BlogPostController@getBlogPosts');

Route::post('/my-feed-posts', 'FeedPostController@getMine');
Route::post('/feed-posts', 'FeedPostController@get');
Route::post('/create-feed', 'FeedPostController@create');
Route::post('/delete-feed', 'FeedPostController@delete');
Route::post('/edit-feed', 'FeedPostController@edit');
Route::post('/like-feed', 'FeedPostController@like');
Route::post('/unlike-feed', 'FeedPostController@unlike');
Route::post('/is-liked-feed', 'FeedPostController@isLiked');

Route::post('/feed-comments', 'FeedPostController@getComments');
Route::post('/comment-feed', 'FeedPostController@comment');
Route::post('/like-comment-feed', 'FeedPostController@likeComment');
Route::post('/unlike-comment-feed', 'FeedPostController@unlikeComment');
Route::post('/is-liked-comment-feed', 'FeedPostController@isLikedComment');

Route::post('feed-comment-replies', 'FeedPostController@getCommentReplies');
Route::post('/reply-comment-feed', 'FeedPostController@replyComment');
Route::post('/like-comment-reply-feed', 'FeedPostController@likeCommentReply');
Route::post('/unlike-comment-reply-feed', 'FeedPostController@unlikeCommentReply');
Route::post('/is-liked-comment-reply-feed', 'FeedPostController@isLikedCommentReply');