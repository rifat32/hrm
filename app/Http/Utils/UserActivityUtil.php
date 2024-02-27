<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\ErrorLog;
use Exception;
use Illuminate\Http\Request;

trait UserActivityUtil
{
    // this function do all the task and returns transaction id or -1
    public function storeActivity(Request $request,$activity="",$description="")
    {

 $user = auth()->user();
 $authorizationHeader = request()->header('Authorization');

 // Now you can parse or use the $authorizationHeader as needed
 // For example, to extract the token from a Bearer token:
 $token = str_replace('Bearer ', '', $authorizationHeader);
$activityLog = [
    "api_url" => $request->fullUrl(),
    "fields" => json_encode(request()->all()),
    "token" => $token,
    "user"=> !empty($user)?(json_encode($user)):"",
    "user_id"=> !empty($user)?$user->id:"",
    "activity"=> $activity,
    "description"=> $description,
    "ip_address" =>  $request->header('X-Forwarded-For'),
    "request_method"=>$request->method(),
    "device" => $request->header('User-Agent')
];
         ActivityLog::create($activityLog);
        error_log(json_encode($activityLog));

return true;

    }
}
