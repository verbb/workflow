<?php
namespace Craft;

class Workflow_SubmissionsService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    public function getCriteria(array $attributes = array())
    {
        $attributes['status'] = null;
        return craft()->elements->getCriteria('Workflow_Submission', $attributes);
    }

    public function getAll()
    {
        return $this->getCriteria()->find();
    }

    public function getAllByOwnerId($ownerId, $draftId)
    {
        return $this->getCriteria(array('ownerId' => $ownerId, 'draftId' => $draftId))->find();
    }

    public function getById($id)
    {
        return $this->getCriteria(array('limit' => 1, 'id' => $id))->first();
    }
    
    public function save(Workflow_SubmissionModel $model)
    {
        $isNewSubmission = !$model->id;

        if ($model->id) {
            $record = Workflow_SubmissionRecord::model()->findById($model->id);
        } else {
            $record = new Workflow_SubmissionRecord();
        }

        $record->setAttributes($model->getAttributes(), false);

        $record->validate();
        $model->addErrors($record->getErrors());

        if ($model->hasErrors()) {
            return false;
        }

        // Fire an 'onBeforeSaveSubmission' event
        $event = new Event($this, array('submission' => $model));
        $this->onBeforeSaveSubmission($event);

        // Allow event to cancel submission saving
        if (!$event->performAction) {
            return false;
        }

        if (!craft()->elements->saveElement($model)) {
            return false;
        }

        if ($isNewSubmission) {
            $record->id = $model->id;
        }

        $record->save(false);

        if ($isNewSubmission) {
            $model->id = $record->id;
        }

        // Fire an 'onSaveSubmission' event
        $this->onSaveSubmission(new Event($this, array('submission' => $model)));

        if ($isNewSubmission) {
            // Trigger notification to publisher
            $this->_sendPublisherNotificationEmail($model);
        }

        return true;
    }

    public function approveSubmission(Workflow_SubmissionModel $model)
    {
        // Fire an 'onBeforeApproveSubmission' event
        $event = new Event($this, array('submission' => $model));
        $this->onBeforeApproveSubmission($event);

        // Allow event to cancel submission saving
        if (!$event->performAction) {
            return false;
        }
        
        // Set the entry to be enabled first
        $entry = craft()->entries->getEntryById($model->owner->id);
        $entry->enabled = true;

        craft()->entries->saveEntry($entry);

        // Then, update our submission record with the necessary information
        $result = $this->save($model);

        // Fire an 'onSaveSubmission' event
        $this->onApproveSubmission(new Event($this, array('submission' => $model)));

        return $result;
    }



    // Event Handlers
    // =========================================================================

    public function onBeforeSaveSubmission(\CEvent $event)
    {
        $this->raiseEvent('onBeforeSaveSubmission', $event);
    }

    public function onSaveSubmission(\CEvent $event)
    {
        $this->raiseEvent('onSaveSubmission', $event);
    }

    public function onBeforeApproveSubmission(\CEvent $event)
    {
        $this->raiseEvent('onBeforeApproveSubmission', $event);
    }

    public function onApproveSubmission(\CEvent $event)
    {
        $this->raiseEvent('onApproveSubmission', $event);
    }




    // Private Methods
    // =========================================================================

    private function _sendPublisherNotificationEmail(Workflow_SubmissionModel $model)
    {
        $settings = craft()->workflow->getSettings();

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->groupId = $settings->publisherUserGroup;
        $publishers = $criteria->find();

        foreach ($publishers as $key => $user) {
            craft()->email->sendEmailByKey($user, 'workflow_publisher_notification', array(
                'submission' => $model,
            ));
        }
    }
}