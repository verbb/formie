import { a as e, i as t, o as n, r, t as i } from "./dist-DsjQF4UQ.js";
import { t as a } from "./debug-BV0DvdHx.js";
import { r as o, t as s } from "./field-references.keys-58ZSTrCW.js";
import { n as c, r as l } from "./field-references.resolver-Bq207xxF.js";
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
	let t = o(e), n = t.split(".").filter(Boolean);
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
function g(e, t, n) {
	let r = RegExp(`^${p(e)}\\.(\\d+)\\.${p(t)}$`);
	return [...n.keys()].filter((e) => r.test(e)).sort((e, t) => Number.parseInt(e.split(".")[1] || "0", 10) - Number.parseInt(t.split(".")[1] || "0", 10));
}
function _(e, t) {
	return c(e, t).value;
}
function v(e, t, n) {
	let r = /* @__PURE__ */ new Set(), { fieldKey: i, columnKey: a } = h(e), o = String(t.scope || "").trim().toLowerCase();
	if (!i || !a || !f.has(o)) {
		let t = s(e);
		return t && (r.add(t), r.add(`${t}[]`)), r;
	}
	return g(i, a, n).forEach((e) => {
		let t = n.get(e);
		if (t?.names?.length) {
			t.names.forEach((e) => {
				r.add(e);
			});
			return;
		}
		let i = s(e);
		i && (r.add(i), r.add(`${i}[]`));
	}), r;
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
var S = "calculations", C = a("fields", "calculations");
function w(e, n, i) {
	let a = l(e), o = {};
	return n.forEach(([e, t]) => {
		let n = x(t);
		if (String(n.scope || "").trim()) {
			let i = y(t.sourceKey || "", n, a);
			o[e] = r(t, i.value);
			return;
		}
		let i = c(t.sourceKey || "", a);
		o[e] = r(t, i.value);
	}), t(o, i.formatting);
}
function T(e, t) {
	let n = l(e), r = /* @__PURE__ */ new Set();
	return t.forEach(([, e]) => {
		let t = x(e);
		if (String(t.scope || "").trim()) {
			v(e.sourceKey || "", t, n).forEach((e) => {
				r.add(e);
			});
			return;
		}
		let i = o(e.sourceKey || ""), a = n.get(i);
		if (a?.names?.length) {
			a.names.forEach((e) => {
				r.add(e);
			});
			return;
		}
		let c = s(i);
		c && (r.add(c), r.add(`${c}[]`));
	}), r;
}
function E(t, r, a, o) {
	let s = n(o), c = i(o), l = /* @__PURE__ */ new Map(), u = null, f = !1, p = !1, m = !1, h = () => {
		l.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), l.clear();
	}, g = (e) => {
		e && !f && queueMicrotask(() => {
			f || (a.dispatchEvent(new Event("input", { bubbles: !0 })), a.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, _ = (n = !1) => {
		let i = w(t, c, o);
		C.log("Evaluate requested.", {
			fieldHandle: r.getAttribute("data-formie-field-handle") || null,
			isInit: n
		});
		let l = {
			calculations: a,
			init: n,
			formula: s,
			variables: i
		};
		if (d(r, S, "before-evaluate", l), !l.formula) {
			let e = a.value !== "";
			a.value = "", g(e);
			return;
		}
		try {
			let t = e(l.formula, l.variables, o), i = {
				calculations: a,
				init: n,
				formula: l.formula,
				variables: l.variables,
				result: t
			};
			d(r, S, "after-evaluate", i);
			let s = typeof i.result == "string" || typeof i.result == "number" ? String(i.result) : "", c = a.value !== s;
			a.value = s, C.log("Evaluate complete.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: s
			}), g(c);
		} catch (e) {
			let t = a.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), C.warn("Evaluate failed.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				error: e instanceof Error ? e.message : e
			}), a.value = "", g(t);
		}
	}, v = (e = !1) => {
		p || f || (p = !0, queueMicrotask(() => {
			p = !1, _(e);
		}));
	}, y = () => {
		h();
		let e = T(t, c);
		if (C.log("Binding variable watchers.", {
			fieldHandle: r.getAttribute("data-formie-field-handle") || null,
			watchCount: e.size
		}), !e.size) return;
		let n = (t) => {
			let n = t.target?.name || "";
			n && e.has(n) && (C.log("Source change detected.", {
				fieldHandle: r.getAttribute("data-formie-field-handle") || null,
				sourceName: n,
				eventType: t.type
			}), v(!1));
		};
		["input", "change"].forEach((e) => {
			t.addEventListener(e, n);
			let r = l.get(t) || /* @__PURE__ */ new Map();
			r.set(e, n), l.set(t, r);
		});
	}, b = () => {
		m || f || (m = !0, queueMicrotask(() => {
			m = !1, y(), v(!1);
		}));
	};
	return y(), u = new MutationObserver(() => {
		b();
	}), u.observe(t, {
		childList: !0,
		subtree: !0
	}), _(!0), () => {
		f = !0, u?.disconnect(), h();
	};
}
var D = {
	moduleId: `formie:${S}`,
	version: 1,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
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
