<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

/** One boundary from Yii keys to exact form-local controls. Page summaries are derived. */
final class SubmissionErrors
{
    // Static Methods
    // =========================================================================

    public static function plainText(string $message): string
    {
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', strip_tags(html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    }

    public static function fromSubmission(Submission $submission): self
    {
        $errors = new self();
        $fields = [];
        foreach ($submission->getFields() as $field) {
            foreach ([$field->handle, (string)$field->id, (string)$field->uid] as $key) {
                $fields[$key] = $field;
            }
        }
        $walk = function(array $raw, string $prefix = '') use (&$walk, $errors, $fields, $submission): void {
            foreach ($raw as $key => $messages) {
                $rawKey = $prefix === '' ? (string)$key : $prefix . '.' . $key;
                if (is_array($messages) && !array_is_list($messages)) {
                    $walk($messages, $rawKey);
                    continue;
                }
                $path = preg_replace('/(^|\.)field:/', '$1', $rawKey);
                $path = trim(str_replace(['[', ']'], ['.', ''], $path), '.');
                $path = preg_replace('/^fields\./', '', $path);
                [$root, $nested] = array_pad(explode('.', $path, 2), 2, '');
                $field = $fields[$root] ?? null;
                foreach ((array)$messages as $message) {
                    if (!is_string($message)) {
                        continue;
                    }
                    $message = self::plainText($message);
                    if ($message === '') {
                        continue;
                    }
                    $errors->_items[] = [
                        'code' => 'validation', 'message' => $message,
                        'fieldId' => $field ? (string)$field->id : null,
                        'path' => $nested,
                        'valuePath' => $field ? $field->handle . ($nested !== '' ? '.' . $nested : '') : ($nested !== '' ? $path : 'form'),
                        'pageId' => $field?->getPage()?->id,
                        'rawKey' => $rawKey,
                    ];
                }
            }
        };
        $walk($submission->getErrors());
        return $errors;
    }

    public static function fromClient(array $errors, Form $form): self
    {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->addErrors(array_replace(['form' => $errors['form'] ?? []], $errors['fields'] ?? []));
        return self::fromSubmission($submission);
    }


    // Properties
    // =========================================================================

    private array $_items = [];


    // Public Methods
    // =========================================================================

    public function toClient(): array
    {
        $result = ['form' => [], 'fields' => []];
        foreach ($this->_items as $item) {
            if ($item['fieldId'] === null) {
                $result['form'][] = $item['message'];
            } else {
                $key = $item['fieldId'] . ($item['path'] !== '' ? '.' . $item['path'] : '');
                $result['fields'][$key][] = $item['message'];
            }
        }
        return $result;
    }

    public function toLegacy(): array
    {
        $result = [];
        foreach ($this->_items as $item) {
            $result[$item['valuePath']][] = $item['message'];
        }
        return $result;
    }

    public function forValuePath(string $path): array
    {
        return $this->toLegacy()[$path] ?? [];
    }

    public function firstPageId(): ?int
    {
        foreach ($this->_items as $item) {
            if ($item['pageId'] !== null) {
                return (int)$item['pageId'];
            }
        }
        return null;
    }

    public function forPage(int $pageId): array
    {
        return array_values(array_filter($this->_items, static fn(array $item): bool => (int)$item['pageId'] === $pageId));
    }
}
