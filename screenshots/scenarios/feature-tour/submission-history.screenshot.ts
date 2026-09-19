import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedWorkflowFixture } from '../../support/fixtures';

let submissionRoute = '/admin/workflow';

export default defineScreenshotScenario({
    id: 'workflow-feature-tour-submission-history',
    output: 'feature-tour/submission-history.png',
    route: () => submissionRoute,
    viewport: { width: 1440, height: 1050, deviceScaleFactor: 2 },
    async setup(context) {
        submissionRoute = (await seedWorkflowFixture(context)).submissionRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Total Reviews' },
        { type: 'text', text: 'Spring campaign launch' },
        { type: 'text', text: 'Please sharpen the opening' },
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
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#main-container',
        x: 0,
        y: 0,
        width: 1260,
        height: 680,
    },
    caption: 'One submission with its editor hand-offs, reviewer response and current pending decision.',
    intent: 'Show the real Workflow submission summary and chronological review trail in Craft 5.',
});
