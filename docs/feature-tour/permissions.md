# Permissions

Workflow's roles come from user groups. You choose the editor, reviewer and publisher groups in Workflow's settings, and those groups decide who can submit, review and approve an entry from its sidebar. Workflow's own permissions sit alongside that, for the people who need the list of every submission and for publishers who are allowed to approve their own work. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Workflow** is listed under **General**, with Craft's other permissions for reaching each plugin's section. The other two are listed under the **Workflow** heading.

| Permission | What It Allows |
| --- | --- |
| Access Workflow | Reach Workflow's section of the control panel. |
| Submissions | Open the **Submissions** screen listing every submission, open each one, and compare the entry's content between reviews. Members of the publisher group can also edit, change the status of and delete submissions, and delete reviews. |
| Approve own submissions | Approve a submission they submitted themselves, as long as they're in the publisher group. |

**Submissions** only works together with **Access Workflow**, and Workflow only shows its section in the control panel's navigation to people who have both. Neither is needed to submit, review or approve entries, which happens on the entry itself, so editors and reviewers usually don't need them. [Setup](docs:feature-tour/setup) shows a typical set of permissions for each group.

Without **Approve own submissions**, a publisher who submitted an entry for review can't approve it, so someone else has to. The `publisherSelfApprovalUserGroups` setting gives the same allowance to whole user groups for each site, and the `definePublisherSelfApproval` event can change the decision for a particular submission. See [Configuration](docs:get-started/configuration) for that setting. Approving and publishing also needs Craft's permission to save the entry.

Admins have every permission, and Workflow's settings are only available to admins. **Approve own submissions** removes the second pair of eyes that a review process is usually there for, so keep it for publishers who genuinely need to publish their own changes, such as on a small team with only one publisher.
