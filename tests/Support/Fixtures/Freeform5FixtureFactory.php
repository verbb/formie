<?php

declare(strict_types=1);

namespace Tests\Support\Fixtures;

use Craft;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Backup\BatchProcessing\BatchProcessInterface;
use Solspace\Freeform\Bundles\Backup\Collections\FieldCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormSubmissionCollection;
use Solspace\Freeform\Bundles\Backup\Collections\NotificationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\PageCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RowCollection;
use Solspace\Freeform\Bundles\Backup\Collections\TemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\FileTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\NotificationTemplateCollection;
use Solspace\Freeform\Bundles\Backup\DTO\Field;
use Solspace\Freeform\Bundles\Backup\DTO\Form;
use Solspace\Freeform\Bundles\Backup\DTO\FormSubmissions;
use Solspace\Freeform\Bundles\Backup\DTO\FreeformDataset;
use Solspace\Freeform\Bundles\Backup\DTO\ImportStrategy;
use Solspace\Freeform\Bundles\Backup\DTO\Layout;
use Solspace\Freeform\Bundles\Backup\DTO\Notification;
use Solspace\Freeform\Bundles\Backup\DTO\Page;
use Solspace\Freeform\Bundles\Backup\DTO\Row;
use Solspace\Freeform\Bundles\Backup\DTO\Submission;
use Solspace\Freeform\Bundles\Backup\DTO\Templates\NotificationTemplate;
use Solspace\Freeform\Bundles\Backup\Import\FreeformImporter;
use Solspace\Freeform\Fields\Implementations\CheckboxesField;
use Solspace\Freeform\Fields\Implementations\CheckboxField;
use Solspace\Freeform\Fields\Implementations\DropdownField;
use Solspace\Freeform\Fields\Implementations\EmailField;
use Solspace\Freeform\Fields\Implementations\HiddenField;
use Solspace\Freeform\Fields\Implementations\HtmlField;
use Solspace\Freeform\Fields\Implementations\NumberField;
use Solspace\Freeform\Fields\Implementations\Pro\DatetimeField;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\MultipleSelectField;
use Solspace\Freeform\Fields\Implementations\Pro\ConfirmationField;
use Solspace\Freeform\Fields\Implementations\Pro\GroupField;
use Solspace\Freeform\Fields\Implementations\Pro\InvisibleField;
use Solspace\Freeform\Fields\Implementations\Pro\PhoneField;
use Solspace\Freeform\Fields\Implementations\Pro\RichTextField;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\Pro\WebsiteField;
use Solspace\Freeform\Fields\Implementations\RadiosField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Form\Settings\Settings;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\ServerSentEvents\SSE;
use Solspace\Freeform\Notifications\Types\Admin\Admin;
use Solspace\Freeform\Records\FormRecord;

