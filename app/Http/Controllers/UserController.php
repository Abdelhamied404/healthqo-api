<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LogResource;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class UserController extends Controller
{
    public function auth(Request $req)
    {
        if (!$req->user()) {
            return new ErrorResource(["message" => "something went wrong please login again"]);
        }
        return new LogResource(['message' => "you are authanticated", 'user' => $req->user()]);
    }

    public function signup(Request $req)
    {
        /**
         * preprocessing
         */
        // validating the request
        $req->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'gender' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        /**
         * signing up
         */
        // creating new user with request
        $img_name = ($req->gender == "male" ? "male" . rand(1, 2) : "female") . ".png";
        $default_avatar = asset('public/profile_pics/default/' . $img_name);
        $user = new User([
            'name' => $req->name,
            'email' => $req->email,
            'gender' => $req->gender,
            'avatar' => $default_avatar,
            // hashing the password
            'password' => bcrypt($req->password)
        ]);
        // try to sign the user up
        if (!$user->save())
            return new ErrorResource(["message" => "can't sign you up"]);

        // generate username
        $user->update(['username' => $user->name . $user->id]);

        // create an access token
        $tokenResult = $this->createToken($user, $req->remember_me);

        /**
         * output
         */
        //create $output variable
        $output = $user;
        $output['token'] = $tokenResult->accessToken;
        $output['token_type'] = 'Bearer';
        $output['expires_at'] = Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
        // return it
        return new LogResource(['message' => 'you are signed up', 'user' => $output]);
    }

    public function login(Request $req)
    {
        /**
         * preprocessing
         */
        // validating the request
        $req->validate([
            'username' => 'required_if:email,null|string|min:3',
            'email' => 'required_if:username,null|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        // setup credentials with email or username if email was null
        if ($req['email'])
            $creds = ["email" => $req->email, "password" => $req->password];
        else
            $creds = ["username" => $req->username, "password" => $req->password];
        // try to log the user in
        if (!Auth::attempt($creds))
            return new ErrorResource(['message' => 'username/email or password are wrong']);

        /**
         * authorizing
         */
        // get the user
        $user = $req->user();
        // create an access token
        $tokenResult = $this->createToken($user, $req->remember_me);

        /**
         * output
         */
        //create $output variable
        $output = $user;
        $output['token'] = $tokenResult->accessToken;
        $output['token_type'] = 'Bearer';
        $output['expires_at'] = Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
        //return it
        return new LogResource(['message' => 'you are logged in', 'user' => $output]);
    }

    public function logout(Request $req)
    {
        if (!$req->user()->token()->revoke())
            return new ErrorResource(['message' => "can't log you out"]);

        return new LogResource(['message' => 'Successfully logged out']);
    }


    public function update(Request $req)
    {
        /**
         * preprocessing
         */
        // validating the request
        $req->validate([
            'username' => 'required_if:email,null|string|min:3',
            'email' => 'required_if:username,null|string|email',
        ]);

        /**
         * updating
         */
        // get old user and columns in this model
        $user = $req->user();
        $cols = Schema::getColumnListing('users');
        // assiging updated user to user excepts avatar and password
        foreach ($req->all() as $key => $value) {
            if (in_array($key, $cols) && $key != "avatar" && $key != "password")
                $user[$key] = $value;
        }
        // update password if set
        if (isset($req['password']))
            $user["password"] = bcrypt($req['password']);

        // get avatar from request
        $avatar = $req->file('avatar');
        if ($avatar) {
            // generate unique file name for the user and save it to the disk
            $img_name = $user["username"] . "-profile." . $avatar->getClientOriginalExtension();
            Storage::disk('local')->put($img_name, File::get($avatar));
            // move to public and get this path
            $avatar->move(public_path() . '/public/profile_pics/', $img_name);
            $path = asset('public/profile_pics/' . $img_name);
            // assign the path to user's avatar
            $user->avatar = $path;
        }

        if (!$user->save())
            return new ErrorResource(["message" => "can't sign you up"]);

        return new LogResource(['message' => 'updated', 'user' => $user]);
    }

    public function destroy(Request $req)
    {
        /**
         * deleting
         */
        // get the currently authanticated user
        $user = $req->user();
        // try to delete him
        if (!$user->delete())
            return new ErrorResource(['message' => "can't delete user"]);

        return new LogResource(['message' => 'deleted']);
    }

    public function find(Request $req, $q)
    {
        /**
         * get all users starts with query and paginate with limit
         */
        $lim = $req->lim ? $req->lim : 5;
        $users = User::where("name", "LIKE", "$q%")->orwhere("username", "LIKE", "$q%")->paginate($lim);
        // check if not users found
        if (!count($users)) {
            return new ErrorResource(['message' => 'no users found']);
        }
        return new LogResource(["message" => 'found users', 'users' => $users]);
    }

    public function get(Request $req, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return new ErrorResource(['message' => 'no users found']);
        }
        return new LogResource(["message" => 'found users', 'user' => $user]);
    }

    public function getByUsername(Request $req, $username)
    {
        $user = User::where("username", $username)->with("doctor.section")->get()[0];
        if (!$user) {
            return new ErrorResource(['message' => 'no user found']);
        }
        return new LogResource(["message" => 'found user', 'user' => $user]);
    }

    /**
     * utils
     */
    protected function createToken($user, $remember_me)
    {
        // create an access token
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        // if user wanted to remember him
        if ($remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        // save this token back to database
        if (!$token->save())
            return new ErrorResource(['message' => "can't remember you"]);

        return $tokenResult;
    }

}
