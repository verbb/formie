import { c as e, f as t, h as n, p as r, u as i } from "./dist-dN2u1m9T.js";
import { t as a } from "./debug-rhzqP1j2.js";
import { n as o, r as s, t as c } from "./field-references.keys-58ZSTrCW.js";
import { n as l, r as u } from "./field-references.resolver-DTL2wni2.js";
import { i as d, n as f } from "./shared-CJ1LIEh3.js";
//#region src/js/utils/field-references.row-scope.ts
var p = /* @__PURE__ */ new Set([
	"first",
	"last",
	"index",
	"all",
	"count",
	"rows"
]);
function m(e) {
	return e.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function h(e, t) {
	let n = String(e || "").trim().toLowerCase();
	if (!n || t <= 0) return [];
	if (n === "even") {
		let e = [];
		for (let n = 1; n <= t; n++) n % 2 == 0 && e.push(n - 1);
		return e;
	}
	if (n === "odd") {
		let e = [];
		for (let n = 1; n <= t; n++) n % 2 == 1 && e.push(n - 1);
		return e;
	}
	let r = n.match(/^every:(\d+)$/);
	if (r) {
		let e = Math.max(1, Number.parseInt(r[1] || "1", 10)), n = [];
		for (let r = 1; r <= t; r += e) n.push(r - 1);
		return n;
	}
	let i = [];
	return n.split(/\s*,\s*/).forEach((e) => {
		let n = e.trim();
		if (!n) return;
		let r = n.match(/^(\d+)\s*-\s*(\d+)$/);
		if (r) {
			let e = Number.parseInt(r[1] || "0", 10), n = Number.parseInt(r[2] || "0", 10);
			e > n && ([e, n] = [n, e]);
			for (let r = Math.max(1, e); r <= Math.min(t, n); r++) r >= 1 && r <= t && i.push(r - 1);
			return;
		}
		let a = Number.parseInt(n, 10);
		Number.isFinite(a) && a >= 1 && a <= t && i.push(a - 1);
	}), [...new Set(i)].sort((e, t) => e - t);
}
function g(e) {
	let t = s(e), n = t.split(".").filter(Boolean);
	return n.length < 2 ? {
		fieldKey: t,
		columnKey: n[n.length - 1] || ""
	} : n.length >= 3 && /^\d+$/.test(n[1] || "") ? {
		fieldKey: n[0] || "",
		columnKey: n.slice(2).join(".")
	} : {
		fieldKey: n[0] || "",
		columnKey: n.slice(1).join(".")
	};
}
function _(e, t, n) {
	let r = RegExp(`^${m(e)}\\.(\\d+)\\.${m(t)}$`);
	return [...n.keys()].filter((e) => r.test(e)).sort((e, t) => Number.parseInt(e.split(".")[1] || "0", 10) - Number.parseInt(t.split(".")[1] || "0", 10));
}
function v(e, t) {
	return l(e, t).value;
}
function y(e, t, n) {
	let r = /* @__PURE__ */ new Set(), { fieldKey: i, columnKey: a } = g(e), o = String(t.scope || "").trim().toLowerCase();
	if (!i || !a || !p.has(o)) {
		let t = c(e);
		return t && (r.add(t), r.add(`${t}[]`)), r;
	}
	return _(i, a, n).forEach((e) => {
		let t = n.get(e);
		if (t?.names?.length) {
			t.names.forEach((e) => {
				r.add(e);
			});
			return;
		}
		let i = c(e);
		i && (r.add(i), r.add(`${i}[]`));
	}), r;
}
function b(e, t, n) {
	let r = String(t.scope || "").trim().toLowerCase();
	if (!r || !p.has(r)) return l(e, n);
	let { fieldKey: i, columnKey: a } = g(e);
	if (!i || !a) return l(e, n);
	let o = _(i, a, n), s = o.map((e) => v(e, n));
	if (r === "count") return {
		key: `${i}.${a}`,
		value: String(o.length),
		found: !0
	};
	if (r === "first") return {
		key: o[0] || `${i}.0.${a}`,
		value: s[0] ?? "",
		found: o.length > 0
	};
	if (r === "last") return {
		key: o[o.length - 1] || `${i}.0.${a}`,
		value: s[s.length - 1] ?? "",
		found: o.length > 0
	};
	if (r === "index") {
		let e = `${i}.${Number.parseInt(String(t.index ?? "0"), 10)}.${a}`;
		return l(e, n);
	}
	if (r === "all") {
		let e = s.flatMap((e) => Array.isArray(e) ? e : e === "" ? [] : [e]);
		return {
			key: `${i}.${a}`,
			value: e,
			found: e.length > 0
		};
	}
	if (r === "rows") {
		let e = h(String(t.rows || ""), o.length);
		if (e.length === 0) return {
			key: `${i}.${a}`,
			value: "",
			found: !1
		};
		if (e.length === 1) return {
			key: o[e[0]] || `${i}.${e[0]}.${a}`,
			value: s[e[0]] ?? "",
			found: !0
		};
		let n = e.flatMap((e) => {
			let t = s[e];
			return Array.isArray(t) ? t : t === "" ? [] : [t];
		});
		return {
			key: `${i}.${a}`,
			value: n,
			found: n.length > 0
		};
	}
	return l(e, n);
}
//#endregion
//#region src/js/modules/fields/calculations.ts
var x = "input[data-formie-calculation-input]";
function S(e) {
	let t = e;
	return {
		scope: t.scope,
		index: t.index,
		rows: t.rows,
		fieldKind: t.fieldKind
	};
}
var C = "calculations";
function w(e) {
	let t = o(e.name).split(".").filter(Boolean);
	return t.length >= 3 ? {
		handle: t[0],
		row: t[1]
	} : null;
}
function T(e, t) {
	let n = s(e).split(".").filter(Boolean);
	return !t || n.length < 2 || n[0] !== t.handle || /^\d+$/.test(n[1] || "") ? e : [
		n[0],
		t.row,
		...n.slice(1)
	].join(".");
}
var E = a("fields", "calculations");
function D(e, n, i, a = null) {
	let o = u(e), s = {};
	return n.forEach(([e, n]) => {
		let r = S(n);
		if (String(r.scope || "").trim()) {
			let i = b(n.sourceKey || "", r, o);
			s[e] = t(n, i.value);
			return;
		}
		let i = l(T(n.sourceKey || "", a), o);
		s[e] = t(n, i.value);
	}), r(s, i.formatting);
}
function O(e, t, n = null) {
	let r = u(e), i = /* @__PURE__ */ new Set();
	return t.forEach(([, e]) => {
		let t = S(e);
		if (String(t.scope || "").trim()) {
			y(e.sourceKey || "", t, r).forEach((e) => {
				i.add(e);
			});
			return;
		}
		let a = s(T(e.sourceKey || "", n)), o = r.get(a);
		if (o?.names?.length) {
			o.names.forEach((e) => {
				i.add(e);
			});
			return;
		}
		let l = c(a);
		l && (i.add(l), i.add(`${l}[]`));
	}), i;
}
function k(t, r, a, o) {
	let s = i(o), c = n(o), l = w(a), u = /* @__PURE__ */ new Map(), d = null, p = !1, m = !1, h = !1, g = () => {
		u.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), u.clear();
	}, _ = (e) => {
		e && !p && queueMicrotask(() => {
			p || (a.dispatchEvent(new Event("input", { bubbles: !0 })), a.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, v = (n = !1) => {
		let i = D(t, c, o, l);
		E.log("Evaluate requested.", {
			fieldHandle: r.getAttribute("data-formie-field-handle") || null,
			isInit: n
		});
		let u = {
			calculations: a,
			init: n,
			formula: s,
			variables: i
		};
		if (f(r, C, "before-evaluate", u), !u.formula) {
			let e = a.value !== "";
			a.value = "", _(e);
		} else try {
			let t = e(u.formula, u.variables, o), i = {
				calculations: a,
				init: n,
				formula: u.formula,
				variables: u.variables,
				result: t
			};
			f(r, C, "after-evaluate", i);
			let s = typeof i.result == "string" || typeof i.result == "number" ? String(i.result) : "", c = a.value !== s;
			a.value = s, E.log("Evaluate complete.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: s
			}), _(c);
		} catch (e) {
			let t = a.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), E.warn("Evaluate failed.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				error: e instanceof Error ? e.message : e
			}), a.value = "", _(t);
		}
	}, y = (e = !1) => {
		m || p || (m = !0, queueMicrotask(() => {
			m = !1, v(e);
		}));
	}, b = () => {
		g();
		let e = O(t, c, l);
		if (E.log("Binding variable watchers.", {
			fieldHandle: r.getAttribute("data-formie-field-handle") || null,
			watchCount: e.size
		}), !e.size) return;
		let n = (t) => {
			let n = t.target?.name || "";
			n && e.has(n) && (E.log("Source change detected.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				sourceName: n,
				eventType: t.type
			}), y(!1));
		};
		["input", "change"].forEach((e) => {
			t.addEventListener(e, n);
			let r = u.get(t) || /* @__PURE__ */ new Map();
			r.set(e, n), u.set(t, r);
		});
	}, x = () => {
		h || p || (h = !0, queueMicrotask(() => {
			h = !1, b(), y(!1);
		}));
	};
	return b(), d = new MutationObserver(() => {
		x();
	}), d.observe(t, {
		childList: !0,
		subtree: !0
	}), v(!0), () => {
		p = !0, d?.disconnect(), g();
	};
}
var A = {
	moduleId: `formie:${C}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => !!e.target.querySelector(x),
	setup: async (e) => {
		let t = e.options || {}, n = d(e);
		E.log("Module setup.", {
			fieldCount: n.length,
			formatting: t.formatting || null
		});
		let r = n.map((n) => {
			let r = n.querySelector(x);
			return r instanceof HTMLInputElement ? k(e.root, n, r, t) : () => {};
		});
		return await e.emit("formie:module:calculations:init", { count: r.length }), { destroy: () => {
			E.log("Module destroy.", { fieldCount: r.length }), r.forEach((e) => {
				e();
			}), e.emit("formie:module:calculations:destroy", {});
		} };
	}
};
//#endregion
export { A as calculationsModule };
