DOCUMENT TRANSLATION – CORE PHP INSTRUCTIONS

GOAL
Create a split-screen interface to upload DOCX files, translate them in the background, preserve layout, manage translated files, support bulk actions, and maintain logs.

--------------------------------------------------

UI LAYOUT
- Screen must be split into two equal halves.
- LEFT SIDE (50%): Translated files explorer.
- RIGHT SIDE (50%): Upload and translation form.

--------------------------------------------------

UPLOAD & TRANSLATION FORM (RIGHT SIDE)
- File input: DOCX only.
- Target language dropdown.
- Project name input.
- Topic name input.
- Submit / Translate button.

--------------------------------------------------

BACKGROUND FORM SUBMISSION
- Form submission must run in the background.
- Use AJAX / Fetch API.
- No page reload.
- Allow multiple translations without blocking UI.

--------------------------------------------------

NOTIFICATIONS
- Use toast / toaster notifications only.
- Show notifications for:
  - Upload started
  - Translation in progress
  - Translation completed successfully
  - Translation failed
- Toast messages must auto-dismiss.
- Do not use alert popups.

--------------------------------------------------

DIRECTORY STRUCTURE
Store translated files in the following structure:

/translated/{project_name}/{topic_name}/

Files:
- original_filename.docx
- translated_filename_{target_language}.docx

--------------------------------------------------

TRANSLATED FILES VIEW (LEFT SIDE)
- Group files by project and topic.
- Display:
  - File name
  - Target language
  - Date & time
- Allow multi-selection using checkboxes.
- Auto-refresh list after successful translation.

--------------------------------------------------

BULK ACTIONS
- Bulk download selected files (individual or ZIP).
- Bulk delete selected files.
- Show confirmation before delete.
- Show toast notification after each bulk action.

--------------------------------------------------

LAYOUT PRESERVATION
- Translated DOCX files must preserve:
  - Original layout
  - Tables
  - Headings
  - Paragraph structure
  - Formatting
- No manual formatting changes allowed.

--------------------------------------------------

LOGS
Log file location:
/logs/translation.log

Each log entry must include:
- Timestamp
- Original file name
- Project name
- Topic name
- Target language
- Status (SUCCESS / FAILED)
- Error message (if any)

Example:
[2026-01-13 15:40:12] SUCCESS | contract.docx | ProjectA | Legal | EN-GB

--------------------------------------------------

ERROR HANDLING
- Invalid file → toast error.
- Translation failure → toast error + log entry.
- File system error → toast error + log entry.

--------------------------------------------------

SECURITY
- Sanitize all user inputs.
- Validate file MIME type and extension.
- Restrict upload file size.
- Prevent directory traversal attacks.

--------------------------------------------------

COMPLETION CHECKLIST
- Split-screen UI implemented.
- Background DOCX translation working.
- Toast notifications implemented.
- Correct directory structure used.
- Bulk download and delete working.
- Translation logs created and maintained.

