import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { cloneElement as r, createContext as i, createElement as a, isValidElement as o, useContext as s, useEffect as c, useMemo as l, useRef as u, useState as d } from "react";
import { CLIENT_FORM_EVENT_NAMES as f, PHONE_COUNTRY_CHANGE_EVENT as p, VISUALLY_HIDDEN_STYLE as m, addressAutocompleteMarkup as h, addressPartMarker as g, allOptionsSelected as _, attachPointerReorder as v, checkboxToggle as y, clientActionAllowed as b, compositePartDefinitions as x, createClientFormInstance as S, createGraphqlClientTransport as C, createRepeaterRowValue as w, createRestClientTransport as T, createTableRowValue as E, datePickerInputValue as D, datePickerValue as O, dateRangePickerMarkup as k, dateRangePickerValue as A, displayFieldContent as j, dropdownOptions as M, fieldContentPath as N, fieldOptions as P, focusClientFormResult as F, getClientErrorAriaLive as I, getClientFieldIds as L, getClientFieldLabelTarget as R, getClientPageProgress as z, getClientString as B, isAddressAutocompletePart as V, isAddressField as H, isArrayValuedField as U, isCalculationField as ee, isCompositeField as te, isDatePickerField as ne, isDateRangePickerField as re, isFieldLabelHidden as ie, isFieldLabelVisuallyHidden as ae, isFileField as oe, isKnownClientFieldType as se, isPhoneCountryEnabled as ce, isRepeatableField as le, isRichTextField as ue, isSearchableDropdown as de, joinIdReferences as fe, loadClientFormBootstrap as pe, loadGraphqlClientFormBootstrap as me, moveListItem as he, paymentFieldMarkup as ge, phoneFieldValue as _e, readAddressParts as ve, readPhoneFieldControls as ye, readUploadManagerEntries as be, repeaterRowDefinitions as xe, resolveClientRendererType as Se, searchableDropdownMarkup as Ce, searchableDropdownValue as we, showsClientPageTitle as Te, summaryFieldMarkup as Ee, surveyLikertRows as De, surveyRankOrder as Oe, tableColumns as ke, toggleAllOptions as Ae, uploadManagerMarkup as je } from "@verbb/formie-core";
//#region src/stable.ts
function W(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => W(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${W(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function G(e) {
	return W(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var Me = i(null);
function Ne(e) {
	return "definition" in e;
}
async function Pe(e) {
	return Ne(e) ? e.definition : e.transport === "graphql" ? me({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : pe({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function Fe(e) {
	let t = Ne(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? C(t) : T(t);
}
function Ie({ errors: e }) {
	return e.length === 0 ? null : a("div", {
		className: "formie-react-errors",
		role: "alert",
		tabIndex: -1,
		"data-formie-form-message": !0
	}, a("ul", null, e.map((e, t) => a("li", { key: `${e}:${t}` }, e))));
}
function Le({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: i, labelId: o, instructionsId: s, children: c }) {
	let { slots: l } = K(), u = (t, n, r) => {
		let i = l[t];
		return i ? a(i, {
			slotKey: t,
			children: n,
			attributes: r,
			field: e
		}) : n;
	};
	return a("div", {
		className: "formie-react-field",
		"data-formie-field-type": e.type,
		"data-formie-field-uid": e.uid,
		"data-formie-field-handle": e.handle,
		"data-formie-field-required": e.required ? "true" : void 0
	}, [
		e.label && !ie(e) ? u("label", a("label", {
			key: "label",
			className: "formie-react-label",
			style: ae(e) ? m : void 0,
			id: o || void 0,
			htmlFor: i && R(e, q(e)) === "control" ? i : void 0
		}, e.label)) : null,
		e.instructions ? u("instructions", a("div", {
			key: "instructions",
			className: "formie-react-description",
			id: s || void 0,
			dangerouslySetInnerHTML: { __html: e.instructions }
		})) : null,
		u("input", a("div", {
			key: "input",
			className: "formie-react-input"
		}, c)),
		u("errors", a("ul", {
			key: "errors",
			id: n,
			className: "formie-react-field-errors",
			style: t.length === 0 ? { position: "absolute" } : void 0,
			"data-formie-field-errors": !0,
			"aria-live": r === "off" ? void 0 : r,
			"aria-atomic": r === "off" ? void 0 : "true"
		}, t.map((e, t) => a("li", { key: `${e}:${t}` }, e))), {
			id: n,
			style: t.length === 0 ? { position: "absolute" } : void 0,
			"data-formie-field-errors": !0,
			"aria-live": r === "off" ? void 0 : r,
			"aria-atomic": r === "off" ? void 0 : "true"
		})
	]);
}
function Re({ children: e }) {
	let { instance: t } = K(), r = u(null);
	return c(() => {
		let e = !1, i;
		return t.setBrowserModuleGuard(() => {
			throw Error("Form features are still loading.");
		}), n(r.current, t).then((t) => {
			e ? t.destroy() : i = t;
		}), () => {
			e = !0, i?.destroy();
		};
	}, [t]), a("div", { ref: r }, e);
}
function ze({ definition: e, session: t, state: n, children: r, className: i, onSubmit: o }) {
	let s = u(null), l = u(n.currentPageId), d = u(n.lastSubmitResult);
	return c(() => {
		n.lastSubmitResult && n.lastSubmitResult !== d.current && F(s.current, l.current), d.current = n.lastSubmitResult, l.current = n.currentPageId;
	}, [n.lastSubmitResult, n.currentPageId]), a("form", {
		ref: s,
		className: i,
		onSubmit: async (e) => {
			e.preventDefault(), await o();
		},
		"data-formie-definition": e.handle,
		"data-formie-render-id": t.tokens.render
	}, r);
}
function Be({ page: e, state: t, children: n }) {
	let r = Te(t.definition) && !!e.label, i = `formie-${t.session.tokens.render || t.session.id}-page-${e.id}-title`;
	return a("section", {
		"data-page-id": e.id,
		"data-formie-page-id": e.id,
		className: "formie-react-page",
		tabIndex: -1,
		"aria-labelledby": r ? i : void 0,
		"aria-label": !r && t.definition.pages.length > 1 && e.label ? e.label : void 0
	}, r ? a("h3", {
		id: i,
		className: "formie-react-page-title"
	}, e.label) : null, n);
}
function Ve(e) {
	let t = z(e.definition, e.currentPageId);
	if (!t) return null;
	let n = a("span", {
		key: "value",
		className: "formie-react-progress-value",
		"aria-hidden": "true"
	}, `${t.percent}%`), r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition);
	return a("div", {
		key: "progress",
		className: "formie-react-progress",
		"data-formie-progress-position": t.position,
		"data-formie-progress-value-position": t.valuePosition,
		"data-formie-progress-state": t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle"
	}, [
		t.valuePosition === "left" ? n : null,
		a("div", {
			key: "track",
			className: "formie-react-progress-track",
			role: "progressbar",
			"aria-label": B(e.definition, "progress"),
			"aria-valuenow": t.percent,
			"aria-valuemin": 0,
			"aria-valuemax": 100
		}, a("div", {
			className: "formie-react-progress-bar",
			style: { width: `${t.percent}%` }
		}, r ? n : null)),
		t.valuePosition === "right" ? n : null
	]);
}
function K() {
	let e = s(Me);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function q(e) {
	return Se(e);
}
function J(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function He(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function Ue(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function Y(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": fe(Ue(e, t), n && t.errorId),
		autoComplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0,
		...g(e) ? { [g(e)]: "true" } : {}
	};
}
function We(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function X(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": fe(Ue(e, t))
	};
}
function Z(e, t, n, r, i, o = q(e)) {
	let s = e.input;
	if (o === "multi-line-text" && ue(e)) return a("div", {
		className: "formie-react-rich-text",
		onInput: (e) => {
			e.target instanceof HTMLTextAreaElement && r(e.target.value);
		}
	}, a("div", {
		key: "editor",
		"data-formie-rich-text": "true"
	}), a("textarea", {
		key: "input",
		...Y(e, i),
		"data-formie-multi-line-text-input": "true",
		style: { display: "none" },
		value: typeof t == "string" ? t : "",
		readOnly: !0,
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0
	}));
	if (o === "multi-line-text") return a("textarea", {
		...Y(e, i),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (o === "dropdown" && de(e)) return a("div", {
		className: "formie-react-combobox",
		onInput: (t) => {
			t.target instanceof HTMLSelectElement && r(we(e, t.target));
		}
	}, a("div", { dangerouslySetInnerHTML: { __html: Ce(e, {
		id: i.inputId || void 0,
		"aria-required": e.required ? "true" : void 0,
		disabled: n
	}) } }));
	if (o === "dropdown") {
		let o = s.multiple === !0, c = U(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = o ? P(e) : M(e), d = l[0] ?? "", f = !o && d === "" && !u.some((e) => e.value === "");
		return a("select", {
			...Y(e, i),
			value: o ? l : d,
			disabled: n,
			multiple: o,
			onChange: (e) => {
				let t = e.target;
				r(o ? Array.from(t.selectedOptions).map((e) => e.value) : c ? t.value === "" ? [] : [t.value] : t.value);
			}
		}, [f ? a("option", {
			key: `${e.id}:empty`,
			value: "",
			disabled: !0,
			hidden: !0
		}) : null, ...u.map((t) => a("option", {
			key: `${e.id}:${t.value}`,
			value: t.value,
			disabled: t.disabled
		}, t.label))]);
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return a("input", {
		...Y(e, i),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		readOnly: ee(e) || void 0,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function Ge(e, t, n, r) {
	if (e.type === "heading") return a("span", null, typeof t == "string" ? t : "");
	if (e.type === "checkbox") return a("input", {
		type: "checkbox",
		"aria-label": e.heading,
		checked: t === !0 || t === "1" || t === 1,
		disabled: n,
		onChange: (e) => r(e.target.checked)
	});
	if (e.type === "multiline") return a("textarea", {
		"aria-label": e.heading,
		value: typeof t == "string" ? t : "",
		disabled: n,
		onChange: (e) => r(e.target.value)
	});
	if (e.type === "select" || e.type === "dropdown") {
		let i = t == null ? "" : String(t);
		return a("select", {
			"aria-label": e.heading,
			value: i,
			disabled: n,
			onChange: (e) => r(e.target.value)
		}, [e.options.some((e) => e.value === i) ? null : a("option", {
			key: ":empty",
			value: i,
			disabled: !0,
			hidden: !0
		}), ...e.options.map((e) => a("option", {
			key: e.value,
			value: e.value
		}, e.label))]);
	}
	return a("input", {
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
function Ke({ field: e, value: t, labelling: n, disabled: i, setValue: s }) {
	let l = u(null), d = u({
		value: t,
		setValue: s
	}), f = ce(e), m = _e(t);
	c(() => {
		d.current = {
			value: t,
			setValue: s
		};
	}, [t, s]), c(() => {
		let e = l.current;
		if (!e) return;
		let t = () => {
			d.current.setValue(ye(e, _e(d.current.value)));
		};
		return e.addEventListener(p, t), () => {
			e.removeEventListener(p, t);
		};
	}, [f]);
	let h = Z(e, m.number, i, (e) => {
		s({
			...ye(l.current, m),
			number: String(e ?? "")
		});
	}, n, "phone");
	return !f || !o(h) ? h : a("div", {
		ref: l,
		className: "formie-react-phone"
	}, r(h, { "data-formie-phone-input": "true" }), a("input", {
		type: "hidden",
		value: m.country ?? "",
		readOnly: !0,
		"data-formie-phone-country-input": "true"
	}));
}
function qe({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: o, errors: s, errorId: c }) {
	let l = P(e), u = De(e), d = u.length > 0, f = d && t && typeof t == "object" ? t : {}, p = o || `${e.id}-likert`;
	return a("div", {
		className: "formie-react-likert",
		...X(e, i, "group")
	}, a("table", null, a("thead", null, a("tr", null, a("td", { key: "corner" }), ...l.map((e, t) => a("th", {
		key: e.value,
		id: `${p}-col-${t}`,
		scope: "col"
	}, e.label)))), a("tbody", null, (d ? u : [{
		label: "",
		value: ""
	}]).map((e, i) => {
		let o = d ? String(f[e.value] ?? "") : t == null ? "" : String(t), u = `${p}-row-${i}`;
		return a("tr", { key: e.value || "answer" }, d ? a("th", {
			id: u,
			scope: "row"
		}, e.label) : a("td"), ...l.map((t, l) => a("td", { key: t.value }, a("input", {
			type: "radio",
			name: `${p}-${i}`,
			value: t.value,
			checked: o === t.value,
			disabled: n || t.disabled,
			"aria-labelledby": d ? `${p}-col-${l} ${u}` : `${p}-col-${l}`,
			...J(s, c),
			onChange: () => r(d ? {
				...f,
				[e.value]: t.value
			} : t.value)
		}))));
	}))));
}
function Je({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let { state: o } = K(), s = u(null), l = u({
		field: e,
		value: t,
		setValue: r
	}), d = Object.fromEntries(P(e).map((e) => [e.value, e.label])), f = Oe(e, t);
	c(() => {
		l.current = {
			field: e,
			value: t,
			setValue: r
		};
	}, [
		e,
		t,
		r
	]), c(() => {
		if (s.current) return v(s.current, "li", (e, t) => {
			let { field: n, value: r, setValue: i } = l.current;
			i(he(Oe(n, r), e, t));
		});
	}, []);
	let p = (e, t) => r(he(f, e, t));
	return a("ol", {
		ref: s,
		className: "formie-react-rank",
		...X(e, i, "group")
	}, f.map((e, t) => a("li", { key: e }, a("span", null, d[e] ?? e), a("button", {
		type: "button",
		disabled: n || t === 0,
		"aria-label": `${B(o.definition, "moveUp")}: ${d[e] ?? e}`,
		onClick: () => p(t, t - 1)
	}, B(o.definition, "moveUp")), a("button", {
		type: "button",
		disabled: n || t === f.length - 1,
		"aria-label": `${B(o.definition, "moveDown")}: ${d[e] ?? e}`,
		onClick: () => p(t, t + 1)
	}, B(o.definition, "moveDown")))));
}
var Ye = {
	position: "absolute",
	inset: 0,
	width: "100%",
	height: "100%",
	margin: 0,
	opacity: 0,
	cursor: "pointer"
};
function Xe({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: o, errors: s, errorId: c }) {
	let l = P(e), u = t == null ? "" : String(t), d = l.findIndex((e) => e.value === u), f = typeof e.input.starColor == "string" && e.input.starColor ? e.input.starColor : void 0;
	return a("div", {
		className: "formie-react-rating",
		style: f ? { color: f } : void 0,
		...X(e, i, "radiogroup")
	}, l.map((t, i) => a("label", {
		key: t.value,
		style: {
			position: "relative",
			display: "inline-block"
		},
		"data-formie-rating-filled": i <= d ? "true" : void 0
	}, a("input", {
		type: "radio",
		name: o || `${e.id}-rating`,
		value: t.value,
		checked: u === t.value,
		disabled: n || t.disabled,
		style: Ye,
		...J(s, c),
		onChange: () => r(t.value),
		onClick: () => {
			u === t.value && r(null);
		}
	}), a("span", { "aria-hidden": "true" }, i <= d ? "★" : "☆"), a("span", { style: m }, t.label))));
}
function Ze({ field: e }) {
	let { state: t } = K();
	return a("div", {
		className: "formie-react-summary",
		dangerouslySetInnerHTML: { __html: Ee(e, t.lastSubmitResult) ?? "" }
	});
}
function Qe(e) {
	let t = (e) => e.replace(/[&"<>]/g, (e) => `&#${e.charCodeAt(0)};`);
	return Object.entries(e).filter(([, e]) => e !== void 0 && e !== !1).map(([e, n]) => {
		let r = e === "autoComplete" ? "autocomplete" : e;
		return n === !0 ? r : `${r}="${t(String(n))}"`;
	}).join(" ");
}
function $e({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let [o] = d(() => `<input ${Qe({
		type: "text",
		...Y(e, i),
		"data-formie-date-datepicker-input": "true",
		value: D(t),
		placeholder: typeof e.input.placeholder == "string" ? e.input.placeholder : void 0,
		disabled: n
	})}>`);
	return a("div", {
		className: "formie-react-date-picker",
		onInput: (e) => {
			let t = e.currentTarget.querySelector("input[type=\"hidden\"]");
			t instanceof HTMLInputElement && r(O(t.value));
		}
	}, a("div", { dangerouslySetInnerHTML: { __html: o } }));
}
function et({ markup: e, errorKey: t, setValue: n }) {
	let { state: r } = K();
	return a("div", {
		className: "formie-react-upload-manager",
		"data-formie-upload-field-handle": N(r.definition, t),
		onInput: (e) => n(be(e.currentTarget))
	}, a("div", { dangerouslySetInnerHTML: { __html: e } }));
}
function tt({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: o } = K(), s = ke(e), c = Array.isArray(t) ? t : [], l = e.input, u = l.static === !0 || l.staticRows === !0, d = Number(l.minRows ?? 0) || 0, f = Number(l.maxRows ?? 0) || 0, p = !r && !u && (f <= 0 || c.length < f), m = (e, t, n) => {
		i(c.map((r, i) => i === e ? {
			...r,
			[t]: n
		} : r));
	};
	return a("div", {
		className: "formie-react-table",
		...X(e, n, "group")
	}, [a("table", { key: "table" }, [a("thead", { key: "head" }, a("tr", null, [...s.map((e) => a("th", {
		key: e.id,
		scope: "col"
	}, e.heading)), u ? null : a("td", { key: "actions" })])), a("tbody", { key: "body" }, c.map((e, t) => a("tr", { key: t }, [...s.map((n) => a("td", { key: n.id }, Ge(n, e[n.handle], r, (e) => m(t, n.handle, e)))), u ? null : a("td", { key: "actions" }, a("button", {
		type: "button",
		disabled: r || c.length <= d,
		"aria-label": `${B(o.definition, "removeRow")} ${t + 1}`,
		onClick: () => i(c.filter((e, n) => n !== t))
	}, B(o.definition, "remove")))])))]), u ? null : a("button", {
		key: "add",
		type: "button",
		disabled: !p,
		onClick: () => i([...c, E(e)])
	}, String(l.addRowLabel || B(o.definition, "addRow")))]);
}
function nt(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function rt({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: o } = K(), s = u(null), l = u(null), [f, p] = d(null), m = nt(e, o.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
	return c(() => {
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
				}), c = () => {
					let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), t = Math.max(1, Math.floor(r.clientWidth || 480)), n = r.getContext("2d");
					r.width = t * e, r.height = 192 * e, r.style.height = "192px", n && (n.setTransform(1, 0, 0, 1, 0, 0), n.scale(e, e)), o.clear();
				}, u = () => {
					i(o.isEmpty() ? "" : o.toDataURL());
				};
				c(), o.addEventListener?.("endStroke", u), t = () => {
					o.removeEventListener?.("endStroke", u);
				}, typeof window < "u" && (window.addEventListener("resize", c), n = () => {
					window.removeEventListener("resize", c);
				}), l.current = o, p(null);
			} catch (t) {
				e || p(t.message || "Unable to load signature support.");
			}
		})(), () => {
			e = !0, t(), n(), l.current = null;
		};
	}, [
		h,
		g,
		_
	]), c(() => {
		let e = l.current;
		if (e) {
			if (!v) e.isEmpty() || e.clear();
			else try {
				e.fromDataURL(v);
			} catch {}
		}
	}, [v]), a("div", { className: "formie-react-signature" }, [
		a("canvas", {
			key: "canvas",
			ref: s,
			"data-formie-signature-canvas": !0,
			...We(e, n),
			style: r ? { pointerEvents: "none" } : void 0
		}),
		a("button", {
			key: "clear",
			type: "button",
			disabled: r,
			"aria-label": B(o.definition, "clearSignature"),
			"data-formie-signature-clear": !0,
			onClick: () => {
				l.current?.clear(), i("");
			}
		}, B(o.definition, "clear")),
		f ? a("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, f) : null
	]);
}
function it({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: o }) {
	let { state: s } = K(), c = x(e), l = t && typeof t == "object" ? t : {};
	if (c.length === 0) return a("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`);
	let u = (t) => o({
		...l,
		...ve(t.currentTarget, e)
	});
	return a("div", {
		className: "formie-react-name-grid",
		...X(e, r, "group"),
		...H(e) ? {
			onInput: u,
			onChange: u
		} : {}
	}, c.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let r = `${n}.${t.handle}`;
		return a($, {
			key: `${e.id}:${t.handle}`,
			field: t,
			value: l[t.handle],
			errors: s.errors.fields[r] || [],
			errorKey: r,
			disabled: i || t.meta?.disabled === !0,
			setValue(e) {
				o({
					...l,
					[t.handle]: e
				});
			}
		});
	}));
}
function at({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let o = e.input, s = Array.isArray(t) ? t : [], c = o.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return a("div", { className: "formie-react-file" }, [a("input", {
		key: "input",
		type: "file",
		...Y(e, n),
		disabled: r,
		multiple: c,
		onChange: (e) => {
			let t = e.target;
			i(Array.from(t.files || []));
		}
	}), l.length > 0 ? a("ul", {
		key: "summary",
		className: "formie-react-file-list"
	}, l.map((e, t) => a("li", { key: `${e}:${t}` }, e))) : null]);
}
function ot({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: o }) {
	let { state: s } = K(), c = xe(e), l = Array.isArray(t) ? t : [], u = e.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !i && (f <= 0 || l.length < f);
	return c.length === 0 ? a("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : a("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0,
		...X(e, r, "group")
	}, [...l.map((t, r) => {
		let u = `${e.id}:${r}`;
		return a("div", {
			key: u,
			className: "formie-react-repeater-item",
			"data-formie-repeater-item": !0
		}, [...c.map((e, s) => a(ct, {
			key: `${u}:${s}`,
			row: e,
			rowIndex: s,
			values: t,
			errorPrefix: `${n}.${r}`,
			disabled: i,
			setFieldValue(e, t) {
				o(l.map((n, i) => i === r ? {
					...n,
					[e.handle]: t
				} : n));
			}
		})), a("button", {
			key: "remove",
			type: "button",
			disabled: i || d > 0 && l.length <= d,
			"aria-label": `${B(s.definition, "removeRow")} ${r + 1}`,
			"data-formie-repeater-remove": !0,
			onClick: () => {
				o(l.filter((e, t) => t !== r));
			}
		}, B(s.definition, "remove"))]);
	}), a("button", {
		key: "add",
		type: "button",
		disabled: !p,
		"data-formie-repeater-add": e.handle,
		onClick: () => {
			o([...l, w(e)]);
		}
	}, String(u.addRowLabel || B(s.definition, "addRow")))]);
}
function Q(e, t = !0) {
	let { field: n, value: r, errorKey: i, errorId: o, disabled: s, setValue: c } = e, l = n.input, u = q(n), d = He(e, t);
	if (re(n)) return a("div", {
		className: "formie-react-date-range-picker",
		onInput: (e) => c(A(e.currentTarget))
	}, a("div", { dangerouslySetInnerHTML: { __html: k(n, {
		id: d.inputId || void 0,
		"aria-label": d.labelledByWrapper ? void 0 : n.label || n.handle,
		"aria-required": n.required ? "true" : void 0,
		placeholder: typeof l.placeholder == "string" ? l.placeholder : void 0
	}) } }));
	if (ne(n)) return a($e, {
		field: n,
		value: r,
		disabled: s,
		setValue: c,
		labelling: d
	});
	if (V(n)) return a("div", { className: "formie-react-address-autocomplete" }, a("div", { dangerouslySetInnerHTML: { __html: h(n, {
		id: d.inputId || void 0,
		"aria-label": d.labelledByWrapper ? void 0 : n.label || n.handle,
		"aria-required": n.required ? "true" : void 0,
		placeholder: typeof l.placeholder == "string" ? l.placeholder : void 0
	}) } }));
	if (te(n)) return a(it, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (le(n)) return a(ot, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "phone") return a(Ke, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "survey-likert") return a(qe, {
		...e,
		labelling: d
	});
	if (u === "survey-rank") return a(Je, {
		...e,
		labelling: d
	});
	if (u === "survey-rating") return a(Xe, {
		...e,
		labelling: d
	});
	if (u === "table") return a(tt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "section") return a("hr", { className: "formie-react-section" });
	let f = je(n);
	if (f !== null) return a(et, {
		markup: f,
		errorKey: i,
		setValue: c
	});
	if (oe(n)) return a(at, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return a(rt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return Z(n, r, s, c, d, u);
	if (u === "radio") {
		let t = P(n), i = U(n), l = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
		return a("div", {
			className: "formie-react-choices",
			...X(n, d, "radiogroup")
		}, t.map((t) => {
			let r = t.value, u = s || t.disabled;
			return a("label", { key: `${n.id}:${r}` }, [a("input", {
				key: "input",
				type: "radio",
				name: e.inputId || `${n.id}-radio`,
				value: r,
				...J(e.errors, o),
				checked: l === r,
				disabled: u,
				onChange: () => {
					c(i ? [r] : r);
				}
			}), a("span", { key: "label" }, t.label)]);
		}));
	}
	if (u === "checkboxes") {
		let t = P(n), i = Array.isArray(r) ? r.map((e) => String(e)) : [], l = y(n), u = l ? a("label", {
			key: "toggle",
			className: "formie-react-checkbox-toggle"
		}, a("input", {
			type: "checkbox",
			checked: _(n, i),
			disabled: s,
			onChange: (e) => c(Ae(n, i, e.target.checked))
		}), a("span", null, l.label)) : null;
		return a("div", {
			className: "formie-react-choices",
			...X(n, d, "group")
		}, l?.position === "top" ? u : null, ...t.map((t) => {
			let r = t.value, l = i.includes(r), u = s || t.disabled;
			return a("label", { key: `${n.id}:${r}` }, [a("input", {
				key: "input",
				type: "checkbox",
				...J(e.errors, o),
				checked: l,
				disabled: u,
				onChange: () => {
					let e = l ? i.filter((e) => e !== r) : [...i, r];
					c(e);
				}
			}), a("span", { key: "label" }, t.label)]);
		}), l?.position === "bottom" ? u : null);
	}
	if (u === "agree") {
		let t = typeof l.descriptionHtml == "string" ? l.descriptionHtml : null;
		return a("div", {
			className: "formie-react-agree",
			...X(n, d, "group")
		}, a("label", { className: "formie-react-boolean" }, [a("input", {
			key: "input",
			type: "checkbox",
			id: e.inputId || void 0,
			"aria-required": n.required ? "true" : void 0,
			...J(e.errors, o),
			checked: r === !0,
			disabled: s,
			onChange: (e) => {
				let t = e.target;
				c(t.checked);
			}
		}), t ? a("span", {
			key: "description",
			dangerouslySetInnerHTML: { __html: t }
		}) : a("span", { key: "description" }, n.label)]));
	}
	let p = j(n);
	if (p) return p.kind === "heading" ? a(p.level, { className: "formie-react-heading" }, p.text) : a("div", {
		className: "formie-react-content",
		dangerouslySetInnerHTML: { __html: p.html }
	});
	if (n.type === "summary") return a(Ze, { field: n });
	let m = ge(n);
	return m === null ? se(u) ? Z(n, r, s, c, d, u) : a("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`) : a("div", {
		className: "formie-react-payment",
		dangerouslySetInnerHTML: { __html: m }
	});
}
function $({ field: e, value: t, errors: n, errorKey: r, disabled: i, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = K(), u = l.fieldStates[r]?.hidden === !0;
	if (u) return null;
	let d = q(e), f = c[e.type] || c[d] || Q, p = s.Field || Le, m = L(l.session, r), h = m.errors, g = I(l.definition), _ = {
		field: e,
		value: t,
		errors: n,
		errorKey: r,
		errorId: h,
		errorAriaLive: g,
		inputId: m.input,
		labelId: m.label,
		instructionsId: m.instructions,
		disabled: i,
		hidden: u,
		setValue: o
	};
	return a(p, {
		field: e,
		errors: n,
		errorId: h,
		errorAriaLive: g,
		inputId: m.input,
		labelId: m.label,
		instructionsId: m.instructions,
		children: f === Q ? Q(_, !s.Field) : a(f, _)
	});
}
function st({ field: e }) {
	let { state: t, instance: n } = K(), r = t.fieldStates[e.id];
	return a($, {
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
function ct({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: i, setFieldValue: o }) {
	let { state: s } = K();
	return a("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return a(st, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return a($, {
			key: e.id || `${t}:${c}`,
			field: e,
			value: n[e.handle],
			errors: s.errors.fields[l] || [],
			errorKey: l,
			disabled: i === !0 || s.fieldStates[l]?.disabled === !0,
			setValue(t) {
				o(e, t);
			}
		});
	}));
}
function lt() {
	let { state: e, instance: t } = K(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
	if (!n) return null;
	let r = [];
	return n.actions.secondary.forEach((e) => {
		r.push(a("button", {
			key: e.type,
			type: "button",
			onClick: () => {
				t.submit(e.type);
			}
		}, e.label));
	}), r.push(a("button", {
		key: n.actions.primary.type,
		type: "submit",
		disabled: !b(e)
	}, n.actions.primary.label)), a("div", { className: "formie-page-actions" }, r);
}
function ut({ className: e }) {
	let { instance: t, state: n, components: r } = K(), i = r.Form || ze, o = r.Page || Be, s = r.ErrorSummary || Ie, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l), d = !(n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm), f = d ? z(n.definition, n.currentPageId)?.position : void 0;
	return c ? a(i, {
		definition: n.definition,
		session: n.session,
		state: n,
		className: e,
		onSubmit: () => t.submit(),
		children: [
			a(s, {
				key: "errors",
				errors: n.errors.form
			}),
			n.lastSubmitResult?.messages.notice ? a("div", {
				key: "notice",
				className: "formie-react-notice",
				role: "status",
				tabIndex: -1,
				"data-formie-form-message": !0,
				dangerouslySetInnerHTML: { __html: n.lastSubmitResult.messages.notice }
			}) : null,
			u ? a("div", {
				key: "error",
				className: "formie-react-error",
				role: "alert",
				tabIndex: -1,
				"data-formie-form-message": !0
			}, l) : null,
			f === "start" ? Ve(n) : null,
			d ? a(o, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => a(ct, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), a(lt, { key: "actions" })]
			}) : null,
			f === "end" ? Ve(n) : null
		]
	}) : null;
}
function dt(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function ft({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: i, onMount: o, onReady: s, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	let [x, C] = d(null), [w, T] = d(null), [E, D] = d(null), O = u(o), k = u(s), A = u(p), j = u(m), M = u(h), N = u(g), P = u(_), F = u(v), I = u(y), L = u(b), R = l(() => G(e), [e]), z = u(e);
	c(() => {
		O.current = o;
	}, [o]), c(() => {
		k.current = s;
	}, [s]), c(() => {
		A.current = p;
	}, [p]), c(() => {
		j.current = m;
	}, [m]), c(() => {
		M.current = h;
	}, [h]), c(() => {
		N.current = g;
	}, [g]), c(() => {
		P.current = _;
	}, [_]), c(() => {
		F.current = v;
	}, [v]), c(() => {
		I.current = y;
	}, [y]), c(() => {
		L.current = b;
	}, [b]), c(() => {
		z.current = e;
	}, [e, R]), c(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = await Pe(z.current), r = Fe(z.current), i = S({
					envelope: n,
					transport: r
				});
				if (e) {
					await i.destroy();
					return;
				}
				D(null), C(i), T(i.getState()), O.current?.(i), k.current?.(i);
				let a = [
					i.subscribe((e) => {
						T(e);
					}),
					i.on("formie:submit:result", (e) => {
						let t = e;
						dt(P.current, j.current, t), t.success ? dt(F.current, M.current, t) : dt(I.current, N.current, t);
					}),
					...f.map((e) => i.on(e, (t) => {
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
	let B = l(() => !x || !w ? null : {
		instance: x,
		state: w,
		components: t,
		fieldComponents: n,
		slots: r
	}, [
		t,
		n,
		x,
		r,
		w
	]);
	return E ? a("div", { className: "formie-react-error" }, E.message) : B ? a(Me.Provider, {
		value: B,
		children: a(Re, { children: a(ut, { className: i }) })
	}) : a("div", { className: "formie-react-loading" }, "Loading form...");
}
function pt() {
	let e = K();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function mt(e) {
	let t = K(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function ht(e) {
	let t = K();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function gt() {
	return K().instance;
}
function _t(e) {
	return K().slots[e] || null;
}
//#endregion
//#region src/index.ts
function vt(e) {
	return !!e && "payload" in e;
}
function yt(e) {
	return "success" in e ? e.success : e.ok;
}
function bt(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function xt(e) {
	let t = e.transport;
	if (!t && !vt(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: vt(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function St(e) {
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
function Ct({ source: n, transport: r, profile: i, endpoint: o, formHandle: s, staticCache: d, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = u(null), A = u(null), j = u(y), M = u(b), N = u(x), P = u(S), F = u(C), I = u(w), L = u(T), R = u(E), z = u(D), B = u(O), V = l(() => xt({
		transport: r,
		profile: i,
		endpoint: o,
		formHandle: s,
		staticCache: d,
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
		o,
		s,
		d,
		f,
		p,
		m,
		h,
		g,
		_,
		n
	]), H = l(() => G(V), [V]), U = u(V);
	return c(() => {
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
	]), c(() => {
		U.current = V;
	}, [V, H]), A.current ||= t(), c(() => {
		let t = k.current, n = A.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, U.current).then((t) => {
			r || (j.current?.(t), M.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				bt(L.current, P.current, t), yt(t) ? bt(R.current, F.current, t) : bt(z.current, I.current, t);
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
	}, [H]), a("div", {
		ref: k,
		className: v
	});
}
function wt({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, staticCache: o, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return a(Ct, {
		source: e,
		transport: t,
		profile: n,
		endpoint: r,
		formHandle: i,
		staticCache: o,
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
function Tt({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, siteId: o, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return a(ft, {
		source: St({
			source: e,
			transport: t,
			profile: n,
			endpoint: r,
			formHandle: i,
			siteId: o,
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
function Et() {
	return l(() => t(), []);
}
function Dt(e) {
	let t = u(null), n = Et(), r = l(() => G(e), [e]), i = u(e), [a, o] = d(null), [s, f] = d(null);
	return c(() => {
		i.current = e;
	}, [e, r]), c(() => {
		let e = t.current;
		if (!e) return;
		let r = !1, a = !1, s = async () => {
			a || (a = !0, await n.unmount(e));
		}, c = new Promise((t) => {
			queueMicrotask(() => {
				r ? t() : n.mount(e, {
					...i.current,
					mode: "server-rendered"
				}).then(async (e) => {
					r ? (await s(), t()) : (o(e), f(null), t());
				}).catch((e) => {
					r || f(e), t();
				});
			});
		});
		return () => {
			r = !0, o(null), c.finally(s);
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
export { Tt as FormieClientForm, wt as FormieForm, pt as useFormie, Et as useFormieClient, mt as useFormieField, Dt as useFormieHtml, gt as useFormieInstance, ht as useFormiePage, _t as useFormieSlot };
