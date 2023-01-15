<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiCheck;
use App\Entity\Rooms;

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

    /**
     * Create Matrix room.
     *
     * @Route("/createRoom", name="createRoom")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function createRoom(string $serverID, Request $request): JsonResponse {
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
        ],
                200);
    }

    /**
     * Update various room state components.
     *
     * @Route("/rooms/{roomID}/state/{stateType}", name="createRoom")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function roomState(string $serverID, string $roomID, string $stateType, Request $request): JsonResponse {
        // Check call auth.
        $authCheck = ApiCheck::checkAuth($request);
        if (!$authCheck['status']) {
            // Auth check failed, return error info.
            return $authCheck['message'];
        }

        // Check HTTP method is accepted.
        $method = $request->getMethod();
        $methodCheck = ApiCheck::checkMethod(['PUT'], $method);
        if (!$methodCheck['status']) {
            // Method check failed, return error info.
            return $methodCheck['message'];
        }

        // Check room exists.
        $room = $this->roomExists($roomID);
        if (empty($room)) {
            return new JsonResponse((object) [
                    'errcode' => 'M_FORBIDDEN',
                    'error' => 'Unknown room'
            ],
                    403);
        }

        $payload = json_decode($request->getContent());

        if ($stateType == 'm.room.topic') {
            $room->setTopic = $payload->topic;

        } elseif ($stateType == 'm.room.name') {
            // Update room name.
            $room->setName = $payload->name;

        } elseif ($stateType == 'm.room.avatar') {
            // Update room avatar.
            $room->setAvatar = $payload->url;
        } else {
            // Unknown state.
            return new JsonResponse((object) [
                    'errcode' => 'M_UNRECOGNIZED',
                    'error' => 'Unrecognized request'
            ],
                    404);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($room);
        $entityManager->flush();

        // Create a mock event ID. This isn't the way Synapse does it (I think), but it's a good enough approximation.
        // This ID doesn't change if the seed data is the same.
        $eventID = substr(hash('sha256', ($serverID . $roomID . $stateType)), 0, 44);

        return new JsonResponse((object) [
                'event_id' => $eventID,
        ],
                200);
    }

    /**
     * Check if room exists.
     *
     * @param string $roomID
     * @return object
     */
    private function roomExists(string $roomID): object
    {
        $entityManager = $this->getDoctrine()->getManager();

        return $entityManager->getRepository(Rooms::class)->findOneBy(['roomid' => $roomID]);

    }

}
