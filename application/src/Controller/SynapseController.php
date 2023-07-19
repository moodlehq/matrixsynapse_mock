<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\ExternalId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\ThreePID;
use App\Entity\RoomMember;
use App\Entity\Room;
use App\Entity\Token;
use App\Traits\GeneralTrait;
use App\Traits\MatrixSynapseTrait;

/**
 * API Controller to serve a mock of the Synapse API.
 *
 * @Route("/{serverID}/_synapse/admin")
 */
class SynapseController extends AbstractController {

    use GeneralTrait, MatrixSynapseTrait;

    /**
     * @Route("/v2", name="endpoint")
     */
    public function endpoint(): JsonResponse
    {
        return new JsonResponse((object) [
            'errcode' => 'M_UNRECOGNIZED',
            'error' => 'Unrecognized request'
        ], 404);
    }

    /**
     * Handle Synapse user registration.
     *
     * @Route("/v2/users/{userID}", name="registerUser")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
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
        $user = $entityManager->getRepository(User::class)->findOneBy(['userid' => $userID, 'serverid' => $serverID]);

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
        $threepids = $this->getDoctrine()->getRepository(ThreePID::class)
                ->getUserThreePIDs($serverID, $user->getId());
        $externalids = $this->getDoctrine()->getRepository(ExternalId::class)
            ->getUserExternalIds($serverID, $user->getId());

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
        $user = new User();
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
    private function updateUser(string $serverID, string $userID, Request $request, User $user): JsonResponse
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
    private function upsertUser(string $serverID, string $userID, Request $request, User $user, int $status = 201): JsonResponse
    {
        $payload = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();
        $hasThreePIDs = false;
        $hasExternalIds = false;

        $user->setServerid($serverID);
        $user->setUserid($userID);
        $user->setDisplayname($payload->displayname);
        $user->setAdmin();
        $user->setPasswordpattern();

        // Process threepids.
        if (!empty($payload->threepids)){
            foreach ($payload->threepids as $pid) {
                $threepid = $entityManager->getRepository(ThreePID::class)
                        ->findOneBy(['serverid' => $serverID, 'userid' => $user->getId(), 'medium' => $pid->medium]);
                if (!$threepid) {
                    // New user, or existing user without any associated ThreePID.
                    $threepid = new ThreePID();
                    $threepid->setMedium($pid->medium);
                    $threepid->setAddress($pid->address);
                    $threepid->setServerid($serverID);

                    $user->addThreePID($threepid);
                    $threepid->setUserid($user);
                } else {
                    // Updating existing.
                    $threepid->setAddress($pid->address);
                }
                $entityManager->persist($threepid);
            }
            $hasThreePIDs = true;
        }

        // Process access tokens.
        $token = $entityManager->getRepository(Token::class)->findOneBy(['userid' => $user->getId()]);
        if (!$token) {
            // New user, or existing user without any associated Tokens.
            $token = new Token();
            $token->setAccesstoken($this->generateToken('access-token'));
            $token->setRefreshtoken($this->generateToken('refresh-token'));
            $token->setServerid($serverID);

            $user->addToken($token);
            $token->setUserid($user);
            $entityManager->persist($token);
        }

        // Process external ids.
        if (!empty($payload->external_ids)){
            foreach ($payload->external_ids as $eid) {
                $externalid = $entityManager->getRepository(ExternalId::class)
                        ->findOneBy(['serverid' => $serverID, 'userid' => $user->getId(), 'auth_provider' => $eid->auth_provider]);
                if (!$externalid) {
                    // New user, or existing user without any associated ExternalIds.
                    $externalid = new ExternalId();
                    $externalid->setAuthProvider($eid->auth_provider);
                    $externalid->setServerid($serverID);

                    $user->addExternalid($externalid);
                    $externalid->setUserid($user);
                }
                $externalid->setExternalId($this->generateExternalId($eid->external_id));
                $entityManager->persist($externalid);
            }
            $hasExternalIds = true;
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

        if ($hasThreePIDs) {
            $payload->threepids['validated_at'] = time();
            $payload->threepids['added_at'] = time();
            $responseObj->threepids = [$payload->threepids];
        }

        if ($hasExternalIds) {
            $payload->external_ids['validated_at'] = time();
            $payload->external_ids['added_at'] = time();
            $responseObj->threepids = [$payload->external_ids];
        }

        return new JsonResponse($responseObj, $status);
    }


    /**
     * Invite user into a room.
     *
     * @Route("/v1/join/{roomID}", name="inviteUser", methods={"POST"})
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function inviteUser(string $serverID, string $roomID, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $entityManager = $this->getDoctrine()->getManager();

        // Check room exists.
        $room = $entityManager->getRepository(Room::class)->findOneBy([
            'serverid' => $serverID,
            'roomid' => $roomID,
        ]);
        if (!$room) {
            return $this->getUnknownRoomResponse();
        }

        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['user_id']);
        if (!$check['status']) {
            return $check['message'];
        }

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'serverid' => $serverID,
            'userid' => $payload->user_id,
        ]);
        if (!$user) {
            return $this->getUnknownRoomResponse();
        }

        $membership = $entityManager->getRepository(RoomMember::class)->findOneBy([
            'serverid' => $serverID,
            'user' => $user,
            'room' => $room,
            'state' => null,
        ]);

        if ($membership) {
            if ($membership->getAccepted()) {
                // Already a member.
                return new JsonResponse((object) [
                    'errcode' => 'M_USER_EXISTS',
                    'error' => 'The invitee is already a member of the room'
                ], 403);
            }

            if ($membership->getBanned()) {
                return new JsonResponse((object) [
                    'errcode' => 'M_USER_IS_BANNED',
                    'error' => 'you cannot invite the user due to being banned from the group.'
                ], 403);
            }

            // Thenw aht!???
            $membership->setAccepted(true);
        } else {
            // Store the room member in the DB.
            $entityManager = $this->getDoctrine()->getManager();
            $roomMember = new RoomMember();

            $roomMember->setRoom($room);
            $roomMember->setUser($user);
            $roomMember->setAccepted(true);
            $roomMember->setBanned();
            $roomMember->setServerid($serverID);
        }

        $entityManager->persist($roomMember);
        $entityManager->flush();

        return new JsonResponse((object) [
            'room_id' => $roomID
        ], 200);
    }

    /**
     * Delete a room.
     *
     * @Route("/v2/rooms/{roomID}", methods={"DELETE"}, name="deleteRoom")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteRoom(string $serverID, string $roomID, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['DELETE'], $request, false);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $entityManager = $this->getDoctrine()->getManager();
        $room = $entityManager->getRepository(Room::class)->findOneBy([
            'serverid' => $serverID,
            'roomid' => $roomID,
        ]);

        if (!$room) {
            return $this->getUnknownRoomResponse();
        }

        $entityManager->remove($room);
        $entityManager->flush();

        return new JsonResponse((object) [
            'delete_id' => substr(hash('sha256', (date("Ymdhms"))), 0, 18)
        ], 200);
    }

    /**
     * Get a room detail.
     *
     * @Route("/v1/rooms/{roomID}", methods={"GET"}, name="roomInfo")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function roomInfo(string $serverID, string $roomID, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['GET'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $entityManager = $this->getDoctrine()->getManager();

        // Check room exists.
        $room = $entityManager->getRepository(Room::class)->findOneBy([
            'serverid' => $serverID,
            'roomid' => $roomID,
        ]);
        if (!$room) {
            return $this->getUnknownRoomResponse();
        }

        // Get all joined members.
        $members = $this->getDoctrine()
            ->getRepository(RoomMember::class)
            ->findBy(['room' => $room, 'serverid' => $serverID, 'state' => null]);
        $memberCount = count($members);

        return new JsonResponse((object) [
            'room_id' => $room->getRoomid(),
            'name' => $room->getName(),
            'canonical_alias' => $room->getRoomAlias(),
            'joined_members' => $memberCount,
            'creator' => $room->getCreator(),
            'avatar' => $room->getAvatar(),
            'topic' => $room->getTopic()
        ], 200);
    }
}
