<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;

interface StorageInterface
{
    public function getCurrentPageId(Form $form): ?int;
    public function setCurrentPageId(Form $form, int $pageId): void;
    public function resetCurrentPageId(Form $form): void;
}