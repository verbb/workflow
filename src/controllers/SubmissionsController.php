<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Element;
use craft\controllers\BaseEntriesController;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SubmissionsController extends BaseEntriesController
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action)
    {
        // Until I can find a better way to handle firing this before an action...
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        $editorNotes = $request->getBodyParam('editorNotes');
        $publisherNotes = $request->getBodyParam('publisherNotes');

        if ($action === 'save-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->editorNotesRequired && !$editorNotes) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'editorNotesErrors' => [Craft::t('workflow', 'Editor notes are required')],
                ]);

                return null;
            }
        }

        if ($action === 'approve-submission' || $action === 'approve-only-submission' || $action === 'reject-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->publisherNotesRequired && !$publisherNotes) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'publisherNotesErrors' => [Craft::t('workflow', 'Publisher notes are required')],
                ]);

                return null;
            }
        }

        return parent::beforeAction($action);
    }

    public function actionUnsavedDraftSubmission()
    {
        // For an unsaved entry (brand new), we want to publish the draft first, then also submit
        // a new draft (with the same details) immediately, because the rest of the functionality for Workflow
        // always deals with drafts. This is mostly so users can have an entry shown in the element index
        // which it can't be if its an unpublished draft. Bit of a pain...

        // We have to duplicate the controller action for publishing a draft, otherwise we can't get the 
        // resulting published entry (easily), which we need to redirect properly and create the new draft from.
        $entry = $this->_publishDraft();

        if (!$entry) {
            return null;
        }

        // Create a new draft, based on the one published
        $draft = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId());

        // Perform the Workflow submission on this new draft
        Workflow::$plugin->getSubmissions()->saveSubmission($draft);

        // Redirect to the new draft
        return $this->redirect(UrlHelper::url($draft->getCpEditUrl(), [
            'draftId' => $draft->draftId,
        ]));
    }

    public function actionSaveDraft()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entry-revisions/save-draft')->send();
    }

    public function actionPublishDraft()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entry-revisions/publish-draft')->send();
    }

    public function actionPublishEntry()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entries/save-entry')->send();
    }


    // Private Methods
    // =========================================================================

    private function _publishDraft()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $draftId = $request->getRequiredBodyParam('draftId');
        $siteId = $request->getBodyParam('siteId');

        /** @var Entry|DraftBehavior|null $draft */
        $draft = Entry::find()
            ->draftId($draftId)
            ->siteId($siteId)
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new NotFoundHttpException('Draft not found');
        }

        // Permission enforcement
        /** @var Entry|null $entry */
        $entry = ElementHelper::sourceElement($draft);
        $this->enforceEditEntryPermissions($entry);
        $section = $entry->getSection();

        // Is this another user's entry (and it's not a Single)?
        $userId = Craft::$app->getUser()->getId();
        if (
            $entry->authorId != $userId &&
            $section->type != Section::TYPE_SINGLE &&
            $entry->enabled
        ) {
            // Make sure they have permission to make live changes to those
            $this->requirePermission('publishPeerEntries:' . $section->uid);
        }

        // Is this another user's draft?
        if ($draft->creatorId != $userId) {
            $this->requirePermission('publishPeerEntryDrafts:' . $section->uid);
        }

        // Populate the main draft attributes
        $this->_setDraftAttributesFromPost($draft);

        // Even more permission enforcement
        if ($draft->enabled) {
            $this->requirePermission('publishEntries:' . $section->uid);
        }

        // Populate the field content
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $draft->setFieldValuesFromRequest($fieldsLocation);
        $draft->updateTitle();

        // Validate and save the draft
        if ($draft->enabled && $draft->enabledForSite) {
            $draft->setScenario(Element::SCENARIO_LIVE);
        }

        if ($draft->getIsUnsavedDraft() && $request->getBodyParam('propagateAll')) {
            $draft->propagateAll = true;
        }

        try {
            if (!Craft::$app->getElements()->saveElement($draft)) {
                throw new InvalidElementException($draft);
            }

            // Publish the draft (finally!)
            $newEntry = Craft::$app->getDrafts()->applyDraft($draft);
        } catch (InvalidElementException $e) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t publish draft.'));

            // Send the draft back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $draft
            ]);
            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));

        return $newEntry;
    }

    private function _setDraftAttributesFromPost(Entry $draft)
    {
        $request = Craft::$app->getRequest();
        /** @var Entry|DraftBehavior $draft */
        $draft->typeId = $request->getBodyParam('typeId');
        // Prevent the last entry type's field layout from being used
        $draft->fieldLayoutId = null;
        // Default to a temp slug to avoid slug validation errors
        $draft->slug = $request->getBodyParam('slug') ?: (ElementHelper::isTempSlug($draft->slug)
            ? $draft->slug
            : ElementHelper::tempSlug());
        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $draft->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $draft->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }
        $draft->enabled = (bool)$request->getBodyParam('enabled');
        $draft->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $draft->enabledForSite);
        $draft->title = $request->getBodyParam('title');

        if (!$draft->typeId) {
            // Default to the section's first entry type
            $draft->typeId = $draft->getSection()->getEntryTypes()[0]->id;
            // Prevent the last entry type's field layout from being used
            $draft->fieldLayoutId = null;
        }

        // Author
        $authorId = $request->getBodyParam('author', ($draft->authorId ?: Craft::$app->getUser()->getIdentity()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $draft->authorId = $authorId;

        // Parent
        $parentId = $request->getBodyParam('parentId');

        if (is_array($parentId)) {
            $parentId = $parentId[0] ?? null;
        }

        $draft->newParentId = $parentId ?: null;
    }

}
