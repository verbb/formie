import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./field-references.keys-58ZSTrCW.js";
import { n } from "./shared-Bx9s0i0P.js";
import { a as r, o as i } from "./dist-BaHXfvBO.js";
//#region src/js/modules/fields/conditions/config.ts
var a = "[data-formie-conditions]";
function o(e) {
	if (!e || typeof e != "object") return null;
	let t = e, n = t.transformerParams;
	return {
		raw: typeof t.raw == "string" ? t.raw : "",
		target: typeof t.target == "string" ? t.target : "",
		handle: typeof t.handle == "string" ? t.handle : "",
		selector: typeof t.selector == "string" ? t.selector : "",
		defaultValue: typeof t.defaultValue == "string" ? t.defaultValue : "",
		transformerId: typeof t.transformerId == "string" ? t.transformerId : "",
		transformerParams: n && typeof n == "object" ? Object.fromEntries(Object.entries(n).map(([e, t]) => [e, String(t ?? "")])) : {},
		isValid: t.isValid !== !1
	};
}
function s(e) {
	let t = Array.from(e.querySelectorAll(a));
	return e.matches("[data-formie-conditions]") ? [e, ...t] : t;
}
function c(e) {
	let t = e.getAttribute("data-formie-conditions");
	if (!t) return null;
	try {
		let e = JSON.parse(t), n = Array.isArray(e.conditions) ? e.conditions.filter((e) => {
			if (!e || typeof e != "object") return !1;
			let t = e;
			return typeof t.field == "string" && typeof t.condition == "string";
		}).map((e) => {
			let t = e;
			return {
				field: e.field,
				source: o(t.source),
				condition: e.condition,
				value: e.value
			};
		}) : [];
		return {
			showRule: e.showRule === "hide" ? "hide" : "show",
			conditionRule: e.conditionRule === "any" ? "any" : "all",
			clearOnHide: e.clearOnHide !== !1,
			isNested: !!e.isNested,
			conditions: n
		};
	} catch (e) {
		return console.error("[formie] Invalid condition JSON.", e), null;
	}
}
//#endregion
//#region src/js/modules/fields/conditions/effects.ts
var l = "data-formie-conditions-disabled", u = "data-formie-preserve-disabled", d = "data-formie-conditionally-hidden", f = "data-formie-page-hidden", p = "formie-conditionally-hidden", m = "fui-cp-muted-conditional-field", h = "fui-cp-muted-conditional-field--expanded", g = "data-formie-cp-muted", _ = "formie-page-hidden", v = "data-formie-row-hidden", y = "formie-row-hidden", b = "data-formie-field-count", x = "[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]", ee = ":scope > [data-formie-field]";
function S(e) {
	e.querySelectorAll("input, select, textarea").forEach((e) => {
		!(e instanceof HTMLInputElement) && !(e instanceof HTMLSelectElement) && !(e instanceof HTMLTextAreaElement) || (e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio" ? e.checked = !1 : e.type !== "hidden" && (e.value = "")), e instanceof HTMLSelectElement && (e.multiple ? Array.from(e.options).forEach((e) => {
			e.selected = !1;
		}) : e.selectedIndex = 0), e instanceof HTMLTextAreaElement && (e.value = ""));
	});
}
function te(e, t) {
	let n = e.hasAttribute("data-formie-page"), r = n ? f : d, i = n ? _ : p, a = e.hasAttribute(r);
	return t ? (a || e.setAttribute(r, "true"), e.classList.contains(i) || e.classList.add(i)) : (a && e.removeAttribute(r), e.classList.contains(i) && e.classList.remove(i)), a !== t;
}
var C = "button[type=\"submit\"], button[data-formie-action], input[type=\"submit\"]";
function w(e, t) {
	if (e instanceof HTMLButtonElement || e instanceof HTMLInputElement) {
		if (t) {
			e.hasAttribute(l) || (e.hasAttribute("disabled") && e.setAttribute(u, "true"), e.setAttribute(l, "true")), e.setAttribute("disabled", "true");
			return;
		}
		e.hasAttribute(l) && (e.hasAttribute(u) ? (e.setAttribute("disabled", "true"), e.removeAttribute(u)) : e.removeAttribute("disabled"), e.removeAttribute(l));
	}
}
function T(e, t) {
	e.matches(C) && w(e, t), e.querySelectorAll(C).forEach((e) => {
		w(e, t);
	}), e.querySelectorAll("input, textarea, select").forEach((e) => {
		if (t) {
			e.hasAttribute(l) || (e.hasAttribute("disabled") && e.setAttribute(u, "true"), e.setAttribute(l, "true")), e.setAttribute("disabled", "true");
			return;
		}
		e.hasAttribute(l) && (e.hasAttribute(u) ? (e.setAttribute("disabled", "true"), e.removeAttribute(u)) : e.removeAttribute("disabled"), e.removeAttribute(l));
	});
}
function ne(e) {
	return !e.hasAttribute(d) && !e.hasAttribute(f) && !e.hasAttribute(v) && !e.hasAttribute("hidden");
}
function E(e) {
	let t = Array.from(e.querySelectorAll(ee)).filter((e) => ne(e)).length;
	if (t > 0) {
		let n = String(t);
		e.getAttribute(b) !== n && e.setAttribute(b, n), e.hasAttribute(v) && e.removeAttribute(v), e.classList.contains(y) && e.classList.remove(y);
		return;
	}
	e.hasAttribute(b) && e.removeAttribute(b), e.hasAttribute(v) || e.setAttribute(v, "true"), e.classList.contains(y) || e.classList.add(y);
}
function D(e) {
	e.removeAttribute(g), e.classList.remove(m), e.classList.remove(h);
}
function O(e) {
	e.removeAttribute(d), e.removeAttribute(f), e.classList.remove(p), e.classList.remove(_);
}
function k(e) {
	let t = e.closest(x);
	for (; t;) E(t), t = t.parentElement?.closest(x) || null;
}
function A(e, t, n, r = {}) {
	if (r.displayMode === "muted") return j(e, t);
	let i = !1;
	return (e.hasAttribute(g) || e.classList.contains(m) || e.classList.contains(h)) && (D(e), i = !0), i = te(e, t) || i, T(e, t), k(e), t && n && i && S(e), i;
}
function j(e, t) {
	let n = !1;
	return t ? ((e.hasAttribute(d) || e.hasAttribute(f) || e.classList.contains(p) || e.classList.contains(_)) && (O(e), n = !0), e.hasAttribute(g) || (e.setAttribute(g, "true"), n = !0), e.classList.contains(m) || (e.classList.add(m), n = !0)) : ((e.hasAttribute(g) || e.classList.contains(m) || e.classList.contains(h)) && (D(e), n = !0), (e.hasAttribute(d) || e.hasAttribute(f) || e.classList.contains(p) || e.classList.contains(_)) && (O(e), n = !0)), k(e), n;
}
//#endregion
//#region src/js/modules/fields/conditions/references.ts
var M = "input, select, textarea", re = "[data-formie-repeater-item], [data-formie-table-row]";
function N(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function ie(e) {
	let t = e.querySelector(M);
	if (!t) return null;
	let n = t.getAttribute("name") || "", r = Array.from(n.matchAll(/\[(\d+)\]/g));
	return r.length && r[r.length - 1]?.[1] || null;
}
function P(e) {
	return e.closest(re);
}
function F(e) {
	return Array.from(e.querySelectorAll(M)).filter((e) => N(e));
}
function I(e) {
	let t = e.getAttribute("name") || "";
	return Array.from(t.matchAll(/\[([^\]]+)\]/g)).map((e) => e[1] || "").filter(Boolean);
}
function L(e, t) {
	if (!t) return !0;
	let n = t.split(/[.:]/).filter(Boolean);
	if (!n.length) return !0;
	let r = I(e);
	return r.length < n.length ? !1 : n.every((e, t) => r[r.length - n.length + t] === e);
}
function R(e, t) {
	if (!t) return e;
	let n = e.filter((e) => L(e, t));
	return n.length ? n : e;
}
function z(e, t) {
	let n = P(e);
	if (!n) return t;
	let r = t.filter((e) => P(e) === n);
	return r.length ? r : t;
}
function B(e) {
	return e.source?.target === "field" && e.source.handle ? e.source : null;
}
function V(e, r, i) {
	let a = B(i);
	if (!a || a.target !== "field" || !a.handle) return [];
	let o = n(a.handle), s = Array.from(e.querySelectorAll(`[data-formie-field-handle="${o}"]`));
	if (s.length) return z(r, s).flatMap((e) => R(F(e), a.selector));
	let c = n(t(a.handle)), l = Array.from(e.querySelectorAll(`[name="${c}"]`)).filter((e) => N(e)), u = Array.from(e.querySelectorAll(`[name="${c}[]"]`)).filter((e) => N(e));
	if (l.length || u.length) return z(r, [...l, ...u]);
	if (!a.handle.includes("__ROW__")) return [];
	let d = ie(r);
	if (d) {
		let r = n(t(a.handle.replace(/__ROW__/g, d))), i = Array.from(e.querySelectorAll(`[name="${r}"]`)).filter((e) => N(e)), o = Array.from(e.querySelectorAll(`[name="${r}[]"]`)).filter((e) => N(e));
		if (i.length || o.length) return [...i, ...o];
	}
	let f = t(a.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+"), p = new RegExp(f);
	return Array.from(e.querySelectorAll("[name]")).filter((e) => N(e) && p.test(e.getAttribute("name") || ""));
}
//#endregion
//#region src/js/modules/fields/conditions/transforms.ts
function H(e) {
	return e == null ? "" : String(e);
}
function U(e) {
	if (typeof e == "boolean") return e;
	if (typeof e == "number") return e !== 0;
	let t = H(e).trim().toLowerCase();
	return !(!t || [
		"0",
		"false",
		"no",
		"off"
	].includes(t));
}
function W(e) {
	return e.toLowerCase().replace(/\b\w/g, (e) => e.toUpperCase());
}
function G(e, t) {
	let n = Number.isFinite(Number(t.decimals)) ? Number(t.decimals) : 0, r = t.decimalPoint ?? ".", i = t.thousandsSeparator ?? ",", [a, o = ""] = e.toFixed(n).split("."), s = a.replace(/\B(?=(\d{3})+(?!\d))/g, i);
	return n === 0 ? s : `${s}${r}${o}`;
}
function K(e) {
	return String(e).padStart(2, "0");
}
function q(e, t) {
	return [
		["Y", String(e.getFullYear())],
		["m", K(e.getMonth() + 1)],
		["d", K(e.getDate())],
		["j", String(e.getDate())],
		["H", K(e.getHours())],
		["h", K((e.getHours() + 11) % 12 + 1)],
		["i", K(e.getMinutes())],
		["A", e.getHours() >= 12 ? "PM" : "AM"],
		["F", e.toLocaleString(void 0, { month: "long" })]
	].reduce((e, [t, n]) => e.replaceAll(t, n), t);
}
function J(e) {
	switch (e) {
		case "datetimeUs12": return "m/d/Y h:i A";
		case "datetimeEu12": return "d/m/Y h:i A";
		case "datetimeEu24": return "d/m/Y H:i";
		case "datetimeIso24": return "Y-m-d H:i";
		case "dateUs": return "m/d/Y";
		case "dateEu": return "d/m/Y";
		case "isoDate": return "Y-m-d";
		case "dateLong": return "F j, Y";
		case "time12": return "h:i A";
		case "time24": return "H:i";
		default: return "";
	}
}
function ae(e, t) {
	let n = t.transformerId, r = t.transformerParams;
	switch (n) {
		case "round":
		case "floor":
		case "ceil": {
			let t = Number(e);
			return Number.isFinite(t) ? String(n === "round" ? Math.round(t) : n === "floor" ? Math.floor(t) : Math.ceil(t)) : e;
		}
		case "format": {
			let t = Number(e);
			if (Number.isFinite(t) && e.trim() !== "") return G(t, r);
			let n = r.preset || "", i = n === "custom" ? r.pattern || "" : J(n);
			if (!i) return e;
			let a = new Date(e);
			return Number.isNaN(a.getTime()) ? e : q(a, i);
		}
		case "lower": return e.toLowerCase();
		case "upper": return e.toUpperCase();
		case "title": return W(e);
		case "capitalize": return e && e.charAt(0).toUpperCase() + e.slice(1);
		case "replace": {
			let t = r.search || "";
			return t ? e.split(t).join(r.replace || "") : e;
		}
		case "truncate": {
			let t = Math.max(1, Number.parseInt(r.length || "50", 10) || 50), n = r.suffix || "...";
			return e.length <= t ? e : `${e.slice(0, Math.max(0, t - n.length))}${n}`;
		}
		case "map": return U(e) ? r.trueLabel || "Yes" : r.falseLabel || "No";
		default: return e;
	}
}
function oe(e, t) {
	if (!t) return e;
	let n = t.transformerId ? e.map((e) => ae(e, t)) : e;
	return (n.length === 0 || n.every((e) => e.trim() === "")) && t.defaultValue ? [t.defaultValue] : n;
}
//#endregion
//#region src/js/modules/fields/conditions/values.ts
function se(e, t) {
	return e.name || `__condition_input_${t}`;
}
function Y(e) {
	return (e.id ? e.ownerDocument.querySelector(`label[for="${e.id}"]`)?.textContent?.trim() : "") || e.closest("label")?.textContent?.trim() || "";
}
function ce(e, t = "") {
	let n = e[0];
	if (!n) return [];
	if (n instanceof HTMLInputElement) {
		if (n.type === "checkbox") {
			let n = e.filter((e) => e instanceof HTMLInputElement && e.checked);
			return t === "label" ? n.map((e) => Y(e)).filter(Boolean) : n.map((e) => e.value);
		}
		if (n.type === "radio") {
			let n = e.filter((e) => e instanceof HTMLInputElement && e.checked);
			return t === "label" ? n.map((e) => Y(e)).filter(Boolean) : n.map((e) => e.value);
		}
		if (n.type === "file") return Array.from(n.files || []).map((e) => e.name);
	}
	return n instanceof HTMLSelectElement && n.multiple ? t === "label" ? Array.from(n.selectedOptions).map((e) => e.label || e.text) : Array.from(n.selectedOptions).map((e) => e.value) : n instanceof HTMLSelectElement && t === "label" ? Array.from(n.selectedOptions).map((e) => e.label || e.text) : e.map((e) => e.value);
}
function X(e) {
	return ["input", "change"];
}
function le(e, t = null) {
	let n = /* @__PURE__ */ new Map();
	return e.forEach((e, t) => {
		let r = se(e, t), i = n.get(r) || [];
		i.push(e), n.set(r, i);
	}), oe(Array.from(n.values()).flatMap((e) => ce(e, t?.selector || "")), t);
}
//#endregion
//#region src/js/modules/fields/conditions/evaluator.ts
function ue(e) {
	return e.closest("[data-formie-conditionally-hidden]") || e.closest("[data-formie-page-hidden]") || e.closest("[hidden]") || e.closest("[aria-hidden=\"true\"]") ? !1 : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
function de(e) {
	return e.length ? e.some((e) => ue(e)) : null;
}
function fe(e, t) {
	return r(e, e.conditions.map((e) => {
		let n = t(e);
		return i(e, le(n, B(e)), { visibility: de(n) });
	}));
}
//#endregion
//#region src/js/modules/fields/conditions.ts
var Z = 4, Q = e("conditions");
function $(e) {
	let t = /* @__PURE__ */ new Set();
	return e.filter((e) => t.has(e) ? !1 : (t.add(e), !0));
}
var pe = {
	id: "conditions",
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches("[data-formie-conditions]") || !!e.target.querySelector("[data-formie-conditions]")),
	setup: async (e) => {
		let t = e.target instanceof HTMLElement ? e.target : e.root;
		if (!s(t).length) {
			Q.log("No condition nodes in scope.");
			return;
		}
		let n = [], r = [], i = !1, a = !1, o = () => {
			n.forEach((e) => {
				e();
			}), n.length = 0;
		}, l = () => s(t).flatMap((e) => {
			let n = c(e);
			return !n || !n.conditions.length ? [] : [{
				node: e,
				settings: n,
				sourceInputs: $(n.conditions.flatMap((n) => V(t, e, n)))
			}];
		}), u = e.options?.cpDisplayMode === "muted" ? "muted" : "hide", d = !1, f = () => {
			let n = !1;
			return r.forEach((r) => {
				let i = fe(r.settings, (e) => V(t, r.node, e)), a = r.node.hasAttribute("data-formie-page") ? "hide" : u, o = A(r.node, i.shouldHide, r.settings.clearOnHide, { displayMode: a });
				n ||= o, Q.log("Condition evaluated.", {
					shouldHide: i.shouldHide,
					finalResult: i.finalResult,
					stateChanged: o
				}), e.emit("formie:conditions:evaluated", {
					node: r.node,
					shouldHide: i.shouldHide,
					finalResult: i.finalResult,
					clearOnHide: r.settings.clearOnHide
				});
			}), n;
		}, p = () => {
			if (!d) {
				d = !0;
				try {
					for (let e = 0; e < Z && f(); e += 1) e === Z - 1 && Q.warn("Reached max evaluation passes.", { maxPasses: Z });
				} finally {
					d = !1;
				}
			}
		}, m = () => {
			d || i || (i = !0, requestAnimationFrame(() => {
				i = !1, !d && p();
			}));
		}, h = () => {
			if ($(r.flatMap((e) => e.sourceInputs)).forEach((e) => {
				let t = () => {
					m();
				};
				X(e).forEach((n) => {
					e.addEventListener(n, t);
				}), n.push(() => {
					X(e).forEach((n) => {
						e.removeEventListener(n, t);
					});
				});
			}), e.form) {
				let t = () => {
					window.setTimeout(() => {
						m();
					}, 0);
				};
				e.form.addEventListener("reset", t), n.push(() => {
					e.form?.removeEventListener("reset", t);
				});
			}
		}, g = () => {
			o(), r = l(), h(), Q.log("Rebuilt condition graph.", { entryCount: r.length }), m();
		}, _ = () => {
			d || a || (a = !0, requestAnimationFrame(() => {
				a = !1, !d && g();
			}));
		}, v = new MutationObserver((e) => {
			if (d) return;
			let t = e.some((e) => e.type === "childList" && (e.addedNodes.length > 0 || e.removedNodes.length > 0)), n = e.some((e) => e.type === "attributes");
			t ? _() : n && m();
		});
		return v.observe(t, {
			childList: !0,
			subtree: !0,
			attributes: !0,
			attributeFilter: [
				"hidden",
				"aria-hidden",
				"data-formie-conditionally-hidden",
				"data-formie-page-hidden",
				"data-formie-row-hidden"
			]
		}), g(), await e.emit("formie:module:conditions:init", { count: r.length }), Q.log("Module setup complete.", { entryCount: r.length }), { destroy: () => {
			o(), v.disconnect(), Q.log("Module destroy."), e.emit("formie:module:conditions:destroy", {});
		} };
	}
};
//#endregion
export { pe as conditionsModule };
