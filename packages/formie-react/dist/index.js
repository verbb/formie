import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { cloneElement as r, createContext as i, createElement as a, isValidElement as o, useContext as s, useEffect as c, useMemo as l, useRef as u, useState as d } from "react";
import { CLIENT_FORM_EVENT_NAMES as f, PHONE_COUNTRY_CHANGE_EVENT as p, VISUALLY_HIDDEN_STYLE as m, clientActionAllowed as h, compositePartDefinitions as g, createClientFormInstance as _, createGraphqlClientTransport as v, createRepeaterRowValue as y, createRestClientTransport as b, createTableRowValue as x, displayFieldContent as S, dropdownOptions as C, fieldOptions as w, focusClientFormResult as T, getClientErrorAriaLive as E, getClientFieldIds as D, getClientFieldLabelTarget as O, getClientPageProgress as k, getClientString as A, isArrayValuedField as j, isCalculationField as M, isCompositeField as N, isFieldLabelHidden as P, isFieldLabelVisuallyHidden as F, isFileField as I, isKnownClientFieldType as L, isPhoneCountryEnabled as R, isRepeatableField as z, joinIdReferences as B, loadClientFormBootstrap as V, loadGraphqlClientFormBootstrap as H, paymentFieldMarkup as U, phoneFieldValue as ee, readPhoneFieldControls as te, repeaterRowDefinitions as ne, resolveClientRendererType as re, showsClientPageTitle as ie, tableColumns as ae } from "@verbb/formie-core";
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
var oe = i(null);
function se(e) {
	return "definition" in e;
}
async function ce(e) {
	return se(e) ? e.definition : e.transport === "graphql" ? H({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : V({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function le(e) {
	let t = se(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? v(t) : b(t);
}
function ue({ errors: e }) {
	return e.length === 0 ? null : a("div", {
		className: "formie-react-errors",
		role: "alert",
		tabIndex: -1,
		"data-formie-form-message": !0
	}, a("ul", null, e.map((e, t) => a("li", { key: `${e}:${t}` }, e))));
}
function de({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: i, labelId: o, instructionsId: s, children: c }) {
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
		e.label && !P(e) ? u("label", a("label", {
			key: "label",
			className: "formie-react-label",
			style: F(e) ? m : void 0,
			id: o || void 0,
			htmlFor: i && O(e, q(e)) === "control" ? i : void 0
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
function fe({ children: e }) {
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
function pe({ definition: e, session: t, state: n, children: r, className: i, onSubmit: o }) {
	let s = u(null), l = u(n.currentPageId), d = u(n.lastSubmitResult);
	return c(() => {
		n.lastSubmitResult && n.lastSubmitResult !== d.current && T(s.current, l.current), d.current = n.lastSubmitResult, l.current = n.currentPageId;
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
function me({ page: e, state: t, children: n }) {
	let r = ie(t.definition) && !!e.label, i = `formie-${t.session.tokens.render || t.session.id}-page-${e.id}-title`;
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
function he(e) {
	let t = k(e.definition, e.currentPageId);
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
			"aria-label": A(e.definition, "progress"),
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
	let e = s(oe);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function q(e) {
	return re(e);
}
function J(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function ge(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function _e(e, t) {
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
		"aria-describedby": B(_e(e, t), n && t.errorId),
		autoComplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0
	};
}
function ve(e, t) {
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
		"aria-describedby": B(_e(e, t))
	};
}
function Z(e, t, n, r, i, o = q(e)) {
	let s = e.input;
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
	if (o === "dropdown") {
		let o = s.multiple === !0, c = j(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = o ? w(e) : C(e), d = l[0] ?? "", f = !o && d === "" && !u.some((e) => e.value === "");
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
		readOnly: M(e) || void 0,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function ye(e, t, n, r) {
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
function be({ field: e, value: t, labelling: n, disabled: i, setValue: s }) {
	let l = u(null), d = u({
		value: t,
		setValue: s
	}), f = R(e), m = ee(t);
	c(() => {
		d.current = {
			value: t,
			setValue: s
		};
	}, [t, s]), c(() => {
		let e = l.current;
		if (!e) return;
		let t = () => {
			d.current.setValue(te(e, ee(d.current.value)));
		};
		return e.addEventListener(p, t), () => {
			e.removeEventListener(p, t);
		};
	}, [f]);
	let h = Z(e, m.number, i, (e) => {
		s({
			...te(l.current, m),
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
function xe({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: o } = K(), s = ae(e), c = Array.isArray(t) ? t : [], l = e.input, u = l.static === !0 || l.staticRows === !0, d = Number(l.minRows ?? 0) || 0, f = Number(l.maxRows ?? 0) || 0, p = !r && !u && (f <= 0 || c.length < f), m = (e, t, n) => {
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
	}, e.heading)), u ? null : a("td", { key: "actions" })])), a("tbody", { key: "body" }, c.map((e, t) => a("tr", { key: t }, [...s.map((n) => a("td", { key: n.id }, ye(n, e[n.handle], r, (e) => m(t, n.handle, e)))), u ? null : a("td", { key: "actions" }, a("button", {
		type: "button",
		disabled: r || c.length <= d,
		"aria-label": `${A(o.definition, "removeRow")} ${t + 1}`,
		onClick: () => i(c.filter((e, n) => n !== t))
	}, A(o.definition, "remove")))])))]), u ? null : a("button", {
		key: "add",
		type: "button",
		disabled: !p,
		onClick: () => i([...c, x(e)])
	}, String(l.addRowLabel || A(o.definition, "addRow")))]);
}
function Se(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function Ce({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: o } = K(), s = u(null), l = u(null), [f, p] = d(null), m = Se(e, o.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
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
			...ve(e, n),
			style: r ? { pointerEvents: "none" } : void 0
		}),
		a("button", {
			key: "clear",
			type: "button",
			disabled: r,
			"aria-label": A(o.definition, "clearSignature"),
			"data-formie-signature-clear": !0,
			onClick: () => {
				l.current?.clear(), i("");
			}
		}, A(o.definition, "clear")),
		f ? a("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, f) : null
	]);
}
function we({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: o }) {
	let { state: s } = K(), c = g(e), l = t && typeof t == "object" ? t : {};
	return c.length === 0 ? a("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`) : a("div", {
		className: "formie-react-name-grid",
		...X(e, r, "group")
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
function Te({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
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
function Ee({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: o }) {
	let { state: s } = K(), c = ne(e), l = Array.isArray(t) ? t : [], u = e.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !i && (f <= 0 || l.length < f);
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
		}, [...c.map((e, s) => a(Oe, {
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
			"aria-label": `${A(s.definition, "removeRow")} ${r + 1}`,
			"data-formie-repeater-remove": !0,
			onClick: () => {
				o(l.filter((e, t) => t !== r));
			}
		}, A(s.definition, "remove"))]);
	}), a("button", {
		key: "add",
		type: "button",
		disabled: !p,
		"data-formie-repeater-add": e.handle,
		onClick: () => {
			o([...l, y(e)]);
		}
	}, String(u.addRowLabel || A(s.definition, "addRow")))]);
}
function Q(e, t = !0) {
	let { field: n, value: r, errorKey: i, errorId: o, disabled: s, setValue: c } = e, l = n.input, u = q(n), d = ge(e, t);
	if (N(n)) return a(we, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (z(n)) return a(Ee, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "phone") return a(be, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "table") return a(xe, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "section") return a("hr", { className: "formie-react-section" });
	if (I(n)) return a(Te, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return a(Ce, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return Z(n, r, s, c, d, u);
	if (u === "radio") {
		let t = w(n), i = j(n), l = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
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
		let t = w(n), i = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return a("div", {
			className: "formie-react-choices",
			...X(n, d, "group")
		}, t.map((t) => {
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
		}));
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
	let f = S(n);
	if (f) return f.kind === "heading" ? a(f.level, { className: "formie-react-heading" }, f.text) : a("div", {
		className: "formie-react-content",
		dangerouslySetInnerHTML: { __html: f.html }
	});
	let p = U(n);
	return p === null ? L(u) ? Z(n, r, s, c, d, u) : a("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`) : a("div", {
		className: "formie-react-payment",
		dangerouslySetInnerHTML: { __html: p }
	});
}
function $({ field: e, value: t, errors: n, errorKey: r, disabled: i, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = K(), u = l.fieldStates[r]?.hidden === !0;
	if (u) return null;
	let d = q(e), f = c[e.type] || c[d] || Q, p = s.Field || de, m = D(l.session, r), h = m.errors, g = E(l.definition), _ = {
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
function De({ field: e }) {
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
function Oe({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: i, setFieldValue: o }) {
	let { state: s } = K();
	return a("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return a(De, {
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
function ke() {
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
		disabled: !h(e)
	}, n.actions.primary.label)), a("div", { className: "formie-page-actions" }, r);
}
function Ae({ className: e }) {
	let { instance: t, state: n, components: r } = K(), i = r.Form || pe, o = r.Page || me, s = r.ErrorSummary || ue, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l), d = !(n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm), f = d ? k(n.definition, n.currentPageId)?.position : void 0;
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
			f === "start" ? he(n) : null,
			d ? a(o, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => a(Oe, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), a(ke, { key: "actions" })]
			}) : null,
			f === "end" ? he(n) : null
		]
	}) : null;
}
function je(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Me({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: i, onMount: o, onReady: s, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: v, onSubmitSuccess: y, onSubmitError: b, onEvent: x }) {
	let [S, C] = d(null), [w, T] = d(null), [E, D] = d(null), O = u(o), k = u(s), A = u(p), j = u(m), M = u(h), N = u(g), P = u(v), F = u(y), I = u(b), L = u(x), R = l(() => G(e), [e]), z = u(e);
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
		P.current = v;
	}, [v]), c(() => {
		F.current = y;
	}, [y]), c(() => {
		I.current = b;
	}, [b]), c(() => {
		L.current = x;
	}, [x]), c(() => {
		z.current = e;
	}, [e, R]), c(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = await ce(z.current), r = le(z.current), i = _({
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
						je(P.current, j.current, t), t.success ? je(F.current, M.current, t) : je(I.current, N.current, t);
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
	let B = l(() => !S || !w ? null : {
		instance: S,
		state: w,
		components: t,
		fieldComponents: n,
		slots: r
	}, [
		t,
		n,
		S,
		r,
		w
	]);
	return E ? a("div", { className: "formie-react-error" }, E.message) : B ? a(oe.Provider, {
		value: B,
		children: a(fe, { children: a(Ae, { className: i }) })
	}) : a("div", { className: "formie-react-loading" }, "Loading form...");
}
function Ne() {
	let e = K();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function Pe(e) {
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
function Fe(e) {
	let t = K();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function Ie() {
	return K().instance;
}
function Le(e) {
	return K().slots[e] || null;
}
//#endregion
//#region src/index.ts
function Re(e) {
	return !!e && "payload" in e;
}
function ze(e) {
	return "success" in e ? e.success : e.ok;
}
function Be(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Ve(e) {
	let t = e.transport;
	if (!t && !Re(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: Re(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function He(e) {
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
function Ue({ source: n, transport: r, profile: i, endpoint: o, formHandle: s, staticCache: d, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = u(null), A = u(null), j = u(y), M = u(b), N = u(x), P = u(S), F = u(C), I = u(w), L = u(T), R = u(E), z = u(D), B = u(O), V = l(() => Ve({
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
				Be(L.current, P.current, t), ze(t) ? Be(R.current, F.current, t) : Be(z.current, I.current, t);
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
function We({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, staticCache: o, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return a(Ue, {
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
function Ge({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, siteId: o, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return a(Me, {
		source: He({
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
function Ke() {
	return l(() => t(), []);
}
function qe(e) {
	let t = u(null), n = Ke(), r = l(() => G(e), [e]), i = u(e), [a, o] = d(null), [s, f] = d(null);
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
export { Ge as FormieClientForm, We as FormieForm, Ne as useFormie, Ke as useFormieClient, Pe as useFormieField, qe as useFormieHtml, Ie as useFormieInstance, Fe as useFormiePage, Le as useFormieSlot };
