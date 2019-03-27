<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Comment;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LogResource;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        //get comments in a post
        /**
         * preprocessing
         */
        $req->validate([
            'post_id' => "required"
        ]);
        $post_id = $req->post_id;
        $lim = $req->lim ? $req->lim : 10;

        /**
         * get comment
         */
        $comments = Comment::where("post_id", $post_id)->with('user')->paginate($lim);
        // check if empty
        if (!count($comments))
            return new ErrorResource(['message' => 'no comments in this post']);

        /**
         * output
         */
        return new LogResource(["message" => 'found comments', 'comments' => $comments]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        /**
         * pre
         */
        $req->validate([
            "body" => "required|min:1",
            "post_id" => "required",
        ]);
        $user = $req->user();
        if (!$user)
            return new ErrorResource(["message" => "you are not authanticated"]);

        /**
         * create new comment
         */
        $comment = new Comment([
            "body" => $req->body,
            "post_id" => $req->post_id,
            "user_id" => $user['id']
        ]);
        // try to save it
        if (!$comment->save())
            return new ErrorResource(['message' => "can't save this comment"]);

        /**
         * output
         */
        $output = $comment->with('user')->with('post')->find($comment->id);

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // show a comment
        /**
         * init
         */
        $comment = Comment::with('user')->with('post')->find($id);
        // check if exist
        if (!$comment)
            return new ErrorResource(["message" => "can't find this comment"]);

        /**
         * ouput
         */
        return new LogResource(["message" => "found the comment", "comment" => $comment]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req, $id)
    {
        // update a comment
        /**
         * pre
         */
        // get old comment
        $comment = Comment::with('user')->with('post')->find($id);
        // check if exists
        if (!$comment)
            return new ErrorResource(["message" => "can't find this comment"]);
        // check if has access
        $hasAccess = $req->user()['id'] == $comment['user']['id'];
        if (!$hasAccess)
            return new ErrorResource(["message" => "it's not your comment to update"]);
        // validating
        $req->validate([
            "body" => "required|min:1",
            "post_id" => "required",
        ]);

        /**
         * updateing
         */
        // update
        $comment->body = $req->body;
        $comment->post_id = $req->post_id;
        $comment->user_id = $req->user()['id'];
        // try to save it 
        if (!$comment->save())
            return new ErrorResource(["message" => "can't update this comment"]);

        return new LogResource(["message" => "comment updated", "comment" => $comment]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $req, $id)
    {
        // delete a single comment
        /**
         * init
         */
        // get the comment
        $comment = Comment::with('user')->with('post')->find($id);
        // check if exists
        if (!$comment)
            return new ErrorResource(["message" => "can't find this comment"]);
        // check if the comment belongs to the same user
        $hasAccess = $req->user()['id'] == $comment['user']['id'];
        if (!$hasAccess)
            return new ErrorResource(["message" => "it's not your comment to delete"]);

        /**
         * delete
         */
        // try to delete
        if (!$comment->delete())
            return new ErrorResource(["message" => "can't delete your comment"]);

        /**
         * output
         */
        return new LogResource(["message" => "comment deleted", "comment" => $comment]);

    }
}
