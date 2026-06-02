<?php
namespace verbb\formie\jobs;

use yii\queue\ExecEvent;

interface DebuggableJobInterface
{
    public function onError(ExecEvent $event): void;
}
