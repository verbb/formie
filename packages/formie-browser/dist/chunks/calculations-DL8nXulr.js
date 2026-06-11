import { t as e } from "./debug-BV0DvdHx.js";
import { r as t, t as n } from "./field-references.keys-58ZSTrCW.js";
import { n as r, r as i } from "./field-references.resolver-CHwn0G0L.js";
import { r as a, t as o } from "./shared-Bx9s0i0P.js";
import { c as s, i as c, r as l, s as u, t as d } from "./dist-FkODFcm-.js";
//#region src/js/modules/fields/calculations.ts
var f = "input[data-formie-calculation-input]", p = "calculations", m = e("fields", "calculations");
function h(e, t, n) {
	let a = i(e), o = {};
	return t.forEach(([e, t]) => {
		o[e] = l(t, r(t.sourceKey || "", a).value);
	}), c(o, n.formatting);
}
function g(e, r) {
	let a = i(e), o = /* @__PURE__ */ new Set();
	return r.forEach(([, e]) => {
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
function _(e, t, n, r) {
	let i = u(r), a = s(r), c = /* @__PURE__ */ new Map(), l = null, f = !1, _ = !1, v = !1, y = () => {
		c.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), c.clear();
	}, b = (e) => {
		!e || f || queueMicrotask(() => {
			f || (n.dispatchEvent(new Event("input", { bubbles: !0 })), n.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, x = (s = !1) => {
		let c = h(e, a, r);
		m.log("Evaluate requested.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			isInit: s
		});
		let l = {
			calculations: n,
			init: s,
			formula: i,
			variables: c
		};
		if (o(t, p, "before-evaluate", l), !l.formula) {
			let e = n.value !== "";
			n.value = "", b(e);
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
			o(t, p, "after-evaluate", i);
			let a = typeof i.result == "string" || typeof i.result == "number" ? String(i.result) : "", c = n.value !== a;
			n.value = a, m.log("Evaluate complete.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				valueChanged: c,
				nextValue: a
			}), b(c);
		} catch (e) {
			let r = n.value !== "";
			console.error("[formie] Failed to evaluate calculation.", e), m.warn("Evaluate failed.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				error: e instanceof Error ? e.message : e
			}), n.value = "", b(r);
		}
	}, S = (e = !1) => {
		_ || f || (_ = !0, queueMicrotask(() => {
			_ = !1, x(e);
		}));
	}, C = () => {
		y();
		let n = g(e, a);
		if (m.log("Binding variable watchers.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			watchCount: n.size
		}), !n.size) return;
		let r = (e) => {
			let r = e.target?.name || "";
			!r || !n.has(r) || (m.log("Source change detected.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				sourceName: r,
				eventType: e.type
			}), S(!1));
		};
		["input", "change"].forEach((t) => {
			e.addEventListener(t, r);
			let n = c.get(e) || /* @__PURE__ */ new Map();
			n.set(t, r), c.set(e, n);
		});
	}, w = () => {
		v || f || (v = !0, queueMicrotask(() => {
			v = !1, C(), S(!1);
		}));
	};
	return C(), l = new MutationObserver(() => {
		w();
	}), l.observe(e, {
		childList: !0,
		subtree: !0
	}), x(!0), () => {
		f = !0, l?.disconnect(), y();
	};
}
var v = {
	id: p,
	kind: "field",
	match: (e) => !!e.target.querySelector(f),
	setup: async (e) => {
		let t = e.options || {}, n = a(e);
		m.log("Module setup.", {
			fieldCount: n.length,
			formatting: t.formatting || null
		});
		let r = n.map((n) => {
			let r = n.querySelector(f);
			return r instanceof HTMLInputElement ? _(e.root, n, r, t) : () => {};
		});
		return await e.emit("formie:module:calculations:init", { count: r.length }), { destroy: () => {
			m.log("Module destroy.", { fieldCount: r.length }), r.forEach((e) => {
				e();
			}), e.emit("formie:module:calculations:destroy", {});
		} };
	}
};
//#endregion
export { v as calculationsModule };
