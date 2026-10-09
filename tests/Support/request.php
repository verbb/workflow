<?php

// Each invocation is one request: no singleton, identity, or event state leaks between requests.
ob_start();
require dirname(__DIR__) . '/runtime/bootstrap.php';
$input = json_decode(isset($argv[1]) ? base64_decode($argv[1], true) : stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$_SERVER['SCRIPT_FILENAME'] = CRAFT_WEB_ROOT . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'workflow-craft5-tests.ddev.site';
$_SERVER['SERVER_PORT'] = 80;
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
$f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
$siteId = $input['siteId'] ?? $f['sites'][0];
$app->getSites()->setCurrentSite($input['currentSiteId'] ?? $siteId);
$p = verbb\workflow\Workflow::$plugin;
$request = $app->getRequest();
$request->getHeaders()->set('Accept', ($input['json'] ?? true) ? 'application/json' : 'text/html');
$request->setIsCpRequest($input['cp'] ?? true);
$app->getView()->setTemplateMode($request->getIsCpRequest() ? craft\web\View::TEMPLATE_MODE_CP : craft\web\View::TEMPLATE_MODE_SITE);
$user = ($input['user'] ?? '') === 'guest' ? null : craft\elements\User::find()->id($f['users'][$input['user'] ?? 'publisher'])->one();
$app->getUser()->setIdentity($user);
if (($input['user'] ?? '') === 'limitedPublisher') {
    $p->getSettings()->publisherUserGroup[$app->getSites()->getCurrentSite()->uid] = $f['limitedGroupUid'];
}
if ($input['requireNotes'] ?? false) {
    $p->getSettings()->publisherNotesRequired[$app->getSites()->getCurrentSite()->uid] = true;
}
$out = ['mail' => [], 'context' => $input['context'] ?? []];
require __DIR__ . '/configure.php';
try {
    $target = $input['target'] ?? null;
    require __DIR__ . '/mutations.php';
    if (isset($input['graphql'])) {
        $out['graphql'] = $app->getGql()->executeQuery(new craft\models\GqlSchema(['name' => 'Test schema', 'scope' => $input['scope']]), $input['graphql']);
    }
    if (isset($input['findProvisionalOf'])) {
        $found = craft\elements\Entry::find()->draftOf($input['findProvisionalOf'])->provisionalDrafts(true)->draftCreator($user->id)->siteId($siteId)->status(null)->one();
        if ($found) {
            $target = ['elementId' => $found->id, 'canonicalId' => $found->getCanonicalId(), 'draftId' => $found->draftId, 'siteId' => $siteId];
        }
    }
    if (isset($input['findTitle'])) {
        $found = craft\elements\Entry::find()->title($input['findTitle'])->siteId($siteId)->drafts(null)->status(null)->one();
        if ($found) {
            $target = ['elementId' => $found->id, 'canonicalId' => $found->getCanonicalId(), 'draftId' => $found->draftId, 'siteId' => $siteId];
        }
    }
    if (isset($input['fixture'])) {
        $kind = $input['fixture'];
        $author = $f['users'][$input['author'] ?? 'editor'];
        $sectionId = $f['sectionId'];
        if (isset($context['sectionType']) || isset($context['propagation'])) {
            require __DIR__ . '/section.php';
        }
        $entry = (($context['sectionType'] ?? '') === 'single' ? craft\elements\Entry::find()->sectionId($sectionId)->siteId($siteId)->status(null)->one() : null) ?? new craft\elements\Entry(['sectionId' => $sectionId, 'typeId' => $f['typeId'], 'siteId' => $siteId, 'title' => 'Article ' . bin2hex(random_bytes(5)), 'enabled' => in_array($kind, ['draft', 'provisional'], true)]);
        $entry->enabled = in_array($kind, ['draft', 'provisional'], true);
        $entry->setAuthorIds([$author]);
        $entry->setFieldValue('summary', 'Original summary');
        $entry->setFieldValue('localizedSummary', 'Original localized content');
        if (!$app->getElements()->saveElement($entry)) {
            throw new RuntimeException(json_encode($entry->getErrors()));
        }
        if ($context['complexContent'] ?? false) {
            require __DIR__ . '/complex-content.php';
        }
        if (in_array($kind, ['draft', 'provisional'], true)) {
            $entry = $app->getDrafts()->createDraft($entry, $author, 'Editorial changes', provisional: $kind === 'provisional');
            $entry->title .= ' revised';
            $entry->setFieldValue('summary', 'Revised summary');
            $entry->setFieldValue('localizedSummary', 'Revised localized content');
            if (!$app->getElements()->saveElement($entry)) {
                throw new RuntimeException(json_encode($entry->getErrors()));
            }
        }
        $target = ['elementId' => $entry->id, 'canonicalId' => $entry->getCanonicalId(), 'draftId' => $entry->draftId, 'siteId' => $siteId];
        if ($kind === 'legacy') {
            // Recreate an already-pending submission from before the authorization changes.
            $submission = new verbb\workflow\elements\Submission(['ownerId' => $entry->id, 'ownerSiteId' => $siteId, 'siteId' => $siteId, 'isPending' => true, 'isComplete' => false]);
            if (!$app->getElements()->saveElement($submission)) {
                throw new RuntimeException(json_encode($submission->getErrors()));
            }
            $review = $p->getSubmissions()->createReview($submission, $entry);
            unset($review->data['draftId']);
            $review->userId = $author;
            $review->role = 'editor';
            $review->status = 'pending';
            if (!$p->getReviews()->saveReview($review)) {
                throw new RuntimeException(json_encode($review->getErrors()));
            }
        }
    }
    if ($target && isset($input['alter'])) {
        $entry = craft\elements\Entry::find()->id($target['elementId'])->drafts(null)->provisionalDrafts(null)->siteId($target['siteId'])->status(null)->one();
        if ($input['alter'] === 'invalid') {
            $entry->setFieldValue('summary', '');
            if (!$app->getElements()->saveElement($entry, false)) {
                throw new RuntimeException('Cannot create invalid content fixture.');
            }
        } elseif ($input['alter'] === 'attributes') {
            foreach ($input['attributes'] ?? [] as $key => $value) {
                $entry->$key = in_array($key, ['postDate', 'expiryDate']) ? craft\helpers\DateTimeHelper::toDateTime($value) : $value;
            }
            $entry->setFieldValues($input['fields'] ?? []);
            if (!$app->getElements()->saveElement($entry)) {
                throw new RuntimeException(json_encode($entry->getErrors()));
            }
        } elseif ($input['alter'] === 'deleteDraft') {
            if (!$app->getElements()->deleteElement($entry, true)) {
                throw new RuntimeException('Cannot delete draft fixture.');
            }
        } elseif ($input['alter'] === 'siblingDraft') {
            $sibling = $app->getDrafts()->createDraft($entry->getCanonical(true), $f['users']['editor'], 'Unsubmitted draft');
            $out['siblingDraftId'] = $sibling->draftId;
            $out['siblingTarget'] = ['elementId' => $sibling->id, 'canonicalId' => $sibling->getCanonicalId(), 'draftId' => $sibling->draftId, 'siteId' => $siteId];
        } elseif ($input['alter'] === 'stripReviewerMetadata') {
            $submission = $p->getSubmissions()->getSubmissionById($input['submissionId'], $siteId);
            foreach ($submission->getReviews() as $review) {
                unset($review->data['reviewerGroupUid']);
                if (!$p->getReviews()->saveReview($review)) {
                    throw new RuntimeException('Cannot create legacy stage history.');
                }
            }
        } elseif ($input['alter'] === 'migrateReview') {
            $review = $p->getSubmissions()->getSubmissionById($input['submissionId'], $siteId)->getLastReview();
            unset($review->data['draftId']);
            if (!$p->getReviews()->saveReview($review)) {
                throw new RuntimeException('Cannot recreate pre-upgrade review.');
            }
            (new verbb\workflow\migrations\m261008_000000_review_draft_identity())->safeUp();
            (new verbb\workflow\migrations\m261008_000000_review_draft_identity())->safeUp();
        }
    }
    if (isset($input['route'])) {
        $body = $input['body'] ?? [];
        if ($input['route'] === 'workflow/elements/save-entry' || $input['route'] === 'entries/create') {
            $body += ['section' => 'articles', 'sectionId' => $f['sectionId'], 'typeId' => $f['typeId'], 'siteId' => $siteId, 'enabled' => false, 'title' => 'Frontend article ' . bin2hex(random_bytes(5)), 'fields' => ['summary' => 'Submitted content']];
        }
        if ($target) {
            $body += ['elementType' => craft\elements\Entry::class, 'elementId' => $target['canonicalId'], 'draftId' => $target['draftId'], 'siteId' => $siteId];
        }
        $request->setBodyParams($body);
        if (isset($input['barrier'])) {
            $directory = $input['barrier']['directory'];
            if (!str_starts_with($directory, CRAFT_BASE_PATH . '/../race-')) {
                throw new RuntimeException('Invalid test barrier directory.');
            }
            touch($directory . '/ready-' . $input['barrier']['index']);
            $deadline = microtime(true) + 15;
            while (!is_file($directory . '/go')) {
                clearstatcache();
                if (microtime(true) > $deadline) {
                    throw new RuntimeException('Request barrier timed out.');
                }
                usleep(10000);
            }
        }
        $response = $app->runAction($input['route'], $input['params'] ?? []);
        $out['response'] = array_intersect_key(is_array($response?->data) ? $response->data : [], array_flip(['message', 'errors', 'success']));
        if (is_array($response?->data) && isset($response->data['html'])) {
            $out['html'] = $response->data['html'];
        }
        $out['httpStatus'] = $response?->statusCode;
        $out['redirect'] = $response?->getHeaders()->get('location');
        if (is_string($response?->data)) {
            $out['html'] = $response->data;
        }
        if ($input['followResponse'] ?? false) {
            $target = null;
        }
        if (!$target && isset($response->data['elementId'], $response->data['canonicalId'], $response->data['draftId'])) {
            $target = ['elementId' => $response->data['elementId'], 'canonicalId' => $response->data['canonicalId'], 'draftId' => $response->data['draftId'], 'siteId' => $siteId];
        }
        if (!$target && isset($response->data['model'])) {
            $model = $response->data['model'];
            $target = ['elementId' => $model['id'], 'canonicalId' => $model['canonicalId'], 'draftId' => $model['draftId'], 'siteId' => $model['siteId']];
        } elseif (!$target && isset($response->data['entry'])) {
            $model = $response->data['entry'];
            $target = ['elementId' => $model['id'], 'canonicalId' => $model['canonicalId'], 'draftId' => $model['draftId'], 'siteId' => $model['siteId']];
        }
        if (!$target && !($input['json'] ?? true) && isset($body['title'])) {
            $created = craft\elements\Entry::find()->title($body['title'])->siteId($siteId)->drafts(null)->status(null)->one();
            if ($created) {
                $target = ['elementId' => $created->id, 'canonicalId' => $created->getCanonicalId(), 'draftId' => $created->draftId, 'siteId' => $siteId];
            }
        }
    }
} catch (Throwable $e) {
    $out['exception'] = ['class' => get_class($e), 'message' => $e->getMessage()];
}
if ($target) {
    $out['target'] = $target;
    $submissionQuery = verbb\workflow\elements\Submission::find()->ownerId($target['canonicalId'])->ownerSiteId($target['siteId'])->siteId($target['siteId'])->status(null)->orderBy(['id' => SORT_DESC]);
    // Keep assertions on the fixture even when a negative test posts another submission's ID.
    $inspectId = $input['submissionId'] ?? $input['body']['submissionId'] ?? null;
    if ($inspectId) {
        $submissionQuery->id($inspectId);
    }
    $submission = $submissionQuery->one();
    $entry = craft\elements\Entry::find()->id($target['elementId'])->drafts(null)->provisionalDrafts(null)->siteId($target['siteId'])->status(null)->one();
    $out['entry'] = $entry ? ['id' => $entry->id, 'summary' => $entry->getFieldValue('summary'), 'title' => $entry->title, 'enabled' => $entry->enabled, 'enabledForSite' => $entry->getEnabledForSite(), 'provisional' => $entry->isProvisionalDraft, 'draftId' => $entry->draftId] : null;
    $out['cpUrl'] = $entry?->getCpEditUrl();
    if ($submission) {
        $out['submission'] = ['id' => $submission->id, 'reviewId' => $submission->getLastReview()->id, 'status' => $submission->status, 'complete' => $submission->isComplete, 'pending' => $submission->isPending, 'reviewCount' => count($submission->getReviews()), 'reviewDraftId' => $submission->getLastReview()->draftId, 'snapshotDraftId' => $submission->getLastReview()->data['draftId'] ?? null, 'statuses' => $user ? $p->getSubmissionPermissions()->getAllowedStatuses($user, $submission) : []];
        $out['history'] = array_map(fn($review) => ['id' => $review->id, 'role' => $review->role, 'status' => $review->status, 'userId' => $review->userId, 'notes' => $review->getNotes(), 'data' => $review->data], $submission->getReviews());
        $out['nextReviewer'] = $entry ? $p->getSubmissions()->getNextReviewerUserGroup($submission, $entry)?->handle : null;
        $out['menus'] = $entry ? ['editor' => $p->getActions()->getEditorActionsMenuItems($entry, $submission, $submission->getLastReview()), 'reviewer' => $p->getActions()->getReviewerActionsMenuItems($entry, $submission, $submission->getLastReview())] : [];
        $out['menu'] = $entry ? $p->getActions()->getPublisherActionsMenuItems($entry, $submission, $submission->getLastReview()) : [];
    }
    $canonical = craft\elements\Entry::find()->id($target['canonicalId'])->siteId($target['siteId'])->status(null)->one();
    $out['canonical'] = $canonical ? ['id' => $canonical->id, 'title' => $canonical->title, 'enabled' => $canonical->enabled, 'enabledForSite' => $canonical->enabledForSite, 'summary' => $canonical->getFieldValue('summary'), 'status' => $canonical->getStatus()] : null;
    if ($context['complexContent'] ?? false) {
        $out['content'] = $canonical ? ['related' => $canonical->getFieldValue('relatedEntries')->ids(), 'details' => $canonical->getFieldValue('details'), 'blocks' => array_map(fn($block) => ['title' => $block->title, 'summary' => $block->getFieldValue('summary')], $canonical->getFieldValue('body')->all())] : null;
    }
    $out['draftExists'] = $entry?->getIsDraft() ?? false;
    $out['localizations'] = array_map(fn($localized) => ['siteId' => $localized->siteId, 'summary' => $localized->getFieldValue('summary'), 'enabledForSite' => $localized->enabledForSite], craft\elements\Entry::find()->id($target['canonicalId'])->site('*')->status(null)->all());
    $out['draftCount'] = (int)craft\elements\Entry::find()->draftOf($target['canonicalId'])->drafts(true)->provisionalDrafts(null)->siteId($siteId)->status(null)->count();
    $out['submissionCount'] = (int)verbb\workflow\elements\Submission::find()->ownerId($target['canonicalId'])->siteId($target['siteId'])->status(null)->count();
    $out['revisionCount'] = (int)(new craft\db\Query())->from(craft\db\Table::REVISIONS)->where(['canonicalId' => $target['canonicalId']])->count();
}
$out['error'] = $app->getSession()->getError();
ob_end_clean();
echo json_encode($out, JSON_THROW_ON_ERROR);
