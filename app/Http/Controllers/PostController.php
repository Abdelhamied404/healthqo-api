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
        $posts = Post::latest()->with('user', 'comments.user', 'votes.user')->paginate($lim);

         // check if not empty
        if (!count($posts))
            return new ErrorResource(['message' => 'no posts found']);

        // check if this user has voted this post
        foreach ($posts as $post) {
            foreach ($post->votes()->get() as $vote) {
                if ($vote->user()->get('id')[0]['id'] == $req->user()['id']) {
                    $post->voted = $vote->vote;
                    break;
                } else {
                    $post->voted = 0;
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
        $output = $post->with('user', 'comments', 'votes')->find($post->id);

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
        $v = end($parts) == "up" ? 1 : -1;


        $alreadyVoted = false;
        // check if already voted this post
        if (count($post->votes)) {
            foreach ($post->votes as $vote) {
                if ($req->user()['id'] == $vote->user['id']) {
                    $alreadyVoted = true;
                    // if it's the same vote then unvote it
                    if ($vote['vote'] == $v) {
                        // unvote
                        return $this->unvote($req, $id);
                    } else {
                        // revote
                        return $this->revote($req, $id, $v);
                    }
                }
            }
        }

        // if first time voting
        // check if post exists
        $post = Post::with("votes.user")->find($id);
        if (!$post)
            return new ErrorResource(["message" => "post does't exists"]);

        // increase votes by 1 in this post
        $post->vote += $v;

        // make a new vote
        $vote = new Vote([
            "vote" => $v,
            "user_id" => $req->user()['id'],
            "post_id" => $id
        ]);

        
        // try to write to database
        if (!($vote->save() && $post->save()))
            return new ErrorResource(["message" => "can't save this vote"]);


        // output
        $post = Post::with('votes.user', 'user', 'comments.user')->find($post->id);
        $post->voted = $v;
        return new LogResource(["message" => "voted", "post" => $post]);

    }

    public function revote(Request $req, $id, $v)
    {
        $vote = Vote::where("post_id", $id)->where("user_id", $req->user()['id']);
        if (!isset($vote->get()[0]))
            return new ErrorResource(["message" => "you haven't voted to this post yet"]);

        $vote = $vote->get()[0];
        $vote->vote = $v;

        $post = Post::with('votes', 'votes.user')->find($id);
        $post->vote += ($v * 2);


        if (!($vote->save() && $post->save()))
            return new ErrorResource(["message" => "can't revote this post"]);

        $post = Post::with('votes.user', 'user', 'comments.user')->find($id);
        $post->voted = $v;
        return new LogResource(["message" => "revoted", "post" => $post]);
    }

    public function unvote(Request $req, $id)
    {
        $vote = Vote::where("post_id", $id)->where("user_id", $req->user()['id']);
        if (!isset($vote->get()[0]))
            return new ErrorResource(["message" => "you haven't voted to this post yet"]);

        $vote = $vote->get()[0];
        $post = Post::with('votes.user')->find($id);
        $post->vote -= $vote->vote;

        if (!($vote->delete() && $post->save()))
            return new ErrorResource(["message" => "can't unvote this post"]);


        $post = Post::with('votes.user', 'user', 'comments.user')->find($id);
        $post->voted = 0;
        return new LogResource(["message" => "unvoted", "post" => $post]);
    }


    // get profile posts
    public function profile(Request $req)
    {
        $user = $req->user();
        $lim = $req->lim ? $req->lim : 10;

        $posts = Post::where("user_id", $user['id'])->with("votes.user")->paginate($lim);
        if (!count($posts))
            return new ErrorResource(['message' => 'no posts found']);

        // check if this user has voted this post
        foreach ($posts as $post) {
            foreach ($post->votes()->get() as $vote) {
                if ($vote->user()->get('id')[0]['id'] == $req->user()['id']) {
                    $post->voted = $vote->vote;
                    break;
                } else {
                    $post->voted = 0;
                }
            }
        }

        return new LogResource(['message' => 'posts found', "posts" => $posts]);
    }

}
