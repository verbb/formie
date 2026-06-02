import { a as getFormStateEventName, i as getFileUploadEventName } from "./event-names-DamGPtXR.js";
import { t as requestJson } from "./http-18nn97DZ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { c as retainFormValidators, i as getModuleFieldTarget, s as releaseFormValidators, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
import fileUploadCss from "#theme/fields/_file.css?inline";
//#region src/js/modules/fields/file-upload.ts
var INPUT_SELECTOR = "input[type=\"file\"][data-formie-file-input]";
var FILE_UPLOAD_EVENT = getFileUploadEventName("uploaded");
var FORM_RESET_EVENT = getFormStateEventName("reset");
var FILE_UPLOAD_KEY_ATTR = "data-formie-file-upload-key";
var HYDRATE_ENDPOINT_ATTR = "data-formie-file-upload-hydrate-endpoint";
var HIDDEN_INPUT_ANCHOR_ATTR = "data-formie-file-upload-anchor";
var HIDDEN_INPUT_VALUE_ATTR = "data-formie-file-upload-asset-id";
var FILE_VALIDATORS = [
	"fileLimit",
	"fileSizeMinLimit",
	"fileSizeMaxLimit"
];
var VALIDATOR_SCOPE = "file-upload";
var MODULE_ID = "file-upload";
ensureModuleStyles(MODULE_ID, [fileUploadCss]);
function isRecord(value) {
	return !!value && typeof value === "object" && !Array.isArray(value);
}
function toPositiveInt(value) {
	const parsed = Number(value);
	return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}
