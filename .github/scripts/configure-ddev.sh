#!/usr/bin/env bash
set -euo pipefail
php_version="${1:?Supply the PHP release line}"
docroot="${2:-tests/runtime/web}"
service=web
web_version="$php_version"
case "$php_version" in
    8.2.0)
        service=php-minimum
        web_version=8.3
        cp .github/php-minimum/docker-compose.yaml .ddev/docker-compose.php-minimum.yaml
        ;;
    8.2|8.3|8.4) ;;
    *) echo "Unsupported test PHP version: $php_version" >&2; exit 1 ;;
esac
printf 'php_version: "%s"\ndocroot: %s\n' "$web_version" "$docroot" > .ddev/config.zz-ci.yaml
mkdir -p "$docroot"
ddev start
ddev exec --service "$service" --raw -- php -r '
    $expected = $argv[1];
    echo "Test interpreter: ", PHP_VERSION, " (", PHP_BINARY, ")", PHP_EOL;
    $actual = substr_count($expected, ".") === 2 ? PHP_VERSION : PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
    exit($actual === $expected ? 0 : 1);
' "$php_version"
