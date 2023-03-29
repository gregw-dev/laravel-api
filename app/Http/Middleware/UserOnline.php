<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Cache;

class UserOnline
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $expiresAt = Carbon::now()->addMinutes(1); // keep online for 1 min
            Cache::put('user-online-' . Auth::user()->id, true, $expiresAt);
        }
        return $next($request);

//        this is how we gonna test isUserOnline
//        foreach ($users as $user) {
//            if (Cache::has('user-online-' . $user->id))
//                echo "User " . $user->name . " is online.";
//            else
//                echo "User " . $user->name . " is offline.";
//        }
    }
}
