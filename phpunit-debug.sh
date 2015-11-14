#!/bin/bash

export XDEBUG_CONFIG=idekey=netbeans-xdebug
exec phpunit "$@"

