# User
For a form, you can configure users to be created for submissions. You can configure:

- User Groups
- Activate User
- Send Activation Email
- User Attribute Mapping
- User Field Mapping
- Overwrite Content
- Update Users
- Update Element Mapping
- Merge User Groups
- Update Search Index

### Mapping
For both user attributes (Username, Email, etc.) and any custom user fields, you can assign a field's content to be mapped to that attribute or field.

The attribute mapping supports values like:

- Username
- First Name
- Last Name
- Full Name
- Email
- Password (new users only)
- Photo

If the native **Addresses** field has been added to your user field layout, address attributes can also be mapped.

### Updating Existing Users
If you turn on **Update Users**, Formie can look for an existing user instead of always creating a new one.

Use **Update Element Mapping** to choose which values should be treated as the match criteria, then use **Merge User Groups** if you want existing group assignments to be kept alongside the groups selected in the integration.

A visitor must be signed in to update an existing account, and can only update their own account. Control panel users can update another account when their Craft permissions allow it. Anonymous submissions can still create new users, but cannot update a matched account.

The **Password (New Users Only)** mapping sets the initial password when the integration creates an account. Existing email addresses and passwords are not changed by this integration. Use Craft's account-management and password-reset flows for those changes.

The activation settings also apply only to accounts created by the integration. Matching an existing pending account does not activate it, send another activation email, or sign the visitor in as that account.
