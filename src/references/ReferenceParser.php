<?php
namespace verbb\formie\references;

use verbb\formie\models\ReferenceExpression;

final class ReferenceParser
{
    // Static Methods
    // =========================================================================

    public static function parse(string $raw): ReferenceExpression
    {
        $text = trim($raw);

        if (!preg_match('/^\{([^{}]+)\}$/D', $text, $match)) {
            return new ReferenceExpression(raw: $raw, diagnostic: 'invalidSyntax');
        }

        $body = $match[1];

        if (preg_match('/%(?![0-9a-fA-F]{2})/', $body) || !mb_check_encoding(rawurldecode($body), 'UTF-8')) {
            return new ReferenceExpression(raw: $raw, diagnostic: 'invalidEncoding');
        }

        // Stable Formie 3's object-template notation is only a field path, never Twig.
        if (str_starts_with($body, 'field.')) {
            $body = 'field:' . substr($body, 6);
        }
        $aliases = self::legacyAliases();
        [$body, $default] = array_pad(explode('|', $body, 2), 2, '');
        [$sourceAlias, $modifiers] = array_pad(explode(';', $body, 2), 2, '');
        $body = ($aliases[$sourceAlias] ?? $sourceAlias) . ($modifiers !== '' ? ';' . $modifiers : '');
        $parts = explode(';', $body);
        $source = array_shift($parts);
        $metadata = [];

        foreach ($parts as $part) {
            if (!preg_match('/^([a-zA-Z][a-zA-Z0-9_]*)=(.*)$/D', $part, $entry) || isset($metadata[$entry[1]])) {
                return new ReferenceExpression(raw: $raw, diagnostic: 'invalidMetadata');
            }
            $metadata[$entry[1]] = rawurldecode($entry[2]);
        }
        $version = $metadata['v'] ?? '1';

        if ($version !== '1') {
            return new ReferenceExpression(raw: $raw, diagnostic: 'unsupportedVersion');
        }
        unset($metadata['v']);
        [$target, $identifier] = array_pad(explode(':', $source, 2), 2, '');

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/D', $target)) {
            return new ReferenceExpression(raw: $raw, diagnostic: 'invalidSource');
        }
        $selector = '';

        if ($target === 'field') {
            [$identifier, $selector] = array_pad(explode(':', $identifier, 2), 2, '');
        }
        $bodyless = in_array($target, ['timestamp', 'allFields', 'allContentFields', 'allVisibleFields'], true);

        if ((!$bodyless && $identifier === '') || preg_match('/[\s{}]/', $identifier . $selector)) {
            return new ReferenceExpression(raw: $raw, diagnostic: 'invalidIdentifier');
        }
        $transform = $metadata['transform'] ?? '';
        unset($metadata['transform']);

        return new ReferenceExpression($raw, $target, rawurldecode($identifier), rawurldecode($selector), rawurldecode($default), $transform, $metadata, true);
    }

    public static function serialize(ReferenceExpression $expression): string
    {
        if (!$expression->isValid || $expression->version !== 1) {
            throw new \InvalidArgumentException('Cannot serialize an invalid reference expression.');
        }
        $encode = static fn(string $value): string => strtr(rawurlencode($value), ['%2F' => '/', '%2E' => '.']);
        $body = $expression->target;

        if ($expression->identifier !== '') {
            $body .= ':' . $encode($expression->identifier);
        }

        if ($expression->selector !== '') {
            $body .= ':' . str_replace('%3A', ':', $encode($expression->selector));
        }

        if ($expression->transformerId !== '') {
            $body .= ';transform=' . rawurlencode($expression->transformerId);
        }

        foreach ($expression->transformerParams as $key => $value) {
            $body .= ';' . $key . '=' . rawurlencode((string)$value);
        }

        if ($expression->default !== '') {
            $body .= '|' . rawurlencode($expression->default);
        }
        return '{' . $body . '}';
    }

    public static function legacyAliases(): array
    {
        $aliases = ['username' => 'user:name'];

        foreach (['form' => ['name', 'handle'], 'submission' => ['id', 'uid', 'title', 'url', 'date', 'site', 'status'], 'site' => ['id', 'name', 'handle', 'url', 'language']] as $target => $names) {
            foreach ($names as $name) {
                $aliases[$target . '.' . $name] = $target . ':' . $name;
            }
        }

        foreach (['form' => ['Name', 'Handle'], 'submission' => ['Title', 'Url', 'Id', 'Uid', 'Date', 'Site', 'Status'], 'system' => ['Name', 'Email', 'ReplyTo'], 'site' => ['Name', 'Handle', 'Url', 'Id', 'Language'], 'user' => ['Ip', 'Id', 'Email', 'FullName', 'FirstName', 'LastName']] as $target => $names) {
            foreach ($names as $name) {
                $aliases[$target . $name] = $target . ':' . lcfirst($name);
            }
        }

        foreach (['dateUs' => 'm/d/Y', 'dateInt' => 'd/m/Y', 'time12' => 'h:i a', 'time24' => 'H:i'] as $name => $pattern) {
            $aliases[$name] = 'timestamp;transform=format;preset=custom;pattern=' . rawurlencode($pattern);
        }
        return $aliases;
    }
}
