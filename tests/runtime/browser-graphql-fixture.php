<?php
$gql = Craft::$app->getGql();
$schema = $gql->getPublicSchema();
$schema->scope = array_merge(['formieForms.all:read', 'formieSubmissions.all:create'], array_map(fn($site) => 'sites.' . $site->uid . ':read', Craft::$app->getSites()->getAllSites()));
if (!$gql->saveSchema($schema)) {
    throw new RuntimeException('Cannot save browser GraphQL schema fixture.');
}
$token = $gql->getPublicToken();
$token->enabled = true;
if (!$gql->saveToken($token)) {
    throw new RuntimeException('Cannot enable browser GraphQL public fixture token.');
}
Craft::$app->getProjectConfig()->saveModifiedConfigData();
