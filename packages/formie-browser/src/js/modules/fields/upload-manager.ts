import Uppy from '@uppy/core';
import XHRUpload from '@uppy/xhr-upload';
import uploadManagerCss from '#theme-css/fields/_upload-manager.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { getFieldModuleEventName, getFormStateEventName } from '#utils/event-names';
import { requestJson } from '#utils/http';
import { createDebug } from '#utils/debug';

const ROOT_SELECTOR = '[data-formie-upload-manager-root]';
const DROPZONE_SELECTOR = '[data-formie-upload-manager]';
const BROWSE_BUTTON_SELECTOR = '[data-formie-upload-manager-browse]';
const BROWSE_INPUT_SELECTOR = '[data-formie-upload-manager-input]';
const STATUS_INPUT_SELECTOR = '[data-formie-upload-manager-status]';
const FILE_LIST_SELECTOR = '[data-formie-upload-manager-list]';
const SORT_CONTROLS_SELECTOR = '[data-formie-upload-manager-sort-controls]';
const SORT_UP_SELECTOR = '[data-formie-upload-manager-sort="up"]';
const SORT_DOWN_SELECTOR = '[data-formie-upload-manager-sort="down"]';
const HIDDEN_INPUT_ANCHOR_ATTR = 'data-formie-file-upload-anchor';
const HIDDEN_INPUT_VALUE_ATTR = 'data-formie-file-upload-asset-id';
const FORM_RESET_EVENT = getFormStateEventName('reset');
const REPEATER_INIT_ROW_EVENT = getFieldModuleEventName('repeater', 'init-row');
const MODULE_ID = 'upload-manager';
const VALIDATOR_SCOPE = 'upload-manager';
const UPLOAD_VALIDATORS = ['uploadManagerRequired', 'uploadManagerFileLimit'] as const;
const UPLOAD_COMPLETE_DISPLAY_MS = 900;
const UPLOAD_TRANSFER_MAX = 95;
const UPLOAD_XHR_TIMEOUT_MS = 120_000;

const debug = createDebug('fields', 'upload-manager');

ensureModuleStyles(MODULE_ID, [uploadManagerCss]);

type UploadManagerOptions = {
    uploadEndpoint?: string;
    deleteEndpoint?: string;
    hydrateEndpoint?: string;
    limitFiles?: number | null;
    sizeMinLimit?: number | null;
    sizeLimit?: number | null;
    accept?: string | null;
    restrictFiles?: boolean;
};

type UploadResponse = {
    success?: boolean;
    assetId?: number;
    filename?: string;
    url?: string | null;
    errors?: Record<string, string[]>;
};

type DeleteResponse = {
    success?: boolean;
};

type HydrateResponse = {
    success?: boolean;
    assets?: Array<{
        assetId?: number;
        filename?: string;
        url?: string | null;
    }>;
};

type ManagedFile = {
    assetId: number | null;
    filename: string;
    uppyFileId: string | null;
    listItem: HTMLElement;
};

type UploadManagerState = {
    field: HTMLElement;
    dropzone: HTMLElement;
    browseInput: HTMLInputElement;
    statusInput: HTMLInputElement | null;
    fileList: HTMLElement;
    anchorInput: HTMLInputElement;
    assetInputName: string;
    uppy: Uppy;
    files: ManagedFile[];
};

function isRecord(value: unknown): value is Record<string, unknown> {
    return !!value && typeof value === 'object' && !Array.isArray(value);
}

function toPositiveInt(value: unknown): number | null {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function toTrimmedString(value: unknown): string {
    return typeof value === 'string' ? value.trim() : '';
}

function getFieldHandle(field: HTMLElement): string {
    return field.getAttribute('data-formie-field-handle')?.trim() || '';
}

function getFormHandle(form: HTMLFormElement | null): string {
    if (!form) {
        return '';
    }

    const handleInput = form.querySelector('input[name="handle"]');

    if (handleInput instanceof HTMLInputElement && handleInput.value.trim()) {
        return handleInput.value.trim();
    }

    return form.getAttribute('data-formie-handle')?.trim() || '';
}

function getHiddenInputs(field: HTMLElement): HTMLInputElement[] {
    return Array.from(field.querySelectorAll('input[type="hidden"]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement;
    });
}

function getAnchorInput(field: HTMLElement, assetInputName: string): HTMLInputElement {
    const existingAnchor = getHiddenInputs(field).find((hiddenInput) => {
        return hiddenInput.hasAttribute(HIDDEN_INPUT_ANCHOR_ATTR)
            || (hiddenInput.name === assetInputName.replace('[]', '') && hiddenInput.value === '');
    });

    if (existingAnchor) {
        existingAnchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, 'true');
        return existingAnchor;
    }

    const anchor = document.createElement('input');
    anchor.type = 'hidden';
    anchor.name = assetInputName.replace('[]', '');
    anchor.value = '';
    anchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, 'true');
    field.prepend(anchor);

    return anchor;
}

