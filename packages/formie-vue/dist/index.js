import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { computed as r, defineComponent as i, h as a, inject as o, onBeforeUnmount as s, onMounted as c, provide as l, ref as u, shallowRef as d, watch as f } from "vue";
import { CLIENT_FORM_EVENT_NAMES as p, VISUALLY_HIDDEN_STYLE as m, clientActionAllowed as h, compositePartDefinitions as g, createClientFormInstance as _, createGraphqlClientTransport as v, createRepeaterRowValue as y, createRestClientTransport as b, createTableRowValue as ee, displayFieldContent as te, dropdownOptions as x, fieldOptions as S, focusClientFormResult as C, getClientErrorAriaLive as ne, getClientFieldIds as re, getClientFieldLabelTarget as ie, getClientPageProgress as w, getClientString as T, isArrayValuedField as E, isCalculationField as ae, isCompositeField as oe, isFieldLabelHidden as se, isFieldLabelVisuallyHidden as ce, isFileField as le, isKnownClientFieldType as ue, isRepeatableField as de, joinIdReferences as D, loadClientFormBootstrap as fe, loadGraphqlClientFormBootstrap as pe, repeaterRowDefinitions as me, resolveClientRendererType as he, showsClientPageTitle as ge, tableColumns as _e } from "@verbb/formie-core";
//#region src/stable.ts
function O(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => O(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${O(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function k(e) {
	return O(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.ts
var A = Symbol("formie-definition-context"), j = {
	field: {
		type: Object,
		required: !0
	},
	value: {
		type: null,
		default: void 0
	},
	errors: {
		type: Array,
		default: () => []
	},
	errorKey: {
		type: String,
		required: !0
	},
	errorId: {
		type: String,
		default: ""
	},
	errorAriaLive: {
		type: String,
		default: "polite"
	},
	inputId: {
		type: String,
		default: ""
	},
	labelId: {
		type: String,
		default: ""
	},
	instructionsId: {
		type: String,
		default: ""
	},
	disabled: {
		type: Boolean,
		default: !1
	},
	hidden: {
		type: Boolean,
		default: !1
	},
	setValue: {
		type: Function,
		required: !0
	}
};
function M() {
	let e = o(A);
	if (!e) throw Error("Formie definition composables must be used within a client-rendered <FormieForm>.");
	return e;
}
function N(e) {
	return "definition" in e;
}
async function P(e) {
	return N(e) ? e.definition : e.transport === "graphql" ? pe({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : fe({
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
	return t.type === "graphql" ? v(t) : b(t);
}
function I(e) {
	return e.pages.flatMap((e) => e.rows).flatMap((e) => e.fields);
}
function L(e) {
	return he(e);
}
function ve(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function R(e) {
	return !e.components.value.Field;
}
function z(e, t, n, r, i) {
	if (!n) return null;
	let o = e.slots.value[t];
	return o ? a(o, {
		slotKey: t,
		attributes: r,
		field: i
	}, { default: () => [n] }) : n;
}
var ye = i({
	name: "FormieVueDefaultErrorSummary",
	props: { errors: {
		type: Array,
		required: !0
	} },
	setup(e) {
		return () => e.errors.length === 0 ? null : a("div", {
			class: "formie-vue-errors",
			role: "alert",
			tabindex: -1,
			"data-formie-form-message": !0
		}, [a("ul", null, e.errors.map((e, t) => a("li", { key: `${e}:${t}` }, e)))]);
	}
}), be = i({
	name: "FormieVueDefaultField",
	props: {
		field: {
			type: Object,
			required: !0
		},
		errors: {
			type: Array,
			required: !0
		},
		errorId: {
			type: String,
			required: !0
		},
		errorAriaLive: {
			type: String,
			required: !0
		},
		inputId: {
			type: String,
			default: ""
		},
		labelId: {
			type: String,
			default: ""
		},
		instructionsId: {
			type: String,
			default: ""
		}
	},
	setup(e, t) {
		let n = M();
		return () => {
			let r = t.slots.default?.() || [], i = {
				class: "formie-vue-label",
				style: ce(e.field) ? m : void 0,
				id: e.labelId || void 0,
				for: e.inputId && ie(e.field, L(e.field)) === "control" ? e.inputId : void 0
			}, o = {
				class: "formie-vue-description",
				id: e.instructionsId || void 0
			};
			return a("div", {
				class: "formie-vue-field",
				"data-formie-field-uid": e.field.uid,
				"data-formie-field-handle": e.field.handle,
				"data-formie-field-type": e.field.type,
				"data-formie-field-required": e.field.required ? "true" : void 0
			}, [
				e.field.label && !se(e.field) ? z(n, "label", a("label", i, e.field.label), i, e.field) : null,
				e.field.instructions ? z(n, "instructions", a("div", {
					...o,
					innerHTML: e.field.instructions
				}), o, e.field) : null,
				z(n, "input", a("div", { class: "formie-vue-input" }, r), { class: "formie-vue-input" }, e.field),
				z(n, "errors", a("ul", {
					id: e.errorId,
					class: "formie-vue-field-errors",
					style: e.errors.length === 0 ? { position: "absolute" } : void 0,
					"data-formie-field-errors": !0,
					"aria-live": e.errorAriaLive === "off" ? void 0 : e.errorAriaLive,
					"aria-atomic": e.errorAriaLive === "off" ? void 0 : "true"
				}, e.errors.map((e, t) => a("li", { key: `${e}:${t}` }, e))), {
					id: e.errorId,
					class: "formie-vue-field-errors",
					style: e.errors.length === 0 ? { position: "absolute" } : void 0,
					"data-formie-field-errors": !0,
					"aria-live": e.errorAriaLive === "off" ? void 0 : e.errorAriaLive,
					"aria-atomic": e.errorAriaLive === "off" ? void 0 : "true"
				}, e.field)
			]);
		};
	}
}), xe = i({ setup(e, { slots: t }) {
	let r = M(), i = u(null), o = !1, l;
	return c(async () => {
		let e = r.instance.value;
		e.setBrowserModuleGuard(() => {
			throw Error("Form features are still loading.");
		});
		let t = await n(i.value, e);
		o ? t.destroy() : l = t;
	}), s(() => {
		o = !0, l?.destroy();
	}), () => a("div", { ref: i }, t.default?.());
} }), Se = i({
	name: "FormieVueDefaultForm",
	props: {
		definition: {
			type: Object,
			required: !0
		},
		session: {
			type: Object,
			required: !0
		},
		state: {
			type: Object,
			required: !0
		},
		className: {
			type: String,
			default: void 0
		},
		onSubmit: {
			type: Function,
			required: !0
		}
	},
	setup(e, t) {
		return () => a("form", {
			class: e.className,
			onSubmit: async (t) => {
				t.preventDefault();
				let n = t.currentTarget, r = e.state.currentPageId;
				await e.onSubmit(), requestAnimationFrame(() => C(n, r));
			},
			"data-formie-definition": e.definition.handle,
			"data-formie-render-id": e.session.tokens.render
		}, t.slots.default?.() || []);
	}
}), Ce = i({
	name: "FormieVueDefaultPage",
	props: {
		page: {
			type: Object,
			required: !0
		},
		state: {
			type: Object,
			required: !0
		}
	},
	setup(e, t) {
		return () => {
			let n = ge(e.state.definition) && !!e.page.label, r = `formie-${e.state.session.tokens.render || e.state.session.id}-page-${e.page.id}-title`;
			return a("section", {
				"data-page-id": e.page.id,
				class: "formie-vue-page",
				tabindex: -1,
				"aria-labelledby": n ? r : void 0,
				"aria-label": !n && e.state.definition.pages.length > 1 && e.page.label ? e.page.label : void 0
			}, [n ? a("h3", {
				id: r,
				class: "formie-vue-page-title"
			}, e.page.label) : null, ...t.slots.default?.() || []]);
		};
	}
});
function B(e) {
	let t = w(e.definition, e.currentPageId);
	if (!t) return null;
	let n = a("span", {
		class: "formie-vue-progress-value",
		"aria-hidden": "true"
	}, `${t.percent}%`), r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition);
	return a("div", {
		key: "progress",
		class: "formie-vue-progress",
		"data-formie-progress-position": t.position,
		"data-formie-progress-value-position": t.valuePosition,
		"data-formie-progress-state": t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle"
	}, [
		t.valuePosition === "left" ? n : null,
		a("div", {
			class: "formie-vue-progress-track",
			role: "progressbar",
			"aria-label": T(e.definition, "progress"),
			"aria-valuenow": t.percent,
			"aria-valuemin": 0,
			"aria-valuemax": 100
		}, a("div", {
			class: "formie-vue-progress-bar",
			style: { width: `${t.percent}%` }
		}, r ? n : null)),
		t.valuePosition === "right" ? n : null
	]);
}
var we = i({
	name: "FormieVueSignatureFieldInput",
	props: j,
	setup(e) {
		let t = M(), n = u(null), i = d(null), o = u(null), l = r(() => ve(e.field, t.state.value?.definition || { modules: {
			contractVersion: 2,
			surface: "client-rendered",
			entries: []
		} }, "formie:signature")?.config), p = r(() => typeof l.value?.backgroundColor == "string" ? l.value.backgroundColor : "#ffffff"), m = r(() => typeof l.value?.penColor == "string" ? l.value.penColor : "#000000"), h = r(() => Number(l.value?.penWeight ?? 2) || 2), g = r(() => typeof e.value == "string" ? e.value : ""), _ = !1, v = () => void 0, y = () => void 0;
		return c(() => {
			(async () => {
				try {
					let t = n.value;
					if (!t) return;
					let { default: r } = await import("./signature_pad-Cqf-RWiu.js");
					if (_) return;
					let a = new r(t, {
						backgroundColor: p.value,
						penColor: m.value,
						minWidth: h.value,
						maxWidth: h.value
					}), s = () => {
						let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), n = Math.max(1, Math.floor(t.clientWidth || 480)), r = t.getContext("2d");
						t.width = n * e, t.height = 192 * e, t.style.height = "192px", r && (r.setTransform(1, 0, 0, 1, 0, 0), r.scale(e, e)), a.clear();
					}, c = () => {
						e.setValue(a.isEmpty() ? "" : a.toDataURL());
					};
					s(), a.addEventListener?.("endStroke", c), v = () => {
						a.removeEventListener?.("endStroke", c);
					}, typeof window < "u" && (window.addEventListener("resize", s), y = () => {
						window.removeEventListener("resize", s);
					}), i.value = a, o.value = null;
				} catch (e) {
					_ || (o.value = e.message || "Unable to load signature support.");
				}
			})();
		}), s(() => {
			_ = !0, v(), y(), i.value = null;
		}), f(g, (e) => {
			let t = i.value;
			if (t) {
				if (!e) t.isEmpty() || t.clear();
				else try {
					t.fromDataURL(e);
				} catch {}
			}
		}, { immediate: !0 }), () => a("div", { class: "formie-vue-signature" }, [
			a("canvas", {
				key: "canvas",
				ref: n,
				"data-formie-signature-canvas": !0,
				...ke(e.field, W(e, R(t))),
				style: e.disabled ? { pointerEvents: "none" } : void 0
			}),
			a("button", {
				key: "clear",
				type: "button",
				disabled: e.disabled,
				"aria-label": T(t.state.value?.definition, "clearSignature"),
				"data-formie-signature-clear": !0,
				onClick: () => {
					i.value?.clear(), e.setValue("");
				}
			}, T(t.state.value?.definition, "clear")),
			o.value ? a("div", {
				key: "error",
				class: "formie-vue-unsupported"
			}, o.value) : null
		]);
	}
}), Te = i({
	name: "FormieVueCompositeFieldInput",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = g(e.field), i = e.value && typeof e.value == "object" ? e.value : {};
			return r.length === 0 ? a("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${e.field.type}`) : a("div", {
				class: "formie-vue-name-grid",
				...q(e.field, W(e, R(t)), "group")
			}, r.filter((e) => e.meta?.hidden !== !0).map((t) => {
				let r = `${e.errorKey}.${t.handle}`;
				return a(V, {
					key: `${e.field.id}:${t.handle}`,
					field: t,
					value: i[t.handle],
					errors: n.errors.fields[r] || [],
					errorKey: r,
					disabled: e.disabled || t.meta?.disabled === !0,
					hidden: !1,
					setValue(n) {
						e.setValue({
							...i,
							[t.handle]: n
						});
					}
				});
			}));
		};
	}
}), Ee = i({
	name: "FormieVueFileFieldInput",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = e.field.input, r = Array.isArray(e.value) ? e.value : [], i = n.multiple === !0, o = r.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
			return a("div", { class: "formie-vue-file" }, [a("input", {
				key: "input",
				type: "file",
				...K(e.field, W(e, R(t))),
				disabled: e.disabled,
				multiple: i,
				onChange: (t) => {
					let n = t.target;
					e.setValue(Array.from(n.files || []));
				}
			}), o.length > 0 ? a("ul", {
				key: "summary",
				class: "formie-vue-file-list"
			}, o.map((e, t) => a("li", { key: `${e}:${t}` }, e))) : null]);
		};
	}
}), V = i({
	name: "FormieVueConfigFieldNode",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = n.fieldStates[e.errorKey]?.hidden === !0 || e.hidden;
			if (r) return null;
			let i = L(e.field), o = t.fieldComponents.value[e.field.type] || t.fieldComponents.value[i] || Me, s = t.components.value.Field || be, c = re(n.session, e.errorKey), l = c.errors, u = ne(n.definition);
			return a(s, {
				field: e.field,
				errors: e.errors,
				errorId: l,
				errorAriaLive: u,
				inputId: c.input,
				labelId: c.label,
				instructionsId: c.instructions
			}, { default: () => [a(o, {
				field: e.field,
				value: e.value,
				errors: e.errors,
				errorKey: e.errorKey,
				errorId: l,
				errorAriaLive: u,
				inputId: c.input,
				labelId: c.label,
				instructionsId: c.instructions,
				disabled: e.disabled,
				hidden: r,
				setValue: e.setValue
			})] });
		};
	}
}), De = i({
	name: "FormieVueConfigField",
	props: { field: {
		type: Object,
		required: !0
	} },
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value, r = t.instance.value;
			if (!n || !r) return null;
			let i = n.fieldStates[e.field.id];
			return a(V, {
				field: e.field,
				value: n.values[e.field.id],
				errors: n.errors.fields[e.field.id] || [],
				errorKey: e.field.id,
				disabled: i?.disabled === !0,
				hidden: i?.hidden === !0,
				setValue(t) {
					r.setValue(e.field.id, t);
				}
			});
		};
	}
}), H = i({
	name: "FormieVueConfigRow",
	props: {
		row: {
			type: Object,
			required: !0
		},
		rowIndex: {
			type: Number,
			required: !0
		},
		values: {
			type: Object,
			default: void 0
		},
		errorPrefix: {
			type: String,
			default: void 0
		},
		disabled: {
			type: Boolean,
			default: !1
		},
		setFieldValue: {
			type: Function,
			default: void 0
		}
	},
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value;
			return n ? a("div", { class: "formie-vue-row" }, e.row.fields.map((t, r) => {
				if (!e.values || !e.setFieldValue) return a(De, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t
				});
				let i = `${e.errorPrefix}.${t.handle}`;
				return a(V, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t,
					value: e.values[t.handle],
					errors: n.errors.fields[i] || [],
					errorKey: i,
					disabled: e.disabled === !0 || n.fieldStates[i]?.disabled === !0,
					hidden: n.fieldStates[i]?.hidden === !0,
					setValue(n) {
						e.setFieldValue?.(t, n);
					}
				});
			})) : null;
		};
	}
}), Oe = i({
	name: "FormieVueRepeaterFieldInput",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = me(e.field), i = Array.isArray(e.value) ? e.value : [], o = e.field.input, s = Number(o.minRows ?? 0) || 0, c = Number(o.maxRows ?? 0) || 0, l = !e.disabled && (c <= 0 || i.length < c);
			if (r.length === 0) return a("div", { class: "formie-vue-unsupported" }, "Unsupported repeater field.");
			let u = W(e, R(t)), d = n.definition;
			return a("div", {
				class: "formie-vue-repeater",
				"data-formie-repeater-container": !0,
				...q(e.field, u, "group")
			}, [...i.map((t, n) => {
				let o = `${e.field.id}:${n}`;
				return a("div", {
					key: o,
					class: "formie-vue-repeater-item",
					"data-formie-repeater-item": !0
				}, [...r.map((r, s) => a(H, {
					key: `${o}:${s}`,
					row: r,
					rowIndex: s,
					values: t,
					errorPrefix: `${e.errorKey}.${n}`,
					disabled: e.disabled,
					setFieldValue(t, r) {
						let a = i.map((e, i) => i === n ? {
							...e,
							[t.handle]: r
						} : e);
						e.setValue(a);
					}
				})), a("button", {
					key: "remove",
					type: "button",
					disabled: e.disabled || s > 0 && i.length <= s,
					"aria-label": `${T(d, "removeRow")} ${n + 1}`,
					"data-formie-repeater-remove": !0,
					onClick: () => {
						e.setValue(i.filter((e, t) => t !== n));
					}
				}, T(d, "remove"))]);
			}), a("button", {
				key: "add",
				type: "button",
				disabled: !l,
				"data-formie-repeater-add": e.field.handle,
				onClick: () => {
					e.setValue([...i, y(e.field)]);
				}
			}, String(o.addRowLabel || T(d, "addRow")))]);
		};
	}
});
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
		"aria-describedby": D(G(e, t), n && t.errorId),
		autocomplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0
	};
}
function ke(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function q(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": D(G(e, t))
	};
}
function J(e, t, n, r, i, o = L(e)) {
	let s = e.input;
	if (o === "multi-line-text") return a("textarea", {
		...K(e, i),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (o === "dropdown") {
		let o = s.multiple === !0, c = E(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = o ? S(e) : x(e), d = o ? "" : l[0] ?? "", f = !o && d === "" && !u.some((e) => e.value === "");
		return a("select", {
			...K(e, i),
			...o ? {} : { value: d },
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
			disabled: t.disabled,
			selected: o ? l.includes(t.value) : void 0
		}, t.label))]);
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return a("input", {
		...K(e, i),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		readonly: ae(e) || void 0,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			if (c === "number") {
				let e = t.valueAsNumber;
				r(Number.isFinite(e) ? e : "");
			} else r(t.value);
		}
	});
}
var Ae = i({
	name: "FormieVueTableFieldInput",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = t.state.value?.definition, r = _e(e.field), i = Array.isArray(e.value) ? e.value : [], o = e.field.input, s = o.static === !0 || o.staticRows === !0, c = Number(o.minRows ?? 0) || 0, l = Number(o.maxRows ?? 0) || 0, u = !e.disabled && !s && (l <= 0 || i.length < l), d = (t, n, r) => {
				e.setValue(i.map((e, i) => i === t ? {
					...e,
					[n]: r
				} : e));
			};
			return a("div", {
				class: "formie-vue-table",
				...q(e.field, W(e, R(t)), "group")
			}, [a("table", null, [a("thead", null, a("tr", null, [...r.map((e) => a("th", {
				key: e.id,
				scope: "col"
			}, e.heading)), s ? null : a("td", { key: "actions" })])), a("tbody", null, i.map((t, o) => a("tr", { key: o }, [...r.map((n) => a("td", { key: n.id }, je(n, t[n.handle], e.disabled, (e) => d(o, n.handle, e)))), s ? null : a("td", { key: "actions" }, a("button", {
				type: "button",
				disabled: e.disabled || i.length <= c,
				"aria-label": `${T(n, "removeRow")} ${o + 1}`,
				onClick: () => e.setValue(i.filter((e, t) => t !== o))
			}, T(n, "remove")))])))]), s ? null : a("button", {
				type: "button",
				disabled: !u,
				onClick: () => e.setValue([...i, ee(e.field)])
			}, String(o.addRowLabel || T(n, "addRow")))]);
		};
	}
});
function je(e, t, n, r) {
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
		onInput: (e) => r(e.target.value)
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
		onInput: (e) => r(e.target.value)
	});
}
var Me = i({
	name: "FormieVueDefaultFieldInput",
	props: j,
	setup(e) {
		let t = M();
		return () => {
			let n = e.field.input, r = L(e.field), i = W(e, R(t));
			if (oe(e.field)) return a(Te, e);
			if (de(e.field)) return a(Oe, e);
			if (le(e.field)) return a(Ee, e);
			if (r === "signature") return a(we, e);
			if (r === "multi-line-text" || r === "dropdown") return J(e.field, e.value, e.disabled, e.setValue, i, r);
			if (r === "table") return a(Ae, e);
			if (r === "section") return a("hr", { class: "formie-vue-section" });
			if (r === "radio") {
				let t = S(e.field), n = E(e.field), r = Array.isArray(e.value) ? String(e.value[0] ?? "") : e.value == null ? "" : String(e.value);
				return a("div", {
					class: "formie-vue-choices",
					...q(e.field, i, "radiogroup")
				}, t.map((t) => {
					let i = t.value, o = e.disabled || t.disabled;
					return a("label", { key: `${e.field.id}:${i}` }, [a("input", {
						key: "input",
						type: "radio",
						name: e.inputId || `${e.field.id}-radio`,
						value: i,
						...U(e.errors, e.errorId),
						checked: r === i,
						disabled: o,
						onChange: () => {
							e.setValue(n ? [i] : i);
						}
					}), a("span", { key: "label" }, t.label)]);
				}));
			}
			if (r === "checkboxes") {
				let t = S(e.field), n = Array.isArray(e.value) ? e.value.map((e) => String(e)) : [];
				return a("div", {
					class: "formie-vue-choices",
					...q(e.field, i, "group")
				}, t.map((t) => {
					let r = t.value, i = n.includes(r), o = e.disabled || t.disabled;
					return a("label", { key: `${e.field.id}:${r}` }, [a("input", {
						key: "input",
						type: "checkbox",
						...U(e.errors, e.errorId),
						checked: i,
						disabled: o,
						onChange: () => {
							let t = i ? n.filter((e) => e !== r) : [...n, r];
							e.setValue(t);
						}
					}), a("span", { key: "label" }, t.label)]);
				}));
			}
			if (r === "agree") {
				let t = typeof n.descriptionHtml == "string" ? n.descriptionHtml : null;
				return a("div", {
					class: "formie-vue-agree",
					...q(e.field, i, "group")
				}, [a("label", { class: "formie-vue-boolean" }, [a("input", {
					key: "input",
					type: "checkbox",
					id: e.inputId || void 0,
					"aria-required": e.field.required ? "true" : void 0,
					...U(e.errors, e.errorId),
					checked: e.value === !0,
					disabled: e.disabled,
					onChange: (t) => {
						let n = t.target;
						e.setValue(n.checked);
					}
				}), t ? a("span", {
					key: "description",
					innerHTML: t
				}) : a("span", { key: "description" }, e.field.label)])]);
			}
			let o = te(e.field);
			return o ? o.kind === "heading" ? a(o.level, { class: "formie-vue-heading" }, o.text) : a("div", {
				class: "formie-vue-content",
				innerHTML: o.html
			}) : ue(r) ? J(e.field, e.value, e.disabled, e.setValue, i, r) : a("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${String(e.field.meta?.fieldType ?? e.field.type)}`);
		};
	}
}), Ne = i({
	name: "FormieVueConfigPageActions",
	setup() {
		let e = M();
		return () => {
			let t = e.state.value, n = e.instance.value;
			if (!t || !n) return null;
			let r = t.definition.pages.find((e) => e.id === t.currentPageId);
			if (!r) return null;
			let i = [];
			return r.actions.secondary.forEach((e) => {
				i.push(a("button", {
					key: e.type,
					type: "button",
					onClick: async (r) => {
						let i = r.currentTarget.closest("form"), a = t.currentPageId;
						await n.submit(e.type), requestAnimationFrame(() => C(i, a));
					}
				}, e.label));
			}), i.push(a("button", {
				key: r.actions.primary.type,
				type: "submit",
				disabled: !h(t)
			}, r.actions.primary.label)), a("div", { class: "formie-page-actions" }, i);
		};
	}
}), Pe = i({
	name: "FormieVueConfigRenderer",
	props: { className: {
		type: String,
		default: void 0
	} },
	setup(e) {
		let t = M();
		return () => {
			let n = t.instance.value, r = t.state.value;
			if (!n || !r) return null;
			let i = t.components.value.Form || Se, o = t.components.value.Page || Ce, s = t.components.value.ErrorSummary || ye, c = r.definition.pages.find((e) => e.id === r.currentPageId && r.pageStates[e.id]?.hidden !== !0) || r.definition.pages.find((e) => r.pageStates[e.id]?.hidden !== !0) || r.definition.pages[0], l = r.lastSubmitResult?.messages.error, u = !!l && !r.errors.form.includes(l), d = !(r.lastSubmitResult?.completion?.behavior === "message" && r.lastSubmitResult.completion.hideForm);
			return c ? a(i, {
				definition: r.definition,
				session: r.session,
				state: r,
				className: e.className,
				onSubmit: () => n.submit()
			}, { default: () => [
				a(s, {
					key: "errors",
					errors: r.errors.form
				}),
				r.lastSubmitResult?.messages.notice ? a("div", {
					key: "notice",
					class: "formie-vue-notice",
					role: "status",
					tabindex: -1,
					"data-formie-form-message": !0,
					innerHTML: r.lastSubmitResult.messages.notice
				}) : null,
				u ? a("div", {
					key: "error",
					class: "formie-vue-error",
					role: "alert",
					tabindex: -1,
					"data-formie-form-message": !0
				}, l) : null,
				d && w(r.definition, r.currentPageId)?.position === "start" ? B(r) : null,
				d ? a(o, {
					key: c.id,
					page: c,
					state: r
				}, { default: () => [...c.rows.map((e, t) => a(H, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), a(Ne, { key: "actions" })] }) : null,
				d && w(r.definition, r.currentPageId)?.position === "end" ? B(r) : null
			] }) : null;
		};
	}
}), Fe = {
	source: {
		type: Object,
		required: !0
	},
	components: {
		type: Object,
		default: () => ({})
	},
	fieldComponents: {
		type: Object,
		default: () => ({})
	},
	slots: {
		type: Object,
		default: () => ({})
	},
	className: {
		type: String,
		default: void 0
	},
	onMount: {
		type: Function,
		default: void 0
	},
	onReady: {
		type: Function,
		default: void 0
	},
	onUnmount: {
		type: Function,
		default: void 0
	},
	onResult: {
		type: Function,
		default: void 0
	},
	onSuccess: {
		type: Function,
		default: void 0
	},
	onError: {
		type: Function,
		default: void 0
	},
	onSubmitResult: {
		type: Function,
		default: void 0
	},
	onSubmitSuccess: {
		type: Function,
		default: void 0
	},
	onSubmitError: {
		type: Function,
		default: void 0
	},
	onEvent: {
		type: Function,
		default: void 0
	}
};
function Y(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
var Ie = i({
	name: "FormieVueDefinitionFormView",
	props: Fe,
	emits: [
		"mount",
		"ready",
		"unmount",
		"result",
		"success",
		"error",
		"submit-result",
		"submit-success",
		"submit-error",
		"event"
	],
	setup(e, { emit: t }) {
		let n = d(null), i = d(null), o = u(null), s = {
			instance: n,
			state: i,
			components: r(() => e.components || {}),
			fieldComponents: r(() => e.fieldComponents || {}),
			slots: r(() => e.slots || {})
		}, c = r(() => k(e.source));
		return l(A, s), f(c, (r, a, s) => {
			let c = !1, l = () => void 0;
			(async () => {
				try {
					let r = await P(e.source), a = F(e.source), s = _({
						envelope: r,
						transport: a
					});
					if (c) {
						await s.destroy();
						return;
					}
					o.value = null, n.value = s, i.value = s.getState(), e.onMount?.(s), e.onReady?.(s), t("mount", s), t("ready", s);
					let u = [
						s.subscribe((e) => {
							i.value = e;
						}),
						s.on("formie:submit:result", (n) => {
							let r = n;
							Y(e.onSubmitResult, e.onResult, r), t("result", r), t("submit-result", r), r.success ? (Y(e.onSubmitSuccess, e.onSuccess, r), t("success", r), t("submit-success", r)) : (Y(e.onSubmitError, e.onError, r), t("error", r), t("submit-error", r));
						}),
						...p.map((n) => s.on(n, (r) => {
							let i = {
								name: n,
								payload: r
							};
							e.onEvent?.(i), t("event", i);
						}))
					];
					l = () => {
						u.forEach((e) => e()), s.destroy(), n.value === s && (n.value = null, i.value = null), e.onUnmount?.(), t("unmount");
					};
				} catch (e) {
					c || (o.value = e);
				}
			})(), s(() => {
				c = !0, l();
			});
		}, { immediate: !0 }), () => o.value ? a("div", { class: "formie-vue-error" }, o.value.message) : !n.value || !i.value ? a("div", { class: "formie-vue-loading" }, "Loading form...") : a(xe, {}, { default: () => a(Pe, { className: e.className }) });
	}
});
function Le() {
	let e = M();
	return {
		definition: r(() => e.state.value?.definition || null),
		session: r(() => e.state.value?.session || null),
		state: e.state,
		instance: e.instance
	};
}
function Re(e) {
	let t = M(), n = r(() => {
		let n = t.state.value?.definition;
		return n && I(n).find((t) => t.id === e) || null;
	});
	return {
		field: n,
		value: r(() => t.state.value?.values[e]),
		errors: r(() => t.state.value?.errors.fields[e] || []),
		hidden: r(() => t.state.value?.fieldStates[e]?.hidden === !0),
		disabled: r(() => t.state.value?.fieldStates[e]?.disabled === !0),
		setValue(e) {
			n.value && t.instance.value && t.instance.value.setValue(n.value.id, e);
		}
	};
}
function ze(e) {
	let t = M();
	return {
		page: r(() => t.state.value?.definition.pages.find((t) => t.id === e) || null),
		isCurrent: r(() => t.state.value?.currentPageId === e),
		hidden: r(() => t.state.value?.pageStates[e]?.hidden === !0)
	};
}
function Be() {
	return M().instance;
}
function Ve(e) {
	let t = M();
	return r(() => t.slots.value[e] || null);
}
//#endregion
//#region src/index.ts
function X(e) {
	return !!e && "payload" in e;
}
function He(e) {
	return "success" in e ? e.success : e.ok;
}
function Z(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function Q(e) {
	let t = e.transport;
	if (!t && !X(e.source)) throw Error("`transport` is required for <FormieForm>.");
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
function Ue(e) {
	if (e.source) return e.source;
	let t = e.transport, n = e.endpoint, r = e.formHandle;
	if (t !== "rest" && t !== "graphql") throw Error("Vue client-rendered forms require `transport=\"rest\"` or `transport=\"graphql\"`.");
	if (!n || !r) throw Error("Vue client-rendered forms require either `source` or both `endpoint` and `formHandle`.");
	return {
		transport: t,
		endpoint: n,
		formHandle: r,
		siteId: e.siteId,
		profile: e.profile
	};
}
function We() {
	return t();
}
function $() {
	return t();
}
function Ge(e) {
	let t = $(), n = u(null), i = d(null), a = u(null), o = r(() => k(e));
	return f([n, o], ([n], r, o) => {
		if (!n) return;
		let s = !1, c = !1, l = async () => {
			c || (c = !0, await t.unmount(n));
		}, u = Promise.resolve().then(async () => {
			if (!s) try {
				let r = await t.mount(n, {
					...e,
					mode: "server-rendered"
				});
				if (s) {
					await l();
					return;
				}
				i.value = r, a.value = null;
			} catch (e) {
				s || (a.value = e);
			}
		});
		o(() => {
			s = !0, i.value = null, u.finally(l);
		});
	}, { immediate: !0 }), {
		rootRef: n,
		state: {
			instance: i,
			isMounted: r(() => !!i.value),
			error: a
		},
		submit: async (e = "submit") => i.value ? i.value.submit(e) : null
	};
}
var Ke = i({
	name: "FormieVueHtmlFormView",
	props: { options: {
		type: Object,
		required: !0
	} },
	emits: [
		"mount",
		"ready",
		"unmount",
		"result",
		"success",
		"error",
		"submit-result",
		"submit-success",
		"submit-error",
		"event"
	],
	setup(n, { emit: i }) {
		let o = u(null), s = t(), c = r(() => Q(n.options)), l = r(() => k(c.value));
		return f([o, l], ([t], r, a) => {
			if (!t) return;
			let o = !1, l = null, u = [], d = Promise.resolve().then(async () => {
				let r = await s.mount(t, c.value);
				o ? await s.unmount(t) : (l = r, n.options.onMount?.(r), n.options.onReady?.(r), i("mount", r), i("ready", r), u.push(r.on("formie:submit:result", (e) => {
					let t = e;
					Z(n.options.onSubmitResult, n.options.onResult, t), i("result", t), i("submit-result", t), He(t) ? (Z(n.options.onSubmitSuccess, n.options.onSuccess, t), i("success", t), i("submit-success", t)) : (Z(n.options.onSubmitError, n.options.onError, t), i("error", t), i("submit-error", t));
				})), e.forEach((e) => {
					u.push(r.on(e, (t) => {
						let r = {
							name: e,
							payload: t
						};
						n.options.onEvent?.(r), i("event", r);
					}));
				}));
			});
			a(() => {
				o = !0, u.forEach((e) => e()), d.finally(async () => {
					await s.unmount(t), l &&= (n.options.onUnmount?.(), i("unmount"), null);
				});
			});
		}, { immediate: !0 }), () => a("div", {
			ref: o,
			class: n.options.className
		});
	}
}), qe = {
	source: {
		type: Object,
		default: void 0
	},
	profile: {
		type: String,
		default: "same-origin-browser"
	},
	transport: {
		type: String,
		default: void 0
	},
	endpoint: {
		type: String,
		default: void 0
	},
	formHandle: {
		type: String,
		default: void 0
	},
	staticCache: {
		type: Boolean,
		default: void 0
	},
	refreshTokens: {
		type: Boolean,
		default: void 0
	},
	locale: {
		type: String,
		default: void 0
	},
	siteId: {
		type: Number,
		default: void 0
	},
	autoVisible: {
		type: Boolean,
		default: void 0
	},
	theme: {
		type: String,
		default: void 0
	},
	themeConfig: {
		type: Object,
		default: void 0
	},
	className: {
		type: String,
		default: void 0
	},
	onMount: {
		type: Function,
		default: void 0
	},
	onReady: {
		type: Function,
		default: void 0
	},
	onUnmount: {
		type: Function,
		default: void 0
	},
	onResult: {
		type: Function,
		default: void 0
	},
	onSuccess: {
		type: Function,
		default: void 0
	},
	onError: {
		type: Function,
		default: void 0
	},
	onSubmitResult: {
		type: Function,
		default: void 0
	},
	onSubmitSuccess: {
		type: Function,
		default: void 0
	},
	onSubmitError: {
		type: Function,
		default: void 0
	},
	onEvent: {
		type: Function,
		default: void 0
	}
}, Je = {
	source: {
		type: Object,
		default: void 0
	},
	profile: {
		type: String,
		default: "same-origin-browser"
	},
	transport: {
		type: String,
		default: void 0
	},
	endpoint: {
		type: String,
		default: void 0
	},
	formHandle: {
		type: String,
		default: void 0
	},
	siteId: {
		type: Number,
		default: void 0
	},
	components: {
		type: Object,
		default: void 0
	},
	fieldComponents: {
		type: Object,
		default: void 0
	},
	slots: {
		type: Object,
		default: void 0
	},
	className: {
		type: String,
		default: void 0
	},
	onMount: {
		type: Function,
		default: void 0
	},
	onReady: {
		type: Function,
		default: void 0
	},
	onUnmount: {
		type: Function,
		default: void 0
	},
	onResult: {
		type: Function,
		default: void 0
	},
	onSuccess: {
		type: Function,
		default: void 0
	},
	onError: {
		type: Function,
		default: void 0
	},
	onSubmitResult: {
		type: Function,
		default: void 0
	},
	onSubmitSuccess: {
		type: Function,
		default: void 0
	},
	onSubmitError: {
		type: Function,
		default: void 0
	},
	onEvent: {
		type: Function,
		default: void 0
	}
}, Ye = i({
	name: "FormieVueForm",
	props: qe,
	emits: [
		"mount",
		"ready",
		"unmount",
		"result",
		"success",
		"error",
		"submit-result",
		"submit-success",
		"submit-error",
		"event"
	],
	setup(e, { emit: t }) {
		return () => {
			let n = {
				source: e.source,
				profile: e.profile,
				transport: e.transport,
				endpoint: e.endpoint,
				formHandle: e.formHandle,
				staticCache: e.staticCache,
				refreshTokens: e.refreshTokens,
				locale: e.locale,
				siteId: e.siteId,
				autoVisible: e.autoVisible,
				theme: e.theme,
				themeConfig: e.themeConfig,
				className: e.className,
				onMount: e.onMount,
				onReady: e.onReady,
				onUnmount: e.onUnmount,
				onResult: e.onResult,
				onSuccess: e.onSuccess,
				onError: e.onError,
				onSubmitResult: e.onSubmitResult,
				onSubmitSuccess: e.onSubmitSuccess,
				onSubmitError: e.onSubmitError,
				onEvent: e.onEvent
			};
			return a(Ke, {
				options: n,
				onMount: (e) => t("mount", e),
				onReady: (e) => t("ready", e),
				onUnmount: () => t("unmount"),
				onResult: (e) => t("result", e),
				onSuccess: (e) => t("success", e),
				onError: (e) => t("error", e),
				onSubmitResult: (e) => t("submit-result", e),
				onSubmitSuccess: (e) => t("submit-success", e),
				onSubmitError: (e) => t("submit-error", e),
				onEvent: (e) => t("event", e)
			});
		};
	}
}), Xe = i({
	name: "FormieVueClientForm",
	props: Je,
	emits: [
		"mount",
		"ready",
		"unmount",
		"result",
		"success",
		"error",
		"submit-result",
		"submit-success",
		"submit-error",
		"event"
	],
	setup(e, { emit: t }) {
		return () => a(Ie, {
			source: Ue({
				source: e.source,
				profile: e.profile,
				transport: e.transport,
				endpoint: e.endpoint,
				formHandle: e.formHandle,
				siteId: e.siteId,
				components: e.components,
				fieldComponents: e.fieldComponents,
				slots: e.slots,
				className: e.className,
				onMount: e.onMount,
				onReady: e.onReady,
				onUnmount: e.onUnmount,
				onResult: e.onResult,
				onSuccess: e.onSuccess,
				onError: e.onError,
				onSubmitResult: e.onSubmitResult,
				onSubmitSuccess: e.onSubmitSuccess,
				onSubmitError: e.onSubmitError,
				onEvent: e.onEvent
			}),
			components: e.components,
			fieldComponents: e.fieldComponents,
			slots: e.slots,
			className: e.className,
			onMount: (n) => {
				e.onMount?.(n), t("mount", n);
			},
			onReady: (n) => {
				e.onReady?.(n), t("ready", n);
			},
			onUnmount: () => {
				e.onUnmount?.(), t("unmount");
			},
			onSubmitResult: (n) => {
				e.onSubmitResult?.(n), e.onResult?.(n), t("result", n), t("submit-result", n);
			},
			onSubmitSuccess: (n) => {
				e.onSubmitSuccess?.(n), e.onSuccess?.(n), t("success", n), t("submit-success", n);
			},
			onSubmitError: (n) => {
				e.onSubmitError?.(n), e.onError?.(n), t("error", n), t("submit-error", n);
			},
			onEvent: (n) => {
				e.onEvent?.(n), t("event", n);
			}
		});
	}
});
//#endregion
export { Xe as FormieClientForm, Ye as FormieForm, We as createVueFormieClient, Le as useFormie, $ as useFormieClient, Re as useFormieField, Ge as useFormieHtml, Be as useFormieInstance, ze as useFormiePage, Ve as useFormieSlot };
