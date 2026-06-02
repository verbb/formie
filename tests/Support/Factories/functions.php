<?php

declare(strict_types=1);

use Tests\Support\Factories\FormieFactory;

if (!function_exists('formie')) {
    function formie(): FormieFactory
    {
        return FormieFactory::make();
    }
}
