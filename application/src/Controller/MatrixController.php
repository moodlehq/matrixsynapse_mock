<?php

namespace App\Controller;

use App\Entity\Password;
use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\Token;
use App\Entity\User;
use App\Service\Filter;
use App\Traits\GeneralTrait;
use App\Traits\MatrixSynapseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller to serve a mock of the Matrix API.
 *
 * @Route("/{serverID}/_matrix/client")
 */
class MatrixController extends AbstractController {
    use GeneralTrait;
    use MatrixSynapseTrait;

    /**
     * @Route("/versions", name="versions")
     */
    public function versions(): JsonResponse
    {
        return new JsonResponse((object) [
            "versions" => [
                "r0.0.1",
                "r0.1.0",
                "r0.2.0",
                "r0.3.0",
                "r0.4.0",
                "r0.5.0",
                "r0.6.0",
                "r0.6.1",
                "v1.1",
                "v1.2",
                "v1.3",
                "v1.4",
                "v1.5",
                "v1.6",
            ],
            "unstable_features"=> [
                "org.matrix.label_based_filtering" => true,
                "org.matrix.e2e_cross_signing" => true,
                "org.matrix.msc2432" => true,
                "uk.half-shot.msc2666.query_mutual_rooms" => true,
                "io.element.e2ee_forced.public" => false,
                "io.element.e2ee_forced.private" => false,
                "io.element.e2ee_forced.trusted_private" => false,
                "org.matrix.msc3026.busy_presence" => false,
                "org.matrix.msc2285.stable" => true,
                "org.matrix.msc3827.stable" => true,
                "org.matrix.msc2716" => false,
                "org.matrix.msc3440.stable" => true,
                "org.matrix.msc3771" => true,
                "org.matrix.msc3773" => false,
                "fi.mau.msc2815" => false,
                "fi.mau.msc2659.stable" => true,
                "org.matrix.msc3882" => false,
                "org.matrix.msc3881" => false,
                "org.matrix.msc3874" => false,
                "org.matrix.msc3886" => false,
                "org.matrix.msc3912" => false,
                "org.matrix.msc3952_intentional_mentions" => false,
                "org.matrix.msc3981" => false,
                "org.matrix.msc3391" => false,
            ]
        ]);
    }

