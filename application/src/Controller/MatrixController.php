<?php

namespace App\Controller;

use App\Entity\Passwords;
use App\Entity\Rooms;
use App\Entity\Roommembers;
use App\Entity\Tokens;
use App\Entity\Users;
use App\Traits\GeneralTrait;
use App\Traits\MatrixSynapseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller to serve a mock of the Matrix API.
 *
 * @Route("/{serverID}/_matrix/client/r0")
 */
class MatrixController extends AbstractController {

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
     * Login a user.
     *
     * @Route("/login", name="login")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function login(string $serverID, Request $request): JsonResponse {
        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['identifier', 'type']);
        if (!$check['status']) {
            return $check['message'];
        }

        // 1. Check if type is in the $palyload->identifier.
        // 2. Return loginidentifier property if no error.
        $check = $this->loginIdentifierType($payload->identifier);
        if (!$check['status']) {
            return $check['message'];
        }

        if ($payload->type === 'm.login.password') {
            if (!isset($payload->password)) {
                return new JsonResponse((object) [
                    'errcode' => 'M_UNKNOWN',
                    'error' => '"Password" is required.'
                ], 400);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(Users::class)->findOneBy($check['loginidentifier']);
            $password = $entityManager->getRepository(Passwords::class)->findOneBy([
                'password' => $this->hashPassword($payload->password, $user->getPasswordpattern())['token'],
                'userid' => $user->getId()
            ]);

            // Check if user with its password is found.
            if ($user && $password) {
                $token = $entityManager->getRepository(Tokens::class)->findOneBy(['userid' => $user->getId()]);

                // Assign client server id if the server id is NULL.
                if (is_null($token->getServerid())) {
                    $token->setServerid($serverID);
                }

                // Check if refresh_token is in the body and set to true,
                // then generate a new refresh_token.
                if (isset($payload->refresh_token) && $payload->refresh_token === true) {
                    $token->setRefreshToken($this->generateToken('refresh-token'));
                    $entityManager->persist($token);
                    $entityManager->flush();

                    $response['refresh_token'] = $token->getRefreshToken();
                }
                $response['user_id'] = $user->getUserid();
                $response['access_token'] = $token->getAccessToken();
                $response['home_server'] = $request->getHost();

                return new JsonResponse((object) $response, 200);
            } else {
                return new JsonResponse((object) [
                    'errcode' => 'M_UNKNOWN',
                    'error' => 'Invalid login credentials'
                ], 403);
            }
        }

        return new JsonResponse((object) [
            'errcode' => 'M_UNKNOWN',
            'error' => 'Bad login type.'
        ], 403);
    }

    /**
     * Refresh the tokens.
     *
     * @Route("/refresh", name="refresh")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(string $serverID, Request $request): JsonResponse {
        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['refresh_token']);
        if (!$check['status']) {
            return $check['message'];
        }

        $tokens = $this->getToken($serverID, $payload->refresh_token);
        if (!empty($tokens)) {
            $tokens->setAccesstoken($this->generateToken('access-token'));
            $tokens->setRefreshtoken($this->generateToken('refresh-token'));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($tokens);
            $entityManager->flush();

            return new JsonResponse((object)[
                'access_token' => $tokens->getAccesstoken(),
                'refresh_token' => $tokens->getRefreshtoken()
            ], 200);
        } else {
            return new JsonResponse((object)[
                'errcode' => 'M_UNKNOWN_TOKEN',
                'refresh_token' => 'Invalid token'
            ], 401);
        }
    }

    /**
     * Create Matrix room.
     *
     * @Route("/createRoom", name="createRoom")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function createRoom(string $serverID, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $payload = json_decode($request->getContent());
        $roomName = $payload->name;
        $host = $request->getHost();

        // Create a mock room ID. This isn't the way Synapse does it (I think), but it's a good enough approximation.
        $roomID = '!'. substr(hash('sha256', ($serverID . $roomName . (string)time())), 0, 18) . ':' . $host;

        // Store the room in the DB.
        $entityManager = $this->getDoctrine()->getManager();
        $room = new Rooms();

        $room->setRoomid($roomID);
        $room->setName($payload->name);
        $room->setTopic($payload->topic);

        $entityManager->persist($room);
        $entityManager->flush();

        return new JsonResponse((object) [
            'room_id' => $roomID,
        ], 200);
    }

    /**
     * Create Matrix room.
     *
     * @Route("/rooms/{roomID}/kick", name="kick")
     * @param Request $request
     * @return JsonResponse
     */
    public function kick(string $roomID, Request $request) : JsonResponse {
        // Check room exists.
        $roomCheck = $this->roomExists($roomID);
        if (!$roomCheck['status']) {
            return $roomCheck['message'];
        }

        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['reason', 'user_id']);
        if (!$check['status']) {
            return $check['message'];
        }

