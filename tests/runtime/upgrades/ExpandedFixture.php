<?php
namespace FormieMigrationCoverage;

use Craft;
use RuntimeException;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

// This corpus is authored through the installed historical plugin. Expected values
// are captured after a separate reload, never manufactured in the Formie 4 target.
final class ExpandedFixture
{
    private array $checks = [];
    private array $map = [];
    public function __construct(private string $root, private int $version) {}

    public function run(string $action): void
    {
        $this->checks = [];
        if ($action === 'seed') {
            if (!in_array($this->version, [2, 3], true)) throw new RuntimeException('Only historical sources can seed.');
            $this->seed();
        } else {
            $this->map = $this->read('coverage-map.json');
            if ($action==='source-reference') {
                if ($this->version===4) throw new RuntimeException('Source reference validation requires a historical source.');
                $uid=$this->map['forms']['coverageScalars']['submissions'][0];
                $submission=Submission::find()->uid($uid)->one();
                $form=$submission->getForm();
                $settings=$this->version===2?$form->settings:$form->getSettings();
                $integration=Formie::$plugin->getIntegrations()->getIntegrationByHandle('fixtureMailchimp');
                $this->configureOfflineMapping($integration);
                $payload=$integration->getFieldMappingValues($submission,$settings->integrations['fixtureMailchimp']['fieldMapping']);
                if (($payload['EMAIL']??null)!=='fixture@example.test') throw new RuntimeException('Historical email mapping is invalid: '.json_encode($payload));
                $other=Submission::find()->uid($this->map['forms']['coverageScalars']['submissions'][1])->one();
                foreach (array_merge([$this->pageSettings($form->getPages()[1])['pageConditions']],array_map(fn($n)=>$n->conditions,$form->getNotifications())) as $conditions) {
                    if (!\verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($conditions,$submission) || \verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($conditions,$other)) throw new RuntimeException('Historical condition is invalid.');
                }
                echo 'Historical email mapping and matching/non-matching page and notification conditions resolve correctly.'.PHP_EOL;
            } elseif ($action==='behaviour') {
                $this->behaviour();
            } elseif (str_starts_with($action,'probe:')) {
                $this->probe(substr($action,6));
            } elseif ($action === 'enrich') {
                if ($this->version === 4) throw new RuntimeException('Cannot enrich an upgrade target.');
                $this->enrich();
            } elseif ($action === 'capture') {
                if ($this->version===4) throw new RuntimeException('Cannot capture expectations from target.');
                $this->run('source-reference');
                $this->map['expected'] = $this->snapshot();
                $this->assertSourceCorpus($this->map['expected']);
                $this->write('coverage-map.json', $this->map);
                echo 'Captured source expectations for '.count($this->map['forms'])." forms.\n";
            } elseif ($action === 'snapshot') {
                $base = $this->read('.state/snapshot-4.json');
                $base['expanded'] = $this->snapshot();
                $base['expandedIdentities'] = $this->identities();
                $this->write('.state/snapshot-4.json', $base);
            } elseif ($action === 'verify') {
                if ($this->version !== 4) throw new RuntimeException('Verification requires Formie 4.');
                $actual = $this->snapshot();
                $this->compare('expanded', $this->expectedForTarget(), $actual);
                $this->identities();
                $report = $this->read('.state/checks.json');
                $report['checks'] = array_merge($report['checks'], $this->checks);
                $report['pass'] = !in_array(false, array_column($report['checks'], 'pass'), true);
                $this->write('.state/checks.json', $report);
                echo count($this->checks).' expanded assertions, '.count(array_filter($this->checks, fn($c) => !$c['pass']))." failures.\n";
            } else throw new RuntimeException('Unknown coverage action.');
        }
    }

    private function field(string $type, string $handle, array $settings = [], array $children = []): array
    {
        return compact('type', 'handle', 'settings', 'children');
    }

    private function definitions(): array
    {
        $f = fn($t, $h, $s = [], $c = []) => $this->field($t, $h, $s, $c);
        $options = ['options' => [['label'=>'Zero','value'=>'0'],['label'=>'Padded','value'=>'00123'],['label'=>'Unicode 東京','value'=>'unicode']]];
        return [
            'coverageScalars' => [
                [$f('SingleLineText','reference',['required'=>true,'placeholder'=>'Reference placeholder','instructions'=>'Keep leading zeros']), $f('MultiLineText','message'), $f('Email','email'), $f('Number','amount',['decimals'=>2]), $f('Hidden','hiddenValue'), $f('Phone','phone')],
                [$f('Dropdown','choice',$options), $f('Checkboxes','choices',$options), $f('Radio','radioChoice',$options), $f('Agree','consent',['checkedValue'=>'Yes','uncheckedValue'=>'No','description'=>[['type'=>'paragraph','content'=>[['type'=>'text','text'=>'Fixture consent']]]]]), $f('Name','singleName',['useMultipleFields'=>false])],
            ],
            'coverageCompound' => [
                [$f('Name','person',['useMultipleFields'=>true]), $f('Address','address'), $f('Date','eventDate',['displayType'=>'calendar']), $f('Table','lineItems',['columns'=>['col1'=>['heading'=>'Description','handle'=>'description','type'=>'singleline'],'col2'=>['heading'=>'Quantity','handle'=>'quantity','type'=>'number']]])],
                [$f('Date','dateInputs',['displayType'=>'inputs']), $f('Date','dateDropdowns',['displayType'=>'dropdowns']), $f('Date','futureYear',['displayType'=>'dropdowns'])],
            ],
            'coverageNested' => [
                [$f('Group','groupBlock',[],[$f('SingleLineText','innerText'),$f('Dropdown','innerChoice',$options)]), $f('Repeater','repeatBlock',['minRows'=>0,'maxRows'=>5,'addLabel'=>'Add fixture row'],[$f('SingleLineText','rowText'),$f('Number','rowNumber')])],
            ],
            'coveragePresentation' => [
                [$f('Heading','heading'),$f('Html','html',['htmlContent'=>'<p>Migration HTML 東京</p>']),$f('Section','section'),$f('Summary','summary'),$f('SingleLineText','visibleText')],
            ],
            'coverageAssets' => [
                [$f('FileUpload','documents',['uploadLocationSource'=>'folder:'.$this->map['volumeUid'],'allowedKinds'=>['text'],'restrictFiles'=>false,'limitFiles'=>'3']),$f('Users','people')],
            ],
            'coverageIsolation' => [
                [$f('SingleLineText','reference'),$f('Dropdown','choice',$options),$f('Email','email')],
            ],
            'coverageEncrypted' => [
                [$f('SingleLineText','secretText',['enableContentEncryption'=>true]), $f('MultiLineText','secretMessage',['enableContentEncryption'=>true]), $f('Email','secretEmail',['enableContentEncryption'=>true]), $f('Phone','secretPhone',['enableContentEncryption'=>true]), $f('Name','secretName',['useMultipleFields'=>true,'enableContentEncryption'=>true])],
            ],
        ];
    }

