import { c as getScopedModuleLifecycleEventName, d as toDomEventName, l as getValidatorEventName, o as getGlobalModuleLifecycleEventName, r as getFieldModuleEventName, t as FORMIE_HTML_EVENT_NAMES, u as normalizeFormieEventName } from "./chunks/event-names-DamGPtXR.js";
import { a as setFormieDebugEnabled, i as isFormieDebugEnabled, n as debugLog, r as debugWarn, t as createDebug } from "./chunks/debug-KnZeKYBI.js";
import { a as setFormHiddenState, c as dispatchPageClientEventForSubmit, i as clearSubmitLoading, n as applyPageState, o as setSubmitLoading, r as applySubmitResultState, s as syncPageTabErrors, t as definePaymentModule } from "./chunks/api-DE7LfK-R.js";
import { n as registerThemeClassMap, r as removeThemeClasses, t as addThemeClasses } from "./chunks/theme-classes-vSHpdCUO.js";
import { t as requestJson } from "./chunks/http-18nn97DZ.js";
import { a as translate, i as t, n as mergeFormieTranslations, r as setFormieTranslations, t as getFormieTranslations } from "./chunks/i18n-vUh-KGiH.js";
import { n as definePassiveCaptchaModule, t as defineCaptchaModule } from "./chunks/api-DOfDzYC_.js";
import { n as inputNameToFieldKey, r as normalizeFieldKey, t as fieldKeyToInputName } from "./chunks/field-references.keys-BpBZ_quS.js";
import { i as parseFieldReference, n as resolveFieldReferenceLive, r as buildFieldValueRegistry, t as resolveFieldReferenceFromFormData } from "./chunks/field-references.resolver-Ba6xhiJC.js";
import { t as defineAddressModule } from "./chunks/api-CbqEMQT5.js";
//#region src/js/compatibility/event-map.ts
var LEGACY_FORMIE_DOM_EVENT_BRIDGES = [
	{
		legacyEvent: "onFormieLoaded",
		canonicalEvent: "formie:mount:after",
		disposition: "approximate",
		target: "document"
	},
	{
		legacyEvent: "onFormieInit",
		canonicalEvent: "formie:mount:after",
		disposition: "approximate",
		target: "document"
	},
	{
		legacyEvent: "onFormieReady",
		canonicalEvent: "formie:mount:after",
		disposition: "safe"
	},
	{
		legacyEvent: "onAfterFormieSubmit",
		canonicalEvent: "formie:submit:result",
		disposition: "safe"
	},
	{
		legacyEvent: "onFormieSubmitError",
		canonicalEvent: "formie:submit:result",
		disposition: "safe"
	},
	{
		legacyEvent: "onFormiePageToggle",
		canonicalEvent: "formie:page:navigate:after",
		disposition: "safe"
	},
	{
		legacyEvent: "onBeforeFormieSubmit",
		canonicalEvent: "formie:submit:before",
		disposition: "approximate"
	},
	{
		legacyEvent: "onFormieValidate",
		canonicalEvent: "formie:stage:validate:before",
		disposition: "approximate"
	},
	{
		legacyEvent: "onAfterFormieValidate",
		canonicalEvent: "formie:stage:validate:after",
		disposition: "approximate"
	},
	{
		legacyEvent: "onFormieSubmit",
		canonicalEvent: "formie:submit:after",
		disposition: "approximate"
	}
];
var LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES = [
	{
		legacyEvent: "formieValidatorInitialized",
		canonicalEvent: "formie:validator:ready",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorDestroyed",
		canonicalEvent: "formie:validator:destroy",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorShowError",
		canonicalEvent: "formie:validator:show-error",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorClearError",
		canonicalEvent: "formie:validator:clear-error",
		disposition: "safe"
	}
];
function resolveLegacyCompatibilityOptions(options) {
	if (!options) return {
		enabled: false,
		legacyDomEvents: false,
		legacyValidatorEvents: false
	};
	if (options === true) return {
		enabled: true,
		legacyDomEvents: true,
		legacyValidatorEvents: true
	};
	const legacyDomEvents = options.legacyDomEvents ?? true;
	const legacyValidatorEvents = options.legacyValidatorEvents ?? true;
	return {
		enabled: legacyDomEvents || legacyValidatorEvents,
		legacyDomEvents,
		legacyValidatorEvents
	};
}
//#endregion
//#region src/js/compatibility/dom-adapter.ts
function dispatchLegacyDomEvent(target, legacyEvent, detail) {
	target.dispatchEvent(new CustomEvent(legacyEvent, {
		bubbles: true,
		detail
	}));
}
function shouldDispatchBridge(bridge, detail) {
	if (bridge.canonicalEvent !== "formie:submit:result") return true;
	const result = detail;
	if (bridge.legacyEvent === "onAfterFormieSubmit") return !!result?.ok;
	if (bridge.legacyEvent === "onFormieSubmitError") return result?.ok === false;
	return true;
}
function createLegacyPageToggleDetail(form, detail) {
	const eventDetail = detail && typeof detail === "object" ? detail : {};
	const nextPageId = typeof eventDetail.pageId === "string" ? eventDetail.pageId : "";
	const pages = Array.from(form.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId,
		nextPageIndex: pages.findIndex((page) => {
			return page.getAttribute("data-formie-page-id") === nextPageId;
		}),
		totalPages: pages.length
	} };
}
function createLegacyDetail(bridge, detail, target, form, instance) {
	const legacyFormieApi = globalThis.Formie || instance;
	if (bridge.legacyEvent === "onFormieLoaded") return { formie: legacyFormieApi };
	if (bridge.legacyEvent === "onFormieInit") return {
		formie: legacyFormieApi,
		form: instance,
		$form: form,
		formId: instance.id
	};
	if (bridge.legacyEvent === "onFormieReady") return {
		...detail && typeof detail === "object" ? detail : {},
		form,
		target,
		instance
	};
	if (bridge.legacyEvent === "onFormiePageToggle") return createLegacyPageToggleDetail(form, detail);
	return detail;
}
function bindLegacyDomEventCompatibility({ target, form, instance, options, unbinds }) {
	if (!options.legacyDomEvents) return;
	LEGACY_FORMIE_DOM_EVENT_BRIDGES.forEach((bridge) => {
		const handler = (event) => {
			if (!(event instanceof CustomEvent) || !shouldDispatchBridge(bridge, event.detail)) return;
			dispatchLegacyDomEvent(bridge.target === "document" ? document : form, bridge.legacyEvent, createLegacyDetail(bridge, event.detail, target, form, instance));
		};
		target.addEventListener(toDomEventName(bridge.canonicalEvent), handler);
		unbinds.push(() => {
			target.removeEventListener(toDomEventName(bridge.canonicalEvent), handler);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function dispatchLegacyValidatorEvent(target, legacyEvent, detail) {
	target.dispatchEvent(new CustomEvent(legacyEvent, {
		bubbles: true,
		detail
	}));
}
function matchesValidator(detail, validator) {
	return !!detail && typeof detail === "object" && detail.validator === validator;
}
function bindLegacyValidatorCompatibility({ target, form, validatorDetail, options, unbinds }) {
	if (!options.legacyValidatorEvents || !validatorDetail) return;
	const { validator, addValidator, removeValidator } = validatorDetail;
	const baseDetail = {
		...validatorDetail,
		form,
		target
	};
	dispatchLegacyValidatorEvent(document, "formieValidatorInitialized", baseDetail);
	const destroyHandler = (event) => {
		if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator)) return;
		dispatchLegacyValidatorEvent(document, "formieValidatorDestroyed", {
			...baseDetail,
			...event.detail
		});
	};
	const showErrorHandler = (event) => {
		if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator) || !(event.target instanceof Element)) return;
		if (!form.contains(event.target)) return;
		dispatchLegacyValidatorEvent(event.target, "formieValidatorShowError", {
			...event.detail,
			addValidator,
			removeValidator,
			form,
			target
		});
	};
	const clearErrorHandler = (event) => {
		if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator) || !(event.target instanceof Element)) return;
		if (!form.contains(event.target)) return;
		dispatchLegacyValidatorEvent(event.target, "formieValidatorClearError", {
			...event.detail,
			addValidator,
			removeValidator,
			form,
			target
		});
	};
	document.addEventListener("formie:validator:destroy", destroyHandler);
	document.addEventListener("formie:validator:show-error", showErrorHandler);
	document.addEventListener("formie:validator:clear-error", clearErrorHandler);
	unbinds.push(() => {
		document.removeEventListener("formie:validator:destroy", destroyHandler);
		document.removeEventListener("formie:validator:show-error", showErrorHandler);
		document.removeEventListener("formie:validator:clear-error", clearErrorHandler);
	});
}
//#endregion
//#region src/js/core/dom-events.ts
function dispatchFormieDomEvent(target, eventName, detail) {
	target.dispatchEvent(new CustomEvent(toDomEventName(eventName), {
		bubbles: true,
		detail
	}));
}
//#endregion
//#region src/js/events/event-bus.ts
var EventBus = class {
	constructor() {
		this.listeners = /* @__PURE__ */ new Map();
	}
	on(eventName, callback) {
		if (!this.listeners.has(eventName)) this.listeners.set(eventName, /* @__PURE__ */ new Set());
		this.listeners.get(eventName)?.add(callback);
		return () => {
			this.listeners.get(eventName)?.delete(callback);
		};
	}
	async emit(eventName, payload) {
		const callbacks = this.listeners.get(eventName);
		if (!callbacks || callbacks.size === 0) return;
		for (const callback of callbacks) await callback(payload);
	}
	async emitSafe(eventName, payload) {
		const callbacks = this.listeners.get(eventName);
		const report = {
			eventName,
			total: callbacks?.size || 0,
			succeeded: 0,
			failed: []
		};
		if (!callbacks || callbacks.size === 0) return report;
		let index = 0;
		for (const callback of callbacks) {
			try {
				await callback(payload);
				report.succeeded += 1;
			} catch (error) {
				report.failed.push({
					index,
					error
				});
			}
			index += 1;
		}
		return report;
	}
	async emitParallelSafe(eventName, payload) {
		const callbacks = this.listeners.get(eventName);
		const report = {
			eventName,
			total: callbacks?.size || 0,
			succeeded: 0,
			failed: []
		};
		if (!callbacks || callbacks.size === 0) return report;
		(await Promise.allSettled(Array.from(callbacks).map(async (callback) => {
			return callback(payload);
		}))).forEach((result, index) => {
			if (result.status === "fulfilled") {
				report.succeeded += 1;
				return;
			}
			report.failed.push({
				index,
				error: result.reason
			});
		});
		return report;
	}
	clear() {
		this.listeners.clear();
	}
};
//#endregion
//#region src/js/transport/forms-api.ts
var debug$6 = createDebug("general", "transport");
function toServerRenderPayloadInput(renderOptions) {
	const input = {};
	[
		"theme",
		"themeConfig",
		"locale",
		"siteId"
	].forEach((key) => {
		if (renderOptions[key] !== void 0) input[key] = renderOptions[key];
	});
	return input;
}
function flattenErrors(errors, path = "", output = {}) {
	if (Array.isArray(errors)) {
		const messages = errors.map((value) => {
			return typeof value === "string" ? value : String(value ?? "");
		}).filter((value) => {
			return value.trim() !== "";
		});
		if (path && messages.length) output[path] = (output[path] || []).concat(messages);
		return output;
	}
	if (errors && typeof errors === "object") Object.entries(errors).forEach(([key, value]) => {
		flattenErrors(value, path ? `${path}.${key}` : key, output);
	});
	return output;
}
function normalizePayload(payload, fallbackFormError) {
	const success = payload.success === true;
	const keepSubmitLoading = payload.keepSubmitLoading === true;
	const errors = payload.errors;
	const fieldErrorsFlat = flattenErrors(errors || {});
	const formErrors = fieldErrorsFlat.form || [];
	const fieldErrors = {};
	Object.entries(fieldErrorsFlat).forEach(([key, value]) => {
		if (key === "form") return;
		const topKey = key.split(".")[0];
		fieldErrors[topKey] = (fieldErrors[topKey] || []).concat(value);
	});
	const resolvedFormErrors = !success && formErrors.length === 0 && Object.keys(fieldErrors).length > 0 ? [fallbackFormError || "Submission failed."] : formErrors;
	const isTransientPendingResult = !success && keepSubmitLoading && resolvedFormErrors.length === 0 && Object.keys(fieldErrors).length === 0;
	return {
		ok: success,
		action: payload.submitAction === "back" || payload.submitAction === "save" || payload.submitAction === "submit" ? payload.submitAction : void 0,
		message: payload.submitActionMessage || (success ? "Submission completed." : isTransientPendingResult ? "" : resolvedFormErrors[0] || "Submission failed."),
		code: success ? void 0 : String(payload.code || "SUBMIT_ERROR"),
		keepSubmitLoading,
		fieldErrors: Object.keys(fieldErrors).length ? fieldErrors : void 0,
		formErrors: resolvedFormErrors.length ? resolvedFormErrors : void 0,
		nextPage: payload.nextPageId ? { id: String(payload.nextPageId) } : null,
		redirect: payload.redirectUrl ? {
			url: String(payload.redirectUrl),
			target: payload.submitActionTab === "new-tab" ? "new-tab" : "same-tab"
		} : null,
		submitData: Array.isArray(payload.submitData) ? payload.submitData : void 0,
		meta: payload
	};
}
async function requestRender(endpoint, handle, renderOptions = {}) {
	const body = JSON.stringify({
		handle,
		renderOptions
	});
	debug$6.log("requestRender start.", {
		endpoint,
		handle
	});
	const result = await requestJson(endpoint, {
		method: "POST",
		body,
		headers: { "Content-Type": "application/json" }
	});
	debug$6.log("requestRender complete.", { hasHtml: !!result.html });
	return result;
}
async function requestGraphqlRender(endpoint, handle, renderOptions = {}) {
	const body = JSON.stringify({
		query: `
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,
		variables: {
			handle,
			input: toServerRenderPayloadInput(renderOptions)
		}
	});
	debug$6.log("requestGraphqlRender start.", {
		endpoint,
		handle
	});
	const result = await requestJson(endpoint, {
		method: "POST",
		body,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(result.errors) && result.errors.length > 0) throw new Error(result.errors.map((error) => error.message || "Unknown GraphQL error").join("; "));
	if (!result.data?.formieHtmlForm) throw new Error(`Form not found for handle "${handle}".`);
	const payload = result.data.formieHtmlForm;
	debug$6.log("requestGraphqlRender complete.", { hasHtml: !!payload.html });
	return payload;
}
async function requestRefreshTokens(endpoint, handle, renderId) {
	const url = new URL(endpoint, window.location.origin);
	url.searchParams.set("handle", handle);
	if (renderId) url.searchParams.set("renderId", renderId);
	debug$6.log("requestRefreshTokens start.", {
		endpoint: url.toString(),
		handle,
		hasRenderId: !!renderId
	});
	const response = await requestJson(url.toString());
	debug$6.log("requestRefreshTokens complete.", { hasRefreshTokens: !!response.refreshTokens });
	return response.refreshTokens || response;
}
async function requestSetPage(url, form, pageId) {
	const requestUrl = new URL(url, window.location.origin);
	const body = new FormData();
	if (pageId) body.append("pageId", pageId);
	if (form) {
		[
			"handle",
			"renderId",
			"draftContextToken",
			"draftContext",
			"continuationToken"
		].forEach((name) => {
			const value = form.querySelector(`input[name="${name}"]`)?.value?.trim();
			if (value) body.append(name, value);
		});
		const csrfValue = form.querySelector("input[name=\"CRAFT_CSRF_TOKEN\"]")?.value?.trim();
		if (csrfValue) body.append("CRAFT_CSRF_TOKEN", csrfValue);
	}
	debug$6.log("requestSetPage start.", {
		requestUrl: requestUrl.toString(),
		pageId: pageId || null
	});
	const result = await requestJson(requestUrl.toString(), {
		method: "POST",
		body
	});
	debug$6.log("requestSetPage complete.", result);
	return result;
}
function clearSubmissionOnUnload(endpoint, form) {
	const requestUrl = new URL(endpoint, window.location.origin);
	const body = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((name) => {
		const value = form.querySelector(`input[name="${name}"]`)?.value?.trim();
		if (value) body.append(name, value);
	});
	const csrfValue = form.querySelector("input[name=\"CRAFT_CSRF_TOKEN\"]")?.value?.trim();
	if (csrfValue) body.append("CRAFT_CSRF_TOKEN", csrfValue);
	debug$6.log("clearSubmissionOnUnload start.", { requestUrl: requestUrl.toString() });
	try {
		if (typeof navigator.sendBeacon === "function" && navigator.sendBeacon(requestUrl.toString(), body)) return;
	} catch (_error) {}
	fetch(requestUrl.toString(), {
		method: "POST",
		body,
		credentials: "include",
		keepalive: true,
		headers: { Accept: "application/json" }
	});
}
async function submitForm(form, formData) {
	const method = (form.getAttribute("method") || "POST").toUpperCase();
	const action = form.getAttribute("action") || window.location.href;
	const fallbackFormError = form.dataset.formieErrorMessage?.trim() || "Submission failed.";
	debug$6.log("submitForm start.", {
		method,
		action,
		submitAction: formData.get("submitAction")
	});
	const response = await fetch(action, {
		method,
		body: formData,
		credentials: "include",
		headers: { Accept: "application/json" }
	});
	const contentType = response.headers.get("content-type") || "";
	if (!contentType.includes("application/json")) {
		if (!response.ok) {
			debug$6.warn("submitForm non-JSON HTTP error.", {
				status: response.status,
				contentType
			});
			return {
				ok: false,
				code: "HTTP_ERROR",
				message: `Request failed (${response.status}).`,
				formErrors: [`Request failed (${response.status}).`]
			};
		}
		debug$6.log("submitForm non-JSON success response.", {
			status: response.status,
			contentType
		});
		return {
			ok: true,
			message: "Submission completed."
		};
	}
	const normalized = normalizePayload(await response.json(), fallbackFormError);
	debug$6.log("submitForm JSON response normalized.", {
		ok: normalized.ok,
		code: normalized.code,
		hasRedirect: !!normalized.redirect?.url,
		hasSubmitData: Array.isArray(normalized.submitData) && normalized.submitData.length > 0
	});
	return normalized;
}
//#endregion
//#region src/js/submit/pipeline.ts
var STAGES = [
	"prepare",
	"normalize",
	"validate",
	"screen",
	"authorize",
	"dispatch",
	"finalize"
];
var PREFLIGHT_STAGES = [
	"prepare",
	"normalize",
	"validate",
	"screen",
	"authorize"
];
var debug$5 = createDebug("general", "pipeline");
function getAbortedResult(stage, reason) {
	return {
		ok: false,
		stage,
		code: "ABORTED",
		message: reason || "Submission aborted.",
		formErrors: [reason || "Submission aborted."]
	};
}
function getPages(form) {
	return Array.from(form.querySelectorAll("[data-formie-page]"));
}
function getValidationScope(form) {
	const pages = getPages(form);
	if (!pages.length) return {
		scope: form,
		final: true
	};
	const currentPage = pages.find((page) => {
		return !page.hasAttribute("data-formie-page-hidden");
	}) || pages[pages.length - 1];
	return {
		scope: currentPage,
		final: currentPage === pages[pages.length - 1]
	};
}
function isSubmittableControl(element) {
	return element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
}
function shouldIncludeControl(control) {
	if (!control.name || control.disabled) return false;
	if (control instanceof HTMLInputElement) {
		if (control.type === "submit" || control.type === "button" || control.type === "reset" || control.type === "image") return false;
		if ((control.type === "checkbox" || control.type === "radio") && !control.checked) return false;
		if (control.type === "file" && (!control.files || control.files.length === 0)) return false;
	}
	return true;
}
function appendControlValue(formData, control) {
	if (control instanceof HTMLInputElement) {
		if (control.type === "file") {
			Array.from(control.files || []).forEach((file) => {
				formData.append(control.name, file);
			});
			return;
		}
		formData.append(control.name, control.value);
		return;
	}
	if (control instanceof HTMLSelectElement && control.multiple) {
		Array.from(control.selectedOptions).forEach((option) => {
			formData.append(control.name, option.value);
		});
		return;
	}
	formData.append(control.name, control.value);
}
function appendControlsFromRoot(formData, form) {
	form.querySelectorAll("input, select, textarea").forEach((node) => {
		const control = isSubmittableControl(node) ? node : null;
		if (!control || control.closest("[data-formie-page]")) return;
		if (!shouldIncludeControl(control)) return;
		appendControlValue(formData, control);
	});
}
function appendControlsFromPage(formData, page) {
	const fieldNames = /* @__PURE__ */ new Set();
	page.querySelectorAll("input, select, textarea").forEach((node) => {
		const control = isSubmittableControl(node) ? node : null;
		if (!control || !control.name || control.disabled) return;
		if (control instanceof HTMLInputElement && (control.type === "submit" || control.type === "button" || control.type === "reset" || control.type === "image")) return;
		if (control.name.startsWith("fields[")) fieldNames.add(control.name);
		if (!shouldIncludeControl(control)) return;
		appendControlValue(formData, control);
	});
	return fieldNames;
}
function appendMissingFieldClears(formData, fieldNames) {
	fieldNames.forEach((name) => {
		if (!formData.has(name)) formData.append(name, "");
	});
}
function buildSubmitFormData(form, action) {
	const pages = getPages(form);
	const currentPage = pages.find((page) => {
		return !page.hasAttribute("data-formie-page-hidden");
	}) || null;
	if (!pages.length || !currentPage) {
		const formData = new FormData(form);
		formData.set("submitAction", action);
		return formData;
	}
	const formData = new FormData();
	appendControlsFromRoot(formData, form);
	appendMissingFieldClears(formData, appendControlsFromPage(formData, currentPage));
	formData.set("submitAction", action);
	return formData;
}
function isFinalSubmitAttempt(form, action) {
	if (action !== "submit") return false;
	const pages = getPages(form);
	if (!pages.length) return true;
	return (pages.find((page) => {
		return !page.hasAttribute("data-formie-page-hidden");
	}) || pages[pages.length - 1]) === pages[pages.length - 1];
}
async function runSubmitPipeline(form, action, bus, options = {}) {
	debug$5.log("Starting submit pipeline.", {
		action,
		preflightOnly: options.preflightOnly === true
	});
	let aborted = false;
	let abortReason;
	let dispatchResult = null;
	const finalSubmitAttempt = isFinalSubmitAttempt(form, action);
	const context = {
		form,
		action,
		formData: buildSubmitFormData(form, action),
		abort: (reason) => {
			aborted = true;
			abortReason = reason;
			debug$5.warn("Pipeline aborted.", { reason });
		},
		isAborted: () => aborted,
		abortReason: () => abortReason
	};
	const runners = {
		prepare: async (ctx) => {
			const submitAction = ctx.form.querySelector("input[name=\"submitAction\"]");
			if (submitAction) submitAction.value = ctx.action;
			ctx.formData.set("submitAction", ctx.action);
			return null;
		},
		normalize: async () => {
			return null;
		},
		validate: async (ctx) => {
			if (ctx.action !== "submit") return null;
			if (options.validateOnSubmit === false) return null;
			if (options.validator) {
				const { scope, final } = getValidationScope(ctx.form);
				const errors = options.validator.submit(final ? ctx.form : scope, { final });
				if (errors.length > 0) {
					errors[0]?.input.focus();
					return {
						ok: false,
						stage: "validate",
						code: "VALIDATION_FAILED",
						message: options.validator.config.errorMessage || "Validation failed.",
						fieldErrors: options.validator.getFieldErrors(errors),
						formErrors: [options.validator.config.errorMessage || "Validation failed."]
					};
				}
				return null;
			}
			if (!ctx.form.checkValidity()) {
				ctx.form.querySelector(":invalid")?.focus();
				return {
					ok: false,
					stage: "validate",
					code: "VALIDATION_FAILED",
					message: "Validation failed.",
					formErrors: ["Validation failed."]
				};
			}
			return null;
		},
		screen: async () => {
			return null;
		},
		authorize: async () => {
			return null;
		},
		dispatch: async (ctx) => {
			ctx.formData = buildSubmitFormData(ctx.form, ctx.action);
			const result = await submitForm(ctx.form, ctx.formData);
			dispatchResult = result;
			return result;
		},
		finalize: async (resultCtx) => {
			if (!dispatchResult) return null;
			if (dispatchResult.ok && dispatchResult.redirect?.url) if (dispatchResult.redirect.target === "new-tab") window.open(dispatchResult.redirect.url, "_blank");
			else window.location.href = dispatchResult.redirect.url;
			return null;
		}
	};
	{
		const emitReport = await bus.emitSafe("formie:submit:before", context);
		if (emitReport.failed.length > 0) debug$5.warn("Submit before listeners failed.", {
			eventName: emitReport.eventName,
			failed: emitReport.failed.length
		});
	}
	if (finalSubmitAttempt) {
		const emitReport = await bus.emitSafe("formie:submit:final:before", context);
		if (emitReport.failed.length > 0) debug$5.warn("Final submit before listeners failed.", {
			eventName: emitReport.eventName,
			failed: emitReport.failed.length
		});
	}
	const stages = options.preflightOnly ? PREFLIGHT_STAGES : STAGES;
	for (const stage of stages) {
		debug$5.log("Stage start.", {
			stage,
			action
		});
		if (aborted) {
			debug$5.warn("Stage skipped due to abort.", {
				stage,
				reason: abortReason
			});
			return getAbortedResult(stage, abortReason);
		}
		{
			const emitReport = await bus.emitSafe(`formie:stage:${stage}:before`, {
				...context,
				stage
			});
			if (emitReport.failed.length > 0) debug$5.warn("Stage before listeners failed.", {
				stage,
				failed: emitReport.failed.length
			});
		}
		if (aborted) {
			const abortedResult = getAbortedResult(stage, abortReason);
			{
				const emitReport = await bus.emitSafe("formie:submit:after", abortedResult);
				if (emitReport.failed.length > 0) debug$5.warn("Submit after listeners failed (abort before stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			if (finalSubmitAttempt) {
				const emitReport = await bus.emitSafe("formie:submit:final:after", abortedResult);
				if (emitReport.failed.length > 0) debug$5.warn("Final submit after listeners failed (abort before stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			debug$5.warn("Aborted after stage before-hooks.", {
				stage,
				reason: abortReason
			});
			return abortedResult;
		}
		const stageResult = await runners[stage](context);
		debug$5.log("Stage runner complete.", {
			stage,
			hasResult: !!stageResult,
			ok: stageResult ? stageResult.ok : void 0,
			code: stageResult?.code
		});
		{
			const emitReport = await bus.emitSafe(`formie:stage:${stage}:after`, {
				...context,
				stage,
				result: stageResult
			});
			if (emitReport.failed.length > 0) debug$5.warn("Stage after listeners failed.", {
				stage,
				failed: emitReport.failed.length
			});
		}
		if (aborted) {
			const abortedResult = getAbortedResult(stage, abortReason);
			{
				const emitReport = await bus.emitSafe("formie:submit:after", abortedResult);
				if (emitReport.failed.length > 0) debug$5.warn("Submit after listeners failed (abort after stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			if (finalSubmitAttempt) {
				const emitReport = await bus.emitSafe("formie:submit:final:after", abortedResult);
				if (emitReport.failed.length > 0) debug$5.warn("Final submit after listeners failed (abort after stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			debug$5.warn("Aborted after stage after-hooks.", {
				stage,
				reason: abortReason
			});
			return abortedResult;
		}
		if (stageResult && !stageResult.ok) {
			{
				const emitReport = await bus.emitSafe("formie:submit:after", stageResult);
				if (emitReport.failed.length > 0) debug$5.warn("Submit after listeners failed (failed stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			if (finalSubmitAttempt) {
				const emitReport = await bus.emitSafe("formie:submit:final:after", stageResult);
				if (emitReport.failed.length > 0) debug$5.warn("Final submit after listeners failed (failed stage).", {
					stage,
					failed: emitReport.failed.length
				});
			}
			debug$5.warn("Pipeline short-circuited by failed stage.", {
				stage,
				code: stageResult.code,
				message: stageResult.message
			});
			return stageResult;
		}
	}
	const successResult = dispatchResult || {
		ok: true,
		stage: options.preflightOnly ? "authorize" : "finalize",
		message: options.preflightOnly ? "Submission preflight completed." : "Submission completed."
	};
	{
		const emitReport = await bus.emitSafe("formie:submit:after", successResult);
		if (emitReport.failed.length > 0) debug$5.warn("Submit after listeners failed (success).", { failed: emitReport.failed.length });
	}
	if (finalSubmitAttempt) {
		const emitReport = await bus.emitSafe("formie:submit:final:after", successResult);
		if (emitReport.failed.length > 0) debug$5.warn("Final submit after listeners failed (success).", { failed: emitReport.failed.length });
	}
	debug$5.log("Pipeline completed.", {
		ok: successResult.ok,
		stage: successResult.stage,
		code: successResult.code
	});
	return successResult;
}
//#endregion
//#region src/js/validation/rules/email.ts
var email = {
	rule: ({ input, getRule }) => {
		if (!getRule("email") || !input.value || input.value.length < 1) return true;
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
	},
	message: ({ input, label, t }) => {
		return input.getAttribute("data-formie-pattern-email-message") ?? input.getAttribute("data-pattern-email-message") ?? t("{attribute} is not a valid email address.", { attribute: label });
	}
};
//#endregion
//#region src/js/validation/rules/shared.ts
function getLabelText(field) {
	return field?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function getComparableInput(ctx) {
	const match = ctx.getRule("match");
	if (!match || match === true || typeof match !== "object" || !ctx.field) return null;
	const fieldHandle = typeof match.fieldHandle === "string" ? match.fieldHandle.trim() : "";
	if (!fieldHandle) return null;
	const sourceField = ctx.form.querySelector(`[data-formie-field-handle="${fieldHandle}"]`);
	if (!sourceField) return null;
	return sourceField.querySelector(ctx.config.fieldsSelector);
}
//#endregion
//#region src/js/validation/rules.ts
var rules_default = {
	required: {
		rule: ({ input, getRule }) => {
			if (!getRule("required") || input.type === "hidden") return true;
			if (input.type === "checkbox" || input.type === "radio") {
				const checkboxInputs = input.form?.querySelectorAll(`[name="${input.name}"]:not([type="hidden"]):not([disabled])`) || [];
				if (checkboxInputs.length) return Array.from(checkboxInputs).some((button) => {
					return button instanceof HTMLInputElement && button.checked;
				});
				return input instanceof HTMLInputElement ? input.checked : true;
			}
			return input.value.trim() !== "";
		},
		message: ({ input, label, t }) => {
			return input.getAttribute("data-formie-required-message") ?? input.getAttribute("data-required-message") ?? t("{attribute} cannot be blank.", { attribute: label });
		}
	},
	email,
	url: {
		rule: ({ input, getRule }) => {
			if (!getRule("url") || !input.value || input.value.length < 1) return true;
			try {
				new URL(input.value);
				return true;
			} catch {
				return false;
			}
		},
		message: ({ input, label, t }) => {
			return input.getAttribute("data-formie-pattern-url-message") ?? input.getAttribute("data-pattern-url-message") ?? t("{attribute} is not a valid URL.", { attribute: label });
		}
	},
	number: {
		rule: ({ input, getRule }) => {
			const rule = getRule("number");
			if (!rule || !input.value || input.value.trim() === "") return true;
			const value = parseFloat(input.value);
			if (Number.isNaN(value)) return false;
			if (rule !== true && typeof rule === "object") {
				const min = typeof rule.min === "number" ? rule.min : null;
				const max = typeof rule.max === "number" ? rule.max : null;
				if (min !== null && value < min) return false;
				if (max !== null && value > max) return false;
			}
			return true;
		},
		message: ({ input, label, getRule, t }) => {
			const rule = getRule("number");
			const min = rule !== true && rule && typeof rule === "object" && typeof rule.min === "number" ? rule.min : null;
			const max = rule !== true && rule && typeof rule === "object" && typeof rule.max === "number" ? rule.max : null;
			if (min !== null && max !== null) return t("{attribute} must be between {min} and {max}.", {
				attribute: label,
				min,
				max
			});
			if (min !== null) return t("{attribute} must be no less than {min}.", {
				attribute: label,
				min
			});
			if (max !== null) return t("{attribute} must be no greater than {max}.", {
				attribute: label,
				max
			});
			return input.getAttribute("data-formie-pattern-number-message") ?? input.getAttribute("data-pattern-number-message") ?? t("{attribute} is not a valid number.", { attribute: label });
		}
	},
	match: {
		rule: (ctx) => {
			const sourceInput = getComparableInput(ctx);
			if (!sourceInput) return true;
			return sourceInput.value === ctx.input.value;
		},
		message: (ctx) => {
			const sourceField = getComparableInput(ctx)?.closest("[data-formie-field-handle]");
			const sourceLabel = getLabelText(sourceField);
			return ctx.t("{name} must match {value}.", {
				name: ctx.label,
				value: sourceLabel
			});
		}
	}
};
//#endregion
//#region src/js/validation/validator.ts
var DEFAULT_PATTERNS = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
};
var debug$4 = createDebug("general", "validator");
function isValidationInput(node) {
	return !!node && (node instanceof HTMLInputElement || node instanceof HTMLSelectElement || node instanceof HTMLTextAreaElement);
}
function removeDescribedBy$1(input, describedById) {
	const current = (input.getAttribute("aria-describedby") || "").trim();
	if (!current) return;
	const filtered = current.split(/\s+/).filter((item) => {
		return item !== describedById;
	});
	if (filtered.length) {
		input.setAttribute("aria-describedby", filtered.join(" "));
		return;
	}
	input.removeAttribute("aria-describedby");
}
function appendDescribedBy$1(input, describedById) {
	const current = (input.getAttribute("aria-describedby") || "").trim();
	const items = current ? current.split(/\s+/) : [];
	if (!items.includes(describedById)) items.push(describedById);
	input.setAttribute("aria-describedby", items.join(" ").trim());
}
function setErrorMessageReference$1(input, errorMessageId) {
	input.setAttribute("aria-errormessage", errorMessageId);
}
function clearErrorMessageReference$1(input, errorMessageId) {
	if (input.getAttribute("aria-errormessage") === errorMessageId) input.removeAttribute("aria-errormessage");
}
var FormieValidator = class {
	constructor(form, config = {}) {
		this.errors = [];
		this.validators = {};
		this.boundListeners = false;
		this.activated = /* @__PURE__ */ new WeakSet();
		this.submitted = false;
		this.initialValues = /* @__PURE__ */ new WeakMap();
		this.form = form;
		this.onBlur = this.blurHandler.bind(this);
		this.onChange = this.changeHandler.bind(this);
		this.onInput = this.inputHandler.bind(this);
		this.config = {
			live: false,
			errorMessage: "",
			fieldContainerErrorClass: [],
			inputErrorClass: [],
			messagesClass: [],
			messageClass: [],
			fieldsSelector: "input:not([type=\"hidden\"]):not([type=\"submit\"]):not([type=\"button\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])",
			patterns: DEFAULT_PATTERNS,
			...config
		};
		Object.entries(rules_default).forEach(([validatorName, validator]) => {
			this.addValidator(validatorName, validator.rule, validator.message);
		});
		this.init();
	}
	init() {
		debug$4.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		});
		this.form.setAttribute("novalidate", "true");
		this.inputs().forEach((input) => {
			this.initialValues.set(input, this.getInputValue(input));
		});
		if (this.config.live) this.addEventListeners();
		this.emitEvent(document, getValidatorEventName("ready"), { validator: this });
	}
	inputs(inputOrSelector = null) {
		if (isValidationInput(inputOrSelector)) return [inputOrSelector];
		const root = inputOrSelector || this.form;
		return Array.from(root.querySelectorAll(this.config.fieldsSelector)).filter((input) => {
			return isValidationInput(input);
		});
	}
	getInputValue(input) {
		if (input instanceof HTMLInputElement && (input.type === "checkbox" || input.type === "radio")) return input.checked;
		if (input instanceof HTMLInputElement && input.type === "file") return input.files?.length ? Array.from(input.files).map((file) => {
			return file.name;
		}).join("|") : "";
		return input.value ?? "";
	}
	isDirty(input) {
		if (!this.initialValues.has(input)) {
			this.initialValues.set(input, this.getInputValue(input));
			return false;
		}
		return this.getInputValue(input) !== this.initialValues.get(input);
	}
	shouldShowError(input) {
		return this.submitted || this.activated.has(input);
	}
	validate(inputOrSelector = null, options = {}) {
		this.errors = [];
		const seenGroups = /* @__PURE__ */ new Set();
		this.inputs(inputOrSelector).forEach((input) => {
			let errorShown = false;
			if (!this.isVisible(input, options)) return;
			const field = input.closest("[data-formie-field-handle]");
			const groupKey = input instanceof HTMLInputElement && (input.type === "checkbox" || input.type === "radio") ? `${field?.getAttribute("data-formie-field-handle") || ""}:${input.name}` : null;
			if (groupKey) {
				if (seenGroups.has(groupKey)) return;
				seenGroups.add(groupKey);
			}
			if (this.shouldShowError(input)) this.removeError(input);
			const opts = this.getValidatorCallbackOptions(input);
			Object.entries(this.validators).forEach(([validatorName, validatorConfig]) => {
				if (!validatorConfig.validate(opts)) {
					const errorMessage = this.getErrorMessage(input, validatorName, validatorConfig, opts);
					if (this.shouldShowError(input) && !errorShown) this.showError(input, validatorName, errorMessage);
					this.errors.push({
						input,
						field: opts.field,
						validator: validatorName,
						message: errorMessage,
						handle: opts.field?.getAttribute("data-formie-field-handle") || null,
						result: false
					});
					errorShown = true;
				}
			});
			if (!errorShown && this.shouldShowError(input)) this.removeError(input);
		});
		debug$4.log("Validation pass complete.", {
			errorCount: this.errors.length,
			includeHiddenPages: options.includeHiddenPages === true
		});
		return this.errors;
	}
	removeAllErrors() {
		this.inputs().forEach((input) => {
			this.removeError(input);
		});
	}
	removeError(input) {
		const fieldContainer = input.closest("[data-formie-field-handle]");
		if (!fieldContainer) {
			input.removeAttribute("aria-invalid");
			return;
		}
		const errorMessages = fieldContainer.querySelector("[data-formie-field-errors]");
		const errorContainerId = errorMessages?.id || "";
		fieldContainer.querySelectorAll("[data-formie-field-error]").forEach((node) => {
			node.remove();
		});
		if (errorMessages) errorMessages.innerHTML = "";
		fieldContainer.querySelectorAll("input, select, textarea").forEach((fieldInput) => {
			const element = fieldInput;
			element.removeAttribute("aria-invalid");
			if (this.config.inputErrorClass.length) element.classList.remove(...this.config.inputErrorClass);
			element.removeAttribute("data-formie-input-has-error");
			if (errorContainerId) removeDescribedBy$1(element, errorContainerId);
			fieldContainer.querySelectorAll("[data-formie-field-error]").forEach((errorNode) => {
				const errorMessageId = errorNode.id;
				if (errorMessageId) clearErrorMessageReference$1(element, errorMessageId);
			});
		});
		for (let element = fieldContainer; element; element = element.parentElement?.closest("[data-formie-field-handle]")) {
			if (this.config.fieldContainerErrorClass.length) element.classList.remove(...this.config.fieldContainerErrorClass);
			element.removeAttribute("data-formie-field-has-error");
		}
		this.emitEvent(input, getValidatorEventName("clear-error"), { validator: this });
		syncPageTabErrors(this.form);
	}
	showError(input, validatorName, errorMessage) {
		const fieldContainer = input.closest("[data-formie-field-handle]");
		if (!fieldContainer) return;
		let errorMessages = fieldContainer.querySelector("[data-formie-field-errors]");
		if (!errorMessages) {
			errorMessages = document.createElement("div");
			errorMessages.setAttribute("data-formie-field-errors", "true");
			if (this.config.messagesClass.length) errorMessages.classList.add(...this.config.messagesClass);
			fieldContainer.appendChild(errorMessages);
		}
		if (this.config.messagesClass.length) errorMessages.classList.add(...this.config.messagesClass);
		errorMessages.innerHTML = "";
		const handle = fieldContainer.getAttribute("data-formie-field-handle") || "field";
		const errorId = `${handle}-error`;
		errorMessages.id = errorMessages.id || `${handle}-errors`;
		errorMessages.setAttribute("aria-live", "polite");
		errorMessages.setAttribute("aria-atomic", "true");
		const errorElement = document.createElement("div");
		errorElement.setAttribute("data-formie-field-error", "true");
		errorElement.setAttribute(`data-formie-field-error-${validatorName}`, "true");
		errorElement.setAttribute("id", errorId);
		errorElement.setAttribute("role", "alert");
		if (this.config.messageClass.length) errorElement.classList.add(...this.config.messageClass);
		errorElement.textContent = errorMessage;
		errorMessages.appendChild(errorElement);
		fieldContainer.setAttribute("data-formie-field-has-error", "true");
		fieldContainer.querySelectorAll("input, select, textarea").forEach((fieldInput) => {
			const element = fieldInput;
			element.setAttribute("aria-invalid", "true");
			if (this.config.inputErrorClass.length) element.classList.add(...this.config.inputErrorClass);
			element.setAttribute("data-formie-input-has-error", "true");
			appendDescribedBy$1(element, errorMessages.id);
			setErrorMessageReference$1(element, errorId);
		});
		for (let element = fieldContainer; element; element = element.parentElement?.closest("[data-formie-field-handle]")) {
			if (this.config.fieldContainerErrorClass.length) element.classList.add(...this.config.fieldContainerErrorClass);
			element.setAttribute("data-formie-field-has-error", "true");
		}
		this.emitEvent(input, getValidatorEventName("show-error"), {
			validator: this,
			validatorName,
			errorMessage
		});
		syncPageTabErrors(this.form);
	}
	getValidatorCallbackOptions(input) {
		const fieldContainer = input.closest("[data-formie-field-handle]");
		const label = fieldContainer?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "";
		const rules = this.parseValidationRules(fieldContainer?.getAttribute("data-formie-validation"));
		return {
			t,
			input,
			label,
			field: fieldContainer,
			form: this.form,
			config: this.config,
			rules,
			getRule: (rule) => {
				return this.getRule(fieldContainer, rule);
			}
		};
	}
	getErrorMessage(input, validatorName, validator, opts) {
		return (typeof validator.errorMessage === "function" ? validator.errorMessage(opts) : validator.errorMessage) ?? t("{attribute} is invalid.", { attribute: opts.label });
	}
	getErrors() {
		return this.errors;
	}
	getFieldErrors(errors = this.errors) {
		const fieldErrors = {};
		errors.forEach((error) => {
			if (!error.handle || fieldErrors[error.handle]?.length) return;
			fieldErrors[error.handle] = [error.message];
		});
		return fieldErrors;
	}
	getRule(field, rule) {
		if (!field) return false;
		const rules = this.parseValidationRules(field.getAttribute("data-formie-validation"));
		if (Object.prototype.hasOwnProperty.call(rules, rule)) return rules[rule];
		return false;
	}
	parseValidationRules(ruleString) {
		const rules = {};
		if (!ruleString) return rules;
		let parsedRules = null;
		try {
			parsedRules = JSON.parse(ruleString);
		} catch {
			debug$4.warn("Invalid validation rules payload.", { formId: this.form.id || null });
			return rules;
		}
		if (!Array.isArray(parsedRules)) return rules;
		parsedRules.forEach((part) => {
			if (!part || typeof part !== "object" || Array.isArray(part)) return;
			const candidate = part;
			const type = typeof candidate.type === "string" ? candidate.type.trim() : "";
			if (!type) return;
			rules[type] = candidate;
		});
		return rules;
	}
	destroy() {
		debug$4.log("Destroying validator.", { formId: this.form.id || null });
		this.removeEventListeners();
		this.form.removeAttribute("novalidate");
		this.emitEvent(document, getValidatorEventName("destroy"), { validator: this });
	}
	isVisible(element, options = {}) {
		if (element.closest("[data-formie-conditionally-hidden]")) return false;
		if (element.closest("[data-formie-page-hidden]")) return !!options.includeHiddenPages;
		return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
	}
	blurHandler(event) {
		if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) return;
		if (event instanceof CustomEvent) return;
		if (event.target instanceof HTMLInputElement && event.target.type === "file") return;
		if (event.target instanceof HTMLInputElement && (event.target.type === "checkbox" || event.target.type === "radio")) return;
		if (this.isDirty(event.target)) this.activated.add(event.target);
		if (this.shouldShowError(event.target)) this.validate(event.target);
	}
	changeHandler(event) {
		if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) return;
		if (event instanceof CustomEvent) return;
		if (event.target instanceof HTMLSelectElement) {
			this.activated.add(event.target);
			this.validate(event.target);
			return;
		}
		if (!(event.target instanceof HTMLInputElement)) return;
		if (event.target.type !== "file" && event.target.type !== "checkbox" && event.target.type !== "radio") return;
		this.activated.add(event.target);
		this.validate(event.target);
	}
	inputHandler(event) {
		if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) return;
		if (event instanceof CustomEvent) return;
		if (event.target instanceof HTMLInputElement && (event.target.type === "checkbox" || event.target.type === "radio")) return;
		if (this.shouldShowError(event.target)) this.validate(event.target);
	}
	submit(inputOrSelector = null, { final = false } = {}) {
		this.submitted = true;
		debug$4.log("Submit validation requested.", { final });
		if (!this.boundListeners) this.addEventListeners();
		this.removeAllErrors();
		return this.validate(inputOrSelector, { includeHiddenPages: final });
	}
	resetLiveState() {
		this.submitted = false;
		this.activated = /* @__PURE__ */ new WeakSet();
		this.errors = [];
		this.removeAllErrors();
	}
	addEventListeners() {
		if (this.boundListeners) return;
		this.form.addEventListener("blur", this.onBlur, true);
		this.form.addEventListener("change", this.onChange, false);
		this.form.addEventListener("input", this.onInput, false);
		this.boundListeners = true;
		debug$4.log("Event listeners attached.");
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, true);
		this.form.removeEventListener("change", this.onChange, false);
		this.form.removeEventListener("input", this.onInput, false);
		this.boundListeners = false;
		debug$4.log("Event listeners removed.");
	}
	emitEvent(target, type, detail = {}) {
		target.dispatchEvent(new CustomEvent(type, {
			bubbles: true,
			detail
		}));
	}
	addValidator(name, validatorFunction, errorMessage) {
		this.validators[name] = {
			validate: validatorFunction,
			errorMessage
		};
	}
	removeValidator(name) {
		delete this.validators[name];
	}
};
//#endregion
//#region src/js/core/submit-result-ui.ts
var successHideTimers = /* @__PURE__ */ new WeakMap();
function getConfiguredSubmitAction(form) {
	return (form.dataset.formieSubmitAction || "").trim();
}
function getErrorMessagePosition(form) {
	return (form.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function getSuccessMessagePosition(form) {
	return (form.dataset.formieSubmitActionMessagePosition || "").trim();
}
function getSuccessMessageTimeoutMs(form) {
	const rawValue = (form.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!rawValue) return null;
	const seconds = Number.parseFloat(rawValue);
	if (!Number.isFinite(seconds) || seconds < 0) return null;
	return Math.round(seconds * 1e3);
}
function shouldHideFormOnSuccess(form) {
	const rawValue = form.dataset.formieSubmitActionFormHide;
	if (rawValue === void 0) return false;
	const normalized = rawValue.trim().toLowerCase();
	return normalized === "true" || normalized === "1" || normalized === "";
}
function clearPendingSuccessHide(form) {
	const timerId = successHideTimers.get(form);
	if (typeof timerId === "number") {
		window.clearTimeout(timerId);
		successHideTimers.delete(form);
	}
}
function getTopMessageHost(form) {
	return form.querySelector("[data-formie-form-messages-top]") || form;
}
function getBottomMessageHost(form) {
	return form.querySelector("[data-formie-form-messages-bottom]") || form;
}
function getErrorMessageHost(form, position) {
	if (position === "bottom-form") return getBottomMessageHost(form);
	return getTopMessageHost(form);
}
function getSuccessMessageHost(form, position) {
	if (position === "top-form") return getTopMessageHost(form);
	if (position === "bottom-form" && !shouldHideFormOnSuccess(form)) return getBottomMessageHost(form);
	return form;
}
function ensureFormErrorContainer(form) {
	const position = getErrorMessagePosition(form);
	const host = getErrorMessageHost(form, position);
	let container = host.querySelector("[data-formie-error-container], [data-formie-errors]");
	if (!container) {
		container = document.createElement("div");
		container.setAttribute("data-formie-errors", "true");
		addThemeClasses(container, form, "errors");
	}
	container.setAttribute("data-formie-error-container", "true");
	if (position === "bottom-form") host.append(container);
	else host.prepend(container);
	return container;
}
function ensureFormErrorMessageContainer(form, container) {
	let messageContainer = container.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	if (!messageContainer) {
		messageContainer = document.createElement("div");
		messageContainer.setAttribute("data-formie-error-message-container", "true");
		container.appendChild(messageContainer);
	}
	messageContainer.setAttribute("data-formie-message", "true");
	messageContainer.setAttribute("data-formie-message-error", "true");
	addThemeClasses(messageContainer, form, "message", "messageError");
	messageContainer.setAttribute("role", "alert");
	messageContainer.setAttribute("aria-live", "polite");
	messageContainer.setAttribute("aria-atomic", "true");
	return messageContainer;
}
function ensureFormSuccessContainer(form, position) {
	let container = form.querySelector("[data-formie-success-container]");
	const host = getSuccessMessageHost(form, position);
	if (!container) {
		container = document.createElement("div");
		container.setAttribute("data-formie-success-container", "true");
		addThemeClasses(container, form, "successes");
	}
	if (position === "bottom-form") host.append(container);
	else if (host === form) host.prepend(container);
	else host.prepend(container);
	return container;
}
function ensureFieldErrorContainer(fieldNode) {
	let container = fieldNode.querySelector("[data-formie-field-errors]");
	if (!container) {
		container = document.createElement("div");
		container.setAttribute("data-formie-field-errors", "true");
		addThemeClasses(container, fieldNode, "fieldErrors");
		fieldNode.appendChild(container);
	}
	return container;
}
function removeDescribedBy(input, describedById) {
	const current = (input.getAttribute("aria-describedby") || "").trim();
	if (!current) return;
	const nextValue = current.split(/\s+/).filter((item) => {
		return item !== describedById;
	}).join(" ").trim();
	if (nextValue) {
		input.setAttribute("aria-describedby", nextValue);
		return;
	}
	input.removeAttribute("aria-describedby");
}
function setErrorMessageReference(input, errorMessageId) {
	input.setAttribute("aria-errormessage", errorMessageId);
}
function clearErrorMessageReference(input, errorMessageId) {
	if (input.getAttribute("aria-errormessage") === errorMessageId) input.removeAttribute("aria-errormessage");
}
function clearFieldErrors(form) {
	form.querySelectorAll("[data-formie-field-handle]").forEach((fieldNode) => {
		const fieldElement = fieldNode;
		const container = fieldElement.querySelector("[data-formie-field-errors]");
		const containerId = container?.id || "";
		const errorMessageIds = Array.from(fieldElement.querySelectorAll("[data-formie-field-error]")).map((node) => {
			return node.id;
		}).filter(Boolean);
		removeThemeClasses(fieldElement, form, "fieldLayoutError");
		fieldElement.removeAttribute("data-formie-field-has-error");
		fieldElement.querySelectorAll("[data-formie-field-error]").forEach((node) => {
			node.remove();
		});
		if (container && !container.querySelector("[data-formie-field-error]")) container.innerHTML = "";
		fieldElement.querySelectorAll("input, select, textarea").forEach((input) => {
			const element = input;
			element.removeAttribute("aria-invalid");
			removeThemeClasses(element, form, "fieldControlError");
			element.removeAttribute("data-formie-input-has-error");
			if (containerId) removeDescribedBy(element, containerId);
			errorMessageIds.forEach((errorMessageId) => {
				clearErrorMessageReference(element, errorMessageId);
			});
		});
	});
	syncPageTabErrors(form);
}
function clearFormErrors(form) {
	form.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((node) => {
		const container = node;
		container.querySelectorAll("[data-formie-error]").forEach((errorNode) => {
			errorNode.remove();
		});
		removeThemeClasses(container, form, "message", "messageError");
		container.removeAttribute("data-formie-message");
		container.removeAttribute("data-formie-message-error");
		container.removeAttribute("role");
		container.removeAttribute("aria-live");
		container.removeAttribute("aria-atomic");
		if (!container.querySelector("[data-formie-error]")) container.innerHTML = "";
	});
}
function clearFormSuccess(form) {
	clearPendingSuccessHide(form);
	form.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((node) => {
		node.remove();
	});
	form.querySelectorAll("[data-formie-success-container]").forEach((node) => {
		const container = node;
		container.querySelectorAll("[data-formie-success]").forEach((successNode) => {
			successNode.remove();
		});
		removeThemeClasses(container, form, "message", "messageSuccess");
		container.removeAttribute("data-formie-message");
		container.removeAttribute("data-formie-message-success");
		container.removeAttribute("role");
		container.removeAttribute("aria-live");
		container.removeAttribute("aria-atomic");
		if (!container.querySelector("[data-formie-success]")) container.innerHTML = "";
	});
	if (!(getConfiguredSubmitAction(form) === "message" && shouldHideFormOnSuccess(form))) setFormHiddenState(form, false);
}
function clearAriaInvalid(form) {
	form.querySelectorAll("[aria-invalid=\"true\"]").forEach((node) => {
		node.removeAttribute("aria-invalid");
	});
}
function appendDescribedBy(input, describedById) {
	const current = (input.getAttribute("aria-describedby") || "").trim();
	const items = current ? current.split(/\s+/) : [];
	if (!items.includes(describedById)) items.push(describedById);
	input.setAttribute("aria-describedby", items.join(" ").trim());
}
function renderFieldErrors(form, fieldErrors) {
	Object.entries(fieldErrors).forEach(([handle, messages]) => {
		const fieldNode = form.querySelector(`[data-formie-field-handle="${handle}"]`);
		if (!fieldNode) return;
		const container = ensureFieldErrorContainer(fieldNode);
		const containerId = container.id && container.id.trim() ? container.id : `${handle}-errors`;
		container.id = containerId;
		container.setAttribute("aria-live", "polite");
		container.setAttribute("aria-atomic", "true");
		addThemeClasses(fieldNode, form, "fieldLayoutError");
		fieldNode.setAttribute("data-formie-field-has-error", "true");
		messages.forEach((message, index) => {
			const errorNode = document.createElement("div");
			errorNode.setAttribute("data-formie-field-error", "true");
			errorNode.setAttribute("role", "alert");
			errorNode.id = `${containerId}-${index + 1}`;
			addThemeClasses(errorNode, form, "fieldError");
			errorNode.textContent = message;
			container.appendChild(errorNode);
		});
		const primaryErrorId = container.querySelector("[data-formie-field-error]")?.id;
		fieldNode.querySelectorAll("input, select, textarea").forEach((input) => {
			const element = input;
			element.setAttribute("aria-invalid", "true");
			addThemeClasses(element, form, "fieldControlError");
			element.setAttribute("data-formie-input-has-error", "true");
			appendDescribedBy(element, containerId);
			if (primaryErrorId) setErrorMessageReference(element, primaryErrorId);
			const instructions = fieldNode.querySelector("[data-formie-instructions]");
			if (instructions?.id) appendDescribedBy(element, instructions.id);
		});
	});
	syncPageTabErrors(form);
}
function renderFormErrors(form, formErrors) {
	const container = ensureFormErrorContainer(form);
	const messageContainer = ensureFormErrorMessageContainer(form, container);
	addThemeClasses(container, form, "errors");
	formErrors.forEach((error) => {
		const errorNode = document.createElement("div");
		errorNode.setAttribute("data-formie-error", "true");
		errorNode.setAttribute("role", "alert");
		addThemeClasses(errorNode, form, "error");
		errorNode.innerHTML = error;
		messageContainer.appendChild(errorNode);
	});
}
function shouldRenderSuccessMessage(form, result) {
	if (!result.message || result.nextPage || result.redirect) return false;
	if (result.action === "save") return true;
	return getConfiguredSubmitAction(form) === "message" && getSuccessMessagePosition(form) !== "";
}
function renderFormSuccess(form, message) {
	const position = getSuccessMessagePosition(form);
	if (!position) return;
	const container = ensureFormSuccessContainer(form, position);
	addThemeClasses(container, form, "message", "messageSuccess");
	container.setAttribute("data-formie-message", "true");
	container.setAttribute("data-formie-message-success", "true");
	container.setAttribute("role", "status");
	container.setAttribute("aria-live", "polite");
	container.setAttribute("aria-atomic", "true");
	const successNode = document.createElement("div");
	successNode.setAttribute("data-formie-success", "true");
	addThemeClasses(successNode, form, "success");
	successNode.innerHTML = message;
	container.appendChild(successNode);
	if (shouldHideFormOnSuccess(form)) setFormHiddenState(form, true);
	const timeoutMs = getSuccessMessageTimeoutMs(form);
	if (timeoutMs !== null) {
		const timerId = window.setTimeout(() => {
			successHideTimers.delete(form);
			clearFormSuccess(form);
		}, timeoutMs);
		successHideTimers.set(form, timerId);
	}
}
function applySubmitResultUi(form, result) {
	clearFieldErrors(form);
	clearFormErrors(form);
	clearFormSuccess(form);
	clearAriaInvalid(form);
	if (result.ok) {
		if (shouldRenderSuccessMessage(form, result)) renderFormSuccess(form, result.message || "");
		return;
	}
	if (result.fieldErrors) renderFieldErrors(form, result.fieldErrors);
	if (result.formErrors?.length) {
		renderFormErrors(form, result.formErrors);
		return;
	}
	if (!result.fieldErrors && result.message) renderFormErrors(form, [result.message]);
}
//#endregion
//#region src/js/core/submit-flow.ts
var debug$3 = createDebug("general", "submit-flow");
function shouldRefreshTokensAfterSubmit(result) {
	if (!result.ok && result.stage === "validate") return false;
	return true;
}
function shouldKeepSubmitLoading(result) {
	if (!result) return false;
	if (result.keepSubmitLoading === true) return true;
	if (result.ok && result.redirect?.url && result.redirect.target !== "new-tab") return true;
	return false;
}
function clearSubmitFeedback(form) {
	clearFieldErrors(form);
	clearFormErrors(form);
	clearFormSuccess(form);
	clearAriaInvalid(form);
}
async function executeAjaxSubmitFlow(params) {
	const { id, target, form, bus, validator, validateOnSubmit, action, submitter, waitForSubmitDelay, onRefreshTokensAfterSubmit, dispatchSubmitResult } = params;
	clearSubmitFeedback(form);
	setSubmitLoading(form, submitter || null);
	let result = {
		ok: false,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await waitForSubmitDelay(form);
		result = await runSubmitPipeline(form, action, bus, {
			validator,
			validateOnSubmit
		});
		applySubmitResultUi(form, result);
		applySubmitResultState(form, result, action);
		if (shouldRefreshTokensAfterSubmit(result)) await onRefreshTokensAfterSubmit(result);
		dispatchSubmitResult(result);
	} catch (error) {
		result = {
			ok: false,
			code: "SUBMIT_ERROR",
			message: error instanceof Error ? error.message : "Submission failed.",
			formErrors: [error instanceof Error ? error.message : "Submission failed."]
		};
		applySubmitResultUi(form, result);
		dispatchSubmitResult(result);
		debug$3.warn("Submit failed with exception.", {
			id,
			action,
			target,
			error: error instanceof Error ? error.message : error
		});
	} finally {
		if (!shouldKeepSubmitLoading(result)) clearSubmitLoading(form);
	}
	return result;
}
//#endregion
//#region src/js/modules/registry.ts
var ModuleRegistry = class {
	constructor() {
		this.modules = /* @__PURE__ */ new Map();
	}
	register(moduleDefinition, options = {}) {
		const existing = this.modules.get(moduleDefinition.id);
		if (existing === moduleDefinition) return true;
		if (existing && !options.replace) {
			console.warn(`[formie] Module "${moduleDefinition.id}" is already registered. Pass { replace: true } to override the existing definition.`);
			return false;
		}
		this.modules.set(moduleDefinition.id, moduleDefinition);
		return true;
	}
	unregister(moduleId) {
		this.modules.delete(moduleId);
	}
	get(moduleId) {
		return this.modules.get(moduleId) || null;
	}
	getAll() {
		return Array.from(this.modules.values());
	}
};
//#endregion
//#region src/js/modules/address/index.ts
var builtinAddressModuleLoaders = {
	"address-finder": () => import("./chunks/address-finder-DfMCiW89.js").then((m) => m.addressFinderModule),
	"google-address": () => import("./chunks/google-address--uR8WDSm.js").then((m) => m.googleAddressModule),
	"loqate": () => import("./chunks/loqate-BICNJlVK.js").then((m) => m.loqateModule),
	"place-kit": () => import("./chunks/place-kit-ldUl-u9w.js").then((m) => m.placeKitModule)
};
//#endregion
//#region src/js/modules/captchas/index.ts
var builtinCaptchaModuleLoaders = {
	"captcha-eu": () => import("./chunks/captcha-eu-DnOWhMwr.js").then((module) => module.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-CqO4WVre.js").then((module) => module.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-CyykcJcM.js").then((module) => module.friendlyCaptchaV2Module),
	"hcaptcha": () => import("./chunks/hcaptcha-CmaFUesv.js").then((module) => module.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-DPJNyv1X.js").then((module) => module.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-zFjpvJ5c.js").then((module) => module.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-CnYtkNvz.js").then((module) => module.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-EAlWhnkX.js").then((module) => module.recaptchaV3Module),
	"snaptcha": () => import("./chunks/snaptcha-CCDunGeb.js").then((module) => module.snaptchaModule),
	"turnstile": () => import("./chunks/turnstile-DP0bdR7T.js").then((module) => module.turnstileModule)
};
//#endregion
//#region src/js/modules/fields/index.ts
var builtinFieldModuleLoaders = {
	"calculations": () => import("./chunks/calculations-CkYAqO_-.js").then((module) => module.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-0x7Tc0br.js").then((module) => module.checkboxRadioModule),
	"conditions": () => import("./chunks/conditions-4fXKhEJS.js").then((module) => module.conditionsModule),
	"date-picker": () => import("./chunks/date-picker-B6iZkjHS.js").then((module) => module.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-Bh63PQSE.js").then((module) => module.fileUploadModule),
	"hidden": () => import("./chunks/hidden-CYnZYple.js").then((module) => module.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-B6Me4lK0.js").then((module) => module.phoneCountryModule),
	"repeater": () => import("./chunks/repeater-CXD1eLSn.js").then((module) => module.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-DkmZRhGj.js").then((module) => module.richTextModule),
	"signature": () => import("./chunks/signature-E9KyYXS1.js").then((module) => module.signatureModule),
	"summary": () => import("./chunks/summary-EcNE0cvg.js").then((module) => module.summaryModule),
	"table": () => import("./chunks/table-yxEDL6kA.js").then((module) => module.tableModule),
	"text-limit": () => import("./chunks/text-limit-D0H_Ca2c.js").then((module) => module.textLimitModule)
};
//#endregion
//#region src/js/modules/payments/index.ts
var builtinPaymentModuleLoaders = {
	"bpoint": () => import("./chunks/bpoint-Ciy3yY9Q.js").then((module) => module.bpointModule),
	"eway": () => import("./chunks/eway-DEAYcwT0.js").then((module) => module.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-CuND59rR.js").then((module) => module.goCardlessModule),
	"mollie": () => import("./chunks/mollie-DwlsgHZ1.js").then((module) => module.mollieModule),
	"moneris": () => import("./chunks/moneris-B_IFZFTx.js").then((module) => module.monerisModule),
	"opayo": () => import("./chunks/opayo-U2x_TOII.js").then((module) => module.opayoModule),
	"paddle": () => import("./chunks/paddle-BqXFrc79.js").then((module) => module.paddleModule),
	"paypal": () => import("./chunks/paypal-Cn_DYGDb.js").then((module) => module.paypalModule),
	"payway": () => import("./chunks/payway-Rnq796eC.js").then((module) => module.paywayModule),
	"square": () => import("./chunks/square-BLqK51rS.js").then((module) => module.squareModule),
	"stripe": () => import("./chunks/stripe-B8gHpZNC.js").then((module) => module.stripeModule)
};
//#endregion
//#region src/js/modules/loader.ts
var builtinModuleLoaders = {
	...builtinFieldModuleLoaders,
	...builtinAddressModuleLoaders,
	...builtinCaptchaModuleLoaders,
	...builtinPaymentModuleLoaders
};
var builtinModuleLoadCache = /* @__PURE__ */ new Map();
var debug$2 = createDebug("general", "loader");
var importModuleFromSrc = new Function("src", "return import(src);");
async function emitModuleLifecycleEvent(emit, moduleId, phase, detail) {
	await emit(getGlobalModuleLifecycleEventName(phase), detail);
	await emit(getScopedModuleLifecycleEventName(moduleId, phase), detail);
}
function isModuleDefinition(definition) {
	return !!definition && typeof definition === "object" && typeof definition.id === "string" && typeof definition.setup === "function" && typeof definition.match === "function";
}
async function resolveBuiltinDefinition(moduleId, ctx) {
	const loader = builtinModuleLoaders[moduleId];
	if (!loader) return null;
	if (!builtinModuleLoadCache.has(moduleId)) builtinModuleLoadCache.set(moduleId, (async () => {
		try {
			const definition = await loader();
			if (!isModuleDefinition(definition)) return null;
			ctx.registry.register(definition);
			return definition;
		} catch (error) {
			console.error("[formie] Failed to load builtin module:", moduleId, error);
			debug$2.warn("Failed loading builtin module.", {
				moduleId,
				error
			});
			return null;
		}
	})());
	return builtinModuleLoadCache.get(moduleId) || null;
}
async function resolveDefinitionFromSrc(src) {
	try {
		const imported = await importModuleFromSrc(src);
		const definition = imported?.default || imported?.formieModule || null;
		if (!isModuleDefinition(definition)) return null;
		return definition;
	} catch (error) {
		console.error("[formie] Failed to load module from src:", src, error);
		debug$2.warn("Failed loading module from src.", {
			src,
			error
		});
		return null;
	}
}
async function resolveDefinition(manifestItem, ctx) {
	const registered = ctx.registry.get(manifestItem.id);
	if (registered) return registered;
	const builtin = await resolveBuiltinDefinition(manifestItem.id, ctx);
	if (builtin) return builtin;
	if (manifestItem.src) {
		const fromSrc = await resolveDefinitionFromSrc(manifestItem.src);
		if (fromSrc) {
			ctx.registry.register(fromSrc);
			return fromSrc;
		}
	}
	return null;
}
function escapeSelectorValue(value) {
	if (typeof window.CSS?.escape === "function") return window.CSS.escape(value);
	return value.replace(/["\\]/g, "\\$&");
}
function queryTargets(root, selector) {
	if (root.matches(selector)) return [root, ...Array.from(root.querySelectorAll(selector))];
	return Array.from(root.querySelectorAll(selector));
}
function resolveTarget(target, ctx) {
	const root = ctx.setupContext.root;
	const form = ctx.setupContext.form;
	const scope = target.targetType;
	const targetId = target.targetId;
	if (scope === "selector") return queryTargets(root, targetId).map((element) => {
		return {
			scope,
			element
		};
	});
	if (scope === "field") return queryTargets(root, `[data-formie-field-handle="${escapeSelectorValue(targetId)}"]`).map((element) => {
		return {
			scope,
			element
		};
	});
	if (scope === "page") return queryTargets(root, `[data-formie-page-id="${escapeSelectorValue(targetId)}"]`).map((element) => {
		return {
			scope,
			element
		};
	});
	if (scope === "button") return queryTargets(root, `[data-formie-action="${escapeSelectorValue(targetId)}"]`).map((element) => {
		return {
			scope,
			element
		};
	});
	return [{
		scope: "form",
		element: form || root
	}];
}
function resolveTargets(item, ctx) {
	return (item.targets && item.targets.length > 0 ? item.targets : [{
		targetType: "form",
		targetId: "form"
	}]).flatMap((target) => {
		return resolveTarget(target, ctx);
	});
}
async function loadModulesFromManifest(manifest, ctx) {
	const instances = [];
	debug$2.log("Loading module manifest.", { manifestCount: manifest.length });
	for (const item of manifest) {
		const definition = await resolveDefinition(item, ctx);
		if (!definition) {
			debug$2.warn("Skipping manifest item (definition not resolved).", {
				moduleId: item.id,
				src: item.src
			});
			continue;
		}
		const targets = resolveTargets(item, ctx);
		debug$2.log("Resolved module targets.", {
			moduleId: definition.id,
			targets: item.targets || [],
			targetCount: targets.length
		});
		if (targets.length === 0 && definition.kind === "address") console.warn(`[formie] Address module "${item.id}" skipped: no target element found for fieldHandle="${item.targets?.find((target) => target.targetType === "field")?.targetId ?? "?"}". Check that the Address field exists in the rendered form.`);
		for (const target of targets) {
			const matchContext = {
				...ctx.matchContext,
				target: target.element,
				scope: target.scope,
				manifestItem: item
			};
			if (!definition.match(matchContext)) {
				if (definition.kind === "address") console.warn(`[formie] Address module "${definition.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`);
				debug$2.log("Module target did not match predicate.", {
					moduleId: definition.id,
					scope: target.scope
				});
				continue;
			}
			const options = item.config || ctx.setupContext.options;
			const moduleEventName = definition.id;
			const lifecycleDetail = {
				moduleId: definition.id,
				moduleKind: definition.kind,
				target: target.element,
				scope: target.scope,
				options,
				manifestItem: item
			};
			await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, "before-setup", lifecycleDetail);
			let instance = null;
			try {
				const setupResult = await definition.setup({
					...ctx.setupContext,
					target: target.element,
					scope: target.scope,
					options
				});
				if (setupResult) instance = setupResult;
			} catch (err) {
				console.error(`[formie] Module "${definition.id}" setup failed:`, err);
				debug$2.warn("Module setup failed.", {
					moduleId: definition.id,
					scope: target.scope,
					error: err
				});
			}
			await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, "after-setup", {
				...lifecycleDetail,
				instanceCreated: !!instance
			});
			if (instance) {
				debug$2.log("Module instance created.", {
					moduleId: definition.id,
					scope: target.scope
				});
				instances.push({
					...instance,
					destroy: async () => {
						debug$2.log("Destroying module instance.", {
							moduleId: definition.id,
							scope: target.scope
						});
						await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, "before-destroy", lifecycleDetail);
						await instance.destroy();
						await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, "after-destroy", lifecycleDetail);
						debug$2.log("Module instance destroyed.", {
							moduleId: definition.id,
							scope: target.scope
						});
					}
				});
			}
		}
	}
	debug$2.log("Module manifest processing complete.", { instanceCount: instances.length });
	return instances;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var DIRTY_TRACKING_IGNORED_FIELD_NAMES = new Set([
	"CRAFT_CSRF_TOKEN",
	"action",
	"redirect",
	"requestToken",
	"renderId",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"continuationToken"
]);
function serializeStableValue(value, seen) {
	if (value == null) return String(value);
	if (typeof value === "string") return JSON.stringify(value);
	if (typeof value === "number" || typeof value === "boolean") return String(value);
	if (typeof value === "function") return "[function]";
	if (typeof File !== "undefined" && value instanceof File) return `[file:${value.name}:${value.size}:${value.type}]`;
	if (typeof Blob !== "undefined" && value instanceof Blob) return `[blob:${value.size}:${value.type}]`;
	if (Array.isArray(value)) return `[${value.map((item) => serializeStableValue(item, seen)).join(",")}]`;
	if (typeof value === "object") {
		if (seen.has(value)) return "[circular]";
		seen.add(value);
		const entries = Object.entries(value).sort(([left], [right]) => left.localeCompare(right)).map(([key, item]) => {
			return `${JSON.stringify(key)}:${serializeStableValue(item, seen)}`;
		});
		seen.delete(value);
		return `{${entries.join(",")}}`;
	}
	return JSON.stringify(String(value));
}
function stableSerialize(value) {
	return serializeStableValue(value, /* @__PURE__ */ new WeakSet());
}
function shouldTrackFieldName(name) {
	if (!name) return false;
	const normalizedName = name.endsWith("[]") ? name.slice(0, -2) : name;
	return !DIRTY_TRACKING_IGNORED_FIELD_NAMES.has(normalizedName);
}
function buildTrackedSnapshot(form) {
	return stableSerialize(Array.from(new FormData(form).entries()).filter(([name]) => {
		return shouldTrackFieldName(String(name || ""));
	}));
}
function createFormUnloadWarningGuard(form, options = {}) {
	let baselineSnapshot = null;
	let isReady = false;
	let isDirty = false;
	let animationFrameId = null;
	let dirtyTimerId = null;
	let baselineTimerId = null;
	const clearScheduledWork = () => {
		if (animationFrameId !== null) {
			window.cancelAnimationFrame(animationFrameId);
			animationFrameId = null;
		}
		if (dirtyTimerId !== null) {
			window.clearTimeout(dirtyTimerId);
			dirtyTimerId = null;
		}
		if (baselineTimerId !== null) {
			window.clearTimeout(baselineTimerId);
			baselineTimerId = null;
		}
	};
	const refreshDirtyState = () => {
		if (!isReady) return false;
		isDirty = buildTrackedSnapshot(form) !== baselineSnapshot;
		return isDirty;
	};
	const captureBaseline = () => {
		baselineSnapshot = buildTrackedSnapshot(form);
		isReady = true;
		isDirty = false;
	};
	const scheduleBaselineCapture = () => {
		clearScheduledWork();
		isReady = false;
		animationFrameId = window.requestAnimationFrame(() => {
			animationFrameId = null;
			baselineTimerId = window.setTimeout(() => {
				baselineTimerId = null;
				captureBaseline();
			}, 0);
		});
	};
	const scheduleDirtyRefresh = () => {
		if (dirtyTimerId !== null) window.clearTimeout(dirtyTimerId);
		dirtyTimerId = window.setTimeout(() => {
			dirtyTimerId = null;
			refreshDirtyState();
		}, 120);
	};
	const handleBeforeUnload = (event) => {
		if (options.shouldWarn && !options.shouldWarn()) return;
		if (!refreshDirtyState()) return;
		event.preventDefault();
		event.returnValue = "";
	};
	form.addEventListener("input", scheduleDirtyRefresh);
	form.addEventListener("change", scheduleDirtyRefresh);
	window.addEventListener("beforeunload", handleBeforeUnload);
	scheduleBaselineCapture();
	return {
		captureBaseline,
		scheduleBaselineCapture,
		refreshDirtyState,
		destroy: () => {
			clearScheduledWork();
			form.removeEventListener("input", scheduleDirtyRefresh);
			form.removeEventListener("change", scheduleDirtyRefresh);
			window.removeEventListener("beforeunload", handleBeforeUnload);
		}
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var ROOT_SELECTORS = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])";
var DEFAULT_SUBMIT_DELAY_MS = 300;
var DEFAULT_HEADLESS_RENDER_ACTION = "/actions/formie/server/forms/render";
var DEFAULT_HEADLESS_GRAPHQL_ENDPOINT = "/api";
var DEFAULT_HEADLESS_REFRESH_TOKENS_ACTION = "/actions/formie/server/forms/refresh-tokens";
var DEFAULT_HEADLESS_SUBMIT_ACTION = "/actions/formie/server/submissions/submit";
var DEFAULT_HEADLESS_SET_PAGE_ACTION = "/actions/formie/server/submissions/set-page";
var DEFAULT_HEADLESS_CLEAR_SUBMISSION_ACTION = "/actions/formie/server/submissions/clear-submission";
var DEFAULT_FILE_UPLOAD_HYDRATE_ACTION = "/actions/formie/file-upload/hydrate";
var debug$1 = createDebug("general", "client");
var compatibilityWarnings = /* @__PURE__ */ new Set();
function parseBooleanOption(value, defaultValue) {
	if (value == null || value === "") return defaultValue;
	const normalized = value.toLowerCase();
	return !(normalized === "false" || normalized === "0" || normalized === "off");
}
function inferStaticCacheOnLoadFromDataset(dataset) {
	if (dataset.formieRefreshTokens != null && dataset.formieRefreshTokens !== "") return parseBooleanOption(dataset.formieRefreshTokens, false);
	if (dataset.formieStaticCache != null && dataset.formieStaticCache !== "") return parseBooleanOption(dataset.formieStaticCache, false);
	return false;
}
function inferOptionsFromElement(target) {
	const dataset = target instanceof HTMLElement ? target.dataset : {};
	return {
		mode: "server-rendered",
		transport: dataset.formieTransport || "rest",
		formHandle: dataset.formieHandle,
		endpoint: dataset.formieEndpoint,
		staticCache: inferStaticCacheOnLoadFromDataset(dataset),
		autoVisible: parseBooleanOption(dataset.formieAutoVisible, true),
		compatibility: parseBooleanOption(dataset.formieCompatibility, false)
	};
}
function normalizeMode(mode) {
	return mode || "server-rendered";
}
function normalizeTransport(transport) {
	return transport || "rest";
}
function getFormFromTarget(target) {
	if (target instanceof HTMLFormElement) return target;
	return target.querySelector("form");
}
function warnCompatibilityOnce(key, message) {
	if (compatibilityWarnings.has(key)) return;
	compatibilityWarnings.add(key);
	debug$1.warn(message);
}
function resolveEndpointAgainstBase(endpoint, baseEndpoint) {
	if (!endpoint) return endpoint;
	try {
		return new URL(endpoint).toString();
	} catch (_error) {}
	if (!baseEndpoint) return endpoint;
	try {
		return new URL(endpoint, baseEndpoint).toString();
	} catch (_error) {
		return endpoint;
	}
}
function resolveHeadlessEndpoint(baseOrEndpoint, actionPath) {
	const candidate = (baseOrEndpoint || "").trim();
	if (!candidate) return actionPath;
	if (candidate.includes("/actions/")) return candidate;
	return resolveEndpointAgainstBase(actionPath, candidate);
}
function resolveHtmlRenderEndpoint(options, target) {
	return resolveHeadlessEndpoint(options.endpoint || target.dataset.formieEndpoint, DEFAULT_HEADLESS_RENDER_ACTION);
}
function resolveGraphqlEndpoint(options, target) {
	const candidate = (options.endpoint || target.dataset.formieEndpoint || "").trim();
	if (!candidate) return DEFAULT_HEADLESS_GRAPHQL_ENDPOINT;
	if (candidate.includes("/graphql") || candidate.endsWith("/api") || candidate.includes("/actions/graphql/")) return candidate;
	return resolveEndpointAgainstBase(DEFAULT_HEADLESS_GRAPHQL_ENDPOINT, candidate);
}
function resolveRefreshTokensEndpoint(options, target) {
	return resolveHeadlessEndpoint(target.dataset.formieRefreshTokensEndpoint || options.endpoint || target.dataset.formieEndpoint, DEFAULT_HEADLESS_REFRESH_TOKENS_ACTION);
}
function mergeSearchParams(sourceUrl, destinationUrl) {
	if (!sourceUrl) return destinationUrl;
	try {
		const source = new URL(sourceUrl, window.location.origin);
		const destination = new URL(destinationUrl, window.location.origin);
		source.searchParams.forEach((value, key) => {
			if (!destination.searchParams.has(key)) destination.searchParams.set(key, value);
		});
		return destination.toString();
	} catch (_error) {
		return destinationUrl;
	}
}
function normalizeHeadlessManagedUrls(target, form, options) {
	const baseEndpoint = options.endpoint || target.dataset.formieEndpoint;
	const submitAction = resolveHeadlessEndpoint(baseEndpoint, DEFAULT_HEADLESS_SUBMIT_ACTION);
	const existingAction = form.getAttribute("action");
	form.setAttribute("action", mergeSearchParams(existingAction, submitAction));
	form.querySelectorAll("[data-formie-tab-link]").forEach((link) => {
		const existingHref = link.getAttribute("href");
		const setPageEndpoint = resolveHeadlessEndpoint(baseEndpoint, DEFAULT_HEADLESS_SET_PAGE_ACTION);
		link.setAttribute("href", mergeSearchParams(existingHref, setPageEndpoint));
	});
	form.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((input) => {
		input.setAttribute("data-formie-file-upload-hydrate-endpoint", resolveHeadlessEndpoint(baseEndpoint, DEFAULT_FILE_UPLOAD_HYDRATE_ACTION));
	});
}
function ensureSupportedHeadlessTransport(transport, mode) {
	if (transport === "graphql" && mode !== "server-rendered") throw new Error(`Formie ${mode} mode does not support GraphQL transport yet.`);
}
function parseBooleanDatasetValue(value) {
	if (value == null) return false;
	const normalized = value.trim().toLowerCase();
	return normalized === "true" || normalized === "1" || normalized === "";
}
function hasAutomaticSubmissionState(form) {
	return parseBooleanOption(form.dataset.formieAutomaticSubmissionState, true);
}
function resolveClearSubmissionEndpoint(options, target, form) {
	return resolveHeadlessEndpoint(form.dataset.formieClearSubmissionEndpoint || options.endpoint || target.dataset.formieEndpoint, DEFAULT_HEADLESS_CLEAR_SUBMISSION_ACTION);
}
function shouldEnableUnloadWarning(form) {
	return parseBooleanDatasetValue(form.dataset.formieUnloadWarning);
}
function markInternalNavigation(form, reason) {
	form.setAttribute("data-formie-internal-navigation", reason);
}
function clearInternalNavigation(form) {
	form.removeAttribute("data-formie-internal-navigation");
}
function hasInternalNavigation(form) {
	return form.getAttribute("data-formie-internal-navigation") !== null;
}
function urlHasSearchParam(sourceUrl, param) {
	if (!sourceUrl) return false;
	try {
		return new URL(sourceUrl, window.location.origin).searchParams.has(param);
	} catch (_error) {
		return false;
	}
}
function formHasResumeTokenState(form) {
	return urlHasSearchParam(window.location.href, "resumeToken") || urlHasSearchParam(form.getAttribute("action"), "resumeToken");
}
function isSameTabClickEvent(event) {
	if (!(event instanceof MouseEvent)) return true;
	return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}