function getUploadedAssetInputs(field: HTMLElement, assetInputName: string): HTMLInputElement[] {
    return getHiddenInputs(field).filter((hiddenInput) => {
        return hiddenInput.name === assetInputName && hiddenInput.value.trim() !== '';
    });
}

function readAssetIdsFromDom(field: HTMLElement, assetInputName: string): number[] {
    return getUploadedAssetInputs(field, assetInputName).map((input) => {
        return toPositiveInt(input.value);
    }).filter((assetId): assetId is number => {
        return assetId !== null;
    });
}

function syncHiddenAssetInputs(field: HTMLElement, anchorInput: HTMLInputElement, assetInputName: string, assetIds: number[]): void {
    let insertionPoint: HTMLInputElement = anchorInput;

    getUploadedAssetInputs(field, assetInputName).forEach((hiddenInput) => {
        hiddenInput.remove();
    });

    assetIds.forEach((assetId) => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = assetInputName;
        hiddenInput.value = String(assetId);
        hiddenInput.setAttribute(HIDDEN_INPUT_VALUE_ATTR, 'true');
        insertionPoint.insertAdjacentElement('afterend', hiddenInput);
        insertionPoint = hiddenInput;
    });
}

function getUploadContext(form: HTMLFormElement | null, field: HTMLElement, dropzone: HTMLElement) {
    const inputKey = dropzone.getAttribute('data-formie-upload-key')?.trim()
        || dropzone.getAttribute('data-formie-input-id')?.trim()
        || '';

    const context: Record<string, string> = {
        handle: getFormHandle(form),
        fieldHandle: getFieldHandle(field),
        inputKey,
    };

    if (!form) {
        return context;
    }

    const passthroughNames = ['renderId', 'draftContextToken', 'draftContext', 'submissionId'] as const;

    passthroughNames.forEach((name) => {
        const input = form.querySelector(`input[name="${name}"]`);

        if (input instanceof HTMLInputElement && input.value.trim()) {
            context[name] = input.value.trim();
        }
    });

    const csrfInput = form.querySelector('input[name="CRAFT_CSRF_TOKEN"]');

    if (csrfInput instanceof HTMLInputElement && csrfInput.value.trim()) {
        context.CRAFT_CSRF_TOKEN = csrfInput.value.trim();
    }

    return context;
}

function buildHydrateFormData(form: HTMLFormElement | null, field: HTMLElement, assetIds: number[]): FormData {
    const body = new FormData();
    const handle = getFormHandle(form);
    const fieldHandle = getFieldHandle(field);

    if (handle) {
        body.append('handle', handle);
    }

    if (fieldHandle) {
        body.append('fieldHandle', fieldHandle);
    }

    const submissionUidInput = form?.querySelector('input[name="submissionUid"]');

    if (submissionUidInput instanceof HTMLInputElement && submissionUidInput.value.trim()) {
        body.append('submissionUid', submissionUidInput.value.trim());
    }

    assetIds.forEach((assetId) => {
        body.append('assetIds[]', String(assetId));
    });

    return body;
}

function parseAcceptTypes(accept: string | null | undefined): string[] | null {
    if (!accept) {
        return null;
    }

    const types = accept.split(',').map((entry) => {
        return entry.trim();
    }).filter(Boolean);

    return types.length ? types : null;
}

function getEndpoint(dropzone: HTMLElement, optionValue: string | undefined, attribute: string, fallback: string): string {
    return optionValue?.trim()
        || dropzone.getAttribute(attribute)?.trim()
        || fallback;
}

function getProgressElements(listItem: HTMLElement): {
    track: HTMLElement | null;
    bar: HTMLElement | null;
    label: HTMLElement | null;
} {
    const progressRoot = listItem.querySelector('[data-formie-upload-manager-progress]');

    if (!(progressRoot instanceof HTMLElement)) {
        return { track: null, bar: null, label: null };
    }

    const track = progressRoot.querySelector('[data-formie-upload-manager-progress-track]');
    const bar = progressRoot.querySelector('[data-formie-upload-manager-progress-bar]');
    const label = progressRoot.querySelector('[data-formie-upload-manager-progress-label]');

    return {
        track: track instanceof HTMLElement ? track : null,
        bar: bar instanceof HTMLElement ? bar : null,
        label: label instanceof HTMLElement ? label : null,
    };
}

