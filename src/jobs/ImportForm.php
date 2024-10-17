<?php

namespace verbb\formie\jobs;

use craft\queue\BaseJob;
use craft\helpers\Json;
use verbb\formie\helpers\ImportExportHelper;

use Exception;

class ImportForm extends BaseJob
{

    public ?string $fileLocation = null;
    public ?string $formAction = 'update';


    public function execute($queue): void
    {
        $this->setProgress($queue, 0.33);

        if (!$this->fileLocation) {
            throw new Exception("No file provided.");
        }

        $json = Json::decode(file_get_contents($this->fileLocation));
        $form = ImportExportHelper::importFormFromJson($json, $this->formAction);

        $this->setProgress($queue, 0.66);

        // check for errors
        if ($form->getConsolidatedErrors()) {
            $errors = Json::encode($form->getConsolidatedErrors());
            throw new Exception("Unable to import form {$this->fileLocation}" . PHP_EOL . "Errors: {$errors}.");
        }

        $this->setProgress($queue, 1);
    }

    protected function defaultDescription(): string
    {
        $fileName = basename($this->fileLocation);
        return "Import of JSON '$fileName'.";
    }
}
