import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, mountClientRenderedModules as n } from "@verbb/formie-browser";
import { computed as r, defineComponent as i, h as a, inject as o, onBeforeUnmount as s, onMounted as c, provide as l, ref as u, shallowRef as d, watch as f } from "vue";
import { CLIENT_FORM_EVENT_NAMES as p, compositePartDefinitions as m, createClientFormInstance as h, createGraphqlClientTransport as g, createRepeaterRowValue as _, createRestClientTransport as v, getClientErrorAriaLive as y, getClientFieldErrorId as b, isCompositeField as x, isFileField as S, isKnownClientFieldType as C, isRepeatableField as w, loadClientFormBootstrap as T, loadGraphqlClientFormBootstrap as ee, repeaterRowDefinitions as te } from "@verbb/formie-core";
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
//#region src/definition-form.ts
var O = Symbol("formie-definition-context"), k = {
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
function A() {
	let e = o(O);
	if (!e) throw Error("Formie definition composables must be used within a client-rendered <FormieForm>.");
	return e;
}
function j(e) {
	return "definition" in e;
}
async function ne(e) {
	return j(e) ? e.definition : e.transport === "graphql" ? ee({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId
	}) : T({
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId
	});
}
function re(e) {
	let t = j(e) ? e.transport : {
		type: e.transport,
		endpoint: e.endpoint,
		profile: e.profile,
		formHandle: e.formHandle,
		siteId: e.siteId
	};
	return t.type === "graphql" ? g(t) : v(t);
}
function ie(e) {
	return e.pages.flatMap((e) => e.rows).flatMap((e) => e.fields);
}
function M(e) {
	if (C(e.type)) return e.type;
	let t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
	return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function ae(e, t, n) {
	return new Set(e.moduleRefs || []), t.modules.entries.find((t) => t.targets.some((t) => t.targetType === "field" && t.targetId === e.uid) && t.capability === (n === "draw-signature" ? "signature" : n)) || null;
}
function N(e, t, n, r) {
	if (!n) return null;
	let i = e.slots.value[t];
	return i ? a(i, {
		slotKey: t,
		attributes: r
	}, { default: () => [n] }) : n;
}
var P = i({
	name: "FormieVueDefaultErrorSummary",
	props: {
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
		}
	},
	setup(e) {
		return () => e.errors.length === 0 ? null : a("div", { class: "formie-vue-errors" }, [a("ul", null, e.errors.map((e, t) => a("li", { key: `${e}:${t}` }, e)))]);
	}
}), F = i({
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
		}
	},
	setup(e, t) {
		let n = A();
		return () => {
			let r = t.slots.default?.() || [];
			return a("div", {
				class: "formie-vue-field",
				"data-formie-field-uid": e.field.uid,
				"data-formie-field-handle": e.field.handle,
				"data-formie-field-type": e.field.type
			}, [
				e.field.label ? N(n, "label", a("label", { class: "formie-vue-label" }, e.field.label), { class: "formie-vue-label" }) : null,
				e.field.instructions ? N(n, "instructions", a("div", {
					class: "formie-vue-description",
					innerHTML: e.field.instructions
				}), { class: "formie-vue-description" }) : null,
				N(n, "input", a("div", { class: "formie-vue-input" }, r), { class: "formie-vue-input" }),
				N(n, "errors", a("ul", {
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
				})
			]);
		};
	}
}), I = i({ setup(e, { slots: t }) {
	let r = A(), i = u(null), o = !1, l;
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
} }), L = i({
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
				let n = t.currentTarget;
				await e.onSubmit(), requestAnimationFrame(() => n.querySelector("[aria-invalid=\"true\"]")?.focus());
			},
			"data-formie-definition": e.definition.handle,
			"data-formie-render-id": e.session.tokens.render
		}, t.slots.default?.() || []);
	}
}), R = i({
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
		return () => a("section", {
			"data-page-id": e.page.id,
			class: "formie-vue-page"
		}, t.slots.default?.() || []);
	}
}), z = i({
	name: "FormieVueSignatureFieldInput",
	props: k,
	setup(e) {
		let t = A(), n = u(null), i = d(null), o = u(null), l = r(() => ae(e.field, t.state.value?.definition || { modules: {
			contractVersion: 1,
			entries: []
		} }, "draw-signature")?.config), p = r(() => {
			let e = l.value?.options;
			return typeof e?.backgroundColor == "string" ? e.backgroundColor : "#ffffff";
		}), m = r(() => {
			let e = l.value?.options;
			return typeof e?.penColor == "string" ? e.penColor : "#000000";
		}), h = r(() => {
			let e = l.value?.options;
			return Number(e?.penWeight ?? 2) || 2;
		}), g = r(() => typeof e.value == "string" ? e.value : ""), _ = !1, v = () => void 0, y = () => void 0;
		return c(() => {
			(async () => {
				try {
					let t = n.value;
					if (!t) return;
					let { default: r } = await import("./signature_pad-dbpVgfTh.js");
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
				if (!e) {
					t.isEmpty() || t.clear();
					return;
				}
				try {
					t.fromDataURL(e);
				} catch {}
			}
		}, { immediate: !0 }), () => a("div", { class: "formie-vue-signature" }, [
			a("canvas", {
				key: "canvas",
				ref: n,
				"data-formie-signature-canvas": !0,
				style: e.disabled ? { pointerEvents: "none" } : void 0
			}),
			a("button", {
				key: "clear",
				type: "button",
				disabled: e.disabled,
				"data-formie-signature-clear": !0,
				onClick: () => {
					i.value?.clear(), e.setValue("");
				}
			}, "Clear"),
			o.value ? a("div", {
				key: "error",
				class: "formie-vue-unsupported"
			}, o.value) : null
		]);
	}
}), B = i({
	name: "FormieVueCompositeFieldInput",
	props: k,
	setup(e) {
		let t = A();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = m(e.field), i = e.value && typeof e.value == "object" ? e.value : {};
			return r.length === 0 ? a("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${e.field.type}`) : a("div", { class: "formie-vue-name-grid" }, r.filter((e) => e.meta?.hidden !== !0).map((t) => {
				let r = `${e.errorKey}.${t.handle}`;
				return a(H, {
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
}), V = i({
	name: "FormieVueFileFieldInput",
	props: k,
	setup(e) {
		return () => {
			let t = e.field.input, n = Array.isArray(e.value) ? e.value : [], r = t.multiple === !0, i = n.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
			return a("div", { class: "formie-vue-file" }, [a("input", {
				key: "input",
				type: "file",
				...K(e.errors, e.errorId),
				disabled: e.disabled,
				multiple: r,
				onChange: (t) => {
					let n = t.target;
					e.setValue(Array.from(n.files || []));
				}
			}), i.length > 0 ? a("ul", {
				key: "summary",
				class: "formie-vue-field-errors"
			}, i.map((e, t) => a("li", { key: `${e}:${t}` }, e))) : null]);
		};
	}
}), H = i({
	name: "FormieVueConfigFieldNode",
	props: k,
	setup(e) {
		let t = A();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = n.fieldStates[e.field.id]?.hidden === !0;
			if (r) return null;
			let i = M(e.field), o = t.fieldComponents.value[e.field.type] || t.fieldComponents.value[i] || J, s = t.components.value.Field || F, c = b(n.session, e.errorKey), l = y(n.definition);
			return a(s, {
				field: e.field,
				errors: e.errors,
				errorId: c,
				errorAriaLive: l
			}, { default: () => [a(o, {
				field: e.field,
				value: e.value,
				errors: e.errors,
				errorKey: e.errorKey,
				errorId: c,
				errorAriaLive: l,
				disabled: e.disabled,
				hidden: r,
				setValue: e.setValue
			})] });
		};
	}
}), U = i({
	name: "FormieVueConfigField",
	props: { field: {
		type: Object,
		required: !0
	} },
	setup(e) {
		let t = A();
		return () => {
			let n = t.state.value, r = t.instance.value;
			if (!n || !r) return null;
			let i = n.fieldStates[e.field.id];
			return a(H, {
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
}), W = i({
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
		let t = A();
		return () => {
			let n = t.state.value;
			return n ? a("div", { class: "formie-vue-row" }, e.row.fields.map((t, r) => {
				if (!e.values || !e.setFieldValue) return a(U, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t
				});
				let i = `${e.errorPrefix}.${t.handle}`;
				return a(H, {
					key: t.id || `${e.rowIndex}:${r}`,
					field: t,
					value: e.values[t.handle],
					errors: n.errors.fields[i] || [],
					errorKey: i,
					disabled: e.disabled === !0 || n.fieldStates[t.id]?.disabled === !0,
					hidden: n.fieldStates[t.id]?.hidden === !0,
					setValue(n) {
						e.setFieldValue?.(t, n);
					}
				});
			})) : null;
		};
	}
}), G = i({
	name: "FormieVueRepeaterFieldInput",
	props: k,
	setup(e) {
		let t = A();
		return () => {
			let n = t.state.value;
			if (!n) return null;
			let r = te(e.field), i = Array.isArray(e.value) ? e.value : [], o = e.field.input, s = Number(o.minRows ?? 0) || 0, c = Number(o.maxRows ?? 0) || 0, l = !e.disabled && (c <= 0 || i.length < c);
			return r.length === 0 ? a("div", { class: "formie-vue-unsupported" }, "Unsupported repeater field.") : a("div", {
				class: "formie-vue-repeater",
				"data-formie-repeater-container": !0
			}, [
				...i.map((t, n) => {
					let o = `${e.field.id}:${n}`;
					return a("div", {
						key: o,
						class: "formie-vue-repeater-item",
						"data-formie-repeater-item": !0
					}, [...r.map((r, s) => a(W, {
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
						"data-formie-repeater-remove": !0,
						onClick: () => {
							e.setValue(i.filter((e, t) => t !== n));
						}
					}, "Remove")]);
				}),
				a("button", {
					key: "add",
					type: "button",
					disabled: !l,
					"data-formie-repeater-add": e.field.handle,
					onClick: () => {
						e.setValue([...i, _(e.field)]);
					}
				}, String(o.addLabel ?? "Add another row")),
				n.errors.fields[e.errorKey] && n.errors.fields[e.errorKey].length > 0 ? a("ul", {
					key: "errors",
					class: "formie-vue-field-errors"
				}, n.errors.fields[e.errorKey].map((e, t) => a("li", { key: `${e}:${t}` }, e))) : null
			]);
		};
	}
});
function K(e, t) {
	return e.length > 0 ? {
		"aria-invalid": "true",
		"aria-errormessage": t,
		"aria-describedby": t
	} : {};
}
function q(e, t, n, r, i = [], o = "") {
	let s = e.input;
	if (e.type === "multi-line-text") return a("textarea", {
		"aria-label": e.label || e.handle,
		...K(i, o),
		value: typeof t == "string" ? t : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			r(t.value);
		}
	});
	if (e.type === "dropdown") {
		let c = Array.isArray(s.options) ? s.options : [], l = s.multiple === !0;
		return a("select", {
			"aria-label": e.label || e.handle,
			...K(i, o),
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
			return a("option", {
				key: `${e.id}:${n}`,
				value: n,
				disabled: t.disabled === !0
			}, String(t.label ?? n));
		}));
	}
	let c = typeof s.inputType == "string" ? s.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return a("input", {
		"aria-label": e.label || e.handle,
		...K(i, o),
		type: c,
		value: typeof t == "string" || typeof t == "number" ? String(t) : "",
		disabled: n,
		placeholder: typeof s.placeholder == "string" ? s.placeholder : void 0,
		onInput: (e) => {
			let t = e.target;
			if (c === "number") {
				let e = t.valueAsNumber;
				r(Number.isFinite(e) ? e : "");
				return;
			}
			r(t.value);
		}
	});
}
var J = i({
	name: "FormieVueDefaultFieldInput",
	props: k,
	setup(e) {
		return () => {
			let t = e.field.input, n = M(e.field);
			if (x(e.field)) return a(B, e);
			if (w(e.field)) return a(G, e);
			if (S(e.field)) return a(V, e);
			if (n === "signature") return a(z, e);
			if (n === "multi-line-text" || n === "dropdown") return q(e.field, e.value, e.disabled, e.setValue, e.errors, e.errorId);
			if (n === "radio") {
				let n = Array.isArray(t.options) ? t.options : [];
				return a("div", { class: "formie-vue-choices" }, n.map((t) => {
					let n = String(t.value ?? ""), r = e.disabled || t.disabled === !0;
					return a("label", { key: `${e.field.id}:${n}` }, [a("input", {
						key: "input",
						type: "radio",
						...K(e.errors, e.errorId),
						checked: e.value === n,
						disabled: r,
						onChange: () => {
							e.setValue(n);
						}
					}), a("span", { key: "label" }, String(t.label ?? n))]);
				}));
			}
			if (n === "checkboxes") {
				let n = Array.isArray(t.options) ? t.options : [], r = Array.isArray(e.value) ? e.value.map((e) => String(e)) : [];
				return a("div", { class: "formie-vue-choices" }, n.map((t) => {
					let n = String(t.value ?? ""), i = r.includes(n), o = e.disabled || t.disabled === !0;
					return a("label", { key: `${e.field.id}:${n}` }, [a("input", {
						key: "input",
						type: "checkbox",
						...K(e.errors, e.errorId),
						checked: i,
						disabled: o,
						onChange: () => {
							let t = i ? r.filter((e) => e !== n) : [...r, n];
							e.setValue(t);
						}
					}), a("span", { key: "label" }, String(t.label ?? n))]);
				}));
			}
			if (n === "agree") {
				let n = typeof t.descriptionHtml == "string" ? t.descriptionHtml : null;
				return a("label", { class: "formie-vue-boolean" }, [a("input", {
					key: "input",
					type: "checkbox",
					...K(e.errors, e.errorId),
					checked: e.value === !0,
					disabled: e.disabled,
					onChange: (t) => {
						let n = t.target;
						e.setValue(n.checked);
					}
				}), n ? a("span", {
					key: "description",
					innerHTML: n
				}) : a("span", { key: "description" }, e.field.label)]);
			}
			return C(n) ? q(e.field, e.value, e.disabled, e.setValue, e.errors, e.errorId) : a("div", { class: "formie-vue-unsupported" }, `Unsupported field type: ${String(e.field.meta?.fieldType ?? e.field.type)}`);
		};
	}
}), oe = i({
	name: "FormieVueConfigPageActions",
	setup() {
		let e = A();
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
					onClick: () => {
						n.submit(e.type);
					}
				}, e.label));
			}), i.push(a("button", {
				key: r.actions.primary.type,
				type: "submit"
			}, r.actions.primary.label)), a("div", { class: "formie-page-actions" }, i);
		};
	}
}), se = i({
	name: "FormieVueConfigRenderer",
	props: { className: {
		type: String,
		default: void 0
	} },
	setup(e) {
		let t = A();
		return () => {
			let n = t.instance.value, r = t.state.value;
			if (!n || !r) return null;
			let i = t.components.value.Form || L, o = t.components.value.Page || R, s = t.components.value.ErrorSummary || P, c = r.definition.pages.find((e) => e.id === r.currentPageId && r.pageStates[e.id]?.hidden !== !0) || r.definition.pages.find((e) => r.pageStates[e.id]?.hidden !== !0) || r.definition.pages[0], l = r.lastSubmitResult?.messages.error, u = !!l && !r.errors.form.includes(l);
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
					class: "formie-vue-notice"
				}, r.lastSubmitResult.messages.notice) : null,
				u ? a("div", {
					key: "error",
					class: "formie-vue-error"
				}, l) : null,
				a(o, {
					key: c.id,
					page: c,
					state: r
				}, { default: () => [...c.rows.map((e, t) => a(W, {
					key: `${c.id}:${t}`,
					row: e,
					rowIndex: t
				})), a(oe, { key: "actions" })] })
			] }) : null;
		};
	}
}), ce = {
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
var le = i({
	name: "FormieVueDefinitionFormView",
	props: ce,
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
		}, c = r(() => D(e.source));
		return l(O, s), f(c, (r, a, s) => {
			let c = !1, l = () => void 0;
			(async () => {
				try {
					let r = await ne(e.source), a = re(e.source), s = h({
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
		}, { immediate: !0 }), () => o.value ? a("div", { class: "formie-vue-error" }, o.value.message) : !n.value || !i.value ? a("div", { class: "formie-vue-loading" }, "Loading form...") : a(I, {}, { default: () => a(se, { className: e.className }) });
	}
});
function ue() {
	let e = A();
	return {
		definition: r(() => e.state.value?.definition || null),
		session: r(() => e.state.value?.session || null),
		state: e.state,
		instance: e.instance
	};
}
function de(e) {
	let t = A(), n = r(() => {
		let n = t.state.value?.definition;
		return n && ie(n).find((t) => t.id === e) || null;
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
function fe(e) {
	let t = A();
	return {
		page: r(() => t.state.value?.definition.pages.find((t) => t.id === e) || null),
		isCurrent: r(() => t.state.value?.currentPageId === e),
		hidden: r(() => t.state.value?.pageStates[e]?.hidden === !0)
	};
}
function pe() {
	return A().instance;
}
function me(e) {
	let t = A();
	return r(() => t.slots.value[e] || null);
}
//#endregion
//#region src/index.ts
function X(e) {
	return !!e && "payload" in e;
}
function he(e) {
	return "success" in e ? e.success : e.ok;
}
function Z(e, t, ...n) {
	e?.(...n), t && t !== e && t(...n);
}
function ge(e) {
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
function _e(e) {
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
function Q() {
	return t();
}
function $() {
	return t();
}
function ve(e) {
	let t = $(), n = u(null), i = d(null), a = u(null), o = r(() => D(e));
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
var ye = i({
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
		let o = u(null), s = t(), c = r(() => ge(n.options)), l = r(() => D(c.value));
		return f([o, l], ([t], r, a) => {
			if (!t) return;
			let o = !1, l = null, u = [], d = Promise.resolve().then(async () => {
				let r = await s.mount(t, c.value);
				if (o) {
					await s.unmount(t);
					return;
				}
				l = r, n.options.onMount?.(r), n.options.onReady?.(r), i("mount", r), i("ready", r), u.push(r.on("formie:submit:result", (e) => {
					let t = e;
					Z(n.options.onSubmitResult, n.options.onResult, t), i("result", t), i("submit-result", t), he(t) ? (Z(n.options.onSubmitSuccess, n.options.onSuccess, t), i("success", t), i("submit-success", t)) : (Z(n.options.onSubmitError, n.options.onError, t), i("error", t), i("submit-error", t));
				})), e.forEach((e) => {
					u.push(r.on(e, (t) => {
						let r = {
							name: e,
							payload: t
						};
						n.options.onEvent?.(r), i("event", r);
					}));
				});
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
}), be = {
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
}, xe = {
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
}, Se = i({
	name: "FormieVueForm",
	props: be,
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
			return a(ye, {
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
}), Ce = i({
	name: "FormieVueClientForm",
	props: xe,
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
		return () => a(le, {
			source: _e({
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
export { Ce as FormieClientForm, Se as FormieForm, Q as createVueFormieClient, ue as useFormie, $ as useFormieClient, de as useFormieField, ve as useFormieHtml, pe as useFormieInstance, fe as useFormiePage, me as useFormieSlot };
