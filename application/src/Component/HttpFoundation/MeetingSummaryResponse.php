<?php

namespace App\Component\HttpFoundation;

use App\Entity\Meeting;
use SimpleXMLElement;
use StdClass;

class MeetingSummaryResponse extends XmlResponse
{
    public function __construct(Meeting $meeting, string $returnCode = 'SUCCESS', int $status = 200, array $headers = [])
    {
        parent::__construct($meeting->getMeetingSummary(), $returnCode, $status, $headers);
    }
}
