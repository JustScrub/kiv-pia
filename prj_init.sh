docker run --rm --volume $PWD/web/Composer:/app composer install
docker compose up -d
WEB_UID=$(docker compose exec --no-TTY --user www-data pia_web id -u)
chown -R $WEB_UID:$WEB_UID web/Articles
