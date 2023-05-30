<?php

namespace verbb\formie\events;

use yii\base\Event;

class TemplatesPathEvent extends Event
{
	// Properties
	// =========================================================================

	public array $paths = [];
}
