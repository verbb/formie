<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Plan;

it('archives an owned payment plan with a persisted typed date and ignores another integration', function (): void {
    $owner = new Stripe(['name' => 'Plan owner', 'handle' => 'planOwner' . uniqid()]);
    $other = new Stripe(['name' => 'Plan other', 'handle' => 'planOther' . uniqid()]);
    foreach ([$owner, $other] as $integration) { expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue(); }
    $plan = new Plan(['integrationId' => $owner->id, 'name' => 'Plan contract', 'handle' => 'planContract' . uniqid(), 'reference' => 'plan_' . uniqid(), 'enabled' => true, 'isArchived' => false, 'planData' => []]);
    $service = Formie::$plugin->getPlans();
    expect($service->savePlan($plan))->toBeTrue();
    $method = new ReflectionMethod(Stripe::class, 'handlePlanDeleted');
    $data = ['id' => 'evt_plan', 'data' => ['object' => ['id' => $plan->reference]]];
    $method->invoke($other, $data);
    expect($service->getPlanById($plan->id)->isArchived)->toBeFalse();
    $method->invoke($owner, $data);
    $archived = $service->getPlanById($plan->id);
    expect($archived->isArchived)->toBeTrue();
    expect($archived->dateArchived)->toBeInstanceOf(DateTime::class);
});
