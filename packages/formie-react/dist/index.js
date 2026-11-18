import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { createContext as r, createElement as i, useContext as a, useEffect as o, useMemo as s, useRef as c, useState as l } from "react";
import { CLIENT_FORM_EVENT_NAMES as u, VISUALLY_HIDDEN_STYLE as d, clientActionAllowed as f, compositePartDefinitions as p, createClientFormInstance as m, createGraphqlClientTransport as h, createRepeaterRowValue as g, createRestClientTransport as _, createTableRowValue as v, displayFieldContent as y, dropdownOptions as b, fieldOptions as x, focusClientFormResult as S, getClientErrorAriaLive as C, getClientFieldIds as w, getClientFieldLabelTarget as T, getClientPageProgress as E, getClientString as D, isArrayValuedField as O, isCalculationField as k, isCompositeField as A, isFieldLabelHidden as j, isFieldLabelVisuallyHidden as M, isFileField as N, isKnownClientFieldType as P, isRepeatableField as F, joinIdReferences as I, loadClientFormBootstrap as L, loadGraphqlClientFormBootstrap as R, repeaterRowDefinitions as z, resolveClientRendererType as B, showsClientPageTitle as V, tableColumns as H } from "@verbb/formie-core";
//#region src/stable.ts
function U(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => U(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${U(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function W(e) {
	return U(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var ee = r(null);
function te(e) {
	return "definition" in e;
}
async function ne(e) {
	return te(e) ? e.definition : e.transport === "graphql" ? R({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : L({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function re(e) {
	let t = te(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? h(t) : _(t);
}
function ie({ errors: e }) {
	return e.length === 0 ? null : i("div", {
		className: "formie-react-errors",
		role: "alert",
		tabIndex: -1,
		"data-formie-form-message": !0
	}, i("ul", null, e.map((e, t) => i("li", { key: `${e}:${t}` }, e))));
}
function ae({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: a, labelId: o, instructionsId: s, children: c }) {
	let { slots: l } = G(), u = (t, n, r) => {
		let a = l[t];
		return a ? i(a, {
			slotKey: t,
			children: n,
			attributes: r,
			field: e
		}) : n;
	};
	return i("div", {
		className: "formie-react-field",
		"data-formie-field-type": e.type,
		"data-formie-field-uid": e.uid,
		"data-formie-field-handle": e.handle,
		"data-formie-field-required": e.required ? "true" : void 0
	}, [
		e.label && !j(e) ? u("label", i("label", {
			key: "label",
			className: "formie-react-label",
			style: M(e) ? d : void 0,
			id: o || void 0,
			htmlFor: a && T(e, K(e)) === "control" ? a : void 0
		}, e.label)) : null,
		e.instructions ? u("instructions", i("div", {
			key: "instructions",
			className: "formie-react-description",
			id: s || void 0,
			dangerouslySetInnerHTML: { __html: e.instructions }
		})) : null,
		u("input", i("div", {
			key: "input",
			className: "formie-react-input"
		}, c)),
		u("errors", i("ul", {
			key: "errors",
			id: n,
			className: "formie-react-field-errors",
			style: t.length === 0 ? { position: "absolute" } : void 0,
			"data-formie-field-errors": !0,
			"aria-live": r === "off" ? void 0 : r,
			"aria-atomic": r === "off" ? void 0 : "true"
		}, t.map((e, t) => i("li", { key: `${e}:${t}` }, e))), {
			id: n,
			style: t.length === 0 ? { position: "absolute" } : void 0,
			"data-formie-field-errors": !0,
			"aria-live": r === "off" ? void 0 : r,
			"aria-atomic": r === "off" ? void 0 : "true"
		})
	]);
}
function oe({ children: e }) {
	let { instance: t } = G(), r = c(null);
	return o(() => {
		let e = !1, i;
		return t.setBrowserModuleGuard(() => {
			throw Error("Form features are still loading.");
		}), n(r.current, t).then((t) => {
			e ? t.destroy() : i = t;
		}), () => {
			e = !0, i?.destroy();
		};
	}, [t]), i("div", { ref: r }, e);
}
function se({ definition: e, session: t, state: n, children: r, className: a, onSubmit: s }) {
	let l = c(null), u = c(n.currentPageId), d = c(n.lastSubmitResult);
	return o(() => {
		n.lastSubmitResult && n.lastSubmitResult !== d.current && S(l.current, u.current), d.current = n.lastSubmitResult, u.current = n.currentPageId;
	}, [n.lastSubmitResult, n.currentPageId]), i("form", {
		ref: l,
		className: a,
		onSubmit: async (e) => {
			e.preventDefault(), await s();
		},
		"data-formie-definition": e.handle,
		"data-formie-render-id": t.tokens.render
	}, r);
}
function ce({ page: e, state: t, children: n }) {
	let r = V(t.definition) && !!e.label, a = `formie-${t.session.tokens.render || t.session.id}-page-${e.id}-title`;
	return i("section", {
		"data-page-id": e.id,
		"data-formie-page-id": e.id,
		className: "formie-react-page",
		tabIndex: -1,
		"aria-labelledby": r ? a : void 0,
		"aria-label": !r && t.definition.pages.length > 1 && e.label ? e.label : void 0
	}, r ? i("h3", {
		id: a,
		className: "formie-react-page-title"
	}, e.label) : null, n);
}
function le(e) {
	let t = E(e.definition, e.currentPageId);
	if (!t) return null;
	let n = i("span", {
		key: "value",
		className: "formie-react-progress-value",
		"aria-hidden": "true"
	}, `${t.percent}%`), r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition);
	return i("div", {
		key: "progress",
		className: "formie-react-progress",
		"data-formie-progress-position": t.position,
		"data-formie-progress-value-position": t.valuePosition,
		"data-formie-progress-state": t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle"
	}, [
		t.valuePosition === "left" ? n : null,
		i("div", {
			key: "track",
			className: "formie-react-progress-track",
			role: "progressbar",
			"aria-label": D(e.definition, "progress"),
			"aria-valuenow": t.percent,
			"aria-valuemin": 0,
			"aria-valuemax": 100
		}, i("div", {
			className: "formie-react-progress-bar",
			style: { width: `${t.percent}%` }
		}, r ? n : null)),
		t.valuePosition === "right" ? n : null
	]);
}
function G() {
	let e = a(ee);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function K(e) {
	return B(e);
}
function q(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function ue(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function de(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function J(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": I(de(e, t), n && t.errorId),
		autoComplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0
	};
}
function fe(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function Y(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": I(de(e, t))
	};
}
function pe(e, t, n, r, a, o = K(e)) {
	let s = e.input;
	if (o === "multi-line-text") return i("textarea", {
		...J(e, a),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (o === "dropdown") {
		let o = s.multiple === !0, c = O(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = o ? x(e) : b(e), d = l[0] ?? "", f = !o && d === "" && !u.some((e) => e.value === "");
		return i("select", {
			...J(e, a),
			value: o ? l : d,
			disabled: n,
			multiple: o,
			onChange: (e) => {
				let t = e.target;
				r(o ? Array.from(t.selectedOptions).map((e) => e.value) : c ? t.value === "" ? [] : [t.value] : t.value);
			}
		}, [f ? i("option", {
			key: `${e.id}:empty`,
			value: "",
			disabled: !0,
			hidden: !0
		}) : null, ...u.map((t) => i("option", {
			key: `${e.id}:${t.value}`,
			value: t.value,
			disabled: t.disabled
		}, t.label))]);
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return i("input", {
		...J(e, a),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		readOnly: k(e) || void 0,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function me(e, t, n, r) {
	if (e.type === "heading") return i("span", null, typeof t == "string" ? t : "");
	if (e.type === "checkbox") return i("input", {
		type: "checkbox",
		"aria-label": e.heading,
		checked: t === !0 || t === "1" || t === 1,
		disabled: n,
		onChange: (e) => r(e.target.checked)
	});
	if (e.type === "multiline") return i("textarea", {
		"aria-label": e.heading,
		value: typeof t == "string" ? t : "",
		disabled: n,
		onChange: (e) => r(e.target.value)
	});
	if (e.type === "select" || e.type === "dropdown") {
		let a = t == null ? "" : String(t);
		return i("select", {
			"aria-label": e.heading,
			value: a,
			disabled: n,
			onChange: (e) => r(e.target.value)
		}, [e.options.some((e) => e.value === a) ? null : i("option", {
			key: ":empty",
			value: a,
			disabled: !0,
			hidden: !0
		}), ...e.options.map((e) => i("option", {
			key: e.value,
			value: e.value
		}, e.label))]);
	}
	return i("input", {
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
function he({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
	let { state: o } = G(), s = H(e), c = Array.isArray(t) ? t : [], l = e.input, u = l.static === !0 || l.staticRows === !0, d = Number(l.minRows ?? 0) || 0, f = Number(l.maxRows ?? 0) || 0, p = !r && !u && (f <= 0 || c.length < f), m = (e, t, n) => {
		a(c.map((r, i) => i === e ? {
			...r,
			[t]: n
		} : r));
	};
	return i("div", {
		className: "formie-react-table",
		...Y(e, n, "group")
	}, [i("table", { key: "table" }, [i("thead", { key: "head" }, i("tr", null, [...s.map((e) => i("th", {
		key: e.id,
		scope: "col"
	}, e.heading)), u ? null : i("td", { key: "actions" })])), i("tbody", { key: "body" }, c.map((e, t) => i("tr", { key: t }, [...s.map((n) => i("td", { key: n.id }, me(n, e[n.handle], r, (e) => m(t, n.handle, e)))), u ? null : i("td", { key: "actions" }, i("button", {
		type: "button",
		disabled: r || c.length <= d,
		"aria-label": `${D(o.definition, "removeRow")} ${t + 1}`,
		onClick: () => a(c.filter((e, n) => n !== t))
	}, D(o.definition, "remove")))])))]), u ? null : i("button", {
		key: "add",
		type: "button",
		disabled: !p,
		onClick: () => a([...c, v(e)])
	}, String(l.addRowLabel || D(o.definition, "addRow")))]);
}
function ge(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function _e({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
	let { state: s } = G(), u = c(null), d = c(null), [f, p] = l(null), m = ge(e, s.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
	return o(() => {
		let e = !1, t = () => void 0, n = () => void 0;
		return (async () => {
			try {
				let r = u.current;
				if (!r) return;
				let { default: i } = await import("./signature_pad-Cqf-RWiu.js");
				if (e) return;
				let o = new i(r, {
					backgroundColor: h,
					penColor: g,
					minWidth: _,
					maxWidth: _
				}), s = () => {
					let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), t = Math.max(1, Math.floor(r.clientWidth || 480)), n = r.getContext("2d");
					r.width = t * e, r.height = 192 * e, r.style.height = "192px", n && (n.setTransform(1, 0, 0, 1, 0, 0), n.scale(e, e)), o.clear();
				}, c = () => {
					a(o.isEmpty() ? "" : o.toDataURL());
				};
				s(), o.addEventListener?.("endStroke", c), t = () => {
					o.removeEventListener?.("endStroke", c);
				}, typeof window < "u" && (window.addEventListener("resize", s), n = () => {
					window.removeEventListener("resize", s);
				}), d.current = o, p(null);
			} catch (t) {
				e || p(t.message || "Unable to load signature support.");
			}
		})(), () => {
			e = !0, t(), n(), d.current = null;
		};
	}, [
		h,
		g,
		_
	]), o(() => {
		let e = d.current;
		if (e) {
			if (!v) e.isEmpty() || e.clear();
			else try {
				e.fromDataURL(v);
			} catch {}
		}
	}, [v]), i("div", { className: "formie-react-signature" }, [
		i("canvas", {
			key: "canvas",
			ref: u,
			"data-formie-signature-canvas": !0,
			...fe(e, n),
			style: r ? { pointerEvents: "none" } : void 0
		}),
		i("button", {
			key: "clear",
			type: "button",
			disabled: r,
			"aria-label": D(s.definition, "clearSignature"),
			"data-formie-signature-clear": !0,
			onClick: () => {
				d.current?.clear(), a("");
			}
		}, D(s.definition, "clear")),
		f ? i("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, f) : null
	]);
}
function ve({ field: e, value: t, errorKey: n, labelling: r, disabled: a, setValue: o }) {
	let { state: s } = G(), c = p(e), l = t && typeof t == "object" ? t : {};
	return c.length === 0 ? i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`) : i("div", {
		className: "formie-react-name-grid",
		...Y(e, r, "group")
	}, c.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let r = `${n}.${t.handle}`;
		return i(Z, {
			key: `${e.id}:${t.handle}`,
			field: t,
			value: l[t.handle],
			errors: s.errors.fields[r] || [],
			errorKey: r,
			disabled: a || t.meta?.disabled === !0,
			setValue(e) {
				o({
					...l,
					[t.handle]: e
				});
			}
		});
	}));
}
function ye({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
	let o = e.input, s = Array.isArray(t) ? t : [], c = o.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return i("div", { className: "formie-react-file" }, [i("input", {
		key: "input",
		type: "file",
		...J(e, n),
		disabled: r,
		multiple: c,
		onChange: (e) => {
			let t = e.target;
			a(Array.from(t.files || []));
		}
	}), l.length > 0 ? i("ul", {
		key: "summary",
		className: "formie-react-file-list"
	}, l.map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null]);
}
function be({ field: e, value: t, errorKey: n, labelling: r, disabled: a, setValue: o }) {
	let { state: s } = G(), c = z(e), l = Array.isArray(t) ? t : [], u = e.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !a && (f <= 0 || l.length < f);
	return c.length === 0 ? i("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : i("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0,
		...Y(e, r, "group")
	}, [...l.map((t, r) => {
		let u = `${e.id}:${r}`;
		return i("div", {
			key: u,
			className: "formie-react-repeater-item",
			"data-formie-repeater-item": !0
		}, [...c.map((e, s) => i(Se, {
			key: `${u}:${s}`,
			row: e,
			rowIndex: s,
			values: t,
			errorPrefix: `${n}.${r}`,
			disabled: a,
			setFieldValue(e, t) {
				o(l.map((n, i) => i === r ? {
					...n,
					[e.handle]: t
				} : n));
			}
		})), i("button", {
			key: "remove",
			type: "button",
			disabled: a || d > 0 && l.length <= d,
			"aria-label": `${D(s.definition, "removeRow")} ${r + 1}`,
			"data-formie-repeater-remove": !0,
			onClick: () => {
				o(l.filter((e, t) => t !== r));
			}
		}, D(s.definition, "remove"))]);
	}), i("button", {
		key: "add",
		type: "button",
		disabled: !p,
		"data-formie-repeater-add": e.handle,
		onClick: () => {
			o([...l, g(e)]);
		}
	}, String(u.addRowLabel || D(s.definition, "addRow")))]);
}
function X(e, t = !0) {
	let { field: n, value: r, errorKey: a, errorId: o, disabled: s, setValue: c } = e, l = n.input, u = K(n), d = ue(e, t);
	if (A(n)) return i(ve, {
		field: n,
		value: r,
		errorKey: a,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (F(n)) return i(be, {
		field: n,
		value: r,
		errorKey: a,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "table") return i(he, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "section") return i("hr", { className: "formie-react-section" });
	if (N(n)) return i(ye, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return i(_e, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return pe(n, r, s, c, d, u);
	if (u === "radio") {
		let t = x(n), a = O(n), l = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
		return i("div", {
			className: "formie-react-choices",
			...Y(n, d, "radiogroup")
		}, t.map((t) => {
			let r = t.value, u = s || t.disabled;
			return i("label", { key: `${n.id}:${r}` }, [i("input", {
				key: "input",
				type: "radio",
				name: e.inputId || `${n.id}-radio`,
				value: r,
				...q(e.errors, o),
				checked: l === r,
				disabled: u,
				onChange: () => {
					c(a ? [r] : r);
				}
			}), i("span", { key: "label" }, t.label)]);
		}));
	}
	if (u === "checkboxes") {
		let t = x(n), a = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return i("div", {
			className: "formie-react-choices",
			...Y(n, d, "group")
		}, t.map((t) => {
			let r = t.value, l = a.includes(r), u = s || t.disabled;
			return i("label", { key: `${n.id}:${r}` }, [i("input", {
				key: "input",
				type: "checkbox",
				...q(e.errors, o),
				checked: l,
				disabled: u,
				onChange: () => {
					let e = l ? a.filter((e) => e !== r) : [...a, r];
					c(e);
				}
			}), i("span", { key: "label" }, t.label)]);
		}));
	}
	if (u === "agree") {
		let t = typeof l.descriptionHtml == "string" ? l.descriptionHtml : null;
		return i("div", {
			className: "formie-react-agree",
			...Y(n, d, "group")
		}, i("label", { className: "formie-react-boolean" }, [i("input", {
			key: "input",
			type: "checkbox",
			id: e.inputId || void 0,
			"aria-required": n.required ? "true" : void 0,
			...q(e.errors, o),
			checked: r === !0,
			disabled: s,
			onChange: (e) => {
				let t = e.target;
				c(t.checked);
			}
		}), t ? i("span", {
			key: "description",
			dangerouslySetInnerHTML: { __html: t }
		}) : i("span", { key: "description" }, n.label)]));
	}
	let f = y(n);
	return f ? f.kind === "heading" ? i(f.level, { className: "formie-react-heading" }, f.text) : i("div", {
		className: "formie-react-content",
		dangerouslySetInnerHTML: { __html: f.html }
	}) : P(u) ? pe(n, r, s, c, d, u) : i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`);
}
function Z({ field: e, value: t, errors: n, errorKey: r, disabled: a, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = G(), u = l.fieldStates[r]?.hidden === !0;
	if (u) return null;
	let d = K(e), f = c[e.type] || c[d] || X, p = s.Field || ae, m = w(l.session, r), h = m.errors, g = C(l.definition), _ = {
		field: e,
		value: t,
		errors: n,
		errorKey: r,
		errorId: h,
		errorAriaLive: g,
		inputId: m.input,
		labelId: m.label,
		instructionsId: m.instructions,
		disabled: a,
		hidden: u,
		setValue: o
	};
	return i(p, {
		field: e,
		errors: n,
		errorId: h,
		errorAriaLive: g,
		inputId: m.input,
		labelId: m.label,
		instructionsId: m.instructions,
		children: f === X ? X(_, !s.Field) : i(f, _)
	});
}
function xe({ field: e }) {
	let { state: t, instance: n } = G(), r = t.fieldStates[e.id];
	return i(Z, {
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
function Se({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: a, setFieldValue: o }) {
	let { state: s } = G();
	return i("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return i(xe, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return i(Z, {
			key: e.id || `${t}:${c}`,
			field: e,
			value: n[e.handle],
			errors: s.errors.fields[l] || [],
			errorKey: l,
			disabled: a === !0 || s.fieldStates[l]?.disabled === !0,
			setValue(t) {
				o(e, t);
			}
		});
	}));
}
function Ce() {
	let { state: e, instance: t } = G(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
	if (!n) return null;
	let r = [];
	return n.actions.secondary.forEach((e) => {
		r.push(i("button", {
			key: e.type,
			type: "button",
			onClick: () => {
				t.submit(e.type);
			}
		}, e.label));
	}), r.push(i("button", {
		key: n.actions.primary.type,
		type: "submit",
		disabled: !f(e)
	}, n.actions.primary.label)), i("div", { className: "formie-page-actions" }, r);
}
function we({ className: e }) {
	let { instance: t, state: n, components: r } = G(), a = r.Form || se, o = r.Page || ce, s = r.ErrorSummary || ie, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l), d = !(n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm), f = d ? E(n.definition, n.currentPageId)?.position : void 0;
	return c ? i(a, {
		definition: n.definition,
		session: n.session,
		state: n,
		className: e,
		onSubmit: () => t.submit(),
		children: [
			i(s, {
				key: "errors",
				errors: n.errors.form
			}),
			n.lastSubmitResult?.messages.notice ? i("div", {
				key: "notice",
				className: "formie-react-notice",
				role: "status",
				tabIndex: -1,
				"data-formie-form-message": !0,
				dangerouslySetInnerHTML: { __html: n.lastSubmitResult.messages.notice }
			}) : null,
			u ? i("div", {
				key: "error",
				className: "formie-react-error",
				role: "alert",
				tabIndex: -1,
				"data-formie-form-message": !0
			}, l) : null,
			f === "start" ? le(n) : null,
			d ? i(o, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => i(Se, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), i(Ce, { key: "actions" })]
			}) : null,
			f === "end" ? le(n) : null
		]
	}) : null;
}
function Q(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Te({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: a, onMount: d, onReady: f, onUnmount: p, onResult: h, onSuccess: g, onError: _, onSubmitResult: v, onSubmitSuccess: y, onSubmitError: b, onEvent: x }) {
	let [S, C] = l(null), [w, T] = l(null), [E, D] = l(null), O = c(d), k = c(f), A = c(p), j = c(h), M = c(g), N = c(_), P = c(v), F = c(y), I = c(b), L = c(x), R = s(() => W(e), [e]), z = c(e);
	o(() => {
		O.current = d;
	}, [d]), o(() => {
		k.current = f;
	}, [f]), o(() => {
		A.current = p;
	}, [p]), o(() => {
		j.current = h;
	}, [h]), o(() => {
		M.current = g;
	}, [g]), o(() => {
		N.current = _;
	}, [_]), o(() => {
		P.current = v;
	}, [v]), o(() => {
		F.current = y;
	}, [y]), o(() => {
		I.current = b;
	}, [b]), o(() => {
		L.current = x;
	}, [x]), o(() => {
		z.current = e;
	}, [e, R]), o(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = await ne(z.current), r = re(z.current), i = m({
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
						Q(P.current, j.current, t), t.success ? Q(F.current, M.current, t) : Q(I.current, N.current, t);
					}),
					...u.map((e) => i.on(e, (t) => {
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
	let B = s(() => !S || !w ? null : {
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
	return E ? i("div", { className: "formie-react-error" }, E.message) : B ? i(ee.Provider, {
		value: B,
		children: i(oe, { children: i(we, { className: a }) })
	}) : i("div", { className: "formie-react-loading" }, "Loading form...");
}
function Ee() {
	let e = G();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function De(e) {
	let t = G(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function Oe(e) {
	let t = G();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function ke() {
	return G().instance;
}
function Ae(e) {
	return G().slots[e] || null;
}
//#endregion
//#region src/index.ts
function je(e) {
	return !!e && "payload" in e;
}
function Me(e) {
	return "success" in e ? e.success : e.ok;
}
function $(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Ne(e) {
	let t = e.transport;
	if (!t && !je(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: je(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function Pe(e) {
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
function Fe({ source: n, transport: r, profile: a, endpoint: l, formHandle: u, staticCache: d, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = c(null), A = c(null), j = c(y), M = c(b), N = c(x), P = c(S), F = c(C), I = c(w), L = c(T), R = c(E), z = c(D), B = c(O), V = s(() => Ne({
		transport: r,
		profile: a,
		endpoint: l,
		formHandle: u,
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
		a,
		l,
		u,
		d,
		f,
		p,
		m,
		h,
		g,
		_,
		n
	]), H = s(() => W(V), [V]), U = c(V);
	return o(() => {
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
	]), o(() => {
		U.current = V;
	}, [V, H]), A.current ||= t(), o(() => {
		let t = k.current, n = A.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, U.current).then((t) => {
			r || (j.current?.(t), M.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				$(L.current, P.current, t), Me(t) ? $(R.current, F.current, t) : $(z.current, I.current, t);
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
	}, [H]), i("div", {
		ref: k,
		className: v
	});
}
function Ie({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, staticCache: o, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return i(Fe, {
		source: e,
		transport: t,
		profile: n,
		endpoint: r,
		formHandle: a,
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
function Le({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, siteId: o, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return i(Te, {
		source: Pe({
			source: e,
			transport: t,
			profile: n,
			endpoint: r,
			formHandle: a,
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
function Re() {
	return s(() => t(), []);
}
function ze(e) {
	let t = c(null), n = Re(), r = s(() => W(e), [e]), i = c(e), [a, u] = l(null), [d, f] = l(null);
	return o(() => {
		i.current = e;
	}, [e, r]), o(() => {
		let e = t.current;
		if (!e) return;
		let r = !1, a = !1, o = async () => {
			a || (a = !0, await n.unmount(e));
		}, s = new Promise((t) => {
			queueMicrotask(() => {
				r ? t() : n.mount(e, {
					...i.current,
					mode: "server-rendered"
				}).then(async (e) => {
					r ? (await o(), t()) : (u(e), f(null), t());
				}).catch((e) => {
					r || f(e), t();
				});
			});
		});
		return () => {
			r = !0, u(null), s.finally(o);
		};
	}, [n, r]), {
		rootRef: t,
		state: {
			instance: a,
			isMounted: !!a,
			error: d
		},
		submit: async (e = "submit") => a ? a.submit(e) : null
	};
}
//#endregion
export { Le as FormieClientForm, Ie as FormieForm, Ee as useFormie, Re as useFormieClient, De as useFormieField, ze as useFormieHtml, ke as useFormieInstance, Oe as useFormiePage, Ae as useFormieSlot };