function setIndeterminateUploadProgress(
    listItem: HTMLElement,
    labelText: string,
    phase: 'preparing' | 'uploading' | 'processing' | 'retrying' = 'uploading',
): void {
    const { track, bar, label } = getProgressElements(listItem);

    listItem.classList.add('is-uploading');
    listItem.classList.remove('is-upload-complete', 'is-complete', 'is-processing', 'is-preparing');

    if (phase === 'processing') {
        listItem.classList.add('is-processing');
    } else if (phase === 'preparing') {
        listItem.classList.add('is-preparing');
    }

    if (track) {
        track.setAttribute('data-indeterminate', 'true');
    }

    if (bar) {
        bar.style.removeProperty('width');
        bar.setAttribute('data-progress', phase);
    }

    if (label) {
        label.textContent = labelText;
    }
}

function setUploadProgress(listItem: HTMLElement, percent: number, labelText?: string): void {
    const { track, bar, label } = getProgressElements(listItem);
    const clamped = Math.max(0, Math.min(UPLOAD_TRANSFER_MAX, Math.round(percent)));

    listItem.classList.add('is-uploading');
    listItem.classList.remove('is-upload-complete', 'is-complete', 'is-processing', 'is-preparing');

    if (track) {
        track.setAttribute('data-indeterminate', 'false');
    }

    if (bar) {
        bar.style.width = `${clamped}%`;
        bar.setAttribute('data-progress', String(clamped));
    }

    if (label) {
        if (labelText) {
            label.textContent = labelText;
        } else if (clamped > 0) {
            label.textContent = `Uploading… ${clamped}%`;
        } else {
            label.textContent = 'Uploading…';
        }
    }
}

function applyUploadTransferProgress(
    listItem: HTMLElement,
    rawPercent: number,
    options: { loaded?: number; total?: number } = {},
): void {
    const { loaded = 0, total = 0 } = options;
    const bytesSent = total > 0 ? loaded >= total : rawPercent >= 100;

    if (bytesSent || rawPercent >= 100) {
        setIndeterminateUploadProgress(listItem, 'Processing…', 'processing');
        return;
    }

    if (rawPercent <= 0 && loaded <= 0) {
        setIndeterminateUploadProgress(listItem, 'Uploading…', 'uploading');
        return;
    }

    const displayPercent = Math.min(
        UPLOAD_TRANSFER_MAX,
        Math.max(1, Math.round(rawPercent)),
    );

    setUploadProgress(
        listItem,
        displayPercent,
        `Uploading… ${displayPercent}%`,
    );
}

function parseUploadErrorMessage(response: unknown, error: unknown): string {
    if (response instanceof XMLHttpRequest && response.responseText) {
        try {
            const body = JSON.parse(response.responseText) as {
                message?: string;
                errors?: Record<string, string[]>;
            };

            if (body.message) {
                return body.message;
            }

            if (body.errors) {
                const firstError = Object.values(body.errors).flat().find(Boolean);

                if (firstError) {
                    return firstError;
                }
            }
        } catch {
            // Fall through to generic messaging.
        }
    }

    if (error instanceof Error && error.message) {
        return error.message;
    }

    return 'Upload failed.';
}

function shouldRetryUpload(xhr: XMLHttpRequest): boolean {
    if (xhr.status === 0) {
        return true;
    }

    return xhr.status >= 500;
}

function markUploadComplete(listItem: HTMLElement): void {
    const { track, bar, label } = getProgressElements(listItem);

    listItem.classList.add('is-uploading', 'is-upload-complete');
    listItem.classList.remove('is-complete', 'is-processing', 'is-preparing');

    if (track) {
        track.setAttribute('data-indeterminate', 'false');
    }

    if (bar) {
        bar.style.width = '100%';
        bar.setAttribute('data-progress', '100');
    }

    if (label) {
        label.textContent = 'Complete';
    }

    window.setTimeout(() => {
        listItem.classList.add('is-complete');
        listItem.classList.remove('is-uploading', 'is-upload-complete');
    }, UPLOAD_COMPLETE_DISPLAY_MS);
}

function createSortButton(direction: 'up' | 'down'): HTMLButtonElement {
    const label = direction === 'up' ? 'Move file up' : 'Move file down';
    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'formie-upload-manager-sort-button';
    button.setAttribute('data-formie-upload-manager-sort', direction);
    button.setAttribute('data-formie-icon', direction === 'up' ? 'arrow-up' : 'arrow-down');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.textContent = label;

    return button;
}

