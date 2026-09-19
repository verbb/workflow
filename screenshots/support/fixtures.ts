import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type WorkflowFixture = {
    draftRoute: string;
    submissionRoute: string;
    compareRoute: string;
    reviewCount: number;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-workflow.php'), 'utf8');

/** Seed a genuine pending Workflow submission with review history and field changes. */
export async function seedWorkflowFixture(context: ScreenshotSetupContext): Promise<WorkflowFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-workflow' });
    const fixture = JSON.parse(output.trim()) as WorkflowFixture;

    if (!fixture.draftRoute || !fixture.submissionRoute || !fixture.compareRoute || fixture.reviewCount !== 3) {
        throw new Error(`Invalid Workflow fixture payload: ${output}`);
    }

    return fixture;
}
