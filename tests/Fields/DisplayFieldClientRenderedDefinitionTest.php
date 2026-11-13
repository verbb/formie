<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\client\FormsController;
use verbb\formie\Formie;
use verbb\formie\models\RichText;
use verbb\formie\positions\AboveInput;

// Client-rendered forms (formie-vue, formie-react, web components) have no server template,
// so display fields must carry the HTML the template would have rendered.
function displayFieldClientInputs(string $transport, string $handle, string $formUid): array
{
    $inputs = [];

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($transport, $handle, $formUid, &$inputs): void {
        expect(Craft::$app->getUser()->getIsGuest())->toBeTrue();

        if ($transport === 'graphql') {
            $gql = Craft::$app->getGql();
            $previousSchema = null;

            try {
                $previousSchema = $gql->getActiveSchema();
            } catch (GqlException) {
            }

            $schema = new GqlSchema(['name' => 'Display Field Client Definition', 'uid' => StringHelper::UUID(), 'scope' => ['formieForms.' . $formUid . ':read']]);
            $gql->setActiveSchema($schema);

            try {
                $response = $gql->executeQuery($schema, 'query($handle: String!) { formieClientForm(handle: $handle) { definition } }', ['handle' => $handle]);
            } finally {
                $gql->setActiveSchema($previousSchema);
                $gql->flushCaches();
            }

            expect($response)->not->toHaveKey('errors');
            $definition = $response['data']['formieClientForm']['definition'];
        } else {
            $request->setBodyParams(['handle' => $handle]);
            $response = (new FormsController('client/forms', Formie::$plugin))->runAction('load');
            expect($response->getStatusCode())->toBe(200);
            $definition = $response->data['definition'];
        }

        foreach ($definition['pages'][0]['rows'] as $row) {
            foreach ($row['fields'] as $field) {
                $inputs[$field['handle']] = $field['input'];
            }
        }
    }, ['method' => 'POST', 'requestUri' => '/actions/formie/client/forms/load']);

    return $inputs;
}

it('sends the rendered HTML of Rich Text and HTML fields in client definitions', function (string $transport): void {
    $settings = Formie::$plugin->getSettings();
    $allowTwig = $settings->allowHtmlFieldTwig;
    $settings->allowHtmlFieldTwig = true;
    $form = null;

    try {
        $form = formie()->form(['title' => 'Display Fields'])
            ->contentField('intro', ['content' => RichText::fromHtml('<h2>Before you start</h2><p>Read the <strong>terms</strong> and <a href="https://example.com/terms" target="_blank">policy</a>.</p>')])
            ->contentField('empty')
            ->htmlField('notice', ['htmlContent' => '<p class="notice">Plain HTML notice</p><script>alert(1)</script>', 'purifyContent' => true])
            ->htmlField('greeting', ['htmlContent' => '<p>Welcome to {{ form.title }}</p>', 'allowTwig' => true])
            ->singleLineTextField('firstName')
            ->create();

        $inputs = displayFieldClientInputs($transport, $form->handle, $form->uid);
        $intro = $form->getFieldByHandle('intro');
        $notice = $form->getFieldByHandle('notice');

        // The same HTML the Twig templates render for these fields.
        expect($inputs['intro']['contentHtml'])->toBe($intro->getRenderedContentBlock($form, null))
            ->and($inputs['intro']['contentHtml'])->toContain('<h2>Before you start</h2>')
            ->and($inputs['intro']['contentHtml'])->toContain('<strong>terms</strong>')
            ->and($inputs['intro']['contentHtml'])->toContain('target="_blank"')
            ->and($inputs['empty']['contentHtml'])->toBe('')
            ->and($inputs['notice']['htmlContent'])->toBe($notice->getRenderedHtmlBlock($form, null))
            ->and($inputs['notice']['htmlContent'])->toContain('<p class="notice">Plain HTML notice</p>')
            ->and($inputs['notice']['htmlContent'])->not->toContain('<script')
            ->and($inputs['greeting']['htmlContent'])->toBe('<p>Welcome to Display Fields</p>')
            ->and($inputs['firstName'])->not->toHaveKeys(['contentHtml', 'htmlContent']);
    } finally {
        $settings->allowHtmlFieldTwig = $allowTwig;

        if ($form) {
            Craft::$app->getElements()->deleteElement($form, true);
        }
    }
})->with(['rest', 'graphql']);

it('sends heading sizes and hidden display labels in client definitions', function (string $transport): void {
    $form = null;

    try {
        $form = formie()->form(['title' => 'Display Labels'])
            ->contentField('intro', ['content' => RichText::fromHtml('<p>Intro</p>')])
            ->contentField('shownIntro', ['content' => RichText::fromHtml('<p>Shown</p>'), 'labelPosition' => AboveInput::class])
            ->htmlField('notice', ['htmlContent' => '<p>Notice</p>'])
            ->htmlField('defaultNotice', ['htmlContent' => '<p>Default</p>', 'labelPosition' => null])
            ->headingField('section', ['label' => 'Your details', 'headingSize' => 'h3'])
            ->headingField('defaultSection', ['label' => 'More details'])
            ->singleLineTextField('firstName')
            ->create();

        $inputs = displayFieldClientInputs($transport, $form->handle, $form->uid);

        // Rich Text and HTML labels are hidden unless the author shows them, as in the templates.
        expect($inputs['intro']['labelHidden'])->toBeTrue()
            ->and($inputs['shownIntro']['labelHidden'])->toBeFalse()
            ->and($inputs['notice']['labelHidden'])->toBeTrue()
            ->and($inputs['defaultNotice']['labelHidden'])->toBeFalse()
            ->and($inputs['section']['headingSize'])->toBe('h3')
            ->and($inputs['defaultSection']['headingSize'])->toBe('h2')
            ->and($inputs['firstName'])->not->toHaveKeys(['labelHidden', 'headingSize']);
    } finally {
        if ($form) {
            Craft::$app->getElements()->deleteElement($form, true);
        }
    }
})->with(['rest', 'graphql']);
