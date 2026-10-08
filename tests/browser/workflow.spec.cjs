const {test, expect} = require('playwright/test');
const {request, login, draft} = require('./helpers.cjs');

async function openDraft(page, fixture, user) {
    await login(page, user);
    const url = new URL(fixture.cpUrl);
    await page.goto(url.pathname + url.search);
}

async function approve(page) {
    await page.locator('.workflow-submission-footer').getByRole('button', {name: 'Actions', exact: true}).click();
    await page.getByRole('button', {name: 'Approve and Publish', exact: true}).click();
}

const inspect = fixture => request({target: fixture.target});

test('editor submits and revokes a draft through the sidebar', async ({page}) => {
    const fixture = draft();
    await openDraft(page, fixture, 'editor');
    await page.getByRole('textbox', {name: 'Summary Required', exact: true}).fill('Browser-reviewed content');
    await page.getByPlaceholder('Notes about your submission', {exact: true}).fill('Ready for review');
    await page.getByText('Save draft and submit for review', {exact: true}).click();
    await expect(page.getByText('Awaiting approval', {exact: true})).toBeVisible();
    await expect(page.getByRole('button', {name: 'Save draft', exact: true})).toBeHidden();
    expect(inspect(fixture).submission.pending).toBe(true);
    await page.getByText('Revoke submission', {exact: true}).click();
    await expect(page.getByText('Save draft and submit for review', {exact: true})).toBeVisible();
    const result = inspect(fixture);
    expect(result.submission.status).toBe('revoked');
    expect(result.entry.summary).toBe('Browser-reviewed content');
});

test('publisher approves through the Workflow action menu', async ({page}) => {
    const fixture = draft(true);
    await openDraft(page, fixture, 'publisher');
    await page.getByPlaceholder('Notes about your response', {exact: true}).fill('Reviewed in browser');
    await approve(page);
    await expect.poll(() => inspect(fixture).submission.complete).toBe(true);
    const result = inspect(fixture);
    expect(result.canonical.summary).toBe('Revised summary');
    expect(result.submission.reviewCount).toBe(2);
    expect(result.draftExists).toBe(false);
});

test('required publisher notes are enforced before publishing', async ({page}) => {
    const fixture = draft(true);
    request({persistNotesRequirement: true});
    try {
        await openDraft(page, fixture, 'publisher');
        await approve(page);
        await expect(page.getByText('Error: Notes are required', {exact: true}).first()).toBeVisible();
        expect(inspect(fixture).submission.pending).toBe(true);
        await page.getByPlaceholder('Notes about your response (required)', {exact: true}).fill('Required response');
        await approve(page);
        await expect.poll(() => inspect(fixture).submission.complete).toBe(true);
    } finally {
        request({persistNotesRequirement: false});
    }
});

test('native Apply draft completes the pending Workflow submission', async ({page}) => {
    const fixture = draft(true);
    await openDraft(page, fixture, 'publisher');
    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('button', {name: 'Apply draft', exact: true}).click();
    await expect.poll(() => inspect(fixture).submission.complete).toBe(true);
    expect(inspect(fixture).submission.reviewCount).toBe(2);
});

test('front-end HTML form submits with real login and CSRF validation', async ({page}) => {
    await login(page, 'editor');
    await page.goto('/');
    const title = `Browser submission ${Date.now()}`;
    await page.getByRole('textbox', {name: 'Title', exact: true}).fill(title);
    await page.getByRole('textbox', {name: 'Summary', exact: true}).fill('Front-end browser content');
    await page.getByRole('button', {name: 'Submit for review', exact: true}).click();
    await expect(page.getByRole('heading', {name: 'Submission received'})).toBeVisible();
    const result = request({findTitle: title});
    expect(result.submission.pending).toBe(true);
    expect(result.submission.reviewCount).toBe(1);
    expect(result.entry.summary).toBe('Front-end browser content');
});

test('canonical editing creates a private provisional autosave', async ({page}) => {
    const fixture = request({fixture: 'canonical', author: 'canonicalEditor'});
    await openDraft(page, fixture, 'canonicalEditor');
    const autosave = page.waitForResponse(response => response.request().method() === 'POST' && decodeURIComponent(response.url()).includes('elements/save-draft'));
    await page.getByRole('textbox', {name: 'Summary Required', exact: true}).fill('Private browser autosave');
    await page.getByRole('heading', {level: 1}).click();
    await autosave;
    const privateDraft = request({findProvisionalOf: fixture.target.canonicalId, user: 'canonicalEditor'});
    expect(privateDraft.entry.provisional).toBe(true);
    expect(privateDraft.entry.summary).toBe('Private browser autosave');
    expect(privateDraft.canonical.summary).toBe('Original summary');
    expect(privateDraft.submissionCount).toBe(0);
});

test('a suspended publisher cannot approve from an already open editor', async ({page}) => {
    const fixture = draft(true);
    await openDraft(page, fixture, 'publisher');
    const changed = request({mutateUsers: {publisher: {suspended: true}}});
    try {
        const decision = page.waitForResponse(response => response.request().method() === 'POST' && (response.request().postData() || '').includes('approve-submission'));
        await approve(page);
        const response = await decision;
        expect([302, 401, 403]).toContain(response.status());
        const result = request({target: fixture.target, user: 'admin'});
        expect(result.submission.pending).toBe(true);
        expect(result.submission.reviewCount).toBe(1);
        expect(result.canonical.summary).toBe('Original summary');
    } finally {
        request({mutateUsers: changed.restoreUsers, user: 'admin'});
    }
});
