#!/bin/bash

sleep 10;

docker cp test/feature/. moodle:/opt/bitnami/moodle/local/aiquestions/tests
docker cp test/config.php moodle:/opt/bitnami/moodle/config.php
docker exec moodle php opt/bitnami/moodle/admin/tool/phpunit/cli/init.php
docker exec moodle bash -c "cd bitnami/moodle && \
        vendor/bin/phpunit local/aiquestions/tests/convert_word_to_pdf_test.php"
