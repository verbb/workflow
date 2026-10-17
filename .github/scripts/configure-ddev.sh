#!/usr/bin/env bash
set -euo pipefail
php_version="${1:?Supply the PHP release line}"
docroot="${2:-tests/runtime/web}"
# Audit overrides must not silently replace the runtime selected by the CI matrix.
printf 'php_version: "%s"\ndocroot: %s\n' "$php_version" "$docroot" > .ddev/config.zz-ci.yaml
mkdir -p "$docroot"
ddev start
ddev exec --raw -- php -r 'echo PHP_VERSION, PHP_EOL; exit((PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION) === $argv[1] ? 0 : 1);' "$php_version"
