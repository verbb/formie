<?php

declare(strict_types=1);

use DateTime;

function resolveRestrictionMessage(mixed $form): ?string
{
    $message = null;

    if ($form->settings->requireUser && !Craft::$app->getUser()->getIdentity()) {
        $message = $form->settings->getRequireUserMessage();
    }

    if ($form->settings->scheduleForm && $form->isBeforeSchedule()) {
        $message = $form->settings->getScheduleFormPendingMessage();
    }

    if ($form->settings->scheduleForm && $form->isAfterSchedule()) {
        $message = $form->settings->getScheduleFormExpiredMessage();
    }

    if ($form->settings->limitSubmissions && !$form->isWithinSubmissionsLimit()) {
        $message = $form->settings->getLimitSubmissionsMessage();
    }

    return $message ? strip_tags($message) : null;
}

it('uses deterministic restriction message precedence in frontend message rendering', function (): void {
    Craft::$app->getUser()->setIdentity(null);

    $form = formie()
        ->form(['title' => 'Restriction Message Precedence'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'requireUser' => true,
        'requireUserMessage' => 'REQUIRE_USER_MESSAGE',
        'scheduleForm' => true,
        'scheduleFormStart' => new DateTime('+1 day'),
        'scheduleFormPendingMessage' => 'PENDING_MESSAGE',
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 0,
        'limitSubmissionsType' => 'total',
        'limitSubmissionsMessage' => 'LIMIT_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $message = resolveRestrictionMessage($form);

    expect($message)->toContain('LIMIT_MESSAGE')
        ->and($message)->not->toContain('REQUIRE_USER_MESSAGE')
        ->and($message)->not->toContain('PENDING_MESSAGE');
});

it('shows pending schedule message over require-user when limits are not exceeded', function (): void {
    Craft::$app->getUser()->setIdentity(null);

    $form = formie()
        ->form(['title' => 'Restriction Message Pending Over Require'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'requireUser' => true,
        'requireUserMessage' => 'REQUIRE_ONLY',
        'scheduleForm' => true,
        'scheduleFormStart' => new DateTime('+1 day'),
        'scheduleFormPendingMessage' => 'PENDING_ONLY',
        'limitSubmissions' => false,
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $message = resolveRestrictionMessage($form);

    expect($message)->toContain('PENDING_ONLY')
        ->and($message)->not->toContain('REQUIRE_ONLY');
});
