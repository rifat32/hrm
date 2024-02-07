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

        if (json_last_error() === JSON_ERROR_NONE) {
            array_walk_recursive($data, function (&$value, $key) {
                if (is_string($value) && strtotime($value) !== false && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    $value = date('d-m-Y', strtotime($value));
                }
            });

            return json_encode($data);
        }

        return $json;
    }


}
