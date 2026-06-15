<?php
namespace verbb\formie\helpers;

class SuspiciousTextHelper
{
    private static array $whitelist = [
        // 1 letter
        'a', 'i',

        // 2 letters - common short words
        'am', 'an', 'as', 'at', 'be', 'by', 'do', 'go', 'he', 'if', 'in', 'is', 'it', 'me', 'my',
        'no', 'of', 'on', 'or', 'so', 'to', 'up', 'us', 'we',

        // us states & territories
        'al', 'ak', 'az', 'ar', 'ca', 'co', 'ct', 'de', 'fl', 'ga', 'hi', 'id', 'il', 'in', 'ia', 'ks', 'ky', 'la', 'me', 'md',
        'ma', 'mi', 'mn', 'ms', 'mo', 'mt', 'ne', 'nv', 'nh', 'nj', 'nm', 'ny', 'nyc', 'nc', 'nd', 'oh', 'ok', 'or', 'pa', 'ri', 'sc',
        'sd', 'tn', 'tx', 'ut', 'vt', 'va', 'wa', 'wv', 'wi', 'wy', 'dc', 'as', 'gu', 'mp', 'pr', 'vi', 'um',
        'alabama', 'alaska', 'arizona', 'arkansas', 'california', 'colorado', 'connecticut',
        'delaware', 'florida', 'georgia', 'hawaii', 'idaho', 'illinois', 'indiana', 'iowa',
        'kansas', 'kentucky', 'louisiana', 'maine', 'maryland', 'massachusetts', 'michigan',
        'minnesota', 'mississippi', 'missouri', 'montana', 'nebraska', 'nevada',
        'newhampshire', 'newjersey', 'newmexico', 'newyork', 'northcarolina', 'northdakota',
        'ohio', 'oklahoma', 'oregon', 'pennsylvania', 'rhodeisland', 'southcarolina',
        'southdakota', 'tennessee', 'texas', 'utah', 'vermont', 'virginia', 'washington',
        'westvirginia', 'wisconsin', 'wyoming',
        'districtcolumbia', 'districtofcolumbia',
        'americanSamoa', 'guam', 'northernmarianaislands', 'puertorico', 'virginislands',

        // canadian provinces & territories
        'ab', 'bc', 'mb', 'nb', 'nl', 'ns', 'nt', 'nu', 'on', 'pe', 'qc', 'sk', 'yt',
        'alberta', 'britishcolumbia', 'manitoba', 'newbrunswick', 'newfoundlandandlabrador', 'novascotia',
        'ontario', 'princeedwardisland', 'quebec', 'saskatchewan', 'northwestterritories', 'nunavut', 'yukon',

        // 2–3 letters - australian state & territories
        'nsw', 'qld', 'sa', 'tas', 'vic', 'wa', 'act', 'nt',
        'newsouthwales', 'queensland', 'southaustralia', 'tasmania', 'victoria', 'westernaustralia',
        'australiancapitalterritory', 'northernterritory',

        // countries
        'usa', 'uk', 'uae', 'india', 'canada', 'ireland', 'scotland', 'wales', 'england',
        'france', 'germany', 'spain', 'italy', 'china', 'japan', 'korea', 'mexico',
        'brazil', 'argentina', 'chile', 'peru', 'colombia', 'australia', 'newzealand',
        'sweden', 'norway', 'denmark', 'finland', 'poland', 'switzerland', 'austria',
        'belgium', 'netherlands',

        // 3 letters
        'and', 'any', 'are', 'can', 'did', 'for', 'get', 'had', 'has', 'her', 'him', 'his', 'how', 'its', 'let', 'man', 'may',
        'new', 'not', 'six', 'now', 'off', 'old', 'one', 'our', 'out', 'put', 'see', 'set', 'she', 'the', 'too', 'two', 'use',
        'war', 'was', 'way', 'who', 'why', 'you', 'non',

        // 4+ letters – existing common words
        'also', 'able', 'back', 'best', 'both', 'call', 'come', 'done', 'door', 'down', 'each', 'even', 'ever', 'from',
        'good', 'have', 'here', 'high', 'into', 'just', 'keep', 'kind', 'know', 'lake', 'last', 'left', 'like', 'long',
        'look', 'made', 'make', 'many', 'more', 'most', 'much', 'near', 'need', 'once', 'only', 'park', 'part', 'past',
        'pool', 'rest', 'road', 'same', 'some', 'soon', 'take', 'tell', 'test', 'than', 'that', 'them', 'then', 'they',
        'this', 'time', 'turn', 'walk', 'well', 'went', 'what', 'when', 'where', 'with', 'work', 'your', 'four', 'five',
        'three',

        // days of the week + abbreviations
        'mon', 'tue', 'tues', 'wed', 'thu', 'thur', 'fri', 'sat', 'sun',
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',

        // months + abbreviations
        'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec',
        'january', 'february', 'march', 'april', 'june', 'july', 'august', 'september', 'october', 'november', 'december',

        // common languages
        'english', 'french', 'spanish', 'german', 'italian', 'portuguese', 'dutch',
        'chinese', 'japanese', 'korean', 'arabic', 'hindi', 'urdu', 'punjabi', 'bengali',
        'tagalog', 'vietnamese', 'swedish', 'norwegian', 'polish', 'russian',

        // currency codes & names
        'usd', 'eur', 'gbp', 'cad', 'aud', 'nzd', 'jpy', 'cny', 'inr', 'chf', 'sek', 'nok',
        'dollar', 'euro', 'pound', 'yen', 'rupee', 'franc', 'peso', 'lira',

        // number words
        'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen',
        'eighteen', 'nineteen', 'twenty',
        'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety',
        'hundred', 'thousand',

        // common given names (male-ish)
        'john', 'james', 'robert', 'michael', 'william', 'david', 'richard', 'joseph', 'thomas', 'charles',
        'christopher', 'daniel', 'matthew', 'anthony', 'mark', 'donald', 'steven', 'paul', 'andrew', 'joshua',
        'kenneth', 'kevin', 'brian', 'george', 'timothy', 'ronald', 'edward', 'jason', 'jeffrey', 'ryan', 'jacob',
        'gary', 'nicholas', 'eric', 'stephen', 'larry', 'justin', 'scott', 'brandon', 'benjamin', 'adam', 'alexander',
        'patrick', 'jack', 'liam', 'noah', 'oliver', 'sean', 'bob',

        // common given names (female-ish)
        'mary', 'patricia', 'jennifer', 'linda', 'elizabeth', 'barbara', 'susan', 'jessica', 'sarah', 'karen',
        'nancy', 'lisa', 'betty', 'margaret', 'sandra', 'ashley', 'kimberly', 'emily', 'donna', 'michelle',
        'carol', 'amanda', 'dorothy', 'melissa', 'deborah', 'stephanie', 'rebecca', 'laura', 'sharon', 'cynthia',
        'kathleen', 'amy', 'angela', 'shirley', 'anna', 'brenda', 'pamela', 'emma', 'chloe', 'olivia', 'sophia',
        'isabella', 'ava', 'mia', 'grace', 'ella', 'victoria', 'hannah', 'abigail', 'madison', 'lucy',
    ];