function createListItem(field: HTMLElement, filename: string): {
    listItem: HTMLElement;
    filenameEl: HTMLElement;
    errorEl: HTMLElement;
    removeButton: HTMLButtonElement;
    sortUpButton: HTMLButtonElement;
    sortDownButton: HTMLButtonElement;
} {
    const listItem = document.createElement('li');
    listItem.className = 'formie-upload-manager-item';
    listItem.setAttribute('data-formie-upload-manager-item', 'true');

    const filenameEl = document.createElement('span');
    filenameEl.className = 'formie-upload-manager-filename';
    filenameEl.setAttribute('data-formie-upload-manager-filename', 'true');
    filenameEl.textContent = filename;

    const progressRoot = document.createElement('div');
    progressRoot.className = 'formie-upload-manager-progress';
    progressRoot.setAttribute('data-formie-upload-manager-progress', 'true');
    progressRoot.setAttribute('aria-live', 'polite');

    const progressTrack = document.createElement('div');
    progressTrack.className = 'formie-upload-manager-progress-track';
    progressTrack.setAttribute('data-formie-upload-manager-progress-track', 'true');
    progressTrack.setAttribute('data-indeterminate', 'true');

    const progressBar = document.createElement('div');
    progressBar.className = 'formie-upload-manager-progress-bar';
    progressBar.setAttribute('data-formie-upload-manager-progress-bar', 'true');
    progressBar.setAttribute('data-progress', '0');
    progressBar.style.width = '0%';

    const progressLabel = document.createElement('span');
    progressLabel.className = 'formie-upload-manager-progress-label';
    progressLabel.setAttribute('data-formie-upload-manager-progress-label', 'true');
    progressLabel.textContent = 'Preparing…';

    progressTrack.append(progressBar);
    progressRoot.append(progressTrack, progressLabel);

    const sortControls = document.createElement('div');
    sortControls.className = 'formie-upload-manager-sort-controls';
    sortControls.setAttribute('data-formie-upload-manager-sort-controls', 'true');
    sortControls.hidden = true;

    const sortUpButton = createSortButton('up');
    const sortDownButton = createSortButton('down');
    sortControls.append(sortUpButton, sortDownButton);

    const actionsRoot = document.createElement('div');
    actionsRoot.className = 'formie-upload-manager-actions';
    actionsRoot.setAttribute('data-formie-upload-manager-actions', 'true');

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'formie-upload-manager-action-button formie-upload-manager-remove-button';
    removeButton.setAttribute('data-formie-upload-manager-remove', 'true');
    removeButton.setAttribute('aria-label', 'Remove file');
    removeButton.setAttribute('title', 'Remove file');
    removeButton.setAttribute('data-formie-icon', 'close');
    removeButton.textContent = 'Remove';

    actionsRoot.append(sortControls, removeButton);

    const errorEl = document.createElement('span');
    errorEl.className = 'formie-upload-manager-error';
    errorEl.setAttribute('data-formie-upload-manager-error', 'true');
    errorEl.hidden = true;

    listItem.append(filenameEl, progressRoot, actionsRoot, errorEl);
    setIndeterminateUploadProgress(listItem, 'Preparing…', 'preparing');

    return {
        listItem,
        filenameEl,
        errorEl,
        removeButton,
        sortUpButton,
        sortDownButton,
    };
}

function getAssetInputName(field: HTMLElement): string {
    const existingAssetInput = getHiddenInputs(field).find((input) => {
        return input.hasAttribute(HIDDEN_INPUT_VALUE_ATTR);
    });

    if (existingAssetInput?.name) {
        return existingAssetInput.name;
    }

    const fieldHandle = getFieldHandle(field);

    return fieldHandle ? `fields[${fieldHandle}][]` : 'fields[fileUpload][]';
}

function countManagedAssets(state: UploadManagerState): number {
    return state.files.filter((file) => {
        return file.assetId !== null;
    }).length;
}

function syncUploadedAssetsEvent(state: UploadManagerState): void {
    const assetIds = state.files.map((file) => {
        return file.assetId;
    }).filter((assetId): assetId is number => {
        return assetId !== null;
    });

    syncHiddenAssetInputs(state.field, state.anchorInput, state.assetInputName, assetIds);

    if (state.statusInput) {
        state.statusInput.value = assetIds.length ? 'uploaded' : '';
    }

    dispatchFieldEvent(state.field, 'file-upload', 'uploaded-assets-sync', {
        assets: assetIds.map((assetId) => {
            const managed = state.files.find((file) => {
                return file.assetId === assetId;
            });

            return {
                assetId,
                filename: managed?.filename || '',
            };
        }),
    });
}

