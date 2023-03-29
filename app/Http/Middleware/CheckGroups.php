<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckGroups
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $group=null)
    {

        $userGroups=Auth::user()->groups()->where('group_name','LIKE',$group)->count();
        if ($userGroups === 0) {
            return redirect('/denied');
        }
        return $next($request);
    }
}
