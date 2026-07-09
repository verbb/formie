import { generateHandle } from '@verbb/plugin-kit-react/utils';
import { createUid } from '@utils/createUid';

const getNextPageNumber = (pages = []) => {
    const maxFromExisting = (pages || []).reduce((maxValue, page) => {
        const labelMatch = String(page?.label || '').match(/^Page\s+(\d+)$/i);
        const handleMatch = String(page?._handle || page?.handle || '').match(/^page(?:-)?(\d+)$/i);

        const labelNumber = labelMatch ? Number(labelMatch[1]) : 0;
        const handleNumber = handleMatch ? Number(handleMatch[1]) : 0;
        const nextMax = Math.max(maxValue, labelNumber, handleNumber);

        return Number.isFinite(nextMax) ? nextMax : maxValue;
    }, 0);

    return Math.max(maxFromExisting + 1, (pages?.length || 0) + 1);
};

const createNewPageData = (pages = [], options = {}) => {
    const nextPageNumber = getNextPageNumber(pages);
    const isFirstPage = (pages?.length || 0) === 0;
    const {
        prefillLabel = true,
    } = options;
    const defaultLabel = `Page ${nextPageNumber}`;
    const label = prefillLabel ? defaultLabel : '';
    const generatedHandle = generateHandle(defaultLabel) || `page${nextPageNumber}`;

    return {
        uid: createUid(),
        label,
        _handle: generatedHandle,
        rows: [],
        settings: {
            showBackButton: !isFirstPage,
        },
    };
};

export { createNewPageData, getNextPageNumber };
