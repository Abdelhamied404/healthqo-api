<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\LogResource;
use App\Http\Resources\ErrorResource;
use App\Doctor;
use App\Section;

class SectionController extends Controller
{
    public function index(Request $req)
    {
        // get all sections
        /**
         * preprocessing
         */
        $lim = $req->lim ? $req->lim : 10;

        /**
         * get sections
         */
        $sections = Section::paginate($lim);
        // check if not empty
        if (!count($sections))
            return new ErrorResource(['message' => 'no sections found']);

        /**
         * output
         */
        return new LogResource(['message' => 'found sections', 'sections' => $sections]);
    }

    public function getDoctors(Request $req)
    {
        // get all doctors in a sepecific section
        /**
         * preprocessing
         */
        $req->validate([
            'section_id' => 'required'
        ]);
        $section_id = $req->section_id;
        $lim = $req->lim ? $req->lim : 5;

        /**
         * get doctors
         */
        $doctors = Doctor::where("section_id", $section_id)->with('user')->paginate($lim);
        // check if not empty
        if (!count($doctors))
            return new ErrorResource(['message' => 'no doctors found in this section']);

        /**
         * output
         */
        return new LogResource(['message' => 'found doctors', 'doctors' => $doctors]);
    }
}
