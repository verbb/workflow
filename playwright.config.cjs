const {defineConfig} = require('playwright/test');
const {execFileSync} = require('node:child_process');

const project = JSON.parse(execFileSync('ddev', ['describe', '-j'], {encoding: 'utf8'})).raw;
if (project.name !== 'workflow-craft5-tests') throw new Error('Browser tests require the dedicated Workflow test project.');

module.exports = defineConfig({
    testDir: './tests/browser',
    testMatch: '**/*.spec.cjs',
    workers: 1,
    fullyParallel: false,
    timeout: 60000,
    expect: {timeout: 10000},
    outputDir: 'output/playwright',
    reporter: [['list'], ['junit', {outputFile: '.cache/verbb-tests/browser-junit.xml'}]],
    use: {baseURL: project.primary_url, browserName: 'chromium', ignoreHTTPSErrors: true, trace: 'retain-on-failure', screenshot: 'only-on-failure'},
});
