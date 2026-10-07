<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\Users as UsersField;

use craft\feedme\fields\Users as FeedMeUsers;

class Users extends FeedMeUsers
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = UsersField::class;
    public static string $name = 'Users';


    // Public Methods
    // =========================================================================

    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/users';
    }
}
