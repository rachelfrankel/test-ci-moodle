#!/bin/bash

docker build -f Dockerfile.moodle -t moodle .
docker build -f Dockerfile.mariadb -t mariadb .
docker network create my-network300
# docker run -d --env ${{secrets.MOODLE_OBJ}} --name moodle -p 80:8080 --net my-network300 moodle
# docker run -d --name mariadb -p 3306:3306 --net my-network300 mariadb



mariadb:
# -e ALLOW_EMPTY_PASSWORD=yes \
  -e MARIADB_USER=bn_moodle \
  -e MARIADB_DATABASE=bitnami_moodle 