import { n as e, r as t, t as n } from "./field-references.keys-58ZSTrCW.js";
import { a as r, c as i } from "./dist-D-zH3M5_.js";
//#region src/js/utils/field-references.parser.ts
function a(e) {
	let n = String(e || "").trim(), r = i(n), a = n.startsWith("{"), o = r.isValid && r.target === "field";
	return {
		raw: n,
		target: o ? "field" : "",
		key: o ? t(r.identifier) : a ? "" : t(n),
		selector: r.selector,
		defaultValue: r.default,
		transforms: r.transformerId ? [{
			id: r.transformerId,
			params: r.transformerParams
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
function c(t) {
	let n = /* @__PURE__ */ new Map();
	return Array.from(t.querySelectorAll("[name]")).filter((e) => o(e)).forEach((t) => {
		let r = e(t.name);
		r && s(n, r, t);
	}), n;
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
function u(e, n) {
	return e.get(t(n)) || null;
}
function d(e, t, n) {
	let a = e.trim().startsWith("{") ? e : `{field:${encodeURIComponent(t)}}`, o = i(a), s = `field:${o.identifier}`, c = o.selector ? `${s}:${o.selector}` : s, l = r(a, {
		definitions: { [s]: {
			id: s,
			selectors: o.selector ? [o.selector] : [],
			availability: {
				server: !0,
				browser: !0
			}
		} },
		values: { [c]: n }
	});
	return {
		key: t,
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
function p(e, t, r) {
	let i = a(e), o = i.key, s = i.selector ? `${o}.${i.selector.replace(/:/g, ".")}` : o, c = r ? u(r, s) : null;
	if (!i.isValid || r && !c) return {
		key: o,
		value: "",
		found: !1,
		diagnostic: i.isValid ? "missingField" : "invalidExpression"
	};
	let l = c?.names?.length ? c.names : [n(s)];
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
