//#region \0rolldown/runtime.js
var e = Object.create, t = Object.defineProperty, n = Object.getOwnPropertyDescriptor, r = Object.getOwnPropertyNames, i = Object.getPrototypeOf, a = Object.prototype.hasOwnProperty, o = (e, t) => () => (t || (e((t = { exports: {} }).exports, t), e = null), t.exports), s = (e, i, o, s) => {
	if (i && typeof i == "object" || typeof i == "function") for (var c = r(i), l = 0, u = c.length, d; l < u; l++) d = c[l], !a.call(e, d) && d !== o && t(e, d, {
		get: ((e) => i[e]).bind(null, d),
		enumerable: !(s = n(i, d)) || s.enumerable
	});
	return e;
}, c = (n, r, o) => (o = n == null ? {} : e(i(n)), s(r || !n || !n.__esModule || !a.call(n, "default") ? t(o, "default", {
	value: n,
	enumerable: !0
}) : o, n));
//#endregion
//#region src/condition-projections.ts
function l(e, t, n) {
	let r = t.scope;
	if (r === "count") return { value: e.length };
	if (r === "all") return { value: e };
	if (r === "first") return { value: e[0] ?? null };
	if (r === "last") return { value: e[e.length - 1] ?? null };
	if (r === "current" || r === "index") {
		let i = r === "current" ? n : /^[0-9]+$/.test(t.index ?? "") ? Number(t.index) : void 0;
		return i !== void 0 && i >= 0 && i < e.length ? { value: e[i] } : { diagnostic: "invalidRowScope" };
	}
	if (r === "rows") {
		let n = t.rows ?? "";
		if (!/^(?:even|odd|every:[1-9]\d*|[1-9]\d*(?:\s*-\s*[1-9]\d*)?(?:\s*,\s*[1-9]\d*(?:\s*-\s*[1-9]\d*)?)*)$/.test(n)) return { diagnostic: "invalidRowScope" };
		let r = e.filter((e, t) => n === "even" ? t % 2 == 1 : n === "odd" ? t % 2 == 0 : n.startsWith("every:") ? t % Number(n.slice(6)) === 0 : n.split(",").some((e) => {
			let [n, r = n] = e.split("-").map(Number);
			return t + 1 >= Math.min(n, r) && t + 1 <= Math.max(n, r);
		}));
		return { value: r.length === 1 ? r[0] : r };
	}
	return { diagnostic: "missingRowScope" };
}
var u = {
	version: 1,
	operators: {
		"=": [
			"text",
			"number",
			"boolean",
			"date",
			"time",
			"datetime",
			"collection"
		],
		"!=": [
			"text",
			"number",
			"boolean",
			"date",
			"time",
			"datetime",
			"collection"
		],
		">": [
			"text",
			"number",
			"date",
			"time",
			"datetime"
		],
		"<": [
			"text",
			"number",
			"date",
			"time",
			"datetime"
		],
		contains: ["text", "collection"],
		notContains: ["text", "collection"],
		startsWith: ["text"],
		endsWith: ["text"],
		empty: [
			"text",
			"number",
			"boolean",
			"date",
			"time",
			"datetime",
			"collection"
		],
		notEmpty: [
			"text",
			"number",
			"boolean",
			"date",
			"time",
			"datetime",
			"collection"
		]
	}
}, d = (e) => ({
	value: null,
	diagnostics: [{ code: e }]
}), f = (e) => typeof e == "number" && !Number.isFinite(e) ? null : e == null ? "" : [
	"string",
	"number",
	"boolean"
].includes(typeof e) ? String(e) : null, p = (e) => e.replace(/^[ \t\r\n\v\f]+|[ \t\r\n\v\f]+$/g, "");
function m(e) {
	if (!["number", "string"].includes(typeof e) || !/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/.test(p(String(e)))) return null;
	let t = Number(e);
	return Number.isFinite(t) ? t : null;
}
function h(e, t) {
	if (t === "text") return f(e);
	if (t === "number") return m(e);
	if (t === "boolean") {
		if (typeof e == "boolean") return e;
		let t = p(f(e) ?? "").toLowerCase();
		return [
			"true",
			"1",
			"yes",
			"on"
		].includes(t) ? !0 : ![
			"false",
			"0",
			"no",
			"off"
		].includes(t) && null;
	}
	if (e && typeof e == "object" && !Array.isArray(e)) {
		let n = e;
		if ((t === "date" ? [
			"year",
			"month",
			"day"
		] : t === "time" ? ["hour", "minute"] : [
			"year",
			"month",
			"day",
			"hour",
			"minute"
		]).some((e) => !/^[0-9]+$/.test(String(n[e]))) || "_input" in n || n.second != null && !/^[0-9]+$/.test(String(n.second))) return null;
		let r = Number(n.hour ?? 0);
		if (n.ampm != null) {
			if (!["AM", "PM"].includes(String(n.ampm)) || r < 1 || r > 12) return null;
			r = r % 12 + (n.ampm === "PM" ? 12 : 0);
		}
		let i = `${String(n.year ?? 1970).padStart(4, "0")}-${String(n.month ?? 1).padStart(2, "0")}-${String(n.day ?? 1).padStart(2, "0")}`, a = `${String(r).padStart(2, "0")}:${String(n.minute ?? 0).padStart(2, "0")}:${String(n.second ?? 0).padStart(2, "0")}`;
		if (t === "datetime") {
			if (h(i, "date") === null || h(a, "time") === null) return null;
			let e = String(n.timezone ?? "UTC");
			if (/^(Z|[+-][0-9]{2}:[0-9]{2})$/.test(e)) return h(`${i}T${a}${e}`, "datetime");
			try {
				let t = Date.parse(`${i}T${a}Z`), n = new Intl.DateTimeFormat("en-GB", {
					timeZone: e,
					year: "numeric",
					month: "2-digit",
					day: "2-digit",
					hour: "2-digit",
					minute: "2-digit",
					second: "2-digit",
					hourCycle: "h23"
				}), r = (e) => {
					let t = Object.fromEntries(n.formatToParts(e).map((e) => [e.type, e.value]));
					return Date.parse(`${t.year.padStart(4, "0")}-${t.month}-${t.day}T${t.hour}:${t.minute}:${t.second}Z`);
				}, o = new Set([
					-864e5,
					0,
					864e5
				].map((e) => t - (r(t + e) - (t + e))).filter((e) => r(e) === t));
				return o.size === 1 ? [...o][0] / 1e3 : null;
			} catch {
				return null;
			}
		}
		e = t === "date" ? i : a;
	}
	if (typeof e != "string") return null;
	if (t === "time") {
		let t = e.match(/^([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?$/);
		return t && Number(t[1]) < 24 && Number(t[2]) < 60 && Number(t[3] ?? 0) < 60 ? Number(t[1]) * 3600 + Number(t[2]) * 60 + Number(t[3] ?? 0) : null;
	}
	let n = e.match(t === "date" ? /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/ : /^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[+-][0-9]{2}:[0-9]{2})$/);
	if (!n || Number(n[1]) < 1) return null;
	let r = Number(n[1]), i = [
		31,
		r % 4 == 0 && (r % 100 != 0 || r % 400 == 0) ? 29 : 28,
		31,
		30,
		31,
		30,
		31,
		31,
		30,
		31,
		30,
		31
	];
	if (Number(n[2]) < 1 || Number(n[2]) > 12 || Number(n[3]) < 1 || Number(n[3]) > i[Number(n[2]) - 1] || t === "datetime" && (Number(n[4]) > 23 || Number(n[5]) > 59 || Number(n[6]) > 59 || n[7] !== "Z" && (Number(n[7].slice(1, 3)) > 23 || Number(n[7].slice(4)) > 59))) return null;
	let a = Date.parse(t === "date" ? `${e}T00:00:00Z` : e);
	return Number.isFinite(a) ? a / 1e3 : null;
}
function g(e) {
	return e && typeof e == "object" ? Object.values(e).flatMap(g) : [e];
}
function _(e, t, n, r = "text") {
	if (!u.operators[e]?.includes(r)) return d("unsupportedOperator");
	if (e === "empty" || e === "notEmpty") {
		if (t && typeof t == "object" && !Array.isArray(t) && ![
			"collection",
			"date",
			"time",
			"datetime"
		].includes(r)) return d("invalidValue");
		let n = t == null || typeof t == "object" && !!t && Object.keys(t).length === 0 || typeof t == "string" && p(t) === "";
		return {
			value: e === "empty" ? n : !n,
			diagnostics: []
		};
	}
	if (r === "collection") {
		if (t != null && typeof t != "object") return d("invalidValue");
		let r = g(t ?? []), i = Array.isArray(n) ? n : [n];
		if ([...r, ...i].some((e) => f(e) === null)) return d("invalidValue");
		let a = r.some((e) => i.some((t) => f(e) === f(t)));
		return {
			value: ["!=", "notContains"].includes(e) ? !a : a,
			diagnostics: []
		};
	}
	let i = h(t, r), a = h(n, r);
	if (i === null || a === null) return d("invalidValue");
	let o = r === "text" ? ((e, t) => {
		let n = Array.from(e, (e) => e.codePointAt(0)), r = Array.from(t, (e) => e.codePointAt(0));
		for (let e = 0; e < Math.min(n.length, r.length); e++) if (n[e] !== r[e]) return n[e] - r[e];
		return n.length - r.length;
	})(String(i), String(a)) : i === a ? 0 : i > a ? 1 : -1;
	return {
		value: {
			"=": i === a,
			"!=": i !== a,
			">": o > 0,
			"<": o < 0,
			contains: String(i).includes(String(a)),
			notContains: !String(i).includes(String(a)),
			startsWith: String(i).startsWith(String(a)),
			endsWith: String(i).endsWith(String(a))
		}[e],
		diagnostics: []
	};
}
function v(e, t) {
	let n = t.flatMap((e, t) => e.diagnostics.map((e) => ({
		...e,
		rule: t
	})));
	return ["all", "any"].includes(e) ? n.length || t.some((e) => e.value === null) ? {
		value: null,
		diagnostics: n
	} : {
		value: e === "all" ? t.every((e) => e.value === !0) : t.some((e) => e.value === !0),
		diagnostics: []
	} : d("invalidSchema");
}
function y(e, t, n = {}) {
	if (e.browserSafe === !1) return d("serverOnlyReference");
	let r = e.valueType ?? (t.length > 1 ? "collection" : "text");
	return _(e.condition, r === "collection" ? t : t[0] ?? null, e.value, r);
}
function b(e, t) {
	let n = v(e.conditionRule, t.map((e) => typeof e == "boolean" ? {
		value: e,
		diagnostics: []
	} : e)), r = n.value === !0;
	return {
		finalResult: r,
		shouldHide: ["show", "enable"].includes(e.showRule) ? !r : r,
		evaluation: n
	};
}
//#endregion
//#region src/schema.ts
var ee = /* @__PURE__ */ new Set([
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
function x(e) {
	return e.pages.flatMap((e) => e.rows.flatMap((e) => e.fields));
}
function S(e, t) {
	return x(e).find((e) => e.id === t);
}
function C(e, t) {
	return x(e).find((e) => e.handle === t);
}
function w(e, t) {
	return Object.fromEntries(Object.entries(t).map(([t, n]) => [S(e, t)?.handle ?? t, n]));
}
function T(e) {
	return ee.has(e);
}
function te(e) {
	if (e.client?.children) return {
		structure: e.client.children.model,
		valueType: e.client.valueType
	};
	if (!e.runtime) throw Error(`Field "${e.handle}" is missing field value metadata.`);
	return e.runtime;
}
function ne(e) {
	return te(e).structure;
}
function E(e) {
	return ne(e) === "fixed-parent" && ae(e).length > 0;
}
function D(e) {
	return ne(e) === "repeatable-parent";
}
function O(e) {
	return e.type === "file" || e.input.fieldKind === "file";
}
function k(e) {
	let t = e.input;
	return O(e) || e.type === "checkboxes" || e.type === "dropdown" && t.multiple === !0;
}
function re(e) {
	return e.type === "agree" || e.input.fieldKind === "boolean";
}
function ie(e) {
	return e.type === "number";
}
function A(e) {
	return e.type === "email";
}
function ae(e) {
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
	if (E(e)) return t.defaultValue && typeof t.defaultValue == "object" ? t.defaultValue : {};
	if (D(e)) {
		let n = Number(t.minRows ?? 0) || 0;
		return n <= 0 ? [] : Array.from({ length: n }, () => oe(e));
	}
	return O(e) || k(e) ? [] : (e.type, t.defaultValue ?? "");
}
function oe(e) {
	return Object.fromEntries(M(e).map((e) => [e.handle, N(e)]));
}
function P(e, t) {
	if (e.type === "checkboxes" || O(e) || k(e)) return Array.isArray(t) ? t.flatMap((t) => P(e, t)) : [];
	if (D(e)) {
		let n = Array.isArray(t) ? t : [], r = M(e);
		return n.flatMap((e) => {
			if (!e || typeof e != "object") return [];
			let t = e;
			return r.flatMap((e) => P(e, t[e.handle]));
		});
	}
	return E(e) && t && typeof t == "object" ? Object.values(t).flatMap((t) => P(e, t)) : t == null ? [] : typeof t == "boolean" ? t ? ["true"] : ["false"] : Array.isArray(t) ? t.flatMap((t) => P(e, t)) : [String(t)];
}
function se(e) {
	return typeof Blob < "u" && e instanceof Blob;
}
async function ce(e) {
	let t = Array.isArray(e) ? e : [];
	return (await Promise.all(t.map(async (e) => {
		if (typeof e == "number") return { assetId: e };
		if (e && typeof e == "object" && typeof e.uploadUid == "string" && typeof e.attachToken == "string") return {
			uploadUid: e.uploadUid,
			attachToken: e.attachToken
		};
		if (e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number") return {
			assetId: e.assetId,
			filename: typeof e.filename == "string" ? e.filename : void 0
		};
		if (e && typeof e == "object" && "fileData" in e && typeof e.fileData == "string") return {
			fileData: e.fileData,
			filename: typeof e.filename == "string" ? e.filename : void 0
		};
		if (se(e)) throw Error("Files must be staged before serialization. Use a Formie REST or GraphQL transport.");
		return null;
	}))).filter((e) => e !== null);
}
async function le(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = { ...n };
	return await Promise.all(e.map(async (e) => {
		r[e.handle] = await de(e, n[e.handle]);
	})), r;
}
async function ue(e, t) {
	let n = M(e);
	return n.length === 0 || !Array.isArray(t) ? [] : Promise.all(t.map(async (e) => le(n, e)));
}
async function de(e, t) {
	return O(e) ? ce(t) : D(e) ? ue(e, t) : E(e) ? le(ae(e), t) : t;
}
async function fe(e, t) {
	let n = await Promise.all(Object.entries(t).map(async ([t, n]) => {
		let r = S(e, t);
		return r ? [r.handle, await de(r, n)] : [t, n];
	}));
	return Object.fromEntries(n);
}
//#endregion
//#region src/condition-state.ts
var pe = (e) => [
	"show",
	"hide",
	"enable",
	"disable"
].includes(e ?? "") ? e : "show";
function me(e) {
	let t = [], n = (e, r, i, a, o) => {
		t.push({
			field: e,
			key: i,
			path: a,
			parent: o
		}), D(e) ? (Array.isArray(r) ? r : []).forEach((t, r) => M(e).forEach((e) => n(e, t?.[e.handle], `${i}.${r}.${e.handle}`, [
			...a,
			String(r),
			e.handle
		], i))) : ae(e).forEach((e) => n(e, r?.[e.handle], `${i}.${e.handle}`, [...a, e.handle], i));
	};
	return x(e.definition).forEach((t) => n(t, e.values[t.id], t.id, [t.handle])), t;
}
function he(e, t) {
	let [n, ...r] = t.key.split(".");
	return r.reduce((e, t) => e && typeof e == "object" ? e[t] : null, e.values[n]);
}
function ge(e, t, n) {
	let r = t.filter((t) => t.field.id === e.fieldId || t.field.handle === e.fieldId || t.path.filter((e) => !/^\d+$/.test(e)).join(".") === e.source?.handle);
	return r.find((e) => e.parent && e.parent === n?.parent) ?? r.find((e) => !e.parent) ?? (r.length === 1 ? r[0] : void 0);
}
function F(e, t, n) {
	if (!e) return {
		value: !0,
		diagnostics: []
	};
	if (e.version != null && e.version !== 1 || ![
		"show",
		"hide",
		"enable",
		"disable"
	].includes(e.effect)) return {
		value: null,
		diagnostics: [{ code: "invalidSchema" }]
	};
	let r = me(t);
	return v(e.mode, e.rules.map((e) => {
		let i = ge(e, r, n);
		if (e.browserSafe === !1 || !i) return {
			value: null,
			diagnostics: [{ code: e.browserSafe === !1 ? "serverOnlyReference" : "unresolvedReference" }]
		};
		if (e.source?.transformerId) return {
			value: null,
			diagnostics: [{ code: "serverOnlyReference" }]
		};
		let a = he(t, i), o = (e.source?.selector ?? "").split(/[.:]/).filter(Boolean), s = { ...e.source?.transformerParams ?? {} }, c = (e, t) => t.reduce((e, t) => e && typeof e == "object" && Object.prototype.hasOwnProperty.call(e, t) ? e[t] : void 0, e);
		if (D(i.field) && (o.length || s.scope)) {
			/^[0-9]+$/.test(o[0] ?? "") && !s.scope && (s.scope = "index", s.index = o.shift());
			let e = (Array.isArray(a) ? a : []).map((e) => c(e, o));
			if (e.some((e) => e === void 0)) return {
				value: null,
				diagnostics: [{ code: "invalidSelector" }]
			};
			let t = n?.path[i.path.length], r = l(e, s, t != null && /^[0-9]+$/.test(t) ? Number(t) : void 0);
			if (r.diagnostic) return {
				value: null,
				diagnostics: [{ code: r.diagnostic }]
			};
			a = s.scope === "rows" && !Array.isArray(r.value) ? [r.value] : r.value;
		} else if (o.length) {
			if (i.field.input.fieldKind === "options" && o.length === 1 && ["label", "value"].includes(o[0])) {
				if (o[0] === "label") {
					let e = i.field.input.options ?? [], t = (t) => e.find((e) => String(e.value) === String(t))?.label;
					a = Array.isArray(a) ? a.map(t) : t(a);
				}
			} else ["date", "time"].includes(o[0]) && i.field.type === "date" || (a = c(a, o));
			if (a === void 0) return {
				value: null,
				diagnostics: [{ code: "invalidSelector" }]
			};
		}
		(a == null || a === "" || Array.isArray(a) && !a.length) && e.source?.defaultValue && (a = e.source.defaultValue);
		let u = i.field.client?.valueType?.kind ?? i.field.runtime?.valueType?.kind, d = e.valueType ?? (u === "number" ? "number" : u === "boolean" ? "boolean" : Array.isArray(a) ? "collection" : "text");
		return _(e.operator, a, e.value, d);
	}));
}
function _e(e) {
	let t = e.definition.pages.find((t) => t.id === e.currentPageId)?.actions.primary.condition;
	if (!t) return !0;
	let n = F(t, e);
	return n.value !== null && n.value === (t.effect === "show" || t.effect === "enable");
}
function ve(e) {
	let t = (e) => Array.isArray(e) ? e.map(t) : e && typeof e == "object" && Object.getPrototypeOf(e) === Object.prototype ? Object.fromEntries(Object.entries(e).map(([e, n]) => [e, t(n)])) : e, n = {
		...e,
		values: t(e.values),
		fieldStates: {},
		pageStates: {}
	}, r = me(n), i = /* @__PURE__ */ new Set(), a = /* @__PURE__ */ new Set(), o = (e) => {
		if (a.has(e.key)) return;
		if (i.has(e.key)) {
			n.fieldStates[e.key] = {
				hidden: !0,
				disabled: !0
			};
			return;
		}
		if (i.add(e.key), e.parent) {
			let t = r.find((t) => t.key === e.parent);
			t && o(t);
		}
		let t = n.definition.pages.find((t) => t.rows.some((t) => t.fields.some((t) => t.id === e.key.split(".")[0])));
		for (let n of [...e.field.condition?.rules ?? [], ...t?.condition?.rules ?? []]) {
			let t = ge(n, r, e);
			t && (o(t), n.source?.selector || r.filter((e) => e.key.startsWith(`${t.key}.`)).forEach(o));
		}
		let s = F(e.field.condition, n, e), c = pe(e.field.condition?.effect), l = e.field.condition ? ["show", "enable"].includes(c) ? s.value !== !0 : s.value === !0 : !1, u = F(t?.condition, n), d = t?.condition ? ["show", "enable"].includes(pe(t.condition.effect)) ? u.value !== !0 : u.value === !0 : !1, f = e.parent ? n.fieldStates[e.parent]?.hidden === !0 : !1, p = n.fieldStates[e.key]?.hidden === !0 || e.field.meta?.hidden === !0 || f || d || ["show", "hide"].includes(c) && l, m = e.field.meta?.disabled === !0 || (e.parent ? n.fieldStates[e.parent]?.disabled === !0 : !1) || ["enable", "disable"].includes(c) && l;
		if (n.fieldStates[e.key] = {
			hidden: p,
			disabled: m
		}, (p || m) && (e.field.condition || f || d || m)) {
			let [t, ...r] = e.key.split("."), i = n.values;
			if (r.length) {
				let e = i[t];
				for (let t of r.slice(0, -1)) e = e && typeof e == "object" ? e[t] : null;
				e && typeof e == "object" && (e[r[r.length - 1]] = null);
			} else i[t] = D(e.field) ? [] : null;
		}
		i.delete(e.key), a.add(e.key);
	};
	r.forEach(o);
	for (let e of n.definition.pages) {
		let t = F(e.condition, n);
		n.pageStates[e.id] = { hidden: e.condition ? ["show", "enable"].includes(pe(e.condition.effect)) ? t.value !== !0 : t.value === !0 : !1 };
	}
	return n.pageStates[n.currentPageId]?.hidden && (n.currentPageId = n.definition.pages.find((e) => !n.pageStates[e.id]?.hidden)?.id ?? n.currentPageId), n;
}
//#endregion
//#region src/validation.ts
var ye = /* @__PURE__ */ new Map();
function be(e, t) {
	return ye.set(e, t), () => {
		ye.get(e) === t && ye.delete(e);
	};
}
function xe(e) {
	return e == null || e === !1 || typeof e == "string" && e.trim() === "" || Array.isArray(e) && e.length === 0;
}
function Se(e, t, n = {}) {
	let r = n.empty ?? xe(e), i = n.label ?? "This field", a = (e, n) => t.messages?.[e] ?? t.message ?? n;
	if (t.type === "required") return r ? a("required", `${i} cannot be blank.`) : null;
	if (r) return null;
	if (t.type === "email") return typeof e == "string" && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e) ? null : a("email", `${i} is not a valid email address.`);
	if (t.type === "number") {
		let n = m(e);
		return n === null ? a("number", `${i} is not a valid number.`) : t.min != null && n < t.min ? a("numberMin", `${i} must be no less than ${t.min}.`) : t.max != null && n > t.max ? a("numberMax", `${i} must be no greater than ${t.max}.`) : null;
	}
	if (t.type === "url") {
		try {
			let t = new URL(String(e));
			if (["http:", "https:"].includes(t.protocol)) return null;
		} catch {}
		return a("url", `${i} is not a valid URL.`);
	}
	if (t.type === "pattern") try {
		let n = t.pattern instanceof RegExp ? t.pattern : typeof t.pattern == "string" ? RegExp(`^(?:${t.pattern})$`) : null;
		return n ? (n.lastIndex = 0, n.test(String(e)) ? null : a("pattern", `${i} is not a valid format.`)) : null;
	} catch {
		return null;
	}
	if (t.type === "match") return n.comparison === void 0 || e === n.comparison ? null : a("match", `${i} must match ${n.comparisonLabel ?? "the other field"}.`);
	if (t.type === "minmaxOptions" && Array.isArray(e)) {
		if (t.min != null && e.length < t.min) return a("minOptions", `Please select at least ${t.min} options.`);
		if (t.max != null && e.length > t.max) return a("maxOptions", `Please select no more than ${t.max} options.`);
	}
	return ye.get(t.type)?.(e, t, n) ?? null;
}
//#endregion
//#region src/browser-modules.ts
var Ce = 1;
function we(e) {
	let t = e;
	if (!t || t.contractVersion !== 1 || !Array.isArray(t.entries)) throw Error("Unsupported browser module contractVersion. Update Formie and its browser packages together.");
	let n = /* @__PURE__ */ new Set();
	for (let e of t.entries) {
		if (!e || typeof e.key != "string" || !e.key || n.has(e.key) || typeof e.moduleId != "string" || !/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(e.moduleId) || "src" in e || !Array.isArray(e.surfaces) || !Array.isArray(e.targets) || typeof e.required != "boolean" || e.surfaces.some((e) => ![
			"server-rendered",
			"client-rendered",
			"cp-edit"
		].includes(e)) || !e.config || typeof e.config != "object" || Array.isArray(e.config) && e.config.length > 0 || typeof e.type != "string" || typeof e.capability != "string") throw Error("Invalid browser module entry. Check the registered module ID, occurrence key and surfaces.");
		for (let t of e.targets) if (!t || ![
			"field",
			"form",
			"page",
			"button",
			"global"
		].includes(t.targetType) || typeof t.targetId != "string") throw Error("Invalid browser module target. Fields require a form-field instance UID.");
		n.add(e.key);
	}
}
//#endregion
//#region src/contract.ts
var Te = 1;
function Ee(e) {
	let t = e;
	if (!t || t.contractVersion !== 1) throw Error("Unsupported client-rendered contractVersion. Update Formie and the client-rendered packages together.");
	if (!t.definition || !Array.isArray(t.definition.pages) || !t.session) throw Error("Invalid client-rendered bootstrap: definition, pages and session are required.");
	we(t.definition.modules);
}
//#endregion
//#region src/date-parts-validation.ts
function I(e) {
	return e == null ? "" : String(e).trim();
}
function L(e) {
	return ae(e).filter((e) => e.meta?.hidden !== !0).map((e) => e.handle).filter((e) => [
		"year",
		"month",
		"day"
	].includes(e));
}
function R(e, t) {
	let n = L(t);
	return n.length !== 0 && n.every((t) => I(e[t]) !== "");
}
function z(e, t, n) {
	if (!Number.isInteger(e) || !Number.isInteger(t) || !Number.isInteger(n)) return !1;
	let r = new Date(e, t - 1, n);
	return r.getFullYear() === e && r.getMonth() === t - 1 && r.getDate() === n;
}
function De(e) {
	let t = Number.parseInt(I(e.year), 10), n = Number.parseInt(I(e.month), 10), r = Number.parseInt(I(e.day), 10), i = I(e.hour) === "" ? 0 : Number.parseInt(I(e.hour), 10), a = I(e.minute) === "" ? 0 : Number.parseInt(I(e.minute), 10), o = I(e.second) === "" ? 0 : Number.parseInt(I(e.second), 10);
	return new Date(t, n - 1, r, i, a, o);
}
function Oe(e, t, n, r) {
	let i = e.validation.find((e) => e.type === "dateParts");
	if (!i || e.input.dateEnabled === !1) return;
	let a = t && typeof t == "object" ? t : {};
	if (!R(a, e)) return;
	if (!z(Number.parseInt(I(a.year), 10), Number.parseInt(I(a.month), 10), Number.parseInt(I(a.day), 10))) {
		let e = `${n}.day`;
		r[e] || (r[e] = ["Day is invalid."]);
		return;
	}
	let o = De(a);
	if (i.minDate) {
		let e = new Date(i.minDate);
		if (Number.isFinite(e.getTime()) && o < e) {
			r[n] = [`The date must be on or after ${e.toLocaleDateString()}.`];
			return;
		}
	}
	if (i.maxDate) {
		let e = new Date(i.maxDate);
		Number.isFinite(e.getTime()) && o > e && (r[n] = [`The date must be on or before ${e.toLocaleDateString()}.`]);
	}
}
//#endregion
//#region src/events.ts
var B = class {
	listeners = /* @__PURE__ */ new Map();
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
};
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
			fields: Object.fromEntries(Object.entries(e.errors.fields).map(([e, t]) => [e, [...t]]))
		},
		fieldStates: Object.fromEntries(Object.entries(e.fieldStates).map(([e, t]) => [e, { ...t }])),
		pageStates: Object.fromEntries(Object.entries(e.pageStates).map(([e, t]) => [e, { ...t }])),
		lastSubmitResult: e.lastSubmitResult ? {
			...e.lastSubmitResult,
			errors: {
				form: [...e.lastSubmitResult.errors.form],
				fields: Object.fromEntries(Object.entries(e.lastSubmitResult.errors.fields).map(([e, t]) => [e, [...t]]))
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
function U(e) {
	return Object.fromEntries(x(e.definition).map((e) => [e.id, N(e)]));
}
function ke(e) {
	return Object.fromEntries(x(e).map((e) => [e.id, {
		hidden: e.meta?.hidden === !0,
		disabled: e.meta?.disabled === !0
	}]));
}
function Ae(e) {
	return Object.fromEntries(e.pages.map((e) => [e.id, { hidden: !1 }]));
}
function je(e, t) {
	let n = e.definition.pages.find((e) => e.id === t);
	if (!n) return [];
	let r = [];
	return n.rows.forEach((e) => {
		e.fields.forEach((e) => {
			r.push(e.id);
		});
	}), r;
}
function W(e) {
	return ve(e);
}
function Me(e, t) {
	return e.type === "checkboxes" ? !Array.isArray(t) || t.length === 0 : re(e) ? t !== !0 : O(e) || D(e) || k(e) ? !Array.isArray(t) || t.length === 0 : E(e) && t && typeof t == "object" ? Object.values(t).every((e) => e == null || typeof e == "string" && e.trim() === "") : t == null || typeof t == "string" && t.trim() === "";
}
function G(e) {
	return e.label?.trim() || e.handle;
}
function K(e, t, n, r, i) {
	if (n.fieldStates[r]?.hidden || n.fieldStates[r]?.disabled) return;
	let a = e.validation.slice();
	e.required && !a.some((e) => e.type === "required") && a.unshift({ type: "required" });
	for (let o of a) {
		let a = (o.fieldId ? S(n.definition, o.fieldId) : void 0) || (o.fieldHandle ? C(n.definition, o.fieldHandle) : void 0), s = Se(t, o, {
			label: G(e),
			empty: Me(e, t),
			comparison: a ? n.values[a.id] : void 0,
			comparisonLabel: a ? G(a) : void 0
		});
		if (s) {
			i[r] = [s];
			return;
		}
	}
	if (E(e)) {
		let a = ae(e), o = t && typeof t == "object" ? t : {};
		a.forEach((e) => {
			e.meta?.hidden !== !0 && K(e, o[e.handle], n, `${r}.${e.handle}`, i);
		}), Oe(e, o, r, i);
		return;
	}
	if (D(e)) {
		let a = Array.isArray(t) ? t : [], o = M(e);
		a.forEach((e, t) => {
			let a = e && typeof e == "object" ? e : {};
			o.forEach((e) => {
				K(e, a[e.handle], n, `${r}.${t}.${e.handle}`, i);
			});
		});
	}
}
function q(e) {
	let t = {
		form: [],
		fields: {}
	};
	return je(e, e.currentPageId).forEach((n) => {
		let r = S(e.definition, n);
		r && e.fieldStates[n]?.hidden !== !0 && e.fieldStates[n]?.disabled !== !0 && K(r, e.values[n], e, n, t.fields);
	}), Object.keys(t.fields).length > 0 && (t.form = [e.definition.settings.validation.formErrorMessage || "Please correct the highlighted fields."]), t;
}
function J({ envelope: e, transport: t }) {
	Ee(e);
	let n = new B(), r = /* @__PURE__ */ new Set(), i = U(e), a = 0, o = {
		status: "ready",
		definition: e.definition,
		session: e.session,
		values: i,
		errors: {
			form: [],
			fields: {}
		},
		fieldStates: ke(e.definition),
		pageStates: Ae(e.definition),
		currentPageId: e.session.currentPageId || e.definition.settings.initialPageId,
		lastSubmitResult: null
	};
	o = W(o);
	let s = () => {
		if (o.status === "destroyed") return;
		let e = H(o);
		r.forEach((t) => {
			t(e);
		});
	}, c = (e) => {
		o.status !== "destroyed" && (o = e(o), s());
	}, l = (e) => o.status === "destroyed" || e !== a, u = () => void 0, d = async () => ({}), f = {
		setBrowserModuleGuard(e) {
			u = e;
		},
		getBrowserRequestOptions() {
			return {
				profile: "same-origin-browser",
				...t.browserRequestOptions
			};
		},
		setBrowserModulePreparation(e) {
			d = e;
		},
		id: e.session.id,
		getState() {
			return H(o);
		},
		subscribe(e) {
			return r.add(e), e(H(o)), () => {
				r.delete(e);
			};
		},
		setValue(e, t) {
			c((n) => {
				let r = Object.fromEntries(Object.entries(n.errors.fields).filter(([t]) => t !== e && !t.startsWith(`${e}.`)));
				return r[e] = [], W({
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
			c((t) => W({
				...t,
				values: {
					...t.values,
					...e
				}
			}));
		},
		async submit(e) {
			if (u(), o.status === "destroyed") return {
				success: !1,
				isFinalPage: !1,
				errors: {
					form: ["Form instance has been destroyed."],
					fields: {}
				},
				messages: { error: "Form instance has been destroyed." },
				session: o.session
			};
			if (o.status === "submitting") return {
				success: !1,
				isFinalPage: !1,
				errors: {
					form: ["A submission is already in progress."],
					fields: {}
				},
				messages: { error: "A submission is already in progress." },
				session: o.session
			};
			let r = o.definition.pages.find((e) => e.id === o.currentPageId), i = e || r?.actions.primary.type || "submit", s = i === "next" ? "submit" : i;
			if (s !== "back" && s !== "save" && o.definition.settings.validation.onSubmit) {
				let e = q(o);
				if (e.form.length > 0 || Object.keys(e.fields).length > 0) {
					let t = {
						success: !1,
						isFinalPage: !1,
						errors: e,
						messages: { error: e.form[0] || null },
						session: o.session
					};
					return c((n) => ({
						...n,
						errors: e,
						lastSubmitResult: t
					})), n.emit("formie:submit:result", t), t;
				}
			}
			let f = ++a;
			c((e) => ({
				...e,
				status: "submitting",
				errors: {
					form: [],
					fields: {}
				}
			}));
			try {
				let e = await d(s);
				if (l(f)) throw Error("Submission was cancelled before sending.");
				let r = await t.submit({
					browserData: e,
					definition: o.definition,
					session: o.session,
					values: o.values,
					action: s
				});
				return l(f) ? r : (c((e) => W({
					...e,
					status: "ready",
					session: r.session ?? e.session,
					currentPageId: r.session?.currentPageId || r.currentPageId || e.currentPageId,
					errors: r.errors,
					lastSubmitResult: r
				})), n.emit("formie:submit:result", r), (r.currentPageId || r.nextPageId) && n.emit("formie:page:navigate", {
					currentPageId: o.currentPageId,
					nextPageId: r.nextPageId || r.currentPageId
				}), r);
			} catch (e) {
				let t = e instanceof Error ? e.message : "Submission failed.", r = {
					success: !1,
					isFinalPage: !1,
					errors: {
						form: [t],
						fields: {}
					},
					messages: { error: t },
					session: o.session
				};
				return l(f) || (c((e) => ({
					...e,
					status: "ready",
					errors: r.errors,
					lastSubmitResult: r
				})), n.emit("formie:submit:result", r)), r;
			}
		},
		async setPage(e) {
			if (o.status === "destroyed" || o.status === "submitting") return;
			let r = ++a;
			if (!t.setPage) {
				c((t) => W({
					...t,
					status: "ready",
					currentPageId: e,
					session: {
						...t.session,
						currentPageId: e
					}
				}));
				return;
			}
			c((e) => ({
				...e,
				status: "refreshing"
			}));
			try {
				let i = await t.setPage({
					definition: o.definition,
					session: o.session,
					values: o.values,
					currentPageId: o.currentPageId,
					targetPageId: e
				});
				if (l(r)) return;
				if (!i.success || !i.session) {
					c((e) => ({
						...e,
						status: "ready",
						errors: i.errors,
						lastSubmitResult: i
					})), n.emit("formie:submit:result", i);
					return;
				}
				let a = i.session;
				c((e) => W({
					...e,
					status: "ready",
					session: a,
					currentPageId: a.currentPageId
				})), n.emit("formie:page:navigate", {
					currentPageId: o.currentPageId,
					nextPageId: e
				});
			} catch (t) {
				let i = t instanceof Error ? t.message : "Unable to change page.";
				l(r) || (c((e) => ({
					...e,
					status: "ready"
				})), n.emit("formie:page:navigate:error", {
					currentPageId: o.currentPageId,
					nextPageId: e,
					error: i
				}));
			}
		},
		async refreshSession() {
			if (o.status === "destroyed" || o.status === "submitting") return;
			let e = ++a;
			c((e) => ({
				...e,
				status: "refreshing"
			}));
			try {
				let r = await t.refreshSession({
					formHandle: o.definition.handle,
					siteId: o.definition.siteId ?? void 0,
					session: o.session
				});
				if (l(e)) return;
				c((e) => W({
					...e,
					status: "ready",
					session: r,
					currentPageId: r.currentPageId || e.currentPageId
				})), n.emit("formie:session:refreshed", r);
			} catch (t) {
				let r = t instanceof Error ? t.message : "Unable to refresh session.";
				l(e) || (c((e) => ({
					...e,
					status: "ready"
				})), n.emit("formie:session:refresh:error", { error: r }));
			}
		},
		reset() {
			o.status !== "destroyed" && (a += 1, c((t) => W({
				...t,
				status: "ready",
				session: e.session,
				values: V(i),
				errors: {
					form: [],
					fields: {}
				},
				currentPageId: e.session.currentPageId || e.definition.settings.initialPageId,
				lastSubmitResult: null
			})), n.emit("formie:state:reset", null));
		},
		async destroy() {
			a += 1, o = {
				...o,
				status: "destroyed"
			}, r.clear();
		},
		on(e, t) {
			return n.on(e, t);
		}
	};
	return queueMicrotask(() => {
		n.emit("formie:client:ready", f.getState());
	}), f;
}
//#endregion
//#region src/event-names.ts
var Y = [
	"formie:client:ready",
	"formie:submit:result",
	"formie:page:navigate",
	"formie:page:navigate:error",
	"formie:session:refreshed",
	"formie:session:refresh:error",
	"formie:state:reset"
], Ne = /* @__PURE__ */ c((/* @__PURE__ */ o(((e, t) => {
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
					if (e == null || !(e instanceof i) || e.tokens.length !== this.tokens.length) return !1;
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
						let n = 0, r = e.position;
						e.position = 0;
						for (let r of this.tokens) {
							let i = r.diff(e.current);
							i.length > 0 && t.push({
								index: n,
								diff: i
							}), e.position < e.tokens.length - 1 && e.next(), n++;
						}
						e.position = r;
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
				t(this, "test", (e, t = null) => this.type === e && (t === null || this.value === t)), t(this, "isEqualTo", (e) => e != null && e instanceof a && e.value == this.value && e.type === this.type && e.cursor === this.cursor), t(this, "diff", (e) => {
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
			for (i = 0; i < e.length; i++) if (o = e.charAt(i), r.indexOf(o) !== -1) {
				if (n += "\\", (h = o.charCodeAt(0)) < 32 || h > 126) switch (o) {
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
			} else n += o;
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
					if (t !== "matches") {
						if (t !== "contains") {
							if (t !== "starts with") {
								if (t !== "ends with") {
									if (t === "in" || t === "not in") {
										let n = t === "not in" ? "=== -1" : ">= 0";
										e.raw("(function(__l, __r){return __r.indexOf(__l) " + n + ";})(").compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")");
										return;
									}
									t === ".." ? e.raw("(function(__s, __e){var __r=[];for(var __i=__s;__i<=__e;__i++){__r.push(__i);}return __r;})(").compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")") : v.functions[t] === void 0 ? (v.operators[t] !== void 0 && (t = v.operators[t]), e.raw("(").compile(this.nodes.left).raw(" ").raw(t).raw(" ").compile(this.nodes.right).raw(")")) : e.raw(`${v.functions[t]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")");
								} else e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())");
							} else e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())");
						} else e.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())");
					} else e.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")");
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
		class ee extends _ {
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
		class x extends _ {
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
		class S extends _ {
			constructor(e) {
				super({}, { name: e }), t(this, "compile", (e) => {
					e.raw(this.attributes.name);
				}), t(this, "evaluate", (e, t) => t[this.attributes.name]), t(this, "toArray", () => [this.attributes.name]), this.name = "NameNode";
			}
		}
		class C extends _ {
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
		class w extends C {
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
		class T extends _ {
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
						case T.PROPERTY_CALL:
							e.compile(this.nodes.node).raw(t ? "?." : ".").raw(this.nodes.attribute.attributes.value);
							break;
						case T.METHOD_CALL:
							e.compile(this.nodes.node).raw(t ? "?." : ".").raw(this.nodes.attribute.attributes.value).raw("(").compile(this.nodes.fnArguments).raw(")");
							break;
						case T.ARRAY_CALL: e.compile(this.nodes.node).raw("[").compile(this.nodes.attribute).raw("]");
					}
				}), t(this, "evaluate", (e, t) => {
					switch (this.attributes.type) {
						case T.PROPERTY_CALL:
							let n = this.nodes.node.evaluate(e, t);
							if (n === null && (this.nodes.attribute.isNullSafe || this.attributes.is_null_coalesce)) return this.attributes.is_short_circuited = !0, null;
							if (n === null && this.isShortCircuited()) return null;
							let r = this.nodes.attribute.attributes.value;
							if (typeof n != "object") throw Error(`Unable to get property "${r}" on a non-object: ` + typeof n);
							return this.attributes.is_null_coalesce ? n[r] ?? null : n[r];
						case T.METHOD_CALL:
							let i = this.nodes.node.evaluate(e, t);
							if (i === null && this.nodes.attribute.isNullSafe) return this.attributes.is_short_circuited = !0, null;
							if (i === null && this.isShortCircuited()) return null;
							let a = this.nodes.attribute.attributes.value;
							if (typeof i != "object") throw Error(`Unable to call method "${a}" on a non-object: ` + typeof i);
							if (i[a] === void 0) throw Error(`Method "${a}" is undefined on object.`);
							if (typeof i[a] != "function") throw Error(`Method "${a}" is not a function on object.`);
							let o = this.nodes.fnArguments.evaluate(e, t);
							return i[a].apply(null, o);
						case T.ARRAY_CALL:
							let s = this.nodes.node.evaluate(e, t);
							if (s === null && this.isShortCircuited()) return null;
							if (!(Array.isArray(s) || typeof s == "object" || s === null && this.attributes.is_null_coalesce)) throw Error("Unable to get an item on a non-array: " + typeof s);
							return this.attributes.is_null_coalesce ? s ? s[this.nodes.attribute.evaluate(e, t)] ?? null : null : s[this.nodes.attribute.evaluate(e, t)];
					}
				}), t(this, "toArray", () => {
					let e = this.nodes.attribute instanceof b && this.nodes.attribute.isNullSafe;
					switch (this.attributes.type) {
						case T.PROPERTY_CALL: return [
							this.nodes.node,
							e ? "?." : ".",
							this.nodes.attribute
						];
						case T.METHOD_CALL: return [
							this.nodes.node,
							e ? "?." : ".",
							this.nodes.attribute,
							"(",
							this.nodes.fnArguments,
							")"
						];
						case T.ARRAY_CALL: return [
							this.nodes.node,
							"[",
							this.nodes.attribute,
							"]"
						];
					}
				}), this.name = "GetAttrNode";
			}
			isShortCircuited() {
				return this.attributes.is_short_circuited || this.nodes.node instanceof T && this.nodes.node.isShortCircuited();
			}
		}
		t(T, "PROPERTY_CALL", 1), t(T, "METHOD_CALL", 2), t(T, "ARRAY_CALL", 3);
		class te extends _ {
			constructor(e, n) {
				super({
					expr1: e,
					expr2: n
				}), t(this, "compile", (e) => {
					e.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))");
				}), t(this, "evaluate", (e, t) => (this.nodes.expr1 instanceof T && this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1), this.nodes.expr1.evaluate(e, t) ?? this.nodes.expr2.evaluate(e, t))), t(this, "toArray", () => [
					"(",
					this.nodes.expr1,
					") ?? (",
					this.nodes.expr2,
					")"
				]), t(this, "_addNullCoalesceAttributeToGetAttrNodes", (e) => {
					if (e instanceof T) {
						e.attributes.is_null_coalesce = !0;
						for (let t of Object.values(e.nodes)) this._addNullCoalesceAttributeToGetAttrNodes(t);
					}
				}), this.name = "NullCoalesceNode";
			}
		}
		class ne extends _ {
			constructor(e) {
				super({}, { name: e }), t(this, "compile", (e) => {
					e.raw(this.attributes.name + " ?? null");
				}), t(this, "evaluate", (e, t) => null), t(this, "toArray", () => [this.attributes.name + " ?? null"]), this.name = "NullCoalescedNameNode";
			}
		}
		class E {
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
					if (this.nestedExecutions++, this.nestedExecutions > 1e3) throw Error("Too many executions on '" + n.toString() + "' of '" + this.tokenStream.toString() + "'");
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
					let e = new C(), t = !0;
					for (; !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "]") && (t || (this.tokenStream.expect(a.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"), !this.tokenStream.current.test(a.PUNCTUATION_TYPE, "]")));) t = !1, e.addElement(this.parseExpression());
					return this.tokenStream.expect(a.PUNCTUATION_TYPE, "]", "An opened array is not properly closed"), e;
				}), t(this, "parseHashExpression", () => {
					this.tokenStream.expect(a.PUNCTUATION_TYPE, "{", "A hash element was expected");
					let e = new C(), t = !0;
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
							let i = new b(t.value, !0, n), o = new w(), s = null;
							if (this.tokenStream.current.test(a.PUNCTUATION_TYPE, "(")) {
								s = T.METHOD_CALL;
								for (let e of Object.values(this.parseArguments().nodes)) o.addElement(e);
							} else s = T.PROPERTY_CALL;
							e = new T(e, i, o, s);
						} else {
							if (t.value !== "[") break;
							{
								this.tokenStream.next();
								let t = this.parseExpression();
								this.tokenStream.expect(a.PUNCTUATION_TYPE, "]"), e = new T(e, t, new w(), T.ARRAY_CALL);
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
					e = new te(e, t);
				}
				for (; this.tokenStream.current.test(a.PUNCTUATION_TYPE, "?");) {
					let t, n;
					this.tokenStream.next(), this.tokenStream.current.test(a.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), t = e, n = this.parseExpression()) : (t = this.parseExpression(), this.tokenStream.current.test(a.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), n = this.parseExpression()) : t instanceof b && typeof t.attributes?.value == "string" ? n = new b("") : t instanceof ee ? (n = t.nodes.expr3, t = t.nodes.expr2) : (n = t, t = e)), e = new ee(e, t, n);
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
								t = new x(e.value, this.parseArguments());
							} else {
								let n = null;
								if (1 & this.flags) n = e.value;
								else {
									if (!this.hasVariable(e.value)) {
										if (this.tokenStream.current.test(a.PUNCTUATION_TYPE, "??")) return new ne(e.value);
										throw new r(`Variable "${e.value}" is not valid`, e.cursor, this.tokenStream.expression, e.value, this.getNames());
									}
									n = e.value, this.objectMatches[n] !== void 0 && (n = this.getNames()[this.objectMatches[n]]);
								}
								t = new S(n);
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
		class D {
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
					else if (Array.isArray(e)) {
						this.raw("[");
						let t = !0;
						for (let n of e) t || this.raw(", "), t = !1, this.repr(n);
						this.raw("]");
					} else if (typeof e == "object") {
						this.raw("{");
						let t = !0;
						for (let n of Object.keys(e)) t || this.raw(", "), t = !1, this.repr(n), this.raw(":"), this.repr(e[n]);
						this.raw("}");
					} else this.string(e);
					return this;
				}), this.source = "", this.functions = e;
			}
		}
		class O {
			constructor(e) {
				this.expression = e;
			}
			toString() {
				return this.expression;
			}
		}
		class k extends O {
			constructor(e, n) {
				super(e), t(this, "getNodes", () => this.nodes), this.nodes = n;
			}
			static fromJSON(e) {
				let t = typeof e == "string" ? JSON.parse(e) : e, n = (e) => {
					if (e == null || e instanceof _ || typeof e != "object" || !e.name) return e;
					switch (e.name) {
						case "ConstantNode": return new b(e.attributes?.value, !!e.isIdentifier, !!e.isNullSafe);
						case "NameNode": return new S(e.attributes?.name);
						case "NullCoalescedNameNode": return new ne(e.attributes?.name);
						case "UnaryNode": return new y(e.attributes?.operator, n(e.nodes?.node));
						case "BinaryNode": return new v(e.attributes?.operator, n(e.nodes?.left), n(e.nodes?.right));
						case "ConditionalNode": return new ee(n(e.nodes?.expr1), n(e.nodes?.expr2), n(e.nodes?.expr3));
						case "NullCoalesceNode": return new te(n(e.nodes?.expr1), n(e.nodes?.expr2));
						case "ArgumentsNode": {
							let t = new w();
							typeof e.type == "string" && (t.type = e.type), typeof e.index == "number" && (t.index = e.index), typeof e.keyIndex == "number" && (t.keyIndex = e.keyIndex), t.nodes = {};
							for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							return t;
						}
						case "ArrayNode": {
							let t = new C();
							typeof e.type == "string" && (t.type = e.type), typeof e.index == "number" && (t.index = e.index), typeof e.keyIndex == "number" && (t.keyIndex = e.keyIndex), t.nodes = {};
							for (let r of Object.keys(e.nodes || {})) t.nodes[r] = n(e.nodes[r]);
							return t;
						}
						case "FunctionNode": {
							let t = n(e.nodes?.fnArguments);
							return new x(e.attributes?.name, t);
						}
						case "GetAttrNode": {
							let t = new T(n(e.nodes?.node), n(e.nodes?.attribute), n(e.nodes?.fnArguments), e.attributes?.type);
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
				}, r = t.expression, i = ((e) => {
					if (e == null) return e;
					if (e.name) return n(e);
					if (Array.isArray(e)) return e.map(n);
					if (typeof e == "object") {
						let t = {};
						for (let r of Object.keys(e)) t[r] = n(e[r]);
						return t;
					}
					return e;
				})(t.nodes);
				return new k(r, i);
			}
		}
		var re;
		class ie {
			constructor(e = 0) {
				t(this, "createCacheItem", (e, t, n) => {
					let r = new A();
					return r.key = e, r.value = t, r.isHit = n, r.defaultLifetime = this.defaultLifetime, r;
				}), t(this, "get", (e, t, n = null, r = null) => {
					let i = this.getItem(e);
					return i.isHit || this.save(i.set(t(i, !0))), i.get();
				}), t(this, "getItem", (e) => {
					let t = this.hasItem(e), n = null;
					return t ? n = this.values[e] : this.values[e] = null, (0, this.createCacheItem)(e, n, t);
				}), t(this, "getItems", (e) => {
					for (let t of e) typeof t == "string" || this.expiries[t] || A.validateKey(t);
					return this.generateItems(e, (/* @__PURE__ */ new Date()).getTime() / 1e3, this.createCacheItem);
				}), t(this, "deleteItems", (e) => {
					for (let t of e) this.deleteItem(t);
					return !0;
				}), t(this, "save", (e) => e instanceof A && (e.expiry !== null && e.expiry <= (/* @__PURE__ */ new Date()).getTime() / 1e3 ? (this.deleteItem(e.key), !0) : (e.expiry === null && 0 < e.defaultLifetime && (e.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e.defaultLifetime), this.values[e.key] = e.value, this.expiries[e.key] = e.expiry || 2 ** 53 - 1, !0))), t(this, "saveDeferred", (e) => this.save(e)), t(this, "commit", () => !0), t(this, "delete", (e) => this.deleteItem(e)), t(this, "getValues", () => this.values), t(this, "hasItem", (e) => !!(typeof e == "string" && this.expiries[e] && this.expiries[e] > (/* @__PURE__ */ new Date()).getTime() / 1e3) || (A.validateKey(e), !!this.expiries[e] && !this.deleteItem(e))), t(this, "clear", () => (this.values = {}, this.expiries = {}, !0)), t(this, "deleteItem", (e) => (typeof e == "string" && this.expiries[e] || A.validateKey(e), delete this.values[e], delete this.expiries[e], !0)), t(this, "reset", () => {
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
		class A {
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
		re = A, t(A, "METADATA_EXPIRY_OFFSET", 1527506807), t(A, "RESERVED_CHARACTERS", [
			"{",
			"}",
			"(",
			")",
			"/",
			"\\",
			"@",
			":"
		]), t(A, "validateKey", (e) => {
			if (typeof e != "string") throw Error(`Cache key must be string, "${typeof e}" given.`);
			if (e === "") throw Error("Cache key length must be greater than zero");
			for (let t of re.RESERVED_CHARACTERS) if (e.indexOf(t) >= 0) throw Error(`Cache key "${e}" contains reserved character "${t}".`);
			return e;
		});
		class ae extends Error {
			constructor(e) {
				super(e), this.name = "LogicException";
			}
			toString() {
				return `${this.name}: ${this.message}`;
			}
		}
		class j {
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
		class M {
			constructor(e = null, n = []) {
				t(this, "compile", (e, t = []) => this.getCompiler().compile(this.parse(e, t).getNodes()).getSource()), t(this, "evaluate", (e, t = {}) => this.parse(e, Object.keys(t)).getNodes().evaluate(this.functions, t)), t(this, "parse", (e, t = [], n = 0) => {
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
					if (a === null) {
						let r = this.getParser().parse(this.getLexer().tokenize(e), t, n);
						a = new k(e, r), i.set(a), this.cache.save(i);
					}
					return a;
				}), t(this, "lint", (e, t = null, n = 0) => {
					t === null && (console.log("Deprecated: passing \"null\" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument"), n |= 1, t = []), e instanceof k || this.getParser().lint(this.getLexer().tokenize(e), t, n);
				}), t(this, "fixedEncodeURIComponent", (e) => encodeURIComponent(e).replace(/[!'()*]/g, function(e) {
					return "%" + e.charCodeAt(0).toString(16);
				})), t(this, "register", (e, t, n) => {
					if (this.parser !== null) throw new ae("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
					this.functions[e] = {
						compiler: t,
						evaluator: n
					};
				}), t(this, "addFunction", (e) => {
					this.register(e.getName(), e.getCompiler(), e.getEvaluator());
				}), t(this, "registerProvider", (e) => {
					for (let t of e.getFunctions()) this.addFunction(t);
				}), t(this, "getLexer", () => (this.lexer === null && (this.lexer = { tokenize: o }), this.lexer)), t(this, "getParser", () => (this.parser === null && (this.parser = new E(this.functions)), this.parser)), t(this, "getCompiler", () => (this.compiler === null && (this.compiler = new D(this.functions)), this.compiler.reset())), this.functions = [], this.lexer = null, this.parser = null, this.compiler = null, this.cache = e || new ie(), this._registerBuiltinFunctions();
				for (let e of n) this.registerProvider(e);
			}
			_registerBuiltinFunctions() {
				let e = j.fromJavascript("Math.min", "min"), t = j.fromJavascript("Math.max", "max");
				this.addFunction(e), this.addFunction(t), this.addFunction(new j("constant", function(e) {
					return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${e})`;
				}, function(e, t) {
					if (typeof t != "string" || !t) return;
					let n = (r = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : {}, t.split(".").reduce((e, t) => e?.[t], r));
					var r;
					return n === void 0 && e && Object.prototype.hasOwnProperty.call(e, t) && (n = e[t]), n;
				})), this.addFunction(new j("enum", function(e) {
					return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${e})`;
				}, function(e, t) {
					if (typeof t != "string" || !t) return;
					let n = String(t).replace(/\\/g, ".").replace(/::/g, ".");
					var r;
					return n ? (r = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : {}, n.split(".").reduce((e, t) => e?.[t], r)) : void 0;
				}));
			}
		}
		class N {
			getFunctions() {
				throw Error("getFunctions must be implemented by " + this.constructor.name);
			}
		}
		let oe = new j("isset", function(e) {
			if (/^(?:"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')$/.test(e)) throw Error("isset() does not support compile() when called with a string-literal path (e.g. isset(\"foo.bar\")). Use an expression path instead (e.g. isset(foo.bar) or isset(foo?.bar)), or call evaluate() directly.");
			return `(function(){try{var __v=(${e});return __v!==null&&__v!==undefined;}catch(e){return false;}})()`;
		}, function(e, t) {
			if (typeof t != "string") return t != null;
			if (!(t.split(/[.\[]/)[0] in e)) return !0;
			let n = "", r = [], i = "", a = "";
			for (let e = 0; e < t.length; e++) {
				let o = t[e];
				if (o !== "]") {
					if (o !== "[") {
						if (i === "object" && (!/[A-z0-9_]/.test(o) || e === t.length - 1)) {
							let n = !1;
							if (e === t.length - 1 && (a += o, n = !0), i = "", r.push({
								type: "object",
								attribute: a
							}), a = "", n) continue;
						}
						o === "." ? (i = "object", a = "") : i ? a += o : n += o;
					} else i = "array", a = "";
				} else i = "", r.push({
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
		});
		function P(...e) {
			let [t, n, r] = e, i = t, a = n;
			if (e.length < 2 || i === void 0 || a === void 0) return null;
			if (i === "" || !1 === i || i === null) return !1;
			if (typeof i == "function" || typeof i == "object" || typeof a == "function" || typeof a == "object") return { 0: "" };
			!0 === i && (i = "1");
			let o = i + "", s = (a + "").split(o);
			return r === void 0 ? s : (r === 0 && (r = 1), r > 0 ? r >= s.length ? s : s.slice(0, r - 1).concat([s.slice(r - 1).join(o)]) : -r >= s.length ? [] : (s.splice(s.length + r), s));
		}
		let se = (e) => Object.entries(e);
		function ce(e) {
			return typeof e == "object" && !!e;
		}
		function le(e) {
			return ce(e) && !function(e) {
				return Array.isArray(e);
			}(e);
		}
		function ue(e) {
			return function(e) {
				return ce(e);
			}(e) ? e : {};
		}
		let de = typeof window == "object" && window !== null ? window : typeof global == "object" && global !== null ? global : {};
		function fe() {
			let e = (() => {
				let e = de.$locutus;
				typeof e == "object" && e || (e = {}, de.$locutus = e);
				let t = e.php;
				return typeof t == "object" && t || (t = {}, e.php = t), t;
			})(), t = e.ini, n = e.locales, r = e.localeCategories, i = e.pointers, a = le(t) ? t : {}, o = ((e) => le(e))(n) ? n : {}, s = ((e) => le(e))(r) ? r : {}, c = Array.isArray(i) ? i : [];
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
		function pe(e) {
			let t = fe().ini[e];
			return t && t.local_value !== void 0 ? t.local_value === null ? "" : String(t.local_value) : "";
		}
		function me(e) {
			if (e === void 0) throw Error("strlen() expects exactly 1 argument, 0 given");
			let t = e + "";
			if ((pe("unicode.semantics") || "off") === "off") return t.length;
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
		}
		function he(e) {
			if (e === void 0) throw Error("strtolower() expects exactly 1 argument, 0 given");
			return (e + "").toLowerCase();
		}
		function ge(e) {
			if (e === void 0) throw Error("strtoupper() expects exactly 1 argument, 0 given");
			return (e + "").toUpperCase();
		}
		function F(e, t, n) {
			let r = function(e) {
				if (typeof e == "boolean") return e ? "1" : "";
				if (typeof e == "string") return e;
				if (typeof e == "number") return isNaN(e) ? "NAN" : isFinite(e) ? e + "" : (e < 0 ? "-" : "") + "INF";
				if (e === void 0) return "";
				if (typeof e == "object") return Array.isArray(e) ? "Array" : e === null ? "" : "Object";
				throw Error("Unsupported value type");
			}(e), i = pe("unicode.semantics") === "on" ? r.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [] : null, a = i ? i.length : r.length, o = a;
			return t < 0 && (t += o), n !== void 0 && (o = n < 0 ? n + o : n + t), !(t > a || t < 0 || t > o) && (i ? i.slice(t, o).join("") : r.slice(t, o));
		}
		function _e(e, t, n) {
			let r = 0;
			return r = (e += "").indexOf(t), r !== -1 && (n ? e.substr(0, r) : e.slice(r));
		}
		function ve(e, t, n) {
			let r = 0;
			return r = (e += "").toLowerCase().indexOf((t + "").toLowerCase()), r !== -1 && (n ? e.substr(0, r) : e.slice(r));
		}
		function ye(e, ...t) {
			let n = {};
			if (t.length < 1) return n;
			let r = ue(e);
			e: for (let [e, i] of se(r)) {
				for (let e of t) {
					let t = ue(e), n = !1;
					for (let [, e] of se(t)) if (e === i) {
						n = !0;
						break;
					}
					if (!n) continue e;
				}
				n[e] = i;
			}
			return n;
		}
		let be = (e) => {
			if (!e || typeof e != "object") return !1;
			let t = Object.getPrototypeOf(e);
			return t === Array.prototype || t === Object.prototype;
		};
		function xe(e, t = 0) {
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
					i && be(r) && (n += xe(r, 1));
				}
				return n;
			}
			for (let t of Object.values(e)) n++, i && be(t) && (n += xe(t, 1));
			return n;
		}
		function Se(...e) {
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
		}
		let Ce = new j("implode", function(e, t) {
			return `__runtime.implode(${e}, ${t})`;
		}, function(e, t, n) {
			return Se(t, n);
		}), we = new j("count", function(e, t) {
			let n = "";
			return t && (n = `, ${t}`), `__runtime.count(${e}${n})`;
		}, function(e, t, n) {
			return xe(t, n);
		}), Te = new j("array_intersect", function(e, ...t) {
			let n = "";
			return t.length > 0 && (n = ", " + t.join(", ")), `__runtime.array_intersect(${e}${n})`;
		}, function(e) {
			let t = [], n = !0;
			for (let e = 1; e < arguments.length; e++) t.push(arguments[e]), Array.isArray(arguments[e]) || (n = !1);
			let r = ye.apply(null, t);
			return n ? Object.values(r) : r;
		});
		function Ee(e, t) {
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
				return r = e, Object.hasOwn(n, r) ? String(n[e]()) : t;
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
		let I = "[ \\t]+", L = "[ \\t]*", R = "(?:([ap])\\.?m\\.?([\\t ]|$))", z = "(2[0-4]|[01]?[0-9])", De = "([01][0-9]|2[0-4])", Oe = "(0?[1-9]|1[0-2])", B = "([0-5]?[0-9])", V = "([0-5][0-9])", H = "(60|[0-5]?[0-9])", U = "(60|[0-5][0-9])", ke = "(?:\\.([0-9]+))", Ae = "sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?", je = "next|last|previous|this", W = "(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|" + Ae, Me = "([0-9]{1,4})", G = "([0-9]{4})", K = "(1[0-2]|0?[0-9])", q = "(0[0-9]|1[0-2])", J = "(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)", Y = "(0[0-9]|[1-2][0-9]|3[01])", Ne = "january|february|march|april|may|june|july|august|september|october|november|december", Pe = "jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec", Fe = "(" + Ne + "|" + Pe + "|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", Ie = "((?:GMT)?([+-])" + z + ":?" + B + "?)", Le = Fe + "[ .\\t-]*" + J + "[,.stndrh\\t ]*";
		function X(e, t) {
			switch (t?.toLowerCase()) {
				case "a":
					e += e === 12 ? -12 : 0;
					break;
				case "p": e += e === 12 ? 0 : 12;
			}
			return e;
		}
		function Z(e) {
			let t = +e;
			return e.length < 4 && t < 100 && (t += t < 70 ? 2e3 : 1900), t;
		}
		function Q(e) {
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
		function Re(e, t = 0) {
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
		function ze(e, t = NaN) {
			let n = e?.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);
			if (!n) return t;
			let r = n[1] === "-" ? -1 : 1, i = +(n[2] ?? 0), a = +(n[4] ?? 0);
			return n[4] || n[3] || (a = Math.floor(i % 100), i = Math.floor(i / 100)), r * (60 * i + a) * 60;
		}
		let Be = {
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
		}, $ = {
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
					this.firstOrLastDayOfMonth = t.toLowerCase() === "first" ? 1 : -1;
				}
			},
			backOrFrontOf: {
				regex: RegExp("^(back|front) of " + z + L + R + "?", "i"),
				name: "backof | frontof",
				callback(e, t, n, r) {
					let i = +n, a = 15;
					return t.toLowerCase() === "back" || (--i, a = 45), i = X(i, r), this.resetTime() && this.time(i, a, 0, 0);
				}
			},
			mssqltime: {
				regex: RegExp("^" + Oe + ":" + V + ":" + U + "[:.]([0-9]+)" + R, "i"),
				name: "mssqltime",
				callback(e, t, n, r, i, a) {
					return this.time(X(+t, a), +n, +r, +i.substr(0, 3));
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
				regex: RegExp("^" + Oe + "[:.]" + B + "[:.]" + U + L + R, "i"),
				name: "timelong12",
				callback(e, t, n, r, i) {
					return this.time(X(+t, i), +n, +r, 0);
				}
			},
			timeShort12: {
				regex: RegExp("^" + Oe + "[:.]" + V + L + R, "i"),
				name: "timeshort12",
				callback(e, t, n, r) {
					return this.time(X(+t, r), +n, 0, 0);
				}
			},
			timeTiny12: {
				regex: RegExp("^" + Oe + L + R, "i"),
				name: "timetiny12",
				callback(e, t, n) {
					return this.time(X(+t, n), 0, 0, 0);
				}
			},
			soap: {
				regex: RegExp("^" + G + "-" + q + "-" + Y + "T" + De + ":" + V + ":" + U + ke + Ie + "?", "i"),
				name: "soap",
				callback(e, t, n, r, i, a, o, s, c) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, +s.substr(0, 3)) && this.zone(ze(c));
				}
			},
			wddx: {
				regex: RegExp("^" + G + "-" + K + "-" + J + "T" + z + ":" + B + ":" + H),
				name: "wddx",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			exif: {
				regex: RegExp("^" + G + ":" + q + ":" + Y + " " + De + ":" + V + ":" + U, "i"),
				name: "exif",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			xmlRpc: {
				regex: RegExp("^" + G + q + Y + "T" + z + ":" + V + ":" + U),
				name: "xmlrpc",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			xmlRpcNoColon: {
				regex: RegExp("^" + G + q + Y + "[Tt]" + z + V + U),
				name: "xmlrpcnocolon",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(+t, n - 1, +r) && this.time(+i, +a, +o, 0);
				}
			},
			clf: {
				regex: RegExp("^" + J + "/(" + Pe + ")/" + G + ":" + De + ":" + V + ":" + U + I + Ie, "i"),
				name: "clf",
				callback(e, t, n, r, i, a, o, s) {
					return this.ymd(+r, Q(n), +t) && this.time(+i, +a, +o, 0) && this.zone(ze(s));
				}
			},
			iso8601long: {
				regex: RegExp("^t?" + z + "[:.]" + B + "[:.]" + H + ke, "i"),
				name: "iso8601long",
				callback(e, t, n, r, i) {
					return this.time(+t, +n, +r, +i.substr(0, 3));
				}
			},
			dateTextual: {
				regex: RegExp("^" + Fe + "[ .\\t-]*" + J + "[,.stndrh\\t ]+" + Me, "i"),
				name: "datetextual",
				callback(e, t, n, r) {
					return this.ymd(Z(r), Q(t), +n);
				}
			},
			pointedDate4: {
				regex: RegExp("^" + J + "[.\\t-]" + K + "[.-]" + G),
				name: "pointeddate4",
				callback(e, t, n, r) {
					return this.ymd(+r, n - 1, +t);
				}
			},
			pointedDate2: {
				regex: RegExp("^" + J + "[.\\t]" + K + "\\.([0-9]{2})"),
				name: "pointeddate2",
				callback(e, t, n, r) {
					return this.ymd(Z(r), n - 1, +t);
				}
			},
			timeLong24: {
				regex: RegExp("^t?" + z + "[:.]" + B + "[:.]" + H),
				name: "timelong24",
				callback(e, t, n, r) {
					return this.time(+t, +n, +r, 0);
				}
			},
			dateNoColon: {
				regex: RegExp("^" + G + q + Y),
				name: "datenocolon",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			pgydotd: {
				regex: RegExp("^" + G + "\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"),
				name: "pgydotd",
				callback(e, t, n) {
					return this.ymd(+t, 0, +n);
				}
			},
			timeShort24: {
				regex: RegExp("^t?" + z + "[:.]" + B, "i"),
				name: "timeshort24",
				callback(e, t, n) {
					return this.time(+t, +n, 0, 0);
				}
			},
			iso8601noColon: {
				regex: RegExp("^t?" + De + V + U, "i"),
				name: "iso8601nocolon",
				callback(e, t, n, r) {
					return this.time(+t, +n, +r, 0);
				}
			},
			iso8601dateSlash: {
				regex: RegExp("^" + G + "/" + q + "/" + Y + "/"),
				name: "iso8601dateslash",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			dateSlash: {
				regex: RegExp("^" + G + "/" + K + "/" + J),
				name: "dateslash",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			american: {
				regex: RegExp("^" + K + "/" + J + "/" + Me),
				name: "american",
				callback(e, t, n, r) {
					return this.ymd(Z(r), t - 1, +n);
				}
			},
			americanShort: {
				regex: RegExp("^" + K + "/" + J),
				name: "americanshort",
				callback(e, t, n) {
					return this.ymd(this.y, t - 1, +n);
				}
			},
			gnuDateShortOrIso8601date2: {
				regex: RegExp("^" + Me + "-" + K + "-" + J),
				name: "gnudateshort | iso8601date2",
				callback(e, t, n, r) {
					return this.ymd(Z(t), n - 1, +r);
				}
			},
			iso8601date4: {
				regex: RegExp("^([+-]?[0-9]{4})-" + q + "-" + Y),
				name: "iso8601date4",
				callback(e, t, n, r) {
					return this.ymd(+t, n - 1, +r);
				}
			},
			gnuNoColon: {
				regex: RegExp("^t?" + De + V, "i"),
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
				regex: RegExp("^" + G + "-" + K),
				name: "gnudateshorter",
				callback(e, t, n) {
					return this.ymd(+t, n - 1, 1);
				}
			},
			pgTextReverse: {
				regex: RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-(" + Pe + ")-" + Y, "i"),
				name: "pgtextreverse",
				callback(e, t, n, r) {
					return this.ymd(Z(t), Q(n), +r);
				}
			},
			dateFull: {
				regex: RegExp("^" + J + "[ \\t.-]*" + Fe + "[ \\t.-]*" + Me, "i"),
				name: "datefull",
				callback(e, t, n, r) {
					return this.ymd(Z(r), Q(n), +t);
				}
			},
			dateNoDay: {
				regex: RegExp("^" + Fe + "[ .\\t-]*" + G, "i"),
				name: "datenoday",
				callback(e, t, n) {
					return this.ymd(+n, Q(t), 1);
				}
			},
			dateNoDayRev: {
				regex: RegExp("^" + G + "[ .\\t-]*" + Fe, "i"),
				name: "datenodayrev",
				callback(e, t, n) {
					return this.ymd(+t, Q(n), 1);
				}
			},
			pgTextShort: {
				regex: RegExp("^(" + Pe + ")-" + Y + "-" + Me, "i"),
				name: "pgtextshort",
				callback(e, t, n, r) {
					return this.ymd(Z(r), Q(t), +n);
				}
			},
			dateNoYear: {
				regex: RegExp("^" + Le, "i"),
				name: "datenoyear",
				callback(e, t, n) {
					return this.ymd(this.y, Q(t), +n);
				}
			},
			dateNoYearRev: {
				regex: RegExp("^" + J + "[ .\\t-]*" + Fe, "i"),
				name: "datenoyearrev",
				callback(e, t, n) {
					return this.ymd(this.y, Q(n), +t);
				}
			},
			isoWeekDay: {
				regex: RegExp("^" + G + "-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"),
				name: "isoweekday | isoweek",
				callback(e, t, n, r) {
					let i = r ? +r : 1;
					if (!this.ymd(+t, 0, 1)) return !1;
					let a = new Date(this.y, this.m, this.d).getDay();
					return a = 0 - (a > 4 ? a - 7 : a), this.rd += a + 7 * (n - 1) + i, !0;
				}
			},
			relativeText: {
				regex: RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|" + je + ")" + I + "(" + W + ")", "i"),
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
						case "sunday": this.resetTime(), this.weekday = Re(n, 7), this.weekdayBehavior = 1, this.rd += 7 * (r > 0 ? r - 1 : r);
					}
				}
			},
			relative: {
				regex: RegExp("^([+-]*)[ \\t]*(\\d+)[ \\t]*(" + W + "|week)", "i"),
				name: "relative",
				callback(e, t, n, r) {
					let i = t.replace(/[^-]/g, "").length, a = +n * (-1) ** i;
					switch (r.toLowerCase()) {
						case "sec":
						case "secs":
						case "second":
						case "seconds":
							this.rs += a;
							break;
						case "min":
						case "mins":
						case "minute":
						case "minutes":
							this.ri += a;
							break;
						case "hour":
						case "hours":
							this.rh += a;
							break;
						case "day":
						case "days":
							this.rd += a;
							break;
						case "fortnight":
						case "fortnights":
						case "forthnight":
						case "forthnights":
							this.rd += 14 * a;
							break;
						case "week":
						case "weeks":
							this.rd += 7 * a;
							break;
						case "month":
						case "months":
							this.rm += a;
							break;
						case "year":
						case "years":
							this.ry += a;
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
						case "sunday": this.resetTime(), this.weekday = Re(r, 7), this.weekdayBehavior = 1, this.rd += 7 * (a > 0 ? a - 1 : a);
					}
				}
			},
			dayText: {
				regex: RegExp("^(" + Ae + ")", "i"),
				name: "daytext",
				callback(e, t) {
					this.resetTime(), this.weekday = Re(t, 0), this.weekdayBehavior !== 2 && (this.weekdayBehavior = 1);
				}
			},
			relativeTextWeek: {
				regex: RegExp("^(" + je + ")" + I + "week", "i"),
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
				regex: RegExp("^(" + Ne + "|" + Pe + ")", "i"),
				name: "monthfull | monthabbr",
				callback(e, t) {
					return this.ymd(this.y, Q(t), this.d);
				}
			},
			tzCorrection: {
				regex: RegExp("^" + Ie, "i"),
				name: "tzcorrection",
				callback(e) {
					return this.zone(ze(e));
				}
			},
			tzAbbr: {
				regex: /* @__PURE__ */ RegExp("^\\(?([a-zA-Z]{1,6})\\)?"),
				name: "tzabbr",
				callback(e, t) {
					let n = Be[t.toLowerCase()];
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
				regex: RegExp("^" + G),
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
				regex: RegExp("^" + Le + "t?" + z + "[:.]" + B + "[:.]" + H, "i"),
				name: "dateshortwithtimelong",
				callback(e, t, n, r, i, a) {
					return this.ymd(this.y, Q(t), +n) && this.time(+r, +i, +a, 0);
				}
			},
			dateShortWithTimeLong12: {
				regex: RegExp("^" + Le + Oe + "[:.]" + B + "[:.]" + U + L + R, "i"),
				name: "dateshortwithtimelong12",
				callback(e, t, n, r, i, a, o) {
					return this.ymd(this.y, Q(t), +n) && this.time(X(+r, o), +i, +a, 0);
				}
			},
			dateShortWithTimeShort: {
				regex: RegExp("^" + Le + "t?" + z + "[:.]" + B, "i"),
				name: "dateshortwithtimeshort",
				callback(e, t, n, r, i) {
					return this.ymd(this.y, Q(t), +n) && this.time(+r, +i, 0, 0);
				}
			},
			dateShortWithTimeShort12: {
				regex: RegExp("^" + Le + Oe + "[:.]" + V + L + R, "i"),
				name: "dateshortwithtimeshort12",
				callback(e, t, n, r, i, a) {
					return this.ymd(this.y, Q(t), +n) && this.time(X(+r, a), +i, 0, 0);
				}
			}
		}, Ve = {
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
		function He(e, t) {
			let n = t ?? Math.floor(Date.now() / 1e3), r = [
				$.yesterday,
				$.now,
				$.noon,
				$.midnightOrToday,
				$.tomorrow,
				$.timestamp,
				$.firstOrLastDay,
				$.backOrFrontOf,
				$.timeTiny12,
				$.timeShort12,
				$.timeLong12,
				$.mssqltime,
				$.oracledate,
				$.timeShort24,
				$.timeLong24,
				$.iso8601long,
				$.gnuNoColon,
				$.iso8601noColon,
				$.americanShort,
				$.american,
				$.iso8601date4,
				$.iso8601dateSlash,
				$.dateSlash,
				$.gnuDateShortOrIso8601date2,
				$.gnuDateShorter,
				$.dateFull,
				$.pointedDate4,
				$.pointedDate2,
				$.dateNoDay,
				$.dateNoDayRev,
				$.dateTextual,
				$.dateNoYear,
				$.dateNoYearRev,
				$.dateNoColon,
				$.xmlRpc,
				$.xmlRpcNoColon,
				$.soap,
				$.wddx,
				$.exif,
				$.pgydotd,
				$.isoWeekDay,
				$.pgTextShort,
				$.pgTextReverse,
				$.clf,
				$.year4,
				$.ago,
				$.dayText,
				$.relativeTextWeek,
				$.relativeText,
				$.monthFullOrMonthAbbr,
				$.tzCorrection,
				$.tzAbbr,
				$.dateShortWithTimeShort12,
				$.dateShortWithTimeLong12,
				$.dateShortWithTimeShort,
				$.dateShortWithTimeLong,
				$.relative,
				$.whitespace
			], i = { ...Ve };
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
		}
		function Ue(e) {
			return e && e.__esModule && Object.prototype.hasOwnProperty.call(e, "default") ? e.default : e;
		}
		var We, Ge = { exports: {} }, Ke = Ue((We || (We = 1, function(e) {
			e.exports = function() {
				var e = 1e3, t = 6e4, n = 36e5, r = "millisecond", i = "second", a = "minute", o = "hour", s = "day", c = "week", l = "month", u = "quarter", d = "year", f = "date", p = "Invalid Date", m = /^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/, h = /\[([^\]]+)]|YYYY|YY|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g, g = {
					name: "en",
					weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),
					months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_"),
					ordinal: function(e) {
						var t = [
							"th",
							"st",
							"nd",
							"rd"
						], n = e % 100;
						return "[" + e + (t[(n - 20) % 10] || t[n] || t[0]) + "]";
					}
				}, _ = function(e, t, n) {
					var r = String(e);
					return !r || r.length >= t ? e : "" + Array(t + 1 - r.length).join(n) + e;
				}, v = {
					s: _,
					z: function(e) {
						var t = -e.utcOffset(), n = Math.abs(t), r = Math.floor(n / 60), i = n % 60;
						return (t <= 0 ? "+" : "-") + _(r, 2, "0") + ":" + _(i, 2, "0");
					},
					m: function e(t, n) {
						if (t.date() < n.date()) return -e(n, t);
						var r = 12 * (n.year() - t.year()) + (n.month() - t.month()), i = t.clone().add(r, l), a = n - i < 0, o = t.clone().add(r + (a ? -1 : 1), l);
						return +(-(r + (n - i) / (a ? i - o : o - i)) || 0);
					},
					a: function(e) {
						return e < 0 ? Math.ceil(e) || 0 : Math.floor(e);
					},
					p: function(e) {
						return {
							M: l,
							y: d,
							w: c,
							d: s,
							D: f,
							h: o,
							m: a,
							s: i,
							ms: r,
							Q: u
						}[e] || String(e || "").toLowerCase().replace(/s$/, "");
					},
					u: function(e) {
						return e === void 0;
					}
				}, y = "en", b = {};
				b[y] = g;
				var ee = "$isDayjsObject", x = function(e) {
					return e instanceof T || !(!e || !e[ee]);
				}, S = function e(t, n, r) {
					var i;
					if (!t) return y;
					if (typeof t == "string") {
						var a = t.toLowerCase();
						b[a] && (i = a), n && (b[a] = n, i = a);
						var o = t.split("-");
						if (!i && o.length > 1) return e(o[0]);
					} else {
						var s = t.name;
						b[s] = t, i = s;
					}
					return !r && i && (y = i), i || !r && y;
				}, C = function(e, t) {
					if (x(e)) return e.clone();
					var n = typeof t == "object" ? t : {};
					return n.date = e, n.args = arguments, new T(n);
				}, w = v;
				w.l = S, w.i = x, w.w = function(e, t) {
					return C(e, {
						locale: t.$L,
						utc: t.$u,
						x: t.$x,
						$offset: t.$offset
					});
				};
				var T = function() {
					function g(e) {
						this.$L = S(e.locale, null, !0), this.parse(e), this.$x = this.$x || e.x || {}, this[ee] = !0;
					}
					var _ = g.prototype;
					return _.parse = function(e) {
						this.$d = function(e) {
							var t = e.date, n = e.utc;
							if (t === null) return /* @__PURE__ */ new Date(NaN);
							if (w.u(t)) return /* @__PURE__ */ new Date();
							if (t instanceof Date) return new Date(t);
							if (typeof t == "string" && !/Z$/i.test(t)) {
								var r = t.match(m);
								if (r) {
									var i = r[2] - 1 || 0, a = (r[7] || "0").substring(0, 3);
									return n ? new Date(Date.UTC(r[1], i, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, a)) : new Date(r[1], i, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, a);
								}
							}
							return new Date(t);
						}(e), this.init();
					}, _.init = function() {
						var e = this.$d;
						this.$y = e.getFullYear(), this.$M = e.getMonth(), this.$D = e.getDate(), this.$W = e.getDay(), this.$H = e.getHours(), this.$m = e.getMinutes(), this.$s = e.getSeconds(), this.$ms = e.getMilliseconds();
					}, _.$utils = function() {
						return w;
					}, _.isValid = function() {
						return this.$d.toString() !== p;
					}, _.isSame = function(e, t) {
						var n = C(e);
						return this.startOf(t) <= n && n <= this.endOf(t);
					}, _.isAfter = function(e, t) {
						return C(e) < this.startOf(t);
					}, _.isBefore = function(e, t) {
						return this.endOf(t) < C(e);
					}, _.$g = function(e, t, n) {
						return w.u(e) ? this[t] : this.set(n, e);
					}, _.unix = function() {
						return Math.floor(this.valueOf() / 1e3);
					}, _.valueOf = function() {
						return this.$d.getTime();
					}, _.startOf = function(e, t) {
						var n = this, r = !!w.u(t) || t, u = w.p(e), p = function(e, t) {
							var i = w.w(n.$u ? Date.UTC(n.$y, t, e) : new Date(n.$y, t, e), n);
							return r ? i : i.endOf(s);
						}, m = function(e, t) {
							return w.w(n.toDate()[e].apply(n.toDate("s"), (r ? [
								0,
								0,
								0,
								0
							] : [
								23,
								59,
								59,
								999
							]).slice(t)), n);
						}, h = this.$W, g = this.$M, _ = this.$D, v = "set" + (this.$u ? "UTC" : "");
						switch (u) {
							case d: return r ? p(1, 0) : p(31, 11);
							case l: return r ? p(1, g) : p(0, g + 1);
							case c:
								var y = this.$locale().weekStart || 0, b = (h < y ? h + 7 : h) - y;
								return p(r ? _ - b : _ + (6 - b), g);
							case s:
							case f: return m(v + "Hours", 0);
							case o: return m(v + "Minutes", 1);
							case a: return m(v + "Seconds", 2);
							case i: return m(v + "Milliseconds", 3);
							default: return this.clone();
						}
					}, _.endOf = function(e) {
						return this.startOf(e, !1);
					}, _.$set = function(e, t) {
						var n, c = w.p(e), u = "set" + (this.$u ? "UTC" : ""), p = (n = {}, n[s] = u + "Date", n[f] = u + "Date", n[l] = u + "Month", n[d] = u + "FullYear", n[o] = u + "Hours", n[a] = u + "Minutes", n[i] = u + "Seconds", n[r] = u + "Milliseconds", n)[c], m = c === s ? this.$D + (t - this.$W) : t;
						if (c === l || c === d) {
							var h = this.clone().set(f, 1);
							h.$d[p](m), h.init(), this.$d = h.set(f, Math.min(this.$D, h.daysInMonth())).$d;
						} else p && this.$d[p](m);
						return this.init(), this;
					}, _.set = function(e, t) {
						return this.clone().$set(e, t);
					}, _.get = function(e) {
						return this[w.p(e)]();
					}, _.add = function(r, u) {
						var f, p = this;
						r = Number(r);
						var m = w.p(u), h = function(e) {
							var t = C(p);
							return w.w(t.date(t.date() + Math.round(e * r)), p);
						};
						if (m === l) return this.set(l, this.$M + r);
						if (m === d) return this.set(d, this.$y + r);
						if (m === s) return h(1);
						if (m === c) return h(7);
						var g = (f = {}, f[a] = t, f[o] = n, f[i] = e, f)[m] || 1, _ = this.$d.getTime() + r * g;
						return w.w(_, this);
					}, _.subtract = function(e, t) {
						return this.add(-1 * e, t);
					}, _.format = function(e) {
						var t = this, n = this.$locale();
						if (!this.isValid()) return n.invalidDate || p;
						var r = e || "YYYY-MM-DDTHH:mm:ssZ", i = w.z(this), a = this.$H, o = this.$m, s = this.$M, c = n.weekdays, l = n.months, u = n.meridiem, d = function(e, n, i, a) {
							return e && (e[n] || e(t, r)) || i[n].slice(0, a);
						}, f = function(e) {
							return w.s(a % 12 || 12, e, "0");
						}, m = u || function(e, t, n) {
							var r = e < 12 ? "AM" : "PM";
							return n ? r.toLowerCase() : r;
						};
						return r.replace(h, function(e, r) {
							return r || function(e) {
								switch (e) {
									case "YY": return String(t.$y).slice(-2);
									case "YYYY": return w.s(t.$y, 4, "0");
									case "M": return s + 1;
									case "MM": return w.s(s + 1, 2, "0");
									case "MMM": return d(n.monthsShort, s, l, 3);
									case "MMMM": return d(l, s);
									case "D": return t.$D;
									case "DD": return w.s(t.$D, 2, "0");
									case "d": return String(t.$W);
									case "dd": return d(n.weekdaysMin, t.$W, c, 2);
									case "ddd": return d(n.weekdaysShort, t.$W, c, 3);
									case "dddd": return c[t.$W];
									case "H": return String(a);
									case "HH": return w.s(a, 2, "0");
									case "h": return f(1);
									case "hh": return f(2);
									case "a": return m(a, o, !0);
									case "A": return m(a, o, !1);
									case "m": return String(o);
									case "mm": return w.s(o, 2, "0");
									case "s": return String(t.$s);
									case "ss": return w.s(t.$s, 2, "0");
									case "SSS": return w.s(t.$ms, 3, "0");
									case "Z": return i;
								}
								return null;
							}(e) || i.replace(":", "");
						});
					}, _.utcOffset = function() {
						return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
					}, _.diff = function(r, f, p) {
						var m, h = this, g = w.p(f), _ = C(r), v = (_.utcOffset() - this.utcOffset()) * t, y = this - _, b = function() {
							return w.m(h, _);
						};
						switch (g) {
							case d:
								m = b() / 12;
								break;
							case l:
								m = b();
								break;
							case u:
								m = b() / 3;
								break;
							case c:
								m = (y - v) / 6048e5;
								break;
							case s:
								m = (y - v) / 864e5;
								break;
							case o:
								m = y / n;
								break;
							case a:
								m = y / t;
								break;
							case i:
								m = y / e;
								break;
							default: m = y;
						}
						return p ? m : w.a(m);
					}, _.daysInMonth = function() {
						return this.endOf(l).$D;
					}, _.$locale = function() {
						return b[this.$L];
					}, _.locale = function(e, t) {
						if (!e) return this.$L;
						var n = this.clone(), r = S(e, t, !0);
						return r && (n.$L = r), n;
					}, _.clone = function() {
						return w.w(this.$d, this);
					}, _.toDate = function() {
						return new Date(this.valueOf());
					}, _.toJSON = function() {
						return this.isValid() ? this.toISOString() : null;
					}, _.toISOString = function() {
						return this.$d.toISOString();
					}, _.toString = function() {
						return this.$d.toUTCString();
					}, g;
				}(), te = T.prototype;
				return C.prototype = te, [
					["$ms", r],
					["$s", i],
					["$m", a],
					["$H", o],
					["$W", s],
					["$M", l],
					["$y", d],
					["$D", f]
				].forEach(function(e) {
					te[e[1]] = function(t) {
						return this.$g(t, e[0], e[1]);
					};
				}), C.extend = function(e, t) {
					return e.$i ||= (e(t, T, C), !0), C;
				}, C.locale = S, C.isDayjs = x, C.unix = function(e) {
					return C(1e3 * e);
				}, C.en = b[y], C.Ls = b, C.p = {}, C;
			}();
		}(Ge)), Ge.exports));
		let qe = (e) => typeof e == "string", Je = (e, t) => e.format(t), Ye = {
			isString: qe,
			strLen: (e) => qe(e) ? e.length : 0,
			isEmail: (e) => !!qe(e) && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e),
			isPhone: (e) => !!qe(e) && (e.substring(0, 2) === "+1" && (e = e.substring(2)), /^\d{10}$/.test(e.replace(/\D/g, ""))),
			isNull: (e) => e === null,
			isCurrency: (e) => /(?=.*?\d)^\$?(([1-9]\d{0,2}(,\d{3})*)|\d+)?(\.\d{1,2})?$/.test(e),
			now: () => Ke(),
			dateFormat: Je,
			year: (e) => Je(e, "YYYY"),
			date: (e) => Je(e, "YYYY-MM-DD"),
			string: (e) => e == null || e.toString === void 0 ? "" : e.toString(),
			int: (e) => parseInt(e)
		}, Xe = {
			strtolower: he,
			strtoupper: ge,
			explode: P,
			strlen: me,
			strstr: _e,
			stristr: ve,
			substr: F,
			implode: Se,
			count: xe,
			array_intersect: (...e) => Te.getEvaluator()(null, ...e),
			date: Ee,
			strtotime: He
		};
		e.AbstractProvider = N, e.ArrayAdapter = ie, e.ArrayProvider = class extends N {
			getFunctions() {
				return [
					Ce,
					we,
					Te
				];
			}
		}, e.BasicProvider = class extends N {
			getFunctions() {
				return [oe];
			}
		}, e.CacheItem = A, e.CompileRuntime = Xe, e.Compiler = D, e.DateProvider = class extends N {
			getFunctions() {
				return [new j("date", function(e, t) {
					let n = "";
					return t && (n = `, ${t}`), `__runtime.date(${e}${n})`;
				}, function(e, t, n) {
					return Ee(t, n);
				}), new j("strtotime", function(e, t) {
					let n = "";
					return t && (n = `, ${t}`), `__runtime.strtotime(${e}${n})`;
				}, function(e, t, n) {
					return He(t, n);
				})];
			}
		}, e.Expression = O, e.ExpressionFunction = j, e.ExpressionLanguage = M, e.IGNORE_UNKNOWN_FUNCTIONS = 2, e.IGNORE_UNKNOWN_VARIABLES = 1, e.Node = _, e.OPERATOR_LEFT = 1, e.OPERATOR_RIGHT = 2, e.ParsedExpression = k, e.Parser = E, e.StringProvider = class extends N {
			getFunctions() {
				return [
					new j("strtolower", (e) => "__runtime.strtolower(" + e + ")", (e, t) => he(t)),
					new j("strtoupper", (e) => "__runtime.strtoupper(" + e + ")", (e, t) => ge(t)),
					new j("explode", (e, t, n = "null") => `__runtime.explode(${e}, ${t}, ${n})`, (e, t, n, r = null) => P(t, n, r)),
					new j("strlen", function(e) {
						return `__runtime.strlen(${e})`;
					}, function(e, t) {
						return me(t);
					}),
					new j("strstr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `__runtime.strstr(${e}, ${t}${r})`;
					}, function(e, t, n, r) {
						return _e(t, n, r);
					}),
					new j("stristr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `__runtime.stristr(${e}, ${t}${r})`;
					}, function(e, t, n, r) {
						return ve(t, n, r);
					}),
					new j("substr", function(e, t, n) {
						let r = "";
						return n && (r = `, ${n}`), `__runtime.substr(${e}, ${t}${r})`;
					}, function(e, t, n, r) {
						return F(t, n, r);
					})
				];
			}
		}, e.SyntaxError = r, e.Token = a, e.TokenStream = i, e.default = M, e.defaultCustomFunctions = Ye, e.tokenize = o, Object.defineProperty(e, "__esModule", { value: !0 });
	}), function(e) {
		var t = e.ExpressionLanguage;
		if (t && typeof t.ExpressionLanguage == "function") {
			var n = t.ExpressionLanguage;
			Object.keys(t).forEach(function(e) {
				e in n || (n[e] = t[e]);
			}), e.ExpressionLanguage = n;
		}
	}(typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : e);
})))(), 1), Pe = null;
function Fe() {
	let e = Ne, t = e.ExpressionLanguage || e.default || Ne;
	if (typeof t != "function") throw TypeError("Unable to resolve expression-language constructor.");
	return t;
}
function Ie() {
	return Pe ??= new (Fe())(), Pe;
}
function Le(e) {
	return (e.formula?.expression || e.formula?.formula || "").trim();
}
function X(e) {
	return Object.entries(e.formula?.variables || {}).filter((e) => !!e[1]?.sourceKey);
}
function Z(e, t) {
	return Object.entries(e).forEach(([t, n]) => {
		if (Array.isArray(n)) {
			let r = n.map((e) => typeof e == "string" && e.trim() !== "" && !Number.isNaN(Number(e)) ? Number(e) : e), i = r.filter((e) => typeof e == "number");
			e[t] = i.length === r.length && r.length > 0 ? i.reduce((e, t) => e + Number(t || 0), 0) : r;
			return;
		}
		typeof n == "string" && n.trim() !== "" && !Number.isNaN(Number(n)) && (e[t] = Number(n));
	}), e;
}
function Q(e, t) {
	if (t.formatting !== "number") return typeof e == "number" || typeof e == "string" ? e : "";
	let n = e;
	Array.isArray(n) && (n = n.reduce((e, t) => e + Number(t || 0), 0));
	let r = typeof t.decimals == "number" ? t.decimals : 0, i = Number(n || 0).toFixed(r);
	return `${t.prefix || ""}${i}${t.suffix || ""}`;
}
function Re(e, t) {
	let n = e.type?.endsWith("\\Number");
	return e.type?.endsWith("\\Checkboxes") ? Array.isArray(t) ? t.length ? t : "" : t ? [t] : "" : Array.isArray(t) ? t.length ? n ? t.map((e) => Number(e || 0)) : t : "" : n ? Number(t || 0) : t;
}
function ze(e, t, n) {
	return Q(Ie().evaluate(e, t), n);
}
//#endregion
//#region src/request-profile.ts
var Be = /* @__PURE__ */ new Map();
async function $(e, t, n = {}) {
	let r = n.profile ?? "same-origin-browser", i = Ve(e, n, t.headers), a = await fetch(e, {
		...t,
		headers: i,
		credentials: r === "cross-origin-public" ? "omit" : "same-origin"
	}), o = new URL(e, typeof location > "u" ? "http://localhost" : location.href).origin, s = a.headers.get("X-Formie-Session");
	return r === "cross-origin-public" && s && Be.set(o, s), a;
}
function Ve(e, t = {}, n) {
	let r = t.profile ?? "same-origin-browser";
	if (!["same-origin-browser", "cross-origin-public"].includes(r)) throw Error("Client-rendered forms require a public browser profile. Use administrative API mutations for trusted administration.");
	let i = new Headers(n);
	i.set("X-Formie-Profile", r);
	let a = new URL(e, typeof location > "u" ? "http://localhost" : location.href).origin;
	if (r === "cross-origin-public") {
		let e = t.publicSession ?? Be.get(a);
		e && i.set("X-Formie-Session", e);
	}
	return i;
}
//#endregion
//#region src/uploads.ts
var He = /* @__PURE__ */ new WeakMap();
async function Ue(e, t, n, r) {
	let i = async (n, a) => {
		if (typeof Blob < "u" && n instanceof Blob) {
			let i = JSON.stringify([
				e.id,
				t.tokens.render,
				t.continuation?.draftContextToken,
				a
			]), o = He.get(n) ?? /* @__PURE__ */ new Map();
			if (He.set(n, o), o.has(i)) return o.get(i);
			let s = (async () => {
				let i = new FormData();
				i.set("file", n, n instanceof File ? n.name : "upload"), i.set("handle", e.handle), e.siteId && i.set("siteId", String(e.siteId)), i.set("fieldHandle", a.replace(/\.\d+$/, "")), i.set("renderId", t.tokens.render ?? ""), i.set("draftContext", String(t.continuation?.draftContext ?? "")), i.set("draftContextToken", String(t.continuation?.draftContextToken ?? "")), t.tokens.csrf && i.set(t.tokens.csrf.name, t.tokens.csrf.value);
				let o = e.submission.uploadEndpoint;
				if (!o) throw Error("The form bootstrap does not provide a staged upload endpoint.");
				let s = await $(o, {
					method: "POST",
					headers: { Accept: "application/json" },
					body: i
				}, r), c = await s.json();
				if (!s.ok || !c.success || !c.uploadUid || !c.attachToken) throw Error("The file could not be staged. Please select it again.");
				return {
					uploadUid: c.uploadUid,
					attachToken: c.attachToken
				};
			})();
			o.set(i, s);
			try {
				return await s;
			} catch (e) {
				throw o.delete(i), e;
			}
		}
		return Array.isArray(n) ? Promise.all(n.map((e, t) => i(e, `${a}.${t}`))) : n && typeof n == "object" ? Object.fromEntries(await Promise.all(Object.entries(n).map(async ([e, t]) => [e, await i(t, `${a}.${e}`)]))) : n;
	}, a = x(e);
	return Object.fromEntries(await Promise.all(Object.entries(n).map(async ([e, t]) => [e, await i(t, a.find((t) => t.id === e)?.handle ?? e)])));
}
//#endregion
//#region src/grants.ts
function We(e) {
	if (!e || typeof window > "u") return;
	let t = new URL(window.location.href), n = !1;
	for (let r of [
		"grantToken",
		"resumeToken",
		"submissionEditToken"
	]) t.searchParams.get(r) === e && (t.searchParams.delete(r), n = !0);
	n && window.history.replaceState(window.history.state, "", t);
}
//#endregion
//#region src/rest.ts
function Ge(e, t) {
	if (t.startsWith("http://") || t.startsWith("https://")) return t;
	let n = t.startsWith("/") ? t : `/${t}`;
	if (e.startsWith("http://") || e.startsWith("https://")) {
		let t = new URL(e);
		return t.pathname = `${t.pathname.replace(/\/+$/, "")}${n}`, t.search = "", t.hash = "", t.toString();
	}
	let r = e.trim();
	return !r || r === "/" ? n : `${r.replace(/\/+$/, "")}${n}`;
}
async function Ke(e, t, n) {
	let r = await $(e, t, n), i = await r.json();
	if (!r.ok && !(typeof i.outcome == "string" && [
		403,
		409,
		422,
		429
	].includes(r.status))) throw Error(`Request failed with status ${r.status}.`);
	return i;
}
function qe(e, t) {
	let n = t?.tokens?.csrf;
	n?.name && n.value && (e[n.name] = n.value);
}
async function Je(e) {
	let t = Ge(e.endpoint, "/actions/formie/client/forms/load"), n = JSON.stringify({
		handle: e.formHandle,
		siteId: e.siteId,
		grantToken: e.grantToken,
		grantPurpose: e.grantPurpose,
		draftContext: e.draftContext,
		query: e.query
	}), r = await Ke(t, {
		method: "POST",
		credentials: e.credentials ?? "same-origin",
		headers: { "Content-Type": "application/json" },
		body: n
	}, e);
	return Ee(r), We(e.grantToken), r;
}
function Ye(e) {
	return {
		browserRequestOptions: {
			profile: e.profile ?? "same-origin-browser",
			publicSession: e.publicSession
		},
		async submit({ definition: t, session: n, values: r, action: i, browserData: a }) {
			let o = Ge(e.endpoint, "/actions/formie/client/submissions/submit"), s = await fe(t, await Ue(t, n, r, e)), c = {
				handle: e.formHandle,
				siteId: e.siteId,
				action: i,
				browserData: a,
				session: n,
				values: s
			};
			return qe(c, n), Ke(o, {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify(c)
			}, e);
		},
		async refreshSession({ session: t }) {
			let n = Ge(e.endpoint, "/actions/formie/client/sessions/refresh"), r = {
				handle: e.formHandle,
				siteId: e.siteId,
				session: t
			};
			return qe(r, t), Ke(n, {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify(r)
			}, e);
		},
		async setPage({ definition: t, session: n, values: r, currentPageId: i, targetPageId: a }) {
			let o = Ge(e.endpoint, "/actions/formie/client/forms/page"), s = await fe(t, await Ue(t, n, r, e)), c = {
				handle: e.formHandle,
				siteId: e.siteId,
				currentPageId: i,
				targetPageId: a,
				session: n,
				values: s
			};
			return qe(c, n), Ke(o, {
				method: "POST",
				credentials: e.credentials ?? "same-origin",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify(c)
			}, e);
		}
	};
}
//#endregion
//#region src/graphql.ts
var Xe = "\n    id\n    version\n    currentPageId\n    tokens\n    continuation\n", Ze = `
    success
    outcome
    version
    submissionUid
    resumeToken
    resumeUrl
    resumeTokenExpiresAt
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    clientEvents
    paymentStatus
    paymentMessage
    paymentRedirectUrl
    paymentAction
    paymentDecision
    keepSubmitLoading
    session {
        ${Xe}
    }
    quizResult
    completion
    redirect
`;
function Qe(e) {
	if (e.startsWith("http://") || e.startsWith("https://")) return e;
	let t = e.trim();
	return !t || t === "/" ? "/api" : t;
}
async function $e(e, t, n) {
	let r = await $(Qe(e.endpoint), {
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
	}, e);
	if (!r.ok) throw Error(`Request failed with status ${r.status}.`);
	let i = await r.json();
	if (i.errors?.length) throw Error(i.errors[0]?.message || "GraphQL returned an error.");
	if (!i.data) throw Error("GraphQL returned no data.");
	return i.data;
}
async function et(e) {
	let t = await $e(e, `
            query ClientForm($handle: String!, $siteId: Int, $grantToken: String, $grantPurpose: String, $draftContext: String, $query: Json) {
                formieClientForm(handle: $handle, siteId: $siteId, grantToken: $grantToken, grantPurpose: $grantPurpose, draftContext: $draftContext, query: $query) {
                    contractVersion
                    definition
                    session {
                        ${Xe}
                    }
                }
            }
        `, {
		handle: e.formHandle,
		siteId: e.siteId,
		grantToken: e.grantToken,
		grantPurpose: e.grantPurpose,
		draftContext: e.draftContext,
		query: e.query
	});
	if (!t.formieClientForm) throw Error("No client form definition was returned.");
	return Ee(t.formieClientForm), We(e.grantToken), t.formieClientForm;
}
function tt(e) {
	return {
		browserRequestOptions: {
			profile: e.profile ?? "same-origin-browser",
			publicSession: e.publicSession
		},
		async submit({ definition: t, session: n, values: r, action: i, browserData: a }) {
			let o = await fe(t, await Ue(t, n, r, e)), s = await $e(e, `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${Ze}
                        }
                    }
                `, { input: {
				handle: e.formHandle,
				siteId: e.siteId,
				action: i,
				browserData: a,
				session: n,
				values: o
			} });
			if (!s.submitFormieClientForm) throw Error("No client submit result was returned.");
			return s.submitFormieClientForm;
		},
		async refreshSession({ session: t }) {
			let n = await $e(e, `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${Xe}
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
			let o = await fe(t, await Ue(t, n, r, e)), s = await $e(e, `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            success outcome httpStatus errors messages currentPageId nextPageId version session { ${Xe} }
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
var nt = (() => {
	let e = Intl.Segmenter;
	return e ? new e(void 0, { granularity: "grapheme" }) : null;
})(), rt = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;
function it(e) {
	return typeof DOMParser < "u" ? new DOMParser().parseFromString(e, "text/html").body.textContent || "" : e.replace(/<[^>]*>/g, " ");
}
function at(e) {
	return it(e);
}
function ot(e) {
	return at(e).replace(/[\s\t\n\r]+/g, " ").trim();
}
function st(e) {
	return nt ? Array.from(nt.segment(e)).length : Array.from(e).length;
}
function ct(e) {
	return e.match(rt)?.length || 0;
}
function lt(e) {
	let t = at(e), n = ot(e);
	return {
		graphemeCount: st(t),
		wordCount: ct(n)
	};
}
//#endregion
//#region src/accessibility.ts
function ut(e) {
	return e.settings.validation.errorAriaLive || "polite";
}
function dt(e, t) {
	return `formie-${e.tokens.render || e.id}-${t}-errors`;
}
//#endregion
//#region src/references.ts
var ft = { username: "user:name" };
Object.entries({
	form: ["name", "handle"],
	submission: [
		"id",
		"uid",
		"title",
		"url",
		"date",
		"site",
		"status"
	],
	site: [
		"id",
		"name",
		"handle",
		"url",
		"language"
	]
}).forEach(([e, t]) => {
	t.forEach((t) => {
		ft[`${e}.${t}`] = `${e}:${t}`;
	});
}), Object.entries({
	form: ["Name", "Handle"],
	submission: [
		"Title",
		"Url",
		"Id",
		"Uid",
		"Date",
		"Site",
		"Status"
	],
	system: [
		"Name",
		"Email",
		"ReplyTo"
	],
	site: [
		"Name",
		"Handle",
		"Url",
		"Id",
		"Language"
	],
	user: [
		"Ip",
		"Id",
		"Email",
		"FullName",
		"FirstName",
		"LastName"
	]
}).forEach(([e, t]) => {
	t.forEach((t) => {
		ft[e + t] = `${e}:${t[0].toLowerCase()}${t.slice(1)}`;
	});
}), Object.entries({
	dateUs: "m/d/Y",
	dateInt: "d/m/Y",
	time12: "h:i a",
	time24: "H:i"
}).forEach(([e, t]) => {
	ft[e] = `timestamp;transform=format;preset=custom;pattern=${encodeURIComponent(t)}`;
});
function pt(e) {
	let t = {
		raw: e,
		target: "",
		identifier: "",
		selector: "",
		default: "",
		transformerId: "",
		transformerParams: {},
		version: 1,
		isValid: !1
	}, n = (e) => ({
		...t,
		diagnostic: e
	}), r = e.trim().match(/^\{([^{}]+)\}$/);
	if (!r) return n("invalidSyntax");
	let i = r[1].replace(/^field\./, "field:"), a = i.indexOf("|"), o = a < 0 ? "" : i.slice(a + 1);
	i = a < 0 ? i : i.slice(0, a);
	let s = i.indexOf(";"), c = s < 0 ? i : i.slice(0, s);
	i = (Object.prototype.hasOwnProperty.call(ft, c) ? ft[c] : c) + (s < 0 ? "" : i.slice(s));
	let [l, ...u] = i.split(";"), d = Object.create(null);
	try {
		for (let e of u) {
			let t = e.match(/^([a-zA-Z][a-zA-Z0-9_]*)=(.*)$/);
			if (!t || Object.prototype.hasOwnProperty.call(d, t[1])) return n("invalidMetadata");
			d[t[1]] = decodeURIComponent(t[2]);
		}
		if ((d.v ?? "1") !== "1") return n("unsupportedVersion");
		delete d.v;
		let e = l.indexOf(":"), r = e < 0 ? l : l.slice(0, e), i = e < 0 ? "" : l.slice(e + 1);
		if (!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(r)) return n("invalidSource");
		let a = "";
		if (r === "field" && i.includes(":")) {
			let e = i.indexOf(":");
			a = i.slice(e + 1), i = i.slice(0, e);
		}
		if (![
			"timestamp",
			"allFields",
			"allContentFields",
			"allVisibleFields"
		].includes(r) && !i || /[\s{}]/.test(i + a)) return n("invalidIdentifier");
		let s = d.transform ?? "";
		return delete d.transform, {
			...t,
			target: r,
			identifier: decodeURIComponent(i),
			selector: decodeURIComponent(a),
			default: decodeURIComponent(o),
			transformerId: s,
			transformerParams: d,
			isValid: !0
		};
	} catch {
		return n("invalidEncoding");
	}
}
function mt(e) {
	if (!e.isValid || e.version !== 1) throw Error("Cannot serialize an invalid reference expression.");
	let t = (e) => encodeURIComponent(e).replace(/[!'()*]/g, (e) => `%${e.charCodeAt(0).toString(16).toUpperCase()}`), n = (e) => t(e).replace(/%2F/g, "/"), r = e.target;
	e.identifier && (r += `:${n(e.identifier)}`), e.selector && (r += `:${n(e.selector).replace(/%3A/g, ":")}`), e.transformerId && (r += `;transform=${t(e.transformerId)}`);
	for (let [n, i] of Object.entries(e.transformerParams)) r += `;${n}=${t(i)}`;
	return e.default && (r += `|${t(e.default)}`), `{${r}}`;
}
function ht(e, t) {
	let n = pt(e);
	if (!n.isValid) return {
		expression: n,
		diagnostic: "invalidExpression"
	};
	let r = `${n.target}:${n.identifier}`, i = (e, t) => Object.prototype.hasOwnProperty.call(e, t);
	if (!i(t.definitions, r)) return {
		expression: n,
		diagnostic: n.target === "field" ? "missingField" : "unknownSource"
	};
	let a = t.definitions[r];
	if (!a.availability.browser) return {
		expression: n,
		diagnostic: "forbiddenSource"
	};
	if (Object.keys(n.transformerParams).some((e) => [
		"scope",
		"index",
		"rows"
	].includes(e))) return {
		expression: n,
		diagnostic: "invalidRowScope"
	};
	if (n.selector && !a.selectors?.includes(n.selector)) return {
		expression: n,
		diagnostic: "invalidSelector"
	};
	let o = n.selector ? `${r}:${n.selector}` : r;
	if (!i(t.values, o)) return {
		expression: n,
		diagnostic: "missingField"
	};
	let s = t.values[o];
	if (n.transformerId) {
		let e = t.transforms?.[n.transformerId];
		if (!e) return {
			expression: n,
			diagnostic: "unknownTransform"
		};
		if (!e.browser) return {
			expression: n,
			diagnostic: "forbiddenSource"
		};
		if (a.transforms && !a.transforms.includes(n.transformerId) || !e.accepts(s) || Object.keys(n.transformerParams).some((t) => !e.parameters.includes(t)) || (s = e.resolve(s, n.transformerParams), !e.acceptsOutput(s))) return {
			expression: n,
			diagnostic: "invalidType"
		};
	} else if (Object.keys(n.transformerParams).length) return {
		expression: n,
		diagnostic: "invalidExpression"
	};
	return (s === "" || s == null || Array.isArray(s) && s.length === 0) && n.default && (s = n.default), {
		expression: n,
		value: s
	};
}
//#endregion
export { Ce as BROWSER_MODULE_CONTRACT_VERSION, Te as CLIENT_FORM_CONTRACT_VERSION, Y as CLIENT_FORM_EVENT_NAMES, x as allFields, we as assertBrowserModuleManifest, Ee as assertClientFormBootstrap, $ as browserRequest, Ve as browserRequestHeaders, xe as browserValueEmpty, Ge as buildActionUrl, _e as clientActionAllowed, Z as coerceCalculationVariables, v as combineConditions, ae as compositePartDefinitions, m as conditionNumber, st as countGraphemes, J as createClientFormInstance, tt as createGraphqlClientTransport, oe as createRepeaterRowValue, Ye as createRestClientTransport, N as defaultValueForField, ze as evaluateCalculationExpression, F as evaluateClientCondition, _ as evaluateCondition, y as evaluateConditionDefinition, P as fieldValueAsStrings, te as fieldValueContract, ne as fieldValueStructure, b as finalizeConditionEvaluation, C as findFieldByHandle, S as findFieldById, Q as formatCalculationValue, Le as getCalculationFormula, X as getCalculationVariableEntries, ut as getClientErrorAriaLive, dt as getClientFieldErrorId, lt as getTextLimitMetrics, ct as getWordCount, re as isBooleanField, E as isCompositeField, A as isEmailField, O as isFileField, T as isKnownClientFieldType, k as isMultiValueField, ie as isNumericField, D as isRepeatableField, Je as loadClientFormBootstrap, et as loadGraphqlClientFormBootstrap, ot as normalizeText, pt as parseReference, Re as readCalculationVariableValue, be as registerBrowserValidationRule, M as repeaterFieldDefinitions, j as repeaterRowDefinitions, ht as resolveReference, l as selectConditionRows, w as serializeFieldValues, mt as serializeReference, fe as serializeTransportFieldValues, Se as validateBrowserValue };
