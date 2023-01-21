<?php
namespace verbb\workflow\controllers;

use Craft;
use craft\elements\Entry;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\Response;

class ElementsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSaveEntry(): Response
    {
        // Create a draft entry for the section
        $siteId = $this->request->getParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $sectionId = $this->request->getRequiredParam('sectionId');
        $section = Craft::$app->getSections()->getSectionById($sectionId);

        if (!$section) {
            throw new BadRequestHttpException('Section invalid.');
        }

        $response = Craft::$app->runAction('entries/create', ['section' => $section->handle, 'siteId' => $siteId]);

        // We have to get the saved draft from the redirect url - gross
        $entry = $this->_getEntryFromResponse($response, $siteId);

        if (!$entry) {
            throw new BadRequestHttpException('Unable to create draft entry.');
        }

        $this->_populateEntryModel($entry);

        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new BadRequestHttpException('Unable to save entry: ' . Json::encode($entry->getErrors()) . '.');
        }
        
        return $this->asModelSuccess($entry, Craft::t('app', '{type} saved.', ['type' => Entry::displayName()]));
    }


    // Private Methods
    // =========================================================================

    private function _populateEntryModel(Entry $entry): void
    {
        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $entry->typeId = $this->request->getBodyParam('typeId', $entry->typeId);
        $entry->slug = $this->request->getBodyParam('slug', $entry->slug);

        if (($postDate = $this->request->getBodyParam('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }

        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $entry->enabled = (bool)$this->request->getBodyParam('enabled', $entry->enabled);
        $entry->setEnabledForSite($enabledForSite ?? $entry->getEnabledForSite());
        $entry->title = $this->request->getBodyParam('title', $entry->title);

        if (!$entry->typeId) {
            // Default to the section's first entry type
            $entry->typeId = $entry->getAvailableEntryTypes()[0]->id;
        }

        // Prevent the last entry type's field layout from being used
        $entry->fieldLayoutId = null;

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $entry->setFieldValuesFromRequest($fieldsLocation);

        // Author
        $authorId = $this->request->getBodyParam('author', ($entry->authorId ?: static::currentUser()->id));

        if (is_array($authorId)) {
            $authorId = $authorId[0] ?? null;
        }

        $entry->authorId = $authorId;

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            $entry->setParentId($parentId);
        }

        // Is fresh?
        if ($this->request->getBodyParam('isFresh')) {
            $entry->setIsFresh();
        }

        // Revision notes
        $entry->setRevisionNotes($this->request->getBodyParam('notes'));
    }

    private function _getEntryFromResponse($response, $siteId)
    {
        if ($this->request->getAcceptsJson()) {
            $elementId = $response->data['entry']['id'] ?? null;
            $draftId = $response->data['entry']['draftId'] ?? null;
            $siteId = $response->data['entry']['siteId'] ?? null;

            return Entry::find()
                ->id($elementId)
                ->siteId($siteId)
                ->draftId($draftId)
                ->status(null)
                ->one();
        }

        $redirectUrl = $response->getHeaders()['location'] ?? null;

        if ($redirectUrl) {
            $urlParams = parse_url($redirectUrl);
            $pathParts = explode('/', $urlParams['path']);
            $elementId = end($pathParts);
            $queryParams = [];

            foreach (explode('&', $urlParams['query']) as $urlParam) {
                $urlParamParts = explode('=', $urlParam);
                $queryParams[$urlParamParts[0]] = $urlParamParts[1];
            }

            $draftId = $queryParams['draftId'] ?? null;

            return Entry::find()
                ->id($elementId)
                ->siteId($siteId)
                ->draftId($draftId)
                ->status(null)
                ->one();
        }

        return null;
    }
}
