<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormSitePolicy;

use Craft;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\models\Site;

use yii\base\Component;

class FormSitePropagation extends Component
{
    // Public Methods
    // =========================================================================

    public function isEnabled(): bool
    {
        return Craft::$app->getIsMultiSite();
    }

    public function getSitePolicy(?FormGroup $group): FormSitePolicy
    {
        if (!$group) {
            return new FormSitePolicy();
        }

        $settings = Formie::$plugin->getFormGroupPolicy()->getSettings($group);
        $sitePolicy = $settings->sitePolicy ?? [];

        return FormSitePolicy::fromArray(is_array($sitePolicy) ? $sitePolicy : []);
    }

    public function getEditableSiteIds(?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();

        if ($user) {
            return Craft::$app->getSites()->getEditableSiteIds($user);
        }

        return Craft::$app->getSites()->getAllSiteIds();
    }

    public function getEditableSites(?User $user = null): array
    {
        $editableIds = $this->getEditableSiteIds($user);

        return array_values(array_filter(
            Craft::$app->getSites()->getAllSites(),
            fn(Site $site) => in_array((int)$site->id, $editableIds, true),
        ));
    }

    public function getSiteOptionsForEditor(?User $user = null): array
    {
        return array_map(function(Site $site) {
            return [
                'value' => (int)$site->id,
                'label' => $site->name,
                'handle' => $site->handle,
                'language' => $site->language,
                'primary' => (bool)$site->primary,
            ];
        }, $this->getEditableSites($user));
    }

    public function resolveSiteIdsForForm(Form $form): array
    {
        if (!$this->isEnabled()) {
            return [(int)Craft::$app->getSites()->getPrimarySite()->id];
        }

        $group = $form->getGroup();
        $policy = $this->getSitePolicy($group);
        $candidateIds = $this->_resolveCandidateSiteIds($policy, $form);

        return array_values(array_unique(array_map('intval', $candidateIds)));
    }

