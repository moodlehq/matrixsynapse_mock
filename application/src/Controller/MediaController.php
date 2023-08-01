<?php

namespace App\Controller;

use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiCheck;
use App\Traits\GeneralTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * API Controller to serve a mock of the Matrix API for media requests.
 *
 * @Route("/{serverID}/_matrix/media")
 */
class MediaController extends AbstractController {

    use GeneralTrait;

    /**
     * @Route("", name="endpoint")
     */
    public function endpoint() : JsonResponse
    {
        return new JsonResponse((object) [
            'errcode' => 'M_UNRECOGNIZED',
            'error' => 'Unrecognized request'
        ], 404);
    }

    /**
     * Create Matrix room.
     *
     * @Route("/r0/upload")
     * @Route("/r0/upload/")
     * @Route("/v3/upload")
     * @Route("/v3/upload/")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMedia(string $serverID, Request $request) : JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $filename = $request->query->get('filename') ?? sha1(uniqid()) . '.jpg';
        $filepath = $this->getParameter('medias_directory').'/'.$filename;
        $contenturi = $request->getSchemeAndHttpHost() . "/medias/$filename";

        $filesystem = new Filesystem();
        $filesystem->dumpFile($filepath, file_get_contents("php://input"));

        $medias = new Media();
        $medias->setContenturi($contenturi);
        $medias->setServerid($serverID);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($medias);
        $entityManager->flush();

        return new JsonResponse((object) [
            'content_uri' => $contenturi,
        ], 200);
    }
}
