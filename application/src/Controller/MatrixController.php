<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Rooms;
use App\Entity\Roommembers;
use App\Traits\GeneralTrait;
use App\Traits\MatrixSynapseTrait;

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
        $accessCheck = $this->authHttpCheck('POST', $request);
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
        $accessCheck = $this->authHttpCheck('PUT', $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        // 3. Check room exists. If exists, "room" property is added.
        $roomCheck = $this->roomExists($roomID, true);
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
        $accessCheck = $this->authHttpCheck('POST', $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        // Check if room exists.
        $this->roomExists($roomID);

        $payload = json_decode($request->getContent());
        $userID = $payload->userid;

        // Check if the user has already been invited.
        $this->isUserInvited($roomID, $userID);

        // Check if the user is banned from the group.
        $this->isUserBanned($roomID, $userID);

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
