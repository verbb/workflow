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
        return $this->getCriteria(array('ownerId' => $ownerId, 'draftId' => $draftId, 'order' => 'dateCreated asc'))->find();
    }

    public function getById($id)
    {
        return $this->getCriteria(array('limit' => 1, 'id' => $id))->first();
    }
    
    public function save(Workflow_SubmissionModel $model)
    {
        $settings = craft()->workflow->getSettings();

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
            WorkflowPlugin::log(print_r($model->getAllErrors(), true), LogLevel::Error, true);

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
            if ($settings->publisherNotifications) {
                $this->_sendPublisherNotificationEmail($model);
            }
        }

        return true;
    }

    public function approveSubmission(Workflow_SubmissionModel $model, $draft)
    {
        $settings = craft()->workflow->getSettings();

        // Fire an 'onBeforeApproveSubmission' event
        $event = new Event($this, array('submission' => $model));
        $this->onBeforeApproveSubmission($event);

        // Allow event to cancel submission saving
        if (!$event->performAction) {
            return false;
        }

        // Check for approving a Draft - need to publish not just save
        if ($draft) {
            $draft->enabled = true;
            craft()->entryRevisions->publishDraft($draft);

            // The Draft entry has now been deleted, so we don't need to update the submission record
            // because it no also doesn't exist (due to cascade deleting of records)
            $result = true;
        } else {
            $entry = craft()->entries->getEntryById($model->owner->id);
            $entry->enabled = true;

            craft()->entries->saveEntry($entry);

            // Then, update our submission record with the necessary information
            $result = $this->save($model);           
        }

        // Fire an 'onSaveSubmission' event
        $this->onApproveSubmission(new Event($this, array('submission' => $model)));

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->_sendEditorNotificationEmail($model);
        }

        return $result;
    }

    public function rejectSubmission(Workflow_SubmissionModel $model)
    {
        $settings = craft()->workflow->getSettings();

        // Fire an 'onBeforeRejectSubmission' event
        $event = new Event($this, array('submission' => $model));
        $this->onBeforeRejectSubmission($event);
        
        // Allow event to cancel submission saving
        if (!$event->performAction) {
            return false;
        }

        $result = $this->save($model);
        
        // Fire an 'onSaveSubmission' event
        $this->onRejectSubmission(new Event($this, array('submission' => $model)));

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->_sendEditorNotificationEmail($model);
        }

        return $result;
    }

    public function revokeSubmission(Workflow_SubmissionModel $model)
    {
        return $this->save($model);
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

    public function onBeforeRejectSubmission(\CEvent $event)
    {
        $this->raiseEvent('onBeforeRejectSubmission', $event);
    }
    public function onRejectSubmission(\CEvent $event)
    {
        $this->raiseEvent('onRejectSubmission', $event);
    }




    // Private Methods
    // =========================================================================

    private function _sendPublisherNotificationEmail(Workflow_SubmissionModel $model)
    {
        $settings = craft()->workflow->getSettings();

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->groupId = $settings->publisherUserGroup;

        // Check settings to see if we should email all publishers or not
        if (isset($settings->selectedPublishers)) {
            if ($settings->selectedPublishers != '*') {
                $criteria->id = $settings->selectedPublishers;
            }
        }

        $publishers = $criteria->find();

        foreach ($publishers as $key => $user) {
            craft()->email->sendEmailByKey($user, 'workflow_publisher_notification', array(
                'submission' => $model,
            ));
        }
    }

    private function _sendEditorNotificationEmail(Workflow_SubmissionModel $model)
    {
        $settings = craft()->workflow->getSettings();

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->groupId = $settings->editorUserGroup;
        $criteria->id = $model->editorId;
        $editor = $criteria->first();

        // Only send to the single user editor - not the whole group
        if ($editor) {
            craft()->email->sendEmailByKey($editor, 'workflow_editor_notification', array(
                'submission' => $model,
            ));
        }
    }
}
