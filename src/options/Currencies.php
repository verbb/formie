<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Currencies extends PredefinedOption
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
        return Craft::t('formie', 'Currencies');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
        ];
    }

    public static function getDataOptions(): array
    {
        return [
            [
                'name' => Craft::t('formie', 'Afghani'),
                'code' => 'AFN',
            ],
            [
                'name' => Craft::t('formie', 'Algerian Dinar'),
                'code' => 'DZD',
            ],
            [
                'name' => Craft::t('formie', 'Argentine Peso'),
                'code' => 'ARS',
            ],
            [
                'name' => Craft::t('formie', 'Armenian Dram'),
                'code' => 'AMD',
            ],
            [
                'name' => Craft::t('formie', 'Aruban Florin'),
                'code' => 'AWG',
            ],
            [
                'name' => Craft::t('formie', 'Australian Dollar'),
                'code' => 'AUD',
            ],
            [
                'name' => Craft::t('formie', 'Azerbaijan Manat'),
                'code' => 'AZN',
            ],
            [
                'name' => Craft::t('formie', 'Bahamian Dollar'),
                'code' => 'BSD',
            ],
            [
                'name' => Craft::t('formie', 'Bahraini Dinar'),
                'code' => 'BHD',
            ],
            [
                'name' => Craft::t('formie', 'Baht'),
                'code' => 'THB',
            ],
            [
                'name' => Craft::t('formie', 'Balboa,US Dollar'),
                'code' => 'PAB,USD',
            ],
            [
                'name' => Craft::t('formie', 'Barbados Dollar'),
                'code' => 'BBD',
            ],
            [
                'name' => Craft::t('formie', 'Belarusian Ruble'),
                'code' => 'BYN',
            ],
            [
                'name' => Craft::t('formie', 'Belize Dollar'),
                'code' => 'BZD',
            ],
            [
                'name' => Craft::t('formie', 'Bermudian Dollar'),
                'code' => 'BMD',
            ],
            [
                'name' => Craft::t('formie', 'Boliviano'),
                'code' => 'BOB',
            ],
            [
                'name' => Craft::t('formie', 'Bolívar'),
                'code' => 'VES',
            ],
            [
                'name' => Craft::t('formie', 'Brazilian Real'),
                'code' => 'BRL',
            ],
            [
                'name' => Craft::t('formie', 'Brunei Dollar'),
                'code' => 'BND',
            ],
            [
                'name' => Craft::t('formie', 'Bulgarian Lev'),
                'code' => 'BGN',
            ],
            [
                'name' => Craft::t('formie', 'Burundi Franc'),
                'code' => 'BIF',
            ],
            [
                'name' => Craft::t('formie', 'CFA Franc BCEAO'),
                'code' => 'XOF',
            ],
            [
                'name' => Craft::t('formie', 'CFA Franc BEAC'),
                'code' => 'XAF',
            ],
            [
                'name' => Craft::t('formie', 'CFP Franc'),
                'code' => 'XPF',
            ],
            [
                'name' => Craft::t('formie', 'Cabo Verde Escudo'),
                'code' => 'CVE',
            ],
            [
                'name' => Craft::t('formie', 'Canadian Dollar'),
                'code' => 'CAD',
            ],
            [
                'name' => Craft::t('formie', 'Cayman Islands Dollar'),
                'code' => 'KYD',
            ],
            [
                'name' => Craft::t('formie', 'Chilean Peso'),
                'code' => 'CLP',
            ],
            [
                'name' => Craft::t('formie', 'Colombian Peso'),
                'code' => 'COP',
            ],
            [
                'name' => Craft::t('formie', 'Comorian Franc'),
                'code' => 'KMF',
            ],
            [
                'name' => Craft::t('formie', 'Congolese Franc'),
                'code' => 'CDF',
            ],
            [
                'name' => Craft::t('formie', 'Convertible Mark'),
                'code' => 'BAM',
            ],
            [
                'name' => Craft::t('formie', 'Cordoba Oro'),
                'code' => 'NIO',
            ],
            [
                'name' => Craft::t('formie', 'Costa Rican Colon'),
                'code' => 'CRC',
            ],
            [
                'name' => Craft::t('formie', 'Cuban Peso,Peso Convertible'),
                'code' => 'CUP,CUC',
            ],
            [
                'name' => Craft::t('formie', 'Czech Koruna'),
                'code' => 'CZK',
            ],
            [
                'name' => Craft::t('formie', 'Dalasi'),
                'code' => 'GMD',
            ],
            [
                'name' => Craft::t('formie', 'Danish Krone'),
                'code' => 'DKK',
            ],
            [
                'name' => Craft::t('formie', 'Denar'),
                'code' => 'MKD',
            ],
            [
                'name' => Craft::t('formie', 'Djibouti Franc'),
                'code' => 'DJF',
            ],
            [
                'name' => Craft::t('formie', 'Dobra'),
                'code' => 'STN',
            ],
            [
                'name' => Craft::t('formie', 'Dominican Peso'),
                'code' => 'DOP',
            ],
            [
                'name' => Craft::t('formie', 'Dong'),
                'code' => 'VND',
            ],
            [
                'name' => Craft::t('formie', 'East Caribbean Dollar'),
                'code' => 'XCD',
            ],
            [
                'name' => Craft::t('formie', 'Egyptian Pound'),
                'code' => 'EGP',
            ],
            [
                'name' => Craft::t('formie', 'El Salvador Colon,US Dollar'),
                'code' => 'SVC,USD',
            ],
            [
                'name' => Craft::t('formie', 'Ethiopian Birr'),
                'code' => 'ETB',
            ],
            [
                'name' => Craft::t('formie', 'Euro'),
                'code' => 'EUR',
            ],
            [
                'name' => Craft::t('formie', 'Fiji Dollar'),
                'code' => 'FJD',
            ],
            [
                'name' => Craft::t('formie', 'Forint'),
                'code' => 'HUF',
            ],
            [
                'name' => Craft::t('formie', 'Ghana Cedi'),
                'code' => 'GHS',
            ],
            [
                'name' => Craft::t('formie', 'Gibraltar Pound'),
                'code' => 'GIP',
            ],
            [
                'name' => Craft::t('formie', 'Gourde,US Dollar'),
                'code' => 'HTG,USD',
            ],
            [
                'name' => Craft::t('formie', 'Guarani'),
                'code' => 'PYG',
            ],
            [
                'name' => Craft::t('formie', 'Guinean Franc'),
                'code' => 'GNF',
            ],
            [
                'name' => Craft::t('formie', 'Guyana Dollar'),
                'code' => 'GYD',
            ],
            [
                'name' => Craft::t('formie', 'Hong Kong Dollar'),
                'code' => 'HKD',
            ],
            [
                'name' => Craft::t('formie', 'Hryvnia'),
                'code' => 'UAH',
            ],
            [
                'name' => Craft::t('formie', 'Iceland Krona'),
                'code' => 'ISK',
            ],
            [
                'name' => Craft::t('formie', 'Indian Rupee'),
                'code' => 'INR',
            ],
            [
                'name' => Craft::t('formie', 'Indian Rupee,Ngultrum'),
                'code' => 'INR,BTN',
            ],
            [
                'name' => Craft::t('formie', 'Iranian Rial'),
                'code' => 'IRR',
            ],
            [
                'name' => Craft::t('formie', 'Iraqi Dinar'),
                'code' => 'IQD',
            ],
            [
                'name' => Craft::t('formie', 'Jamaican Dollar'),
                'code' => 'JMD',
            ],
            [
                'name' => Craft::t('formie', 'Jordanian Dinar'),
                'code' => 'JOD',
            ],
            [
                'name' => Craft::t('formie', 'Kenyan Shilling'),
                'code' => 'KES',
            ],
            [
                'name' => Craft::t('formie', 'Kina'),
                'code' => 'PGK',
            ],
            [
                'name' => Craft::t('formie', 'Kuna'),
                'code' => 'HRK',
            ],
            [
                'name' => Craft::t('formie', 'Kuwaiti Dinar'),
                'code' => 'KWD',
            ],
            [
                'name' => Craft::t('formie', 'Kwanza'),
                'code' => 'AOA',
            ],
            [
                'name' => Craft::t('formie', 'Kyat'),
                'code' => 'MMK',
            ],
            [
                'name' => Craft::t('formie', 'Lao Kip'),
                'code' => 'LAK',
            ],
            [
                'name' => Craft::t('formie', 'Lari'),
                'code' => 'GEL',
            ],
            [
                'name' => Craft::t('formie', 'Lebanese Pound'),
                'code' => 'LBP',
            ],
            [
                'name' => Craft::t('formie', 'Lek'),
                'code' => 'ALL',
            ],
            [
                'name' => Craft::t('formie', 'Lempira'),
                'code' => 'HNL',
            ],
            [
                'name' => Craft::t('formie', 'Leone'),
                'code' => 'SLL',
            ],
            [
                'name' => Craft::t('formie', 'Liberian Dollar'),
                'code' => 'LRD',
            ],
            [
                'name' => Craft::t('formie', 'Libyan Dinar'),
                'code' => 'LYD',
            ],
            [
                'name' => Craft::t('formie', 'Lilangeni'),
                'code' => 'SZL',
            ],
            [
                'name' => Craft::t('formie', 'Loti,Rand'),
                'code' => 'LSL,ZAR',
            ],
            [
                'name' => Craft::t('formie', 'Malagasy Ariary'),
                'code' => 'MGA',
            ],
            [
                'name' => Craft::t('formie', 'Malawi Kwacha'),
                'code' => 'MWK',
            ],
            [
                'name' => Craft::t('formie', 'Malaysian Ringgit'),
                'code' => 'MYR',
            ],
            [
                'name' => Craft::t('formie', 'Mauritius Rupee'),
                'code' => 'MUR',
            ],
            [
                'name' => Craft::t('formie', 'Mexican Peso'),
                'code' => 'MXN',
            ],
            [
                'name' => Craft::t('formie', 'Moldovan Leu'),
                'code' => 'MDL',
            ],
            [
                'name' => Craft::t('formie', 'Moroccan Dirham'),
                'code' => 'MAD',
            ],
            [
                'name' => Craft::t('formie', 'Mozambique Metical'),
                'code' => 'MZN',
            ],
            [
                'name' => Craft::t('formie', 'Naira'),
                'code' => 'NGN',
            ],
            [
                'name' => Craft::t('formie', 'Nakfa'),
                'code' => 'ERN',
            ],
            [
                'name' => Craft::t('formie', 'Namibia Dollar,Rand'),
                'code' => 'NAD,ZAR',
            ],
            [
                'name' => Craft::t('formie', 'Nepalese Rupee'),
                'code' => 'NPR',
            ],
            [
                'name' => Craft::t('formie', 'Netherlands Antillean Guilder'),
                'code' => 'ANG',
            ],
            [
                'name' => Craft::t('formie', 'New Israeli Sheqel'),
                'code' => 'ILS',
            ],
            [
                'name' => Craft::t('formie', 'New Zealand Dollar'),
                'code' => 'NZD',
            ],
            [
                'name' => Craft::t('formie', 'No universal currency'),
                'code' => '',
            ],
            [
                'name' => Craft::t('formie', 'North Korean Won'),
                'code' => 'KPW',
            ],
            [
                'name' => Craft::t('formie', 'Norwegian Krone'),
                'code' => 'NOK',
            ],
            [
                'name' => Craft::t('formie', 'Ouguiya'),
                'code' => 'MRU',
            ],
            [
                'name' => Craft::t('formie', 'Pakistan Rupee'),
                'code' => 'PKR',
            ],
            [
                'name' => Craft::t('formie', 'Pataca'),
                'code' => 'MOP',
            ],
            [
                'name' => Craft::t('formie', 'Pa’anga'),
                'code' => 'TOP',
            ],
            [
                'name' => Craft::t('formie', 'Peso Uruguayo'),
                'code' => 'UYU',
            ],
            [
                'name' => Craft::t('formie', 'Philippine Peso'),
                'code' => 'PHP',
            ],
            [
                'name' => Craft::t('formie', 'Pound Sterling'),
                'code' => 'GBP',
            ],
            [
                'name' => Craft::t('formie', 'Pula'),
                'code' => 'BWP',
            ],
            [
                'name' => Craft::t('formie', 'Qatari Rial'),
                'code' => 'QAR',
            ],
            [
                'name' => Craft::t('formie', 'Quetzal'),
                'code' => 'GTQ',
            ],
            [
                'name' => Craft::t('formie', 'Rand'),
                'code' => 'ZAR',
            ],
            [
                'name' => Craft::t('formie', 'Rial Omani'),
                'code' => 'OMR',
            ],
            [
                'name' => Craft::t('formie', 'Riel'),
                'code' => 'KHR',
            ],
            [
                'name' => Craft::t('formie', 'Romanian Leu'),
                'code' => 'RON',
            ],
            [
                'name' => Craft::t('formie', 'Rufiyaa'),
                'code' => 'MVR',
            ],
            [
                'name' => Craft::t('formie', 'Rupiah'),
                'code' => 'IDR',
            ],
            [
                'name' => Craft::t('formie', 'Russian Ruble'),
                'code' => 'RUB',
            ],
            [
                'name' => Craft::t('formie', 'Rwanda Franc'),
                'code' => 'RWF',
            ],
            [
                'name' => Craft::t('formie', 'Saint Helena Pound'),
                'code' => 'SHP',
            ],
            [
                'name' => Craft::t('formie', 'Saudi Riyal'),
                'code' => 'SAR',
            ],
            [
                'name' => Craft::t('formie', 'Serbian Dinar'),
                'code' => 'RSD',
            ],
            [
                'name' => Craft::t('formie', 'Seychelles Rupee'),
                'code' => 'SCR',
            ],
            [
                'name' => Craft::t('formie', 'Singapore Dollar'),
                'code' => 'SGD',
            ],
            [
                'name' => Craft::t('formie', 'Sol'),
                'code' => 'PEN',
            ],
            [
                'name' => Craft::t('formie', 'Solomon Islands Dollar'),
                'code' => 'SBD',
            ],
            [
                'name' => Craft::t('formie', 'Som'),
                'code' => 'KGS',
            ],
            [
                'name' => Craft::t('formie', 'Somali Shilling'),
                'code' => 'SOS',
            ],
            [
                'name' => Craft::t('formie', 'Somoni'),
                'code' => 'TJS',
            ],
            [
                'name' => Craft::t('formie', 'South Sudanese Pound'),
                'code' => 'SSP',
            ],
            [
                'name' => Craft::t('formie', 'Sri Lanka Rupee'),
                'code' => 'LKR',
            ],
            [
                'name' => Craft::t('formie', 'Sudanese Pound'),
                'code' => 'SDG',
            ],
            [
                'name' => Craft::t('formie', 'Surinam Dollar'),
                'code' => 'SRD',
            ],
            [
                'name' => Craft::t('formie', 'Swedish Krona'),
                'code' => 'SEK',
            ],
            [
                'name' => Craft::t('formie', 'Swiss Franc'),
                'code' => 'CHF',
            ],
            [
                'name' => Craft::t('formie', 'Syrian Pound'),
                'code' => 'SYP',
            ],
            [
                'name' => Craft::t('formie', 'Taka'),
                'code' => 'BDT',
            ],
            [
                'name' => Craft::t('formie', 'Tala'),
                'code' => 'WST',
            ],
            [
                'name' => Craft::t('formie', 'Tanzanian Shilling'),
                'code' => 'TZS',
            ],
            [
                'name' => Craft::t('formie', 'Tenge'),
                'code' => 'KZT',
            ],
            [
                'name' => Craft::t('formie', 'Trinidad and Tobago Dollar'),
                'code' => 'TTD',
            ],
            [
                'name' => Craft::t('formie', 'Tugrik'),
                'code' => 'MNT',
            ],
            [
                'name' => Craft::t('formie', 'Tunisian Dinar'),
                'code' => 'TND',
            ],
            [
                'name' => Craft::t('formie', 'Turkish Lira'),
                'code' => 'TRY',
            ],
            [
                'name' => Craft::t('formie', 'Turkmenistan New Manat'),
                'code' => 'TMT',
            ],
            [
                'name' => Craft::t('formie', 'UAE Dirham'),
                'code' => 'AED',
            ],
            [
                'name' => Craft::t('formie', 'US Dollar'),
                'code' => 'USD',
            ],
            [
                'name' => Craft::t('formie', 'Uganda Shilling'),
                'code' => 'UGX',
            ],
            [
                'name' => Craft::t('formie', 'Uzbekistan Sum'),
                'code' => 'UZS',
            ],
            [
                'name' => Craft::t('formie', 'Vatu'),
                'code' => 'VUV',
            ],
            [
                'name' => Craft::t('formie', 'Won'),
                'code' => 'KRW',
            ],
            [
                'name' => Craft::t('formie', 'Yemeni Rial'),
                'code' => 'YER',
            ],
            [
                'name' => Craft::t('formie', 'Yen'),
                'code' => 'JPY',
            ],
            [
                'name' => Craft::t('formie', 'Yuan Renminbi'),
                'code' => 'CNY',
            ],
            [
                'name' => Craft::t('formie', 'Zambian Kwacha'),
                'code' => 'ZMW',
            ],
            [
                'name' => Craft::t('formie', 'Zimbabwe Dollar'),
                'code' => 'ZWL',
            ],
            [
                'name' => Craft::t('formie', 'Zloty'),
                'code' => 'PLN',
            ],
        ];
    }
}
