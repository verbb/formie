const createOption = (label, value) => {
    return {
        label,
        value,
        default: false,
    };
};

export const SURVEY_DISPLAY_DEFAULTS = {
    likert: [
        createOption(Craft.t('formie', 'Strongly disagree'), 'strongly-disagree'),
        createOption(Craft.t('formie', 'Disagree'), 'disagree'),
        createOption(Craft.t('formie', 'Neutral'), 'neutral'),
        createOption(Craft.t('formie', 'Agree'), 'agree'),
        createOption(Craft.t('formie', 'Strongly agree'), 'strongly-agree'),
    ],
    rating: [
        createOption(Craft.t('formie', 'Terrible'), 'terrible'),
        createOption(Craft.t('formie', 'Not so great'), 'not-so-great'),
        createOption(Craft.t('formie', 'Neutral'), 'neutral'),
        createOption(Craft.t('formie', 'Pretty good'), 'pretty-good'),
        createOption(Craft.t('formie', 'Excellent'), 'excellent'),
    ],
};

export const getSurveyDisplayDefaultOptions = (displayType) => {
    return SURVEY_DISPLAY_DEFAULTS[displayType] ?? null;
};

export const hasSurveyDisplayDefaultOptions = (displayType) => {
    return getSurveyDisplayDefaultOptions(displayType) !== null;
};
