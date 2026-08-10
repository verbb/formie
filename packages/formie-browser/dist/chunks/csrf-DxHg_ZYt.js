//#region src/js/utils/csrf.ts
var e = "CRAFT_CSRF_TOKEN", t = "data-formie-csrf-param";
function n() {
	let e = globalThis.Craft?.csrfTokenName;
	return typeof e == "string" && e.trim() ? e.trim() : null;
}
function r(e) {
	return typeof CSS < "u" && typeof CSS.escape == "function" ? CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
}
function i(e, t) {
	let n = e.querySelector(`input[name="${r(t)}"]`);
	return n instanceof HTMLInputElement ? n : null;
}
function a(r) {
	if (!r) return null;
	let a = r.querySelector("input[data-formie-csrf]");
	if (a instanceof HTMLInputElement && a.name.trim()) return a;
	if (r instanceof Element) {
		let e = r.getAttribute(t)?.trim();
		if (e) {
			let t = i(r, e);
			if (t) return t;
		}
	}
	let o = n();
	if (o) {
		let e = i(r, o);
		if (e) return e;
	}
	return i(r, e);
}
function o(e) {
	let t = a(e), n = t?.name?.trim() || "", r = t?.value?.trim() || "";
	return !n || !r ? null : {
		name: n,
		value: r
	};
}
function s(e, t) {
	let n = o(t);
	n && e.append(n.name, n.value);
}
function c(e, t) {
	let n = o(t);
	n && (e[n.name] = n.value);
}
function l(r, i) {
	let a = r.endsWith("[]") ? r.slice(0, -2) : r;
	if (!a) return !1;
	if (a === e) return !0;
	let s = n();
	if (s && a === s) return !0;
	if (i instanceof Element) {
		let e = i.getAttribute(t)?.trim();
		if (e && a === e) return !0;
	}
	let c = o(i);
	return !!c && a === c.name;
}
//#endregion
export { l as i, c as n, o as r, s as t };
