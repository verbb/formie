//#region \0rolldown/runtime.js
var e = Object.create, t = Object.defineProperty, n = Object.getOwnPropertyDescriptor, r = Object.getOwnPropertyNames, i = Object.getPrototypeOf, a = Object.prototype.hasOwnProperty, o = (e, t) => () => (t || (e((t = { exports: {} }).exports, t), e = null), t.exports), s = (e, i, o, s) => {
	if (i && typeof i == "object" || typeof i == "function") for (var c = r(i), l = 0, u = c.length, d; l < u; l++) d = c[l], !a.call(e, d) && d !== o && t(e, d, {
		get: ((e) => i[e]).bind(null, d),
		enumerable: !(s = n(i, d)) || s.enumerable
	});
	return e;
}, c = (n, r, a) => (a = n == null ? {} : e(i(n)), s(r || !n || !n.__esModule ? t(a, "default", {
	value: n,
	enumerable: !0
}) : a, n));
//#endregion
//#region src/conditions.ts
function l(e) {
	return Array.isArray(e) ? e.map((e) => String(e ?? "")) : [String(e ?? "")];
}
function u(e, t) {
	return e.some((e) => t.includes(e));
}
function d(e, t) {
	return e.some((e) => t.some((t) => t === e || t.includes(e)));
}
function f(e, t, n) {
	return t.some((t) => e.some((e) => n(e, t)));
}
function p(e, t, n) {
	return t.some((t) => {
		let r = Number.parseFloat(t);
		return Number.isFinite(r) ? e.some((e) => {
			let t = Number.parseFloat(e);
			return Number.isFinite(t) ? n(t, r) : !1;
		}) : !1;
	});
}
function m(e) {
	return e.length === 0 || e.every((e) => e.trim() === "");
}
function h(e, t, n = {}) {
	let r = String(e.condition || ""), i = l(e.value), a = n.visibility ?? null;
	switch (r) {
		case "=": return u(i, t);
		case "!=": return !u(i, t);
		case ">": return p(t, i, (e, t) => e > t);
		case "<": return p(t, i, (e, t) => e < t);
		case "contains": return d(i, t);
		case "notContains": return !d(i, t);
		case "startsWith": return f(t, i, (e, t) => e.startsWith(t));
		case "endsWith": return f(t, i, (e, t) => e.endsWith(t));
		case "empty": return m(t);
		case "notEmpty": return !m(t);
		case "visible": return a === !0;
		case "hidden": return a === !1;
		default: return !1;
	}
}
function g(e, t) {
	let n = e.conditionRule === "any" ? t.includes(!0) : t.every((e) => e === !0);
	return {
		finalResult: n,
		shouldHide: n && e.showRule !== "show" || !n && e.showRule === "show"
	};
}
//#endregion
//#region src/events.ts
var _ = class {
	constructor() {
		this.listeners = /* @__PURE__ */ new Map();
	}
	on(e, t) {
		let n = this.listeners.get(e) ?? /* @__PURE__ */ new Set();
		return n.add(t), this.listeners.set(e, n), () => {
			n.delete(t), n.size === 0 && this.listeners.delete(e);
		};
	}
	emit(e, t) {
		let n = this.listeners.get(e);
		n && n.forEach((e) => {
			e(t);
		});
	}
}, v = new Set([
	"single-line-text",
	"multi-line-text",
	"number",
	"email",
	"phone",
	"dropdown",
	"radio",
	"checkboxes",
	"agree",
	"date",
	"name",
	"address",
	"repeater",
	"signature",
	"file"
]);
function y(e) {
	return e.pages.flatMap((e) => e.rows.flatMap((e) => e.fields));
}
function b(e, t) {
	return y(e).find((e) => e.id === t);
}
function x(e, t) {
	return y(e).find((e) => e.handle === t);
}
function ee(e, t) {
	return Object.fromEntries(Object.entries(t).map(([t, n]) => [b(e, t)?.handle ?? t, n]));
}
function te(e) {
	return v.has(e);
}
function S(e) {
	if (!e.runtime) throw Error(`Field "${e.handle}" is missing field value metadata.`);
	return e.runtime;
}
function C(e) {
	return S(e).structure;
}
function w(e) {
	return C(e) === "fixed-parent" && A(e).length > 0;
}
function T(e) {
	return C(e) === "repeatable-parent";
}
function E(e) {
	return e.type === "file" || e.input.fieldKind === "file";
}
function D(e) {
	let t = e.input;
	return E(e) || e.type === "checkboxes" || e.type === "dropdown" && t.multiple === !0;
}
function O(e) {
	return e.type === "agree" || e.input.fieldKind === "boolean";
}
function ne(e) {
	return e.type === "number";
}
function k(e) {
	return e.type === "email";
}
function A(e) {
	let t = e.input;
	return Array.isArray(t.parts) ? t.parts.filter((e) => !!e && typeof e == "object" && "handle" in e && "type" in e) : [];
}
function j(e) {
	let t = e.input.rowSchema;
	return !t || typeof t != "object" || !Array.isArray(t.rows) ? [] : t.rows;
}
function M(e) {
	return j(e).flatMap((e) => e.fields);
}
function N(e) {
	let t = e.input;
	if (e.type === "checkboxes") return (Array.isArray(t.options) ? t.options : []).filter((e) => e.selected === !0).map((e) => e.value ?? "");
	if (e.type === "radio" || e.type === "dropdown") {
		let n = Array.isArray(t.options) ? t.options : [];
		if (e.type === "dropdown" && t.multiple === !0) return n.filter((e) => e.selected === !0).map((e) => e.value ?? "");
		let r = n.find((e) => e.selected === !0);
		if (r) return r.value ?? "";
	}
	if (e.type === "agree") return t.defaultValue ?? !1;
	if (w(e)) return t.defaultValue && typeof t.defaultValue == "object" ? t.defaultValue : {};
	if (T(e)) {
		let n = Number(t.minRows ?? 0) || 0;
		return n <= 0 ? [] : Array.from({ length: n }, () => P(e));
	}
	return E(e) || D(e) ? [] : (e.type, t.defaultValue ?? "");
}
function P(e) {
	return Object.fromEntries(M(e).map((e) => [e.handle, N(e)]));
}
function F(e, t) {
	if (e.type === "checkboxes" || E(e) || D(e)) return Array.isArray(t) ? t.flatMap((t) => F(e, t)) : [];
	if (T(e)) {
		let n = Array.isArray(t) ? t : [], r = M(e);
		return n.flatMap((e) => {
			if (!e || typeof e != "object") return [];
			let t = e;
			return r.flatMap((e) => F(e, t[e.handle]));
		});
	}
	return w(e) && t && typeof t == "object" ? Object.values(t).flatMap((t) => F(e, t)) : t == null ? [] : typeof t == "boolean" ? t ? ["true"] : ["false"] : Array.isArray(t) ? t.flatMap((t) => F(e, t)) : [String(t)];
}
function I(e) {
	return typeof Blob < "u" && e instanceof Blob;
}
async function re(e) {
	return new Promise((t, n) => {
		let r = new FileReader();
		r.onerror = () => {
			n(r.error || /* @__PURE__ */ Error("Unable to read file."));
		}, r.onload = () => {
			t(typeof r.result == "string" ? r.result : "");
		}, r.readAsDataURL(e);
	});
}
async function ie(e) {
	let t = Array.isArray(e) ? e : [];
	return (await Promise.all(t.map(async (e) => typeof e == "number" ? { assetId: e } : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? {
		assetId: e.assetId,
		filename: typeof e.filename == "string" ? e.filename : void 0
	} : e && typeof e == "object" && "fileData" in e && typeof e.fileData == "string" ? {
		fileData: e.fileData,
		filename: typeof e.filename == "string" ? e.filename : void 0
	} : I(e) ? {
		fileData: await re(e),
		filename: "name" in e && typeof e.name == "string" ? e.name : "upload.bin"
	} : null))).filter((e) => e !== null);
}
async function L(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = { ...n };
	return await Promise.all(e.map(async (e) => {
		r[e.handle] = await z(e, n[e.handle]);
	})), r;
}
async function R(e, t) {
	let n = M(e);
	return n.length === 0 || !Array.isArray(t) ? [] : Promise.all(t.map(async (e) => L(n, e)));
}
async function z(e, t) {
	return E(e) ? ie(t) : T(e) ? R(e, t) : w(e) ? L(A(e), t) : t;
}
async function B(e, t) {
	let n = await Promise.all(Object.entries(t).map(async ([t, n]) => {
		let r = b(e, t);
		return r ? [r.handle, await z(r, n)] : [t, n];
	}));
	return Object.fromEntries(n);
}
//#endregion
//#region src/form-instance.ts
function V(e) {
	return Array.isArray(e) ? e.map((e) => V(e)) : !e || typeof e != "object" || typeof File < "u" && e instanceof File || typeof Blob < "u" && e instanceof Blob ? e : Object.fromEntries(Object.entries(e).map(([e, t]) => [e, V(t)]));
}
function H(e) {
	return {
		...e,
		session: {
			...e.session,
			tokens: { ...e.session.tokens },
			continuation: e.session.continuation ? { ...e.session.continuation } : null
		},
		values: V(e.values),
		errors: {
			form: [...e.errors.form],
			fields: Object.fromEntries(Object.entries(e.errors.fields).map(([e, t]) => [e, [...t]])),
			pages: Object.fromEntries(Object.entries(e.errors.pages).map(([e, t]) => [e, [...t]]))
		},
		fieldStates: Object.fromEntries(Object.entries(e.fieldStates).map(([e, t]) => [e, { ...t }])),
		pageStates: Object.fromEntries(Object.entries(e.pageStates).map(([e, t]) => [e, { ...t }])),
		lastSubmitResult: e.lastSubmitResult ? {
			...e.lastSubmitResult,
			errors: {
				form: [...e.lastSubmitResult.errors.form],
				fields: Object.fromEntries(Object.entries(e.lastSubmitResult.errors.fields).map(([e, t]) => [e, [...t]])),
				pages: Object.fromEntries(Object.entries(e.lastSubmitResult.errors.pages).map(([e, t]) => [e, [...t]]))
			},
			messages: { ...e.lastSubmitResult.messages },
			session: e.lastSubmitResult.session ? {
				...e.lastSubmitResult.session,
				tokens: { ...e.lastSubmitResult.session.tokens },
				continuation: e.lastSubmitResult.session.continuation ? { ...e.lastSubmitResult.session.continuation } : null
			} : null
		} : null
	};
}
function ae(e) {
	return Object.fromEntries(y(e.definition).map((e) => [e.id, N(e)]));
}
function oe(e) {
	return Object.fromEntries(y(e).map((e) => [e.id, {
		hidden: e.meta?.hidden === !0,
		disabled: e.meta?.disabled === !0
	}]));
}
function se(e) {
	return Object.fromEntries(e.pages.map((e) => [e.id, { hidden: !1 }]));
}
function U(e, t) {
	let n = e.definition.pages.find((e) => e.id === t);
	if (!n) return [];
	let r = [];
	return n.rows.forEach((e) => {
		e.fields.forEach((e) => {
			r.push(e.id);
		});
	}), r;
}
function ce(e, t) {
	return b(e, t.fieldId) || x(e, t.fieldId);
}
function le(e) {
	let t = oe(e.definition);
	return y(e.definition).forEach((n) => {
		let r = n.condition;
		if (!r || r.rules.length === 0) return;
		let i = r.rules.map((n) => {
			let r = ce(e.definition, n), i = r ? t[r.id]?.hidden !== !0 : null;
			return h({
				condition: n.operator,
				value: n.value
			}, r ? F(r, e.values[r.id]) : [], { visibility: i });
		});
		if (r.effect === "show" || r.effect === "hide") {
			let { shouldHide: e } = g({
				conditionRule: r.mode,
				showRule: r.effect === "show" ? "show" : "hide"
			}, i);
			t[n.id] = {
				...t[n.id],
				hidden: t[n.id].hidden || e
			};
			return;
		}
		let a = r.mode === "any" ? i.includes(!0) : i.every((e) => e === !0);
		t[n.id] = {
			...t[n.id],
			disabled: t[n.id].disabled || (r.effect === "disable" ? a : !a)
		};
	}), t;
}
function ue(e) {
	return N(e);
}
function de(e, t, n) {
	let r = e.values;
	return y(e.definition).forEach((e) => {
		let i = e.condition, a = t[e.id]?.hidden === !0, o = n[e.id]?.hidden === !0, s = i?.clearOnHide !== !1;
		if (!o || a || !s) return;
		let c = ue(e);
		r[e.id] !== c && (r = {
			...r,
			[e.id]: c
		});
	}), r;
}
function W(e, t) {
	return Object.fromEntries(e.definition.pages.map((n) => {
		let r = n.condition;
		if (!r || r.rules.length === 0) return [n.id, { hidden: !1 }];
		let i = r.rules.map((n) => {
			let r = ce(e.definition, n), i = r ? t[r.id]?.hidden !== !0 : null;
			return h({
				condition: n.operator,
				value: n.value
			}, r ? F(r, e.values[r.id]) : [], { visibility: i });
		}), { shouldHide: a } = g({
			conditionRule: r.mode,
			showRule: r.effect === "show" ? "show" : "hide"
		}, i);
		return [n.id, { hidden: a }];
	}));
}
function G(e, t, n) {
	let r = e.pages[0]?.id || "", i = e.pages.find((e) => t[e.id]?.hidden !== !0)?.id || r;
	return n ? t[n]?.hidden === !0 ? i : n : i;
}
function K(e) {
	let t = e;
	for (let e = 0; e < 3; e += 1) {
		let e = le(t), n = de(t, t.fieldStates, e);
		if (n !== t.values) {
			t = {
				...t,
				values: n,
				fieldStates: e
			};
			continue;
		}
		let r = W(t, e);
		return {
			...t,
			fieldStates: e,
			pageStates: r,
			currentPageId: G(t.definition, r, t.currentPageId)
		};
	}
	let n = le(t), r = W(t, n);
	return {
		...t,
		fieldStates: n,
		pageStates: r,
		currentPageId: G(t.definition, r, t.currentPageId)
	};
}
function q(e, t) {
	return e.type === "checkboxes" ? !Array.isArray(t) || t.length === 0 : O(e) ? t !== !0 : E(e) || T(e) || D(e) ? !Array.isArray(t) || t.length === 0 : w(e) && t && typeof t == "object" ? Object.values(t).every((e) => e == null || typeof e == "string" && e.trim() === "") : t == null ? !0 : typeof t == "string" ? t.trim() === "" : !1;
}
function J(e, t, n, r, i) {
	let a = new Set(e.validation.map((e) => e.type)), o = e.input;
	if ((e.required || a.has("required")) && q(e, t)) {
		i[r] = ["This field is required."];
		return;
	}
	if ((k(e) || a.has("email")) && typeof t == "string" && t.trim() !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)) {
		i[r] = ["Please enter a valid email address."];
		return;
	}
	if ((ne(e) || a.has("number")) && typeof t == "string" && t.trim() !== "") {
		let n = Number.parseFloat(t);
		if (!Number.isFinite(n)) {
			i[r] = ["Please enter a valid number."];
			return;
		}
		let a = e.validation.find((e) => e.type === "number"), s = Number(o.min ?? a?.min ?? NaN), c = Number(o.max ?? a?.max ?? NaN);
		if (Number.isFinite(s) && n < s) {
			i[r] = [`Please enter a value greater than or equal to ${s}.`];
			return;
		}
		if (Number.isFinite(c) && n > c) {
			i[r] = [`Please enter a value less than or equal to ${c}.`];
			return;
		}
	}
	if (a.has("url") && typeof t == "string" && t.trim() !== "") try {
		new URL(t);
	} catch {
		i[r] = ["Please enter a valid URL."];
		return;
	}
	let s = e.validation.find((e) => e.type === "match");
	if (s && typeof t == "string" && t.trim() !== "") {
		let e = (s.fieldId ? b(n.definition, s.fieldId) : void 0) || (s.fieldHandle ? x(n.definition, s.fieldHandle) : void 0), a = e ? n.values[e.id] : void 0;
		if (typeof a == "string" && a !== t) {
			i[r] = ["This value must match the related field."];
			return;
		}
	}
	if (a.has("minmaxOptions") && Array.isArray(t)) {
		let n = e.validation.find((e) => e.type === "minmaxOptions"), a = Number(o.min ?? n?.min ?? NaN), s = Number(o.max ?? n?.max ?? NaN);
		if (Number.isFinite(a) && t.length < a) {
			i[r] = [`Please select at least ${a} option${a === 1 ? "" : "s"}.`];
			return;
		}
		if (Number.isFinite(s) && t.length > s) {
			i[r] = [`Please select no more than ${s} option${s === 1 ? "" : "s"}.`];
			return;
		}
	}
	if (w(e)) {
		let a = A(e), o = t && typeof t == "object" ? t : {};
		a.forEach((e) => {
			e.meta?.hidden !== !0 && J(e, o[e.handle], n, `${r}.${e.handle}`, i);
		});
		return;
	}
	if (T(e)) {
		let a = Array.isArray(t) ? t : [], o = M(e);
		a.forEach((e, t) => {
			let a = e && typeof e == "object" ? e : {};
			o.forEach((e) => {
				J(e, a[e.handle], n, `${r}.${t}.${e.handle}`, i);
			});
		});
	}
}
function fe(e) {
	let t = {
		form: [],
		fields: {},
		pages: {}
	};
	return U(e, e.currentPageId).forEach((n) => {
		let r = b(e.definition, n);
		!r || e.fieldStates[n]?.hidden === !0 || e.fieldStates[n]?.disabled === !0 || J(r, e.values[n], e, n, t.fields);
	}), Object.keys(t.fields).length > 0 && (t.form = [e.definition.settings.validation.formErrorMessage || "Please correct the highlighted fields."]), t;
}
function Y({ envelope: e, transport: t }) {
	let n = new _(), r = /* @__PURE__ */ new Set(), i = ae(e), a = {
		status: "ready",
		definition: e.definition,
		session: e.session,
		values: i,
		errors: {
			form: [],
			fields: {},
			pages: {}
		},
		fieldStates: oe(e.definition),
		pageStates: se(e.definition),
		currentPageId: e.session.currentPageId || e.definition.settings.initialPageId,
		lastSubmitResult: null
	};
	a = K(a);
	let o = () => {
		let e = H(a);
		r.forEach((t) => {
			t(e);
		});
	}, s = (e) => {
		a = e(a), o();
	}, c = {
		id: e.session.id,
		getState() {
			return H(a);
		},
		subscribe(e) {
			return r.add(e), e(H(a)), () => {
				r.delete(e);
			};
		},
		setValue(e, t) {
			s((n) => {
				let r = Object.fromEntries(Object.entries(n.errors.fields).filter(([t]) => t !== e && !t.startsWith(`${e}.`)));
				return r[e] = [], K({
					...n,
					values: {
						...n.values,
						[e]: t
					},
					errors: {
						...n.errors,
						fields: r
					}
				});
			});
		},
		patchValues(e) {
			s((t) => K({
				...t,
				values: {
					...t.values,
					...e
				}
			}));
		},
		async submit(e) {
			let r = a.definition.pages.find((e) => e.id === a.currentPageId), i = e || r?.actions.primary.type || "submit", o = i === "next" ? "submit" : i;
			if (o !== "back" && o !== "save" && a.definition.settings.validation.onSubmit) {
				let e = fe(a);
				if (e.form.length > 0 || Object.keys(e.fields).length > 0) {
					let t = {
						success: !1,
						isFinalPage: !1,
						errors: e,
						messages: { error: e.form[0] || null },
						session: a.session
					};
					return s((n) => ({
						...n,
						errors: e,
						lastSubmitResult: t
					})), n.emit("formie:submit:result", t), t;
				}
			}
			s((e) => ({
				...e,
				status: "submitting",
				errors: {
					form: [],
					fields: {},
					pages: {}
				}
			}));
			try {
				let e = await t.submit({
					definition: a.definition,
					session: a.session,
					values: a.values,
					action: o
				});
				return s((t) => K({
					...t,
					status: "ready",
					session: e.session ?? t.session,
					currentPageId: e.session?.currentPageId || e.currentPageId || t.currentPageId,
					errors: e.errors,
					lastSubmitResult: e
				})), n.emit("formie:submit:result", e), (e.currentPageId || e.nextPageId) && n.emit("formie:page:navigate", {
					currentPageId: a.currentPageId,
					nextPageId: e.nextPageId || e.currentPageId
				}), e;
			} catch (e) {
				let t = e instanceof Error ? e.message : "Submission failed.", r = {
					success: !1,
					isFinalPage: !1,
					errors: {
						form: [t],
						fields: {},
						pages: {}
					},
					messages: { error: t },
					session: a.session
				};
				return s((e) => ({
					...e,
					status: "ready",
					errors: r.errors,
					lastSubmitResult: r
				})), n.emit("formie:submit:result", r), r;
			}
		},
		async setPage(e) {
			if (!t.setPage) {
				s((t) => K({
					...t,
					currentPageId: e,
					session: {
						...t.session,
						currentPageId: e
					}
				}));
				return;
			}
			s((e) => ({
				...e,
				status: "refreshing"
			}));
			try {
				let r = await t.setPage({
					definition: a.definition,
					session: a.session,
					values: a.values,
					currentPageId: a.currentPageId,
					targetPageId: e
				});
				s((e) => K({
					...e,
					status: "ready",
					session: r,
					currentPageId: r.currentPageId
				})), n.emit("formie:page:navigate", {
					currentPageId: a.currentPageId,
					nextPageId: e
				});
			} catch (t) {
				let r = t instanceof Error ? t.message : "Unable to change page.";
				s((e) => ({
					...e,
					status: "ready"
				})), n.emit("formie:page:navigate:error", {
					currentPageId: a.currentPageId,
					nextPageId: e,
					error: r
				});
			}
		},
		async refreshSession() {
			s((e) => ({
				...e,
				status: "refreshing"
			}));
			try {
				let e = await t.refreshSession({
					formHandle: a.definition.handle,
					siteId: a.definition.siteId ?? void 0,
					session: a.session
				});
				s((t) => K({
					...t,
					status: "ready",
					session: e,
					currentPageId: e.currentPageId || t.currentPageId
				})), n.emit("formie:session:refreshed", e);
			} catch (e) {
				let t = e instanceof Error ? e.message : "Unable to refresh session.";
				s((e) => ({
					...e,
					status: "ready"
				})), n.emit("formie:session:refresh:error", { error: t });
			}
		},
		reset() {
			s((t) => K({
				...t,
				session: e.session,
				values: { ...i },
				errors: {
					form: [],
					fields: {},
					pages: {}
				},
				currentPageId: e.session.currentPageId || e.definition.settings.initialPageId,
				lastSubmitResult: null
			})), n.emit("formie:state:reset", null);
		},
		async destroy() {
			s((e) => ({
				...e,
				status: "destroyed"
			})), r.clear();
		},
		on(e, t) {
			return n.on(e, t);
		}
	};
	return queueMicrotask(() => {
		n.emit("formie:client:ready", c.getState());
	}), c;
}
//#endregion
//#region src/event-names.ts
var pe = [
	"formie:client:ready",
	"formie:submit:result",
	"formie:page:navigate",
	"formie:page:navigate:error",
	"formie:session:refreshed",
	"formie:session:refresh:error",
	"formie:state:reset"
], me = /* @__PURE__ */ c((/* @__PURE__ */ o(((e, t) => {
	(function(n, r) {
		typeof e == "object" && t !== void 0 ? r(e) : typeof define == "function" && define.amd ? define(["exports"], r) : r((n = typeof globalThis < "u" ? globalThis : n || self).ExpressionLanguage = {});
	})(e, function(e) {
		function t(e, t, n) {
			return (t = function(e) {
				var t = function(e, t) {
					if (typeof e != "object" || !e) return e;
					var n = e[Symbol.toPrimitive];
					if (n !== void 0) {
						var r = n.call(e, t);
						if (typeof r != "object") return r;
						throw TypeError("@@toPrimitive must return a primitive value.");
					}
					return (t === "string" ? String : Number)(e);
				}(e, "string");
				return typeof t == "symbol" ? t : t + "";
			}(t)) in e ? Object.defineProperty(e, t, {
				value: n,
				enumerable: !0,
				configurable: !0,
				writable: !0
			}) : e[t] = n, e;
		}
		let n = function(e, t) {
			if (e.length === 0) return t.length;
			if (t.length === 0) return e.length;
			let n, r, i = [];
			for (n = 0; n <= t.length; n++) i[n] = [n];
			for (r = 0; r <= e.length; r++) i[0] === void 0 && (i[0] = []), i[0][r] = r;
			for (n = 1; n <= t.length; n++) for (r = 1; r <= e.length; r++) t.charAt(n - 1) === e.charAt(r - 1) ? i[n][r] = i[n - 1][r - 1] : i[n][r] = Math.min(i[n - 1][r - 1] + 1, Math.min(i[n][r - 1] + 1, i[n - 1][r] + 1));
			return i[t.length] === void 0 && (i[t.length] = []), i[t.length][e.length];
		};
		class r extends Error {
			constructor(e, t, n, r, i) {
				super(e), this.name = "SyntaxError", this.cursor = t, this.expression = n, this.subject = r, this.proposals = i;
			}
			toString() {
				let e = `${this.name}: ${this.message} around position ${this.cursor}`;
				if (this.expression && (e += ` for expression \`${this.expression}\``), e += ".", this.subject && this.proposals) {
					let t = 2 ** 53 - 1, r = null;
					for (let e of this.proposals) {
						let i = n(this.subject, e);
						i < t && (r = e, t = i);
					}
					r !== null && t < 3 && (e += ` Did you mean "${r}"?`);
				}
				return e;
			}
		}
		class i {
			constructor(e, n) {
				t(this, "next", () => {
					if (this.position += 1, this.tokens[this.position] === void 0) throw new r("Unexpected end of expression", this.last.cursor, this.expression);
				}), t(this, "expect", (e, t, n) => {
					let i = this.current;
					if (!i.test(e, t)) {
						let a = "";
						n && (a = n + ". ");
						let o = "";
						throw t && (o = ` with value "${t}"`), a += `Unexpected token "${i.type}" of value "${i.value}" ("${e}" expected${o})`, new r(a, i.cursor, this.expression);
					}
					this.next();
				}), t(this, "isEOF", () => a.EOF_TYPE === this.current.type), t(this, "isEqualTo", (e) => {
					if (e == null || !e instanceof i || e.tokens.length !== this.tokens.length) return !1;
					let t = e.position;
					e.position = 0;
					let n = !0;
					for (let t of this.tokens) {
						if (!e.current.isEqualTo(t)) {
							n = !1;
							break;
						}
						e.position < e.tokens.length - 1 && e.next();
					}
					return e.position = t, n;
				}), t(this, "diff", (e) => {
					let t = [];
					if (!this.isEqualTo(e)) {
						let n = e.position;
						e.position = 0;
						for (let n of this.tokens) {
							let r = n.diff(e.current);
							r.length > 0 && t.push({
								index: 0,
								diff: r
							}), e.position < e.tokens.length - 1 && e.next();
						}
						e.position = n;
					}
					return t;
				}), this.expression = e, this.position = 0, this.tokens = n;
			}
			get current() {
				return this.tokens[this.position];
			}
			get last() {
				return this.tokens[this.position - 1];
			}
			toString() {
				return this.tokens.join("\n");
			}
		}
		class a {
			constructor(e, n, r) {
				t(this, "test", (e, t = null) => this.type === e && (t === null || this.value === t)), t(this, "isEqualTo", (e) => !(e == null || !e instanceof a) && e.value == this.value && e.type === this.type && e.cursor === this.cursor), t(this, "diff", (e) => {
					let t = [];
					return this.isEqualTo(e) || (e.value !== this.value && t.push(`Value: ${e.value} != ${this.value}`), e.cursor !== this.cursor && t.push(`Cursor: ${e.cursor} != ${this.cursor}`), e.type !== this.type && t.push(`Type: ${e.type} != ${this.type}`)), t;
				}), this.value = n, this.type = e, this.cursor = r;
			}
			toString() {
				return `${this.cursor} [${this.type}] ${this.value}`;
			}
		}
		function o(e) {
			let t = 0, n = [], o = [], c = (e = e.replace(/\r|\n|\t|\v|\f/g, " ")).length;
			for (; t < c;) {
				if (e[t] === " ") {
					++t;
					continue;
				}
				if (e.substr(t, 2) === "/*") {
					let n = e.indexOf("*/", t + 2);
					if (n === -1) {
						t = c;
						break;
					}
					t = n + 2;
					continue;
				}
				let i = s(e.substr(t));
				if (i !== null) {
					let e = i.length, r = i.replace(/_/g, "");
					i = r.indexOf(".") === -1 && r.indexOf("e") === -1 && r.indexOf("E") === -1 ? parseInt(r, 10) : parseFloat(r), n.push(new a(a.NUMBER_TYPE, i, t + 1)), t += e;
				} else if ("([{".indexOf(e[t]) >= 0) o.push([e[t], t]), n.push(new a(a.PUNCTUATION_TYPE, e[t], t + 1)), ++t;
				else if (")]}".indexOf(e[t]) >= 0) {
					if (o.length === 0) throw new r(`Unexpected "${e[t]}"`, t, e);
					let [i, s] = o.pop(), c = i.replace("(", ")").replace("{", "}").replace("[", "]");
					if (e[t] !== c) throw new r(`Unclosed "${i}"`, s, e);
					n.push(new a(a.PUNCTUATION_TYPE, e[t], t + 1)), ++t;
				} else {
					let i = u(e.substr(t));
					if (i !== null) n.push(new a(a.STRING_TYPE, i.captured, t + 1)), t += i.length;
					else if (e.substr(t, 2) === "\\\\") n.push(new a(a.PUNCTUATION_TYPE, "\\", t + 1)), t += 2;
					else {
						let i = n.length > 0 ? n[n.length - 1] : null;
						if (i && i.type === a.PUNCTUATION_TYPE && (i.value === "." || i.value === "?.")) {
							let i = m(e.substr(t));
							if (i) n.push(new a(a.NAME_TYPE, i, t + 1)), t += i.length;
							else {
								let i = p(e.substr(t));
								if (i) n.push(new a(a.OPERATOR_TYPE, i, t + 1)), t += i.length;
								else if (e.substr(t, 2) === "?." || e.substr(t, 2) === "??") n.push(new a(a.PUNCTUATION_TYPE, e.substr(t, 2), t + 1)), t += 2;
								else {
									if (!(".,?:".indexOf(e[t]) >= 0)) throw new r(`Unexpected character "${e[t]}"`, t, e);
									n.push(new a(a.PUNCTUATION_TYPE, e[t], t + 1)), ++t;
								}
							}
						} else {
							let i = p(e.substr(t));
							if (i) n.push(new a(a.OPERATOR_TYPE, i, t + 1)), t += i.length;
							else if (e.substr(t, 2) === "?." || e.substr(t, 2) === "??") n.push(new a(a.PUNCTUATION_TYPE, e.substr(t, 2), t + 1)), t += 2;
							else if (".,?:".indexOf(e[t]) >= 0) n.push(new a(a.PUNCTUATION_TYPE, e[t], t + 1)), ++t;
							else {
								let i = m(e.substr(t));
								if (!i) throw new r(`Unexpected character "${e[t]}"`, t, e);
								n.push(new a(a.NAME_TYPE, i, t + 1)), t += i.length;
							}
						}
					}
				}
			}
			if (n.push(new a(a.EOF_TYPE, null, t + 1)), o.length > 0) {
				let [t, n] = o.pop();
				throw new r(`Unclosed "${t}"`, n, e);
			}
			return new i(e, n);
		}
		function s(e) {
			let t = null, n = e.match(/^(?:((?:\d(?:_?\d)*)\.(?:\d(?:_?\d)*)|\.(?:\d(?:_?\d)*)|(?:\d(?:_?\d)*))(?:[eE][+-]?\d(?:_?\d)*)?)/);
			return n && n.length > 0 && (t = n[0]), t;
		}
		t(a, "EOF_TYPE", "end of expression"), t(a, "NAME_TYPE", "name"), t(a, "NUMBER_TYPE", "number"), t(a, "STRING_TYPE", "string"), t(a, "OPERATOR_TYPE", "operator"), t(a, "PUNCTUATION_TYPE", "punctuation");
		let c = /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;
		function l(e, t) {
			return t === "\"" ? e = e.replace(/\\\"/g, "\"") : t === "'" && (e = e.replace(/\\'/g, "'")), e = e.replace(/\\\\/g, "\\");
		}
		function u(e) {
			let t = null;
			if (["'", "\""].indexOf(e.substr(0, 1)) === -1) return t;
			let n = c.exec(e);
			return n !== null && n.length > 0 && (t = n[1] === void 0 ? { captured: l(n[2], "'") } : { captured: l(n[1], "\"") }, t.length = n[0].length), t;
		}
		let d = /* @__PURE__ */ "&&,and,||,or,+,-,**,*,/,%,&,|,^,>>,<<,===,!==,!=,==,<=,>=,<,>,contains,matches,starts with,ends with,not in,in,not,!,xor,~,..".split(","), f = [
			"and",
			"or",
			"matches",
			"contains",
			"starts with",
			"ends with",
			"not in",
			"in",
			"not",
			"xor"
		];
		function p(e) {
			let t = null;
			for (let n of d) if (e.substr(0, n.length) === n) {
				f.indexOf(n) >= 0 ? e.substr(0, n.length + 1) === n + " " && (t = n) : t = n;
				break;
			}
			return t;
		}
		function m(e) {
			let t = null, n = e.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);
			return n && n.length > 0 && (t = n[0]), t;
		}
		function h(e) {
			return /boolean|number|string/.test(typeof e);
		}
		function g(e, t) {
			var n = "", r = [], i = 0, a = 0, o = "", s = "", c = "", l = "", u = "", d = 0, f = 0, p = 0, m = 0, h = 0, g = [], _ = "", v = /%([\dA-Fa-f]+)/g, y = function(e, t) {
				return (e += "").length < t ? Array(++t - e.length).join("0") + e : e;
			};
			for (i = 0; i < t.length; i++) if (o = t.charAt(i), s = t.charAt(i + 1), o === "\\" && s && /\d/.test(s)) {
				if (m = i + (p = (c = t.slice(i + 1).match(/^\d+/)[0]).length) + 1, t.charAt(m) + t.charAt(m + 1) === "..") {
					if (d = c.charCodeAt(0), /\\\d/.test(t.charAt(m + 2) + t.charAt(m + 3))) l = t.slice(m + 3).match(/^\d+/)[0], i += 1;
					else {
						if (!t.charAt(m + 2)) throw Error("Range with no end point");
						l = t.charAt(m + 2);
					}
					if ((f = l.charCodeAt(0)) > d) for (a = d; a <= f; a++) r.push(String.fromCharCode(a));
					else r.push(".", c, l);
					i += l.length + 2;
				} else u = String.fromCharCode(parseInt(c, 8)), r.push(u);
				i += p;
			} else if (s + t.charAt(i + 2) === "..") {
				if (d = (c = o).charCodeAt(0), /\\\d/.test(t.charAt(i + 3) + t.charAt(i + 4))) l = t.slice(i + 4).match(/^\d+/)[0], i += 1;
				else {
					if (!t.charAt(i + 3)) throw Error("Range with no end point");
					l = t.charAt(i + 3);
				}
				if ((f = l.charCodeAt(0)) > d) for (a = d; a <= f; a++) r.push(String.fromCharCode(a));
				else r.push(".", c, l);
				i += l.length + 2;
			} else r.push(o);
			for (i = 0; i < e.length; i++) if (o = e.charAt(i), r.indexOf(o) !== -1) if (n += "\\", (h = o.charCodeAt(0)) < 32 || h > 126) switch (o) {
				case "\n":
					n += "n";
					break;
				case "	":
					n += "t";
					break;
				case "\r":
					n += "r";
					break;
				case "\x07":
					n += "a";
					break;
				case "\v":
					n += "v";
					break;
				case "\b":
					n += "b";
					break;
				case "\f":
					n += "f";
					break;
				default: for (_ = encodeURIComponent(o), (g = v.exec(_)) !== null && (n += y(parseInt(g[1], 16).toString(8), 3)); (g = v.exec(_)) !== null;) n += "\\" + y(parseInt(g[1], 16).toString(8), 3);
			}
			else n += o;
			else n += o;
			return n;
		}
		class _ {
			constructor(e = {}, n = {}) {
				t(this, "compile", (e) => {
					for (let t of Object.values(this.nodes)) t.compile(e);
				}), t(this, "evaluate", (e, t) => {
					let n = [];
					for (let r of Object.values(this.nodes)) n.push(r.evaluate(e, t));
					return n;
				}), t(this, "toArray", () => {
					throw Error(`Dumping a "${this.name}" instance is not supported yet.`);
				}), t(this, "dump", () => {
					let e = "";
					for (let t of this.toArray()) e += h(t) ? t : t.dump();
					return e;
				}), t(this, "dumpString", (e) => `"${g(e, "\0	\"\\")}"`), t(this, "isHash", (e) => {
					let t = 0;
					for (let n of Object.keys(e)) if (n = parseInt(n), n !== t++) return !0;
					return !1;
				}), this.name = "Node", this.nodes = e, this.attributes = n;
			}
			toString() {
				let e = [];
				for (let t of Object.keys(this.attributes)) {
					let n = "null";
					this.attributes[t] && (n = this.attributes[t].toString()), e.push(`${t}: '${n}'`);
				}
				let t = [this.name + "(" + e.join(", ")];
				if (this.nodes.length > 0) {
					for (let e of Object.values(this.nodes)) {
						let n = e.toString().split("\n");
						for (let e of n) t.push("    " + e);
					}
					t.push(")");
				} else t[0] += ")";
				return t.join("\n");
			}
		}
		class v extends _ {
			constructor(e, n, r) {
				super({
					left: n,
					right: r
				}, { operator: e }), t(this, "compile", (e) => {
					let t = this.attributes.operator;
					t === "matches" ? e.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")") : t === "contains" ? e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())") : t === "starts with" ? e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : t === "ends with" ? e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : v.functions[t] === void 0 ? (v.operators[t] !== void 0 && (t = v.operators[t]), e.raw("(").compile(this.nodes.left).raw(" ").raw(t).raw(" ").compile(this.nodes.right).raw(")")) : e.raw(`${v.functions[t]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")");
				}), t(this, "evaluate", (e, t) => {
					let n = this.attributes.operator, r = this.nodes.left.evaluate(e, t);
					if (v.functions[n] !== void 0) {
						let i = this.nodes.right.evaluate(e, t);
						switch (n) {
							case "not in": return i.indexOf(r) === -1;
							case "in": return i.indexOf(r) >= 0;
							case "..": return function(e, t) {
								let n = [];
								for (let r = e; r <= t; r++) n.push(r);
								return n;
							}(r, i);
							case "**": return r ** +i;
						}
					}
					let i = null;
					switch (n) {
						case "or":
						case "||": return r || (i = this.nodes.right.evaluate(e, t)), r || i;
						case "and":
						case "&&": return r && (i = this.nodes.right.evaluate(e, t)), r && i;
						case "xor": return i = this.nodes.right.evaluate(e, t), i && !r || r && !i;
						case "<<": return i = this.nodes.right.evaluate(e, t), r << i;
						case ">>": return i = this.nodes.right.evaluate(e, t), r >> i;
					}
					switch (i = this.nodes.right.evaluate(e, t), n) {
						case "|": return r | i;
						case "^": return r ^ i;
						case "&": return r & i;
						case "==": return r == i;
						case "===": return r === i;
						case "!=": return r != i;
						case "!==": return r !== i;
						case "<": return r < i;
						case ">": return r > i;
						case ">=": return r >= i;
						case "<=": return r <= i;
						case "not in": return i.indexOf(r) === -1;
						case "in": return i.indexOf(r) >= 0;
						case "+": return r + i;
						case "-": return r - i;
						case "~": return r.toString() + i.toString();
						case "*": return r * i;
						case "/": return r / i;
						case "%": return r % i;
						case "matches":
							if (r == null) return !1;
							let e = i.match(v.regex_expression);
							return new RegExp(e[1], e[2]).test(r);
						case "contains": return r.toString().toLowerCase().includes(i.toString().toLowerCase());
						case "starts with": return r.toString().toLowerCase().startsWith(i.toString().toLowerCase());
						case "ends with": return r.toString().toLowerCase().endsWith(i.toString().toLowerCase());
					}
				}), t(this, "toArray", () => [
					"(",
					this.nodes.left,
					" " + this.attributes.operator + " ",
					this.nodes.right,
					")"
				]), this.name = "BinaryNode";
			}
		}
		t(v, "regex_expression", /\/(.+)\/(.*)/), t(v, "operators", {
			"~": ".",
			and: "&&",
			or: "||",
			xor: "xor",
			"<<": "<<",
			">>": ">>"
		}), t(v, "functions", {
			"**": "Math.pow",
			"..": "range",
			in: "includes",
			"not in": "!includes"
		});
		class y extends _ {
			constructor(e, n) {
				super({ node: n }, { operator: e }), t(this, "compile", (e) => {
					e.raw("(").raw(y.operators[this.attributes.operator]).compile(this.nodes.node).raw(")");
				}), t(this, "evaluate", (e, t) => {
					let n = this.nodes.node.evaluate(e, t);
					switch (this.attributes.operator) {
						case "not":
						case "!": return !n;
						case "-": return -n;
						case "~": return ~n;
					}
					return n;
				}), t(this, "toArray", () => [
					"(",
					this.attributes.operator + " ",
					this.nodes.node,
					")"
				]), this.name = "UnaryNode";
			}
		}
		t(y, "operators", {
			"!": "!",
			not: "!",
			"+": "+",
			"-": "-",
			"~": "~"
		});
		class b extends _ {
			constructor(e, n = !1, r = !1) {
				super({}, { value: e }), t(this, "compile", (e) => {
					e.repr(this.attributes.value, this.isIdentifier);
				}), t(this, "evaluate", (e, t) => this.attributes.value), t(this, "toArray", () => {
					let e = [], t = this.attributes.value;
					if (this.isIdentifier) e.push(t);
					else if (!0 === t) e.push("true");
					else if (!1 === t) e.push("false");
					else if (t === null) e.push("null");
					else if (typeof t == "number") e.push(t);
					else if (typeof t == "string") e.push(this.dumpString(t));
					else if (Array.isArray(t)) {
						for (let n of t) e.push(","), e.push(new b(n));
						e[0] = "[", e.push("]");
					} else if (this.isHash(t)) {
						for (let n of Object.keys(t)) e.push(", "), e.push(new b(n)), e.push(": "), e.push(new b(t[n]));
						e[0] = "{", e.push("}");
					}
					return e;
				}), this.isIdentifier = n, this.isNullSafe = r, this.name = "ConstantNode";
			}
		}
		class x extends _ {
			constructor(e, n, r) {
				super({
					expr1: e,
					expr2: n,
					expr3: r
				}), t(this, "compile", (e) => {
					e.raw("((").compile(this.nodes.expr1).raw(") ? (").compile(this.nodes.expr2).raw(") : (").compile(this.nodes.expr3).raw("))");
				}), t(this, "evaluate", (e, t) => this.nodes.expr1.evaluate(e, t) ? this.nodes.expr2.evaluate(e, t) : this.nodes.expr3.evaluate(e, t)), t(this, "toArray", () => [
					"(",
					this.nodes.expr1,
					" ? ",
					this.nodes.expr2,
					" : ",
					this.nodes.expr3,
					")"
				]), this.name = "ConditionalNode";
			}
		}
		class ee extends _ {
			constructor(e, n) {
				super({ fnArguments: n }, { name: e }), t(this, "compile", (e) => {
					let t = [];
					for (let n of Object.values(this.nodes.fnArguments.nodes)) t.push(e.subcompile(n));
					let n = e.getFunction(this.attributes.name);
					e.raw(n.compiler.apply(null, t));
				}), t(this, "evaluate", (e, t) => {
					let n = [t];
					for (let r of Object.values(this.nodes.fnArguments.nodes)) n.push(r.evaluate(e, t));
					return e[this.attributes.name].evaluator.apply(null, n);
				}), t(this, "toArray", () => {
					let e = [];
					e.push(this.attributes.name);
					for (let t of Object.values(this.nodes.fnArguments.nodes)) e.push(", "), e.push(t);
					return e[1] = "(", e.push(")"), e;
				}), this.name = "FunctionNode";
			}
		}
		class te extends _ {
			constructor(e) {
				super({}, { name: e }), t(this, "compile", (e) => {
					e.raw(this.attributes.name);
				}), t(this, "evaluate", (e, t) => t[this.attributes.name]), t(this, "toArray", () => [this.attributes.name]), this.name = "NameNode";
			}
		}
		class S extends _ {
			constructor() {
				super(), t(this, "addElement", (e, t = null) => {
					t === null ? t = new b(++this.index) : this.type === "Array" && (this.type = "Object"), this.nodes[(++this.keyIndex).toString()] = t, this.nodes[(++this.keyIndex).toString()] = e;
				}), t(this, "compile", (e) => {
					this.type === "Object" ? e.raw("{") : e.raw("["), this.compileArguments(e, this.type !== "Array"), this.type === "Object" ? e.raw("}") : e.raw("]");
				}), t(this, "evaluate", (e, t) => {
					let n;
					if (this.type === "Array") {
						n = [];
						for (let r of this.getKeyValuePairs()) n.push(r.value.evaluate(e, t));
					} else {
						n = {};
						for (let r of this.getKeyValuePairs()) n[r.key.evaluate(e, t)] = r.value.evaluate(e, t);
					}
					return n;
				}), t(this, "toArray", () => {
					let e = {};
					for (let t of this.getKeyValuePairs()) e[t.key.attributes.value] = t.value;
					let t = [];
					if (this.isHash(e)) {
						for (let n of Object.keys(e)) t.push(", "), t.push(new b(n)), t.push(": "), t.push(e[n]);
						t[0] = "{", t.push("}");
					} else {
						for (let n of Object.values(e)) t.push(", "), t.push(n);
						t[0] = "[", t.push("]");
					}
					return t;
				}), t(this, "getKeyValuePairs", () => {
					let e, t, n, r = [], i = Object.values(this.nodes);
					for (e = 0, t = i.length; e < t; e += 2) n = i.slice(e, e + 2), r.push({
						key: n[0],
						value: n[1]
					});
					return r;
				}), t(this, "compileArguments", (e, t = !0) => {
					let n = !0;
					for (let r of this.getKeyValuePairs()) n || e.raw(", "), n = !1, t && e.compile(r.key).raw(": "), e.compile(r.value);
				}), this.name = "ArrayNode", this.type = "Array", this.index = -1, this.keyIndex = -1;
			}
		}
		class C extends S {
			constructor() {
				super(), t(this, "compile", (e) => {
					this.compileArguments(e, !1);
				}), t(this, "toArray", () => {
					let e = [];
					for (let t of this.getKeyValuePairs()) e.push(t.value), e.push(", ");
					return e.pop(), e;
				}), this.name = "ArgumentsNode";
			}
		}
		class w extends _ {
			constructor(e, n, r, i) {
				super({
					node: e,
					attribute: n,
					fnArguments: r
				}, {
					type: i,
					is_null_coalesce: !1,
					is_short_circuited: !1
				}), t(this, "compile", (e) => {
					let t = this.nodes.attribute instanceof b && this.nodes.attribute.isNullSafe;
					switch (this.attributes.type) {
						case w.PROPERTY_CALL:
							e.compile(this.nodes.node).raw(t ? "?." : ".").raw(this.nodes.attribute.attributes.value);
							break;
						case w.METHOD_CALL:
							e.compile(this.nodes.node).raw(t ? "?." : ".").raw(this.nodes.attribute.attributes.value).raw("(").compile(this.nodes.fnArguments).raw(")");
							break;
						case w.ARRAY_CALL: e.compile(this.nodes.node).raw("[").compile(this.nodes.attribute).raw("]");
					}
				}), t(this, "evaluate", (e, t) => {
					switch (this.attributes.type) {
						case w.PROPERTY_CALL:
							let n = this.nodes.node.evaluate(e, t);
							if (n === null && (this.nodes.attribute.isNullSafe || this.attributes.is_null_coalesce)) return this.attributes.is_short_circuited = !0, null;
							if (n === null && this.isShortCircuited()) return null;
							if (typeof n != "object") throw Error(`Unable to get property "${r}" on a non-object: ` + typeof n);
							let r = this.nodes.attribute.attributes.value;
							return this.attributes.is_null_coalesce ? n[r] ?? null : n[r];
						case w.METHOD_CALL:
							let i = this.nodes.node.evaluate(e, t);
							if (i === null && this.nodes.attribute.isNullSafe) return this.attributes.is_short_circuited = !0, null;
							if (i === null && this.isShortCircuited()) return null;
							let a = this.nodes.attribute.attributes.value;
							if (typeof i != "object") throw Error(`Unable to call method "${a}" on a non-object: ` + typeof i);
							if (i[a] === void 0) throw Error(`Method "${a}" is undefined on object.`);
							if (typeof i[a] != "function") throw Error(`Method "${a}" is not a function on object.`);
							let o = this.nodes.fnArguments.evaluate(e, t);
							return i[a].apply(null, o);
						case w.ARRAY_CALL:
							let s = this.nodes.node.evaluate(e, t);
							if (s === null && this.isShortCircuited()) return null;
							if (!(Array.isArray(s) || typeof s == "object" || s === null && this.attributes.is_null_coalesce)) throw Error("Unable to get an item on a non-array: " + typeof s);
							return this.attributes.is_null_coalesce ? s ? s[this.nodes.attribute.evaluate(e, t)] ?? null : null : s[this.nodes.attribute.evaluate(e, t)];
					}
				}), t(this, "toArray", () => {
					let e = this.nodes.attribute instanceof b && this.nodes.attribute.isNullSafe;
					switch (this.attributes.type) {
						case w.PROPERTY_CALL: return [
							this.nodes.node,
							e ? "?." : ".",
							this.nodes.attribute
						];
						case w.METHOD_CALL: return [
							this.nodes.node,
							e ? "?." : ".",
							this.nodes.attribute,
							"(",
							this.nodes.fnArguments,
							")"
						];
						case w.ARRAY_CALL: return [
							this.nodes.node,
							"[",
							this.nodes.attribute,
							"]"
						];
					}
				}), this.name = "GetAttrNode";
			}
			isShortCircuited() {
				return this.attributes.is_short_circuited || this.nodes.node instanceof w && this.nodes.node.isShortCircuited();
			}
		}
		t(w, "PROPERTY_CALL", 1), t(w, "METHOD_CALL", 2), t(w, "ARRAY_CALL", 3);
		class T extends _ {
			constructor(e, n) {
				super({
					expr1: e,
					expr2: n
				}), t(this, "compile", (e) => {
					e.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))");
				}), t(this, "evaluate", (e, t) => (this.nodes.expr1 instanceof w && this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1), this.nodes.expr1.evaluate(e, t) ?? this.nodes.expr2.evaluate(e, t))), t(this, "toArray", () => [
					"(",
					this.nodes.expr1,
					") ?? (",
					this.nodes.expr2,
					")"
				]), t(this, "_addNullCoalesceAttributeToGetAttrNodes", (e) => {
					if (!(!e instanceof w)) {
						e.attributes.is_null_coalesce = !0;
						for (let t of Object.values(e.nodes)) this._addNullCoalesceAttributeToGetAttrNodes(t);
					}
				}), this.name = "NullCoalesceNode";
			}
		}
		class E extends _ {
			constructor(e) {
				super({}, { name: e }), t(this, "compile", (e) => {
					e.raw(this.attributes.name + " ?? null");
				}), t(this, "evaluate", (e, t) => null), t(this, "toArray", () => [this.attributes.name + " ?? null"]), this.name = "NullCoalescedNameNode";
			}
		}
		class D {
			constructor(e = {}) {
				t(this, "functions", {}), t(this, "unaryOperators", {
					not: { precedence: 50 },
					"!": { precedence: 50 },
					"-": { precedence: 500 },
					"+": { precedence: 500 },
					"~": { precedence: 500 }
				}), t(this, "binaryOperators", {
					or: {
						precedence: 10,
						associativity: 1
					},
					"||": {
						precedence: 10,
						associativity: 1
					},
					xor: {
						precedence: 12,
						associativity: 1
					},
					and: {
						precedence: 15,
						associativity: 1
					},
					"&&": {
						precedence: 15,
						associativity: 1
					},
					"|": {
						precedence: 16,
						associativity: 1
					},
					"^": {
						precedence: 17,
						associativity: 1
					},
					"&": {
						precedence: 18,
						associativity: 1
					},
					"==": {
						precedence: 20,
						associativity: 1
					},
					"===": {
						precedence: 20,
						associativity: 1
					},
					"!=": {
						precedence: 20,
						associativity: 1
					},
					"!==": {
						precedence: 20,
						associativity: 1
					},
					"<": {
						precedence: 20,
						associativity: 1
					},
					">": {
						precedence: 20,
						associativity: 1
					},
					">=": {
						precedence: 20,
						associativity: 1
					},
					"<=": {
						precedence: 20,
						associativity: 1
					},
					"not in": {
						precedence: 20,
						associativity: 1
					},
					in: {
						precedence: 20,
						associativity: 1
					},
					matches: {
						precedence: 20,
						associativity: 1
					},
					contains: {
						precedence: 20,
						associativity: 1
					},
					"starts with": {
						precedence: 20,
						associativity: 1
					},
					"ends with": {
						precedence: 20,
						associativity: 1
					},
					"..": {
						precedence: 25,
						associativity: 1
					},
					"<<": {
						precedence: 25,
						associativity: 1
					},
					">>": {
						precedence: 25,
						associativity: 1
					},
					"+": {
						precedence: 30,
						associativity: 1
					},
					"-": {
						precedence: 30,
						associativity: 1
					},
					"~": {
						precedence: 40,
						associativity: 1
					},
					"*": {
						precedence: 60,
						associativity: 1
					},
					"/": {
						precedence: 60,
						associativity: 1
					},
					"%": {
						precedence: 60,
						associativity: 1
					},
					"**": {
						precedence: 200,
						associativity: 2
					}
				}), t(this, "parse", (e, t = [], n = 0) => {
					this.tokenStream = e, this.names = t, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = n;
					let i = this.parseExpression();
					if (!this.tokenStream.isEOF()) throw new r(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}"`, this.tokenStream.current.cursor, this.tokenStream.expression);
					return i;
				}), t(this, "lint", (e, t = [], n = 0) => {
					t === null && (console.log("Deprecated: passing \"null\" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument"), n |= 1, t = []), this.parse(e, t, n);
				}), t(this, "parseExpression", (e = 0) => {
					let t = this.getPrimary(), n = this.tokenStream.current;
					if (this.nestedExecutions++, this.nestedExecutions > 1e3) throw Error("Way to many executions on '" + n.toString() + "' of '" + this.tokenStream.toString() + "'");
					for (; n.test(a.OPERATOR_TYPE) && this.binaryOperators[n.value] !== void 0 && this.binaryOperators[n.value] !== null && this.binaryOperators[n.value].precedence >= e;) {
						let e = this.binaryOperators[n.value];
						this.tokenStream.next();
						let r = this.parseExpression(e.associativity === 1 ? e.precedence + 1 : e.precedence);
						t = new v(n.value, t, r), n = this.tokenStream.current;
					}
					return e === 0 ? this.parseConditionalExpression(t) : t;
				}), t(this, "getPrimary", () => {
					let e = this.tokenStream.current;
					if (e.test(a.OPERATOR_TYPE) && this.unaryOperators[e.value] !== void 0 && this.unaryOperators[e.value] !== null) {
						let t = this.unaryOperators[e.value];
						this.tokenStream.next();
						let n = this.parseExpression(t.precedence);
						return this.parsePostfixExpression(new y(e.value, n));
					}
					if (e.test(a.PUNCTUATION_TYPE, "(")) {
						this.tokenStream.next();
						let e = this.parseExpression();
						return this.tokenStream.expect(a.PUNCTUATION_TYPE, ")", "An opened parenthesis is not properly closed"), this.parsePostfixExpression(e);
					}
					return this.parsePrimaryExpression();
				}), t(this, "hasVariable", (e) => this.getNames().indexOf(e) >= 0), t(this, "getNames", () => {
					if (this.cachedNames !== null) return this.cachedNames;
					if (this.names && this.names.length > 0) {
						let e = [], t = 0;
						this.objectMatches = {};
						for (let n of this.names) typeof n == "object" ? (this.objectMatches[Object.values(n)[0]] = t, e.push(Object.keys(n)[0]), e.push(Object.values(n)[0])) : e.push(n), t++;
						return this.cachedNames = e, e;
					}
					return [];
				}), t(this, "parseArrayExpression", () => {
					this.tokenStream.expect(a.PUNCTUATION_TYPE, "[", "An array element was expected");
					let e = new S(), t = !0;
					for (; !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "]") && (t || (this.tokenStream.expect(a.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"), !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "]")));) t = !1, e.addElement(this.parseExpression());
					return this.tokenStream.expect(a.PUNCTUATION_TYPE, "]", "An opened array is not properly closed"), e;
				}), t(this, "parseHashExpression", () => {
					this.tokenStream.expect(a.PUNCTUATION_TYPE, "{", "A hash element was expected");
					let e = new S(), t = !0;
					for (; !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "}") && (t || (this.tokenStream.expect(a.PUNCTUATION_TYPE, ",", "A hash value must be followed by a comma"), !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "}")));) {
						t = !1;
						let n = null;
						if (this.tokenStream.current.test(a.STRING_TYPE) || this.tokenStream.current.test(a.NAME_TYPE) || this.tokenStream.current.test(a.NUMBER_TYPE)) n = new b(this.tokenStream.current.value), this.tokenStream.next();
						else {
							if (!this.tokenStream.current.test(a.PUNCTUATION_TYPE, "(")) {
								let e = this.tokenStream.current;
								throw new r(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${e.type}" of value "${e.value}"`, e.cursor, this.tokenStream.expression);
							}
							n = this.parseExpression();
						}
						this.tokenStream.expect(a.PUNCTUATION_TYPE, ":", "A hash key must be followed by a colon (:)");
						let i = this.parseExpression();
						e.addElement(i, n);
					}
					return this.tokenStream.expect(a.PUNCTUATION_TYPE, "}", "An opened hash is not properly closed"), e;
				}), t(this, "parsePostfixExpression", (e) => {
					let t = this.tokenStream.current;
					for (; a.PUNCTUATION_TYPE === t.type;) {
						if (t.value === "." || t.value === "?.") {
							let n = t.value === "?.";
							if (this.tokenStream.next(), t = this.tokenStream.current, this.tokenStream.next(), a.NAME_TYPE !== t.type && (a.OPERATOR_TYPE !== t.type || !/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(t.value))) throw new r("Expected name", t.cursor, this.tokenStream.expression);
							let i = new b(t.value, !0, n), o = new C(), s = null;
							if (this.tokenStream.current.test(a.PUNCTUATION_TYPE, "(")) {
								s = w.METHOD_CALL;
								for (let e of Object.values(this.parseArguments().nodes)) o.addElement(e);
							} else s = w.PROPERTY_CALL;
							e = new w(e, i, o, s);
						} else {
							if (t.value !== "[") break;
							{
								this.tokenStream.next();
								let t = this.parseExpression();
								this.tokenStream.expect(a.PUNCTUATION_TYPE, "]"), e = new w(e, t, new C(), w.ARRAY_CALL);
							}
						}
						t = this.tokenStream.current;
					}
					return e;
				}), t(this, "parseArguments", () => {
					let e = [];
					for (this.tokenStream.expect(a.PUNCTUATION_TYPE, "(", "A list of arguments must begin with an opening parenthesis"); !this.tokenStream.current.test(a.PUNCTUATION_TYPE, ")");) e.length !== 0 && this.tokenStream.expect(a.PUNCTUATION_TYPE, ",", "Arguments must be separated by a comma"), e.push(this.parseExpression());
					return this.tokenStream.expect(a.PUNCTUATION_TYPE, ")", "A list of arguments must be closed by a parenthesis"), new _(e);
				}), this.functions = e, this.tokenStream = null, this.names = null, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = 0;
			}
			parseConditionalExpression(e) {
				for (; this.tokenStream.current.test(a.PUNCTUATION_TYPE, "??");) {
					this.tokenStream.next();
					let t = this.parseExpression();
					e = new T(e, t);
				}
				for (; this.tokenStream.current.test(a.PUNCTUATION_TYPE, "?");) {
					let t, n;
					this.tokenStream.next(), this.tokenStream.current.test(a.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), t = e, n = this.parseExpression()) : (t = this.parseExpression(), this.tokenStream.current.test(a.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), n = this.parseExpression()) : t instanceof b && typeof t.attributes?.value == "string" ? n = new b("") : t instanceof x ? (n = t.nodes.expr3, t = t.nodes.expr2) : (n = t, t = e)), e = new x(e, t, n);
				}
				return e;
			}
			parsePrimaryExpression() {
				let e = this.tokenStream.current, t = null;
				switch (e.type) {
					case a.NAME_TYPE:
						switch (this.tokenStream.next(), e.value) {
							case "true":
							case "TRUE": return new b(!0);
							case "false":
							case "FALSE": return new b(!1);
							case "null":
							case "NULL": return new b(null);
							default: if (this.tokenStream.current.value === "(") {
								if (this.functions[e.value] === void 0 && !(2 & this.flags)) throw new r(`The function "${e.value}" does not exist`, e.cursor, this.tokenStream.expression, e.values, Object.keys(this.functions));
								t = new ee(e.value, this.parseArguments());
							} else {
								let n = null;
								if (1 & this.flags) n = e.value;
								else {
									if (!this.hasVariable(e.value)) {
										if (this.tokenStream.current.test(a.PUNCTUATION_TYPE, "??")) return new E(e.value);
										throw new r(`Variable "${e.value}" is not valid`, e.cursor, this.tokenStream.expression, e.value, this.getNames());
									}
									n = e.value, this.objectMatches[n] !== void 0 && (n = this.getNames()[this.objectMatches[n]]);
								}
								t = new te(n);
							}
						}
						break;
					case a.NUMBER_TYPE:
					case a.STRING_TYPE: return this.tokenStream.next(), new b(e.value);
					default: if (e.test(a.PUNCTUATION_TYPE, "[")) t = this.parseArrayExpression();
					else {
						if (!e.test(a.PUNCTUATION_TYPE, "{")) throw new r(`Unexpected token "${e.type}" of value "${e.value}"`, e.cursor, this.tokenStream.expression);
						t = this.parseHashExpression();
					}
				}
				return this.parsePostfixExpression(t);
			}
		}
		class O {
			constructor(e) {
				t(this, "getFunction", (e) => this.functions[e]), t(this, "getSource", () => this.source), t(this, "reset", () => (this.source = "", this)), t(this, "compile", (e) => (e.compile(this), this)), t(this, "subcompile", (e) => {
					let t = this.source;
					this.source = "", e.compile(this);
					let n = this.source;
					return this.source = t, n;
				}), t(this, "raw", (e) => (this.source += e, this)), t(this, "string", (e) => (this.source += "\"" + g(e, "\0	\"$\\") + "\"", this)), t(this, "repr", (e, t = !1) => {
					if (t) this.raw(e);
					else if (Number.isInteger(e) || +e === e && (!isFinite(e) || e % 1)) this.raw(e);
					else if (e === null) this.raw("null");
					else if (typeof e == "boolean") this.raw(e ? "true" : "false");
					else if (typeof e == "object") {
						this.raw("{");
						let t = !0;
						for (let n of Object.keys(e)) t || this.raw(", "), t = !1, this.repr(n), this.raw(":"), this.repr(e[n]);
						this.raw("}");
					} else if (Array.isArray(e)) {
						this.raw("[");
						let t = !0;
						for (let n of e) t || this.raw(", "), t = !1, this.repr(n);
						this.raw("]");
					} else this.string(e);
					return this;
				}), this.source = "", this.functions = e;
			}
		}
		class ne {
			constructor(e) {
				this.expression = e;
			}
			toString() {
				return this.expression;
			}
		}
		class k extends ne {
			constructor(e, n) {
				super(e), t(this, "getNodes", () => this.nodes), this.nodes = n;
			}
			static fromJSON(e) {
				let t = typeof e == "string" ? JSON.parse(e) : e, n = (e) => {
					if (e == null || e instanceof _ || typeof e != "object" || !e.name) return e;
					switch (e.name) {
						case "ConstantNode": return new b(e.attributes?.value, !!e.isIdentifier, !!e.isNullSafe);
						case "NameNode": return new te(e.attributes?.name);
						case "NullCoalescedNameNode": return new E(e.attributes?.name);
						case "UnaryNode": return new y(e.attributes?.operator, n(e.nodes?.node));
						case "BinaryNode": return new v(e.attributes?.operator, n(e.nodes?.left), n(e.nodes?.right));
						case "ConditionalNode": return new x(n(e.nodes?.expr1), n(e.nodes?.expr2), n(e.nodes?.expr3));
						case "NullCoalesceNode": return new T(n(e.nodes?.expr1), n(e.nodes?.expr2));
						case "ArgumentsNode": {
							let t = new C();
							typeof e.type == "string" && (t.type = e.type), typeof e.index == "number" && (t.index = e.index), typeof e.keyIndex == "number" && (t.keyIndex = e.keyIndex), t.nodes = {};
							for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							return t;
						}
						case "ArrayNode": {
							let t = new S();
							typeof e.type == "string" && (t.type = e.type), typeof e.index == "number" && (t.index = e.index), typeof e.keyIndex == "number" && (t.keyIndex = e.keyIndex), t.nodes = {};
							for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							return t;
						}
						case "FunctionNode": {
							let t = n(e.nodes?.arguments);
							return new ee(e.attributes?.name, t);
						}
						case "GetAttrNode": {
							let t = new w(n(e.nodes?.node), n(e.nodes?.attribute), n(e.nodes?.fnArguments), e.attributes?.type);
							return e.attributes && typeof e.attributes.is_null_coalesce == "boolean" && (t.attributes.is_null_coalesce = e.attributes.is_null_coalesce), e.attributes && typeof e.attributes.is_short_circuited == "boolean" && (t.attributes.is_short_circuited = e.attributes.is_short_circuited), t;
						}
						case "Node": {
							let t = new _();
							if (Array.isArray(e.nodes)) t.nodes = e.nodes.map(n);
							else {
								t.nodes = {};
								for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							}
							return t.attributes = e.attributes || {}, t;
						}
						default: {
							let t = new _();
							if (t.name = e.name, Array.isArray(e.nodes)) t.nodes = e.nodes.map(n);
							else {
								t.nodes = {};
								for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							}
							return t.attributes = e.attributes || {}, t;
						}
					}
				}, r = t.expression;
				return new k(r, ((e) => {
					if (e == null) return e;
					if (e.name) return n(e);
					if (Array.isArray(e)) return e.map(n);
					if (typeof e == "object") {
						let t = {};
						for (let r of Object.keys(e)) t[r] = n(e[r]);
						return t;
					}
					return e;
				})(t.nodes));
			}
		}
		var A;
		class j {
			constructor(e = 0) {
				t(this, "createCacheItem", (e, t, n) => {
					let r = new M();
					return r.key = e, r.value = t, r.isHit = n, r.defaultLifetime = this.defaultLifetime, r;
				}), t(this, "get", (e, t, n = null, r = null) => {
					let i = this.getItem(e);
					return i.isHit || this.save(i.set(t(i, !0))), i.get();
				}), t(this, "getItem", (e) => {
					let t = this.hasItem(e), n = null;
					return t ? n = this.values[e] : this.values[e] = null, (0, this.createCacheItem)(e, n, t);
				}), t(this, "getItems", (e) => {
					for (let t of e) typeof t == "string" || this.expiries[t] || M.validateKey(t);
					return this.generateItems(e, (/* @__PURE__ */ new Date()).getTime() / 1e3, this.createCacheItem);
				}), t(this, "deleteItems", (e) => {
					for (let t of e) this.deleteItem(t);
					return !0;
				}), t(this, "save", (e) => !(!e instanceof M) && (e.expiry !== null && e.expiry <= (/* @__PURE__ */ new Date()).getTime() / 1e3 ? (this.deleteItem(e.key), !0) : (e.expiry === null && 0 < e.defaultLifetime && (e.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e.defaultLifetime), this.values[e.key] = e.value, this.expiries[e.key] = e.expiry || 2 ** 53 - 1, !0))), t(this, "saveDeferred", (e) => this.save(e)), t(this, "commit", () => !0), t(this, "delete", (e) => this.deleteItem(e)), t(this, "getValues", () => this.values), t(this, "hasItem", (e) => !!(typeof e == "string" && this.expiries[e] && this.expiries[e] > (/* @__PURE__ */ new Date()).getTime() / 1e3) || (M.validateKey(e), !!this.expiries[e] && !this.deleteItem(e))), t(this, "clear", () => (this.values = {}, this.expiries = {}, !0)), t(this, "deleteItem", (e) => (typeof e == "string" && this.expiries[e] || M.validateKey(e), delete this.values[e], delete this.expiries[e], !0)), t(this, "reset", () => {
					this.clear();
				}), t(this, "generateItems", (e, t, n) => {
					let r = [];
					for (let i of e) {
						let e = null, a = !!this.expiries[i];
						a || !(this.expiries[i] > t) && this.deleteItem(i) ? e = this.values[i] : this.values[i] = null, r[i] = n(i, e, a);
					}
					return r;
				}), this.defaultLifetime = e, this.values = {}, this.expiries = {};
			}
		}
		class M {
			constructor() {
				t(this, "getKey", () => this.key), t(this, "get", () => this.value), t(this, "set", (e) => (this.value = e, this)), t(this, "expiresAt", (e) => {
					if (e === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
					else {
						if (!(e instanceof Date)) throw Error(`Expiration date must be instance of Date or be null, "${e.name}" given`);
						this.expiry = e.getTime() / 1e3;
					}
					return this;
				}), t(this, "expiresAfter", (e) => {
					if (e === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
					else {
						if (!Number.isInteger(e)) throw Error(`Expiration date must be an integer or be null, "${e.name}" given`);
						this.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e;
					}
					return this;
				}), t(this, "tag", (e) => {
					if (!this.isTaggable) throw Error(`Cache item "${this.key}" comes from a non tag-aware pool: you cannot tag it.`);
					Array.isArray(e) || (e = [e]);
					for (let t of e) {
						if (typeof t != "string") throw Error(`Cache tag must by a string, "${typeof t}" given.`);
						if (this.newMetadata.tags[t] && t === "") throw Error("Cache tag length must be greater than zero");
						this.newMetadata.tags[t] = t;
					}
					return this;
				}), t(this, "getMetadata", () => this.metadata), this.key = null, this.value = null, this.isHit = !1, this.expiry = null, this.defaultLifetime = null, this.metadata = {}, this.newMetadata = {}, this.innerItem = null, this.poolHash = null, this.isTaggable = !1;
			}
		}
		A = M, t(M, "METADATA_EXPIRY_OFFSET", 1527506807), t(M, "RESERVED_CHARACTERS", [
			"{",
			"}",
			"(",
			")",
			"/",
			"\\",
			"@",
			":"
		]), t(M, "validateKey", (e) => {
			if (typeof e != "string") throw Error(`Cache key must be string, "${typeof e}" given.`);
			if (e === "") throw Error("Cache key length must be greater than zero");
			for (let t of A.RESERVED_CHARACTERS) if (e.indexOf(t) >= 0) throw Error(`Cache key "${e}" contains reserved character "${t}".`);
			return e;
		});
		class N extends Error {
			constructor(e) {
				super(e), this.name = "LogicException";
			}
			toString() {
				return `${this.name}: ${this.message}`;
			}
		}
		class P {
			constructor(e, n, r) {
				t(this, "getName", () => this.name), t(this, "getCompiler", () => this.compiler), t(this, "getEvaluator", () => this.evaluator), this.name = e, this.compiler = n, this.evaluator = r;
			}
			static fromJavascript(e, t = null) {
				if (typeof e != "string" || e.length === 0) throw TypeError("A JavaScript function name (string) must be provided.");
				let n = e.replace(/^\/+/, ""), r = n.split("."), i = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : {};
				for (let e of r) {
					if (i == null) break;
					i = i[e];
				}
				if (typeof i != "function") throw Error(`JavaScript function "${n}" does not exist.`);
				if (!t && r.length > 1) throw Error(`An expression function name must be defined when JavaScript function "${n}" is namespaced.`);
				return new this(t || r[r.length - 1], (...e) => `${n}(${e.join(", ")})`, (e, ...t) => i(...t));
			}
		}
		class F {
			constructor(e = null, n = []) {
				t(this, "compile", (e, t = []) => this.getCompiler().compile(this.parse(e, t).getNodes()).getSource()), t(this, "evaluate", (e, t = {}) => this.parse(e, Object.keys(t)).getNodes().evaluate(this.functions, t)), t(this, "parse", (e, t, n = 0) => {
					if (e instanceof k) return e;
					t.sort((e, t) => {
						let n = e, r = t;
						return typeof e == "object" && (n = Object.values(e)[0]), typeof t == "object" && (r = Object.values(t)[0]), n.localeCompare(r);
					});
					let r = [];
					for (let e of t) {
						let t = e;
						typeof e == "object" && (t = Object.keys(e)[0] + ":" + Object.values(e)[0]), r.push(t);
					}
					let i = this.cache.getItem(this.fixedEncodeURIComponent(e + "//" + r.join("|"))), a = i.get();
					return a === null && (a = new k(e, this.getParser().parse(this.getLexer().tokenize(e), t, n)), i.set(a), this.cache.save(i)), a;
				}), t(this, "lint", (e, t = null, n = 0) => {
					t === null && (console.log("Deprecated: passing \"null\" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument"), n |= 1, t = []), e instanceof k || this.getParser().lint(this.getLexer().tokenize(e), t, n);
				}), t(this, "fixedEncodeURIComponent", (e) => encodeURIComponent(e).replace(/[!'()*]/g, function(e) {
					return "%" + e.charCodeAt(0).toString(16);
				})), t(this, "register", (e, t, n) => {
					if (this.parser !== null) throw new N("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
					this.functions[e] = {
						compiler: t,
						evaluator: n
					};
				}), t(this, "addFunction", (e) => {
					this.register(e.getName(), e.getCompiler(), e.getEvaluator());
				}), t(this, "registerProvider", (e) => {
					for (let t of e.getFunctions()) this.addFunction(t);
				}), t(this, "getLexer", () => (this.lexer === null && (this.lexer = { tokenize: o }), this.lexer)), t(this, "getParser", () => (this.parser === null && (this.parser = new D(this.functions)), this.parser)), t(this, "getCompiler", () => (this.compiler === null && (this.compiler = new O(this.functions)), this.compiler.reset())), this.functions = [], this.lexer = null, this.parser = null, this.compiler = null, this.cache = e || new j(), this._registerBuiltinFunctions();
				for (let e of n) this.registerProvider(e);
			}
			_registerBuiltinFunctions() {
				let e = P.fromJavascript("Math.min", "min"), t = P.fromJavascript("Math.max", "max");
				this.addFunction(e), this.addFunction(t), this.addFunction(new P("constant", function(e) {
					return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${e})`;
				}, function(e, t) {
					if (typeof t != "string" || !t) return;
					let n = (r = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : {}, t.split(".").reduce((e, t) => e?.[t], r));
					var r;
					return n === void 0 && e && Object.prototype.hasOwnProperty.call(e, t) && (n = e[t]), n;
				})), this.addFunction(new P("enum", function(e) {
					return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${e})`;
				}, function(e, t) {
					if (typeof t != "string" || !t) return;
					let n = String(t).replace(/\\/g, ".").replace(/::/g, ".");
					var r;
					return n ? (r = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : {}, n.split(".").reduce((e, t) => e?.[t], r)) : void 0;
				}));
			}
		}
		class I {
			getFunctions() {
				throw Error("getFunctions must be implemented by " + this.name);
			}
		}
		let re = new P("isset", function(e) {
			return `isset(${e})`;
		}, function(e, t) {
			if (typeof t != "string") return t != null;
			if (!(t.split(/[.\[]/)[0] in e)) return !0;
			let n = "", r = [], i = "", a = "";
			for (let e = 0; e < t.length; e++) {
				let o = t[e];
				if (o !== "]") if (o !== "[") {
					if (i === "object" && (!/[A-z0-9_]/.test(o) || e === t.length - 1)) {
						let n = !1;
						if (e === t.length - 1 && (a += o, n = !0), i = "", r.push({
							type: "object",
							attribute: a
						}), a = "", n) continue;
					}
					o === "." ? (i = "object", a = "") : i ? a += o : n += o;
				} else i = "array", a = "";
				else i = "", r.push({
					type: "array",
					index: a.replace(/"/g, "").replace(/'/g, "")
				}), a = "";
			}
			if (r.length > 0) {
				if (e[n] !== void 0) {
					let t = e[n];
					for (let e of r) {
						if (e.type === "array") {
							if (t[e.index] === void 0) return !1;
							t = t[e.index];
						}
						if (e.type === "object") {
							if (t[e.attribute] === void 0) return !1;
							t = t[e.attribute];
						}
					}
					return !0;
				}
				return !1;
			}
			return e[n] !== void 0;
		}), ie = (e) => Object.entries(e);
		function L(e) {
			return typeof e == "object" && !!e;
		}
		function R(e) {
			return L(e) && !function(e) {
				return Array.isArray(e);
			}(e);
		}
		function z(e) {
			return function(e) {
				return L(e);
			}(e) ? e : {};
		}
		let B = typeof window == "object" && window !== null ? window : typeof global == "object" && global !== null ? global : {};
		function V() {
			let e = (() => {
				let e = B.$locutus;
				typeof e == "object" && e || (e = {}, B.$locutus = e);
				let t = e.php;
				return typeof t == "object" && t || (t = {}, e.php = t), t;
			})(), t = e.ini, n = e.locales, r = e.localeCategories, i = e.pointers, a = R(t) ? t : {}, o = ((e) => R(e))(n) ? n : {}, s = ((e) => R(e))(r) ? r : {}, c = Array.isArray(i) ? i : [];
			t !== a && (e.ini = a), n !== o && (e.locales = o), r !== s && (e.localeCategories = s), i !== c && (e.pointers = c);
			let l = e.locale_default;
			return {
				ini: a,
				locales: o,
				localeCategories: s,
				pointers: c,
				locale_default: typeof l == "string" ? l : void 0
			};
		}
		function H(e) {
			let t = V().ini[e];
			return t && t.local_value !== void 0 ? t.local_value === null ? "" : String(t.local_value) : "";
		}
		function ae(e, t, n) {
			let r = function(e) {
				if (typeof e == "boolean") return e ? "1" : "";
				if (typeof e == "string") return e;
				if (typeof e == "number") return isNaN(e) ? "NAN" : isFinite(e) ? e + "" : (e < 0 ? "-" : "") + "INF";
				if (e === void 0) return "";
				if (typeof e == "object") return Array.isArray(e) ? "Array" : e === null ? "" : "Object";
				throw Error("Unsupported value type");
			}(e), i = H("unicode.semantics") === "on" ? r.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [] : null, a = i ? i.length : r.length, o = a;
			return t < 0 && (t += o), n !== void 0 && (o = n < 0 ? n + o : n + t), !(t > a || t < 0 || t > o) && (i ? i.slice(t, o).join("") : r.slice(t, o));
		}
		function oe(e, ...t) {
			let n = {};
			if (t.length < 1) return n;
			let r = z(e);
			e: for (let [e, i] of ie(r)) {
				for (let e of t) {
					let t = z(e), n = !1;
					for (let [, e] of ie(t)) if (e === i) {
						n = !0;
						break;
					}
					if (!n) continue e;
				}
				n[e] = i;
			}
			return n;
		}
		let se = (e) => {
			if (!e || typeof e != "object") return !1;
			let t = Object.getPrototypeOf(e);
			return t === Array.prototype || t === Object.prototype;
		};
		function U(e, t = 0) {
			let n = 0;
			if (e == null) return 0;
			if (typeof e != "object") return 1;
			let r = Object.getPrototypeOf(e);
			if (r !== Array.prototype && r !== Object.prototype) return 1;
			let i = t === "COUNT_RECURSIVE" || t === 1;
			if (Array.isArray(e)) {
				for (let t of Object.keys(e)) {
					n++;
					let r = e[Number(t)];
					i && se(r) && (n += U(r, 1));
				}
				return n;
			}
			for (let t in e) if (Object.prototype.hasOwnProperty.call(e, t)) {
				n++;
				let r = e[t];
				i && se(r) && (n += U(r, 1));
			}
			return n;
		}
		let ce = new P("implode", function(e, t) {
			return `implode(${e}, ${t})`;
		}, function(e, t, n) {
			return function(...e) {
				let t, n = "", r = "", i = "";
				if (e.length === 1) {
					let [n] = e;
					t = n;
				} else {
					let [n, r] = e;
					i = String(n ?? ""), t = r;
				}
				if (typeof t == "object" && t) {
					if (Array.isArray(t)) return t.join(i);
					for (let e in t) n += r + t[e], r = i;
					return n;
				}
				return String(t);
			}(t, n);
		}), le = new P("count", function(e, t) {
			let n = "";
			return t && (n = `, ${t}`), `count(${e}${n})`;
		}, function(e, t, n) {
			return U(t, n);
		}), ue = new P("array_intersect", function(e, ...t) {
			let n = "";
			return t.length > 0 && (n = ", " + t.join(", ")), `array_intersect(${e}${n})`;
		}, function(e) {
			let t = [], n = !0;
			for (let e = 1; e < arguments.length; e++) t.push(arguments[e]), Array.isArray(arguments[e]) || (n = !1);
			let r = oe.apply(null, t);
			return n ? Object.values(r) : r;
		});
		function de(e, t) {
			let n, r = /* @__PURE__ */ new Date(), i = [
				"Sun",
				"Mon",
				"Tues",
				"Wednes",
				"Thurs",
				"Fri",
				"Satur",
				"January",
				"February",
				"March",
				"April",
				"May",
				"June",
				"July",
				"August",
				"September",
				"October",
				"November",
				"December"
			], a = /\\?(.?)/gi, o = function(e, t) {
				return r = e, Object.prototype.hasOwnProperty.call(n, r) ? String(n[e]()) : t;
				var r;
			}, s = function(e, t) {
				let n = String(e);
				for (; n.length < t;) n = "0" + n;
				return n;
			};
			return n = {
				d: function() {
					return s(n.j(), 2);
				},
				D: function() {
					return String(n.l()).slice(0, 3);
				},
				j: function() {
					return r.getDate();
				},
				l: function() {
					return (i[Number(n.w())] ?? "") + "day";
				},
				N: function() {
					return Number(n.w()) || 7;
				},
				S: function() {
					let e = Number(n.j()), t = e % 10;
					return t <= 3 && Number.parseInt(String(e % 100 / 10), 10) === 1 && (t = 0), [
						"st",
						"nd",
						"rd"
					][t - 1] || "th";
				},
				w: function() {
					return r.getDay();
				},
				z: function() {
					let e = new Date(Number(n.Y()), Number(n.n()) - 1, Number(n.j())), t = new Date(Number(n.Y()), 0, 1);
					return Math.round((e.getTime() - t.getTime()) / 864e5);
				},
				W: function() {
					let e = new Date(Number(n.Y()), Number(n.n()) - 1, Number(n.j()) - Number(n.N()) + 3), t = new Date(e.getFullYear(), 0, 4);
					return s(1 + Math.round((e.getTime() - t.getTime()) / 864e5 / 7), 2);
				},
				F: function() {
					return i[6 + Number(n.n())] ?? "";
				},
				m: function() {
					return s(n.n(), 2);
				},
				M: function() {
					return String(n.F()).slice(0, 3);
				},
				n: function() {
					return r.getMonth() + 1;
				},
				t: function() {
					return new Date(Number(n.Y()), Number(n.n()), 0).getDate();
				},
				L: function() {
					let e = Number(n.Y());
					return +(e % 4 == 0 && e % 100 != 0 || e % 400 == 0);
				},
				o: function() {
					let e = Number(n.n()), t = Number(n.W());
					return Number(n.Y()) + (e === 12 && t < 9 ? 1 : e === 1 && t > 9 ? -1 : 0);
				},
				Y: function() {
					return r.getFullYear();
				},
				y: function() {
					return String(n.Y()).slice(-2);
				},
				a: function() {
					return r.getHours() > 11 ? "pm" : "am";
				},
				A: function() {
					return String(n.a()).toUpperCase();
				},
				B: function() {
					let e = 3600 * r.getUTCHours(), t = 60 * r.getUTCMinutes(), n = r.getUTCSeconds();
					return s(Math.floor((e + t + n + 3600) / 86.4) % 1e3, 3);
				},
				g: function() {
					return Number(n.G()) % 12 || 12;
				},
				G: function() {
					return r.getHours();
				},
				h: function() {
					return s(n.g(), 2);
				},
				H: function() {
					return s(n.G(), 2);
				},
				i: function() {
					return s(r.getMinutes(), 2);
				},
				s: function() {
					return s(r.getSeconds(), 2);
				},
				u: function() {
					return s(1e3 * r.getMilliseconds(), 6);
				},
				e: function() {
					throw Error("Not supported (see source code of date() for timezone on how to add support)");
				},
				I: function() {
					let e = new Date(Number(n.Y()), 0), t = Date.UTC(Number(n.Y()), 0), r = new Date(Number(n.Y()), 6), i = Date.UTC(Number(n.Y()), 6);
					return e.getTime() - t === r.getTime() - i ? 0 : 1;
				},
				O: function() {
					let e = r.getTimezoneOffset(), t = Math.abs(e);
					return (e > 0 ? "-" : "+") + s(100 * Math.floor(t / 60) + t % 60, 4);
				},
				P: function() {
					let e = String(n.O());
					return e.slice(0, 3) + ":" + e.slice(3, 5);
				},
				T: function() {
					return "UTC";
				},
				Z: function() {
					return 60 * -r.getTimezoneOffset();
				},
				c: function() {
					return "Y-m-d\\TH:i:sP".replace(a, o);
				},
				r: function() {
					return "D, d M Y H:i:s O".replace(a, o);
				},
				U: function() {
					return r.getTime() / 1e3 | 0;
				}
			}, c = e, r = (l = t) === void 0 ? /* @__PURE__ */ new Date() : l instanceof Date ? new Date(l) : /* @__PURE__ */ new Date(1e3 * Number(l)), c.replace(a, o);
			var c, l;
		}
		function W(e, t) {
			switch (t?.toLowerCase()) {
				case "a":
					e += e === 12 ? -12 : 0;
					break;
				case "p": e += e === 12 ? 0 : 12;
			}
			return e;
		}
		function G(e) {
			let t = +e;
			return e.length < 4 && t < 100 && (t += t < 70 ? 2e3 : 1900), t;
		}
		function K(e) {
			return {
				jan: 0,
				january: 0,
				i: 0,
				feb: 1,
				february: 1,
				ii: 1,
				mar: 2,
				march: 2,
				iii: 2,
				apr: 3,
				april: 3,
				iv: 3,
				may: 4,
				v: 4,
				jun: 5,
				june: 5,
				vi: 5,
				jul: 6,
				july: 6,
				vii: 6,
				aug: 7,
				august: 7,
				viii: 7,
				sep: 8,
				sept: 8,
				september: 8,
				ix: 8,
				oct: 9,
				october: 9,
				x: 9,
				nov: 10,
				november: 10,
				xi: 10,
				dec: 11,
				december: 11,
				xii: 11
			}[e.toLowerCase()] ?? NaN;
		}
		function q(e, t = 0) {
			return {
				mon: 1,
				monday: 1,
				tue: 2,
				tuesday: 2,
				wed: 3,
				wednesday: 3,
				thu: 4,
				thursday: 4,
				fri: 5,
				friday: 5,
				sat: 6,
				saturday: 6,
				sun: 0,
				sunday: 0
			}[e.toLowerCase()] || t;
		}
		function J(e, t = NaN) {
			let n = e?.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);
			if (!n) return t;
			let r = n[1] === "-" ? -1 : 1, i = +(n[2] ?? 0), a = +(n[4] ?? 0);
			return n[4] || n[3] || (a = Math.floor(i % 100), i = Math.floor(i / 100)), r * (60 * i + a) * 60;
		}
		let fe = {
			acdt: 37800,
			acst: 34200,
			addt: -7200,
			adt: -10800,
			aedt: 39600,
			aest: 36e3,
			ahdt: -32400,
			ahst: -36e3,
			akdt: -28800,
			akst: -32400,
			amt: -13840,
			apt: -10800,
			ast: -14400,
			awdt: 32400,
			awst: 28800,
			awt: -10800,
			bdst: 7200,
			bdt: -36e3,
			bmt: -14309,
			bst: 3600,
			cast: 34200,
			cat: 7200,
			cddt: -14400,
			cdt: -18e3,
			cemt: 10800,
			cest: 7200,
			cet: 3600,
			cmt: -15408,
			cpt: -18e3,
			cst: -21600,
			cwt: -18e3,
			chst: 36e3,
			dmt: -1521,
			eat: 10800,
			eddt: -10800,
			edt: -14400,
			eest: 10800,
			eet: 7200,
			emt: -26248,
			ept: -14400,
			est: -18e3,
			ewt: -14400,
			ffmt: -14660,
			fmt: -4056,
			gdt: 39600,
			gmt: 0,
			gst: 36e3,
			hdt: -34200,
			hkst: 32400,
			hkt: 28800,
			hmt: -19776,
			hpt: -34200,
			hst: -36e3,
			hwt: -34200,
			iddt: 14400,
			idt: 10800,
			imt: 25025,
			ist: 7200,
			jdt: 36e3,
			jmt: 8440,
			jst: 32400,
			kdt: 36e3,
			kmt: 5736,
			kst: 30600,
			lst: 9394,
			mddt: -18e3,
			mdst: 16279,
			mdt: -21600,
			mest: 7200,
			met: 3600,
			mmt: 9017,
			mpt: -21600,
			msd: 14400,
			msk: 10800,
			mst: -25200,
			mwt: -21600,
			nddt: -5400,
			ndt: -9052,
			npt: -9e3,
			nst: -12600,
			nwt: -9e3,
			nzdt: 46800,
			nzmt: 41400,
			nzst: 43200,
			pddt: -21600,
			pdt: -25200,
			pkst: 21600,
			pkt: 18e3,
			plmt: 25590,
			pmt: -13236,
			ppmt: -17340,
			ppt: -25200,
			pst: -28800,
			pwt: -25200,
			qmt: -18840,
			rmt: 5794,
			sast: 7200,
			sdmt: -16800,
			sjmt: -20173,
			smt: -13884,
			sst: -39600,
			tbmt: 10751,
			tmt: 12344,
			uct: 0,
			utc: 0,
			wast: 7200,
			wat: 3600,
			wemt: 7200,
			west: 3600,
			wet: 0,
			wib: 25200,
			wita: 28800,
			wit: 32400,
			wmt: 5040,
			yddt: -25200,
			ydt: -28800,
			ypt: -28800,
			yst: -32400,
			ywt: -28800,
			a: 3600,
			b: 7200,
			c: 10800,
			d: 14400,
			e: 18e3,
			f: 21600,
			g: 25200,
			h: 28800,
			i: 32400,
			k: 36e3,
			l: 39600,
			m: 43200,
			n: -3600,
			o: -7200,
			p: -10800,
			q: -14400,
			r: -18e3,
			s: -21600,
			t: -25200,
			u: -28800,
			v: -32400,
			w: -36e3,
			x: -39600,
			y: -43200,
			z: 0
		}, Y = {
			yesterday: {
				regex: /^yesterday/i,
				name: "yesterday",
				callback() {
					return --this.rd, this.resetTime();
				}
			},
			now: {
				regex: /^now/i,
				name: "now"
			},
			noon: {
				regex: /^noon/i,
				name: "noon",
				callback() {
					return this.resetTime() && this.time(12, 0, 0, 0);
				}
			},
			midnightOrToday: {
				regex: /^(midnight|today)/i,
				name: "midnight | today",
				callback() {
					return this.resetTime();
				}
			},
			tomorrow: {
				regex: /^tomorrow/i,
				name: "tomorrow",
				callback() {
					return this.rd += 1, this.resetTime();
				}
			},
			timestamp: {
				regex: /^@(-?\d+)/i,
				name: "timestamp",
				callback(e, t) {
					return this.rs += +t, this.y = 1970, this.m = 0, this.d = 1, this.dates = 0, this.resetTime() && this.zone(0);
				}
			},
			firstOrLastDay: {
				regex: /^(first|last) day of/i,
				name: "firstdayof | lastdayof",
				callback(e, t) {
					t.toLowerCase() === "first" ? this.firstOrLastDayOfMonth = 1 : this.firstOrLastDayOfMonth = -1;
				}
			},
			backOrFrontOf: {
				regex: /* @__PURE__ */ RegExp("^(back|front) of (2[0-4]|[01]?[0-9])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))?", "i"),
				name: "backof | frontof",
				callback(e, t, n, r) {
					let i = +n, a = 15;
					return t.toLowerCase() === "back" || (--i, a = 45), i = W(i, r), this.resetTime() && this.time(i, a, 0, 0);
				}
			},
			mssqltime: {
				regex: /* @__PURE__ */ RegExp("^(0?[1-9]|1[0-2]):([0-5][0-9]):(60|[0-5][0-9])[:.]([0-9]+)(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "mssqltime",
				callback(e, t, n, r, i, a) {
					return this.time(W(+t, a), +n, +r, +i.substr(0, 3));
				}
			},
			oracledate: {
				regex: /^(\d{2})-([A-Z]{3})-(\d{2})$/i,
				name: "d-M-y",
				callback(e, t, n, r) {
					let i = {
						JAN: 0,
						FEB: 1,
						MAR: 2,
						APR: 3,
						MAY: 4,
						JUN: 5,
						JUL: 6,
						AUG: 7,
						SEP: 8,
						OCT: 9,
						NOV: 10,
						DEC: 11
					}[n.toUpperCase()] ?? NaN;
					return this.ymd(2e3 + parseInt(r, 10), i, parseInt(t, 10));
				}
			},
			timeLong12: {
				regex: /* @__PURE__ */ RegExp("^(0?[1-9]|1[0-2])[:.]([0-5]?[0-9])[:.](60|[0-5][0-9])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "timelong12",
				callback(e, t, n, r, i) {
					return this.time(W(+t, i), +n, +r, 0);
				}
			},
			timeShort12: {
				regex: /* @__PURE__ */ RegExp("^(0?[1-9]|1[0-2])[:.]([0-5][0-9])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "timeshort12",
				callback(e, t, n, r) {
					return this.time(W(+t, r), +n, 0, 0);
				}
			},
			timeTiny12: {
				regex: /* @__PURE__ */ RegExp("^(0?[1-9]|1[0-2])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "timetiny12",
				callback(e, t, n) {
					return this.time(W(+t, n), 0, 0, 0);
				}
			},
			soap: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})-(0[0-9]|1[0-2])-(0[0-9]|[1-2][0-9]|3[01])T([01][0-9]|2[0-4]):([0-5][0-9]):(60|[0-5][0-9])(?:\\.([0-9]+))((?:GMT)?([+-])(2[0-4]|[01]?[0-9]):?([0-5]?[0-9])?)?", "i"),
				name: "soap",
				callback(e, t, n, r, i, a, o, s, c) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, +s.substr(0, 3)) && this.zone(J(c));
				}
			},
			wddx: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})-(1[0-2]|0?[0-9])-(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)T(2[0-4]|[01]?[0-9]):([0-5]?[0-9]):(60|[0-5]?[0-9])"),
				name: "wddx",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			exif: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4}):(0[0-9]|1[0-2]):(0[0-9]|[1-2][0-9]|3[01]) ([01][0-9]|2[0-4]):([0-5][0-9]):(60|[0-5][0-9])", "i"),
				name: "exif",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			xmlRpc: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})(0[0-9]|1[0-2])(0[0-9]|[1-2][0-9]|3[01])T(2[0-4]|[01]?[0-9]):([0-5][0-9]):(60|[0-5][0-9])"),
				name: "xmlrpc",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			xmlRpcNoColon: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})(0[0-9]|1[0-2])(0[0-9]|[1-2][0-9]|3[01])[Tt](2[0-4]|[01]?[0-9])([0-5][0-9])(60|[0-5][0-9])"),
				name: "xmlrpcnocolon",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			clf: {
				regex: /* @__PURE__ */ RegExp("^(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)/(jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec)/([0-9]{4}):([01][0-9]|2[0-4]):([0-5][0-9]):(60|[0-5][0-9])[ \\t]+((?:GMT)?([+-])(2[0-4]|[01]?[0-9]):?([0-5]?[0-9])?)", "i"),
				name: "clf",
				callback(e, t, n, r, i, a, o, s) {
					return this.ymd(+r, K(n), +t) && this.time(+i, +a, +o, 0) && this.zone(J(s));
				}
			},
			iso8601long: {
				regex: /* @__PURE__ */ RegExp("^t?(2[0-4]|[01]?[0-9])[:.]([0-5]?[0-9])[:.](60|[0-5]?[0-9])(?:\\.([0-9]+))", "i"),
				name: "iso8601long",
				callback(e, t, n, r, i) {
					return this.time(+t, +n, +r, +i.substr(0, 3));
				}
			},
			dateTextual: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]+([0-9]{1,4})", "i"),
				name: "datetextual",
				callback(e, t, n, r) {
					return this.ymd(G(r), K(t), +n);
				}
			},
			pointedDate4: {
				regex: /* @__PURE__ */ RegExp("^(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[.\\t-](1[0-2]|0?[0-9])[.-]([0-9]{4})"),
				name: "pointeddate4",
				callback(e, t, n, r) {
					return this.ymd(+r, n - 1, +t);
				}
			},
			pointedDate2: {
				regex: /* @__PURE__ */ RegExp("^(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[.\\t](1[0-2]|0?[0-9])\\.([0-9]{2})"),
				name: "pointeddate2",
				callback(e, t, n, r) {
					return this.ymd(G(r), n - 1, +t);
				}
			},
			timeLong24: {
				regex: /* @__PURE__ */ RegExp("^t?(2[0-4]|[01]?[0-9])[:.]([0-5]?[0-9])[:.](60|[0-5]?[0-9])"),
				name: "timelong24",
				callback(e, t, n, r) {
					return this.time(+t, +n, +r, 0);
				}
			},
			dateNoColon: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})(0[0-9]|1[0-2])(0[0-9]|[1-2][0-9]|3[01])"),
				name: "datenocolon",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			pgydotd: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"),
				name: "pgydotd",
				callback(e, t, n) {
					return this.ymd(+t, 0, +n);
				}
			},
			timeShort24: {
				regex: /* @__PURE__ */ RegExp("^t?(2[0-4]|[01]?[0-9])[:.]([0-5]?[0-9])", "i"),
				name: "timeshort24",
				callback(e, t, n) {
					return this.time(+t, +n, 0, 0);
				}
			},
			iso8601noColon: {
				regex: /* @__PURE__ */ RegExp("^t?([01][0-9]|2[0-4])([0-5][0-9])(60|[0-5][0-9])", "i"),
				name: "iso8601nocolon",
				callback(e, t, n, r) {
					return this.time(+t, +n, +r, 0);
				}
			},
			iso8601dateSlash: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})/(0[0-9]|1[0-2])/(0[0-9]|[1-2][0-9]|3[01])/"),
				name: "iso8601dateslash",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			dateSlash: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})/(1[0-2]|0?[0-9])/(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)"),
				name: "dateslash",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			american: {
				regex: /* @__PURE__ */ RegExp("^(1[0-2]|0?[0-9])/(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)/([0-9]{1,4})"),
				name: "american",
				callback(e, t, n, r) {
					return this.ymd(G(r), t - 1, +n);
				}
			},
			americanShort: {
				regex: /* @__PURE__ */ RegExp("^(1[0-2]|0?[0-9])/(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)"),
				name: "americanshort",
				callback(e, t, n) {
					return this.ymd(this.y, t - 1, +n);
				}
			},
			gnuDateShortOrIso8601date2: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{1,4})-(1[0-2]|0?[0-9])-(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)"),
				name: "gnudateshort | iso8601date2",
				callback(e, t, n, r) {
					return this.ymd(G(t), n - 1, +r);
				}
			},
			iso8601date4: {
				regex: /* @__PURE__ */ RegExp("^([+-]?[0-9]{4})-(0[0-9]|1[0-2])-(0[0-9]|[1-2][0-9]|3[01])"),
				name: "iso8601date4",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			gnuNoColon: {
				regex: /* @__PURE__ */ RegExp("^t?([01][0-9]|2[0-4])([0-5][0-9])", "i"),
				name: "gnunocolon",
				callback(e, t, n) {
					switch (this.times) {
						case 0: return this.time(+t, +n, 0, this.f);
						case 1: return this.y = 100 * t + +n, this.times++, !0;
						default: return !1;
					}
				}
			},
			gnuDateShorter: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})-(1[0-2]|0?[0-9])"),
				name: "gnudateshorter",
				callback(e, t, n) {
					return this.ymd(+t, n - 1, 1);
				}
			},
			pgTextReverse: {
				regex: /* @__PURE__ */ RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-(jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec)-(0[0-9]|[1-2][0-9]|3[01])", "i"),
				name: "pgtextreverse",
				callback(e, t, n, r) {
					return this.ymd(G(t), K(n), +r);
				}
			},
			dateFull: {
				regex: /* @__PURE__ */ RegExp("^(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[ \\t.-]*(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ \\t.-]*([0-9]{1,4})", "i"),
				name: "datefull",
				callback(e, t, n, r) {
					return this.ymd(G(r), K(n), +t);
				}
			},
			dateNoDay: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*([0-9]{4})", "i"),
				name: "datenoday",
				callback(e, t, n) {
					return this.ymd(+n, K(t), 1);
				}
			},
			dateNoDayRev: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})[ .\\t-]*(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", "i"),
				name: "datenodayrev",
				callback(e, t, n) {
					return this.ymd(+t, K(n), 1);
				}
			},
			pgTextShort: {
				regex: /* @__PURE__ */ RegExp("^(jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec)-(0[0-9]|[1-2][0-9]|3[01])-([0-9]{1,4})", "i"),
				name: "pgtextshort",
				callback(e, t, n, r) {
					return this.ymd(G(r), K(t), +n);
				}
			},
			dateNoYear: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]*", "i"),
				name: "datenoyear",
				callback(e, t, n) {
					return this.ymd(this.y, K(t), +n);
				}
			},
			dateNoYearRev: {
				regex: /* @__PURE__ */ RegExp("^(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[ .\\t-]*(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", "i"),
				name: "datenoyearrev",
				callback(e, t, n) {
					return this.ymd(this.y, K(n), +t);
				}
			},
			isoWeekDay: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"),
				name: "isoweekday | isoweek",
				callback(e, t, n, r) {
					let i = r ? +r : 1;
					if (!this.ymd(+t, 0, 1)) return !1;
					let a = new Date(this.y, this.m, this.d).getDay();
					return a = 0 - (a > 4 ? a - 7 : a), this.rd += a + 7 * (n - 1) + i, !0;
				}
			},
			relativeText: {
				regex: /* @__PURE__ */ RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|next|last|previous|this)[ \\t]+((?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?)", "i"),
				name: "relativetext",
				callback(e, t, n) {
					let { amount: r } = function(e) {
						let t = e.toLowerCase();
						return {
							amount: {
								last: -1,
								previous: -1,
								this: 0,
								first: 1,
								next: 1,
								second: 2,
								third: 3,
								fourth: 4,
								fifth: 5,
								sixth: 6,
								seventh: 7,
								eight: 8,
								eighth: 8,
								ninth: 9,
								tenth: 10,
								eleventh: 11,
								twelfth: 12
							}[t] ?? 0,
							behavior: { this: 1 }[t] || 0
						};
					}(t);
					switch (n.toLowerCase()) {
						case "sec":
						case "secs":
						case "second":
						case "seconds":
							this.rs += r;
							break;
						case "min":
						case "mins":
						case "minute":
						case "minutes":
							this.ri += r;
							break;
						case "hour":
						case "hours":
							this.rh += r;
							break;
						case "day":
						case "days":
							this.rd += r;
							break;
						case "fortnight":
						case "fortnights":
						case "forthnight":
						case "forthnights":
							this.rd += 14 * r;
							break;
						case "week":
						case "weeks":
							this.rd += 7 * r;
							break;
						case "month":
						case "months":
							this.rm += r;
							break;
						case "year":
						case "years":
							this.ry += r;
							break;
						case "mon":
						case "monday":
						case "tue":
						case "tuesday":
						case "wed":
						case "wednesday":
						case "thu":
						case "thursday":
						case "fri":
						case "friday":
						case "sat":
						case "saturday":
						case "sun":
						case "sunday": this.resetTime(), this.weekday = q(n, 7), this.weekdayBehavior = 1, this.rd += 7 * (r > 0 ? r - 1 : r);
					}
				}
			},
			relative: {
				regex: /* @__PURE__ */ RegExp("^([+-]*)[ \\t]*(\\d+)[ \\t]*((?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?|week)", "i"),
				name: "relative",
				callback(e, t, n, r) {
					let i = n * (-1) ** t.replace(/[^-]/g, "").length;
					switch (r.toLowerCase()) {
						case "sec":
						case "secs":
						case "second":
						case "seconds":
							this.rs += i;
							break;
						case "min":
						case "mins":
						case "minute":
						case "minutes":
							this.ri += i;
							break;
						case "hour":
						case "hours":
							this.rh += i;
							break;
						case "day":
						case "days":
							this.rd += i;
							break;
						case "fortnight":
						case "fortnights":
						case "forthnight":
						case "forthnights":
							this.rd += 14 * i;
							break;
						case "week":
						case "weeks":
							this.rd += 7 * i;
							break;
						case "month":
						case "months":
							this.rm += i;
							break;
						case "year":
						case "years":
							this.ry += i;
							break;
						case "mon":
						case "monday":
						case "tue":
						case "tuesday":
						case "wed":
						case "wednesday":
						case "thu":
						case "thursday":
						case "fri":
						case "friday":
						case "sat":
						case "saturday":
						case "sun":
						case "sunday": this.resetTime(), this.weekday = q(r, 7), this.weekdayBehavior = 1, this.rd += 7 * (i > 0 ? i - 1 : i);
					}
				}
			},
			dayText: {
				regex: /* @__PURE__ */ RegExp("^(sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?)", "i"),
				name: "daytext",
				callback(e, t) {
					this.resetTime(), this.weekday = q(t, 0), this.weekdayBehavior !== 2 && (this.weekdayBehavior = 1);
				}
			},
			relativeTextWeek: {
				regex: /* @__PURE__ */ RegExp("^(next|last|previous|this)[ \\t]+week", "i"),
				name: "relativetextweek",
				callback(e, t) {
					switch (this.weekdayBehavior = 2, t.toLowerCase()) {
						case "this":
							this.rd += 0;
							break;
						case "next":
							this.rd += 7;
							break;
						case "last":
						case "previous": this.rd -= 7;
					}
					isNaN(this.weekday) && (this.weekday = 1);
				}
			},
			monthFullOrMonthAbbr: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec)", "i"),
				name: "monthfull | monthabbr",
				callback(e, t) {
					return this.ymd(this.y, K(t), this.d);
				}
			},
			tzCorrection: {
				regex: /* @__PURE__ */ RegExp("^((?:GMT)?([+-])(2[0-4]|[01]?[0-9]):?([0-5]?[0-9])?)", "i"),
				name: "tzcorrection",
				callback(e) {
					return this.zone(J(e));
				}
			},
			tzAbbr: {
				regex: /* @__PURE__ */ RegExp("^\\(?([a-zA-Z]{1,6})\\)?"),
				name: "tzabbr",
				callback(e, t) {
					let n = fe[t.toLowerCase()];
					return n != null && !Number.isNaN(n) && this.zone(n);
				}
			},
			ago: {
				regex: /^ago/i,
				name: "ago",
				callback() {
					this.ry = -this.ry, this.rm = -this.rm, this.rd = -this.rd, this.rh = -this.rh, this.ri = -this.ri, this.rs = -this.rs, this.rf = -this.rf;
				}
			},
			year4: {
				regex: /* @__PURE__ */ RegExp("^([0-9]{4})"),
				name: "year4",
				callback(e, t) {
					return this.y = +t, !0;
				}
			},
			whitespace: {
				regex: /^[ .,\t]+/,
				name: "whitespace"
			},
			dateShortWithTimeLong: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]*t?(2[0-4]|[01]?[0-9])[:.]([0-5]?[0-9])[:.](60|[0-5]?[0-9])", "i"),
				name: "dateshortwithtimelong",
				callback(e, t, n, r, i, a) {
					return this.ymd(this.y, K(t), +n) && this.time(+r, +i, +a, 0);
				}
			},
			dateShortWithTimeLong12: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]*(0?[1-9]|1[0-2])[:.]([0-5]?[0-9])[:.](60|[0-5][0-9])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "dateshortwithtimelong12",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(this.y, K(t), +n) && this.time(W(+r, o), +i, +a, 0);
				}
			},
			dateShortWithTimeShort: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]*t?(2[0-4]|[01]?[0-9])[:.]([0-5]?[0-9])", "i"),
				name: "dateshortwithtimeshort",
				callback(e, t, n, r, i) {
					return this.ymd(this.y, K(t), +n) && this.time(+r, +i, 0, 0);
				}
			},
			dateShortWithTimeShort12: {
				regex: /* @__PURE__ */ RegExp("^(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec|i[vx]|vi{0,3}|xi{0,2}|i{1,3})[ .\\t-]*(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)[,.stndrh\\t ]*(0?[1-9]|1[0-2])[:.]([0-5][0-9])[ \\t]*(?:([ap])\\.?m\\.?([\\t ]|$))", "i"),
				name: "dateshortwithtimeshort12",
				callback(e, t, n, r, i, a) {
					return this.ymd(this.y, K(t), +n) && this.time(W(+r, a), +i, 0, 0);
				}
			}
		}, pe = {
			y: NaN,
			m: NaN,
			d: NaN,
			h: NaN,
			i: NaN,
			s: NaN,
			f: NaN,
			ry: 0,
			rm: 0,
			rd: 0,
			rh: 0,
			ri: 0,
			rs: 0,
			rf: 0,
			weekday: NaN,
			weekdayBehavior: 0,
			firstOrLastDayOfMonth: 0,
			z: NaN,
			dates: 0,
			times: 0,
			zones: 0,
			ymd(e, t, n) {
				return !(this.dates > 0) && (this.dates++, this.y = e, this.m = t, this.d = n, !0);
			},
			time(e, t, n, r) {
				return !(this.times > 0) && (this.times++, this.h = e, this.i = t, this.s = n, this.f = r, !0);
			},
			resetTime() {
				return this.h = 0, this.i = 0, this.s = 0, this.f = 0, this.times = 0, !0;
			},
			zone(e) {
				return this.zones <= 1 && (this.zones++, this.z = e, !0);
			},
			toDate(e) {
				switch (this.dates && !this.times && (this.h = this.i = this.s = this.f = 0), isNaN(this.y) && (this.y = e.getFullYear()), isNaN(this.m) && (this.m = e.getMonth()), isNaN(this.d) && (this.d = e.getDate()), isNaN(this.h) && (this.h = e.getHours()), isNaN(this.i) && (this.i = e.getMinutes()), isNaN(this.s) && (this.s = e.getSeconds()), isNaN(this.f) && (this.f = e.getMilliseconds()), this.firstOrLastDayOfMonth) {
					case 1:
						this.d = 1;
						break;
					case -1: this.d = 0, this.m += 1;
				}
				if (!isNaN(this.weekday)) {
					let t = new Date(e.getTime());
					t.setFullYear(this.y, this.m, this.d), t.setHours(this.h, this.i, this.s, this.f);
					let n = t.getDay();
					if (this.weekdayBehavior === 2) n === 0 && this.weekday !== 0 && (this.weekday = -6), this.weekday === 0 && n !== 0 && (this.weekday = 7), this.d -= n, this.d += this.weekday;
					else {
						let e = this.weekday - n;
						(this.rd < 0 && e < 0 || this.rd >= 0 && e <= -this.weekdayBehavior) && (e += 7), this.weekday >= 0 ? this.d += e : this.d -= 7 - (Math.abs(this.weekday) - n), this.weekday = NaN;
					}
				}
				this.y += this.ry, this.m += this.rm, this.d += this.rd, this.h += this.rh, this.i += this.ri, this.s += this.rs, this.f += this.rf, this.ry = this.rm = this.rd = 0, this.rh = this.ri = this.rs = this.rf = 0;
				let t = new Date(e.getTime());
				switch (t.setFullYear(this.y, this.m, this.d), t.setHours(this.h, this.i, this.s, this.f), this.firstOrLastDayOfMonth) {
					case 1:
						t.setDate(1);
						break;
					case -1: t.setMonth(t.getMonth() + 1, 0);
				}
				return isNaN(this.z) || t.getTimezoneOffset() === this.z || (t.setUTCFullYear(t.getFullYear(), t.getMonth(), t.getDate()), t.setUTCHours(t.getHours(), t.getMinutes(), t.getSeconds() - this.z, t.getMilliseconds())), t;
			}
		};
		e.AbstractProvider = I, e.ArrayAdapter = j, e.ArrayProvider = class extends I {
			getFunctions() {
				return [
					ce,
					le,
					ue
				];
			}
		}, e.BasicProvider = class extends I {
			getFunctions() {
				return [re];
			}
		}, e.Compiler = O, e.DateProvider = class extends I {
			getFunctions() {
				return [new P("date", function(e, t) {
					let n = "";
					return t && (n = `, ${t}`), `date(${e}${n})`;
				}, function(e, t, n) {
					return de(t, n);
				}), new P("strtotime", function(e, t) {
					let n = "";
					return t && (n = `, ${t}`), `strtotime(${e}${n})`;
				}, function(e, t, n) {
					return function(e, t) {
						let n = t ?? Math.floor(Date.now() / 1e3), r = [
							Y.yesterday,
							Y.now,
							Y.noon,
							Y.midnightOrToday,
							Y.tomorrow,
							Y.timestamp,
							Y.firstOrLastDay,
							Y.backOrFrontOf,
							Y.timeTiny12,
							Y.timeShort12,
							Y.timeLong12,
							Y.mssqltime,
							Y.oracledate,
							Y.timeShort24,
							Y.timeLong24,
							Y.iso8601long,
							Y.gnuNoColon,
							Y.iso8601noColon,
							Y.americanShort,
							Y.american,
							Y.iso8601date4,
							Y.iso8601dateSlash,
							Y.dateSlash,
							Y.gnuDateShortOrIso8601date2,
							Y.gnuDateShorter,
							Y.dateFull,
							Y.pointedDate4,
							Y.pointedDate2,
							Y.dateNoDay,
							Y.dateNoDayRev,
							Y.dateTextual,
							Y.dateNoYear,
							Y.dateNoYearRev,
							Y.dateNoColon,
							Y.xmlRpc,
							Y.xmlRpcNoColon,
							Y.soap,
							Y.wddx,
							Y.exif,
							Y.pgydotd,
							Y.isoWeekDay,
							Y.pgTextShort,
							Y.pgTextReverse,
							Y.clf,
							Y.year4,
							Y.ago,
							Y.dayText,
							Y.relativeTextWeek,
							Y.relativeText,
							Y.monthFullOrMonthAbbr,
							Y.tzCorrection,
							Y.tzAbbr,
							Y.dateShortWithTimeShort12,
							Y.dateShortWithTimeLong12,
							Y.dateShortWithTimeShort,
							Y.dateShortWithTimeLong,
							Y.relative,
							Y.whitespace
						], i = { ...pe };
						for (; e.length;) {
							let t = null, n = null;
							for (let i of r) {
								let r = e.match(i.regex);
								r && (!t || r[0].length > t[0].length) && (t = r, n = i);
							}
							if (!n || !t || n.callback && !1 === n.callback.apply(i, t)) return !1;
							e = e.substr(t[0].length), n = null, t = null;
						}
						return Math.floor(i.toDate(/* @__PURE__ */ new Date(1e3 * n)).getTime() / 1e3);
					}(t, n);
				})];
			}
		}, e.ExpressionFunction = P, e.ExpressionLanguage = F, e.IGNORE_UNKNOWN_FUNCTIONS = 2, e.IGNORE_UNKNOWN_VARIABLES = 1, e.Parser = D, e.StringProvider = class extends I {
			getFunctions() {
				return [
					new P("strtolower", (e) => "strtolower(" + e + ")", (e, t) => function(e) {
						return (e + "").toLowerCase();
					}(t)),
					new P("strtoupper", (e) => "strtoupper(" + e + ")", (e, t) => function(e) {
						return (e + "").toUpperCase();
					}(t)),
					new P("explode", (e, t, n = "null") => `explode(${e}, ${t}, ${n})`, (e, t, n, r = null) => function(...e) {
						let [t, n, r] = e, i = t, a = n;
						if (e.length < 2 || i === void 0 || a === void 0) return null;
						if (i === "" || !1 === i || i === null) return !1;
						if (typeof i == "function" || typeof i == "object" || typeof a == "function" || typeof a == "object") return { 0: "" };
						!0 === i && (i = "1");
						let o = i + "", s = (a + "").split(o);
						return r === void 0 ? s : (r === 0 && (r = 1), r > 0 ? r >= s.length ? s : s.slice(0, r - 1).concat([s.slice(r - 1).join(o)]) : -r >= s.length ? [] : (s.splice(s.length + r), s));
					}(t, n, r)),
					new P("strlen", function(e) {
						return `strlen(${e});`;
					}, function(e, t) {
						return function(e) {
							let t = e + "";
							if ((H("unicode.semantics") || "off") === "off") return t.length;
							let n = 0, r = 0, i = function(e, t) {
								let n = e.charCodeAt(t);
								if (n >= 55296 && n <= 56319) {
									if (e.length <= t + 1) throw Error("High surrogate without following low surrogate");
									let n = e.charCodeAt(t + 1);
									if (n < 56320 || n > 57343) throw Error("High surrogate without following low surrogate");
									return e.charAt(t) + e.charAt(t + 1);
								}
								if (n >= 56320 && n <= 57343) {
									if (t === 0) throw Error("Low surrogate without preceding high surrogate");
									let n = e.charCodeAt(t - 1);
									if (n < 55296 || n > 56319) throw Error("Low surrogate without preceding high surrogate");
									return !1;
								}
								return e.charAt(t);
							};
							for (n = 0, r = 0; n < t.length; n++) !1 !== i(t, n) && r++;
							return r;
						}(t);
					}),
					new P("strstr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `strstr(${e}, ${t}${r});`;
					}, function(e, t, n, r) {
						return function(e, t, n) {
							let r = 0;
							return r = (e += "").indexOf(t), r !== -1 && (n ? e.substr(0, r) : e.slice(r));
						}(t, n, r);
					}),
					new P("stristr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `stristr(${e}, ${t}${r});`;
					}, function(e, t, n, r) {
						return function(e, t, n) {
							let r = 0;
							return r = (e += "").toLowerCase().indexOf((t + "").toLowerCase()), r !== -1 && (n ? e.substr(0, r) : e.slice(r));
						}(t, n, r);
					}),
					new P("substr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `substr(${e}, ${t}${r});`;
					}, function(e, t, n, r) {
						return ae(t, n, r);
					})
				];
			}
		}, e.default = F, e.tokenize = o, Object.defineProperty(e, "__esModule", { value: !0 });
	}), function(e) {
		var t = e.ExpressionLanguage;
		if (t && typeof t.ExpressionLanguage == "function") {
			var n = t.ExpressionLanguage;
			Object.keys(t).forEach(function(e) {
				e in n || (n[e] = t[e]);
			}), e.ExpressionLanguage = n;
		}
	}(typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : e);
})))(), 1), he = null;
function ge() {
	let e = me, t = e.ExpressionLanguage || e.default || me;
	if (typeof t != "function") throw TypeError("Unable to resolve expression-language constructor.");
	return t;
}
function _e() {
	return he ??= new (ge())(), he;
}
function ve(e) {
	return (e.formula?.expression || e.formula?.formula || "").trim();
}
function ye(e) {
	return Object.entries(e.formula?.variables || {}).filter((e) => !!e[1]?.sourceKey);
}
function be(e, t) {
	return Object.entries(e).forEach(([t, n]) => {
		if (Array.isArray(n)) {
			e[t] = n.map((e) => typeof e == "string" && e.trim() !== "" && !Number.isNaN(Number(e)) ? Number(e) : e);
			return;
		}
		typeof n == "string" && n.trim() !== "" && !Number.isNaN(Number(n)) && (e[t] = Number(n));
	}), e;
}
function xe(e, t) {
	if (t.formatting !== "number") return typeof e == "number" || typeof e == "string" ? e : "";
	let n = e;
	Array.isArray(n) && (n = n.reduce((e, t) => e + Number(t || 0), 0));
	let r = typeof t.decimals == "number" ? t.decimals : 0, i = Number(n || 0).toFixed(r);
	return `${t.prefix || ""}${i}${t.suffix || ""}`;
}
function Se(e, t) {
	let n = e.type?.endsWith("\\Number");
	return e.type?.endsWith("\\Checkboxes") ? Array.isArray(t) ? t.length ? t : "" : t ? [t] : "" : Array.isArray(t) ? t.length ? n ? t.map((e) => Number(e || 0)) : t : "" : n ? Number(t || 0) : t;
}
function Ce(e, t, n) {
	return xe(_e().evaluate(e, t), n);
}
//#endregion
//#region src/rest.ts
function X(e, t) {
	if (t.startsWith("http://") || t.startsWith("https://")) return t;
	if (e.startsWith("http://") || e.startsWith("https://")) return new URL(t, e).toString();
	let n = e.trim();
	return !n || n === "/" ? t : `${n.replace(/\/+$/, "")}${t}`;
}
async function Z(e, t) {
	let n = await fetch(e, t);
	if (!n.ok) throw Error(`Request failed with status ${n.status}.`);
	return n.json();
}
async function we(e) {
	let t = X(e.endpoint, "/actions/formie/client/forms/load"), n = JSON.stringify({
		handle: e.formHandle,
		siteId: e.siteId
	});
	return Z(t, {
		method: "POST",
		credentials: e.credentials ?? "same-origin",
		headers: { "Content-Type": "application/json" },
		body: n
	});
}
function Te(e) {
	return {
		async submit({ definition: t, session: n, values: r, action: i }) {
			let a = X(e.endpoint, "/actions/formie/client/submissions/submit"), o = await B(t, r);
			return Z(a, {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					handle: e.formHandle,
					siteId: e.siteId,
					action: i,
					session: n,
					values: o
				})
			});
		},
		async refreshSession({ session: t }) {
			return Z(X(e.endpoint, "/actions/formie/client/sessions/refresh"), {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					handle: e.formHandle,
					siteId: e.siteId,
					session: t
				})
			});
		},
		async setPage({ definition: t, session: n, values: r, currentPageId: i, targetPageId: a }) {
			let o = X(e.endpoint, "/actions/formie/client/forms/page"), s = await B(t, r);
			return Z(o, {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					handle: e.formHandle,
					siteId: e.siteId,
					currentPageId: i,
					targetPageId: a,
					session: n,
					values: s
				})
			});
		}
	};
}
//#endregion
//#region src/graphql.ts
var Q = "\n    id\n    currentPageId\n    tokens\n    continuation\n", Ee = `
    success
    submissionUid
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    session {
        ${Q}
    }
`;
function De(e) {
	if (e.startsWith("http://") || e.startsWith("https://")) return e;
	let t = e.trim();
	return !t || t === "/" ? "/api" : t;
}
async function $(e, t, n) {
	let r = await fetch(De(e.endpoint), {
		method: "POST",
		credentials: e.credentials ?? "same-origin",
		headers: {
			"Content-Type": "application/json",
			Accept: "application/json"
		},
		body: JSON.stringify({
			query: t,
			variables: n
		})
	});
	if (!r.ok) throw Error(`Request failed with status ${r.status}.`);
	let i = await r.json();
	if (i.errors?.length) throw Error(i.errors[0]?.message || "GraphQL returned an error.");
	if (!i.data) throw Error("GraphQL returned no data.");
	return i.data;
}
async function Oe(e) {
	let t = await $(e, `
            query ClientForm($handle: String!, $siteId: Int) {
                formieClientForm(handle: $handle, siteId: $siteId) {
                    schemaVersion
                    definition
                    session {
                        ${Q}
                    }
                }
            }
        `, {
		handle: e.formHandle,
		siteId: e.siteId
	});
	if (!t.formieClientForm) throw Error("No client form definition was returned.");
	return t.formieClientForm;
}
function ke(e) {
	return {
		async submit({ definition: t, session: n, values: r, action: i }) {
			let a = await B(t, r), o = await $(e, `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${Ee}
                        }
                    }
                `, { input: {
				handle: e.formHandle,
				siteId: e.siteId,
				action: i,
				session: n,
				values: a
			} });
			if (!o.submitFormieClientForm) throw Error("No client submit result was returned.");
			return o.submitFormieClientForm;
		},
		async refreshSession({ session: t }) {
			let n = await $(e, `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${Q}
                        }
                    }
                `, { input: {
				handle: e.formHandle,
				siteId: e.siteId,
				session: t
			} });
			if (!n.refreshFormieClientSession) throw Error("No client session was returned.");
			return n.refreshFormieClientSession;
		},
		async setPage({ definition: t, session: n, values: r, currentPageId: i, targetPageId: a }) {
			let o = await B(t, r), s = await $(e, `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            ${Q}
                        }
                    }
                `, { input: {
				handle: e.formHandle,
				siteId: e.siteId,
				currentPageId: i,
				targetPageId: a,
				session: n,
				values: o
			} });
			if (!s.setFormieClientPage) throw Error("No client page session was returned.");
			return s.setFormieClientPage;
		}
	};
}
//#endregion
//#region src/text.ts
var Ae = (() => {
	let e = Intl.Segmenter;
	return e ? new e(void 0, { granularity: "grapheme" }) : null;
})(), je = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;
function Me(e) {
	return typeof DOMParser < "u" ? new DOMParser().parseFromString(e, "text/html").body.textContent || "" : e.replace(/<[^>]*>/g, " ");
}
function Ne(e) {
	return Me(e);
}
function Pe(e) {
	return Ne(e).replace(/[\s\t\n\r]+/g, " ").trim();
}
function Fe(e) {
	return Ae ? Array.from(Ae.segment(e)).length : Array.from(e).length;
}
function Ie(e) {
	return e.match(je)?.length || 0;
}
function Le(e) {
	let t = Ne(e), n = Pe(e);
	return {
		graphemeCount: Fe(t),
		wordCount: Ie(n)
	};
}
//#endregion
export { pe as FRONTEND_CLIENT_EVENT_NAMES, y as allFields, be as coerceCalculationVariables, A as compositePartDefinitions, Fe as countGraphemes, Y as createFrontendFormInstance, ke as createGraphqlFrontendTransport, P as createRepeaterRowValue, Te as createRestFrontendTransport, N as defaultValueForField, Ce as evaluateCalculationExpression, h as evaluateConditionDefinition, F as fieldValueAsStrings, S as fieldValueContract, C as fieldValueStructure, g as finalizeConditionEvaluation, x as findFieldByHandle, b as findFieldById, xe as formatCalculationValue, ve as getCalculationFormula, ye as getCalculationVariableEntries, Le as getTextLimitMetrics, Ie as getWordCount, O as isBooleanField, w as isCompositeField, k as isEmailField, E as isFileField, te as isKnownFrontendFieldType, D as isMultiValueField, ne as isNumericField, T as isRepeatableField, we as loadFrontendEnvelope, Oe as loadGraphqlFrontendEnvelope, Pe as normalizeText, Se as readCalculationVariableValue, M as repeaterFieldDefinitions, j as repeaterRowDefinitions, ee as serializeFieldValues, B as serializeTransportFieldValues };
