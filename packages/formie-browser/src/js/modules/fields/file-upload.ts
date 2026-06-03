import fileUploadCss from '#theme-css/fields/_file.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldTarget, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { getFileUploadEventName, getFormStateEventName } from '#utils/event-names';
import { requestJson } from '#utils/http';

const INPUT_SELECTOR = 'input[type="file"][data-formie-file-input]';
const FILE_UPLOAD_EVENT = getFileUploadEventName('uploaded');
const FORM_RESET_EVENT = getFormStateEventName('reset');
const FILE_UPLOAD_KEY_ATTR = 'data-formie-file-upload-key';
const HYDRATE_ENDPOINT_ATTR = 'data-formie-file-upload-hydrate-endpoint';
const HIDDEN_INPUT_ANCHOR_ATTR = 'data-formie-file-upload-anchor';
const HIDDEN_INPUT_VALUE_ATTR = 'data-formie-file-upload-asset-id';
const FILE_VALIDATORS = [
    'fileLimit',
    'fileSizeMinLimit',
    'fileSizeMaxLimit',
] as const;
const VALIDATOR_SCOPE = 'file-upload';
const MODULE_ID = 'file-upload';

ensureModuleStyles(MODULE_ID, [fileUploadCss]);

type UploadedAsset = {
    assetId: number | null;
    filename: string;
    url?: string | null;
};

type FileUploadHydrateResponse = {
    success?: boolean;
    assets?: unknown[];
};

type FileUploadState = {
    field: HTMLElement;
    input: HTMLInputElement;
    summaryRoot: HTMLElement | null;
    uploadedAssets: UploadedAsset[];
    pendingFiles: string[];
    hydrationToken: number;
};

type FileUploadEventMatcher = {
    fieldHandle: string;
    inputKey: string;
    inputName: string;
    pendingFiles: string[];
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

function getFileValueName(input: HTMLInputElement): string {
    return input.name.endsWith('[]') ? input.name.slice(0, -2) : input.name;
}

function getUploadedAssetInputName(input: HTMLInputElement): string {
    return `${getFileValueName(input)}[]`;
}

function getFileUploadKey(input: HTMLInputElement): string {
    return input.getAttribute(FILE_UPLOAD_KEY_ATTR)?.trim()
        || input.getAttribute('data-formie-input-id')?.trim()
        || '';
}

function getHiddenInputs(field: HTMLElement): HTMLInputElement[] {
    return Array.from(field.querySelectorAll('input[type="hidden"]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement;
    });
}

function getUploadedAssetInputs(field: HTMLElement, input: HTMLInputElement): HTMLInputElement[] {
    const assetInputName = getUploadedAssetInputName(input);

    return getHiddenInputs(field).filter((hiddenInput) => {
        return hiddenInput.name === assetInputName && hiddenInput.value.trim() !== '';
    });
}

function ensureUploadedAssetAnchor(field: HTMLElement, input: HTMLInputElement): HTMLInputElement {
    const anchorName = getFileValueName(input);
    const existingAnchor = getHiddenInputs(field).find((hiddenInput) => {
        return hiddenInput.hasAttribute(HIDDEN_INPUT_ANCHOR_ATTR) || (hiddenInput.name === anchorName && hiddenInput.value === '');
    });

    if (existingAnchor) {
        existingAnchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, 'true');
        existingAnchor.name = anchorName;
        existingAnchor.value = '';
        return existingAnchor;
    }

    const anchor = document.createElement('input');
    anchor.type = 'hidden';
    anchor.name = anchorName;
    anchor.value = '';
    anchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, 'true');
    input.insertAdjacentElement('afterend', anchor);

    return anchor;
}

