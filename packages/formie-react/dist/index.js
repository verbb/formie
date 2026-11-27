import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { Fragment as r, cloneElement as i, createContext as a, createElement as o, isValidElement as s, useContext as c, useEffect as l, useMemo as u, useRef as d, useState as f } from "react";
import { CLIENT_FORM_EVENT_NAMES as p, PHONE_COUNTRY_CHANGE_EVENT as m, VISUALLY_HIDDEN_STYLE as h, addressAutocompleteMarkup as g, addressPartMarker as _, addressStatePart as v, allOptionsSelected as y, attachPointerReorder as b, checkboxToggle as x, clientActionAllowed as S, compositePartDefinitions as C, createClientFormInstance as w, createGraphqlClientTransport as T, createRepeaterRowValue as E, createRestClientTransport as D, createTableRowValue as O, customFieldMarkup as k, datePickerInputValue as A, datePickerValue as j, dateRangePickerMarkup as M, dateRangePickerValue as N, displayFieldContent as P, dropdownOptions as F, fieldContentPath as I, fieldOptions as L, focusClientFormResult as R, getClientErrorAriaLive as z, getClientFieldIds as B, getClientFieldLabelTarget as V, getClientFieldLayout as H, getClientPageProgress as U, getClientString as W, isAddressAutocompletePart as ee, isAddressField as te, isArrayValuedField as ne, isCalculationField as re, isCompositeField as ie, isDatePickerField as ae, isDateRangePickerField as oe, isDynamicAddressStatePart as se, isFieldLabelHidden as ce, isFieldLabelVisuallyHidden as le, isFileField as ue, isKnownClientFieldType as de, isPhoneCountryEnabled as fe, isRepeatableField as pe, isRichTextField as me, isSearchableDropdown as he, joinIdReferences as ge, loadAddressSubdivisions as _e, loadClientFormBootstrap as ve, loadGraphqlClientFormBootstrap as ye, moveListItem as be, paymentFieldMarkup as xe, phoneFieldValue as Se, readAddressParts as Ce, readCustomFieldControls as we, readPhoneFieldControls as Te, readUploadManagerEntries as Ee, repeaterRowDefinitions as De, resolveClientRendererType as Oe, searchableDropdownMarkup as ke, searchableDropdownValue as Ae, showsClientPageTitle as je, summaryFieldMarkup as Me, surveyLikertRows as Ne, surveyRankOrder as Pe, tableColumns as Fe, textLimitCounter as Ie, toggleAllOptions as Le, uploadManagerMarkup as Re } from "@verbb/formie-core";
import { jsx as ze, jsxs as Be } from "react/jsx-runtime";
//#region src/stable.ts
function G(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => G(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${G(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function K(e) {
	return G(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var Ve = a(null);
function He(e) {
	return "definition" in e;
}
async function Ue(e) {
	return He(e) ? e.definition : e.transport === "graphql" ? ye({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : ve({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function We(e) {
	let t = He(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? T(t) : D(t);
}
function Ge({ errors: e }) {
	return e.length === 0 ? null : o("div", {
		className: "formie-react-errors",
		role: "alert",
		tabIndex: -1,
		"data-formie-form-message": !0
	}, o("ul", null, e.map((e, t) => o("li", { key: `${e}:${t}` }, e))));
}
function Ke({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: i, labelId: a, instructionsId: s, children: c }) {
	let { slots: l } = q(), u = (t, n, r) => {
		let i = l[t];
		return i ? o(i, {
			slotKey: t,
			children: n,
			attributes: r,
			field: e
		}) : n;
	}, d = H(e), f = e.label && !ce(e) ? u("label", o("label", {
		key: "label",
		className: "formie-react-label",
		style: le(e) ? h : void 0,
		id: a || void 0,
		htmlFor: i && V(e, J(e)) === "control" ? i : void 0
	}, e.label)) : null, p = e.instructions ? u("instructions", o("div", {
		key: "instructions",
		className: "formie-react-description",
		id: s || void 0,
		dangerouslySetInnerHTML: { __html: e.instructions }
	})) : null, m = u("input", o("div", {
		key: "input",
		className: "formie-react-input"
	}, c)), g = u("errors", o("ul", {
		key: "errors",
		id: n,
		className: "formie-react-field-errors",
		style: t.length === 0 ? { position: "absolute" } : void 0,
		"data-formie-field-errors": !0,
		"aria-live": r === "off" ? void 0 : r,
		"aria-atomic": r === "off" ? void 0 : "true"
	}, t.map((e, t) => o("li", { key: `${e}:${t}` }, e))), {
		id: n,
		style: t.length === 0 ? { position: "absolute" } : void 0,
		"data-formie-field-errors": !0,
		"aria-live": r === "off" ? void 0 : r,
		"aria-atomic": r === "off" ? void 0 : "true"
	}), _ = [
		d.instructions === "above" ? p : null,
		d.errors === "above" ? g : null,
		m,
		d.instructions === "below" ? p : null,
		d.errors === "below" ? g : null
	], v = d.label === "left" || d.label === "right" ? [o("div", {
		key: "content",
		className: "formie-react-field-content"
	}, _)] : _;
	return o("div", {
		className: "formie-react-field",
		"data-formie-field-type": e.type,
		"data-formie-field-uid": e.uid,
		"data-formie-field-handle": e.handle,
		"data-formie-field-required": e.required ? "true" : void 0,
		"data-formie-label-position": d.label,
		"data-formie-instructions-position": d.instructions,
		"data-formie-error-position": d.errors
	}, d.label === "below" || d.label === "right" ? [...v, f] : [f, ...v]);
}
function qe({ children: e }) {
	let { instance: t } = q(), r = d(null);
	return l(() => {
		let e = !1, i;
		return t.setBrowserModuleGuard(() => {
			throw Error("Form features are still loading.");
		}), n(r.current, t).then((t) => {
			e ? t.destroy() : i = t;
		}), () => {
			e = !0, i?.destroy();
		};
	}, [t]), o("div", { ref: r }, e);
}
function Je({ definition: e, session: t, state: n, children: r, className: i, onSubmit: a }) {
	let { instance: s } = q(), c = d(null), u = d(n.currentPageId), f = d(null);
	return l(() => s.on("formie:submit:result", () => {
		f.current = u.current;
	}), [s]), l(() => {
		f.current !== null && (R(c.current, f.current), f.current = null), u.current = n.currentPageId;
	}), o("form", {
		ref: c,
		className: i,
		onSubmit: async (e) => {
			e.preventDefault(), await a();
		},
		"data-formie-definition": e.handle,
		"data-formie-render-id": t.tokens.render
	}, r);
}
function Ye({ page: e, state: t, children: n }) {
	let r = je(t.definition) && !!e.label, i = `formie-${t.session.tokens.render || t.session.id}-page-${e.id}-title`;
	return o("section", {
		"data-page-id": e.id,
		"data-formie-page-id": e.id,
		className: "formie-react-page",
		tabIndex: -1,
		"aria-labelledby": r ? i : void 0,
		"aria-label": !r && t.definition.pages.length > 1 && e.label ? e.label : void 0
	}, r ? o("h3", {
		id: i,
		className: "formie-react-page-title"
	}, e.label) : null, n);
}
function Xe(e) {
	let t = U(e.definition, e.currentPageId);
	if (!t) return null;
	let n = o("span", {
		key: "value",
		className: "formie-react-progress-value",
		"aria-hidden": "true"
	}, `${t.percent}%`), r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition);
	return o("div", {
		key: "progress",
		className: "formie-react-progress",
		"data-formie-progress-position": t.position,
		"data-formie-progress-value-position": t.valuePosition,
		"data-formie-progress-state": t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle"
	}, [
		t.valuePosition === "left" ? n : null,
		o("div", {
			key: "track",
			className: "formie-react-progress-track",
			role: "progressbar",
			"aria-label": W(e.definition, "progress"),
			"aria-valuenow": t.percent,
			"aria-valuemin": 0,
			"aria-valuemax": 100
		}, o("div", {
			className: "formie-react-progress-bar",
			style: { width: `${t.percent}%` }
		}, r ? n : null)),
		t.valuePosition === "right" ? n : null
	]);
}
function q() {
	let e = c(Ve);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function J(e) {
	return Oe(e);
}
function Y(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function Ze(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function Qe(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function X(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": ge(Qe(e, t), n && t.errorId),
		autoComplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0,
		..._(e) ? { [_(e)]: "true" } : {}
	};
}
function $e(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function Z(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": ge(Qe(e, t))
	};
}
function Q(e, t, n, r, i, a = J(e)) {
	let s = e.input;
	if (a === "multi-line-text" && me(e)) return o("div", {
		className: "formie-react-rich-text",
		onInput: (e) => {
			e.target instanceof HTMLTextAreaElement && r(e.target.value);
		}
	}, o("div", {
		key: "editor",
		"data-formie-rich-text": "true"
	}), o("textarea", {
		key: "input",
		...X(e, i),
		"data-formie-multi-line-text-input": "true",
		style: { display: "none" },
		value: typeof t == "string" ? t : "",
		readOnly: !0,
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0
	}));
	if (a === "multi-line-text") return o("textarea", {
		...X(e, i),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (a === "dropdown" && he(e)) return o("div", {
		className: "formie-react-combobox",
		onInput: (t) => {
			t.target instanceof HTMLSelectElement && r(Ae(e, t.target));
		}
	}, o("div", { dangerouslySetInnerHTML: { __html: ke(e, {
		id: i.inputId || void 0,
		"aria-required": e.required ? "true" : void 0,
		disabled: n,
		..._(e) ? { [_(e)]: !0 } : {}
	}) } }));
	if (a === "dropdown") {
		let a = s.multiple === !0, c = ne(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = a ? L(e) : F(e), d = l[0] ?? "", f = !a && d === "" && !u.some((e) => e.value === "");
		return o("select", {
			...X(e, i),
			value: a ? l : d,
			disabled: n,
			multiple: a,
			onChange: (e) => {
				let t = e.target;
				r(a ? Array.from(t.selectedOptions).map((e) => e.value) : c ? t.value === "" ? [] : [t.value] : t.value);
			}
		}, [f ? o("option", {
			key: `${e.id}:empty`,
			value: "",
			disabled: !0,
			hidden: !0
		}) : null, ...u.map((t) => o("option", {
			key: `${e.id}:${t.value}`,
			value: t.value,
			disabled: t.disabled
		}, t.label))]);
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return o("input", {
		...X(e, i),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		readOnly: re(e) || void 0,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function et(e, t, n, r) {
	if (e.type === "heading") return o("span", null, typeof t == "string" ? t : "");
	if (e.type === "checkbox") return o("input", {
		type: "checkbox",
		"aria-label": e.heading,
		checked: t === !0 || t === "1" || t === 1,
		disabled: n,
		onChange: (e) => r(e.target.checked)
	});
	if (e.type === "multiline") return o("textarea", {
		"aria-label": e.heading,
		value: typeof t == "string" ? t : "",
		disabled: n,
		onChange: (e) => r(e.target.value)
	});
	if (e.type === "select" || e.type === "dropdown") {
		let i = t == null ? "" : String(t);
		return o("select", {
			"aria-label": e.heading,
			value: i,
			disabled: n,
			onChange: (e) => r(e.target.value)
		}, [e.options.some((e) => e.value === i) ? null : o("option", {
			key: ":empty",
			value: i,
			disabled: !0,
			hidden: !0
		}), ...e.options.map((e) => o("option", {
			key: e.value,
			value: e.value
		}, e.label))]);
	}
	return o("input", {
		type: {
			number: "number",
			email: "email",
			url: "url",
			date: "date",
			time: "time",
			color: "color"
		}[e.type] ?? "text",
		"aria-label": e.heading,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		onChange: (e) => r(e.target.value)
	});
}
function tt({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
	let c = d(null), u = d({
		value: t,
		setValue: a
	}), f = fe(e), p = Se(t);
	l(() => {
		u.current = {
			value: t,
			setValue: a
		};
	}, [t, a]), l(() => {
		let e = c.current;
		if (!e) return;
		let t = () => {
			u.current.setValue(Te(e, Se(u.current.value)));
		};
		return e.addEventListener(m, t), () => {
			e.removeEventListener(m, t);
		};
	}, [f]);
	let h = Q(e, p.number, r, (e) => {
		a({
			...Te(c.current, p),
			number: String(e ?? "")
		});
	}, n, "phone");
	return !f || !s(h) ? h : o("div", {
		ref: c,
		className: "formie-react-phone"
	}, i(h, { "data-formie-phone-input": "true" }), o("input", {
		type: "hidden",
		value: p.country ?? "",
		readOnly: !0,
		"data-formie-phone-country-input": "true"
	}));
}
function nt({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: a, errors: s, errorId: c }) {
	let l = L(e), u = Ne(e), d = u.length > 0, f = d && t && typeof t == "object" ? t : {}, p = a || `${e.id}-likert`;
	return o("div", {
		className: "formie-react-likert",
		...Z(e, i, "group")
	}, o("table", null, o("thead", null, o("tr", null, o("td", { key: "corner" }), ...l.map((e, t) => o("th", {
		key: e.value,
		id: `${p}-col-${t}`,
		scope: "col"
	}, e.label)))), o("tbody", null, (d ? u : [{
		label: "",
		value: ""
	}]).map((e, i) => {
		let a = d ? String(f[e.value] ?? "") : t == null ? "" : String(t), u = `${p}-row-${i}`;
		return o("tr", { key: e.value || "answer" }, d ? o("th", {
			id: u,
			scope: "row"
		}, e.label) : o("td"), ...l.map((t, l) => o("td", { key: t.value }, o("input", {
			type: "radio",
			name: `${p}-${i}`,
			value: t.value,
			checked: a === t.value,
			disabled: n || t.disabled,
			"aria-labelledby": d ? `${p}-col-${l} ${u}` : `${p}-col-${l}`,
			...Y(s, c),
			onChange: () => r(d ? {
				...f,
				[e.value]: t.value
			} : t.value)
		}))));
	}))));
}
function rt({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let { state: a } = q(), s = d(null), c = d({
		field: e,
		value: t,
		setValue: r
	}), u = Object.fromEntries(L(e).map((e) => [e.value, e.label])), f = Pe(e, t);
	l(() => {
		c.current = {
			field: e,
			value: t,
			setValue: r
		};
	}, [
		e,
		t,
		r
	]), l(() => {
		if (s.current) return b(s.current, "li", (e, t) => {
			let { field: n, value: r, setValue: i } = c.current;
			i(be(Pe(n, r), e, t));
		});
	}, []);
	let p = (e, t) => r(be(f, e, t));
	return o("ol", {
		ref: s,
		className: "formie-react-rank",
		...Z(e, i, "group")
	}, f.map((e, t) => o("li", { key: e }, o("span", null, u[e] ?? e), o("button", {
		type: "button",
		disabled: n || t === 0,
		"aria-label": `${W(a.definition, "moveUp")}: ${u[e] ?? e}`,
		onClick: () => p(t, t - 1)
	}, W(a.definition, "moveUp")), o("button", {
		type: "button",
		disabled: n || t === f.length - 1,
		"aria-label": `${W(a.definition, "moveDown")}: ${u[e] ?? e}`,
		onClick: () => p(t, t + 1)
	}, W(a.definition, "moveDown")))));
}
var it = {
	position: "absolute",
	inset: 0,
	width: "100%",
	height: "100%",
	margin: 0,
	opacity: 0,
	cursor: "pointer"
};
function at({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: a, errors: s, errorId: c }) {
	let l = L(e), u = t == null ? "" : String(t), d = l.findIndex((e) => e.value === u), f = typeof e.input.starColor == "string" && e.input.starColor ? e.input.starColor : void 0;
	return o("div", {
		className: "formie-react-rating",
		style: f ? { color: f } : void 0,
		...Z(e, i, "radiogroup")
	}, l.map((t, i) => o("label", {
		key: t.value,
		style: {
			position: "relative",
			display: "inline-block"
		},
		"data-formie-rating-filled": i <= d ? "true" : void 0
	}, o("input", {
		type: "radio",
		name: a || `${e.id}-rating`,
		value: t.value,
		checked: u === t.value,
		disabled: n || t.disabled,
		style: it,
		...Y(s, c),
		onChange: () => r(t.value),
		onClick: () => {
			u === t.value && r(null);
		}
	}), o("span", { "aria-hidden": "true" }, i <= d ? "★" : "☆"), o("span", { style: h }, t.label))));
}
function ot({ field: e }) {
	let { state: t } = q();
	return o("div", {
		className: "formie-react-summary",
		dangerouslySetInnerHTML: { __html: Me(e, t.lastSubmitResult) ?? "" }
	});
}
function st(e) {
	let t = (e) => e.replace(/[&"<>]/g, (e) => `&#${e.charCodeAt(0)};`);
	return Object.entries(e).filter(([, e]) => e !== void 0 && e !== !1).map(([e, n]) => {
		let r = e === "autoComplete" ? "autocomplete" : e;
		return n === !0 ? r : `${r}="${t(String(n))}"`;
	}).join(" ");
}
function ct({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let [a] = f(() => `<input ${st({
		type: "text",
		...X(e, i),
		"data-formie-date-datepicker-input": "true",
		value: A(t),
		placeholder: typeof e.input.placeholder == "string" ? e.input.placeholder : void 0,
		disabled: n
	})}>`);
	return o("div", {
		className: "formie-react-date-picker",
		onInput: (e) => {
			let t = e.currentTarget.querySelector("input[type=\"hidden\"]");
			t instanceof HTMLInputElement && r(j(t.value));
		}
	}, o("div", { dangerouslySetInnerHTML: { __html: a } }));
}
function lt({ markup: e, errorKey: t, setValue: n }) {
	let { state: r } = q();
	return o("div", {
		className: "formie-react-upload-manager",
		"data-formie-upload-field-handle": I(r.definition, t),
		onInput: (e) => n(Ee(e.currentTarget))
	}, o("div", { dangerouslySetInnerHTML: { __html: e } }));
}
function ut({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: a } = q(), s = Fe(e), c = Array.isArray(t) ? t : [], l = e.input, u = l.static === !0 || l.staticRows === !0, d = Number(l.minRows ?? 0) || 0, f = Number(l.maxRows ?? 0) || 0, p = !r && !u && (f <= 0 || c.length < f), m = (e, t, n) => {
		i(c.map((r, i) => i === e ? {
			...r,
			[t]: n
		} : r));
	};
	return o("div", {
		className: "formie-react-table",
		...Z(e, n, "group")
	}, [o("table", { key: "table" }, [o("thead", { key: "head" }, o("tr", null, [...s.map((e) => o("th", {
		key: e.id,
		scope: "col"
	}, e.heading)), u ? null : o("td", { key: "actions" })])), o("tbody", { key: "body" }, c.map((e, t) => o("tr", { key: t }, [...s.map((n) => o("td", { key: n.id }, et(n, e[n.handle], r, (e) => m(t, n.handle, e)))), u ? null : o("td", { key: "actions" }, o("button", {
		type: "button",
		disabled: r || c.length <= d,
		"aria-label": `${W(a.definition, "removeRow")} ${t + 1}`,
		onClick: () => i(c.filter((e, n) => n !== t))
	}, W(a.definition, "remove")))])))]), u ? null : o("button", {
		key: "add",
		type: "button",
		disabled: !p,
		onClick: () => i([...c, O(e)])
	}, String(l.addRowLabel || W(a.definition, "addRow")))]);
}
function dt(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function ft({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: a } = q(), s = d(null), c = d(null), [u, p] = f(null), m = dt(e, a.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
	return l(() => {
		let e = !1, t = () => void 0, n = () => void 0;
		return (async () => {
			try {
				let r = s.current;
				if (!r) return;
				let { default: a } = await import("./signature_pad-Cqf-RWiu.js");
				if (e) return;
				let o = new a(r, {
					backgroundColor: h,
					penColor: g,
					minWidth: _,
					maxWidth: _
				}), l = () => {
					let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), t = Math.max(1, Math.floor(r.clientWidth || 480)), n = r.getContext("2d");
					r.width = t * e, r.height = 192 * e, r.style.height = "192px", n && (n.setTransform(1, 0, 0, 1, 0, 0), n.scale(e, e)), o.clear();
				}, u = () => {
					i(o.isEmpty() ? "" : o.toDataURL());
				};
				l(), o.addEventListener?.("endStroke", u), t = () => {
					o.removeEventListener?.("endStroke", u);
				}, typeof window < "u" && (window.addEventListener("resize", l), n = () => {
					window.removeEventListener("resize", l);
				}), c.current = o, p(null);
			} catch (t) {
				e || p(t.message || "Unable to load signature support.");
			}
		})(), () => {
			e = !0, t(), n(), c.current = null;
		};
	}, [
		h,
		g,
		_
	]), l(() => {
		let e = c.current;
		if (e) {
			if (!v) e.isEmpty() || e.clear();
			else try {
				e.fromDataURL(v);
			} catch {}
		}
	}, [v]), o("div", { className: "formie-react-signature" }, [
		o("canvas", {
			key: "canvas",
			ref: s,
			"data-formie-signature-canvas": !0,
			...$e(e, n),
			style: r ? { pointerEvents: "none" } : void 0
		}),
		o("button", {
			key: "clear",
			type: "button",
			disabled: r,
			"aria-label": W(a.definition, "clearSignature"),
			"data-formie-signature-clear": !0,
			onClick: () => {
				c.current?.clear(), i("");
			}
		}, W(a.definition, "clear")),
		u ? o("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, u) : null
	]);
}
function pt({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: a }) {
	let { state: s, instance: c } = q(), [, u] = f(0), d = C(e), p = t && typeof t == "object" ? t : {}, m = d.map((e) => v(e, p.country, p[e.handle])), h = m.find((e) => e.loading), g = m.find((e) => se(e.field) && !e.loading && (p[e.field.handle] ?? "") !== e.value);
	if (l(() => {
		if (h) {
			let e = !0;
			return _e(h.field, String(p.country), c.getBrowserRequestOptions()).then(() => {
				e && u((e) => e + 1);
			}), () => {
				e = !1;
			};
		}
		g && a({
			...p,
			[g.field.handle]: g.value
		});
	}), d.length === 0) return o("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`);
	let _ = (t) => a({
		...p,
		...Ce(t.currentTarget, e)
	});
	return o("div", {
		className: "formie-react-name-grid",
		...Z(e, r, "group"),
		...te(e) ? {
			onInput: _,
			onChange: _
		} : {}
	}, m.filter((e) => e.field.meta?.hidden !== !0 && !e.hidden).map(({ field: t }) => {
		let r = `${n}.${t.handle}`;
		return o(gt, {
			key: `${e.id}:${t.handle}`,
			field: t,
			value: p[t.handle],
			errors: s.errors.fields[r] || [],
			errorKey: r,
			disabled: i || t.meta?.disabled === !0,
			setValue(e) {
				a({
					...p,
					[t.handle]: e
				});
			}
		});
	}));
}
function mt({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let a = e.input, s = Array.isArray(t) ? t : [], c = a.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return o("div", { className: "formie-react-file" }, [o("input", {
		key: "input",
		type: "file",
		...X(e, n),
		disabled: r,
		multiple: c,
		onChange: (e) => {
			let t = e.target;
			i(Array.from(t.files || []));
		}
	}), l.length > 0 ? o("ul", {
		key: "summary",
		className: "formie-react-file-list"
	}, l.map((e, t) => o("li", { key: `${e}:${t}` }, e))) : null]);
}
function ht({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: a }) {
	let { state: s } = q(), c = De(e), l = Array.isArray(t) ? t : [], u = e.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !i && (f <= 0 || l.length < f);
	return c.length === 0 ? o("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : o("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0,
		...Z(e, r, "group")
	}, [...l.map((t, r) => {
		let u = `${e.id}:${r}`;
		return o("div", {
			key: u,
			className: "formie-react-repeater-item",
			"data-formie-repeater-item": !0
		}, [...c.map((e, s) => o(yt, {
			key: `${u}:${s}`,
			row: e,
			rowIndex: s,
			values: t,
			errorPrefix: `${n}.${r}`,
			disabled: i,
			setFieldValue(e, t) {
				a(l.map((n, i) => i === r ? {
					...n,
					[e.handle]: t
				} : n));
			}
		})), o("button", {
			key: "remove",
			type: "button",
			disabled: i || d > 0 && l.length <= d,
			"aria-label": `${W(s.definition, "removeRow")} ${r + 1}`,
			"data-formie-repeater-remove": !0,
			onClick: () => {
				a(l.filter((e, t) => t !== r));
			}
		}, W(s.definition, "remove"))]);
	}), o("button", {
		key: "add",
		type: "button",
		disabled: !p,
		"data-formie-repeater-add": e.handle,
		onClick: () => {
			a([...l, E(e)]);
		}
	}, String(u.addRowLabel || W(s.definition, "addRow")))]);
}
function $(e, t = !0) {
	let { field: n, value: r, errorKey: i, errorId: a, disabled: s, setValue: c } = e, l = n.input, u = J(n), d = Ze(e, t);
	if (oe(n)) return o("div", {
		className: "formie-react-date-range-picker",
		onInput: (e) => c(N(e.currentTarget))
	}, o("div", { dangerouslySetInnerHTML: { __html: M(n, {
		id: d.inputId || void 0,
		"aria-label": d.labelledByWrapper ? void 0 : n.label || n.handle,
		"aria-required": n.required ? "true" : void 0,
		placeholder: typeof l.placeholder == "string" ? l.placeholder : void 0
	}) } }));
	if (ae(n)) return o(ct, {
		field: n,
		value: r,
		disabled: s,
		setValue: c,
		labelling: d
	});
	if (ee(n)) return o("div", { className: "formie-react-address-autocomplete" }, o("div", { dangerouslySetInnerHTML: { __html: g(n, {
		id: d.inputId || void 0,
		"aria-label": d.labelledByWrapper ? void 0 : n.label || n.handle,
		"aria-required": n.required ? "true" : void 0,
		placeholder: typeof l.placeholder == "string" ? l.placeholder : void 0
	}) } }));
	if (ie(n)) return o(pt, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (pe(n)) return o(ht, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "phone") return o(tt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "survey-likert") return o(nt, {
		...e,
		labelling: d
	});
	if (u === "survey-rank") return o(rt, {
		...e,
		labelling: d
	});
	if (u === "survey-rating") return o(at, {
		...e,
		labelling: d
	});
	if (u === "table") return o(ut, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "section") return o("hr", { className: "formie-react-section" });
	let f = Re(n);
	if (f !== null) return o(lt, {
		markup: f,
		errorKey: i,
		setValue: c
	});
	if (ue(n)) return o(mt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return o(ft, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return Q(n, r, s, c, d, u);
	if (u === "radio") {
		let t = L(n), i = ne(n), l = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
		return o("div", {
			className: "formie-react-choices",
			...Z(n, d, "radiogroup")
		}, t.map((t) => {
			let r = t.value, u = s || t.disabled;
			return o("label", { key: `${n.id}:${r}` }, [o("input", {
				key: "input",
				type: "radio",
				name: e.inputId || `${n.id}-radio`,
				value: r,
				...Y(e.errors, a),
				checked: l === r,
				disabled: u,
				onChange: () => {
					c(i ? [r] : r);
				}
			}), o("span", { key: "label" }, t.label)]);
		}));
	}
	if (u === "checkboxes") {
		let t = L(n), i = Array.isArray(r) ? r.map((e) => String(e)) : [], l = x(n), u = l ? o("label", {
			key: "toggle",
			className: "formie-react-checkbox-toggle"
		}, o("input", {
			type: "checkbox",
			checked: y(n, i),
			disabled: s,
			onChange: (e) => c(Le(n, i, e.target.checked))
		}), o("span", null, l.label)) : null;
		return o("div", {
			className: "formie-react-choices",
			...Z(n, d, "group")
		}, l?.position === "top" ? u : null, ...t.map((t) => {
			let r = t.value, l = i.includes(r), u = s || t.disabled;
			return o("label", { key: `${n.id}:${r}` }, [o("input", {
				key: "input",
				type: "checkbox",
				...Y(e.errors, a),
				checked: l,
				disabled: u,
				onChange: () => {
					let e = l ? i.filter((e) => e !== r) : [...i, r];
					c(e);
				}
			}), o("span", { key: "label" }, t.label)]);
		}), l?.position === "bottom" ? u : null);
	}
	if (u === "agree") {
		let t = typeof l.descriptionHtml == "string" ? l.descriptionHtml : null;
		return o("div", {
			className: "formie-react-agree",
			...Z(n, d, "group")
		}, o("label", { className: "formie-react-boolean" }, [o("input", {
			key: "input",
			type: "checkbox",
			id: e.inputId || void 0,
			"aria-required": n.required ? "true" : void 0,
			...Y(e.errors, a),
			checked: r === !0,
			disabled: s,
			onChange: (e) => {
				let t = e.target;
				c(t.checked);
			}
		}), t ? o("span", {
			key: "description",
			dangerouslySetInnerHTML: { __html: t }
		}) : o("span", { key: "description" }, n.label)]));
	}
	let p = P(n);
	if (p) return p.kind === "heading" ? o(p.level, { className: "formie-react-heading" }, p.text) : o("div", {
		className: "formie-react-content",
		dangerouslySetInnerHTML: { __html: p.html }
	});
	if (n.type === "summary") return o(ot, { field: n });
	let m = k(n, d.labelledByWrapper && !ce(n) ? d.labelId : null);
	if (m !== null) return o("div", {
		className: "formie-react-custom-field",
		onInput: (e) => c(we(e.currentTarget, n)),
		dangerouslySetInnerHTML: { __html: m }
	});
	let h = xe(n);
	return h === null ? de(u) ? Q(n, r, s, c, d, u) : o("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`) : o("div", {
		className: "formie-react-payment",
		dangerouslySetInnerHTML: { __html: h }
	});
}
function gt({ field: e, value: t, errors: n, errorKey: i, disabled: a, setValue: s }) {
	let { components: c, fieldComponents: l, state: u } = q(), d = u.fieldStates[i]?.hidden === !0;
	if (d) return null;
	let f = J(e), p = l[e.type] || l[f] || $, m = c.Field || Ke, h = B(u.session, i), g = h.errors, _ = z(u.definition), v = {
		field: e,
		value: t,
		errors: n,
		errorKey: i,
		errorId: g,
		errorAriaLive: _,
		inputId: h.input,
		labelId: h.label,
		instructionsId: h.instructions,
		disabled: a,
		hidden: d,
		setValue: s
	};
	return o(m, {
		field: e,
		errors: n,
		errorId: g,
		errorAriaLive: _,
		inputId: h.input,
		labelId: h.label,
		instructionsId: h.instructions,
		children: o(r, null, p === $ ? $(v, !c.Field) : o(p, v), _t(Ie(e, t, u.definition)))
	});
}
function _t(e) {
	return e ? /* @__PURE__ */ Be("div", {
		className: "formie-react-limit",
		"data-formie-limit-text": "true",
		children: [/* @__PURE__ */ ze("span", {
			className: e.over ? "formie-react-limit-number formie-react-limit-number-error" : "formie-react-limit-number",
			children: e.count
		}), ` ${e.text}`]
	}) : null;
}
function vt({ field: e }) {
	let { state: t, instance: n } = q(), r = t.fieldStates[e.id];
	return o(gt, {
		field: e,
		value: t.values[e.id],
		errors: t.errors.fields[e.id] || [],
		errorKey: e.id,
		disabled: r?.disabled === !0,
		setValue(t) {
			n.setValue(e.id, t);
		}
	});
}
function yt({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: i, setFieldValue: a }) {
	let { state: s } = q();
	return o("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !a) return o(vt, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return o(gt, {
			key: e.id || `${t}:${c}`,
			field: e,
			value: n[e.handle],
			errors: s.errors.fields[l] || [],
			errorKey: l,
			disabled: i === !0 || s.fieldStates[l]?.disabled === !0,
			setValue(t) {
				a(e, t);
			}
		});
	}));
}
function bt() {
	let { state: e, instance: t } = q(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
	if (!n) return null;
	let r = [];
	return n.actions.secondary.forEach((e) => {
		r.push(o("button", {
			key: e.type,
			type: "button",
			onClick: () => {
				t.submit(e.type);
			}
		}, e.label));
	}), r.push(o("button", {
		key: n.actions.primary.type,
		type: "submit",
		disabled: !S(e)
	}, n.actions.primary.label)), o("div", { className: "formie-page-actions" }, r);
}
function xt({ className: e }) {
	let { instance: t, state: n, components: r } = q(), i = r.Form || Je, a = r.Page || Ye, s = r.ErrorSummary || Ge, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l), d = !(n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm), f = d ? U(n.definition, n.currentPageId)?.position : void 0;
	return c ? o(i, {
		definition: n.definition,
		session: n.session,
		state: n,
		className: e,
		onSubmit: () => t.submit(),
		children: [
			o(s, {
				key: "errors",
				errors: n.errors.form
			}),
			n.lastSubmitResult?.messages.notice ? o("div", {
				key: "notice",
				className: "formie-react-notice",
				role: "status",
				tabIndex: -1,
				"data-formie-form-message": !0,
				dangerouslySetInnerHTML: { __html: n.lastSubmitResult.messages.notice }
			}) : null,
			u ? o("div", {
				key: "error",
				className: "formie-react-error",
				role: "alert",
				tabIndex: -1,
				"data-formie-form-message": !0
			}, l) : null,
			f === "start" ? Xe(n) : null,
			d ? o(a, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => o(yt, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), o(bt, { key: "actions" })]
			}) : null,
			f === "end" ? Xe(n) : null
		]
	}) : null;
}
function St(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Ct({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: i, onMount: a, onReady: s, onUnmount: c, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	let [x, S] = f(null), [C, T] = f(null), [E, D] = f(null), O = d(a), k = d(s), A = d(c), j = d(m), M = d(h), N = d(g), P = d(_), F = d(v), I = d(y), L = d(b), R = u(() => K(e), [e]), z = d(e);
	l(() => {
		O.current = a;
	}, [a]), l(() => {
		k.current = s;
	}, [s]), l(() => {
		A.current = c;
	}, [c]), l(() => {
		j.current = m;
	}, [m]), l(() => {
		M.current = h;
	}, [h]), l(() => {
		N.current = g;
	}, [g]), l(() => {
		P.current = _;
	}, [_]), l(() => {
		F.current = v;
	}, [v]), l(() => {
		I.current = y;
	}, [y]), l(() => {
		L.current = b;
	}, [b]), l(() => {
		z.current = e;
	}, [e, R]), l(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = await Ue(z.current), r = We(z.current), i = w({
					envelope: n,
					transport: r
				});
				if (e) {
					await i.destroy();
					return;
				}
				D(null), S(i), T(i.getState()), O.current?.(i), k.current?.(i);
				let a = [
					i.subscribe((e) => {
						T(e);
					}),
					i.on("formie:submit:result", (e) => {
						let t = e;
						St(P.current, j.current, t), t.success ? St(F.current, M.current, t) : St(I.current, N.current, t);
					}),
					...p.map((e) => i.on(e, (t) => {
						L.current?.({
							name: e,
							payload: t
						});
					}))
				];
				t = () => {
					a.forEach((e) => e()), i.destroy(), A.current?.();
				};
			} catch (t) {
				e || D(t);
			}
		})(), () => {
			e = !0, t();
		};
	}, [R]);
	let B = u(() => !x || !C ? null : {
		instance: x,
		state: C,
		components: t,
		fieldComponents: n,
		slots: r
	}, [
		t,
		n,
		x,
		r,
		C
	]);
	return E ? o("div", { className: "formie-react-error" }, E.message) : B ? o(Ve.Provider, {
		value: B,
		children: o(qe, { children: o(xt, { className: i }) })
	}) : o("div", { className: "formie-react-loading" }, "Loading form...");
}
function wt() {
	let e = q();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function Tt(e) {
	let t = q(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
	return {
		field: n,
		value: t.state.values[e],
		errors: t.state.errors.fields[e] || [],
		hidden: t.state.fieldStates[e]?.hidden === !0,
		disabled: t.state.fieldStates[e]?.disabled === !0,
		setValue(e) {
			n && t.instance.setValue(n.id, e);
		}
	};
}
function Et(e) {
	let t = q();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function Dt() {
	return q().instance;
}
function Ot(e) {
	return q().slots[e] || null;
}
//#endregion
//#region src/index.ts
function kt(e) {
	return !!e && "payload" in e;
}
function At(e) {
	return "success" in e ? e.success : e.ok;
}
function jt(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Mt(e) {
	let t = e.transport;
	if (!t && !kt(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: kt(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function Nt(e) {
	if (e.source) return e.source;
	let t = e.transport, n = e.endpoint, r = e.formHandle;
	if (t !== "rest" && t !== "graphql") throw Error("React client-rendered forms require `transport=\"rest\"` or `transport=\"graphql\"`.");
	if (!n || !r) throw Error("React client-rendered forms require either `source` or both `endpoint` and `formHandle`.");
	return {
		transport: t,
		endpoint: n,
		formHandle: r,
		siteId: e.siteId,
		profile: e.profile
	};
}
function Pt({ source: n, transport: r, profile: i, endpoint: a, formHandle: s, staticCache: c, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = d(null), A = d(null), j = d(y), M = d(b), N = d(x), P = d(S), F = d(C), I = d(w), L = d(T), R = d(E), z = d(D), B = d(O), V = u(() => Mt({
		transport: r,
		profile: i,
		endpoint: a,
		formHandle: s,
		staticCache: c,
		refreshTokens: f,
		locale: p,
		siteId: m,
		autoVisible: h,
		theme: g,
		themeConfig: _,
		source: n
	}), [
		r,
		i,
		a,
		s,
		c,
		f,
		p,
		m,
		h,
		g,
		_,
		n
	]), H = u(() => K(V), [V]), U = d(V);
	return l(() => {
		j.current = y, M.current = b, N.current = x, P.current = S, F.current = C, I.current = w, L.current = T, R.current = E, z.current = D, B.current = O;
	}, [
		y,
		b,
		x,
		S,
		C,
		w,
		T,
		E,
		D,
		O
	]), l(() => {
		U.current = V;
	}, [V, H]), A.current ||= t(), l(() => {
		let t = k.current, n = A.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, U.current).then((t) => {
			r || (j.current?.(t), M.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				jt(L.current, P.current, t), At(t) ? jt(R.current, F.current, t) : jt(z.current, I.current, t);
			})), e.forEach((e) => {
				i.push(t.on(e, (t) => {
					B.current?.({
						name: e,
						payload: t
					});
				}));
			}));
		}), () => {
			r = !0, i.forEach((e) => e()), t && n && n.unmount(t).finally(() => {
				N.current?.();
			});
		};
	}, [H]), o("div", {
		ref: k,
		className: v
	});
}
function Ft({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, staticCache: a, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return o(Pt, {
		source: e,
		transport: t,
		profile: n,
		endpoint: r,
		formHandle: i,
		staticCache: a,
		refreshTokens: s,
		locale: c,
		siteId: l,
		autoVisible: u,
		theme: d,
		themeConfig: f,
		className: p,
		onMount: m,
		onReady: h,
		onUnmount: g,
		onResult: _,
		onSuccess: v,
		onError: y,
		onSubmitResult: b,
		onSubmitSuccess: x,
		onSubmitError: S,
		onEvent: C
	});
}
function It({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, siteId: a, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return o(Ct, {
		source: Nt({
			source: e,
			transport: t,
			profile: n,
			endpoint: r,
			formHandle: i,
			siteId: a,
			components: s,
			fieldComponents: c,
			slots: l,
			className: u,
			onMount: d,
			onReady: f,
			onUnmount: p,
			onResult: m,
			onSuccess: h,
			onError: g,
			onSubmitResult: _,
			onSubmitSuccess: v,
			onSubmitError: y,
			onEvent: b
		}),
		components: s,
		fieldComponents: c,
		slots: l,
		className: u,
		onMount: d,
		onReady: f,
		onUnmount: p,
		onResult: m,
		onSuccess: h,
		onError: g,
		onSubmitResult: _,
		onSubmitSuccess: v,
		onSubmitError: y,
		onEvent: b
	});
}
function Lt() {
	return u(() => t(), []);
}
function Rt(e) {
	let t = d(null), n = Lt(), r = u(() => K(e), [e]), i = d(e), [a, o] = f(null), [s, c] = f(null);
	return l(() => {
		i.current = e;
	}, [e, r]), l(() => {
		let e = t.current;
		if (!e) return;
		let r = !1, a = !1, s = async () => {
			a || (a = !0, await n.unmount(e));
		}, l = new Promise((t) => {
			queueMicrotask(() => {
				r ? t() : n.mount(e, {
					...i.current,
					mode: "server-rendered"
				}).then(async (e) => {
					r ? (await s(), t()) : (o(e), c(null), t());
				}).catch((e) => {
					r || c(e), t();
				});
			});
		});
		return () => {
			r = !0, o(null), l.finally(s);
		};
	}, [n, r]), {
		rootRef: t,
		state: {
			instance: a,
			isMounted: !!a,
			error: s
		},
		submit: async (e = "submit") => a ? a.submit(e) : null
	};
}
//#endregion
export { It as FormieClientForm, Ft as FormieForm, wt as useFormie, Lt as useFormieClient, Tt as useFormieField, Rt as useFormieHtml, Dt as useFormieInstance, Et as useFormiePage, Ot as useFormieSlot };
