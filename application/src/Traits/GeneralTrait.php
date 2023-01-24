<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiCheck;

trait GeneralTrait {

    /**
     * Check for auth and HTTP method.
     *
     * @param string $requestMethod
     * @param Request $request
     * @param string $roomID
     * @param bool $getRoom Whether or not to return room object.
     * @return array|null
     */
    public function authHttpCheck(string $requestMethod = null, Request $request): ?array {
        // Check call auth.
        $authCheck = ApiCheck::checkAuth($request);
        if (!$authCheck['status']) {
            // Auth check failed, return error info.
            return $authCheck;
        }

        // Check HTTP method is accepted.
        $method = $request->getMethod();
        $methodCheck = ApiCheck::checkMethod([$requestMethod], $method);
        if (!$methodCheck['status']) {
            // Method check failed, return error info.
            return $methodCheck;
        }

        return ['status' => true];
    }

    /**
     * Generates a unique id.
     *
     * @param string $extra
     * @return String
     */
    private function generateUniqueID(string $extra = null): string {
        $string = hash('sha256', $extra.date("Ymdhms"));
        $newstring = null;
        $prevpos = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $randpos = (int)rand(1, 10);
            if (($randpos > 3) && (($i % $randpos) === 0)) {
                $prevpos = (int)($prevpos + $randpos);
                $newstring = substr_replace($newstring ?? $string, '-', $prevpos, 1);
            }
        }
        return $newstring;
    }
}