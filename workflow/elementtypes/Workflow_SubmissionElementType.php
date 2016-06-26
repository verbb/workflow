<?php
namespace Craft;

class Workflow_SubmissionElementType extends BaseElementType
{
    // Public Methods
    // =========================================================================

    public function getName()
    {
        return Craft::t('Workflow Submission');
    }

    public function hasContent()
    {
        return false;
    }

    public function hasTitles()
    {
        return false;
    }

    public function hasStatuses()
    {
        return true;
    }

    public function getStatuses()
    {
        return array(
            Workflow_SubmissionModel::APPROVED => Craft::t('Approved'),
            Workflow_SubmissionModel::PENDING => Craft::t('Pending'),
        );
    }

    public function getSources($context = null)
    {
        $settings = craft()->workflow->getSettings();

        $sources = array(
            '*' => array(
                'label' => Craft::t('All Submissions'),
            ),
        );

        $submissions = craft()->workflow_submissions->getAll();

        foreach ($submissions as $submission) {
            $elementType = craft()->elements->getElementType($submission->owner->elementType);
            $key = 'elements:'.$elementType->classHandle;

            $sources[$key] = array('heading' => $elementType->name);

            $sources[$key.':all'] = array(
                'label' => Craft::t('All ' . $elementType->name),
                'criteria' => array('elementType' => $submission->owner->elementType),
            );
        }

        return $sources;
    }

    public function populateElementModel($row)
    {
        return Workflow_SubmissionModel::populateModel($row);
    }

    public function defineTableAttributes($source = null)
    {
        return array(
            'id'            => Craft::t('Entry'),
            'editor'        => Craft::t('Editor'),
            'dateCreated'   => Craft::t('Date Submitted'),
            'publisher'     => Craft::t('Publisher'),
            'dateApproved'  => Craft::t('Date Approved'),
        );
    }

    public function defineSortableAttributes()
    {
        return array(
            'id'            => Craft::t('Entry'),
            'editor'        => Craft::t('Editor'),
            'dateCreated'   => Craft::t('Date Submitted'),
            'publisher'     => Craft::t('Publisher'),
            'dateApproved'  => Craft::t('Date Approved'),
        );
    }

    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        switch ($attribute) {
            case 'publisher':
            case 'editor': {
                if ($element->$attribute) {
                    return "<a href='" . $element->$attribute->cpEditUrl . "'>" . $element->$attribute . "</a>";
                }
            }
            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    public function defineCriteriaAttributes()
    {
        return array(
            'ownerId'       => array(AttributeType::Number),
            'draftId'       => array(AttributeType::Number),
            'editorId'      => array(AttributeType::Number),
            'publisherId'   => array(AttributeType::Number),
            'status'        => array(AttributeType::String, 'default' => Workflow_SubmissionModel::PENDING),
            'order'         => array(AttributeType::String, 'default' => 'dateCreated asc'),
        );
    }

    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
        ->addSelect('
            workflow_submissions.ownerId,
            workflow_submissions.draftId,
            workflow_submissions.editorId,
            workflow_submissions.publisherId,
            workflow_submissions.status
        ')
        ->join('workflow_submissions workflow_submissions', 'workflow_submissions.id = elements.id');

        if ($criteria->ownerId) {
            $query->andWhere(DbHelper::parseParam('workflow_submissions.ownerId', $criteria->ownerId, $query->params));
        }

        if ($criteria->draftId) {
            $query->andWhere(DbHelper::parseParam('workflow_submissions.draftId', $criteria->draftId, $query->params));
        }

        if ($criteria->editorId) {
            $query->andWhere(DbHelper::parseParam('workflow_submissions.editorId', $criteria->editorId, $query->params));
        }

        if ($criteria->publisherId) {
            $query->andWhere(DbHelper::parseParam('workflow_submissions.publisherId', $criteria->publisherId, $query->params));
        }

        if ($criteria->status) {
            $query->andWhere(DbHelper::parseParam('workflow_submissions.status', $criteria->status, $query->params));
        }

        if ($criteria->dateCreated) {
            $query->andWhere(DbHelper::parseDateParam('workflow_submissions.dateCreated', $criteria->dateCreated, $query->params));
        }
    }
    
    public function getAvailableActions($source = null)
    {
        return array('Workflow_SubmissionsStatus');
    }

}