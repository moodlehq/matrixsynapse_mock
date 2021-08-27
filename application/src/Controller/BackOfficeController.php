<?php

namespace App\Controller;

use App\Component\HttpFoundation\MeetingSummaryResponse;
use App\Component\HttpFoundation\XmlResponse;
use App\Entity\Attendee;
use App\Entity\Meeting;
use App\Entity\Recording;
use Firebase\JWT\JWT;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use stdClass;

/**
 * @Route ("/{serverID}/backoffice")
 */
class BackOfficeController extends DataController
{

    /**
     * Default shared secret for BBB.
     *
     * @var string
     */
    const DEFAULT_SHARED_SECRET = '8cd8ef52e8e101574e400365b55e11a6';

    /**
     * @Route("/createMeeting", name="backOfficeMeetingCreate")
     */
    public function backOfficeMeetingCreate(string $serverID, Request $request): XmlResponse
    {
        $meeting = new Meeting();
        $meeting->setServerID($serverID);
        $meeting->setMeetingId($request->query->get('meetingID'));
        $meeting->setAttendeePW($request->query->get('attendeePW'));
        $meeting->setModeratorPW($request->query->get('moderatorPW'));
        $meeting->setRunning(true);

        if ($request->query->has('name')) {
            $meeting->setMeetingName($request->query->get('name'));
        } else if ($request->query->has('meetingName')) {
            $meeting->setMeetingName($request->query->get('meetingName'));
        }
        $meeting->setMetadata($this->getMetadataFromRequest($request));

        if ($request->query->has('maxUsers')) {
            $meeting->setMaxUsers($request->query->get('maxUsers'));
        }

        if ($request->query->has('voiceBridge')) {
            if ($voiceBridge = $request->query->get('voiceBridge')) {
                $meeting->setVoiceBridge($voiceBridge);
            }
        }

        if ($request->query->has('dialNumber')) {
            if ($dialNumber = $request->query->get('dialNumber')) {
                $meeting->setDialNumber($dialNumber);
            }
        }

        $entityManager = $this->getDoctrine()->getManager();

        if ($request->query->has('moderators')) {
            $moderatorCount = $request->query->get('moderators');
            for ($i = 1; $i <= $moderatorCount; $i++) {
                $attendee = new Attendee();
                $attendee->setUserId("Moderator {$i}");
                $attendee->setFullName("Moderator {$i}");
                $attendee->setRole(Attendee::ROLE_MODERATOR);
                $attendee->setIsPresenter(true);

                $entityManager->persist($attendee);
                $meeting->addAttendee($attendee);
            }
        }

        if ($request->query->has('participants')) {
            $participantCount = $request->query->get('participants');
            for ($i = 1; $i <= $participantCount; $i++) {
                $attendee = new Attendee();
                $attendee->setUserId("Moderator {$i}");
                $attendee->setFullName("Moderator {$i}");

                $entityManager->persist($attendee);
                $meeting->addAttendee($attendee);
            }
        }

        $entityManager->persist($meeting);
        $entityManager->flush();

        return new MeetingSummaryResponse($meeting);
    }

    /**
     * @Route("/createRecording", name="backOfficeRecordingCreate")
     */
    public function backOfficeRecordingCreate(string $serverID, Request $request): XmlResponse
    {
        $meetingID = $request->query->get('meetingID');
        $meeting = $this->findRoomConfiguration($serverID, $meetingID);
        if (empty($meeting)) {
            return $this->handleRoomNotFound($meetingID);
        }

        $recording = new Recording();
        $recording->setServerID($serverID);
        $meeting->addRecording($recording);

        if ($request->query->has('recordID')) {
            $recording->setRecordID($request->query->get('recordID'));
        }

        if ($request->query->has('published')) {
            $recording->setPublished(!empty($request->query->get('published')));
        }

        if ($request->query->has('protect')) {
            $recording->setProtected(!empty($request->query->get('protect')));
        }

        if ($request->query->has('isBreakout')) {
            $recording->setIsBreakout(!empty($request->query->get('isBreakout')));
        }

        $recording->setMetadata($this->getRecordingMetadataFromRequest($request));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($recording);
        $entityManager->persist($meeting);
        $entityManager->flush();

        return new XmlResponse((object) [
            'recordID' => $recording->getRecordID(),
        ]);
    }

    /**
     * @Route("/recordings", name="backOfficeListRecordings")
     */
    public function backOfficeListRecordings(string $serverID): XmlResponse
    {
        $entities = $this->getDoctrine()
            ->getRepository(Recording::class)
            ->findBy([
                'serverID' => $serverID,
            ]);

        $items = array_map(function($entity): array {
            return $entity->getRecordingInfo();
        }, $entities);

        return new XmlResponse((object) ['recordings' => $items]);
    }

    /**
     * @Route("/meetings", name="backOfficeListMeetings")
     */
    public function backOfficeListMeetings(string $serverID): XmlResponse
    {
        $meetingEntities = $this->getDoctrine()
            ->getRepository(Meeting::class)
            ->findBy([
                'serverID' => $serverID,
            ]);

        $meetings = array_map(function($meeting): stdClass {
            return $meeting->getMeetingInfo();
        }, $meetingEntities);

        return new XmlResponse((object) ['meetings' => $meetings]);
    }

    /**
     * @Route("/sendNotifications")
     */
    public function sendNotifications(string $serverID): XmlResponse
    {
        $entities = $this->getDoctrine()
            ->getRepository(Recording::class)
            ->findBy([
                'serverID' => $serverID,
                'brokerNotified' => false,
            ]);

        $client = HttpClient::create();
        $entityManager = $this->getDoctrine()->getManager();

        $notified = [];
        foreach ($entities as $entity) {
            $url = htmlspecialchars_decode($entity->getMetadataValue('bn-recording-ready-url'));
            $jwtparams = JWT::encode((object) [
                'record_id' => $entity->getRecordID(),
            ], self::DEFAULT_SHARED_SECRET, 'HS256');

            $response = $client->request('GET', $url, [
                'query' => [
                    'signed_parameters' => $jwtparams,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200 || $statusCode === 202) {
                $notified[] = $entity->getRecordingInfo();
                $entity->setBrokerNotified(true);
                $entityManager->persist($entity);
            }
        }

        $entityManager->flush();

        return new XmlResponse((object) [
            'recordings' => [
                'forcexmlarraytype' => 'recording',
                'array' => $notified,
            ],
        ]);
    }

    /**
     * @Route("/reset", name="backOfficeReset")
     */
    public function backOfficeReset(string $serverID): XmlResponse
    {
        $entities = [
            Attendee::class,
            Recording::class,
            Meeting::class,
        ];

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($entities as $entityClass) {
            $entities = $this->getDoctrine()
                ->getRepository($entityClass)
                ->findBy(['serverID' => $serverID]);
            foreach ($entities as $entity) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }

        return new XmlResponse((object) ['reset' => true]);
    }
}
