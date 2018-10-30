# Setup

Workflow makes use of Craft's inbuilt user permissions system, which is already quite powerful. Permissions allow us to set whether a user can edit an entry, or publish live changes - we'll make use of that.

## User Groups

To get started, you'll need to create two member groups - lets call them Editor and Publisher. Editor users will be able to create entries, save drafts, and submit these for review. They will not be able to publish entries live. Publishers on the other hand have essentially full access, and are the recipient of email notifications when entries need to be reviewed and approved.

Start by making a User Group called Editor with the following settings:

![](https://raw.githubusercontent.com/engram-design/Workflow/craft-2/screenshots/editor-permissions.png)

Things to note are disabling access to the Workflow plugin page, and disabling the ability to publish changes live.

Create another User Group called Publisher with the following settings:

![](https://raw.githubusercontent.com/engram-design/Workflow/craft-2/screenshots/publisher-permissions.png)

You can name either of these groups whatever you like, but we'll refer to them as Editor/Publisher throughout the guide.

## Plugin Settings

Next, head to the plugin settings for Workflow, and assign both these groups you created to their respective fields. Easy as that!

[Next: **Editors →**](/craft-plugins/workflow/docs/feature-tour/editors)