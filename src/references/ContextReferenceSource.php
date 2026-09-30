<?php
namespace verbb\formie\references;

use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;
use verbb\formie\models\ReferenceExpression;

/** Registered context sources expose only explicitly supported properties. */
final class ContextReferenceSource
{
    // Public Methods
    // =========================================================================

    public function resolve(ReferenceExpression $expression, ReferenceContext $context): ResolvedReference
    {
        $id = $expression->identifier;
        $target = $expression->target;
        if ($target === 'dispatch') {
            $id = str_replace(':', '.', $id);
        }
        $definition = (new ReferenceCatalogue())->definition($target, $id);
        if (!$definition) {
            throw new ReferenceException(ReferenceDiagnostic::UnknownSource);
        }
        $definition->assertAvailable($context);
        if ($target === 'custom') {
            $source = (new ReferenceCatalogue())->source($id);
            if (!$source->definition->server || !in_array('server', $context->permissions, true)) {
                throw new ReferenceException(ReferenceDiagnostic::ForbiddenSource);
            }
            $value = ($source->resolver)($context);
            if (!$source->definition->valueType->accepts($value)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }
            return new ResolvedReference($expression, $value, $source->definition);
        }
        if ($target === 'env') {
            if (!in_array('server', $context->permissions, true) || !array_key_exists($id, $context->environment)) {
                throw new ReferenceException(ReferenceDiagnostic::ForbiddenSource);
            }
            $value = $context->environment[$id];
        } elseif ($target === 'metadata') {
            $parts = explode('.', $id);
            if (!in_array($parts[0], ['request', 'custom'], true)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
            }
            $value = $context->metadata;
            foreach ($parts as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
                $value = $value[$part];
            }
            if (!FieldValueType::storageSafe()->accepts($value)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }
        } elseif (in_array($target, ['allFields', 'allContentFields', 'allVisibleFields'], true)) {
            if (!$context->submission) {
                throw new ReferenceException(ReferenceDiagnostic::ForbiddenSource);
            }
            $value = Variables::getSummaryVariables($context->submission, $context->notification ?? new Notification())[$target];
        } else {
            $values = match ($target) {
                'form' => ['name' => $context->form?->title, 'handle' => $context->form?->handle],
                'submission' => ['title' => $context->submission?->title, 'id' => $context->submission?->id, 'uid' => $context->submission?->uid, 'url' => $context->submission?->getCpEditUrl(), 'date' => $context->submission?->dateCreated?->format('Y-m-d H:i:s'), 'site' => $context->submission?->siteId, 'status' => $context->submission?->getStatus()],
                'site' => ['id' => $context->site?->id, 'name' => $context->site?->name, 'handle' => $context->site?->handle, 'url' => $context->site?->getBaseUrl(), 'language' => $context->site?->language],
                'user' => ['id' => $context->user?->id, 'email' => $context->user?->email, 'name' => $context->user?->username, 'username' => $context->user?->username, 'fullName' => $context->user?->fullName, 'firstName' => $context->user?->firstName, 'lastName' => $context->user?->lastName, 'ip' => $context->submission?->ipAddress],
                'system' => $context->system,
                'timestamp' => ['' => $context->now?->format('Y-m-d H:i:s')],
                'report' => $context->report,
                'dispatch' => $this->_dispatch($context->dispatch),
                default => throw new ReferenceException(ReferenceDiagnostic::UnknownSource),
            };
            if (!array_key_exists($id, $values)) {
                throw new ReferenceException(ReferenceDiagnostic::UnknownSource);
            }
            $value = $values[$id];
        }
        return new ResolvedReference($expression, $value, $definition);
    }

    // Private Methods
    // =========================================================================

    private function _dispatch(array $dispatch): array
    {
        $values = [];
        foreach ($dispatch as $handle => $result) {
            foreach (['id', 'url', 'success', 'type'] as $key) {
                $values[$handle . '.' . $key] = $result[$key] ?? null;
            }
        }
        return $values;
    }

}
