import { t as e } from "./debug-BV0DvdHx.js";
import { r as t, t as n } from "./field-references.keys-58ZSTrCW.js";
import { n as r, r as i } from "./field-references.resolver-CHwn0G0L.js";
import { c as a, i as o, r as s, s as c, t as l } from "./dist-FkODFcm-.js";
import { r as u, t as d } from "./shared-Bx9s0i0P.js";
//#region src/js/modules/fields/calculations.ts
var f = "input[data-formie-calculation-input]", p = "calculations", m = e("fields", "calculations");
function h(e, t, n) {
	let a = i(e), c = {};
	return t.forEach(([e, t]) => {
		c[e] = s(t, r(t.sourceKey || "", a).value);
	}), o(c, n.formatting);
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
	let i = c(r), o = a(r), s = /* @__PURE__ */ new Map(), u = null, f = !1, _ = !1, v = !1, y = () => {
		s.forEach((e, t) => {
			e.forEach((e, n) => {
				t.removeEventListener(n, e);
			});
		}), s.clear();
	}, b = (e) => {
		!e || f || queueMicrotask(() => {
			f || (n.dispatchEvent(new Event("input", { bubbles: !0 })), n.dispatchEvent(new Event("change", { bubbles: !0 })));
		});
	}, x = (a = !1) => {
		let s = h(e, o, r);
		m.log("Evaluate requested.", {
			fieldHandle: t.getAttribute("data-formie-field-handle") || null,
			isInit: a
		});
		let c = {
			calculations: n,
			init: a,
			formula: i,
			variables: s
		};
		if (d(t, p, "before-evaluate", c), !c.formula) {
			let e = n.value !== "";
			n.value = "", b(e);
			return;
		}
		try {
			let e = l(c.formula, c.variables, r), i = {
				calculations: n,
				init: a,
				formula: c.formula,
				variables: c.variables,
				result: e
			};
			d(t, p, "after-evaluate", i);
			let o = typeof i.result == "string" || typeof i.result == "number" ? String(i.result) : "", s = n.value !== o;
			n.value = o, m.log("Evaluate complete.", {
				fieldHandle: t.getAttribute("data-formie-field-handle") || null,
				valueChanged: s,
				nextValue: o
			}), b(s);
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
		let n = g(e, o);
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
			let n = s.get(e) || /* @__PURE__ */ new Map();
			n.set(t, r), s.set(e, n);
		});
	}, w = () => {
		v || f || (v = !0, queueMicrotask(() => {
			v = !1, C(), S(!1);
		}));
	};
	return C(), u = new MutationObserver(() => {
		w();
	}), u.observe(e, {
		childList: !0,
		subtree: !0
	}), x(!0), () => {
		f = !0, u?.disconnect(), y();
	};
}
var v = {
	id: p,
	kind: "field",
	match: (e) => !!e.target.querySelector(f),
	setup: async (e) => {
		let t = e.options || {}, n = u(e);
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