function registerValidators(form: HTMLFormElement | null): void {
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator('uploadManagerRequired', ({ input }) => {
            if (!input.matches(STATUS_INPUT_SELECTOR)) {
                return true;
            }

            const field = input.closest('[data-formie-field-handle]');

            if (!(field instanceof HTMLElement)) {
                return true;
            }

            if (input.getAttribute('data-formie-validation-required') !== 'true') {
                return true;
            }

            return readAssetIdsFromDom(field, getAssetInputName(field)).length > 0;
        }, ({ input, label, t }) => {
            return input.getAttribute('data-formie-required-message')
                ?? t('{label} cannot be blank.', { label });
        });

        validator.addValidator('uploadManagerFileLimit', ({ input }) => {
            if (!input.matches(STATUS_INPUT_SELECTOR)) {
                return true;
            }

            const field = input.closest('[data-formie-field-handle]');

            if (!(field instanceof HTMLElement)) {
                return true;
            }

            const dropzone = field.querySelector(DROPZONE_SELECTOR);

            if (!(dropzone instanceof HTMLElement)) {
                return true;
            }

            const limit = parseInt(dropzone.getAttribute('data-formie-file-limit') || '', 10);

            if (!limit) {
                return true;
            }

            return readAssetIdsFromDom(field, getAssetInputName(field)).length <= limit;
        }, ({ input, t }) => {
            const field = input.closest('[data-formie-field-handle]');
            const dropzone = field?.querySelector(DROPZONE_SELECTOR);

            return dropzone?.getAttribute('data-formie-validation-max-files-message')
                ?? t('Choose up to {files} files.', {
                    files: dropzone?.getAttribute('data-formie-file-limit') || '',
                });
        });
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, UPLOAD_VALIDATORS);
}

function resolveFormElement(form: HTMLFormElement | null, field: HTMLElement): HTMLFormElement | null {
    if (form instanceof HTMLFormElement) {
        return form;
    }

    const closestForm = field.closest('form');

    return closestForm instanceof HTMLFormElement ? closestForm : null;
}

