# Fields
Fields are arguably the core of a form, providing users the means to input actual content into a form. With Formie's form builder interface, fields are organised into rows. Each row can have multiple fields (up to 4), allowing fields to be shown in a column layout, side-by-side.

Each field varies by its type, allowing for different functionality and behaviour depending on the field.

<img src="https://verbb.io/uploads/plugins/formie/formie-address.png" />

:::tip
Looking for custom fields? Developers can create their own custom fields to extend the functionality of Formie. Read the [Custom Field](docs:developers/custom-field) docs for more.
:::

## Settings
All fields have a standard collection of settings.

Attribute | Description
--- | ---
Label | The label that describes this field.
Handle | How you’ll refer to this field in your templates.
Required | Whether this field should be required when filling out the form.
Error Message | When validating the form, show this message if an error occurs.
Label Position | How the label for the field should be positioned.
Instructions | Instructions to guide the user when filling out this form.
Instructions Position | How the instructions for the field should be positioned.
CSS Classes | Add classes to be outputted on this field’s container.
Container Attributes | Add attributes to be outputted on this field’s container.
Input Attributes | Add attributes to be outputted on this field’s input.
Visibility | See below.

### Visibility
You can set any field to be "Hidden" on the front-end. This will still be available in the source of the page, but hidden for general users. You can also set it to be "Disabled", where the field is never rendered on the front-end, but can still have its value set via [Field Population](docs:template-guides/populating-forms). This can be beneficial if you're concerned with tampering of the HTML before submission.

See the full [Field](docs:developers/field) documentation for more.

In addition, some fields have some additional specific settings, described below.

### Match Field
For some fields (Text, Number, Password and Email) you can select whether to enforce validation where two fields need to have the same value. This is most commonly used for "confirm" fields, such as Email, or Password.




## Field Types
Formie provides 31 different fields for use in your forms.



### Address
A field for addresses. There are a number of sub-fields that can be enabled as required:

- Address 1
- Address 2
- Address 3
- City
- State / Province
- ZIP / Postcode
- Country

