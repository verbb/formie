<?php
namespace verbb\formie\controllers;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class CsrfController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `formie/csrf/*` actions are deprecated. Refer to the docs for the updated code - https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms.');
        
        return parent::beforeAction($action);
    }

    /**
     * Returns the CSRF token and param.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->asJson([
            'param' => Craft::$app->getRequest()->csrfParam,
            'token' => Craft::$app->getRequest()->getCsrfToken(),
        ]);
    }

    /**
     * Returns a CSRF input field.
     *
     * @return Response
     */
    public function actionInput(): Response
    {
        $request = Craft::$app->getRequest();

        $input = '<input type="hidden" name="' . $request->csrfParam . '" value="' . $request->getCsrfToken() . '">';

        return $this->asRaw($input);
    }

    /**
     * Returns the CSRF param.
     *
     * @return Response
     */
    public function actionParam(): Response
    {
        return $this->asRaw(Craft::$app->getRequest()->csrfParam);
    }

    /**
     * Returns a CSRF token.
     *
     * @return Response
     */
    public function actionToken(): Response
    {
        return $this->asRaw(Craft::$app->getRequest()->getCsrfToken());
    }
}
