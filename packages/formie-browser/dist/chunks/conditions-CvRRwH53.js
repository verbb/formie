import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./field-references.keys-58ZSTrCW.js";
import { n } from "./shared-Bx9s0i0P.js";
import { c as r, s as i } from "./dist-DXR-E56t.js";
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
function te(e) {
	e.querySelectorAll("input, select, textarea").forEach((e) => {
		(e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && (e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio" ? e.checked = !1 : e.type !== "hidden" && (e.value = "")), e instanceof HTMLSelectElement && (e.multiple ? Array.from(e.options).forEach((e) => {
			e.selected = !1;
		}) : e.selectedIndex = 0), e instanceof HTMLTextAreaElement && (e.value = ""));
	});
}
function ne(e, t) {
	let n = e.hasAttribute("data-formie-page"), r = n ? f : d, i = n ? _ : p, a = e.hasAttribute(r);
	return t ? (a || e.setAttribute(r, "true"), e.classList.contains(i) || e.classList.add(i)) : (a && e.removeAttribute(r), e.classList.contains(i) && e.classList.remove(i)), a !== t;
}
var S = "button[type=\"submit\"], button[data-formie-action], input[type=\"submit\"]";
function C(e, t) {
	if (e instanceof HTMLButtonElement || e instanceof HTMLInputElement) {
		if (t) {
			e.hasAttribute(l) || (e.hasAttribute("disabled") && e.setAttribute(u, "true"), e.setAttribute(l, "true")), e.setAttribute("disabled", "true");
			return;
		}
		e.hasAttribute(l) && (e.hasAttribute(u) ? (e.setAttribute("disabled", "true"), e.removeAttribute(u)) : e.removeAttribute("disabled"), e.removeAttribute(l));
	}
}
function w(e, t) {
	e.matches(S) && C(e, t), e.querySelectorAll(S).forEach((e) => {
		C(e, t);
	}), e.querySelectorAll("input, textarea, select").forEach((e) => {
		if (t) {
			e.hasAttribute(l) || (e.hasAttribute("disabled") && e.setAttribute(u, "true"), e.setAttribute(l, "true")), e.setAttribute("disabled", "true");
			return;
		}
		e.hasAttribute(l) && (e.hasAttribute(u) ? (e.setAttribute("disabled", "true"), e.removeAttribute(u)) : e.removeAttribute("disabled"), e.removeAttribute(l));
	});
}
function re(e) {
	return !e.hasAttribute(d) && !e.hasAttribute(f) && !e.hasAttribute(v) && !e.hasAttribute("hidden");
}
function ie(e) {
	let t = Array.from(e.querySelectorAll(ee)).filter((e) => re(e)).length;
	if (t > 0) {
		let n = String(t);
		e.getAttribute(b) !== n && e.setAttribute(b, n), e.hasAttribute(v) && e.removeAttribute(v), e.classList.contains(y) && e.classList.remove(y);
		return;
	}
	e.hasAttribute(b) && e.removeAttribute(b), e.hasAttribute(v) || e.setAttribute(v, "true"), e.classList.contains(y) || e.classList.add(y);
}
function T(e) {
	e.removeAttribute(g), e.classList.remove(m), e.classList.remove(h);
}
function E(e) {
	e.removeAttribute(d), e.removeAttribute(f), e.classList.remove(p), e.classList.remove(_);
}
function D(e) {
	let t = e.closest(x);
	for (; t;) ie(t), t = t.parentElement?.closest(x) || null;
}
function O(e, t, n, r = {}) {
	if (r.displayMode === "muted") return k(e, t);
	let i = !1;
	return (e.hasAttribute(g) || e.classList.contains(m) || e.classList.contains(h)) && (T(e), i = !0), i = ne(e, t) || i, w(e, t), D(e), t && n && i && te(e), i;
}
function k(e, t) {
	let n = !1;
	return t ? ((e.hasAttribute(d) || e.hasAttribute(f) || e.classList.contains(p) || e.classList.contains(_)) && (E(e), n = !0), e.hasAttribute(g) || (e.setAttribute(g, "true"), n = !0), e.classList.contains(m) || (e.classList.add(m), n = !0)) : ((e.hasAttribute(g) || e.classList.contains(m) || e.classList.contains(h)) && (T(e), n = !0), (e.hasAttribute(d) || e.hasAttribute(f) || e.classList.contains(p) || e.classList.contains(_)) && (E(e), n = !0)), D(e), n;
}
//#endregion
//#region src/js/modules/fields/conditions/references.ts
var A = "input, select, textarea", j = "[data-formie-repeater-item], [data-formie-table-row]";
function M(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function ae(e) {
	let t = e.querySelector(A);
	if (!t) return null;
	let n = t.getAttribute("name") || "", r = Array.from(n.matchAll(/\[(\d+)\]/g));
	return r.length && r[r.length - 1]?.[1] || null;
}
function N(e) {
	return e.closest(j);
}
function oe(e) {
	return Array.from(e.querySelectorAll(A)).filter((e) => M(e));
}
function se(e) {
	let t = e.getAttribute("name") || "";
	return Array.from(t.matchAll(/\[([^\]]+)\]/g)).map((e) => e[1] || "").filter(Boolean);
}
function ce(e, t) {
	if (!t) return !0;
	let n = t.split(/[.:]/).filter(Boolean);
	if (!n.length) return !0;
	let r = se(e);
	return r.length < n.length ? !1 : n.every((e, t) => r[r.length - n.length + t] === e);
}
function P(e, t) {
	if (!t) return e;
	let n = e.filter((e) => ce(e, t));
	return n.length ? n : e;
}
function F(e, t) {
	let n = N(e);
	if (!n) return t;
	let r = t.filter((e) => N(e) === n);
	return r.length ? r : t;
}
function I(e) {
	return !e.source?.target || !e.source.handle ? null : e.source.target === "field" || e.source.target === "submission" ? e.source : null;
}
function L(e, r, i) {
	let a = I(i);
	if (!a || a.target !== "field" || !a.handle) return [];
	let o = n(a.handle), s = Array.from(e.querySelectorAll(`[data-formie-field-handle="${o}"]`));
	if (s.length) return F(r, s).flatMap((e) => P(oe(e), a.selector));
	let c = t(a.handle), l = n(c), u = Array.from(e.querySelectorAll(`[name="${l}"]`)).filter((e) => M(e)), d = Array.from(e.querySelectorAll(`[name="${l}[]"]`)).filter((e) => M(e));
	if (u.length || d.length) return F(r, [...u, ...d]);
	if (!a.handle.includes("__ROW__")) return [];
	let f = ae(r);
	if (f) {
		let r = t(a.handle.replace(/__ROW__/g, f)), i = n(r), o = Array.from(e.querySelectorAll(`[name="${i}"]`)).filter((e) => M(e)), s = Array.from(e.querySelectorAll(`[name="${i}[]"]`)).filter((e) => M(e));
		if (o.length || s.length) return [...o, ...s];
	}
	let p = t(a.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+"), m = new RegExp(p);
	return Array.from(e.querySelectorAll("[name]")).filter((e) => M(e) && m.test(e.getAttribute("name") || ""));
}
//#endregion
//#region src/js/modules/fields/conditions/transforms.ts
function R(e) {
	return e == null ? "" : String(e);
}
function z(e) {
	if (typeof e == "boolean") return e;
	if (typeof e == "number") return e !== 0;
	let t = R(e).trim().toLowerCase();
	return !(!t || [
		"0",
		"false",
		"no",
		"off"
	].includes(t));
}
function B(e) {
	return e.toLowerCase().replace(/\b\w/g, (e) => e.toUpperCase());
}
function V(e, t) {
	let n = Number.isFinite(Number(t.decimals)) ? Number(t.decimals) : 0, r = t.decimalPoint ?? ".", i = t.thousandsSeparator ?? ",", [a, o = ""] = e.toFixed(n).split("."), s = a.replace(/\B(?=(\d{3})+(?!\d))/g, i);
	return n === 0 ? s : `${s}${r}${o}`;
}
function H(e) {
	return String(e).padStart(2, "0");
}
function U(e, t) {
	return [
		["Y", String(e.getFullYear())],
		["m", H(e.getMonth() + 1)],
		["d", H(e.getDate())],
		["j", String(e.getDate())],
		["H", H(e.getHours())],
		["h", H((e.getHours() + 11) % 12 + 1)],
		["i", H(e.getMinutes())],
		["A", e.getHours() >= 12 ? "PM" : "AM"],
		["F", e.toLocaleString(void 0, { month: "long" })]
	].reduce((e, [t, n]) => e.replaceAll(t, n), t);
}
function W(e) {
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
function le(e, t) {
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
			if (Number.isFinite(t) && e.trim() !== "") return V(t, r);
			let n = r.preset || "", i = n === "custom" ? r.pattern || "" : W(n);
			if (!i) return e;
			let a = new Date(e);
			return Number.isNaN(a.getTime()) ? e : U(a, i);
		}
		case "lower": return e.toLowerCase();
		case "upper": return e.toUpperCase();
		case "title": return B(e);
		case "capitalize": return e && e.charAt(0).toUpperCase() + e.slice(1);
		case "replace": {
			let t = r.search || "";
			return t ? e.split(t).join(r.replace || "") : e;
		}
		case "truncate": {
			let t = Math.max(1, Number.parseInt(r.length || "50", 10) || 50), n = r.suffix || "...";
			return e.length <= t ? e : `${e.slice(0, Math.max(0, t - n.length))}${n}`;
		}
		case "map": return z(e) ? r.trueLabel || "Yes" : r.falseLabel || "No";
		default: return e;
	}
}
function G(e, t) {
	if (!t) return e;
	let n = t.transformerId ? e.map((e) => le(e, t)) : e;
	return (n.length === 0 || n.every((e) => e.trim() === "")) && t.defaultValue ? [t.defaultValue] : n;
}
//#endregion
//#region src/js/modules/fields/conditions/submission-context.ts
var K = "data-formie-submission", q = "formie:submission-context-change";
function ue(e) {
	return !!e && typeof e == "object" && !Array.isArray(e);
}
function de(e, t = e) {
	return t.closest(`[${K}]`) || (e instanceof Element && e.hasAttribute("data-formie-submission") ? e : e.querySelector(`[${K}]`));
}
function fe(e, t = e) {
	let n = de(e, t)?.getAttribute(K);
	if (!n) return {};
	try {
		let e = JSON.parse(n);
		return ue(e) ? Object.fromEntries(Object.entries(e).map(([e, t]) => [e, t == null ? "" : String(t)])) : {};
	} catch (e) {
		return console.error("[formie] Invalid submission context JSON.", e), {};
	}
}
function pe(e, t, n = e) {
	let r = fe(e, n), i = String(t.handle || "").trim(), a = "";
	return i !== "" && (a = Object.prototype.hasOwnProperty.call(r, i) ? r[i] ?? "" : r[`submission${i.charAt(0).toUpperCase()}${i.slice(1)}`] ?? ""), a === "" && t.defaultValue && (a = t.defaultValue), G([a], t);
}
//#endregion
//#region src/js/modules/fields/conditions/values.ts
function me(e, t) {
	return e.name || `__condition_input_${t}`;
}
function J(e) {
	return (e.id ? e.ownerDocument.querySelector(`label[for="${e.id}"]`)?.textContent?.trim() : "") || e.closest("label")?.textContent?.trim() || "";
}
function he(e, t = "") {
	let n = e[0];
	if (!n) return [];
	if (n instanceof HTMLInputElement) {
		if (n.type === "checkbox") {
			let n = e.filter((e) => e instanceof HTMLInputElement && e.checked);
			return t === "label" ? n.map((e) => J(e)).filter(Boolean) : n.map((e) => e.value);
		}
		if (n.type === "radio") {
			let n = e.filter((e) => e instanceof HTMLInputElement && e.checked);
			return t === "label" ? n.map((e) => J(e)).filter(Boolean) : n.map((e) => e.value);
		}
		if (n.type === "file") return Array.from(n.files || []).map((e) => e.name);
	}
	return n instanceof HTMLSelectElement && n.multiple ? t === "label" ? Array.from(n.selectedOptions).map((e) => e.label || e.text) : Array.from(n.selectedOptions).map((e) => e.value) : n instanceof HTMLSelectElement && t === "label" ? Array.from(n.selectedOptions).map((e) => e.label || e.text) : e.map((e) => e.value);
}
function Y(e) {
	return ["input", "change"];
}
function ge(e, t = null) {
	let n = /* @__PURE__ */ new Map();
	return e.forEach((e, t) => {
		let r = me(e, t), i = n.get(r) || [];
		i.push(e), n.set(r, i);
	}), G(Array.from(n.values()).flatMap((e) => he(e, t?.selector || "")), t);
}
//#endregion
//#region src/js/modules/fields/conditions/evaluator.ts
function X(e) {
	return e.closest("[data-formie-conditionally-hidden]") || e.closest("[data-formie-page-hidden]") || e.closest("[hidden]") || e.closest("[aria-hidden=\"true\"]") ? !1 : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
function _e(e) {
	return e.length ? e.some((e) => X(e)) : null;
}
function ve(e, t, n = {}) {
	let a = n.root, o = n.from || a, s = e.conditions.map((e) => {
		let n = t(e), i = I(e), s = i?.target === "submission" && a && o ? pe(a, i, o) : ge(n, i);
		return r(e, s, { visibility: _e(n) });
	});
	return i(e, s);
}
//#endregion
//#region src/js/modules/fields/conditions.ts
var Z = 4, Q = e("conditions");
function $(e) {
	let t = /* @__PURE__ */ new Set();
	return e.filter((e) => !t.has(e) && (t.add(e), !0));
}
var ye = {
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
				sourceInputs: $(n.conditions.flatMap((n) => L(t, e, n)))
			}];
		}), u = e.options?.cpDisplayMode === "muted" ? "muted" : "hide", d = !1, f = () => {
			let n = !1;
			return r.forEach((r) => {
				let i = ve(r.settings, (e) => L(t, r.node, e), {
					root: t,
					from: r.node
				}), a = r.node.hasAttribute("data-formie-page") ? "hide" : u, o = O(r.node, i.shouldHide, r.settings.clearOnHide, { displayMode: a });
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
					for (let e = 0; e < Z && f(); e += 1) e === 3 && Q.warn("Reached max evaluation passes.", { maxPasses: Z });
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
				Y(e).forEach((n) => {
					e.addEventListener(n, t);
				}), n.push(() => {
					Y(e).forEach((n) => {
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
				let r = () => {
					m();
				};
				e.form.addEventListener(q, r), n.push(() => {
					e.form?.removeEventListener(q, r);
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
				"data-formie-row-hidden",
				K
			]
		}), g(), await e.emit("formie:module:conditions:init", { count: r.length }), Q.log("Module setup complete.", { entryCount: r.length }), { destroy: () => {
			o(), v.disconnect(), Q.log("Module destroy."), e.emit("formie:module:conditions:destroy", {});
		} };
	}
};
//#endregion
export { ye as conditionsModule };
