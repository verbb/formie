<?php
namespace verbb\formie\controllers;

use verbb\formie\helpers\Table;

use Craft;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\services\ElementSources;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class UserSubmissionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionReassignModal(): Response
    {
        $this->requireAcceptsJson();
        $userIds = $this->_userIds();

        return $this->asCpModal()
            ->action('formie/user-submissions/reassign')
            ->contentHtml(
                fn() =>
                Cp::elementSelectFieldHtml([
                    'label' => Craft::t('formie', 'Choose a new user'),
                    'name' => 'newUserId',
                    'elementType' => User::class,
                    'context' => ElementSources::CONTEXT_RESTRICTED_MODAL,
                    'criteria' => [
                        'id' => array_map(fn(int $id) => "not $id", $userIds),
                        'status' => null,
                    ],
                    'single' => true,
                ]) .
                array_reduce($userIds, fn(string $html, int $id) => $html . Html::hiddenInput('elementIds[]', (string)$id), '')
            )
            ->submitButtonLabel(Craft::t('formie', 'Reassign'));
    }

    public function actionReassign(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $userIds = $this->_userIds();
        $newUserId = (int)$this->request->getRequiredBodyParam('newUserId');

        if (in_array($newUserId, $userIds, true) || !Craft::$app->getUsers()->getUserById($newUserId)) {
            throw new BadRequestHttpException('Invalid replacement user.');
        }

        $count = Db::update(Table::FORMIE_SUBMISSIONS, ['userId' => $newUserId], ['userId' => $userIds]);

        return $this->asSuccess(Craft::t('formie', '{numSubmissions, plural, =1{Submission} other{Submissions}} reassigned.', [
            'numSubmissions' => $count,
        ]));
    }

    public function actionDetach(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $userIds = $this->_userIds();
        $count = Db::update(Table::FORMIE_SUBMISSIONS, ['userId' => null], ['userId' => $userIds]);

        return $this->asSuccess(Craft::t('formie', '{numSubmissions, plural, =1{Submission} other{Submissions}} updated.', [
            'numSubmissions' => $count,
        ]));
    }


    // Private Methods
    // =========================================================================

    private function _userIds(): array
    {
        $userIds = array_values(array_unique(array_map('intval', (array)$this->request->getRequiredParam('elementIds'))));
        $users = User::find()->id($userIds)->status(null)->all();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$userIds || count($users) !== count($userIds) || !$currentUser) {
            throw new BadRequestHttpException('Invalid users.');
        }

        foreach ($users as $user) {
            if (!$user->canDelete($currentUser)) {
                throw new ForbiddenHttpException('User is not permitted to delete these users.');
            }
        }

        return $userIds;
    }
}
