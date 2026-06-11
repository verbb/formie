import { t as e } from "./debug-BV0DvdHx.js";
import { r as t, t as n } from "./field-references.keys-58ZSTrCW.js";
import { n as r, r as i } from "./field-references.resolver-CHwn0G0L.js";
import { r as a, t as o } from "./shared-Bx9s0i0P.js";
import { c as s, i as c, r as l, s as u, t as d } from "./dist-BKMvLXjr.js";
//#region src/js/utils/field-references.row-scope.ts
var f = new Set([
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
			for (let r = e; r <= n; r++) r >= 1 && r <= t && i.push(r - 1);
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
	return r(e, t).value;
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
	let i = String(t.scope || "").trim().toLowerCase();
	if (!i || !f.has(i)) return r(e, n);
	let { fieldKey: a, columnKey: o } = h(e);
	if (!a || !o) return r(e, n);
	let s = g(a, o, n), c = s.map((e) => _(e, n));
	if (i === "count") return {
		key: `${a}.${o}`,
		value: String(s.length),
		found: !0
	};
	if (i === "first") return {
		key: s[0] || `${a}.0.${o}`,
		value: c[0] ?? "",
		found: s.length > 0
	};
	if (i === "last") return {
		key: s[s.length - 1] || `${a}.0.${o}`,
		value: c[c.length - 1] ?? "",
		found: s.length > 0
	};
	if (i === "index") return r(`${a}.${Number.parseInt(String(t.index ?? "0"), 10)}.${o}`, n);
	if (i === "all") {
		let e = c.flatMap((e) => Array.isArray(e) ? e : e === "" ? [] : [e]);
		return {
			key: `${a}.${o}`,
			value: e,
			found: e.length > 0
		};
	}
	if (i === "rows") {
		let e = m(String(t.rows || ""), s.length);
		if (e.length === 0) return {
			key: `${a}.${o}`,
			value: "",
			found: !1
		};
		if (e.length === 1) return {
			key: s[e[0]] || `${a}.${e[0]}.${o}`,
			value: c[e[0]] ?? "",
			found: !0
		};
		let n = e.flatMap((e) => {
			let t = c[e];
			return Array.isArray(t) ? t : t === "" ? [] : [t];
		});
		return {
			key: `${a}.${o}`,
			value: n,
			found: n.length > 0
		};
	}
	return r(e, n);
}
//#endregion
//#region src/js/modules/fields/calculations.ts
var b = "input[data-formie-calculation-input]", x = "calculations", S = e("fields", "calculations");
function C(e, t, n) {
	let a = i(e), o = {};
	return t.forEach(([e, t]) => {
		if (String(t.scope || "").trim()) {
			o[e] = l(t, y(t.sourceKey || "", t, a).value);
			return;
		}
		o[e] = l(t, r(t.sourceKey || "", a).value);
	}), c(o, n.formatting);
}
function w(e, r) {
	let a = i(e), o = /* @__PURE__ */ new Set();
	return r.forEach(([, e]) => {
		if (String(e.scope || "").trim()) {
			v(e.sourceKey || "", e, a).forEach((e) => {
				o.add(e);
			});
			return;
		}
		let r = t(e.sourceKey || ""), i = a.get(r);
		if (i?.names?.length) {
			i.names.forEach((e) => {
				o.add(e);
			});
			return;
		}
		let s = n(r);
		s && (o.add(s), o.add(`${s}[]`));
	}), o;
}
function T(e, t, n, r) {
	let i = u(r), a = s(r), c = /* @__PURE__ */ new Map(), l = null, f = !1, p = !1, m = !1, h = () => {
		c.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), c.clear();
	}, g = (e) => {
		!e || f || queueMicrotask(() => {
			f || (n.dispatchEvent(new Event("input", { bubbles: !0 })), n.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, _ = (s = !1) => {
		let c = C(e, a, r);
		S.log("Evaluate requested.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			isInit: s
		});
		let l = {
			calculations: n,
			init: s,
			formula: i,
			variables: c
		};
		if (o(t, x, "before-evaluate", l), !l.formula) {
			let e = n.value !== "";
			n.value = "", g(e);
			return;
		}
		try {
			let e = d(l.formula, l.variables, r), i = {
				calculations: n,
				init: s,
				formula: l.formula,
				variables: l.variables,
				result: e
			};
			o(t, x, "after-evaluate", i);
			let a = typeof i.result == "string" || typeof i.result == "number" ? String(i.result) : "", c = n.value !== a;
			n.value = a, S.log("Evaluate complete.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: a
			}), g(c);
		} catch (e) {
			let r = n.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), S.warn("Evaluate failed.", {
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
		let n = w(e, a);
		if (S.log("Binding variable watchers.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			watchCount: n.size
		}), !n.size) return;
		let r = (e) => {
			let r = e.target?.name || "";
			!r || !n.has(r) || (S.log("Source change detected.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				sourceName: r,
				eventType: e.type
			}), v(!1));
		};
		["input", "change"].forEach((t) => {
			e.addEventListener(t, r);
			let n = c.get(e) || /* @__PURE__ */ new Map();
			n.set(t, r), c.set(e, n);
		});
	}, b = () => {
		m || f || (m = !0, queueMicrotask(() => {
			m = !1, y(), v(!1);
		}));
	};
	return y(), l = new MutationObserver(() => {
		b();
	}), l.observe(e, {
		childList: !0,
		subtree: !0
	}), _(!0), () => {
		f = !0, l?.disconnect(), h();
	};
}
var E = {
	id: x,
	kind: "field",
	match: (e) => !!e.target.querySelector(b),
	setup: async (e) => {
		let t = e.options || {}, n = a(e);
		S.log("Module setup.", {
			fieldCount: n.length,
			formatting: t.formatting || null
		});
		let r = n.map((n) => {
			let r = n.querySelector(b);
			return r instanceof HTMLInputElement ? T(e.root, n, r, t) : () => {};
		});
		return await e.emit("formie:module:calculations:init", { count: r.length }), { destroy: () => {
			S.log("Module destroy.", { fieldCount: r.length }), r.forEach((e) => {
				e();
			}), e.emit("formie:module:calculations:destroy", {});
		} };
	}
};
//#endregion
export { E as calculationsModule };
