<?php

namespace App\Controller;

use App\Component\HttpFoundation\ErrorResponse;
use App\Component\HttpFoundation\MeetingInfoResponse;
use App\Component\HttpFoundation\MeetingSummaryResponse;
use App\Component\HttpFoundation\MessageResponse;
use App\Component\HttpFoundation\XmlResponse;
use App\Entity\Attendee;
use App\Entity\Meeting;
use App\Entity\Recording;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller to serve a mock of the BigBlueButton API.
 *
 * @Route("/{serverID}/api")
 */
class ApiController extends DataController
{
    /**
     * @Route("", name="status")
     */
    public function status(): XmlResponse
    {
        return new XmlResponse((object) [
            'version' => 0.9,
        ]);
    }

    /**
     * @Route("/getMeetingInfo", name="meetingInfo")
     */
    public function meetingInfo(string $serverID, Request $request): XmlResponse
    {
        $meetingID = $request->query->get('meetingID');
        $meeting = $this->findRoomConfiguration($serverID, $meetingID);
        if (empty($meeting)) {
            return $this->handleRoomNotFound($meetingID);
        }

        return new MeetingInfoResponse($meeting);
    }

    /**
     * @Route("/end", name="meetingEnd")
     */
    public function meetingEnd(string $serverID, Request $request): XmlResponse
    {
        $meetingID = $request->query->get('meetingID');
        $meeting = $this->findRoomConfiguration($serverID, $meetingID);
        if (empty($meeting)) {
            return $this->handleRoomNotFound($meetingID);
        }

        if (!$meeting->getRunning()) {
            // The BBB sends an error meeting not found when the meeting is already ended.
            return $this->handleRoomNotFound($meetingID);
        }

        if (!$meeting->checkModeratorPW($request->query->get('password'))) {
            return $this->handleAccessDenied();
        }

        $entityManager = $this->getDoctrine()->getManager();
        // Remove all attendees and set running to false.
        foreach($meeting->getAttendees() as $attendee) {
            $meeting->removeAttendee($attendee);
        }
        $meeting->setRunning(false);
        $entityManager->persist($meeting);
        $entityManager->flush();

        return new MessageResponse(
            'sentEndMeetingRequest',
            'A request to end the meeting was sent. ' .
            'Please wait a few seconds, and then use the getMeetingInfo ' .
            'or isMeetingRunning API calls to verify that it was ended.'
        );
    }

    const LOCK_SETTINGS_DATA = [
        'disableCam',
        'disableMic',
        'disablePrivateChat',
        'disablePublicChat',
        'disableNote',
        'lockedLayout',
        'hideUserList',
        'lockOnJoin',
        'lockOnJoinConfigurable'
    ];