function toTrimmedString(value) {
	return typeof value === "string" ? value.trim() : "";
}
function getFieldHandle(field) {
	return field.getAttribute("data-formie-field-handle")?.trim() || "";
}
function getFileValueName(input) {
	return input.name.endsWith("[]") ? input.name.slice(0, -2) : input.name;
}
function getUploadedAssetInputName(input) {
	return `${getFileValueName(input)}[]`;
}
function getFileUploadKey(input) {
	return input.getAttribute(FILE_UPLOAD_KEY_ATTR)?.trim() || input.getAttribute("data-formie-input-id")?.trim() || "";
}
function getHiddenInputs(field) {
	return Array.from(field.querySelectorAll("input[type=\"hidden\"]")).filter((input) => {
		return input instanceof HTMLInputElement;
	});
}
function getUploadedAssetInputs(field, input) {
	const assetInputName = getUploadedAssetInputName(input);
	return getHiddenInputs(field).filter((hiddenInput) => {
		return hiddenInput.name === assetInputName && hiddenInput.value.trim() !== "";
	});
}
function ensureUploadedAssetAnchor(field, input) {
	const anchorName = getFileValueName(input);
	const existingAnchor = getHiddenInputs(field).find((hiddenInput) => {
		return hiddenInput.hasAttribute(HIDDEN_INPUT_ANCHOR_ATTR) || hiddenInput.name === anchorName && hiddenInput.value === "";
	});
	if (existingAnchor) {
		existingAnchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, "true");
		existingAnchor.name = anchorName;
		existingAnchor.value = "";
		return existingAnchor;
	}
	const anchor = document.createElement("input");
	anchor.type = "hidden";
	anchor.name = anchorName;
	anchor.value = "";
	anchor.setAttribute(HIDDEN_INPUT_ANCHOR_ATTR, "true");
	input.insertAdjacentElement("afterend", anchor);
	return anchor;
}
function normalizeUploadedAsset(candidate) {
	if (Array.isArray(candidate)) return null;
	const directId = toPositiveInt(candidate);
	if (directId) return {
		assetId: directId,
		filename: ""
	};
	if (!isRecord(candidate)) return null;
	const assetId = toPositiveInt(candidate.assetId ?? candidate.id ?? candidate.value);
	const filename = toTrimmedString(candidate.filename ?? candidate.title ?? candidate.label ?? candidate.name);
	const url = toTrimmedString(candidate.url) || null;
	if (!assetId && !filename) return null;
	return {
		assetId,
		filename,
		url
	};
}
function normalizeUploadedAssets(candidate) {
	if (!Array.isArray(candidate)) {
		const singleAsset = normalizeUploadedAsset(candidate);
		return singleAsset ? [singleAsset] : [];
	}
	return candidate.flatMap((entry) => {
		if (Array.isArray(entry)) return normalizeUploadedAssets(entry);
		const asset = normalizeUploadedAsset(entry);
		return asset ? [asset] : [];
	});
}
function readSummaryItems(summaryRoot) {
	if (!summaryRoot) return [];
	return Array.from(summaryRoot.querySelectorAll("[data-formie-file-summary-item]")).map((item) => {
		return {
			assetId: null,
			filename: item.textContent?.trim() || ""
		};
	}).filter((asset) => {
		return asset.filename !== "";
	});
}
function readUploadedAssetsFromHiddenInputs(field, input) {
	return getUploadedAssetInputs(field, input).map((hiddenInput) => {
		return {
			assetId: toPositiveInt(hiddenInput.value),
			filename: ""
		};
	}).filter((asset) => {
		return asset.assetId !== null;
	});
}
function mergeUploadedAssets(assetIds, summaryItems) {
	if (!assetIds.length) return summaryItems;
	return assetIds.map((asset, index) => {
		return {
			assetId: asset.assetId,
			filename: summaryItems[index]?.filename || ""
		};
	});
}
function normalizeFilenameList(filenames) {
	return filenames.map((filename) => {
		return filename.trim().toLowerCase();
	}).filter(Boolean);
}
function matchesPendingFiles(uploadedAssets, pendingFiles) {
	const uploadedFilenames = normalizeFilenameList(uploadedAssets.map((asset) => {
		return asset.filename;
	}));
	const normalizedPendingFiles = normalizeFilenameList(pendingFiles);
	if (!uploadedFilenames.length || !normalizedPendingFiles.length) return false;
	return uploadedFilenames.every((filename) => {
		return normalizedPendingFiles.includes(filename);
	});
}
function registerValidators(form) {
	retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
		validator.addValidator("fileLimit", ({ input }) => {
			const limit = parseInt(input.getAttribute("data-formie-file-limit") || "", 10);
			if (input.type !== "file" || !limit || !("files" in input) || !input.files?.length) return true;
			return input.files.length <= limit;
		}, ({ input, t }) => {
			return t("Choose up to {files} files.", { files: input.getAttribute("data-formie-file-limit") || "" });
		});
		validator.addValidator("fileSizeMinLimit", ({ input }) => {
			const sizeBytes = parseFloat(input.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
			if (input.type !== "file" || !sizeBytes || !("files" in input) || !input.files?.length) return true;
			return Array.from(input.files).every((file) => {
				return file.size >= sizeBytes;
			});
		}, ({ input, t }) => {
			return t("File must be larger than {filesize} MB.", { filesize: input.getAttribute("data-formie-size-min-limit") || "" });
		});
		validator.addValidator("fileSizeMaxLimit", ({ input }) => {
			const sizeBytes = parseFloat(input.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
			if (input.type !== "file" || !sizeBytes || !("files" in input) || !input.files?.length) return true;
			return Array.from(input.files).every((file) => {
				return file.size <= sizeBytes;
			});
		}, ({ input, t }) => {
			return t("File must be smaller than {filesize} MB.", { filesize: input.getAttribute("data-formie-size-max-limit") || "" });
		});
	});
}
function unregisterValidators(form) {
	releaseFormValidators(form, VALIDATOR_SCOPE, FILE_VALIDATORS);
}
function ensureSummaryRoot(field, input) {
	const existingSummary = field.querySelector("[data-formie-file-summary]");
	if (existingSummary) return existingSummary;
	const summary = document.createElement("div");
	summary.className = "formie-file-summary";
	summary.setAttribute("data-formie-file-summary", "true");
	input.insertAdjacentElement("afterend", summary);
	return summary;
}
function renderSummary(state) {
	const items = state.pendingFiles.length ? state.pendingFiles : state.uploadedAssets.map((asset) => {
		return asset.filename || (asset.assetId ? `Asset #${asset.assetId}` : "");
	}).filter(Boolean);
	const existingSummary = state.summaryRoot || state.field.querySelector("[data-formie-file-summary]");
	if (!items.length) {
		if (existingSummary) {
			existingSummary.replaceChildren();
			existingSummary.hidden = true;
		}
		state.summaryRoot = existingSummary;
		return;
	}
	const summaryRoot = existingSummary || ensureSummaryRoot(state.field, state.input);
	if (!summaryRoot) return;
	summaryRoot.hidden = false;
	state.summaryRoot = summaryRoot;
	const list = document.createElement("ul");
	list.className = "formie-file-summary-container";
	list.setAttribute("data-formie-file-summary-container", "true");
	items.forEach((filename) => {
		const item = document.createElement("li");
		item.className = "formie-file-summary-item";
		item.setAttribute("data-formie-file-summary-item", "true");
		item.textContent = filename;
		list.appendChild(item);
	});
	summaryRoot.replaceChildren(list);
}
function updateUploadedAssetInputs(field, input, uploadedAssets) {
	let insertionPoint = ensureUploadedAssetAnchor(field, input);
	const anchorName = getUploadedAssetInputName(input);
	getUploadedAssetInputs(field, input).forEach((hiddenInput) => {
		hiddenInput.remove();
	});
	uploadedAssets.forEach((asset) => {
		if (!asset.assetId) return;
		const hiddenInput = document.createElement("input");
		hiddenInput.type = "hidden";
		hiddenInput.name = anchorName;
		hiddenInput.value = String(asset.assetId);
		hiddenInput.setAttribute(HIDDEN_INPUT_VALUE_ATTR, "true");
		insertionPoint.insertAdjacentElement("afterend", hiddenInput);
		insertionPoint = hiddenInput;
	});
	input.value = "";
}
async function hydrateUploadedAssets(field, input, uploadedAssets) {
	const assetIds = uploadedAssets.map((asset) => {
		return asset.assetId;
	}).filter((assetId) => {
		return assetId !== null;
	});
	if (!assetIds.length) return uploadedAssets;
	const hydrateEndpoint = input.getAttribute(HYDRATE_ENDPOINT_ATTR)?.trim() || "/actions/formie/file-upload/hydrate";
	const body = new FormData();
	assetIds.forEach((assetId) => {
		body.append("assetIds[]", String(assetId));
	});
	const hydratedAssets = normalizeUploadedAssets((await requestJson(hydrateEndpoint, {
		method: "POST",
		body
	})).assets);
	if (!hydratedAssets.length) return uploadedAssets;
	const hydratedMap = new Map(hydratedAssets.map((asset) => {
		return [asset.assetId, asset];
	}));
	return uploadedAssets.map((asset) => {
		if (!asset.assetId || !hydratedMap.has(asset.assetId)) return asset;
		const hydratedAsset = hydratedMap.get(asset.assetId);
		return {
			assetId: asset.assetId,
			filename: hydratedAsset.filename || asset.filename,
			url: hydratedAsset.url ?? asset.url
		};
	});
}
function getUploadedAssetsFromEvent(event, matcher) {
	const detail = event.detail;
	if (!isRecord(detail)) return [];
	const detailFieldHandle = toTrimmedString(detail.fieldHandle);
	const detailInputKey = toTrimmedString(detail.inputKey);
	const detailInputName = toTrimmedString(detail.inputName ?? detail.name);
	const nestedData = isRecord(detail.data) ? detail.data : null;
	const nestedFieldHandle = toTrimmedString(nestedData?.fieldHandle);
	const nestedInputKey = toTrimmedString(nestedData?.inputKey);
	const nestedInputName = toTrimmedString(nestedData?.inputName ?? nestedData?.name);
	const candidates = [];
	let matchedByInputKey = false;
	let matchedByInputName = false;
	let matchedByFieldHandle = false;
	if (detailInputKey !== "" && detailInputKey === matcher.inputKey) {
		matchedByInputKey = true;
		candidates.push(detail.assets, detail.assetIds, detail.data);
	}
	if (detailInputName !== "" && detailInputName === matcher.inputName) {
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
			nestedData.data
		];
		if (nestedInputKey !== "" && nestedInputKey === matcher.inputKey) {
			matchedByInputKey = true;
			candidates.push(...nestedAssetCandidates);
		}
		if (nestedInputName !== "" && nestedInputName === matcher.inputName) {
			matchedByInputName = true;
			candidates.push(...nestedAssetCandidates);
		}
		if (nestedFieldHandle === matcher.fieldHandle) {
			matchedByFieldHandle = true;
			candidates.push(...nestedAssetCandidates);
		}
	}
	[
		detail.assets,
		detail.assetIds,
		detail.data
	].forEach((value) => {
		if (!isRecord(value)) return;
		if (matcher.inputKey && matcher.inputKey in value) {
			matchedByInputKey = true;
			candidates.push(value[matcher.inputKey]);
		}
		if (matcher.inputName in value) {
			matchedByInputName = true;
			candidates.push(value[matcher.inputName]);
		}
		if (matcher.fieldHandle in value) candidates.push(value[matcher.fieldHandle]);
	});
	for (const candidate of candidates) {
		const uploadedAssets = normalizeUploadedAssets(candidate);
		if (!uploadedAssets.length) continue;
		if (matchedByInputKey || matchedByInputName || matchedByFieldHandle || matchesPendingFiles(uploadedAssets, matcher.pendingFiles)) return uploadedAssets;
	}
	return [];
}
var fileUploadModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(INPUT_SELECTOR);
	},
	setup: async (ctx) => {
		const field = getModuleFieldTarget(ctx);
		const scope = field || ctx.target;
		const inputs = Array.from(scope.querySelectorAll(INPUT_SELECTOR)).filter((input) => {
			return input instanceof HTMLInputElement;
		});
		const form = ctx.form;
		registerValidators(form);
		const unbinds = inputs.map((input) => {
			if (!(field instanceof HTMLElement)) return () => {};
			const state = {
				field,
				input,
				summaryRoot: ensureSummaryRoot(field, input),
				uploadedAssets: mergeUploadedAssets(readUploadedAssetsFromHiddenInputs(field, input), readSummaryItems(field.querySelector("[data-formie-file-summary]"))),
				pendingFiles: [],
				hydrationToken: 0
			};
			const syncPendingFiles = () => {
				state.pendingFiles = Array.from(input.files || []).map((file) => {
					return file.name;
				});
				renderSummary(state);
			};
			const syncUploadedAssets = async (uploadedAssets) => {
				state.hydrationToken += 1;
				const hydrationToken = state.hydrationToken;
				state.pendingFiles = [];
				state.uploadedAssets = uploadedAssets;
				updateUploadedAssetInputs(field, input, uploadedAssets);
				renderSummary(state);
				try {
					const hydratedAssets = await hydrateUploadedAssets(field, input, uploadedAssets);
					if (hydrationToken !== state.hydrationToken) return;
					state.uploadedAssets = hydratedAssets;
					renderSummary(state);
					dispatchFieldEvent(field, MODULE_ID, "uploaded-assets-sync", {
						fileUpload: field,
						assets: hydratedAssets
					});
				} catch (error) {
					console.error("[formie] Failed to hydrate uploaded file details.", error);
				}
			};
			const resetUploadedAssets = () => {
				state.hydrationToken += 1;
				state.pendingFiles = [];
				state.uploadedAssets = [];
				updateUploadedAssetInputs(field, input, []);
				renderSummary(state);
			};
			const uploadedAssetHandler = (event) => {
				const fieldHandle = getFieldHandle(field);
				if (!fieldHandle) return;
				const uploadedAssets = getUploadedAssetsFromEvent(event, {
					fieldHandle,
					inputKey: getFileUploadKey(input),
					inputName: input.name,
					pendingFiles: state.pendingFiles
				});
				if (!uploadedAssets.length) return;
				syncUploadedAssets(uploadedAssets);
			};
			const resetHandler = () => {
				resetUploadedAssets();
			};
			input.addEventListener("change", syncPendingFiles);
			form?.addEventListener(FILE_UPLOAD_EVENT, uploadedAssetHandler);
			form?.addEventListener(FORM_RESET_EVENT, resetHandler);
			renderSummary(state);
			return () => {
				input.removeEventListener("change", syncPendingFiles);
				form?.removeEventListener(FILE_UPLOAD_EVENT, uploadedAssetHandler);
				form?.removeEventListener(FORM_RESET_EVENT, resetHandler);
			};
		});
		await ctx.emit("formie:module:file-upload:init", { count: inputs.length });
		return { destroy: () => {
			unbinds.forEach((unbind) => {
				unbind();
			});
			unregisterValidators(form);
			ctx.emit("formie:module:file-upload:destroy", {});
		} };
	}
};
//#endregion
export { fileUploadModule };
