<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\Tokens;
use App\Entity\Users;

trait MatrixSynapseTrait {
    private function getUnknownRoomResponse(): JsonResponse
    {
        return new JsonResponse((object) [
            'errcode' => 'M_FORBIDDEN',
            'error' => 'Unknown room'
        ], 403);
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
}
