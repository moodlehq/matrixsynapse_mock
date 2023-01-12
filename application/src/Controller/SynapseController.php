<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller to serve a mock of the Synapse API.
 *
 * @Route("/{serverID}/_synapse/admin/v2")
 */
class SynapseController extends DataController {

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
