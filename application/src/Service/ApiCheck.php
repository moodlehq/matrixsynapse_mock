<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiCheck {

    /**
     * Check if a correct Authorization header has been received.
     *
     * @param Request $request
     * @return array $response
     */
    public static function checkAuth(Request $request): array
    {
        $response = ['status' => true, 'message' => ''];

        // Check auth key is valid.
        $authHeader = $request->headers->get('authorization');
        if (empty($authHeader)) {
            // No valid auth header found.
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                'errcode' => 'M_MISSING_TOKEN',
                'error' => 'Missing access token'
            ], 401);
        } else {
            $authToken = substr($authHeader, 7);
            if (!self::isValidAuthToken($authToken)){
                // Auth token is not valid.
                $response['status'] = false;
                $response['message'] = new JsonResponse((object) [
                    'errcode' => 'M_UNKNOWN_TOKEN',
                    'error' => 'Invalid access token passed.'
                ], 401);
            }
        }
        return $response;
    }

    /**
     * Check if supplied auth token is valid.
     *
     * @param string $authToken
     * @return bool
     */
    private static function isValidAuthToken(string $authToken): bool
    {
        // TODO: check supplied token against list in DB. For now everything is valid.
        return true;
    }

    /**
     * Check if the API endpoint was called with an accepted HTTP method.
     *
     * @param array $acceptedTypes
     * @param string $method
     * @return array $response
     */
    public static function checkMethod(array $acceptedTypes, string $method): array
    {
        $response = ['status' => true, 'message' => ''];

        if(!in_array($method, $acceptedTypes)) {
            // Used method is not allowed for this call.
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                'errcode' => 'M_UNRECOGNIZED',
                'error' => 'Unrecognized request'
            ], 404);
        }
        return $response;
    }
}
