# Matrix Synapse Mock API Server

This project and associated docker containers provide a partial mock of the main Matrix Synapse server and can be used for
testing of Synapse integrations.

## Quick setup using Docker
Run the following command to get your mock server up and running:
   ```
   docker run -p 8001:80 moodlehq/matrixsynapse_mock
   ```

## API information
All endpoints must be prefixed with a `serverID` to allow for parallel runs, for example if your Synapse server endpoint is:

```
https://synapse:8008/_synapse/admin/v2/users/@testuser:synapse
```

The Mock Api endpoint would be:

```
http://localhost:8001/someServerID/_synapse/admin/v2/users/@testuser:synapse
```

## API coverage
The following are the currently mocked API endpoints. These should respond with the same HTTP status code, content type and response content as a real Synapse server.

### Synapse
Call API endpoint without Auth Header:<br/>
`curl -i -X GET http://localhost:8001/someServerID/_synapse/admin/v2/users/@barzuser:synapse`

Call API endpoint using wrong HTTP method:
```
curl -i --header "Authorization: Bearer syt_YWRtaW4_iNofAsbInAInYAFwkmdi_1oVMxI" \
-X POST http://localhost:8001/someServerID/_synapse/admin/v2/users/@testuser:synapse`
```

Get user info. Handles existing and non-existing users:
```
curl -i --header "Authorization: Bearer syt_YWRtaW4_iNofAsbInAInYAFwkmdi_1oVMxI" \
-X GET http://localhost:8001/someServerID/_synapse/admin/v2/users/@testuser:synapse
```

Create and update users:
```
curl -i --header "Authorization: Bearer syt_YWRtaW4_iNofAsbInAInYAFwkmdi_1oVMxI" \
-X PUT \
-d '{"displayname":"Someone new","threepids":[{"medium":"email","address":"newuser@bar.com"}]}' \
http://localhost:8001/someServerID/_synapse/admin/v2/users/@anewuser:synapse
```

### Matrix
Create a room:
```
curl -X POST -H 'Authorization: Bearer syt_YWRtaW4_KXUwoITuowupgGEIGNuK_4MLu3S' \
-d '{"name":"curl room","topic":"curly topic","preset":"private_chat","visibility":"private","initial_state":[{"type":"m.room.encryption","state_key":"","content":{"algorithm":"m.megolm.v1.aes-sha2"}}]}' \
http://localhost:8001/someServerID/_matrix/client/r0/createRoom
```

## More information


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

## Automated tests
You need to define `TEST_MOD_SYNAPSE_MOCK_SERVER` in your config.php when running automated tests like PHPUnit and Behat.
Otherwise, most of the BigBlueButton tests will be marked skipped.

For example, add the following to your config.php after the `$CFG->wwwroot` line:
   ```
   define('TEST_MOD_SYNAPSE_MOCK_SERVER', "http://localhost:8001/hash" . sha1($CFG->wwwroot));
   ```


## Local development

* Symphony : `wget https://get.symfony.com/cli/installer -O - | bash`
* PHP SQLLite3 : example `sudo apt install php7.4-sqlite3`


Check requirements:

    cd application
    symfony check:requirements

Set up the db:

    php bin/console doctrine:database:drop --force
    php bin/console doctrine:schema:create

Run the project:

    symfony server:start --port=8001 --no-tls --allow-http

Debugging:

    XDEBUG_SESSION=1 symfony server:start --port=8001 --no-tls --allow-http

Notes:

* The database is intended to be **disposable**. Migrations are not guaranteed to work. Please be prepared to drop and
  recreate the database.


## Publishing status

Note : if you want to publish on your own repository (being Docker Hub or Git Container Registry), you need to define the
following secrets [following secrets ](https://docs.github.com/en/actions/security-guides/encrypted-secrets):
* DOCKERHUB_OWNER
* DOCKERHUB_TOKEN
* DOCKERHUB_USERNAME
* GH_OWNER

[![Docker multiarch publish](https://github.com/moodlehq/matrixsynapse_mock/actions/workflows/build_and_publish.yml/badge.svg)](https://github.com/moodlehq/matrixsynapse_mock/actions/workflows/build_and_publish.yml)
