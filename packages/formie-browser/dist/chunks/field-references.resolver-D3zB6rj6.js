import { n as e, r as t, t as n } from "./field-references.keys-BGhkWxVZ.js";
//#region src/js/utils/field-references.parser.ts
function r(e) {
	let t = e.split(";").map((e) => e.trim()).filter(Boolean);
	if (!t.length) return {
		source: "",
		transforms: []
	};
	let [n, ...r] = t, i = [], a = null;
	return r.forEach((e) => {
		if (e.startsWith("transform=")) {
			a && i.push(a), a = {
				id: decodeURIComponent(e.slice(10) || "").trim(),
				params: {}
			};
			return;
		}
		if (!a || !e.includes("=")) return;
		let [t, n] = e.split("=", 2), r = (t || "").trim();
		!r || r === "transform" || (a.params[r] = decodeURIComponent(n || "").trim());
	}), a && i.push(a), {
		source: n || "",
		transforms: i
	};
}
function i(e) {
	let n = String(e || "").trim();
	if (!n) return {
		raw: n,
		target: "",
		key: "",
		selector: "",
		defaultValue: "",
		transforms: [],
		isToken: !1,
		isValid: !1
	};
	let i = n.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);
	if (!i) return {
		raw: n,
		target: "",
		key: t(n),
		selector: "",
		defaultValue: "",
		transforms: [],
		isToken: !1,
		isValid: !0
	};
	let a = (i[1] || "").trim().toLowerCase(), [o, s = ""] = (i[2] || "").trim().split("|", 2), { source: c, transforms: l } = r(o || "");
	if (a !== "field") return {
		raw: n,
		target: "",
		key: "",
		selector: "",
		defaultValue: s.trim(),
		transforms: l,
		isToken: !0,
		isValid: !1
	};
	let u = c.indexOf(":"), d = u === -1 ? c : c.slice(0, u), f = u === -1 ? "" : c.slice(u + 1), p = t(d);
	return {
		raw: n,
		target: "field",
		key: p,
		selector: f.trim(),
		defaultValue: s.trim(),
		transforms: l,
		isToken: !0,
		isValid: p !== ""
	};
}
//#endregion
//#region src/js/utils/field-references.registry.ts
function a(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement || e instanceof HTMLSelectElement;
}
function o(e, t, n) {
	let r = t.trim(), i = String(n.name || "").trim();
	if (!r || !i) return;
	let a = e.get(r) || {
		key: r,
		names: [],
		inputs: []
	};
	a.names.includes(i) || a.names.push(i), a.inputs.includes(n) || a.inputs.push(n), e.set(r, a);
}
function s(t) {
	let n = /* @__PURE__ */ new Map();
	return Array.from(t.querySelectorAll("[name]")).filter((e) => a(e)).forEach((t) => {
		let r = e(t.name);
		r && o(n, r, t);
	}), n;
}
//#endregion
//#region src/js/utils/field-references.resolver.ts
function c(e) {
	if (!e.length) return "";
	let t = e[0];
	if (t instanceof HTMLSelectElement && t.multiple) return Array.from(t.selectedOptions).map((e) => e.value);
	if (e.some((e) => e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio"))) {
		let t = e.flatMap((e) => !(e instanceof HTMLInputElement) || !e.checked ? [] : [e.value]);
		return t.length > 1 ? t : t[0] || "";
	}
	return t.value;
}
function l(e, n) {
	return e.get(t(n)) || null;
}
function u(e, t) {
	let n = i(e), r = n.key, a = l(t, r);
	if (!a) return {
		key: r,
		value: n.defaultValue,
		found: !1
	};
	let o = c(a.inputs);
	return {
		key: r,
		value: o === "" && n.defaultValue !== "" ? n.defaultValue : o,
		found: !0
	};
}
function d(e, t, r) {
	let a = i(e), o = a.key;
	if (!o) return {
		key: o,
		value: a.defaultValue,
		found: !1
	};
	let s = r ? l(r, o) : null, c = (s?.names?.length ? s.names : [n(o)]).flatMap((e) => {
		let n = t.getAll(e).map((e) => String(e ?? ""));
		return n.length ? n : t.getAll(`${e}[]`).map((e) => String(e ?? ""));
	}).filter((e) => e !== "");
	return c.length ? {
		key: o,
		value: c.length > 1 ? c : c[0],
		found: !0
	} : {
		key: o,
		value: a.defaultValue,
		found: !1
	};
}
//#endregion
export { i, u as n, s as r, d as t };
