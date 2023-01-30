<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Externalids;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Users;
use App\Entity\Threepids;
use App\Entity\Roommembers;
use App\Entity\Tokens;
use App\Entity\Passwords;
use App\Traits\GeneralTrait;
use App\Traits\MatrixSynapseTrait;

/**
 * API Controller to serve a mock of the Synapse API.
 *
 * @Route("/{serverID}/_synapse/admin/v2")
 */
class SynapseController extends AbstractController {

    use GeneralTrait, MatrixSynapseTrait;

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
     * Create admin user.
     *
     * @Route("/create-admin", name="createAdmin")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function createAdmin(string $serverID, Request $request) {
        $method = $request->getMethod();
        if ($method === 'POST') {
            $entityManager = $this->getDoctrine()->getManager();

            $user = $entityManager->getRepository(Users::class)->findOneBy(['userid' => '@admin:synapse']);
            if (!$user) {
                $user = new Users();
                $user->setServerid($serverID);
                $user->setUserid('@admin:synapse');
                $user->setDisplayname('Admin User');
                $user->setAdmin(true);
            }

            // Process tokens.
            $token = $entityManager->getRepository(Tokens::class)
                    ->findOneBy(['userid' => $user->getId()]);
            if (!$token) {
                // New user, or existing user without any associated Tokens.
                $token = new Tokens();
                $token->setAccesstoken($this->generateToken('access-token'));
                $token->setExpiresinms();
                $token->setServerid($serverID);

                $user->addtoken($token);
                $token->setUserid($user);
                $entityManager->persist($token);
            }

            // Process password.
            $passwords = $entityManager->getRepository(Passwords::class)
                    ->findOneBy(['userid' => $user->getId()]);
            if (!$passwords) {
                // 1. Generates and returns token as password.
                // 2. Generates and returns token pattern.
                $password = $this->hashPassword('password', null, true);

                // New user, or existing user without any associated Tokens.
                $passwords = new Passwords();
                $passwords->setPassword($password['token']);

                $user->addPasswords($passwords);
                $user->setPasswordpattern($password['pattern']);
                $passwords->setUserid($user);
                $entityManager->persist($passwords);
            }
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(
                'Admin user has already been created.',
                200
            );
        } else {
            return new JsonResponse(
                'Only POST method is allowed.',
                403
            );
        }
    }

