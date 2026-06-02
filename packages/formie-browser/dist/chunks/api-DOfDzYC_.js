import { a as getFormStateEventName } from "./event-names-DamGPtXR.js";
import { t as createDebug } from "./debug-KnZeKYBI.js";
import { t as addThemeClasses } from "./theme-classes-vSHpdCUO.js";
import { i as t } from "./i18n-vUh-KGiH.js";
import { n as sleep, t as debounce } from "./async-B3DUf1GZ.js";
//#region src/js/modules/captchas/constants.ts
var DEFAULT_WAIT_FOR_VALUE_MS = 2e3;
var CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS = 5e3;
var CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS = 5e3;
var CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS = 12e4;
//#endregion
//#region src/js/modules/captchas/utils.ts
function getScriptAttributes(loadingMethod) {
	const normalized = String(loadingMethod || "asyncDefer").toLowerCase();
	return {
		async: normalized.includes("async"),
		defer: normalized.includes("defer")
	};
}
function getInputValue(root, name) {
	const inputs = Array.from(root.querySelectorAll(`input[name="${name}"], textarea[name="${name}"]`));
	for (const input of inputs) {
		const value = String(input.value || "").trim();
		if (value !== "") return value;
	}
	return "";
}
function hasCaptchaValue(root, names) {
	return names.some((name) => {
		return getInputValue(root, name) !== "";
	});
}
function clearCaptchaValues(root, names) {
	names.forEach((name) => {
		Array.from(root.querySelectorAll(`input[name="${name}"], textarea[name="${name}"]`)).forEach((input) => {
			input.value = "";
		});
	});
}
function ensureCaptchaValueInput(root, name, { value = "", container } = {}) {
	let input = root.querySelector(`input[name="${name}"]`);
	if (!input) {
		input = document.createElement("input");
		input.type = "hidden";
		input.name = name;
		(container || (root instanceof HTMLElement ? root : null))?.appendChild(input);
	}
	input.value = value;
	return input;
}
async function waitForCaptchaValue(root, names, waitForValueMs) {
	if (hasCaptchaValue(root, names)) return true;
	const deadline = Date.now() + Math.max(waitForValueMs, 0);
	while (Date.now() < deadline) {
		await sleep(120);
		if (hasCaptchaValue(root, names)) return true;
	}
	return false;
}
//#endregion
//#region src/js/modules/captchas/host.ts
var CAPTCHA_OPTION_KEYS = new Set([
	"handle",
	"placeholderSelector",
	"errorMessage",
	"sessionKey",
	"value"
]);
var CAPTCHA_ERROR_SELECTOR = "[data-formie-captcha-error-container]";
var CAPTCHA_VISIBILITY_EVENTS = [
	"formie:page:navigate",
	"formie:page:navigate:after",
	"formie:submit:result"
];
function bindDomEvent(target, eventName, callback) {
	target.addEventListener(eventName, callback);
	return () => {
		target.removeEventListener(eventName, callback);
	};
}
function queryCaptchaPlaceholders(root, selector) {
	if (root instanceof HTMLElement && root.matches(selector)) return [root, ...Array.from(root.querySelectorAll(selector))];
	return Array.from(root.querySelectorAll(selector));
}
function isElementVisible(target) {
	if (!(target instanceof HTMLElement)) return false;
	if (!target.isConnected) return false;
	if (target.hidden || target.closest("[hidden]")) return false;
	if (target.closest("[data-formie-page-hidden]") || target.closest("[aria-hidden=\"true\"]")) return false;
	const style = window.getComputedStyle(target);
	return style.display !== "none" && style.visibility !== "hidden";
}
function getPrimaryPlaceholder(root, selector) {
	const placeholders = queryCaptchaPlaceholders(root, selector);
	return placeholders.find((placeholder) => isElementVisible(placeholder)) || placeholders[0] || null;
}
function createCaptchaContainer(placeholder) {
	placeholder.innerHTML = "";
	const container = document.createElement("div");
	placeholder.appendChild(container);
	return container;
}
function clearCaptchaError(placeholder) {
	placeholder?.querySelector(CAPTCHA_ERROR_SELECTOR)?.remove();
}
function showCaptchaError(placeholder, message, themeSource) {
	if (!placeholder) return;
	clearCaptchaError(placeholder);
	const container = document.createElement("div");
	container.setAttribute("data-formie-captcha-error-container", "");
	container.setAttribute("aria-live", "polite");
	container.setAttribute("aria-atomic", "true");
	addThemeClasses(container, themeSource || placeholder, "fieldErrors");
	const error = document.createElement("div");
	error.setAttribute("data-formie-captcha-error", "");
	error.setAttribute("role", "alert");
	addThemeClasses(error, themeSource || placeholder, "fieldError");
	error.textContent = message;
	container.appendChild(error);
	placeholder.appendChild(container);
}
function parseRefreshTokensEvent(event) {
	const detail = event instanceof CustomEvent ? event.detail : null;
	if (!detail || typeof detail !== "object") return null;
	return detail;
}
function getCaptchaRefreshEntry(payload, providerHandle) {
	if (!payload?.captchas || typeof payload.captchas !== "object") return null;
	const entry = payload.captchas[providerHandle];
	if (!entry || typeof entry !== "object") return null;
	return entry;
}
function observeVisiblePlaceholders(root, selector, onShow, onHide) {
	const visible = /* @__PURE__ */ new Set();
	const reconcileNow = () => {
		const placeholders = queryCaptchaPlaceholders(root, selector);
		const nextVisible = new Set(placeholders.filter((placeholder) => isElementVisible(placeholder)));
		placeholders.forEach((placeholder) => {
			if (nextVisible.has(placeholder) && !visible.has(placeholder)) {
				visible.add(placeholder);
				onShow(placeholder);
			}
		});
		Array.from(visible).forEach((placeholder) => {
			if (nextVisible.has(placeholder)) return;
			visible.delete(placeholder);
			onHide(placeholder);
		});
	};
	const reconcile = debounce(reconcileNow, 20);
	const mutationObserver = new MutationObserver(() => {
		reconcile();
	});
	mutationObserver.observe(root, {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: [
			"class",
			"style",
			"hidden",
			"aria-hidden",
			"data-formie-page-hidden"
		]
	});
	const cleanups = [bindDomEvent(window, "resize", () => {
		reconcile();
	}), ...CAPTCHA_VISIBILITY_EVENTS.map((eventName) => {
		return bindDomEvent(root, eventName, () => {
			reconcile();
		});
	})];
	reconcileNow();
	return {
		cleanup: () => {
			mutationObserver.disconnect();
			cleanups.forEach((cleanup) => {
				cleanup();
			});
			Array.from(visible).forEach((placeholder) => {
				onHide(placeholder);
			});
			visible.clear();
		},
		reconcile,
		getVisible: () => {
			return queryCaptchaPlaceholders(root, selector).filter((placeholder) => isElementVisible(placeholder));
		}
	};
}
function getCaptchaProviderHandle(id, options) {
	return (typeof options.handle === "string" && options.handle.trim() !== "" ? options.handle.trim() : "") || id;
}
function normalizeCaptchaModuleOptions(id, rawOptions, { defaultPlaceholderSelector, defaultTokenFieldNames = [], defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS }) {
	const options = rawOptions || {};
	const provider = Object.entries(options).reduce((carry, [key, value]) => {
		if (CAPTCHA_OPTION_KEYS.has(key)) return carry;
		carry[key] = value;
		return carry;
	}, {});
	const tokenFieldNames = defaultTokenFieldNames.map(String).filter(Boolean);
	const waitForValueMs = Number(defaultWaitForValueMs);
	const placeholderSelector = typeof options.placeholderSelector === "string" && options.placeholderSelector.trim() !== "" ? options.placeholderSelector.trim() : defaultPlaceholderSelector;
	const errorMessage = typeof options.errorMessage === "string" && options.errorMessage.trim() !== "" ? options.errorMessage.trim() : t("Captcha challenge must be completed.");
	const sessionKey = typeof options.sessionKey === "string" && options.sessionKey.trim() !== "" ? options.sessionKey.trim() : null;
	const value = typeof options.value === "string" ? options.value : null;
	return {
		handle: getCaptchaProviderHandle(id, options),
		ui: {
			placeholderSelector,
			errorMessage
		},
		transport: {
			tokenFieldNames,
			waitForValueMs: Number.isFinite(waitForValueMs) ? waitForValueMs : defaultWaitForValueMs,
			sessionKey,
			value
		},
		provider
	};
}
function createCaptchaHostServices(ctx, options) {
	const tokenRoot = ctx.form || ctx.root;
	const placeholderSelector = options.ui.placeholderSelector;
	const providerHandle = options.handle;
	return {
		form: ctx.form,
		root: ctx.root,
		placeholder: {
			query: () => queryCaptchaPlaceholders(ctx.root, placeholderSelector),
			getPrimary: () => getPrimaryPlaceholder(ctx.root, placeholderSelector),
			observe: (onShow, onHide) => observeVisiblePlaceholders(ctx.root, placeholderSelector, onShow, onHide),
			createContainer: (placeholder) => createCaptchaContainer(placeholder),
			clear: (placeholder) => {
				if (!placeholder) return;
				clearCaptchaError(placeholder);
				placeholder.innerHTML = "";
			}
		},
		errors: {
			getDefaultMessage: () => options.ui.errorMessage,
			show: (message, placeholder) => {
				showCaptchaError(placeholder || getPrimaryPlaceholder(ctx.root, placeholderSelector), message || options.ui.errorMessage, ctx.form || ctx.root);
			},
			clear: (placeholder) => {
				clearCaptchaError(placeholder || getPrimaryPlaceholder(ctx.root, placeholderSelector));
			}
		},
		tokens: {
			names: options.transport.tokenFieldNames,
			has: (names = options.transport.tokenFieldNames, root = tokenRoot) => hasCaptchaValue(root, names),
			read: (name = options.transport.tokenFieldNames[0], root = tokenRoot) => name ? getInputValue(root, name) : "",
			write: (value, { names = options.transport.tokenFieldNames, root = tokenRoot, container = ctx.form } = {}) => {
				names.forEach((name) => {
					ensureCaptchaValueInput(root, name, {
						value,
						container
					});
				});
			},
			clear: (names = options.transport.tokenFieldNames, root = tokenRoot) => {
				clearCaptchaValues(root, names);
			},
			wait: (timeoutMs = options.transport.waitForValueMs, names = options.transport.tokenFieldNames, root = tokenRoot) => {
				return waitForCaptchaValue(root, names, timeoutMs);
			}
		},
		refresh: {
			providerHandle,
			onTokensRefreshed: (callback) => {
				const handlers = ["formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"].map((eventName) => {
					return bindDomEvent(ctx.root, eventName, (event) => {
						const entry = getCaptchaRefreshEntry(parseRefreshTokensEvent(event), providerHandle);
						if (entry) callback(entry);
					});
				});
				return () => {
					handlers.forEach((cleanup) => {
						cleanup();
					});
				};
			}
		},
		events: {
			onRoot: (eventName, callback) => bindDomEvent(ctx.root, eventName, callback),
			onForm: (eventName, callback) => {
				if (!ctx.form) return () => {};
				return bindDomEvent(ctx.form, eventName, callback);
			}
		}
	};
}
//#endregion
//#region src/js/modules/captchas/factories.ts
var debug = createDebug("captchas");
function createCaptchaModule({ id, defaultPlaceholderSelector, defaultTokenFieldNames = [], defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS, setup }) {
	return {
		id,
		kind: "captcha",
		match: () => true,
		setup: async (ctx) => {
			const options = normalizeCaptchaModuleOptions(id, ctx.options || {}, {
				defaultPlaceholderSelector,
				defaultTokenFieldNames,
				defaultWaitForValueMs
			});
			debug.log("Setup module.", {
				moduleId: id,
				placeholderSelector: options.ui.placeholderSelector,
				tokenFieldNames: options.transport.tokenFieldNames
			});
			const services = createCaptchaHostServices(ctx, options);
			return setup({
				...ctx,
				options,
				services
			});
		}
	};
}
function createPassiveCaptchaModule({ id, defaultPlaceholderSelector, defaultTokenFieldNames = [], defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS }) {
	return createCaptchaModule({
		id,
		defaultPlaceholderSelector,
		defaultTokenFieldNames,
		defaultWaitForValueMs,
		setup: async ({ services, options, root }) => {
			const cleanups = [];
			let activePlaceholder = services.placeholder.getPrimary();
			let sessionKey = options.transport.sessionKey;
			let value = options.transport.value || "";
			const renderPlaceholder = (placeholder) => {
				if (!placeholder || !sessionKey) return;
				placeholder.innerHTML = "";
				ensureCaptchaValueInput(placeholder, sessionKey, {
					value,
					container: placeholder
				});
			};
			const visibility = services.placeholder.observe((placeholder) => {
				activePlaceholder = placeholder;
				debug.log("Passive placeholder visible.", { moduleId: id });
				renderPlaceholder(placeholder);
			}, (placeholder) => {
				if (activePlaceholder === placeholder) activePlaceholder = services.placeholder.getPrimary();
				placeholder.innerHTML = "";
			});
			cleanups.push(visibility.cleanup);
			renderPlaceholder(activePlaceholder);
			cleanups.push(services.refresh.onTokensRefreshed((entry) => {
				sessionKey = typeof entry.sessionKey === "string" && entry.sessionKey.trim() !== "" ? entry.sessionKey.trim() : sessionKey;
				value = typeof entry.value === "string" ? entry.value : "";
				const placeholder = services.placeholder.getPrimary() || activePlaceholder;
				activePlaceholder = placeholder;
				renderPlaceholder(placeholder);
			}));
			return {
				destroy: () => {
					cleanups.forEach((cleanup) => {
						cleanup();
					});
				},
				onBeforeStage: async (stageCtx) => {
					if (stageCtx.stage !== "screen" || stageCtx.action !== "submit") return;
					const tokenFieldNames = sessionKey ? [sessionKey] : options.transport.tokenFieldNames;
					if (tokenFieldNames.length === 0) return;
					if (!await waitForCaptchaValue(root, tokenFieldNames, options.transport.waitForValueMs)) {
						const message = services.errors.getDefaultMessage();
						services.errors.show(message, activePlaceholder);
						debug.warn("Passive captcha missing token.", {
							moduleId: id,
							tokenFieldNames
						});
						stageCtx.abort(message);
					}
				}
			};
		}
	});
}
function createManagedCaptchaModule(adapter) {
	return createCaptchaModule({
		id: adapter.id,
		defaultPlaceholderSelector: adapter.defaultPlaceholderSelector,
		defaultTokenFieldNames: adapter.defaultTokenFieldNames,
		setup: async (ctx) => {
			const cleanups = [];
			const mountedWidgets = /* @__PURE__ */ new Map();
			const mountPromises = /* @__PURE__ */ new Map();
			let activePlaceholder = ctx.services.placeholder.getPrimary();
			let destroyed = false;
			let apiPromise = null;
			const getApi = async () => {
				if (!apiPromise) {
					debug.log("Loading captcha provider API.", { moduleId: adapter.id });
					apiPromise = adapter.load(ctx);
				}
				return apiPromise;
			};
			const unmountPlaceholder = async (placeholder) => {
				const widget = mountedWidgets.get(placeholder);
				ctx.services.errors.clear(placeholder);
				if (!widget) {
					placeholder.innerHTML = "";
					return;
				}
				const api = await getApi();
				if (adapter.unmount) await adapter.unmount({
					api,
					widget,
					placeholder,
					services: ctx.services,
					options: ctx.options,
					provider: ctx.options.provider
				});
				mountedWidgets.delete(placeholder);
				placeholder.innerHTML = "";
				ctx.services.tokens.clear();
				debug.log("Unmounted captcha placeholder widget.", { moduleId: adapter.id });
				if (activePlaceholder === placeholder) activePlaceholder = ctx.services.placeholder.getPrimary();
			};
			const mountPlaceholder = async (placeholder) => {
				if (destroyed || mountedWidgets.has(placeholder) || mountPromises.has(placeholder)) return;
				const promise = (async () => {
					const api = await getApi();
					if (destroyed || mountedWidgets.has(placeholder)) return;
					const container = ctx.services.placeholder.createContainer(placeholder);
					const widget = await adapter.mount({
						api,
						placeholder,
						container,
						services: ctx.services,
						options: ctx.options,
						provider: ctx.options.provider
					});
					mountedWidgets.set(placeholder, widget);
					activePlaceholder = placeholder;
					debug.log("Mounted captcha placeholder widget.", { moduleId: adapter.id });
				})().finally(() => {
					mountPromises.delete(placeholder);
				});
				mountPromises.set(placeholder, promise);
				await promise;
			};
			const visibility = ctx.services.placeholder.observe((placeholder) => {
				activePlaceholder = placeholder;
				mountPlaceholder(placeholder);
			}, (placeholder) => {
				unmountPlaceholder(placeholder);
			});
			cleanups.push(visibility.cleanup);
			const rerenderVisiblePlaceholders = async (reason) => {
				const placeholdersToMount = visibility.getVisible();
				if (adapter.reset) {
					const api = await getApi();
					for (const placeholder of placeholdersToMount) {
						const widget = mountedWidgets.get(placeholder);
						if (!widget) {
							await mountPlaceholder(placeholder);
							continue;
						}
						await adapter.reset({
							api,
							widget,
							placeholder,
							services: ctx.services,
							options: ctx.options,
							provider: ctx.options.provider,
							reason
						});
						ctx.services.tokens.clear();
						ctx.services.errors.clear(placeholder);
					}
					visibility.reconcile();
					return;
				}
				for (const placeholder of Array.from(mountedWidgets.keys())) await unmountPlaceholder(placeholder);
				for (const placeholder of placeholdersToMount) await mountPlaceholder(placeholder);
				visibility.reconcile();
			};
			cleanups.push(ctx.services.events.onRoot("formie:submit:result", (event) => {
				const detail = event instanceof CustomEvent ? event.detail : null;
				if (detail?.stage === "validate") return;
				if (detail?.ok === false && detail?.stage === "screen") return;
				if (detail?.ok === true) return;
				rerenderVisiblePlaceholders("submit-result");
			}));
			if (ctx.form) cleanups.push(ctx.services.events.onForm(getFormStateEventName("reset"), () => {
				activePlaceholder = ctx.services.placeholder.getPrimary() || activePlaceholder;
				window.setTimeout(() => {
					rerenderVisiblePlaceholders("reset-state");
				}, 0);
			}));
			return {
				destroy: async () => {
					destroyed = true;
					cleanups.forEach((cleanup) => {
						cleanup();
					});
					for (const placeholder of Array.from(mountedWidgets.keys())) await unmountPlaceholder(placeholder);
				},
				onBeforeStage: async (stageCtx) => {
					if (stageCtx.stage !== "screen" || stageCtx.action !== "submit") return;
					const visiblePlaceholders = visibility.getVisible();
					if (visiblePlaceholders.length === 0) return;
					let placeholder = visiblePlaceholders.find((candidate) => candidate === activePlaceholder) || visiblePlaceholders[0];
					await mountPlaceholder(placeholder);
					placeholder = activePlaceholder || placeholder;
					ctx.services.errors.clear(placeholder);
					const widget = mountedWidgets.get(placeholder);
					if (!widget) {
						const message = ctx.services.errors.getDefaultMessage();
						ctx.services.errors.show(message, placeholder);
						debug.warn("Captcha widget unavailable at screen stage.", { moduleId: adapter.id });
						stageCtx.abort(message);
						return;
					}
					const api = await getApi();
					await adapter.screen({
						api,
						widget,
						placeholder,
						services: ctx.services,
						options: ctx.options,
						provider: ctx.options.provider,
						stageCtx
					});
				}
			};
		}
	});
}
//#endregion
//#region src/js/modules/captchas/api.ts
var defineCaptchaModule = createManagedCaptchaModule;
var definePassiveCaptchaModule = createPassiveCaptchaModule;
//#endregion
export { CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS as a, CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS as i, definePassiveCaptchaModule as n, CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS as o, getScriptAttributes as r, defineCaptchaModule as t };
