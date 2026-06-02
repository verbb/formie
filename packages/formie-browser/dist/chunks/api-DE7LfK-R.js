import { a as getFormStateEventName, u as normalizeFormieEventName } from "./event-names-DamGPtXR.js";
import { t as createDebug } from "./debug-KnZeKYBI.js";
import { i as toggleThemeClasses, r as removeThemeClasses, t as addThemeClasses } from "./theme-classes-vSHpdCUO.js";
import { i as t } from "./i18n-vUh-KGiH.js";
import { n as sleep, t as debounce } from "./async-B3DUf1GZ.js";
import { r as normalizeFieldKey, t as fieldKeyToInputName } from "./field-references.keys-BpBZ_quS.js";
import { n as resolveFieldReferenceLive, r as buildFieldValueRegistry } from "./field-references.resolver-Ba6xhiJC.js";
//#region src/js/core/page-client-event.ts
var debug$2 = createDebug("general", "page-client-event");
var CLIENT_EVENT_ATTR = "data-formie-client-event";
function escapePageIdForSelector(pageId) {
	if (typeof window !== "undefined" && window.CSS?.escape) return window.CSS.escape(pageId);
	return pageId.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
}
function resolveSubmittedPageId(form) {
	const fromInput = form.querySelector("input[name=\"pageId\"]")?.value?.trim();
	if (fromInput) return fromInput;
	const fromVisible = form.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim();
	if (fromVisible) return fromVisible;
	return form.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function parseClientEventAttr(raw) {
	if (!raw?.trim()) return null;
	try {
		const parsed = JSON.parse(raw);
		return parsed && typeof parsed === "object" ? parsed : null;
	} catch {
		debug$2.warn("Invalid data-formie-client-event JSON.", { rawPreview: raw.slice(0, 80) });
		return null;
	}
}
function buildPayloadObject(fields) {
	const out = {};
	fields.forEach((row) => {
		const key = typeof row.label === "string" ? row.label.trim() : "";
		if (!key) return;
		out[key] = typeof row.value === "string" ? row.value : "";
	});
	return out;
}
/**
* When the builder enables JavaScript events for a page, the theme emits
* `data-formie-client-event` on that page's section. On each successful
* **submit** (not back/save), push the configured key/value object to
* `window.dataLayer` (when present) and dispatch `formie:client-event`.
*/
function dispatchPageClientEventForSubmit(form, action) {
	if (action !== "submit") return;
	const pageId = resolveSubmittedPageId(form);
	if (!pageId) {
		debug$2.log("No submitted page id; skipping client event.");
		return;
	}
	const section = form.querySelector(`[data-formie-page][data-formie-page-id="${escapePageIdForSelector(pageId)}"]`);
	if (!section) {
		debug$2.log("No page section for id; skipping client event.", { pageId });
		return;
	}
	const rawAttr = section.getAttribute(CLIENT_EVENT_ATTR);
	if (rawAttr === null) return;
	const config = parseClientEventAttr(rawAttr);
	if (!config || !Array.isArray(config.fields)) return;
	const payload = buildPayloadObject(config.fields);
	const win = window;
	win.dataLayer = win.dataLayer || [];
	win.dataLayer.push(payload);
	form.dispatchEvent(new CustomEvent("formie:client-event", {
		bubbles: true,
		detail: { payload }
	}));
	debug$2.log("Dispatched page client event.", {
		pageId,
		keys: Object.keys(payload)
	});
}
//#endregion
//#region src/js/core/page-tab-errors.ts
function setTabErrorState(tab, hasError) {
	toggleThemeClasses(tab, tab, "tabError", hasError);
	if (hasError) {
		tab.setAttribute("data-formie-tab-error", "true");
		return;
	}
	tab.removeAttribute("data-formie-tab-error");
}
function syncPageTabErrors(form) {
	const pageIdsWithErrors = /* @__PURE__ */ new Set();
	form.querySelectorAll("[data-formie-page]").forEach((pageNode) => {
		const page = pageNode;
		const pageId = page.getAttribute("data-formie-page-id");
		if (!pageId) return;
		if (page.querySelector("[data-formie-field-has-error]")) pageIdsWithErrors.add(pageId);
	});
	form.querySelectorAll("[data-formie-tab]").forEach((tabNode) => {
		const tab = tabNode;
		const pageId = tab.getAttribute("data-formie-page-id");
		setTabErrorState(tab, !!pageId && pageIdsWithErrors.has(pageId));
	});
}
//#endregion
//#region src/js/core/submit-result-state.ts
var STALE_SUBMISSION_STATE_CODE = "STALE_SUBMISSION_STATE";
var finalSubmitResetTimers = /* @__PURE__ */ new WeakMap();
var originalSubmitterMarkup = /* @__PURE__ */ new WeakMap();
var debug$1 = createDebug("general", "submit-result");
function setHiddenInputValue(form, name, value) {
	let input = form.querySelector(`input[name="${name}"]`);
	if (!input) {
		input = document.createElement("input");
		input.type = "hidden";
		input.name = name;
		form.appendChild(input);
	}
	input.value = value;
}
function markInternalNavigation(form, reason) {
	form.setAttribute("data-formie-internal-navigation", reason);
}
function removeHiddenInput(form, name) {
	form.querySelector(`input[name="${name}"]`)?.remove();
}
function stripQueryParam(urlValue, paramName) {
	try {
		const url = new URL(urlValue, window.location.href);
		url.searchParams.delete(paramName);
		return url.toString();
	} catch {
		return urlValue;
	}
}
function isSameOriginUrl(urlValue) {
	try {
		return new URL(urlValue, window.location.href).origin === window.location.origin;
	} catch {
		return false;
	}
}
function getPageElements(form) {
	return Array.from(form.querySelectorAll("[data-formie-page]"));
}
function getTabElements(form) {
	return Array.from(form.querySelectorAll("[data-formie-tab]"));
}
function getProgressPercent(form, currentPageIndex, totalPages) {
	if (currentPageIndex < 0 || totalPages < 1) return 0;
	if ((form.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") === "page-position") return Math.round((currentPageIndex + 1) / totalPages * 100);
	return Math.round(currentPageIndex / totalPages * 100);
}
function getProgressState(progress) {
	if (progress <= 0) return "start";
	if (progress >= 100) return "end";
	return "middle";
}
function getConfiguredSubmitAction(form) {
	return (form.dataset.formieSubmitAction || "").trim();
}
function shouldHideFormOnSuccess(form) {
	const rawValue = form.dataset.formieSubmitActionFormHide;
	if (rawValue === void 0) return false;
	const normalized = rawValue.trim().toLowerCase();
	return normalized === "true" || normalized === "1" || normalized === "";
}
function setFormHiddenState(form, hidden) {
	const sections = [
		"[data-formie-form-header]",
		"[data-formie-form-navigation]",
		"[data-formie-form-body]",
		"[data-formie-form-footer]"
	];
	form.toggleAttribute("data-formie-form-hidden", hidden);
	sections.forEach((selector) => {
		form.querySelectorAll(selector).forEach((node) => {
			const element = node;
			if (hidden) element.hidden = true;
			else element.hidden = false;
		});
	});
}
function clearPendingFinalSubmitReset(form) {
	const timerId = finalSubmitResetTimers.get(form);
	if (typeof timerId === "number") {
		window.clearTimeout(timerId);
		finalSubmitResetTimers.delete(form);
	}
}
function setSubmitterLoadingText(submitter, loadingText) {
	if (!originalSubmitterMarkup.has(submitter)) originalSubmitterMarkup.set(submitter, submitter.innerHTML);
	submitter.textContent = loadingText;
}
function restoreSubmitterLoadingText(submitter) {
	const originalMarkup = originalSubmitterMarkup.get(submitter);
	if (originalMarkup === void 0) return;
	submitter.innerHTML = originalMarkup;
	originalSubmitterMarkup.delete(submitter);
}
function updateProgressUi(form, progress) {
	const progressBar = form.querySelector("[data-formie-progress-bar]");
	const progressValue = form.querySelector("[data-formie-progress-value]");
	if (!progressBar) return;
	progressBar.style.width = `${progress}%`;
	progressBar.setAttribute("aria-valuenow", `${progress}`);
	progressBar.setAttribute("data-formie-progress-state", getProgressState(progress));
	if (progressValue) {
		progressValue.textContent = `${progress}%`;
		progressValue.setAttribute("data-formie-progress-value", `${progress}`);
	}
}
function applySubmitterLoadingState(form, submitter) {
	if (!submitter) return;
	const indicator = (form.dataset.formieLoadingIndicator || "").trim();
	if (!indicator) return;
	submitter.setAttribute("data-formie-loading-indicator", indicator);
	if (indicator === "spinner") {
		toggleThemeClasses(submitter, form, "loading", true);
		restoreSubmitterLoadingText(submitter);
		submitter.removeAttribute("data-formie-loading-text");
		return;
	}
	if (indicator === "text") {
		const configuredText = (form.dataset.formieLoadingIndicatorText || "").trim();
		const fallbackText = submitter.textContent?.trim() || "";
		const loadingText = configuredText || fallbackText;
		submitter.setAttribute("data-formie-loading-text", loadingText);
		setSubmitterLoadingText(submitter, loadingText);
		return;
	}
	restoreSubmitterLoadingText(submitter);
	submitter.removeAttribute("data-formie-loading-text");
}
function getActionButtons(form) {
	return Array.from(form.querySelectorAll("[data-formie-action]"));
}
function setSubmitLoading(form, submitter) {
	if (form.getAttribute("data-formie-loading") === "true") return;
	form.setAttribute("data-formie-loading", "true");
	getActionButtons(form).forEach((button) => {
		if ("disabled" in button) {
			if (button.disabled) button.setAttribute("data-formie-was-disabled", "true");
			else button.removeAttribute("data-formie-was-disabled");
			button.disabled = true;
		}
	});
	if (submitter) {
		submitter.setAttribute("data-formie-loading", "true");
		applySubmitterLoadingState(form, submitter);
	}
}
function clearSubmitLoading(form) {
	form.removeAttribute("data-formie-loading");
	getActionButtons(form).forEach((button) => {
		if ("disabled" in button) {
			const element = button;
			element.disabled = element.getAttribute("data-formie-was-disabled") === "true";
		}
		restoreSubmitterLoadingText(button);
		button.removeAttribute("data-formie-was-disabled");
		button.removeAttribute("data-formie-loading");
		toggleThemeClasses(button, form, "loading", false);
		button.removeAttribute("data-formie-loading-indicator");
		button.removeAttribute("data-formie-loading-text");
	});
}
function applyPageState(form, nextPageId) {
	const pages = getPageElements(form);
	const tabs = getTabElements(form);
	const currentPageIndex = pages.findIndex((page) => {
		return page.getAttribute("data-formie-page-id") === nextPageId;
	});
	pages.forEach((page) => {
		if (page.getAttribute("data-formie-page-id") === nextPageId) {
			page.removeAttribute("data-formie-page-hidden");
			removeThemeClasses(page, form, "pageHidden");
		} else {
			page.setAttribute("data-formie-page-hidden", "true");
			addThemeClasses(page, form, "pageHidden");
		}
	});
	tabs.forEach((tab, index) => {
		const isCurrent = tab.getAttribute("data-formie-page-id") === nextPageId;
		const isComplete = currentPageIndex > -1 && index < currentPageIndex;
		toggleThemeClasses(tab, form, "tabCurrent", isCurrent);
		toggleThemeClasses(tab, form, "tabComplete", isComplete);
		if (isCurrent) tab.setAttribute("aria-current", "page");
		else tab.removeAttribute("aria-current");
		if (isComplete) tab.setAttribute("data-formie-tab-complete", "true");
		else tab.removeAttribute("data-formie-tab-complete");
	});
	if (currentPageIndex > -1 && pages.length > 0) updateProgressUi(form, getProgressPercent(form, currentPageIndex, pages.length));
	setHiddenInputValue(form, "pageId", nextPageId);
	syncPageTabErrors(form);
}
function syncSubmissionIdentity(form, result) {
	const submissionUid = result.meta?.submissionUid;
	if (typeof submissionUid === "string" && submissionUid.trim() !== "") setHiddenInputValue(form, "submissionUid", submissionUid);
	const continuationToken = (result.meta?.session)?.continuation?.continuationToken;
	if (typeof continuationToken === "string" && continuationToken.trim() !== "") setHiddenInputValue(form, "continuationToken", continuationToken);
	else removeHiddenInput(form, "continuationToken");
}
function clearResumeTokenState(form) {
	const action = form.getAttribute("action");
	if (action) form.setAttribute("action", stripQueryParam(action, "resumeToken"));
	try {
		const url = new URL(window.location.href);
		if (!url.searchParams.has("resumeToken")) return;
		url.searchParams.delete("resumeToken");
		window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);
	} catch {}
}
function applyResumeTokenState(form, result) {
	const resumeUrl = result.meta?.resumeUrl;
	if (typeof resumeUrl !== "string" || resumeUrl.trim() === "") return;
	const normalizedResumeUrl = resumeUrl.trim();
	if (!isSameOriginUrl(normalizedResumeUrl)) return;
	if (form.getAttribute("action")) form.setAttribute("action", normalizedResumeUrl);
	try {
		const url = new URL(normalizedResumeUrl, window.location.href);
		window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);
	} catch {}
}
function resetSubmissionState(form, options = {}) {
	const validator = form.formieValidation;
	const firstPageId = getPageElements(form)[0]?.getAttribute("data-formie-page-id");
	clearPendingFinalSubmitReset(form);
	form.reset();
	if (!options.preserveHiddenState) setFormHiddenState(form, false);
	removeHiddenInput(form, "submissionId");
	removeHiddenInput(form, "submissionUid");
	removeHiddenInput(form, "continuationToken");
	removeHiddenInput(form, "pageId");
	clearResumeTokenState(form);
	validator?.resetLiveState();
	if (firstPageId) {
		applyPageState(form, firstPageId);
		form.dispatchEvent(new CustomEvent(getFormStateEventName("reset"), { bubbles: true }));
		return;
	}
	syncPageTabErrors(form);
	form.dispatchEvent(new CustomEvent(getFormStateEventName("reset"), { bubbles: true }));
}
function shouldResetSubmissionState(result) {
	return result.code === STALE_SUBMISSION_STATE_CODE || result.meta?.resetState === true;
}
function dispatchSubmitDataEvents(form, result) {
	const submitData = result.submitData;
	const dispatchedEvents = /* @__PURE__ */ new Set();
	let hasPaymentFollowUpEvent = false;
	if (Array.isArray(submitData) && submitData.length > 0) {
		const events = submitData.filter((item) => typeof item === "object" && item !== null && "event" in item && typeof item.event === "string");
		for (const eventData of events) {
			const eventName = normalizeFormieEventName(eventData.event);
			dispatchedEvents.add(eventName);
			debug$1.log("Dispatching submitData event.", { eventName });
			if (eventName.startsWith("formie:payment:")) hasPaymentFollowUpEvent = true;
			form.dispatchEvent(new CustomEvent(eventName, {
				bubbles: true,
				detail: { data: eventData.data }
			}));
		}
	}
	const meta = result.meta || {};
	const paymentAction = (meta.paymentAction && typeof meta.paymentAction === "object" ? meta.paymentAction : null) || (meta.paymentDecision && typeof meta.paymentDecision === "object" ? meta.paymentDecision.action : null);
	const actionEvent = paymentAction ? String(paymentAction.event || "") : "";
	const actionPayload = paymentAction ? paymentAction.payload : void 0;
	const normalizedActionEvent = normalizeFormieEventName(actionEvent);
	if (normalizedActionEvent && !dispatchedEvents.has(normalizedActionEvent)) {
		if (normalizedActionEvent.startsWith("formie:payment:")) hasPaymentFollowUpEvent = true;
		form.dispatchEvent(new CustomEvent(normalizedActionEvent, {
			bubbles: true,
			detail: { data: actionPayload }
		}));
		debug$1.log("Dispatching fallback payment action event.", { eventName: normalizedActionEvent });
	}
	return { hasPaymentFollowUpEvent };
}
function applySubmitResultState(form, result, action) {
	debug$1.log("Applying submit result state.", {
		ok: result.ok,
		action,
		code: result.code,
		hasRedirect: !!result.redirect?.url,
		hasSubmitData: Array.isArray(result.submitData) && result.submitData.length > 0
	});
	if (shouldResetSubmissionState(result)) {
		resetSubmissionState(form);
		debug$1.log("Resetting state due to stale/reset marker.");
		return;
	}
	const eventDispatchResult = dispatchSubmitDataEvents(form, result);
	if (!result.ok && result.redirect?.url && !eventDispatchResult.hasPaymentFollowUpEvent) {
		debug$1.log("Applying redirect fallback for failed result.", {
			url: result.redirect.url,
			target: result.redirect.target
		});
		clearPendingFinalSubmitReset(form);
		if (result.redirect.target === "new-tab") window.open(result.redirect.url, "_blank");
		else {
			markInternalNavigation(form, "redirect");
			window.location.href = result.redirect.url;
		}
		return;
	}
	syncSubmissionIdentity(form, result);
	if (!result.ok) {
		debug$1.log("Non-redirect failure; keeping current form state.");
		clearPendingFinalSubmitReset(form);
		return;
	}
	dispatchPageClientEventForSubmit(form, action);
	if (result.nextPage?.id) {
		clearPendingFinalSubmitReset(form);
		form.formieValidation?.resetLiveState();
		applyPageState(form, result.nextPage.id);
		debug$1.log("Advanced to next page.", { nextPageId: result.nextPage.id });
		return;
	}
	if (action === "save") {
		clearPendingFinalSubmitReset(form);
		applyResumeTokenState(form, result);
		debug$1.log("Applied save/resume token state.");
		return;
	}
	if (action === "submit" && !result.redirect?.url) {
		const configuredSubmitAction = getConfiguredSubmitAction(form);
		const preserveHiddenState = configuredSubmitAction === "message" && shouldHideFormOnSuccess(form);
		if (configuredSubmitAction === "reload") {
			clearPendingFinalSubmitReset(form);
			markInternalNavigation(form, "reload");
			window.location.reload();
			return;
		}
		if (configuredSubmitAction === "reset") {
			resetSubmissionState(form);
			return;
		}
		clearPendingFinalSubmitReset(form);
		resetSubmissionState(form, { preserveHiddenState });
		return;
	}
	if (action === "submit" && result.redirect?.url && result.redirect.target === "new-tab") {
		const preserveHiddenState = getConfiguredSubmitAction(form) === "message" && shouldHideFormOnSuccess(form);
		clearPendingFinalSubmitReset(form);
		resetSubmissionState(form, { preserveHiddenState });
		return;
	}
	clearPendingFinalSubmitReset(form);
}
//#endregion
//#region src/js/modules/payments/constants.ts
/** Default timeout (ms) to wait for payment token inputs to populate. */
var DEFAULT_WAIT_FOR_VALUE_MS = 2500;
/** Per-provider default required input name suffixes for payment tokens. */
var DEFAULT_REQUIRED_INPUT_SUFFIXES = {
	bpoint: ["bpointToken"],
	stripe: ["stripePaymentIntentId"],
	paypal: ["paypalOrderId", "paypalAuthId"],
	payway: ["paywayTokenId"],
	opayo: ["opayoTokenId"],
	eway: ["ewayTokenData"],
	"go-cardless": ["goCardlessRedirectId"],
	mollie: ["molliePaymentId"],
	moneris: ["monerisTokenId"],
	paddle: ["paddleTransactionId"],
	square: ["squarePaymentId"]
};
//#endregion
//#region src/js/utils/fields.ts
function normalizeHandle(handle) {
	return handle.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function getFieldName(handle) {
	return `fields[${normalizeHandle(handle)}]`;
}
function getFormFields(form, handle) {
	const fieldName = getFieldName(handle);
	const direct = Array.from(form.querySelectorAll(`[name="${fieldName}"]`));
	const multi = Array.from(form.querySelectorAll(`[name="${fieldName}[]"]`));
	return (multi.length ? multi : direct).filter((element) => {
		return element instanceof HTMLElement;
	});
}
function getFieldLabel(form, handle) {
	const fields = getFormFields(form, handle);
	for (const field of fields) {
		const label = field.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
		if (label) return label;
	}
	return "";
}
function currencyToFloat(currencyString) {
	let sanitized = currencyString.replace(/[^\d.,-]/g, "");
	const hasComma = sanitized.includes(",");
	const hasDot = sanitized.includes(".");
	if (hasComma && hasDot) sanitized = sanitized.replace(/\./g, "").replace(/,/, ".");
	else if (hasComma && !hasDot) sanitized = sanitized.replace(/,/, ".");
	else sanitized = sanitized.replace(/,/g, "");
	return parseFloat(sanitized);
}
//#endregion
//#region src/js/modules/payments/utils.ts
function findPaymentInputBySuffix(root, suffix) {
	const escapedSuffix = suffix.replace(/"/g, "\\\"");
	return root.querySelector(`input[name$="[${escapedSuffix}]"]`) || root.querySelector(`input[name$="${escapedSuffix}"]`);
}
function hasRequiredPaymentInputs(root, requiredInputSuffixes) {
	const missingSuffix = requiredInputSuffixes.find((suffix) => {
		const input = findPaymentInputBySuffix(root, suffix);
		return !input || String(input.value || "").trim() === "";
	});
	return {
		ok: !missingSuffix,
		missingSuffix
	};
}
async function waitForRequiredPaymentInputs(root, requiredInputSuffixes, waitForValueMs) {
	const initial = hasRequiredPaymentInputs(root, requiredInputSuffixes);
	if (initial.ok) return initial;
	const deadline = Date.now() + Math.max(waitForValueMs, 0);
	while (Date.now() < deadline) {
		await sleep(120);
		const current = hasRequiredPaymentInputs(root, requiredInputSuffixes);
		if (current.ok) return current;
	}
	return hasRequiredPaymentInputs(root, requiredInputSuffixes);
}
//#endregion
//#region src/js/modules/payments/host.ts
var PAYMENT_OPTION_KEYS = new Set([
	"handle",
	"requiredInputSuffixes",
	"waitForValueMs",
	"errorMessage"
]);
var PAYMENT_SUCCESS_SELECTOR = "[data-payment-success]";
var PAYMENT_ERROR_SELECTOR = "[data-payment-error]";
function getPaymentProviderHandle(id, options) {
	return (typeof options.handle === "string" && options.handle.trim() !== "" ? options.handle.trim() : "") || id;
}
function normalizePaymentModuleOptions(id, rawOptions, defaults) {
	const options = rawOptions || {};
	const provider = Object.entries(options).reduce((carry, [key, value]) => {
		if (PAYMENT_OPTION_KEYS.has(key)) return carry;
		carry[key] = value;
		return carry;
	}, {});
	const requiredInputSuffixes = Array.isArray(options.requiredInputSuffixes) ? options.requiredInputSuffixes.map(String).filter(Boolean) : defaults.defaultRequiredInputSuffixes || [];
	const waitForValueMs = Number(options.waitForValueMs ?? defaults.defaultWaitForValueMs ?? 2500);
	const errorMessage = typeof options.errorMessage === "string" && options.errorMessage.trim() !== "" ? options.errorMessage.trim() : "Payment authorization is incomplete.";
	return {
		handle: getPaymentProviderHandle(id, options),
		transport: {
			requiredInputSuffixes,
			waitForValueMs: Number.isFinite(waitForValueMs) ? waitForValueMs : DEFAULT_WAIT_FOR_VALUE_MS,
			errorMessage
		},
		provider
	};
}
function bindDomEvent(target, eventName, callback) {
	target.addEventListener(eventName, callback);
	return () => {
		target.removeEventListener(eventName, callback);
	};
}
function createPaymentHostServices(ctx, options) {
	const field = ctx.target;
	const form = ctx.form;
	const root = ctx.root;
	const tokenRoot = form || root;
	const suffixes = options.transport.requiredInputSuffixes;
	const getRegistry = () => {
		return buildFieldValueRegistry(form || root);
	};
	const getReferenceValue = (reference) => {
		const value = resolveFieldReferenceLive(reference, getRegistry()).value;
		if (Array.isArray(value)) return value[0] || "";
		return String(value || "");
	};
	const updateInputs = (name, value) => {
		const names = Array.isArray(name) ? name : [name];
		for (const n of names) {
			const input = findPaymentInputBySuffix(tokenRoot, n) ?? field.querySelector(`input[name*="${n}"]`);
			if (input) input.value = value;
		}
	};
	const addError = (message) => {
		const container = field.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || field;
		const existing = container.querySelector(PAYMENT_ERROR_SELECTOR);
		if (existing) existing.remove();
		const el = document.createElement("div");
		el.setAttribute("data-payment-error", "");
		el.textContent = message;
		addThemeClasses(el, form || root, "fieldError");
		container.appendChild(el);
	};
	const removeError = () => {
		field.querySelector(PAYMENT_ERROR_SELECTOR)?.remove();
	};
	const addSuccess = (message) => {
		const container = field.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || field;
		const existing = container.querySelector(PAYMENT_SUCCESS_SELECTOR);
		if (existing) existing.remove();
		const el = document.createElement("div");
		el.setAttribute("data-payment-success", "");
		el.textContent = message;
		addThemeClasses(el, form || root, "successMessage");
		container.appendChild(el);
	};
	const removeSuccess = () => {
		field.querySelector(PAYMENT_SUCCESS_SELECTOR)?.remove();
	};
	const triggerSubmit = () => {
		if (form) form.setAttribute("data-formie-internal-resubmit", "true");
		if (form && typeof form.requestSubmit === "function") form.requestSubmit();
		else if (form) form.submit();
	};
	const releaseSubmitLoading = () => {
		if (!form) return;
		form.removeAttribute("data-formie-internal-resubmit");
		clearSubmitLoading(form);
	};
	const resolveAmount = (opts) => {
		const searchRoot = form || root;
		const isDynamic = String(opts.type || "").toLowerCase() === "dynamic" && typeof opts.variable === "string" && opts.variable.trim() !== "";
		const source = opts.value ?? (isDynamic ? opts.variable : opts.fixed);
		const sourceString = String(source ?? "").trim();
		const numericDirect = typeof source === "number" ? source : currencyToFloat(sourceString);
		if (Number.isFinite(numericDirect) && numericDirect > 0) return {
			ok: true,
			value: numericDirect
		};
		if (sourceString !== "") {
			const raw = getReferenceValue(sourceString);
			const numeric = currencyToFloat(raw);
			if (Number.isFinite(numeric) && numeric > 0) return {
				ok: true,
				value: numeric
			};
			const label = getFieldLabel(searchRoot, sourceString);
			if (!raw) return {
				ok: false,
				error: label ? t("Provide a value for \"{label}\" to proceed.", { label }) : t("Provide a payment amount to proceed.")
			};
		}
		return {
			ok: false,
			error: t("Payment amount must be greater than 0.")
		};
	};
	const resolveCurrency = (opts) => {
		const searchRoot = form || root;
		const isDynamic = String(opts.type || "").toLowerCase() === "dynamic" && typeof opts.variable === "string" && opts.variable.trim() !== "";
		const source = opts.value ?? (isDynamic ? opts.variable : opts.fixed ?? opts.defaultCurrency ?? "");
		const sourceString = String(source ?? "").trim();
		const direct = sourceString.toUpperCase();
		if (/^[A-Z]{3}$/.test(direct) && !isDynamic) return {
			ok: true,
			value: direct
		};
		if (sourceString !== "") {
			const raw = String(getReferenceValue(sourceString) || "").trim();
			const normalized = raw.toUpperCase();
			if (/^[A-Z]{3}$/.test(normalized)) return {
				ok: true,
				value: normalized
			};
			const label = getFieldLabel(searchRoot, sourceString);
			if (!raw) return {
				ok: false,
				error: label ? t("Provide a value for \"{label}\" to proceed.", { label }) : t("Provide a payment currency to proceed.")
			};
		}
		return {
			ok: false,
			error: t("Payment currency must be a valid 3-letter code.")
		};
	};
	const watchFieldValueChanges = (handles, callback, debounceMs = 600) => {
		const searchRoot = form || root;
		const normalizedHandles = handles.map((handle) => String(handle || "").trim()).filter(Boolean);
		if (normalizedHandles.length === 0) return () => {};
		const registry = getRegistry();
		const watchedNames = /* @__PURE__ */ new Set();
		normalizedHandles.forEach((handle) => {
			const key = normalizeFieldKey(handle);
			const entry = registry.get(key);
			if (entry?.names?.length) {
				entry.names.forEach((name) => {
					watchedNames.add(name);
				});
				return;
			}
			const fallback = fieldKeyToInputName(key);
			if (fallback) {
				watchedNames.add(fallback);
				watchedNames.add(`${fallback}[]`);
			}
		});
		const debounced = debounce(() => {
			callback();
		}, debounceMs);
		const onInput = (event) => {
			const inputName = event.target?.name || "";
			if (!inputName || !watchedNames.has(inputName)) return;
			debounced();
		};
		searchRoot.addEventListener("input", onInput);
		searchRoot.addEventListener("change", onInput);
		return () => {
			searchRoot.removeEventListener("input", onInput);
			searchRoot.removeEventListener("change", onInput);
		};
	};
	return {
		root,
		form,
		field,
		updateInputs,
		addError,
		removeError,
		addSuccess,
		removeSuccess,
		hasToken: () => hasRequiredPaymentInputs(tokenRoot, suffixes).ok,
		waitForToken: (timeoutMs = options.transport.waitForValueMs) => {
			return waitForRequiredPaymentInputs(tokenRoot, suffixes, timeoutMs).then((r) => r.ok);
		},
		getFieldValue: (handle, type = "string") => {
			const raw = getReferenceValue(handle);
			if (type === "float" || type === "int" || type === "number") return currencyToFloat(raw);
			return raw;
		},
		resolveAmount,
		resolveCurrency,
		watchFieldValueChanges,
		triggerSubmit,
		releaseSubmitLoading,
		getBillingData: (billingDetails) => {
			const billing = {};
			if (!billingDetails || typeof billingDetails !== "object") return { billing_details: billing };
			if (billingDetails.billingName) {
				const name = getReferenceValue(billingDetails.billingName);
				if (name) billing.name = name;
			}
			if (billingDetails.billingEmail) {
				const email = getReferenceValue(billingDetails.billingEmail);
				if (email) billing.email = email;
			}
			if (billingDetails.billingAddress) {
				const addr = billingDetails.billingAddress;
				const address = {};
				const address1 = getReferenceValue(`${addr}.address1`);
				const address2 = getReferenceValue(`${addr}.address2`);
				const address3 = getReferenceValue(`${addr}.address3`);
				const city = getReferenceValue(`${addr}.city`);
				const zip = getReferenceValue(`${addr}.zip`);
				const state = getReferenceValue(`${addr}.state`);
				const country = getReferenceValue(`${addr}.country`);
				if (address1) address.line1 = address1;
				if (address2) address.line2 = address2;
				if (address3) address.line3 = address3;
				if (city) address.city = city;
				if (zip) address.postal_code = zip;
				if (state) address.state = state;
				if (country) address.country = country;
				if (Object.keys(address).length) billing.address = address;
			}
			return { billing_details: billing };
		},
		events: {
			onForm: (eventName, callback) => {
				if (!form) return () => {};
				return bindDomEvent(form, eventName, callback);
			},
			onRoot: (eventName, callback) => bindDomEvent(root, eventName, callback)
		}
	};
}
//#endregion
//#region src/js/modules/payments/factories.ts
var debug = createDebug("payments");
function isTargetVisible(element) {
	const node = element;
	return !node.closest("[data-formie-page-hidden]") && !node.closest("[hidden]");
}
function createManagedPaymentModule(adapter) {
	const defaultSuffixes = adapter.defaultRequiredInputSuffixes ?? DEFAULT_REQUIRED_INPUT_SUFFIXES[adapter.id] ?? [];
	return {
		id: adapter.id,
		kind: "payment",
		match: (ctx) => {
			return !!(ctx.target.querySelector("[data-formie-field-type=\"payment\"]") || ctx.target.closest("[data-formie-field-type=\"payment\"]") || ctx.target.getAttribute?.("data-formie-field-type") === "payment");
		},
		setup: async (ctx) => {
			const fieldWithRegistry = ctx.target;
			const registry = fieldWithRegistry.__formiePaymentModuleRegistry || {};
			fieldWithRegistry.__formiePaymentModuleRegistry = registry;
			const previous = registry[adapter.id];
			if (previous?.destroy) {
				debug.warn("Found stale payment module instance; destroying previous.", { moduleId: adapter.id });
				try {
					await previous.destroy();
				} catch {}
			}
			const options = normalizePaymentModuleOptions(adapter.id, ctx.options || {}, { defaultRequiredInputSuffixes: defaultSuffixes });
			const services = createPaymentHostServices(ctx, options);
			const setupCtx = {
				...ctx,
				options,
				services
			};
			const cleanups = [];
			let apiPromise = null;
			let widget = null;
			let customSetupResult = null;
			let authorizeInFlight = null;
			const getApi = async () => {
				if (!apiPromise) {
					debug.log("Loading payment provider API.", { moduleId: adapter.id });
					apiPromise = adapter.load(setupCtx);
				}
				return apiPromise;
			};
			const ensureMounted = async () => {
				if (!adapter.mount || widget || !isTargetVisible(ctx.target)) return;
				const api = await getApi();
				try {
					widget = await adapter.mount({
						api,
						field: ctx.target,
						services,
						options,
						provider: options.provider
					});
					debug.log("Payment widget mounted.", {
						moduleId: adapter.id,
						handle: options.handle
					});
				} catch {
					debug.warn("Payment widget mount failed.", {
						moduleId: adapter.id,
						handle: options.handle
					});
				}
			};
			cleanups.push(ctx.on("formie:submit:before", () => {
				services.removeError();
				services.removeSuccess();
			}));
			if (adapter.setup) {
				const root = ctx.root || ctx.form || ctx.target;
				customSetupResult = await adapter.setup({
					...setupCtx,
					root
				});
				if (customSetupResult.destroy) cleanups.push(customSetupResult.destroy);
			}
			if (adapter.mount && isTargetVisible(ctx.target)) await ensureMounted();
			["formie:page:navigate:after", "formie:submit:result"].forEach((eventName) => {
				const handleVisibility = () => {
					ensureMounted();
				};
				ctx.root.addEventListener(eventName, handleVisibility);
				cleanups.push(() => {
					ctx.root.removeEventListener(eventName, handleVisibility);
				});
			});
			const destroy = async () => {
				debug.log("Destroying payment module.", {
					moduleId: adapter.id,
					handle: options.handle
				});
				cleanups.forEach((c) => c());
				if (widget && adapter.unmount) {
					const api = await getApi();
					await adapter.unmount({
						api,
						widget,
						field: ctx.target,
						services,
						options,
						provider: options.provider
					});
					debug.log("Payment widget unmounted.", {
						moduleId: adapter.id,
						handle: options.handle
					});
				}
				if (registry[adapter.id]?.destroy === destroy) delete registry[adapter.id];
				debug.log("Payment module destroy complete.", {
					moduleId: adapter.id,
					handle: options.handle
				});
			};
			registry[adapter.id] = { destroy };
			return {
				destroy,
				onBeforeStage: async (stageCtx) => {
					if (customSetupResult?.onBeforeStage) {
						await customSetupResult.onBeforeStage(stageCtx);
						return;
					}
					if (stageCtx.stage !== "authorize" || stageCtx.action !== "submit") return;
					if (ctx.target.closest("[data-formie-page]")?.hasAttribute("data-formie-page-hidden")) return;
					await ensureMounted();
					const api = await getApi();
					if (adapter.onBeforeAuthorize) {
						if (!authorizeInFlight) authorizeInFlight = (async () => {
							return adapter.onBeforeAuthorize({
								api,
								widget,
								field: ctx.target,
								services,
								options,
								provider: options.provider,
								stageCtx
							});
						})().finally(() => {
							authorizeInFlight = null;
						});
						const ok = await authorizeInFlight;
						debug.log("onBeforeAuthorize resolved.", {
							moduleId: adapter.id,
							handle: options.handle,
							ok
						});
						if (!ok) {
							stageCtx.abort(options.transport.errorMessage);
							return;
						}
						return;
					}
					if (options.transport.requiredInputSuffixes.length === 0) return;
					const result = await waitForRequiredPaymentInputs(ctx.form || ctx.root, options.transport.requiredInputSuffixes, options.transport.waitForValueMs);
					if (!result.ok) {
						debug.warn("Required payment input(s) missing.", {
							moduleId: adapter.id,
							handle: options.handle,
							missingSuffix: result.missingSuffix
						});
						stageCtx.abort(options.transport.errorMessage);
					}
				},
				onAfterStage: async (stagePayload, result) => {
					if (stagePayload.stage !== "dispatch" || !adapter.onAfterSubmit) return;
					await adapter.onAfterSubmit({
						field: ctx.target,
						services,
						options,
						provider: options.provider,
						result
					});
				}
			};
		}
	};
}
//#endregion
//#region src/js/modules/payments/api.ts
var definePaymentModule = createManagedPaymentModule;
//#endregion
export { setFormHiddenState as a, dispatchPageClientEventForSubmit as c, clearSubmitLoading as i, applyPageState as n, setSubmitLoading as o, applySubmitResultState as r, syncPageTabErrors as s, definePaymentModule as t };
