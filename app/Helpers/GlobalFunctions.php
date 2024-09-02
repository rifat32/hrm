<?php





if (!function_exists('debugHalt')) {
    function debugHalt($message)
    {
        // Send JSON response with a 500 status code
        $response = response()->json([
            'error' => 'Error',
            'message' => $message
        ], 409);

        // Send the response
        $response->send();

        // Stop further execution
        exit;
    }
}
