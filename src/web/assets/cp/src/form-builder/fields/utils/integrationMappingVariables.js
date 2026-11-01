// Destinations convert ordinary scalar values themselves. Block summaries are offered
// only where the server reports a free-text destination.
export const MAPPING_VARIABLE_CONFIG = {
    usage: 'integration',
    shapes: ['inline'],
    types: ['text', 'date', 'number', 'boolean'],
    groupFieldsByPage: true,
    groups: ['fieldsVariables', 'staticFormVariables', 'staticGeneralVariables', 'staticSiteVariables'],
};

export const SUMMARY_MAPPING_VARIABLE_CONFIG = {
    ...MAPPING_VARIABLE_CONFIG,
    shapes: ['inline', 'block'],
    includeBlockValues: true,
};

export const supportsMappingSummaries = (integrationField) => {
    return integrationField?.acceptsSummaries === true;
};
