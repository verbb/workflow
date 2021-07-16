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
                    'entry' => $this->getDraftEntry(),
                ]);

                return null;
            }
        }

        if ($action === 'approve-submission' || $action === 'approve-only-submission' || $action === 'reject-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->publisherNotesRequired && !$publisherNotes) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'publisherNotesErrors' => [Craft::t('workflow', 'Publisher notes are required')],
                    'entry' => $this->getDraftEntry(),
                ]);

                return null;
            }
        }

        return parent::beforeAction($action);
    }

    public function actionUnsavedDraftSubmission()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entry-revisions/save-draft');
    }

    public function actionSaveDraft()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entry-revisions/save-draft');
    }

    public function actionPublishDraft()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entry-revisions/publish-draft');
    }

    public function actionPublishEntry()
    {
        // We're already checking validation in our beforeAction
        return Craft::$app->runAction('entries/save-entry');
    }


    // Private Methods
    // =========================================================================

    private function getDraftEntry()
    {
        $request = Craft::$app->getRequest();

        $draftId = $request->getBodyParam('draftId');
        $entryId = $request->getBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');

        $entry = null;

        if ($draftId) {
            $entry = Entry::find()
                ->draftId($draftId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();
        }

        if ($entryId) {
            $entry = Entry::find()
                ->id($entryId)
                ->siteId($siteId)
                ->anyStatus()
                ->one();
        }

        if ($entry) {
            $this->_setDraftAttributesFromPost($entry);
            $entry->setFieldValuesFromRequest($fieldsLocation);
            $entry->updateTitle();
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);

            return $entry;
        }
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
        
        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $draft->enabled = in_array(true, $enabledForSite, false) || $draft->enabled;
        } else {
            $draft->enabled = (bool)$request->getBodyParam('enabled', $draft->enabled);
        }
        $draft->setEnabledForSite($enabledForSite ?? $draft->getEnabledForSite());
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
