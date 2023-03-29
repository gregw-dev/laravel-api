<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Auth;

class IsValidConferenceRoom
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
        $pubRoomName = $request->input('room_name');
        if(!empty($pubRoomName)){
            $colTemp = Str::of($pubRoomName)->explode('.');
            $count = count($colTemp);
            $strUuid = $colTemp[$count-1];
            $strRoomType = strtolower($colTemp[$count-2]);
            if($strRoomType === 'account'){
                $arrTemp = Auth::user()->accounts->pluck('account_uuid')->toArray();
            }else{
                $arrTemp = Auth::user()->teams->pluck('project_uuid')->toArray();
            }
            if(in_array($strUuid, $arrTemp)){
                return $next($request);
            }
        }
        abort(Response::HTTP_FORBIDDEN, "Invalid or unauthorized room" );
    }
}
