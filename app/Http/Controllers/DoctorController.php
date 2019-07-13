<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Doctor;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LogResource;
use Illuminate\Support\Facades\DB;
use App\Section;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\User;

class DoctorController extends Controller
{
    public function getRecommended(Request $req)
    {
        $lim = $req->lim ? $req->lim : 5;
        $doctors = Doctor::orderBy('rate', 'desc')->with("user")->with("section")->paginate($lim);

        if (count($doctors) <= 0)
            return new ErrorResource(["message" => "no doctors found"]);

        return new LogResource(["message" => "found doctors", 'doctors' => $doctors]);
    }
    public function store(Request $req)
    {
        // update or register a new doctor
        /**
         * update user
         */
        $user = new UserController();
        $user->update($req);

        /**
         * preprocessing
         */
        // validating the request
        $req->validate([
            'section_id' => 'required|integer',
            'certificate' => 'required'
        ]);
        // init doctors and cols of this model
        $curr_user_id = $req->user()['id'];
        $doctor = Doctor::where('user_id', $curr_user_id)->first();

        if ($doctor == null) {
            $doctor = new Doctor();
            $doctor['user_id'] = $curr_user_id;
        }
        $cols = Schema::getColumnListing('doctors');
        // check if section changed
        $old_sec_id = $doctor['section_id'];
        if ($old_sec_id != $req->section_id) {
            $doctor->is_trusted = 0;
            $doctor->rate = 0;
        }
        // assign all except is trusted, certificate and rate
        foreach ($req->all() as $key => $value) {
            if (in_array($key, $cols) && $key != "is_trusted" && $key != "certificate" && $key != "rate") {
                $doctor[$key] = $value;
            }
        }

        // upload the certificate
        $certificate = $req->file('certificate');
        if ($certificate) {
            // generate unique file name for the doctor and save it to the disk
            $img_name = $curr_user_id . "-certificate." . $certificate->getClientOriginalExtension();
            Storage::disk('local')->put($img_name, File::get($certificate));
            // move to public and get this path
            $certificate->move(public_path() . '/public/certificates/', $img_name);
            $path = asset('public/certificates/' . $img_name);
            // assign the path to user's avatar
            $doctor["certificate"] = $path;
        }

        /**
         * try to save these data
         */
        if (!$doctor->save())
            return new ErrorResource(['message' => "can't add you to doctors"]);

        /**
         * output
         */
        $doctor = Doctor::where("user_id", $curr_user_id)->with('user')->with('section')->first();
        return new LogResource(["message" => "doctor updated", 'doctor' => $doctor]);
    }

    public function find(Request $req, $q)
    {
        $lim = $req->lim ? $req->lim : 5;

        $users = Doctor::with("user")->whereHas("user", function ($query) use ($q) {
            $query->where("name", "LIKE", "$q%");
        })->paginate($lim);

        if (!count($users)) {
            return new ErrorResource(['message' => 'no users found']);
        }
        return new LogResource(["message" => 'found users', 'users' => $users]);
    }
}
