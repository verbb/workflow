# Setup
Workflow uses Craft's user permissions to separate editing from publishing. An editor can prepare content for review while a publisher decides when it becomes public.

## User Groups
To get started, you'll need to create two member groups - lets call them Editor and Publisher. Editor users will be able to create entries, save drafts, and submit these for review. They will not be able to publish entries live. Publishers on the other hand have essentially full access, and are the recipient of email notifications when entries need to be reviewed and approved.

### Editors
Start by making a User Group called "Editor" with the following settings for the section you want Workflow enabled for:

**General**
- ☐ Access the site when the system is off
- ☑ Access the control panel
    - ☐ Access the control panel when the system is offline
    - ☐ Perform Craft CMS and plugin updates
    - ☐ Access Workflow

:::tip
This permission isn't required for [front-end submissions](docs:template-guides/front-end-submission).
:::

**Section - Pages**
- ☑ View entries
    - ☑ Create entries
    - ☐ Save entries
    - ☐ Delete entries
    - ☑ View other users’ entries
        - ☐ Save other users’ entries
        - ☐ Delete other users’ entries
    - ☑ View other users’ drafts
        - ☐ Save other users’ drafts
        - ☐ Delete other users’ drafts

Things to note are disabling access to the Workflow plugin page, and disabling the ability to `Save entries` which controls publishing live changes.

### Reviewers
You can also make use of more "steps" in the review process by adding Reviewers - as many as you like - between Editors and Publishers.

**General**
- ☐ Access the site when the system is off
- ☑ Access the control panel
    - ☐ Access the control panel when the system is offline
    - ☐ Perform Craft CMS and plugin updates
    - ☐ Access Workflow

**Section - Pages**
- ☑ View entries
    - ☑ Create entries
    - ☐ Save entries
    - ☐ Delete entries
    - ☑ View other users’ entries
        - ☐ Save other users’ entries
        - ☐ Delete other users’ entries
    - ☑ View other users’ drafts
        - ☑ Save other users’ drafts
        - ☐ Delete other users’ drafts


### Publishers
Create another User Group called "Publisher" with the following settings:

**General**
- ☐ Access the site when the system is off
- ☑ Access the control panel
    - ☐ Access the control panel when the system is offline
    - ☐ Perform Craft CMS and plugin updates
    - ☑ Access Workflow

**Section - Pages**
- ☑ View entries
    - ☑ Create entries
    - ☑ Save entries
    - ☐ Delete entries
    - ☑ View other users’ entries
        - ☑ Save other users’ entries
        - ☐ Delete other users’ entries
    - ☑ View other users’ drafts
        - ☑ Save other users’ drafts
        - ☐ Delete other users’ drafts

**Workflow**
- ☑ Overview
- ☐ Settings

You can name either of these groups whatever you like, but we'll refer to them as Editor/Reviewer/Publisher throughout the guide.

## Plugin Settings
Next, head to the plugin settings for Workflow, and assign both these groups you created to their respective fields.

## Follow an Entry Through Review

Create separate test users in the Editor and Publisher groups and choose a section configured for Workflow. As the editor, create an entry, add recognisable content and submit it for review. Check that the publisher receives the notification and can open the submitted content.

As the publisher, review the entry and use the approval action when it is ready. Inspect the public page after publishing. Repeat with a change to an existing live entry, checking the draft and public page separately so you can see which content is awaiting approval. If you use intermediate reviewers, follow the same submission through each configured review step before the publisher's final action.

Also test a rejection: add a useful review note, return the submission to the editor and confirm they can revise and resubmit it. Use ordinary group members for these checks, since an administrator's broader permissions can hide a missing permission in your setup.
