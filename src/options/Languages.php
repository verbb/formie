<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Languages extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
        return [
            [
                'name' => Craft::t('formie', 'Abkhazian'),
                '2-letter' => 'ab',
                '3-letter' => 'abk',
            ],
            [
                'name' => Craft::t('formie', 'Afar'),
                '2-letter' => 'aa',
                '3-letter' => 'aar',
            ],
            [
                'name' => Craft::t('formie', 'Afrikaans'),
                '2-letter' => 'af',
                '3-letter' => 'afr',
            ],
            [
                'name' => Craft::t('formie', 'Akan'),
                '2-letter' => 'ak',
                '3-letter' => 'aka',
            ],
            [
                'name' => Craft::t('formie', 'Albanian'),
                '2-letter' => 'sq',
                '3-letter' => 'alb',
            ],
            [
                'name' => Craft::t('formie', 'Amharic'),
                '2-letter' => 'am',
                '3-letter' => 'amh',
            ],
            [
                'name' => Craft::t('formie', 'Arabic'),
                '2-letter' => 'ar',
                '3-letter' => 'ara',
            ],
            [
                'name' => Craft::t('formie', 'Aragonese'),
                '2-letter' => 'an',
                '3-letter' => 'arg',
            ],
            [
                'name' => Craft::t('formie', 'Armenian'),
                '2-letter' => 'hy',
                '3-letter' => 'arm',
            ],
            [
                'name' => Craft::t('formie', 'Assamese'),
                '2-letter' => 'as',
                '3-letter' => 'asm',
            ],
            [
                'name' => Craft::t('formie', 'Avaric'),
                '2-letter' => 'av',
                '3-letter' => 'ava',
            ],
            [
                'name' => Craft::t('formie', 'Avestan'),
                '2-letter' => 'ae',
                '3-letter' => 'ave',
            ],
            [
                'name' => Craft::t('formie', 'Aymara'),
                '2-letter' => 'ay',
                '3-letter' => 'aym',
            ],
            [
                'name' => Craft::t('formie', 'Azerbaijani'),
                '2-letter' => 'az',
                '3-letter' => 'aze',
            ],
            [
                'name' => Craft::t('formie', 'Bambara'),
                '2-letter' => 'bm',
                '3-letter' => 'bam',
            ],
            [
                'name' => Craft::t('formie', 'Bashkir'),
                '2-letter' => 'ba',
                '3-letter' => 'bak',
            ],
            [
                'name' => Craft::t('formie', 'Basque'),
                '2-letter' => 'eu',
                '3-letter' => 'baq',
            ],
            [
                'name' => Craft::t('formie', 'Belarusian'),
                '2-letter' => 'be',
                '3-letter' => 'bel',
            ],
            [
                'name' => Craft::t('formie', 'Bengali'),
                '2-letter' => 'bn',
                '3-letter' => 'ben',
            ],
            [
                'name' => Craft::t('formie', 'Bihari languages'),
                '2-letter' => 'bh',
                '3-letter' => 'bih',
            ],
            [
                'name' => Craft::t('formie', 'Bislama'),
                '2-letter' => 'bi',
                '3-letter' => 'bis',
            ],
            [
                'name' => Craft::t('formie', 'Bokmål, Norwegian; Norwegian Bokmål'),
                '2-letter' => 'nb',
                '3-letter' => 'nob',
            ],
            [
                'name' => Craft::t('formie', 'Bosnian'),
                '2-letter' => 'bs',
                '3-letter' => 'bos',
            ],
            [
                'name' => Craft::t('formie', 'Breton'),
                '2-letter' => 'br',
                '3-letter' => 'bre',
            ],
            [
                'name' => Craft::t('formie', 'Bulgarian'),
                '2-letter' => 'bg',
                '3-letter' => 'bul',
            ],
            [
                'name' => Craft::t('formie', 'Burmese'),
                '2-letter' => 'my',
                '3-letter' => 'bur',
            ],
            [
                'name' => Craft::t('formie', 'Catalan; Valencian'),
                '2-letter' => 'ca',
                '3-letter' => 'cat',
            ],
            [
                'name' => Craft::t('formie', 'Central Khmer'),
                '2-letter' => 'km',
                '3-letter' => 'khm',
            ],
            [
                'name' => Craft::t('formie', 'Chamorro'),
                '2-letter' => 'ch',
                '3-letter' => 'cha',
            ],
            [
                'name' => Craft::t('formie', 'Chechen'),
                '2-letter' => 'ce',
                '3-letter' => 'che',
            ],
            [
                'name' => Craft::t('formie', 'Chichewa; Chewa; Nyanja'),
                '2-letter' => 'ny',
                '3-letter' => 'nya',
            ],
            [
                'name' => Craft::t('formie', 'Chinese'),
                '2-letter' => 'zh',
                '3-letter' => 'chi',
            ],
            [
                'name' => Craft::t('formie', 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic'),
                '2-letter' => 'cu',
                '3-letter' => 'chu',
            ],
            [
                'name' => Craft::t('formie', 'Chuvash'),
                '2-letter' => 'cv',
                '3-letter' => 'chv',
            ],
            [
                'name' => Craft::t('formie', 'Cornish'),
                '2-letter' => 'kw',
                '3-letter' => 'cor',
            ],
            [
                'name' => Craft::t('formie', 'Corsican'),
                '2-letter' => 'co',
                '3-letter' => 'cos',
            ],
            [
                'name' => Craft::t('formie', 'Cree'),
                '2-letter' => 'cr',
                '3-letter' => 'cre',
            ],
            [
                'name' => Craft::t('formie', 'Croatian'),
                '2-letter' => 'hr',
                '3-letter' => 'hrv',
            ],
            [
                'name' => Craft::t('formie', 'Czech'),
                '2-letter' => 'cs',
                '3-letter' => 'cze',
            ],
            [
                'name' => Craft::t('formie', 'Danish'),
                '2-letter' => 'da',
                '3-letter' => 'dan',
            ],
            [
                'name' => Craft::t('formie', 'Divehi; Dhivehi; Maldivian'),
                '2-letter' => 'dv',
                '3-letter' => 'div',
            ],
            [
                'name' => Craft::t('formie', 'Dutch; Flemish'),
                '2-letter' => 'nl',
                '3-letter' => 'dut',
            ],
            [
                'name' => Craft::t('formie', 'Dzongkha'),
                '2-letter' => 'dz',
                '3-letter' => 'dzo',
            ],
            [
                'name' => Craft::t('formie', 'English'),
                '2-letter' => 'en',
                '3-letter' => 'eng',
            ],
            [
                'name' => Craft::t('formie', 'Esperanto'),
                '2-letter' => 'eo',
                '3-letter' => 'epo',
            ],
            [
                'name' => Craft::t('formie', 'Estonian'),
                '2-letter' => 'et',
                '3-letter' => 'est',
            ],
            [
                'name' => Craft::t('formie', 'Ewe'),
                '2-letter' => 'ee',
                '3-letter' => 'ewe',
            ],
            [
                'name' => Craft::t('formie', 'Faroese'),
                '2-letter' => 'fo',
                '3-letter' => 'fao',
            ],
            [
                'name' => Craft::t('formie', 'Fijian'),
                '2-letter' => 'fj',
                '3-letter' => 'fij',
            ],
            [
                'name' => Craft::t('formie', 'Finnish'),
                '2-letter' => 'fi',
                '3-letter' => 'fin',
            ],
            [
                'name' => Craft::t('formie', 'French'),
                '2-letter' => 'fr',
                '3-letter' => 'fre',
            ],
            [
                'name' => Craft::t('formie', 'Fulah'),
                '2-letter' => 'ff',
                '3-letter' => 'ful',
            ],
            [
                'name' => Craft::t('formie', 'Gaelic; Scottish Gaelic'),
                '2-letter' => 'gd',
                '3-letter' => 'gla',
            ],
            [
                'name' => Craft::t('formie', 'Galician'),
                '2-letter' => 'gl',
                '3-letter' => 'glg',
            ],
            [
                'name' => Craft::t('formie', 'Ganda'),
                '2-letter' => 'lg',
                '3-letter' => 'lug',
            ],
            [
                'name' => Craft::t('formie', 'Georgian'),
                '2-letter' => 'ka',
                '3-letter' => 'geo',
            ],
            [
                'name' => Craft::t('formie', 'German'),
                '2-letter' => 'de',
                '3-letter' => 'ger',
            ],
            [
                'name' => Craft::t('formie', 'Greek, Modern (1453-)'),
                '2-letter' => 'el',
                '3-letter' => 'gre',
            ],
            [
                'name' => Craft::t('formie', 'Guarani'),
                '2-letter' => 'gn',
                '3-letter' => 'grn',
            ],
            [
                'name' => Craft::t('formie', 'Gujarati'),
                '2-letter' => 'gu',
                '3-letter' => 'guj',
            ],
            [
                'name' => Craft::t('formie', 'Haitian; Haitian Creole'),
                '2-letter' => 'ht',
                '3-letter' => 'hat',
            ],
            [
                'name' => Craft::t('formie', 'Hausa'),
                '2-letter' => 'ha',
                '3-letter' => 'hau',
            ],
            [
                'name' => Craft::t('formie', 'Hebrew'),
                '2-letter' => 'he',
                '3-letter' => 'heb',
            ],
            [
                'name' => Craft::t('formie', 'Herero'),
                '2-letter' => 'hz',
                '3-letter' => 'her',
            ],
            [
                'name' => Craft::t('formie', 'Hindi'),
                '2-letter' => 'hi',
                '3-letter' => 'hin',
            ],
            [
                'name' => Craft::t('formie', 'Hiri Motu'),
                '2-letter' => 'ho',
                '3-letter' => 'hmo',
            ],
            [
                'name' => Craft::t('formie', 'Hungarian'),
                '2-letter' => 'hu',
                '3-letter' => 'hun',
            ],
            [
                'name' => Craft::t('formie', 'Icelandic'),
                '2-letter' => 'is',
                '3-letter' => 'ice',
            ],
            [
                'name' => Craft::t('formie', 'Ido'),
                '2-letter' => 'io',
                '3-letter' => 'ido',
            ],
            [
                'name' => Craft::t('formie', 'Igbo'),
                '2-letter' => 'ig',
                '3-letter' => 'ibo',
            ],
            [
                'name' => Craft::t('formie', 'Indonesian'),
                '2-letter' => 'id',
                '3-letter' => 'ind',
            ],
            [
                'name' => Craft::t('formie', 'Interlingua (International Auxiliary Language Association)'),
                '2-letter' => 'ia',
                '3-letter' => 'ina',
            ],
            [
                'name' => Craft::t('formie', 'Interlingue; Occidental'),
                '2-letter' => 'ie',
                '3-letter' => 'ile',
            ],
            [
                'name' => Craft::t('formie', 'Inuktitut'),
                '2-letter' => 'iu',
                '3-letter' => 'iku',
            ],
            [
                'name' => Craft::t('formie', 'Inupiaq'),
                '2-letter' => 'ik',
                '3-letter' => 'ipk',
            ],
            [
                'name' => Craft::t('formie', 'Irish'),
                '2-letter' => 'ga',
                '3-letter' => 'gle',
            ],
            [
                'name' => Craft::t('formie', 'Italian'),
                '2-letter' => 'it',
                '3-letter' => 'ita',
            ],
            [
                'name' => Craft::t('formie', 'Japanese'),
                '2-letter' => 'ja',
                '3-letter' => 'jpn',
            ],
            [
                'name' => Craft::t('formie', 'Javanese'),
                '2-letter' => 'jv',
                '3-letter' => 'jav',
            ],
            [
                'name' => Craft::t('formie', 'Kalaallisut; Greenlandic'),
                '2-letter' => 'kl',
                '3-letter' => 'kal',
            ],
            [
                'name' => Craft::t('formie', 'Kannada'),
                '2-letter' => 'kn',
                '3-letter' => 'kan',
            ],
            [
                'name' => Craft::t('formie', 'Kanuri'),
                '2-letter' => 'kr',
                '3-letter' => 'kau',
            ],
            [
                'name' => Craft::t('formie', 'Kashmiri'),
                '2-letter' => 'ks',
                '3-letter' => 'kas',
            ],
            [
                'name' => Craft::t('formie', 'Kazakh'),
                '2-letter' => 'kk',
                '3-letter' => 'kaz',
            ],
            [
                'name' => Craft::t('formie', 'Kikuyu; Gikuyu'),
                '2-letter' => 'ki',
                '3-letter' => 'kik',
            ],
            [
                'name' => Craft::t('formie', 'Kinyarwanda'),
                '2-letter' => 'rw',
                '3-letter' => 'kin',
            ],
            [
                'name' => Craft::t('formie', 'Kirghiz; Kyrgyz'),
                '2-letter' => 'ky',
                '3-letter' => 'kir',
            ],
            [
                'name' => Craft::t('formie', 'Komi'),
                '2-letter' => 'kv',
                '3-letter' => 'kom',
            ],
            [
                'name' => Craft::t('formie', 'Kongo'),
                '2-letter' => 'kg',
                '3-letter' => 'kon',
            ],
            [
                'name' => Craft::t('formie', 'Korean'),
                '2-letter' => 'ko',
                '3-letter' => 'kor',
            ],
            [
                'name' => Craft::t('formie', 'Kuanyama; Kwanyama'),
                '2-letter' => 'kj',
                '3-letter' => 'kua',
            ],
            [
                'name' => Craft::t('formie', 'Kurdish'),
                '2-letter' => 'ku',
                '3-letter' => 'kur',
            ],
            [
                'name' => Craft::t('formie', 'Lao'),
                '2-letter' => 'lo',
                '3-letter' => 'lao',
            ],
            [
                'name' => Craft::t('formie', 'Latin'),
                '2-letter' => 'la',
                '3-letter' => 'lat',
            ],
            [
                'name' => Craft::t('formie', 'Latvian'),
                '2-letter' => 'lv',
                '3-letter' => 'lav',
            ],
            [
                'name' => Craft::t('formie', 'Limburgan; Limburger; Limburgish'),
                '2-letter' => 'li',
                '3-letter' => 'lim',
            ],
            [
                'name' => Craft::t('formie', 'Lingala'),
                '2-letter' => 'ln',
                '3-letter' => 'lin',
            ],
            [
                'name' => Craft::t('formie', 'Lithuanian'),
                '2-letter' => 'lt',
                '3-letter' => 'lit',
            ],
            [
                'name' => Craft::t('formie', 'Luba-Katanga'),
                '2-letter' => 'lu',
                '3-letter' => 'lub',
            ],
            [
                'name' => Craft::t('formie', 'Luxembourgish; Letzeburgesch'),
                '2-letter' => 'lb',
                '3-letter' => 'ltz',
            ],
            [
                'name' => Craft::t('formie', 'Macedonian'),
                '2-letter' => 'mk',
                '3-letter' => 'mac',
            ],
            [
                'name' => Craft::t('formie', 'Malagasy'),
                '2-letter' => 'mg',
                '3-letter' => 'mlg',
            ],
            [
                'name' => Craft::t('formie', 'Malay'),
                '2-letter' => 'ms',
                '3-letter' => 'may',
            ],
            [
                'name' => Craft::t('formie', 'Malayalam'),
                '2-letter' => 'ml',
                '3-letter' => 'mal',
            ],
            [
                'name' => Craft::t('formie', 'Maltese'),
                '2-letter' => 'mt',
                '3-letter' => 'mlt',
            ],
            [
                'name' => Craft::t('formie', 'Manx'),
                '2-letter' => 'gv',
                '3-letter' => 'glv',
            ],
            [
                'name' => Craft::t('formie', 'Maori'),
                '2-letter' => 'mi',
                '3-letter' => 'mao',
            ],
            [
                'name' => Craft::t('formie', 'Marathi'),
                '2-letter' => 'mr',
                '3-letter' => 'mar',
            ],
            [
                'name' => Craft::t('formie', 'Marshallese'),
                '2-letter' => 'mh',
                '3-letter' => 'mah',
            ],
            [
                'name' => Craft::t('formie', 'Mongolian'),
                '2-letter' => 'mn',
                '3-letter' => 'mon',
            ],
            [
                'name' => Craft::t('formie', 'Nauru'),
                '2-letter' => 'na',
                '3-letter' => 'nau',
            ],
            [
                'name' => Craft::t('formie', 'Navajo; Navaho'),
                '2-letter' => 'nv',
                '3-letter' => 'nav',
            ],
            [
                'name' => Craft::t('formie', 'Ndebele, North; North Ndebele'),
                '2-letter' => 'nd',
                '3-letter' => 'nde',
            ],
            [
                'name' => Craft::t('formie', 'Ndebele, South; South Ndebele'),
                '2-letter' => 'nr',
                '3-letter' => 'nbl',
            ],
            [
                'name' => Craft::t('formie', 'Ndonga'),
                '2-letter' => 'ng',
                '3-letter' => 'ndo',
            ],
            [
                'name' => Craft::t('formie', 'Nepali'),
                '2-letter' => 'ne',
                '3-letter' => 'nep',
            ],
            [
                'name' => Craft::t('formie', 'Northern Sami'),
                '2-letter' => 'se',
                '3-letter' => 'sme',
            ],
            [
                'name' => Craft::t('formie', 'Norwegian'),
                '2-letter' => 'no',
                '3-letter' => 'nor',
            ],
            [
                'name' => Craft::t('formie', 'Norwegian Nynorsk; Nynorsk, Norwegian'),
                '2-letter' => 'nn',
                '3-letter' => 'nno',
            ],
            [
                'name' => Craft::t('formie', 'Occitan (post 1500)'),
                '2-letter' => 'oc',
                '3-letter' => 'oci',
            ],
            [
                'name' => Craft::t('formie', 'Ojibwa'),
                '2-letter' => 'oj',
                '3-letter' => 'oji',
            ],
            [
                'name' => Craft::t('formie', 'Oriya'),
                '2-letter' => 'or',
                '3-letter' => 'ori',
            ],
            [
                'name' => Craft::t('formie', 'Oromo'),
                '2-letter' => 'om',
                '3-letter' => 'orm',
            ],
            [
                'name' => Craft::t('formie', 'Ossetian; Ossetic'),
                '2-letter' => 'os',
                '3-letter' => 'oss',
            ],
            [
                'name' => Craft::t('formie', 'Pali'),
                '2-letter' => 'pi',
                '3-letter' => 'pli',
            ],
            [
                'name' => Craft::t('formie', 'Panjabi; Punjabi'),
                '2-letter' => 'pa',
                '3-letter' => 'pan',
            ],
            [
                'name' => Craft::t('formie', 'Persian'),
                '2-letter' => 'fa',
                '3-letter' => 'per',
            ],
            [
                'name' => Craft::t('formie', 'Polish'),
                '2-letter' => 'pl',
                '3-letter' => 'pol',
            ],
            [
                'name' => Craft::t('formie', 'Portuguese'),
                '2-letter' => 'pt',
                '3-letter' => 'por',
            ],
            [
                'name' => Craft::t('formie', 'Pushto; Pashto'),
                '2-letter' => 'ps',
                '3-letter' => 'pus',
            ],
            [
                'name' => Craft::t('formie', 'Quechua'),
                '2-letter' => 'qu',
                '3-letter' => 'que',
            ],
            [
                'name' => Craft::t('formie', 'Romanian; Moldavian; Moldovan'),
                '2-letter' => 'ro',
                '3-letter' => 'rum',
            ],
            [
                'name' => Craft::t('formie', 'Romansh'),
                '2-letter' => 'rm',
                '3-letter' => 'roh',
            ],
            [
                'name' => Craft::t('formie', 'Rundi'),
                '2-letter' => 'rn',
                '3-letter' => 'run',
            ],
            [
                'name' => Craft::t('formie', 'Russian'),
                '2-letter' => 'ru',
                '3-letter' => 'rus',
            ],
            [
                'name' => Craft::t('formie', 'Samoan'),
                '2-letter' => 'sm',
                '3-letter' => 'smo',
            ],
            [
                'name' => Craft::t('formie', 'Sango'),
                '2-letter' => 'sg',
                '3-letter' => 'sag',
            ],
            [
                'name' => Craft::t('formie', 'Sanskrit'),
                '2-letter' => 'sa',
                '3-letter' => 'san',
            ],
            [
                'name' => Craft::t('formie', 'Sardinian'),
                '2-letter' => 'sc',
                '3-letter' => 'srd',
            ],
            [
                'name' => Craft::t('formie', 'Serbian'),
                '2-letter' => 'sr',
                '3-letter' => 'srp',
            ],
            [
                'name' => Craft::t('formie', 'Shona'),
                '2-letter' => 'sn',
                '3-letter' => 'sna',
            ],
            [
                'name' => Craft::t('formie', 'Sichuan Yi; Nuosu'),
                '2-letter' => 'ii',
                '3-letter' => 'iii',
            ],
            [
                'name' => Craft::t('formie', 'Sindhi'),
                '2-letter' => 'sd',
                '3-letter' => 'snd',
            ],
            [
                'name' => Craft::t('formie', 'Sinhala; Sinhalese'),
                '2-letter' => 'si',
                '3-letter' => 'sin',
            ],
            [
                'name' => Craft::t('formie', 'Slovak'),
                '2-letter' => 'sk',
                '3-letter' => 'slo',
            ],
            [
                'name' => Craft::t('formie', 'Slovenian'),
                '2-letter' => 'sl',
                '3-letter' => 'slv',
            ],
            [
                'name' => Craft::t('formie', 'Somali'),
                '2-letter' => 'so',
                '3-letter' => 'som',
            ],
            [
                'name' => Craft::t('formie', 'Sotho, Southern'),
                '2-letter' => 'st',
                '3-letter' => 'sot',
            ],
            [
                'name' => Craft::t('formie', 'Spanish; Castilian'),
                '2-letter' => 'es',
                '3-letter' => 'spa',
            ],
            [
                'name' => Craft::t('formie', 'Sundanese'),
                '2-letter' => 'su',
                '3-letter' => 'sun',
            ],
            [
                'name' => Craft::t('formie', 'Swahili'),
                '2-letter' => 'sw',
                '3-letter' => 'swa',
            ],
            [
                'name' => Craft::t('formie', 'Swati'),
                '2-letter' => 'ss',
                '3-letter' => 'ssw',
            ],
            [
                'name' => Craft::t('formie', 'Swedish'),
                '2-letter' => 'sv',
                '3-letter' => 'swe',
            ],
            [
                'name' => Craft::t('formie', 'Tagalog'),
                '2-letter' => 'tl',
                '3-letter' => 'tgl',
            ],
            [
                'name' => Craft::t('formie', 'Tahitian'),
                '2-letter' => 'ty',
                '3-letter' => 'tah',
            ],
            [
                'name' => Craft::t('formie', 'Tajik'),
                '2-letter' => 'tg',
                '3-letter' => 'tgk',
            ],
            [
                'name' => Craft::t('formie', 'Tamil'),
                '2-letter' => 'ta',
                '3-letter' => 'tam',
            ],
            [
                'name' => Craft::t('formie', 'Tatar'),
                '2-letter' => 'tt',
                '3-letter' => 'tat',
            ],
            [
                'name' => Craft::t('formie', 'Telugu'),
                '2-letter' => 'te',
                '3-letter' => 'tel',
            ],
            [
                'name' => Craft::t('formie', 'Thai'),
                '2-letter' => 'th',
                '3-letter' => 'tha',
            ],
            [
                'name' => Craft::t('formie', 'Tibetan'),
                '2-letter' => 'bo',
                '3-letter' => 'tib',
            ],
            [
                'name' => Craft::t('formie', 'Tigrinya'),
                '2-letter' => 'ti',
                '3-letter' => 'tir',
            ],
            [
                'name' => Craft::t('formie', 'Tonga (Tonga Islands)'),
                '2-letter' => 'to',
                '3-letter' => 'ton',
            ],
            [
                'name' => Craft::t('formie', 'Tsonga'),
                '2-letter' => 'ts',
                '3-letter' => 'tso',
            ],
            [
                'name' => Craft::t('formie', 'Tswana'),
                '2-letter' => 'tn',
                '3-letter' => 'tsn',
            ],
            [
                'name' => Craft::t('formie', 'Turkish'),
                '2-letter' => 'tr',
                '3-letter' => 'tur',
            ],
            [
                'name' => Craft::t('formie', 'Turkmen'),
                '2-letter' => 'tk',
                '3-letter' => 'tuk',
            ],
            [
                'name' => Craft::t('formie', 'Twi'),
                '2-letter' => 'tw',
                '3-letter' => 'twi',
            ],
            [
                'name' => Craft::t('formie', 'Uighur; Uyghur'),
                '2-letter' => 'ug',
                '3-letter' => 'uig',
            ],
            [
                'name' => Craft::t('formie', 'Ukrainian'),
                '2-letter' => 'uk',
                '3-letter' => 'ukr',
            ],
            [
                'name' => Craft::t('formie', 'Urdu'),
                '2-letter' => 'ur',
                '3-letter' => 'urd',
            ],
            [
                'name' => Craft::t('formie', 'Uzbek'),
                '2-letter' => 'uz',
                '3-letter' => 'uzb',
            ],
            [
                'name' => Craft::t('formie', 'Venda'),
                '2-letter' => 've',
                '3-letter' => 'ven',
            ],
            [
                'name' => Craft::t('formie', 'Vietnamese'),
                '2-letter' => 'vi',
                '3-letter' => 'vie',
            ],
            [
                'name' => Craft::t('formie', 'Volapük'),
                '2-letter' => 'vo',
                '3-letter' => 'vol',
            ],
            [
                'name' => Craft::t('formie', 'Walloon'),
                '2-letter' => 'wa',
                '3-letter' => 'wln',
            ],
            [
                'name' => Craft::t('formie', 'Welsh'),
                '2-letter' => 'cy',
                '3-letter' => 'wel',
            ],
            [
                'name' => Craft::t('formie', 'Western Frisian'),
                '2-letter' => 'fy',
                '3-letter' => 'fry',
            ],
            [
                'name' => Craft::t('formie', 'Wolof'),
                '2-letter' => 'wo',
                '3-letter' => 'wol',
            ],
            [
                'name' => Craft::t('formie', 'Xhosa'),
                '2-letter' => 'xh',
                '3-letter' => 'xho',
            ],
            [
                'name' => Craft::t('formie', 'Yiddish'),
                '2-letter' => 'yi',
                '3-letter' => 'yid',
            ],
            [
                'name' => Craft::t('formie', 'Yoruba'),
                '2-letter' => 'yo',
                '3-letter' => 'yor',
            ],
            [
                'name' => Craft::t('formie', 'Zhuang; Chuang'),
                '2-letter' => 'za',
                '3-letter' => 'zha',
            ],
            [
                'name' => Craft::t('formie', 'Zulu'),
                '2-letter' => 'zu',
                '3-letter' => 'zul',
            ],
        ];
    }
}
