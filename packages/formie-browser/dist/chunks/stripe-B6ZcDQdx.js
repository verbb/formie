import { s as getPaymentProviderActionEventName } from "./event-names-DamGPtXR.js";
import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as removeThemeClasses, t as addThemeClasses } from "./theme-classes-vSHpdCUO.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import stripeCss from "#theme/integrations/_stripe.css?inline";
//#region src/js/modules/payments/stripe.ts
ensureModuleStyles("stripe", [stripeCss]);
var SCRIPT_ID = "FORMIE_STRIPE_SCRIPT";
var CONFIRM_EVENT = getPaymentProviderActionEventName("stripe", "confirm");
var PLACEHOLDER_SELECTOR = "[data-formie-stripe-elements-placeholder]";
var ZERO_DECIMAL_CURRENCIES = new Set([
	"BIF",
	"CLP",
	"DJF",
	"GNF",
	"JPY",
	"KMF",
	"KRW",
	"MGA",
	"PYG",
	"RWF",
	"UGX",
	"VND",
	"VUV",
	"XAF",
	"XOF",
	"XPF"
]);
function toStripeSubunitAmount(amount, currency) {
	if (ZERO_DECIMAL_CURRENCIES.has(currency.toUpperCase())) return Math.ceil(amount);
	return Math.ceil(amount * 100);
}
function clearPlaceholderError(placeholder, themeSource) {
	if (!placeholder) return;
	removeThemeClasses(placeholder, themeSource, "fieldErrors", "fieldError");
	placeholder.querySelectorAll("[data-payment-placeholder-error]").forEach((el) => {
		removeThemeClasses(el, themeSource, "fieldError");
	});
}
function renderPlaceholderLoading(placeholder, themeSource, message = "Loading payment options...") {
	if (!placeholder) return;
	clearPlaceholderError(placeholder, themeSource);
	placeholder.removeAttribute("hidden");
	placeholder.innerHTML = "";
	const spinner = document.createElement("div");
	spinner.className = "formie-loading";
	const text = document.createElement("div");
	text.textContent = message;
	placeholder.append(spinner, text);
}
function showPlaceholderMessage(placeholder, themeSource, message) {
	if (!placeholder) return;
	clearPlaceholderError(placeholder, themeSource);
	placeholder.removeAttribute("hidden");
	addThemeClasses(placeholder, themeSource, "fieldErrors");
	placeholder.innerHTML = "";
	const error = document.createElement("div");
	error.setAttribute("data-payment-placeholder-error", "");
	error.textContent = message;
	addThemeClasses(error, themeSource, "fieldError");
	placeholder.appendChild(error);
}
var stripeModule = definePaymentModule({
	id: "stripe",
	defaultRequiredInputSuffixes: ["stripePaymentIntentId"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		const publishableKey = provider.publishableKey;
		if (!publishableKey?.trim()) {
			console.error("[formie] Missing publishableKey for Stripe.");
			return null;
		}
		return (await loadScriptAndEnsureGlobal("Stripe", {
			id: SCRIPT_ID,
			src: "https://js.stripe.com/v3"
		}))(publishableKey);
	},
	mount: async (args) => {
		const { api, field, services, provider } = args;
		const placeholder = field.querySelector("[data-formie-stripe-elements]");
		const loadingPlaceholder = field.querySelector(PLACEHOLDER_SELECTOR);
		const stripeField = field;
		const themeSource = services.form || services.root;
		if (!placeholder || !api) return null;
		const initial = provider.initialPaymentInformation || {};
		const dynamicHandles = [initial.amount, initial.currency].map((value) => String(value ?? "").trim()).filter((value, index) => {
			return (index === 0 ? provider.amountType : provider.currencyType) === "dynamic" && value !== "";
		});
		const getResolvedPaymentInfo = () => {
			const amountResult = services.resolveAmount({ value: initial.amount });
			if (!amountResult.ok) return {
				ok: false,
				error: "error" in amountResult ? amountResult.error : "Provide a payment amount to proceed."
			};
			const currencyResult = services.resolveCurrency({ value: initial.currency });
			if (!currencyResult.ok) return {
				ok: false,
				error: "error" in currencyResult ? currencyResult.error : "Provide a payment currency to proceed."
			};
			const currencyCode = currencyResult.value.toLowerCase();
			const amountValue = provider.amountType === "dynamic" ? toStripeSubunitAmount(amountResult.value, currencyCode) : amountResult.value;
			return {
				ok: true,
				value: {
					...initial,
					capture_method: "automatic",
					mode: provider.paymentType === "subscription" ? "subscription" : "payment",
					appearance: {},
					amount: amountValue,
					currency: currencyCode
				}
			};
		};
		const destroyWidget = () => {
			try {
				stripeField.__formieStripeWidget?.paymentElement?.destroy?.();
			} catch (error) {}
			stripeField.__formieStripeWidget = null;
			stripeField.__formieStripeElements = void 0;
			placeholder.innerHTML = "";
		};
		const evaluateAndRender = () => {
			const resolved = getResolvedPaymentInfo();
			if (!resolved.ok) {
				destroyWidget();
				showPlaceholderMessage(loadingPlaceholder, themeSource, "error" in resolved ? resolved.error : "Unable to resolve payment details.");
				return;
			}
			try {
				if (stripeField.__formieStripeWidget && stripeField.__formieStripeElements) {
					stripeField.__formieStripeElements.update(resolved.value);
					clearPlaceholderError(loadingPlaceholder, themeSource);
					loadingPlaceholder?.setAttribute("hidden", "hidden");
					return;
				}
				renderPlaceholderLoading(loadingPlaceholder, themeSource);
				const elements = api.elements(resolved.value);
				const paymentElement = elements.create("payment", {});
				paymentElement.mount(placeholder);
				paymentElement.on?.("loaderror", (event) => {
					const message = event?.error?.message || "Unable to load payment options.";
					destroyWidget();
					showPlaceholderMessage(loadingPlaceholder, themeSource, message);
				});
				paymentElement.on?.("ready", () => {
					clearPlaceholderError(loadingPlaceholder, themeSource);
					loadingPlaceholder?.setAttribute("hidden", "hidden");
				});
				stripeField.__formieStripeElements = elements;
				stripeField.__formieStripeInstance = api;
				stripeField.__formieStripeWidget = {
					elements,
					paymentElement
				};
				if (!paymentElement.on) {
					clearPlaceholderError(loadingPlaceholder, themeSource);
					loadingPlaceholder?.setAttribute("hidden", "hidden");
				}
			} catch (error) {
				destroyWidget();
				showPlaceholderMessage(loadingPlaceholder, themeSource, error instanceof Error ? error.message : "Unable to initialize Stripe payment element.");
			}
		};
		stripeField.__formieStripeEvaluateAndRender = evaluateAndRender;
		stripeField.__formieStripeDynamicUnbind?.();
		if (dynamicHandles.length > 0) stripeField.__formieStripeDynamicUnbind = services.watchFieldValueChanges(dynamicHandles, () => {
			evaluateAndRender();
		}, 600);
		evaluateAndRender();
		return stripeField.__formieStripeWidget || null;
	},
	unmount: async (args) => {
		args.widget?.paymentElement?.destroy();
		const stripeField = args.field;
		stripeField.__formieStripeWidget = null;
		stripeField.__formieStripeElements = void 0;
		stripeField.__formieStripeInstance = null;
		stripeField.__formieStripeLastClientSecret = void 0;
		stripeField.__formieStripeEvaluateAndRender = null;
		stripeField.__formieStripeDynamicUnbind?.();
		stripeField.__formieStripeDynamicUnbind = null;
	},
	onBeforeAuthorize: async (args) => {
		const { widget, services, field } = args;
		const stripeField = field;
		let activeWidget = widget;
		if (!activeWidget?.elements) {
			stripeField.__formieStripeEvaluateAndRender?.();
			activeWidget = stripeField.__formieStripeWidget || null;
		}
		if (!activeWidget?.elements) return false;
		const result = await activeWidget.elements.submit();
		if (result?.error) {
			services.addError(result.error.message);
			return false;
		}
		return true;
	},
	setup: async (ctx) => {
		const { services, options } = ctx;
		const provider = options.provider;
		const field = ctx.target;
		const handler = async (event) => {
			try {
				const data = event.detail?.data;
				if (!data?.clientSecret) return;
				if (field.__formieStripeConfirming) return;
				if (field.__formieStripeLastClientSecret === data.clientSecret) return;
				const elements = field.__formieStripeElements;
				if (!elements) {
					services.addError("Stripe elements not ready for 3DS.");
					return;
				}
				const instance = field.__formieStripeInstance;
				const publishableKey = provider.publishableKey;
				if (!instance || !publishableKey) {
					services.addError("Stripe is not initialized.");
					return;
				}
				field.__formieStripeConfirming = true;
				const returnUrl = new URL(data.returnUrl || window.location.href);
				returnUrl.searchParams.set("origin", window.location.href);
				const result = await (data.type === "setup" ? instance.confirmSetup : instance.confirmPayment)({
					elements,
					clientSecret: data.clientSecret,
					redirect: "if_required",
					confirmParams: { return_url: returnUrl.toString() }
				});
				if (result?.error) {
					services.releaseSubmitLoading();
					services.addError(result.error.message);
					return;
				}
				if (data.subscriptionId) services.updateInputs("stripeSubscriptionId", data.subscriptionId);
				const pi = result && "paymentIntent" in result ? result.paymentIntent : null;
				const si = result && "setupIntent" in result ? result.setupIntent : null;
				if (pi?.id) services.updateInputs("stripePaymentIntentId", pi.id);
				else if (si?.id) services.updateInputs("stripePaymentIntentId", si.id);
				else {
					services.releaseSubmitLoading();
					services.addError("Stripe confirmation did not return an intent ID.");
					return;
				}
				field.__formieStripeLastClientSecret = data.clientSecret;
				services.triggerSubmit();
			} catch (error) {
				services.releaseSubmitLoading();
				services.addError(error instanceof Error ? error.message : "Unable to confirm Stripe payment.");
			} finally {
				field.__formieStripeConfirming = false;
			}
		};
		field.__formieStripeConfirmUnbind?.();
		field.__formieStripeConfirmUnbind = services.events.onForm(CONFIRM_EVENT, handler);
		return { destroy: () => {
			field.__formieStripeConfirmUnbind?.();
			field.__formieStripeConfirmUnbind = null;
		} };
	},
	onAfterSubmit: async (args) => {
		const stripeField = args.field;
		if (args.result?.ok && !args.result?.nextPage) {
			stripeField.__formieStripeWidget?.paymentElement?.destroy?.();
			stripeField.__formieStripeWidget = null;
			stripeField.__formieStripeElements = void 0;
			args.services.updateInputs(["stripePaymentIntentId", "stripeSubscriptionId"], "");
			stripeField.__formieStripeLastClientSecret = void 0;
		}
	}
});
//#endregion
export { stripeModule };
