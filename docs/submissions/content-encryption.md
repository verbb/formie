# Content Encryption

Content encryption protects selected submission field values while they are stored in the database. It is optional: a site that does not enable **Enable Content Encryption** on any field does not need an encryption key or any additional setup.

Use it for values that should not be readable from a database backup or copied database on its own. It is not an access-control system. Formie decrypts the value for authorised users and processes running with the key, so control-panel permissions, server security, exports, notifications and integrations still matter.

## What Content Encryption Covers

Formie encrypts the complete stored value of each field where **Enable Content Encryption** is enabled. This includes structured values and encrypted fields nested inside Group and Repeater fields. Encrypted fields are also excluded from Craft’s search keywords.

Content encryption does not encrypt:

- file uploads
- submission attributes outside field content
- sent email bodies
- generated export files
- values after they are sent to an integration, webhook or payment provider
- integration and captcha settings

Protect those destinations using their own storage, access and retention controls. Integration credentials should normally use environment references so their literal values are not committed to project config.

## Set Up the Encryption Key

Run the following command from the Craft project root:

```shell
php craft formie/encryption/generate-key
```

The command creates a dedicated key and saves it as `FORMIE_ENCRYPTION_KEY` in Craft’s configured `.env` file. If the file does not exist, the command asks before creating it. Use `--stdout` to print only the key without changing a file when your hosting platform, deployment system or secrets manager owns the environment.

Keep a recoverable copy outside the web server. Do not commit the key to project config or a repository. Losing every copy permanently prevents Formie from decrypting content written with that key.

Deploy the same key and Formie code to every web and queue process, then restart long-running workers. Once every process can read the new format, activate encrypted writes:

```shell
php craft formie/encryption/activate
```

This deliberate activation step prevents one node from writing content that an older web or queue process cannot read during a deployment. Check the current state at any time with:

```shell
php craft formie/encryption/status
```

The **Formie → Settings → Encryption** page shows the same setup state and the non-secret key ID. A site with no encrypted fields receives only a quiet confirmation that no action is required. Warnings appear when encrypted fields need a key, a configured key has not been activated, the key is malformed, or its ID differs from the key that previously wrote content.

## Use the Same Key with Shared Data

Every environment that needs to read a submission database must have the key that encrypted its content. The key may differ between environments only when their submission data remains separate.

| Situation | Required setup |
| --- | --- |
| Production data is copied to staging or local development | The receiving environment needs the production encryption key. |
| Staging submissions may later be copied to production | Production must be able to read the staging key before the data is copied. |
| Environments use independent databases that are never exchanged | They may use different keys. |
| Several web nodes or queue workers serve one database | Every process needs the same current key and rescue keys. |

The `generate-key` command changes only the current Craft installation. It cannot distribute the key to another server, container, worker or hosting platform.

## Enable Encryption for a Field

Open the field in the form builder and enable **Enable Content Encryption**. The toggle is disabled while it is off if no valid `FORMIE_ENCRYPTION_KEY` is available. An existing encrypted field remains enabled and can be switched off, which allows a site to stop using encryption without first restoring a missing setup key.

Once encryption is active, new values and subsequent saves for that field use the dedicated key. Enabling the setting does not immediately rewrite every historical submission. After making and verifying a database backup, encrypt existing values with:

```shell
php craft formie/encryption/reencrypt --confirm-backup --key-id=r_123456789abc
```

Replace the example with the key ID shown by `formie/encryption/status`. The command works in resumable batches, handles nested values, and updates a submission only when it has not changed concurrently. It never replaces a value it cannot decrypt.

Switching the field setting off makes future saves use plaintext storage. It does not bulk-decrypt historical values; keep the relevant key available while older encrypted submissions remain.

## Missing or Incorrect Keys

When Formie finds encrypted content without its key, it preserves the original stored value instead of treating it as empty. The submission becomes read-only across control-panel, front-end, GraphQL and console save paths so another edit cannot accidentally overwrite the unavailable value or clear fields through conditional logic.

The control panel identifies the required key ID where possible. Exports replace only the unavailable field with `[encrypted: unavailable]`; notifications, integrations and other outbound projections fail visibly instead of sending a placeholder or blank value. New submissions to a form with encrypted fields show a temporary-unavailable message if encrypted writes cannot be performed.

Restore the matching key as the current key or add it temporarily to `FORMIE_ENCRYPTION_PREVIOUS_KEYS`. This variable accepts a comma-separated list or JSON array. Once Formie can read the content again, run `reencrypt` to move it to the current key before removing the rescue key.

## Rotate the Key

Key rotation is deliberate because every value written with the former key still needs to be readable during the change.

1. Make a verified database backup and retain a secure copy of the current key.
2. Generate a replacement without changing the running environment: `php craft formie/encryption/generate-key --rotate --stdout`.
3. Deploy the replacement as `FORMIE_ENCRYPTION_KEY` and the former key in `FORMIE_ENCRYPTION_PREVIOUS_KEYS` to every web and queue process. Use a coordinated deployment or maintenance window so no process writes with a key another process cannot read.
4. Restart long-running workers and verify the current and expected key IDs with `php craft formie/encryption/status`.
5. Run `php craft formie/encryption/reencrypt --confirm-backup --key-id=r_123456789abc --adopt-new-key` using the replacement key ID.
6. Remove the former key only after re-encryption completes without unavailable values.

You can inspect a saved key’s non-secret ID before configuring it with `php craft formie/encryption/verify-key <value>`.

## Craft’s Security Key

The dedicated Formie key is used only for encrypted submission field content. Formie continues to use Craft’s security key for short-lived form tokens and disposable operational evidence. Rotating Craft’s key does not affect submission content written with the active Formie encryption format, although cached forms, sessions and transient operations protected by Craft may expire.

See [Console Commands](/developers/console-commands#submission-content-encryption) for the command reference and [Configuration](/get-started/configuration#encryptionkey) for the corresponding Formie settings.
