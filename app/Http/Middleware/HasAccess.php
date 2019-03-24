<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HasAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header = $request->header();
        $key = isset($header["x-api-key"]) ? $header["x-api-key"][0] : "null";
        $client = DB::table('oauth_clients')->where('secret', $key)->get();

        if ($client != "[]") {
            return $next($request);
        } else
            abort(404);
    }
}
