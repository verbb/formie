import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { createContext as r, createElement as i, useContext as a, useEffect as o, useMemo as s, useRef as c, useState as l } from "react";
import { CLIENT_FORM_EVENT_NAMES as u, clientActionAllowed as d, compositePartDefinitions as f, createClientFormInstance as p, createGraphqlClientTransport as m, createRepeaterRowValue as h, createRestClientTransport as g, displayFieldContent as _, getClientErrorAriaLive as v, getClientFieldIds as y, getClientFieldLabelTarget as b, isCompositeField as x, isFieldLabelHidden as S, isFileField as C, isKnownClientFieldType as w, isRepeatableField as T, joinIdReferences as E, loadClientFormBootstrap as D, loadGraphqlClientFormBootstrap as O, repeaterRowDefinitions as k } from "@verbb/formie-core";
//#region src/stable.ts
function A(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => A(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${A(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function j(e) {
	return A(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var M = r(null);
function N(e) {
	return "definition" in e;
}
async function P(e) {
	return N(e) ? e.definition : e.transport === "graphql" ? O({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : D({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function F(e) {
	let t = N(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? m(t) : g(t);
}
function I({ errors: e }) {
	return e.length === 0 ? null : i("div", { className: "formie-react-errors" }, i("ul", null, e.map((e, t) => i("li", { key: `${e}:${t}` }, e))));
}
function L({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: a, labelId: o, instructionsId: s, children: c }) {
	let { slots: l } = V(), u = (t, n, r) => {
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
		e.label && !S(e) ? u("label", i("label", {
			key: "label",
			className: "formie-react-label",
			id: o || void 0,
			htmlFor: a && b(e, H(e)) === "control" ? a : void 0
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
function R({ children: e }) {
	let { instance: t } = V(), r = c(null);
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
function z({ definition: e, session: t, state: n, children: r, className: a, onSubmit: s }) {
	let l = c(null);
	return o(() => {
		n.lastSubmitResult?.success === !1 && l.current?.querySelector("[aria-invalid=\"true\"]")?.focus();
	}, [n.lastSubmitResult]), i("form", {
		ref: l,
		className: a,
		onSubmit: async (e) => {
			e.preventDefault(), await s();
		},
		"data-formie-definition": e.handle,
		"data-formie-render-id": t.tokens.render
	}, r);
}
function B({ page: e, children: t }) {
	return i("section", {
		"data-page-id": e.id,
		"data-formie-page-id": e.id,
		className: "formie-react-page"
	}, t);
}
function V() {
	let e = a(M);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function H(e) {
	if (w(e.type)) return e.type;
	let t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
	return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function U(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function W(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function G(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function K(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": E(G(e, t), n && t.errorId)
	};
}
function q(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": E(G(e, t))
	};
}
function J(e, t, n, r, a) {
	let o = e.input;
	if (e.type === "multi-line-text") return i("textarea", {
		...K(e, a),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof o.placeholder == "string" ? o.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (e.type === "dropdown") {
		let s = Array.isArray(o.options) ? o.options : [], c = o.multiple === !0;
		return i("select", {
			...K(e, a),
			value: c ? void 0 : typeof t == "string" ? t : "",
			disabled: n,
			multiple: c,
			onChange: (e) => {
				let t = e.target;
				r(c ? Array.from(t.selectedOptions).map((e) => e.value) : t.value);
			}
		}, s.map((t) => {
			let n = String(t.value ?? "");
			return i("option", {
				key: `${e.id}:${n}`,
				value: n,
				disabled: t.disabled === !0
			}, String(t.label ?? n));
		}));
	}
	let s = typeof o.inputType == "string" ? o.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return i("input", {
		...K(e, a),
		type: s,
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof o.placeholder == "string" ? o.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function ee(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function te({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: s } = V(), u = c(null), d = c(null), [f, p] = l(null), m = ee(e, s.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
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
			style: r ? { pointerEvents: "none" } : void 0
		}),
		i("button", {
			key: "clear",
			type: "button",
			disabled: r,
			"data-formie-signature-clear": !0,
			onClick: () => {
				d.current?.clear(), a("");
			}
		}, "Clear"),
		f ? i("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, f) : null
	]);
}
function ne({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: o } = V(), s = f(e), c = t && typeof t == "object" ? t : {};
	return s.length === 0 ? i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`) : i("div", { className: "formie-react-name-grid" }, s.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let s = `${n}.${t.handle}`;
		return i(X, {
			key: `${e.id}:${t.handle}`,
			field: t,
			value: c[t.handle],
			errors: o.errors.fields[s] || [],
			errorKey: s,
			disabled: r || t.meta?.disabled === !0,
			setValue(e) {
				a({
					...c,
					[t.handle]: e
				});
			}
		});
	}));
}
function re({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
	let o = e.input, s = Array.isArray(t) ? t : [], c = o.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return i("div", { className: "formie-react-file" }, [i("input", {
		key: "input",
		type: "file",
		...K(e, n),
		disabled: r,
		multiple: c,
		onChange: (e) => {
			let t = e.target;
			a(Array.from(t.files || []));
		}
	}), l.length > 0 ? i("ul", {
		key: "summary",
		className: "formie-react-field-errors"
	}, l.map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null]);
}
function ie({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: o } = V(), s = k(e), c = Array.isArray(t) ? t : [], l = e.input, u = Number(l.minRows ?? 0) || 0, d = Number(l.maxRows ?? 0) || 0, f = !r && (d <= 0 || c.length < d);
	return s.length === 0 ? i("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : i("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0
	}, [
		...c.map((t, o) => {
			let l = `${e.id}:${o}`;
			return i("div", {
				key: l,
				className: "formie-react-repeater-item",
				"data-formie-repeater-item": !0
			}, [...s.map((e, s) => i(Z, {
				key: `${l}:${s}`,
				row: e,
				rowIndex: s,
				values: t,
				errorPrefix: `${n}.${o}`,
				disabled: r,
				setFieldValue(e, t) {
					a(c.map((n, r) => r === o ? {
						...n,
						[e.handle]: t
					} : n));
				}
			})), i("button", {
				key: "remove",
				type: "button",
				disabled: r || u > 0 && c.length <= u,
				"data-formie-repeater-remove": !0,
				onClick: () => {
					a(c.filter((e, t) => t !== o));
				}
			}, "Remove")]);
		}),
		i("button", {
			key: "add",
			type: "button",
			disabled: !f,
			"data-formie-repeater-add": e.handle,
			onClick: () => {
				a([...c, h(e)]);
			}
		}, String(l.addRowLabel ?? "Add another row")),
		o.errors.fields[n] && o.errors.fields[n].length > 0 ? i("ul", {
			key: "errors",
			className: "formie-react-field-errors"
		}, o.errors.fields[n].map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null
	]);
}
function Y(e, t = !0) {
	let { field: n, value: r, errorKey: a, errorId: o, disabled: s, setValue: c } = e, l = n.input, u = H(n), d = W(e, t);
	if (x(n)) return i(ne, {
		field: n,
		value: r,
		errorKey: a,
		disabled: s,
		setValue: c
	});
	if (T(n)) return i(ie, {
		field: n,
		value: r,
		errorKey: a,
		disabled: s,
		setValue: c
	});
	if (C(n)) return i(re, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return i(te, {
		field: n,
		value: r,
		errorKey: a,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return J(n, r, s, c, d);
	if (u === "radio") {
		let t = Array.isArray(l.options) ? l.options : [];
		return i("div", {
			className: "formie-react-choices",
			...q(n, d, "radiogroup")
		}, t.map((t) => {
			let a = String(t.value ?? ""), l = s || t.disabled === !0;
			return i("label", { key: `${n.id}:${a}` }, [i("input", {
				key: "input",
				type: "radio",
				...U(e.errors, o),
				checked: r === a,
				disabled: l,
				onChange: () => {
					c(a);
				}
			}), i("span", { key: "label" }, String(t.label ?? a))]);
		}));
	}
	if (u === "checkboxes") {
		let t = Array.isArray(l.options) ? l.options : [], a = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return i("div", {
			className: "formie-react-choices",
			...q(n, d, "group")
		}, t.map((t) => {
			let r = String(t.value ?? ""), l = a.includes(r), u = s || t.disabled === !0;
			return i("label", { key: `${n.id}:${r}` }, [i("input", {
				key: "input",
				type: "checkbox",
				...U(e.errors, o),
				checked: l,
				disabled: u,
				onChange: () => {
					let e = l ? a.filter((e) => e !== r) : [...a, r];
					c(e);
				}
			}), i("span", { key: "label" }, String(t.label ?? r))]);
		}));
	}
	if (u === "agree") {
		let t = typeof l.descriptionHtml == "string" ? l.descriptionHtml : null;
		return i("div", {
			className: "formie-react-agree",
			...q(n, d, "group")
		}, i("label", { className: "formie-react-boolean" }, [i("input", {
			key: "input",
			type: "checkbox",
			id: e.inputId || void 0,
			"aria-required": n.required ? "true" : void 0,
			...U(e.errors, o),
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
	let f = _(n);
	return f ? f.kind === "heading" ? i(f.level, { className: "formie-react-heading" }, f.text) : i("div", {
		className: "formie-react-content",
		dangerouslySetInnerHTML: { __html: f.html }
	}) : w(u) ? J(n, r, s, c, d) : i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`);
}
function X({ field: e, value: t, errors: n, errorKey: r, disabled: a, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = V(), u = l.fieldStates[r]?.hidden === !0;
	if (u) return null;
	let d = H(e), f = c[e.type] || c[d] || Y, p = s.Field || L, m = y(l.session, r), h = m.errors, g = v(l.definition), _ = {
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
		children: f === Y ? Y(_, !s.Field) : f(_)
	});
}
function ae({ field: e }) {
	let { state: t, instance: n } = V(), r = t.fieldStates[e.id];
	return i(X, {
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
function Z({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: a, setFieldValue: o }) {
	let { state: s } = V();
	return i("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return i(ae, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return i(X, {
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
function oe() {
	let { state: e, instance: t } = V(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
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
		disabled: !d(e)
	}, n.actions.primary.label)), i("div", { className: "formie-page-actions" }, r);
}
function se({ className: e }) {
	let { instance: t, state: n, components: r } = V(), a = r.Form || z, o = r.Page || B, s = r.ErrorSummary || I, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l);
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
				dangerouslySetInnerHTML: { __html: n.lastSubmitResult.messages.notice }
			}) : null,
			u ? i("div", {
				key: "error",
				className: "formie-react-error"
			}, l) : null,
			n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm ? null : i(o, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => i(Z, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), i(oe, { key: "actions" })]
			})
		]
	}) : null;
}
function Q(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function ce({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: a, onMount: d, onReady: f, onUnmount: m, onResult: h, onSuccess: g, onError: _, onSubmitResult: v, onSubmitSuccess: y, onSubmitError: b, onEvent: x }) {
	let [S, C] = l(null), [w, T] = l(null), [E, D] = l(null), O = c(d), k = c(f), A = c(m), N = c(h), I = c(g), L = c(_), z = c(v), B = c(y), V = c(b), H = c(x), U = s(() => j(e), [e]), W = c(e);
	o(() => {
		O.current = d;
	}, [d]), o(() => {
		k.current = f;
	}, [f]), o(() => {
		A.current = m;
	}, [m]), o(() => {
		N.current = h;
	}, [h]), o(() => {
		I.current = g;
	}, [g]), o(() => {
		L.current = _;
	}, [_]), o(() => {
		z.current = v;
	}, [v]), o(() => {
		B.current = y;
	}, [y]), o(() => {
		V.current = b;
	}, [b]), o(() => {
		H.current = x;
	}, [x]), o(() => {
		W.current = e;
	}, [e, U]), o(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = await P(W.current), r = F(W.current), i = p({
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
						Q(z.current, N.current, t), t.success ? Q(B.current, I.current, t) : Q(V.current, L.current, t);
					}),
					...u.map((e) => i.on(e, (t) => {
						H.current?.({
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
	}, [U]);
	let G = s(() => !S || !w ? null : {
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
	return E ? i("div", { className: "formie-react-error" }, E.message) : G ? i(M.Provider, {
		value: G,
		children: i(R, { children: i(se, { className: a }) })
	}) : i("div", { className: "formie-react-loading" }, "Loading form...");
}
function le() {
	let e = V();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function ue(e) {
	let t = V(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function de(e) {
	let t = V();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function fe() {
	return V().instance;
}
function pe(e) {
	return V().slots[e] || null;
}
//#endregion
//#region src/index.ts
function me(e) {
	return !!e && "payload" in e;
}
function he(e) {
	return "success" in e ? e.success : e.ok;
}
function $(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function ge(e) {
	let t = e.transport;
	if (!t && !me(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: me(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function _e(e) {
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
function ve({ source: n, transport: r, profile: a, endpoint: l, formHandle: u, staticCache: d, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = c(null), A = c(null), M = c(y), N = c(b), P = c(x), F = c(S), I = c(C), L = c(w), R = c(T), z = c(E), B = c(D), V = c(O), H = s(() => ge({
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
	]), U = s(() => j(H), [H]), W = c(H);
	return o(() => {
		M.current = y, N.current = b, P.current = x, F.current = S, I.current = C, L.current = w, R.current = T, z.current = E, B.current = D, V.current = O;
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
		W.current = H;
	}, [H, U]), A.current ||= t(), o(() => {
		let t = k.current, n = A.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, W.current).then((t) => {
			r || (M.current?.(t), N.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				$(R.current, F.current, t), he(t) ? $(z.current, I.current, t) : $(B.current, L.current, t);
			})), e.forEach((e) => {
				i.push(t.on(e, (t) => {
					V.current?.({
						name: e,
						payload: t
					});
				}));
			}));
		}), () => {
			r = !0, i.forEach((e) => e()), t && n && n.unmount(t).finally(() => {
				P.current?.();
			});
		};
	}, [U]), i("div", {
		ref: k,
		className: v
	});
}
function ye({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, staticCache: o, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return i(ve, {
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
function be({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, siteId: o, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return i(ce, {
		source: _e({
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
function xe() {
	return s(() => t(), []);
}
function Se(e) {
	let t = c(null), n = xe(), r = s(() => j(e), [e]), i = c(e), [a, u] = l(null), [d, f] = l(null);
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
export { be as FormieClientForm, ye as FormieForm, le as useFormie, xe as useFormieClient, ue as useFormieField, Se as useFormieHtml, fe as useFormieInstance, de as useFormiePage, pe as useFormieSlot };
