<?php

namespace App\Traits;

trait ApiResponseTrait
{
    public function successResponse($data, $message = '', $code = 200, $pagination = null, $links = null)
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];
        if ($message) {
            $response['message'] = $message;
        }
        if ($pagination) {
            $response['pagination'] = $pagination;
        }
        if ($links) {
            $response['links'] = $links;
        }
        return response()->json($response, $code);
    }

    public function errorResponse($message, $code = 400, $details = null, $errorCode = null)
    {
        $error = [
            'code' => $errorCode ?: 'ERROR',
            'message' => $message,
        ];
        if ($details) {
            $error['details'] = $details;
        }
        return response()->json([
            'success' => false,
            'error' => $error
        ], $code);
    }
}