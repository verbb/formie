import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { createContext as r, createElement as i, useContext as a, useEffect as o, useMemo as s, useRef as c, useState as l } from "react";
import { CLIENT_FORM_EVENT_NAMES as u, clientActionAllowed as d, compositePartDefinitions as f, createClientFormInstance as p, createGraphqlClientTransport as m, createRepeaterRowValue as h, createRestClientTransport as g, getClientErrorAriaLive as _, getClientFieldErrorId as v, isCompositeField as y, isFileField as b, isKnownClientFieldType as x, isRepeatableField as S, loadClientFormBootstrap as C, loadGraphqlClientFormBootstrap as w, repeaterRowDefinitions as T } from "@verbb/formie-core";
//#region src/stable.ts
function E(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => E(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${E(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function D(e) {
	return E(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var O = r(null);
function k(e) {
	return "definition" in e;
}
async function A(e) {
	return k(e) ? e.definition : e.transport === "graphql" ? w({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : C({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function j(e) {
	let t = k(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? m(t) : g(t);
}
function M({ errors: e }) {
	return e.length === 0 ? null : i("div", { className: "formie-react-errors" }, i("ul", null, e.map((e, t) => i("li", { key: `${e}:${t}` }, e))));
}
function N({ field: e, errors: t, errorId: n, errorAriaLive: r, children: a }) {
	let { slots: o } = L(), s = (e, t, n) => {
		let r = o[e];
		return r ? i(r, {
			slotKey: e,
			children: t,
			attributes: n
		}) : t;
	};
	return i("div", {
		className: "formie-react-field",
		"data-formie-field-type": e.type,
		"data-formie-field-uid": e.uid,
		"data-formie-field-handle": e.handle
	}, [
		e.label ? s("label", i("label", {
			key: "label",
			className: "formie-react-label"
		}, e.label)) : null,
		e.instructions ? s("instructions", i("div", {
			key: "instructions",
			className: "formie-react-description",
			dangerouslySetInnerHTML: { __html: e.instructions }
		})) : null,
		s("input", i("div", {
			key: "input",
			className: "formie-react-input"
		}, a)),
		s("errors", i("ul", {
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
function P({ children: e }) {
	let { instance: t } = L(), r = c(null);
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
function F({ definition: e, session: t, state: n, children: r, className: a, onSubmit: s }) {
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
function I({ page: e, children: t }) {
	return i("section", {
		"data-page-id": e.id,
		"data-formie-page-id": e.id,
		className: "formie-react-page"
	}, t);
}
function L() {
	let e = a(O);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function R(e) {
	if (x(e.type)) return e.type;
	let t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
	return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function z(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function B(e, t, n, r, a = [], o = "") {
	let s = e.input;
	if (e.type === "multi-line-text") return i("textarea", {
		"aria-label": e.label || e.handle,
		...z(a, o),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (e.type === "dropdown") {
		let c = Array.isArray(s.options) ? s.options : [], l = s.multiple === !0;
		return i("select", {
			"aria-label": e.label || e.handle,
			...z(a, o),
			value: l ? void 0 : typeof t == "string" ? t : "",
			disabled: n,
			multiple: l,
			onChange: (e) => {
				let t = e.target;
				if (l) {
					r(Array.from(t.selectedOptions).map((e) => e.value));
					return;
				}
				r(t.value);
			}
		}, c.map((t) => {
			let n = String(t.value ?? "");
			return i("option", {
				key: `${e.id}:${n}`,
				value: n,
				disabled: t.disabled === !0
			}, String(t.label ?? n));
		}));
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return i("input", {
		"aria-label": e.label || e.handle,
		...z(a, o),
		type: c,
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
}
function V(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function H({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: s } = L(), u = c(null), d = c(null), [f, p] = l(null), m = V(e, s.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
	return o(() => {
		let e = !1, t = () => void 0, n = () => void 0;
		return (async () => {
			try {
				let r = u.current;
				if (!r) return;
				let { default: i } = await import("./signature_pad-dbpVgfTh.js");
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
			if (!v) {
				e.isEmpty() || e.clear();
				return;
			}
			try {
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
function U({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: o } = L(), s = f(e), c = t && typeof t == "object" ? t : {};
	return s.length === 0 ? i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`) : i("div", { className: "formie-react-name-grid" }, s.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let s = `${n}.${t.handle}`;
		return i(K, {
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
function W({ field: e, value: t, errors: n, errorId: r, disabled: a, setValue: o }) {
	let s = e.input, c = Array.isArray(t) ? t : [], l = s.multiple === !0, u = c.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return i("div", { className: "formie-react-file" }, [i("input", {
		key: "input",
		type: "file",
		...z(n, r),
		disabled: a,
		multiple: l,
		onChange: (e) => {
			let t = e.target;
			o(Array.from(t.files || []));
		}
	}), u.length > 0 ? i("ul", {
		key: "summary",
		className: "formie-react-field-errors"
	}, u.map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null]);
}
function G({ field: e, value: t, errorKey: n, disabled: r, setValue: a }) {
	let { state: o } = L(), s = T(e), c = Array.isArray(t) ? t : [], l = e.input, u = Number(l.minRows ?? 0) || 0, d = Number(l.maxRows ?? 0) || 0, f = !r && (d <= 0 || c.length < d);
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
			}, [...s.map((e, s) => i(q, {
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
		}, String(l.addLabel ?? "Add another row")),
		o.errors.fields[n] && o.errors.fields[n].length > 0 ? i("ul", {
			key: "errors",
			className: "formie-react-field-errors"
		}, o.errors.fields[n].map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null
	]);
}
function ee(e) {
	let { field: t, value: n, errorKey: r, errorId: a, disabled: o, setValue: s } = e, c = t.input, l = R(t);
	if (y(t)) return i(U, {
		field: t,
		value: n,
		errorKey: r,
		disabled: o,
		setValue: s
	});
	if (S(t)) return i(G, {
		field: t,
		value: n,
		errorKey: r,
		disabled: o,
		setValue: s
	});
	if (b(t)) return i(W, {
		field: t,
		value: n,
		errors: e.errors,
		errorId: a,
		disabled: o,
		setValue: s
	});
	if (l === "signature") return i(H, {
		field: t,
		value: n,
		errorKey: r,
		disabled: o,
		setValue: s
	});
	if (l === "multi-line-text" || l === "dropdown") return B(t, n, o, s, e.errors, a);
	if (l === "radio") {
		let r = Array.isArray(c.options) ? c.options : [];
		return i("div", { className: "formie-react-choices" }, r.map((r) => {
			let c = String(r.value ?? ""), l = o || r.disabled === !0;
			return i("label", { key: `${t.id}:${c}` }, [i("input", {
				key: "input",
				type: "radio",
				...z(e.errors, a),
				checked: n === c,
				disabled: l,
				onChange: () => {
					s(c);
				}
			}), i("span", { key: "label" }, String(r.label ?? c))]);
		}));
	}
	if (l === "checkboxes") {
		let r = Array.isArray(c.options) ? c.options : [], l = Array.isArray(n) ? n.map((e) => String(e)) : [];
		return i("div", { className: "formie-react-choices" }, r.map((n) => {
			let r = String(n.value ?? ""), c = l.includes(r), u = o || n.disabled === !0;
			return i("label", { key: `${t.id}:${r}` }, [i("input", {
				key: "input",
				type: "checkbox",
				...z(e.errors, a),
				checked: c,
				disabled: u,
				onChange: () => {
					let e = c ? l.filter((e) => e !== r) : [...l, r];
					s(e);
				}
			}), i("span", { key: "label" }, String(n.label ?? r))]);
		}));
	}
	if (l === "agree") {
		let r = typeof c.descriptionHtml == "string" ? c.descriptionHtml : null;
		return i("label", { className: "formie-react-boolean" }, [i("input", {
			key: "input",
			type: "checkbox",
			...z(e.errors, a),
			checked: n === !0,
			disabled: o,
			onChange: (e) => {
				let t = e.target;
				s(t.checked);
			}
		}), r ? i("span", {
			key: "description",
			dangerouslySetInnerHTML: { __html: r }
		}) : i("span", { key: "description" }, t.label)]);
	}
	return x(l) ? B(t, n, o, s, e.errors, a) : i("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(t.meta?.fieldType ?? t.type)}`);
}
function K({ field: e, value: t, errors: n, errorKey: r, disabled: a, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = L(), u = l.fieldStates[r]?.hidden === !0;
	if (u) return null;
	let d = R(e), f = c[e.type] || c[d] || ee, p = s.Field || N, m = v(l.session, r), h = _(l.definition);
	return i(p, {
		field: e,
		errors: n,
		errorId: m,
		errorAriaLive: h,
		children: f({
			field: e,
			value: t,
			errors: n,
			errorKey: r,
			errorId: m,
			errorAriaLive: h,
			disabled: a,
			hidden: u,
			setValue: o
		})
	});
}
function te({ field: e }) {
	let { state: t, instance: n } = L(), r = t.fieldStates[e.id];
	return i(K, {
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
function q({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: a, setFieldValue: o }) {
	let { state: s } = L();
	return i("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return i(te, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return i(K, {
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
function ne() {
	let { state: e, instance: t } = L(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
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
function re({ className: e }) {
	let { instance: t, state: n, components: r } = L(), a = r.Form || F, o = r.Page || I, s = r.ErrorSummary || M, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l);
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
				children: [...c.rows.map((e, t) => i(q, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), i(ne, { key: "actions" })]
			})
		]
	}) : null;
}
function J(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Y({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: a, onMount: d, onReady: f, onUnmount: m, onResult: h, onSuccess: g, onError: _, onSubmitResult: v, onSubmitSuccess: y, onSubmitError: b, onEvent: x }) {
	let [S, C] = l(null), [w, T] = l(null), [E, k] = l(null), M = c(d), N = c(f), F = c(m), I = c(h), L = c(g), R = c(_), z = c(v), B = c(y), V = c(b), H = c(x), U = s(() => D(e), [e]), W = c(e);
	o(() => {
		M.current = d;
	}, [d]), o(() => {
		N.current = f;
	}, [f]), o(() => {
		F.current = m;
	}, [m]), o(() => {
		I.current = h;
	}, [h]), o(() => {
		L.current = g;
	}, [g]), o(() => {
		R.current = _;
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
				let n = await A(W.current), r = j(W.current), i = p({
					envelope: n,
					transport: r
				});
				if (e) {
					await i.destroy();
					return;
				}
				k(null), C(i), T(i.getState()), M.current?.(i), N.current?.(i);
				let a = [
					i.subscribe((e) => {
						T(e);
					}),
					i.on("formie:submit:result", (e) => {
						let t = e;
						J(z.current, I.current, t), t.success ? J(B.current, L.current, t) : J(V.current, R.current, t);
					}),
					...u.map((e) => i.on(e, (t) => {
						H.current?.({
							name: e,
							payload: t
						});
					}))
				];
				t = () => {
					a.forEach((e) => e()), i.destroy(), F.current?.();
				};
			} catch (t) {
				e || k(t);
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
	return E ? i("div", { className: "formie-react-error" }, E.message) : G ? i(O.Provider, {
		value: G,
		children: i(P, { children: i(re, { className: a }) })
	}) : i("div", { className: "formie-react-loading" }, "Loading form...");
}
function ie() {
	let e = L();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function ae(e) {
	let t = L(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function oe(e) {
	let t = L();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function se() {
	return L().instance;
}
function ce(e) {
	return L().slots[e] || null;
}
//#endregion
//#region src/index.ts
function X(e) {
	return !!e && "payload" in e;
}
function le(e) {
	return "success" in e ? e.success : e.ok;
}
function Z(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function ue(e) {
	let t = e.transport;
	if (!t && !X(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: X(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function Q(e) {
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
function de({ source: n, transport: r, profile: a, endpoint: l, formHandle: u, staticCache: d, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: O, onEvent: k }) {
	let A = c(null), j = c(null), M = c(y), N = c(b), P = c(x), F = c(S), I = c(C), L = c(w), R = c(T), z = c(E), B = c(O), V = c(k), H = s(() => ue({
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
	]), U = s(() => D(H), [H]), W = c(H);
	return o(() => {
		M.current = y, N.current = b, P.current = x, F.current = S, I.current = C, L.current = w, R.current = T, z.current = E, B.current = O, V.current = k;
	}, [
		y,
		b,
		x,
		S,
		C,
		w,
		T,
		E,
		O,
		k
	]), o(() => {
		W.current = H;
	}, [H, U]), j.current ||= t(), o(() => {
		let t = A.current, n = j.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, W.current).then((t) => {
			r || (M.current?.(t), N.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				Z(R.current, F.current, t), le(t) ? Z(z.current, I.current, t) : Z(B.current, L.current, t);
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
		ref: A,
		className: v
	});
}
function fe({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, staticCache: o, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return i(de, {
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
function pe({ source: e, transport: t, profile: n, endpoint: r, formHandle: a, siteId: o, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return i(Y, {
		source: Q({
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
function $() {
	return s(() => t(), []);
}
function me(e) {
	let t = c(null), n = $(), r = s(() => D(e), [e]), i = c(e), [a, u] = l(null), [d, f] = l(null);
	return o(() => {
		i.current = e;
	}, [e, r]), o(() => {
		let e = t.current;
		if (!e) return;
		let r = !1, a = !1, o = async () => {
			a || (a = !0, await n.unmount(e));
		}, s = new Promise((t) => {
			queueMicrotask(() => {
				if (r) {
					t();
					return;
				}
				n.mount(e, {
					...i.current,
					mode: "server-rendered"
				}).then(async (e) => {
					if (r) {
						await o(), t();
						return;
					}
					u(e), f(null), t();
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
export { pe as FormieClientForm, fe as FormieForm, ie as useFormie, $ as useFormieClient, ae as useFormieField, me as useFormieHtml, se as useFormieInstance, oe as useFormiePage, ce as useFormieSlot };
