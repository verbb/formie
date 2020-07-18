<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class Address extends Model
{
    public $address1;
    public $address2;
    public $address3;
    public $city;
    public $state;
    public $zip;
    public $country;

}
