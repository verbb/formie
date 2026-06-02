<?php

declare(strict_types=1);

namespace Tests\Support;

final class MaliciousPayloads
{
    public static function twigProbe(): string
    {
        return "{{ 'TWIG_SENTINEL' }}{% set marker = 'CONTROL_SENTINEL' %}";
    }

    public static function twigMathProbe(): string
    {
        return '{{ 7 * 7 }}';
    }

    public static function storedXssProbe(): string
    {
        return '<script>alert("xss")</script><img src=x onerror=alert("xss")><p>safe-text</p>';
    }

    public static function attributeBreakoutProbe(): string
    {
        return '" autofocus onfocus="alert(\'xss\')" data-breakout="1';
    }

    public static function javascriptProtocolProbe(): string
    {
        return 'javascript:alert("xss")';
    }

    public static function encodedJavascriptProtocolProbe(): string
    {
        return 'java&#x73;cript:alert("xss")';
    }

    public static function dataUrlProbe(): string
    {
        return 'data:text/html;base64,PHNjcmlwdD5hbGVydCgieHNzIik8L3NjcmlwdD4=';
    }

    public static function controlCharacterTextProbe(): string
    {
        return "safe\x00text\x1Fwith-controls";
    }

    public static function unknownFieldHandle(): string
    {
        return '__proto__';
    }
}
