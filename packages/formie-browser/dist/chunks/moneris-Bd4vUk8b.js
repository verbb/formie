import { t as e } from "./api-DztsCJ4r.js";
//#region src/js/modules/payments/moneris.ts
var t = {
	940: "Invalid profile ID (tokenization request).",
	941: "Error generating token.",
	942: "Invalid Profile ID or source URL.",
	943: "Card data is invalid.",
	944: "Invalid expiration date.",
	945: "Invalid CVD."
}, n = (e) => {
	let n = (e) => {
		let n = String(e.dataKey ?? e.data_key ?? "").trim(), r = String(e.responseCode ?? e.response_code ?? "").trim(), i = String(e.responseMessage ?? e.response_message ?? "").trim(), a = String(e.error ?? e.errorMessage ?? e.message ?? "").trim();
		if (n) return {
			token: n,
			error: ""
		};
		if (r && r !== "001") {
			let e = t[r] || `Moneris response code ${r}.`;
			return {
				token: "",
				error: i || e
			};
		}
		return {
			token: "",
			error: a
		};
	};
	if (e && typeof e == "object") return n(e);
	if (typeof e != "string") return {
		token: "",
		error: ""
	};
	let r = e.trim();
	if (!r) return {
		token: "",
		error: ""
	};
	try {
		let e = JSON.parse(r);
		if (e && typeof e == "object") return n(e);
	} catch {}
	if (r.includes("=")) {
		let e = new URLSearchParams(r), n = (e.get("dataKey") || e.get("data_key") || "").trim(), i = (e.get("responseCode") || e.get("response_code") || "").trim(), a = (e.get("responseMessage") || e.get("response_message") || "").trim(), o = (e.get("error") || e.get("message") || "").trim();
		if (n) return {
			token: n,
			error: ""
		};
		if (i && i !== "001") {
			let e = t[i] || `Moneris response code ${i}.`;
			return {
				token: "",
				error: a || e
			};
		}
		return {
			token: "",
			error: o
		};
	}
	return /^[A-Za-z0-9._-]{8,}$/.test(r) ? {
		token: r,
		error: ""
	} : {
		token: "",
		error: ""
	};
}, r = e({
	id: "moneris",
	defaultRequiredInputSuffixes: ["monerisTokenId"],
	load: async () => null,
	onBeforeAuthorize: async (e) => {
		let { field: t, services: r, options: i } = e, a = t, o = (i.provider.endpointUrl || "").trim(), s = t.querySelector("[data-formie-moneris-frame]"), c = "";
		try {
			c = new URL(o, window.location.origin).origin;
		} catch {
			c = "";
		}
		if (!s?.contentWindow || !c) return r.addError("Moneris frame or endpoint is missing."), !1;
		a.__formieMonerisAuthorizeCleanup?.();
		let l = (a.__formieMonerisAuthorizeRequestId || 0) + 1;
		return a.__formieMonerisAuthorizeRequestId = l, new Promise((e) => {
			let t = !1, i = "", o = 0, u = () => {
				window.removeEventListener("message", d), window.clearTimeout(o), a.__formieMonerisAuthorizeCleanup === u && (a.__formieMonerisAuthorizeCleanup = null);
			}, d = (o) => {
				if (t || l !== a.__formieMonerisAuthorizeRequestId || o.origin !== c) return;
				let s = n(o.data);
				s.token ? (t = !0, u(), r.updateInputs("monerisTokenId", s.token), e(!0)) : s.error && (i = s.error);
			};
			a.__formieMonerisAuthorizeCleanup = u, o = window.setTimeout(() => {
				t || (t = !0, u(), r.addError(i || "Moneris tokenization timed out. Please try again."), e(!1));
			}, 1e4), window.addEventListener("message", d);
			try {
				s.contentWindow?.postMessage("tokenize", c);
			} catch {
				t = !0, u(), r.addError("Moneris tokenization could not be started."), e(!1);
			}
		});
	},
	setup: async (e) => {
		let { services: t } = e, r = t.events.onRoot("message", (e) => {
			if (!e.origin.includes("moneris")) return;
			let r = n(e.data);
			r.token && t.updateInputs("monerisTokenId", r.token);
		});
		return { destroy: () => {
			r();
			let t = e.target;
			t.__formieMonerisAuthorizeCleanup?.(), t.__formieMonerisAuthorizeCleanup = null;
		} };
	},
	onAfterSubmit: async ({ services: e }) => {
		e.updateInputs("monerisTokenId", "");
	}
});
//#endregion
export { r as monerisModule };