    private function fieldConfig(array $definition): array
    {
        $type = 'verbb\\formie\\fields\\'.($this->version === 2 ? 'formfields\\' : '').$definition['type'];
        $settings = $definition['settings'];
        $rows = $definition['children'] ? [['fields'=>array_map(fn($f)=>$this->fieldConfig($f), $definition['children'])]] : [];
        if ($this->version === 2) {
            $config = ['id'=>'new-'.$definition['handle'],'type'=>$type,'label'=>ucfirst($definition['handle']),'handle'=>$definition['handle'],'rowId'=>'new-row-'.$definition['handle'],'settings'=>$settings];
            if ($rows) $config['rows'] = $rows;
            return $config;
        }
        if (in_array($definition['type'], ['Name','Address','Date'], true)) $settings['rows'] = (new $type($settings))->getSubFields();
        if ($definition['type'] === 'Date') {
            $settings['rows'][0]['fields'][0]['label'] = 'Custom date part';
            if ($definition['handle'] === 'futureYear') {
                $settings['rows'][0]['fields'][0]['minYearRange'] = 100;
                $settings['rows'][0]['fields'][0]['maxYearRange'] = 101;
            }
            $lastRow = array_key_last($settings['rows']);
            $lastField = array_key_last($settings['rows'][$lastRow]['fields']);
            $settings['rows'][$lastRow]['fields'][$lastField]['enabled'] = false;
        }
        if ($rows) $settings['rows'] = $rows;
        return array_merge(['type'=>$type,'label'=>ucfirst($definition['handle']),'handle'=>$definition['handle']], $settings);
    }

