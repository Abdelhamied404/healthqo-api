<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Appointment;
use App\Reservation;
use App\Http\Resources\LogResource;

class ReservationController extends Controller
{
    public function store($id, Request $req)
    {

        if (!(Appointment::find($id)))
            return new ErrorResource(["message" => "appointment not found"]);

        $reserved = (Reservation::where("appointment_id", $id)->with("user")->get());
        if (count($reserved)) {
            return new ErrorResource(["message" => "appointment already reserved by " . $reserved[0]->user->name, "data" => $reserved[0]]);
        }


        $code = md5((random_bytes(16) . microtime()));
        $reservation = new Reservation([
            "code" => $code,
            "user_id" => $req->user()["id"],
            "appointment_id" => $id
        ]);

        $ac = new AppointmentController();
        if (!$ac->check($id))
            return new ErrorResource(["message" => "can't check this appointment"]);

        if (!$reservation->save())
            return new ErrorResource(["message" => "can't reserve this appointment"]);

        $res = $reservation->with("appointment.doctor.user", "user")->get()[0];
        return new LogResource(["message" => "reserved!", "reservation" => $res]);



    }
}
