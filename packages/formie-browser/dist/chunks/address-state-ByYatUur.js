import { t as e } from "./request-profile-DhwkeCpS.js";
import { t } from "./dist-ZLeW0zZ3.js";
import { t as n } from "./debug-BV0DvdHx.js";
import { a as r, n as i } from "./theme-classes-DAQuEqdP.js";
import { n as a } from "./constants-DVcJAvc5.js";
import { i as o, n as s } from "./shared-OKhSay59.js";
import { initFormieCombobox as c } from "./combobox-CWNdx4Fk.js";
//#region src/js/modules/fields/address-state.ts
var l = "[data-formie-address-state-dynamic]", u = "[data-formie-address-state-input]", d = "[data-formie-address-state-autofill-anchor]", f = "formie-address-autofill-start", p = a.country, m = "address-state", h = "formie/address/subdivisions", g = ["formie-select", "formie-dropdown-input"], _ = [
	0,
	100,
	300
], v = n("fields", "address-state"), y = /* @__PURE__ */ new Map(), b = /* @__PURE__ */ new Map();
function x(e) {
	return e.closest("[data-formie-field-type=\"address\"]") || e.closest("[data-formie-address-field-layout]")?.closest("[data-formie-field]") || e.closest("[data-formie-field]");
}
function S(e) {
	let t = e.querySelector(p);
	return t instanceof HTMLInputElement || t instanceof HTMLSelectElement ? t : null;
}
function C(e) {
	let t = e.querySelector(l);
	return t instanceof HTMLInputElement || t instanceof HTMLSelectElement ? t : null;
}
function w(e) {
	return e.querySelector("[data-formie-field-label]");
}
function T(e) {
	e.dispatchEvent(new Event("input", { bubbles: !0 })), e.dispatchEvent(new Event("change", { bubbles: !0 }));
}
function E(e, t) {
	if (!(e instanceof HTMLSelectElement)) return;
	let n = e._formieTomSelect;
	n && n.getValue() !== t && n.setValue(t, !0);
}
function D(e, t) {
	if (e.value !== t) {
		e.value = t, T(e);
		return;
	}
	E(e, t);
}
function O(e, t) {
	e.autofillAnchor && (e.autofillAnchor.value = t);
}
function k(e) {
	let t = e.autofillAnchor?.value?.trim() || "", n = e.stateControl.value?.trim() || "", r = t || n;
	r && (e.pendingStateValue = r);
}
function A(e) {
	return e.pendingStateValue?.trim() || e.autofillAnchor?.value?.trim() || e.stateControl.value?.trim() || "";
}
function j(e) {
	if (e.autofillAnchor) return e.autofillAnchor;
	let t = e.addressRoot.querySelector(d);
	if (t instanceof HTMLInputElement) e.autofillAnchor = t;
	else {
		let t = document.createElement("input");
		t.type = "text", t.setAttribute("data-formie-address-state-autofill-anchor", "true"), t.setAttribute("autocomplete", "address-level1"), t.setAttribute("tabindex", "-1"), t.setAttribute("aria-hidden", "true"), t.className = "formie-sr-only", e.addressRoot.appendChild(t), e.autofillAnchor = t;
	}
	return e.stateControl.setAttribute("autocomplete", "off"), e.stateControl.value && !e.autofillAnchor.value && (e.autofillAnchor.value = e.stateControl.value), e.autofillAnchor;
}
function M(e) {
	k(e);
	let t = A(e);
	if (!t) return;
	let n = e.lastSubdivisions, r = n.length > 0 && L(t, n) || t;
	D(e.stateControl, r), E(e.stateControl, r), O(e, r), e.pendingStateValue = r;
}
function N(e, t, n, r) {
	let i = new URL(/^https?:\/\//.test(e) || e.startsWith("/") ? e : `/actions/${e}`, window.location.origin);
	return i.searchParams.set("country", t), i.searchParams.set("optionLabel", n), i.searchParams.set("optionValue", r), i.toString();
}
function P(e, t, n, r) {
	return [
		e,
		t,
		n,
		r
	].join("|");
}
function F(e) {
	return y.has(e);
}
async function I(e, n, r, i, a) {
	let o = P(e, n, r, i);
	return y.has(o) ? y.get(o) || null : (b.has(o) || b.set(o, (async () => {
		try {
			let s = await t(N(i, e, n, r), { headers: { Accept: "application/json" } }, a);
			if (!s.ok) return y.set(o, null), null;
			let c = await s.json();
			return y.set(o, c), c;
		} catch (t) {
			return v.warn("Failed fetching subdivisions.", {
				country: e,
				error: t
			}), y.set(o, null), null;
		} finally {
			b.delete(o);
		}
	})()), b.get(o) || null);
}
function L(e, t) {
	let n = e.trim().toLowerCase();
	if (!n) return null;
	for (let e of t) if (e.value.toLowerCase() === n || (e.name || "").toLowerCase() === n || (e.short || "").toLowerCase() === n || e.label.toLowerCase() === n) return e.value;
	return null;
}
function R(e, t) {
	[
		"id",
		"name",
		"required",
		"disabled",
		"placeholder",
		"aria-describedby",
		"data-formie-input-id",
		"data-formie-input-type",
		"data-formie-input-error-state",
		"data-formie-address-state-dynamic",
		"data-formie-address-state-hide-when-unused",
		"data-formie-address-state-use-searchable",
		"data-formie-address-state-use-datalist",
		"data-formie-address-state-option-label",
		"data-formie-address-state-option-value"
	].forEach((n) => {
		let r = e.getAttribute(n);
		if (r === null) {
			t.removeAttribute(n);
			return;
		}
		t.setAttribute(n, r);
	});
}
function z(e, t) {
	t.className = e.className;
}
function B(e, t) {
	let n = [...g];
	i(e, "fieldControlError").forEach((t) => {
		e.classList.contains(t) && n.push(t);
	}), t.className = n.join(" ");
}
function V(e, t) {
	return e.parentNode?.replaceChild(t, e), t;
}
function H(e, t) {
	let n = document.createElement("input");
	return n.type = "text", R(e, n), z(e, n), n.setAttribute("data-formie-input-type", "text"), n.setAttribute("data-formie-address-state-input", "true"), n.setAttribute("data-formie-single-line-text-input", "true"), n.setAttribute("autocomplete", "off"), n.removeAttribute("data-formie-combobox-input"), n.value = t, n;
}
function U(e, t, n, r = "") {
	e.innerHTML = "";
	let i = document.createElement("option");
	i.value = "", i.textContent = n || "", e.appendChild(i), t.forEach((t) => {
		let n = document.createElement("option");
		n.value = t.value, n.textContent = t.label, e.appendChild(n);
	}), e.value = L(r, t) || r;
}
function W(e, t, n, r) {
	let i = document.createElement("select");
	return R(e, i), B(e, i), i.setAttribute("data-formie-input-type", "select"), i.setAttribute("data-formie-address-state-input", "true"), i.setAttribute("data-formie-select", "true"), i.setAttribute("data-formie-dropdown-input", "true"), i.removeAttribute("data-formie-single-line-text-input"), i.setAttribute("autocomplete", "off"), U(i, n, r, t), i;
}
function G(e, t, n, r) {
	let i = e.list;
	if (!r || n.length === 0) {
		e.removeAttribute("list"), i && i.remove();
		return;
	}
	let a = e.ownerDocument.getElementById(t);
	a || (a = e.ownerDocument.createElement("datalist"), a.id = t, e.insertAdjacentElement("afterend", a)), a.innerHTML = "", n.forEach((t) => {
		let n = e.ownerDocument.createElement("option");
		n.value = t.label, a?.appendChild(n);
	}), e.setAttribute("list", t);
}
function K(e, t) {
	let { stateField: n, stateControl: i, required: a } = e;
	if (r(n, n, "conditionalHidden", !t), n.toggleAttribute("data-formie-conditionally-hidden", !t), !t) {
		i.required = !1, i.disabled = !0;
		return;
	}
	i.disabled = !1, i.required = a;
}
function q(e) {
	if (e.fetchingAnnouncementEl) return e.fetchingAnnouncementEl;
	let t = document.createElement("div");
	return t.className = "formie-sr-only", t.setAttribute("data-formie-address-state-fetching-announce", "true"), t.setAttribute("aria-live", "polite"), t.setAttribute("aria-atomic", "true"), e.addressRoot.appendChild(t), e.fetchingAnnouncementEl = t, t;
}
function J() {
	let e = document.createElement("div");
	e.className = "formie-address-state-skeleton", e.setAttribute("data-formie-address-state-skeleton", "true"), e.setAttribute("aria-hidden", "true");
	let t = document.createElement("div");
	return t.className = "formie-address-state-skeleton-input", e.appendChild(t), e;
}
function Y(e) {
	e.addressRoot.setAttribute("data-formie-address-state-fetching", "true"), e.countryControl?.setAttribute("aria-busy", "true"), e.stateControl.setAttribute("aria-hidden", "true"), e.stateControl.setAttribute("tabindex", "-1"), r(e.stateField, e.stateField, "conditionalHidden", !1), e.stateField.removeAttribute("data-formie-conditionally-hidden"), e.stateField.setAttribute("data-formie-address-state-skeleton-active", "true");
	let t = e.stateField.querySelector("[data-formie-field-control]");
	t instanceof HTMLElement && (e.skeletonEl || (e.skeletonEl = J(), t.appendChild(e.skeletonEl)), e.skeletonEl.removeAttribute("hidden")), q(e).textContent = "Loading state or province options for the selected country.";
}
function X(e) {
	e.addressRoot.removeAttribute("data-formie-address-state-fetching"), e.countryControl?.removeAttribute("aria-busy"), e.stateControl.removeAttribute("aria-hidden"), e.stateControl.removeAttribute("tabindex"), e.stateField.removeAttribute("data-formie-address-state-skeleton-active"), e.skeletonEl?.setAttribute("hidden", "hidden"), e.fetchingAnnouncementEl && (e.fetchingAnnouncementEl.textContent = "");
}
function Z(e, t) {
	let n = w(e);
	if (!n) return;
	let r = n.querySelector("[data-formie-field-required]");
	n.textContent = t, r && n.appendChild(r);
}
function Q(e, t) {
	e.setAttribute("data-formie-combobox-input", "true"), s(e, "combobox", "before-init", {
		select: e,
		options: { placeholder: t }
	});
	let n = c(e, { placeholder: t });
	return s(e, "combobox", "after-init", {
		combobox: e._formieTomSelect,
		options: { placeholder: t }
	}), n;
}
async function $(t, n) {
	let { hideWhenUnused: r = !0, useSearchable: i = !0, useDatalist: a = !0, optionLabel: o = "name", optionValue: s = "name", placeholder: c = null, subdivisionsAction: l = h } = n, u = t.countryControl?.value?.trim() || "";
	k(t);
	let d = A(t), f = ++t.fetchGeneration;
	if (t.comboboxCleanup?.(), t.comboboxCleanup = null, !u) {
		if (Z(t.stateField, t.stateField.dataset.formieAddressStateDefaultLabel || "State / Province"), X(t), r) {
			K(t, !1), D(t.stateControl, ""), O(t, ""), t.pendingStateValue = "", t.lastSubdivisions = [];
			return;
		}
		if (K(t, !0), t.stateControl instanceof HTMLSelectElement) {
			let e = H(t.stateControl, d);
			t.stateControl = V(t.stateControl, e);
		} else t.stateControl.disabled = !0, t.stateControl.placeholder = c || t.stateControl.placeholder;
		t.lastSubdivisions = [];
		return;
	}
	F(P(u, o, s, l)) || Y(t);
	let p = await I(u, o, s, l, e(t.addressRoot.closest("form")));
	if (f !== t.fetchGeneration) return;
	let m = p?.subdivisions || [], g = p?.administrativeAreaUsed ?? !0, _ = p?.administrativeAreaLabel || "State / Province";
	if (t.lastSubdivisions = m, X(t), r && !g) {
		K(t, !1), D(t.stateControl, ""), O(t, ""), t.pendingStateValue = "";
		return;
	}
	if (K(t, !0), Z(t.stateField, _), m.length > 0) {
		let e = t.stateControl instanceof HTMLSelectElement ? t.stateControl : W(t.stateControl, d, m, c);
		t.stateControl === e ? (B(t.stateControl, e), U(e, m, c, e.value)) : t.stateControl = V(t.stateControl, e), i ? t.comboboxCleanup = Q(e, c) : e.removeAttribute("data-formie-combobox-input"), D(e, L(d, m) || d), M(t);
		return;
	}
	let v = t.stateControl instanceof HTMLInputElement ? t.stateControl : H(t.stateControl, d);
	t.stateControl !== v && (t.stateControl = V(t.stateControl, v)), v.disabled = !1, G(v, t.datalistId, m, a), D(v, d), M(t);
}
function ee(e, t) {
	let n = () => {
		k(e), e.countryControl?.value?.trim() && (e.lastCountry = "", $(e, t).then(() => {
			M(e);
		}));
	};
	_.forEach((t) => {
		let r = window.setTimeout(n, t);
		e.autofillSweepTimers.push(r);
	});
}
function te(e) {
	e.countryChangeTimer !== null && (window.clearTimeout(e.countryChangeTimer), e.countryChangeTimer = null), e.autofillSweepTimers.forEach((e) => {
		window.clearTimeout(e);
	}), e.autofillSweepTimers = [];
}
function ne(e, t) {
	let n = x(e);
	if (!n) return v.warn("Address root not found; skipping field."), () => {};
	let r = C(e);
	if (!r) return v.warn("Dynamic state control not found; skipping field."), () => {};
	let i = w(e);
	i && !e.dataset.formieAddressStateDefaultLabel && (e.dataset.formieAddressStateDefaultLabel = i.textContent?.trim() || "State / Province");
	let a = {
		addressRoot: n,
		stateField: e,
		stateControl: r,
		countryControl: S(n),
		autofillAnchor: null,
		pendingStateValue: r.value?.trim() || "",
		datalistId: `formie-address-state-datalist-${r.getAttribute("data-formie-input-id") || Math.random().toString(36).slice(2)}`,
		comboboxCleanup: null,
		skeletonEl: null,
		fetchingAnnouncementEl: null,
		autofillSweepTimers: [],
		countryChangeTimer: null,
		lastCountry: "",
		fetchGeneration: 0,
		required: r.required,
		lastSubdivisions: []
	};
	j(a);
	let o = (e = !1) => {
		let n = a.countryControl?.value?.trim() || "";
		if (!e && n === a.lastCountry) return;
		let r = a.lastCountry, i = A(a);
		a.lastCountry = n, $(a, t).then(() => {
			M(a), r && n && r !== n && !L(i, a.lastSubdivisions) && !A(a) && (D(a.stateControl, ""), O(a, ""));
		});
	}, s = () => {
		a.countryChangeTimer !== null && window.clearTimeout(a.countryChangeTimer), a.countryChangeTimer = window.setTimeout(() => {
			a.countryChangeTimer = null, k(a), o(!0);
		}, 50);
	}, c = (e) => {
		let n = e.target;
		if (!(n instanceof HTMLInputElement || n instanceof HTMLSelectElement)) return;
		let r = n === a.autofillAnchor, i = n.matches(l) || n.matches(u);
		if ((r || i) && (i && !r && O(a, n.value), k(a), a.countryControl?.value?.trim())) {
			if (a.lastSubdivisions.length > 0) {
				M(a);
				return;
			}
			a.lastCountry = "", $(a, t).then(() => {
				M(a);
			});
		}
	}, d = (e) => {
		if (e.animationName !== f) return;
		let r = e.target;
		(r instanceof HTMLInputElement || r instanceof HTMLSelectElement) && n.contains(r) && (k(a), a.countryControl?.value?.trim() && (a.lastCountry = "", $(a, t).then(() => {
			M(a);
		})));
	}, p = (e) => {
		let n = e.detail?.state?.trim();
		if (!n) {
			o();
			return;
		}
		a.pendingStateValue = n, O(a, n), $(a, t).then(() => {
			D(a.stateControl, n), E(a.stateControl, n), a.lastCountry = a.countryControl?.value?.trim() || "";
		});
	};
	return a.countryControl?.addEventListener("change", s), a.countryControl?.addEventListener("input", s), n.addEventListener("input", c), n.addEventListener("change", c), n.addEventListener("animationstart", d), n.addEventListener("formie:address:google:populate", p), n.addEventListener("formie:address:address-finder:populate", p), n.addEventListener("formie:address:loqate:populate", p), n.addEventListener("formie:address:place-kit:populate", p), o(), ee(a, t), () => {
		te(a), X(a), a.skeletonEl?.remove(), a.fetchingAnnouncementEl?.remove(), a.autofillAnchor?.remove(), a.comboboxCleanup?.(), a.countryControl?.removeEventListener("change", s), a.countryControl?.removeEventListener("input", s), n.removeEventListener("input", c), n.removeEventListener("change", c), n.removeEventListener("animationstart", d), n.removeEventListener("formie:address:google:populate", p), n.removeEventListener("formie:address:address-finder:populate", p), n.removeEventListener("formie:address:loqate:populate", p), n.removeEventListener("formie:address:place-kit:populate", p);
	};
}
var re = {
	moduleId: `formie:${m}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => !!e.target.querySelector(l),
	setup: async (e) => {
		let t = e.options || {}, n = o(e), r = n.map((e) => ne(e, t));
		return v.log("Module setup.", { fieldCount: n.length }), { destroy: () => {
			r.forEach((e) => e()), v.log("Module destroy.", { fieldCount: n.length });
		} };
	}
};
//#endregion
export { re as addressStateModule };
