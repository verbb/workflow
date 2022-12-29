# Setup
Workflow makes use of Craft's inbuilt user permissions system, which is already quite powerful. Permissions allow us to set whether a user can edit an entry, or publish live changes - we'll make use of that.

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

You can name either of these groups whatever you like, but we'll refer to them as Editor/Publisher throughout the guide.

### Reviewers
You can also make use of more "steps" in the reviewal process by adding Reviewers - as many as you like - between Editors and Publishers.

Reviewers have the same permissions as Publishers.

## Plugin Settings
Next, head to the plugin settings for Workflow, and assign both these groups you created to their respective fields. Easy as that!
