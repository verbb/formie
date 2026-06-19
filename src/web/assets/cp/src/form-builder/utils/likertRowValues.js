import { syncLikertKeys } from '@form-builder/utils/likertKeyValues';

export function syncLikertRowValues(rows) {
    return syncLikertKeys(rows, 'row');
}
