import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { Fragment as r, cloneElement as i, createContext as a, createElement as o, isValidElement as s, useContext as c, useEffect as l, useMemo as u, useRef as d, useState as f } from "react";
import { CLIENT_FORM_EVENT_NAMES as p, PHONE_COUNTRY_CHANGE_EVENT as m, VISUALLY_HIDDEN_STYLE as h, addressAutocompleteMarkup as g, addressPartMarker as _, addressStatePart as v, allOptionsSelected as y, attachPointerReorder as b, checkboxToggle as x, clientActionAllowed as S, compositePartDefinitions as C, createClientFormInstance as w, createGraphqlClientTransport as T, createRepeaterRowValue as E, createRestClientTransport as D, createTableRowValue as O, customFieldMarkup as k, datePickerInputValue as A, datePickerValue as j, dateRangePickerMarkup as M, dateRangePickerValue as N, displayFieldContent as P, dropdownOptions as F, fieldContentPath as I, fieldOptions as L, focusClientFormResult as R, getClientErrorAriaLive as z, getClientFieldIds as B, getClientFieldLabelTarget as V, getClientFieldLayout as H, getClientPageProgress as U, getClientString as W, isAddressAutocompletePart as ee, isAddressField as te, isArrayValuedField as ne, isCalculationField as re, isCompositeField as ie, isDatePickerField as ae, isDateRangePickerField as oe, isDynamicAddressStatePart as se, isFieldLabelHidden as ce, isFieldLabelVisuallyHidden as le, isFileField as ue, isKnownClientFieldType as de, isPhoneCountryEnabled as fe, isRepeatableField as pe, isRichTextField as me, isSearchableDropdown as he, joinIdReferences as ge, loadAddressSubdivisions as _e, loadClientFormBootstrap as ve, loadGraphqlClientFormBootstrap as ye, moveListItem as be, paymentFieldMarkup as xe, phoneFieldValue as Se, readAddressParts as Ce, readCustomFieldControls as we, readPhoneFieldControls as Te, readUploadManagerEntries as Ee, repeaterRowDefinitions as De, resolveClientRendererType as Oe, searchableDropdownMarkup as ke, searchableDropdownValue as Ae, showsClientPageTitle as je, summaryFieldMarkup as Me, surveyLikertRows as Ne, surveyRankOrder as Pe, tableColumns as Fe, textLimitCounter as Ie, toggleAllOptions as Le, uploadManagerMarkup as Re } from "@verbb/formie-core";
//#region \0rolldown/runtime.js
var G = (e, t) => () => (t || (e((t = { exports: {} }).exports, t, t.exports), e = null), t.exports), ze = /* @__PURE__ */ ((e) => typeof require < "u" ? require : typeof Proxy < "u" ? new Proxy(e, { get: (e, t) => (typeof require < "u" ? require : e)[t] }) : e)(function(e) {
	if (typeof require < "u") return require.apply(this, arguments);
	throw Error("Calling `require` for \"" + e + "\" in an environment that doesn't expose the `require` function. See https://rolldown.rs/in-depth/bundling-cjs#require-external-modules for more details.");
});
//#endregion
//#region src/stable.ts
function K(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => K(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${K(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function q(e) {
	return K(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region ../../node_modules/react/cjs/react-jsx-runtime.production.js
var Be = /* @__PURE__ */ G(((e) => {
	var t = Symbol.for("react.transitional.element"), n = Symbol.for("react.fragment");
	function r(e, n, r) {
		var i = null;
		if (r !== void 0 && (i = "" + r), n.key !== void 0 && (i = "" + n.key), "key" in n) for (var a in r = {}, n) a !== "key" && (r[a] = n[a]);
		else r = n;
		return n = r.ref, {
			$$typeof: t,
			type: e,
			key: i,
			ref: n === void 0 ? null : n,
			props: r
		};
	}
	e.Fragment = n, e.jsx = r, e.jsxs = r;
})), Ve = /* @__PURE__ */ G(((e) => {
	process.env.NODE_ENV !== "production" && (function() {
		function t(e) {
			if (e == null) return null;
			if (typeof e == "function") return e.$$typeof === k ? null : e.displayName || e.name || null;
			if (typeof e == "string") return e;
			switch (e) {
				case _: return "Fragment";
				case y: return "Profiler";
				case v: return "StrictMode";
				case C: return "Suspense";
				case w: return "SuspenseList";
				case D: return "Activity";
				case O: return "ViewTransition";
			}
			if (typeof e == "object") switch (typeof e.tag == "number" && console.error("Received an unexpected object in getComponentNameFromType(). This is likely a bug in React. Please file an issue."), e.$$typeof) {
				case g: return "Portal";
				case x: return e.displayName || "Context";
				case b: return (e._context.displayName || "Context") + ".Consumer";
				case S:
					var n = e.render;
					return e = e.displayName, e ||= (e = n.displayName || n.name || "", e === "" ? "ForwardRef" : "ForwardRef(" + e + ")"), e;
				case T: return n = e.displayName || null, n === null ? t(e.type) || "Memo" : n;
				case E:
					n = e._payload, e = e._init;
					try {
						return t(e(n));
					} catch {}
			}
			return null;
		}
		function n(e) {
			return "" + e;
		}
		function r(e) {
			try {
				n(e);
				var t = !1;
			} catch {
				t = !0;
			}
			if (t) {
				t = console;
				var r = t.error, i = typeof Symbol == "function" && Symbol.toStringTag && e[Symbol.toStringTag] || e.constructor.name || "Object";
				return r.call(t, "The provided key is an unsupported type %s. This value must be coerced to a string before using it here.", i), n(e);
			}
		}
		function i(e) {
			if (e === _) return "<>";
			if (typeof e == "object" && e && e.$$typeof === E) return "<...>";
			try {
				var n = t(e);
				return n ? "<" + n + ">" : "<...>";
			} catch {
				return "<...>";
			}
		}
		function a() {
			var e = A.A;
			return e === null ? null : e.getOwner();
		}
		function o() {
			return Error("react-stack-top-frame");
		}
		function s(e) {
			if (j.call(e, "key")) {
				var t = Object.getOwnPropertyDescriptor(e, "key").get;
				if (t && t.isReactWarning) return !1;
			}
			return e.key !== void 0;
		}
		function c(e, t) {
			function n() {
				P || (P = !0, console.error("%s: `key` is not a prop. Trying to access it will result in `undefined` being returned. If you need to access the same value within the child component, you should pass it as a different prop. (https://react.dev/link/special-props)", t));
			}
			n.isReactWarning = !0, Object.defineProperty(e, "key", {
				get: n,
				configurable: !0
			});
		}
		function l() {
			var e = t(this.type);
			return F[e] || (F[e] = !0, console.error("Accessing element.ref was removed in React 19. ref is now a regular prop. It will be removed from the JSX Element type in a future release.")), e = this.props.ref, e === void 0 ? null : e;
		}
		function u(e, t, n, r, i, a) {
			var o = n.ref;
			return e = {
				$$typeof: h,
				type: e,
				key: t,
				props: n,
				_owner: r
			}, (o === void 0 ? null : o) === null ? Object.defineProperty(e, "ref", {
				enumerable: !1,
				value: null
			}) : Object.defineProperty(e, "ref", {
				enumerable: !1,
				get: l
			}), e._store = {}, Object.defineProperty(e._store, "validated", {
				configurable: !1,
				enumerable: !1,
				writable: !0,
				value: 0
			}), Object.defineProperty(e, "_debugInfo", {
				configurable: !1,
				enumerable: !1,
				writable: !0,
				value: null
			}), Object.defineProperty(e, "_debugStack", {
				configurable: !1,
				enumerable: !1,
				writable: !0,
				value: i
			}), Object.defineProperty(e, "_debugTask", {
				configurable: !1,
				enumerable: !1,
				writable: !0,
				value: a
			}), Object.freeze && (Object.freeze(e.props), Object.freeze(e)), e;
		}
		function d(e, n, i, o, l, d) {
			var p = n.children;
			if (p !== void 0) {
				if (o) {
					if (M(p)) {
						for (o = 0; o < p.length; o++) f(p[o]);
						Object.freeze && Object.freeze(p);
					} else console.error("React.jsx: Static children should always be an array. You are likely explicitly calling React.jsxs or React.jsxDEV. Use the Babel transform instead.");
				} else f(p);
			}
			if (j.call(n, "key")) {
				p = t(e);
				var m = Object.keys(n).filter(function(e) {
					return e !== "key";
				});
				o = 0 < m.length ? "{key: someKey, " + m.join(": ..., ") + ": ...}" : "{key: someKey}", R[p + o] || (m = 0 < m.length ? "{" + m.join(": ..., ") + ": ...}" : "{}", console.error("A props object containing a \"key\" prop is being spread into JSX:\n  let props = %s;\n  <%s {...props} />\nReact keys must be passed directly to JSX without using spread:\n  let props = %s;\n  <%s key={someKey} {...props} />", o, p, m, p), R[p + o] = !0);
			}
			if (p = null, i !== void 0 && (r(i), p = "" + i), s(n) && (r(n.key), p = "" + n.key), "key" in n) for (var h in i = {}, n) h !== "key" && (i[h] = n[h]);
			else i = n;
			return p && c(i, typeof e == "function" ? e.displayName || e.name || "Unknown" : e), u(e, p, i, a(), l, d);
		}
		function f(e) {
			p(e) ? e._store && (e._store.validated = 1) : typeof e == "object" && e && e.$$typeof === E && (e._payload.status === "fulfilled" ? p(e._payload.value) && e._payload.value._store && (e._payload.value._store.validated = 1) : e._store && (e._store.validated = 1));
		}
		function p(e) {
			return typeof e == "object" && !!e && e.$$typeof === h;
		}
		var m = ze("react"), h = Symbol.for("react.transitional.element"), g = Symbol.for("react.portal"), _ = Symbol.for("react.fragment"), v = Symbol.for("react.strict_mode"), y = Symbol.for("react.profiler"), b = Symbol.for("react.consumer"), x = Symbol.for("react.context"), S = Symbol.for("react.forward_ref"), C = Symbol.for("react.suspense"), w = Symbol.for("react.suspense_list"), T = Symbol.for("react.memo"), E = Symbol.for("react.lazy"), D = Symbol.for("react.activity"), O = Symbol.for("react.view_transition"), k = Symbol.for("react.client.reference"), A = m.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE, j = Object.prototype.hasOwnProperty, M = Array.isArray, N = console.createTask ? console.createTask : function() {
			return null;
		};
		m = { react_stack_bottom_frame: function(e) {
			return e();
		} };
		var P, F = {}, I = m.react_stack_bottom_frame.bind(m, o)(), L = N(i(o)), R = {};
		e.Fragment = _, e.jsx = function(e, t, n) {
			var r = 1e4 > A.recentlyCreatedOwnerStacks++;
			if (r) {
				var a = Error.stackTraceLimit;
				Error.stackTraceLimit = 10;
				var o = Error("react-stack-top-frame");
				Error.stackTraceLimit = a;
			} else o = I;
			return d(e, t, n, !1, o, r ? N(i(e)) : L);
		}, e.jsxs = function(e, t, n) {
			var r = 1e4 > A.recentlyCreatedOwnerStacks++;
			if (r) {
				var a = Error.stackTraceLimit;
				Error.stackTraceLimit = 10;
				var o = Error("react-stack-top-frame");
				Error.stackTraceLimit = a;
			} else o = I;
			return d(e, t, n, !0, o, r ? N(i(e)) : L);
		};
	})();
})), He = (/* @__PURE__ */ G(((e, t) => {
	t.exports = process.env.NODE_ENV === "production" ? Be() : Ve();
})))(), Ue = a(null);
function We(e) {
	return "definition" in e;
}
async function Ge(e) {
	return We(e) ? e.definition : e.transport === "graphql" ? ye({
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
function Ke(e) {
	let t = We(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? T(t) : D(t);
}
function qe({ errors: e }) {
	return e.length === 0 ? null : o("div", {
		className: "formie-react-errors",
		role: "alert",
		tabIndex: -1,
		"data-formie-form-message": !0
	}, o("ul", null, e.map((e, t) => o("li", { key: `${e}:${t}` }, e))));
}
function Je({ field: e, errors: t, errorId: n, errorAriaLive: r, inputId: i, labelId: a, instructionsId: s, children: c }) {
	let { slots: l } = J(), u = (t, n, r) => {
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
		htmlFor: i && V(e, Y(e)) === "control" ? i : void 0
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
function Ye({ children: e }) {
	let { instance: t } = J(), r = d(null);
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
function Xe({ definition: e, session: t, state: n, children: r, className: i, onSubmit: a }) {
	let { instance: s } = J(), c = d(null), u = d(n.currentPageId), f = d(null);
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
function Ze({ page: e, state: t, children: n }) {
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
function Qe(e) {
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
function J() {
	let e = c(Ue);
	if (!e) throw Error("Formie definition hooks must be used within a client-rendered <FormieClientForm />.");
	return e;
}
function Y(e) {
	return Oe(e);
}
function X(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function $e(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function et(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function Z(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": ge(et(e, t), n && t.errorId),
		autoComplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0,
		..._(e) ? { [_(e)]: "true" } : {}
	};
}
function tt(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function Q(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": ge(et(e, t))
	};
}
function $(e, t, n, r, i, a = Y(e)) {
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
		...Z(e, i),
		"data-formie-multi-line-text-input": "true",
		style: { display: "none" },
		value: typeof t == "string" ? t : "",
		readOnly: !0,
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0
	}));
	if (a === "multi-line-text") return o("textarea", {
		...Z(e, i),
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
			...Z(e, i),
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
		...Z(e, i),
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
function nt(e, t, n, r) {
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
function rt({ field: e, value: t, labelling: n, disabled: r, setValue: a }) {
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
	let h = $(e, p.number, r, (e) => {
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
function it({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: a, errors: s, errorId: c }) {
	let l = L(e), u = Ne(e), d = u.length > 0, f = d && t && typeof t == "object" ? t : {}, p = a || `${e.id}-likert`;
	return o("div", {
		className: "formie-react-likert",
		...Q(e, i, "group")
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
			...X(s, c),
			onChange: () => r(d ? {
				...f,
				[e.value]: t.value
			} : t.value)
		}))));
	}))));
}
function at({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let { state: a } = J(), s = d(null), c = d({
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
		...Q(e, i, "group")
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
var ot = {
	position: "absolute",
	inset: 0,
	width: "100%",
	height: "100%",
	margin: 0,
	opacity: 0,
	cursor: "pointer"
};
function st({ field: e, value: t, disabled: n, setValue: r, labelling: i, inputId: a, errors: s, errorId: c }) {
	let l = L(e), u = t == null ? "" : String(t), d = l.findIndex((e) => e.value === u), f = typeof e.input.starColor == "string" && e.input.starColor ? e.input.starColor : void 0;
	return o("div", {
		className: "formie-react-rating",
		style: f ? { color: f } : void 0,
		...Q(e, i, "radiogroup")
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
		style: ot,
		...X(s, c),
		onChange: () => r(t.value),
		onClick: () => {
			u === t.value && r(null);
		}
	}), o("span", { "aria-hidden": "true" }, i <= d ? "★" : "☆"), o("span", { style: h }, t.label))));
}
function ct({ field: e }) {
	let { state: t } = J();
	return o("div", {
		className: "formie-react-summary",
		dangerouslySetInnerHTML: { __html: Me(e, t.lastSubmitResult) ?? "" }
	});
}
function lt(e) {
	let t = (e) => e.replace(/[&"<>]/g, (e) => `&#${e.charCodeAt(0)};`);
	return Object.entries(e).filter(([, e]) => e !== void 0 && e !== !1).map(([e, n]) => {
		let r = e === "autoComplete" ? "autocomplete" : e;
		return n === !0 ? r : `${r}="${t(String(n))}"`;
	}).join(" ");
}
function ut({ field: e, value: t, disabled: n, setValue: r, labelling: i }) {
	let [a] = f(() => `<input ${lt({
		type: "text",
		...Z(e, i),
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
function dt({ markup: e, errorKey: t, setValue: n }) {
	let { state: r } = J();
	return o("div", {
		className: "formie-react-upload-manager",
		"data-formie-upload-field-handle": I(r.definition, t),
		onInput: (e) => n(Ee(e.currentTarget))
	}, o("div", { dangerouslySetInnerHTML: { __html: e } }));
}
function ft({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: a } = J(), s = Fe(e), c = Array.isArray(t) ? t : [], l = e.input, u = l.static === !0 || l.staticRows === !0, d = Number(l.minRows ?? 0) || 0, f = Number(l.maxRows ?? 0) || 0, p = !r && !u && (f <= 0 || c.length < f), m = (e, t, n) => {
		i(c.map((r, i) => i === e ? {
			...r,
			[t]: n
		} : r));
	};
	return o("div", {
		className: "formie-react-table",
		...Q(e, n, "group")
	}, [o("table", { key: "table" }, [o("thead", { key: "head" }, o("tr", null, [...s.map((e) => o("th", {
		key: e.id,
		scope: "col"
	}, e.heading)), u ? null : o("td", { key: "actions" })])), o("tbody", { key: "body" }, c.map((e, t) => o("tr", { key: t }, [...s.map((n) => o("td", { key: n.id }, nt(n, e[n.handle], r, (e) => m(t, n.handle, e)))), u ? null : o("td", { key: "actions" }, o("button", {
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
function pt(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function mt({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let { state: a } = J(), s = d(null), c = d(null), [u, p] = f(null), m = pt(e, a.definition, "formie:signature")?.config, h = typeof m?.backgroundColor == "string" ? String(m.backgroundColor) : "#ffffff", g = typeof m?.penColor == "string" ? String(m.penColor) : "#000000", _ = Number(m?.penWeight ?? 2) || 2, v = typeof t == "string" ? t : "";
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
			...tt(e, n),
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
function ht({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: a }) {
	let { state: s, instance: c } = J(), [, u] = f(0), d = C(e), p = t && typeof t == "object" ? t : {}, m = d.map((e) => v(e, p.country, p[e.handle])), h = m.find((e) => e.loading), g = m.find((e) => se(e.field) && !e.loading && (p[e.field.handle] ?? "") !== e.value);
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
		...Q(e, r, "group"),
		...te(e) ? {
			onInput: _,
			onChange: _
		} : {}
	}, m.filter((e) => e.field.meta?.hidden !== !0 && !e.hidden).map(({ field: t }) => {
		let r = `${n}.${t.handle}`;
		return o(yt, {
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
function gt({ field: e, value: t, labelling: n, disabled: r, setValue: i }) {
	let a = e.input, s = Array.isArray(t) ? t : [], c = a.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return o("div", { className: "formie-react-file" }, [o("input", {
		key: "input",
		type: "file",
		...Z(e, n),
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
function _t({ field: e, value: t, errorKey: n, labelling: r, disabled: i, setValue: a }) {
	let { state: s } = J(), c = De(e), l = Array.isArray(t) ? t : [], u = e.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !i && (f <= 0 || l.length < f);
	return c.length === 0 ? o("div", { className: "formie-react-unsupported" }, "Unsupported repeater field.") : o("div", {
		className: "formie-react-repeater",
		"data-formie-repeater-container": !0,
		...Q(e, r, "group")
	}, [...l.map((t, r) => {
		let u = `${e.id}:${r}`;
		return o("div", {
			key: u,
			className: "formie-react-repeater-item",
			"data-formie-repeater-item": !0
		}, [...c.map((e, s) => o(St, {
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
function vt(e, t = !0) {
	let { field: n, value: r, errorKey: i, errorId: a, disabled: s, setValue: c } = e, l = n.input, u = Y(n), d = $e(e, t);
	if (oe(n)) return o("div", {
		className: "formie-react-date-range-picker",
		onInput: (e) => c(N(e.currentTarget))
	}, o("div", { dangerouslySetInnerHTML: { __html: M(n, {
		id: d.inputId || void 0,
		"aria-label": d.labelledByWrapper ? void 0 : n.label || n.handle,
		"aria-required": n.required ? "true" : void 0,
		placeholder: typeof l.placeholder == "string" ? l.placeholder : void 0
	}) } }));
	if (ae(n)) return o(ut, {
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
	if (ie(n)) return o(ht, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (pe(n)) return o(_t, {
		field: n,
		value: r,
		errorKey: i,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "phone") return o(rt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "survey-likert") return o(it, {
		...e,
		labelling: d
	});
	if (u === "survey-rank") return o(at, {
		...e,
		labelling: d
	});
	if (u === "survey-rating") return o(st, {
		...e,
		labelling: d
	});
	if (u === "table") return o(ft, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "section") return o("hr", { className: "formie-react-section" });
	let f = Re(n);
	if (f !== null) return o(dt, {
		markup: f,
		errorKey: i,
		setValue: c
	});
	if (ue(n)) return o(gt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "signature") return o(mt, {
		field: n,
		value: r,
		labelling: d,
		disabled: s,
		setValue: c
	});
	if (u === "multi-line-text" || u === "dropdown") return $(n, r, s, c, d, u);
	if (u === "radio") {
		let t = L(n), i = ne(n), l = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
		return o("div", {
			className: "formie-react-choices",
			...Q(n, d, "radiogroup")
		}, t.map((t) => {
			let r = t.value, u = s || t.disabled;
			return o("label", { key: `${n.id}:${r}` }, [o("input", {
				key: "input",
				type: "radio",
				name: e.inputId || `${n.id}-radio`,
				value: r,
				...X(e.errors, a),
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
			...Q(n, d, "group")
		}, l?.position === "top" ? u : null, ...t.map((t) => {
			let r = t.value, l = i.includes(r), u = s || t.disabled;
			return o("label", { key: `${n.id}:${r}` }, [o("input", {
				key: "input",
				type: "checkbox",
				...X(e.errors, a),
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
			...Q(n, d, "group")
		}, o("label", { className: "formie-react-boolean" }, [o("input", {
			key: "input",
			type: "checkbox",
			id: e.inputId || void 0,
			"aria-required": n.required ? "true" : void 0,
			...X(e.errors, a),
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
	if (n.type === "summary") return o(ct, { field: n });
	let m = k(n, d.labelledByWrapper && !ce(n) ? d.labelId : null);
	if (m !== null) return o("div", {
		className: "formie-react-custom-field",
		onInput: (e) => c(we(e.currentTarget, n)),
		dangerouslySetInnerHTML: { __html: m }
	});
	let h = xe(n);
	return h === null ? de(u) ? $(n, r, s, c, d, u) : o("div", { className: "formie-react-unsupported" }, `Unsupported field type: ${String(n.meta?.fieldType ?? n.type)}`) : o("div", {
		className: "formie-react-payment",
		dangerouslySetInnerHTML: { __html: h }
	});
}
function yt({ field: e, value: t, errors: n, errorKey: i, disabled: a, setValue: s }) {
	let { components: c, fieldComponents: l, state: u } = J(), d = u.fieldStates[i]?.hidden === !0;
	if (d) return null;
	let f = Y(e), p = l[e.type] || l[f] || vt, m = c.Field || Je, h = B(u.session, i), g = h.errors, _ = z(u.definition), v = {
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
		children: o(r, null, p === vt ? vt(v, !c.Field) : o(p, v), bt(Ie(e, t, u.definition)))
	});
}
function bt(e) {
	return e ? /* @__PURE__ */ (0, He.jsxs)("div", {
		className: "formie-react-limit",
		"data-formie-limit-text": "true",
		children: [/* @__PURE__ */ (0, He.jsx)("span", {
			className: e.over ? "formie-react-limit-number formie-react-limit-number-error" : "formie-react-limit-number",
			children: e.count
		}), ` ${e.text}`]
	}) : null;
}
function xt({ field: e }) {
	let { state: t, instance: n } = J(), r = t.fieldStates[e.id];
	return o(yt, {
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
function St({ row: e, rowIndex: t, values: n, errorPrefix: r, disabled: i, setFieldValue: a }) {
	let { state: s } = J();
	return o("div", { className: "formie-react-row" }, e.fields.map((e, c) => {
		if (!n || !a) return o(xt, {
			key: e.id || `${t}:${c}`,
			field: e
		});
		let l = `${r}.${e.handle}`;
		return o(yt, {
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
function Ct() {
	let { state: e, instance: t } = J(), n = e.definition.pages.find((t) => t.id === e.currentPageId);
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
function wt({ className: e }) {
	let { instance: t, state: n, components: r } = J(), i = r.Form || Xe, a = r.Page || Ze, s = r.ErrorSummary || qe, c = n.definition.pages.find((e) => e.id === n.currentPageId && n.pageStates[e.id]?.hidden !== !0) || n.definition.pages.find((e) => n.pageStates[e.id]?.hidden !== !0) || n.definition.pages[0], l = n.lastSubmitResult?.messages.error, u = !!l && !n.errors.form.includes(l), d = !(n.lastSubmitResult?.completion?.behavior === "message" && n.lastSubmitResult.completion.hideForm), f = d ? U(n.definition, n.currentPageId)?.position : void 0;
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
			f === "start" ? Qe(n) : null,
			d ? o(a, {
				key: c.id,
				page: c,
				state: n,
				children: [...c.rows.map((e, t) => o(St, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), o(Ct, { key: "actions" })]
			}) : null,
			f === "end" ? Qe(n) : null
		]
	}) : null;
}
function Tt(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Et({ source: e, components: t = {}, fieldComponents: n = {}, slots: r = {}, className: i, onMount: a, onReady: s, onUnmount: c, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	let [x, S] = f(null), [C, T] = f(null), [E, D] = f(null), O = d(a), k = d(s), A = d(c), j = d(m), M = d(h), N = d(g), P = d(_), F = d(v), I = d(y), L = d(b), R = u(() => q(e), [e]), z = d(e);
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
				let n = await Ge(z.current), r = Ke(z.current), i = w({
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
						Tt(P.current, j.current, t), t.success ? Tt(F.current, M.current, t) : Tt(I.current, N.current, t);
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
	return E ? o("div", { className: "formie-react-error" }, E.message) : B ? o(Ue.Provider, {
		value: B,
		children: o(Ye, { children: o(wt, { className: i }) })
	}) : o("div", { className: "formie-react-loading" }, "Loading form...");
}
function Dt() {
	let e = J();
	return {
		definition: e.state.definition,
		session: e.state.session,
		state: e.state,
		instance: e.instance
	};
}
function Ot(e) {
	let t = J(), n = t.state.definition.pages.flatMap((e) => e.rows).flatMap((e) => e.fields).find((t) => t.id === e);
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
function kt(e) {
	let t = J();
	return {
		page: t.state.definition.pages.find((t) => t.id === e) || null,
		isCurrent: t.state.currentPageId === e,
		hidden: t.state.pageStates[e]?.hidden === !0
	};
}
function At() {
	return J().instance;
}
function jt(e) {
	return J().slots[e] || null;
}
//#endregion
//#region src/index.ts
function Mt(e) {
	return !!e && "payload" in e;
}
function Nt(e) {
	return "success" in e ? e.success : e.ok;
}
function Pt(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Ft(e) {
	let t = e.transport;
	if (!t && !Mt(e.source)) throw Error("`transport` is required for <FormieForm />.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		payload: Mt(e.source) ? e.source.payload : void 0,
		staticCache: e.staticCache,
		refreshTokens: e.refreshTokens,
		locale: e.locale,
		siteId: e.siteId,
		autoVisible: e.autoVisible,
		theme: e.theme,
		themeConfig: e.themeConfig
	};
}
function It(e) {
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
function Lt({ source: n, transport: r, profile: i, endpoint: a, formHandle: s, staticCache: c, refreshTokens: f, locale: p, siteId: m, autoVisible: h, theme: g, themeConfig: _, className: v, onMount: y, onReady: b, onUnmount: x, onResult: S, onSuccess: C, onError: w, onSubmitResult: T, onSubmitSuccess: E, onSubmitError: D, onEvent: O }) {
	let k = d(null), A = d(null), j = d(y), M = d(b), N = d(x), P = d(S), F = d(C), I = d(w), L = d(T), R = d(E), z = d(D), B = d(O), V = u(() => Ft({
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
	]), H = u(() => q(V), [V]), U = d(V);
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
				Pt(L.current, P.current, t), Nt(t) ? Pt(R.current, F.current, t) : Pt(z.current, I.current, t);
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
function Rt({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, staticCache: a, refreshTokens: s, locale: c, siteId: l, autoVisible: u, theme: d, themeConfig: f, className: p, onMount: m, onReady: h, onUnmount: g, onResult: _, onSuccess: v, onError: y, onSubmitResult: b, onSubmitSuccess: x, onSubmitError: S, onEvent: C }) {
	return o(Lt, {
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
function zt({ source: e, transport: t, profile: n, endpoint: r, formHandle: i, siteId: a, components: s, fieldComponents: c, slots: l, className: u, onMount: d, onReady: f, onUnmount: p, onResult: m, onSuccess: h, onError: g, onSubmitResult: _, onSubmitSuccess: v, onSubmitError: y, onEvent: b }) {
	return o(Et, {
		source: It({
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
function Bt() {
	return u(() => t(), []);
}
function Vt(e) {
	let t = d(null), n = Bt(), r = u(() => q(e), [e]), i = d(e), [a, o] = f(null), [s, c] = f(null);
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
export { zt as FormieClientForm, Rt as FormieForm, Dt as useFormie, Bt as useFormieClient, Ot as useFormieField, Vt as useFormieHtml, At as useFormieInstance, kt as useFormiePage, jt as useFormieSlot };
