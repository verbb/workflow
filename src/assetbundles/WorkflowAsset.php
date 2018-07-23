<?php
namespace verbb\workflow\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WorkflowAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/workflow/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/workflow.css',
        ];

        parent::init();
    }
}
