<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LogResource;
use App\Post;

use App\Events\NewPostsCast;
use App\Vote;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        // get all posts
        /**
         * preprocessing
         */
        $lim = $req->lim ? $req->lim : 10;

        /**
         * get posts
         */
        $posts = Post::orderBy('id', 'desc')->with('user', 'comments', 'comments.user')->paginate($lim);
        // check if not empty
        if (!count($posts))
            return new ErrorResource(['message' => 'no posts found']);

        /**
         * output
         */
        return new LogResource(['message' => 'found posts', 'posts' => $posts]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        // add new post
        /**
         * preprocessing
         */
        // validating
        $req->validate([
            'title' => 'required|min:1',
            'body' => 'required|min:1',
            'tags' => 'required|min:1'
        ]);
        // get signed in user
        $user = $req->user();
        if (!$user)
            return new ErrorResource(["message" => "something went wrong please login again"]);


        /**
         * create new post
         */
        $post = new Post([
            'title' => $req->title,
            'body' => $req->body,
            'tags' => $req->tags,
            'user_id' => $user['id']
        ]);

        // try to save it
        if (!$post->save())
            return new ErrorResource(['message' => "can't save this post"]);

        /**
         * output
         */
        $output = $post->with('user', 'comments')->find($post->id);
        /**
         * once i have a new post
         * fire a broadcast Event
         */
        event(new NewPostsCast($output));

        return new LogResource(["message" => "posted", "post" => $output]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // get a specific post
        /**
         * init
         */
        $post = Post::with('user')->find($id);
        if (!$post)
            return new ErrorResource(['message' => "no post found"]);

        /**
         * output
         */
        return new LogResource(["message" => 'found post', 'post' => $post]);

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
        // update the post
        /**
         * preprocessing
         */
        // get old post
        $post = Post::with('user')->find($id);
        // check if exists
        if (!$post)
            return new ErrorResource(['message' => "can't find this post"]);
        // check if has access
        $hasAccess = $req->user()['id'] == $post['user']['id'];
        if (!$hasAccess)
            return new ErrorResource(["message" => "it's not your post to update"]);
        // validating
        $req->validate([
            'title' => 'required|min:1',
            'body' => 'required|min:1',
            'tags' => 'required|min:1',
        ]);

        /**
         * updating
         */
        // update
        $post->title = $req->title;
        $post->body = $req->body;
        $post->tags = $req->tags;
        // try to save it
        if (!$post->save())
            return new ErrorResource(['message' => "can't update your post"]);

        return new LogResource(["message" => "post updated!", "post" => $post]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $req, $id)
    {
        // delete a single post
        /**
         * init
         */
        // get the post
        $post = Post::with('user')->find($id);
        // check if exists
        if (!$post)
            return new ErrorResource(["message" => "can't find your post"]);
        // check if has access to delete this post
        $hasAccess = $req->user()['id'] == $post['user']['id'];
        if (!$hasAccess)
            return new ErrorResource(["message" => "it's not your post to delete"]);

        /**
         * delete
         */
        // try to delete
        if (!$post->delete())
            return new ErrorResource(["message" => "can't delete your post"]);

        /**
         * output
         */
        return new LogResource(["message" => "post deleted", "post" => $post]);
    }

    // custom
    public function vote(Request $req, $id)
    {

        $already_voted = true;

        $req->validate([
            "vote" => "required|integer",
        ]);

        // get the post
        $post = Post::with('votes', 'votes.user')->find($id);

        $vote = Vote::where("user_id", $req->user()['id'])->get()[0];
        if (!$vote) {
            $already_voted = false;
            $vote = new Vote([
                "vote" => $req->vote,
                "user_id" => $req->user()['id'],
                "post_id" => $id
            ]);
        }
            

        // check if exists
        if (!$post)
            return new ErrorResource(["message" => "can't find this post"]);

        // vote up or down

        $post->vote += $req->vote;
        $vote->vote = intval($req->vote);

        // try to save it
        if (!$vote->save())
            return new ErrorResource(["message" => "can't vote this post"]);
        if (!$post->save())
            return new ErrorResource(["message" => "can't vote this post"]);


        // done
        return new LogResource(["message" => "voted", "post" => $post]);
    }

}
