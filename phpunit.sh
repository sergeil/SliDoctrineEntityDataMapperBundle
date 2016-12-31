#!/usr/bin/env bash

## Allows to run functional test in Docker containers, usage:
# $ ./phpunit.sh
# By providing --md flag MySQL container will be started as a daemon, so there's going to be no need to wait to have
# a container bootstrapped when you re-run tests frequently
# $ ./phpunit.sh --md

set -e

args=$@
is_daemon=false

if [[ ${args:0:4} == "--md" ]]; then
  args=${args:4}
  is_daemon=true
fi

if ! type docker > /dev/null; then
    echo "Docker is required to run tests."
    exit 1
fi

if [ ! -d "vendor" ]; then
  echo "# No vendor dir detected, installing dependencies first then"

  docker run \
  -it \
  --rm \
  -v `pwd`:/mnt/tmp \
  -w /mnt/tmp \
  modera/php7-fpm "composer install"
fi

if [[ `docker ps` != *"sli_mysql"* ]]; then
  if [ "$is_daemon" = true ] ; then
    echo "# Starting database for functional tests (as daemon)"
  else
    echo "# Starting database for functional tests"
  fi

  docker run -d -e MYSQL_ROOT_PASSWORD=123123 --name sli_mysql mysql:5 > /dev/null
else
  echo "# MySQL container is already running, reusing it"
fi

# see Tests/Fixtures/App/app/config/parameters.yml
docker exec sli_mysql \
    bash -c "mysql -u root -p123123 -e 'CREATE DATABASE IF NOT EXISTS sli_doctrineentitydatamapper'"

docker run \
    -t \
    --rm \
    -v `pwd`:/mnt/tmp \
    -w /mnt/tmp \
    -e SYMFONY__db_host=mysql \
    -e SYMFONY__db_port=3306 \
    -e SYMFONY__db_user=root \
    -e SYMFONY__db_password=123123 \
    --link sli_mysql:mysql \
    modera/php7-fpm "vendor/bin/phpunit ${args}"

exit_code=$?

if [ "$is_daemon" = false ] ; then
  docker rm -f sli_mysql > /dev/null
fi