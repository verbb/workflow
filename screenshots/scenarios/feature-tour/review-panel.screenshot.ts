import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedWorkflowFixture } from '../../support/fixtures';

let draftRoute = '/admin/entries';

export default defineScreenshotScenario({
    id: 'workflow-feature-tour-review-panel',
    output: 'feature-tour/review-panel.png',
    route: () => draftRoute,
    viewport: { width: 1440, height: 980, deviceScaleFactor: 2 },
    async setup(context) {
        draftRoute = (await seedWorkflowFixture(context)).draftRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.workflow-history-pane', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Awaiting approval' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                (() => {
                    document.activeElement?.blur();
                    window.scrollTo(0, 0);

                    document.querySelector('.workflow-submission-heading .fieldtoggle')?.click();
                    const currentHistory = document.querySelector('.workflow-history-heading .fieldtoggle');
                    currentHistory?.click();

                    document.querySelector('#main-container')?.scrollTo(0, 0);
                    document.querySelector('#content-container')?.scrollTo(0, 0);
                    document.querySelector('#details-container')?.scrollTo(0, 0);
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 350 } },
    ],
    target: {
        type: 'clip',
        x: 226,
        y: 0,
        width: 1214,
        height: 900,
    },
    caption: 'A pending entry shown in Craft with its real approval controls and review history.',
    intent: 'Show Workflow where editors and publishers use it: alongside the current Craft entry editor.',
});
