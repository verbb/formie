<?php

namespace verbb\formie\console\controllers;

use verbb\formie\elements\Form;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\jobs\ImportForm;
use verbb\formie\Formie;

use Craft;
use craft\console\Controller;
use craft\helpers\Db;
use craft\helpers\Console;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\FileHelper;

use Throwable;

use yii\console\ExitCode;

/**
 * Manages Formie Forms.
 */
class FormsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var string form ID as a comma-separated list
     */
    public ?string $formId = null;

    /**
     * @var string form handle as a comma-separated list
     */
    public ?string $formHandle = null;

    /**
     * @var bool Create a new form, prevent updating an existing form
     */
    public bool $create = false;

    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'delete':
                $options[] = 'formId';
                $options[] = 'formHandle';
                break;
            case 'import':
            case 'import-all':
                $options[] = 'create';
                break;
        }

        return $options;
    }

    /**
     * Delete Formie forms.
     */
    public function actionDelete(): int
    {
        $formIds = null;

        if ($this->formId !== null) {
            $formIds = explode(',', $this->formId);
        }

        if ($this->formHandle !== null) {
            $formHandle = explode(',', $this->formHandle);

            $formIds = Form::find()->handle($formHandle)->ids();
        }

        if (!$this->formId && !$this->formHandle) {
            $this->stderr('You must provide either a --form-id or --form-handle option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$formIds) {
            $this->stderr('Unable to find any matching forms.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($formIds as $formId) {
            $query = Form::find()->id($formId);

            $count = (int)$query->count();

            if ($count === 0) {
                $this->stdout('No forms exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);

                continue;
            }

            $elementsText = $count === 1 ? 'form' : 'forms';
            $this->stdout("Deleting $count $elementsText for form $formId ..." . PHP_EOL, Console::FG_YELLOW);

            $elementsService = Craft::$app->getElements();

            foreach (Db::each($query) as $element) {
                $elementsService->deleteElement($element);

                $this->stdout("Deleted form $element->id ..." . PHP_EOL, Console::FG_GREEN);
            }
        }

        return ExitCode::OK;
    }

    /**
     * List all available Formie forms that can be exported or imported.
     */
    public function actionList($folderPath = null): int
    {
        $path = $folderPath ?? $this->getExportPath();
        try {
            $files = FileHelper::findFiles($path, ['only' => ['*.json']]);
        } catch (\Throwable $th) {
            $this->stderr("The export directory is empty or does not exist." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!empty($files)) {
            $listEntries[] = [
                'title' => 'JSON to import:',
                'entriesList' => array_map(function ($file) {
                    return [
                        'name' => $file,
                        'title' => ''
                    ];
                }, $files)
            ];
        }

        $allForms = Formie::$plugin->getForms()->getAllForms();
        if (!empty($allForms)) {
            $listEntries[] = [
                'title' => 'Existing forms:',
                'entriesList' => array_map(function ($form) {
                    return [
                        'name' => "[$form->id] $form->handle",
                        'title' => $form->title
                    ];
                }, $allForms)
            ];
        }

        foreach ($listEntries as $entries) {
            $this->stdout($entries['title'] . PHP_EOL, Console::FG_YELLOW);

            $handleMaxLen = max(array_map('strlen', array_column($entries['entriesList'], 'name')));

            foreach ($entries['entriesList'] as $entry) {
                $this->stdout("- " . $entry['name'], Console::FG_GREEN);
                $this->stdout(Console::moveCursorTo($handleMaxLen + 5));
                $this->stdout($entry['title'] . PHP_EOL);
            }
        }


        return ExitCode::OK;
    }

    /**
     * Export Formie forms as JSON. Accepts comma-separated lists of form IDs and/or handles.
     */
    public function actionExport($idsOrHandles = null): int
    {
        $formIds = null;

        if ($idsOrHandles === null) {
            $this->stderr('No arguments provided' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach (explode(',', $idsOrHandles) as $idOrHandle) {
            if (is_numeric($idOrHandle)) {
                $formIds[] = $idOrHandle;
            } else {
                $formIds[] = Form::find()->handle($idOrHandle)->one()->id ?? null;
            }
        }

        if (!$formIds) {
            $this->stderr('Unable to find any matching forms.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $query = Form::find()->id($formIds);
        $count = (int)$query->count();

        if ($count === 0) {
            $this->stdout('No forms exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);
        }

        $elementsText = $count === 1 ? 'form' : 'forms';
        $this->stdout("Exporting $count $elementsText ..." . PHP_EOL, Console::FG_YELLOW);

        foreach (Db::each($query) as $element) {
            try {
                $formExport = ImportExportHelper::generateFormExport($element);
                $json = Json::encode($formExport, JSON_PRETTY_PRINT);
                $exportPath = $this->generateExportPathByHandle($element->handle);
                FileHelper::writeToFile($exportPath, $json);
                $this->stdout("Exporting form $element->id to $exportPath" . PHP_EOL, Console::FG_GREEN);
            } catch (Throwable $e) {

                $this->stderr("Unable to export form $element->id." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return ExitCode::OK;
    }

    /**
     * Import a Formie form JSON from a path.
     */
    public function actionImport($fileLocation = null): int
    {

        if ($fileLocation === null) {
            $this->stderr('You must provide a path to a JSON file.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!is_file($fileLocation)) {
            // Expected absolute path
            // Try export folder
            $fileLocation = $this->getExportPath() . DIRECTORY_SEPARATOR  . $fileLocation;
            if (!is_file($fileLocation)) {
                $this->stderr("No file exists at the given path." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        if (strtolower(pathinfo($fileLocation, PATHINFO_EXTENSION)) !== 'json') {
            $this->stderr("The file is not of type JSON." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $json = Json::decode(file_get_contents($fileLocation));
        } catch (\Exception $e) {
            $this->stderr("Failed to decode JSON from the file." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // default, update existing form
        $formAction = $this->create ? 'create' : 'update';

        $form = ImportExportHelper::importFormFromJson($json, $formAction);

        // check for errors
        if ($form->getConsolidatedErrors()) {
            $this->stderr("Unable to import the form." . PHP_EOL, Console::FG_RED);
            $errors = Json::encode($form->getConsolidatedErrors());
            $this->stderr("Errors: $errors" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Form $form->handle has be {$formAction}d." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Import all Formie JSON from a folder.
     */
    public function actionImportAll($folderPath = null): int
    {
        $path = $folderPath ?? $this->getExportPath();
        try {
            $files = FileHelper::findFiles($path, ['only' => ['*.json']]);
        } catch (\Throwable $th) {
            $this->stderr("The export directory is empty or does not exist." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (empty($files)) {
            $this->stderr("No JSON files found in folder $path." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // use jobs to prevent db overload or php timeout
        foreach ($files as $file) {

            Queue::push(new ImportForm(
                [
                    'fileLocation' => $file,
                    'formAction' => $this->create ? 'create' : 'update'
                ]
            ));

            $basename = basename($file);
            $this->stdout("File '$basename' has been added to the import queue." . PHP_EOL, Console::FG_GREEN);
        }


        return ExitCode::OK;
    }

    // Protected Methods
    // =========================================================================

    private function generateExportPathByHandle($handle): string
    {
        return $this->getExportPath() . DIRECTORY_SEPARATOR . "formie-$handle.json";
    }

    private function getExportPath(): string
    {
        $settings = Formie::$plugin->getSettings();
        return $settings->getAbsoluteDefaultExportFolder();
    }
}
