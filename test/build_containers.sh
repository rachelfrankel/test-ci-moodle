#!/bin/bash

docker build -f Dockerfile.moodle -t moodle .
docker build -f Dockerfile.mariadb -t mariadb .
docker network create my-network300
docker run -d --name moodle -p 80:8080 --net my-network300 moodle
docker run -d --name mariadb -p 3306:3306 --net my-network300 mariadb
