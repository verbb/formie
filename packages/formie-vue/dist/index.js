import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { cloneVNode as r, computed as i, defineComponent as a, h as o, inject as s, onBeforeUnmount as c, onMounted as l, provide as u, ref as d, shallowRef as f, watch as p } from "vue";
import { CLIENT_FORM_EVENT_NAMES as m, PHONE_COUNTRY_CHANGE_EVENT as h, VISUALLY_HIDDEN_STYLE as g, allOptionsSelected as _, attachPointerReorder as v, checkboxToggle as y, clientActionAllowed as ee, compositePartDefinitions as te, createClientFormInstance as ne, createGraphqlClientTransport as re, createRepeaterRowValue as ie, createRestClientTransport as ae, createTableRowValue as oe, datePickerInputValue as se, datePickerValue as ce, dateRangePickerMarkup as le, dateRangePickerValue as b, displayFieldContent as ue, dropdownOptions as de, fieldContentPath as fe, fieldOptions as x, focusClientFormResult as S, getClientErrorAriaLive as pe, getClientFieldIds as me, getClientFieldLabelTarget as he, getClientPageProgress as C, getClientString as w, isArrayValuedField as T, isCalculationField as ge, isCompositeField as _e, isDatePickerField as ve, isDateRangePickerField as ye, isFieldLabelHidden as be, isFieldLabelVisuallyHidden as xe, isFileField as Se, isKnownClientFieldType as Ce, isPhoneCountryEnabled as we, isRepeatableField as Te, isRichTextField as Ee, isSearchableDropdown as E, joinIdReferences as D, loadClientFormBootstrap as De, loadGraphqlClientFormBootstrap as Oe, moveListItem as ke, paymentFieldMarkup as Ae, phoneFieldValue as O, readPhoneFieldControls as k, readUploadManagerEntries as je, repeaterRowDefinitions as Me, resolveClientRendererType as Ne, searchableDropdownMarkup as Pe, searchableDropdownValue as Fe, showsClientPageTitle as Ie, summaryFieldMarkup as Le, surveyLikertRows as Re, surveyRankOrder as A, tableColumns as ze, toggleAllOptions as Be, uploadManagerMarkup as Ve } from "@verbb/formie-core";
//#region src/stable.ts
function j(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => j(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${j(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function M(e) {
	return j(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.ts
var N = Symbol("formie-definition-context"), P = {
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
function F() {
	let e = s(N);
	if (!e) throw Error("Formie definition composables must be used within a client-rendered <FormieForm>.");
	return e;
}
function I(e) {
	return "definition" in e;
}
async function He(e) {
	return I(e) ? e.definition : e.transport === "graphql" ? Oe({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	}) : De({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	});
}
function Ue(e) {
	let t = I(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId,
		query: e.query
	};
	return t.type === "graphql" ? re(t) : ae(t);
}
function We(e) {
	return e.pages.flatMap((e) => e.rows).flatMap((e) => e.fields);
}
function L(e) {
	return Ne(e);
}
function R(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.entries.find((e) => r.has(e.key) && e.moduleId === n) || null;
}
function z(e) {
	return !e.components.value.Field;
}
function B(e, t, n, r, i) {
	if (!n) return null;
	let a = e.slots.value[t];
	return a ? o(a, {
		slotKey: t,
		attributes: r,
		field: i
	}, { default: () => [n] }) : n;
}
var Ge = a({
	name: "FormieVueDefaultErrorSummary",
	props: { errors: {
		type: Array,
		required: !0
	} },
	setup(e) {
		return () => e.errors.length === 0 ? null : o("div", {
			class: "formie-vue-errors",
			role: "alert",
			tabindex: -1,
			"data-formie-form-message": !0
		}, [o("ul", null, e.errors.map((e, t) => o("li", { key: `${e}:${t}` }, e)))]);
	}
}), Ke = a({
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
		let n = F();
		return () => {
			let r = t.slots.default?.() || [], i = {
				class: "formie-vue-label",
				style: xe(e.field) ? g : void 0,
				id: e.labelId || void 0,
				for: e.inputId && he(e.field, L(e.field)) === "control" ? e.inputId : void 0
			}, a = {
				class: "formie-vue-description",
				id: e.instructionsId || void 0
			};
			return o("div", {
				class: "formie-vue-field",
				"data-formie-field-uid": e.field.uid,
				"data-formie-field-handle": e.field.handle,
				"data-formie-field-type": e.field.type,
				"data-formie-field-required": e.field.required ? "true" : void 0
			}, [
				e.field.label && !be(e.field) ? B(n, "label", o("label", i, e.field.label), i, e.field) : null,
				e.field.instructions ? B(n, "instructions", o("div", {
					...a,
					innerHTML: e.field.instructions
				}), a, e.field) : null,
				B(n, "input", o("div", { class: "formie-vue-input" }, r), { class: "formie-vue-input" }, e.field),
				B(n, "errors", o("ul", {
					id: e.errorId,
					class: "formie-vue-field-errors",
					style: e.errors.length === 0 ? { position: "absolute" } : void 0,
					"data-formie-field-errors": !0,
					"aria-live": e.errorAriaLive === "off" ? void 0 : e.errorAriaLive,
					"aria-atomic": e.errorAriaLive === "off" ? void 0 : "true"
				}, e.errors.map((e, t) => o("li", { key: `${e}:${t}` }, e))), {
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
}), qe = a({ setup(e, { slots: t }) {
	let r = F(), i = d(null), a = !1, s;
	return l(async () => {
		let e = r.instance.value;
		e.setBrowserModuleGuard(() => {
			throw Error("Form features are still loading.");
		});
		let t = await n(i.value, e);
		a ? t.destroy() : s = t;
	}), c(() => {
		a = !0, s?.destroy();
	}), () => o("div", { ref: i }, t.default?.());
} }), Je = a({
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
		return () => o("form", {
			class: e.className,
			onSubmit: async (t) => {
				t.preventDefault();
				let n = t.currentTarget, r = e.state.currentPageId;
				await e.onSubmit(), requestAnimationFrame(() => S(n, r));
			},
			"data-formie-definition": e.definition.handle,
			"data-formie-render-id": e.session.tokens.render
		}, t.slots.default?.() || []);
	}
}), Ye = a({
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
			let n = Ie(e.state.definition) && !!e.page.label, r = `formie-${e.state.session.tokens.render || e.state.session.id}-page-${e.page.id}-title`;
			return o("section", {
				"data-page-id": e.page.id,
				class: "formie-vue-page",
				tabindex: -1,
				"aria-labelledby": n ? r : void 0,
				"aria-label": !n && e.state.definition.pages.length > 1 && e.page.label ? e.page.label : void 0
			}, [n ? o("h3", {
				id: r,
				class: "formie-vue-page-title"
			}, e.page.label) : null, ...t.slots.default?.() || []]);
		};
	}
});
function V(e) {
	let t = C(e.definition, e.currentPageId);
	if (!t) return null;
	let n = o("span", {
		class: "formie-vue-progress-value",
		"aria-hidden": "true"
	}, `${t.percent}%`), r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition);
	return o("div", {
		key: "progress",
		class: "formie-vue-progress",
		"data-formie-progress-position": t.position,
		"data-formie-progress-value-position": t.valuePosition,
		"data-formie-progress-state": t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle"
	}, [
		t.valuePosition === "left" ? n : null,
		o("div", {
			class: "formie-vue-progress-track",
			role: "progressbar",
			"aria-label": w(e.definition, "progress"),
			"aria-valuenow": t.percent,
			"aria-valuemin": 0,
			"aria-valuemax": 100
		}, o("div", {
			class: "formie-vue-progress-bar",
			style: { width: `${t.percent}%` }
		}, r ? n : null)),
		t.valuePosition === "right" ? n : null
	]);
}
var Xe = a({
	name: "FormieVueSignatureFieldInput",
	props: P,
	setup(e) {
		let t = F(), n = d(null), r = f(null), a = d(null), s = i(() => R(e.field, t.state.value?.definition || { modules: {
			contractVersion: 2,
			surface: "client-rendered",
			entries: []
		} }, "formie:signature")?.config), u = i(() => typeof s.value?.backgroundColor == "string" ? s.value.backgroundColor : "#ffffff"), m = i(() => typeof s.value?.penColor == "string" ? s.value.penColor : "#000000"), h = i(() => Number(s.value?.penWeight ?? 2) || 2), g = i(() => typeof e.value == "string" ? e.value : ""), _ = !1, v = () => void 0, y = () => void 0;
		return l(() => {
			(async () => {
				try {
					let t = n.value;
					if (!t) return;
					let { default: i } = await import("./signature_pad-Cqf-RWiu.js");
					if (_) return;
					let o = new i(t, {
						backgroundColor: u.value,
						penColor: m.value,
						minWidth: h.value,
						maxWidth: h.value
					}), s = () => {
						let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), n = Math.max(1, Math.floor(t.clientWidth || 480)), r = t.getContext("2d");
						t.width = n * e, t.height = 192 * e, t.style.height = "192px", r && (r.setTransform(1, 0, 0, 1, 0, 0), r.scale(e, e)), o.clear();
					}, c = () => {
						e.setValue(o.isEmpty() ? "" : o.toDataURL());
					};
					s(), o.addEventListener?.("endStroke", c), v = () => {
						o.removeEventListener?.("endStroke", c);
					}, typeof window < "u" && (window.addEventListener("resize", s), y = () => {
						window.removeEventListener("resize", s);
					}), r.value = o, a.value = null;
				} catch (e) {
					_ || (a.value = e.message || "Unable to load signature support.");
				}
			})();
		}), c(() => {
			_ = !0, v(), y(), r.value = null;
		}), p(g, (e) => {
			let t = r.value;
			if (t) {
				if (!e) t.isEmpty() || t.clear();
				else try {
					t.fromDataURL(e);
				} catch {}
			}
		}, { immediate: !0 }), () => o("div", { class: "formie-vue-signature" }, [
			o("canvas", {
				key: "canvas",
				ref: n,
				"data-formie-signature-canvas": !0,
				...nt(e.field, G(e, z(t))),
				style: e.disabled ? { pointerEvents: "none" } : void 0
			}),
			o("button", {
				key: "clear",
				type: "button",
				disabled: e.disabled,
				"aria-label": w(t.state.value?.definition, "clearSignature"),
				"data-formie-signature-clear": !0,
				onClick: () => {
					r.value?.clear(), e.setValue("");
				}
			}, w(t.state.value?.definition, "clear")),
			a.value ? o("div", {
				key: "error",
				class: "formie-vue-unsupported"
			}, a.value) : null
		]);
	}
}), Ze = a({
	name: "FormieVueDatePickerFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = G(e, z(t));
			return o("div", {
				class: "formie-vue-date-picker",
				onInput: (t) => {
					let n = t.currentTarget.querySelector("input[type=\"hidden\"]");
					n instanceof HTMLInputElement && e.setValue(ce(n.value));
				}
			}, o("div", null, o("input", {
				type: "text",
				...q(e.field, n),
				"data-formie-date-datepicker-input": "true",
				value: se(e.value),
				disabled: e.disabled,
				placeholder: typeof e.field.input.placeholder == "string" ? e.field.input.placeholder : void 0
			})));
		};
	}
}), Qe = a({
	name: "FormieVueCompositeFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = te(e.field), i = e.value && typeof e.value == "object" ? e.value : {};
			return r.length === 0 ? o("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${e.field.type}`) : o("div", {
				class: "formie-vue-name-grid",
				...J(e.field, G(e, z(t)), "group")
			}, r.filter((e) => e.meta?.hidden !== !0).map((t) => {
				let r = `${e.errorKey}.${t.handle}`;
				return o(H, {
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
}), $e = a({
	name: "FormieVueFileFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = e.field.input, r = Array.isArray(e.value) ? e.value : [], i = n.multiple === !0, a = r.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
			return o("div", { class: "formie-vue-file" }, [o("input", {
				key: "input",
				type: "file",
				...q(e.field, G(e, z(t))),
				disabled: e.disabled,
				multiple: i,
				onChange: (t) => {
					let n = t.target;
					e.setValue(Array.from(n.files || []));
				}
			}), a.length > 0 ? o("ul", {
				key: "summary",
				class: "formie-vue-file-list"
			}, a.map((e, t) => o("li", { key: `${e}:${t}` }, e))) : null]);
		};
	}
}), H = a({
	name: "FormieVueConfigFieldNode",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = n.fieldStates[e.errorKey]?.hidden === !0 || e.hidden;
			if (r) return null;
			let i = L(e.field), a = t.fieldComponents.value[e.field.type] || t.fieldComponents.value[i] || dt, s = t.components.value.Field || Ke, c = me(n.session, e.errorKey), l = c.errors, u = pe(n.definition);
			return o(s, {
				field: e.field,
				errors: e.errors,
				errorId: l,
				errorAriaLive: u,
				inputId: c.input,
				labelId: c.label,
				instructionsId: c.instructions
			}, { default: () => [o(a, {
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
}), et = a({
	name: "FormieVueConfigField",
	props: { field: {
		type: Object,
		required: !0
	} },
	setup(e) {
		let t = F();
		return () => {
			let n = t.state.value, r = t.instance.value;
			if (!n || !r) return null;
			let i = n.fieldStates[e.field.id];
			return o(H, {
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
}), U = a({
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
		let t = F();
		return () => {
			let n = t.state.value;
			return n ? o("div", { class: "formie-vue-row" }, e.row.fields.map((t, r) => {
				if (!e.values || !e.setFieldValue) return o(et, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t
				});
				let i = `${e.errorPrefix}.${t.handle}`;
				return o(H, {
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
}), tt = a({
	name: "FormieVueRepeaterFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = Me(e.field), i = Array.isArray(e.value) ? e.value : [], a = e.field.input, s = Number(a.minRows ?? 0) || 0, c = Number(a.maxRows ?? 0) || 0, l = !e.disabled && (c <= 0 || i.length < c);
			if (r.length === 0) return o("div", { class: "formie-vue-unsupported" }, "Unsupported repeater field.");
			let u = G(e, z(t)), d = n.definition;
			return o("div", {
				class: "formie-vue-repeater",
				"data-formie-repeater-container": !0,
				...J(e.field, u, "group")
			}, [...i.map((t, n) => {
				let a = `${e.field.id}:${n}`;
				return o("div", {
					key: a,
					class: "formie-vue-repeater-item",
					"data-formie-repeater-item": !0
				}, [...r.map((r, s) => o(U, {
					key: `${a}:${s}`,
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
				})), o("button", {
					key: "remove",
					type: "button",
					disabled: e.disabled || s > 0 && i.length <= s,
					"aria-label": `${w(d, "removeRow")} ${n + 1}`,
					"data-formie-repeater-remove": !0,
					onClick: () => {
						e.setValue(i.filter((e, t) => t !== n));
					}
				}, w(d, "remove"))]);
			}), o("button", {
				key: "add",
				type: "button",
				disabled: !l,
				"data-formie-repeater-add": e.field.handle,
				onClick: () => {
					e.setValue([...i, ie(e.field)]);
				}
			}, String(a.addRowLabel || w(d, "addRow")))]);
		};
	}
});
function W(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function G(e, t) {
	return {
		errors: e.errors,
		errorId: e.errorId,
		inputId: e.inputId,
		labelId: e.labelId,
		instructionsId: e.instructionsId,
		labelledByWrapper: t && !!e.field.label
	};
}
function K(e, t) {
	return t.labelledByWrapper && e.instructions ? t.instructionsId : null;
}
function q(e, t) {
	let n = t.errors.length > 0;
	return {
		id: t.inputId || void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": e.required ? "true" : void 0,
		"aria-invalid": n ? "true" : void 0,
		"aria-errormessage": n ? t.errorId : void 0,
		"aria-describedby": D(K(e, t), n && t.errorId),
		autocomplete: typeof e.input.autocomplete == "string" ? e.input.autocomplete : void 0
	};
}
function nt(e, t) {
	return {
		role: "img",
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle
	};
}
function J(e, t, n) {
	return {
		role: n,
		"aria-labelledby": t.labelledByWrapper ? t.labelId : void 0,
		"aria-label": t.labelledByWrapper ? void 0 : e.label || e.handle,
		"aria-required": n === "radiogroup" && e.required ? "true" : void 0,
		"aria-describedby": D(K(e, t))
	};
}
function Y(e, t, n, r, i, a = L(e)) {
	let s = e.input;
	if (a === "multi-line-text" && Ee(e)) return o("div", {
		class: "formie-vue-rich-text",
		onInput: (e) => {
			let t = e.target;
			t instanceof HTMLTextAreaElement && r(t.value);
		}
	}, [o("div", {
		key: "editor",
		"data-formie-rich-text": "true"
	}), o("textarea", {
		key: "input",
		...q(e, i),
		"data-formie-multi-line-text-input": "true",
		style: { display: "none" },
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0
	})]);
	if (a === "multi-line-text") return o("textarea", {
		...q(e, i),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (a === "dropdown" && E(e)) return o("div", {
		class: "formie-vue-combobox",
		onInput: (t) => {
			let n = t.target;
			n instanceof HTMLSelectElement && r(Fe(e, n));
		}
	}, o("div", { innerHTML: Pe(e, {
		id: i.inputId || void 0,
		"aria-required": e.required ? "true" : void 0,
		disabled: n
	}) }));
	if (a === "dropdown") {
		let a = s.multiple === !0, c = T(e), l = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), u = a ? x(e) : de(e), d = a ? "" : l[0] ?? "", f = !a && d === "" && !u.some((e) => e.value === "");
		return o("select", {
			...q(e, i),
			...a ? {} : { value: d },
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
			disabled: t.disabled,
			selected: a ? l.includes(t.value) : void 0
		}, t.label))]);
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return o("input", {
		...q(e, i),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		readonly: ge(e) || void 0,
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
var rt = a({
	name: "FormieVuePhoneFieldInput",
	props: P,
	setup(e) {
		let t = F(), n = d(null), i = () => {
			e.setValue(k(n.value, O(e.value)));
		};
		return l(() => {
			n.value?.addEventListener(h, i);
		}), c(() => {
			n.value?.removeEventListener(h, i);
		}), () => {
			let i = G(e, z(t)), a = O(e.value), s = Y(e.field, a.number, e.disabled, (t) => {
				e.setValue({
					...k(n.value, a),
					number: String(t ?? "")
				});
			}, i, "phone");
			return we(e.field) ? o("div", {
				ref: n,
				class: "formie-vue-phone"
			}, [r(s, { "data-formie-phone-input": "true" }), o("input", {
				type: "hidden",
				value: a.country ?? "",
				"data-formie-phone-country-input": "true"
			})]) : s;
		};
	}
}), it = a({
	name: "FormieVueSurveyLikertFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = G(e, z(t)), r = x(e.field), i = Re(e.field), a = i.length > 0, s = a && e.value && typeof e.value == "object" ? e.value : {}, c = e.inputId || `${e.field.id}-likert`;
			return o("div", {
				class: "formie-vue-likert",
				...J(e.field, n, "group")
			}, [o("table", null, [o("thead", null, o("tr", null, [o("td", { key: "corner" }), ...r.map((e, t) => o("th", {
				key: e.value,
				id: `${c}-col-${t}`,
				scope: "col"
			}, e.label))])), o("tbody", null, (a ? i : [{
				label: "",
				value: ""
			}]).map((t, n) => {
				let i = a ? String(s[t.value] ?? "") : e.value == null ? "" : String(e.value), l = `${c}-row-${n}`;
				return o("tr", { key: t.value || "answer" }, [a ? o("th", {
					id: l,
					scope: "row"
				}, t.label) : o("td"), ...r.map((r, u) => o("td", { key: r.value }, o("input", {
					type: "radio",
					name: `${c}-${n}`,
					value: r.value,
					checked: i === r.value,
					disabled: e.disabled || r.disabled,
					"aria-labelledby": a ? `${c}-col-${u} ${l}` : `${c}-col-${u}`,
					...W(e.errors, e.errorId),
					onChange: () => {
						e.setValue(a ? {
							...s,
							[t.value]: r.value
						} : r.value);
					}
				})))]);
			}))])]);
		};
	}
}), at = a({
	name: "FormieVueSurveyRankFieldInput",
	props: P,
	setup(e) {
		let t = F(), n = d(null), r, i = (t, n) => {
			e.setValue(ke(A(e.field, e.value), t, n));
		};
		return l(() => {
			n.value && (r = v(n.value, "li", i));
		}), c(() => r?.()), () => {
			let r = G(e, z(t)), a = t.state.value?.definition, s = Object.fromEntries(x(e.field).map((e) => [e.value, e.label])), c = A(e.field, e.value);
			return o("ol", {
				ref: n,
				class: "formie-vue-rank",
				...J(e.field, r, "group")
			}, c.map((t, n) => o("li", { key: t }, [
				o("span", null, s[t] ?? t),
				o("button", {
					type: "button",
					disabled: e.disabled || n === 0,
					"aria-label": `${w(a, "moveUp")}: ${s[t] ?? t}`,
					onClick: () => i(n, n - 1)
				}, w(a, "moveUp")),
				o("button", {
					type: "button",
					disabled: e.disabled || n === c.length - 1,
					"aria-label": `${w(a, "moveDown")}: ${s[t] ?? t}`,
					onClick: () => i(n, n + 1)
				}, w(a, "moveDown"))
			])));
		};
	}
}), ot = {
	position: "absolute",
	inset: "0",
	width: "100%",
	height: "100%",
	margin: "0",
	opacity: "0",
	cursor: "pointer"
}, st = a({
	name: "FormieVueSurveyRatingFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = G(e, z(t)), r = x(e.field), i = e.value == null ? "" : String(e.value), a = r.findIndex((e) => e.value === i), s = typeof e.field.input.starColor == "string" && e.field.input.starColor ? e.field.input.starColor : void 0;
			return o("div", {
				class: "formie-vue-rating",
				style: s ? { color: s } : void 0,
				...J(e.field, n, "radiogroup")
			}, r.map((t, n) => o("label", {
				key: t.value,
				style: {
					position: "relative",
					display: "inline-block"
				},
				"data-formie-rating-filled": n <= a ? "true" : void 0
			}, [
				o("input", {
					type: "radio",
					name: e.inputId || `${e.field.id}-rating`,
					value: t.value,
					checked: i === t.value,
					disabled: e.disabled || t.disabled,
					style: ot,
					...W(e.errors, e.errorId),
					onChange: () => e.setValue(t.value),
					onClick: () => {
						i === t.value && e.setValue(null);
					}
				}),
				o("span", { "aria-hidden": "true" }, n <= a ? "★" : "☆"),
				o("span", { style: g }, t.label)
			])));
		};
	}
}), ct = a({
	name: "FormieVueSummaryFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => o("div", {
			class: "formie-vue-summary",
			innerHTML: Le(e.field, t.state.value?.lastSubmitResult) ?? ""
		});
	}
}), lt = a({
	name: "FormieVueTableFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = t.state.value?.definition, r = ze(e.field), i = Array.isArray(e.value) ? e.value : [], a = e.field.input, s = a.static === !0 || a.staticRows === !0, c = Number(a.minRows ?? 0) || 0, l = Number(a.maxRows ?? 0) || 0, u = !e.disabled && !s && (l <= 0 || i.length < l), d = (t, n, r) => {
				e.setValue(i.map((e, i) => i === t ? {
					...e,
					[n]: r
				} : e));
			};
			return o("div", {
				class: "formie-vue-table",
				...J(e.field, G(e, z(t)), "group")
			}, [o("table", null, [o("thead", null, o("tr", null, [...r.map((e) => o("th", {
				key: e.id,
				scope: "col"
			}, e.heading)), s ? null : o("td", { key: "actions" })])), o("tbody", null, i.map((t, a) => o("tr", { key: a }, [...r.map((n) => o("td", { key: n.id }, ut(n, t[n.handle], e.disabled, (e) => d(a, n.handle, e)))), s ? null : o("td", { key: "actions" }, o("button", {
				type: "button",
				disabled: e.disabled || i.length <= c,
				"aria-label": `${w(n, "removeRow")} ${a + 1}`,
				onClick: () => e.setValue(i.filter((e, t) => t !== a))
			}, w(n, "remove")))])))]), s ? null : o("button", {
				type: "button",
				disabled: !u,
				onClick: () => e.setValue([...i, oe(e.field)])
			}, String(a.addRowLabel || w(n, "addRow")))]);
		};
	}
});
function ut(e, t, n, r) {
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
		onInput: (e) => r(e.target.value)
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
		onInput: (e) => r(e.target.value)
	});
}
var dt = a({
	name: "FormieVueDefaultFieldInput",
	props: P,
	setup(e) {
		let t = F();
		return () => {
			let n = e.field.input, r = L(e.field), i = G(e, z(t));
			if (ye(e.field)) return o("div", {
				class: "formie-vue-date-range-picker",
				onInput: (t) => e.setValue(b(t.currentTarget))
			}, o("div", { innerHTML: le(e.field, {
				id: i.inputId || void 0,
				"aria-label": i.labelledByWrapper ? void 0 : e.field.label || e.field.handle,
				"aria-required": e.field.required ? "true" : void 0,
				placeholder: typeof n.placeholder == "string" ? n.placeholder : void 0
			}) }));
			if (ve(e.field)) return o(Ze, e);
			if (_e(e.field)) return o(Qe, e);
			if (Te(e.field)) return o(tt, e);
			let a = Ve(e.field);
			if (a !== null) return o("div", {
				class: "formie-vue-upload-manager",
				"data-formie-upload-field-handle": t.state.value ? fe(t.state.value.definition, e.errorKey) : e.field.handle,
				onInput: (t) => e.setValue(je(t.currentTarget))
			}, o("div", { innerHTML: a }));
			if (Se(e.field)) return o($e, e);
			if (r === "signature") return o(Xe, e);
			if (r === "multi-line-text" || r === "dropdown") return Y(e.field, e.value, e.disabled, e.setValue, i, r);
			if (r === "table") return o(lt, e);
			if (r === "phone") return o(rt, e);
			if (r === "survey-likert") return o(it, e);
			if (r === "survey-rank") return o(at, e);
			if (r === "survey-rating") return o(st, e);
			if (r === "section") return o("hr", { class: "formie-vue-section" });
			if (r === "radio") {
				let t = x(e.field), n = T(e.field), r = Array.isArray(e.value) ? String(e.value[0] ?? "") : e.value == null ? "" : String(e.value);
				return o("div", {
					class: "formie-vue-choices",
					...J(e.field, i, "radiogroup")
				}, t.map((t) => {
					let i = t.value, a = e.disabled || t.disabled;
					return o("label", { key: `${e.field.id}:${i}` }, [o("input", {
						key: "input",
						type: "radio",
						name: e.inputId || `${e.field.id}-radio`,
						value: i,
						...W(e.errors, e.errorId),
						checked: r === i,
						disabled: a,
						onChange: () => {
							e.setValue(n ? [i] : i);
						}
					}), o("span", { key: "label" }, t.label)]);
				}));
			}
			if (r === "checkboxes") {
				let t = x(e.field), n = Array.isArray(e.value) ? e.value.map((e) => String(e)) : [], r = y(e.field), a = r ? o("label", {
					key: "toggle",
					class: "formie-vue-checkbox-toggle"
				}, [o("input", {
					type: "checkbox",
					checked: _(e.field, n),
					disabled: e.disabled,
					onChange: (t) => e.setValue(Be(e.field, n, t.target.checked))
				}), o("span", null, r.label)]) : null;
				return o("div", {
					class: "formie-vue-choices",
					...J(e.field, i, "group")
				}, [
					r?.position === "top" ? a : null,
					...t.map((t) => {
						let r = t.value, i = n.includes(r), a = e.disabled || t.disabled;
						return o("label", { key: `${e.field.id}:${r}` }, [o("input", {
							key: "input",
							type: "checkbox",
							...W(e.errors, e.errorId),
							checked: i,
							disabled: a,
							onChange: () => {
								let t = i ? n.filter((e) => e !== r) : [...n, r];
								e.setValue(t);
							}
						}), o("span", { key: "label" }, t.label)]);
					}),
					r?.position === "bottom" ? a : null
				]);
			}
			if (r === "agree") {
				let t = typeof n.descriptionHtml == "string" ? n.descriptionHtml : null;
				return o("div", {
					class: "formie-vue-agree",
					...J(e.field, i, "group")
				}, [o("label", { class: "formie-vue-boolean" }, [o("input", {
					key: "input",
					type: "checkbox",
					id: e.inputId || void 0,
					"aria-required": e.field.required ? "true" : void 0,
					...W(e.errors, e.errorId),
					checked: e.value === !0,
					disabled: e.disabled,
					onChange: (t) => {
						let n = t.target;
						e.setValue(n.checked);
					}
				}), t ? o("span", {
					key: "description",
					innerHTML: t
				}) : o("span", { key: "description" }, e.field.label)])]);
			}
			let s = ue(e.field);
			if (s) return s.kind === "heading" ? o(s.level, { class: "formie-vue-heading" }, s.text) : o("div", {
				class: "formie-vue-content",
				innerHTML: s.html
			});
			if (e.field.type === "summary") return o(ct, e);
			let c = Ae(e.field);
			return c === null ? Ce(r) ? Y(e.field, e.value, e.disabled, e.setValue, i, r) : o("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${String(e.field.meta?.fieldType ?? e.field.type)}`) : o("div", {
				class: "formie-vue-payment",
				innerHTML: c
			});
		};
	}
}), ft = a({
	name: "FormieVueConfigPageActions",
	setup() {
		let e = F();
		return () => {
			let t = e.state.value, n = e.instance.value;
			if (!t || !n) return null;
			let r = t.definition.pages.find((e) => e.id === t.currentPageId);
			if (!r) return null;
			let i = [];
			return r.actions.secondary.forEach((e) => {
				i.push(o("button", {
					key: e.type,
					type: "button",
					onClick: async (r) => {
						let i = r.currentTarget.closest("form"), a = t.currentPageId;
						await n.submit(e.type), requestAnimationFrame(() => S(i, a));
					}
				}, e.label));
			}), i.push(o("button", {
				key: r.actions.primary.type,
				type: "submit",
				disabled: !ee(t)
			}, r.actions.primary.label)), o("div", { class: "formie-page-actions" }, i);
		};
	}
}), pt = a({
	name: "FormieVueConfigRenderer",
	props: { className: {
		type: String,
		default: void 0
	} },
	setup(e) {
		let t = F();
		return () => {
			let n = t.instance.value, r = t.state.value;
			if (!n || !r) return null;
			let i = t.components.value.Form || Je, a = t.components.value.Page || Ye, s = t.components.value.ErrorSummary || Ge, c = r.definition.pages.find((e) => e.id === r.currentPageId && r.pageStates[e.id]?.hidden !== !0) || r.definition.pages.find((e) => r.pageStates[e.id]?.hidden !== !0) || r.definition.pages[0], l = r.lastSubmitResult?.messages.error, u = !!l && !r.errors.form.includes(l), d = !(r.lastSubmitResult?.completion?.behavior === "message" && r.lastSubmitResult.completion.hideForm);
			return c ? o(i, {
				definition: r.definition,
				session: r.session,
				state: r,
				className: e.className,
				onSubmit: () => n.submit()
			}, { default: () => [
				o(s, {
					key: "errors",
					errors: r.errors.form
				}),
				r.lastSubmitResult?.messages.notice ? o("div", {
					key: "notice",
					class: "formie-vue-notice",
					role: "status",
					tabindex: -1,
					"data-formie-form-message": !0,
					innerHTML: r.lastSubmitResult.messages.notice
				}) : null,
				u ? o("div", {
					key: "error",
					class: "formie-vue-error",
					role: "alert",
					tabindex: -1,
					"data-formie-form-message": !0
				}, l) : null,
				d && C(r.definition, r.currentPageId)?.position === "start" ? V(r) : null,
				d ? o(a, {
					key: c.id,
					page: c,
					state: r
				}, { default: () => [...c.rows.map((e, t) => o(U, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), o(ft, { key: "actions" })] }) : null,
				d && C(r.definition, r.currentPageId)?.position === "end" ? V(r) : null
			] }) : null;
		};
	}
}), mt = {
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
function X(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
var ht = a({
	name: "FormieVueDefinitionFormView",
	props: mt,
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
		let n = f(null), r = f(null), a = d(null), s = {
			instance: n,
			state: r,
			components: i(() => e.components || {}),
			fieldComponents: i(() => e.fieldComponents || {}),
			slots: i(() => e.slots || {})
		}, c = i(() => M(e.source));
		return u(N, s), p(c, (i, o, s) => {
			let c = !1, l = () => void 0;
			(async () => {
				try {
					let i = await He(e.source), o = Ue(e.source), s = ne({
						envelope: i,
						transport: o
					});
					if (c) {
						await s.destroy();
						return;
					}
					a.value = null, n.value = s, r.value = s.getState(), e.onMount?.(s), e.onReady?.(s), t("mount", s), t("ready", s);
					let u = [
						s.subscribe((e) => {
							r.value = e;
						}),
						s.on("formie:submit:result", (n) => {
							let r = n;
							X(e.onSubmitResult, e.onResult, r), t("result", r), t("submit-result", r), r.success ? (X(e.onSubmitSuccess, e.onSuccess, r), t("success", r), t("submit-success", r)) : (X(e.onSubmitError, e.onError, r), t("error", r), t("submit-error", r));
						}),
						...m.map((n) => s.on(n, (r) => {
							let i = {
								name: n,
								payload: r
							};
							e.onEvent?.(i), t("event", i);
						}))
					];
					l = () => {
						u.forEach((e) => e()), s.destroy(), n.value === s && (n.value = null, r.value = null), e.onUnmount?.(), t("unmount");
					};
				} catch (e) {
					c || (a.value = e);
				}
			})(), s(() => {
				c = !0, l();
			});
		}, { immediate: !0 }), () => a.value ? o("div", { class: "formie-vue-error" }, a.value.message) : !n.value || !r.value ? o("div", { class: "formie-vue-loading" }, "Loading form...") : o(qe, {}, { default: () => o(pt, { className: e.className }) });
	}
});
function gt() {
	let e = F();
	return {
		definition: i(() => e.state.value?.definition || null),
		session: i(() => e.state.value?.session || null),
		state: e.state,
		instance: e.instance
	};
}
function _t(e) {
	let t = F(), n = i(() => {
		let n = t.state.value?.definition;
		return n && We(n).find((t) => t.id === e) || null;
	});
	return {
		field: n,
		value: i(() => t.state.value?.values[e]),
		errors: i(() => t.state.value?.errors.fields[e] || []),
		hidden: i(() => t.state.value?.fieldStates[e]?.hidden === !0),
		disabled: i(() => t.state.value?.fieldStates[e]?.disabled === !0),
		setValue(e) {
			n.value && t.instance.value && t.instance.value.setValue(n.value.id, e);
		}
	};
}
function vt(e) {
	let t = F();
	return {
		page: i(() => t.state.value?.definition.pages.find((t) => t.id === e) || null),
		isCurrent: i(() => t.state.value?.currentPageId === e),
		hidden: i(() => t.state.value?.pageStates[e]?.hidden === !0)
	};
}
function yt() {
	return F().instance;
}
function bt(e) {
	let t = F();
	return i(() => t.slots.value[e] || null);
}
//#endregion
//#region src/index.ts
function Z(e) {
	return !!e && "payload" in e;
}
function xt(e) {
	return "success" in e ? e.success : e.ok;
}
function Q(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function St(e) {
	let t = e.transport;
	if (!t && !Z(e.source)) throw Error("`transport` is required for <FormieForm>.");
	return {
		mode: "server-rendered",
		transport: t,
		profile: e.profile,
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
function Ct(e) {
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
function wt() {
	return t();
}
function $() {
	return t();
}
function Tt(e) {
	let t = $(), n = d(null), r = f(null), a = d(null), o = i(() => M(e));
	return p([n, o], ([n], i, o) => {
		if (!n) return;
		let s = !1, c = !1, l = async () => {
			c || (c = !0, await t.unmount(n));
		}, u = Promise.resolve().then(async () => {
			if (!s) try {
				let i = await t.mount(n, {
					...e,
					mode: "server-rendered"
				});
				if (s) {
					await l();
					return;
				}
				r.value = i, a.value = null;
			} catch (e) {
				s || (a.value = e);
			}
		});
		o(() => {
			s = !0, r.value = null, u.finally(l);
		});
	}, { immediate: !0 }), {
		rootRef: n,
		state: {
			instance: r,
			isMounted: i(() => !!r.value),
			error: a
		},
		submit: async (e = "submit") => r.value ? r.value.submit(e) : null
	};
}
var Et = a({
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
	setup(n, { emit: r }) {
		let a = d(null), s = t(), c = i(() => St(n.options)), l = i(() => M(c.value));
		return p([a, l], ([t], i, a) => {
			if (!t) return;
			let o = !1, l = null, u = [], d = Promise.resolve().then(async () => {
				let i = await s.mount(t, c.value);
				o ? await s.unmount(t) : (l = i, n.options.onMount?.(i), n.options.onReady?.(i), r("mount", i), r("ready", i), u.push(i.on("formie:submit:result", (e) => {
					let t = e;
					Q(n.options.onSubmitResult, n.options.onResult, t), r("result", t), r("submit-result", t), xt(t) ? (Q(n.options.onSubmitSuccess, n.options.onSuccess, t), r("success", t), r("submit-success", t)) : (Q(n.options.onSubmitError, n.options.onError, t), r("error", t), r("submit-error", t));
				})), e.forEach((e) => {
					u.push(i.on(e, (t) => {
						let i = {
							name: e,
							payload: t
						};
						n.options.onEvent?.(i), r("event", i);
					}));
				}));
			});
			a(() => {
				o = !0, u.forEach((e) => e()), d.finally(async () => {
					await s.unmount(t), l &&= (n.options.onUnmount?.(), r("unmount"), null);
				});
			});
		}, { immediate: !0 }), () => o("div", {
			ref: a,
			class: n.options.className
		});
	}
}), Dt = {
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
}, Ot = {
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
}, kt = a({
	name: "FormieVueForm",
	props: Dt,
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
			return o(Et, {
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
}), At = a({
	name: "FormieVueClientForm",
	props: Ot,
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
		return () => o(ht, {
			source: Ct({
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
export { At as FormieClientForm, kt as FormieForm, wt as createVueFormieClient, gt as useFormie, $ as useFormieClient, _t as useFormieField, Tt as useFormieHtml, yt as useFormieInstance, vt as useFormiePage, bt as useFormieSlot };