function bindUploadManagerField(field: HTMLElement, form: HTMLFormElement | null, options: UploadManagerOptions): () => void {
    const root = field.querySelector(ROOT_SELECTOR);

    if (!(root instanceof HTMLElement)) {
        return () => { };
    }

    const dropzone = root.querySelector(DROPZONE_SELECTOR);

    if (!(dropzone instanceof HTMLElement)) {
        return () => { };
    }

    const browseInput = root.querySelector(BROWSE_INPUT_SELECTOR);
    const browseButton = root.querySelector(BROWSE_BUTTON_SELECTOR);
    const fileList = root.querySelector(FILE_LIST_SELECTOR);
    const statusInput = root.querySelector(STATUS_INPUT_SELECTOR);

    if (!(browseInput instanceof HTMLInputElement) || !(fileList instanceof HTMLElement)) {
        return () => { };
    }

    const resolvedForm = resolveFormElement(form, field);
    const resolvedStatusInput = statusInput instanceof HTMLInputElement ? statusInput : null;

    const assetInputName = getAssetInputName(field);
    const anchorInput = getAnchorInput(field, assetInputName);
    const uploadEndpoint = getEndpoint(dropzone, options.uploadEndpoint, 'data-formie-file-upload-upload-endpoint', '/actions/formie/file-upload/upload');
    const deleteEndpoint = getEndpoint(dropzone, options.deleteEndpoint, 'data-formie-file-upload-delete-endpoint', '/actions/formie/file-upload/delete');
    const hydrateEndpoint = getEndpoint(dropzone, options.hydrateEndpoint, 'data-formie-file-upload-hydrate-endpoint', '/actions/formie/file-upload/hydrate');
    const limitFiles = options.limitFiles ?? toPositiveInt(dropzone.getAttribute('data-formie-file-limit'));
    const sizeLimit = options.sizeLimit ?? parseFloat(dropzone.getAttribute('data-formie-size-max-limit') || '');
    const sizeMinLimit = options.sizeMinLimit ?? parseFloat(dropzone.getAttribute('data-formie-size-min-limit') || '');
    const accept = options.accept ?? dropzone.getAttribute('accept') ?? browseInput.accept;
    const allowedFileTypes = parseAcceptTypes(accept);

    const restrictions: {
        maxNumberOfFiles?: number;
        maxFileSize?: number;
        minFileSize?: number;
        allowedFileTypes?: string[] | null;
    } = {};

    if (limitFiles) {
        restrictions.maxNumberOfFiles = limitFiles;
    }

    if (sizeLimit) {
        restrictions.maxFileSize = sizeLimit * 1000 * 1000;
    }

    if (sizeMinLimit) {
        restrictions.minFileSize = sizeMinLimit * 1000 * 1000;
    }

    if (allowedFileTypes) {
        restrictions.allowedFileTypes = allowedFileTypes;
    }

    const uppy = new Uppy({
        autoProceed: true,
        restrictions,
    });

    uppy.use(XHRUpload, {
        endpoint: uploadEndpoint,
        fieldName: 'file',
        formData: true,
        withCredentials: true,
        timeout: UPLOAD_XHR_TIMEOUT_MS,
        shouldRetry: shouldRetryUpload,
        headers: {
            Accept: 'application/json',
        },
        allowedMetaFields: [
            'handle',
            'fieldHandle',
            'inputKey',
            'renderId',
            'draftContextToken',
            'draftContext',
            'submissionId',
            'CRAFT_CSRF_TOKEN',
        ],
        getResponseData(xhr) {
            try {
                return JSON.parse(xhr.responseText) as UploadResponse;
            } catch {
                return {};
            }
        },
        onBeforeRequest(xhr, _retryCount, files) {
            const uppyFile = files[0];

            if (!uppyFile?.id || !uppyFile.size) {
                return;
            }

            // Uppy XHRUpload ignores upload progress unless `lengthComputable` is true.
            // Multipart FormData uploads often report `lengthComputable: false`, so we
            // estimate from bytes sent vs file size to keep the bar moving during upload.
            xhr.upload.addEventListener('progress', (event: ProgressEvent) => {
                const managedFile = getManagedFileByUppyId(uppyFile.id);

                if (!managedFile) {
                    return;
                }

                let percent = 0;

                if (event.lengthComputable && event.total > 0) {
                    percent = Math.round((event.loaded / event.total) * 100);
                } else if (event.loaded > 0 && uppyFile.size) {
                    percent = Math.min(100, Math.round((event.loaded / uppyFile.size) * 100));
                }

                applyUploadTransferProgress(managedFile.listItem, percent, {
                    loaded: event.loaded,
                    total: event.lengthComputable ? event.total : (uppyFile.size ?? undefined),
                });
            });
        },
    });

    const state: UploadManagerState = {
        field,
        dropzone,
        browseInput,
        statusInput: resolvedStatusInput,
        fileList,
        anchorInput,
        assetInputName,
        uppy,
        files: [],
    };

    const remainingUploadCapacity = (): number | null => {
        if (!limitFiles) {
            return null;
        }

        return Math.max(0, limitFiles - countManagedAssets(state));
    };

    const setDropzoneActive = (active: boolean) => {
        dropzone.classList.toggle('formie-upload-manager-dropzone-active', active);
    };

    const isSortableManagedFile = (managedFile: ManagedFile): boolean => {
        return managedFile.assetId !== null
            && !managedFile.listItem.classList.contains('is-error');
    };

    const syncSortControls = () => {
        const showSort = state.files.length >= 2;

        state.files.forEach((managedFile, index) => {
            const sortControls = managedFile.listItem.querySelector(SORT_CONTROLS_SELECTOR);
            const sortUpButton = managedFile.listItem.querySelector(SORT_UP_SELECTOR);
            const sortDownButton = managedFile.listItem.querySelector(SORT_DOWN_SELECTOR);

            if (!(sortControls instanceof HTMLElement)) {
                return;
            }

            const canSort = showSort && isSortableManagedFile(managedFile);
            sortControls.hidden = !canSort;

            if (sortUpButton instanceof HTMLButtonElement) {
                sortUpButton.disabled = !canSort || index === 0;
            }

            if (sortDownButton instanceof HTMLButtonElement) {
                sortDownButton.disabled = !canSort || index >= state.files.length - 1;
            }
        });
    };

    const moveManagedFile = (managedFile: ManagedFile, direction: -1 | 1) => {
        const index = state.files.indexOf(managedFile);
        const targetIndex = index + direction;

        if (index < 0 || targetIndex < 0 || targetIndex >= state.files.length) {
            return;
        }

        const targetFile = state.files[targetIndex];
        state.files[index] = targetFile;
        state.files[targetIndex] = managedFile;

        if (direction === -1) {
            managedFile.listItem.before(targetFile.listItem);
        } else {
            managedFile.listItem.after(targetFile.listItem);
        }

        syncUploadedAssetsEvent(state);
        syncSortControls();

        dispatchFieldEvent(state.field, 'file-upload', 'uploaded-assets-reordered', {
            assets: state.files.filter((file) => {
                return file.assetId !== null;
            }).map((file) => {
                return {
                    assetId: file.assetId as number,
                    filename: file.filename,
                };
            }),
        });
    };

    const registerManagedFile = (
        managedFile: ManagedFile,
        removeButton: HTMLButtonElement,
        sortUpButton: HTMLButtonElement,
        sortDownButton: HTMLButtonElement,
    ) => {
        removeButton.addEventListener('click', () => {
            void removeManagedFile(managedFile);
        });

        sortUpButton.addEventListener('click', () => {
            moveManagedFile(managedFile, -1);
        });

        sortDownButton.addEventListener('click', () => {
            moveManagedFile(managedFile, 1);
        });
    };

    const removeManagedFile = async(managedFile: ManagedFile) => {
        if (managedFile.uppyFileId) {
            uppy.removeFile(managedFile.uppyFileId);
        }

        if (managedFile.assetId) {
            const body = new FormData();
            const context = getUploadContext(resolvedForm, field, dropzone);

            Object.entries(context).forEach(([key, value]) => {
                body.append(key, value);
            });

            body.append('assetId', String(managedFile.assetId));

            try {
                await requestJson<DeleteResponse>(deleteEndpoint, {
                    method: 'POST',
                    body,
                });
            } catch (error) {
                debug.warn('Failed to delete uploaded asset.', { assetId: managedFile.assetId, error });
            }
        }

        managedFile.listItem.remove();
        state.files = state.files.filter((file) => {
            return file !== managedFile;
        });
        syncUploadedAssetsEvent(state);
        syncSortControls();
    };

    const addManagedFileFromAsset = (assetId: number, filename: string) => {
        const { listItem, removeButton, sortUpButton, sortDownButton } = createListItem(field, filename);
        listItem.classList.add('is-complete');
        fileList.append(listItem);

        const managedFile: ManagedFile = {
            assetId,
            filename,
            uppyFileId: null,
            listItem,
        };

        registerManagedFile(managedFile, removeButton, sortUpButton, sortDownButton);
        state.files.push(managedFile);
        syncSortControls();
    };

    const hydrateExistingAssets = async() => {
        const assetIds = readAssetIdsFromDom(field, assetInputName);

        if (!assetIds.length) {
            return;
        }

        try {
            const response = await requestJson<HydrateResponse>(hydrateEndpoint, {
                method: 'POST',
                body: buildHydrateFormData(resolvedForm, field, assetIds),
            });

            const assets = response.assets || [];
            const assetsById = new Map(assets.map((asset) => {
                return [toPositiveInt(asset.assetId), asset] as const;
            }).filter(([assetId]) => {
                return assetId !== null;
            }));

            assetIds.forEach((assetId) => {
                if (state.files.some((file) => {
                    return file.assetId === assetId;
                })) {
                    return;
                }

                const asset = assetsById.get(assetId);

                if (!asset) {
                    return;
                }

                addManagedFileFromAsset(assetId, asset.filename || `Asset #${assetId}`);
            });

            syncUploadedAssetsEvent(state);
            syncSortControls();
        } catch (error) {
            debug.warn('Failed to hydrate uploaded assets.', { error });
        }
    };

    const getManagedFileByUppyId = (uppyFileId: string | undefined): ManagedFile | null => {
        if (!uppyFileId) {
            return null;
        }

        return state.files.find((file) => {
            return file.uppyFileId === uppyFileId;
        }) || null;
    };

    uppy.on('upload-progress', (uppyFile, progress) => {
        const managedFile = getManagedFileByUppyId(uppyFile?.id);

        if (!managedFile) {
            return;
        }

        const total = progress.bytesTotal || 0;
        const uploaded = progress.bytesUploaded || 0;
        const percent = total > 0 ? Math.round((uploaded / total) * 100) : 0;

        applyUploadTransferProgress(managedFile.listItem, percent, {
            loaded: uploaded,
            total,
        });
    });

    uppy.on('upload-retry', (retryFile) => {
        const managedFile = getManagedFileByUppyId(retryFile?.id);

        if (!managedFile) {
            return;
        }

        setIndeterminateUploadProgress(managedFile.listItem, 'Retrying…', 'retrying');
    });

    uppy.on('upload-success', (successFile, response) => {
        const managedFile = getManagedFileByUppyId(successFile?.id);

        if (!managedFile) {
            return;
        }

        const errorEl = managedFile.listItem.querySelector('[data-formie-upload-manager-error]');
        const body = isRecord(response?.body) ? response.body as UploadResponse : {};
        const assetId = toPositiveInt(body.assetId);

        if (!body.success || !assetId) {
            managedFile.listItem.classList.add('is-error');
            managedFile.listItem.classList.remove('is-uploading', 'is-upload-complete', 'is-processing', 'is-preparing');

            if (errorEl instanceof HTMLElement) {
                errorEl.hidden = false;
                errorEl.textContent = parseUploadErrorMessage(null, { message: 'Upload failed.' });
                const validationError = body.errors
                    ? Object.values(body.errors).flat().find(Boolean)
                    : null;

                if (validationError) {
                    errorEl.textContent = validationError;
                }
            }

            return;
        }

        managedFile.assetId = assetId;
        managedFile.filename = body.filename || managedFile.filename;
        markUploadComplete(managedFile.listItem);
        syncUploadedAssetsEvent(state);
        syncSortControls();
    });

    uppy.on('upload-error', (errorFile, error, response) => {
        const managedFile = getManagedFileByUppyId(errorFile?.id);

        if (!managedFile) {
            return;
        }

        const errorEl = managedFile.listItem.querySelector('[data-formie-upload-manager-error]');
        managedFile.listItem.classList.add('is-error');
        managedFile.listItem.classList.remove('is-uploading', 'is-upload-complete', 'is-processing', 'is-preparing');

        if (errorEl instanceof HTMLElement) {
            errorEl.hidden = false;
            errorEl.textContent = parseUploadErrorMessage(response, error);
        }
    });

    uppy.on('file-added', (file) => {
        const capacity = remainingUploadCapacity();

        if (capacity !== null && capacity <= 0) {
            uppy.removeFile(file.id);
            uppy.info('File limit reached.', 'error', 3000);
            return;
        }

        uppy.setFileMeta(file.id, getUploadContext(resolvedForm, field, dropzone));

        const { listItem, removeButton, sortUpButton, sortDownButton } = createListItem(field, file.name || 'Upload');
        fileList.append(listItem);

        const managedFile: ManagedFile = {
            assetId: null,
            filename: file.name || 'Upload',
            uppyFileId: file.id,
            listItem,
        };

        registerManagedFile(managedFile, removeButton, sortUpButton, sortDownButton);
        state.files.push(managedFile);
        syncSortControls();
    });

    const openBrowseDialog = () => {
        browseInput.click();
    };

    browseButton?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openBrowseDialog();
    });

    dropzone.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest(BROWSE_BUTTON_SELECTOR)) {
            return;
        }

        openBrowseDialog();
    });

    dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openBrowseDialog();
        }
    });

    browseInput.addEventListener('change', () => {
        if (!browseInput.files?.length) {
            return;
        }

        const files = Array.from(browseInput.files);
        browseInput.value = '';

        try {
            uppy.addFiles(files.map((file) => {
                return {
                    name: file.name,
                    type: file.type,
                    data: file,
                };
            }));
        } catch (error) {
            if (error instanceof Error) {
                uppy.info(error.message, 'error', 3000);
            }
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            event.stopPropagation();
            setDropzoneActive(true);
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            event.stopPropagation();
            setDropzoneActive(false);
        });
    });

    dropzone.addEventListener('drop', (event) => {
        const transfer = event.dataTransfer;

        if (!transfer?.files?.length) {
            return;
        }

        const files = Array.from(transfer.files);

        try {
            uppy.addFiles(files.map((file) => {
                return {
                    name: file.name,
                    type: file.type,
                    data: file,
                };
            }));
        } catch (error) {
            if (error instanceof Error) {
                uppy.info(error.message, 'error', 3000);
            }
        }
    });

    const resetHandler = () => {
        state.files.forEach((managedFile) => {
            managedFile.listItem.remove();
        });
        state.files = [];
        uppy.cancelAll();
        syncHiddenAssetInputs(field, anchorInput, assetInputName, []);
    };

    resolvedForm?.addEventListener(FORM_RESET_EVENT, resetHandler);

    void hydrateExistingAssets();

    return () => {
        resolvedForm?.removeEventListener(FORM_RESET_EVENT, resetHandler);
        uppy.destroy();
        state.files = [];
    };
}

