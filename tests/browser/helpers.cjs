const {execFileSync} = require('node:child_process');
const {expect} = require('playwright/test');

function request(input) {
    const output = execFileSync('ddev', ['exec', '--raw', '--', 'php', 'tests/Support/request.php', Buffer.from(JSON.stringify(input)).toString('base64')], {encoding: 'utf8'});
    const result = JSON.parse(output);
    expect(result.exception).toBeUndefined();
    return result;
}

async function login(page, username) {
    await page.goto('/admin/login');
    await page.getByRole('textbox', {name: 'Username or Email', exact: true}).fill(username);
    await page.getByRole('textbox', {name: 'Password', exact: true}).fill('Workflow-testing-password-815!');
    await page.getByRole('button', {name: 'Sign in', exact: true}).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.waitForLoadState('domcontentloaded');
}

function draft(submitted = false) {
    const fixture = request({fixture: 'draft', author: 'editor'});
    if (!submitted) return fixture;
    return request({route: 'elements/save-draft', user: 'editor', target: fixture.target, body: {'workflow-action': 'save-submission', enabled: true, enabledForSite: true}});
}

module.exports = {request, login, draft};
