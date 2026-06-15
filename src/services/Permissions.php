<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;
use craft\enums\CmsEdition;

class Permissions extends Component
{
    // Constants
    // =========================================================================

    public const GROUP_UNGROUPED = 'ungrouped';

    public const PERM_ACCESS_FORMS = 'formie-accessForms';
    public const PERM_ACCESS_INTEGRATIONS = 'formie-accessIntegrations';
    public const PERM_ACCESS_SETTINGS = 'formie-accessSettings';
    public const PERM_ACCESS_SUBMISSIONS = 'formie-accessSubmissions';
    public const PERM_CREATE_FORMS = 'formie-createForms';
    public const PERM_DELETE_FORMS = 'formie-deleteForms';
    public const PERM_IMPORT_FORMS = 'formie-importForms';
    public const PERM_EXPORT_FORMS = 'formie-exportForms';
    public const PERM_MANAGE_FORMS = 'formie-manageForms';
    public const PERM_VIEW_FORMS = 'formie-viewForms';

    public const PERM_VIEW_SUBMISSIONS = 'formie-viewSubmissions';
    public const PERM_CREATE_SUBMISSIONS = 'formie-createSubmissions';
    public const PERM_SAVE_SUBMISSIONS = 'formie-saveSubmissions';
    public const PERM_DELETE_SUBMISSIONS = 'formie-deleteSubmissions';

    public const PERM_VIEW_SENT_NOTIFICATIONS = 'formie-viewSentNotifications';
    public const PERM_RESEND_SENT_NOTIFICATIONS = 'formie-resendSentNotifications';
    public const PERM_DELETE_SENT_NOTIFICATIONS = 'formie-deleteSentNotifications';


    // Public Methods
    // =========================================================================

    public function groupScope(string $handle): string
    {
        return "group:{$handle}";
    }

    public function formScope(Form $form): string
    {
        return (string)$form->uid;
    }

    public function scopedPermission(string $base, string $scope): string
    {
        return "{$base}:{$scope}";
    }

    public function getFormGroupHandle(Form $form): string
    {
        if (!$form->groupId) {
            return self::GROUP_UNGROUPED;
        }

        $group = Formie::$plugin->getFormGroups()->getGroupById((int)$form->groupId);

        return $group?->handle ?? self::GROUP_UNGROUPED;
    }

    public function formUsesDedicatedPermissions(Form $form): bool
    {
        return (bool)$form->getSettings()?->usePerFormPermissions;
    }

    public function canAccessForms(?User $user): bool
    {
        return $this->_can($user, self::PERM_ACCESS_FORMS);
    }

    public function canAccessIntegrations(?User $user): bool
    {
        return $this->_can($user, self::PERM_ACCESS_INTEGRATIONS);
    }

    public function canAccessSettings(?User $user): bool
    {
        return $this->_can($user, self::PERM_ACCESS_SETTINGS);
    }

    public function settingsPagePermissionKey(string $page): string
    {
        $normalized = str_replace(['/', '-'], '', ucwords($page, '-/'));

        return 'formie-settings' . $normalized;
    }

