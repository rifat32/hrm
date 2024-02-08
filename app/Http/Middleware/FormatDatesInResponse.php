<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FormatDatesInResponse
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response->headers->get('content-type') === 'application/json') {
            $content = $response->getContent();
            $convertedContent = $this->convertDatesInJson($content);
            $response->setContent($convertedContent);
        }

        return $response;
    }

    private function convertDatesInJson($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            array_walk_recursive($data, function (&$value, $key) {
                // Check if the value resembles a date but not in the format G-0001
                if (is_string($value) && Carbon::hasFormat($value, 'Y-m-d')) {
                    $value = Carbon::createFromFormat('Y-m-d', $value)->format('d-m-Y');
                }
            });

            return json_encode($data);
        }

        return $json;
    }


}
