<?php
namespace verbb\formie\options\predefined;

use verbb\formie\base\PredefinedOption;
use verbb\formie\helpers\LocaleDataHelper;

use Craft;

class Languages extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Languages');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', '2-Letter Code'), 'value' => '2-letter'],
            ['label' => Craft::t('formie', '3-Letter Code'), 'value' => '3-letter'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', '2-Letter Code'), 'value' => '2-letter'],
            ['label' => Craft::t('formie', '3-Letter Code'), 'value' => '3-letter'],
        ];
    }

    public static function getDataOptions(): array
    {
        // Deliberately not using Craft::t().
        $locale = Craft::$app->getLocale()->getLanguageID();
        $options = [];

        foreach (self::languageEntries() as $entry) {
            $code = $entry['2-letter'];
            $name = LocaleDataHelper::languageName($code, $locale);
            $fallback = $entry['fallback'];

            if ($name === $code || $name === '') {
                $name = $fallback;
            }

            $options[] = [
                'name' => $name,
                '2-letter' => $entry['2-letter'],
                '3-letter' => $entry['3-letter'],
            ];
        }

        return $options;
    }

    private static function languageEntries(): array
    {
        return [
            ['fallback' => 'Abkhazian', '2-letter' => 'ab', '3-letter' => 'abk'],
            ['fallback' => 'Afar', '2-letter' => 'aa', '3-letter' => 'aar'],
            ['fallback' => 'Afrikaans', '2-letter' => 'af', '3-letter' => 'afr'],
            ['fallback' => 'Akan', '2-letter' => 'ak', '3-letter' => 'aka'],
            ['fallback' => 'Albanian', '2-letter' => 'sq', '3-letter' => 'alb'],
            ['fallback' => 'Amharic', '2-letter' => 'am', '3-letter' => 'amh'],
            ['fallback' => 'Arabic', '2-letter' => 'ar', '3-letter' => 'ara'],
            ['fallback' => 'Aragonese', '2-letter' => 'an', '3-letter' => 'arg'],
            ['fallback' => 'Armenian', '2-letter' => 'hy', '3-letter' => 'arm'],
            ['fallback' => 'Assamese', '2-letter' => 'as', '3-letter' => 'asm'],
            ['fallback' => 'Avaric', '2-letter' => 'av', '3-letter' => 'ava'],
            ['fallback' => 'Avestan', '2-letter' => 'ae', '3-letter' => 'ave'],
            ['fallback' => 'Aymara', '2-letter' => 'ay', '3-letter' => 'aym'],
            ['fallback' => 'Azerbaijani', '2-letter' => 'az', '3-letter' => 'aze'],
            ['fallback' => 'Bambara', '2-letter' => 'bm', '3-letter' => 'bam'],
            ['fallback' => 'Bashkir', '2-letter' => 'ba', '3-letter' => 'bak'],
            ['fallback' => 'Basque', '2-letter' => 'eu', '3-letter' => 'baq'],
            ['fallback' => 'Belarusian', '2-letter' => 'be', '3-letter' => 'bel'],
            ['fallback' => 'Bengali', '2-letter' => 'bn', '3-letter' => 'ben'],
            ['fallback' => 'Bihari languages', '2-letter' => 'bh', '3-letter' => 'bih'],
            ['fallback' => 'Bislama', '2-letter' => 'bi', '3-letter' => 'bis'],
            ['fallback' => 'Bokmål, Norwegian; Norwegian Bokmål', '2-letter' => 'nb', '3-letter' => 'nob'],
            ['fallback' => 'Bosnian', '2-letter' => 'bs', '3-letter' => 'bos'],
            ['fallback' => 'Breton', '2-letter' => 'br', '3-letter' => 'bre'],
            ['fallback' => 'Bulgarian', '2-letter' => 'bg', '3-letter' => 'bul'],
            ['fallback' => 'Burmese', '2-letter' => 'my', '3-letter' => 'bur'],
            ['fallback' => 'Catalan; Valencian', '2-letter' => 'ca', '3-letter' => 'cat'],
            ['fallback' => 'Central Khmer', '2-letter' => 'km', '3-letter' => 'khm'],
            ['fallback' => 'Chamorro', '2-letter' => 'ch', '3-letter' => 'cha'],
            ['fallback' => 'Chechen', '2-letter' => 'ce', '3-letter' => 'che'],
            ['fallback' => 'Chichewa; Chewa; Nyanja', '2-letter' => 'ny', '3-letter' => 'nya'],
            ['fallback' => 'Chinese', '2-letter' => 'zh', '3-letter' => 'chi'],
            ['fallback' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic', '2-letter' => 'cu', '3-letter' => 'chu'],
            ['fallback' => 'Chuvash', '2-letter' => 'cv', '3-letter' => 'chv'],
            ['fallback' => 'Cornish', '2-letter' => 'kw', '3-letter' => 'cor'],
            ['fallback' => 'Corsican', '2-letter' => 'co', '3-letter' => 'cos'],
            ['fallback' => 'Cree', '2-letter' => 'cr', '3-letter' => 'cre'],
            ['fallback' => 'Croatian', '2-letter' => 'hr', '3-letter' => 'hrv'],
            ['fallback' => 'Czech', '2-letter' => 'cs', '3-letter' => 'cze'],
            ['fallback' => 'Danish', '2-letter' => 'da', '3-letter' => 'dan'],
            ['fallback' => 'Divehi; Dhivehi; Maldivian', '2-letter' => 'dv', '3-letter' => 'div'],
            ['fallback' => 'Dutch; Flemish', '2-letter' => 'nl', '3-letter' => 'dut'],
            ['fallback' => 'Dzongkha', '2-letter' => 'dz', '3-letter' => 'dzo'],
            ['fallback' => 'English', '2-letter' => 'en', '3-letter' => 'eng'],
            ['fallback' => 'Esperanto', '2-letter' => 'eo', '3-letter' => 'epo'],
            ['fallback' => 'Estonian', '2-letter' => 'et', '3-letter' => 'est'],
            ['fallback' => 'Ewe', '2-letter' => 'ee', '3-letter' => 'ewe'],
            ['fallback' => 'Faroese', '2-letter' => 'fo', '3-letter' => 'fao'],
            ['fallback' => 'Fijian', '2-letter' => 'fj', '3-letter' => 'fij'],
            ['fallback' => 'Finnish', '2-letter' => 'fi', '3-letter' => 'fin'],
            ['fallback' => 'French', '2-letter' => 'fr', '3-letter' => 'fre'],
            ['fallback' => 'Fulah', '2-letter' => 'ff', '3-letter' => 'ful'],
            ['fallback' => 'Gaelic; Scottish Gaelic', '2-letter' => 'gd', '3-letter' => 'gla'],
            ['fallback' => 'Galician', '2-letter' => 'gl', '3-letter' => 'glg'],
            ['fallback' => 'Ganda', '2-letter' => 'lg', '3-letter' => 'lug'],
            ['fallback' => 'Georgian', '2-letter' => 'ka', '3-letter' => 'geo'],
            ['fallback' => 'German', '2-letter' => 'de', '3-letter' => 'ger'],
            ['fallback' => 'Greek, Modern (1453-)', '2-letter' => 'el', '3-letter' => 'gre'],
            ['fallback' => 'Guarani', '2-letter' => 'gn', '3-letter' => 'grn'],
            ['fallback' => 'Gujarati', '2-letter' => 'gu', '3-letter' => 'guj'],
            ['fallback' => 'Haitian; Haitian Creole', '2-letter' => 'ht', '3-letter' => 'hat'],
            ['fallback' => 'Hausa', '2-letter' => 'ha', '3-letter' => 'hau'],
            ['fallback' => 'Hebrew', '2-letter' => 'he', '3-letter' => 'heb'],
            ['fallback' => 'Herero', '2-letter' => 'hz', '3-letter' => 'her'],
            ['fallback' => 'Hindi', '2-letter' => 'hi', '3-letter' => 'hin'],
            ['fallback' => 'Hiri Motu', '2-letter' => 'ho', '3-letter' => 'hmo'],
            ['fallback' => 'Hungarian', '2-letter' => 'hu', '3-letter' => 'hun'],
            ['fallback' => 'Icelandic', '2-letter' => 'is', '3-letter' => 'ice'],
            ['fallback' => 'Ido', '2-letter' => 'io', '3-letter' => 'ido'],
            ['fallback' => 'Igbo', '2-letter' => 'ig', '3-letter' => 'ibo'],
            ['fallback' => 'Indonesian', '2-letter' => 'id', '3-letter' => 'ind'],
            ['fallback' => 'Interlingua (International Auxiliary Language Association)', '2-letter' => 'ia', '3-letter' => 'ina'],
            ['fallback' => 'Interlingue; Occidental', '2-letter' => 'ie', '3-letter' => 'ile'],
            ['fallback' => 'Inuktitut', '2-letter' => 'iu', '3-letter' => 'iku'],
            ['fallback' => 'Inupiaq', '2-letter' => 'ik', '3-letter' => 'ipk'],
            ['fallback' => 'Irish', '2-letter' => 'ga', '3-letter' => 'gle'],
            ['fallback' => 'Italian', '2-letter' => 'it', '3-letter' => 'ita'],
            ['fallback' => 'Japanese', '2-letter' => 'ja', '3-letter' => 'jpn'],
            ['fallback' => 'Javanese', '2-letter' => 'jv', '3-letter' => 'jav'],
            ['fallback' => 'Kalaallisut; Greenlandic', '2-letter' => 'kl', '3-letter' => 'kal'],
            ['fallback' => 'Kannada', '2-letter' => 'kn', '3-letter' => 'kan'],
            ['fallback' => 'Kanuri', '2-letter' => 'kr', '3-letter' => 'kau'],
            ['fallback' => 'Kashmiri', '2-letter' => 'ks', '3-letter' => 'kas'],
            ['fallback' => 'Kazakh', '2-letter' => 'kk', '3-letter' => 'kaz'],
            ['fallback' => 'Kikuyu; Gikuyu', '2-letter' => 'ki', '3-letter' => 'kik'],
            ['fallback' => 'Kinyarwanda', '2-letter' => 'rw', '3-letter' => 'kin'],
            ['fallback' => 'Kirghiz; Kyrgyz', '2-letter' => 'ky', '3-letter' => 'kir'],
            ['fallback' => 'Komi', '2-letter' => 'kv', '3-letter' => 'kom'],
            ['fallback' => 'Kongo', '2-letter' => 'kg', '3-letter' => 'kon'],
            ['fallback' => 'Korean', '2-letter' => 'ko', '3-letter' => 'kor'],
            ['fallback' => 'Kuanyama; Kwanyama', '2-letter' => 'kj', '3-letter' => 'kua'],
            ['fallback' => 'Kurdish', '2-letter' => 'ku', '3-letter' => 'kur'],
            ['fallback' => 'Lao', '2-letter' => 'lo', '3-letter' => 'lao'],
            ['fallback' => 'Latin', '2-letter' => 'la', '3-letter' => 'lat'],
            ['fallback' => 'Latvian', '2-letter' => 'lv', '3-letter' => 'lav'],
            ['fallback' => 'Limburgan; Limburger; Limburgish', '2-letter' => 'li', '3-letter' => 'lim'],
            ['fallback' => 'Lingala', '2-letter' => 'ln', '3-letter' => 'lin'],
            ['fallback' => 'Lithuanian', '2-letter' => 'lt', '3-letter' => 'lit'],
            ['fallback' => 'Luba-Katanga', '2-letter' => 'lu', '3-letter' => 'lub'],
            ['fallback' => 'Luxembourgish; Letzeburgesch', '2-letter' => 'lb', '3-letter' => 'ltz'],
            ['fallback' => 'Macedonian', '2-letter' => 'mk', '3-letter' => 'mac'],
            ['fallback' => 'Malagasy', '2-letter' => 'mg', '3-letter' => 'mlg'],
            ['fallback' => 'Malay', '2-letter' => 'ms', '3-letter' => 'may'],
            ['fallback' => 'Malayalam', '2-letter' => 'ml', '3-letter' => 'mal'],
            ['fallback' => 'Maltese', '2-letter' => 'mt', '3-letter' => 'mlt'],
            ['fallback' => 'Manx', '2-letter' => 'gv', '3-letter' => 'glv'],
            ['fallback' => 'Maori', '2-letter' => 'mi', '3-letter' => 'mao'],
            ['fallback' => 'Marathi', '2-letter' => 'mr', '3-letter' => 'mar'],
            ['fallback' => 'Marshallese', '2-letter' => 'mh', '3-letter' => 'mah'],
            ['fallback' => 'Mongolian', '2-letter' => 'mn', '3-letter' => 'mon'],
            ['fallback' => 'Nauru', '2-letter' => 'na', '3-letter' => 'nau'],
            ['fallback' => 'Navajo; Navaho', '2-letter' => 'nv', '3-letter' => 'nav'],
            ['fallback' => 'Ndebele, North; North Ndebele', '2-letter' => 'nd', '3-letter' => 'nde'],
            ['fallback' => 'Ndebele, South; South Ndebele', '2-letter' => 'nr', '3-letter' => 'nbl'],
            ['fallback' => 'Ndonga', '2-letter' => 'ng', '3-letter' => 'ndo'],
            ['fallback' => 'Nepali', '2-letter' => 'ne', '3-letter' => 'nep'],
            ['fallback' => 'Northern Sami', '2-letter' => 'se', '3-letter' => 'sme'],
            ['fallback' => 'Norwegian', '2-letter' => 'no', '3-letter' => 'nor'],
            ['fallback' => 'Norwegian Nynorsk; Nynorsk, Norwegian', '2-letter' => 'nn', '3-letter' => 'nno'],
            ['fallback' => 'Occitan (post 1500)', '2-letter' => 'oc', '3-letter' => 'oci'],
            ['fallback' => 'Ojibwa', '2-letter' => 'oj', '3-letter' => 'oji'],
            ['fallback' => 'Oriya', '2-letter' => 'or', '3-letter' => 'ori'],
            ['fallback' => 'Oromo', '2-letter' => 'om', '3-letter' => 'orm'],
            ['fallback' => 'Ossetian; Ossetic', '2-letter' => 'os', '3-letter' => 'oss'],
            ['fallback' => 'Pali', '2-letter' => 'pi', '3-letter' => 'pli'],
            ['fallback' => 'Panjabi; Punjabi', '2-letter' => 'pa', '3-letter' => 'pan'],
            ['fallback' => 'Persian', '2-letter' => 'fa', '3-letter' => 'per'],
            ['fallback' => 'Polish', '2-letter' => 'pl', '3-letter' => 'pol'],
            ['fallback' => 'Portuguese', '2-letter' => 'pt', '3-letter' => 'por'],
            ['fallback' => 'Pushto; Pashto', '2-letter' => 'ps', '3-letter' => 'pus'],
            ['fallback' => 'Quechua', '2-letter' => 'qu', '3-letter' => 'que'],
            ['fallback' => 'Romanian; Moldavian; Moldovan', '2-letter' => 'ro', '3-letter' => 'rum'],
            ['fallback' => 'Romansh', '2-letter' => 'rm', '3-letter' => 'roh'],
            ['fallback' => 'Rundi', '2-letter' => 'rn', '3-letter' => 'run'],
            ['fallback' => 'Russian', '2-letter' => 'ru', '3-letter' => 'rus'],
            ['fallback' => 'Samoan', '2-letter' => 'sm', '3-letter' => 'smo'],
            ['fallback' => 'Sango', '2-letter' => 'sg', '3-letter' => 'sag'],
            ['fallback' => 'Sanskrit', '2-letter' => 'sa', '3-letter' => 'san'],
            ['fallback' => 'Sardinian', '2-letter' => 'sc', '3-letter' => 'srd'],
            ['fallback' => 'Serbian', '2-letter' => 'sr', '3-letter' => 'srp'],
            ['fallback' => 'Shona', '2-letter' => 'sn', '3-letter' => 'sna'],
            ['fallback' => 'Sichuan Yi; Nuosu', '2-letter' => 'ii', '3-letter' => 'iii'],
            ['fallback' => 'Sindhi', '2-letter' => 'sd', '3-letter' => 'snd'],
            ['fallback' => 'Sinhala; Sinhalese', '2-letter' => 'si', '3-letter' => 'sin'],
            ['fallback' => 'Slovak', '2-letter' => 'sk', '3-letter' => 'slo'],
            ['fallback' => 'Slovenian', '2-letter' => 'sl', '3-letter' => 'slv'],
            ['fallback' => 'Somali', '2-letter' => 'so', '3-letter' => 'som'],
            ['fallback' => 'Sotho, Southern', '2-letter' => 'st', '3-letter' => 'sot'],
            ['fallback' => 'Spanish; Castilian', '2-letter' => 'es', '3-letter' => 'spa'],
            ['fallback' => 'Sundanese', '2-letter' => 'su', '3-letter' => 'sun'],
            ['fallback' => 'Swahili', '2-letter' => 'sw', '3-letter' => 'swa'],
            ['fallback' => 'Swati', '2-letter' => 'ss', '3-letter' => 'ssw'],
            ['fallback' => 'Swedish', '2-letter' => 'sv', '3-letter' => 'swe'],
            ['fallback' => 'Tagalog', '2-letter' => 'tl', '3-letter' => 'tgl'],
            ['fallback' => 'Tahitian', '2-letter' => 'ty', '3-letter' => 'tah'],
            ['fallback' => 'Tajik', '2-letter' => 'tg', '3-letter' => 'tgk'],
            ['fallback' => 'Tamil', '2-letter' => 'ta', '3-letter' => 'tam'],
            ['fallback' => 'Tatar', '2-letter' => 'tt', '3-letter' => 'tat'],
            ['fallback' => 'Telugu', '2-letter' => 'te', '3-letter' => 'tel'],
            ['fallback' => 'Thai', '2-letter' => 'th', '3-letter' => 'tha'],
            ['fallback' => 'Tibetan', '2-letter' => 'bo', '3-letter' => 'tib'],
            ['fallback' => 'Tigrinya', '2-letter' => 'ti', '3-letter' => 'tir'],
            ['fallback' => 'Tonga (Tonga Islands)', '2-letter' => 'to', '3-letter' => 'ton'],
            ['fallback' => 'Tsonga', '2-letter' => 'ts', '3-letter' => 'tso'],
            ['fallback' => 'Tswana', '2-letter' => 'tn', '3-letter' => 'tsn'],
            ['fallback' => 'Turkish', '2-letter' => 'tr', '3-letter' => 'tur'],
            ['fallback' => 'Turkmen', '2-letter' => 'tk', '3-letter' => 'tuk'],
            ['fallback' => 'Twi', '2-letter' => 'tw', '3-letter' => 'twi'],
            ['fallback' => 'Uighur; Uyghur', '2-letter' => 'ug', '3-letter' => 'uig'],
            ['fallback' => 'Ukrainian', '2-letter' => 'uk', '3-letter' => 'ukr'],
            ['fallback' => 'Urdu', '2-letter' => 'ur', '3-letter' => 'urd'],
            ['fallback' => 'Uzbek', '2-letter' => 'uz', '3-letter' => 'uzb'],
            ['fallback' => 'Venda', '2-letter' => 've', '3-letter' => 'ven'],
            ['fallback' => 'Vietnamese', '2-letter' => 'vi', '3-letter' => 'vie'],
            ['fallback' => 'Volapük', '2-letter' => 'vo', '3-letter' => 'vol'],
            ['fallback' => 'Walloon', '2-letter' => 'wa', '3-letter' => 'wln'],
            ['fallback' => 'Welsh', '2-letter' => 'cy', '3-letter' => 'wel'],
            ['fallback' => 'Western Frisian', '2-letter' => 'fy', '3-letter' => 'fry'],
            ['fallback' => 'Wolof', '2-letter' => 'wo', '3-letter' => 'wol'],
            ['fallback' => 'Xhosa', '2-letter' => 'xh', '3-letter' => 'xho'],
            ['fallback' => 'Yiddish', '2-letter' => 'yi', '3-letter' => 'yid'],
            ['fallback' => 'Yoruba', '2-letter' => 'yo', '3-letter' => 'yor'],
            ['fallback' => 'Zhuang; Chuang', '2-letter' => 'za', '3-letter' => 'zha'],
            ['fallback' => 'Zulu', '2-letter' => 'zu', '3-letter' => 'zul'],
        ];
    }
}