    public function isFormAvailableForSite(Form $form, int $siteId): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return in_array($siteId, $this->resolveSiteIdsForForm($form), true);
    }

    public function resolveSiteIdsForGroup(?FormGroup $group): array
    {
        if (!$this->isEnabled()) {
            return $this->getEditableSiteIds();
        }

        $policy = $this->getSitePolicy($group);
        $editableIds = $this->getEditableSiteIds();
        $enabledIds = $policy->enabledSiteIds ?? $editableIds;
        $enabledIds = array_values(array_intersect(
            array_map('intval', $enabledIds),
            $editableIds,
        ));

        if ($enabledIds === []) {
            $enabledIds = $editableIds;
        }

        return $enabledIds;
    }

    public function isGroupAvailableForSite(?FormGroup $group, int $siteId): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return in_array((int)$siteId, $this->resolveSiteIdsForGroup($group), true);
    }

    public function resolveCreationSiteIdForForm(Form $form): ?int
    {
        if (!$this->isEnabled() || $form->id) {
            return null;
        }

        $supportedSiteIds = $this->resolveSiteIdsForForm($form);

        if ($supportedSiteIds === []) {
            return null;
        }

        $request = Craft::$app->getRequest();
        $requestedSite = Cp::requestedSite();
        $candidateIds = array_filter([
            $request->getParam('siteId'),
            $requestedSite?->id,
            $form->siteId,
            Craft::$app->getSites()->getCurrentSite()->id,
        ], fn(mixed $siteId) => $siteId !== null && $siteId !== '');

        foreach ($candidateIds as $candidateId) {
            $candidateId = (int)$candidateId;

            if (in_array($candidateId, $supportedSiteIds, true)) {
                return $candidateId;
            }
        }

        return (int)reset($supportedSiteIds);
    }

    public function syncFormSites(Form $form): void
    {
        if (!$this->isEnabled() || !$form->id) {
            return;
        }

        $enabledSiteIds = $this->resolveSiteIdsForForm($form);

        if ($enabledSiteIds === []) {
            return;
        }

        $canonicalTitle = (string)$form->title;

        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $enabled = in_array($siteId, $enabledSiteIds, true);

            $row = (new Query())
                ->select(['id', 'enabled'])
                ->from([CraftTable::ELEMENTS_SITES])
                ->where(['elementId' => $form->id, 'siteId' => $siteId])
                ->one();

            if ($row) {
                if ((bool)$row['enabled'] !== $enabled) {
                    Db::update(CraftTable::ELEMENTS_SITES, ['enabled' => $enabled], ['id' => $row['id']]);
                }

                continue;
            }

            if ($enabled) {
                $title = $this->_resolveElementSiteTitle((int)$form->id, (int)$siteId, $canonicalTitle);

                Db::insert(CraftTable::ELEMENTS_SITES, [
                    'elementId' => $form->id,
                    'siteId' => $siteId,
                    'title' => $title !== '' ? $title : null,
                    'enabled' => true,
                ]);
            }
        }

        $this->syncElementSiteTitles((int)$form->id, $canonicalTitle);
    }

    public function syncElementSiteTitles(int $formId, ?string $canonicalTitle = null): void
    {
        if (!$this->isEnabled() || !$formId) {
            return;
        }

        $primarySiteId = (int)Craft::$app->getSites()->getPrimarySite()->id;

        if ($canonicalTitle === null) {
            $canonicalTitle = (new Query())
                ->select(['title'])
                ->from([CraftTable::ELEMENTS_SITES])
                ->where(['elementId' => $formId, 'siteId' => $primarySiteId])
                ->scalar();
        }

        $canonicalTitle = (string)($canonicalTitle ?? '');

        if ($canonicalTitle === '') {
            $form = Form::find()->id($formId)->siteId($primarySiteId)->status(null)->one();

            if (!$form) {
                return;
            }

            $canonicalTitle = (string)($form->title ?: $form->handle);
        }

        $form = Form::find()->id($formId)->siteId($primarySiteId)->status(null)->one();

        if (!$form) {
            return;
        }

        foreach ($this->resolveSiteIdsForForm($form) as $siteId) {
            $siteId = (int)$siteId;
            $title = $this->_resolveElementSiteTitle($formId, $siteId, $canonicalTitle);

            if ($title === '') {
                continue;
            }

            $row = (new Query())
                ->select(['id', 'title'])
                ->from([CraftTable::ELEMENTS_SITES])
                ->where(['elementId' => $formId, 'siteId' => $siteId])
                ->one();

            if (!$row) {
                continue;
            }

            if ((string)($row['title'] ?? '') === $title) {
                continue;
            }

            Db::update(CraftTable::ELEMENTS_SITES, ['title' => $title], ['id' => $row['id']]);
        }
    }


    // Private Methods
    // =========================================================================

    private function _resolveCandidateSiteIds(FormSitePolicy $policy, Form $form): array
    {
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();
        $editableIds = $this->getEditableSiteIds();

        $enabledIds = $policy->enabledSiteIds ?? $editableIds;
        $enabledIds = array_values(array_intersect(
            array_map('intval', $enabledIds),
            $editableIds,
        ));

        if ($enabledIds === []) {
            $enabledIds = $editableIds;
        }

        $sitesById = [];
        foreach ($sitesService->getAllSites() as $site) {
            $sitesById[(int)$site->id] = $site;
        }

        $candidates = array_values(array_filter(
            $enabledIds,
            fn(int $siteId) => isset($sitesById[$siteId]),
        ));

        return match ($policy->propagation) {
            FormSitePolicy::PROPAGATION_CREATED_SITE_ONLY => $this->_filterCreatedSiteOnly($form, $candidates, $primarySite),
            FormSitePolicy::PROPAGATION_SAME_LANGUAGE => $this->_filterSameLanguage($candidates, $primarySite, $sitesById),
            FormSitePolicy::PROPAGATION_SAME_SITE_GROUP => $this->_filterSameSiteGroup($candidates, $primarySite, $sitesById),
            default => $candidates,
        };
    }

    private function _filterCreatedSiteOnly(Form $form, array $candidateIds, Site $primarySite): array
    {
        $createdSiteId = (int)($form->siteId ?: $primarySite->id);

        if (in_array($createdSiteId, $candidateIds, true)) {
            return [$createdSiteId];
        }

        return $candidateIds === [] ? [$createdSiteId] : [reset($candidateIds)];
    }

    private function _filterSameLanguage(array $candidateIds, Site $primarySite, array $sitesById): array
    {
        $language = $primarySite->language;

        return array_values(array_filter(
            $candidateIds,
            fn(int $siteId) => ($sitesById[$siteId]->language ?? null) === $language,
        ));
    }

    private function _filterSameSiteGroup(array $candidateIds, Site $primarySite, array $sitesById): array
    {
        $groupId = $primarySite->groupId;

        return array_values(array_filter(
            $candidateIds,
            fn(int $siteId) => ($sitesById[$siteId]->groupId ?? null) === $groupId,
        ));
    }

    private function _resolveElementSiteTitle(int $formId, int $siteId, string $canonicalTitle): string
    {
        $titles = Formie::$plugin->getFormSiteOverrides()->resolveFormTitlesForSite(
            [$formId => $canonicalTitle],
            $siteId,
        );

        return (string)($titles[$formId] ?? $canonicalTitle);
    }
}
