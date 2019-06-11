<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Appointment;
use App\Doctor;
use App\Http\Resources\LogResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Database\QueryException;


class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        $req->validate([
            "doctor_id" => "required|exists:doctors,id",
            "lim" => "integer"
        ]);
        $lim = $req->lim ? $req->lim : 15;
        $doc_id = $req->doctor_id;

        $appointments = Appointment::with("doctor.user", "reservation.user")->where("doctor_id", $doc_id)->paginate($lim);

        if (!count($appointments)) {
            return new ErrorResource(["message" => "no appointments found"]);
        }

        return new LogResource(["message" => "all appointments", "appointments" => $appointments]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        if (!count($req->user()->doctor()->get()))
            return new ErrorResource(["message" => "you are not a doctor"]);

        $doc_id = $req->user()->doctor()->get()[0]["id"];

        $appointments = $req->appointments;
        $appois = array();
        foreach ($appointments as $ap) {
            $appointment = new Appointment([
                "time" => $ap,
                "doctor_id" => $doc_id
            ]);
            try {
                $appointment->save();
                array_push($appois, $appointment->with("doctor.user", "reservation.user")->get()[0]);
            } catch (QueryException $e) {
                $errorCode = $e->errorInfo[1];
                if ($errorCode == 1062)
                    return new ErrorResource(["message" => "you already saved this appointment", "appointment" => $appointment]);
                return new ErrorResource(["message" => "something went wrong when saving"]);
            }
        }

        return new LogResource(["message" => "saved!", "appointments" => $appois]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    // custom utils
    public function check($id)
    {
        $appointment = Appointment::find($id);
        $appointment->checked = true;

        if (!$appointment->update())
            return false;

        return true;
    }

}
