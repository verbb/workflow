<?php

// Exercise the real web bootstrap and plugin event registration from a PHP test process.
return ['components' => ['request' => static function() {
    return Craft::createObject(array_merge(craft\helpers\App::webRequestConfig(), [
        'isConsoleRequest' => false,
        'enableCsrfValidation' => PHP_SAPI !== 'cli',
    ]));
}]];
