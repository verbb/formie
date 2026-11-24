<?php

declare(strict_types=1);

use craft\elements\User;
use craft\services\UserPermissions;
use craft\web\TemplateResponseBehavior;
use craft\web\TemplateResponseFormatter;
use craft\web\View;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\base\web\twig\Extension as BaseTwigExtension;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\PdfTemplate;
use verbb\formie\services\Permissions;

/**
 * Opens the Forms index with every form row except $keepFormIds removed, inside a transaction that's rolled back, so
 * the page sees a site with no forms or with only those.
 */
function formsIndexFirstRunOpen(?User $user = null, array $keepFormIds = []): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $keepFormIds): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user ?? User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new BaseTwigExtension());

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_FORMS, ['not', ['id' => $keepFormIds]])->execute();

            $response = (new FormsController('forms', Formie::$plugin))->actionIndex();
            $template = $response->getBehavior(TemplateResponseBehavior::NAME)->template;

            if ($template !== 'formie/forms/_first-run') {
                return ['template' => $template, 'html' => ''];
            }

            (new TemplateResponseFormatter())->format($response);

            return ['template' => $template, 'html' => (string)$response->content];
        } finally {
            $transaction->rollBack();
        }
    }, ['method' => 'GET', 'requestUri' => '/admin/formie/forms']);
}

function formsIndexFirstRunViewer(): User
{
    $name = 'formsviewer' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $grants = PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS]);
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

it('shows one page empty state with a New form button before any form exists', function(): void {
    $page = formsIndexFirstRunOpen();

    expect($page['template'])->toBe('formie/forms/_first-run')
        ->and($page['html'])->toContain('<pk-state-panel layout="page" heading="Create your first form"')
        ->and($page['html'])->toContain('class="formie-spot"')
        ->and($page['html'])->toContain('formie/forms/new')
        ->and($page['html'])->toContain('formie-bare-content')
        ->and($page['html'])->not->toContain('Craft.createElementIndex');
});

it('makes the New form button the same group menu as the index header when there are form groups', function(): void {
    $group = new FormGroup(['name' => 'First run group', 'handle' => 'firstRunGroup' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    try {
        $html = formsIndexFirstRunOpen()['html'];

        expect($html)->toContain('aria-controls="formie-new-form-menu" data-disclosure-trigger')
            ->and($html)->toContain('New First run group form')
            ->and($html)->toContain($group->uid);
    } finally {
        Formie::$plugin->getFormGroups()->deleteGroup($group);
    }
});

// The index header only offers groups it lists as sources, so a group the user can't manage isn't in its menu.
it('leaves groups the user can’t use out of the first-run New form menu', function(): void {
    $allowed = new FormGroup(['name' => 'First run allowed', 'handle' => 'firstRunAllowed' . bin2hex(random_bytes(5))]);
    $other = new FormGroup(['name' => 'First run other', 'handle' => 'firstRunOther' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($allowed))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($other))->toBeTrue();

    $permissions = Formie::$plugin->getPermissions();
    $name = 'formsgroupuser' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new UserPermissions());
    $grants = PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_CREATE_FORMS,
        $permissions->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissions->groupScope($allowed->handle)),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]);
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();
    $user = User::find()->id($user->id)->status(null)->one();

    try {
        $page = formsIndexFirstRunOpen($user);

        expect($page['template'])->toBe('formie/forms/_first-run')
            ->and($page['html'])->toContain('heading="Create your first form"')
            ->and($page['html'])->toContain($allowed->uid)
            ->and($page['html'])->not->toContain($other->uid);
    } finally {
        Craft::$app->getElements()->deleteElement($user, true);
        Formie::$plugin->getFormGroups()->deleteGroup($allowed);
        Formie::$plugin->getFormGroups()->deleteGroup($other);
    }
});

it('shows people who can’t create forms a plain empty state without a button', function(): void {
    $user = formsIndexFirstRunViewer();

    try {
        $page = formsIndexFirstRunOpen($user);

        expect($page['template'])->toBe('formie/forms/_first-run')
            ->and($page['html'])->toContain('heading="No forms yet"')
            ->and($page['html'])->toContain('Forms will appear here once someone creates one.')
            ->and($page['html'])->not->toContain('formie/forms/new');
    } finally {
        Craft::$app->getElements()->deleteElement($user, true);
    }
});

it('keeps the element index once a form exists, even a trashed one', function(): void {
    $form = formie()->form(['title' => 'First run trashed form'])->singleLineTextField('name')->create();

    try {
        expect(formsIndexFirstRunOpen(null, [$form->id])['template'])->toBe('formie/forms/index');

        expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();

        expect(formsIndexFirstRunOpen(null, [$form->id])['template'])->toBe('formie/forms/index');
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});

it('replaces an empty template list with an empty state', function(string $path, string $variable, string $class, string $heading, string $message): void {
    WebRequestTestHelper::withWebRequestContext(function($request) use ($path, $variable, $class, $heading, $message): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $view = Craft::$app->getView();
        $view->registerTwigExtension(new BaseTwigExtension());
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $empty = $view->renderTemplate('formie/settings/' . $path, [$variable => []]);

        expect($empty)->toContain('<pk-state-panel heading="' . $heading . '">')
            ->and($empty)->toContain($message)
            ->and($empty)->not->toContain($path . '-vue-admin-table"');

        $listed = $view->renderTemplate('formie/settings/' . $path, [$variable => [new $class(['id' => 1, 'name' => 'Listed', 'handle' => 'listed'])]]);

        expect($listed)->toContain($path . '-vue-admin-table"')
            ->and($listed)->not->toContain('<pk-state-panel');
    });
})->with([
    ['form-templates', 'formTemplates', FormTemplate::class, 'No form templates yet', 'Forms use Formie’s built-in templates until you add one of your own.'],
    ['email-templates', 'emailTemplates', EmailTemplate::class, 'No email templates yet', 'Email notifications use Formie’s built-in template until you add one of your own.'],
    ['pdf-templates', 'pdfTemplates', PdfTemplate::class, 'No PDF templates yet', 'PDF attachments use the notification’s email content until you add a template of your own.'],
]);