    public static function analyze(string $value, int $minimumWordLength = 6, array $allowedTerms = []): array
    {
        $allowedTerms = array_merge($allowedTerms, self::$whitelist);

        $value = trim($value);
        if (empty($value)) {
            return [
                'is_suspicious' => false,
                'bad_word_count' => 0,
                'short_word_junk_count' => 0,
                'words' => [],
            ];
        }

        $badWordCount = 0;
        $shortWordJunkCount = 0;
        $wordReports = [];

        // Strip URLs and emails so values like "https" never enter the loop
        $cleanedValue = preg_replace('~https?://\S+|www\.\S+|\S+@\S+~iu', ' ', $value) ?? $value;

        $rawCleanedValues = preg_split('/\s+/', trim($cleanedValue)) ?: [];
        $nonEmptyValues = array_values(array_filter($rawCleanedValues, static fn ($rawCleanedValue) => '' !== $rawCleanedValue));
        $isSingleValueField = 1 === \count($nonEmptyValues);

        // Catches a whole field that is just a short value (mirror per-word logic)
        $letters = preg_replace('/[^A-Za-z]/u', '', $cleanedValue);
        if (!empty($letters) && mb_strlen($letters) <= 4 && !self::isAllowedTerm($letters, $allowedTerms)) {
            $length = mb_strlen($letters);
            $characters = preg_split('//u', mb_strtolower($letters), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
            $unique = \count(array_unique($characters));
            $hasVowel = (bool) preg_match('/[aeiou]/i', $letters);

            $reasons = [];

            // Keyboard-run detection (whole value)
            $lettersLowerCase = strtolower($letters);
            $triads = [
                'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio', 'iop',
                'asd', 'sdf', 'dfg', 'fgh', 'ghj', 'hjk', 'jkl',
                'zxc', 'xcv', 'cvb', 'vbn', 'bnm',
            ];
            $tetrads = ['qwer', 'asdf', 'zxcv'];

            if (3 === $length && \in_array($lettersLowerCase, $triads, true)) {
                $badWordCount += 2;

                $reasons[] = 'keyboard_triad';
            }

            if (4 === $length && \in_array($lettersLowerCase, $tetrads, true)) {
                $badWordCount += 2;

                $reasons[] = 'keyboard_run';
            }

            if ($length <= 3 && $unique <= 2) {
                $badWordCount += 2;

                $reasons[] = 'short_whole_value_low_variety';
            } elseif (4 === $length && $unique <= 2 && !$hasVowel) {
                $badWordCount += 2;

                $reasons[] = 'short_whole_value_no_vowels_low_variety';
            }

            if (!empty($reasons)) {
                $wordReports[] = [
                    'word' => $value,
                    'letters' => $letters,
                    'length' => $length,
                    'reasons' => $reasons,
                    'scope' => 'whole_value',
                ];
            }
        }

        $words = preg_split('/\s+/', $cleanedValue);
        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }

            // Skip URLs
            if (preg_match('~^(https?://|www\.)~i', $word)) {
                continue;
            }

            // Skip emails
            if (filter_var($word, \FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Skip bare protocol-ish values
            if (preg_match('/^(?:http|https|www)$/i', $word)) {
                continue;
            }

            // Only analyze Latin words (strip punctuation first)
            $alphaOnly = preg_replace('/[^[:alpha:]]/u', '', $word);
            if (empty($alphaOnly) || !preg_match('/^\p{Latin}+$/u', $alphaOnly)) {
                continue;
            }

            if (self::isAllowedTerm($word, $allowedTerms)) {
                continue;
            }

            // Canonical letters
            $letters = preg_replace('/[^A-Za-z]/u', '', $word);
            if (empty($letters)) {
                continue;
            }

            $length = mb_strlen($letters);
            $unique = \count(array_unique(preg_split('//u', $letters, -1, \PREG_SPLIT_NO_EMPTY)));

            $reasons = [];

            // Short junk value: 2–4 letters (e.g. "asd", "qwe", "zzz")
            if ($length >= 2 && $length <= 4) {
                // skip common/allowed terms
                if (!self::isAllowedTerm($letters, $allowedTerms)) {
                    $hasVowel = (bool) preg_match('/[aeiou]/i', $letters);

                    // Keyboard-run detection (per word)
                    $lettersLowerCase = strtolower($letters);
                    $triads = [
                        'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio', 'iop',
                        'asd', 'sdf', 'dfg', 'fgh', 'ghj', 'hjk', 'jkl',
                        'zxc', 'xcv', 'cvb', 'vbn', 'bnm',
                    ];
                    $tetrads = ['qwer', 'asdf', 'zxcv'];

                    // 2-letter tokens: auto-allow except extremely obvious junk
                    if (2 === $length) {
                        // Only treat as junk if no vowels AND both characters are the same (e.g. "zz", "qq", "bb")
                        if (!$hasVowel && 1 === $unique) {
                            ++$shortWordJunkCount;

                            $reasons[] = 'short_two_letter_repeated_consonant';
                        }
                    } else {
                        // 3-4 letters: keep existing keyboard-run + low-variety logic
                        if (3 === $length && \in_array($lettersLowerCase, $triads, true)) {
                            $badWordCount += 2;

                            $reasons[] = 'keyboard_triad';
                        }

                        if (4 === $length && \in_array($lettersLowerCase, $tetrads, true)) {
                            $badWordCount += 2;

                            $reasons[] = 'keyboard_run';
                        }

                        // 3 letters: only if very low variety
                        if (3 === $length && $unique <= 2) {
                            ++$shortWordJunkCount;

                            $reasons[] = 'short_low_variety';
                        }
                        // 4 letters: only if very low variety AND no vowels
                        elseif (4 === $length && $unique <= 2 && !$hasVowel) {
                            ++$shortWordJunkCount;

                            $reasons[] = 'short_no_vowels_low_variety';
                        }
                    }
                }

                if (!empty($reasons)) {
                    $wordReports[] = [
                        'word' => $word,
                        'letters' => $letters,
                        'length' => $length,
                        'reasons' => $reasons,
                    ];
                }

                // short words are fully handled; skip the rest of the heavy checks
                continue;
            }

            // use the canonical letters length, not raw value
            if ($length < $minimumWordLength) {
                continue;
            }

            // Low alphabet variety: 7+ letters using less than 3 distinct chars = junk
            if ($length >= 7 && $unique <= 3) {
                $badWordCount += 2;

                $reasons[] = 'low_variety_long';
            }

            // Near-repeat small motif (1–3 chars), tolerate ~20% mismatches and a 1-char shift
            if ($length >= 6) {
                $repeatish = false;

                for ($m = 1; $m <= 3; ++$m) {
                    $motif = mb_substr($letters, 0, $m);
                    $allowed = intdiv($length, 5); // ~20%

                    $mismatches = 0;
                    for ($pos = 0; $pos < $length; $pos += $m) {
                        $chunk = mb_substr($letters, $pos, $m);
                        $cl = mb_strlen($chunk);
                        for ($i = 0; $i < $cl; ++$i) {
                            if (mb_substr($chunk, $i, 1) !== mb_substr($motif, $i, 1)) {
                                ++$mismatches;
                            }
                        }
                    }

                    $shifted = mb_substr($letters, 1);
                    $mismatches2 = 0;
                    $length2 = mb_strlen($shifted);
                    for ($pos = 0; $pos < $length2; $pos += $m) {
                        $chunk = mb_substr($shifted, $pos, $m);
                        $cl = mb_strlen($chunk);
                        for ($i = 0; $i < $cl; ++$i) {
                            if (mb_substr($chunk, $i, 1) !== mb_substr($motif, $i, 1)) {
                                ++$mismatches2;
                            }
                        }
                    }

                    if ($mismatches <= $allowed || $mismatches2 <= $allowed) {
                        $repeatish = true;

                        break;
                    }
                }

                if ($repeatish) {
                    $badWordCount += 2;

                    $reasons[] = 'near_repeat_motif';
                }
            }

            // Off-by-one near-repeat
            if ($length >= 5) {
                $minusLast = mb_substr($letters, 0, $length - 1);
                $minusFirst = mb_substr($letters, 1);
                if (preg_match('/^([A-Za-z]{1,3})\1+$/', $minusLast) || preg_match('/^([A-Za-z]{1,3})\1+$/', $minusFirst)) {
                    $badWordCount += 2;

                    $reasons[] = 'off_by_one_repeat';
                }
            }

            // Exact repeat of 1–3 char motif
            if (preg_match('/^([A-Za-z]{1,3})\1+$/', $letters)) {
                $badWordCount += 2;

                $reasons[] = 'exact_motif_repeat';
            }

            // Repetitive CV (consonant–vowel) pattern
            if ($isSingleValueField) {
                // Be stricter for single-token fields:
                // - long token (>=10),
                // - *low* unique letter variety (<=6) — random blobs tend to reuse letters,
                // - matches CV pattern,
                // - does NOT end with common English suffixes (avoid false positives like "popularised/organized/processing")
                if (
                    $length >= 10
                    && $unique <= 6
                    && preg_match('/^(?:[bcdfghjklmnpqrstvwxyz][aeiouy]){5,}[bcdfghjklmnpqrstvwxyz]?$/iu', $letters)
                    && !preg_match('/(ing|tion|sion|ment|ally|ized|ised|able|less|ness)$/i', $letters)
                ) {
                    $badWordCount += 2;

                    $reasons[] = 'repetitive_consonant_vowel_single_value';
                }
            } else {
                // Paragraph/phrase context: keep softer rule and suffix pardons
                if (
                    $length >= 12
                    && $unique <= 5 // very few distinct letters
                    && preg_match('/^(?:[bcdfghjklmnpqrstvwxyz][aeiouy]){5,}[bcdfghjklmnpqrstvwxyz]?$/iu', $letters)
                    && !preg_match('/(ing|tion|sion|ment|ally|ized|ised|able|less|ness)$/i', $letters)
                ) {
                    $badWordCount += 2;

                    $reasons[] = 'repetitive_consonant_vowel';
                }
            }

            // Vowel ratio extremes
            $vowelPattern = self::vowelPatternForLength($length);
            $vowels = preg_match_all('/['.$vowelPattern.']/iu', $letters);

            if ($length >= 10 && $vowels <= 2) {
                $badWordCount += 2;

                $reasons[] = 'very_few_vowels_long';
            } elseif ($length >= 6 && 0 === $vowels) {
                $badWordCount += 2;

                $reasons[] = 'no_vowels_long';
            } else {
                $ratio = $vowels ? ($vowels / max(1, $length)) : 0;
                if ($ratio < 0.20 || $ratio > 0.80) {
                    ++$badWordCount;

                    $reasons[] = 'vowel_ratio_extreme';
                }
            }

            // Consonant dominance / runs
            $cons = $length - $vowels;
            $consRatio = $cons / max(1, $length);
            $consPattern = '^'.$vowelPattern;
            $hasRun6 = (bool) preg_match('/['.$consPattern.']{6,}/iu', $letters);
            $hasRun5Long = ($length >= 12) && preg_match('/['.$consPattern.']{5,}/iu', $letters);

            if (($length >= 12 && $consRatio >= 0.78) || $hasRun6 || $hasRun5Long) {
                ++$badWordCount;

                $reasons[] = 'consonant_runs_or_dominance';
            }

            // Namey mixed case exemptions
            $isCamelCase = (bool) preg_match('/^[A-Z][a-z]+(?:[A-Z][a-z]+)+$/', $letters);
            $isLowerCamelNamey = (bool) preg_match('/^[a-z]+[A-Z][a-z]+$/', $letters);

            // Case transitions
            $caseTransitions = preg_match_all('/(?:(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[a-z]))/u', $letters);

            if (!($isCamelCase || $isLowerCamelNamey) && $caseTransitions >= 2) {
                ++$badWordCount;

                $reasons[] = 'mixed_case_noise';
            }

            // Stricter for single-value fields with mixed case noise
            if ($isSingleValueField && $length >= 8 && !($isCamelCase || $isLowerCamelNamey) && $caseTransitions >= 2) {
                $badWordCount += 2;

                $reasons[] = 'mixed_case_noise_single_value';
            }

            // Entropy
            $entropy = self::shannonEntropy($letters);
            if (!($isCamelCase || $isLowerCamelNamey) && $entropy > 3.9) {
                ++$badWordCount;

                $reasons[] = 'high_entropy';
            }

            if (!empty($reasons)) {
                $wordReports[] = [
                    'word' => $word,
                    'letters' => $letters,
                    'length' => $length,
                    'reasons' => $reasons,
                ];
            }
        }

        // Only penalize if there were multiple short-junk values
        if ($shortWordJunkCount >= 3) {
            $badWordCount += 2;
        }

        return [
            'is_suspicious' => $badWordCount >= 2,
            'bad_word_count' => $badWordCount,
            'short_word_junk_count' => $shortWordJunkCount,
            'words' => $wordReports,
        ];
    }

    public static function vowelPatternForLength(int $length): string
    {
        // For long values, treat 'y' as a vowel to avoid false positives like "industry's", "synchrony"
        return $length >= 6 ? 'aeiouy' : 'aeiou';
    }

    public static function isAllowedTerm(string $value, array $allowedTerms): bool
    {
        $normalize = strtoupper(preg_replace('/[^A-Z]/i', '', $value) ?? '');
        $normalizedAllowedTerms = array_map(
            static fn ($allowedTerm) => strtoupper(preg_replace('/[^A-Z]/i', '', $allowedTerm) ?? ''),
            $allowedTerms
        );

        return in_array($normalize, $normalizedAllowedTerms, true);
    }

    public static function shannonEntropy(string $value): float
    {
        $length = mb_strlen($value);
        if (0 === $length) {
            return 0.0;
        }

        $frequency = [];

        for ($i = 0; $i < $length; ++$i) {
            $character = mb_substr($value, $i, 1);
            $frequency[$character] = ($frequency[$character] ?? 0) + 1;
        }

        $H = 0.0;

        foreach ($frequency as $character) {
            $p = $character / $length;
            $H -= $p * log($p, 2);
        }

        return $H;
    }
}
