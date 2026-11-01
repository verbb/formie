import * as core from '@tiptap/core';
import * as pmModel from '@tiptap/pm/model';
import * as pmState from '@tiptap/pm/state';
import * as pmView from '@tiptap/pm/view';
import {
    registerTiptapExtension as registerKitExtension,
    registerTiptapTextStyleDefinition,
    registerTiptapToolbarControl as registerKitControl,
} from '@verbb/plugin-kit-tiptap-core';

const formie = Craft.Formie ??= {};
const stateKey = Symbol.for('verbb.formie.tiptap.state');
const registrationState = formie[stateKey] ??= {
    registeredExtensionIds: new Set(),
    registeredTextStyleIds: new Set(),
};
const { registeredExtensionIds, registeredTextStyleIds } = registrationState;

const registerTiptapExtension = (id, factoryOrExtension, options = {}) => {
    const dispose = registerKitExtension({
        id,
        extension: factoryOrExtension,
        surfaces: options.surfaces,
    });

    registeredExtensionIds.add(id);

    return () => {
        registeredExtensionIds.delete(id);
        dispose();
    };
};

const registerTiptapControl = (id, control) => {
    return registerKitControl({
        ...control,
        id,
    });
};

const bridge = {
    core,
    prosemirror: {
        model: pmModel,
        state: pmState,
        view: pmView,
    },
    registerTiptapExtension,
    registerTiptapControl,
};

formie.registerTiptapExtension = registerTiptapExtension;
formie.registerTiptapControl = registerTiptapControl;
formie.tiptap = bridge;

const dispatchRegistrationEvent = (requirements = null) => {
    document.dispatchEvent(new CustomEvent('formie:tiptap:register', {
        detail: {
            ...bridge,
            requirements,
        },
    }));
};

/** Apply server-authorized declarative styles and verify both halves of persisted extensions. */
export const configureFormieTiptap = (config = {}) => {
    (config.textStyles ?? []).forEach((definition) => {
        if (registeredTextStyleIds.has(definition.id)) {
            return;
        }

        registerTiptapTextStyleDefinition(definition);
        registeredTextStyleIds.add(definition.id);
    });

    dispatchRegistrationEvent(config);

    const missingExtensionIds = (config.extensionIds ?? []).filter((id) => {
        return !registeredExtensionIds.has(id);
    });

    if (missingExtensionIds.length > 0) {
        throw new Error(
            `Formie TipTap extensions are registered on the server but missing from the client: ${missingExtensionIds.join(', ')}.`,
        );
    }
};

dispatchRegistrationEvent();