    public function canAccessSettingsPage(?User $user, string $page): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_ACCESS_SETTINGS)) {
            return true;
        }

        $page = $this->normalizeSettingsPage($page);

        if ($page === 'spam-protection') {
            return $user->can($this->settingsPagePermissionKey('spam-protection'))
                || $user->can($this->settingsPagePermissionKey('spam'))
                || $user->can($this->settingsPagePermissionKey('captchas'));
        }

        return $user->can($this->settingsPagePermissionKey($page));
    }

    public function normalizeSettingsPage(string $page): string
    {
        if (in_array($page, ['spam', 'captchas'], true)) {
            return 'spam-protection';
        }

        return $page;
    }

    public function canAccessAnySettings(?User $user): bool
    {
        if ($this->canAccessSettings($user)) {
            return true;
        }

        foreach (array_keys($this->getSettingsPageDefinitions()) as $page) {
            if ($this->canAccessSettingsPage($user, $page)) {
                return true;
            }
        }

        return false;
    }

    public function resolveSettingsPageFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return null;
        }

        if (!preg_match('#/formie/settings(?:/([^/]+)(?:/([^/]+))?)?#', $path, $matches)) {
            return null;
        }

        $section = $matches[1] ?? 'general';
        $subsection = $matches[2] ?? null;

        if ($section === 'migrate' && $subsection) {
            return "migrate/$subsection";
        }

        return $section;
    }

    public function getSettingsPageDefinitions(): array
    {
        return [
            'general' => Craft::t('formie', 'General settings'),
            'import-export' => Craft::t('formie', 'Import/Export'),
            'forms' => Craft::t('formie', 'Forms settings'),
            'form-groups' => Craft::t('formie', 'Form groups'),
            'synced-fields' => Craft::t('formie', 'Synced fields'),
            'defaults' => Craft::t('formie', 'Defaults'),
            'fields' => Craft::t('formie', 'Fields'),
            'notifications' => Craft::t('formie', 'Email notifications settings'),
            'sent-notifications' => Craft::t('formie', 'Sent notifications settings'),
            'statuses' => Craft::t('formie', 'Statuses'),
            'submissions' => Craft::t('formie', 'Submissions settings'),
            'integrations-settings' => Craft::t('formie', 'Integrations settings'),
            'spam-protection' => Craft::t('formie', 'Spam protection'),
            'form-templates' => Craft::t('formie', 'Form templates'),
            'email-templates' => Craft::t('formie', 'Email templates'),
            'pdf-templates' => Craft::t('formie', 'PDF templates'),
            'support' => Craft::t('formie', 'Get support'),
            'migrate/freeform4' => Craft::t('formie', 'Migrate Freeform 4'),
            'migrate/freeform5' => Craft::t('formie', 'Migrate Freeform 5'),
            'migrate/sprout-forms' => Craft::t('formie', 'Migrate Sprout Forms'),
        ];
    }

    public function getSettingsPermissionDefinitions(): array
    {
        $definitions = [];

        foreach ($this->getSettingsPageDefinitions() as $page => $label) {
            $definitions[$this->settingsPagePermissionKey($page)] = ['label' => $label];
        }

        return $definitions;
    }

    public function canCreateForms(?User $user): bool
    {
        return $this->_can($user, self::PERM_CREATE_FORMS);
    }

    public function canImportForms(?User $user): bool
    {
        return $this->_can($user, self::PERM_IMPORT_FORMS);
    }

    public function canExportForms(?User $user): bool
    {
        return $this->_can($user, self::PERM_EXPORT_FORMS);
    }

    public function canManageForm(?User $user, Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_MANAGE_FORMS)) {
            return true;
        }

        if ($user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canViewForm(?User $user, Form $form): bool
    {
        if ($this->canManageForm($user, $form)) {
            return true;
        }

        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_VIEW_FORMS)) {
            return true;
        }

        if ($user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canDeleteForm(?User $user, Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if (!$user->can(self::PERM_DELETE_FORMS)) {
            return false;
        }

        return $this->canManageForm($user, $form);
    }

    public function canDuplicateForm(?User $user, Form $form): bool
    {
        return $this->canCreateForms($user) && $this->canManageForm($user, $form);
    }

    public function canManageFormInGroup(?User $user, ?FormGroup $group): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_MANAGE_FORMS)) {
            return true;
        }

        $handle = $group?->handle ?? self::GROUP_UNGROUPED;

        return $user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $this->groupScope($handle)));
    }

    public function canShowFormBuilderTab(?User $user, Form $form, string $basePermission): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can($basePermission)) {
            return true;
        }

        if ($user->can($this->scopedPermission($basePermission, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission($basePermission, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canViewSubmissions(?User $user, ?Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_VIEW_SUBMISSIONS)) {
            return true;
        }

        if (!$form) {
            return true;
        }

        if ($user->can($this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canSaveSubmissions(?User $user, ?Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_SAVE_SUBMISSIONS)) {
            return true;
        }

        if (!$form) {
            return false;
        }

        if ($user->can($this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canDeleteSubmissions(?User $user, ?Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_DELETE_SUBMISSIONS)) {
            return true;
        }

        if (!$form) {
            return false;
        }

        if ($user->can($this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canViewSentNotifications(?User $user, ?Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_VIEW_SENT_NOTIFICATIONS)) {
            return true;
        }

        if (!$form) {
            return true;
        }

        if ($user->can($this->scopedPermission(self::PERM_VIEW_SENT_NOTIFICATIONS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_VIEW_SENT_NOTIFICATIONS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function canDeleteSentNotifications(?User $user, ?Form $form): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->can(self::PERM_DELETE_SENT_NOTIFICATIONS)) {
            return true;
        }

        if (!$form) {
            return false;
        }

        if ($user->can($this->scopedPermission(self::PERM_DELETE_SENT_NOTIFICATIONS, $this->formScope($form)))) {
            return true;
        }

        return $user->can($this->scopedPermission(self::PERM_DELETE_SENT_NOTIFICATIONS, $this->groupScope($this->getFormGroupHandle($form))));
    }

    public function hasGlobalFormAccess(?User $user): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->can(self::PERM_MANAGE_FORMS) || $user->can(self::PERM_VIEW_FORMS);
    }

    public function hasGlobalSubmissionAccess(?User $user): bool
    {
        if ($this->_isElevated($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->can(self::PERM_VIEW_SUBMISSIONS);
    }

    /**
     * Returns null when the user can access every form, otherwise a list of allowed form IDs.
     */
    public function getAccessibleFormIds(?User $user): ?array
    {
        if ($this->hasGlobalFormAccess($user)) {
            return null;
        }

        if (!$user) {
            return [];
        }

        $formRows = (new Query())
            ->select(['f.id', 'f.uid', 'f.groupId', 'g.handle AS groupHandle'])
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => CraftTable::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->leftJoin(['g' => Table::FORMIE_FORM_GROUPS], '[[g.id]] = [[f.groupId]]')
            ->where(['e.dateDeleted' => null])
            ->all();

        $accessibleIds = [];

        foreach ($formRows as $row) {
            $formId = (int)($row['id'] ?? 0);

            if (!$formId) {
                continue;
            }

            $uid = (string)($row['uid'] ?? '');
            $groupHandle = (string)($row['groupHandle'] ?? self::GROUP_UNGROUPED);

            if ($user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $uid))
                || $user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $uid))
                || $user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $this->groupScope($groupHandle)))
                || $user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $this->groupScope($groupHandle)))
            ) {
                $accessibleIds[] = $formId;
            }
        }

        return array_values(array_unique($accessibleIds));
    }

    public function userCanAccessFormRecord(?User $user, array $formRow): bool
    {
        if ($this->hasGlobalFormAccess($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $uid = (string)($formRow['uid'] ?? '');
        $groupHandle = (string)($formRow['groupHandle'] ?? self::GROUP_UNGROUPED);

        return $user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $uid))
            || $user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $uid))
            || $user->can($this->scopedPermission(self::PERM_MANAGE_FORMS, $this->groupScope($groupHandle)))
            || $user->can($this->scopedPermission(self::PERM_VIEW_FORMS, $this->groupScope($groupHandle)));
    }

    public function userCanViewSubmissionsForFormRecord(?User $user, array $formRow): bool
    {
        if ($this->hasGlobalSubmissionAccess($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $uid = (string)($formRow['uid'] ?? '');
        $groupHandle = (string)($formRow['groupHandle'] ?? self::GROUP_UNGROUPED);

        return $user->can($this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $uid))
            || $user->can($this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $this->groupScope($groupHandle)));
    }

    public function grantCreatorPermissions(User $user, Form $form): void
    {
        if (Craft::$app->edition === CmsEdition::Solo) {
            return;
        }

        if ($this->canManageForm($user, $form)) {
            return;
        }

        if (!$this->canCreateForms($user)) {
            return;
        }

        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($user->id);
        $scope = $this->formUsesDedicatedPermissions($form)
            ? $this->formScope($form)
            : $this->groupScope($this->getFormGroupHandle($form));

        $permissions[] = $this->scopedPermission(self::PERM_MANAGE_FORMS, $scope);
        $permissions = $this->_appendNestedManagePermissions($user, $permissions, $scope);

        if ($user->can(self::PERM_VIEW_SUBMISSIONS) || $user->can($this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $scope))) {
            $permissions[] = $this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $scope);
            $permissions[] = $this->scopedPermission(self::PERM_CREATE_SUBMISSIONS, $scope);
            $permissions[] = $this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $scope);
            $permissions[] = $this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $scope);
        }

        $permissions = array_values(array_unique($permissions));

        if (Craft::$app->edition === CmsEdition::Pro) {
            Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);
        }
    }

    public function getFormPermissionDefinitions(): array
    {
        $definitions = [
            self::PERM_CREATE_FORMS => [
                'label' => Craft::t('formie', 'Create forms'),
                'nested' => $this->_getCreateFormTabPermissions(),
            ],
            self::PERM_DELETE_FORMS => ['label' => Craft::t('formie', 'Delete forms')],
            self::PERM_IMPORT_FORMS => ['label' => Craft::t('formie', 'Import forms')],
            self::PERM_EXPORT_FORMS => ['label' => Craft::t('formie', 'Export forms')],
            self::PERM_VIEW_FORMS => ['label' => Craft::t('formie', 'View all forms')],
            self::PERM_MANAGE_FORMS => [
                'label' => Craft::t('formie', 'Manage all forms'),
                'nested' => $this->_getManageFormTabPermissions(),
            ],
        ];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $scope = $this->groupScope($group->handle);

            $definitions[$this->scopedPermission(self::PERM_VIEW_FORMS, $scope)] = [
                'label' => Craft::t('formie', 'View “{name}” forms', ['name' => $group->name]),
            ];

            $definitions[$this->scopedPermission(self::PERM_MANAGE_FORMS, $scope)] = [
                'label' => Craft::t('formie', 'Manage “{name}” forms', ['name' => $group->name]),
                'nested' => $this->_getManageFormTabPermissions($scope),
            ];
        }

        $ungroupedScope = $this->groupScope(self::GROUP_UNGROUPED);

        $definitions[$this->scopedPermission(self::PERM_VIEW_FORMS, $ungroupedScope)] = [
            'label' => Craft::t('formie', 'View ungrouped forms'),
        ];

        $definitions[$this->scopedPermission(self::PERM_MANAGE_FORMS, $ungroupedScope)] = [
            'label' => Craft::t('formie', 'Manage ungrouped forms'),
            'nested' => $this->_getManageFormTabPermissions($ungroupedScope),
        ];

        if (Craft::$app->edition === CmsEdition::Pro) {
            foreach (Formie::$plugin->getForms()->getAllForms() as $form) {
                if (!$this->formUsesDedicatedPermissions($form)) {
                    continue;
                }

                $scope = $this->formScope($form);

                $definitions[$this->scopedPermission(self::PERM_MANAGE_FORMS, $scope)] = [
                    'label' => Craft::t('formie', 'Manage “{name}” form', ['name' => $form->title]),
                    'nested' => $this->_getManageFormTabPermissions($scope),
                ];
            }
        }

        return $definitions;
    }

    public function getSubmissionPermissionDefinitions(): array
    {
        $definitions = [
            self::PERM_VIEW_SUBMISSIONS => [
                'label' => Craft::t('formie', 'View all submissions'),
                'nested' => [
                    self::PERM_CREATE_SUBMISSIONS => ['label' => Craft::t('formie', 'Create submissions')],
                    self::PERM_SAVE_SUBMISSIONS => ['label' => Craft::t('formie', 'Save submissions')],
                    self::PERM_DELETE_SUBMISSIONS => ['label' => Craft::t('formie', 'Delete submissions')],
                ],
            ],
        ];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $scope = $this->groupScope($group->handle);

            $definitions[$this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $scope)] = [
                'label' => Craft::t('formie', 'View “{name}” submissions', ['name' => $group->name]),
                'nested' => [
                    $this->scopedPermission(self::PERM_CREATE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Create submissions')],
                    $this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Save submissions')],
                    $this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Delete submissions')],
                ],
            ];
        }

        $ungroupedScope = $this->groupScope(self::GROUP_UNGROUPED);

        $definitions[$this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $ungroupedScope)] = [
            'label' => Craft::t('formie', 'View ungrouped submissions'),
            'nested' => [
                $this->scopedPermission(self::PERM_CREATE_SUBMISSIONS, $ungroupedScope) => ['label' => Craft::t('formie', 'Create submissions')],
                $this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $ungroupedScope) => ['label' => Craft::t('formie', 'Save submissions')],
                $this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $ungroupedScope) => ['label' => Craft::t('formie', 'Delete submissions')],
            ],
        ];

        if (Craft::$app->edition === CmsEdition::Pro) {
            foreach (Formie::$plugin->getForms()->getAllForms() as $form) {
                if (!$this->formUsesDedicatedPermissions($form)) {
                    continue;
                }

                $scope = $this->formScope($form);

                $definitions[$this->scopedPermission(self::PERM_VIEW_SUBMISSIONS, $scope)] = [
                    'label' => Craft::t('formie', 'View “{name}” submissions', ['name' => $form->title]),
                    'nested' => [
                        $this->scopedPermission(self::PERM_CREATE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Create submissions')],
                        $this->scopedPermission(self::PERM_SAVE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Save submissions')],
                        $this->scopedPermission(self::PERM_DELETE_SUBMISSIONS, $scope) => ['label' => Craft::t('formie', 'Delete submissions')],
                    ],
                ];
            }
        }

        return $definitions;
    }

    public function getSentNotificationPermissionDefinitions(): array
    {
        $definitions = [
            self::PERM_VIEW_SENT_NOTIFICATIONS => [
                'label' => Craft::t('formie', 'View all sent notifications'),
                'nested' => [
                    self::PERM_RESEND_SENT_NOTIFICATIONS => ['label' => Craft::t('formie', 'Resend sent notifications')],
                    self::PERM_DELETE_SENT_NOTIFICATIONS => ['label' => Craft::t('formie', 'Delete sent notifications')],
                ],
            ],
        ];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $scope = $this->groupScope($group->handle);

            $definitions[$this->scopedPermission(self::PERM_VIEW_SENT_NOTIFICATIONS, $scope)] = [
                'label' => Craft::t('formie', 'View “{name}” sent notifications', ['name' => $group->name]),
                'nested' => [
                    $this->scopedPermission(self::PERM_RESEND_SENT_NOTIFICATIONS, $scope) => ['label' => Craft::t('formie', 'Resend sent notifications')],
                    $this->scopedPermission(self::PERM_DELETE_SENT_NOTIFICATIONS, $scope) => ['label' => Craft::t('formie', 'Delete sent notifications')],
                ],
            ];
        }

        $ungroupedScope = $this->groupScope(self::GROUP_UNGROUPED);

        $definitions[$this->scopedPermission(self::PERM_VIEW_SENT_NOTIFICATIONS, $ungroupedScope)] = [
            'label' => Craft::t('formie', 'View ungrouped sent notifications'),
            'nested' => [
                $this->scopedPermission(self::PERM_RESEND_SENT_NOTIFICATIONS, $ungroupedScope) => ['label' => Craft::t('formie', 'Resend sent notifications')],
                $this->scopedPermission(self::PERM_DELETE_SENT_NOTIFICATIONS, $ungroupedScope) => ['label' => Craft::t('formie', 'Delete sent notifications')],
            ],
        ];

        if (Craft::$app->edition === CmsEdition::Pro) {
            foreach (Formie::$plugin->getForms()->getAllForms() as $form) {
                if (!$this->formUsesDedicatedPermissions($form)) {
                    continue;
                }

                $scope = $this->formScope($form);

                $definitions[$this->scopedPermission(self::PERM_VIEW_SENT_NOTIFICATIONS, $scope)] = [
                    'label' => Craft::t('formie', 'View “{name}” sent notifications', ['name' => $form->title]),
                    'nested' => [
                        $this->scopedPermission(self::PERM_RESEND_SENT_NOTIFICATIONS, $scope) => ['label' => Craft::t('formie', 'Resend sent notifications')],
                        $this->scopedPermission(self::PERM_DELETE_SENT_NOTIFICATIONS, $scope) => ['label' => Craft::t('formie', 'Delete sent notifications')],
                    ],
                ];
            }
        }

        return $definitions;
    }


    // Private Methods
    // =========================================================================

    private function _isElevated(?User $user): bool
    {
        return (bool)($user?->admin);
    }

    private function _can(?User $user, string $permission): bool
    {
        return $this->_isElevated($user) || ($user?->can($permission) ?? false);
    }

    private function _getCreateFormTabPermissions(?string $scope = null): array
    {
        $suffix = $scope ? ":{$scope}" : '';

        return [
            "formie-createFormAppearance{$suffix}" => ['label' => Craft::t('formie', 'Show form appearance tab')],
            "formie-createFormBehavior{$suffix}" => ['label' => Craft::t('formie', 'Show form behaviour tab')],
            "formie-createNotifications{$suffix}" => ['label' => Craft::t('formie', 'Show form email notifications tab')],
            "formie-createFormIntegrations{$suffix}" => ['label' => Craft::t('formie', 'Show form integrations tab')],
            "formie-createFormUsage{$suffix}" => ['label' => Craft::t('formie', 'Show form usage tab')],
            "formie-createFormSettings{$suffix}" => ['label' => Craft::t('formie', 'Show form settings tab')],
        ];
    }

    private function _getManageFormTabPermissions(?string $scope = null): array
    {
        $suffix = $scope ? ":{$scope}" : '';

        return [
            "formie-showFormAppearance{$suffix}" => ['label' => Craft::t('formie', 'Show form appearance tab')],
            "formie-showFormBehavior{$suffix}" => ['label' => Craft::t('formie', 'Show form behaviour tab')],
            "formie-showNotifications{$suffix}" => [
                'label' => Craft::t('formie', 'Show form email notifications tab'),
                'nested' => [
                    "formie-showNotificationsAdvanced{$suffix}" => ['label' => Craft::t('formie', 'Show email notification advanced tab')],
                    "formie-showNotificationsTemplates{$suffix}" => ['label' => Craft::t('formie', 'Show email notification templates tab')],
                ],
            ],
            "formie-showFormIntegrations{$suffix}" => ['label' => Craft::t('formie', 'Show form integrations tab')],
            "formie-showFormUsage{$suffix}" => ['label' => Craft::t('formie', 'Show form usage tab')],
            "formie-showFormSettings{$suffix}" => ['label' => Craft::t('formie', 'Show form settings tab')],
        ];
    }

    private function _appendNestedManagePermissions(User $user, array $permissions, string $scope): array
    {
        $pairs = [
            ['formie-showFormAppearance', 'formie-createFormAppearance'],
            ['formie-showFormBehavior', 'formie-createFormBehavior'],
            ['formie-showNotifications', 'formie-createNotifications'],
            ['formie-showNotificationsAdvanced', 'formie-createNotifications'],
            ['formie-showNotificationsTemplates', 'formie-createNotifications'],
            ['formie-showFormIntegrations', 'formie-createFormIntegrations'],
            ['formie-showFormUsage', 'formie-createFormUsage'],
            ['formie-showFormSettings', 'formie-createFormSettings'],
        ];

        foreach ($pairs as [$managePermission, $createPermission]) {
            if ($user->can($managePermission) || $user->can($createPermission)) {
                $permissions[] = $this->scopedPermission($managePermission, $scope);
            }
        }

        return $permissions;
    }
}