    /**
     * @Route("/create", name="meetingCreate")
     */
    public function meetingCreate(string $serverID, Request $request): XmlResponse
    {
        // We check if the meeting does not already exists.
        $meetingID = $request->query->get('meetingID');
        $meeting = $this->findRoomConfiguration($serverID, $meetingID);

        if (empty($meeting)) {
            $meeting = new Meeting();
            $meeting->setMeetingId($request->query->get('meetingID'));
            $meeting->setAttendeePW($request->query->get('attendeePW'));
            $meeting->setModeratorPW($request->query->get('moderatorPW'));
            $meeting->setMeetingName($request->query->get('name'));
            $meeting->setServerID($serverID);

            if ($request->query->has('voiceBridge')) {
                $meeting->setVoiceBridge($request->query->get('voiceBridge'));
            }

            if ($request->query->has('dialNumber')) {
                $meeting->setDialNumber($request->query->get('dialNumber'));
            }
            $meeting->setMetadata($this->getMetadataFromRequest($request));

            // Lock settings.
            foreach (self::LOCK_SETTINGS_DATA as $lockName) {
                $lockSettingName = 'lockSettings' . ucfirst($lockName);
                if ($request->query->has($lockSettingName)) {
                    $meeting->setLockSetting($lockName, $request->query->get($lockSettingName));
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($meeting);
            $entityManager->flush();
        }

        return new MeetingSummaryResponse($meeting);
    }

    /**
     * @Route("/join", name="meetingJoin")
     */
    public function meetingJoin(string $serverID, Request $request): Response
    {
        $meetingID = $request->query->get('meetingID');
        $meeting = $this->findRoomConfiguration($serverID, $meetingID);
        if (empty($meeting)) {
            return $this->handleRoomNotFound($meetingID);
        }

        if (!$request->query->has('password')) {
            return $this->handleRoomNotFound($meetingID);
        }

        $attendee = new Attendee();
        $attendee->setServerID($serverID);
        if ($request->query->has('guest')) {
            $attendee->setUserID('guest');
        } else {
            $attendee->setUserId($request->query->get('userID'));
        }
        $attendee->setFullName($request->query->get('fullName'));

        $ismoderator = false;
        $role = $request->query->get('role');
        if (!$role) {
            $password = $request->query->get('password');
            if ($meeting->checkModeratorPW($password)) {
                $ismoderator = true;
            } else if (!$meeting->checkAttendeePW($password)) {
                return new XmlResponse((object) [], 'FAILED', 503);
            }
        }
        $ismoderator = $ismoderator || $role === 'MODERATOR';
        if ($ismoderator) {
            $attendee->setRole(Attendee::ROLE_MODERATOR);
            $attendee->setIsPresenter(true);
        }

        $meeting->addAttendee($attendee);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($attendee);
        $entityManager->persist($meeting);
        $entityManager->flush();

        $logoutURL = null;
        if ($request->query->has('logoutURL')) {
            $logoutURL = $request->query->get('logoutURL');
        }

        $lockSettings = [];
        foreach(self::LOCK_SETTINGS_DATA as $lockName) {
            $lockSettings[$lockName] = $meeting->getLockSetting($lockName);
        }

        return $this->render('mocked_meeting.html.twig', [
            'meeting' => $meeting,
            'attendee' => $attendee,
            'logoutURL' => $logoutURL,
            'lockSettings' => $lockSettings
        ]);

    }

    /**
     * @Route("/getRecordings", name="recordingsGet")
     */
    public function recordingsGet(string $serverID, Request $request): XmlResponse
    {
        $filter = [
            'serverID' => $serverID,
        ];

        if ($request->query->has('meetingID') && $meetingID = $request->query->get('meetingID')) {
            $meetings = explode(',', $meetingID);
            $filter['meeting'] = [];
            foreach ($meetings as $mID) {
                $meeting = $this->findRoomConfiguration($serverID, $mID);
                if (empty($meeting)) {
                    return $this->handleRoomNotFound($mID);
                }
                $filter['meeting'][] = $meeting;
            }
        } else if ($request->query->has('recordID') && $recordID = $request->query->get('recordID')) {
            $filter['recordID'] = explode(',', $recordID);
        }

        $entities = $this->getDoctrine()
             ->getRepository(Recording::class)
             ->findBy($filter);

        $recordings = array_map(function($entity): array {
            return $entity->getRecordingInfo();
        }, $entities);

        return new XmlResponse((object) [
            'recordings' => (object) [
                'forcexmlarraytype' => 'recording',
                'array' => $recordings,
            ],
        ]);

    }

    /**
     * @Route("/updateRecordings", name="recordingsUpdate")
     */
    public function recordingsUpdate(string $serverID, Request $request): XmlResponse
    {
        $recordID = $request->query->get('recordID');
        $recording = $this->getDoctrine()
            ->getRepository(Recording::class)
            ->findOneBy([
                'serverID' => $serverID,
                'recordID' => $recordID,
            ]);

        if (empty($recording)) {
            return new ErrorResponse(
                'notFound',
                'We could not find a recording with that recordID',
                'FAILED',
                404
            );
        }

        $metadata = $recording->getMetadata();
        $newMetadata = $this->getRecordingMetadataFromRequest($request, false);
        foreach ($newMetadata as $key => $value) {
            $metadata[$key] = $value;
        }
        $recording->setMetadata($metadata);

        if ($request->query->has('published')) {
            $recording->setPublished($request->query->get('published') !== 'false');
        }

        if ($request->query->has('protect')) {
            $recording->setProtected($request->query->get('protect') !== 'false');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($recording);
        $entityManager->flush();

        return new XmlResponse((object) [
            'updated' => true,
        ]);
    }

    /**
     * @Route("/deleteRecordings", name="recordingsDelete")
     */
    public function recordingsDelete(string $serverID, Request $request): XmlResponse
    {
        $recordID = $request->query->get('recordID');
        $recording = $this->getDoctrine()
            ->getRepository(Recording::class)
            ->findOneBy([
                'serverID' => $serverID,
                'recordID' => $recordID,
            ]);

        if (empty($recording)) {
            return new ErrorResponse(
                'notFound',
                'We could not find a recording with that recordID',
                'FAILED',
                404
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($recording);
        $entityManager->flush();

        return new XmlResponse((object) [
            'updated' => true,
        ]);
    }
}
