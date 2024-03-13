<?php

namespace App\Http\Middleware;

use App\Models\ErrorLog;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

class ResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response->headers->get('content-type') === 'application/json') {
            Session::flush();
            $content = $response->getContent();
            $convertedContent = $this->convertDatesInJson($content);
            $response->setContent($convertedContent);

            if (($response->getStatusCode() >= 500 && $response->getStatusCode() < 600|| $response->getStatusCode() == 422)) {
                $errorLog = [
                    "api_url" => $request->fullUrl(),
                    "fields" => json_encode(request()->all()),
                    "token" => request()->bearerToken()?request()->bearerToken():"",
                    "user" => auth()->user() ? json_encode(auth()->user()) : "",
                    "user_id" => auth()->user() ?auth()->user()->id:"",
                    "status_code" => $response->getStatusCode(),
                    "ip_address" => request()->header('X-Forwarded-For'),
                    "request_method" => $request->method(),
                    "message" =>  $response->getContent(),
                ];



                  $error =   ErrorLog::create($errorLog);
                    $errorMessage = "Error ID: ".$error->id." - Status: ".$error->status_code." - Operation Failed, something is wrong! - Please call to the customer care.";
                    $response->setContent(json_encode(['message' => $errorMessage]));


            }
        }

        return $response;
    }

    private function convertDatesInJson($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            array_walk_recursive($data, function (&$value, $key) {
                // Check if the value resembles a date but not in the format G-0001
                if (is_string($value) && (Carbon::hasFormat($value, 'Y-m-d') || Carbon::hasFormat($value, 'Y-m-d\TH:i:s.u\Z') || Carbon::hasFormat($value, 'Y-m-d\TH:i:s'))) {
                    // Parse the date and format it as 'd-m-Y'
                    $date = Carbon::parse($value);
                    $value = $date->format('d-m-Y');
                }
            });

            return json_encode($data);
        }

        return $json;
    }


}
