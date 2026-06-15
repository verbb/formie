<?php
namespace verbb\formie\helpers;

class SuspiciousTextHelper
{
    // Constants
    // =========================================================================

    private const SUSPICIOUS_SCORE_THRESHOLD = 2;

    private const KEYBOARD_WHOLE_WORDS = [
        'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio', 'iop',
        'asd', 'sdf', 'dfg', 'fgh', 'ghj', 'hjk', 'jkl',
        'zxc', 'xcv', 'cvb', 'vbn', 'bnm',
        'qwer', 'asdf', 'zxcv',
    ];

    private const KEYBOARD_SUBSTRINGS = [
        'qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm',
    ];

    private const BUILTIN_ALLOWED_TERMS = [
        'a', 'i',
        'am', 'an', 'as', 'at', 'be', 'by', 'do', 'go', 'he', 'if', 'in', 'is', 'it', 'me', 'my',
        'no', 'of', 'on', 'or', 'so', 'to', 'up', 'us', 'we',
        'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one',
        'our', 'out', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see',
        'two', 'way', 'who', 'yes', 'yet', 'she', 'too', 'use', 'any', 'day', 'did', 'let', 'put',
        'say', 'man', 'men', 'run', 'set', 'try', 'why', 'own', 'per', 'via',
    ];


    // Public Methods
    // =========================================================================

    public static function analyze(string $value, array $allowedTerms = []): array
    {
        $allowed = self::_normalizeAllowedTerms(array_merge(self::BUILTIN_ALLOWED_TERMS, $allowedTerms));
        $value = trim($value);

        if ($value === '') {
            return self::_emptyResult();
        }

        $cleaned = preg_replace('~https?://\S+|www\.\S+|\S+@\S+~iu', ' ', $value) ?? $value;
        $tokens = array_values(array_filter(preg_split('/\s+/u', trim($cleaned)) ?: []));
        $score = 0;
        $hits = [];
        $shortJunkCount = 0;

        foreach ($tokens as $token) {
            if (self::_shouldSkipToken($token)) {
                continue;
            }

            $letters = self::_lettersOnly($token);

            if ($letters === '' || !preg_match('/^\p{Latin}+$/u', $letters)) {
                continue;
            }

            if (self::_isAllowed($letters, $allowed)) {
                continue;
            }

            foreach (self::_analyzeWord($letters) as $hit) {
                $score += $hit['weight'];
                $hits[] = $hit;

                if ($hit['type'] === 'short_junk') {
                    $shortJunkCount++;
                }
            }
        }

        if ($shortJunkCount >= 3) {
            $score += 2;
            $hits[] = [
                'type' => 'short_junk_cluster',
                'weight' => 2,
                'token' => null,
            ];
        }

        if (count($tokens) === 1) {
            $letters = self::_lettersOnly($tokens[0]);

            if ($letters !== '' && !self::_isAllowed($letters, $allowed)) {
                foreach (self::_analyzeSingleTokenField($letters) as $hit) {
                    $score += $hit['weight'];
                    $hits[] = $hit;
                }
            }
        }

        return [
            'is_suspicious' => $score >= self::SUSPICIOUS_SCORE_THRESHOLD,
            'score' => $score,
            'hits' => $hits,
        ];
    }


    // Private Methods
    // =========================================================================

    private static function _emptyResult(): array
    {
        return [
            'is_suspicious' => false,
            'score' => 0,
            'hits' => [],
        ];
    }

    private static function _normalizeAllowedTerms(array $terms): array
    {
        $normalized = [];

        foreach ($terms as $term) {
            $term = self::_normalizeTerm((string)$term);

            if ($term !== '') {
                $normalized[] = $term;
            }
        }

        return array_values(array_unique($normalized));
    }

    private static function _normalizeTerm(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z]/i', '', $value) ?? '');
    }

    private static function _lettersOnly(string $value): string
    {
        return preg_replace('/[^[:alpha:]]/u', '', $value) ?? '';
    }

    private static function _shouldSkipToken(string $token): bool
    {
        if ($token === '') {
            return true;
        }

        if (preg_match('~^(https?://|www\.)~i', $token)) {
            return true;
        }

        if (filter_var($token, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return (bool)preg_match('/^(?:http|https|www)$/i', $token);
    }

    private static function _isAllowed(string $letters, array $allowed): bool
    {
        return in_array(self::_normalizeTerm($letters), $allowed, true);
    }

    private static function _analyzeWord(string $letters): array
    {
        $length = mb_strlen($letters);
        $lower = mb_strtolower($letters);
        $uniqueCount = count(array_unique(mb_str_split($lower)));
        $hits = [];

        if ($length >= 2 && $length <= 4) {
            $shortHit = self::_analyzeShortWord($lower, $uniqueCount);

            if ($shortHit) {
                $hits[] = $shortHit;
            }

            return $hits;
        }

        if ($length >= 5) {
            if (in_array($lower, self::KEYBOARD_WHOLE_WORDS, true)) {
                $hits[] = self::_hit('keyboard_word', 2, $letters);
            }

            foreach (self::KEYBOARD_SUBSTRINGS as $sequence) {
                if (str_contains($lower, $sequence)) {
                    $hits[] = self::_hit('keyboard_sequence', 2, $letters);
                    break;
                }
            }

            if ($length >= 6 && !preg_match('/[aeiou]/i', $letters)) {
                $hits[] = self::_hit('no_vowels', 1, $letters);
            }

            if ($length >= 8 && $uniqueCount <= 3) {
                $hits[] = self::_hit('low_variety', 2, $letters);
            }

            if ($length >= 5 && preg_match('/^(.{1,3})\1{2,}$/u', $lower)) {
                $hits[] = self::_hit('repeated_motif', 2, $letters);
            }

            if ($length >= 6 && preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/iu', $lower)) {
                $hits[] = self::_hit('consonant_run', 1, $letters);
            }
        }

        return $hits;
    }

    private static function _analyzeShortWord(string $lower, int $uniqueCount): ?array
    {
        $length = mb_strlen($lower);
        $hasVowel = (bool)preg_match('/[aeiou]/i', $lower);

        if ($length === 2) {
            if (!$hasVowel && $uniqueCount === 1) {
                return self::_hit('short_junk', 1, $lower);
            }

            return null;
        }

        if (in_array($lower, self::KEYBOARD_WHOLE_WORDS, true)) {
            return self::_hit('keyboard_word', 2, $lower);
        }

        if ($length === 3 && $uniqueCount <= 2) {
            return self::_hit('short_junk', 1, $lower);
        }

        if ($length === 4 && $uniqueCount <= 2 && !$hasVowel) {
            return self::_hit('short_junk', 1, $lower);
        }

        return null;
    }

    private static function _analyzeSingleTokenField(string $letters): array
    {
        $lower = mb_strtolower($letters);
        $length = mb_strlen($lower);
        $hits = [];

        if ($length >= 8 && preg_match('/^(?:[bcdfghjklmnpqrstvwxyz][aeiouy]){4,}/iu', $lower)) {
            $hits[] = self::_hit('random_cv_pattern', 2, $letters);
        }

        if ($length >= 10 && count(array_unique(mb_str_split($lower))) <= 6) {
            foreach (self::KEYBOARD_SUBSTRINGS as $sequence) {
                if (str_contains($lower, $sequence)) {
                    $hits[] = self::_hit('keyboard_sequence', 2, $letters);
                    break;
                }
            }
        }

        return $hits;
    }

    private static function _hit(string $type, int $weight, string $token): array
    {
        return [
            'type' => $type,
            'weight' => $weight,
            'token' => $token,
        ];
    }
}
