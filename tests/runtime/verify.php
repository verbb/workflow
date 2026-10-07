<?php
require_once __DIR__ . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
$app->setEdition(\craft\enums\CmsEdition::Pro);
$package = json_decode(file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);
$handle = $package['extra']['handle'];
$plugin = $app->getPlugins()->getPlugin($handle);
if (!$app->getIsInstalled() || !$plugin || !$app->getPlugins()->isPluginInstalled($handle) || !$app->getPlugins()->isPluginEnabled($handle) || $app->getPlugins()->isPluginUpdatePending($plugin)) {
    throw new RuntimeException('Fresh plugin installation is incomplete: ' . $handle);
}
$source = (new ReflectionClass($plugin))->getFileName();
if (!str_starts_with(realpath($source), realpath(dirname(__DIR__, 2) . '/src') . '/')) {
    throw new RuntimeException('Craft loaded a different plugin checkout.');
}
echo 'Verified normal Craft installation and plugin source: ' . $handle . PHP_EOL;
// This script does not run Application::run(), so persist the edition before the next process boots.
$app->getProjectConfig()->saveModifiedConfigData();
