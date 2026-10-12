<?php
namespace verbb\formie\elements\deletionblockers;

use verbb\formie\elements\Submission;

use Craft;
use craft\elements\deletionblockers\BaseDeletionBlocker;
use craft\helpers\Cp;
use craft\helpers\Html;

use Illuminate\Support\Collection;

class UserSubmissionsBlocker extends BaseDeletionBlocker
{
    // Properties
    // =========================================================================

    private Collection $_submissionIds;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->_submissionIds = Submission::find()
            ->userId($this->elements->ids()->all())
            ->siteId('*')
            ->unique()
            ->status(null)
            ->collectIds();

        parent::init();
    }

    public function isActive(): bool
    {
        return $this->_submissionIds->isNotEmpty();
    }

    public function getSummary(): string
    {
        return Craft::t('formie', '{numSubmissions, number} form {numSubmissions, plural, =1{submission is} other{submissions are}} assigned to the {numUsers, plural, =1{user} other{users}}.', [
            'numSubmissions' => $this->_submissionIds->count(),
            'numUsers' => $this->elements->count(),
        ]);
    }

    public function getDetails(): ?string
    {
        return Cp::elementIndexHtml(Submission::class, [
            'context' => 'pane',
            'sources' => false,
            'jsSettings' => [
                'criteria' => [
                    'userId' => $this->elements->ids()->all(),
                    'status' => null,
                ],
            ],
        ]);
    }

    public function getActions(): array
    {
        $submissionCount = $this->_submissionIds->count();

        return [
            [
                'icon' => 'user-plus',
                'label' => Craft::t('formie', 'Reassign {numSubmissions, plural, =1{submission} other{submissions}}', [
                    'numSubmissions' => $submissionCount,
                ]),
                'callback' => Html::jsWithVars(fn($userIds) => <<<JS
new Craft.CpModal('formie/user-submissions/reassign-modal', {
  params: {
    elementIds: $userIds,
  },
  onSubmit: (ev) => {
    resolve(ev.response.data.message);
  },
  onCancel: () => {
    reject();
  },
});
JS, [
                    $this->elements->ids()->all(),
                ]),
            ],
            [
                'icon' => 'user-minus',
                'label' => Craft::t('formie', 'Remove {numUsers, plural, =1{user} other{users}} from {numSubmissions, plural, =1{submission} other{submissions}}', [
                    'numUsers' => $this->elements->count(),
                    'numSubmissions' => $submissionCount,
                ]),
                'action' => 'formie/user-submissions/detach',
            ],
            [
                'icon' => 'trash',
                'label' => Craft::t('formie', 'Delete {numSubmissions, plural, =1{submission} other{submissions}}', [
                    'numSubmissions' => $submissionCount,
                ]),
                'destructive' => true,
                'callback' => Html::jsWithVars(fn($elementType, $submissionIds, $message) => <<<JS
new Craft.ElementDeletionManager($elementType, $submissionIds, {
  onSuccess: () => {
    resolve($message);
  },
  onCancel: () => {
    reject();
  },
});
JS, [
                    Submission::class,
                    $this->_submissionIds->all(),
                    Craft::t('formie', '{numSubmissions, plural, =1{Submission} other{Submissions}} deleted.', [
                        'numSubmissions' => $submissionCount,
                    ]),
                ]),
            ],
        ];
    }
}
