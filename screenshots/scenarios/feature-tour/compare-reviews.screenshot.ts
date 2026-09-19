import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedWorkflowFixture } from '../../support/fixtures';

let compareRoute = '/admin/workflow';

export default defineScreenshotScenario({
    id: 'workflow-feature-tour-compare-reviews',
    output: 'feature-tour/compare-reviews.png',
    route: () => compareRoute,
    viewport: { width: 1440, height: 980, deviceScaleFactor: 2 },
    async setup(context) {
        compareRoute = (await seedWorkflowFixture(context)).compareRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.workflow-compare-reviews', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Amelia Hart' },
        { type: 'text', text: 'Noah Bennett' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                (() => {
                    document.activeElement?.blur();
                    window.scrollTo(0, 0);
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#main-container',
        x: 0,
        y: 0,
        width: 1360,
        height: 600,
    },
    caption: 'The previous and current entry states compared field by field before approval.',
    intent: 'Show Workflow using the real Craft field layout to make editorial changes easy to review.',
});
