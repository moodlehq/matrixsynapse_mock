<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller to serve a mock of the Matrix API.
 *
 * @Route("/{serverID}/_matrix/client/r0")
 */
class MatrixController extends DataController {

    /**
     * @Route("", name="endpoint")
     */
    public function endpoint(): JsonResponse
    {
        return new JsonResponse((object) [
                'errcode' => 'M_UNRECOGNIZED',
                'error' => 'Unrecognized request'
        ],
        404);
    }

}
