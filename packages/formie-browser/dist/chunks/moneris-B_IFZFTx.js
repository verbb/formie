import { t as definePaymentModule } from "./api-DE7LfK-R.js";
//#region src/js/modules/payments/moneris.ts
var MONERIS_RESPONSE_MESSAGES = {
	"940": "Invalid profile ID (tokenization request).",
	"941": "Error generating token.",
	"942": "Invalid Profile ID or source URL.",
	"943": "Card data is invalid.",
	"944": "Invalid expiration date.",
	"945": "Invalid CVD."
};
var extractMonerisToken = (payload) => {
	const fromObject = (value) => {
		const token = String(value.dataKey ?? value.data_key ?? "").trim();
		const responseCode = String(value.responseCode ?? value.response_code ?? "").trim();
		const responseMessage = String(value.responseMessage ?? value.response_message ?? "").trim();
		const error = String(value.error ?? value.errorMessage ?? value.message ?? "").trim();
		if (token) return {
			token,
			error: ""
		};
		if (responseCode && responseCode !== "001") {
			const codeMessage = MONERIS_RESPONSE_MESSAGES[responseCode] || `Moneris response code ${responseCode}.`;
			return {
				token: "",
				error: responseMessage || codeMessage
			};
		}
		return {
			token: "",
			error
		};
	};
	if (payload && typeof payload === "object") return fromObject(payload);
	if (typeof payload !== "string") return {
		token: "",
		error: ""
	};
	const value = payload.trim();
	if (!value) return {
		token: "",
		error: ""
	};
	try {
		const decoded = JSON.parse(value);
		if (decoded && typeof decoded === "object") return fromObject(decoded);
	} catch {}
	if (value.includes("=")) {
		const params = new URLSearchParams(value);
		const token = (params.get("dataKey") || params.get("data_key") || "").trim();
		const responseCode = (params.get("responseCode") || params.get("response_code") || "").trim();
		const responseMessage = (params.get("responseMessage") || params.get("response_message") || "").trim();
		const error = (params.get("error") || params.get("message") || "").trim();
		if (token) return {
			token,
			error: ""
		};
		if (responseCode && responseCode !== "001") {
			const codeMessage = MONERIS_RESPONSE_MESSAGES[responseCode] || `Moneris response code ${responseCode}.`;
			return {
				token: "",
				error: responseMessage || codeMessage
			};
		}
		return {
			token: "",
			error
		};
	}
	if (/^[A-Za-z0-9._-]{8,}$/.test(value)) return {
		token: value,
		error: ""
	};
	return {
		token: "",
		error: ""
	};
};
var monerisModule = definePaymentModule({
	id: "moneris",
	defaultRequiredInputSuffixes: ["monerisTokenId"],
	load: async () => null,
	onBeforeAuthorize: async (args) => {
		const { field, services, options } = args;
		const fieldState = field;
		const endpointUrl = (options.provider.endpointUrl || "").trim();
		const iframe = field.querySelector("[data-formie-moneris-frame]");
		let endpointOrigin = "";
		try {
			endpointOrigin = new URL(endpointUrl, window.location.origin).origin;
		} catch {
			endpointOrigin = "";
		}
		if (!iframe?.contentWindow || !endpointOrigin) {
			services.addError("Moneris frame or endpoint is missing.");
			return false;
		}
		fieldState.__formieMonerisAuthorizeCleanup?.();
		const requestId = (fieldState.__formieMonerisAuthorizeRequestId || 0) + 1;
		fieldState.__formieMonerisAuthorizeRequestId = requestId;
		return new Promise((resolve) => {
			let resolved = false;
			let lastError = "";
			let timeout = 0;
			const cleanup = () => {
				window.removeEventListener("message", messageHandler);
				window.clearTimeout(timeout);
				if (fieldState.__formieMonerisAuthorizeCleanup === cleanup) fieldState.__formieMonerisAuthorizeCleanup = null;
			};
			const messageHandler = (event) => {
				if (resolved || requestId !== fieldState.__formieMonerisAuthorizeRequestId) return;
				if (event.origin !== endpointOrigin) return;
				const result = extractMonerisToken(event.data);
				if (result.token) {
					resolved = true;
					cleanup();
					services.updateInputs("monerisTokenId", result.token);
					resolve(true);
				} else if (result.error) lastError = result.error;
			};
			fieldState.__formieMonerisAuthorizeCleanup = cleanup;
			timeout = window.setTimeout(() => {
				if (resolved) return;
				resolved = true;
				cleanup();
				services.addError(lastError || "Moneris tokenization timed out. Please try again.");
				resolve(false);
			}, 1e4);
			window.addEventListener("message", messageHandler);
			try {
				iframe.contentWindow?.postMessage("tokenize", endpointOrigin);
			} catch {
				resolved = true;
				cleanup();
				services.addError("Moneris tokenization could not be started.");
				resolve(false);
			}
		});
	},
	setup: async (ctx) => {
		const { services } = ctx;
		const messageHandler = (event) => {
			if (!event.origin.includes("moneris")) return;
			const result = extractMonerisToken(event.data);
			if (result.token) services.updateInputs("monerisTokenId", result.token);
		};
		const unbind = services.events.onRoot("message", messageHandler);
		return { destroy: () => {
			unbind();
			const fieldState = ctx.target;
			fieldState.__formieMonerisAuthorizeCleanup?.();
			fieldState.__formieMonerisAuthorizeCleanup = null;
		} };
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("monerisTokenId", "");
	}
});
//#endregion
export { monerisModule };