function normalizeUploadedAsset(candidate: unknown): UploadedAsset | null {
    if (Array.isArray(candidate)) {
        return null;
    }

    const directId = toPositiveInt(candidate);
    if (directId) {
        return {
            assetId: directId,
            filename: '',
        };
    }

    if (!isRecord(candidate)) {
        return null;
    }

    const assetId = toPositiveInt(candidate.assetId ?? candidate.id ?? candidate.value);
    const filename = toTrimmedString(candidate.filename ?? candidate.title ?? candidate.label ?? candidate.name);
    const url = toTrimmedString(candidate.url) || null;

    if (!assetId && !filename) {
        return null;
    }

    return {
        assetId,
        filename,
        url,
    };
}

function normalizeUploadedAssets(candidate: unknown): UploadedAsset[] {
    if (!Array.isArray(candidate)) {
        const singleAsset = normalizeUploadedAsset(candidate);
        return singleAsset ? [singleAsset] : [];
    }

    return candidate.flatMap((entry) => {
        if (Array.isArray(entry)) {
            return normalizeUploadedAssets(entry);
        }

        const asset = normalizeUploadedAsset(entry);
        return asset ? [asset] : [];
    });
}

function readSummaryItems(summaryRoot: HTMLElement | null): UploadedAsset[] {
    if (!summaryRoot) {
        return [];
    }

    return Array.from(summaryRoot.querySelectorAll('[data-formie-file-summary-item]')).map((item) => {
        return {
            assetId: null,
            filename: item.textContent?.trim() || '',
        };
    }).filter((asset) => {
        return asset.filename !== '';
    });
}

function readUploadedAssetsFromHiddenInputs(field: HTMLElement, input: HTMLInputElement): UploadedAsset[] {
    return getUploadedAssetInputs(field, input).map((hiddenInput) => {
        return {
            assetId: toPositiveInt(hiddenInput.value),
            filename: '',
        };
    }).filter((asset) => {
        return asset.assetId !== null;
    });
}

function mergeUploadedAssets(assetIds: UploadedAsset[], summaryItems: UploadedAsset[]): UploadedAsset[] {
    if (!assetIds.length) {
        return summaryItems;
    }

    return assetIds.map((asset, index) => {
        return {
            assetId: asset.assetId,
            filename: summaryItems[index]?.filename || '',
        };
    });
}

function normalizeFilenameList(filenames: string[]): string[] {
    return filenames.map((filename) => {
        return filename.trim().toLowerCase();
    }).filter(Boolean);
}

function matchesPendingFiles(uploadedAssets: UploadedAsset[], pendingFiles: string[]): boolean {
    const uploadedFilenames = normalizeFilenameList(uploadedAssets.map((asset) => {
        return asset.filename;
    }));
    const normalizedPendingFiles = normalizeFilenameList(pendingFiles);

    if (!uploadedFilenames.length || !normalizedPendingFiles.length) {
        return false;
    }

    return uploadedFilenames.every((filename) => {
        return normalizedPendingFiles.includes(filename);
    });
}

function registerValidators(form: HTMLFormElement | null): void {
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator('fileLimit', ({ input }) => {
            const limit = parseInt(input.getAttribute('data-formie-file-limit') || '', 10);

            if (input.type !== 'file' || !limit || !('files' in input) || !input.files?.length) {
                return true;
            }

            return input.files.length <= limit;
        }, ({ input, t }) => {
            return t('Choose up to {files} files.', {
                files: input.getAttribute('data-formie-file-limit') || '',
            });
        });

        validator.addValidator('fileSizeMinLimit', ({ input }) => {
            const sizeLimit = parseFloat(input.getAttribute('data-formie-size-min-limit') || '');
            const sizeBytes = sizeLimit * 1000 * 1000;

            if (input.type !== 'file' || !sizeBytes || !('files' in input) || !input.files?.length) {
                return true;
            }

            return Array.from(input.files).every((file) => {
                return file.size >= sizeBytes;
            });
        }, ({ input, t }) => {
            return t('File must be larger than {filesize} MB.', {
                filesize: input.getAttribute('data-formie-size-min-limit') || '',
            });
        });

        validator.addValidator('fileSizeMaxLimit', ({ input }) => {
            const sizeLimit = parseFloat(input.getAttribute('data-formie-size-max-limit') || '');
            const sizeBytes = sizeLimit * 1000 * 1000;

            if (input.type !== 'file' || !sizeBytes || !('files' in input) || !input.files?.length) {
                return true;
            }

            return Array.from(input.files).every((file) => {
                return file.size <= sizeBytes;
            });
        }, ({ input, t }) => {
            return t('File must be smaller than {filesize} MB.', {
                filesize: input.getAttribute('data-formie-size-max-limit') || '',
            });
        });
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, FILE_VALIDATORS);
}

