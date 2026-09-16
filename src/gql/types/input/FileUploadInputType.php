<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\helpers\ArrayHelper;

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
                    'description' => 'The ID of an already-uploaded asset.',
                ],
            ],
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));

        return Type::listOf($argumentType);
    }

    public static function normalizeValue($values): array
    {
        $assetIds = [];
        $newValues = [];

        foreach ($values as $key => $value) {
            // Translate `fileData` to `data` which the Craft Assets field natively supports. Also handle filename.
            if (!empty($value['fileData'])) {
                $dataString = ArrayHelper::remove($value, 'fileData');
                // Each file must decode independently; a malformed later item must never
                // inherit the previous file's bytes. Strict decoding rejects corrupt data.
                $fileData = false;

                if (preg_match('/\Adata:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)\z/is', $dataString, $matches)) {
                    // Decode the file
                    $fileData = base64_decode($matches['data'], true);
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

            if (!empty($value['assetId'])) {
                $assetIds[] = $value['assetId'];
            }
        }

        // Keep the ID-only contract, but retain new data alongside IDs for mixed edits.
        if ($assetIds && !$newValues) {
            return $assetIds;
        }

        // Save under `mutationData` so we can handle normalization easier for GQL-specific stuff
        return $assetIds + ['mutationData' => $newValues];
    }
}
