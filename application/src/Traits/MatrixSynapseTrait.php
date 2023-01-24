<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Rooms;
use App\Entity\Roommembers;

trait MatrixSynapseTrait {

    /**
     * Check if user is invited.
     *
     * @param string $roomID
     * @param string $userID
     * @return JsonResponse|null
     */
    private function isUserInvited(string $roomID = null, string $userID = null): ?JsonResponse {
        $userCheck = $this->getRoomMember($roomID, $userID);
        if (!empty($userCheck)) {
            return new JsonResponse((object) [
                'errcode' => 'M_USER_EXISTS',
                'error' => 'The invitee is already a member of the room'
            ], 403);
        }
    }

    /**
     * Check if user is a group member.
     *
     * @param string $roomID
     * @param string $userID
     * @return object|null
     */
    private function validateRoomInviter(string $roomID, string $userID): ?object
    {
        if ($userID) {
            $userCheck = $this->getRoomMember($roomID, $userID);
            if (!empty($userCheck)) {
                return new JsonResponse((object) [
                    'errcode' => 'M_NOT_MEMBER',
                    'error' => 'You have to be a group member to be able to invite a user.'
                ], 403);
            }
        } else {
            return new JsonResponse((object) [
                'errcode' => 'M_NOT_MEMBER',
                'error' => 'You are not a group member yet.'
            ], 403);
        }
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
        return $entityManager->getRepository(Roommembers::class)->findOneBy(['roomid' => $roomID, 'userid' => $userID]);
    }

    /**
     * Check if user is banned from the group.
     *
     * @param string $roomID
     * @param string $userID
     * @return JsonResponse|null
     */
    private function isUserBanned(string $roomID, string $userID): ?JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $data = $entityManager->getRepository(Roommembers::class)->findOneBy(['roomid' => $roomID, 'userid' => $userID, 'banned' => true]);
        if (!empty($data)) {
            return new JsonResponse((object) [
                'errcode' => 'M_USER_IS_BANNED',
                'error' => 'you cannot invite the user due to being banned from the group.'
            ], 403);
        }
        return null;
    }

    /**
     * Check if room exists.
     *
     * @param string $roomID
     * @param bool $getRoom Whether or not to return room object.
     */
    private function roomExists(string $roomID, bool $getRoom = false) {
        if ($roomID) {
            // Check room exists.
            $room = $this->getRoom($roomID);
            if (empty($room)) {
                return  new JsonResponse((object) [
                    'errcode' => 'M_FORBIDDEN',
                    'error' => 'Unknown room'
                ], 403);
            }

            // Add "room" property if $getRoom is true.
            if ($getRoom) return ['room' => $room];
        }
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
}