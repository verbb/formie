<?php

$form = \verbb\formie\elements\Form::find()->handle('browserCraftLogin')->one();
if (!$form) {
    $form = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Craft login', 'handle' => 'browserCraftLogin'])
        ->settings(['disableCaptchas' => true, 'validationOnSubmit' => true, 'validationOnFocus' => true])
        ->singleLineTextField('loginName', ['label' => 'Login name', 'required' => true])
        ->passwordField('password', ['label' => 'Password', 'required' => true])->create();
}
$user = \craft\elements\User::find()->username('browserCraftLogin')->status(null)->one();
if (!$user) {
    $user = new \craft\elements\User([
        'username' => 'browserCraftLogin',
        'email' => 'craft-login@example.test',
        'newPassword' => 'testing-only-craft-login-password',
    ]);
    if (!\Craft::$app->getElements()->saveElement($user)) {
        throw new RuntimeException('Cannot save the Craft login fixture user.');
    }
    \Craft::$app->getUsers()->activateUser($user);
}
