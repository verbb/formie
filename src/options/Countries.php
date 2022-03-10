<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Countries extends PredefinedOption
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
        return Craft::t('formie', 'Countries');
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
                'name' => Craft::t('formie', 'Afghanistan'),
                '2-letter' => 'AF',
                '3-letter' => 'AFG',
            ],
            [
                'name' => Craft::t('formie', 'Albania'),
                '2-letter' => 'AL',
                '3-letter' => 'ALB',
            ],
            [
                'name' => Craft::t('formie', 'Algeria'),
                '2-letter' => 'DZ',
                '3-letter' => 'DZA',
            ],
            [
                'name' => Craft::t('formie', 'American Samoa'),
                '2-letter' => 'AS',
                '3-letter' => 'ASM',
            ],
            [
                'name' => Craft::t('formie', 'Andorra'),
                '2-letter' => 'AD',
                '3-letter' => 'AND',
            ],
            [
                'name' => Craft::t('formie', 'Angola'),
                '2-letter' => 'AO',
                '3-letter' => 'AGO',
            ],
            [
                'name' => Craft::t('formie', 'Anguilla'),
                '2-letter' => 'AI',
                '3-letter' => 'AIA',
            ],
            [
                'name' => Craft::t('formie', 'Antarctica'),
                '2-letter' => 'AQ',
                '3-letter' => 'ATA',
            ],
            [
                'name' => Craft::t('formie', 'Antigua & Barbuda'),
                '2-letter' => 'AG',
                '3-letter' => 'ATG',
            ],
            [
                'name' => Craft::t('formie', 'Argentina'),
                '2-letter' => 'AR',
                '3-letter' => 'ARG',
            ],
            [
                'name' => Craft::t('formie', 'Armenia'),
                '2-letter' => 'AM',
                '3-letter' => 'ARM',
            ],
            [
                'name' => Craft::t('formie', 'Aruba'),
                '2-letter' => 'AW',
                '3-letter' => 'ABW',
            ],
            [
                'name' => Craft::t('formie', 'Australia'),
                '2-letter' => 'AU',
                '3-letter' => 'AUS',
            ],
            [
                'name' => Craft::t('formie', 'Austria'),
                '2-letter' => 'AT',
                '3-letter' => 'AUT',
            ],
            [
                'name' => Craft::t('formie', 'Azerbaijan'),
                '2-letter' => 'AZ',
                '3-letter' => 'AZE',
            ],
            [
                'name' => Craft::t('formie', 'Bahamas'),
                '2-letter' => 'BS',
                '3-letter' => 'BHS',
            ],
            [
                'name' => Craft::t('formie', 'Bahrain'),
                '2-letter' => 'BH',
                '3-letter' => 'BHR',
            ],
            [
                'name' => Craft::t('formie', 'Bangladesh'),
                '2-letter' => 'BD',
                '3-letter' => 'BGD',
            ],
            [
                'name' => Craft::t('formie', 'Barbados'),
                '2-letter' => 'BB',
                '3-letter' => 'BRB',
            ],
            [
                'name' => Craft::t('formie', 'Belarus'),
                '2-letter' => 'BY',
                '3-letter' => 'BLR',
            ],
            [
                'name' => Craft::t('formie', 'Belgium'),
                '2-letter' => 'BE',
                '3-letter' => 'BEL',
            ],
            [
                'name' => Craft::t('formie', 'Belize'),
                '2-letter' => 'BZ',
                '3-letter' => 'BLZ',
            ],
            [
                'name' => Craft::t('formie', 'Benin'),
                '2-letter' => 'BJ',
                '3-letter' => 'BEN',
            ],
            [
                'name' => Craft::t('formie', 'Bermuda'),
                '2-letter' => 'BM',
                '3-letter' => 'BMU',
            ],
            [
                'name' => Craft::t('formie', 'Bhutan'),
                '2-letter' => 'BT',
                '3-letter' => 'BTN',
            ],
            [
                'name' => Craft::t('formie', 'Bolivia'),
                '2-letter' => 'BO',
                '3-letter' => 'BOL',
            ],
            [
                'name' => Craft::t('formie', 'Bosnia'),
                '2-letter' => 'BA',
                '3-letter' => 'BIH',
            ],
            [
                'name' => Craft::t('formie', 'Botswana'),
                '2-letter' => 'BW',
                '3-letter' => 'BWA',
            ],
            [
                'name' => Craft::t('formie', 'Bouvet Island'),
                '2-letter' => 'BV',
                '3-letter' => 'BVT',
            ],
            [
                'name' => Craft::t('formie', 'Brazil'),
                '2-letter' => 'BR',
                '3-letter' => 'BRA',
            ],
            [
                'name' => Craft::t('formie', 'British Indian Ocean Territory'),
                '2-letter' => 'IO',
                '3-letter' => 'IOT',
            ],
            [
                'name' => Craft::t('formie', 'British Virgin Islands'),
                '2-letter' => 'VG',
                '3-letter' => 'VGB',
            ],
            [
                'name' => Craft::t('formie', 'Brunei'),
                '2-letter' => 'BN',
                '3-letter' => 'BRN',
            ],
            [
                'name' => Craft::t('formie', 'Bulgaria'),
                '2-letter' => 'BG',
                '3-letter' => 'BGR',
            ],
            [
                'name' => Craft::t('formie', 'Burkina Faso'),
                '2-letter' => 'BF',
                '3-letter' => 'BFA',
            ],
            [
                'name' => Craft::t('formie', 'Burundi'),
                '2-letter' => 'BI',
                '3-letter' => 'BDI',
            ],
            [
                'name' => Craft::t('formie', 'Cambodia'),
                '2-letter' => 'KH',
                '3-letter' => 'KHM',
            ],
            [
                'name' => Craft::t('formie', 'Cameroon'),
                '2-letter' => 'CM',
                '3-letter' => 'CMR',
            ],
            [
                'name' => Craft::t('formie', 'Canada'),
                '2-letter' => 'CA',
                '3-letter' => 'CAN',
            ],
            [
                'name' => Craft::t('formie', 'Cape Verde'),
                '2-letter' => 'CV',
                '3-letter' => 'CPV',
            ],
            [
                'name' => Craft::t('formie', 'Caribbean Netherlands'),
                '2-letter' => 'BQ',
                '3-letter' => 'BES',
            ],
            [
                'name' => Craft::t('formie', 'Cayman Islands'),
                '2-letter' => 'KY',
                '3-letter' => 'CYM',
            ],
            [
                'name' => Craft::t('formie', 'Central African Republic'),
                '2-letter' => 'CF',
                '3-letter' => 'CAF',
            ],
            [
                'name' => Craft::t('formie', 'Chad'),
                '2-letter' => 'TD',
                '3-letter' => 'TCD',
            ],
            [
                'name' => Craft::t('formie', 'Chile'),
                '2-letter' => 'CL',
                '3-letter' => 'CHL',
            ],
            [
                'name' => Craft::t('formie', 'China'),
                '2-letter' => 'CN',
                '3-letter' => 'CHN',
            ],
            [
                'name' => Craft::t('formie', 'Christmas Island'),
                '2-letter' => 'CX',
                '3-letter' => 'CXR',
            ],
            [
                'name' => Craft::t('formie', 'Cocos (Keeling) Islands'),
                '2-letter' => 'CC',
                '3-letter' => 'CCK',
            ],
            [
                'name' => Craft::t('formie', 'Colombia'),
                '2-letter' => 'CO',
                '3-letter' => 'COL',
            ],
            [
                'name' => Craft::t('formie', 'Comoros'),
                '2-letter' => 'KM',
                '3-letter' => 'COM',
            ],
            [
                'name' => Craft::t('formie', 'Congo - Brazzaville'),
                '2-letter' => 'CG',
                '3-letter' => 'COG',
            ],
            [
                'name' => Craft::t('formie', 'Congo - Kinshasa'),
                '2-letter' => 'CD',
                '3-letter' => 'COD',
            ],
            [
                'name' => Craft::t('formie', 'Cook Islands'),
                '2-letter' => 'CK',
                '3-letter' => 'COK',
            ],
            [
                'name' => Craft::t('formie', 'Costa Rica'),
                '2-letter' => 'CR',
                '3-letter' => 'CRI',
            ],
            [
                'name' => Craft::t('formie', 'Croatia'),
                '2-letter' => 'HR',
                '3-letter' => 'HRV',
            ],
            [
                'name' => Craft::t('formie', 'Cuba'),
                '2-letter' => 'CU',
                '3-letter' => 'CUB',
            ],
            [
                'name' => Craft::t('formie', 'Curaçao'),
                '2-letter' => 'CW',
                '3-letter' => 'CUW',
            ],
            [
                'name' => Craft::t('formie', 'Cyprus'),
                '2-letter' => 'CY',
                '3-letter' => 'CYP',
            ],
            [
                'name' => Craft::t('formie', 'Czechia'),
                '2-letter' => 'CZ',
                '3-letter' => 'CZE',
            ],
            [
                'name' => Craft::t('formie', 'Côte d’Ivoire'),
                '2-letter' => 'CI',
                '3-letter' => 'CIV',
            ],
            [
                'name' => Craft::t('formie', 'Denmark'),
                '2-letter' => 'DK',
                '3-letter' => 'DNK',
            ],
            [
                'name' => Craft::t('formie', 'Djibouti'),
                '2-letter' => 'DJ',
                '3-letter' => 'DJI',
            ],
            [
                'name' => Craft::t('formie', 'Dominica'),
                '2-letter' => 'DM',
                '3-letter' => 'DMA',
            ],
            [
                'name' => Craft::t('formie', 'Dominican Republic'),
                '2-letter' => 'DO',
                '3-letter' => 'DOM',
            ],
            [
                'name' => Craft::t('formie', 'Ecuador'),
                '2-letter' => 'EC',
                '3-letter' => 'ECU',
            ],
            [
                'name' => Craft::t('formie', 'Egypt'),
                '2-letter' => 'EG',
                '3-letter' => 'EGY',
            ],
            [
                'name' => Craft::t('formie', 'El Salvador'),
                '2-letter' => 'SV',
                '3-letter' => 'SLV',
            ],
            [
                'name' => Craft::t('formie', 'Equatorial Guinea'),
                '2-letter' => 'GQ',
                '3-letter' => 'GNQ',
            ],
            [
                'name' => Craft::t('formie', 'Eritrea'),
                '2-letter' => 'ER',
                '3-letter' => 'ERI',
            ],
            [
                'name' => Craft::t('formie', 'Estonia'),
                '2-letter' => 'EE',
                '3-letter' => 'EST',
            ],
            [
                'name' => Craft::t('formie', 'Eswatini'),
                '2-letter' => 'SZ',
                '3-letter' => 'SWZ',
            ],
            [
                'name' => Craft::t('formie', 'Ethiopia'),
                '2-letter' => 'ET',
                '3-letter' => 'ETH',
            ],
            [
                'name' => Craft::t('formie', 'Falkland Islands'),
                '2-letter' => 'FK',
                '3-letter' => 'FLK',
            ],
            [
                'name' => Craft::t('formie', 'Faroe Islands'),
                '2-letter' => 'FO',
                '3-letter' => 'FRO',
            ],
            [
                'name' => Craft::t('formie', 'Fiji'),
                '2-letter' => 'FJ',
                '3-letter' => 'FJI',
            ],
            [
                'name' => Craft::t('formie', 'Finland'),
                '2-letter' => 'FI',
                '3-letter' => 'FIN',
            ],
            [
                'name' => Craft::t('formie', 'France'),
                '2-letter' => 'FR',
                '3-letter' => 'FRA',
            ],
            [
                'name' => Craft::t('formie', 'French Guiana'),
                '2-letter' => 'GF',
                '3-letter' => 'GUF',
            ],
            [
                'name' => Craft::t('formie', 'French Polynesia'),
                '2-letter' => 'PF',
                '3-letter' => 'PYF',
            ],
            [
                'name' => Craft::t('formie', 'French Southern Territories'),
                '2-letter' => 'TF',
                '3-letter' => 'ATF',
            ],
            [
                'name' => Craft::t('formie', 'Gabon'),
                '2-letter' => 'GA',
                '3-letter' => 'GAB',
            ],
            [
                'name' => Craft::t('formie', 'Gambia'),
                '2-letter' => 'GM',
                '3-letter' => 'GMB',
            ],
            [
                'name' => Craft::t('formie', 'Georgia'),
                '2-letter' => 'GE',
                '3-letter' => 'GEO',
            ],
            [
                'name' => Craft::t('formie', 'Germany'),
                '2-letter' => 'DE',
                '3-letter' => 'DEU',
            ],
            [
                'name' => Craft::t('formie', 'Ghana'),
                '2-letter' => 'GH',
                '3-letter' => 'GHA',
            ],
            [
                'name' => Craft::t('formie', 'Gibraltar'),
                '2-letter' => 'GI',
                '3-letter' => 'GIB',
            ],
            [
                'name' => Craft::t('formie', 'Greece'),
                '2-letter' => 'GR',
                '3-letter' => 'GRC',
            ],
            [
                'name' => Craft::t('formie', 'Greenland'),
                '2-letter' => 'GL',
                '3-letter' => 'GRL',
            ],
            [
                'name' => Craft::t('formie', 'Grenada'),
                '2-letter' => 'GD',
                '3-letter' => 'GRD',
            ],
            [
                'name' => Craft::t('formie', 'Guadeloupe'),
                '2-letter' => 'GP',
                '3-letter' => 'GLP',
            ],
            [
                'name' => Craft::t('formie', 'Guam'),
                '2-letter' => 'GU',
                '3-letter' => 'GUM',
            ],
            [
                'name' => Craft::t('formie', 'Guatemala'),
                '2-letter' => 'GT',
                '3-letter' => 'GTM',
            ],
            [
                'name' => Craft::t('formie', 'Guernsey'),
                '2-letter' => 'GG',
                '3-letter' => 'GGY',
            ],
            [
                'name' => Craft::t('formie', 'Guinea'),
                '2-letter' => 'GN',
                '3-letter' => 'GIN',
            ],
            [
                'name' => Craft::t('formie', 'Guinea-Bissau'),
                '2-letter' => 'GW',
                '3-letter' => 'GNB',
            ],
            [
                'name' => Craft::t('formie', 'Guyana'),
                '2-letter' => 'GY',
                '3-letter' => 'GUY',
            ],
            [
                'name' => Craft::t('formie', 'Haiti'),
                '2-letter' => 'HT',
                '3-letter' => 'HTI',
            ],
            [
                'name' => Craft::t('formie', 'Heard & McDonald Islands'),
                '2-letter' => 'HM',
                '3-letter' => 'HMD',
            ],
            [
                'name' => Craft::t('formie', 'Honduras'),
                '2-letter' => 'HN',
                '3-letter' => 'HND',
            ],
            [
                'name' => Craft::t('formie', 'Hong Kong'),
                '2-letter' => 'HK',
                '3-letter' => 'HKG',
            ],
            [
                'name' => Craft::t('formie', 'Hungary'),
                '2-letter' => 'HU',
                '3-letter' => 'HUN',
            ],
            [
                'name' => Craft::t('formie', 'Iceland'),
                '2-letter' => 'IS',
                '3-letter' => 'ISL',
            ],
            [
                'name' => Craft::t('formie', 'India'),
                '2-letter' => 'IN',
                '3-letter' => 'IND',
            ],
            [
                'name' => Craft::t('formie', 'Indonesia'),
                '2-letter' => 'ID',
                '3-letter' => 'IDN',
            ],
            [
                'name' => Craft::t('formie', 'Iran'),
                '2-letter' => 'IR',
                '3-letter' => 'IRN',
            ],
            [
                'name' => Craft::t('formie', 'Iraq'),
                '2-letter' => 'IQ',
                '3-letter' => 'IRQ',
            ],
            [
                'name' => Craft::t('formie', 'Ireland'),
                '2-letter' => 'IE',
                '3-letter' => 'IRL',
            ],
            [
                'name' => Craft::t('formie', 'Isle of Man'),
                '2-letter' => 'IM',
                '3-letter' => 'IMN',
            ],
            [
                'name' => Craft::t('formie', 'Israel'),
                '2-letter' => 'IL',
                '3-letter' => 'ISR',
            ],
            [
                'name' => Craft::t('formie', 'Italy'),
                '2-letter' => 'IT',
                '3-letter' => 'ITA',
            ],
            [
                'name' => Craft::t('formie', 'Jamaica'),
                '2-letter' => 'JM',
                '3-letter' => 'JAM',
            ],
            [
                'name' => Craft::t('formie', 'Japan'),
                '2-letter' => 'JP',
                '3-letter' => 'JPN',
            ],
            [
                'name' => Craft::t('formie', 'Jersey'),
                '2-letter' => 'JE',
                '3-letter' => 'JEY',
            ],
            [
                'name' => Craft::t('formie', 'Jordan'),
                '2-letter' => 'JO',
                '3-letter' => 'JOR',
            ],
            [
                'name' => Craft::t('formie', 'Kazakhstan'),
                '2-letter' => 'KZ',
                '3-letter' => 'KAZ',
            ],
            [
                'name' => Craft::t('formie', 'Kenya'),
                '2-letter' => 'KE',
                '3-letter' => 'KEN',
            ],
            [
                'name' => Craft::t('formie', 'Kiribati'),
                '2-letter' => 'KI',
                '3-letter' => 'KIR',
            ],
            [
                'name' => Craft::t('formie', 'Kuwait'),
                '2-letter' => 'KW',
                '3-letter' => 'KWT',
            ],
            [
                'name' => Craft::t('formie', 'Kyrgyzstan'),
                '2-letter' => 'KG',
                '3-letter' => 'KGZ',
            ],
            [
                'name' => Craft::t('formie', 'Laos'),
                '2-letter' => 'LA',
                '3-letter' => 'LAO',
            ],
            [
                'name' => Craft::t('formie', 'Latvia'),
                '2-letter' => 'LV',
                '3-letter' => 'LVA',
            ],
            [
                'name' => Craft::t('formie', 'Lebanon'),
                '2-letter' => 'LB',
                '3-letter' => 'LBN',
            ],
            [
                'name' => Craft::t('formie', 'Lesotho'),
                '2-letter' => 'LS',
                '3-letter' => 'LSO',
            ],
            [
                'name' => Craft::t('formie', 'Liberia'),
                '2-letter' => 'LR',
                '3-letter' => 'LBR',
            ],
            [
                'name' => Craft::t('formie', 'Libya'),
                '2-letter' => 'LY',
                '3-letter' => 'LBY',
            ],
            [
                'name' => Craft::t('formie', 'Liechtenstein'),
                '2-letter' => 'LI',
                '3-letter' => 'LIE',
            ],
            [
                'name' => Craft::t('formie', 'Lithuania'),
                '2-letter' => 'LT',
                '3-letter' => 'LTU',
            ],
            [
                'name' => Craft::t('formie', 'Luxembourg'),
                '2-letter' => 'LU',
                '3-letter' => 'LUX',
            ],
            [
                'name' => Craft::t('formie', 'Macau'),
                '2-letter' => 'MO',
                '3-letter' => 'MAC',
            ],
            [
                'name' => Craft::t('formie', 'Madagascar'),
                '2-letter' => 'MG',
                '3-letter' => 'MDG',
            ],
            [
                'name' => Craft::t('formie', 'Malawi'),
                '2-letter' => 'MW',
                '3-letter' => 'MWI',
            ],
            [
                'name' => Craft::t('formie', 'Malaysia'),
                '2-letter' => 'MY',
                '3-letter' => 'MYS',
            ],
            [
                'name' => Craft::t('formie', 'Maldives'),
                '2-letter' => 'MV',
                '3-letter' => 'MDV',
            ],
            [
                'name' => Craft::t('formie', 'Mali'),
                '2-letter' => 'ML',
                '3-letter' => 'MLI',
            ],
            [
                'name' => Craft::t('formie', 'Malta'),
                '2-letter' => 'MT',
                '3-letter' => 'MLT',
            ],
            [
                'name' => Craft::t('formie', 'Marshall Islands'),
                '2-letter' => 'MH',
                '3-letter' => 'MHL',
            ],
            [
                'name' => Craft::t('formie', 'Martinique'),
                '2-letter' => 'MQ',
                '3-letter' => 'MTQ',
            ],
            [
                'name' => Craft::t('formie', 'Mauritania'),
                '2-letter' => 'MR',
                '3-letter' => 'MRT',
            ],
            [
                'name' => Craft::t('formie', 'Mauritius'),
                '2-letter' => 'MU',
                '3-letter' => 'MUS',
            ],
            [
                'name' => Craft::t('formie', 'Mayotte'),
                '2-letter' => 'YT',
                '3-letter' => 'MYT',
            ],
            [
                'name' => Craft::t('formie', 'Mexico'),
                '2-letter' => 'MX',
                '3-letter' => 'MEX',
            ],
            [
                'name' => Craft::t('formie', 'Micronesia'),
                '2-letter' => 'FM',
                '3-letter' => 'FSM',
            ],
            [
                'name' => Craft::t('formie', 'Moldova'),
                '2-letter' => 'MD',
                '3-letter' => 'MDA',
            ],
            [
                'name' => Craft::t('formie', 'Monaco'),
                '2-letter' => 'MC',
                '3-letter' => 'MCO',
            ],
            [
                'name' => Craft::t('formie', 'Mongolia'),
                '2-letter' => 'MN',
                '3-letter' => 'MNG',
            ],
            [
                'name' => Craft::t('formie', 'Montenegro'),
                '2-letter' => 'ME',
                '3-letter' => 'MNE',
            ],
            [
                'name' => Craft::t('formie', 'Montserrat'),
                '2-letter' => 'MS',
                '3-letter' => 'MSR',
            ],
            [
                'name' => Craft::t('formie', 'Morocco'),
                '2-letter' => 'MA',
                '3-letter' => 'MAR',
            ],
            [
                'name' => Craft::t('formie', 'Mozambique'),
                '2-letter' => 'MZ',
                '3-letter' => 'MOZ',
            ],
            [
                'name' => Craft::t('formie', 'Myanmar'),
                '2-letter' => 'MM',
                '3-letter' => 'MMR',
            ],
            [
                'name' => Craft::t('formie', 'Namibia'),
                '2-letter' => 'NA',
                '3-letter' => 'NAM',
            ],
            [
                'name' => Craft::t('formie', 'Nauru'),
                '2-letter' => 'NR',
                '3-letter' => 'NRU',
            ],
            [
                'name' => Craft::t('formie', 'Nepal'),
                '2-letter' => 'NP',
                '3-letter' => 'NPL',
            ],
            [
                'name' => Craft::t('formie', 'Netherlands'),
                '2-letter' => 'NL',
                '3-letter' => 'NLD',
            ],
            [
                'name' => Craft::t('formie', 'New Caledonia'),
                '2-letter' => 'NC',
                '3-letter' => 'NCL',
            ],
            [
                'name' => Craft::t('formie', 'New Zealand'),
                '2-letter' => 'NZ',
                '3-letter' => 'NZL',
            ],
            [
                'name' => Craft::t('formie', 'Nicaragua'),
                '2-letter' => 'NI',
                '3-letter' => 'NIC',
            ],
            [
                'name' => Craft::t('formie', 'Niger'),
                '2-letter' => 'NE',
                '3-letter' => 'NER',
            ],
            [
                'name' => Craft::t('formie', 'Nigeria'),
                '2-letter' => 'NG',
                '3-letter' => 'NGA',
            ],
            [
                'name' => Craft::t('formie', 'Niue'),
                '2-letter' => 'NU',
                '3-letter' => 'NIU',
            ],
            [
                'name' => Craft::t('formie', 'Norfolk Island'),
                '2-letter' => 'NF',
                '3-letter' => 'NFK',
            ],
            [
                'name' => Craft::t('formie', 'North Korea'),
                '2-letter' => 'KP',
                '3-letter' => 'PRK',
            ],
            [
                'name' => Craft::t('formie', 'North Macedonia'),
                '2-letter' => 'MK',
                '3-letter' => 'MKD',
            ],
            [
                'name' => Craft::t('formie', 'Northern Mariana Islands'),
                '2-letter' => 'MP',
                '3-letter' => 'MNP',
            ],
            [
                'name' => Craft::t('formie', 'Norway'),
                '2-letter' => 'NO',
                '3-letter' => 'NOR',
            ],
            [
                'name' => Craft::t('formie', 'Oman'),
                '2-letter' => 'OM',
                '3-letter' => 'OMN',
            ],
            [
                'name' => Craft::t('formie', 'Pakistan'),
                '2-letter' => 'PK',
                '3-letter' => 'PAK',
            ],
            [
                'name' => Craft::t('formie', 'Palau'),
                '2-letter' => 'PW',
                '3-letter' => 'PLW',
            ],
            [
                'name' => Craft::t('formie', 'Palestine'),
                '2-letter' => 'PS',
                '3-letter' => 'PSE',
            ],
            [
                'name' => Craft::t('formie', 'Panama'),
                '2-letter' => 'PA',
                '3-letter' => 'PAN',
            ],
            [
                'name' => Craft::t('formie', 'Papua New Guinea'),
                '2-letter' => 'PG',
                '3-letter' => 'PNG',
            ],
            [
                'name' => Craft::t('formie', 'Paraguay'),
                '2-letter' => 'PY',
                '3-letter' => 'PRY',
            ],
            [
                'name' => Craft::t('formie', 'Peru'),
                '2-letter' => 'PE',
                '3-letter' => 'PER',
            ],
            [
                'name' => Craft::t('formie', 'Philippines'),
                '2-letter' => 'PH',
                '3-letter' => 'PHL',
            ],
            [
                'name' => Craft::t('formie', 'Pitcairn Islands'),
                '2-letter' => 'PN',
                '3-letter' => 'PCN',
            ],
            [
                'name' => Craft::t('formie', 'Poland'),
                '2-letter' => 'PL',
                '3-letter' => 'POL',
            ],
            [
                'name' => Craft::t('formie', 'Portugal'),
                '2-letter' => 'PT',
                '3-letter' => 'PRT',
            ],
            [
                'name' => Craft::t('formie', 'Puerto Rico'),
                '2-letter' => 'PR',
                '3-letter' => 'PRI',
            ],
            [
                'name' => Craft::t('formie', 'Qatar'),
                '2-letter' => 'QA',
                '3-letter' => 'QAT',
            ],
            [
                'name' => Craft::t('formie', 'Romania'),
                '2-letter' => 'RO',
                '3-letter' => 'ROU',
            ],
            [
                'name' => Craft::t('formie', 'Russia'),
                '2-letter' => 'RU',
                '3-letter' => 'RUS',
            ],
            [
                'name' => Craft::t('formie', 'Rwanda'),
                '2-letter' => 'RW',
                '3-letter' => 'RWA',
            ],
            [
                'name' => Craft::t('formie', 'Réunion'),
                '2-letter' => 'RE',
                '3-letter' => 'REU',
            ],
            [
                'name' => Craft::t('formie', 'Samoa'),
                '2-letter' => 'WS',
                '3-letter' => 'WSM',
            ],
            [
                'name' => Craft::t('formie', 'San Marino'),
                '2-letter' => 'SM',
                '3-letter' => 'SMR',
            ],
            [
                'name' => Craft::t('formie', 'Saudi Arabia'),
                '2-letter' => 'SA',
                '3-letter' => 'SAU',
            ],
            [
                'name' => Craft::t('formie', 'Senegal'),
                '2-letter' => 'SN',
                '3-letter' => 'SEN',
            ],
            [
                'name' => Craft::t('formie', 'Serbia'),
                '2-letter' => 'RS',
                '3-letter' => 'SRB',
            ],
            [
                'name' => Craft::t('formie', 'Seychelles'),
                '2-letter' => 'SC',
                '3-letter' => 'SYC',
            ],
            [
                'name' => Craft::t('formie', 'Sierra Leone'),
                '2-letter' => 'SL',
                '3-letter' => 'SLE',
            ],
            [
                'name' => Craft::t('formie', 'Singapore'),
                '2-letter' => 'SG',
                '3-letter' => 'SGP',
            ],
            [
                'name' => Craft::t('formie', 'Sint Maarten'),
                '2-letter' => 'SX',
                '3-letter' => 'SXM',
            ],
            [
                'name' => Craft::t('formie', 'Slovakia'),
                '2-letter' => 'SK',
                '3-letter' => 'SVK',
            ],
            [
                'name' => Craft::t('formie', 'Slovenia'),
                '2-letter' => 'SI',
                '3-letter' => 'SVN',
            ],
            [
                'name' => Craft::t('formie', 'Solomon Islands'),
                '2-letter' => 'SB',
                '3-letter' => 'SLB',
            ],
            [
                'name' => Craft::t('formie', 'Somalia'),
                '2-letter' => 'SO',
                '3-letter' => 'SOM',
            ],
            [
                'name' => Craft::t('formie', 'South Africa'),
                '2-letter' => 'ZA',
                '3-letter' => 'ZAF',
            ],
            [
                'name' => Craft::t('formie', 'South Georgia & South Sandwich Islands'),
                '2-letter' => 'GS',
                '3-letter' => 'SGS',
            ],
            [
                'name' => Craft::t('formie', 'South Korea'),
                '2-letter' => 'KR',
                '3-letter' => 'KOR',
            ],
            [
                'name' => Craft::t('formie', 'South Sudan'),
                '2-letter' => 'SS',
                '3-letter' => 'SSD',
            ],
            [
                'name' => Craft::t('formie', 'Spain'),
                '2-letter' => 'ES',
                '3-letter' => 'ESP',
            ],
            [
                'name' => Craft::t('formie', 'Sri Lanka'),
                '2-letter' => 'LK',
                '3-letter' => 'LKA',
            ],
            [
                'name' => Craft::t('formie', 'St. Barthélemy'),
                '2-letter' => 'BL',
                '3-letter' => 'BLM',
            ],
            [
                'name' => Craft::t('formie', 'St. Helena'),
                '2-letter' => 'SH',
                '3-letter' => 'SHN',
            ],
            [
                'name' => Craft::t('formie', 'St. Kitts & Nevis'),
                '2-letter' => 'KN',
                '3-letter' => 'KNA',
            ],
            [
                'name' => Craft::t('formie', 'St. Lucia'),
                '2-letter' => 'LC',
                '3-letter' => 'LCA',
            ],
            [
                'name' => Craft::t('formie', 'St. Martin'),
                '2-letter' => 'MF',
                '3-letter' => 'MAF',
            ],
            [
                'name' => Craft::t('formie', 'St. Pierre & Miquelon'),
                '2-letter' => 'PM',
                '3-letter' => 'SPM',
            ],
            [
                'name' => Craft::t('formie', 'St. Vincent & Grenadines'),
                '2-letter' => 'VC',
                '3-letter' => 'VCT',
            ],
            [
                'name' => Craft::t('formie', 'Sudan'),
                '2-letter' => 'SD',
                '3-letter' => 'SDN',
            ],
            [
                'name' => Craft::t('formie', 'Suriname'),
                '2-letter' => 'SR',
                '3-letter' => 'SUR',
            ],
            [
                'name' => Craft::t('formie', 'Svalbard & Jan Mayen'),
                '2-letter' => 'SJ',
                '3-letter' => 'SJM',
            ],
            [
                'name' => Craft::t('formie', 'Sweden'),
                '2-letter' => 'SE',
                '3-letter' => 'SWE',
            ],
            [
                'name' => Craft::t('formie', 'Switzerland'),
                '2-letter' => 'CH',
                '3-letter' => 'CHE',
            ],
            [
                'name' => Craft::t('formie', 'Syria'),
                '2-letter' => 'SY',
                '3-letter' => 'SYR',
            ],
            [
                'name' => Craft::t('formie', 'São Tomé & Príncipe'),
                '2-letter' => 'ST',
                '3-letter' => 'STP',
            ],
            [
                'name' => Craft::t('formie', 'Taiwan'),
                '2-letter' => 'TW',
                '3-letter' => 'TWN',
            ],
            [
                'name' => Craft::t('formie', 'Tajikistan'),
                '2-letter' => 'TJ',
                '3-letter' => 'TJK',
            ],
            [
                'name' => Craft::t('formie', 'Tanzania'),
                '2-letter' => 'TZ',
                '3-letter' => 'TZA',
            ],
            [
                'name' => Craft::t('formie', 'Thailand'),
                '2-letter' => 'TH',
                '3-letter' => 'THA',
            ],
            [
                'name' => Craft::t('formie', 'Timor-Leste'),
                '2-letter' => 'TL',
                '3-letter' => 'TLS',
            ],
            [
                'name' => Craft::t('formie', 'Togo'),
                '2-letter' => 'TG',
                '3-letter' => 'TGO',
            ],
            [
                'name' => Craft::t('formie', 'Tokelau'),
                '2-letter' => 'TK',
                '3-letter' => 'TKL',
            ],
            [
                'name' => Craft::t('formie', 'Tonga'),
                '2-letter' => 'TO',
                '3-letter' => 'TON',
            ],
            [
                'name' => Craft::t('formie', 'Trinidad & Tobago'),
                '2-letter' => 'TT',
                '3-letter' => 'TTO',
            ],
            [
                'name' => Craft::t('formie', 'Tunisia'),
                '2-letter' => 'TN',
                '3-letter' => 'TUN',
            ],
            [
                'name' => Craft::t('formie', 'Turkey'),
                '2-letter' => 'TR',
                '3-letter' => 'TUR',
            ],
            [
                'name' => Craft::t('formie', 'Turkmenistan'),
                '2-letter' => 'TM',
                '3-letter' => 'TKM',
            ],
            [
                'name' => Craft::t('formie', 'Turks & Caicos Islands'),
                '2-letter' => 'TC',
                '3-letter' => 'TCA',
            ],
            [
                'name' => Craft::t('formie', 'Tuvalu'),
                '2-letter' => 'TV',
                '3-letter' => 'TUV',
            ],
            [
                'name' => Craft::t('formie', 'U.S. Outlying Islands'),
                '2-letter' => 'UM',
                '3-letter' => 'UMI',
            ],
            [
                'name' => Craft::t('formie', 'U.S. Virgin Islands'),
                '2-letter' => 'VI',
                '3-letter' => 'VIR',
            ],
            [
                'name' => Craft::t('formie', 'UK'),
                '2-letter' => 'GB',
                '3-letter' => 'GBR',
            ],
            [
                'name' => Craft::t('formie', 'US'),
                '2-letter' => 'US',
                '3-letter' => 'USA',
            ],
            [
                'name' => Craft::t('formie', 'Uganda'),
                '2-letter' => 'UG',
                '3-letter' => 'UGA',
            ],
            [
                'name' => Craft::t('formie', 'Ukraine'),
                '2-letter' => 'UA',
                '3-letter' => 'UKR',
            ],
            [
                'name' => Craft::t('formie', 'United Arab Emirates'),
                '2-letter' => 'AE',
                '3-letter' => 'ARE',
            ],
            [
                'name' => Craft::t('formie', 'Uruguay'),
                '2-letter' => 'UY',
                '3-letter' => 'URY',
            ],
            [
                'name' => Craft::t('formie', 'Uzbekistan'),
                '2-letter' => 'UZ',
                '3-letter' => 'UZB',
            ],
            [
                'name' => Craft::t('formie', 'Vanuatu'),
                '2-letter' => 'VU',
                '3-letter' => 'VUT',
            ],
            [
                'name' => Craft::t('formie', 'Vatican City'),
                '2-letter' => 'VA',
                '3-letter' => 'VAT',
            ],
            [
                'name' => Craft::t('formie', 'Venezuela'),
                '2-letter' => 'VE',
                '3-letter' => 'VEN',
            ],
            [
                'name' => Craft::t('formie', 'Vietnam'),
                '2-letter' => 'VN',
                '3-letter' => 'VNM',
            ],
            [
                'name' => Craft::t('formie', 'Wallis & Futuna'),
                '2-letter' => 'WF',
                '3-letter' => 'WLF',
            ],
            [
                'name' => Craft::t('formie', 'Western Sahara'),
                '2-letter' => 'EH',
                '3-letter' => 'ESH',
            ],
            [
                'name' => Craft::t('formie', 'Yemen'),
                '2-letter' => 'YE',
                '3-letter' => 'YEM',
            ],
            [
                'name' => Craft::t('formie', 'Zambia'),
                '2-letter' => 'ZM',
                '3-letter' => 'ZMB',
            ],
            [
                'name' => Craft::t('formie', 'Zimbabwe'),
                '2-letter' => 'ZW',
                '3-letter' => 'ZWE',
            ],
            [
                'name' => Craft::t('formie', 'Åland Islands'),
                '2-letter' => 'AX',
                '3-letter' => 'ALA',
            ],
        ];
    }
}
