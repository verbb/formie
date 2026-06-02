import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t } from "@verbb/formie-browser";
import { createContext as n, createElement as r, useContext as i, useEffect as a, useMemo as o, useRef as s, useState as c } from "react";
import { FRONTEND_CLIENT_EVENT_NAMES as l, compositePartDefinitions as u, createFrontendFormInstance as d, createGraphqlFrontendTransport as f, createRepeaterRowValue as p, createRestFrontendTransport as m, isCompositeField as h, isFileField as g, isKnownFrontendFieldType as _, isRepeatableField as v, loadFrontendEnvelope as y, loadGraphqlFrontendEnvelope as b, repeaterRowDefinitions as x } from "@verbb/formie-core";
//#region src/stable.ts
function S(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => S(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${S(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function C(e) {
	return S(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.tsx
var w = n(null);
function T(e) {
	return "definition" in e;
}
async function E(e) {
	return T(e) ? e.definition : e.transport === "graphql" ? b({
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	}) : y({
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	});
}
function D(e) {
	let t = T(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	};
	return t.type === "graphql" ? f(t) : m(t);
}
function O({ errors: e }) {
	return e.length === 0 ? null : r("div", { className: "formie-react-errors" }, r("ul", null, e.map((e, t) => r("li", { key: `${e}:${t}` }, e))));
}
function k({ field: e, errors: t, children: n }) {
	let { slots: i } = M(), a = (e, t, n) => {
		let a = i[e];
		return a ? r(a, {
			slotKey: e,
			children: t,
			attributes: n
		}) : t;
	};
	return r("div", {
		className: "formie-react-field",
		"data-field-type": e.type
	}, [
		e.label ? a("label", r("label", {
			key: "label",
			className: "formie-react-label"
		}, e.label)) : null,
		e.instructions ? a("instructions", r("div", {
			key: "instructions",
			className: "formie-react-description"
		}, e.instructions)) : null,
		a("input", r("div", {
			key: "input",
			className: "formie-react-input"
		}, n)),
		t.length > 0 ? a("errors", r("ul", {
			key: "errors",
			className: "formie-react-field-errors"
		}, t.map((e, t) => r("li", { key: `${e}:${t}` }, e)))) : null
	]);
}
function A({ definition: e, session: t, state: n, children: i, className: a, onSubmit: o }) {
	return r("form", {
		className: a,
		onSubmit: (e) => {
			e.preventDefault(), o();
		},
		"data-formie-definition": e.handle,
		"data-formie-render-id": t.tokens.render
	}, i);
}
function j({ page: e, children: t }) {
	return r("section", {
		"data-page-id": e.id,
		className: "formie-react-page"
	}, t);
}
function M() {
	let e = i(w);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieForm />.");
	return e;
}
function N(e) {
	if (_(e.type)) return e.type;
	let t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
	return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function P(e, t, n, i) {
	let a = e.input;
	if (e.type === "multi-line-text") return r("textarea", {
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof a.placeholder == "string" ? a.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			i(t.value);
		}
	});
	if (e.type === "dropdown") {
		let o = Array.isArray(a.options) ? a.options : [], s = a.multiple === !0;
		return r("select", {
			value: s ? void 0 : typeof t == "string" ? t : "",
			disabled: n,
			multiple: s,
			onChange: (e) => {
				let t = e.target;
				if (s) {
					i(Array.from(t.selectedOptions).map((e) => e.value));
					return;
				}
				i(t.value);
			}
		}, o.map((t) => {
			let n = String(t.value ?? "");
			return r("option", {
				key: `${e.id}:${n}`,
				value: n,
				disabled: t.disabled === !0
			}, String(t.label ?? n));
		}));
	}
	return r("input", {
		type: typeof a.inputType == "string" ? a.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text",
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof a.placeholder == "string" ? a.placeholder : void 0,
		onChange: (e) => {
			let t = e.target;
			i(t.value);
		}
	});
}
function F(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.find((e) => r.has(e.id) && e.capability === n) || null;
}
function I({ field: e, value: t, errorKey: n, disabled: i, setValue: o }) {
	let { state: l } = M(), u = s(null), d = s(null), [f, p] = c(null), m = F(e, l.definition, "draw-signature")?.config, h = typeof m?.options == "object" && m.options && typeof m.options.backgroundColor == "string" ? String(m.options.backgroundColor) : "#ffffff", g = typeof m?.options == "object" && m.options && typeof m.options.penColor == "string" ? String(m.options.penColor) : "#000000", _ = typeof m?.options == "object" && m.options && Number(m.options.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
	return a(() => {
		let e = !1, t = () => void 0, n = () => void 0;
		return (async () => {
			try {
				let r = u.current;
				if (!r) return;
				let { default: i } = await import("./signature_pad-69C-Dgt3.js");
				if (e) return;
				let a = new i(r, {
					backgroundColor: h,
					penColor: g,
					minWidth: _,
					maxWidth: _
				}), s = () => {
					let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), t = Math.max(1, Math.floor(r.clientWidth || 480)), n = r.getContext("2d");
					r.width = t * e, r.height = 192 * e, r.style.height = "192px", n && (n.setTransform(1, 0, 0, 1, 0, 0), n.scale(e, e)), a.clear();
				}, c = () => {
					o(a.isEmpty() ? "" : a.toDataURL());
				};
				s(), a.addEventListener?.("endStroke", c), t = () => {
					a.removeEventListener?.("endStroke", c);
				}, typeof window < "u" && (window.addEventListener("resize", s), n = () => {
					window.removeEventListener("resize", s);
				}), d.current = a, p(null);
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
	]), a(() => {
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
	}, [v]), r("div", { className: "formie-react-signature" }, [
		r("canvas", {
			key: "canvas",
			ref: u,
			"data-formie-signature-canvas": !0,
			style: i ? { pointerEvents: "none" } : void 0
		}),
		r("button", {
			key: "clear",
			type: "button",
			disabled: i,
			"data-formie-signature-clear": !0,
			onClick: () => {
				d.current?.clear(), o("");
			}
		}, "Clear"),
		f ? r("div", {
			key: "error",
			className: "formie-react-unsupported"
		}, f) : null
	]);
}
function L({ field: e, value: t, errorKey: n, disabled: i, setValue: a }) {
	let { state: o } = M(), s = u(e), c = t && typeof t == "object" ? t : {};
	return s.length === 0 ? r("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${e.type}`) : r("div", { className: "formie-react-name-grid" }, s.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let s = `${n}.${t.handle}`;
		return r(V, {
			key: `${e.id}:${t.handle}`,
			field: t,
			value: c[t.handle],
			errors: o.errors.fields[s] || [],
			errorKey: s,
			disabled: i || t.meta?.disabled === !0,
			setValue(e) {
				a({
					...c,
					[t.handle]: e
				});
			}
		});
	}));
}
function R({ field: e, value: t, disabled: n, setValue: i }) {
	let a = e.input, o = Array.isArray(t) ? t : [], s = a.multiple === !0, c = o.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return r("div", { className: "formie-react-file" }, [r("input", {
		key: "input",
		type: "file",
		disabled: n,
		multiple: s,
		onChange: (e) => {
			let t = e.target;
			i(Array.from(t.files || []));
		}
	}), c.length > 0 ? r("ul", {
		key: "summary",
		className: "formie-react-field-errors"
	}, c.map((e, t) => r("li", { key: `${e}:${t}` }, e))) : null]);
}
function z({ field: e, value: t, errorKey: n, disabled: i, setValue: a }) {
	let { state: o } = M(), s = x(e), c = Array.isArray(t) ? t : [], l = e.input, u = Number(l.minRows ?? 0) || 0, d = Number(l.maxRows ?? 0) || 0, f = !i && (d <= 0 || c.length < d);
	return s.length === 0 ? r("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : r("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0
	}, [
		...c.map((t, o) => {
			let l = `${e.id}:${o}`;
			return r("div", {
				key: l,
				className: "formie-react-repeater-item",
				"data-formie-repeater-item": !0
			}, [...s.map((e, s) => r(U, {
				key: `${l}:${s}`,
				row: e,
				rowIndex: s,
				values: t,
				errorPrefix: `${n}.${o}`,
				disabled: i,
				setFieldValue(e, t) {
					a(c.map((n, r) => r === o ? {
						...n,
						[e.handle]: t
					} : n));
				}
			})), r("button", {
				key: "remove",
				type: "button",
				disabled: i || u > 0 && c.length <= u,
				"data-formie-repeater-remove": !0,
				onClick: () => {
					a(c.filter((e, t) => t !== o));
				}
			}, "Remove")]);
		}),
		r("button", {
			key: "add",
			type: "button",
			disabled: !f,
			"data-formie-repeater-add": e.handle,
			onClick: () => {
				a([...c, p(e)]);
			}
		}, String(l.addLabel ?? "Add another row")),
		o.errors.fields[n] && o.errors.fields[n].length > 0 ? r("ul", {
			key: "errors",
			className: "formie-react-field-errors"
		}, o.errors.fields[n].map((e, t) => r("li", { key: `${e}:${t}` }, e))) : null
	]);
}
function B(e) {
	let { field: t, value: n, errorKey: i, disabled: a, setValue: o } = e, s = t.input, c = N(t);
	if (h(t)) return r(L, {
		field: t,
		value: n,
		errorKey: i,
		disabled: a,
		setValue: o
	});
	if (v(t)) return r(z, {
		field: t,
		value: n,
		errorKey: i,
		disabled: a,
		setValue: o
	});
	if (g(t)) return r(R, {
		field: t,
		value: n,
		disabled: a,
		setValue: o
	});
	if (c === "signature") return r(I, {
		field: t,
		value: n,
		errorKey: i,
		disabled: a,
		setValue: o
	});
	if (c === "multi-line-text" || c === "dropdown") return P(t, n, a, o);
	if (c === "radio") return r("div", { className: "formie-react-choices" }, (Array.isArray(s.options) ? s.options : []).map((e) => {
		let i = String(e.value ?? "");
		return r("label", { key: `${t.id}:${i}` }, [r("input", {
			key: "input",
			type: "radio",
			checked: n === i,
			disabled: a,
			onChange: () => {
				o(i);
			}
		}), r("span", { key: "label" }, String(e.label ?? i))]);
	}));
	if (c === "checkboxes") {
		let e = Array.isArray(s.options) ? s.options : [], i = Array.isArray(n) ? n.map((e) => String(e)) : [];
		return r("div", { className: "formie-react-choices" }, e.map((e) => {
			let n = String(e.value ?? ""), s = i.includes(n);
			return r("label", { key: `${t.id}:${n}` }, [r("input", {
				key: "input",
				type: "checkbox",
				checked: s,
				disabled: a,
				onChange: () => {
					o(s ? i.filter((e) => e !== n) : [...i, n]);
				}
			}), r("span", { key: "label" }, String(e.label ?? n))]);
		}));
	}
	if (c === "agree") {
		let e = typeof s.descriptionHtml == "string" ? s.descriptionHtml : null;
		return r("label", { className: "formie-react-boolean" }, [r("input", {
			key: "input",
			type: "checkbox",
			checked: n === !0,
			disabled: a,
			onChange: (e) => {
				let t = e.target;
				o(t.checked);
			}
		}), e ? r("span", {
			key: "description",
			dangerouslySetInnerHTML: { __html: e }
		}) : r("span", { key: "description" }, t.label)]);
	}
	return _(c) ? P(t, n, a, o) : r("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(t.meta?.fieldType ?? t.type)}`);
}
function V({ field: e, value: t, errors: n, errorKey: i, disabled: a, setValue: o }) {
	let { components: s, fieldComponents: c, state: l } = M(), u = l.fieldStates[e.id]?.hidden === !0;
	if (u) return null;
	let d = N(e), f = c[e.type] || c[d] || B;
	return r(s.Field || k, {
		field: e,
		errors: n,
		children: f({
			field: e,
			value: t,
			errors: n,
			errorKey: i,
			disabled: a,
			hidden: u,
			setValue: o
		})
	});
}
function H({ field: e }) {
	let { state: t, instance: n } = M(), i = t.fieldStates[e.id];
	return r(V, {
		field: e,
		value: t.values[e.id],
		errors: t.errors.fields[e.id] || [],
		errorKey: e.id,
		disabled: i?.disabled === !0,
		setValue(t) {
			n.setValue(e.id, t);
		}
	});
}
function U({ row: e, rowIndex: t, values: n, errorPrefix: i, disabled: a, setFieldValue: o }) {
	let { state: s } = M();
	return r("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !o) return r(H, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${i}.${e.handle}`;
		return r(V, {
			key: e.id || `${t}:${c}`,
			field: e,
			value: n[e.handle],
			errors: s.errors.fields[l] || [],
			errorKey: l,
			disabled: a === !0 || s.fieldStates[e.id]?.disabled === !0,
			setValue(t) {
				o(e, t);
			}
		});
	}));
}
function W() {
	let { state: e, instance: t } = M(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
	if (!n) return null;
	let i = [];
	return n.actions.secondary.forEach((e) => {
		i.push(r("button", {
			key: e.type,
			type: "button",
			onClick: () => {
				t.submit(e.type);
			}
		}, e.label));
	}), i.push(r("button", {
		key: n.actions.primary.type,
		type: "submit"
	}, n.actions.primary.label)), r("div", { className: "formie-page-actions" }, i);
}
function G({ className: e }) {
	let { instance: t, state: n, components: i } = M(), a = i.Form || A, o = i.Page || j, s = i.ErrorSummary || O, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l);
	return c ? r(a, {
		definition: n.definition,
		session: n.session,
		state: n,
		className: e,
		onSubmit: () => {
			t.submit();
		},
		children: [
			r(s, {
				key: "errors",
				errors: n.errors.form
			}),
			n.lastSubmitResult?.messages.notice ? r("div", {
				key: "notice",
				className: "formie-react-notice"
			}, n.lastSubmitResult.messages.notice) : null,
			u ? r("div", {
				key: "error",
				className: "formie-react-error"
			}, l) : null,
			r(o, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => r(U, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), r(W, { key: "actions" })]
			})
		]
	}) : null;
}
function K(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function q({ source: e, components: t = {}, fieldComponents: n = {}, slots: i = {}, className: u, onMount: f, onReady: p, onUnmount: m, onResult: h, onSuccess: g, onError: _, onSubmitResult: v, onSubmitSuccess: y, onSubmitError: b, onEvent: x }) {
	let [S, T] = c(null), [O, k] = c(null), [A, j] = c(null), M = s(f), N = s(p), P = s(m), F = s(h), I = s(g), L = s(_), R = s(v), z = s(y), B = s(b), V = s(x), H = o(() => C(e), [e]), U = s(e);
	a(() => {
		M.current = f;
	}, [f]), a(() => {
		N.current = p;
	}, [p]), a(() => {
		P.current = m;
	}, [m]), a(() => {
		F.current = h;
	}, [h]), a(() => {
		I.current = g;
	}, [g]), a(() => {
		L.current = _;
	}, [_]), a(() => {
		R.current = v;
	}, [v]), a(() => {
		z.current = y;
	}, [y]), a(() => {
		B.current = b;
	}, [b]), a(() => {
		V.current = x;
	}, [x]), a(() => {
		U.current = e;
	}, [e, H]), a(() => {
		let e = !1, t = () => void 0;
		return (async () => {
			try {
				let n = d({
					envelope: await E(U.current),
					transport: D(U.current)
				});
				if (e) {
					await n.destroy();
					return;
				}
				j(null), T(n), k(n.getState()), M.current?.(n), N.current?.(n);
				let r = [
					n.subscribe((e) => {
						k(e);
					}),
					n.on("formie:submit:result", (e) => {
						let t = e;
						K(R.current, F.current, t), t.success ? K(z.current, I.current, t) : K(B.current, L.current, t);
					}),
					...l.map((e) => n.on(e, (t) => {
						V.current?.({
							name: e,
							payload: t
						});
					}))
				];
				t = () => {
					r.forEach((e) => e()), n.destroy(), P.current?.();
				};
			} catch (t) {
				e || j(t);
			}
		})(), () => {
			e = !0, t();
		};
	}, [H]);
	let W = o(() => !S || !O ? null : {
		instance: S,
		state: O,
		components: t,
		fieldComponents: n,
		slots: i
	}, [
		t,
		n,
		S,
		i,
		O
	]);
	return A ? r("div", { className: "formie-react-error" }, A.message) : W ? r(w.Provider, {
		value: W,
		children: r(G, { className: u })
	}) : r("div", { className: "formie-react-loading" }, "Loading form...");
}
function J() {
	let e = M();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function Y(e) {
	let t = M(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function X(e) {
	let t = M();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function ee() {
	return M().instance;
}
function te(e) {
	return M().slots[e] || null;
}
//#endregion
//#region src/index.ts
function Z(e) {
	return !!e && "payload" in e;
}
function ne(e) {
	return "success" in e ? e.success : e.ok;
}
function Q(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function re(e) {
	let t = e.transport;
	if (!t && !Z(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: Z(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function ie(e) {
	if (e.source) return e.source;
	let t = e.transport, n = e.endpoint, r = e.formHandle;
	if (t !== "rest" && t !== "graphql") throw Error("React client-rendered forms require `transport=\"rest\"` or `transport=\"graphql\"`.");
	if (!n || !r) throw Error("React client-rendered forms require either `source` or both `endpoint` and `formHandle`.");
	return {
		transport: t,
		endpoint: n,
		formHandle: r,
		siteId: e.siteId
	};
}
function ae({ source: n, transport: i, endpoint: c, formHandle: l, staticCache: u, refreshTokens: d, locale: f, siteId: p, autoVisible: m, theme: h, themeConfig: g, className: _, onMount: v, onReady: y, onUnmount: b, onResult: x, onSuccess: S, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = s(null), A = s(null), j = s(v), M = s(y), N = s(b), P = s(x), F = s(S), I = s(w), L = s(T), R = s(E), z = s(D), B = s(O), V = o(() => re({
		transport: i,
		endpoint: c,
		formHandle: l,
		staticCache: u,
		refreshTokens: d,
		locale: f,
		siteId: p,
		autoVisible: m,
		theme: h,
		themeConfig: g,
		source: n
	}), [
		i,
		c,
		l,
		u,
		d,
		f,
		p,
		m,
		h,
		g,
		n
	]), H = o(() => C(V), [V]), U = s(V);
	return a(() => {
		j.current = v, M.current = y, N.current = b, P.current = x, F.current = S, I.current = w, L.current = T, R.current = E, z.current = D, B.current = O;
	}, [
		v,
		y,
		b,
		x,
		S,
		w,
		T,
		E,
		D,
		O
	]), a(() => {
		U.current = V;
	}, [V, H]), A.current ||= t(), a(() => {
		let t = k.current, n = A.current;
		if (!t || !n) return;
		let r = !1, i = [];
		return n.mount(t, U.current).then((t) => {
			r || (j.current?.(t), M.current?.(t), i.push(t.on("formie:submit:result", (e) => {
				let t = e;
				Q(L.current, P.current, t), ne(t) ? Q(R.current, F.current, t) : Q(z.current, I.current, t);
			})), e.forEach((e) => {
				i.push(t.on(e, (t) => {
					B.current?.({
						name: e,
						payload: t
					});
				}));
			}));
		}), () => {
			r = !0, i.forEach((e) => e()), !(!t || !n) && n.unmount(t).finally(() => {
				N.current?.();
			});
		};
	}, [H]), r("div", {
		ref: k,
		className: _
	});
}
function oe({ source: e, transport: t, endpoint: n, formHandle: i, staticCache: a, refreshTokens: o, locale: s, siteId: c, autoVisible: l, theme: u, themeConfig: d, className: f, onMount: p, onReady: m, onUnmount: h, onResult: g, onSuccess: _, onError: v, onSubmitResult: y, onSubmitSuccess: b, onSubmitError: x, onEvent: S }) {
	return r(ae, {
		source: e,
		transport: t,
		endpoint: n,
		formHandle: i,
		staticCache: a,
		refreshTokens: o,
		locale: s,
		siteId: c,
		autoVisible: l,
		theme: u,
		themeConfig: d,
		className: f,
		onMount: p,
		onReady: m,
		onUnmount: h,
		onResult: g,
		onSuccess: _,
		onError: v,
		onSubmitResult: y,
		onSubmitSuccess: b,
		onSubmitError: x,
		onEvent: S
	});
}
function se({ source: e, transport: t, endpoint: n, formHandle: i, siteId: a, components: o, fieldComponents: s, slots: c, className: l, onMount: u, onReady: d, onUnmount: f, onResult: p, onSuccess: m, onError: h, onSubmitResult: g, onSubmitSuccess: _, onSubmitError: v, onEvent: y }) {
	return r(q, {
		source: ie({
			source: e,
			transport: t,
			endpoint: n,
			formHandle: i,
			siteId: a,
			components: o,
			fieldComponents: s,
			slots: c,
			className: l,
			onMount: u,
			onReady: d,
			onUnmount: f,
			onResult: p,
			onSuccess: m,
			onError: h,
			onSubmitResult: g,
			onSubmitSuccess: _,
			onSubmitError: v,
			onEvent: y
		}),
		components: o,
		fieldComponents: s,
		slots: c,
		className: l,
		onMount: u,
		onReady: d,
		onUnmount: f,
		onResult: p,
		onSuccess: m,
		onError: h,
		onSubmitResult: g,
		onSubmitSuccess: _,
		onSubmitError: v,
		onEvent: y
	});
}
function $() {
	return o(() => t(), []);
}
function ce(e) {
	let t = s(null), n = $(), r = o(() => C(e), [e]), i = s(e), [l, u] = c(null), [d, f] = c(null);
	return a(() => {
		i.current = e;
	}, [e, r]), a(() => {
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
			instance: l,
			isMounted: !!l,
			error: d
		},
		submit: async (e = "submit") => l ? l.submit(e) : null
	};
}
//#endregion
export { se as FormieClientForm, oe as FormieForm, J as useFormie, $ as useFormieClient, Y as useFormieField, ce as useFormieHtml, ee as useFormieInstance, X as useFormiePage, te as useFormieSlot };
