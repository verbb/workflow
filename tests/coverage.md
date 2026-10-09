# Workflow scenario coverage

The suite follows the same submissions across requests and checks persisted state, content, review history, and notifications. Dataset combinations expand the scenarios below; the test count does not mean every possible combination of Craft settings has been exercised.

| Entry state or workflow | Actor and action | Expected behaviour | Tests |
| --- | --- | --- | --- |
| Front-end unpublished entry | Editor submits HTML or JSON, including custom field locations | One pending submission; submitted content reaches the draft | `Feature/ApprovalTest.php`, `Feature/EntryPointsTest.php`, `Feature/FrontendBoundaryTest.php`, `browser/workflow.spec.cjs` |
| Invalid front-end submission | Guest, missing create permission, or missing required content | An explicit authentication, permission, or validation failure; no approval history | `Feature/FrontendBoundaryTest.php` |
| New CP draft, existing-entry draft, or direct-entry submission | Publisher approves and publishes or applies on either site, including a secondary-site draft while the primary site remains current | Correct target and publication state; exactly one approval | `Feature/ApprovalTest.php`, `Feature/StatusTest.php` |
| Provisional draft | Authorized editor autosaves, updates, reopens, and discards | Private edits persist without creating a submission or changing canonical content | `Feature/ProvisionalDraftsTest.php`, `browser/workflow.spec.cjs` |
| Provisional draft converted to a saved draft | Editor submits, followed by zero, one, or two reviewer stages | Conversion and submission work together or in separate requests; approval publishes the submitted content | `Feature/ProvisionalDraftsTest.php` |
| Pending draft and an unrelated provisional draft | Publisher approves the submitted draft | The unrelated provisional content is never published | `Feature/ProvisionalDraftsTest.php` |
| Pending locked draft | Editor, another editor, current/later reviewer, publisher, outsider, or admin saves | Only actors authorized for the current workflow can edit; disabled locking is tested separately | `Feature/DraftEditingTest.php`, `Feature/ProvisionalDraftsTest.php` |
| Zero, one, or two reviewer stages | Editor submits; reviewers act in sequence; publisher completes | Correct next group, review count, status, and recipients | `Feature/ReviewLifecycleTest.php`, `Feature/NotificationsTest.php` |
| Several members of one reviewer group | Either member approves; another tries afterward or concurrently | One stage decision and one next-stage notification | `Feature/GroupMembershipTest.php`, `Feature/DistinctActorsConcurrencyTest.php` |
| One person in both reviewer groups | The same person approves each stage | Separate recorded decisions advance both stages and then finish reviewing | `Feature/GroupMembershipTest.php` |
| Reviewer also belongs to publisher group | The person uses a publisher action | Existing publisher override authority is retained; the review is recorded as a publisher decision | `Feature/GroupMembershipTest.php` |
| Pending submission after group/permission changes | Removed reviewer or publisher acts; replacement acts; individual Craft permissions are revoked | Current permissions are checked and authorized replacements can continue | `Feature/GroupMembershipTest.php` |
| Empty reviewer group or reordered/inserted stages | Later reviewer or publisher acts | No silent reviewer-stage bypass; publisher override remains available; completed groups stay recorded | `Feature/GroupMembershipTest.php` |
| Previously completed review stage | Reviewer changes group membership | Progress stays attached to the recorded group | `Feature/GroupMembershipTest.php` |
| Review history from before stage metadata existed | Next reviewer acts | Historical approvals are replayed in configured order | `Feature/GroupMembershipTest.php` |
| Second-stage rejection, repeated three times | Editor corrects and resubmits through front-end, draft, or direct-entry flows | Each cycle restarts review; corrected content and complete history survive | `Feature/InterruptedLifecycleTest.php` |
| Entry author changes during review | New author and original submitting editor try revocation | Authorship alone does not transfer submission ownership | `Feature/InterruptedLifecycleTest.php` |
| Several submitted drafts of one entry | Publisher approves each separately | Draft targets and submission histories remain independent | `Feature/InterruptedLifecycleTest.php`, `Feature/ApprovalGuardsTest.php` |
| Canonical content changes during review | Publisher applies the pending draft | Unedited canonical attributes survive while submitted field changes apply | `Feature/InterruptedLifecycleTest.php` |
| Two publishers, two reviewers, or editor versus publisher | Competing approvals, rejections, or revocation | One valid transition wins; no duplicate reviews or notifications | `Feature/ConcurrencyTest.php`, `Feature/DistinctActorsConcurrencyTest.php` |
| Singles and structures on either site | Submit, review, and publish with all-site or no propagation configured | Correct content and one submission, with expected localization counts where applicable | `Feature/SectionVariantsTest.php` |
| Shared and site-translated fields | Approve a localized draft | Shared values propagate; other localized values remain unchanged | `Feature/SectionVariantsTest.php` |
| Disabled localization | Publisher approves and applies | Content applies without enabling that localization | `Feature/SectionVariantsTest.php` |
| Matrix, relations, and tables | Add/replace or remove content in the pending draft | Canonical content changes only on approval; nested entries do not create extra submissions | `Feature/ContentVariantsTest.php`, `Feature/SectionVariantsTest.php` |
| Invalid content, missing notes, stale target, deleted draft, or persistence failure | Approval through editor, status, or native Craft actions | Failure preserves canonical content and pending history | `Feature/ApprovalGuardsTest.php`, `Feature/ContentVariantsTest.php`, `Feature/StatusTest.php` |
| Browser already open when publisher is suspended | Publisher clicks approval | Craft rejects the session and the submission stays pending | `browser/workflow.spec.cjs` |
| Submission history and queries | Compare/delete reviews, filter submissions, or query GraphQL | Permissions and scope filters hold; stage metadata is excluded from content comparisons | `Feature/ReviewHistoryTest.php`, `Feature/GraphqlTest.php`, `Services/SubmissionQueriesTest.php`, `Services/ReviewDataTest.php` |

## Assertions and isolation

Positive paths assert successful responses as well as stored results. Permission tests reject unexpected PHP/runtime exceptions, and the expanded tests restore changed group memberships and permissions in `finally` blocks. Fresh request processes prevent cached identities and settings from masking changes between requests. Concurrent tests use different database connections and a start barrier.

The browser suite exercises actual HTTP authentication, CSRF protection, autosaves, and form controls. CLI controller tests intentionally bypass browser authentication setup; suspended-session behaviour therefore belongs in browser coverage.

Four mutation checks verify that tests fail when required-note validation, draft locking, completed filtering, or reviewer-stage progression is deliberately broken. Their results describe these four safeguards, not a full mutation score for the plugin.

## Remaining boundaries

The current compatibility baseline is Craft 5.11.4, PHP 8.3, MySQL 8, and Chromium. Other supported Craft/PHP versions, PostgreSQL, Firefox/WebKit, third-party fields, real mail delivery failures, arbitrary propagation groups/languages, deeply nested Matrix trees, and every possible workflow reconfiguration require additional fixtures or compatibility jobs.

Older reviews do not contain the group that approved them. Their compatibility fallback uses configured stage order; historical group/configuration changes made before that metadata existed cannot be reconstructed reliably. New reviews record the group UID.

When adding a regression, identify the entry state, entry point, site/section policy, actor memberships and permissions, existing review history, action, and expected side effects. Assert both the intended outcome and what must remain unchanged. Include a successful control for denied paths so a broken fixture cannot masquerade as a passing permission check.
