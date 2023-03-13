<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Rooms;
use App\Entity\Roommembers;
use App\Entity\Tokens;
use App\Entity\Users;

trait MatrixSynapseTrait {

    /**
     * Check if user is invited.
     *
     * @param string $roomID
     * @param string $userID
     * @return array
     */
    private function isUserInvited(string $roomID = null, string $userID = null): ?array {
        $userCheck = $this->getRoomMember($roomID, $userID);
        if (!empty($userCheck)) {
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                'errcode' => 'M_USER_EXISTS',
                'error' => 'The invitee is already a member of the room'
            ], 403);

            return $response;
        }

        return $this->ok();
    }

    /**
     * Check if user is a group member.
     *
     * @param string $roomID
     * @param string $userID
     * @return array
     */
    private function validateRoomInviter(string $roomID, string $userID): ?array
    {
        if ($userID) {
            $userCheck = $this->getRoomMember($roomID, $userID);
            if (!empty($userCheck)) {
                $response['status'] = false;
                $response['message'] = new JsonResponse((object) [
                    'errcode' => 'M_NOT_MEMBER',
                    'error' => 'You have to be a group member to be able to invite a user.'
                ], 403);

                return $response;
            }
        } else {
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                'errcode' => 'M_NOT_MEMBER',
                'error' => 'You are not a group member yet.'
            ], 403);

            return $response;
        }

        return $this->ok();
    }

    /**
     * Get a room member.
     *
     * @param string $roomID
     * @param string $userID
     * @return object|null
     */
    private function getRoomMember(string $roomID, string $userID): ?object
    {
        $entityManager = $this->getDoctrine()->getManager();
        return $entityManager->getRepository(Roommembers::class)->findOneBy(['roomid' => $roomID, 'userid' => $userID, 'state' => null]);
    }

    /**
     * Check if user is banned from the group.
     *
     * @param string $roomID
     * @param string $userID
     * @return array
     */
    private function isUserBanned(string $roomID, string $userID): ?array
    {
        $entityManager = $this->getDoctrine()->getManager();
        $data = $entityManager->getRepository(Roommembers::class)->findOneBy(['roomid' => $roomID, 'userid' => $userID, 'banned' => true]);
        if (!empty($data)) {
            $response['status'] = false;
            $response['message'] = new JsonResponse((object) [
                'errcode' => 'M_USER_IS_BANNED',
                'error' => 'you cannot invite the user due to being banned from the group.'
            ], 403);

            return $response;
        }

        return $this->ok();
    }

    /**
     * Check if room exists.
     *
     * @param string $roomID
     * @param bool $getRoom Whether or not to return room object.
     */
    private function roomExists(string $roomID = null, bool $getRoom = false) {
        if ($roomID) {
            // Check room exists.
            $room = $this->getRoom($roomID);
            if (empty($room)) {
                $response['status'] = false;
                $response['message'] = new JsonResponse((object) [
                    'errcode' => 'M_FORBIDDEN',
                    'error' => 'Unknown room'
                ], 403);

                return $response;
            }

            // Add "room" property if $getRoom is true.
            if ($getRoom) return [
                'status' => true, 'room' => $room
            ];
        }

        return $this->ok();
    }

    /**
     * Get a room.
     *
     * @param string $roomID
     * @return object|null
     */
    private function getRoom(string $roomID): ?object
    {
        $entityManager = $this->getDoctrine()->getManager();
        return $entityManager->getRepository(Rooms::class)->findOneBy(['roomid' => $roomID]);
    }

    /**
     * Get user token.
     *
     * @param string $serverID
     * @param string $refreshToken
     * @return object|null
     */
    private function getToken(string $serverID, string $refreshToken): ?object
    {
        $entityManager = $this->getDoctrine()->getManager();
        return $entityManager->getRepository(Tokens::class)->findOneBy([
            'serverid' => $serverID,
            'refreshtoken' => $refreshToken
        ]);
    }

    /**
     * Get a user.
     *
     * @param string $userID
     * @return object|null
     */
    private function getOneUser(string $userID): ?object
    {
        $entityManager = $this->getDoctrine()->getManager();
        return $entityManager->getRepository(Users::class)->findOneBy([
            'userid' => $userID
        ]);
    }

    /**
     * Return array of true status.
     *
     * @return array
     */
    private function ok() : array {
        return ['status' => true];
    }
}