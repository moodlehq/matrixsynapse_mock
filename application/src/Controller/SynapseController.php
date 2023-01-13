<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Users;
use App\Entity\Threepids;

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

    /**
     * Handle Synapse user registration.
     *
     * @Route("/users/{userID}", name="registerUser")
     */
    public function registerUser(string $serverID, string $userID, Request $request): JsonResponse
    {
        // Check call auth.
        $authCheck = $this->checkAuth($request);
        if (!$authCheck['status']) {
            // Auth check failed, return error info.
            return $authCheck['message'];
        }

        // Check HTTP method is accepted.
        $method = $request->getMethod();
        $methodCheck = $this->checkMethod(['PUT', 'GET'], $method);
        if (!$methodCheck['status']) {
            // Method check failed, return error info.
            return $methodCheck['message'];
        }

        if ($method == 'PUT') {
            // Create user.
            $this->createUser($serverID, $userID, $request);
        } elseif ($method == 'GET') {
            // Get user.
        }


        return new JsonResponse((object) [
                'severID' => $serverID,
                'userID' => $userID,
                'method' => $request->getMethod(),
                'authHeader' => $request->headers->get('authorization')
        ]);
    }

    /**
     * Check if a correct Authorization header has been received.
     *
     * @param Request $request
     * @return array $response
     */
    private function checkAuth(Request $request): array
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
            ],
                    401);
        } else {
            $authToken = substr($authHeader, 7);
            if (!$this->isValidAuthToken($authToken)){
                // Auth token is not valid.
                $response['status'] = false;
                $response['message'] = new JsonResponse((object) [
                        'errcode' => 'M_UNKNOWN_TOKEN',
                        'error' => 'Invalid access token passed.'
                ],
                        401);
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
    private function isValidAuthToken(string $authToken): bool
    {
        // TODO: check supplied token against list in DB. For now everything is valid.
        return True;
    }

    /**
     * Check if the API endpoint was called with an accepted HTTP method.
     *
     * @param array $acceptedTypes
     * @param string $method
     * @return array $response
     */
    private function checkMethod(array $acceptedTypes, string $method): array
    {
        $response = ['status' => true, 'message' => ''];

        if(!in_array($method, $acceptedTypes)) {
            // Used method is not allowed for this call.
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                    'errcode' => 'M_UNRECOGNIZED',
                    'error' => 'Unrecognized request'
            ],
                    404);
        }

        return $response;
    }

    /**
     * @param string $serverID
     * @param string $userID
     * @param Request $request
     * @return \stdClass
     */
    private function createUser(string $serverID, string $userID, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();

        $user = new Users();
        $user->setServerid($serverID);
        $user->setUserid($userID);
        $user->setDisplayname($payload->displayname);

        // Process threepids.
        if (!empty($payload->threepids)){
            foreach ($payload->threepids as $pid) {
                $threepid = new Threepids();
                $threepid->setMedium($pid->medium);
                $threepid->setAddress($pid->address);

                $user->addThreepid($threepid);
            }
        }

        // Process external ids.
        //TODO: add support for external ids.

        $entityManager->persist($user);
        $entityManager->flush();

        //{"name":"@barruser:synapse","is_guest":0,"admin":false,"consent_version":null,"consent_ts":null,"consent_server_notice_sent":null,"appservice_id":null,"creation_ts":1673592567,"user_type":null,"deactivated":false,"shadow_banned":false,"displayname":"Barabius Darabius...","avatar_url":null,"threepids":[{"medium":"email","address":"bar@bar.com","validated_at":1673592567128,"added_at":1673592567128}],"external_ids":[],"erased":false}
        return new JsonResponse((object) [
                'errcode' => 'M_UNKNOWN_TOKEN',
                'error' => 'Invalid access token passed.'
        ],
                401);

    }

}
