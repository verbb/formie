import { syncLikertKeys } from '@form-builder/utils/likertKeyValues';

export function syncLikertColumnValues(options) {
    return syncLikertKeys(options, 'col');
}