    private function seed(): void
    {
        $this->map = ['revision'=>1,'sourceVersion'=>$this->version,'forms'=>[], 'stencils'=>[], 'integrations'=>[], 'dateChildSettings'=>true];
        $this->resources();
        foreach ($this->definitions() as $handle=>$pages) {
            if ($old = Form::find()->handle($handle)->status(null)->one()) Craft::$app->getElements()->deleteElement($old, true);
            $form = new Form(['title'=>'Migration '.$handle,'handle'=>$handle]);
            $config = [];
            foreach ($pages as $index=>$fields) {
                $rows = [];
                foreach (array_chunk($fields, 2) as $chunk) $rows[] = ['fields'=>array_map(fn($f)=>$this->fieldConfig($f),$chunk)];
                $config[] = ['label'=>'Coverage page '.($index+1),'settings'=>['submitButtonLabel'=>'Continue fixture','backButtonLabel'=>'Back fixture'],'rows'=>$rows];
            }
            if ($this->version === 2) foreach ($config as $index=>&$pageConfig) $pageConfig['id'] = 'new-page-'.$index;
            unset($pageConfig);
            if ($this->version === 2) $form->setFormFieldLayout(Formie::$plugin->getForms()->buildFieldLayout($config, Form::class));
            else $form->setFormLayout(new FieldLayout(['pages'=>$config]));
            $settings = ($this->version===2 ? $form->settings : $form->getSettings());
            foreach (['displayFormTitle'=>true,'displayPageTabs'=>true,'displayPageProgress'=>true,'scrollToTop'=>false,'submitMethod'=>'page-reload','submitAction'=>'message','submitActionMessage'=>'Migration thank you 東京','validationOnFocus'=>true,'errorMessage'=>'Fixture validation failed'] as $key=>$value) $settings->$key = $value;
            $form->settings = $settings;
            if ($this->version === 2) $ok = Formie::$plugin->getForms()->saveForm($form);
            else $ok = Craft::$app->getElements()->saveElement($form);
            if (!$ok) throw new RuntimeException($handle.': '.json_encode($form->getErrors()));
            foreach ($pages as &$pageDefinitions) foreach ($pageDefinitions as &$definition) {
                if (in_array($definition['type'], ['Section','Summary'], true)) foreach ($this->fields($form) as $actualField) if ((new \ReflectionClass($actualField))->getShortName() === $definition['type']) $definition['handle'] = $actualField->handle;
            }
            unset($pageDefinitions, $definition);
            $this->map['forms'][$handle] = ['uid'=>$form->uid,'definitions'=>$pages,'submissions'=>[]];
            $this->notifications($form);
            foreach ($this->payloads($handle) as $index=>$values) {
                $sub = new Submission(['title'=>'Coverage '.$handle.' '.$index,'formId'=>$form->id,'siteId'=>Craft::$app->getSites()->getPrimarySite()->id,'isSpam'=>$index===2]);
                if ($this->version === 2 && $handle === 'coverageNested') {
                    $values['groupBlock'] = ['new1'=>['fields'=>$values['groupBlock']]];
                    $rows=[]; foreach ($values['repeatBlock']['rows'] as $k=>$row) $rows['new'.($k+1)] = ['fields'=>$row];
                    $values['repeatBlock'] = ['rows'=>$rows,'sortOrder'=>array_keys($rows)];
                }
                $sub->setFieldValues($values);
                if (!Craft::$app->getElements()->saveElement($sub)) throw new RuntimeException($handle.' submission '.$index.': '.json_encode($sub->getErrors()));
                $this->map['forms'][$handle]['submissions'][] = $sub->uid;
            }
            if (in_array($handle,['coverageScalars','coverageNested'],true)) $this->stencil($form,$config);
        }
        $this->integrations();
        $settings = Formie::$plugin->getSettings();
        $values = ['pluginName'=>'Migration Formie 東京','ajaxTimeout'=>37,'enableUnloadWarning'=>false,'enableBackSubmission'=>false,'filterIntegrationMapping'=>false,'useQueueForNotifications'=>true,'useQueueForIntegrations'=>true];
        if ($this->version === 2) unset($values['filterIntegrationMapping']);
        if (!Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin,$values)) throw new RuntimeException('Plugin settings save failed.');
        $this->map['pluginSettings'] = array_keys($values);
        $this->write('coverage-map.json',$this->map);
        if ($this->version === 2) Craft::$app->getProjectConfig()->saveModifiedConfigData(true);
        else Craft::$app->getProjectConfig()->flush();
        echo "Seeded expanded corpus; run capture in a fresh process.\n";
    }

    private function payloads(string $handle): array
    {
        return match ($handle) {
            'coverageScalars'=>[
                ['reference'=>'00123','message'=>"Line one\nCafé 東京 😀",'email'=>'fixture@example.test','amount'=>'12.50','hiddenValue'=>'0','phone'=>'+61412345678','choice'=>'00123','choices'=>['0','unicode'],'radioChoice'=>'0','consent'=>true,'singleName'=>'Café Person'],
                ['reference'=>'0','message'=>'0','email'=>'second@example.test','amount'=>0,'hiddenValue'=>'00123','phone'=>'','choice'=>'0','choices'=>[],'radioChoice'=>'unicode','consent'=>false,'singleName'=>'Zero'],
                ['reference'=>'nullable','message'=>null,'email'=>'','amount'=>null,'hiddenValue'=>null,'choice'=>null,'choices'=>null,'radioChoice'=>null,'consent'=>false,'singleName'=>''],
            ],
            'coverageCompound'=>[
                ['person'=>['firstName'=>'Café','lastName'=>'東京'],'address'=>['address1'=>'123 Fixture St','city'=>'Melbourne','state'=>'VIC','zip'=>'0300','country'=>'AU'],'eventDate'=>'2024-02-29 13:45:00','dateInputs'=>'2024-02-29 00:00:00','dateDropdowns'=>'2024-12-31 23:59:00','lineItems'=>[['col1'=>'00123','col2'=>'0'],['col1'=>'東京','col2'=>'2']]],
                ['person'=>['firstName'=>'','lastName'=>''],'address'=>[],'eventDate'=>null,'dateInputs'=>null,'dateDropdowns'=>null,'lineItems'=>[]],
            ],
            'coverageNested'=>[
                ['groupBlock'=>['innerText'=>'Nested 東京','innerChoice'=>'00123'],'repeatBlock'=>['rows'=>[['rowText'=>'00123','rowNumber'=>0],['rowText'=>'東京','rowNumber'=>2]]]],
                ['groupBlock'=>['innerText'=>'','innerChoice'=>'0'],'repeatBlock'=>['rows'=>[]]],
            ],
            'coverageAssets'=>[['documents'=>$this->map['assetIds'],'people'=>[\craft\elements\User::find()->admin(true)->one()->id]],['documents'=>[],'people'=>[]]],
            'coveragePresentation'=>[['visibleText'=>'Render fixture']],
            'coverageEncrypted'=>[
                ['secretText'=>'private-00123-東京','secretMessage'=>"Private café\nSecond line",'secretEmail'=>'private@example.test','secretPhone'=>'+61412345678','secretName'=>['firstName'=>'Private','lastName'=>'東京']],
                ['secretText'=>'0','secretMessage'=>'0','secretEmail'=>'','secretPhone'=>'','secretName'=>['firstName'=>'','lastName'=>'']],
                ['secretText'=>null,'secretMessage'=>null,'secretEmail'=>null,'secretPhone'=>null,'secretName'=>null],
            ],
            default=>array_map(fn($i)=>['reference'=>'isolation-'.$i,'choice'=>$i%2?'00123':'0','email'=>'fixture'.$i.'@example.test'],range(0,24)),
        };
    }

    private function resources(): void
    {
        $fs = Craft::$app->getFs()->getFilesystemByHandle('coverageUploads');
        if (!$fs) {
            $fs = new \craft\fs\Local(['name'=>'Coverage uploads','handle'=>'coverageUploads','path'=>'@webroot/uploads/coverage']);
            if (!Craft::$app->getFs()->saveFilesystem($fs)) throw new RuntimeException('Filesystem save failed.');
        }
        $volume = Craft::$app->getVolumes()->getVolumeByHandle('coverageUploads');
        if (!$volume) {
            $volume = new \craft\models\Volume(['name'=>'Coverage uploads','handle'=>'coverageUploads','fsHandle'=>'coverageUploads']);
            if (!Craft::$app->getVolumes()->saveVolume($volume)) throw new RuntimeException('Volume save failed.');
        }
        $this->map['volumeUid']=$volume->uid;
        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
        $this->map['assetIds']=[]; $this->map['assets']=[];
        foreach (['leading-zero.txt'=>'00123','unicode.txt'=>'Café 東京'] as $filename=>$content) {
            $path=\craft\helpers\Assets::tempFilePath($filename); file_put_contents($path,$content);
            $asset=new \craft\elements\Asset(['tempFilePath'=>$path,'filename'=>$filename,'newFolderId'=>$folder->id,'avoidFilenameConflicts'=>true]);
            $asset->setVolumeId($volume->id);
            if (!Craft::$app->getElements()->saveElement($asset)) throw new RuntimeException(json_encode($asset->getErrors()));
            $this->map['assetIds'][]=(int)$asset->id;
            $this->map['assets'][$asset->uid]=hash('sha256',$content);
        }
    }

    private function behaviour(): void
    {
        if ($this->version!==4) throw new RuntimeException('Behaviour tests require target.');
        $transaction=Craft::$app->getDb()->beginTransaction(); $checks=[]; $failures=[];
        try {
            $beforeForms = $this->snapshot();
            $beforeIdentities = $this->identities();
            foreach ($this->map['forms'] as $handle => $entry) {
                $editable = Form::find()->uid($entry['uid'])->status(null)->one();
                $editable->title .= ' edited after upgrade';
                if (!Craft::$app->getElements()->saveElement($editable)) throw new RuntimeException('Migrated form cannot save: ' . $handle . ' ' . json_encode($editable->getErrors()));
                $beforeForms['forms'][$handle]['title'] = $editable->title;
                $checks[] = 'Edited and saved migrated form ' . $handle;
            }
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            $this->compare('formEdit', $beforeForms, $this->snapshot());
            $this->compare('formEdit.identities', $beforeIdentities, $this->identities());
            $editFailures = array_values(array_filter($this->checks, fn($check) => !$check['pass']));
            $this->write('.state/form-edit.json', ['pass' => !$editFailures, 'failures' => $editFailures]);
            if ($editFailures) throw new RuntimeException('Editing migrated forms changed content, configuration or field identities: ' . json_encode($editFailures));
            $checks[] = 'Form edits preserved content, configuration and field identities';
            // Save through the element API: no delivery workflow or external requests.
            foreach ($this->map['forms'] as $handle=>$entry) {
                foreach ($entry['submissions'] as $uid) {
                    $sub=Submission::find()->uid($uid)->status(null)->isSpam(null)->one();
                    $form=$sub->getForm();$before=[];
                    foreach ($this->fields($form) as $f) $before[$f->handle]=$this->value($f,$sub);
                    $sub->title .= ' round trip';
                    if (!Craft::$app->getElements()->saveElement($sub)) throw new RuntimeException('Migrated submission cannot save: '.json_encode($sub->getErrors()));
                    $fresh=Submission::find()->uid($uid)->status(null)->isSpam(null)->one();$after=[];
                    foreach ($this->fields($fresh->getForm()) as $f) $after[$f->handle]=$this->value($f,$fresh);
                    if ($before!==$after) throw new RuntimeException('Submission content changed on round trip: '.$handle.' '.$uid);
                    $checks[]='Saved and reloaded '.$uid;
                }
            }
            $form=Form::find()->handle('coverageIsolation')->status(null)->one();
            $sub=new Submission(['formId'=>$form->id,'siteId'=>$form->siteId,'title'=>'New after upgrade']);
            $sub->setFieldValues(['reference'=>'post-upgrade-00123','choice'=>'00123','email'=>'post-upgrade@example.test']);
            if (!Craft::$app->getElements()->saveElement($sub)) throw new RuntimeException('New submission failed: '.json_encode($sub->getErrors()));
            $fresh=Submission::find()->id($sub->id)->one();
            if ((string)$fresh->getFieldValue('reference')!=='post-upgrade-00123') throw new RuntimeException('New submission did not persist.');
            $checks[]='New submission saved and reloaded';
            $scalars=$this->map['forms']['coverageScalars'];
            $submission=Submission::find()->uid($scalars['submissions'][0])->one();
            $form=$submission->getForm();
            try {
            $integration=Formie::$plugin->getIntegrations()->getIntegrationByHandle('fixtureMailchimp');
            $this->configureOfflineMapping($integration);
            $mapping=$form->getSettings()->integrations['fixtureMailchimp']['fieldMapping'];
            $payload=$integration->getFieldMappingValues($submission,$mapping);
            if (($payload['EMAIL']??null)!=='fixture@example.test') throw new RuntimeException('Migrated integration mapping does not resolve: '.json_encode(['mapping'=>$mapping,'payload'=>$payload,'email'=>$submission->getFieldValue('email')]));
            $checks[]='Resolved migrated integration email payload without sending';
            } catch (\Throwable $e) { $failures[]=['area'=>'integration mapping','error'=>$e->getMessage()]; }
            foreach ($form->getNotifications() as $notification) {
                try {
                $resolved=\verbb\formie\helpers\Variables::getFieldAndValueForReference($notification->replyTo,$submission);
                if ((string)$resolved['value']!=='fixture@example.test') throw new RuntimeException('Migrated notification reply-to does not resolve.');
                $checks[]='Resolved notification recipient '.$notification->handle;
                } catch (\Throwable $e) { $failures[]=['area'=>'notification '.$notification->handle,'error'=>$e->getMessage()]; }
                try {
                    if (!str_contains($notification->getParsedContent(),'Migration notification content 東京')) throw new RuntimeException('Notification content no longer renders.');
                    $checks[]='Rendered notification body '.$notification->handle;
                    $other=Submission::find()->uid($scalars['submissions'][1])->one();
                    if (!\verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($notification->conditions,$submission) || \verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($notification->conditions,$other)) throw new RuntimeException('Migrated notification condition does not distinguish matching and non-matching submissions.');
                    $checks[]='Evaluated notification condition '.$notification->handle;
                } catch (\Throwable $e) { $failures[]=['area'=>'notification body '.$notification->handle,'error'=>$e->getMessage()]; }
            }
            try {
                $conditions=$this->pageSettings($form->getPages()[1])['pageConditions'];
                $other=Submission::find()->uid($scalars['submissions'][1])->one();
                if (!\verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($conditions,$submission) || \verbb\formie\helpers\ConditionsHelper::getConditionalTestResult($conditions,$other)) throw new RuntimeException('Migrated page condition does not distinguish matching and non-matching submissions.');
                $checks[]='Evaluated matching and non-matching page conditions';
            } catch (\Throwable $e) { $failures[]=['area'=>'page condition','error'=>$e->getMessage()]; }
            $stencilIndex = 0;
            foreach ($this->map['stencils'] as $handle=>$_) {
                try {
                $stencil=Formie::$plugin->getStencils()->getStencilByHandle($handle);
                $form=new Form(['title'=>'Materialised coverage stencil','handle'=>'coverageMaterialised' . $stencilIndex++]);
                $stencil->applyStencilToForm($form,true);
                if (!$form->getFields() || count($form->getNotifications())!==count($stencil->getNotifications())) throw new RuntimeException('Stencil materialisation failed.');
                if (!Craft::$app->getElements()->saveElement($form)) throw new RuntimeException('Materialised stencil cannot save: ' . json_encode($form->getErrors()));
                $reloaded = Form::find()->id($form->id)->status(null)->one();
                if (!$reloaded || count($reloaded->getFields()) !== count($form->getFields()) || count($reloaded->getNotifications()) !== count($stencil->getNotifications())) throw new RuntimeException('Materialised stencil did not reload intact.');
                $checks[]='Materialised, saved and reloaded '.$handle;
                } catch (\Throwable $e) { $failures[]=['area'=>'stencil '.$handle,'error'=>$e->getMessage()]; }
            }
            $this->write('.state/coverage-behaviour.json',['pass'=>!$failures,'checks'=>$checks,'failures'=>$failures,'externalDelivery'=>'not invoked','rolledBack'=>true]);
            $report=$this->read('.state/checks.json');
            foreach ($checks as $check) $report['checks'][]=['name'=>'behaviour: '.$check,'pass'=>true];
            foreach ($failures as $failure) $report['checks'][]=['name'=>'behaviour: '.$failure['area'],'pass'=>false,'actual'=>$failure['error']];
            $report['pass']=!in_array(false,array_column($report['checks'],'pass'),true);
            $this->write('.state/checks.json',$report);
            echo count($checks)." post-upgrade behaviour checks passed, ".count($failures)." failed.\n";
            foreach ($failures as $failure) echo json_encode($failure).PHP_EOL;
        } finally { $transaction->rollBack(); }
    }

    private function offlineMetadata(): array
    {
        return ['lists' => [['class' => 'verbb\\formie\\models\\IntegrationCollection', 'id' => 'fixture-list', 'name' => 'Offline fixture list', 'fields' => [
            ['class' => 'verbb\\formie\\models\\IntegrationField', 'handle' => 'EMAIL', 'name' => 'Fixture email', 'type' => 'string'],
        ]]]];
    }

    private function configureOfflineMapping($integration): void
    {
        $integration->listId = 'fixture-list';
        // Older frozen corpora predate provider metadata. Keep their mapping-only check
        // explicit; new corpora must preserve the metadata authored in Formie 3.
        if (empty($this->map['integrationMetadata'])) $integration->cache['settings'] = $this->offlineMetadata();
    }

    private function identities(): array
    {
        if ($this->version !== 4) return [];
        $identities=[];
        $walk=function($owner,string $path) use (&$walk,&$identities) {
            foreach ($this->fields($owner) as $field) {
                $key=$path.'.'.$field->handle;
                foreach (['uid','reference'] as $property) {
                    $value=$field->$property;
                    if (!is_string($value) || !preg_match('/^[a-f0-9-]{36}$/i',$value)) throw new RuntimeException('Invalid migrated identity '.$key.'.'.$property);
                    $identities[$key][$property]=$value;
                }
                if (method_exists($field,'getFields')) $walk($field,$key);
            }
        };
        foreach ($this->map['forms'] as $handle=>$entry) $walk(Form::find()->uid($entry['uid'])->status(null)->one(),$handle);
        foreach ($this->map['stencils'] as $handle=>$_) if (Formie::$plugin->getStencils()->getStencilByHandle($handle)->scope!=='project') throw new RuntimeException('Wrong migrated stencil scope.');
        foreach ($this->map['integrations'] as $handle=>$_) if (Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)->scope!=='project') throw new RuntimeException('Wrong migrated integration scope.');
        return $identities;
    }

    private function probe(string $area): void
    {
        if ($this->version!==4) throw new RuntimeException('Probes require the isolated target.');
        if (!$this->read('.state/checks.json')['pass']) throw new RuntimeException('Probes require a passing baseline verification.');
        $db=Craft::$app->getDb(); $transaction=$db->beginTransaction();
        try {
            $formUid=$this->map['forms']['coverageScalars']['uid'];
            $formId=(new \craft\db\Query())->select('id')->from('{{%elements}}')->where(['uid'=>$formUid])->scalar();
            if ($area==='notification') $db->createCommand()->update('{{%formie_notifications}}',['subject'=>'Deliberately corrupted probe'],['formId'=>$formId])->execute();
            elseif ($area==='form-settings') {
                $raw=(new \craft\db\Query())->select('settings')->from('{{%formie_forms}}')->where(['id'=>$formId])->scalar();
                $settings=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$settings['scrollToTop']=!$settings['scrollToTop'];
                $db->createCommand()->update('{{%formie_forms}}',['settings'=>json_encode($settings)],['id'=>$formId])->execute();
            } elseif ($area==='submission') {
                $uid=$this->map['forms']['coverageScalars']['submissions'][0];
                $id=(new \craft\db\Query())->select('id')->from('{{%elements}}')->where(['uid'=>$uid])->scalar();
                $db->createCommand()->update('{{%formie_submissions}}',['content'=>'{}'],['id'=>$id])->execute();
            } else throw new RuntimeException('Unknown probe.');
            $this->compare('probe',$this->expectedForTarget(),$this->snapshot());
            $failures=array_values(array_filter($this->checks,fn($c)=>!$c['pass']));
            if (!$failures) throw new RuntimeException('Verifier missed deliberate corruption: '.$area);
            $this->write('.state/probe-'.$area.'.json',['detected'=>true,'area'=>$area,'failures'=>$failures]);
            echo 'Detected deliberate '.$area.' corruption; rolling back.'.PHP_EOL;
        } finally { $transaction->rollBack(); }
    }

    private function enrich(): void
    {
        $nodes=fn($text)=>[['type'=>'paragraph','content'=>[['type'=>'text','text'=>$text]]]];
        foreach ($this->map['forms'] as $handle=>$entry) {
            $form=Form::find()->uid($entry['uid'])->status(null)->one();
            $settings=$this->version===2?$form->settings:$form->getSettings();
            $settings->submitActionMessage=$nodes('Migration thank you 東京');
            $settings->errorMessage=$nodes('Fixture validation failed');
            if ($handle === 'coverageScalars') $settings->integrations = ['fixtureMailchimp'=>['enabled'=>false,'listId'=>'fixture-list','fieldMapping'=>['EMAIL'=>$this->version===2?'{email}':'{field:email}'],'optInField'=>$this->version===2?'{consent}':'{field:consent}']];
            $form->settings=$settings;
            $pages=$form->getPages();
            if ($handle === 'coverageScalars') {
            $pageSettings=$this->pageSettings($pages[1]);
            $pageSettings['enablePageConditions']=true;
            $pageSettings['pageConditions']=['conditionRule'=>'all','conditions'=>[['field'=>$this->version===2?'reference':'{field:reference}','condition'=>'=','value'=>'00123']]];
            if ($this->version===2) $pages[1]->settings->setAttributes($pageSettings,false); else $pages[1]->setPageSettings($pageSettings);
            $reference=$this->fields($form)['reference'];
            $reference->instructions=json_encode($nodes('Keep leading zeros'));
            }
            if ($this->version===2) $ok=Formie::$plugin->getForms()->saveForm($form); else $ok=Craft::$app->getElements()->saveElement($form);
            if (!$ok) throw new RuntimeException('Enrich form: '.json_encode($form->getErrors()));
            foreach ($form->getNotifications() as $n) {
                $n->content=json_encode($nodes('Migration notification content 東京'));
                if ($handle === 'coverageScalars') {
                $n->replyTo=$this->version===2?'{field.email}':'{field:email}';
                $n->enableConditions=true;
                $n->conditions=['conditionRule'=>'all','conditions'=>[['field'=>$this->version===2?'{reference}':'{field:reference}','condition'=>'=','value'=>'00123']]];
                $n->attachAssets=array_map(fn($id)=>['id'=>$id],$this->map['assetIds']);
                }
                if (!Formie::$plugin->getNotifications()->saveNotification($n)) throw new RuntimeException('Enrich notification: '.json_encode($n->getErrors()));
            }
        }
        foreach ($this->map['stencils'] as $handle=>$formHandle) {
            $stencil=Formie::$plugin->getStencils()->getStencilByHandle($handle);
            $stencil->data->settings->submitActionMessage=$nodes('Migration thank you 東京');
            $stencil->data->settings->errorMessage=$nodes('Fixture validation failed');
            foreach ($stencil->data->notifications as &$n) {
                if (is_array($n)) $n['content']=json_encode($nodes('Migration notification content 東京')); else $n->content=json_encode($nodes('Migration notification content 東京'));
            }
            unset($n);
            if (!Formie::$plugin->getStencils()->saveStencil($stencil)) throw new RuntimeException('Stencil enrichment failed.');
        }
        if ($this->version===2) Craft::$app->getProjectConfig()->saveModifiedConfigData(true); else Craft::$app->getProjectConfig()->flush();
        echo "Enriched page conditions, notification references/attachments and integration mappings.\n";
    }

    private function references($value, ?Form $form = null)
    {
        // Compare the semantic field identity across handle-to-reference migration.
        // Only references resolving to a field in this form can be canonicalized;
        // unknown references stay verbatim and wrong-field references still differ.
        // The independent behaviour checks require values and conditions to resolve.
        if ($this->version===4 && is_string($value) && $form) {
            $handles = [];
            foreach ($form->getFields() as $field) $handles[$field->reference] = $field->handle;
            return preg_replace_callback('/\{field:([^}:;|]+)([^}]*)\}/', static fn($m) => isset($handles[$m[1]]) ? '{field:'.$handles[$m[1]].$m[2].'}' : $m[0], $value);
        }
        if (is_array($value)) {
            foreach ($value as $key=>&$item) {
                if ($this->version!==4 && $key==='field' && $item==='reference') $item='{field:reference}';
                else $item=$this->references($item, $form);
            }
            unset($item); return $value;
        }
        if ($this->version!==4 && is_string($value)) return str_replace(['{field.email}','{email}','{reference}','{consent}'],['{field:email}','{field:email}','{field:reference}','{field:consent}'],$value);
        return $value;
    }

    private function assertSourceCorpus(array $snapshot): void
    {
        $first=fn($handle)=>array_values($snapshot['forms'][$handle]['submissions'])[0]['values'];
        if (($first('coverageCompound')['person']['firstName']??null)!=='Café') throw new RuntimeException('Source Name fixture did not persist.');
        if (($first('coverageCompound')['address']['city']??null)!=='Melbourne') throw new RuntimeException('Source Address fixture did not persist.');
        foreach (['eventDate' => '2024-02-29 13:45:00', 'dateInputs' => '2024-02-29 00:00:00', 'dateDropdowns' => '2024-12-31 23:59:00'] as $handle => $value) {
            if (($first('coverageCompound')[$handle] ?? null) !== $value) throw new RuntimeException('Source Date variant did not persist: ' . $handle);
        }
        if ($this->version === 3) {
            $futureYear = array_values(array_filter($snapshot['forms']['coverageCompound']['fields'], fn($field) => $field['handle'] === 'futureYear'))[0];
            $year = (int)date('Y');
            if ($futureYear['dateChildren']['year']['yearOptions'] !== ['', $year + 100, $year + 101]) throw new RuntimeException('Source custom future year range did not persist.');
        }
        if (($first('coverageNested')['groupBlock'][0]['innerText']??null)!=='Nested 東京') throw new RuntimeException('Source Group fixture did not persist.');
        if (count($first('coverageNested')['repeatBlock'])!==2) throw new RuntimeException('Source Repeater fixture did not persist both rows.');
        if (count($first('coverageAssets')['documents'])!==2) throw new RuntimeException('Source Upload fixture did not persist both relations.');
        if (!$snapshot['forms']['coverageScalars']['pageSettings'][1]['enablePageConditions']) throw new RuntimeException('Source page conditions did not persist.');
        if (count($snapshot['forms'])!==count($this->definitions()) || count($snapshot['integrations'])!==2 || count($snapshot['stencils'])!==2) throw new RuntimeException('Incomplete source corpus.');
        if ($first('coverageEncrypted')['secretText']!=='private-00123-東京' || $first('coverageEncrypted')['secretEmail']!=='private@example.test') throw new RuntimeException('Historical encrypted values did not decrypt.');
        if ($snapshot['integrations']['fixtureMailchimp']['metadata'] !== [['id' => 'fixture-list', 'name' => 'Offline fixture list', 'fields' => [['handle' => 'EMAIL', 'type' => 'string']]]]) throw new RuntimeException('Historical provider metadata did not persist.');
        if (!$snapshot['integrations']['fixtureMailchimp']['connected']) throw new RuntimeException('Historical connection state did not persist.');
    }

    private function notifications(Form $form): void
    {
        foreach ([true,false] as $enabled) {
            $n = new Notification(['formId'=>$form->id,'name'=>$enabled?'Fixture enabled':'Fixture disabled','handle'=>$enabled?'fixtureEnabled':'fixtureDisabled','enabled'=>$enabled,'subject'=>'Migration subject 東京','recipients'=>'email','to'=>'recipient@example.test','cc'=>'copy@example.test','bcc'=>'blind@example.test','replyTo'=>'reply@example.test','from'=>'sender@example.test','fromName'=>'Fixture sender','content'=>'<p>Migration notification content 東京</p>','attachFiles'=>true,'enableConditions'=>false]);
            if (!Formie::$plugin->getNotifications()->saveNotification($n)) throw new RuntimeException('Notification: '.json_encode($n->getErrors()));
        }
    }

    private function stencil(Form $form,array $pages): void
    {
        $stencilHandle = 'stencil'.ucfirst($form->handle).$form->id;
        if ($old = Formie::$plugin->getStencils()->getStencilByHandle($stencilHandle)) Formie::$plugin->getStencils()->deleteStencil($old);
        $s = new Stencil(['name'=>'Stencil '.$form->handle,'handle'=>'stencil'.ucfirst($form->handle).$form->id]);
        if ($this->version === 2) $s->data = new StencilData(['pages'=>$pages,'settings'=>($this->version===2 ? $form->settings : $form->getSettings())->toArray(),'notifications'=>array_map(fn($n)=>$n->toArray(),$form->getNotifications())]);
        else $s->data->populateFormData($form);
        if (!Formie::$plugin->getStencils()->saveStencil($s)) throw new RuntimeException('Stencil: '.json_encode($s->getErrors()));
        $this->map['stencils'][$s->handle] = $form->handle;
    }

    private function integrations(): void
    {
        foreach ([['Mailchimp','emailmarketing',['apiKey'=>'fixture-not-a-real-key-us1','appendTags'=>true,'useDoubleOptIn'=>true]],['HubSpot','crm',['accessToken'=>'fixture-not-a-real-token','mapToContact'=>true]]] as [$name,$group,$settings]) {
            if ($old = Formie::$plugin->getIntegrations()->getIntegrationByHandle('fixture'.$name)) Formie::$plugin->getIntegrations()->deleteIntegration($old);
            $class = 'verbb\\formie\\integrations\\'.$group.'\\'.$name;
            $i = new $class(array_merge(['name'=>'Fixture '.$name,'handle'=>'fixture'.$name,'enabled'=>false],$settings));
            if ($name === 'Mailchimp') {
                $i->cache['settings'] = $this->offlineMetadata();
                $this->map['integrationMetadata'] = true;
            }
            if (!Formie::$plugin->getIntegrations()->saveIntegration($i)) throw new RuntimeException('Integration: '.json_encode($i->getErrors()));
            if ($name === 'Mailchimp') {
                // Provider discovery writes this cache separately from project-config settings.
                // Use an offline provider response; no live account or HTTP request is involved.
                Craft::$app->getDb()->createCommand()->update('{{%formie_integrations}}', ['cache' => json_encode(['connection' => 'success', 'settings' => $this->offlineMetadata()])], ['id' => $i->id])->execute();
            }
            $this->map['integrations'][$i->handle] = array_keys($settings);
        }
    }

    private function snapshot(): array
    {
        $result = ['assets'=>[],'forms'=>[],'stencils'=>[],'integrations'=>[],'pluginSettings'=>[]];
        foreach ($this->map['forms'] as $handle=>$entry) {
            $form = Form::find()->uid($entry['uid'])->status(null)->one();
            if (!$form) throw new RuntimeException('Missing form '.$handle);
            $fields = $this->fields($form);
            $data = ['id'=>(int)$form->id,'uid'=>$form->uid,'title'=>$form->title,'handle'=>$form->handle,'pages'=>array_map(fn($p)=>$this->version===2?$p->name:$p->label,$form->getPages()),'fields'=>[],'settings'=>[],'notifications'=>[],'submissions'=>[]];
            foreach ($entry['definitions'] as $page) foreach ($page as $definition) {
                $field = $fields[$definition['handle']] ?? null;
                if (!$field) throw new RuntimeException('Missing field '.$handle.'.'.$definition['handle'].'; actual '.implode(',',array_keys($fields)));
                $data['fields'][] = $this->fieldSnapshot($field,$definition);
            }
            foreach (['displayFormTitle','displayPageTabs','displayPageProgress','scrollToTop','submitMethod','submitAction','submitActionMessage','validationOnFocus','errorMessage'] as $key) $data['settings'][$key] = in_array($key,['submitActionMessage','errorMessage'],true) ? $this->rich(($this->version===2 ? $form->settings : $form->getSettings())->$key) : ($this->version===2 ? $form->settings : $form->getSettings())->$key;
            $data['settings']['integrations'] = $this->references(($this->version===2 ? $form->settings : $form->getSettings())->integrations, $form);
            $data['pageSettings'] = array_map(fn($p)=>$this->references(array_intersect_key($this->pageSettings($p),array_flip(['submitButtonLabel','backButtonLabel','enablePageConditions','pageConditions'])), $form),$form->getPages());
            foreach ($form->getNotifications() as $n) $data['notifications'][$n->handle] = $this->notificationSnapshot($n);
            ksort($data['notifications']);
            foreach ($entry['submissions'] as $uid) {
                $sub = Submission::find()->uid($uid)->status(null)->isSpam(null)->one();
                if (!$sub) throw new RuntimeException('Missing submission '.$uid);
                $values = [];
                foreach ($fields as $field) $values[$field->handle] = $this->value($field,$sub);
                $data['submissions'][$uid] = ['id'=>(int)$sub->id,'formId'=>(int)$sub->formId,'title'=>$sub->title,'isSpam'=>(bool)$sub->isSpam,'values'=>$values];
            }
            $result['forms'][$handle] = $data;
        }
        foreach ($this->map['assets'] as $uid=>$hash) {
            $asset=\craft\elements\Asset::find()->uid($uid)->status(null)->one();
            if (!$asset) throw new RuntimeException('Missing asset '.$uid);
            $stream=$asset->getStream(); $content=stream_get_contents($stream); fclose($stream);
            $result['assets'][$uid]=['id'=>(int)$asset->id,'filename'=>$asset->filename,'sha256'=>hash('sha256',$content)];
            if ($result['assets'][$uid]['sha256']!==$hash) throw new RuntimeException('Asset bytes changed '.$uid);
        }
        foreach ($this->map['pluginSettings'] as $key) $result['pluginSettings'][$key] = Formie::$plugin->getSettings()->$key;
        foreach ($this->map['integrations'] as $handle=>$keys) {
            $i = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);
            if (!$i) throw new RuntimeException('Missing integration '.$handle);
            $result['integrations'][$handle] = ['name'=>$i->name,'handle'=>$i->handle,'uid'=>$i->uid,'enabled'=>$i->getEnabled(false)];
            foreach ($keys as $key) $result['integrations'][$handle][$key] = $i->$key;
            if ($handle === 'fixtureMailchimp' && !empty($this->map['integrationMetadata'])) {
                $result['integrations'][$handle]['connected'] = $i->getIsConnected();
                $lists = $this->version === 4 ? $i->getConfigValue('lists') : $i->getFormSettingValue('lists');
                $result['integrations'][$handle]['metadata'] = array_map(fn($list) => [
                    'id' => $list->id, 'name' => $list->name,
                    'fields' => array_map(fn($field) => ['handle' => $field->handle, 'type' => $field->type], $list->fields),
                ], $lists);
            }
        }
        foreach ($this->map['stencils'] as $handle=>$formHandle) {
            $s = Formie::$plugin->getStencils()->getStencilByHandle($handle);
            if (!$s) throw new RuntimeException('Missing stencil '.$handle);
            $result['stencils'][$handle] = ['name'=>$s->name,'handle'=>$s->handle,'uid'=>$s->uid,'settings'=>array_intersect_key($s->data->settings->toArray(),$result['forms'][$formHandle]['settings']),'notifications'=>array_map(fn($n)=>$this->notificationSnapshot(is_array($n)?new Notification($n):$n),$s->getNotifications()),'layout'=>$this->stencilTree($this->version===2?$s->data->pages:$s->getFormLayout()->getFormBuilderConfig())];
            // Legacy accessors remain supported; toArray intentionally exposes only canonical keys.
            if ($this->version === 4) {
                foreach (['submitAction', 'submitActionMessage'] as $key) {
                    $result['stencils'][$handle]['settings'][$key] = $s->data->settings->$key;
                }
            }
        }
        foreach ($result['stencils'] as &$stencil) foreach (['submitActionMessage','errorMessage'] as $key) $stencil['settings'][$key] = $this->rich($stencil['settings'][$key]);
        unset($stencil);
        if (isset($this->map['forms']['coverageEncrypted']) && $this->version >= 3) {
            $uid = $this->map['forms']['coverageEncrypted']['submissions'][0];
            $id = (new \craft\db\Query())->select('id')->from('{{%elements}}')->where(['uid' => $uid])->scalar();
            $stored = (new \craft\db\Query())->select('content')->from('{{%formie_submissions}}')->where(['id' => $id])->scalar();
            foreach (['private-00123', 'private@example.test', '+61412345678'] as $plaintext) {
                if (str_contains($stored, $plaintext)) throw new RuntimeException('Encrypted fixture leaked plaintext at rest.');
            }
            if (!str_contains($stored, 'base64:') && !str_contains($stored, '__formie_enc')) throw new RuntimeException('Encrypted fixture lacks ciphertext markers.');
            $result['encryptedAtRest'] = true;
        }
        return $result;
    }

    private function stencilTree(array $pages): array
    {
        $out=[];
        foreach ($pages as $page) {
            $rows=[];
            foreach ($page['rows']??[] as $row) {
                $fields=[];
                foreach ($row['fields']??[] as $field) {
                    $settings=$field['settings']??$field;
                    $type=substr($field['type'],strrpos($field['type'],'\\')+1);
                    $node=['type'=>$type,'handle'=>$field['handle']??$settings['handle']??null,'label'=>$field['label']??$settings['label']??null,'required'=>(bool)($settings['required']??false)];
                    if (isset($settings['options'])) $node['options']=array_map(fn($option)=>['label'=>$option['label'],'value'=>$option['value'],'optgroup'=>$option['optgroup']??false],$settings['options']);
                    $children=$field['rows']??$settings['rows']??[];
                    if (in_array($type,['Group','Repeater'],true)) $node['children']=$this->stencilTree([['label'=>'Nested','rows'=>$children]]);
                    $fields[]=$node;
                }
                $rows[]=$fields;
            }
            $out[]=['label'=>$page['label'],'rows'=>$rows];
        }
        return $out;
    }

    private function pageSettings($page): array { return is_array($page->settings)?$page->settings:$page->settings->toArray(); }

    private function rich($value): string
    {
        $html = trim(\verbb\formie\helpers\RichTextHelper::getHtmlContent($value, null, false));
        // A paragraph wrapper is added when plain text becomes editor document nodes.
        return preg_replace('/^<p>([^<]*)<\/p>$/u', '$1', $html);
    }

    private function notificationSnapshot($n): array
    {
        $result = [];
        $form = $this->version===4 && $n->formId ? Form::find()->id($n->formId)->status(null)->one() : null;
        foreach (['name','handle','enabled','subject','recipients','to','cc','bcc','replyTo','from','fromName','content','attachFiles','attachAssets','enableConditions','conditions'] as $key) $result[$key] = $key==='content'?$this->rich($n->$key):$this->references($n->$key, $form);
        return $result;
    }

    private function fields($owner): array
    {
        $fields = $this->version===2 ? $owner->getCustomFields() : $owner->getFields();
        $out = [];
        foreach ($fields as $field) $out[$field->handle] = $field;
        return $out;
    }

    private function fieldSnapshot($field,array $definition): array
    {
        $out = ['type'=>(new \ReflectionClass($field))->getShortName(),'handle'=>$field->handle,'label'=>$this->version===2?$field->name:$field->label,'required'=>(bool)$field->required,'settings'=>[],'children'=>[]];
        foreach ($definition['settings'] as $key=>$_) {
            if ($key === 'required') continue;
            $out['settings'][$key] = in_array($key,['instructions','description'],true) ? $this->rich($field->$key) : $this->plain($field->$key);
        }
        foreach ($definition['children'] as $child) $out['children'][] = $this->fieldSnapshot($this->fields($field)[$child['handle']],$child);
        if ($definition['type'] === 'Date' && $this->map['sourceVersion'] === 3 && ($this->map['dateChildSettings'] ?? false)) {
            $out['dateChildren'] = array_map(function($child) {
                $config = ['handle' => $child->handle, 'uid' => $child->uid, 'label' => $child->label, 'enabled' => (bool)$child->enabled];
                if ($child instanceof \verbb\formie\fields\subfields\DateYearDropdown) $config['yearOptions'] = array_column($child->options(), 'value');
                return $config;
            }, $this->fields($field));
        }
        if ($this->map['sourceVersion'] === 3) $out['uid'] = $field->uid;
        return $out;
    }

    private function value($field,$owner): mixed
    {
        $type = (new \ReflectionClass($field))->getShortName();
        if (in_array($type,['Heading','Html','Section','Summary'],true)) return null;
        $value = is_array($owner) ? ($owner[$field->handle] ?? null) : $owner->getFieldValue($field->handle);
        if (in_array($type,['Group','Repeater'],true)) {
            $rows = is_object($value) && method_exists($value,'all') ? $value->all() : $value;
            if ($rows === null) return [];
            if ($type === 'Group' && is_array($rows) && !array_is_list($rows)) $rows = [$rows];
            if (is_object($rows) && method_exists($rows,'getFieldValue')) $rows = [$rows];
            $out=[];
            foreach ($rows as $row) {
                $one=[];
                foreach ($this->fields($field) as $child) $one[$child->handle]=$this->value($child,$row);
                $out[]=$one;
            }
            return $out;
        }
        if (in_array($type,['FileUpload','Users'],true)) return array_map('intval',$value->ids());
        // Compare readable content, not randomized ciphertext. Independently inspect storage below.
        $encrypted = $field->enableContentEncryption;
        try {
            $field->enableContentEncryption = false;
            $serialized = $field->serializeValue($value,is_object($owner) ? $owner : null);
        } finally {
            $field->enableContentEncryption = $encrypted;
        }
        // Storage changes from JSON strings / UID keys to native content models.
        if (in_array($type,['Name','Address','Phone'],true) && is_string($serialized) && str_starts_with($serialized,'{')) $serialized = json_decode($serialized,true,512,JSON_THROW_ON_ERROR);
        if ($type === 'Date') {
            if (!$serialized) return null;
            if (is_array($serialized)) return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $serialized['year']??0,$serialized['month']??0,$serialized['day']??0,$serialized['hour']??0,$serialized['minute']??0,$serialized['second']??0);
            return (new \DateTime((string)$serialized))->format('Y-m-d H:i:s');
        }
        // Public empty collection semantics, independent of the JSON storage sentinel.
        if ($type === 'Checkboxes' && $serialized === null) return [];
        if ($type === 'Name' && !$field->useMultipleFields && $serialized === null) return '';
        if ($type === 'Table' && ($serialized === null || $serialized === [] || $serialized === [['col1'=>null,'col2'=>null]])) return [];
        if (in_array($type,['Name','Address'],true) && is_array($serialized)) {
            if ($this->version !== 2) { $mapped=[]; foreach ($this->fields($field) as $child) $mapped[$child->handle]=$serialized[$child->uid]??null; $serialized=$mapped; }
            $keys=$type==='Name'?['firstName','middleName','lastName']:['address1','address2','address3','city','state','zip','country'];
            $mapped=[]; foreach ($keys as $key) $mapped[$key]=$serialized[$key]??null; return $mapped;
        }
        return $this->plain($serialized);
    }

    private function plain($value): mixed
    {
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d H:i:s');
        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) return $this->plain($value->jsonSerialize());
            if (method_exists($value,'toArray')) return $this->plain($value->toArray());
            throw new RuntimeException('Unmapped value object '.get_class($value));
        }
        if (is_array($value)) return array_map(fn($v)=>$this->plain($v),$value);
        return $value;
    }

    private function expectedForTarget(): array
    {
        $expected = $this->map['expected'];
        // These are explicit representation changes, applied to the historical oracle only.
        // Never normalize the observed target to hide missing data or broken references.
        foreach ($expected['forms'] as $handle => &$form) {
            if (isset($form['settings']['integrations']['fixtureMailchimp']['fieldMapping']['EMAIL'])) {
                $value = $form['settings']['integrations']['fixtureMailchimp']['fieldMapping']['EMAIL'];
                $form['settings']['integrations']['fixtureMailchimp']['fieldMapping']['EMAIL'] = ['kind' => 'reference', 'value' => $value];
            }
            foreach ($form['notifications'] as &$notification) {
                $notification['conditions'] = $this->expectedConditions($notification['conditions']);
            }
            unset($notification);
            foreach ($form['pageSettings'] as &$page) {
                if (isset($page['pageConditions'])) $page['pageConditions'] = $this->expectedConditions($page['pageConditions']);
            }
            unset($page);
            foreach ($form['submissions'] as &$submission) {
                foreach ($this->map['forms'][$handle]['definitions'] as $page) {
                    foreach ($page as $field) {
                        $key = $field['handle'];
                        $submission['values'][$key] = $this->expectedValue($field, $submission['values'][$key]);
                    }
                }
            }
            unset($submission);
        }
        unset($form);
        return $expected;
    }

    private function expectedConditions(mixed $conditions): mixed
    {
        if (!is_array($conditions) || !$conditions) return $conditions;
        $conditions['version'] = 1;
        foreach ($conditions['conditions'] ?? [] as $index => $condition) {
            $conditions['conditions'][$index]['legacyForward'] = true;
        }
        return $conditions;
    }

    private function expectedValue(array $field, mixed $value): mixed
    {
        if (in_array($field['type'], ['SingleLineText', 'MultiLineText'], true) && $value === null) return '';
        if (in_array($field['type'], ['Name', 'Address'], true) && is_array($value)) {
            foreach ($value as $key => &$part) {
                if ($key !== 'country' && $part === null) $part = '';
            }
            unset($part);
            return $value;
        }
        if ($field['type'] === 'Phone' && is_array($value)) {
            $number = $value['number'] ?? '';
            // The authored populated number is Australian; Formie 4 derives its region.
            $country = $value['country'] ?? ($number === '+61412345678' ? 'AU' : null);
            return ['number' => $number, 'country' => $country];
        }
        if (in_array($field['type'], ['Group', 'Repeater'], true) && is_array($value)) {
            foreach ($value as &$row) {
                foreach ($field['children'] as $child) {
                    $row[$child['handle']] = $this->expectedValue($child, $row[$child['handle']]);
                }
            }
            unset($row);
        }
        return $value;
    }

    private function compare(string $path,$expected,$actual): void
    {
        if (is_array($expected) && is_array($actual)) {
            $expectedKeys = array_keys($expected); $actualKeys = array_keys($actual);
            if (!array_is_list($expected) && !array_is_list($actual)) { sort($expectedKeys); sort($actualKeys); }
            $this->checks[]=['name'=>$path.' keys','expected'=>$expectedKeys,'actual'=>$actualKeys,'pass'=>$expectedKeys===$actualKeys];
            foreach ($expected as $key=>$value) $this->compare($path.'.'.$key,$value,$actual[$key]??null);
        } else $this->checks[]=['name'=>$path,'expected'=>$expected,'actual'=>$actual,'pass'=>$expected===$actual];
    }
    private function read(string $path): array { return json_decode(file_get_contents($this->root.'/'.$path),true,512,JSON_THROW_ON_ERROR); }
    private function write(string $path,array $data): void { file_put_contents($this->root.'/'.$path,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n"); }
}
