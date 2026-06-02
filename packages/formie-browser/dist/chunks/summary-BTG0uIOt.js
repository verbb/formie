import { t as createDebug } from "./debug-KnZeKYBI.js";
import { i as toggleThemeClasses } from "./theme-classes-vSHpdCUO.js";
import { n as requestText } from "./http-18nn97DZ.js";
import { t as debounce } from "./async-B3DUf1GZ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as getModuleFieldContainers, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
import summaryCss from "#theme/fields/_summary.css?inline";
//#region src/js/modules/fields/summary.ts
var BLOCKS_SELECTOR = "[data-formie-summary-blocks]";
var CONTAINER_SELECTOR = "[data-formie-summary-container]";
var SUMMARY_ACTION = "formie/fields/get-summary-html";
var MODULE_ID = "summary";
var debug = createDebug("fields", "summary");
ensureModuleStyles(MODULE_ID, [summaryCss]);
function getSummaryRequestUrl() {
	const url = new URL(window.location.href);
	url.hash = "";
	return url.toString();
}
function getSummaryRequestState(field) {
	return { accessToken: field.querySelector("[data-formie-summary-token]")?.value?.trim() || null };
}
async function requestSummaryHtml(form, state, signal) {
	if (!state.accessToken) throw new Error("Summary field requires an access token.");
	const formData = new FormData(form);
	formData.set("action", SUMMARY_ACTION);
	formData.set("accessToken", state.accessToken);
	return requestText(getSummaryRequestUrl(), {
		method: "POST",
		body: formData,
		signal,
		headers: { Accept: "text/html" }
	});
}
function initSummaryField(field, root) {
	const form = field.closest("form");
	if (!(form instanceof HTMLFormElement)) {
		debug.warn("Missing form ancestor; skipping field.");
		return () => {};
	}
	let hasFetched = false;
	let isDirty = true;
	let isVisible = false;
	let dirtyVersion = 0;
	let requestVersion = 0;
	let activeRequest = null;
	const getBlocks = () => {
		const blocks = field.querySelector(BLOCKS_SELECTOR);
		return blocks instanceof HTMLElement ? blocks : null;
	};
	const getContainer = () => {
		const container = field.querySelector(CONTAINER_SELECTOR);
		return container instanceof HTMLElement ? container : null;
	};
	const setLoadingState = (isLoading) => {
		const blocks = getBlocks();
		if (!blocks) return;
		if (isLoading) {
			blocks.setAttribute("data-formie-loading", "true");
			blocks.setAttribute("aria-busy", "true");
			toggleThemeClasses(blocks, form, "loading", true);
			return;
		}
		blocks.removeAttribute("data-formie-loading");
		blocks.removeAttribute("aria-busy");
		toggleThemeClasses(blocks, form, "loading", false);
	};
	setLoadingState(!!getSummaryRequestState(field).accessToken);
	const queueFetch = () => {
		if (!isVisible || hasFetched && !isDirty) return;
		debug.log("Queueing fetch.");
		fetchSummary();
	};
	const fetchSummary = debounce(async () => {
		const state = getSummaryRequestState(field);
		if (!getBlocks() || !state.accessToken) {
			debug.warn("Missing state for fetch.", state);
			setLoadingState(false);
			return;
		}
		requestVersion += 1;
		const currentRequestVersion = requestVersion;
		const requestDirtyVersion = dirtyVersion;
		activeRequest?.abort();
		activeRequest = new AbortController();
		setLoadingState(true);
		try {
			const html = await requestSummaryHtml(form, state, activeRequest.signal);
			if (currentRequestVersion !== requestVersion) return;
			const container = getContainer();
			const nextMarkup = document.createElement("template");
			nextMarkup.innerHTML = html.trim();
			const nextContainer = nextMarkup.content.querySelector(CONTAINER_SELECTOR);
			if (container && nextContainer instanceof HTMLElement) container.replaceWith(nextContainer);
			else if (container) container.innerHTML = html;
			hasFetched = true;
			isDirty = dirtyVersion !== requestDirtyVersion;
			debug.log("Fetch complete.", {
				isDirty,
				dirtyVersion,
				requestVersion: currentRequestVersion
			});
			dispatchFieldEvent(field, MODULE_ID, "fetch-summary", {
				summary: field,
				html
			});
		} catch (error) {
			if (error instanceof DOMException && error.name === "AbortError") {
				debug.log("Fetch aborted.");
				return;
			}
			console.error("[formie] Failed to load summary field HTML.", error);
		} finally {
			if (currentRequestVersion === requestVersion) {
				setLoadingState(false);
				activeRequest = null;
				if (isDirty) queueFetch();
			}
		}
	}, 300);
	const markDirty = (event) => {
		const target = event?.target;
		if (target instanceof Node && field.contains(target)) return;
		isDirty = true;
		dirtyVersion += 1;
		debug.log("Marked dirty.", { dirtyVersion });
	};
	const handleFieldMutation = (event) => {
		markDirty(event);
		queueFetch();
	};
	const handleSubmitResult = () => {
		isDirty = true;
		debug.log("Submit result received; refreshing.");
		queueFetch();
	};
	const handlePageNavigate = () => {
		isDirty = true;
		debug.log("Page navigation received; refreshing.");
		queueFetch();
	};
	const observer = new IntersectionObserver((entries) => {
		isVisible = !!entries[0]?.isIntersecting;
		if (!isVisible) return;
		debug.log("Field became visible.");
		dispatchFieldEvent(field, MODULE_ID, "field-visible", { summary: field });
		queueFetch();
	}, {
		root: form,
		rootMargin: "50px"
	});
	observer.observe(field);
	form.addEventListener("input", handleFieldMutation);
	form.addEventListener("change", handleFieldMutation);
	root.addEventListener("formie:page:navigate:after", handlePageNavigate);
	root.addEventListener("formie:submit:result", handleSubmitResult);
	return () => {
		activeRequest?.abort();
		observer.disconnect();
		form.removeEventListener("input", handleFieldMutation);
		form.removeEventListener("change", handleFieldMutation);
		root.removeEventListener("formie:page:navigate:after", handlePageNavigate);
		root.removeEventListener("formie:submit:result", handleSubmitResult);
		debug.log("Field destroyed.");
	};
}
var summaryModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(BLOCKS_SELECTOR);
	},
	setup: async (ctx) => {
		const cleanups = getModuleFieldContainers(ctx).map((field) => {
			return initSummaryField(field, ctx.root);
		});
		debug.log("Module setup.", { fieldCount: cleanups.length });
		await ctx.emit("formie:module:summary:init", { count: cleanups.length });
		return { destroy: () => {
			cleanups.forEach((cleanup) => {
				cleanup();
			});
			debug.log("Module destroy.", { fieldCount: cleanups.length });
			ctx.emit("formie:module:summary:destroy", {});
		} };
	}
};
//#endregion
export { summaryModule };
