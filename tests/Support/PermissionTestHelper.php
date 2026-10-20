<?php

namespace Tests\Support;

use verbb\formie\Formie;

final class PermissionTestHelper
{
    public static function withParents(array $grants): array
    {
        // Fixture roles select the same ancestors required by Craft's checkbox editor.
        // Migration and hierarchy tests deliberately assign their raw grants instead.
        $selected = array_map('strtolower', $grants);
        $visit = function(array $definitions, array $parents = []) use (&$visit, &$selected): void {
            foreach ($definitions as $name => $definition) {
                if (in_array(strtolower($name), $selected, true)) {
                    array_push($selected, ...array_intersect(array_map('strtolower', $parents), [
                        'formie-accesssettingssection', 'formie-accessspamprotection',
                        'formie-accessstencilssection', 'formie-accessintegrationssection',
                        'formie-settingsimportexport', 'formie-settingsscheduledreports',
                    ]));
                }
                if (isset($definition['nested'])) {
                    $visit($definition['nested'], [...$parents, $name]);
                }
            }
        };
        foreach (Formie::$plugin->getPermissions()->getPermissionGroups() as $group) {
            $visit($group['permissions']);
        }
        return array_values(array_unique($selected));
    }
}
