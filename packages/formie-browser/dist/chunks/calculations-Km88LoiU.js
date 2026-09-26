import { t as e } from "./debug-BV0DvdHx.js";
import { r as t, t as n } from "./field-references.keys-58ZSTrCW.js";
import { i as r, o as i, r as a, s as o, t as s } from "./dist-D-zH3M5_.js";
import { n as c, r as l } from "./field-references.resolver-UFZAXOEZ.js";
import { r as u, t as d } from "./shared-Bx9s0i0P.js";
//#region src/js/utils/field-references.row-scope.ts
var f = /* @__PURE__ */ new Set([
	"first",
	"last",
	"index",
	"all",
	"count",
	"rows"
]);
function p(e) {
	return e.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function m(e, t) {
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
function h(e) {
	let n = t(e), r = n.split(".").filter(Boolean);
	return r.length < 2 ? {
		fieldKey: n,
		columnKey: r[r.length - 1] || ""
	} : r.length >= 3 && /^\d+$/.test(r[1] || "") ? {
		fieldKey: r[0] || "",
		columnKey: r.slice(2).join(".")
	} : {
		fieldKey: r[0] || "",
		columnKey: r.slice(1).join(".")
	};
}
function g(e, t, n) {
	let r = RegExp(`^${p(e)}\\.(\\d+)\\.${p(t)}$`);
	return [...n.keys()].filter((e) => r.test(e)).sort((e, t) => Number.parseInt(e.split(".")[1] || "0", 10) - Number.parseInt(t.split(".")[1] || "0", 10));
}
function _(e, t) {
	return c(e, t).value;
}
function v(e, t, r) {
	let i = /* @__PURE__ */ new Set(), { fieldKey: a, columnKey: o } = h(e), s = String(t.scope || "").trim().toLowerCase();
	if (!a || !o || !f.has(s)) {
		let t = n(e);
		return t && (i.add(t), i.add(`${t}[]`)), i;
	}
	return g(a, o, r).forEach((e) => {
		let t = r.get(e);
		if (t?.names?.length) {
			t.names.forEach((e) => {
				i.add(e);
			});
			return;
		}
		let a = n(e);
		a && (i.add(a), i.add(`${a}[]`));
	}), i;
}
function y(e, t, n) {
	let r = String(t.scope || "").trim().toLowerCase();
	if (!r || !f.has(r)) return c(e, n);
	let { fieldKey: i, columnKey: a } = h(e);
	if (!i || !a) return c(e, n);
	let o = g(i, a, n), s = o.map((e) => _(e, n));
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
		return c(e, n);
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
		let e = m(String(t.rows || ""), o.length);
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
	return c(e, n);
}
//#endregion
//#region src/js/modules/fields/calculations.ts
var b = "input[data-formie-calculation-input]";
function x(e) {
	let t = e;
	return {
		scope: t.scope,
		index: t.index,
		rows: t.rows,
		fieldKind: t.fieldKind
	};
}
var S = "calculations", C = e("fields", "calculations");
function w(e, t, n) {
	let r = l(e), a = {};
	return t.forEach(([e, t]) => {
		let n = x(t);
		if (String(n.scope || "").trim()) {
			let o = y(t.sourceKey || "", n, r);
			a[e] = i(t, o.value);
			return;
		}
		let o = c(t.sourceKey || "", r);
		a[e] = i(t, o.value);
	}), s(a, n.formatting);
}
function T(e, r) {
	let i = l(e), a = /* @__PURE__ */ new Set();
	return r.forEach(([, e]) => {
		let r = x(e);
		if (String(r.scope || "").trim()) {
			v(e.sourceKey || "", r, i).forEach((e) => {
				a.add(e);
			});
			return;
		}
		let o = t(e.sourceKey || ""), s = i.get(o);
		if (s?.names?.length) {
			s.names.forEach((e) => {
				a.add(e);
			});
			return;
		}
		let c = n(o);
		c && (a.add(c), a.add(`${c}[]`));
	}), a;
}
function E(e, t, n, i) {
	let s = a(i), c = r(i), l = /* @__PURE__ */ new Map(), u = null, f = !1, p = !1, m = !1, h = () => {
		l.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), l.clear();
	}, g = (e) => {
		e && !f && queueMicrotask(() => {
			f || (n.dispatchEvent(new Event("input", { bubbles: !0 })), n.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, _ = (r = !1) => {
		let a = w(e, c, i);
		C.log("Evaluate requested.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			isInit: r
		});
		let l = {
			calculations: n,
			init: r,
			formula: s,
			variables: a
		};
		if (d(t, S, "before-evaluate", l), !l.formula) {
			let e = n.value !== "";
			n.value = "", g(e);
			return;
		}
		try {
			let e = o(l.formula, l.variables, i), a = {
				calculations: n,
				init: r,
				formula: l.formula,
				variables: l.variables,
				result: e
			};
			d(t, S, "after-evaluate", a);
			let s = typeof a.result == "string" || typeof a.result == "number" ? String(a.result) : "", c = n.value !== s;
			n.value = s, C.log("Evaluate complete.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: s
			}), g(c);
		} catch (e) {
			let r = n.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), C.warn("Evaluate failed.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				error: e instanceof Error ? e.message : e
			}), n.value = "", g(r);
		}
	}, v = (e = !1) => {
		p || f || (p = !0, queueMicrotask(() => {
			p = !1, _(e);
		}));
	}, y = () => {
		h();
		let n = T(e, c);
		if (C.log("Binding variable watchers.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			watchCount: n.size
		}), !n.size) return;
		let r = (e) => {
			let r = e.target?.name || "";
			r && n.has(r) && (C.log("Source change detected.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				sourceName: r,
				eventType: e.type
			}), v(!1));
		};
		["input", "change"].forEach((t) => {
			e.addEventListener(t, r);
			let n = l.get(e) || /* @__PURE__ */ new Map();
			n.set(t, r), l.set(e, n);
		});
	}, b = () => {
		m || f || (m = !0, queueMicrotask(() => {
			m = !1, y(), v(!1);
		}));
	};
	return y(), u = new MutationObserver(() => {
		b();
	}), u.observe(e, {
		childList: !0,
		subtree: !0
	}), _(!0), () => {
		f = !0, u?.disconnect(), h();
	};
}
var D = {
	id: S,
	kind: "field",
	match: (e) => !!e.target.querySelector(b),
	setup: async (e) => {
		let t = e.options || {}, n = u(e);
		C.log("Module setup.", {
			fieldCount: n.length,
			formatting: t.formatting || null
		});
		let r = n.map((n) => {
			let r = n.querySelector(b);
			return r instanceof HTMLInputElement ? E(e.root, n, r, t) : () => {};
		});
		return await e.emit("formie:module:calculations:init", { count: r.length }), { destroy: () => {
			C.log("Module destroy.", { fieldCount: r.length }), r.forEach((e) => {
				e();
			}), e.emit("formie:module:calculations:destroy", {});
		} };
	}
};
//#endregion
export { D as calculationsModule };
