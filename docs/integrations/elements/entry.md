# Entry
For a form, you can configure entries to be created for submissions.

You'll need to configure:

- Entry Type
- Use Submission User as Author
- Default Entry Author
- Entry Attribute Mapping
- Entry Field Mapping
- Overwrite Content
- Create a New Draft
- Update Entries
- Update Element Mapping
- Update on Submission Edit
- Update Search Index

### Mapping
For both the entries attributes (Title, Post Date, etc.) and any custom fields, you can assign a field's content to be mapped to that attribute or field.

For instance, you might have a Date, Users and Single-Line Text field for your form. With this integration, you could map these fields to the entry Post Date, Author and Title respectively.

Turn on **Use Submission User as Author** to assign the user recorded on the submission (requires **Collect User** on the form) as the entry author. If no user is recorded, the **Default Entry Author** is used instead. You can still map the Author attribute explicitly to override this behaviour.

The attribute mapping supports entry attributes like:

- Title
- Site ID
- Slug
- Author
- Post Date
- Expiry Date
- Enabled
- Date Created
- Date Updated

If you turn on **Update Entries**, you can also choose which attributes or unique fields should be used to find an existing entry before updating it.

Turn on **Update on Submission Edit** to keep the linked entry in sync when a submission is edited and re-submitted on the front end.

When a form submission is created successfully, a queue job will run to create the entry element after the users' submission.
