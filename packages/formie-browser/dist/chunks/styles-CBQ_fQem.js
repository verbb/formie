import { n as e } from "./scripts-Br6ohEoT.js";
//#region src/js/modules/styles.ts
var t = /* @__PURE__ */ new Set();
function n() {
	let t = document.querySelector("script[data-formie-startup]");
	return e() || t?.nonce || null;
}
function r(e, r) {
	if (typeof document > "u") return;
	let i = `formie-module-style:${e}`;
	if (t.has(i) || document.querySelector(`style[data-formie-module-style="${e}"]`)) {
		t.add(i);
		return;
	}
	let a = r.filter((e) => typeof e == "string" && e.trim().length > 0).join("\n");
	if (!a) {
		t.add(i);
		return;
	}
	let o = document.createElement("style"), s = n();
	s && (o.nonce = s), o.setAttribute("data-formie-module-style", e), o.textContent = a, document.head.appendChild(o), t.add(i);
}
//#endregion
export { r as t };
