<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Appointment;
use App\Doctor;
use App\Http\Resources\LogResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Database\QueryException;
use App\Reservation;

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
        return new LogResource(["message" => "all appointments", "appointments" => $this->group($appointments)]);


        // if (!count($appointments)) {
        //     return new ErrorResource(["message" => "no appointments found"]);
        // }

        // return new LogResource(["message" => "all appointments", "appointments" => $appointments]);
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

    public function checkCode(Request $req)
    {
        $req->validate([
            "code" => "required",
        ]);

        $user = $req->user();
        $user_id = $user["id"];

        if ($this->isDoctor($user_id)) {

            $code = $req->code;
            $reservation =  Reservation::where("code", $code)->with("appointment.doctor")->with("user")->get();

            if ($reservation->count()) {
                $reservation = $reservation[0];
                if ($reservation->appointment->doctor->user_id == $user_id) {
                    return new LogResource(["message" => "correct code", "data" => $reservation]);
                } else {
                    return new ErrorResource(["message" => "wrong code"]);
                }
            } else {
                return new ErrorResource(["message" => "wrong code"]);
            }
        }
    }


    public function getCode(Request $req)
    {
        $req->validate([
            "id" => "required|exists:appointments,id"
        ]);

        $appointment = Appointment::with("reservation.user")->find($req->id);
        if ($appointment->reservation->user->id == $req->user()["id"]) {
            $code = $appointment->reservation->code;
        } else {
            $code = "you can't have this appointment";
        }
        echo $code;
    }


    // custom utils
    public function isDoctor($id)
    {
        return Doctor::with("user")->where("user_id", $id)->get()->count();
    }

    public function check($id)
    {
        $appointment = Appointment::find($id);
        $appointment->checked = true;

        if (!$appointment->update())
            return false;

        return true;
    }

    public function group($list)
    {
        $grouped = [];
        $list = $list->toArray()["data"];

        while (count($list)) {
            $checker = null;
            $row = [];
            foreach ($list as $i => $ele) {
                if ($i == 0) {
                    // checks if it's the first element
                    array_push($row, $ele);
                    array_shift($list);
                    $checker = explode(" ", $ele["time"])[0];
                } else {
                    // checks on all other values
                    $date = explode(" ", $ele["time"])[0];
                    if ($checker == $date) {
                        array_push($row, $ele);
                        array_shift($list);
                    } else {
                        continue;
                    }
                }
            }

            array_push($grouped, $row);
        }

        return ($grouped);
    }
}
