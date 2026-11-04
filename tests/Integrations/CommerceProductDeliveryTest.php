<?php

use craft\commerce\elements\Product as ProductElement;
use craft\commerce\models\ProductType;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\elements\User;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\Product;

function commerceProductDelivery(array $mapping, array $values, bool $overwriteValues = false): array
{
    if (!Commerce::getInstance() || !Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
        test()->markTestSkipped('Commerce plugin is not installed/enabled for this environment.');
    }

    $key = bin2hex(random_bytes(5));
    $siteId = Craft::$app->getSites()->getPrimarySite()->id;
    $type = new ProductType(['name' => 'Delivery ' . $key, 'handle' => 'delivery' . $key, 'maxVariants' => 1, 'hasVariantTitleField' => false]);
    $type->setSiteSettings([$siteId => new ProductTypeSite(['siteId' => $siteId, 'hasUrls' => false])]);
    expect(Commerce::getInstance()->getProductTypes()->saveProductType($type), json_encode($type->getErrors()))->toBeTrue();

    $connection = new Product(['name' => 'Delivery ' . $key, 'handle' => 'delivery' . $key, 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($connection), json_encode($connection->getErrors()))->toBeTrue();

    $builder = formie()->form()->singleLineTextField('productName');
    foreach (array_keys($values) as $handle) {
        if ($handle !== 'productName') {
            $builder = $builder->singleLineTextField($handle);
        }
    }
    $form = $builder->create();
    $attributeMapping = ['title' => References::field($form->getFieldByHandle('productName')->reference)];
    foreach ($mapping as $attribute => $handle) {
        $attributeMapping[$attribute] = References::field($form->getFieldByHandle($handle)->reference);
    }
    $form->settings->integrations = [$connection->handle => [
        'enabled' => true, 'productTypeId' => $type->id, 'overwriteValues' => $overwriteValues,
        'defaultAuthorId' => [User::find()->admin(true)->one()->id],
        'attributeMapping' => $attributeMapping,
    ]];
    expect(Craft::$app->getElements()->saveElement($form), json_encode($form->getErrors()))->toBeTrue();
    Formie::$plugin->getForms()->invalidateFormCaches();

    // A reloaded form, its saved binding and the real runner, as a submission would use them.
    $form = Form::find()->id($form->id)->status(null)->one();
    $submission = formie()->submission($form)->with($values)->save();
    $binding = array_values(array_filter(Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form), fn($item) => $item->integration->handle === $connection->handle))[0];
    $result = Formie::$plugin->getIntegrationRunner()->runIntegration($binding->createRuntime(), $submission, 'product-delivery-' . $key, 'synchronous', ['triggerEvent' => 'submit']);

    $products = ProductElement::find()->typeId($type->id)->siteId($siteId)->status(null)->all();

    return [$result, $products];
}

it('saves the mapped variant with its SKU and price on a delivered product', function (): void {
    [$result, $products] = commerceProductDelivery(['variants.new1.sku' => 'sku', 'variants.new1.price' => 'amount'], [
        'productName' => 'Delivered product', 'sku' => 'DELIVERED-' . bin2hex(random_bytes(4)), 'amount' => '12.34',
    ]);

    expect($result->isSuccessful())->toBeTrue()->and($products)->toHaveCount(1);
    $product = $products[0];
    $row = (new Query())->from('{{%commerce_variants}}')->where(['primaryOwnerId' => $product->id])->all();
    $variant = ProductElement::find()->id($product->id)->status(null)->one()->getDefaultVariant(true);

    expect($product->title)->toBe('Delivered product')
        ->and($row)->toHaveCount(1)
        ->and($variant->sku)->toStartWith('DELIVERED-')
        ->and((float)$variant->basePrice)->toBe(12.34)
        ->and((float)$variant->price)->toBe(12.34);
});

it('saves other mapped variant attributes alongside the SKU and price', function (): void {
    [$result, $products] = commerceProductDelivery([
        'variants.new1.sku' => 'sku', 'variants.new1.price' => 'amount', 'variants.new1.weight' => 'weight',
        'variants.new1.width' => 'width', 'variants.new1.minQty' => 'minimum',
    ], ['productName' => 'Measured product', 'sku' => 'MEASURED-' . bin2hex(random_bytes(4)), 'amount' => '5.5', 'weight' => '2', 'width' => '3', 'minimum' => '4']);

    expect($result->isSuccessful())->toBeTrue()->and($products)->toHaveCount(1);
    $variant = ProductElement::find()->id($products[0]->id)->status(null)->one()->getDefaultVariant(true);

    expect((float)$variant->basePrice)->toBe(5.5)
        ->and((float)$variant->weight)->toBe(2.0)
        ->and((float)$variant->width)->toBe(3.0)
        ->and((int)$variant->minQty)->toBe(4);
});

it('saves the mapped variant when Overwrite Values is on', function (): void {
    [$result, $products] = commerceProductDelivery(['variants.new1.sku' => 'sku', 'variants.new1.price' => 'amount'], [
        'productName' => 'Overwriting product', 'sku' => 'OVERWRITE-' . bin2hex(random_bytes(4)), 'amount' => '7.25',
    ], true);

    expect($result->isSuccessful())->toBeTrue()->and($products)->toHaveCount(1);
    $variant = ProductElement::find()->id($products[0]->id)->status(null)->one()->getDefaultVariant(true);

    expect($variant->sku)->toStartWith('OVERWRITE-')
        ->and((float)$variant->basePrice)->toBe(7.25);
});
