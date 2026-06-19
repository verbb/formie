import { syncLikertKeys } from '@form-builder/utils/likertKeyValues';

export function syncQuestionOptionValues(options) {
    return syncLikertKeys(options, 'opt');
}
