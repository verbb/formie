import { useEffect, useRef } from 'react';

import useAppStore from './useAppStore';

const TITLE_SEPARATOR = ' - ';

const normalizeTitlePart = (value) => {
    return String(value ?? '').trim();
};

const resolveCraftTitleSuffix = (currentTitle, formTitle, tabLabels = {}) => {
    const normalizedTitle = normalizeTitlePart(currentTitle);

    if (!normalizedTitle || !formTitle) {
        return '';
    }

    const formPrefix = `${formTitle}${TITLE_SEPARATOR}`;

    if (normalizedTitle.startsWith(formPrefix)) {
        let suffix = normalizedTitle.slice(formTitle.length);

        for (const label of Object.values(tabLabels)) {
            const tabPrefix = `${TITLE_SEPARATOR}${normalizeTitlePart(label)}`;

            if (tabPrefix !== TITLE_SEPARATOR && suffix.startsWith(`${tabPrefix}${TITLE_SEPARATOR}`)) {
                suffix = suffix.slice(tabPrefix.length);
                break;
            }
        }

        return suffix;
    }

    const firstSeparator = normalizedTitle.indexOf(TITLE_SEPARATOR);

    if (firstSeparator !== -1) {
        return normalizedTitle.slice(firstSeparator);
    }

    return '';
};

const useFormBuilderDocumentTitle = () => {
    const activeTab = useAppStore((state) => { return state.activeTab; });
    const newItemTitle = useAppStore((state) => { return state.newItemTitle; });
    const tabLabels = useAppStore((state) => { return state.tabLabels; });
    const title = useAppStore((state) => { return state.title; });
    const craftTitleSuffixRef = useRef(null);

    useEffect(() => {
        const formTitle = normalizeTitlePart(title) || normalizeTitlePart(newItemTitle);

        if (!formTitle) {
            return;
        }

        const tabLabel = normalizeTitlePart(tabLabels?.[activeTab]);

        if (craftTitleSuffixRef.current === null) {
            craftTitleSuffixRef.current = resolveCraftTitleSuffix(document.title, formTitle, tabLabels);
        }

        const titleParts = [formTitle];

        if (tabLabel) {
            titleParts.push(tabLabel);
        }

        document.title = `${titleParts.join(TITLE_SEPARATOR)}${craftTitleSuffixRef.current}`;
    }, [activeTab, newItemTitle, tabLabels, title]);
};

export { useFormBuilderDocumentTitle };
