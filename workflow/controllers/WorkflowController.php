<?php
namespace Craft;

class WorkflowController extends BaseController
{
    // Public Methods
    // =========================================================================

    //
    // Control Panel
    //

    public function actionIndex()
    {
        $submissions = craft()->workflow_submissions->getAll();

        $this->renderTemplate('workflow/index', array(
            'submissions' => $submissions,
        ));
    }

    public function actionSettings()
    {
        $settings = craft()->workflow->getSettings();

        $this->renderTemplate('workflow/settings', array(
            'settings' => $settings,
        ));
    }

    //
    // Front-End
    //
    public function actionSendForSubmission()
    {
        $user = craft()->userSession->getUser();

        $model = new Workflow_SubmissionModel();
        $model->elementId = craft()->request->getParam('entryId');
        $model->draftId = craft()->request->getParam('draftId');
        $model->editorId = $user->id;
        $model->dateApproved = null;

        if (craft()->workflow_submissions->save($model)) {
            craft()->userSession->setNotice(Craft::t('Entry submitted for approval.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not submit for approval.'));
        }

        // Redirect page to the entry as its not a form submission
        craft()->request->redirect(craft()->request->urlReferrer);
    }

    public function actionApproveSubmission()
    {
        $user = craft()->userSession->getUser();

        $submissionId = craft()->request->getParam('submissionId');
        $model = craft()->workflow_submissions->getById($submissionId);
        $model->approved = true;
        $model->publisherId = $user->id;
        $model->dateApproved = new DateTime;

        if (craft()->workflow_submissions->approveSubmission($model)) {
            craft()->userSession->setNotice(Craft::t('Entry approved and published.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not approve and publish.'));
        }

        // Redirect page to the entry as its not a form submission
        craft()->request->redirect(craft()->request->urlReferrer);
    }





    // Private Methods
    // =========================================================================

    /*private function _response($model = null)
    {
        // Handle Ajax response
        if (craft()->request->isAjaxRequest()) {
            $this->returnJson($model);
        } else {
            $this->_redirect($model);
        }
    }

    private function _redirect($model)
    {
        $url = craft()->request->getPost('redirect');

        if ($url === null) {
            $url = craft()->request->getParam('return');

            if ($url === null) {
                $url = craft()->request->getUrlReferrer();

                if ($url === null) {
                    $url = '/';
                }
            }
        }

        craft()->request->redirect($url);
    }*/
}
