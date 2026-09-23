<?php
namespace verbb\formie\controllers;

use craft\web\Controller;

abstract class AdminController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->requireAdmin(false);

        return parent::beforeAction($action);
    }
}
