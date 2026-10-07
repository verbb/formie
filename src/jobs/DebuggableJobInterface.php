<?php
namespace verbb\formie\jobs;

use yii\queue\ExecEvent;

interface DebuggableJobInterface
{
    // Public Methods
    // =========================================================================

    public function onError(ExecEvent $event): void;
}
