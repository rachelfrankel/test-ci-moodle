#!/bin/bash

moodle_path="opt/bitnami/moodle"
base_url="https://moodle.org/plugins/download.php/"

declare -A downloads=(
    ["29376/local_aiquestions_moodle42_2023060400.zip"]="/local"
    ["28405/mod_adaptivequiz_moodle41_2023011500.zip"]="/mod"
    ["30960/mod_zoom_moodle43_2024012500.zip"]="/mod"
    ["30811/report_growth_moodle43_2024010500.zip"]="/report"
)

for url in "${!downloads[@]}"; do
    file_name=$(basename "$url")    
    wget "$base_url$url"
    unzip "$file_name" -d "$moodle_path${downloads[$url]}"
    rm "$file_name"
done