function ensureSummaryRoot(field: HTMLElement, input: HTMLInputElement): HTMLElement | null {
    const existingSummary = field.querySelector('[data-formie-file-summary]') as HTMLElement | null;
    if (existingSummary) {
        return existingSummary;
    }

    const summary = document.createElement('div');
    summary.className = 'formie-file-summary';
    summary.setAttribute('data-formie-file-summary', 'true');

    input.insertAdjacentElement('afterend', summary);

    return summary;
}

function renderSummary(state: FileUploadState): void {
    const items = state.pendingFiles.length
        ? state.pendingFiles
        : state.uploadedAssets.map((asset) => {
            return asset.filename || (asset.assetId ? `Asset #${asset.assetId}` : '');
        }).filter(Boolean);

    const existingSummary = state.summaryRoot || state.field.querySelector('[data-formie-file-summary]') as HTMLElement | null;

    if (!items.length) {
        if (existingSummary) {
            existingSummary.replaceChildren();
            existingSummary.hidden = true;
        }

        state.summaryRoot = existingSummary;
        return;
    }

    const summaryRoot = existingSummary || ensureSummaryRoot(state.field, state.input);

    if (!summaryRoot) {
        return;
    }

    summaryRoot.hidden = false;
    state.summaryRoot = summaryRoot;
    const list = document.createElement('ul');
    list.className = 'formie-file-summary-container';
    list.setAttribute('data-formie-file-summary-container', 'true');

    items.forEach((filename) => {
        const item = document.createElement('li');
        item.className = 'formie-file-summary-item';
        item.setAttribute('data-formie-file-summary-item', 'true');
        item.textContent = filename;
        list.appendChild(item);
    });

    summaryRoot.replaceChildren(list);
}