function parseIntegerDatasetValue(value, fallback = 0) {
	if (!value) return fallback;
	const parsed = Number.parseInt(value, 10);
	if (!Number.isFinite(parsed)) return fallback;
	return parsed;
}
function getSubmitDelayMs(form) {
	return Math.max(0, parseIntegerDatasetValue(form.dataset.formieSubmitDelay, DEFAULT_SUBMIT_DELAY_MS));
}
function shouldValidateOnSubmit(form) {
	return parseBooleanDatasetValue(form.dataset.formieValidationOnSubmit);
}
async function waitForSubmitDelay(form) {
	const delay = getSubmitDelayMs(form);
	if (delay < 1) return;
	await new Promise((resolve) => {
		window.setTimeout(resolve, delay);
	});
}
function parseJsonAttribute(element, attributeName) {
	const rawValue = element?.getAttribute(attributeName)?.trim();
	if (!rawValue) return null;
	try {
		return JSON.parse(rawValue);
	} catch (error) {
		console.error(`[formie] Failed to parse ${attributeName}.`, error);
		return null;
	}
}
function getEmbeddedPayload(target, form) {
	const payloadRoot = form || (target instanceof HTMLFormElement ? target : null);
	if (!payloadRoot) return null;
	const modules = parseJsonAttribute(payloadRoot, "data-formie-modules");
	const theme = parseJsonAttribute(payloadRoot, "data-formie-theme");
	if (!modules && !theme) return null;
	return {
		modules: modules || void 0,
		theme: theme || void 0
	};
}
function isElementVisible(target) {
	if (!(target instanceof HTMLElement)) return true;
	if (!target.isConnected) return false;
	if (target.hidden || target.closest("[hidden]")) return false;
	const style = window.getComputedStyle(target);
	if (style.display === "none" || style.visibility === "hidden") return false;
	return target.getClientRects().length > 0;
}
function isWithinScope(target, scope) {
	if (scope === document) return true;
	if (scope instanceof Element) return scope === target || scope.contains(target);
	return true;
}
function getTargetDebugLabel(target) {
	const element = target;
	const id = element.id ? `#${element.id}` : "";
	const handle = element.dataset?.formieHandle ? `[handle="${element.dataset.formieHandle}"]` : "";
	return `${element.tagName ? element.tagName.toLowerCase() : "element"}${id}${handle}`;
}
function applyRefreshTokensToForm(form, refreshTokens) {
	if (!refreshTokens) return;
	if (refreshTokens.csrf?.param && refreshTokens.csrf?.token) {
		const csrfInput = form.querySelector(`input[name="${refreshTokens.csrf.param}"]`);
		if (csrfInput) csrfInput.value = refreshTokens.csrf.token;
	}
	if (refreshTokens.requestToken) {
		const requestTokenInput = form.querySelector("input[name=\"requestToken\"]");
		if (requestTokenInput) requestTokenInput.value = refreshTokens.requestToken;
	}
	if (refreshTokens.renderId) {
		const renderIdInput = form.querySelector("input[name=\"renderId\"]");
		if (renderIdInput) renderIdInput.value = refreshTokens.renderId;
	}
	if (refreshTokens.captchas && typeof refreshTokens.captchas === "object") Object.values(refreshTokens.captchas).forEach((captchaEntry) => {
		if (!captchaEntry || typeof captchaEntry !== "object") return;
		const entry = captchaEntry;
		if (!entry.sessionKey) return;
		const captchaInput = form.querySelector(`input[name="${entry.sessionKey}"]`);
		if (captchaInput && typeof entry.value === "string") captchaInput.value = entry.value;
	});
}
async function ensureHtmlRender(target, options) {
	const mode = normalizeMode(options.mode);
	const transport = normalizeTransport(options.transport);
	if (mode !== "server-rendered") return null;
	if (options.payload) {
		if (options.payload.html) target.innerHTML = options.payload.html;
		return options.payload;
	}
	ensureSupportedHeadlessTransport(transport, mode);
	const hasForm = !!getFormFromTarget(target);
	const formHandle = options.formHandle || target.dataset.formieHandle;
	if (hasForm || !formHandle) return null;
	const renderOptions = {
		mode,
		endpoint: options.endpoint,
		locale: options.locale,
		siteId: options.siteId,
		theme: options.theme,
		themeConfig: options.themeConfig
	};
	const endpoint = transport === "graphql" ? resolveGraphqlEndpoint(options, target) : resolveHtmlRenderEndpoint(options, target);
	const payload = transport === "graphql" ? await requestGraphqlRender(endpoint, formHandle, renderOptions) : await requestRender(endpoint, formHandle, {
		...renderOptions,
		endpoint
	});
	if (payload?.html) target.innerHTML = payload.html;
	return payload;
}
async function refreshTokensAfterSubmitIfNeeded(target, options, form) {
	if (options.refreshTokens === false) return;
	ensureSupportedHeadlessTransport(normalizeTransport(options.transport), normalizeMode(options.mode));
	const formHandle = options.formHandle || target.dataset.formieHandle;
	if (!formHandle) return;
	const refreshTokens = await requestRefreshTokens(resolveRefreshTokensEndpoint(options, target), formHandle, form.querySelector("input[name=\"renderId\"]")?.value || void 0);
	applyRefreshTokensToForm(form, refreshTokens);
	dispatchFormieDomEvent(target, "formie:refresh-tokens:refreshed", refreshTokens);
}
function bindFormEvents(target, form, options, bus, validator, unbinds) {
	const submitMethod = String(form.dataset.formieSubmitMethod || "").trim().toLowerCase();
	const clearSubmissionEndpoint = resolveClearSubmissionEndpoint(options, target, form);
	let allowNativeSubmit = false;
	const submitButtons = form.querySelectorAll("[data-formie-action]");
	const setPendingAction = (action) => {
		if (action) {
			form.setAttribute("data-formie-pending-action", action);
			return;
		}
		form.removeAttribute("data-formie-pending-action");
	};
	if (shouldEnableUnloadWarning(form)) {
		const unloadWarning = createFormUnloadWarningGuard(form, { shouldWarn: () => {
			return !hasInternalNavigation(form);
		} });
		const handleSubmitResult = (event) => {
			if (!(event instanceof CustomEvent)) return;
			const result = event.detail;
			if (!result?.ok) return;
			if (result.action === "save") unloadWarning.scheduleBaselineCapture();
		};
		const handleStateReset = () => {
			unloadWarning.scheduleBaselineCapture();
		};
		target.addEventListener("formie:submit:result", handleSubmitResult);
		form.addEventListener("formie:state:reset", handleStateReset);
		unbinds.push(() => {
			target.removeEventListener("formie:submit:result", handleSubmitResult);
			form.removeEventListener("formie:state:reset", handleStateReset);
			unloadWarning.destroy();
		});
	}
	submitButtons.forEach((button) => {
		const handler = (event) => {
			const action = event.currentTarget.getAttribute("data-formie-action");
			const submitAction = form.querySelector("input[name=\"submitAction\"]");
			setPendingAction(action);
			if (action && submitAction) submitAction.value = action;
		};
		button.addEventListener("click", handler);
		unbinds.push(() => {
			button.removeEventListener("click", handler);
		});
	});
	form.querySelectorAll("[data-formie-tab-link]").forEach((link) => {
		const handler = async (event) => {
			if (submitMethod !== "ajax") {
				if (isSameTabClickEvent(event)) markInternalNavigation(form, "set-page");
				return;
			}
			event.preventDefault();
			const currentTarget = event.currentTarget;
			const nextPageId = currentTarget?.getAttribute("data-formie-page-id");
			const href = currentTarget?.getAttribute("href");
			if (!nextPageId || !href) return;
			applyPageState(form, nextPageId);
			dispatchFormieDomEvent(target, "formie:page:navigate", {
				pageId: nextPageId,
				href
			});
			try {
				dispatchFormieDomEvent(target, "formie:page:navigate:after", {
					pageId: nextPageId,
					href,
					response: await requestSetPage(href, form, nextPageId)
				});
			} catch (error) {
				console.error("[formie] Failed to persist page navigation state.", error);
				dispatchFormieDomEvent(target, "formie:page:navigate:error", {
					pageId: nextPageId,
					href,
					error
				});
			}
		};
		link.addEventListener("click", handler);
		unbinds.push(() => {
			link.removeEventListener("click", handler);
		});
	});
	if (!hasAutomaticSubmissionState(form)) {
		let requestedClearOnLeave = false;
		const leaveHandler = () => {
			if (requestedClearOnLeave || hasInternalNavigation(form) || formHasResumeTokenState(form)) return;
			requestedClearOnLeave = true;
			clearSubmissionOnUnload(clearSubmissionEndpoint, form);
		};
		window.addEventListener("pagehide", leaveHandler);
		window.addEventListener("beforeunload", leaveHandler);
		unbinds.push(() => {
			window.removeEventListener("pagehide", leaveHandler);
			window.removeEventListener("beforeunload", leaveHandler);
		});
	}
	const submitHandler = async (event) => {
		if (allowNativeSubmit) return;
		const isAjaxSubmit = submitMethod === "ajax";
		if (!isAjaxSubmit) event.preventDefault();
		else event.preventDefault();
		if (form.getAttribute("data-formie-loading") === "true") {
			if (!(form.getAttribute("data-formie-internal-resubmit") === "true")) return;
			form.removeAttribute("data-formie-internal-resubmit");
		} else form.removeAttribute("data-formie-internal-resubmit");
		const submitter = event.submitter;
		const actionFromSubmitter = submitter?.getAttribute("data-formie-action");
		const pendingAction = form.getAttribute("data-formie-pending-action");
		const submitAction = form.querySelector("input[name=\"submitAction\"]");
		const action = actionFromSubmitter || pendingAction || submitAction?.value || "submit";
		let result = null;
		let nativeSubmitStarted = false;
		try {
			if (isAjaxSubmit) result = await executeAjaxSubmitFlow({
				target,
				form,
				bus,
				validator,
				validateOnSubmit: shouldValidateOnSubmit(form),
				action,
				submitter,
				waitForSubmitDelay,
				onRefreshTokensAfterSubmit: async () => {
					await refreshTokensAfterSubmitIfNeeded(target, options, form);
				},
				dispatchSubmitResult: (submitResult) => {
					dispatchFormieDomEvent(target, "formie:submit:result", submitResult);
				}
			});
			else {
				clearSubmitFeedback(form);
				setSubmitLoading(form, submitter);
				await waitForSubmitDelay(form);
				result = await runSubmitPipeline(form, action, bus, {
					validator,
					validateOnSubmit: shouldValidateOnSubmit(form),
					preflightOnly: true
				});
				if (result.ok) {
					dispatchPageClientEventForSubmit(form, action);
					allowNativeSubmit = true;
					markInternalNavigation(form, "submit");
					setPendingAction(null);
					let nativeValidationFailed = false;
					const nativeInvalidHandler = () => {
						nativeValidationFailed = true;
						allowNativeSubmit = false;
						clearInternalNavigation(form);
						clearSubmitLoading(form);
					};
					if (typeof form.requestSubmit === "function") {
						form.addEventListener("invalid", nativeInvalidHandler, true);
						try {
							form.requestSubmit();
						} finally {
							form.removeEventListener("invalid", nativeInvalidHandler, true);
						}
					} else form.submit();
					if (nativeValidationFailed) return;
					nativeSubmitStarted = true;
					return;
				}
				applySubmitResultUi(form, result);
				dispatchFormieDomEvent(target, "formie:submit:result", result);
				clearInternalNavigation(form);
			}
		} catch (error) {
			allowNativeSubmit = false;
			result = {
				ok: false,
				code: "SUBMIT_ERROR",
				message: error instanceof Error ? error.message : "Submission failed.",
				formErrors: [error instanceof Error ? error.message : "Submission failed."]
			};
			applySubmitResultUi(form, result);
			dispatchFormieDomEvent(target, "formie:submit:result", result);
			clearInternalNavigation(form);
		} finally {
			setPendingAction(null);
			if (!isAjaxSubmit && !nativeSubmitStarted && !shouldKeepSubmitLoading(result)) clearSubmitLoading(form);
		}
	};
	form.addEventListener("submit", submitHandler);
	unbinds.push(() => {
		form.removeEventListener("submit", submitHandler);
	});
}
async function refreshTokensIfNeeded(target, options, form) {
	if (options.refreshTokens === false) return;
	if (!options.staticCache) return;
	ensureSupportedHeadlessTransport(normalizeTransport(options.transport), normalizeMode(options.mode));
	const formHandle = options.formHandle || target.dataset.formieHandle;
	const endpoint = resolveRefreshTokensEndpoint(options, target);
	const renderId = (form?.querySelector("input[name=\"renderId\"]"))?.value || void 0;
	if (!formHandle) return;
	const refreshTokens = await requestRefreshTokens(endpoint, formHandle, renderId);
	if (!refreshTokens || !form) return;
	applyRefreshTokensToForm(form, refreshTokens);
	dispatchFormieDomEvent(target, "formie:refresh-tokens:after", refreshTokens);
}
function createFormieClient() {
	const instances = /* @__PURE__ */ new Map();
	const moduleRegistry = new ModuleRegistry();
	const pendingVisibilityMounts = /* @__PURE__ */ new Map();
	const pendingUnmounts = /* @__PURE__ */ new Map();
	const stageNames = [
		"prepare",
		"normalize",
		"validate",
		"screen",
		"authorize",
		"dispatch",
		"finalize"
	];
	const unmount = async (target) => {
		const inFlightUnmount = pendingUnmounts.get(target);
		if (inFlightUnmount) {
			await inFlightUnmount;
			return;
		}
		const unmountPromise = (async () => {
			debug$1.log("Unmount requested.", { target: getTargetDebugLabel(target) });
			const pendingUnmount = pendingVisibilityMounts.get(target);
			if (pendingUnmount) {
				pendingUnmount();
				pendingVisibilityMounts.delete(target);
			}
			const state = instances.get(target);
			if (!state) {
				debug$1.log("Unmount skipped (no mounted state).", { target: getTargetDebugLabel(target) });
				return;
			}
			dispatchFormieDomEvent(target, "formie:unmount:before", { id: state.instance.id });
			state.unbinds.forEach((unbind) => {
				unbind();
			});
			state.unbinds = [];
			state.validator?.destroy();
			state.validator = null;
			for (const moduleInstance of state.modules) await moduleInstance.destroy();
			state.modules = [];
			state.bus.clear();
			instances.delete(target);
			dispatchFormieDomEvent(target, "formie:unmount:after", { id: state.instance.id });
			debug$1.log("Unmount complete.", {
				id: state.instance.id,
				target: getTargetDebugLabel(target)
			});
		})().finally(() => {
			pendingUnmounts.delete(target);
		});
		pendingUnmounts.set(target, unmountPromise);
		await unmountPromise;
	};
	const mount = async (target, options) => {
		debug$1.log("Mount requested.", {
			target: getTargetDebugLabel(target),
			mode: options.mode,
			autoVisible: options.autoVisible
		});
		const pendingMount = pendingVisibilityMounts.get(target);
		if (pendingMount) {
			pendingMount();
			pendingVisibilityMounts.delete(target);
		}
		const existing = instances.get(target);
		if (existing) {
			debug$1.log("Mount skipped (already mounted).", {
				id: existing.instance.id,
				target: getTargetDebugLabel(target)
			});
			return existing.instance;
		}
		const bus = new EventBus();
		const unbinds = [];
		const id = target?.id || `formie-${instances.size + 1}`;
		const mergedFromDom = inferOptionsFromElement(target);
		const normalizedOptions = {
			...mergedFromDom,
			...options,
			mode: normalizeMode(options.mode ?? mergedFromDom.mode),
			transport: normalizeTransport(options.transport ?? mergedFromDom.transport)
		};
		const compatibilityOptions = resolveLegacyCompatibilityOptions(normalizedOptions.compatibility);
		if (normalizedOptions.mode !== "server-rendered" && !getFormFromTarget(target)) throw new Error(`Formie ${normalizedOptions.mode} mode is not implemented yet in the browser client.`);
		const renderPayload = await ensureHtmlRender(target, normalizedOptions);
		const form = getFormFromTarget(target);
		normalizedOptions.staticCache = options.staticCache ?? (form ? inferStaticCacheOnLoadFromDataset(form.dataset) : inferStaticCacheOnLoadFromDataset(target.dataset));
		const embeddedPayload = getEmbeddedPayload(target, form);
		const payload = renderPayload || embeddedPayload ? {
			...renderPayload || {},
			...embeddedPayload || {}
		} : null;
		const themeClassMap = payload?.theme;
		const stateStore = {};
		const moduleManifest = (payload?.modules || []).filter((item) => {
			return !!item?.id && !!item?.type;
		});
		debug$1.log("Resolved mount payload.", {
			target: getTargetDebugLabel(target),
			hasRenderPayload: !!renderPayload,
			hasEmbeddedPayload: !!embeddedPayload,
			moduleCount: moduleManifest.length
		});
		const resolvedThemeClassMap = registerThemeClassMap(target, themeClassMap, form);
		const validator = form ? new FormieValidator(form, {
			live: parseBooleanDatasetValue(form.dataset.formieValidationOnFocus),
			errorMessage: form.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: resolvedThemeClassMap.fieldLayoutError || [],
			inputErrorClass: resolvedThemeClassMap.fieldControlError || [],
			messagesClass: resolvedThemeClassMap.fieldErrors || [],
			messageClass: resolvedThemeClassMap.fieldError || []
		}) : null;
		if (form && validator) {
			const formWithValidationApi = form;
			formWithValidationApi.formieValidation = validator;
			stateStore.validation = validator;
			const validatorDetail = {
				validator,
				addValidator: validator.addValidator.bind(validator),
				removeValidator: validator.removeValidator.bind(validator)
			};
			dispatchFormieDomEvent(form, "formie:validator:ready", validatorDetail);
			dispatchFormieDomEvent(target, "formie:validator:ready", validatorDetail);
		}
		if (form) {
			if (renderPayload || normalizedOptions.endpoint || target.dataset.formieEndpoint) normalizeHeadlessManagedUrls(target, form, normalizedOptions);
			syncPageTabErrors(form);
		}
		if (Object.keys(resolvedThemeClassMap).length) dispatchFormieDomEvent(target, "formie:theme:applied", { hasClasses: true });
		const modules = await loadModulesFromManifest(moduleManifest, {
			registry: moduleRegistry,
			matchContext: {
				root: target,
				form,
				mode: normalizedOptions.mode
			},
			setupContext: {
				formId: id,
				root: target,
				form,
				target,
				scope: "form",
				state: stateStore,
				on: (eventName, callback) => {
					return bus.on(eventName, callback);
				},
				emit: (eventName, payload) => {
					dispatchFormieDomEvent(target, eventName, payload);
					return bus.emitSafe(eventName, payload).then((emitReport) => {
						if (emitReport.failed.length > 0) debug$1.warn("Lifecycle listeners failed.", {
							eventName,
							failed: emitReport.failed.length
						});
					});
				}
			}
		});
		debug$1.log("Module setup complete.", {
			target: getTargetDebugLabel(target),
			moduleInstances: modules.length
		});
		const instance = {
			id,
			root: target,
			submit: async (action = "submit") => {
				debug$1.log("Submit requested.", {
					id,
					target: getTargetDebugLabel(target),
					action
				});
				if (!form) return {
					ok: false,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				const submitAction = form.querySelector("input[name=\"submitAction\"]");
				if (submitAction) submitAction.value = action;
				if (form.getAttribute("data-formie-loading") === "true") return {
					ok: false,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				const fallbackSubmitter = form.querySelector(`[data-formie-action="${action}"]`);
				const result = await executeAjaxSubmitFlow({
					id,
					target,
					form,
					bus,
					validator,
					validateOnSubmit: shouldValidateOnSubmit(form),
					action,
					submitter: fallbackSubmitter,
					waitForSubmitDelay,
					onRefreshTokensAfterSubmit: async () => {
						await refreshTokensAfterSubmitIfNeeded(target, normalizedOptions, form);
					},
					dispatchSubmitResult: (submitResult) => {
						dispatchFormieDomEvent(target, "formie:submit:result", submitResult);
					}
				});
				debug$1.log("Submit completed.", {
					id,
					action,
					ok: result.ok,
					code: result.code,
					message: result.message
				});
				return result;
			},
			destroy: async () => {
				await unmount(target);
			},
			on: (eventName, callback) => {
				return bus.on(eventName, callback);
			}
		};
		if (form) {
			bindLegacyValidatorCompatibility({
				target,
				form,
				validatorDetail: validator ? {
					validator,
					addValidator: validator.addValidator.bind(validator),
					removeValidator: validator.removeValidator.bind(validator)
				} : null,
				options: compatibilityOptions,
				unbinds
			});
			bindLegacyDomEventCompatibility({
				target,
				form,
				instance,
				options: compatibilityOptions,
				unbinds
			});
		}
		if (form) {
			bindFormEvents(target, form, normalizedOptions, bus, validator, unbinds);
			await refreshTokensIfNeeded(target, normalizedOptions, form);
		}
		stageNames.forEach((stageName) => {
			const beforeDomUnbind = bus.on(`formie:stage:${stageName}:before`, async (payload) => {
				dispatchFormieDomEvent(target, `formie:stage:${stageName}:before`, payload);
			});
			const beforeUnbind = bus.on(`formie:stage:${stageName}:before`, async (payload) => {
				for (const moduleInstance of modules) if (moduleInstance.onBeforeStage) await moduleInstance.onBeforeStage(payload);
			});
			const afterDomUnbind = bus.on(`formie:stage:${stageName}:after`, async (payload) => {
				dispatchFormieDomEvent(target, `formie:stage:${stageName}:after`, payload);
			});
			const afterUnbind = bus.on(`formie:stage:${stageName}:after`, async (payload) => {
				const stagePayload = payload;
				for (const moduleInstance of modules) if (moduleInstance.onAfterStage) await moduleInstance.onAfterStage(stagePayload, stagePayload.result);
			});
			unbinds.push(beforeDomUnbind, beforeUnbind, afterDomUnbind, afterUnbind);
		});
		const submitBeforeUnbind = bus.on("formie:submit:before", async (payload) => {
			dispatchFormieDomEvent(target, "formie:submit:before", payload);
		});
		const submitAfterUnbind = bus.on("formie:submit:after", async (payload) => {
			dispatchFormieDomEvent(target, "formie:submit:after", payload);
		});
		const submitFinalBeforeUnbind = bus.on("formie:submit:final:before", async (payload) => {
			dispatchFormieDomEvent(target, "formie:submit:final:before", payload);
		});
		const submitFinalAfterUnbind = bus.on("formie:submit:final:after", async (payload) => {
			dispatchFormieDomEvent(target, "formie:submit:final:after", payload);
		});
		unbinds.push(submitBeforeUnbind, submitAfterUnbind, submitFinalBeforeUnbind, submitFinalAfterUnbind);
		instances.set(target, {
			options: normalizedOptions,
			bus,
			form,
			validator,
			modules,
			unbinds,
			instance
		});
		dispatchFormieDomEvent(target, "formie:mount:after", {
			id,
			mode: normalizedOptions.mode
		});
		debug$1.log("Mount complete.", {
			id,
			target: getTargetDebugLabel(target),
			mode: normalizedOptions.mode
		});
		return instance;
	};
	const mountWhenVisible = (target, options) => {
		if (!options.autoVisible || isElementVisible(target) || typeof IntersectionObserver === "undefined") return mount(target, options);
		if (instances.has(target)) return Promise.resolve(instances.get(target)?.instance || null);
		if (pendingVisibilityMounts.has(target)) {
			debug$1.log("Mount deferred (already waiting visibility).", { target: getTargetDebugLabel(target) });
			return Promise.resolve(null);
		}
		const observer = new IntersectionObserver((entries) => {
			if (!entries.some((entry) => {
				return entry.target === target && entry.isIntersecting;
			})) return;
			observer.disconnect();
			pendingVisibilityMounts.delete(target);
			debug$1.log("Visibility reached, proceeding mount.", { target: getTargetDebugLabel(target) });
			mount(target, {
				...options,
				autoVisible: false
			});
		}, { threshold: .01 });
		observer.observe(target);
		pendingVisibilityMounts.set(target, () => {
			observer.disconnect();
		});
		debug$1.log("Mount deferred until visible.", { target: getTargetDebugLabel(target) });
		return Promise.resolve(null);
	};
	const update = async (target, options) => {
		const state = instances.get(target);
		if (!state) return mount(target, {
			...inferOptionsFromElement(target),
			...options,
			mode: options.mode || "server-rendered"
		});
		state.options = {
			...state.options,
			...options
		};
		const resolvedThemeClassMap = registerThemeClassMap(target, options.payload?.theme || state.options.payload?.theme || getEmbeddedPayload(target, state.form)?.theme, state.form);
		if (state.validator) {
			state.validator.config.fieldContainerErrorClass = resolvedThemeClassMap.fieldLayoutError || [];
			state.validator.config.inputErrorClass = resolvedThemeClassMap.fieldControlError || [];
			state.validator.config.messagesClass = resolvedThemeClassMap.fieldErrors || [];
			state.validator.config.messageClass = resolvedThemeClassMap.fieldError || [];
		}
		if (Object.keys(resolvedThemeClassMap).length) dispatchFormieDomEvent(target, "formie:theme:applied", {
			hasClasses: true,
			reason: "update"
		});
		return state.instance;
	};
	const getInstance = (target) => {
		return instances.get(target)?.instance || null;
	};
	const refreshForCache = async (targetOrId) => {
		warnCompatibilityOnce("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
		let target = null;
		if (typeof targetOrId === "string") {
			const byId = document.getElementById(targetOrId);
			if (byId) target = byId;
			else target = document.querySelector(`[data-formie-form-id="${targetOrId}"]`);
		} else target = targetOrId;
		if (!target) {
			debug$1.warn("refreshForCache target not found.", { targetOrId });
			return;
		}
		const state = instances.get(target);
		const form = getFormFromTarget(target);
		const options = state?.options || inferOptionsFromElement(target);
		if (!form) {
			debug$1.warn("refreshForCache found no form element for target.", { target: getTargetDebugLabel(target) });
			return;
		}
		const formHandle = options.formHandle || target.dataset.formieHandle || form.dataset.formieHandle;
		const endpoint = resolveRefreshTokensEndpoint(options, target);
		const renderId = form.querySelector("input[name=\"renderId\"]")?.value || void 0;
		if (!formHandle) {
			debug$1.warn("refreshForCache found no form handle for target.", { target: getTargetDebugLabel(target) });
			return;
		}
		const refreshTokens = await requestRefreshTokens(endpoint, formHandle, renderId);
		if (!refreshTokens) return;
		applyRefreshTokensToForm(form, refreshTokens);
		dispatchFormieDomEvent(target, "formie:refresh-tokens:after", refreshTokens);
	};
	const registerModule = (moduleDefinition, options) => {
		return moduleRegistry.register(moduleDefinition, options);
	};
	const unregisterModule = (moduleId) => {
		moduleRegistry.unregister(moduleId);
	};
	const getRegisteredModules = () => {
		return moduleRegistry.getAll();
	};
	const scan = async (root) => {
		const scope = root || document;
		const targets = Array.from(scope.querySelectorAll(ROOT_SELECTORS));
		debug$1.log("Scan started.", {
			scope: scope === document ? "document" : scope,
			targetCount: targets.length
		});
		const instances = (await Promise.all(targets.map((target) => {
			return mountWhenVisible(target, inferOptionsFromElement(target));
		}))).filter((item) => !!item);
		debug$1.log("Scan finished.", {
			mountedCount: instances.length,
			deferredCount: targets.length - instances.length
		});
		return instances;
	};
	const observe = (root) => {
		if (typeof MutationObserver === "undefined") return () => {};
		const scope = root || document;
		debug$1.log("Observer started.", { scope: scope === document ? "document" : scope });
		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				mutation.addedNodes.forEach((node) => {
					if (!(node instanceof Element)) return;
					if (node.matches(ROOT_SELECTORS)) {
						debug$1.log("Observer detected new root.", { target: getTargetDebugLabel(node) });
						mountWhenVisible(node, inferOptionsFromElement(node));
					}
					node.querySelectorAll(ROOT_SELECTORS).forEach((child) => {
						debug$1.log("Observer detected new nested root.", { target: getTargetDebugLabel(child) });
						mountWhenVisible(child, inferOptionsFromElement(child));
					});
				});
				mutation.removedNodes.forEach((node) => {
					if (!(node instanceof Element)) return;
					if (instances.has(node)) {
						debug$1.log("Observer detected removed root.", { target: getTargetDebugLabel(node) });
						unmount(node);
					}
					node.querySelectorAll(ROOT_SELECTORS).forEach((child) => {
						if (instances.has(child)) {
							debug$1.log("Observer detected removed nested root.", { target: getTargetDebugLabel(child) });
							unmount(child);
						}
					});
				});
			});
		});
		observer.observe(scope, {
			childList: true,
			subtree: true
		});
		return () => {
			observer.disconnect();
			debug$1.log("Observer stopped.");
			pendingVisibilityMounts.forEach((cleanup, target) => {
				if (isWithinScope(target, scope)) {
					cleanup();
					pendingVisibilityMounts.delete(target);
				}
			});
			const roots = [];
			if (scope instanceof Element && scope.matches(ROOT_SELECTORS)) roots.push(scope);
			scope.querySelectorAll(ROOT_SELECTORS).forEach((target) => {
				roots.push(target);
			});
			roots.forEach((target) => {
				if (instances.has(target)) unmount(target);
			});
		};
	};
	return {
		mount,
		unmount,
		update,
		getInstance,
		refreshForCache,
		registerModule,
		unregisterModule,
		getRegisteredModules,
		scan,
		observe
	};
}
//#endregion
//#region src/js/core/hydrate-modules.ts
var debug = createDebug("general", "module-hydrator");
async function hydrateFormieModules(options) {
	const root = options.root;
	const form = options.form ?? (root instanceof HTMLFormElement ? root : root.closest("form"));
	const modules = options.modules ?? [];
	const mode = options.mode ?? "server-rendered";
	const registry = options.registry ?? new ModuleRegistry();
	const bus = new EventBus();
	const instances = await loadModulesFromManifest(modules, {
		registry,
		setupContext: {
			formId: form?.id || root.id || "formie-modules",
			root,
			form,
			target: root,
			scope: "form",
			state: {},
			options: {},
			on: (eventName, callback) => {
				return bus.on(eventName, callback);
			},
			emit: async (eventName, payload) => {
				await bus.emit(eventName, payload);
			}
		},
		matchContext: {
			root,
			form,
			mode
		}
	});
	debug.log("Hydrated module manifest.", {
		moduleCount: modules.length,
		instanceCount: instances.length,
		mode
	});
	return {
		destroy: async () => {
			await destroyModuleInstances(instances);
			bus.clear();
		},
		on: (eventName, callback) => {
			return bus.on(eventName, callback);
		},
		emit: async (eventName, payload) => {
			await bus.emit(eventName, payload);
		},
		registerModule: (moduleDefinition, registrationOptions = {}) => {
			return registry.register(moduleDefinition, registrationOptions);
		},
		unregisterModule: (moduleId) => {
			registry.unregister(moduleId);
		},
		getRegisteredModules: () => {
			return registry.getAll();
		}
	};
}
async function destroyModuleInstances(instances) {
	for (const instance of instances) try {
		await instance.destroy();
	} catch (error) {
		console.error("[formie] Failed to destroy module instance.", error);
		debug.warn("Failed destroying module instance.", { error });
	}
}
//#endregion
//#region src/js/core/formie.ts
function isElement(value) {
	return value instanceof Element;
}
function isSuccessfulResult(result) {
	return result.ok;
}
function describeTarget(target) {
	if (typeof target === "string") return `selector "${target}"`;
	if (isElement(target)) return `element "${target.tagName.toLowerCase()}"`;
	return "provided element collection";
}
function toUniqueElements(values) {
	const seen = /* @__PURE__ */ new Set();
	const elements = [];
	for (const value of values) {
		if (!isElement(value) || seen.has(value)) continue;
		seen.add(value);
		elements.push(value);
	}
	return elements;
}
function resolveElements(target) {
	if (typeof target === "string") return Array.from(document.querySelectorAll(target));
	if (isElement(target)) return [target];
	return toUniqueElements(target);
}
function waitForDomReady() {
	if (document.readyState !== "loading") return Promise.resolve();
	return new Promise((resolve) => {
		document.addEventListener("DOMContentLoaded", () => resolve(), { once: true });
	});
}
async function resolveElementsWhenReady(target) {
	const elements = resolveElements(target);
	if (elements.length > 0 || typeof target !== "string") return elements;
	await waitForDomReady();
	return resolveElements(target);
}
function resolveObservationScope(target) {
	if (typeof target === "string") return document;
	if (isElement(target)) return target.getRootNode();
	return document;
}
function buildMountOptions(options) {
	const { element: _element, observe: _observe, allowEmpty: _allowEmpty, client: _client, onReady: _onReady, onResult: _onResult, onSuccess: _onSuccess, onError: _onError, onEvent: _onEvent, ...mountOptions } = options;
	return {
		mode: "server-rendered",
		...mountOptions
	};
}
async function mountResolvedElements(options, client, states, targets) {
	const mounted = [];
	const mountOptions = buildMountOptions(options);
	for (const target of targets) {
		const existing = states.get(target);
		if (existing) {
			mounted.push(existing.instance);
			continue;
		}
		const instance = await client.mount(target, mountOptions);
		const unsubs = [];
		options.onReady?.(instance);
		unsubs.push(instance.on("formie:submit:result", (payload) => {
			const result = payload;
			options.onResult?.(result, instance);
			if (isSuccessfulResult(result)) options.onSuccess?.(result, instance);
			else options.onError?.(result, instance);
		}));
		if (options.onEvent) for (const eventName of FORMIE_HTML_EVENT_NAMES) unsubs.push(instance.on(eventName, (payload) => {
			options.onEvent?.({
				name: eventName,
				payload
			}, instance);
		}));
		states.set(target, {
			instance,
			unsubs
		});
		mounted.push(instance);
	}
	return mounted;
}
async function formie(options) {
	const client = options.client ?? createFormieClient();
	const states = /* @__PURE__ */ new Map();
	const matchedElements = await resolveElementsWhenReady(options.element);
	if (matchedElements.length === 0 && !options.allowEmpty) throw new Error(`Formie could not find any elements for ${describeTarget(options.element)}.`);
	await mountResolvedElements(options, client, states, matchedElements);
	const stopObserving = options.observe ? client.observe(resolveObservationScope(options.element)) : null;
	return {
		client,
		get instances() {
			return Array.from(states.values()).map(({ instance }) => instance);
		},
		get(target) {
			const element = typeof target === "string" ? document.querySelector(target) : target;
			if (!element) return null;
			return states.get(element)?.instance ?? client.getInstance(element);
		},
		async rescan() {
			const nextTargets = resolveElements(options.element);
			if (nextTargets.length === 0) return Array.from(states.values()).map(({ instance }) => instance);
			return mountResolvedElements(options, client, states, nextTargets);
		},
		async destroy() {
			stopObserving?.();
			const mountedEntries = Array.from(states.entries());
			for (const [target, state] of mountedEntries) {
				state.unsubs.forEach((unsubscribe) => unsubscribe());
				await client.unmount(target);
				states.delete(target);
			}
		}
	};
}
//#endregion
export { FORMIE_HTML_EVENT_NAMES, FormieValidator, LEGACY_FORMIE_DOM_EVENT_BRIDGES, LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, ModuleRegistry, bindLegacyDomEventCompatibility, bindLegacyValidatorCompatibility, buildFieldValueRegistry, createDebug, createFormieClient, debugLog, debugWarn, defineAddressModule, defineCaptchaModule, definePassiveCaptchaModule, definePaymentModule, fieldKeyToInputName, formie, getFieldModuleEventName, getFormieTranslations, getGlobalModuleLifecycleEventName, getScopedModuleLifecycleEventName, hydrateFormieModules, inputNameToFieldKey, isFormieDebugEnabled, mergeFormieTranslations, normalizeFieldKey, normalizeFormieEventName, parseFieldReference, resolveFieldReferenceFromFormData, resolveFieldReferenceLive, resolveLegacyCompatibilityOptions, setFormieDebugEnabled, setFormieTranslations, t, toDomEventName, translate };
