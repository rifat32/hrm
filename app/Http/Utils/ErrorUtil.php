<?php

namespace App\Http\Utils;

use App\Models\ErrorLog;
use Exception;
use Illuminate\Http\Request;

trait ErrorUtil
{
    // this function do all the task and returns transaction id or -1
    public function sendError(Exception $e, $statusCode, Request $request)
    {
        // first return 422 custom error
        if ($e->getCode() == 422) {
            $statusCode = 422;
            return response()->json(json_decode($e->getMessage()), 422);
        }


        if (env("APP_DEBUG") === false) {
            $data["message"] = "something went wrong";
        } else {
            $data["message"] = $e->getMessage();
        }



        $user = auth()->user();
        $errorLog = [
            "api_url" => $request->fullUrl(),
            "user" => !empty($user) ? (json_encode($user)) : "",
            "user_id" => !empty($user) ? $user->id : "",
            "message" => $e->getMessage(),
            "status_code" => $statusCode,
            "line" => $e->getLine(),
            "file" => $e->getFile(),
            "ip_address" =>  $request->header('X-Forwarded-For'),

            "request_method" => $request->method()

        ];
        ErrorLog::create($errorLog);
        return response()->json($data, $statusCode);
    }
    public function storeError($e, $statusCode,$line,$file)
    {
        $user = auth()->user();
        $errorLog = [
            "api_url" => request()->fullUrl(),
            "user" => !empty($user) ? (json_encode($user)) : "",
            "user_id" => !empty($user) ? $user->id : "",
            "message" => json_encode($e),
            "status_code" => $statusCode,
            "line" => $line,
            "file" => $file,
            "ip_address" =>  request()->header('X-Forwarded-For'),
            "request_method" => request()->method()
        ];
        ErrorLog::create($errorLog);
    }
}