If you have any [Address Providers](docs:feature-tour/integrations#address-providers) configured, you can also enable an *Auto-Complete* setting. This allows the use of a single text input to type an address, and have it auto-completed.


### Agree
A field for a single checkbox. Its ideal purpose is to be an agreement checkbox for terms & conditions, or similar. It can be marked as required or not as well as have its checked and unchecked values set.

The description for the Agree field can also contain HTML via a WYSIWYG editor. You can control the available buttons and formatting via [available buttons](docs:get-started/configuration#available-buttons).

#### Settings
Setting | Description
--- | ---
Description | The description for the field. This will be shown next to the checkbox.
Checked Value | The value of this field when it is checked.
Unchecked Value | The value of this field when it is unchecked.
Default Value | The default value for the field when it loads.



### Calculations
A field for creating read-only content based on other fields' content. Supports arithmetic, bitwise, comparison, logic, string, array, numeric and ternary operators, and of course being able to reference other fields.

The underlying parsing logic is based off the [Symfony Expression Syntax](https://symfony.com/doc/current/components/expression_language/syntax.html).

#### Arithmetic Operators
- `+` (addition)
- `-` (subtraction)
- `*` (multiplication)
- `/` (division)
- `%` (modulus)
- `**` (pow)

```
10 + 10 + 22
```

#### Bitwise Operators
- `&` (and)
- `|` (or)
- `^` (xor)

#### Comparison Operators
- `==` (equal)
- `===` (identical)
- `!=` (not equal)
- `!==` (not identical)
- `<` (less than)
- `>` (greater than)
- `<=` (less than or equal to)
- `>=` (greater than or equal to)
- `matches` (regex match)

```
{field1} == {field2}
{field1} > {field2}
```

#### Logical Operators
- `not` or `!`
- `and` or `&&`
- `or` or `||`

```
{field1} < 10 or {field2} > 10
```

#### String Operators
- `~` (concatenation)

```
{field1} ~ " " ~ {field2}
```

#### Array Operators
- `in` (contain)
- `not in` (does not contain)

#### Numeric Operators
- `..` (range)


#### Settings
Setting | Description
--- | ---
Calculations Formula | Provide the formula used to calculate the result for this field. Use arithmetic operators (+, -, *, /, etc) and reference other fields.



### Categories
A field for users to select categories from.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select categories from?
Branch Limit | Limit the number of selectable category branches.
Root Category | Only output the direct children of the chosen category. 
Show Structure | Whether to show the hierarchical structure of categories in the dropdown.



### Checkboxes
A field for a collection of checkboxes for the user to pick one or many options, each with their own label and value.

#### Settings
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.



### Date/Time
A field to select the date or time, or both. There are some different display types:

- Calendar
- Dropdown fields (a field for year, month, etc.)
- Text input fields (a field for year, month, etc.)

#### Settings
Setting | Description
--- | ---
Include Date | Whether this field should include the date.
Include Time | Whether this field should include the time.
Default Value | Entering a default value will place the value in the field when it loads.
Display Type | Set different display layouts for this field.
Min Date | Set a minimum date for dates to be picked from.
Max Date | Set a maximum date for dates to be picked up to.
Date Format | Set the format for dates, shown on the front-end.
Time Format | Set the format for time, shown on the front-end.
Use Date Picker | Whether this field should use the bundled cross-browser date picker ([Flatpickr.js](https://flatpickr.js.org/) docs) when rendering this field.
Date Picker Options | Add any additional options for the date picker to use. For available options, refer to the [Flatpickr.js](https://flatpickr.js.org/) docs.



### Dropdown
A field for users select from a dropdown field. The field can also get to allow multiple options to be set.

#### Settings
Setting | Description
--- | ---
Allow Multiple | Whether this field should allow multiple options to be selected.
Options | Define the available options for users to select from.



### Email Address
A field for users to enter their email. This is `<input type="email">` field.

#### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Unique Value | Whether to limit user input to unique values only. This will require that a value entered in this field does not already exist in a submission for this field and form.
Validate Domain (DNS) | Whether to validate the domain name provided for the email via DNS record lookup. This can help ensure users enter valid email addresses.
Blocked Domains | Define a list of domain names to block. Users entering email addresses containing these domains will be blocked from using them.



### Entries
A field for users to select entries from.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select entries from?
Limit | Limit the number of selectable entries.



### File Upload
A field for users to upload images from their device. This is `<input type="file">` field. It provides the following additional settings:

#### Settings
Setting | Description
--- | ---
Upload Location | 
Limit Number of Files | Limit the number of files a user can upload.
Limit File Size | Limit the size of the files a user can upload.
Restrict allowed file types | 



### Group
A field to allow grouping of additional fields, in much the same way a row is grouped, by placing fields into columns. Grouped fields can have up to 4 fields in columns.



### Heading
A field to show text in a heading.

#### Settings
Setting | Description
--- | ---
Heading Size | Choose the size for the heading.



### Hidden
A field to create a hidden input. This is `<input type="hidden">` field.

#### Settings
Setting | Description
--- | ---
Default Value | |Entering a default value will place the value in the field when it loads.



### Html
A field to allow any HTML code to be shown on the form. Useful for `<iframe>` embeds, or any arbitrary HTML.

#### Settings
Setting | Description
--- | ---
HTML Content | Enter HTML content to be rendered for this field.



### Multi-Line Text
A field for text entry that runs over multiple lines. This is a `<textarea>` input.

#### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.



### Name
A field for users to enter the name. Can be used as a single `<input type="text">` input, or split into several sub-fields:

- Prefix
- First Name
- Middle Name
- Last Name

#### Settings
Setting | Description
--- | ---
Use Multiple Name Fields | Whether this field should use multiple fields for users to enter their details.



### Number
A field to enter a validated number. This is a `<input type="number">` field.

#### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Numbers | Whether to limit the numbers for this field.
Decimal Points | Set the number of decimal points to format the field value.



### Password
A field to enter a password. This is a `<input type="password">` field. This stores its content as a hashed password, most commonly used for a user registration form.



### Payment
A field for allowing payment to be captured from the user. This field's settings will differ depending on the [Payment Integration](docs:integrations/payments) chosen.



### Phone
A field to enter a phone number. This is a `<input type="tel">` field.

#### Settings
Setting | Description
--- | ---
Show Country Code Dropdown | Whether to show an additional dropdown for selecting the country code.



### Products
A field for users to select products from.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select products from?
Limit | Limit the number of selectable products.



### Radio
A field for radio button groups, for the user to pick a single option from.

#### Settings
Setting | Description
--- | ---
Options | Define the available options for users to select from.
Layout | Select which layout to use for these fields.



### Repeater
A field to allow multiple sub-fields (similar to Group), but they are repeatable. Users can generate new rows of inputs as required. Sub-fields can be laid out in a similar fashion to rows, by placing fields into columns.

#### Settings
Setting | Description
--- | ---
Add Label | The label for the button that adds another instance.
Minimum instances | The minimum required number of instances of this repeater's fields that must be completed.
Maximum instances | The maximum required number of instances of this repeater's fields that must be completed.



### Recipients
A field to allow a dynamic recipient to be set for the submission, and used in email notifications. Any email addresses defined by this field are protected, preventing them from being scraped by bots or other parties.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.


### Section
A UI element to split field content with a `<hr>` element.

#### Settings
Setting | Description
--- | ---
Border | Add a border to this section.
Border Width | Set the border width (in pixels).
Border Color | Set the border color.



### Signature
A field for the user to sign with their mouse or finger, saving as an image.

#### Settings
Setting | Description
--- | ---
Background Color | Set the background color.
Pen Color | Set the pen color.
Pen Weight | Set the line thickness (weight) for the pen.



### Single-Line Text
A field for the user to enter text. This is a `<input type="text">` field.

#### Settings
Setting | Description
--- | ---
Placeholder | The text that will be shown if the field doesn’t have a value.
Default Value | Entering a default value will place the value in the field when it loads.
Limit Field Content | Whether to limit the content of this field.
Limit | Enter the number of characters or words to limit this field by.



### Summary
A field that shows a summary of all submission values to the user. Commonly used on the last page of a multi-page form.



### Table
A field showing values in a tabular format. Similar to a Repeater field, users can add more rows of content, but is more simplistic than a Repeater field.

#### Settings
Setting | Description
--- | ---
Table Columns | Define the columns your table should have.
Default Values | Define the default values for the field.
Add Row Label | The label for the button that adds another row.
Static | Whether this field should disallow adding more rows, showing only the default rows.
Minimum instances | The minimum required number of rows in this table that must be completed.
Maximum instances | The maximum required number of rows in this table that must be completed.



### Tags
A field for users to select or create tag elements.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select tags from?



### Users
A field for users to select users from.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Sources | Which sources do you want to select users from?
Limit | Limit the number of selectable users.



### Variants
A field for users to select variants from.

#### Settings
Setting | Description
--- | ---
Display Type | What sort of field to show on the front-end for users.
Placeholder | The option shown initially, when no option is selected.
Source | Which source do you want to select variants from?
Limit | Limit the number of selectable variants.


## Predefined Field Options
For Dropdown, Checkboxes and Radio Buttons, Formie provides a collection of preset options for common scenarios for you to pick from. This might be useful to quickly populate an entire Dropdown with a list of countries, states, languages, currencies and lots more.

You can also select what content to use for the Label and Value of each option. For instance, you might like to label the option as Australia, but have the value as the abbreviated "AU".

The below is a list of options Formie provides by default.

<!-- index-disable -->

### Countries
- Afghanistan
- Albania
- Algeria
- American Samoa
- Andorra
- Angola
- Anguilla
- Antarctica
- Antigua & Barbuda
- Argentina
- Armenia
- Aruba
- Australia
- Austria
- Azerbaijan
- Bahamas
- Bahrain
- Bangladesh
- Barbados
- Belarus
- Belgium
- Belize
- Benin
- Bermuda
- Bhutan
- Bolivia
- Bosnia
- Botswana
- Bouvet Island
- Brazil
- British Indian Ocean Territory
- British Virgin Islands
- Brunei
- Bulgaria
- Burkina Faso
- Burundi
- Cambodia
- Cameroon
- Canada
- Cape Verde
- Caribbean Netherlands
- Cayman Islands
- Central African Republic
- Chad
- Chile
- China
- Christmas Island
- Cocos (Keeling) Islands
- Colombia
- Comoros
- Congo - Brazzaville
- Congo - Kinshasa
- Cook Islands
- Costa Rica
- Croatia
- Cuba
- Curaçao
- Cyprus
- Czechia
- Côte d’Ivoire
- Denmark
- Djibouti
- Dominica
- Dominican Republic
- Ecuador
- Egypt
- El Salvador
- Equatorial Guinea
- Eritrea
- Estonia
- Eswatini
- Ethiopia
- Falkland Islands
- Faroe Islands
- Fiji
- Finland
- France
- French Guiana
- French Polynesia
- French Southern Territories
- Gabon
- Gambia
- Georgia
- Germany
- Ghana
- Gibraltar
- Greece
- Greenland
- Grenada
- Guadeloupe
- Guam
- Guatemala
- Guernsey
- Guinea
- Guinea-Bissau
- Guyana
- Haiti
- Heard & McDonald Islands
- Honduras
- Hong Kong
- Hungary
- Iceland
- India
- Indonesia
- Iran
- Iraq
- Ireland
- Isle of Man
- Israel
- Italy
- Jamaica
- Japan
- Jersey
- Jordan
- Kazakhstan
- Kenya
- Kiribati
- Kuwait
- Kyrgyzstan
- Laos
- Latvia
- Lebanon
- Lesotho
- Liberia
- Libya
- Liechtenstein
- Lithuania
- Luxembourg
- Macau
- Madagascar
- Malawi
- Malaysia
- Maldives
- Mali
- Malta
- Marshall Islands
- Martinique
- Mauritania
- Mauritius
- Mayotte
- Mexico
- Micronesia
- Moldova
- Monaco
- Mongolia
- Montenegro
- Montserrat
- Morocco
- Mozambique
- Myanmar
- Namibia
- Nauru
- Nepal
- Netherlands
- New Caledonia
- New Zealand
- Nicaragua
- Niger
- Nigeria
- Niue
- Norfolk Island
- North Korea
- North Macedonia
- Northern Mariana Islands
- Norway
- Oman
- Pakistan
- Palau
- Palestine
- Panama
- Papua New Guinea
- Paraguay
- Peru
- Philippines
- Pitcairn Islands
- Poland
- Portugal
- Puerto Rico
- Qatar
- Romania
- Russia
- Rwanda
- Réunion
- Samoa
- San Marino
- Saudi Arabia
- Senegal
- Serbia
- Seychelles
- Sierra Leone
- Singapore
- Sint Maarten
- Slovakia
- Slovenia
- Solomon Islands
- Somalia
- South Africa
- South Georgia & South Sandwich Islands
- South Korea
- South Sudan
- Spain
- Sri Lanka
- St. Barthélemy
- St. Helena
- St. Kitts & Nevis
- St. Lucia
- St. Martin
- St. Pierre & Miquelon
- St. Vincent & Grenadines
- Sudan
- Suriname
- Svalbard & Jan Mayen
- Sweden
- Switzerland
- Syria
- São Tomé & Príncipe
- Taiwan
- Tajikistan
- Tanzania
- Thailand
- Timor-Leste
- Togo
- Tokelau
- Tonga
- Trinidad & Tobago
- Tunisia
- Turkey
- Turkmenistan
- Turks & Caicos Islands
- Tuvalu
- U.S. Outlying Islands
- U.S. Virgin Islands
- UK
- US
- Uganda
- Ukraine
- United Arab Emirates
- Uruguay
- Uzbekistan
- Vanuatu
- Vatican City
- Venezuela
- Vietnam
- Wallis & Futuna
- Western Sahara
- Yemen
- Zambia
- Zimbabwe
- Åland Islands

### States (Australia)
- Australian Capital Territory
- New South Wales
- Northern Territory
- Queensland
- South Australia
- Tasmania
- Victoria
- Western Australia

### States (Canada)
- Alberta
- British Columbia
- Manitoba
- New Brunswick
- Newfoundland and Labrador
- Northwest Territories
- Nova Scotia
- Nunavut
- Ontario
- Prince Edward Island
- Quebec
- Saskatchewan
- Yukon

### States (USA)
- Alabama
- Alaska
- Arizona
- Arkansas
- California
- Colorado
- Connecticut
- Delaware
- Florida
- Georgia
- Hawaii
- Idaho
- Illinois
- Indiana
- Iowa
- Kansas
- Kentucky
- Louisiana
- Maine
- Maryland
- Massachusetts
- Michigan
- Minnesota
- Mississippi
- Missouri
- Montana
- Nebraska
- Nevada
- New Hampshire
- New Jersey
- New Mexico
- New York
- North Carolina
- North Dakota
- Ohio
- Oklahoma
- Oregon
- Pennsylvania
- Rhode Island
- South Carolina
- South Dakota
- Tennessee
- Texas
- Utah
- Vermont
- Virginia
- Washington
- West Virginia
- Wisconsin
- Wyoming

### Continents
- Africa
- Antarctica
- Asia
- Australia
- Europe
- North America
- South America

### Days
- Sunday
- Monday
- Tuesday
- Wednesday
- Thursday
- Friday
- Saturday

### Months
- January
- February
- March
- April
- May
- June
- July
- August
- September
- October
- November
- December

### Currencies
- Afghani
- Algerian Dinar
- Argentine Peso
- Armenian Dram
- Aruban Florin
- Australian Dollar
- Azerbaijan Manat
- Bahamian Dollar
- Bahraini Dinar
- Baht
- Balboa,US Dollar
- Barbados Dollar
- Belarusian Ruble
- Belize Dollar
- Bermudian Dollar
- Boliviano
- Bolívar
- Brazilian Real
- Brunei Dollar
- Bulgarian Lev
- Burundi Franc
- CFA Franc BCEAO
- CFA Franc BEAC
- CFP Franc
- Cabo Verde Escudo
- Canadian Dollar
- Cayman Islands Dollar
- Chilean Peso
- Colombian Peso
- Comorian Franc
- Congolese Franc
- Convertible Mark
- Cordoba Oro
- Costa Rican Colon
- Cuban Peso,Peso Convertible
- Czech Koruna
- Dalasi
- Danish Krone
- Denar
- Djibouti Franc
- Dobra
- Dominican Peso
- Dong
- East Caribbean Dollar
- Egyptian Pound
- El Salvador Colon,US Dollar
- Ethiopian Birr
- Euro
- Fiji Dollar
- Forint
- Ghana Cedi
- Gibraltar Pound
- Gourde,US Dollar
- Guarani
- Guinean Franc
- Guyana Dollar
- Hong Kong Dollar
- Hryvnia
- Iceland Krona
- Indian Rupee
- Indian Rupee,Ngultrum
- Iranian Rial
- Iraqi Dinar
- Jamaican Dollar
- Jordanian Dinar
- Kenyan Shilling
- Kina
- Kuna
- Kuwaiti Dinar
- Kwanza
- Kyat
- Lao Kip
- Lari
- Lebanese Pound
- Lek
- Lempira
- Leone
- Liberian Dollar
- Libyan Dinar
- Lilangeni
- Loti,Rand
- Malagasy Ariary
- Malawi Kwacha
- Malaysian Ringgit
- Mauritius Rupee
- Mexican Peso
- Moldovan Leu
- Moroccan Dirham
- Mozambique Metical
- Naira
- Nakfa
- Namibia Dollar,Rand
- Nepalese Rupee
- Netherlands Antillean Guilder
- New Israeli Sheqel
- New Zealand Dollar
- No universal currency
- North Korean Won
- Norwegian Krone
- Ouguiya
- Pakistan Rupee
- Pataca
- Pa’anga
- Peso Uruguayo
- Philippine Peso
- Pound Sterling
- Pula
- Qatari Rial
- Quetzal
- Rand
- Rial Omani
- Riel
- Romanian Leu
- Rufiyaa
- Rupiah
- Russian Ruble
- Rwanda Franc
- Saint Helena Pound
- Saudi Riyal
- Serbian Dinar
- Seychelles Rupee
- Singapore Dollar
- Sol
- Solomon Islands Dollar
- Som
- Somali Shilling
- Somoni
- South Sudanese Pound
- Sri Lanka Rupee
- Sudanese Pound
- Surinam Dollar
- Swedish Krona
- Swiss Franc
- Syrian Pound
- Taka
- Tala
- Tanzanian Shilling
- Tenge
- Trinidad and Tobago Dollar
- Tugrik
- Tunisian Dinar
- Turkish Lira
- Turkmenistan New Manat
- UAE Dirham
- US Dollar
- Uganda Shilling
- Uzbekistan Sum
- Vatu
- Won
- Yemeni Rial
- Yen
- Yuan Renminbi
- Zambian Kwacha
- Zimbabwe Dollar
- Zloty

### Languages
- Abkhazian
- Afar
- Afrikaans
- Akan
- Albanian
- Amharic
- Arabic
- Aragonese
- Armenian
- Assamese
- Avaric
- Avestan
- Aymara
- Azerbaijani
- Bambara
- Bashkir
- Basque
- Belarusian
- Bengali
- Bihari languages
- Bislama
- Bokmål, Norwegian; Norwegian Bokmål
- Bosnian
- Breton
- Bulgarian
- Burmese
- Catalan; Valencian
- Central Khmer
- Chamorro
- Chechen
- Chichewa; Chewa; Nyanja
- Chinese
- Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic
- Chuvash
- Cornish
- Corsican
- Cree
- Croatian
- Czech
- Danish
- Divehi; Dhivehi; Maldivian
- Dutch; Flemish
- Dzongkha
- English
- Esperanto
- Estonian
- Ewe
- Faroese
- Fijian
- Finnish
- French
- Fulah
- Gaelic; Scottish Gaelic
- Galician
- Ganda
- Georgian
- German
- Greek, Modern (1453-)
- Guarani
- Gujarati
- Haitian; Haitian Creole
- Hausa
- Hebrew
- Herero
- Hindi
- Hiri Motu
- Hungarian
- Icelandic
- Ido
- Igbo
- Indonesian
- Interlingua (International Auxiliary Language Association)
- Interlingue; Occidental
- Inuktitut
- Inupiaq
- Irish
- Italian
- Japanese
- Javanese
- Kalaallisut; Greenlandic
- Kannada
- Kanuri
- Kashmiri
- Kazakh
- Kikuyu; Gikuyu
- Kinyarwanda
- Kirghiz; Kyrgyz
- Komi
- Kongo
- Korean
- Kuanyama; Kwanyama
- Kurdish
- Lao
- Latin
- Latvian
- Limburgan; Limburger; Limburgish
- Lingala
- Lithuanian
- Luba-Katanga
- Luxembourgish; Letzeburgesch
- Macedonian
- Malagasy
- Malay
- Malayalam
- Maltese
- Manx
- Maori
- Marathi
- Marshallese
- Mongolian
- Nauru
- Navajo; Navaho
- Ndebele, North; North Ndebele
- Ndebele, South; South Ndebele
- Ndonga
- Nepali
- Northern Sami
- Norwegian
- Norwegian Nynorsk; Nynorsk, Norwegian
- Occitan (post 1500)
- Ojibwa
- Oriya
- Oromo
- Ossetian; Ossetic
- Pali
- Panjabi; Punjabi
- Persian
- Polish
- Portuguese
- Pushto; Pashto
- Quechua
- Romanian; Moldavian; Moldovan
- Romansh
- Rundi
- Russian
- Samoan
- Sango
- Sanskrit
- Sardinian
- Serbian
- Shona
- Sichuan Yi; Nuosu
- Sindhi
- Sinhala; Sinhalese
- Slovak
- Slovenian
- Somali
- Sotho, Southern
- Spanish; Castilian
- Sundanese
- Swahili
- Swati
- Swedish
- Tagalog
- Tahitian
- Tajik
- Tamil
- Tatar
- Telugu
- Thai
- Tibetan
- Tigrinya
- Tonga (Tonga Islands)
- Tsonga
- Tswana
- Turkish
- Turkmen
- Twi
- Uighur; Uyghur
- Ukrainian
- Urdu
- Uzbek
- Venda
- Vietnamese
- Volapük
- Walloon
- Welsh
- Western Frisian
- Wolof
- Xhosa
- Yiddish
- Yoruba
- Zhuang; Chuang
- Zulu

### Industry
- Accounting/Finance
- Advertising/Public Relations
- Aerospace/Aviation
- Arts/Entertainment/Publishing
- Automotive
- Banking/Mortgage
- Business Development
- Business Opportunity
- Clerical/Administrative
- Construction/Facilities
- Consumer Goods
- Customer Service
- Education/Training
- Energy/Utilities
- Engineering
- Government/Military
- Green
- Healthcare
- Hospitality/Travel
- Human Resources
- Installation/Maintenance
- Insurance
- Internet
- Job Search Aids
- Law Enforcement/Security
- Legal
- Management/Executive
- Manufacturing/Operations
- Marketing
- Non-Profit/Volunteer
- Pharmaceutical/Biotech
- Professional Services
- QA/Quality Control
- Real Estate
- Restaurant/Food Service
- Retail
- Sales
- Science/Research
- Skilled Labor
- Technology
- Telecommunications
- Transportation/Logistics
- Other

### Education
- High School
- Associate Degree
- Bachelor‘s Degree
- Graduate or Professional Degree
- Some College
- Other
- Prefer not to answer

### Employment
- Full-Time
- Part-Time
- Self-Employed
- Homemaker
- Retired
- Student
- Prefer not to answer

### Marital Status
- Single
- Married
- Divorced
- Widowed
- Prefer not to answer

### Age
- Under 18
- 18-24
- 25-34
- 35-44
- 45-54
- 55-64
- 65 or above
- Prefer not to answer

### Gender
- Male
- Female
- Neither
- Prefer not to answer

### Size
- Extra Extra Small
- Extra Small
- Small
- Medium
- Large
- Extra Large
- Extra Extra Large

### Acceptability
- Acceptable
- Somewhat acceptable
- Neutral
- Unacceptable
- Totally unacceptable
- Not applicable

### Agreement
- Strongly agree
- Agree
- Neutral
- Disagree
- Strongly disagree
- Not applicable

### Comparison
- Much Better
- Somewhat Better
- About the Same
- Somewhat Worse
- Much Worse
- Not applicable

### Difficulty
- Very easy
- Easy
- Neutral
- Difficult
- Very difficult
- Not applicable

### How Long
- Less than a month
- 1-6 months
- 1-3 years
- Over 3 years
- Never used

### How Often
- Every day
- Once a week
- 2 to 3 times a week
- Once a month
- 2 to 3 times a month
- Less than once a month

### Importance
- Very important
- Important
- Neutral
- Somewhat important
- Not at all important
- Not applicable

### Satisfaction
- Very satisfied
- Satisfied
- Neutral
- Unsatisfied
- Very unsatisfied
- Not applicable

### Would You
- Definitely
- Probably
- Neutral
- Probably Not
- Definitely Not
- Not applicable

<!-- index-disable-end -->

You can also register your own [predefined options](docs:developers/events#predefined-field-options)
