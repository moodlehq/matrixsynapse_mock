<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Tokens;
use Doctrine\ORM\EntityManagerInterface;

class ApiCheck extends AbstractController {

    private $entityManger;

    /**
     * Constructor function.
     *
     * @param EntityManagerInterface $entityManger
     */
    public function __construct(EntityManagerInterface $entityManger)
    {
        $this->entityManger = $entityManger;
    }

    /**
     * Check if a correct Authorization header has been received.
     *
     * @param Request $request
     * @return array $response
     */
    public function checkAuth($request): array
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
            $check_token = $this->isValidAuthToken($authToken);
            if (empty($check_token)){
                // Auth token is not valid.
                $response['status'] = false;
                $response['message'] = new JsonResponse((object) [
                    'errcode' => 'M_UNKNOWN_TOKEN',
                    'error' => 'Invalid access token passed.'
                ], 401);
            } else {
                $response['user_id'] = $check_token->getUserid()->getUserid();
            }

        }
        return $response;
    }

    /**
     * Check if supplied auth token is valid.
     *
     * @param string $authToken
     * @return object
     */
    private function isValidAuthToken(string $authToken): ?object
    {
        return $this->entityManger->getRepository(Tokens::class)->findOneBy(['accesstoken' => $authToken]);
    }

    /**
     * Check if the API endpoint was called with an accepted HTTP method.
     *
     * @param array $acceptedTypes
     * @param string $method
     * @return array $response
     */
    public function checkMethod(array $acceptedTypes, string $method): array
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
