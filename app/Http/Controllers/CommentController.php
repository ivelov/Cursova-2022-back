<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Return comments info at selected page and report
     *
     * @return Object
     */
    public function index($repId, $page)
    {
        if (!Auth::user()) {
            abort(403);
        }

        $comments = Comment::join('users', 'users.id', '=', 'comments.user_id')
            ->where('comments.report_id', $repId)
            ->orderBy('comments.created_at', 'asc')
            ->paginate(5, [
                'comments.id',
                'comments.text',
                'comments.created_at',
                'comments.updated_at',
                'users.firstname',
                'users.lastname',
            ], 'com_page', $page);
        $commentsInfoArray = [];
        foreach ($comments as $key => $comment) {
            $commentsInfoArray[$key] = $comment;
            $commentsInfoArray[$key]['id'] = $comment->id;
            $commentsInfoArray[$key]['text'] = $comment->text;
            $commentsInfoArray[$key]['createdAt'] = date_create($comment->created_at)->format('Y.m.d H:i:s');
            $commentsInfoArray[$key]['updatedAt'] = date_create($comment->updated_at)->format('Y.m.d H:i:s');
            $commentsInfoArray[$key]['firstname'] = $comment->firstname;
            $commentsInfoArray[$key]['lastname'] = $comment->lastname;
            $commentsInfoArray[$key]['canUpdate'] = $this->canUpdate($comment->id);
        }

        $pageInfo = [];
        $pageInfo['comments'] = $commentsInfoArray;

        $pageInfo['maxPage'] = $comments->lastPage();

        $pageInfo['commentsCount'] = count($comments);

        $pageInfo['pageee'] = $page;

        return json_encode($pageInfo);
    }
    /**
     * Add comment to db
     *
     * @return bool
     */
    public function create(CommentRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $res = Comment::create([
            'text' => $request->text,
            'user_id' => $user->id,
            'report_id' => $request->reportId,
        ]);
        if ($res) {
            MailController::newComment($user, $request->reportId);
        } else {
            abort(500);
        }
    }
    /**
     * Update comment
     *
     * @return bool
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if (!$request->text) {
            abort(400);
        }

        $comment = Comment::findOrFail($request->id);
        if ($user->id != $comment->user_id && $user-> role != 'admin') {
            abort(403);
        }
        $comment->text = $request->text;
        $comment->save();
    }
    /**
     * Determines if user can update selected comment
     *
     * @return bool
     */
    public function canUpdate($commentId)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $comment = Comment::findOrFail($commentId);

        if ($user->id != $comment->user_id) {
            return false;
        }

        if (strtotime($comment->updated_at) > strtotime('-10 minutes')) {
            return true;
        } else {
            return false;
        }
    }
}