        $roommembers = $this->getRoomMember($roomID, $payload->user_id);
        if (empty($roommembers)) {
            return new JsonResponse((object) [
                'errcode' => 'M_NOT_MEMBER',
                'error' => 'The target user_id is not a room member.'
            ], 403);
        }

        // Update th membership.
        $roommembers->setState('leave');
        $roommembers->setReason($payload->reason);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($roommembers);
        $entityManager->flush();
        return new JsonResponse((object)[]);
    }

    /**
     * Update various room state components.
     *
     * @Route("/rooms/{roomID}/state/{eventType}", name="roomState")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function roomState(string $serverID, string $roomID, string $eventType, Request $request): JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['PUT'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        // Check room exists. If exists, "room" property is added.
        $roomCheck = $this->roomExists($roomID, true);
        if (!$roomCheck['status']) {
            return $roomCheck['message'];
        }
        $room = $roomCheck['room'];
        $payload = json_decode($request->getContent());

        if ($eventType == 'm.room.topic') {
            $room->setTopic($payload->topic);

        } elseif ($eventType == 'm.room.name') {
            // Update room name.
            $room->setName($payload->name);

        } elseif ($eventType == 'm.room.avatar') {
            // Update room avatar.
            $room->setAvatar($payload->url);
        } else {
            // Unknown state.
            return new JsonResponse((object) [
                'errcode' => 'M_UNRECOGNIZED',
                'error' => 'Unrecognized request'
            ], 404);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($room);
        $entityManager->flush();

        // Create a mock event ID. This isn't the way Synapse does it (I think), but it's a good enough approximation.
        // This ID doesn't change if the seed data is the same.
        $eventID = substr(hash('sha256', ($serverID . $roomID . $eventType)), 0, 44);

        return new JsonResponse((object) [
            'event_id' => $eventID,
        ], 200);
    }

    /**
     * Invite user into a room.
     *
     * @Route("/rooms/{roomID}/invite", name="inviteUser")
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
        $this->roomExists($roomID);

        $payload = json_decode($request->getContent());
        $userID = $payload->userid;

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

        // Check if "currentuserid" is sent with the body.
        if (!isset($payload->currentuserid)) {
            return new JsonResponse((object) [
                'errcode' => 'M_BAD_JSON',
                'message' => '"currentuserid" has not been sent as part of the body'
            ], 400);
        }

        // Check if the inviter is a member of the group.
        $this->validateRoomInviter($roomID, $payload->currentuserid);

        // Store the room member in the DB.
        $entityManager = $this->getDoctrine()->getManager();
        $roomMember = new Roommembers();

        $roomMember->setRoomid($roomID);
        $roomMember->setReason($payload->reason);
        $roomMember->setUserid($userID);
        $roomMember->setAccepted();

        $entityManager->persist($roomMember);
        $entityManager->flush();

        return new JsonResponse((object) [
            'message' => 'The user has been invited to join the room'
        ], 200);
    }
}