final class Freeform5FixtureFactory
{
    public static function createLargeFixture(bool $includeExtendedFields = false): array
    {
        $suffix = strtolower(bin2hex(random_bytes(4)));
        $formUid = self::uid();
        $templateId = 'template-' . $suffix;
        $formHandle = 'f' . substr($suffix, 0, 1);

        $template = new NotificationTemplate();
        $template->uid = self::uid();
        $template->id = $templateId;
        $template->name = 'Admin Template ' . $suffix;
        $template->handle = 'admin-notify-' . $suffix;
        $template->fromEmail = 'no-reply@example.test';
        $template->fromName = 'Fixture Sender';
        $template->replyToEmail = 'reply@example.test';
        $template->subject = 'Fixture notification';
        $template->body = 'Body for {{ contactEmail:value }}';
        $template->textBody = 'Body text';
        $template->autoText = false;

        $notification = new Notification();
        $notification->id = 'notification-' . $suffix;
        $notification->uid = self::uid();
        $notification->enabled = true;
        $notification->idAttribute = 'template';
        $notification->name = 'Admin Notification';
        $notification->type = Admin::class;
        $notification->metadata = [
            'name' => 'Admin Notification',
            'enabled' => true,
            'template' => $templateId,
            'recipients' => [
                ['email' => 'ops@example.test', 'name' => 'Ops'],
            ],
        ];

        $form = new Form();
        $form->uid = $formUid;
        $form->name = 'Freeform Migration Fixture ' . $suffix;
        $form->handle = $formHandle;
        $form->order = 0;
        $form->settings = new Settings([
            'general' => [
                'name' => 'Freeform Migration Fixture ' . $suffix,
                'handle' => $formHandle,
                'submissionTitle' => 'Fixture submission title',
                'attributes' => [
                    'form' => [],
                    'row' => [],
                    'success' => [],
                    'errors' => [],
                ],
            ],
            'behavior' => [
                'ajax' => true,
                'successBehavior' => 'redirect-return-url',
                'returnUrl' => 'https://example.test/thanks',
                'successMessage' => 'Thank you for submitting.',
            ],
        ], Craft::$container->get(PropertyProvider::class));
        $form->pages = self::buildPages($includeExtendedFields);
        $form->notifications = new NotificationCollection([$notification]);
        $form->notificationTemplates = new NotificationTemplateCollection([$template]);

        $submissionRows = [
            [
                'title' => 'Fixture Submission A',
                'status' => 'open',
                'values' => [
                    'fullName' => 'Alice Example',
                    'contactEmail' => 'alice@example.test',
                    'alternateName' => 'Alice Alt',
                    'consent' => 'yes',
                    'age' => 34,
                    'source' => 'direct',
                    'topic' => 'sales',
                    'interests' => ['a', 'c'],
                    'choice' => 'daily',
                    'scheduledAt' => '2026-01-20 10:00:00',
                    'profileGroup' => [
                        'groupNote' => 'Nested note',
                    ],
                ],
            ],
            [
                'title' => 'Fixture Submission B',
                'status' => 'open',
                'values' => [
                    'fullName' => 'Bob Example',
                    'contactEmail' => 'bob@example.test',
                    'alternateName' => 'Bob Alt',
                    'consent' => 'no',
                    'age' => 44,
                    'source' => 'referral',
                    'topic' => 'support',
                    'interests' => ['b'],
                    'choice' => 'weekly',
                    'scheduledAt' => '2026-01-21 11:00:00',
                    'profileGroup' => [
                        'groupNote' => 'Second nested note',
                    ],
                ],
            ],
        ];

        if ($includeExtendedFields) {
            $submissionRows[0]['values']['confirmEmail'] = 'alice@example.test';
            $submissionRows[0]['values']['resumeFiles'] = [];
            $submissionRows[0]['values']['secretToken'] = 'secret-a';
            $submissionRows[0]['values']['departments'] = ['eng', 'sales'];
            $submissionRows[0]['values']['contactPhone'] = '+15551234567';
            $submissionRows[0]['values']['details'] = 'Long notes A';
            $submissionRows[0]['values']['website'] = 'https://alice.example.test';
            $submissionRows[0]['values']['extraRich'] = '<p>Alice rich text</p>';
            $submissionRows[0]['values']['detailsTable'] = [['q1' => 'Row A']];

            $submissionRows[1]['values']['confirmEmail'] = 'bob@example.test';
            $submissionRows[1]['values']['resumeFiles'] = [];
            $submissionRows[1]['values']['secretToken'] = 'secret-b';
            $submissionRows[1]['values']['departments'] = ['support'];
            $submissionRows[1]['values']['contactPhone'] = '+15557654321';
            $submissionRows[1]['values']['details'] = 'Long notes B';
            $submissionRows[1]['values']['website'] = 'https://bob.example.test';
            $submissionRows[1]['values']['extraRich'] = '<p>Bob rich text</p>';
            $submissionRows[1]['values']['detailsTable'] = [['q1' => 'Row B']];
        }

        $formSubmissions = new FormSubmissions();
        $formSubmissions->formUid = $formUid;
        $formSubmissions->submissionBatchProcessor = new ArraySubmissionBatchProcessor($submissionRows);
        $formSubmissions->setProcessor(static function(array $row): Submission {
            $submission = new Submission();
            $submission->title = (string)$row['title'];
            $submission->status = (string)$row['status'];

            foreach (($row['values'] ?? []) as $handle => $value) {
                $submission->{$handle} = $value;
            }

            return $submission;
        });

        $dataset = (new FreeformDataset())
            ->setStrategy(new ImportStrategy([
                'forms' => ImportStrategy::TYPE_REPLACE,
                'templates' => ImportStrategy::TYPE_REPLACE,
                'integrations' => ImportStrategy::TYPE_SKIP,
            ]))
            ->setForms(new FormCollection([$form]))
            ->setTemplates(
                (new TemplateCollection())
                    ->setNotification(new NotificationTemplateCollection())
                    ->setFormatting(new FileTemplateCollection())
                    ->setSuccess(new FileTemplateCollection())
            )
            ->setFormSubmissions(new FormSubmissionCollection([$formSubmissions]));

        $importer = Craft::$container->get(FreeformImporter::class);
        $importer->import($dataset, new SilentSse());

        $formRecord = FormRecord::findOne(['uid' => $formUid]);
        $importedForm = $formRecord ? Freeform::getInstance()->forms->getFormById((int)$formRecord->id) : null;

        return [
            'formId' => (int)($formRecord->id ?? 0),
            'formUid' => $formUid,
            'formHandle' => $formHandle,
            'formTitle' => $form->name,
            'freeformForm' => $importedForm,
        ];
    }

