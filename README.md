# Workflow

Workflow is a Craft CMS plugin for a common publisher-editor scenario when it comes to content authoring.

Your site might have two distinct user groups - Publishers and Editors. An Editor is a user who can create and edit content entries, but cannot publish these so they are publicly visible. Instead, Editors should submit these entries to a Publisher user, who is then notified (via email), reviews the entry, and eventually approved the entry for public viewing.

<img src="https://raw.githubusercontent.com/engram-design/Workflow/master/screenshots/review-pane.png" />


## Features

- Makes use of Craft's native user permissions.
- Choose which user groups are your Editor and Publisher roles.
- Email notifications to Publisher group, which editable content.
- Events for third-party plugins to hook into for submissions.


## Documentation

Please visit the [Wiki](https://github.com/engram-design/Workflow/wiki) for all documentation, a getting started guide, template tags, and developer resources.


## Roadmap
- Entry diffs to compare previous and submitted entries.
- Setting specific recipients for Publisher notifications - rather than entire Publisher group.
- Better note-tracking per-version
- Incorporate versions - tie each submission to a version


### Changelog

[View JSON Changelog](https://github.com/engram-design/Workflow/blob/master/changelog.json)
