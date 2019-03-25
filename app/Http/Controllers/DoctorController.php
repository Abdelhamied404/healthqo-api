<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Doctor;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LogResource;
use Illuminate\Support\Facades\DB;
use App\Section;
use Illuminate\Support\Facades\Schema;

class DoctorController extends Controller
{
    public function getRecommended(Request $req)
    {
        $lim = $req->lim ? $req->lim : 5;
        $doctors = Doctor::orderBy('rate', 'desc')->with("user")->with("section")->paginate();
        return response()->json($doctors, 200);
    }
    public function store(Request $req)
    {
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
        $cols = Schema::getColumnListing('doctors');
        // check if section changed
        $old_sec_id = $doctor['section_id'];
        if ($old_sec_id != $req->section_id) {
            $doctor->is_trusted = 0;
            $doctor->rate = 0;
        }
        // assign all except is trusted and rate
        foreach ($req->all() as $key => $value) {
            if (in_array($key, $cols) && $key != "is_trusted" && $key != "rate") {
                $doctor[$key] = $value;
            }
        }

        /**
         * try to save these data
         */
        if (!$doctor->save())
            return new ErrorResource(['message' => "can't add you to doctors"]);

        /**
         * output
         */
        $output = $doctor->with('user')->with('section')->get();
        return new LogResource(["message" => "doctor updated", 'doctor' => $output]);
    }


}
