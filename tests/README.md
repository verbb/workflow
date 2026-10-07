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

The Pest suite exercises actual Craft web controllers and Workflow event handlers, with a fresh application process for every request. Fixtures include two sites, non-admin identities with different Craft and Workflow permissions, two ordered reviewer groups, and entries with required fields, relations, tables, and Matrix blocks.

| Area | Cases |
| --- | --- |
| Entry points | Front-end JSON and HTML forms, new CP drafts, drafts of published entries, direct entry submissions, native draft application, submission status changes, and bulk actions |
| Lifecycle | Submission, ordered reviews, publisher approval and rejection, resubmission, revocation, new submissions after completion, optional approve-only actions, and repeated or stale requests |
| Authorization | Editor ownership, reviewer ordering, publisher groups per site, Craft entry and site permissions, anonymous requests, self-approval settings and events, wrong entries, wrong drafts, wrong sites, and disabled sections |
| Validation and rollback | Required content and notes, deleted drafts, migration of existing review data, injected review persistence failures, and unchanged live content after failed approvals |
| Content | Multi-site propagation, versioning enabled and disabled, scheduled and expired entries, relations, tables, nested Matrix entries, draft locks, publisher edits, and review snapshots |
| Notifications | Recipient routing across review stages, editor and author messages, notification toggles, cancellation events, and no duplicate messages on failed or repeated actions |
| Concurrency | Real competing PHP processes with separate database connections: approval against approval, approval against rejection, and rejection against rejection, for draft and direct-entry submissions |
| Queries and history | Boolean filters, combined editor/reviewer/publisher filters, Twig queries, GraphQL section/site/inactive scopes, review comparisons, deletion permissions, content differences, and note encoding |

Add regression cases under `tests/Feature` or `tests/Services` and use the request helpers in `tests/Pest.php`. `tests/Support/request.php` boots the normal Craft web application, selects a fixture identity, and dispatches the requested controller action. CSRF validation is disabled only for these CLI requests. Keep fixture creation in `tests/runtime/seed.php` or the request worker; do not repair production schema or services in the test bootstrap. Tests must preserve their own targets and avoid depending on execution order.

The integration baseline is Craft 5.11.4, PHP 8.3, and MySQL 8. PostgreSQL, other Craft/PHP versions, third-party field plugins, and real mail transports need separate compatibility coverage.

## Results and CI

Results are written to `.cache/verbb-tests/result.json`, `.cache/verbb-tests/junit.xml`, and `.cache/verbb-tests/latest.log`. Craft logs remain under the generated application's storage. Setup failures stop the run; an empty or incomplete suite cannot report success.

The GitHub Actions workflow runs the clean-install Pest suite on pull requests and manual dispatch, and uploads diagnostics. The dedicated DDEV project can be stopped with `ddev stop` or removed with `ddev delete` from this checkout when no tests are running; the next run recreates it.
