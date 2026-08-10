import { r as e } from "./async-nPFRNQ06.js";
//#region src/js/utils/scripts.ts
var t = /* @__PURE__ */ new Map();
function n() {
	let e = document.querySelector("meta[property=\"csp-nonce\"], meta[name=\"csp-nonce\"]");
	return e && (e.nonce || e.getAttribute("nonce") || e.getAttribute("content")) || null;
}
async function r(t, n = 5e3) {
	return e(() => window[t] ?? null, {
		timeoutMs: n,
		intervalMs: 30
	});
}
async function i({ id: e, src: r, async: i = !0, defer: a = !0 }) {
	return document.getElementById(e) || (t.has(e) || t.set(e, new Promise((o, s) => {
		let c = document.createElement("script");
		c.id = e, c.src = r, c.async = i, c.defer = a;
		let l = n();
		l && c.setAttribute("nonce", l), c.onload = () => {
			o(c);
		}, c.onerror = () => {
			t.delete(e), s(/* @__PURE__ */ Error(`Failed to load external script: ${r}`));
		}, document.body.appendChild(c);
	})), t.get(e));
}
async function a(e, t) {
	return window[e] ?? (await i(t), r(e, t.timeoutMs));
}
//#endregion
export { i as n, a as r, r as t };
