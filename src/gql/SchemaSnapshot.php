<?php
namespace verbb\formie\gql;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use craft\helpers\Gql;
use craft\models\GqlSchema;

/** Request-local schema metadata. Layout graphs are never loaded to enumerate types. */
final class SchemaSnapshot
{
    // Properties
    // =========================================================================

    private array $_forms;
    private ?array $_fieldConfigs = null;
    private GqlSchema $_schema;


    // Public Methods
    // =========================================================================

    public function __construct(GqlSchema $schema)
    {
        $this->_schema = clone $schema;
        $uids = [];
        $all = false;
        foreach ($schema->scope as $scope) {
            if (preg_match('/^formie(?:Forms|Submissions)\.([^:]+):[^:]+$/iD', $scope, $match)) {
                if (strtolower($match[1]) === 'all') {
                    $all = true;
                } else {
                    $uids[] = $match[1];
                }
            }
        }
        $this->_forms = !$all && !$uids ? [] : Form::find()->withoutCpIndexScope()->site('*')->unique()->uid($all ? null : array_values(array_unique($uids)))->all();
    }

    public function forms(string $namespace): array
    {
        if (!in_array($namespace, ['formieForms', 'formieSubmissions'], true)) {
            throw new \InvalidArgumentException('Unknown Formie schema namespace.');
        }
        return array_values(array_filter($this->_forms, fn(Form $form): bool => Gql::isSchemaAwareOf($namespace . '.all', $this->_schema) || Gql::isSchemaAwareOf($namespace . '.' . $form->uid, $this->_schema)));
    }

    public function fieldConfigs(int $formId): array
    {
        $this->_fieldConfigs ??= Formie::$plugin->getFields()->getAllFieldConfigsForForms(array_map(static fn(Form $form): int => (int)$form->id, $this->_forms));
        return $this->_fieldConfigs[$formId] ?? [];
    }
}
