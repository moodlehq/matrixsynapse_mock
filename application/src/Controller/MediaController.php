<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiCheck;


/**
 * API Controller to serve a mock of the Matrix API for media requests.
 *
 * @Route("/{serverID}/_matrix/media/r0")
 */
class MediaController extends AbstractController {

    /**
     * @Route("", name="endpoint")
     */
    public function endpoint(): JsonResponse
    {
        return new JsonResponse((object) [
            'errcode' => 'M_UNRECOGNIZED',
            'error' => 'Unrecognized request'
        ], 404);
    }

    /**
     * Create Matrix room.
     *
     * @Route("/upload", name="uploadMedia")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMedia(string $serverID, Request $request): JsonResponse {
        // Check call auth.
        $authCheck = ApiCheck::checkAuth($request);
        if (!$authCheck['status']) {
            // Auth check failed, return error info.
            return $authCheck['message'];
        }

        // Check HTTP method is accepted.
        $method = $request->getMethod();
        $methodCheck = ApiCheck::checkMethod(['POST'], $method);
        if (!$methodCheck['status']) {
            // Method check failed, return error info.
            return $methodCheck['message'];
        }

        $filename = $request->query->get('filename');
        $host = $request->getHost();

        // We don't care about the payload and storing it.
        // Just assume everything is fine and return a fake URI for it.
        $contentURI = 'mxc://' . $host . '/' . substr(hash('sha256', ($serverID . $filename . $host)), 0, 24);

        return new JsonResponse((object) [
            'content_uri' => $contentURI,
        ], 200);
    }
}
