<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Users;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

final class UnregisteredFieldHelperPreviewField extends Users
{
    public static bool $constructed = false;

    public function __construct(array $config = [])
    {
        self::$constructed = true;
        parent::__construct($config);
    }
}

function fieldHelperViewer(string $emailPrefix = 'field-helper-viewer'): User
{
    $name = $emailPrefix . '-' . bin2hex(random_bytes(5));
    $user = new User([
        'username' => $name,
        'email' => $name . '@example.test',
        'active' => true,
        'pending' => false,
    ]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
    ]))->toBeTrue();

    return $user;
}

it('requires formie form access permission for cp field helper actions', function (): void {
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);

    try {
        WebRequestTestHelper::withWebRequestContext(function (): void {
            $controller = new FieldsController('formie-fields-security', Craft::$app);

            expect(fn() => $controller->runAction('get-field-type-config'))
                ->toThrow(ForbiddenHttpException::class);
        }, [
            'method' => 'POST',
            'headers' => [
                'Accept' => 'application/json',
            ],
            'bodyParams' => [
                'type' => 'verbb\\formie\\fields\\SingleLineText',
            ],
        ]);
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }
})->group('security');

it('returns metadata only for elements the current user can view', function (): void {
    $viewer = fieldHelperViewer();
    $restricted = User::find()->admin(true)->one();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($viewer, $restricted): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($viewer->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);

        expect(Craft::$app->getElements()->canView($restricted, $identity))->toBeFalse()
            ->and(Craft::$app->getElements()->canView($identity, $identity))->toBeTrue();

        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'elements' => [
                ['id' => $restricted->id, 'siteId' => $restricted->siteId],
                ['id' => $identity->id, 'siteId' => $identity->siteId],
            ],
        ]);
        $data = (new FieldsController('formie-fields-security', Formie::$plugin))
            ->runAction('get-element-select-options')
            ->data;

        expect($data)->toHaveCount(1)
            ->and(array_keys($data))->toBe([0])
            ->and($data[0]['id'])->toBe($identity->id)
            ->and(json_encode($data, JSON_THROW_ON_ERROR))->not->toContain($restricted->email);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
    ]);
})->group('security');

it('filters element preview labels and totals through Craft view authorization', function (): void {
    $viewer = fieldHelperViewer('zz-field-helper-viewer');

    for ($i = 0; $i < 6; $i++) {
        $name = 'aa-field-helper-restricted-' . $i . '-' . bin2hex(random_bytes(4));
        $restricted = new User([
            'username' => $name,
            'email' => $name . '@example.test',
            'active' => true,
            'pending' => false,
        ]);
        expect(Craft::$app->getElements()->saveElement($restricted))->toBeTrue();
    }

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($viewer): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($viewer->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'field' => [
                'type' => Users::class,
                'settings' => ['sources' => '*'],
            ],
        ]);
        $data = (new FieldsController('formie-fields-security', Formie::$plugin))
            ->runAction('get-element-select-preview-options')
            ->data;

        expect($data)->toBe([
            'total' => '1',
            'options' => [
                ['label' => $identity->email, 'value' => $identity->id],
            ],
        ]);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
    ]);
})->group('security');

it('rejects unregistered and non-element preview field classes', function (string $type): void {
    $viewer = fieldHelperViewer();
    UnregisteredFieldHelperPreviewField::$constructed = false;

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($viewer, $type): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($viewer->id)->status(null)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'field' => [
                'type' => $type,
                'settings' => [],
            ],
        ]);

        expect(fn() => (new FieldsController('formie-fields-security', Formie::$plugin))
            ->runAction('get-element-select-preview-options'))
            ->toThrow(BadRequestHttpException::class, 'Invalid element field type.');
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
    ]);

    expect(UnregisteredFieldHelperPreviewField::$constructed)->toBeFalse();
})->with([
    UnregisteredFieldHelperPreviewField::class,
    SingleLineText::class,
])->group('security');
