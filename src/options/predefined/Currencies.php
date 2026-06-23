<?php
namespace verbb\formie\options\predefined;

use verbb\formie\base\PredefinedOption;

use Craft;

class Currencies extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

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
        return self::currencyEntries();
    }

    private static function currencyEntries(): array
    {
        // Deliberately not using Craft::t().
        return [
            ['name' => 'Afghani', 'code' => 'AFN'],
            ['name' => 'Algerian Dinar', 'code' => 'DZD'],
            ['name' => 'Argentine Peso', 'code' => 'ARS'],
            ['name' => 'Armenian Dram', 'code' => 'AMD'],
            ['name' => 'Aruban Florin', 'code' => 'AWG'],
            ['name' => 'Australian Dollar', 'code' => 'AUD'],
            ['name' => 'Azerbaijan Manat', 'code' => 'AZN'],
            ['name' => 'Bahamian Dollar', 'code' => 'BSD'],
            ['name' => 'Bahraini Dinar', 'code' => 'BHD'],
            ['name' => 'Baht', 'code' => 'THB'],
            ['name' => 'Balboa,US Dollar', 'code' => 'PAB,USD'],
            ['name' => 'Barbados Dollar', 'code' => 'BBD'],
            ['name' => 'Belarusian Ruble', 'code' => 'BYN'],
            ['name' => 'Belize Dollar', 'code' => 'BZD'],
            ['name' => 'Bermudian Dollar', 'code' => 'BMD'],
            ['name' => 'Boliviano', 'code' => 'BOB'],
            ['name' => 'Bolívar', 'code' => 'VES'],
            ['name' => 'Brazilian Real', 'code' => 'BRL'],
            ['name' => 'Brunei Dollar', 'code' => 'BND'],
            ['name' => 'Bulgarian Lev', 'code' => 'BGN'],
            ['name' => 'Burundi Franc', 'code' => 'BIF'],
            ['name' => 'CFA Franc BCEAO', 'code' => 'XOF'],
            ['name' => 'CFA Franc BEAC', 'code' => 'XAF'],
            ['name' => 'CFP Franc', 'code' => 'XPF'],
            ['name' => 'Cabo Verde Escudo', 'code' => 'CVE'],
            ['name' => 'Canadian Dollar', 'code' => 'CAD'],
            ['name' => 'Cayman Islands Dollar', 'code' => 'KYD'],
            ['name' => 'Chilean Peso', 'code' => 'CLP'],
            ['name' => 'Colombian Peso', 'code' => 'COP'],
            ['name' => 'Comorian Franc', 'code' => 'KMF'],
            ['name' => 'Congolese Franc', 'code' => 'CDF'],
            ['name' => 'Convertible Mark', 'code' => 'BAM'],
            ['name' => 'Cordoba Oro', 'code' => 'NIO'],
            ['name' => 'Costa Rican Colon', 'code' => 'CRC'],
            ['name' => 'Cuban Peso,Peso Convertible', 'code' => 'CUP,CUC'],
            ['name' => 'Czech Koruna', 'code' => 'CZK'],
            ['name' => 'Dalasi', 'code' => 'GMD'],
            ['name' => 'Danish Krone', 'code' => 'DKK'],
            ['name' => 'Denar', 'code' => 'MKD'],
            ['name' => 'Djibouti Franc', 'code' => 'DJF'],
            ['name' => 'Dobra', 'code' => 'STN'],
            ['name' => 'Dominican Peso', 'code' => 'DOP'],
            ['name' => 'Dong', 'code' => 'VND'],
            ['name' => 'East Caribbean Dollar', 'code' => 'XCD'],
            ['name' => 'Egyptian Pound', 'code' => 'EGP'],
            ['name' => 'El Salvador Colon,US Dollar', 'code' => 'SVC,USD'],
            ['name' => 'Ethiopian Birr', 'code' => 'ETB'],
            ['name' => 'Euro', 'code' => 'EUR'],
            ['name' => 'Fiji Dollar', 'code' => 'FJD'],
            ['name' => 'Forint', 'code' => 'HUF'],
            ['name' => 'Ghana Cedi', 'code' => 'GHS'],
            ['name' => 'Gibraltar Pound', 'code' => 'GIP'],
            ['name' => 'Gourde,US Dollar', 'code' => 'HTG,USD'],
            ['name' => 'Guarani', 'code' => 'PYG'],
            ['name' => 'Guinean Franc', 'code' => 'GNF'],
            ['name' => 'Guyana Dollar', 'code' => 'GYD'],
            ['name' => 'Hong Kong Dollar', 'code' => 'HKD'],
            ['name' => 'Hryvnia', 'code' => 'UAH'],
            ['name' => 'Iceland Krona', 'code' => 'ISK'],
            ['name' => 'Indian Rupee', 'code' => 'INR'],
            ['name' => 'Indian Rupee,Ngultrum', 'code' => 'INR,BTN'],
            ['name' => 'Iranian Rial', 'code' => 'IRR'],
            ['name' => 'Iraqi Dinar', 'code' => 'IQD'],
            ['name' => 'Jamaican Dollar', 'code' => 'JMD'],
            ['name' => 'Jordanian Dinar', 'code' => 'JOD'],
            ['name' => 'Kenyan Shilling', 'code' => 'KES'],
            ['name' => 'Kina', 'code' => 'PGK'],
            ['name' => 'Kuna', 'code' => 'HRK'],
            ['name' => 'Kuwaiti Dinar', 'code' => 'KWD'],
            ['name' => 'Kwanza', 'code' => 'AOA'],
            ['name' => 'Kyat', 'code' => 'MMK'],
            ['name' => 'Lao Kip', 'code' => 'LAK'],
            ['name' => 'Lari', 'code' => 'GEL'],
            ['name' => 'Lebanese Pound', 'code' => 'LBP'],
            ['name' => 'Lek', 'code' => 'ALL'],
            ['name' => 'Lempira', 'code' => 'HNL'],
            ['name' => 'Leone', 'code' => 'SLL'],
            ['name' => 'Liberian Dollar', 'code' => 'LRD'],
            ['name' => 'Libyan Dinar', 'code' => 'LYD'],
            ['name' => 'Lilangeni', 'code' => 'SZL'],
            ['name' => 'Loti,Rand', 'code' => 'LSL,ZAR'],
            ['name' => 'Malagasy Ariary', 'code' => 'MGA'],
            ['name' => 'Malawi Kwacha', 'code' => 'MWK'],
            ['name' => 'Malaysian Ringgit', 'code' => 'MYR'],
            ['name' => 'Mauritius Rupee', 'code' => 'MUR'],
            ['name' => 'Mexican Peso', 'code' => 'MXN'],
            ['name' => 'Moldovan Leu', 'code' => 'MDL'],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD'],
            ['name' => 'Mozambique Metical', 'code' => 'MZN'],
            ['name' => 'Naira', 'code' => 'NGN'],
            ['name' => 'Nakfa', 'code' => 'ERN'],
            ['name' => 'Namibia Dollar,Rand', 'code' => 'NAD,ZAR'],
            ['name' => 'Nepalese Rupee', 'code' => 'NPR'],
            ['name' => 'Netherlands Antillean Guilder', 'code' => 'ANG'],
            ['name' => 'New Israeli Sheqel', 'code' => 'ILS'],
            ['name' => 'New Zealand Dollar', 'code' => 'NZD'],
            ['name' => 'No universal currency', 'code' => ''],
            ['name' => 'North Korean Won', 'code' => 'KPW'],
            ['name' => 'Norwegian Krone', 'code' => 'NOK'],
            ['name' => 'Ouguiya', 'code' => 'MRU'],
            ['name' => 'Pakistan Rupee', 'code' => 'PKR'],
            ['name' => 'Pataca', 'code' => 'MOP'],
            ['name' => 'Pa’anga', 'code' => 'TOP'],
            ['name' => 'Peso Uruguayo', 'code' => 'UYU'],
            ['name' => 'Philippine Peso', 'code' => 'PHP'],
            ['name' => 'Pound Sterling', 'code' => 'GBP'],
            ['name' => 'Pula', 'code' => 'BWP'],
            ['name' => 'Qatari Rial', 'code' => 'QAR'],
            ['name' => 'Quetzal', 'code' => 'GTQ'],
            ['name' => 'Rand', 'code' => 'ZAR'],
            ['name' => 'Rial Omani', 'code' => 'OMR'],
            ['name' => 'Riel', 'code' => 'KHR'],
            ['name' => 'Romanian Leu', 'code' => 'RON'],
            ['name' => 'Rufiyaa', 'code' => 'MVR'],
            ['name' => 'Rupiah', 'code' => 'IDR'],
            ['name' => 'Russian Ruble', 'code' => 'RUB'],
            ['name' => 'Rwanda Franc', 'code' => 'RWF'],
            ['name' => 'Saint Helena Pound', 'code' => 'SHP'],
            ['name' => 'Saudi Riyal', 'code' => 'SAR'],
            ['name' => 'Serbian Dinar', 'code' => 'RSD'],
            ['name' => 'Seychelles Rupee', 'code' => 'SCR'],
            ['name' => 'Singapore Dollar', 'code' => 'SGD'],
            ['name' => 'Sol', 'code' => 'PEN'],
            ['name' => 'Solomon Islands Dollar', 'code' => 'SBD'],
            ['name' => 'Som', 'code' => 'KGS'],
            ['name' => 'Somali Shilling', 'code' => 'SOS'],
            ['name' => 'Somoni', 'code' => 'TJS'],
            ['name' => 'South Sudanese Pound', 'code' => 'SSP'],
            ['name' => 'Sri Lanka Rupee', 'code' => 'LKR'],
            ['name' => 'Sudanese Pound', 'code' => 'SDG'],
            ['name' => 'Surinam Dollar', 'code' => 'SRD'],
            ['name' => 'Swedish Krona', 'code' => 'SEK'],
            ['name' => 'Swiss Franc', 'code' => 'CHF'],
            ['name' => 'Syrian Pound', 'code' => 'SYP'],
            ['name' => 'Taka', 'code' => 'BDT'],
            ['name' => 'Tala', 'code' => 'WST'],
            ['name' => 'Tanzanian Shilling', 'code' => 'TZS'],
            ['name' => 'Tenge', 'code' => 'KZT'],
            ['name' => 'Trinidad and Tobago Dollar', 'code' => 'TTD'],
            ['name' => 'Tugrik', 'code' => 'MNT'],
            ['name' => 'Tunisian Dinar', 'code' => 'TND'],
            ['name' => 'Turkish Lira', 'code' => 'TRY'],
            ['name' => 'Turkmenistan New Manat', 'code' => 'TMT'],
            ['name' => 'UAE Dirham', 'code' => 'AED'],
            ['name' => 'US Dollar', 'code' => 'USD'],
            ['name' => 'Uganda Shilling', 'code' => 'UGX'],
            ['name' => 'Uzbekistan Sum', 'code' => 'UZS'],
            ['name' => 'Vatu', 'code' => 'VUV'],
            ['name' => 'Won', 'code' => 'KRW'],
            ['name' => 'Yemeni Rial', 'code' => 'YER'],
            ['name' => 'Yen', 'code' => 'JPY'],
            ['name' => 'Yuan Renminbi', 'code' => 'CNY'],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMW'],
            ['name' => 'Zimbabwe Dollar', 'code' => 'ZWL'],
            ['name' => 'Zloty', 'code' => 'PLN'],
        ];
    }
}
