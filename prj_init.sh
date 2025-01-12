#!/bin/bash

# try rights for docker
if ! docker ps >/dev/null 2>&1 ; then
  echo "You need to have docker rights to run this script (try sudo ;-))"
  exit 1
fi

# modify compose.yml based on flags
# -a: add admin mock data, -u: add user mock data, -t: add test data
while getopts "aut" opt; do
  case $opt in
    a)
      sed -i "s|#ADMINMOCKDATA|- ./SQL_Scripts/admin_mock_data.sql:/docker-entrypoint-initdb.d/03.sql|" compose.yaml 
      ;;
    u)
      sed -i "s|#USERMOCKDATA|- ./SQL_Scripts/uzivatel_mock_data.sql:/docker-entrypoint-initdb.d/04.sql|" compose.yaml 
      ;;
    t)
      sed -i "s|#APITESTSQLSCRIPT|- ./SQL_Scripts/api_test_data.sql:/docker-entrypoint-initdb.d/02.sql|" compose.yaml 
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      ;;
  esac
done

docker run --rm --volume $PWD/web/Composer:/app composer install
docker compose up -d
WEB_UID=$(docker compose exec --no-TTY --user www-data pia_web id -u)
chown -R $WEB_UID:$WEB_UID web/Articles || chmod -R 777 web/Articles
