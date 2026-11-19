import { n as e } from "./async-2GdXNFCE.js";
import { i as t } from "./api-BPtH5RBA.js";
//#region src/js/modules/captchas/friendly-captcha-shared.ts
function n(e) {
	let t = e.tokens.read();
	return t !== "" && !t.startsWith(".");
}
async function r(r, i = t) {
	let a = Date.now() + Math.max(i, 0);
	for (; !n(r);) {
		if (Date.now() >= a) return !1;
		await e(120);
	}
	return !0;
}
//#endregion
export { r as n, n as t };
