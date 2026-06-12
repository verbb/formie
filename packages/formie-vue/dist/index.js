import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t } from "@verbb/formie-browser";
import { computed as n, defineComponent as r, h as i, inject as a, onBeforeUnmount as o, onMounted as s, provide as c, ref as l, shallowRef as u, watch as d } from "vue";
import { FRONTEND_CLIENT_EVENT_NAMES as f, compositePartDefinitions as p, createFrontendFormInstance as m, createGraphqlFrontendTransport as h, createRepeaterRowValue as g, createRestFrontendTransport as _, isCompositeField as v, isFileField as y, isKnownFrontendFieldType as b, isRepeatableField as x, loadFrontendEnvelope as S, loadGraphqlFrontendEnvelope as ee, repeaterRowDefinitions as te } from "@verbb/formie-core";
//#region src/stable.ts
function C(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => C(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${C(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function w(e) {
	return C(e, /* @__PURE__ */ new WeakSet());
}
//#endregion
//#region src/definition-form.ts
var T = Symbol("formie-definition-context"), E = {
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
function D() {
	let e = a(T);
	if (!e) throw Error("Formie definition composables must be used within a client-rendered <FormieForm>.");
	return e;
}
function O(e) {
	return "definition" in e;
}
async function k(e) {
	return O(e) ? e.definition : e.transport === "graphql" ? ee({
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	}) : S({
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	});
}
function ne(e) {
	let t = O(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		formHandle: e.formHandle,
		siteId: e.siteId
	};
	return t.type === "graphql" ? h(t) : _(t);
}
function re(e) {
	return e.pages.flatMap((e) => e.rows).flatMap((e) => e.fields);
}
function A(e) {
	if (b(e.type)) return e.type;
	let t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
	return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function ie(e, t, n) {
	let r = new Set(e.moduleRefs || []);
	return t.modules.find((e) => r.has(e.id) && e.capability === n) || null;
}
function j(e, t, n, r) {
	if (!n) return null;
	let a = e.slots.value[t];
	return a ? i(a, {
		slotKey: t,
		attributes: r
	}, { default: () => [n] }) : n;
}
var M = r({
	name: "FormieVueDefaultErrorSummary",
	props: { errors: {
		type: Array,
		required: !0
	} },
	setup(e) {
		return () => e.errors.length === 0 ? null : i("div", { class: "formie-vue-errors" }, [i("ul", null, e.errors.map((e, t) => i("li", { key: `${e}:${t}` }, e)))]);
	}
}), N = r({
	name: "FormieVueDefaultField",
	props: {
		field: {
			type: Object,
			required: !0
		},
		errors: {
			type: Array,
			required: !0
		}
	},
	setup(e, t) {
		let n = D();
		return () => {
			let r = t.slots.default?.() || [];
			return i("div", {
				class: "formie-vue-field",
				"data-field-type": e.field.type
			}, [
				e.field.label ? j(n, "label", i("label", { class: "formie-vue-label" }, e.field.label), { class: "formie-vue-label" }) : null,
				e.field.instructions ? j(n, "instructions", i("div", {
					class: "formie-vue-description",
					innerHTML: e.field.instructions
				}), { class: "formie-vue-description" }) : null,
				j(n, "input", i("div", { class: "formie-vue-input" }, r), { class: "formie-vue-input" }),
				e.errors.length > 0 ? j(n, "errors", i("ul", { class: "formie-vue-field-errors" }, e.errors.map((e, t) => i("li", { key: `${e}:${t}` }, e))), { class: "formie-vue-field-errors" }) : null
			]);
		};
	}
}), P = r({
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
		return () => i("form", {
			class: e.className,
			onSubmit: (t) => {
				t.preventDefault(), e.onSubmit();
			},
			"data-formie-definition": e.definition.handle,
			"data-formie-render-id": e.session.tokens.render
		}, t.slots.default?.() || []);
	}
}), F = r({
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
		return () => i("section", {
			"data-page-id": e.page.id,
			class: "formie-vue-page"
		}, t.slots.default?.() || []);
	}
}), I = r({
	name: "FormieVueSignatureFieldInput",
	props: E,
	setup(e) {
		let t = D(), r = l(null), a = u(null), c = l(null), f = n(() => ie(e.field, t.state.value?.definition || { modules: [] }, "draw-signature")?.config), p = n(() => {
			let e = f.value?.options;
			return typeof e?.backgroundColor == "string" ? e.backgroundColor : "#ffffff";
		}), m = n(() => {
			let e = f.value?.options;
			return typeof e?.penColor == "string" ? e.penColor : "#000000";
		}), h = n(() => {
			let e = f.value?.options;
			return Number(e?.penWeight ?? 2) || 2;
		}), g = n(() => typeof e.value == "string" ? e.value : ""), _ = !1, v = () => void 0, y = () => void 0;
		return s(() => {
			(async () => {
				try {
					let t = r.value;
					if (!t) return;
					let { default: n } = await import("./signature_pad-D-mX46zR.js");
					if (_) return;
					let i = new n(t, {
						backgroundColor: p.value,
						penColor: m.value,
						minWidth: h.value,
						maxWidth: h.value
					}), o = () => {
						let e = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), n = Math.max(1, Math.floor(t.clientWidth || 480)), r = t.getContext("2d");
						t.width = n * e, t.height = 192 * e, t.style.height = "192px", r && (r.setTransform(1, 0, 0, 1, 0, 0), r.scale(e, e)), i.clear();
					}, s = () => {
						e.setValue(i.isEmpty() ? "" : i.toDataURL());
					};
					o(), i.addEventListener?.("endStroke", s), v = () => {
						i.removeEventListener?.("endStroke", s);
					}, typeof window < "u" && (window.addEventListener("resize", o), y = () => {
						window.removeEventListener("resize", o);
					}), a.value = i, c.value = null;
				} catch (e) {
					_ || (c.value = e.message || "Unable to load signature support.");
				}
			})();
		}), o(() => {
			_ = !0, v(), y(), a.value = null;
		}), d(g, (e) => {
			let t = a.value;
			if (t) {
				if (!e) {
					t.isEmpty() || t.clear();
					return;
				}
				try {
					t.fromDataURL(e);
				} catch {}
			}
		}, { immediate: !0 }), () => i("div", { class: "formie-vue-signature" }, [
			i("canvas", {
				key: "canvas",
				ref: r,
				"data-formie-signature-canvas": !0,
				style: e.disabled ? { pointerEvents: "none" } : void 0
			}),
			i("button", {
				key: "clear",
				type: "button",
				disabled: e.disabled,
				"data-formie-signature-clear": !0,
				onClick: () => {
					a.value?.clear(), e.setValue("");
				}
			}, "Clear"),
			c.value ? i("div", {
				key: "error",
				class: "formie-vue-unsupported"
			}, c.value) : null
		]);
	}
}), L = r({
	name: "FormieVueCompositeFieldInput",
	props: E,
	setup(e) {
		let t = D();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = p(e.field), a = e.value && typeof e.value == "object" ? e.value : {};
			return r.length === 0 ? i("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${e.field.type}`) : i("div", { class: "formie-vue-name-grid" }, r.filter((e) => e.meta?.hidden !== !0).map((t) => {
				let r = `${e.errorKey}.${t.handle}`;
				return i(z, {
					key: `${e.field.id}:${t.handle}`,
					field: t,
					value: a[t.handle],
					errors: n.errors.fields[r] || [],
					errorKey: r,
					disabled: e.disabled || t.meta?.disabled === !0,
					hidden: !1,
					setValue(n) {
						e.setValue({
							...a,
							[t.handle]: n
						});
					}
				});
			}));
		};
	}
}), R = r({
	name: "FormieVueFileFieldInput",
	props: E,
	setup(e) {
		return () => {
			let t = e.field.input, n = Array.isArray(e.value) ? e.value : [], r = t.multiple === !0, a = n.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
			return i("div", { class: "formie-vue-file" }, [i("input", {
				key: "input",
				type: "file",
				disabled: e.disabled,
				multiple: r,
				onChange: (t) => {
					let n = t.target;
					e.setValue(Array.from(n.files || []));
				}
			}), a.length > 0 ? i("ul", {
				key: "summary",
				class: "formie-vue-field-errors"
			}, a.map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null]);
		};
	}
}), z = r({
	name: "FormieVueConfigFieldNode",
	props: E,
	setup(e) {
		let t = D();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = n.fieldStates[e.field.id]?.hidden === !0;
			if (r) return null;
			let a = A(e.field), o = t.fieldComponents.value[e.field.type] || t.fieldComponents.value[a] || W;
			return i(t.components.value.Field || N, {
				field: e.field,
				errors: e.errors
			}, { default: () => [i(o, {
				field: e.field,
				value: e.value,
				errors: e.errors,
				errorKey: e.errorKey,
				disabled: e.disabled,
				hidden: r,
				setValue: e.setValue
			})] });
		};
	}
}), B = r({
	name: "FormieVueConfigField",
	props: { field: {
		type: Object,
		required: !0
	} },
	setup(e) {
		let t = D();
		return () => {
			let n = t.state.value, r = t.instance.value;
			if (!n || !r) return null;
			let a = n.fieldStates[e.field.id];
			return i(z, {
				field: e.field,
				value: n.values[e.field.id],
				errors: n.errors.fields[e.field.id] || [],
				errorKey: e.field.id,
				disabled: a?.disabled === !0,
				hidden: a?.hidden === !0,
				setValue(t) {
					r.setValue(e.field.id, t);
				}
			});
		};
	}
}), V = r({
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
		let t = D();
		return () => {
			let n = t.state.value;
			return n ? i("div", { class: "formie-vue-row" }, e.row.fields.map((t, r) => {
				if (!e.values || !e.setFieldValue) return i(B, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t
				});
				let a = `${e.errorPrefix}.${t.handle}`;
				return i(z, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t,
					value: e.values[t.handle],
					errors: n.errors.fields[a] || [],
					errorKey: a,
					disabled: e.disabled === !0 || n.fieldStates[t.id]?.disabled === !0,
					hidden: n.fieldStates[t.id]?.hidden === !0,
					setValue(n) {
						e.setFieldValue?.(t, n);
					}
				});
			})) : null;
		};
	}
}), H = r({
	name: "FormieVueRepeaterFieldInput",
	props: E,
	setup(e) {
		let t = D();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = te(e.field), a = Array.isArray(e.value) ? e.value : [], o = e.field.input, s = Number(o.minRows ?? 0) || 0, c = Number(o.maxRows ?? 0) || 0, l = !e.disabled && (c <= 0 || a.length < c);
			return r.length === 0 ? i("div", { class: "formie-vue-unsupported" }, "Unsupported repeater field.") : i("div", {
				class: "formie-vue-repeater",
				"data-formie-repeater-container": !0
			}, [
				...a.map((t, n) => {
					let o = `${e.field.id}:${n}`;
					return i("div", {
						key: o,
						class: "formie-vue-repeater-item",
						"data-formie-repeater-item": !0
					}, [...r.map((r, s) => i(V, {
						key: `${o}:${s}`,
						row: r,
						rowIndex: s,
						values: t,
						errorPrefix: `${e.errorKey}.${n}`,
						disabled: e.disabled,
						setFieldValue(t, r) {
							let i = a.map((e, i) => i === n ? {
								...e,
								[t.handle]: r
							} : e);
							e.setValue(i);
						}
					})), i("button", {
						key: "remove",
						type: "button",
						disabled: e.disabled || s > 0 && a.length <= s,
						"data-formie-repeater-remove": !0,
						onClick: () => {
							e.setValue(a.filter((e, t) => t !== n));
						}
					}, "Remove")]);
				}),
				i("button", {
					key: "add",
					type: "button",
					disabled: !l,
					"data-formie-repeater-add": e.field.handle,
					onClick: () => {
						e.setValue([...a, g(e.field)]);
					}
				}, String(o.addLabel ?? "Add another row")),
				n.errors.fields[e.errorKey] && n.errors.fields[e.errorKey].length > 0 ? i("ul", {
					key: "errors",
					class: "formie-vue-field-errors"
				}, n.errors.fields[e.errorKey].map((e, t) => i("li", { key: `${e}:${t}` }, e))) : null
			]);
		};
	}
});
function U(e, t, n, r) {
	let a = e.input;
	if (e.type === "multi-line-text") return i("textarea", {
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof a.placeholder == "string" ? a.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (e.type === "dropdown") {
		let o = Array.isArray(a.options) ? a.options : [], s = a.multiple === !0;
		return i("select", {
			value: s ? void 0 : typeof t == "string" ? t : "",
			disabled: n,
			multiple: s,
			onChange: (e) => {
				let t = e.target;
				if (s) {
					r(Array.from(t.selectedOptions).map((e) => e.value));
					return;
				}
				r(t.value);
			}
		}, o.map((t) => {
			let n = String(t.value ?? "");
			return i("option", {
				key: `${e.id}:${n}`,
				value: n,
				disabled: t.disabled === !0
			}, String(t.label ?? n));
		}));
	}
	let o = typeof a.inputType == "string" ? a.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return i("input", {
		type: o,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		placeholder: typeof a.placeholder == "string" ? a.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			if (o === "number") {
				let e = t.valueAsNumber;
				r(Number.isFinite(e) ? e : "");
				return;
			}
			r(t.value);
		}
	});
}
var W = r({
	name: "FormieVueDefaultFieldInput",
	props: E,
	setup(e) {
		return () => {
			let t = e.field.input, n = A(e.field);
			if (v(e.field)) return i(L, e);
			if (x(e.field)) return i(H, e);
			if (y(e.field)) return i(R, e);
			if (n === "signature") return i(I, e);
			if (n === "multi-line-text" || n === "dropdown") return U(e.field, e.value, e.disabled, e.setValue);
			if (n === "radio") return i("div", { class: "formie-vue-choices" }, (Array.isArray(t.options) ? t.options : []).map((t) => {
				let n = String(t.value ?? ""), r = e.disabled || t.disabled === !0;
				return i("label", { key: `${e.field.id}:${n}` }, [i("input", {
					key: "input",
					type: "radio",
					checked: e.value === n,
					disabled: r,
					onChange: () => {
						e.setValue(n);
					}
				}), i("span", { key: "label" }, String(t.label ?? n))]);
			}));
			if (n === "checkboxes") {
				let n = Array.isArray(t.options) ? t.options : [], r = Array.isArray(e.value) ? e.value.map((e) => String(e)) : [];
				return i("div", { class: "formie-vue-choices" }, n.map((t) => {
					let n = String(t.value ?? ""), a = r.includes(n), o = e.disabled || t.disabled === !0;
					return i("label", { key: `${e.field.id}:${n}` }, [i("input", {
						key: "input",
						type: "checkbox",
						checked: a,
						disabled: o,
						onChange: () => {
							let t = a ? r.filter((e) => e !== n) : [...r, n];
							e.setValue(t);
						}
					}), i("span", { key: "label" }, String(t.label ?? n))]);
				}));
			}
			if (n === "agree") {
				let n = typeof t.descriptionHtml == "string" ? t.descriptionHtml : null;
				return i("label", { class: "formie-vue-boolean" }, [i("input", {
					key: "input",
					type: "checkbox",
					checked: e.value === !0,
					disabled: e.disabled,
					onChange: (t) => {
						let n = t.target;
						e.setValue(n.checked);
					}
				}), n ? i("span", {
					key: "description",
					innerHTML: n
				}) : i("span", { key: "description" }, e.field.label)]);
			}
			return b(n) ? U(e.field, e.value, e.disabled, e.setValue) : i("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${String(e.field.meta?.fieldType ?? e.field.type)}`);
		};
	}
}), G = r({
	name: "FormieVueConfigPageActions",
	setup() {
		let e = D();
		return () => {
			let t = e.state.value, n = e.instance.value;
			if (!t || !n) return null;
			let r = t.definition.pages.find((e) => e.id === t.currentPageId);
			if (!r) return null;
			let a = [];
			return r.actions.secondary.forEach((e) => {
				a.push(i("button", {
					key: e.type,
					type: "button",
					onClick: () => {
						n.submit(e.type);
					}
				}, e.label));
			}), a.push(i("button", {
				key: r.actions.primary.type,
				type: "submit"
			}, r.actions.primary.label)), i("div", { class: "formie-page-actions" }, a);
		};
	}
}), K = r({
	name: "FormieVueConfigRenderer",
	props: { className: {
		type: String,
		default: void 0
	} },
	setup(e) {
		let t = D();
		return () => {
			let n = t.instance.value, r = t.state.value;
			if (!n || !r) return null;
			let a = t.components.value.Form || P, o = t.components.value.Page || F, s = t.components.value.ErrorSummary || M, c = r.definition.pages.find((e) => e.id === r.currentPageId && r.pageStates[e.id]?.hidden !== !0) || r.definition.pages.find((e) => r.pageStates[e.id]?.hidden !== !0) || r.definition.pages[0], l = r.lastSubmitResult?.messages.error, u = !!l && !r.errors.form.includes(l);
			return c ? i(a, {
				definition: r.definition,
				session: r.session,
				state: r,
				className: e.className,
				onSubmit: () => {
					n.submit();
				}
			}, { default: () => [
				i(s, {
					key: "errors",
					errors: r.errors.form
				}),
				r.lastSubmitResult?.messages.notice ? i("div", {
					key: "notice",
					class: "formie-vue-notice"
				}, r.lastSubmitResult.messages.notice) : null,
				u ? i("div", {
					key: "error",
					class: "formie-vue-error"
				}, l) : null,
				i(o, {
					key: c.id,
					page: c,
					state: r
				}, { default: () => [...c.rows.map((e, t) => i(V, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), i(G, { key: "actions" })] })
			] }) : null;
		};
	}
}), q = {
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
function J(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
var Y = r({
	name: "FormieVueDefinitionFormView",
	props: q,
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
		let r = u(null), a = u(null), o = l(null), s = {
			instance: r,
			state: a,
			components: n(() => e.components || {}),
			fieldComponents: n(() => e.fieldComponents || {}),
			slots: n(() => e.slots || {})
		}, p = n(() => w(e.source));
		return c(T, s), d(p, (n, i, s) => {
			let c = !1, l = () => void 0;
			(async () => {
				try {
					let n = m({
						envelope: await k(e.source),
						transport: ne(e.source)
					});
					if (c) {
						await n.destroy();
						return;
					}
					o.value = null, r.value = n, a.value = n.getState(), e.onMount?.(n), e.onReady?.(n), t("mount", n), t("ready", n);
					let i = [
						n.subscribe((e) => {
							a.value = e;
						}),
						n.on("formie:submit:result", (n) => {
							let r = n;
							J(e.onSubmitResult, e.onResult, r), t("result", r), t("submit-result", r), r.success ? (J(e.onSubmitSuccess, e.onSuccess, r), t("success", r), t("submit-success", r)) : (J(e.onSubmitError, e.onError, r), t("error", r), t("submit-error", r));
						}),
						...f.map((r) => n.on(r, (n) => {
							let i = {
								name: r,
								payload: n
							};
							e.onEvent?.(i), t("event", i);
						}))
					];
					l = () => {
						i.forEach((e) => e()), n.destroy(), r.value === n && (r.value = null, a.value = null), e.onUnmount?.(), t("unmount");
					};
				} catch (e) {
					c || (o.value = e);
				}
			})(), s(() => {
				c = !0, l();
			});
		}, { immediate: !0 }), () => o.value ? i("div", { class: "formie-vue-error" }, o.value.message) : !r.value || !a.value ? i("div", { class: "formie-vue-loading" }, "Loading form...") : i(K, { className: e.className });
	}
});
function ae() {
	let e = D();
	return {
		definition: n(() => e.state.value?.definition || null),
		session: n(() => e.state.value?.session || null),
		state: e.state,
		instance: e.instance
	};
}
function oe(e) {
	let t = D(), r = n(() => {
		let n = t.state.value?.definition;
		return n && re(n).find((t) => t.id === e) || null;
	});
	return {
		field: r,
		value: n(() => t.state.value?.values[e]),
		errors: n(() => t.state.value?.errors.fields[e] || []),
		hidden: n(() => t.state.value?.fieldStates[e]?.hidden === !0),
		disabled: n(() => t.state.value?.fieldStates[e]?.disabled === !0),
		setValue(e) {
			!r.value || !t.instance.value || t.instance.value.setValue(r.value.id, e);
		}
	};
}
function se(e) {
	let t = D();
	return {
		page: n(() => t.state.value?.definition.pages.find((t) => t.id === e) || null),
		isCurrent: n(() => t.state.value?.currentPageId === e),
		hidden: n(() => t.state.value?.pageStates[e]?.hidden === !0)
	};
}
function ce() {
	return D().instance;
}
function le(e) {
	let t = D();
	return n(() => t.slots.value[e] || null);
}
//#endregion
//#region src/index.ts
function X(e) {
	return !!e && "payload" in e;
}
function ue(e) {
	return "success" in e ? e.success : e.ok;
}
function Z(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function de(e) {
	let t = e.transport;
	if (!t && !X(e.source)) throw Error("`transport` is required for <FormieForm>.");
	return {
		mode: "server-rendered",
		transport: t,
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
function fe(e) {
	if (e.source) return e.source;
	let t = e.transport, n = e.endpoint, r = e.formHandle;
	if (t !== "rest" && t !== "graphql") throw Error("Vue client-rendered forms require `transport=\"rest\"` or `transport=\"graphql\"`.");
	if (!n || !r) throw Error("Vue client-rendered forms require either `source` or both `endpoint` and `formHandle`.");
	return {
		transport: t,
		endpoint: n,
		formHandle: r,
		siteId: e.siteId
	};
}
function pe() {
	return t();
}
function Q() {
	return t();
}
function $(e) {
	let t = Q(), r = l(null), i = u(null), a = l(null);
	return d([r, n(() => w(e))], ([n], r, o) => {
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
		rootRef: r,
		state: {
			instance: i,
			isMounted: n(() => !!i.value),
			error: a
		},
		submit: async (e = "submit") => i.value ? i.value.submit(e) : null
	};
}
var me = r({
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
	setup(r, { emit: a }) {
		let o = l(null), s = t(), c = n(() => de(r.options));
		return d([o, n(() => w(c.value))], ([t], n, i) => {
			if (!t) return;
			let o = !1, l = null, u = [], d = Promise.resolve().then(async () => {
				let n = await s.mount(t, c.value);
				if (o) {
					await s.unmount(t);
					return;
				}
				l = n, r.options.onMount?.(n), r.options.onReady?.(n), a("mount", n), a("ready", n), u.push(n.on("formie:submit:result", (e) => {
					let t = e;
					Z(r.options.onSubmitResult, r.options.onResult, t), a("result", t), a("submit-result", t), ue(t) ? (Z(r.options.onSubmitSuccess, r.options.onSuccess, t), a("success", t), a("submit-success", t)) : (Z(r.options.onSubmitError, r.options.onError, t), a("error", t), a("submit-error", t));
				})), e.forEach((e) => {
					u.push(n.on(e, (t) => {
						let n = {
							name: e,
							payload: t
						};
						r.options.onEvent?.(n), a("event", n);
					}));
				});
			});
			i(() => {
				o = !0, u.forEach((e) => e()), d.finally(async () => {
					await s.unmount(t), l &&= (r.options.onUnmount?.(), a("unmount"), null);
				});
			});
		}, { immediate: !0 }), () => i("div", {
			ref: o,
			class: r.options.className
		});
	}
}), he = {
	source: {
		type: Object,
		default: void 0
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
}, ge = {
	source: {
		type: Object,
		default: void 0
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
}, _e = r({
	name: "FormieVueForm",
	props: he,
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
		return () => i(me, {
			options: {
				source: e.source,
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
			},
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
	}
}), ve = r({
	name: "FormieVueClientForm",
	props: ge,
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
		return () => i(Y, {
			source: fe({
				source: e.source,
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
export { ve as FormieClientForm, _e as FormieForm, pe as createVueFormieClient, ae as useFormie, Q as useFormieClient, oe as useFormieField, $ as useFormieHtml, ce as useFormieInstance, se as useFormiePage, le as useFormieSlot };
