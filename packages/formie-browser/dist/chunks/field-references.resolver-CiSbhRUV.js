import { p as e, u as t } from "./dist-vTdOlBe4.js";
import { n, r, t as i } from "./field-references.keys-58ZSTrCW.js";
//#region src/js/utils/field-references.parser.ts
function a(t) {
	let n = String(t || "").trim(), i = e(n), a = n.startsWith("{"), o = i.isValid && i.target === "field";
	return {
		raw: n,
		target: o ? "field" : "",
		key: o ? r(i.identifier) : a ? "" : r(n),
		selector: i.selector,
		defaultValue: i.default,
		transforms: i.transformerId ? [{
			id: i.transformerId,
			params: i.transformerParams
		}] : [],
		isToken: a,
		isValid: a ? o : n !== ""
	};
}
//#endregion
//#region src/js/utils/field-references.registry.ts
function o(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement || e instanceof HTMLSelectElement;
}
function s(e, t, n) {
	let r = t.trim(), i = String(n.name || "").trim();
	if (!r || !i) return;
	let a = e.get(r) || {
		key: r,
		names: [],
		inputs: []
	};
	a.names.includes(i) || a.names.push(i), a.inputs.includes(n) || a.inputs.push(n), e.set(r, a);
}
function c(e) {
	let t = /* @__PURE__ */ new Map();
	return Array.from(e.querySelectorAll("[name]")).filter((e) => o(e)).forEach((e) => {
		let r = n(e.name);
		r && s(t, r, e);
	}), t;
}
//#endregion
//#region src/js/utils/field-references.resolver.ts
function l(e) {
	if (!e.length) return "";
	let t = e[0];
	if (t instanceof HTMLSelectElement && t.multiple) return Array.from(t.selectedOptions).map((e) => e.value);
	if (e.some((e) => e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio"))) {
		let t = e.flatMap((e) => !(e instanceof HTMLInputElement) || !e.checked ? [] : [e.value]);
		return t.length > 1 ? t : t[0] || "";
	}
	return t.value;
}
function u(e, t) {
	return e.get(r(t)) || null;
}
function d(n, r, i) {
	let a = n.trim().startsWith("{") ? n : `{field:${encodeURIComponent(r)}}`, o = e(a), s = `field:${o.identifier}`, c = o.selector ? `${s}:${o.selector}` : s, l = t(a, {
		definitions: { [s]: {
			id: s,
			selectors: o.selector ? [o.selector] : [],
			availability: {
				server: !0,
				browser: !0
			}
		} },
		values: { [c]: i }
	});
	return {
		key: r,
		value: l.diagnostic ? "" : l.value,
		found: !l.diagnostic,
		diagnostic: l.diagnostic
	};
}
function f(e, t) {
	let n = a(e), r = n.key, i = n.selector ? `${r}.${n.selector.replace(/:/g, ".")}` : r, o = n.isValid ? u(t, i) : null;
	return o ? d(e, r, l(o.inputs)) : {
		key: r,
		value: "",
		found: !1,
		diagnostic: n.isValid ? n.selector ? "invalidSelector" : "missingField" : "invalidExpression"
	};
}
function p(e, t, n) {
	let r = a(e), o = r.key, s = r.selector ? `${o}.${r.selector.replace(/:/g, ".")}` : o, c = n ? u(n, s) : null;
	if (!r.isValid || n && !c) return {
		key: o,
		value: "",
		found: !1,
		diagnostic: r.isValid ? "missingField" : "invalidExpression"
	};
	let l = c?.names?.length ? c.names : [i(s)];
	if (!(c || l.some((e) => t.has(e) || t.has(`${e}[]`)))) return {
		key: o,
		value: "",
		found: !1,
		diagnostic: "missingField"
	};
	let f = l.flatMap((e) => [...t.getAll(e), ...t.getAll(`${e}[]`)]).map((e) => String(e ?? ""));
	return d(e, o, f.length > 1 ? f : f[0] ?? "");
}
//#endregion
export { a as i, f as n, c as r, p as t };
