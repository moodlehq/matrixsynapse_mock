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

# Local development

* Symphony : `wget https://get.symfony.com/cli/installer -O - | bash`
* PHP SQLLite3 : example `sudo apt install php7.4-sqlite3`


Check requirements:

    symfony check:requirements

Setup the db:

    php bin/console doctrine:database:create
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate

Run the project:

    symfony server:start --port=8001 --no-tls --allow-http
