# Testing

Install [DDEV](https://docs.ddev.com/en/stable/users/install/ddev-installation/) and a supported Docker provider, then run from this plugin checkout:

```sh
ddev test
ddev test --filter='approves submitted content'
```

The command starts `workflow-craft5-tests`, installs a clean Craft application and this checkout through Composer, creates fixtures, and runs Pest. It requires no separate Craft site, host PHP, host Composer, or `.env.testing`. `composer test` calls the same command.

Each run resets only the dedicated test database, project configuration, and storage under `.cache/verbb-tests/app`. Dependencies are cached. The runtime rejects external databases and parallel workers; run the suite serially. No private workspace tooling or sibling checkout is needed.

The initial baseline is Craft 5.11.4, PHP 8.3, MySQL 8.0, and Pest 3. The test application's dependencies are locked in `tests/runtime/composer.lock`. Run `ddev test --update-lock` to deliberately update them, then review the lock diff and test results. The plugin's production Craft requirement is unchanged.

## Regression coverage

The Pest suite exercises actual Craft web controllers and Workflow event handlers, with a fresh application process for every request. Fixtures include two sites, non-admin identities with different Craft and Workflow permissions, two ordered reviewer groups, overlapping memberships, and entries with required fields, relations, tables, and Matrix blocks.

| Area | Cases |
| --- | --- |
| Provisional drafts | Explicit autosave creation and updates, reopening, discarding, conversion before submission, and isolation from another pending draft |
| Entry points | Front-end JSON and HTML forms, new CP drafts, drafts of published entries, direct entry submissions, native draft application, submission status changes, and bulk actions |
| Lifecycle | Submission, ordered reviews, publisher approval and rejection, resubmission, revocation, new submissions after completion, optional approve-only actions, and repeated or stale requests |
| Authorization | Editor ownership, reviewer ordering, publisher groups per site, Craft entry and site permissions, anonymous requests, self-approval settings and events, wrong entries, wrong drafts, wrong sites, and disabled sections |
| Validation and rollback | Required content and notes, deleted drafts, migration of existing review data, injected review persistence failures, and unchanged live content after failed approvals |
| Content | Multi-site propagation, versioning enabled and disabled, scheduled and expired entries, relations, tables, nested Matrix entries, draft locks, publisher edits, and review snapshots |
| Notifications | Recipient routing across review stages, editor and author messages, notification toggles, cancellation events, and no duplicate messages on failed or repeated actions |
| Concurrency | Real competing PHP processes with separate database connections: approval against approval, approval against rejection, and rejection against rejection, for draft and direct-entry submissions; different publishers, same-stage reviewers, and editor revocation racing publisher approval |
| Queries and history | Boolean filters, combined editor/reviewer/publisher filters, Twig queries, GraphQL section/site/inactive scopes, review comparisons, deletion permissions, content differences, and note encoding |
| Changing workflows | Shared reviewer groups, overlapping reviewer/publisher roles, membership and Craft permission changes between requests, empty groups, reordered and inserted stages, legacy review history, and repeated second-stage rejection cycles |
| Additional content setups | Singles and structures, all-site and no propagation, translated versus shared fields, disabled localizations, sibling drafts, canonical edits during review, and removal of nested content and relations |

Add regression cases under `tests/Feature` or `tests/Services` and use the request helpers in `tests/Pest.php`. `tests/Support/request.php` boots the normal Craft web application, selects a fixture identity, and dispatches the requested controller action. CSRF validation is disabled only for these CLI requests. Keep fixture creation in `tests/runtime/seed.php` or the request worker; do not repair production schema or services in the test bootstrap. Tests must preserve their own targets and avoid depending on execution order.

## Browser tests

The small Playwright suite complements Pest with actual Chromium interactions: editor submission, draft locking and revocation, the publisher action menu, required-note errors and recovery, native Apply draft, front-end HTML forms, private provisional autosaves, and suspension of an already signed-in publisher. Browser requests use normal fixture-account login and CSRF validation. Fixture setup and database assertions use the same CLI request worker; there is no browser-accessible authentication shortcut. Notifications remain disabled in the browser fixtures, while Pest notification tests restrict delivery to local files.

```sh
npm ci
npx playwright install chromium
npm run test:browser
```

`test:browser` prepares a clean test application first. To run against an application already created by `ddev test`, use `npm run test:browser:installed`. Run only one suite at a time: a clean installation resets the shared test fixtures. Browser tests use one worker and fresh browser contexts; traces and screenshots are retained on failure under `output/playwright`.

The tested baseline is MySQL 8, PHP 8.3, Craft 5.11.4, and Chromium. PostgreSQL, other Craft/PHP versions, Firefox/WebKit, third-party field plugins, and real mail transports are not covered by this suite. Add these as deliberate compatibility jobs or dedicated fixtures when relevant; passing this baseline does not establish compatibility across that wider matrix.

## Mutation checks

After `ddev test`, run:

```sh
ddev exec --raw -- php tests/runtime/mutate.php
```

These targeted checks disable required approval notes, draft locking, completed-submission filtering, and reviewer-stage advancement one at a time. Each case must first pass without modification and then fail an assertion with the mutation. Syntax errors, empty suites, and execution errors do not count as caught regressions. Mutated classes are loaded only by CLI tests from the owned runtime directory; the plugin source files are never modified. Reports remain in `.cache/verbb-tests/mutations`.

The [scenario matrix](coverage.md) maps behaviours to test files and records boundaries that still need separate coverage. This is behavioural regression coverage, not a measured line-coverage percentage or an exhaustive Cartesian product of every setting.

## Results and CI

Results are written to `.cache/verbb-tests/result.json`, `.cache/verbb-tests/junit.xml`, and `.cache/verbb-tests/latest.log`. Craft logs remain under the generated application's storage. Setup failures stop the run; an empty or incomplete suite cannot report success.

The GitHub Actions workflow runs the clean-install Pest suite, mutation checks, and browser suite on pull requests and manual dispatch, and uploads diagnostics. Browser JUnit results are written to `.cache/verbb-tests/browser-junit.xml`. The dedicated DDEV project can be stopped with `ddev stop` or removed with `ddev delete` from this checkout when no tests are running; the next run recreates it.
