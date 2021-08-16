#!/usr/bin/env bash

bin/console doctrine:schema:create --no-interaction

exec "$@"