function updateUploadedAssetInputs(field: HTMLElement, input: HTMLInputElement, uploadedAssets: UploadedAsset[]): void {
    const anchor = ensureUploadedAssetAnchor(field, input);
    let insertionPoint: HTMLInputElement = anchor;
    // The upload event returns persisted asset ids; convert those back into the
    // hidden-input shape the backend expects on subsequent submit/save actions.
    const anchorName = getUploadedAssetInputName(input);
    getUploadedAssetInputs(field, input).forEach((hiddenInput) => {
        hiddenInput.remove();
    });

    uploadedAssets.forEach((asset) => {
        if (!asset.assetId) {
            return;
        }

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = anchorName;
        hiddenInput.value = String(asset.assetId);
        hiddenInput.setAttribute(HIDDEN_INPUT_VALUE_ATTR, 'true');
        insertionPoint.insertAdjacentElement('afterend', hiddenInput);
        insertionPoint = hiddenInput;
    });

    input.value = '';
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

function buildHydrateFormData(field: HTMLElement, input: HTMLInputElement, assetIds: number[]): FormData {
    const form = input.form;
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

async function hydrateUploadedAssets(field: HTMLElement, input: HTMLInputElement, uploadedAssets: UploadedAsset[]): Promise<UploadedAsset[]> {
    const assetIds = uploadedAssets.map((asset) => {
        return asset.assetId;
    }).filter((assetId): assetId is number => {
        return assetId !== null;
    });

    if (!assetIds.length) {
        return uploadedAssets;
    }

    const hydrateEndpoint = input.getAttribute(HYDRATE_ENDPOINT_ATTR)?.trim() || '/actions/formie/file-upload/hydrate';
    const response = await requestJson<FileUploadHydrateResponse>(hydrateEndpoint, {
        method: 'POST',
        body: buildHydrateFormData(field, input, assetIds),
    });
    const hydratedAssets = normalizeUploadedAssets(response.assets);

    if (!hydratedAssets.length) {
        return uploadedAssets;
    }

    const hydratedMap = new Map(hydratedAssets.map((asset) => {
        return [asset.assetId, asset];
    }));

    return uploadedAssets.map((asset) => {
        if (!asset.assetId || !hydratedMap.has(asset.assetId)) {
            return asset;
        }

        const hydratedAsset = hydratedMap.get(asset.assetId)!;
        return {
            assetId: asset.assetId,
            filename: hydratedAsset.filename || asset.filename,
            url: hydratedAsset.url ?? asset.url,
        };
    });
}

function getUploadedAssetsFromEvent(event: Event, matcher: FileUploadEventMatcher): UploadedAsset[] {
    const detail = (event as CustomEvent<unknown>).detail;

    if (!isRecord(detail)) {
        return [];
    }

    const detailFieldHandle = toTrimmedString(detail.fieldHandle);
    const detailInputKey = toTrimmedString(detail.inputKey);
    const detailInputName = toTrimmedString(detail.inputName ?? detail.name);
    const nestedData = isRecord(detail.data) ? detail.data : null;
    const nestedFieldHandle = toTrimmedString(nestedData?.fieldHandle);
    const nestedInputKey = toTrimmedString(nestedData?.inputKey);
    const nestedInputName = toTrimmedString(nestedData?.inputName ?? nestedData?.name);
    const candidates: unknown[] = [];
    let matchedByInputKey = false;
    let matchedByInputName = false;
    let matchedByFieldHandle = false;

    if (detailInputKey !== '' && detailInputKey === matcher.inputKey) {
        matchedByInputKey = true;
        candidates.push(detail.assets, detail.assetIds, detail.data);
    }

    if (detailInputName !== '' && detailInputName === matcher.inputName) {
        matchedByInputName = true;
        candidates.push(detail.assets, detail.assetIds, detail.data);
    }

    if (detailFieldHandle === matcher.fieldHandle) {
        matchedByFieldHandle = true;
        candidates.push(detail.assets, detail.assetIds, detail.data);
    }

    if (nestedData) {
        const nestedAssetCandidates = [
            nestedData.assets,
            nestedData.assetIds,
            nestedData.uploadedAssets,
            nestedData.data,
        ];

        if (nestedInputKey !== '' && nestedInputKey === matcher.inputKey) {
            matchedByInputKey = true;
            candidates.push(...nestedAssetCandidates);
        }

        if (nestedInputName !== '' && nestedInputName === matcher.inputName) {
            matchedByInputName = true;
            candidates.push(...nestedAssetCandidates);
        }

        if (nestedFieldHandle === matcher.fieldHandle) {
            matchedByFieldHandle = true;
            candidates.push(...nestedAssetCandidates);
        }
    }

    [detail.assets, detail.assetIds, detail.data].forEach((value) => {
        if (!isRecord(value)) {
            return;
        }

        if (matcher.inputKey && matcher.inputKey in value) {
            matchedByInputKey = true;
            candidates.push(value[matcher.inputKey]);
        }

        if (matcher.inputName in value) {
            matchedByInputName = true;
            candidates.push(value[matcher.inputName]);
        }

        if (matcher.fieldHandle in value) {
            candidates.push(value[matcher.fieldHandle]);
        }
    });

    for (const candidate of candidates) {
        const uploadedAssets = normalizeUploadedAssets(candidate);

        if (!uploadedAssets.length) {
            continue;
        }

        if (matchedByInputKey || matchedByInputName || matchedByFieldHandle || matchesPendingFiles(uploadedAssets, matcher.pendingFiles)) {
            return uploadedAssets;
        }
    }

    return [];
}

export const fileUploadModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(INPUT_SELECTOR);
    },
    setup: async(ctx) => {
        const field = getModuleFieldTarget(ctx);
        const scope = field || ctx.target;
        const inputs = Array.from(scope.querySelectorAll(INPUT_SELECTOR)).filter((input): input is HTMLInputElement => {
            return input instanceof HTMLInputElement;
        });
        const form = ctx.form;

        registerValidators(form);

        const unbinds = inputs.map((input) => {
            if (!(field instanceof HTMLElement)) {
                return () => {};
            }

            const state: FileUploadState = {
                field,
                input,
                summaryRoot: ensureSummaryRoot(field, input),
                uploadedAssets: mergeUploadedAssets(
                    readUploadedAssetsFromHiddenInputs(field, input),
                    readSummaryItems(field.querySelector('[data-formie-file-summary]') as HTMLElement | null)
                ),
                pendingFiles: [],
                hydrationToken: 0,
            };
            const syncPendingFiles = () => {
                state.pendingFiles = Array.from(input.files || []).map((file) => {
                    return file.name;
                });
                renderSummary(state);
            };
            const syncUploadedAssets = async(uploadedAssets: UploadedAsset[]) => {
                state.hydrationToken += 1;
                const hydrationToken = state.hydrationToken;
                state.pendingFiles = [];
                state.uploadedAssets = uploadedAssets;
                updateUploadedAssetInputs(field, input, uploadedAssets);
                renderSummary(state);

                try {
                    const hydratedAssets = await hydrateUploadedAssets(field, input, uploadedAssets);

                    if (hydrationToken !== state.hydrationToken) {
                        return;
                    }

                    state.uploadedAssets = hydratedAssets;
                    renderSummary(state);
                    dispatchFieldEvent(field, MODULE_ID, 'uploaded-assets-sync', {
                        fileUpload: field,
                        assets: hydratedAssets,
                    });
                } catch (error) {
                    console.error('[formie] Failed to hydrate uploaded file details.', error);
                }
            };
            const resetUploadedAssets = () => {
                state.hydrationToken += 1;
                state.pendingFiles = [];
                state.uploadedAssets = [];
                updateUploadedAssetInputs(field, input, []);
                renderSummary(state);
            };
            const uploadedAssetHandler = (event: Event) => {
                const fieldHandle = getFieldHandle(field);

                if (!fieldHandle) {
                    return;
                }

                const uploadedAssets = getUploadedAssetsFromEvent(event, {
                    fieldHandle,
                    inputKey: getFileUploadKey(input),
                    inputName: input.name,
                    pendingFiles: state.pendingFiles,
                });

                if (!uploadedAssets.length) {
                    return;
                }

                void syncUploadedAssets(uploadedAssets);
            };
            const resetHandler = () => {
                resetUploadedAssets();
            };

            input.addEventListener('change', syncPendingFiles);
            form?.addEventListener(FILE_UPLOAD_EVENT, uploadedAssetHandler as EventListener);
            form?.addEventListener(FORM_RESET_EVENT, resetHandler as EventListener);

            const syncInitialState = () => {
                const assetIdsFromDom = readUploadedAssetsFromHiddenInputs(field, input);
                const summaryItems = readSummaryItems(state.summaryRoot);
                state.uploadedAssets = mergeUploadedAssets(assetIdsFromDom, summaryItems);

                if (state.uploadedAssets.some((asset) => asset.assetId && !asset.filename)) {
                    void syncUploadedAssets(state.uploadedAssets);
                    return;
                }

                renderSummary(state);
            };

            syncInitialState();

            return () => {
                input.removeEventListener('change', syncPendingFiles);
                form?.removeEventListener(FILE_UPLOAD_EVENT, uploadedAssetHandler as EventListener);
                form?.removeEventListener(FORM_RESET_EVENT, resetHandler as EventListener);
            };
        });

        await ctx.emit('formie:module:file-upload:init', {
            count: inputs.length,
        });

        return {
            destroy: () => {
                unbinds.forEach((unbind) => {
                    unbind();
                });
                unregisterValidators(form);

                void ctx.emit('formie:module:file-upload:destroy', {});
            },
        };
    },
};
