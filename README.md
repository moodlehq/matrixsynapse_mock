# BigBlueButton Mock API Server

This project and associated docker containers provide a mock of the main BigBlueButton server and can be used for
testing of BigBlueButton integrations.

## Quick setup using Docker
Run the following command to get your mock server up and running:
   ```
   docker run -p 8001:80 moodlehq/bigbluebutton_mock
   ```

## Example usage
### Automated tests
You need to define `TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER` in your config.php when running automated tests like PHPUnit and Behat. 
Otherwise, most of the BigBlueButton tests will be marked skipped.

For example, add the following to your config.php after the `$CFG->wwwroot` line:
   ```
   define('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER', "http://localhost:8001/hash" . sha1($CFG->wwwroot));
   ```

## More information
All endpoints must be prefixed with a serverID to allow for parallel runs, for example:

```
http://localhost:8001/someServerID/api
```

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
* DOCKERHUB_PASSWORD
* DOCKERHUB_TOKEN
* DOCKERHUB_USERNAME
* GH_OWNER

[![Docker multiarch publish](https://github.com/moodlehq/bigbluebutton_mock/actions/workflows/build_and_publish.yml/badge.svg)](https://github.com/moodlehq/bigbluebutton_mock/actions/workflows/build_and_publish.yml)
