import { s as getPaymentProviderActionEventName } from "./event-names-DamGPtXR.js";
import { t as createDebug } from "./debug-KnZeKYBI.js";
import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/payments/opayo.ts
var SCRIPT_ID = "FORMIE_OPAYO_SCRIPT";
var SCRIPT_SRC_LIVE = "https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js";
var SCRIPT_SRC_SANDBOX = "https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js";
var debug = createDebug("payments", "opayo");
var CHALLENGE_EVENT = getPaymentProviderActionEventName("opayo", "challenge");
var CHALLENGE_RESPONSE_MESSAGE = "formie:payment:opayo:challenge:response";
var opayoModule = definePaymentModule({
	id: "opayo",
	defaultRequiredInputSuffixes: ["opayoTokenId"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		await loadScriptAndEnsureGlobal("sagepayOwnForm", {
			id: SCRIPT_ID,
			src: Boolean(provider.useSandbox) ? SCRIPT_SRC_SANDBOX : SCRIPT_SRC_LIVE,
			timeoutMs: 1e4
		});
		return null;
	},
	onBeforeAuthorize: async (args) => {
		const { field, services, options } = args;
		const provider = options.provider;
		const handle = provider.handle || "opayo";
		const form = services.form;
		if (!form?.action) {
			services.addError("Form action is missing.");
			debug.warn("Missing form action before authorize.");
			return false;
		}
		const sagepayOwnForm = window.sagepayOwnForm;
		if (!sagepayOwnForm) {
			services.addError("Opayo script failed to load.");
			debug.warn("sagepayOwnForm global not available.");
			return false;
		}
		const cardholderName = field.querySelector("[data-opayo-card=\"cardholder-name\"]")?.value ?? "";
		let cardNumber = field.querySelector("[data-opayo-card=\"card-number\"]")?.value ?? "";
		let expiryDate = field.querySelector("[data-opayo-card=\"expiry-date\"]")?.value ?? "";
		const securityCode = field.querySelector("[data-opayo-card=\"security-code\"]")?.value ?? "";
		cardNumber = cardNumber.replace(/[\s/]/g, "");
		expiryDate = expiryDate.replace(/[\s/]/g, "");
		return new Promise((resolve) => {
			const formData = new FormData();
			formData.append("action", "formie/payment-webhooks/process-callback");
			formData.append("merchantSessionKey", "true");
			formData.append("handle", handle);
			formData.append("sessionToken", provider.sessionToken || "");
			fetch(form.action, {
				method: "POST",
				body: formData
			}).then(async (res) => {
				if (res.status < 200 || res.status >= 300) {
					services.addError(`${res.status}: ${res.statusText}`);
					debug.warn("Merchant session request failed.", {
						status: res.status,
						statusText: res.statusText
					});
					resolve(false);
					return;
				}
				try {
					const merchantSessionKey = (await res.json()).merchantSessionKey;
					if (!merchantSessionKey) {
						services.addError("Unable to get merchant session.");
						debug.warn("merchantSessionKey missing in callback response.");
						resolve(false);
						return;
					}
					sagepayOwnForm({ merchantSessionKey }).tokeniseCardDetails({
						cardDetails: {
							cardholderName,
							cardNumber,
							expiryDate,
							securityCode
						},
						onTokenised: (result) => {
							if (result.success && result.cardIdentifier) {
								services.updateInputs("opayoTokenId", result.cardIdentifier);
								services.updateInputs("opayoSessionKey", merchantSessionKey);
								debug.log("Tokenization succeeded.", { hasCardIdentifier: !!result.cardIdentifier });
								resolve(true);
							} else {
								services.addError(result.errors?.[0]?.message || "Tokenization failed.");
								debug.warn("Tokenization failed.", result);
								resolve(false);
							}
						}
					});
				} catch {
					services.addError("Unable to parse merchant session response.");
					debug.warn("Failed to parse merchant session response.");
					resolve(false);
				}
			}).catch(() => {
				services.addError("Network error. Please try again.");
				debug.warn("Network error requesting merchant session.");
				resolve(false);
			});
		});
	},
	setup: async (ctx) => {
		const { services } = ctx;
		ctx.target;
		let activeDialog = null;
		let handling3DSResponse = false;
		const closeDialog = () => {
			if (activeDialog?.parentNode) activeDialog.parentNode.removeChild(activeDialog);
			activeDialog = null;
		};
		const unbind3DS = services.events.onForm(CHALLENGE_EVENT, ((event) => {
			const data = event.detail?.data;
			if (!data?.acsUrl || !data?.creq) return;
			handling3DSResponse = false;
			debug.log("Received payment challenge event.", {
				hasAcsUrl: !!data.acsUrl,
				hasCreq: !!data.creq
			});
			const md = (services.form?.querySelector("input[name*=\"opayoSessionKey\"]"))?.value || "";
			const dialog = document.createElement("div");
			dialog.className = "formie-modal";
			dialog.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`;
			dialog.innerHTML = `
                <div class="formie-modal-backdrop" data-dialog-close></div>
                <div class="formie-modal-content">
                    <div class="formie-loading formie-loading-large" style="--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;"></div>
                    <iframe width="100%" height="100%" style="width: 100%; height: 100%; position: relative; z-index: 1;"></iframe>
                </div>
            `;
			const iframe = dialog.querySelector("iframe");
			const esc = (s) => s.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
			const callbackUrl = data.returnUrl || data.redirectUrl || "";
			const html = `<form action="${esc(data.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${esc(data.creq || "")}" />
                <input type="hidden" name="threeDSSessionData" value="${esc(data.threeDSSessionData || "")}" />
                <input type="hidden" name="MD" value="${esc(md)}" />
                <input type="hidden" name="TermUrl" value="${esc(callbackUrl)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${esc(callbackUrl)}" />
            </form><script>document.forms[0].submit();<\/script>`;
			closeDialog();
			document.body.appendChild(dialog);
			activeDialog = dialog;
			if (iframe?.contentWindow) {
				iframe.contentWindow.document.open();
				iframe.contentWindow.document.write(html);
				iframe.contentWindow.document.close();
			}
		}));
		const messageHandler = (event) => {
			if (event.data?.message !== CHALLENGE_RESPONSE_MESSAGE) return;
			if (!activeDialog) {
				debug.log("Ignoring 3DS response without active dialog.");
				return;
			}
			if (handling3DSResponse) {
				debug.warn("Ignoring duplicate 3DS response while processing.");
				return;
			}
			handling3DSResponse = true;
			debug.log("Received payment challenge response message.", event.data?.value);
			closeDialog();
			services.removeError();
			if (event.data?.value?.error) {
				services.addError(event.data.value.error.message);
				services.releaseSubmitLoading();
				handling3DSResponse = false;
				return;
			}
			services.updateInputs("opayo3DSComplete", event.data.value?.transactionId ?? "");
			services.triggerSubmit();
		};
		window.addEventListener("message", messageHandler);
		return { destroy: () => {
			unbind3DS();
			window.removeEventListener("message", messageHandler);
			closeDialog();
			handling3DSResponse = false;
		} };
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs([
			"opayoTokenId",
			"opayoSessionKey",
			"opayo3DSComplete"
		], "");
	}
});
//#endregion
export { opayoModule };
