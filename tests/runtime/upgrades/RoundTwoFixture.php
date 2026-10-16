<?php
namespace FormieMigrationCoverage;

use Craft;
use RuntimeException;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

final class RoundTwoFixture
{
    private array $map = [];
    private array $checks = [];

    public function __construct(private string $root, private int $version) {}

    public function run(string $action): void
    {
        if ($action === 'seed') {
            if ($this->version === 4) throw new RuntimeException('Historical source required.');
            $this->seed();
            $this->write('round-two-map.json', $this->map);
            return;
        }
        $this->map = $this->read('round-two-map.json');
        if ($action === 'restore-settings') {
            if ($this->version===4) throw new RuntimeException('Historical source required.');
            $original=$this->read('coverage-map.json')['expected']['pluginSettings'];
            if (!Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin,array_merge(Formie::$plugin->getSettings()->toArray(),$original))) throw new RuntimeException('Cannot restore existing fixture settings.');
            if ($this->version===2) Craft::$app->getProjectConfig()->saveModifiedConfigData(true); else Craft::$app->getProjectConfig()->flush();
            echo 'Preserved existing corpus settings alongside new settings.'.PHP_EOL;
        } elseif ($action === 'permissions') {
            if ($this->version===4) throw new RuntimeException('Historical source required.');
            Craft::$app->getUserPermissions()->saveUserPermissions($this->map['userId'],$this->permissionNames($this->map['forms'][0]));
            echo 'Saved historical parent and scoped submission permissions.'.PHP_EOL;
        } elseif ($action === 'sites') {
            if ($this->version===4) throw new RuntimeException('Historical source required.');
            $site=Craft::$app->getSites()->getSiteByHandle('roundTwoFrench');
            if (!$site) {
                $group = new \craft\models\SiteGroup(['name' => 'Historical translations']);
                if (!Craft::$app->getSites()->saveGroup($group)) throw new RuntimeException(Json::encode($group->getErrors()));
                $site=new \craft\models\Site(['groupId'=>$group->id,'name'=>'Migration French','handle'=>'roundTwoFrench','language'=>'fr','hasUrls'=>false,'enabled'=>true]);
                if (!Craft::$app->getSites()->saveSite($site)) throw new RuntimeException(Json::encode($site->getErrors()));
            }
            $form=Form::find()->uid($this->map['forms'][0])->one();
            $sub=new Submission(['formId'=>$form->id,'siteId'=>$site->id,'title'=>'Secondary-site historical content']);
            $sub->setFieldValues(['shared'=>'Bonjour migration']);
            if (!Craft::$app->getElements()->saveElement($sub)) throw new RuntimeException(Json::encode($sub->getErrors()));
            $this->map['secondarySiteId']=$site->id; $this->map['secondarySubmissionUid']=$sub->uid;
            $this->write('round-two-map.json',$this->map);
            if ($this->version===2) Craft::$app->getProjectConfig()->saveModifiedConfigData(true); else Craft::$app->getProjectConfig()->flush();
            echo 'Seeded secondary-site content.'.PHP_EOL;
        } elseif ($action === 'capture') {
            if ($this->version === 4) throw new RuntimeException('Never capture expectations from target.');
            $this->map['expected'] = $this->snapshot();
            if (!$this->map['expected']['synced'] || $this->map['expected']['access'] !== [true, false]) throw new RuntimeException('Historical sync or permission fixture invalid: '.Json::encode($this->map['expected']));
            if ($this->map['expected']['forms'][0]['layout']!==[[['shared','payment']]] || $this->map['expected']['forms'][1]['layout']!==[[['shared']]]) throw new RuntimeException('Historical page/row layout did not persist.');
            if (($this->map['expected']['spam']['spamLimit']??null)!==137 || ($this->map['expected']['captcha']['siteKey']??null)!=='fixture-site-key' || ($this->map['expected']['defaults']['dateType']??null)!=='dropdowns') throw new RuntimeException('Non-default historical settings did not persist.');
            $this->write('round-two-map.json', $this->map);
            echo "Captured source-validated round-two expectations.\n";
        } elseif ($action === 'verify') {
            $expected = $this->map['expected'];
            // Formie 4's payment contract renamed successful payments, without replaying them.
            if ($expected['payment']['status'] === 'success') $expected['payment']['status'] = 'succeeded';
            $expected['subscription']['data']['requiresAction'] = false;
            $this->compare('roundTwo', $expected, $this->snapshot());
            $this->behaviour();
            $report = $this->read('.state/checks.json');
            $report['checks'] = array_merge($report['checks'], $this->checks);
            $report['pass'] = !in_array(false, array_column($report['checks'], 'pass'), true);
            $this->write('.state/checks.json', $report);
            foreach ($this->checks as $check) if (!$check['pass']) echo Json::encode($check).PHP_EOL;
            echo count($this->checks)." round-two comparisons.\n";
        } elseif ($action === 'snapshot') {
            $snapshot = $this->read('.state/snapshot-4.json');
            $snapshot['roundTwo'] = $this->snapshot();
            $this->write('.state/snapshot-4.json', $snapshot);
        } else throw new RuntimeException('Unknown action.');
    }

    private function permissionNames(string $uid): array
    {
        return ['accessCp','accessPlugin-formie', $this->version===2?'formie-viewSubmissions':'formie-accessSubmissions', ($this->version===2?'formie-manageSubmission:':'formie-viewSubmissions:').$uid];
    }

    private function fields($form): array
    {
        $out = [];
        foreach ($this->version === 2 ? $form->getCustomFields() : $form->getFields() as $field) $out[$field->handle] = $field;
        return $out;
    }

    private function layout(Form $form): array
    {
        $pages=[];
        foreach ($form->getPages() as $page) {
            $rows=[];
            foreach ($page->getRows() as $row) {
                $fields=is_array($row)?$row['fields']:$row->getFields();
                $rows[]=array_values(array_map(fn($field)=>$field->handle,$fields));
            }
            $pages[]=$rows;
        }
        return $pages;
    }

    private function saveForm(Form $form): void
    {
        $saved = $this->version === 2 ? Formie::$plugin->getForms()->saveForm($form) : Craft::$app->getElements()->saveElement($form);
        if (!$saved) throw new RuntimeException(Json::encode($form->getErrors()));
    }

    private function seed(): void
    {
        if (is_file($this->root.'/round-two-map.json')) throw new RuntimeException('Round-two source already seeded; edit explicitly rather than deleting history.');
        Craft::$app->setEdition($this->version===2 ? Craft::Pro : \craft\enums\CmsEdition::Pro);
        $this->map = ['sourceVersion'=>$this->version, 'forms'=>[], 'submissions'=>[]];
        $stripe = Formie::$plugin->getIntegrations()->getIntegrationByHandle('roundTwoStripe') ?? new \verbb\formie\integrations\payments\Stripe(['name'=>'Migration offline Stripe','handle'=>'roundTwoStripe','enabled'=>false]);
        if (!Formie::$plugin->getIntegrations()->saveIntegration($stripe)) throw new RuntimeException(Json::encode($stripe->getErrors()));
        $this->map['integrationId'] = $stripe->id;
        Craft::$app->getDb()->createCommand()->delete('{{%formie_payments}}',['integrationId'=>$stripe->id,'reference'=>'fixture-pay-00123'])->execute();
        Craft::$app->getDb()->createCommand()->delete('{{%formie_payments_subscriptions}}',['integrationId'=>$stripe->id,'reference'=>'fixture-sub-00123'])->execute();
        foreach (['roundTwoAllowed', 'roundTwoDenied'] as $index=>$handle) {
            if ($old=Form::find()->handle($handle)->status(null)->one()) Craft::$app->getElements()->deleteElement($old,true);
            $form = new Form(['title'=>$handle,'handle'=>$handle]);
            $definitions = [['SingleLineText','shared', ['required'=>$index===0, 'placeholder'=>'Shared historical placeholder']]];
            if ($index === 0) $definitions[] = ['Payment','payment',['paymentIntegration'=>'roundTwoStripe']];
            $fields = [];
            foreach ($definitions as [$type,$fieldHandle,$settings]) {
                $class = 'verbb\\formie\\fields\\'.($this->version===2?'formfields\\':'').$type;
                $fields[] = $this->version === 2
                    ? ['id'=>'new-'.$fieldHandle,'rowId'=>'new-row','type'=>$class,'label'=>ucfirst($fieldHandle),'handle'=>$fieldHandle,'settings'=>$settings]
                    : array_merge(['type'=>$class,'label'=>ucfirst($fieldHandle),'handle'=>$fieldHandle], $settings);
            }
            $pages = [['id'=>'new-page','label'=>'Relationship page','rows'=>[['fields'=>$fields]]]];
            if ($this->version !== 2) unset($pages[0]['id']);
            if ($this->version === 2) $form->setFormFieldLayout(Formie::$plugin->getForms()->buildFieldLayout($pages, Form::class));
            else $form->setFormLayout(new \verbb\formie\models\FieldLayout(['pages'=>$pages]));
            $this->saveForm($form);
            $this->map['forms'][] = $form->uid;
            $submission = new Submission(['formId'=>$form->id,'siteId'=>$form->siteId,'title'=>'Historical relationship '.$index]);
            $submission->setFieldValues(['shared'=>'owned-by-'.$handle]);
            if (!Craft::$app->getElements()->saveElement($submission)) throw new RuntimeException(Json::encode($submission->getErrors()));
            $this->map['submissions'][] = $submission->uid;
        }
        $forms = array_map(fn($uid)=>Form::find()->uid($uid)->status(null)->one(), $this->map['forms']);
        $first = $this->fields($forms[0]); $second = $this->fields($forms[1]);
        if ($this->version === 2) {
            $sync = Formie::$plugin->getSyncs()->createSync($first['shared'], $second['shared']);
            if (!Formie::$plugin->getSyncs()->saveSync($sync)) throw new RuntimeException('Cannot save legacy field sync.');
        } else {
            $second['shared']->syncId = $first['shared']->id;
            if (!Formie::$plugin->getFields()->saveField($second['shared'], false)) throw new RuntimeException('Cannot save legacy field sync.');
        }
        // Payment models persist historical provider responses locally; no payment processing is invoked.
        $sub = Submission::find()->uid($this->map['submissions'][0])->one();
        $subscription = new \verbb\formie\models\Subscription(['integrationId'=>$stripe->id,'submissionId'=>$sub->id,'fieldId'=>$first['payment']->id,'reference'=>'fixture-sub-00123','trialDays'=>7,'subscriptionData'=>['customer'=>'fixture-customer'],'hasStarted'=>true]);
        if (!Formie::$plugin->getSubscriptions()->saveSubscription($subscription)) throw new RuntimeException(Json::encode($subscription->getErrors()));
        $payment = new \verbb\formie\models\Payment(['integrationId'=>$stripe->id,'submissionId'=>$sub->id,'fieldId'=>$first['payment']->id,'subscriptionId'=>$subscription->id,'amount'=>12.50,'currency'=>'AUD','status'=>'success','reference'=>'fixture-pay-00123','response'=>['id'=>'offline-response']]);
        if (!Formie::$plugin->getPayments()->savePayment($payment)) throw new RuntimeException(Json::encode($payment->getErrors()));
        $this->map['paymentId']=$payment->id; $this->map['subscriptionId']=$subscription->id;

        $user = User::find()->username('migrationRestricted')->one() ?? new User(['username'=>'migrationRestricted','email'=>'migration-restricted@example.test']);
        if (!Craft::$app->getElements()->saveElement($user)) throw new RuntimeException(Json::encode($user->getErrors()));
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $this->permissionNames($forms[0]->uid));
        $this->map['userId']=$user->id;
        $this->seedSettings($forms[0]);
        if ($this->version===2) Craft::$app->getProjectConfig()->saveModifiedConfigData(true); else Craft::$app->getProjectConfig()->flush();
        echo "Seeded two related forms, payment/subscription history, restricted user and configuration.\n";
    }

    private function seedSettings(Form $form): void
    {
        $legacy = $this->read('coverage-map.json');
        $this->map['uploadSource']='folder:'.$legacy['volumeUid'];
        $values = ['saveSpam'=>false,'spamLimit'=>137,'spamEmailNotifications'=>true,'spamBehaviour'=>'showMessage','spamBehaviourMessage'=>'Historical spam message','spamKeywords'=>'fixture-spam-word',
            'defaultFileUploadVolume'=>$this->map['uploadSource'],'defaultDateDisplayType'=>'dropdowns','defaultDateValueOption'=>'today',
            'captchas'=>['recaptcha'=>['type'=>'verbb\\formie\\integrations\\captchas\\Recaptcha','enabled'=>false,'saveSpam'=>false,'settings'=>['siteKey'=>'fixture-site-key','secretKey'=>'fixture-secret-key','type'=>'v3']]]];
        if (!Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin, array_merge(Formie::$plugin->getSettings()->toArray(),$values))) throw new RuntimeException('Cannot save historical settings.');
        $this->map['spamKeys']=['saveSpam','spamLimit','spamEmailNotifications','spamBehaviour','spamBehaviourMessage','spamKeywords'];
        @mkdir($this->root.'/templates/round-two',0777,true);
        file_put_contents($this->root.'/templates/round-two/form.twig','<form>Historical template fixture</form>');
        $existingTemplateId=(new Query())->select('id')->from('{{%formie_formtemplates}}')->where(['handle'=>'roundTwoTemplate'])->scalar();
        $template = ($existingTemplateId?Formie::$plugin->getFormTemplates()->getTemplateById($existingTemplateId):null) ?? new \verbb\formie\models\FormTemplate(['name'=>'Migration template','handle'=>'roundTwoTemplate','template'=>'round-two','useCustomTemplates'=>true,'outputCssLayout'=>false,'outputCssTheme'=>true,'outputJsBase'=>false,'outputJsTheme'=>false]);
        if (!Formie::$plugin->getFormTemplates()->saveTemplate($template)) throw new RuntimeException(Json::encode($template->getErrors()));
        $this->map['templateId']=$template->id;
        $settings = $this->version===2?$form->settings:$form->getSettings();
        $form->templateId=$template->id; $form->settings=$settings;
        $page=$form->getPages()[0];
        $pageSettings=$this->version===2?$page->settings->toArray():$page->getSettings();
        $pageSettings['jsGtmEventOptions']=[['label'=>'event','value'=>'migrationCompleted'],['label'=>'formHandle','value'=>'roundTwoAllowed']];
        if ($this->version===2) $page->settings->setAttributes($pageSettings,false); else $page->setPageSettings($pageSettings);
        $this->saveForm($form);
        @mkdir($this->root.'/templates/round-two',0777,true);
        file_put_contents($this->root.'/templates/round-two/form.twig','<form>Historical template fixture</form>');
    }

    private function snapshot(): array
    {
        $forms=array_map(fn($uid)=>Form::find()->uid($uid)->status(null)->one(),$this->map['forms']);
        $fields=array_map(fn($form)=>$this->fields($form),$forms);
        $user=User::find()->id($this->map['userId'])->one();
        $out=['forms'=>[], 'access'=>[], 'synced'=>false];
        if (isset($this->map['secondarySiteId'])) {
            $siteForm=Form::find()->uid($this->map['forms'][0])->siteId($this->map['secondarySiteId'])->status(null)->one();
            $secondary=Submission::find()->uid($this->map['secondarySubmissionUid'])->siteId($this->map['secondarySiteId'])->status(null)->one();
            if (!$siteForm || !$secondary) throw new RuntimeException('Secondary-site form or submission missing.');
            $out['secondary']=['formTitle'=>$siteForm->title,'submissionSiteId'=>(int)$secondary->siteId,'value'=>(string)$secondary->getFieldValue('shared')];
        }
        foreach ($forms as $index=>$form) {
            $sub=Submission::find()->uid($this->map['submissions'][$index])->status(null)->one();
            $out['forms'][]=['id'=>(int)$form->id,'uid'=>$form->uid,'layout'=>$this->layout($form),'sharedValue'=>(string)$sub->getFieldValue('shared'),'required'=>(bool)$fields[$index]['shared']->required,'placeholder'=>$fields[$index]['shared']->placeholder,'submissionId'=>(int)$sub->id];
            $out['access'][]=$this->version===4 ? Formie::$plugin->getPermissions()->canViewSubmissions($user,$form) : $user->can(($this->version===2?'formie-manageSubmission:':'formie-viewSubmissions:').$form->uid);
        }
        if ($this->version===2) {
            $a=Formie::$plugin->getSyncs()->getFieldSync($fields[0]['shared']); $b=Formie::$plugin->getSyncs()->getFieldSync($fields[1]['shared']);
            $out['synced']=$a && $b && $a->id===$b->id;
        } elseif ($this->version===3) $out['synced']=$fields[1]['shared']->syncId===$fields[0]['shared']->id;
        else $out['synced']=$fields[0]['shared']->definitionId!==null && $fields[0]['shared']->definitionId===$fields[1]['shared']->definitionId;
        $payment=Formie::$plugin->getPayments()->getPaymentById($this->map['paymentId']);
        $subscription=Formie::$plugin->getSubscriptions()->getSubscriptionById($this->map['subscriptionId']);
        if (!$payment || !$subscription) throw new RuntimeException('Historical payment/subscription missing.');
        $out['payment']=['id'=>$payment->id,'amount'=>(float)$payment->amount,'currency'=>$payment->currency,'status'=>$payment->status,'reference'=>$payment->reference,'submissionId'=>$payment->getSubmission()->id,'fieldHandle'=>$payment->getField()->handle,'subscriptionId'=>$payment->subscriptionId,'integrationId'=>$payment->integrationId];
        $out['subscription']=['id'=>$subscription->id,'reference'=>$subscription->reference,'trialDays'=>$subscription->trialDays,'data'=>$subscription->subscriptionData,'submissionId'=>$subscription->getSubmission()->id,'fieldHandle'=>($this->version===2?Craft::$app->getFields()->getFieldById($subscription->fieldId):Formie::$plugin->getFields()->getFieldById($subscription->fieldId))->handle,'integrationId'=>$subscription->integrationId];
        $settings=Formie::$plugin->getSettings();
        $spam=$this->version===4?Formie::$plugin->getSpamProtection()->getSettingsValues():$settings->toArray();
        $out['spam']=array_intersect_key($spam,array_flip($this->map['spamKeys'])); ksort($out['spam']);
        if ($this->version===4) {
            $row=Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle('recaptcha');
            $captcha=Json::decodeIfJson($row['settings']);
            $out['captcha']=['siteKey'=>$captcha['siteKey']??null,'secretKey'=>$captcha['secretKey']??null,'type'=>$captcha['type']??null,'enabled'=>filter_var($row['enabled'],FILTER_VALIDATE_BOOLEAN),'saveSpam'=>(bool)$row['saveSpam']];
            $defaults=$settings->fieldDefaults;
            $out['defaults']=['upload'=>$defaults[\verbb\formie\fields\FileUpload::class]['uploadLocationSource']??null,'dateType'=>$defaults[\verbb\formie\fields\Date::class]['displayType']??null,'dateOption'=>$defaults[\verbb\formie\fields\Date::class]['defaultOption']??null];
        } else {
            $captcha=$settings->captchas['recaptcha'];
            $out['captcha']=array_merge(array_intersect_key($captcha['settings'],array_flip(['siteKey','secretKey','type'])),array_intersect_key($captcha,array_flip(['enabled','saveSpam'])));
            $out['defaults']=['upload'=>$settings->defaultFileUploadVolume,'dateType'=>$settings->defaultDateDisplayType,'dateOption'=>$settings->defaultDateValueOption];
        }
        $template=Formie::$plugin->getFormTemplates()->getTemplateById($this->map['templateId']);
        $out['template']=['id'=>$template->id,'template'=>$template->template,'custom'=>$template->useCustomTemplates,'css'=>$this->version===4?$template->outputCss:($template->outputCssLayout||$template->outputCssTheme),'js'=>$this->version===4?$template->outputJs:($template->outputJsBase||$template->outputJsTheme)];
        $out['configurationScopes']=['captcha'=>$this->version===4?$row['scope']:'project','spam'=>$this->version===4?Formie::$plugin->getSpamProtection()->getScope():'project'];
        $rawSettings=Craft::$app->getProjectConfig()->get('plugins.formie.settings')??[];
        $oldKeys=array_merge($this->map['spamKeys'],['defaultFileUploadVolume','defaultDateDisplayType','defaultDateValueOption']);
        $out['oldSettingKeysRemoved']=$this->version!==4 || (!array_intersect(array_keys($rawSettings),$oldKeys) && empty($rawSettings['captchas']));
        $out['template']['association']=$forms[0]->templateId;
        $page=$forms[0]->getPages()[0]; $ps=$this->version===2?$page->settings->toArray():$page->getSettings();
        $out['clientEvents']=$this->version===4?($ps['clientEvents']??[]):[['event'=>'migrationCompleted','payload'=>[['key'=>'formHandle','value'=>'roundTwoAllowed']]]];
        if ($this->version!==4 && ($ps['jsGtmEventOptions']??[])!==[['label'=>'event','value'=>'migrationCompleted'],['label'=>'formHandle','value'=>'roundTwoAllowed']]) throw new RuntimeException('Historical client event did not persist.');
        return $out;
    }

    private function behaviour(): void
    {
        if ($this->version !== 4) throw new RuntimeException('Behaviour verification requires target.');
        $transaction=Craft::$app->getDb()->beginTransaction();
        try {
            $forms=array_map(fn($uid)=>Form::find()->uid($uid)->status(null)->one(),$this->map['forms']);
            $a=$this->fields($forms[0])['shared'];
            $b=$this->fields($forms[1])['shared'];
            $before=$this->snapshot();
            $a->placeholder='Shared edit after upgrade';
            if (!Formie::$plugin->getFields()->saveField($a)) throw new RuntimeException('Shared field edit failed: '.Json::encode($a->getErrors()));
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            $freshA=Formie::$plugin->getFields()->getFieldById($a->id);
            $freshB=Formie::$plugin->getFields()->getFieldById($b->id);
            $this->compare('roundTwo.sharedEdit.placeholders',['Shared edit after upgrade','Shared edit after upgrade'],[$freshA->placeholder,$freshB->placeholder]);
            $this->compare('roundTwo.sharedEdit.required',[true,false],[(bool)$freshA->required,(bool)$freshB->required]);
            $after=$this->snapshot();
            foreach ($before['forms'] as $index=>$form) $this->compare('roundTwo.sharedEdit.content.'.$index,$form['sharedValue'],$after['forms'][$index]['sharedValue']);
        } catch (\Throwable $e) {
            $this->checks[]=['name'=>'roundTwo.sharedEdit','pass'=>false,'actual'=>$e->getMessage()];
        } finally {
            $transaction->rollBack();
            Formie::$plugin->getFields()->resetFieldRegistryCache();
        }
        try {
            $form=Form::find()->uid($this->map['forms'][0])->one();
            $submission=Submission::find()->uid($this->map['submissions'][0])->one();
            $definitions=$form->getPages()[0]->getSettings()['clientEvents']??[];
            $events=\verbb\formie\helpers\ClientEventsHelper::resolveEvents($definitions,$submission);
            $this->compare('roundTwo.resolvedEvent',[['event'=>'migrationCompleted','payload'=>['event'=>'migrationCompleted','formHandle'=>'roundTwoAllowed']]],$events);
        } catch (\Throwable $e) {
            $this->checks[]=['name'=>'roundTwo.resolvedEvent','pass'=>false,'actual'=>$e->getMessage()];
        }
    }

    private function compare(string $path, mixed $expected, mixed $actual): void
    {
        if (is_array($expected) && is_array($actual)) {
            foreach ($expected as $key=>$value) $this->compare($path.'.'.$key,$value,$actual[$key]??null);
            if (count($expected)!==count($actual)) $this->checks[]=['name'=>$path.' count','expected'=>count($expected),'actual'=>count($actual),'pass'=>false];
        } else $this->checks[]=['name'=>$path,'expected'=>$expected,'actual'=>$actual,'pass'=>$expected===$actual];
    }
    private function read(string $path): array { return Json::decode(file_get_contents($this->root.'/'.$path)); }
    private function write(string $path,array $data): void { file_put_contents($this->root.'/'.$path,Json::encode($data,JSON_PRETTY_PRINT).PHP_EOL); }
}
