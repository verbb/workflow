<?php

$relatedEntry = new craft\elements\Entry(['sectionId' => $f['sectionId'], 'typeId' => $f['typeId'], 'siteId' => $siteId, 'title' => 'Related ' . bin2hex(random_bytes(4)), 'enabled' => true]);
$relatedEntry->setAuthorIds([$author]);
$relatedEntry->setFieldValue('summary', 'Related content');
if (!$app->getElements()->saveElement($relatedEntry)) {
    throw new RuntimeException('Cannot save related content fixture.');
}
$entry->setFieldValue('relatedEntries', [$relatedEntry->id]);
$entry->setFieldValue('details', [['col1' => 'Original table cell']]);
$entry->setFieldValue('body', ['new1' => ['type' => 'textBlock', 'title' => 'Original block', 'fields' => ['summary' => 'Original nested content']]]);
if (!$app->getElements()->saveElement($entry)) {
    throw new RuntimeException(json_encode($entry->getErrors()));
}
