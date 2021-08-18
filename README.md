# BigBlueButton Mock API Server

This project and associated docker containers provide a mock of the main BigBlueButton server and can be used for
testing of BigBlueButton integrations.

In addition to the standard endpoints, additional endpoints are provided for setting up data which a test requires or expects:
```
/backoffice/createMeeting
/backoffice/createRecording
```

In addition, the following endpoint can be used to trigger a reset between tests:
```
/backoffice/reset
```

And the following endpoints exist to view the current meetings and recordings:
```
/backoffice/meetings
/backoffice/recordings
```
