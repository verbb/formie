<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\UploadLimits;

use Craft;
use craft\base\Field as CraftField;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;

use yii\base\InvalidArgumentException;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;

class FileUploadInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType($context): ListOfType
    {
        $typeName = 'FileUploadInput';

        if ($argumentType = GqlEntityRegistry::getEntity($typeName)) {
            return Type::listOf($argumentType);
        }

        $argumentType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'uploadUid' => ['type' => Type::string(), 'description' => 'The staged upload identity. Requires an attach capability.'],
                'attachToken' => ['type' => Type::string(), 'description' => 'The purpose-bound capability returned by upload creation.'],
                'fileData' => [
                    'name' => 'fileData',
                    'type' => Type::string(),
                    'description' => 'The contents of the file in Base64 format. If provided, takes precedence over the URL.',
                ],
                'filename' => [
                    'name' => 'filename',
                    'type' => Type::string(),
                    'description' => 'The file name to use (including the extension) data with the `fileData` field.',
                ],
                'assetId' => [
                    'name' => 'assetId',
                    'type' => Type::int(),
                    'description' => 'The ID of an existing related asset to retain while editing.',
                ],
            ],
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));

        return Type::listOf($argumentType);
    }

    public static function normalizeValue($values): array
    {
        $assetIds = [];
        $assetPositions = [];
        $uploadReferences = [];
        $newValues = [];
        $maxBytes = UploadLimits::maxFileBytes();
        $maxEncodedBytes = 4 * (int)ceil($maxBytes / 3);

        foreach ($values as $key => $value) {
            $position = is_int($key) ? $key : count($assetIds) + count($uploadReferences) + count($newValues);

            if (!empty($value['uploadUid']) || !empty($value['attachToken'])) {
                $uploadUid = trim((string)($value['uploadUid'] ?? ''));
                $attachToken = trim((string)($value['attachToken'] ?? ''));

                if ($uploadUid === '' || $attachToken === '' || !empty($value['assetId']) || !empty($value['fileData'])) {
                    throw new UserError('Invalid upload capability.');
                }

                // Exact form/field/submission authorization happens where the field and
                // SubmissionCommand are available. Keep the capability intact until then.
                $uploadReferences[] = [
                    'uploadUid' => $uploadUid,
                    'attachToken' => $attachToken,
                    'position' => $position,
                ];

                continue;
            }
            // Translate `fileData` to `data` which the Craft Assets field natively supports. Also handle filename.
            if (!empty($value['fileData'])) {
                $dataString = ArrayHelper::remove($value, 'fileData');
                // Bound the input before regex captures or decoded copies are allocated.
                if (!is_string($dataString) || strlen($dataString) > $maxEncodedBytes + 256) {
                    throw new UserError('Uploaded file exceeds the maximum allowed size.');
                }
                // Each file must decode independently; a malformed later item must never
                // inherit the previous file's bytes. Strict decoding rejects corrupt data.
                $fileData = false;

                if (preg_match('/\Adata:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)\z/is', $dataString, $matches)) {
                    $encoded = $matches['data'];
                    $padding = str_ends_with($encoded, '==') ? 2 : (str_ends_with($encoded, '=') ? 1 : 0);
                    $decodedBytes = intdiv(strlen($encoded) * 3, 4) - $padding;
                    if (strlen($encoded) > $maxEncodedBytes || $decodedBytes > $maxBytes) {
                        throw new UserError('Uploaded file exceeds the maximum allowed size.');
                    }
                    $fileData = base64_decode($matches['data'], true);
                    if ($fileData !== false && strlen($fileData) > $maxBytes) {
                        throw new UserError('Uploaded file exceeds the maximum allowed size.');
                    }
                }

                if ($fileData !== false && $fileData !== '') {
                    if (empty($value['filename'])) {
                        // Make up a filename
                        $extension = null;

                        if (isset($matches['type'])) {
                            try {
                                $extension = FileHelper::getExtensionByMimeType($matches['type']);
                            } catch (InvalidArgumentException $e) {
                            }
                        }

                        if (!$extension) {
                            throw new UserError('Invalid file data provided.');
                        }

                        $newValues[$key]['filename'] = 'Uploaded_file.' . $extension;
                    } else {
                        $newValues[$key]['filename'] = AssetsHelper::prepareAssetName($value['filename']);
                    }

                    $newValues[$key]['type'] = 'data';
                    $newValues[$key]['data'] = $fileData;
                } else {
                    throw new UserError('Invalid file data provided');
                }
            }

            if (!empty($value['assetId']) && is_numeric($value['assetId'])) {
                $assetIds[] = (int)$value['assetId'];
                $assetPositions[] = $position;
            }
        }

        if ($assetIds && !$newValues && !$uploadReferences) {
            return $assetIds;
        }

        $normalized = [];

        foreach ($assetIds as $index => $assetId) {
            $normalized[$uploadReferences ? $assetPositions[$index] : $index] = $assetId;
        }

        if ($uploadReferences) {
            $normalized['uploadReferences'] = $uploadReferences;
        }

        if ($newValues) {
            // Save under `mutationData` so fields can stage bytes after validation.
            $normalized['mutationData'] = $newValues;
        }

        return $normalized;
    }
}
