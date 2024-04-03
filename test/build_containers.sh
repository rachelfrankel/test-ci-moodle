#!/bin/bash

# docker pull ghcr.io/rachelfrankel/ci/moodle-test:1.0.0
# docker pull bitnami/mariadb:11.2
# docker network create my-network
# docker run --rm -d --name moodle -p 80:8080 --network my-network ghcr.io/rachelfrankel/ci/moodle-test:1.0.0
# docker run --rm -d --name mariadb -p 3306:3306 --network my-network bitnami/mariadb:11.2



docker build -f Dockerfile.moodle -t moodle .
docker build -f Dockerfile.mariadb -t mariadb .
docker network create my-network300
docker run -d --name moodle -p 80:8080 --network my-network300 moodle
docker run -d --name mariadb -p 3306:3306 --network my-network300 mariadb
# docker run -d --name moodle -p 80:8080 moodle
# docker run -d --name mariadb -p 3306:3306 mariadb

# docker build -t moodle .
# docker build -f Dockerfile.moodle -t moodle .
# docker build -f Dockerfile.mariadb -t moodle .
# docker network create moodle1
# # docker run -d --env-file env/.env.moodle --name moodle -p 80:8080 --network moodle moodle
# docker run -d --name moodle -p 80:8080 --network moodle1 moodle
# docker run -d --name mariadb -p 3306:3306 --network moodle1 mariadb
# docker run -d --env-file env/.env.mariadb --name mariadb -p 3306:3306 --network moodle bitnami/mariadb:11.2