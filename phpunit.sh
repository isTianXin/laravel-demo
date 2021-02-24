#!/usr/bin/env bash
set -e

prepare_file='prepare_phpunit.sh'
if [[ -f $prepare_file ]]
then
  #shellcheck source=/dev/null
  . $prepare_file
fi

STARTED_AT=$(date +%s)

php artisan migrate:fresh --env=testing
php artisan migrate:refresh --env=testing

./vendor/bin/phpunit --stop-on-defect --coverage-text tests/
file='public/coverage/index.html'
if [[ -f $file ]]; then
    percentage=$(grep -m 1 'progressbar' $file | awk -Fvaluenow '{print $2}' | awk -F\" '{print $2}')
    sed -i "s/>[0-9]\+\.\?[0-9]\+%/>$percentage%/g" coverage.svg
    if [[ -n $(command -v rsvg-convert) ]]; then
      rsvg-convert coverage.svg > coverage.png
    fi
fi

FINISHED_AT=$(date +%s)
echo 'Time taken: '$((FINISHED_AT - STARTED_AT))' seconds'
