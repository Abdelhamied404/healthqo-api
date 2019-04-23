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
         $posts = Post::latest()->with('user', 'comments', 'comments.user', 'votes')->paginate($lim);

         // check if not empty
         if (!count($posts))
             return new ErrorResource(['message' => 'no posts found']);

         // if user authanticated
         if($req->user()){
           // get posts user voted up or down
           $votedPosts = Post::whereHas('votes', function ($query) use ($req){
             $query->where('user_id', $req->user()['id']);
           })->pluck('id')->toArray();

           foreach ($posts as $post) {
             $post_id = $post->id;
             if(in_array($post_id, $votedPosts)){
               $post->voted=1;
             }else{
               $post->voted=0;
             }
           }
         }

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
        /**
         * pre
         */
        // get the post
        $post = Post::find($id);
        // check if exists
        if (!$post)
            return new ErrorResource(["message" => "can't find this post"]);

        // if exists
        // check if up or down
        $parts = explode('/', $req->url());
        $v = end($parts);
        $v = ($v == "up") ? 1 : -1;

        // get the vote and check if already voted on this post
        $already_voted = true;
        $vote = Vote::where("user_id", $req->user()['id'])->get();
        $vote = isset($vote[0]) ? $vote[0] : null;
        if (!$vote) {
            $already_voted = false;
            $vote = new Vote([
                "vote" => $v,
                "user_id" => $req->user()['id'],
                "post_id" => $id
            ]);
        }

        // vote up or down
        if ($already_voted) {
            if ($v != $vote->vote) {
                $post->vote += $v * 2;
            }
        } else {
            $post->vote += $v;
        }
        $vote->vote = $v;

        // try to save it
        if (!($vote->save() && $post->save()))
            return new ErrorResource(["message" => "can't vote this post"]);

        // output
        $post = Post::with('votes', 'votes.user')->find($id);
        return new LogResource(["message" => "voted", "post" => $post]);
    }

    public function unvote(Request $req, $id)
    {
        $vote = Vote::where("post_id", $id)->where("user_id", $req->user()['id']);
        if (!isset($vote->get()[0]))
            return new ErrorResource(["message" => "you haven't voted to this post yet"]);

        $vote = $vote->get()[0];
        $post = Post::with('votes', 'votes.user')->find($id);
        $post->vote -= $vote->vote;

        if (!($vote->delete() && $post->save()))
            return new ErrorResource(["message" => "can't unvote this post"]);

        return new LogResource(["message" => "unvoted"]);
    }

}