export const uploadManagerModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(ROOT_SELECTOR);
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as UploadManagerOptions;
        const form = ctx.form;
        const boundFields = new WeakSet<HTMLElement>();
        const unbinds: Array<() => void> = [];

        registerValidators(form);

        const bindFieldsInRoot = (root: ParentNode) => {
            const fieldCandidates = new Set<HTMLElement>();

            if (root instanceof HTMLElement && root.hasAttribute('data-formie-field-handle')) {
                fieldCandidates.add(root);
            }

            if (root instanceof Element) {
                root.querySelectorAll('[data-formie-field-handle]').forEach((element) => {
                    if (element instanceof HTMLElement) {
                        fieldCandidates.add(element);
                    }
                });

                root.querySelectorAll(ROOT_SELECTOR).forEach((element) => {
                    const field = element.closest('[data-formie-field-handle]');

                    if (field instanceof HTMLElement) {
                        fieldCandidates.add(field);
                    }
                });
            }

            fieldCandidates.forEach((field) => {
                if (boundFields.has(field) || !field.querySelector(ROOT_SELECTOR)) {
                    return;
                }

                boundFields.add(field);
                unbinds.push(bindUploadManagerField(field, form, options));
            });
        };

        bindFieldsInRoot(ctx.target);

        const repeaterInitRowHandler = (event: Event) => {
            const detail = (event as CustomEvent<unknown>).detail;

            if (!isRecord(detail)) {
                return;
            }

            const row = detail.row;

            if (row instanceof HTMLElement) {
                bindFieldsInRoot(row);
            }
        };

        form?.addEventListener(REPEATER_INIT_ROW_EVENT, repeaterInitRowHandler as EventListener);

        debug.log('Module setup.', { count: unbinds.length });

        await ctx.emit('formie:module:upload-manager:init', {
            count: unbinds.length,
        });

        return {
            destroy: () => {
                form?.removeEventListener(REPEATER_INIT_ROW_EVENT, repeaterInitRowHandler as EventListener);
                unbinds.forEach((unbind) => {
                    unbind();
                });
                unregisterValidators(form);
                debug.log('Module destroy.');
                void ctx.emit('formie:module:upload-manager:destroy', {});
            },
        };
    },
};
