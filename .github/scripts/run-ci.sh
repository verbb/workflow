#!/usr/bin/env bash
set -euo pipefail
php_version="${1:?Supply PHP version}"
order="${2:?Supply default or random order}"
shift 2
case "$php_version" in 8.2.0) service=php-minimum ;; 8.2|8.3|8.4) service=web ;; *) exit 2 ;; esac
case "$order" in default|random) ;; *) exit 2 ;; esac
python3 .github/scripts/check-ci.py
ddev exec --service "$service" --raw -- php -r '
    $expected = $argv[1];
    echo "Running full suite on PHP ", PHP_VERSION, PHP_EOL;
    exit((substr_count($expected, ".") === 2 ? PHP_VERSION : PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION) === $expected ? 0 : 1);
' "$php_version"
python3 .github/scripts/record-inputs.py before
status=0
ddev exec --service "$service" --raw -- php tests/runtime/run.php --suite=all --order-by="$order" --random-order-seed=9122026 "$@" || status=$?
python3 .github/scripts/record-inputs.py after
if [ "$status" -ne 0 ]; then
    # PHP may die before JUnit can close. Keep the initiating test and fatal visible.
    for log in .cache/verbb-tests/events.log .cache/verbb-tests/app/storage/logs/phperrors.log; do
        if [ -f "$log" ]; then tail -n 35 "$log" >&2; fi
    done
    exit "$status"
fi
ddev exec --service "$service" --raw -- composer check-platform-reqs --working-dir=.cache/verbb-tests/app --no-plugins
ddev exec --service "$service" --raw -- php .github/scripts/test-runner-guards.php
