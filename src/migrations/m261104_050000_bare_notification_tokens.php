<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\references\ReferenceMigration;

use craft\db\Migration;
use craft\db\Query;

class m261104_050000_bare_notification_tokens extends Migration
{
    // Constants
    // =========================================================================

    protected const COLUMNS = ['subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName', 'sender', 'content'];

    // Formie 3 read these from the submission before looking for a field with the same handle.
    protected const SUBMISSION_ATTRIBUTES = ['id' => '{submission:id}', 'uid' => '{submission:uid}', 'title' => '{submission:title}'];

    // A single pair of braces around a handle or dotted path. This also skips Twig `{{ x }}` and JSON.
    protected const TOKEN_PATTERN = '/(?<!\{)\{([A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)*)\}(?!\})/';


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Formie 3 rendered {handle} through Twig, and Formie 4 only resolves reference tokens, so these
        // notifications now fail or lose the value. Sites that already upgraded are repaired too. Only exact
        // matches on the form are converted: anything with a filter or Twig still needs converting by hand.
        foreach ((new Query())->from(Table::FORMIE_NOTIFICATIONS)->each(100, $this->db) as $row) {
            $form = null;
            $changes = [];

            foreach (self::COLUMNS as $column) {
                $value = $row[$column] ?? null;

                if (!is_string($value) || (!preg_match(self::TOKEN_PATTERN, $value) && !str_contains($value, '{field:'))) {
                    continue;
                }

                $form ??= Form::find()->id($row['formId'])->site('*')->unique()->status(null)->trashed(null)->one();

                if (!$form) {
                    break;
                }

                // Tokens with a path, such as {field:name.firstName}, weren't converted by the earlier migration either.
                $updated = preg_replace_callback(self::TOKEN_PATTERN, fn(array $match) => $this->convertToken($form, $match), $value);
                $updated = ReferenceMigration::canonicalFieldTokens($form, $updated);

                if ($updated !== $value) {
                    $changes[$column] = $updated;
                }
            }

            if ($changes) {
                $this->update(Table::FORMIE_NOTIFICATIONS, $changes, ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261104_050000_bare_notification_tokens cannot be reverted.\n";

        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function convertToken(Form $form, array $match): string
    {
        if (isset(self::SUBMISSION_ATTRIBUTES[$match[1]])) {
            return self::SUBMISSION_ATTRIBUTES[$match[1]];
        }

        if (!str_contains($match[1], '.')) {
            $reference = $form->getFieldByHandle($match[1])?->reference;

            return $reference ? '{field:' . $reference . '}' : $match[0];
        }

        $token = '{field:' . $match[1] . '}';
        $converted = ReferenceMigration::canonicalFieldTokens($form, $token);

        // Unknown paths stay exactly as they were written, for diagnosis.
        return $converted !== $token ? $converted : $match[0];
    }
}