    private static function buildPages(bool $includeExtendedFields = false): PageCollection
    {
        return new PageCollection([
            self::page(
                'Primary Page',
                [
                    [
                        self::field(TextField::class, 'fullName', 'Full Name', ['placeholder' => 'Full Name'], true),
                        self::field(EmailField::class, 'contactEmail', 'Contact Email'),
                        self::field(TextField::class, 'alternateName', 'Alternate Name', ['placeholder' => 'Alt Name']),
                    ],
                    [
                        self::field(CheckboxField::class, 'consent', 'Consent', ['checked' => true, 'value' => 'yes']),
                        self::field(NumberField::class, 'age', 'Age', ['min' => 1, 'max' => 120]),
                        self::field(HiddenField::class, 'source', 'Source', ['value' => 'direct']),
                    ],
                ]
            ),
            self::page(
                'Secondary Page',
                [
                    [
                        self::field(DropdownField::class, 'topic', 'Topic', [
                            'options' => self::options(['sales', 'support', 'billing']),
                        ]),
                        self::field(CheckboxesField::class, 'interests', 'Interests', [
                            'options' => self::options(['a', 'b', 'c']),
                        ]),
                        self::field(RadiosField::class, 'choice', 'Choice', [
                            'options' => self::options(['daily', 'weekly']),
                        ]),
                    ],
                    [
                        self::groupField(),
                        self::field(HtmlField::class, 'bannerHtml', 'Banner', ['content' => '<p>Intro</p>']),
                        self::field(DatetimeField::class, 'scheduledAt', 'Scheduled At', ['dateTimeType' => 'both', 'dateOrder' => 'ymd']),
                    ],
                    ...($includeExtendedFields ? [[
                        self::field(ConfirmationField::class, 'confirmEmail', 'Confirm Email', ['targetField' => 'contactEmail']),
                        self::field(FileUploadField::class, 'resumeFiles', 'Resume Files'),
                        self::field(InvisibleField::class, 'secretToken', 'Secret Token', ['value' => 'fixture-secret']),
                    ], [
                        self::field(MultipleSelectField::class, 'departments', 'Departments', [
                            'options' => self::options(['eng', 'sales', 'support']),
                        ]),
                        self::field(PhoneField::class, 'contactPhone', 'Contact Phone'),
                        self::field(TableField::class, 'detailsTable', 'Details Table', [
                            'tableLayout' => [
                                ['label' => 'Question', 'value' => 'q1', 'type' => 'singleline'],
                            ],
                            'addButtonLabel' => 'Add Row',
                        ]),
                    ], [
                        self::field(TextareaField::class, 'details', 'Details', ['maxLength' => 500]),
                        self::field(WebsiteField::class, 'website', 'Website'),
                        self::field(RichTextField::class, 'extraRich', 'Extra Rich', ['content' => '<p>Fixture rich text</p>']),
                    ]] : []),
                ]
            ),
        ]);
    }

    private static function page(string $label, array $rowFields): Page
    {
        $rows = [];

        foreach ($rowFields as $fields) {
            $row = new Row();
            $row->uid = self::uid();
            $row->fields = new FieldCollection($fields);
            $rows[] = $row;
        }

        $layout = new Layout();
        $layout->uid = self::uid();
        $layout->rows = new RowCollection($rows);

        $page = new Page();
        $page->uid = self::uid();
        $page->label = $label;
        $page->layout = $layout;

        return $page;
    }

    private static function groupField(): Field
    {
        $inner = self::field(TextField::class, 'groupNote', 'Group Note');

        $innerRow = new Row();
        $innerRow->uid = self::uid();
        $innerRow->fields = new FieldCollection([$inner]);

        $innerLayout = new Layout();
        $innerLayout->uid = self::uid();
        $innerLayout->rows = new RowCollection([$innerRow]);

        $group = self::field(GroupField::class, 'profileGroup', 'Profile Group');
        $group->layout = $innerLayout;

        return $group;
    }

    private static function field(string $type, string $handle, string $label, array $metadata = [], bool $required = false): Field
    {
        $field = new Field();
        $field->uid = self::uid();
        $field->name = $label;
        $field->handle = $handle;
        $field->type = $type;
        $field->required = $required;
        $field->metadata = $metadata;

        return $field;
    }

    private static function options(array $values): OptionCollection
    {
        $collection = new OptionCollection();

        foreach ($values as $value) {
            $collection->add((string)$value, strtoupper((string)$value));
        }

        return $collection;
    }

    private static function uid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}

final class ArraySubmissionBatchProcessor implements BatchProcessInterface
{
    public function __construct(private readonly array $rows)
    {
    }

    public function batch(int $size): \Generator
    {
        foreach (array_chunk($this->rows, max(1, $size)) as $chunk) {
            yield $chunk;
        }
    }

    public function total(): int
    {
        return count($this->rows);
    }
}

final class SilentSse extends SSE
{
    public function __construct()
    {
    }

    public function message(string $event, mixed $message): void
    {
    }
}
