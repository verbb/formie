<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\console\controllers\FormsController;
use verbb\formie\elements\Form;

it('lists nothing without failing when there are no forms or export files', function(): void {
    $db = Craft::$app->getDb();
    $transaction = $db->beginTransaction();
    $folder = Craft::getAlias('@storage/runtime/forms-list-console-' . bin2hex(random_bytes(5)));
    mkdir($folder, 0775, true);
    $controller = new class('forms', Formie::$plugin) extends FormsController {
        public string $captured = '';

        public function stdout($string): int { $this->captured .= (string)$string; return strlen((string)$string); }
        public function stderr($string): int { $this->captured .= (string)$string; return strlen((string)$string); }
    };

    // devMode turns the warning from an unset list into an exception, so fail on any warning here.
    set_error_handler(static function(int $severity, string $message, string $file = '', int $line = 0): bool {
        if (str_ends_with($file, 'FormsController.php')) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        return false;
    });

    try {
        foreach (Form::find()->site('*')->unique()->status(null)->all() as $form) {
            Craft::$app->getElements()->deleteElement($form);
        }

        expect(Form::find()->site('*')->unique()->all())->toBe([])
            ->and($controller->actionList($folder))->toBe(0)
            ->and($controller->captured)->toBe('');

        $form = formie()->form()->singleLineTextField('message')->create();
        expect($controller->actionList($folder))->toBe(0)
            ->and($controller->captured)->toContain($form->handle);
    } finally {
        restore_error_handler();
        $transaction->rollBack();
        rmdir($folder);
    }
});
