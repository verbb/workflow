# Changelog

## 1.1.0 - 2019-02-25

### Added
- Added editor notes, to send along with a submission.
- Submissions now listed on entry events, to improve submission workflow.
- Submit brand-new entries for review (provided they pass validation).
- Translation to norwegian. (thanks @phoob).
- Add serialised data to submissions to prepare for entry comparison diffs.

### Changed
- Updated widget styles to be more compact for entry sidebars.
- Update min requirement to Craft 3.1.x.
- Updated plugin settings to use UIDs for Craft 3.1.x.
- 'Submit for review' now saves entry content, then submits for review. No more clicking 'Save' then 'Submit'.
- 'Submit for review' now validates entry content before submission.
- Changed controller action from `workflow/submissions/send` to `workflow/submissions/save-submission`.
- Changed controller action from `workflow/submissions/revoke` to `workflow/submissions/revoke-submission`.
- Changed controller action from `workflow/submissions/approve` to `workflow/submissions/approve-submission`.
- Changed controller action from `workflow/submissions/reject` to `workflow/submissions/reject-submission`.

### Fixed 
- Fix draft submissions not being retained after approval.
- Submissions now trigger entry validation, ensuring only 'valid' entries are submitted for review.
- Fix dateApproved/dateRejected not showing their value.
- Fix error when sorting by Editor or Publisher in CP.
- Fix error when no sections are selected.
- Fix not publishing a submission when approving.

## 1.0.3 - 2018-11-10

### Fixed
- Show the entry title on submissions index, rather than the Submission ID.

## 1.0.2 - 2018-07-24

### Fixed
- Fix Verbb logo reference (for real this time)

## 1.0.1 - 2018-07-24

### Fixed
- Fix elementType typo
- Fix Verbb logo reference

## 1.0.0 - 2018-07-23

- Craft 3 release

## 0.9.6 - 2017-11-04

### Fixed
- Minor fix for sidebar icon.

## 0.9.5 - 2017-10-17

### Added
- Verbb marketing (new plugin icon, readme, etc).

## 0.9.4 - 2016-10-15

### Added
- Added ability to enabled/disable email notifications for editors or publishers.
- Added ability to select all, or individual publishers to be notified via email on submissions.

### Fixed
- Only individual editors receive approval/rejection email notifications - not each user in the editor group.

## 0.9.3 - 2016-09-24

### Added
- Added ability to set notes on an approval/rejection from a publisher.
- Added submission rejection option integrating notes (as above).
- Added new email message for when editors are notified on their submission.
- Added ability for editors to revoke their submission.
- Added widget for submissions.

### Changed
- Editors now receive emails when their submission is approved/rejected.
- Improved entry widget to combine multiple submissions into a single pane.

### Fixed
- Fixed missing attributes for element type.
- Fixed typo in element action.
- Fixed status indicators on element index.

## 0.9.2 - 2016-07-08

### Added
- Added Draft overview screen - shows all available drafts site-wide.

## 0.9.1 - 2016-07-08

### Fixed
- Fixed approval link in email not including draft.
- Fixed where a Draft entry was approved, its state was not set to public.

## 0.9.0 - 2016-06-26

### Added
- Initial release.
