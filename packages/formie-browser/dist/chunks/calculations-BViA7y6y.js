import { f as e, h as t, l as n, s as r, u as i } from "./dist-qbOmGR5G.js";
import { t as a } from "./debug-rhzqP1j2.js";
import { n as o, r as s, t as c } from "./field-references.keys-58ZSTrCW.js";
import { n as l, r as u } from "./field-references.resolver-C9fww6Zm.js";
import { a as d, r as f } from "./shared-wVxxLWwM.js";
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
		let e = Number.parseInt(String(t.index ?? "0"), 10);
		return {
			key: o[e] || `${i}.${e}.${a}`,
			value: s[e] ?? "",
			found: e >= 0 && e < o.length
		};
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
var x = "input[data-formie-calculation-input]", S = "[data-formie-conditionally-hidden], [data-formie-conditionally-disabled], [data-formie-conditions-disabled]";
function C(e) {
	let t = e;
	return {
		scope: t.scope,
		index: t.index,
		rows: t.rows,
		fieldKind: t.fieldKind
	};
}
var w = "calculations";
function T(e) {
	let t = o(e.name).split(".").filter(Boolean);
	return t.length >= 3 ? {
		handle: t[0],
		row: t[1]
	} : null;
}
function E(e, t) {
	let n = s(e).split(".").filter(Boolean);
	return !t || n.length < 2 || n[0] !== t.handle || /^\d+$/.test(n[1] || "") ? e : [
		n[0],
		t.row,
		...n.slice(1)
	].join(".");
}
var D = a("fields", "calculations");
function O(t, n, r, a = null) {
	let o = u(t), s = {};
	return o.forEach((e, t) => {
		e.inputs = e.inputs.filter((e) => !e.closest(S)), e.inputs.length || o.delete(t);
	}), n.forEach(([e, t]) => {
		let n = C(t);
		if (String(n.scope || "").trim()) {
			let r = b(t.sourceKey || "", n, o);
			s[e] = i(t, r.value);
			return;
		}
		let r = l(E(t.sourceKey || "", a), o);
		s[e] = i(t, r.value);
	}), e(s, r.formatting);
}
function k(e, t, n = null) {
	let r = u(e), i = /* @__PURE__ */ new Set();
	return t.forEach(([, e]) => {
		let t = C(e);
		if (String(t.scope || "").trim()) {
			y(e.sourceKey || "", t, r).forEach((e) => {
				i.add(e);
			});
			return;
		}
		let a = s(E(e.sourceKey || "", n)), o = r.get(a);
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
function A(e, i, a, o) {
	let s = n(o), c = t(o), l = T(a), u = /* @__PURE__ */ new Map(), d = null, p = !1, m = !1, h = !1, g = () => {
		u.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), u.clear();
	}, _ = (e) => {
		e && !p && queueMicrotask(() => {
			p || (a.dispatchEvent(new Event("input", { bubbles: !0 })), a.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, v = (t = !1) => {
		let n = O(e, c, o, l);
		D.log("Evaluate requested.", {
			fieldHandle: i.getAttribute("data-formie-field-handle") || null,
			isInit: t
		});
		let u = {
			calculations: a,
			init: t,
			formula: s,
			variables: n
		};
		if (f(i, w, "before-evaluate", u), !u.formula) {
			let e = a.value !== "";
			a.value = "", _(e);
		} else try {
			let e = r(u.formula, u.variables, o), n = {
				calculations: a,
				init: t,
				formula: u.formula,
				variables: u.variables,
				result: e
			};
			f(i, w, "after-evaluate", n);
			let s = typeof n.result == "string" || typeof n.result == "number" ? String(n.result) : "", c = a.value !== s;
			a.value = s, D.log("Evaluate complete.", {
				fieldHandle: i.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: s
			}), _(c);
		} catch (e) {
			let t = a.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), D.warn("Evaluate failed.", {
				fieldHandle: i.getAttribute("data-formie-field-handle") || null,
				error: e instanceof Error ? e.message : e
			}), a.value = "", _(t);
		}
	}, y = (e = !1) => {
		m || p || (m = !0, queueMicrotask(() => {
			m = !1, v(e);
		}));
	}, b = () => {
		g();
		let t = k(e, c, l);
		if (D.log("Binding variable watchers.", {
			fieldHandle: i.getAttribute("data-formie-field-handle") || null,
			watchCount: t.size
		}), !t.size) return;
		let n = (e) => {
			let n = e.target?.name || "";
			n && t.has(n) && (D.log("Source change detected.", {
				fieldHandle: i.getAttribute("data-formie-field-handle") || null,
				sourceName: n,
				eventType: e.type
			}), y(!1));
		};
		["input", "change"].forEach((t) => {
			e.addEventListener(t, n);
			let r = u.get(e) || /* @__PURE__ */ new Map();
			r.set(t, n), u.set(e, r);
		});
	}, x = () => {
		h || p || (h = !0, queueMicrotask(() => {
			h = !1, b(), y(!1);
		}));
	};
	return b(), d = new MutationObserver(() => {
		x();
	}), d.observe(e, {
		childList: !0,
		subtree: !0
	}), v(!0), {
		destroy: () => {
			p = !0, d?.disconnect(), g();
		},
		reevaluate: () => y(!1)
	};
}
var j = {
	moduleId: `formie:${w}`,
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
		D.log("Module setup.", {
			fieldCount: n.length,
			formatting: t.formatting || null
		});
		let r = n.flatMap((n) => {
			let r = n.querySelector(x);
			return r instanceof HTMLInputElement ? [A(e.root, n, r, t)] : [];
		}), i = e.on("formie:conditions:evaluated", () => {
			r.forEach((e) => e.reevaluate());
		});
		return await e.emit("formie:module:calculations:init", { count: r.length }), { destroy: () => {
			D.log("Module destroy.", { fieldCount: r.length }), i(), r.forEach((e) => {
				e.destroy();
			}), e.emit("formie:module:calculations:destroy", {});
		} };
	}
};
//#endregion
export { j as calculationsModule };
