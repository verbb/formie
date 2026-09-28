<?php
namespace verbb\formie\migrations;

use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260928_020000_subscription_lifecycle extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $columns = [
            'providerStatus' => $this->string(80),
            'startedAt' => $this->dateTime(),
            'trialStartsAt' => $this->dateTime(),
            'trialEndsAt' => $this->dateTime(),
            'currentPeriodStartsAt' => $this->dateTime(),
            'currentPeriodEndsAt' => $this->dateTime(),
            'nextPaymentAt' => $this->dateTime(),
            'pausedAt' => $this->dateTime(),
            'cancelAt' => $this->dateTime(),
            'cancelledAt' => $this->dateTime(),
            'endedAt' => $this->dateTime(),
            'cancellationMode' => $this->string(32),
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->db->columnExists(Table::FORMIE_SUBSCRIPTIONS, $name)) {
                $this->addColumn(Table::FORMIE_SUBSCRIPTIONS, $name, $definition);
            }
        }

        foreach ((new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->each() as $row) {
            $data = Json::decodeIfJson($row['subscriptionData'] ?? null);
            $data = is_array($data) ? $data : [];
            $providerStatus = trim((string)($data['status'] ?? ''));
            $status = $this->_status((string)($row['status'] ?? ''), $providerStatus);
            $periodEnd = $this->_date($data['current_period_end'] ?? null);
            $nextPaymentAt = $this->_date($data['upcoming_payments'][0]['charge_date'] ?? null)
                ?? ($row['nextPaymentDate'] ?? null)
                ?? $periodEnd;
            $cancelScheduled = (string)($row['status'] ?? '') === 'cancelling' || !empty($data['cancel_at_period_end']);

            $this->update(Table::FORMIE_SUBSCRIPTIONS, [
                'status' => $status->value,
                'providerStatus' => $providerStatus !== '' ? $providerStatus : null,
                'startedAt' => $this->_date($data['start_date'] ?? $data['created_at'] ?? null),
                'trialStartsAt' => $this->_date($data['trial_start'] ?? null),
                'trialEndsAt' => $this->_date($data['trial_end'] ?? null),
                'currentPeriodStartsAt' => $this->_date($data['current_period_start'] ?? null),
                'currentPeriodEndsAt' => $periodEnd,
                'nextPaymentAt' => $nextPaymentAt,
                'pausedAt' => $row['dateSuspended'] ?? null,
                'cancelAt' => $cancelScheduled ? ($this->_date($data['cancel_at'] ?? null) ?? $periodEnd ?? $nextPaymentAt) : null,
                'cancelledAt' => $row['dateCanceled'] ?? $this->_date($data['canceled_at'] ?? null),
                'endedAt' => $row['dateExpired'] ?? $this->_date($data['ended_at'] ?? null),
                'cancellationMode' => $cancelScheduled ? SubscriptionCancellationMode::AT_PERIOD_END->value : null,
            ], ['id' => $row['id']], [], false);
        }

        foreach ((new Query())->from(Table::FORMIE_PAYMENTS)->where(['not', ['subscriptionId' => null]])->each() as $row) {
            $scope = Json::decodeIfJson($row['scope'] ?? null);
            $scope = is_array($scope) ? $scope : [];

            if (!empty($scope['legacy'])) {
                // Formie 3 did not distinguish setup attempts from actual charges.
                // Keep historical rows visible as payments without inventing either meaning.
                $scope['monetary'] = true;
                $scope['operation'] = 'legacySubscriptionPayment';
            } else {
                $initial = (bool)($scope['initial'] ?? false);
                $scope['monetary'] = !$initial;
                $scope['operation'] = $initial ? 'subscriptionSetup' : 'recurringCharge';
            }

            $this->update(Table::FORMIE_PAYMENTS, ['scope' => Json::encode($scope)], ['id' => $row['id']], [], false);
        }

        foreach (['nextPaymentDate', 'dateSuspended', 'dateCanceled', 'dateExpired'] as $column) {
            if ($this->db->columnExists(Table::FORMIE_SUBSCRIPTIONS, $column)) {
                $this->dropColumn(Table::FORMIE_SUBSCRIPTIONS, $column);
            }
        }

        foreach (['nextPaymentAt', 'cancelAt', 'endedAt'] as $column) {
            if (!$this->_hasIndexOn($column)) {
                $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, $column, false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260928_020000_subscription_lifecycle cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _status(string $status, string $providerStatus): SubscriptionStatus
    {
        return match ($providerStatus) {
            'trialing' => SubscriptionStatus::TRIALING,
            'active' => SubscriptionStatus::ACTIVE,
            'past_due', 'unpaid' => SubscriptionStatus::PAST_DUE,
            'paused' => SubscriptionStatus::PAUSED,
            'incomplete_expired', 'customer_approval_denied' => SubscriptionStatus::FAILED,
            'cancelled', 'canceled' => SubscriptionStatus::CANCELLED,
            'finished' => SubscriptionStatus::COMPLETED,
            default => SubscriptionStatus::fromStored($status, $providerStatus),
        };
    }

    private function _date(mixed $value): ?string
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return gmdate('Y-m-d H:i:s', (int)$value);
        }

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function _hasIndexOn(string $column): bool
    {
        foreach ($this->db->getSchema()->getTableIndexes(Table::FORMIE_SUBSCRIPTIONS, true) as $index) {
            if ($index->columnNames === [$column]) {
                return true;
            }
        }

        return false;
    }
}