    /**
     * @Route("/r0")
     * @Route("/v1")
     * @Route("/v2")
     * @Route("/v3")
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
     * @Route("/r0/login", name="login")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function login(string $serverID, Request $request): JsonResponse {
        // 1. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request, false);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

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
                    'errcode' => 'M_INVALID_PARAM',
                    'error' => 'Bad parameter: password'
                ], 400);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(User::class)->findOneBy($check['loginidentifier']);

            $passwordpatter = $user ? $user->getPasswordpattern() : null;
            $userid = $user ? $user->getId() : null;
            $password = $entityManager->getRepository(Password::class)->findOneBy([
                'password' => $this->hashPassword($payload->password, $passwordpatter)['token'],
                'userid' => $userid
            ]);

            // Check if user with its password is found.
            if ($user && $password) {
                $token = $entityManager->getRepository(Token::class)->findOneBy(['userid' => $user->getId()]);

                // Assign client server id if the server id is NULL.
                if (is_null($token->getServerid())) {
                    $token->setServerid($serverID);
                }

                // Check if refresh_token is in the body and set to true,
                // then generate a new refresh_token.
                if (isset($payload->refresh_token) && $payload->refresh_token === true) {
                    $token->setRefreshToken($this->generateToken('refresh-token'));
                    $response['refresh_token'] = $token->getRefreshToken();
                }

                $token->setAccessToken($this->generateToken());
                $entityManager->persist($token);
                $entityManager->flush();

                $response['user_id'] = $user->getUserid();
                $response['access_token'] = $token->getAccesstoken();
                // $response['refresh_token'] = $token->getRefreshtoken();
                $response['home_server'] = $request->getHost();

                return new JsonResponse((object) $response, 200);
            } else {
                return new JsonResponse((object) [
                    'errcode' => 'M_FORBIDDEN',
                    'error' => 'Invalid username or password'
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
     * @Route("/r0/refresh", name="refresh")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(string $serverID, Request $request):JsonResponse {
        // 1. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request, false);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $payload = json_decode($request->getContent());
        $check = $this->validateRequest((array)$payload, ['refresh_token']);
        if (!$check['status']) {
            return $check['message'];
        }

        $tokens = $this->getToken($serverID, $payload->refresh_token);
        if (!empty($tokens)) {
            $tokens->setAccesstoken($this->generateToken('access-token'));
            $tokens->setRefreshtoken($this->generateToken('refresh-token'));
            $tokens->setServerid($serverID);

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
     * @Route("/r0/createRoom")
     * @Route("/v3/createRoom")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function createRoom(string $serverID, Request $request):JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['POST'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }

        $payload = json_decode($request->getContent());
        $roomName = $payload->name ?? rand();
        $host = $request->getHost();

        // Create a mock room ID. This isn't the way Synapse does it (I think), but it's a good enough approximation.
        $roomID = '!'. substr(hash('sha256', ($serverID . $roomName . (string)time())), 0, 18) . ':' . $host;
        $response['room_id'] = $roomID;

        // Store the room in the DB.
        $entityManager = $this->getDoctrine()->getManager();
        $room = new Room();

        $room->setRoomid($roomID);
        $room->setName($roomName);
        $room->setTopic($payload->topic ?? null);
        $room->setServerid($serverID);
        $room->setCreator($accessCheck['user_id']);
        $room->setSpace(null);
        if (isset($payload->room_alias_name) && !empty($payload->room_alias_name)) {
            $room_alias = "#{$payload->room_alias_name}:{$host}";
            $check_alias = $entityManager->getRepository(Room::class)->findOneBy(['roomalias' => $room_alias]);
            if (empty($check_alias)) {
                $room->setRoomAlias($room_alias);
                $response['room_alias'] = $room_alias;
            } else {
                return new JsonResponse((object) [
                    'errcode' => 'M_ROOM_IN_USE',
                    'error' => 'Room alias already taken'
                ], 400);
            }
        }

        $entityManager->persist($room);
        $entityManager->flush();

        return new JsonResponse((object) $response, 200);
    }

    /**
     * Sync Matrix room.
     * Sync room according to the API:
     * https://spec.matrix.org/v1.8/client-server-api/#get_matrixclientv3sync
     *
     * @Route("/v3/sync")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(string $serverID, Request $request):JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['GET'], $request);
        if (!$accessCheck['status']) {
            return $accessCheck['message'];
        }
        // Params are being queried via a filter in the request.
        $filter = Filter::fromRequest($request);

        $roomSearch = [
            'serverid' => $serverID,
        ];

        if ($roomIds = $filter->getRoomFilter()->getRoomIds()) {
            $roomSearch['roomid'] = $roomIds;
        }

        // Get a list of rooms.
        $entityManager = $this->getDoctrine()->getManager();
        $roomList = $entityManager->getRepository(Room::class)->findBy($roomSearch);

        // Get room state data for each room.
        $roomData = [];
        foreach ($roomList as $room) {
            $roomData[$room->getRoomId()] = $room->getRoomState($request);
        }

        return new JsonResponse((object) [
            'rooms' => [
                'join' => $roomData,
            ],
        ]);
    }

    /**
     * Create Matrix room.
     *
     * @Route("/r0/rooms/{roomID}/kick")
     * @Route("/v3/rooms/{roomID}/kick")
     * @param Request $request
     * @return JsonResponse
     */
    public function kick(
        string $serverID,
        string $roomID,
        Request $request,
    ): JsonResponse {
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
            'room' => $room,
            'user' => $user,
        ]);

        if (empty($membership)) {
            return new JsonResponse((object) [
                'errcode' => 'M_NOT_MEMBER',
                'error' => 'The target user_id is not a room member.'
            ], 403);
        }

        // Update the membership.
        $membership->setState('leave');
        $membership->setReason($payload->reason ?? null);

        $entityManager->persist($membership);
        $entityManager->flush();
        return new JsonResponse((object)[]);
    }

    /**
     * Update various room state components.
     *
     * @Route("/r0/rooms/{roomID}/state/{eventType}")
     * @Route("/r0/rooms/{roomID}/state/{eventType}/")
     * @Route("/v3/rooms/{roomID}/state/{eventType}")
     * @Route("/v3/rooms/{roomID}/state/{eventType}/")
     * @param string $serverID
     * @param string $eventType
     * @param Request $request
     * @return JsonResponse
     */
    public function roomState(string $serverID, string $roomID, string $eventType, Request $request):JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.

        $accessCheck = $this->authHttpCheck(['PUT', 'GET'], $request);
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

        $method = $request->getMethod();

        // PUT requests.
        if ($method == 'PUT') {

            $payload = json_decode($request->getContent());

            if ($eventType == 'm.room.topic') {
                $check = $this->validateRequest((array)$payload, ['topic']);
                if (!$check['status']) {
                    return $check['message'];
                }
                $room->setTopic($payload->topic);

            } elseif ($eventType == 'm.room.name') {
                // Update room name.
                $check = $this->validateRequest((array)$payload, ['name']);
                if (!$check['status']) {
                    return $check['message'];
                }
                $room->setName($payload->name);

            } elseif ($eventType == 'm.room.avatar') {
                // Update room avatar.
                $check = $this->validateRequest((array)$payload, ['url']);
                if (!$check['status']) {
                    return $check['message'];
                }
                $room->setAvatar($payload->url);
            } elseif ($eventType == 'm.room.power_levels') {
                // Room power level api call validation to check if the users are passed in the payload to update the power level.
                $check = $this->validateRequest((array)$payload, ['users']);
                if (!$check['status']) {
                    return $check['message'];
                }
                // Update power levels for each room member.
                foreach ($payload->users as $userid => $level) {
                    if (!empty($level)) {
                        $user = $entityManager->getRepository(User::class)->findOneBy([
                            'serverid' => $serverID,
                            'userid' => $userid,
                        ]);
                        if (!empty($user)) {
                            $membership = $entityManager->getRepository(RoomMember::class)->findOneBy([
                                'serverid' => $serverID,
                                'room' => $room,
                                'user' => $user,
                            ]);
                            if(!empty($membership)) {
                                $membership->setPowerLevel($level);
                            }
                        }
                    }
                }
            } else {
                // Unknown state.
                return new JsonResponse((object) [
                    'errcode' => 'M_UNRECOGNIZED',
                    'error' => 'Unrecognized request'
                ], 404);
            }

        // GET requests.
        } else if ($method == 'GET') {

            if ($eventType == 'm.room.power_levels') {

                $powerLevels = $room->getPowerLevels($request);
                return new JsonResponse((object) $powerLevels, 200);

            } else {
                // Unknown state.
                return new JsonResponse((object) [
                    'errcode' => 'M_UNRECOGNIZED',
                    'error' => 'Unrecognized request'
                ], 404);
            }
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
     * Gets all joined members of a group.
     *
     * @Route("/r0/rooms/{roomID}/joined_members")
     * @Route("/v3/rooms/{roomID}/joined_members")
     * @param string $serverID
     * @param string $roomID
     * @param Request $request
     * @return JsonResponse
     */
    public function getJoinedMembers(string $serverID, string $roomID, Request $request):JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.
        $accessCheck = $this->authHttpCheck(['GET'], $request);
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

        // Get all joined members.
        $members = $this->getDoctrine()
            ->getRepository(RoomMember::class)
            ->findBy(['room' => $room, 'serverid' => $serverID, 'state' => null]);

        $memberinfo = [];
        foreach ($members as $member) {
            $memberinfo[$member->getUser()->getUserid()] = (object) [
                'avatar_url' => $member->getUser()->getAvatarurl(),
                'display_name' => $member->getUser()->getDisplayname(),
            ];
        }

        return new JsonResponse((object) [
            'joined' => (object) $memberinfo,
        ], 200);
    }

    /**
     * Verify the room space state api.
     *
     * @Route("/v3/rooms/{roomID}/state/{eventType}/{stateKey}")
     * @param string $serverID
     * @param string $eventType
     * @param string $stateKey
     * @param Request $request
     * @return JsonResponse
     */
    public function roomSpace(string $serverID, string $roomID, string $eventType, string $stateKey, Request $request):JsonResponse {
        // 1. Check call auth.
        // 2. Check HTTP method is accepted.

        $accessCheck = $this->authHttpCheck(['PUT'], $request);
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

        // Check space exists.
        $space = $entityManager->getRepository(Room::class)->findOneBy([
            'serverid' => $serverID,
            'roomid' => $stateKey,
        ]);
        if (!$space) {
            return $this->getUnknownRoomResponse();
        }

        $payload = json_decode($request->getContent());

        if ($eventType == 'm.space.child') {
            $check = $this->validateRequest((array)$payload, ['via']);
            if (!$check['status']) {
                return $check['message'];
            }
            $room->setSpace($stateKey);

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
}
