import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ElementSelectField } from './ElementSelectField.jsx';

const controls = vi.hoisted(() => {return {
    buttons: [],
    embeddedSelector: null,
    openSelector: vi.fn(),
    setElementData: vi.fn(),
}});

vi.mock('@verbb/plugin-kit-react/components', () => {return {
    Button: (props) => { controls.buttons.push(props); return null; },
    Icon: () => {return null},
    Spinner: () => {return null},
    Status: () => {return null},
}});
vi.mock('@verbb/plugin-kit-react/utils', () => {return {
    cn: () => {return ''},
    hostOpenElementSelector: controls.openSelector,
    hostRequest: vi.fn(),
}});
vi.mock('@verbb/plugin-kit-react/hooks', () => {return {
    useTranslation: () => {return (message) => {return message}},
}});
vi.mock('@verbb/plugin-kit-react/forms', () => {return {
    FieldLayout: ({ children }) => {return children},
}});
vi.mock('./element-store.js', () => {return {
    default: () => {return {
        getElementData: () => {return []},
        hasElementData: () => {return false},
        setElementData: controls.setElementData,
    }},
}});
vi.mock('./CraftElementIndexDialog.jsx', () => {return {
    CraftElementIndexDialog: (props) => { controls.embeddedSelector = props; return null; },
}});

beforeEach(() => {
    controls.buttons = [];
    controls.embeddedSelector = null;
    vi.clearAllMocks();
});

const renderField = (settings = {}) => {
    const form = { setFieldValue: vi.fn() };
    renderToStaticMarkup(React.createElement(ElementSelectField, {
        form,
        field: {
            name: 'redirectEntry',
            elementType: 'craft\\elements\\Entry',
            elementSelectStorageKeyPrefix: 'FormieElementSelectField',
            limit: 1,
            ...settings,
        },
    }));
    return form;
};

describe('Craft element source selection', () => {
    it.each([false, true])('allows Craft to supply all available sources (embedded: %s)', (embeddedElementIndex) => {
        renderField({ embeddedElementIndex });
        if (!embeddedElementIndex) {
            controls.buttons[0].onClick();
        }

        const options = embeddedElementIndex ? controls.embeddedSelector : controls.openSelector.mock.calls[0][1];
        // ['*'] selects only All entries, which editors can disable in Craft.
        expect(options.sources).toBeNull();
        expect(options.multiSelect).toBe(false);
    });

    it.each([{ sources: ['section:blog'] }, { sources: [] }, { sources: false }])('preserves an explicit source restriction: $sources', ({ sources }) => {
        renderField({ sources });
        controls.buttons[0].onClick();
        expect(controls.openSelector.mock.calls[0][1].sources).toEqual(sources);
    });

    it('retains the selected entry and its site', () => {
        const form = renderField();
        controls.buttons[0].onClick();
        const [elementType, options] = controls.openSelector.mock.calls[0];
        expect(elementType).toBe('craft\\elements\\Entry');
        options.onSelect([{ id: 42, siteId: 3, label: 'Thank you', status: 'live' }]);
        expect(form.setFieldValue).toHaveBeenCalledWith('redirectEntry', [{ id: 42, siteId: 3 }]);
        expect(controls.setElementData).toHaveBeenCalledWith('redirectEntry', [expect.objectContaining({
            id: 42, siteId: 3, label: 'Thank you', elementType,
        })]);
    });
});
