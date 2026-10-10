# Content Encryption

Content encryption protects sensitive submission answers while they are stored in the database. It is useful for fields containing personal, financial or confidential information that should not be readable from a database backup on its own.

Encryption is optional. If a site does not use encrypted fields, there is nothing to configure.

## How Content Encryption Works

When encryption is enabled for a field, Formie encrypts its value before saving the submission. Formie decrypts the value when an authorised user or process needs to use it.

The encryption key is kept separately from the database. This means someone who obtains only a copy of the database cannot read the protected field values. Anyone with access to both the database and the key can still read them, so the key must be stored securely.

The key belongs only to Formie submission content and should remain available for as long as encrypted content is retained.

Content encryption does not replace control-panel permissions or normal server security. Users who are allowed to view a submission in Formie can still view its decrypted values.

## What Is Protected

Formie encrypts the stored value of each field where **Enable Content Encryption** is enabled. This includes structured field values and encrypted fields inside Group and Repeater fields. Encrypted field values are also excluded from Craft’s search index.

Content encryption does not protect:

- file uploads
- submission details outside field content
- sent email bodies
- generated export files
- values after they are sent to an integration, webhook or payment provider
- integration and captcha settings

Those destinations need their own appropriate access, storage and retention controls.

## Set Up Content Encryption

A developer or site administrator with command-line access must set up the key before an editor can enable encryption for a field.

From the Craft project root, run:

```shell
php craft formie/encryption/generate-key
```

The command adds `FORMIE_ENCRYPTION_KEY` to Craft’s configured `.env` file. It asks before creating the file if it does not already exist.

If your hosting platform or secrets manager controls environment variables, use the following command instead and add the resulting value through that system:

```shell
php craft formie/encryption/generate-key --stdout
```

Store a secure backup of the key outside the web server. Do not commit it to a repository or project config. If every copy of the key is lost, the content encrypted with it cannot be recovered.

Add the same key to every web server and queue worker that uses the site’s database, then restart any long-running workers. Once every process has the key, run:

```shell
php craft formie/encryption/activate
```

The separate activation step makes deployment safer. It ensures Formie does not start writing encrypted content until every part of the site is ready to read it.

You can check the setup under **Formie → Settings → Encryption**. The page shows whether encryption is ready without displaying the key itself.

## Enable Encryption for a Field

Open the field in the form builder and enable **Enable Content Encryption**. If the site does not have a valid encryption key, the setting is disabled and explains that an administrator must finish the setup first.

Once enabled, new values saved for that field are encrypted. If the field already contains submission data that should also be protected, make a verified database backup and use Formie’s `reencrypt` command. See [Console Commands](/developers/console-commands#submission-content-encryption) for the command options.

Disabling the setting causes future saves for that field to use normal unencrypted storage. It does not automatically rewrite previously encrypted submissions, so the key must remain available while that content is still stored.

## Use the Key Across Environments

Every environment that needs to read a submission database also needs the key that encrypted its content.

Use the same key when production data is copied to staging or local development, or when several servers and queue workers share one database. Environments may use different keys only when their submission data remains completely separate.

The key-generation command changes only the environment where it is run. It cannot distribute the key to other servers, containers or hosting platforms for you.

## If a Key Is Unavailable

Formie never treats an encrypted value as empty when its key is unavailable. It preserves the stored value and makes the submission read-only so another edit cannot accidentally overwrite protected content.

The control panel explains that the required key is unavailable. Exports identify the affected value as unavailable, while notifications and integrations stop rather than sending an empty or misleading value. A visitor cannot submit a form that requires encryption until the correct key is available again.

Restore the matching key before working with the affected submissions. If a key has been replaced deliberately, it can be supplied temporarily through `FORMIE_ENCRYPTION_PREVIOUS_KEYS` while content is moved to the current key.

## Rotate the Key

You normally only need to rotate the key when it may have been exposed or your organisation’s security policy requires it.

Rotation must be planned because Formie needs the previous key until all stored content has been encrypted with the replacement. The safe sequence is:

1. Make a verified database backup and retain a secure copy of the current key.
2. Generate a replacement key without changing the running environment.
3. Deploy the replacement key and make the previous key available through `FORMIE_ENCRYPTION_PREVIOUS_KEYS` on every server and queue worker.
4. Restart long-running workers and check **Formie → Settings → Encryption**.
5. Run Formie’s `reencrypt` command to move stored content to the replacement key.
6. Remove the previous key only after Formie reports that no content still needs it.

See [Console Commands](/developers/console-commands#submission-content-encryption) for the rotation commands and [Configuration](/get-started/configuration#encryptionkey) for the corresponding settings.
