<?php
namespace verbb\workflow\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SetupAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Plugin Kit's setup checklist, kept out of WorkflowAsset because only the first-run Submissions page needs it.
        $this->sourcePath = '@verbb/workflow/web/assets/cp/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'setup.js',
        ];

        parent::init();
    }
}
