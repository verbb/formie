<?php

use craft\elements\conditions\entries\TypeConditionRule;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\Entry;
use craft\services\ElementSources;
use craft\services\Entries as EntriesService;
use verbb\formie\fields\Entries;

class SourceCacheEntriesService extends EntriesService
{
    public int $entryTypeLoads = 0;

    public function getEntryTypesBySectionId(int $sectionId): array
    {
        $this->entryTypeLoads++;

        return parent::getEntryTypesBySectionId($sectionId);
    }
}

class SourceCacheEntryCondition extends EntryCondition
{
    public static int $builds = 0;

    public function init(): void
    {
        self::$builds++;
        parent::init();
    }
}

class SourceCacheEntriesField extends Entries
{
    public array $testSources = [];

    protected function availableSources(): array
    {
        return $this->testSources;
    }
}

function sourceCacheField(): SourceCacheEntriesField
{
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();
    $field = new SourceCacheEntriesField(['sources' => ['custom:test']]);
    $field->testSources = [[
        'key' => 'custom:test',
        'type' => ElementSources::TYPE_CUSTOM,
        'label' => 'Test source',
        'condition' => [
            'class' => SourceCacheEntryCondition::class,
            'conditionRules' => [[
                'class' => TypeConditionRule::class,
                'values' => [$entry->getType()->uid],
            ]],
        ],
    ]];

    return $field;
}

it('reuses section entry types across option builders and field instances', function (): void {
    $original = Craft::$app->getEntries();
    $entries = new SourceCacheEntriesService();
    Craft::$app->set('entries', $entries);

    try {
        $field = new SourceCacheEntriesField();
        foreach ($entries->getAllSections() as $section) {
            $field->testSources[] = [
                'key' => 'section:' . $section->uid,
                'label' => $section->name,
                'criteria' => ['sectionId' => $section->id],
            ];
        }

        expect($field->testSources)->not->toBeEmpty();
        $labels = $field->getLabelSourceOptions();
        $loads = $entries->entryTypeLoads;
        $sources = $field->getSourceOptions();
        $second = new SourceCacheEntriesField();
        $second->testSources = $field->testSources;

        for ($i = 0; $i < 3; $i++) {
            expect($field->getLabelSourceOptions())->toBe($labels)
                ->and($second->getSourceOptions())->toBe($sources);
            $field->getElementsQuery();
        }

        expect($entries->entryTypeLoads)->toBe($loads);
    } finally {
        Craft::$app->set('entries', $original);
    }
});

it('builds a custom source condition once while returning independent queries', function (): void {
    $field = sourceCacheField();
    SourceCacheEntryCondition::$builds = 0;
    $events = 0;
    $field->on(Entries::EVENT_MODIFY_ELEMENT_QUERY, function () use (&$events): void {
        $events++;
    });
    $first = $field->getElementsQuery();
    $expected = $first->ids();
    expect($expected)->not->toBeEmpty();
    $first->id([-1]);

    for ($i = 0; $i < 3; $i++) {
        $query = $field->getElementsQuery();
        expect($query)->not->toBe($first)
            ->and($query->ids())->toBe($expected);
    }

    expect(SourceCacheEntryCondition::$builds)->toBe(1)
        ->and($events)->toBe(4);
});

it('rebuilds changed source conditions and keeps field caches separate', function (): void {
    $field = sourceCacheField();
    SourceCacheEntryCondition::$builds = 0;
    $expected = $field->getElementsQuery()->ids();
    $other = sourceCacheField();
    expect($other->getElementsQuery()->ids())->toBe($expected);
    $field->testSources[0]['condition']['conditionRules'][0]['operator'] = 'ni';
    $changed = $field->getElementsQuery()->ids();
    expect(array_intersect($expected, $changed))->toBe([])
        ->and($field->getElementsQuery()->ids())->toBe($changed)
        ->and($other->getElementsQuery()->ids())->toBe($expected)
        ->and(SourceCacheEntryCondition::$builds)->toBe(3);
});

it('applies current query settings and site after warming a custom source', function (): void {
    $field = sourceCacheField();
    $field->getElementsQuery();
    $field->orderBy = 'title DESC';
    $field->optionLimit = '1';
    $sites = Craft::$app->getSites();
    $originalSite = $sites->getCurrentSite();

    try {
        foreach ($sites->getAllSites() as $site) {
            $sites->setCurrentSite($site);
            $query = $field->getElementsQuery();
            expect($query->siteId)->toBe($site->id)
                ->and((int)$query->limit)->toBe(1)
                ->and($query->orderBy)->toBe(['title' => SORT_DESC]);
        }

        $field->sources = [];
        expect($field->getElementsQuery()->typeId)->toBeNull();
    } finally {
        $sites->setCurrentSite($originalSite);
    }
});