    /**
     * Handle Synapse user registration.
     *
     * @Route("/users/{userID}", name="registerUser")
     */
    public function registerUser(string $serverID, string $userID, Request $request): JsonResponse
    {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $method = $request->getMethod();
        $accessCheck = $this->authHttpCheck(['PUT', 'GET'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        // Add, update of get user info.
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(Users::class)->findOneBy(['userid' => $userID, 'serverid' => $serverID]);

        if ($method == 'PUT' && !$user) {
            // Create user.
            return $this->createUser($serverID, $userID, $request);
        } elseif ($method == 'PUT' && $user) {
            // Update user.
            return $this->updateUser($serverID, $userID, $request, $user);
        } elseif ($method == 'GET' && !$user) {
            // Get but no user.
            return new JsonResponse((object) [
                'errcode' => 'M_NOT_FOUND',
                'error' => 'User not found'
            ], 404);
        }

        // Finally return user info.
        $threepids = $this->getDoctrine()->getRepository(Threepids::class)
                ->getUserThreepids($serverID, $user->getId());
        $externalids = $this->getDoctrine()->getRepository(Externalids::class)
                ->getUserExternalids($serverID, $user->getId());

        return new JsonResponse((object) [
                'name' => $userID,
                'is_guest' => 0,
                'admin' => false,
                'consent_version' => null,
                'consent_ts' => null,
                'consent_server_notice_sent' => null,
                'appservice_id' => null,
                'creation_ts' => time(),
                'user_type' => null,
                'deactivated' => false,
                'shadow_banned' => false,
                'displayname' => $user->getDisplayname(),
                'avatar_url' => null,
                'threepids '=> $threepids,
                'external_ids' => $externalids,
                'erased' => false
        ], 200);
    }

    /**
     * Create a new user in the DB.
     *
     * @param string $serverID
     * @param string $userID
     * @param Request $request
     * @return JsonResponse
     */
    private function createUser(string $serverID, string $userID, Request $request): JsonResponse
    {
        $user = new Users();
        return $this->upsertUser($serverID, $userID, $request, $user);
    }

    /**
     * Update a new user in the DB.
     *
     * @param string $serverID
     * @param string $userID
     * @param Request $request
     * @return JsonResponse
     */
    private function updateUser(string $serverID, string $userID, Request $request, Users $user): JsonResponse
    {
        return $this->upsertUser($serverID, $userID, $request, $user, 200);
    }

    /**
     * Create or update a new user in the DB.
     * This upsert function does most of the processing.
     *
     * @param string $serverID
     * @param string $userID
     * @param Request $request
     * @return JsonResponse
     */
    private function upsertUser(string $serverID, string $userID, Request $request, Users $user, int $status = 201): JsonResponse
    {
        $payload = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();
        $hasThreepids = false;
        $hasExternalids = false;

        $user->setServerid($serverID);
        $user->setUserid($userID);
        $user->setDisplayname($payload->displayname);
        $user->setAdmin();
        $user->setPasswordpattern();

        // Process threepids.
        if (!empty($payload->threepids)){
            foreach ($payload->threepids as $pid) {
                $threepid = $entityManager->getRepository(Threepids::class)
                        ->findOneBy(['serverid' => $serverID, 'userid' => $user->getId(), 'medium' => $pid->medium]);
                if (!$threepid) {
                    // New user, or existing user without any associated Threepids.
                    $threepid = new Threepids();
                    $threepid->setMedium($pid->medium);
                    $threepid->setAddress($pid->address);
                    $threepid->setServerid($serverID);

                    $user->addThreepid($threepid);
                    $threepid->setUserid($user);
                } else {
                    // Updating existing.
                    $threepid->setAddress($pid->address);
                }
                $entityManager->persist($threepid);
            }
            $hasThreepids = true;
        }

        // Process access tokens.
        $token = $entityManager->getRepository(Tokens::class)->findOneBy(['userid' => $user->getId()]);
        if (!$token) {
            // New user, or existing user without any associated Tokens.
            $token = new Tokens();
            $token->setAccesstoken($this->generateToken('access-token'));

            $user->addToken($token);
            $token->setUserid($user);
            $entityManager->persist($token);
        }

        // Process external ids.
        if (!empty($payload->external_ids)){
            foreach ($payload->external_ids as $eid) {
                $externalid = $entityManager->getRepository(Externalids::class)
                        ->findOneBy(['serverid' => $serverID, 'userid' => $user->getId(), 'auth_provider' => $eid->auth_provider]);
                if (!$externalid) {
                    // New user, or existing user without any associated Externalids.
                    $externalid = new Externalids();
                    $externalid->setAuthProvider($eid->auth_provider);
                    $externalid->setServerid($serverID);

                    $user->addExternalid($externalid);
                    $externalid->setUserid($user);
                }
                $externalid->setExternalId($eid->external_id);
                $entityManager->persist($externalid);
            }
            $hasExternalids = true;
        }

        $entityManager->persist($user);
        $entityManager->flush();

        // Craft the response to mimic what the real server would return.
        $responseObj = (object) [
                'name' => $userID,
                'is_guest' => 0,
                'admin' => false,
                'consent_version' => null,
                'consent_ts' => null,
                'consent_server_notice_sent' => null,
                'appservice_id' => null,
                'creation_ts' => time(),
                'user_type' => null,
                'deactivated' => false,
                'shadow_banned' => false,
                'displayname' => $payload->displayname,
                'avatar_url' => null,
                'external_ids' => [],
                'erased' => false
        ];

        if ($hasThreepids) {
            $payload->threepids['validated_at'] = time();
            $payload->threepids['added_at'] = time();
            $responseObj->threepids = [$payload->threepids];
        }

        if ($hasExternalids) {
            $payload->external_ids['validated_at'] = time();
            $payload->external_ids['added_at'] = time();
            $responseObj->threepids = [$payload->external_ids];
        }

        return new JsonResponse($responseObj, $status);
    }


    /**
     * Invite user into a room.
     *
     * @Route("/join/{roomID}", name="inviteUser")
     * @param Request $request
     * @return JsonResponse
     */
    public function inviteUser(string $roomID, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        // Check if room exists.
        $check = $this->roomExists($roomID);
        if (!$check['status']) {
            return $check['message'];
        }

        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['user_id']);
        if (!$check['status']) {
            return $check['message'];
        }
        $userID = $payload->user_id;

        // Check if the user has already been invited.
        $check = $this->isUserInvited($roomID, $userID);
        if (!$check['status']) {
            return $check['message'];
        }

        // Check if the user is banned from the group.
        $check = $this->isUserBanned($roomID, $userID);
        if (!$check['status']) {
            return $check['message'];
        }

        // Store the room member in the DB.
        $entityManager = $this->getDoctrine()->getManager();
        $roomMember = new Roommembers();

        $roomMember->setRoomid($roomID);
        $roomMember->setUserid($userID);
        $roomMember->setAccepted(true);
        $roomMember->setBanned();

        $entityManager->persist($roomMember);
        $entityManager->flush();

        return new JsonResponse((object) [
            'room_id' => $roomID
        ], 200);
    }
}
