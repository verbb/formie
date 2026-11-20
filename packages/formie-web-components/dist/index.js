import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, createFormieClient as n, mountClientRenderedModules as r } from "@verbb/formie-browser";
import { CLIENT_FORM_EVENT_NAMES as i, PHONE_COUNTRY_CHANGE_EVENT as a, VISUALLY_HIDDEN_CSS as o, attachPointerReorder as s, clientActionAllowed as c, compositePartDefinitions as l, createClientFormInstance as u, createGraphqlClientTransport as d, createRepeaterRowValue as f, createRestClientTransport as p, createTableRowValue as m, displayFieldContent as h, dropdownOptions as g, fieldOptions as _, fieldValueStructure as ee, focusClientFormResult as te, getClientErrorAriaLive as ne, getClientFieldIds as re, getClientFieldLabelTarget as ie, getClientPageProgress as ae, getClientString as v, isArrayValuedField as oe, isCalculationField as se, isCompositeField as ce, isFieldLabelHidden as le, isFieldLabelVisuallyHidden as ue, isFileField as de, isKnownClientFieldType as fe, isPhoneCountryEnabled as pe, isRepeatableField as me, joinIdReferences as he, loadClientFormBootstrap as ge, loadGraphqlClientFormBootstrap as _e, moveListItem as ve, paymentFieldMarkup as ye, phoneFieldValue as be, readPhoneFieldControls as xe, repeaterRowDefinitions as Se, resolveClientRendererType as Ce, showsClientPageTitle as we, surveyLikertRows as Te, surveyRankOrder as Ee, tableColumns as De } from "@verbb/formie-core";
//#region ../../node_modules/@lit/reactive-element/css-tag.js
var y = globalThis, Oe = y.ShadowRoot && (y.ShadyCSS === void 0 || y.ShadyCSS.nativeShadow) && "adoptedStyleSheets" in Document.prototype && "replace" in CSSStyleSheet.prototype, ke = Symbol(), Ae = /* @__PURE__ */ new WeakMap(), je = class {
	constructor(e, t, n) {
		if (this._$cssResult$ = !0, n !== ke) throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");
		this.cssText = e, this.t = t;
	}
	get styleSheet() {
		let e = this.o, t = this.t;
		if (Oe && e === void 0) {
			let n = t !== void 0 && t.length === 1;
			n && (e = Ae.get(t)), e === void 0 && ((this.o = e = new CSSStyleSheet()).replaceSync(this.cssText), n && Ae.set(t, e));
		}
		return e;
	}
	toString() {
		return this.cssText;
	}
}, Me = (e) => new je(typeof e == "string" ? e : e + "", void 0, ke), Ne = (e, ...t) => new je(e.length === 1 ? e[0] : t.reduce((t, n, r) => t + ((e) => {
	if (!0 === e._$cssResult$) return e.cssText;
	if (typeof e == "number") return e;
	throw Error("Value passed to 'css' function must be a 'css' function result: " + e + ". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.");
})(n) + e[r + 1], e[0]), e, ke), Pe = (e, t) => {
	if (Oe) e.adoptedStyleSheets = t.map((e) => e instanceof CSSStyleSheet ? e : e.styleSheet);
	else for (let n of t) {
		let t = document.createElement("style"), r = y.litNonce;
		r !== void 0 && t.setAttribute("nonce", r), t.textContent = n.cssText, e.appendChild(t);
	}
}, Fe = Oe ? (e) => e : (e) => e instanceof CSSStyleSheet ? ((e) => {
	let t = "";
	for (let n of e.cssRules) t += n.cssText;
	return Me(t);
})(e) : e, { is: Ie, defineProperty: Le, getOwnPropertyDescriptor: Re, getOwnPropertyNames: ze, getOwnPropertySymbols: Be, getPrototypeOf: Ve } = Object, b = globalThis, He = b.trustedTypes, Ue = He ? He.emptyScript : "", We = b.reactiveElementPolyfillSupport, x = (e, t) => e, S = {
	toAttribute(e, t) {
		switch (t) {
			case Boolean:
				e = e ? Ue : null;
				break;
			case Object:
			case Array: e = e == null ? e : JSON.stringify(e);
		}
		return e;
	},
	fromAttribute(e, t) {
		let n = e;
		switch (t) {
			case Boolean:
				n = e !== null;
				break;
			case Number:
				n = e === null ? null : Number(e);
				break;
			case Object:
			case Array: try {
				n = JSON.parse(e);
			} catch {
				n = null;
			}
		}
		return n;
	}
}, Ge = (e, t) => !Ie(e, t), Ke = {
	attribute: !0,
	type: String,
	converter: S,
	reflect: !1,
	useDefault: !1,
	hasChanged: Ge
};
Symbol.metadata ??= Symbol("metadata"), b.litPropertyMetadata ??= /* @__PURE__ */ new WeakMap();
var C = class extends HTMLElement {
	static addInitializer(e) {
		this._$Ei(), (this.l ??= []).push(e);
	}
	static get observedAttributes() {
		return this.finalize(), this._$Eh && [...this._$Eh.keys()];
	}
	static createProperty(e, t = Ke) {
		if (t.state && (t.attribute = !1), this._$Ei(), this.prototype.hasOwnProperty(e) && ((t = Object.create(t)).wrapped = !0), this.elementProperties.set(e, t), !t.noAccessor) {
			let n = Symbol(), r = this.getPropertyDescriptor(e, n, t);
			r !== void 0 && Le(this.prototype, e, r);
		}
	}
	static getPropertyDescriptor(e, t, n) {
		let { get: r, set: i } = Re(this.prototype, e) ?? {
			get() {
				return this[t];
			},
			set(e) {
				this[t] = e;
			}
		};
		return {
			get: r,
			set(t) {
				let a = r?.call(this);
				i?.call(this, t), this.requestUpdate(e, a, n);
			},
			configurable: !0,
			enumerable: !0
		};
	}
	static getPropertyOptions(e) {
		return this.elementProperties.get(e) ?? Ke;
	}
	static _$Ei() {
		if (this.hasOwnProperty(x("elementProperties"))) return;
		let e = Ve(this);
		e.finalize(), e.l !== void 0 && (this.l = [...e.l]), this.elementProperties = new Map(e.elementProperties);
	}
	static finalize() {
		if (this.hasOwnProperty(x("finalized"))) return;
		if (this.finalized = !0, this._$Ei(), this.hasOwnProperty(x("properties"))) {
			let e = this.properties, t = [...ze(e), ...Be(e)];
			for (let n of t) this.createProperty(n, e[n]);
		}
		let e = this[Symbol.metadata];
		if (e !== null) {
			let t = litPropertyMetadata.get(e);
			if (t !== void 0) for (let [e, n] of t) this.elementProperties.set(e, n);
		}
		this._$Eh = /* @__PURE__ */ new Map();
		for (let [e, t] of this.elementProperties) {
			let n = this._$Eu(e, t);
			n !== void 0 && this._$Eh.set(n, e);
		}
		this.elementStyles = this.finalizeStyles(this.styles);
	}
	static finalizeStyles(e) {
		let t = [];
		if (Array.isArray(e)) {
			let n = new Set(e.flat(1 / 0).reverse());
			for (let e of n) t.unshift(Fe(e));
		} else e !== void 0 && t.push(Fe(e));
		return t;
	}
	static _$Eu(e, t) {
		let n = t.attribute;
		return !1 === n ? void 0 : typeof n == "string" ? n : typeof e == "string" ? e.toLowerCase() : void 0;
	}
	constructor() {
		super(), this._$Ep = void 0, this.isUpdatePending = !1, this.hasUpdated = !1, this._$Em = null, this._$Ev();
	}
	_$Ev() {
		this._$ES = new Promise((e) => this.enableUpdating = e), this._$AL = /* @__PURE__ */ new Map(), this._$E_(), this.requestUpdate(), this.constructor.l?.forEach((e) => e(this));
	}
	addController(e) {
		(this._$EO ??= /* @__PURE__ */ new Set()).add(e), this.renderRoot !== void 0 && this.isConnected && e.hostConnected?.();
	}
	removeController(e) {
		this._$EO?.delete(e);
	}
	_$E_() {
		let e = /* @__PURE__ */ new Map(), t = this.constructor.elementProperties;
		for (let n of t.keys()) this.hasOwnProperty(n) && (e.set(n, this[n]), delete this[n]);
		e.size > 0 && (this._$Ep = e);
	}
	createRenderRoot() {
		let e = this.shadowRoot ?? this.attachShadow(this.constructor.shadowRootOptions);
		return Pe(e, this.constructor.elementStyles), e;
	}
	connectedCallback() {
		this.renderRoot ??= this.createRenderRoot(), this.enableUpdating(!0), this._$EO?.forEach((e) => e.hostConnected?.());
	}
	enableUpdating(e) {}
	disconnectedCallback() {
		this._$EO?.forEach((e) => e.hostDisconnected?.());
	}
	attributeChangedCallback(e, t, n) {
		this._$AK(e, n);
	}
	_$ET(e, t) {
		let n = this.constructor.elementProperties.get(e), r = this.constructor._$Eu(e, n);
		if (r !== void 0 && !0 === n.reflect) {
			let i = (n.converter?.toAttribute === void 0 ? S : n.converter).toAttribute(t, n.type);
			this._$Em = e, i == null ? this.removeAttribute(r) : this.setAttribute(r, i), this._$Em = null;
		}
	}
	_$AK(e, t) {
		let n = this.constructor, r = n._$Eh.get(e);
		if (r !== void 0 && this._$Em !== r) {
			let e = n.getPropertyOptions(r), i = typeof e.converter == "function" ? { fromAttribute: e.converter } : e.converter?.fromAttribute === void 0 ? S : e.converter;
			this._$Em = r;
			let a = i.fromAttribute(t, e.type);
			this[r] = a ?? this._$Ej?.get(r) ?? a, this._$Em = null;
		}
	}
	requestUpdate(e, t, n, r = !1, i) {
		if (e !== void 0) {
			let a = this.constructor;
			if (!1 === r && (i = this[e]), n ??= a.getPropertyOptions(e), !((n.hasChanged ?? Ge)(i, t) || n.useDefault && n.reflect && i === this._$Ej?.get(e) && !this.hasAttribute(a._$Eu(e, n)))) return;
			this.C(e, t, n);
		}
		!1 === this.isUpdatePending && (this._$ES = this._$EP());
	}
	C(e, t, { useDefault: n, reflect: r, wrapped: i }, a) {
		n && !(this._$Ej ??= /* @__PURE__ */ new Map()).has(e) && (this._$Ej.set(e, a ?? t ?? this[e]), !0 !== i || a !== void 0) || (this._$AL.has(e) || (this.hasUpdated || n || (t = void 0), this._$AL.set(e, t)), !0 === r && this._$Em !== e && (this._$Eq ??= /* @__PURE__ */ new Set()).add(e));
	}
	async _$EP() {
		this.isUpdatePending = !0;
		try {
			await this._$ES;
		} catch (e) {
			Promise.reject(e);
		}
		let e = this.scheduleUpdate();
		return e != null && await e, !this.isUpdatePending;
	}
	scheduleUpdate() {
		return this.performUpdate();
	}
	performUpdate() {
		if (!this.isUpdatePending) return;
		if (!this.hasUpdated) {
			if (this.renderRoot ??= this.createRenderRoot(), this._$Ep) {
				for (let [e, t] of this._$Ep) this[e] = t;
				this._$Ep = void 0;
			}
			let e = this.constructor.elementProperties;
			if (e.size > 0) for (let [t, n] of e) {
				let { wrapped: e } = n, r = this[t];
				!0 !== e || this._$AL.has(t) || r === void 0 || this.C(t, void 0, n, r);
			}
		}
		let e = !1, t = this._$AL;
		try {
			e = this.shouldUpdate(t), e ? (this.willUpdate(t), this._$EO?.forEach((e) => e.hostUpdate?.()), this.update(t)) : this._$EM();
		} catch (t) {
			throw e = !1, this._$EM(), t;
		}
		e && this._$AE(t);
	}
	willUpdate(e) {}
	_$AE(e) {
		this._$EO?.forEach((e) => e.hostUpdated?.()), this.hasUpdated || (this.hasUpdated = !0, this.firstUpdated(e)), this.updated(e);
	}
	_$EM() {
		this._$AL = /* @__PURE__ */ new Map(), this.isUpdatePending = !1;
	}
	get updateComplete() {
		return this.getUpdateComplete();
	}
	getUpdateComplete() {
		return this._$ES;
	}
	shouldUpdate(e) {
		return !0;
	}
	update(e) {
		this._$Eq &&= this._$Eq.forEach((e) => this._$ET(e, this[e])), this._$EM();
	}
	updated(e) {}
	firstUpdated(e) {}
};
C.elementStyles = [], C.shadowRootOptions = { mode: "open" }, C[x("elementProperties")] = /* @__PURE__ */ new Map(), C[x("finalized")] = /* @__PURE__ */ new Map(), We?.({ ReactiveElement: C }), (b.reactiveElementVersions ??= []).push("2.1.2");
//#endregion
//#region ../../node_modules/lit-html/lit-html.js
var qe = globalThis, Je = (e) => e, w = qe.trustedTypes, Ye = w ? w.createPolicy("lit-html", { createHTML: (e) => e }) : void 0, Xe = "$lit$", T = `lit$${Math.random().toFixed(9).slice(2)}$`, Ze = "?" + T, Qe = `<${Ze}>`, E = document, D = () => E.createComment(""), O = (e) => e === null || typeof e != "object" && typeof e != "function", $e = Array.isArray, et = (e) => $e(e) || typeof e?.[Symbol.iterator] == "function", tt = "[ 	\n\f\r]", k = /<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g, nt = /-->/g, rt = />/g, A = RegExp(`>|${tt}(?:([^\\s"'>=/]+)(${tt}*=${tt}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`, "g"), it = /'/g, at = /"/g, ot = /^(?:script|style|textarea|title)$/i, j = ((e) => (t, ...n) => ({
	_$litType$: e,
	strings: t,
	values: n
}))(1), M = Symbol.for("lit-noChange"), N = Symbol.for("lit-nothing"), st = /* @__PURE__ */ new WeakMap(), P = E.createTreeWalker(E, 129);
function ct(e, t) {
	if (!$e(e) || !e.hasOwnProperty("raw")) throw Error("invalid template strings array");
	return Ye === void 0 ? t : Ye.createHTML(t);
}
var lt = (e, t) => {
	let n = e.length - 1, r = [], i, a = t === 2 ? "<svg>" : t === 3 ? "<math>" : "", o = k;
	for (let t = 0; t < n; t++) {
		let n = e[t], s, c, l = -1, u = 0;
		for (; u < n.length && (o.lastIndex = u, c = o.exec(n), c !== null);) u = o.lastIndex, o === k ? c[1] === "!--" ? o = nt : c[1] === void 0 ? c[2] === void 0 ? c[3] !== void 0 && (o = A) : (ot.test(c[2]) && (i = RegExp("</" + c[2], "g")), o = A) : o = rt : o === A ? c[0] === ">" ? (o = i ?? k, l = -1) : c[1] === void 0 ? l = -2 : (l = o.lastIndex - c[2].length, s = c[1], o = c[3] === void 0 ? A : c[3] === "\"" ? at : it) : o === at || o === it ? o = A : o === nt || o === rt ? o = k : (o = A, i = void 0);
		let d = o === A && e[t + 1].startsWith("/>") ? " " : "";
		a += o === k ? n + Qe : l >= 0 ? (r.push(s), n.slice(0, l) + Xe + n.slice(l) + T + d) : n + T + (l === -2 ? t : d);
	}
	return [ct(e, a + (e[n] || "<?>") + (t === 2 ? "</svg>" : t === 3 ? "</math>" : "")), r];
}, ut = class e {
	constructor({ strings: t, _$litType$: n }, r) {
		let i;
		this.parts = [];
		let a = 0, o = 0, s = t.length - 1, c = this.parts, [l, u] = lt(t, n);
		if (this.el = e.createElement(l, r), P.currentNode = this.el.content, n === 2 || n === 3) {
			let e = this.el.content.firstChild;
			e.replaceWith(...e.childNodes);
		}
		for (; (i = P.nextNode()) !== null && c.length < s;) {
			if (i.nodeType === 1) {
				if (i.hasAttributes()) for (let e of i.getAttributeNames()) if (e.endsWith(Xe)) {
					let t = u[o++], n = i.getAttribute(e).split(T), r = /([.?@])?(.*)/.exec(t);
					c.push({
						type: 1,
						index: a,
						name: r[2],
						strings: n,
						ctor: r[1] === "." ? ft : r[1] === "?" ? pt : r[1] === "@" ? mt : L
					}), i.removeAttribute(e);
				} else e.startsWith(T) && (c.push({
					type: 6,
					index: a
				}), i.removeAttribute(e));
				if (ot.test(i.tagName)) {
					let e = i.textContent.split(T), t = e.length - 1;
					if (t > 0) {
						i.textContent = w ? w.emptyScript : "";
						for (let n = 0; n < t; n++) i.append(e[n], D()), P.nextNode(), c.push({
							type: 2,
							index: ++a
						});
						i.append(e[t], D());
					}
				}
			} else if (i.nodeType === 8) {
				if (i.data === Ze) c.push({
					type: 2,
					index: a
				});
				else {
					let e = -1;
					for (; (e = i.data.indexOf(T, e + 1)) !== -1;) c.push({
						type: 7,
						index: a
					}), e += T.length - 1;
				}
			}
			a++;
		}
	}
	static createElement(e, t) {
		let n = E.createElement("template");
		return n.innerHTML = e, n;
	}
};
function F(e, t, n = e, r) {
	if (t === M) return t;
	let i = r === void 0 ? n._$Cl : n._$Co?.[r], a = O(t) ? void 0 : t._$litDirective$;
	return i?.constructor !== a && (i?._$AO?.(!1), a === void 0 ? i = void 0 : (i = new a(e), i._$AT(e, n, r)), r === void 0 ? n._$Cl = i : (n._$Co ??= [])[r] = i), i !== void 0 && (t = F(e, i._$AS(e, t.values), i, r)), t;
}
var dt = class {
	constructor(e, t) {
		this._$AV = [], this._$AN = void 0, this._$AD = e, this._$AM = t;
	}
	get parentNode() {
		return this._$AM.parentNode;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	u(e) {
		let { el: { content: t }, parts: n } = this._$AD, r = (e?.creationScope ?? E).importNode(t, !0);
		P.currentNode = r;
		let i = P.nextNode(), a = 0, o = 0, s = n[0];
		for (; s !== void 0;) {
			if (a === s.index) {
				let t;
				s.type === 2 ? t = new I(i, i.nextSibling, this, e) : s.type === 1 ? t = new s.ctor(i, s.name, s.strings, this, e) : s.type === 6 && (t = new ht(i, this, e)), this._$AV.push(t), s = n[++o];
			}
			a !== s?.index && (i = P.nextNode(), a++);
		}
		return P.currentNode = E, r;
	}
	p(e) {
		let t = 0;
		for (let n of this._$AV) n !== void 0 && (n.strings === void 0 ? n._$AI(e[t]) : (n._$AI(e, n, t), t += n.strings.length - 2)), t++;
	}
}, I = class e {
	get _$AU() {
		return this._$AM?._$AU ?? this._$Cv;
	}
	constructor(e, t, n, r) {
		this.type = 2, this._$AH = N, this._$AN = void 0, this._$AA = e, this._$AB = t, this._$AM = n, this.options = r, this._$Cv = r?.isConnected ?? !0;
	}
	get parentNode() {
		let e = this._$AA.parentNode, t = this._$AM;
		return t !== void 0 && e?.nodeType === 11 && (e = t.parentNode), e;
	}
	get startNode() {
		return this._$AA;
	}
	get endNode() {
		return this._$AB;
	}
	_$AI(e, t = this) {
		e = F(this, e, t), O(e) ? e === N || e == null || e === "" ? (this._$AH !== N && this._$AR(), this._$AH = N) : e !== this._$AH && e !== M && this._(e) : e._$litType$ === void 0 ? e.nodeType === void 0 ? et(e) ? this.k(e) : this._(e) : this.T(e) : this.$(e);
	}
	O(e) {
		return this._$AA.parentNode.insertBefore(e, this._$AB);
	}
	T(e) {
		this._$AH !== e && (this._$AR(), this._$AH = this.O(e));
	}
	_(e) {
		this._$AH !== N && O(this._$AH) ? this._$AA.nextSibling.data = e : this.T(E.createTextNode(e)), this._$AH = e;
	}
	$(e) {
		let { values: t, _$litType$: n } = e, r = typeof n == "number" ? this._$AC(e) : (n.el === void 0 && (n.el = ut.createElement(ct(n.h, n.h[0]), this.options)), n);
		if (this._$AH?._$AD === r) this._$AH.p(t);
		else {
			let e = new dt(r, this), n = e.u(this.options);
			e.p(t), this.T(n), this._$AH = e;
		}
	}
	_$AC(e) {
		let t = st.get(e.strings);
		return t === void 0 && st.set(e.strings, t = new ut(e)), t;
	}
	k(t) {
		$e(this._$AH) || (this._$AH = [], this._$AR());
		let n = this._$AH, r, i = 0;
		for (let a of t) i === n.length ? n.push(r = new e(this.O(D()), this.O(D()), this, this.options)) : r = n[i], r._$AI(a), i++;
		i < n.length && (this._$AR(r && r._$AB.nextSibling, i), n.length = i);
	}
	_$AR(e = this._$AA.nextSibling, t) {
		for (this._$AP?.(!1, !0, t); e !== this._$AB;) {
			let t = Je(e).nextSibling;
			Je(e).remove(), e = t;
		}
	}
	setConnected(e) {
		this._$AM === void 0 && (this._$Cv = e, this._$AP?.(e));
	}
}, L = class {
	get tagName() {
		return this.element.tagName;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	constructor(e, t, n, r, i) {
		this.type = 1, this._$AH = N, this._$AN = void 0, this.element = e, this.name = t, this._$AM = r, this.options = i, n.length > 2 || n[0] !== "" || n[1] !== "" ? (this._$AH = Array(n.length - 1).fill(/* @__PURE__ */ new String()), this.strings = n) : this._$AH = N;
	}
	_$AI(e, t = this, n, r) {
		let i = this.strings, a = !1;
		if (i === void 0) e = F(this, e, t, 0), a = !O(e) || e !== this._$AH && e !== M, a && (this._$AH = e);
		else {
			let r = e, o, s;
			for (e = i[0], o = 0; o < i.length - 1; o++) s = F(this, r[n + o], t, o), s === M && (s = this._$AH[o]), a ||= !O(s) || s !== this._$AH[o], s === N ? e = N : e !== N && (e += (s ?? "") + i[o + 1]), this._$AH[o] = s;
		}
		a && !r && this.j(e);
	}
	j(e) {
		e === N ? this.element.removeAttribute(this.name) : this.element.setAttribute(this.name, e ?? "");
	}
}, ft = class extends L {
	constructor() {
		super(...arguments), this.type = 3;
	}
	j(e) {
		this.element[this.name] = e === N ? void 0 : e;
	}
}, pt = class extends L {
	constructor() {
		super(...arguments), this.type = 4;
	}
	j(e) {
		this.element.toggleAttribute(this.name, !!e && e !== N);
	}
}, mt = class extends L {
	constructor(e, t, n, r, i) {
		super(e, t, n, r, i), this.type = 5;
	}
	_$AI(e, t = this) {
		if ((e = F(this, e, t, 0) ?? N) === M) return;
		let n = this._$AH, r = e === N && n !== N || e.capture !== n.capture || e.once !== n.once || e.passive !== n.passive, i = e !== N && (n === N || r);
		r && this.element.removeEventListener(this.name, this, n), i && this.element.addEventListener(this.name, this, e), this._$AH = e;
	}
	handleEvent(e) {
		typeof this._$AH == "function" ? this._$AH.call(this.options?.host ?? this.element, e) : this._$AH.handleEvent(e);
	}
}, ht = class {
	constructor(e, t, n) {
		this.element = e, this.type = 6, this._$AN = void 0, this._$AM = t, this.options = n;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	_$AI(e) {
		F(this, e);
	}
}, gt = {
	M: Xe,
	P: T,
	A: Ze,
	C: 1,
	L: lt,
	R: dt,
	D: et,
	V: F,
	I,
	H: L,
	N: pt,
	U: mt,
	B: ft,
	F: ht
}, _t = qe.litHtmlPolyfillSupport;
_t?.(ut, I), (qe.litHtmlVersions ??= []).push("3.3.3");
var vt = (e, t, n) => {
	let r = n?.renderBefore ?? t, i = r._$litPart$;
	if (i === void 0) {
		let e = n?.renderBefore ?? null;
		r._$litPart$ = i = new I(t.insertBefore(D(), e), e, void 0, n ?? {});
	}
	return i._$AI(e), i;
}, yt = globalThis, R = class extends C {
	constructor() {
		super(...arguments), this.renderOptions = { host: this }, this._$Do = void 0;
	}
	createRenderRoot() {
		let e = super.createRenderRoot();
		return this.renderOptions.renderBefore ??= e.firstChild, e;
	}
	update(e) {
		let t = this.render();
		this.hasUpdated || (this.renderOptions.isConnected = this.isConnected), super.update(e), this._$Do = vt(t, this.renderRoot, this.renderOptions);
	}
	connectedCallback() {
		super.connectedCallback(), this._$Do?.setConnected(!0);
	}
	disconnectedCallback() {
		super.disconnectedCallback(), this._$Do?.setConnected(!1);
	}
	render() {
		return M;
	}
};
R._$litElement$ = !0, R.finalized = !0, yt.litElementHydrateSupport?.({ LitElement: R });
var bt = yt.litElementPolyfillSupport;
bt?.({ LitElement: R }), (yt.litElementVersions ??= []).push("4.2.2");
//#endregion
//#region ../../node_modules/lit-html/static.js
var xt = Symbol.for(""), St = (e) => {
	if (e?.r === xt) return e?._$litStatic$;
}, Ct = (e) => ({
	_$litStatic$: e,
	r: xt
}), wt = /* @__PURE__ */ new Map(), z = ((e) => (t, ...n) => {
	let r = n.length, i, a, o = [], s = [], c, l = 0, u = !1;
	for (; l < r;) {
		for (c = t[l]; l < r && (a = n[l], (i = St(a)) !== void 0);) c += i + t[++l], u = !0;
		l !== r && s.push(a), o.push(c), l++;
	}
	if (l === r && o.push(t[r]), u) {
		let e = o.join("$$lit$$");
		(t = wt.get(e)) === void 0 && (o.raw = o, wt.set(e, t = o)), n = s;
	}
	return e(t, ...n);
})(j), Tt = {
	attribute: !0,
	type: String,
	converter: S,
	reflect: !1,
	hasChanged: Ge
}, Et = (e = Tt, t, n) => {
	let { kind: r, metadata: i } = n, a = globalThis.litPropertyMetadata.get(i);
	if (a === void 0 && globalThis.litPropertyMetadata.set(i, a = /* @__PURE__ */ new Map()), r === "setter" && ((e = Object.create(e)).wrapped = !0), a.set(n.name, e), r === "accessor") {
		let { name: r } = n;
		return {
			set(n) {
				let i = t.get.call(this);
				t.set.call(this, n), this.requestUpdate(r, i, e, !0, n);
			},
			init(t) {
				return t !== void 0 && this.C(r, void 0, e, t), t;
			}
		};
	}
	if (r === "setter") {
		let { name: r } = n;
		return function(n) {
			let i = this[r];
			t.call(this, n), this.requestUpdate(r, i, e, !0, n);
		};
	}
	throw Error("Unsupported decorator location: " + r);
};
function B(e) {
	return (t, n) => typeof n == "object" ? Et(e, t, n) : ((e, t, n) => {
		let r = t.hasOwnProperty(n);
		return t.constructor.createProperty(n, e), r ? Object.getOwnPropertyDescriptor(t, n) : void 0;
	})(e, t, n);
}
//#endregion
//#region ../../node_modules/@lit/reactive-element/decorators/state.js
function Dt(e) {
	return B({
		...e,
		state: !0,
		attribute: !1
	});
}
//#endregion
//#region src/registry.ts
var Ot = /^[a-z][a-z0-9]*(-[a-z0-9]+)+$/;
function V(e) {
	if (!Ot.test(e)) throw Error(`[Formie WC] Invalid custom element tag "${e}". Use lowercase hyphenated names (e.g. my-text-field).`);
}
var kt = class e {
	constructor() {
		this.fieldControls = {}, this.fieldTag = null, this.regions = {};
	}
	registerFieldControl(e, t) {
		return V(t), this.fieldControls[e] = t, this;
	}
	registerField(e) {
		return V(e), this.fieldTag = e, this;
	}
	registerRegion(e, t) {
		return V(t), this.regions[e] = t, this;
	}
	clone() {
		let t = new e();
		return t.fieldControls = { ...this.fieldControls }, t.fieldTag = this.fieldTag, t.regions = { ...this.regions }, t;
	}
}, At = new kt();
function jt() {
	return At;
}
function Mt() {
	return new kt();
}
//#endregion
//#region ../../node_modules/lit-html/directive-helpers.js
var { I: Nt } = gt, Pt = (e) => e, Ft = (e) => e.strings === void 0, It = () => document.createComment(""), H = (e, t, n) => {
	let r = e._$AA.parentNode, i = t === void 0 ? e._$AB : t._$AA;
	if (n === void 0) n = new Nt(r.insertBefore(It(), i), r.insertBefore(It(), i), e, e.options);
	else {
		let t = n._$AB.nextSibling, a = n._$AM, o = a !== e;
		if (o) {
			let t;
			n._$AQ?.(e), n._$AM = e, n._$AP !== void 0 && (t = e._$AU) !== a._$AU && n._$AP(t);
		}
		if (t !== i || o) {
			let e = n._$AA;
			for (; e !== t;) {
				let t = Pt(e).nextSibling;
				Pt(r).insertBefore(e, i), e = t;
			}
		}
	}
	return n;
}, U = (e, t, n = e) => (e._$AI(t, n), e), Lt = {}, Rt = (e, t = Lt) => e._$AH = t, zt = (e) => e._$AH, Bt = (e) => {
	e._$AR(), e._$AA.remove();
}, Vt = {
	ATTRIBUTE: 1,
	CHILD: 2,
	PROPERTY: 3,
	BOOLEAN_ATTRIBUTE: 4,
	EVENT: 5,
	ELEMENT: 6
}, Ht = (e) => (...t) => ({
	_$litDirective$: e,
	values: t
}), Ut = class {
	constructor(e) {}
	get _$AU() {
		return this._$AM._$AU;
	}
	_$AT(e, t, n) {
		this._$Ct = e, this._$AM = t, this._$Ci = n;
	}
	_$AS(e, t) {
		return this.update(e, t);
	}
	update(e, t) {
		return this.render(...t);
	}
}, W = (e, t) => {
	let n = e._$AN;
	if (n === void 0) return !1;
	for (let e of n) e._$AO?.(t, !1), W(e, t);
	return !0;
}, Wt = (e) => {
	let t, n;
	do {
		if ((t = e._$AM) === void 0) break;
		n = t._$AN, n.delete(e), e = t;
	} while (n?.size === 0);
}, Gt = (e) => {
	for (let t; t = e._$AM; e = t) {
		let n = t._$AN;
		if (n === void 0) t._$AN = n = /* @__PURE__ */ new Set();
		else if (n.has(e)) break;
		n.add(e), Jt(t);
	}
};
function Kt(e) {
	this._$AN === void 0 ? this._$AM = e : (Wt(this), this._$AM = e, Gt(this));
}
function qt(e, t = !1, n = 0) {
	let r = this._$AH, i = this._$AN;
	if (i !== void 0 && i.size !== 0) {
		if (t) {
			if (Array.isArray(r)) for (let e = n; e < r.length; e++) W(r[e], !1), Wt(r[e]);
			else r != null && (W(r, !1), Wt(r));
		} else W(this, e);
	}
}
var Jt = (e) => {
	e.type == Vt.CHILD && (e._$AP ??= qt, e._$AQ ??= Kt);
}, Yt = class extends Ut {
	constructor() {
		super(...arguments), this._$AN = void 0;
	}
	_$AT(e, t, n) {
		super._$AT(e, t, n), Gt(this), this.isConnected = e._$AU;
	}
	_$AO(e, t = !0) {
		e !== this.isConnected && (this.isConnected = e, e ? this.reconnected?.() : this.disconnected?.()), t && (W(this, e), Wt(this));
	}
	setValue(e) {
		if (Ft(this._$Ct)) this._$Ct._$AI(e, this);
		else {
			let t = [...this._$Ct._$AH];
			t[this._$Ci] = e, this._$Ct._$AI(t, this, 0);
		}
	}
	disconnected() {}
	reconnected() {}
}, Xt = /* @__PURE__ */ new WeakMap(), Zt = Ht(class extends Yt {
	render(e) {
		return N;
	}
	update(e, [t]) {
		let n = t !== this.G;
		return n && this.rt(void 0), (n || this.lt !== this.ct) && (this.G = t, this.ht = e.options?.host, this.rt(this.ct = e.element)), N;
	}
	rt(e) {
		if (this.G !== void 0) {
			if (this.isConnected || (e = void 0), typeof this.G == "function") {
				let t = this.ht ?? globalThis, n = Xt.get(t);
				n === void 0 && (n = /* @__PURE__ */ new WeakMap(), Xt.set(t, n)), n.get(this.G) !== void 0 && this.G.call(this.ht, void 0), n.set(this.G, e), e !== void 0 && this.G.call(this.ht, e);
			} else this.G.value = e;
		}
	}
	get lt() {
		return typeof this.G == "function" ? Xt.get(this.ht ?? globalThis)?.get(this.G) : this.G?.value;
	}
	disconnected() {
		this.lt === this.ct && this.rt(void 0);
	}
	reconnected() {
		this.rt(this.ct);
	}
}), Qt = (e, t, n) => {
	let r = /* @__PURE__ */ new Map();
	for (let i = t; i <= n; i++) r.set(e[i], i);
	return r;
}, $t = Ht(class extends Ut {
	constructor(e) {
		if (super(e), e.type !== Vt.CHILD) throw Error("repeat() can only be used in text expressions");
	}
	dt(e, t, n) {
		let r;
		n === void 0 ? n = t : t !== void 0 && (r = t);
		let i = [], a = [], o = 0;
		for (let t of e) i[o] = r ? r(t, o) : o, a[o] = n(t, o), o++;
		return {
			values: a,
			keys: i
		};
	}
	render(e, t, n) {
		return this.dt(e, t, n).values;
	}
	update(e, [t, n, r]) {
		let i = zt(e), { values: a, keys: o } = this.dt(t, n, r);
		if (!Array.isArray(i)) return this.ut = o, a;
		let s = this.ut ??= [], c = [], l, u, d = 0, f = i.length - 1, p = 0, m = a.length - 1;
		for (; d <= f && p <= m;) if (i[d] === null) d++;
		else if (i[f] === null) f--;
		else if (s[d] === o[p]) c[p] = U(i[d], a[p]), d++, p++;
		else if (s[f] === o[m]) c[m] = U(i[f], a[m]), f--, m--;
		else if (s[d] === o[m]) c[m] = U(i[d], a[m]), H(e, c[m + 1], i[d]), d++, m--;
		else if (s[f] === o[p]) c[p] = U(i[f], a[p]), H(e, i[d], i[f]), f--, p++;
		else if (l === void 0 && (l = Qt(o, p, m), u = Qt(s, d, f)), l.has(s[d])) {
			if (l.has(s[f])) {
				let t = u.get(o[p]), n = t === void 0 ? null : i[t];
				if (n === null) {
					let t = H(e, i[d]);
					U(t, a[p]), c[p] = t;
				} else c[p] = U(n, a[p]), H(e, i[d], n), i[t] = null;
				p++;
			} else Bt(i[f]), f--;
		} else Bt(i[d]), d++;
		for (; p <= m;) {
			let t = H(e, c[m + 1]);
			U(t, a[p]), c[p++] = t;
		}
		for (; d <= f;) {
			let e = i[d++];
			e !== null && Bt(e);
		}
		return this.ut = o, Rt(e, c), M;
	}
}), en = class extends Ut {
	constructor(e) {
		if (super(e), this.it = N, e.type !== Vt.CHILD) throw Error(this.constructor.directiveName + "() can only be used in child bindings");
	}
	render(e) {
		if (e === N || e == null) return this._t = void 0, this.it = e;
		if (e === M) return e;
		if (typeof e != "string") throw Error(this.constructor.directiveName + "() called with a non-string value");
		if (e === this.it) return this._t;
		this.it = e;
		let t = [e];
		return t.raw = t, this._t = {
			_$litType$: this.constructor.resultType,
			strings: t,
			values: []
		};
	}
};
en.directiveName = "unsafeHTML", en.resultType = 1;
var G = Ht(en);
//#endregion
//#region src/field-utils.ts
function tn(e) {
	return !!e && typeof e == "object" && "id" in e && "handle" in e && "type" in e;
}
function K(e) {
	return Ce(e);
}
//#endregion
//#region src/types.ts
var q = "formie-control-value-change";
//#endregion
//#region src/render-view.ts
function nn(e) {
	return V(e), Ct(e);
}
function rn(e, t, n, r, i, a, o, s, c, l, u) {
	if (!e || !(e instanceof HTMLElement)) return;
	let d = t.toLowerCase(), f = e.firstElementChild;
	(!f || f.tagName.toLowerCase() !== d) && (e.replaceChildren(), f = document.createElement(t), f.addEventListener(q, (e) => {
		u(e.detail);
	}), e.append(f)), f.field = n, f.value = r, f.errorKey = i, f.errors = a, f.errorId = o.errors, f.inputId = o.input, f.labelId = o.label, f.instructionsId = o.instructions, f.errorAriaLive = s, f.disabled = c, f.hidden = l;
}
function an(e, t, n, r, i, a, o, s, c, l, u) {
	return j`<div
        class="starter-core-registry-host min-w-0"
        ${Zt((e) => {
		rn(e, t, n, r, i, a, o, s, c, l, u);
	})}
    ></div>`;
}
function on(e, t, n, r, i, a, o = "default") {
	if (o === "compositePart") return ln(t, n, r, i, a);
	let s = e.registry.fieldTag;
	if (!s) return un(t, n, r, i, a);
	let c = nn(s);
	return z`<${c} .field=${t} .errors=${n} .errorId=${r.errors} .inputId=${r.input} .labelId=${r.label} .instructionsId=${r.instructions} .errorAriaLive=${i}>${a}</${c}>`;
}
function sn(e) {
	return ue(e) ? o : N;
}
function cn(e, t) {
	return ie(e, K(e)) === "control" ? t.input : N;
}
function ln(e, t, n, r, i) {
	return j`
        <div class="starter-component-subfield" data-formie-field-type=${e.type} data-formie-field-uid=${e.uid} data-formie-field-handle=${e.handle} data-formie-field-required=${e.required ? "true" : N}>
            ${e.label && !le(e) ? j`<label class="starter-component-subfield-label" style=${sn(e)} id=${n.label} for=${cn(e, n)}>${e.label}</label>` : N}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${i}</div>
            <ul id=${n.errors} data-formie-field-errors aria-live=${r === "off" ? N : r} aria-atomic=${r === "off" ? N : "true"} style=${t.length === 0 ? "position: absolute" : N} class="grid gap-1 text-sm text-red-600">
                ${t.map((e) => j`<li>${e}</li>`)}
            </ul>
        </div>
    `;
}
function un(e, t, n, r, i) {
	return j`
        <div class="starter-component-card" data-formie-field-type=${e.type} data-formie-field-uid=${e.uid} data-formie-field-handle=${e.handle} data-formie-field-required=${e.required ? "true" : N}>
            ${e.label && !le(e) ? j`<label class="starter-component-label" style=${sn(e)} id=${n.label} for=${cn(e, n)}>${e.label}</label>` : N}
            ${e.instructions ? j`<div class="starter-component-help" id=${n.instructions}>${G(e.instructions)}</div>` : N}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${i}</div>
            <ul id=${n.errors} data-formie-field-errors aria-live=${r === "off" ? N : r} aria-atomic=${r === "off" ? N : "true"} style=${t.length === 0 ? "position: absolute" : N} class="grid gap-1 text-sm text-red-600">
                ${t.map((e) => j`<li>${e}</li>`)}
            </ul>
        </div>
    `;
}
function dn(e, t) {
	return t.labelledByWrapper && e.instructions ? t.ids.instructions : null;
}
function fn(e, t) {
	return he(dn(e, t), t.errors.length > 0 && t.ids.errors) ?? N;
}
function J(e, t) {
	return t.labelledByWrapper ? N : e.label || e.handle;
}
function pn(e) {
	return typeof e.input.autocomplete == "string" ? e.input.autocomplete : N;
}
function mn(e, t, n, r, i, a = K(e)) {
	let o = e.input, s = i.errors, c = i.ids.errors;
	if (a === "multi-line-text") return j`
            <textarea
            id=${i.ids.input}
            aria-label=${J(e, i)}
            aria-required=${e.required ? "true" : N}
            aria-invalid=${s.length > 0 ? "true" : N}
            aria-errormessage=${s.length > 0 ? c : N}
            aria-describedby=${fn(e, i)}
            autocomplete=${pn(e)}
                class="starter-component-control"
                .value=${typeof t == "string" ? t : ""}
                ?disabled=${n}
                placeholder=${typeof o.placeholder == "string" ? o.placeholder : ""}
                @input=${(e) => {
		r(e.target.value);
	}}
            ></textarea>
        `;
	if (a === "dropdown") {
		let a = o.multiple === !0, l = oe(e), u = (Array.isArray(t) ? t : t == null ? [] : [t]).map((e) => String(e)), d = a ? _(e) : g(e), f = u[0] ?? "", p = !a && f === "" && !d.some((e) => e.value === "");
		return j`
            <select
            id=${i.ids.input}
            aria-label=${J(e, i)}
            aria-required=${e.required ? "true" : N}
            aria-invalid=${s.length > 0 ? "true" : N}
            aria-errormessage=${s.length > 0 ? c : N}
            aria-describedby=${fn(e, i)}
            autocomplete=${pn(e)}
                class="starter-component-control"
                ?disabled=${n}
                ?multiple=${a}
                @change=${(e) => {
			let t = e.target;
			r(a ? Array.from(t.selectedOptions).map((e) => e.value) : l ? t.value === "" ? [] : [t.value] : t.value);
		}}
            >
                ${p ? j`<option value="" disabled hidden .selected=${!0}></option>` : N}
                ${d.map((e) => j`
                        <option
                            value=${e.value}
                            ?disabled=${e.disabled}
                            .selected=${a ? u.includes(e.value) : f === e.value}
                        >
                            ${e.label}
                        </option>
                    `)}
            </select>
        `;
	}
	let l = typeof o.inputType == "string" ? o.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return j`
        <input
            id=${i.ids.input}
            aria-label=${J(e, i)}
            aria-required=${e.required ? "true" : N}
            aria-invalid=${s.length > 0 ? "true" : N}
            aria-errormessage=${s.length > 0 ? c : N}
            aria-describedby=${fn(e, i)}
            autocomplete=${pn(e)}
            class="starter-component-control"
            type=${l}
            data-formie-phone-input=${pe(e) ? "true" : N}
            .value=${typeof t == "string" || typeof t == "number" ? String(t) : ""}
            ?disabled=${n}
            ?readonly=${se(e)}
            placeholder=${typeof o.placeholder == "string" ? o.placeholder : ""}
            @input=${(e) => {
		r(e.target.value);
	}}
        />
    `;
}
var hn = /* @__PURE__ */ new WeakMap();
function gn(e) {
	return Zt((t) => {
		t && (hn.has(t) || t.addEventListener(a, (e) => hn.get(t)?.(e)), hn.set(t, e));
	});
}
function _n(e, t, n, r, i) {
	let a = be(t), o = () => document.getElementById(i.ids.input)?.closest(".starter-component-phone"), s = mn(e, a.number, n, (e) => {
		r({
			...xe(o(), a),
			number: String(e ?? "")
		});
	}, i, "phone");
	return pe(e) ? j`
        <div class="starter-component-phone" ${gn((e) => {
		r(xe(e.currentTarget, a));
	})}>
            ${s}
            <input type="hidden" data-formie-phone-country-input="true" .value=${a.country ?? ""} />
        </div>
    ` : s;
}
function vn(e, t, n, r, i, a) {
	let o = _(e), s = Te(e), c = s.length > 0, l = c && t && typeof t == "object" ? t : {}, u = i.ids.input, d = Y(e, i), f = i.errors.length > 0;
	return j`
        <div class="starter-component-likert" role="group" aria-labelledby=${d.labelledBy} aria-label=${d.label} aria-describedby=${d.describedBy}>
            <table>
                <thead><tr><td></td>${o.map((e, t) => j`<th id=${`${u}-col-${t}`} scope="col">${e.label}</th>`)}</tr></thead>
                <tbody>
                    ${(c ? s : [{
		label: "",
		value: ""
	}]).map((e, i) => {
		let s = c ? String(l[e.value] ?? "") : t == null ? "" : String(t), d = `${u}-row-${i}`;
		return j`<tr>
                            ${c ? j`<th id=${d} scope="row">${e.label}</th>` : j`<td></td>`}
                            ${o.map((t, o) => j`<td><input
                                type="radio"
                                name=${`${u}-${i}`}
                                value=${t.value}
                                .checked=${s === t.value}
                                ?disabled=${n || t.disabled}
                                aria-labelledby=${c ? `${u}-col-${o} ${d}` : `${u}-col-${o}`}
                                aria-invalid=${f ? "true" : N}
                                aria-errormessage=${f ? a : N}
                                @change=${() => r(c ? {
			...l,
			[e.value]: t.value
		} : t.value)}
                            /></td>`)}
                        </tr>`;
	})}
                </tbody>
            </table>
        </div>
    `;
}
var yn = /* @__PURE__ */ new WeakMap();
function bn(e) {
	return Zt((t) => {
		t && (yn.has(t) || s(t, "li", (e, n) => yn.get(t)?.(e, n)), yn.set(t, e));
	});
}
function xn(e, t, n, r, i, a) {
	let o = e.state.definition, s = Object.fromEntries(_(t).map((e) => [e.value, e.label])), c = Ee(t, n), l = Y(t, a), u = (e, t) => i(ve(c, e, t));
	return j`
        <ol class="starter-component-rank" role="group" aria-labelledby=${l.labelledBy} aria-label=${l.label} aria-describedby=${l.describedBy} ${bn(u)}>
            ${$t(c, (e) => e, (e, t) => j`<li>
                <span>${s[e] ?? e}</span>
                <button type="button" ?disabled=${r || t === 0} aria-label=${`${v(o, "moveUp")}: ${s[e] ?? e}`} @click=${() => u(t, t - 1)}>${v(o, "moveUp")}</button>
                <button type="button" ?disabled=${r || t === c.length - 1} aria-label=${`${v(o, "moveDown")}: ${s[e] ?? e}`} @click=${() => u(t, t + 1)}>${v(o, "moveDown")}</button>
            </li>`)}
        </ol>
    `;
}
function Sn(e, t, n, r, i, a) {
	let s = _(e), c = t == null ? "" : String(t), l = s.findIndex((e) => e.value === c), u = typeof e.input.starColor == "string" && e.input.starColor ? `color: ${e.input.starColor}` : N, d = Y(e, i), f = i.errors.length > 0;
	return j`
        <div class="starter-component-rating" style=${u} role="radiogroup" aria-labelledby=${d.labelledBy} aria-label=${d.label} aria-describedby=${d.describedBy}>
            ${s.map((e, t) => j`<label style="position: relative; display: inline-block" data-formie-rating-filled=${t <= l ? "true" : N}>
                <input
                    type="radio"
                    name=${i.ids.input}
                    value=${e.value}
                    .checked=${c === e.value}
                    ?disabled=${n || e.disabled}
                    style="position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer"
                    aria-invalid=${f ? "true" : N}
                    aria-errormessage=${f ? a : N}
                    @change=${() => r(e.value)}
                    @click=${() => {
		c === e.value && r(null);
	}}
                />
                <span aria-hidden="true">${t <= l ? "★" : "☆"}</span>
                <span style=${o}>${e.label}</span>
            </label>`)}
        </div>
    `;
}
function Cn(e, t, n, r) {
	if (e.type === "heading") return j`<span>${typeof t == "string" ? t : ""}</span>`;
	if (e.type === "checkbox") return j`<input type="checkbox" aria-label=${e.heading} .checked=${t === !0 || t === "1" || t === 1} ?disabled=${n} @change=${(e) => r(e.target.checked)} />`;
	if (e.type === "multiline") return j`<textarea aria-label=${e.heading} .value=${typeof t == "string" ? t : ""} ?disabled=${n} @input=${(e) => r(e.target.value)}></textarea>`;
	if (e.type === "select" || e.type === "dropdown") {
		let i = t == null ? "" : String(t);
		return j`<select aria-label=${e.heading} ?disabled=${n} @change=${(e) => r(e.target.value)}>
            ${e.options.some((e) => e.value === i) ? N : j`<option value=${i} disabled hidden .selected=${!0}></option>`}
            ${e.options.map((e) => j`<option value=${e.value} .selected=${e.value === i}>${e.label}</option>`)}
        </select>`;
	}
	return j`<input type=${{
		number: "number",
		email: "email",
		url: "url",
		date: "date",
		time: "time",
		color: "color"
	}[e.type] ?? "text"} aria-label=${e.heading} .value=${typeof t == "string" || typeof t == "number" ? String(t) : ""} ?disabled=${n} @input=${(e) => r(e.target.value)} />`;
}
function wn(e, t, n, r, i, a) {
	let o = De(t), s = Array.isArray(n) ? n : [], c = t.input, l = c.static === !0 || c.staticRows === !0, u = Number(c.minRows ?? 0) || 0, d = Number(c.maxRows ?? 0) || 0, f = !r && !l && (d <= 0 || s.length < d), p = e.state.definition, h = (e, t, n) => {
		i(s.map((r, i) => i === e ? {
			...r,
			[t]: n
		} : r));
	};
	return j`
        <div class="grid gap-2" role="group" aria-labelledby=${a.labelledByWrapper ? a.ids.label : N} aria-label=${J(t, a)}>
            <table>
                <thead>
                    <tr>
                        ${o.map((e) => j`<th scope="col">${e.heading}</th>`)}
                        ${l ? N : j`<td></td>`}
                    </tr>
                </thead>
                <tbody>
                    ${s.map((e, t) => j`
                        <tr>
                            ${o.map((n) => j`<td>${Cn(n, e[n.handle], r, (e) => h(t, n.handle, e))}</td>`)}
                            ${l ? N : j`<td>
                                <button type="button" aria-label=${`${v(p, "removeRow")} ${t + 1}`} ?disabled=${r || s.length <= u} @click=${() => i(s.filter((e, n) => n !== t))}>
                                    ${v(p, "remove")}
                                </button>
                            </td>`}
                        </tr>
                    `)}
                </tbody>
            </table>
            ${l ? N : j`<button type="button" ?disabled=${!f} @click=${() => i([...s, m(t)])}>
                ${String(c.addRowLabel || v(p, "addRow"))}
            </button>`}
        </div>
    `;
}
function Tn(e, t) {
	let { field: n, value: r, errorKey: i, disabled: a, setValue: o } = t, s = n.input, c = K(n), l = {
		errors: t.errors,
		ids: t.ids,
		labelledByWrapper: t.labelledByWrapper && !!n.label
	}, u = l.labelledByWrapper ? t.ids.label : N, d = he(dn(n, l)) ?? N;
	if (ce(n)) return Dn(e, t, l);
	if (me(n)) return On(e, t, l);
	if (c === "table") return wn(e, n, r, a, o, l);
	if (c === "phone") return _n(n, r, a, o, l);
	if (c === "survey-likert") return vn(n, r, a, o, l, l.ids.errors);
	if (c === "survey-rank") return xn(e, n, r, a, o, l);
	if (c === "survey-rating") return Sn(n, r, a, o, l, l.ids.errors);
	if (c === "section") return j`<hr class="starter-component-section" />`;
	if (de(n)) return En(n, r, a, o, l);
	if (c === "signature") return j`<formie-internal-signature
            aria-invalid=${t.errors.length > 0 ? "true" : N}
            aria-errormessage=${t.errors.length > 0 ? t.errorId : N}
            aria-describedby=${t.errors.length > 0 ? t.errorId : N}
            .clearLabel=${v(e.state.definition, "clear")}
            .clearAccessibleLabel=${v(e.state.definition, "clearSignature")}
            .field=${n}
            .modules=${e.state.definition.modules}
            .value=${typeof r == "string" ? r : ""}
            ?disabled=${a}
            @formie-control-value-change=${(e) => {
		o(e.detail);
	}}
        ></formie-internal-signature>`;
	if (c === "multi-line-text" || c === "dropdown") return mn(n, r, a, o, l, c);
	if (c === "radio") {
		let e = _(n), i = oe(n), s = Array.isArray(r) ? String(r[0] ?? "") : r == null ? "" : String(r);
		return j`
            <div class="flex flex-col gap-2" role="radiogroup" aria-labelledby=${u} aria-label=${J(n, l)} aria-required=${n.required ? "true" : N} aria-describedby=${d}>
                ${e.map((e) => {
			let n = e.value, r = a || e.disabled;
			return j`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                aria-invalid=${t.errors.length > 0 ? "true" : N}
                                aria-errormessage=${t.errors.length > 0 ? t.errorId : N}
                                aria-describedby=${t.errors.length > 0 ? t.errorId : N}
                                name=${t.ids.input}
                                value=${n}
                                .checked=${s === n}
                                ?disabled=${r}
                                @change=${() => {
				o(i ? [n] : n);
			}}
                            />
                            <span>${e.label}</span>
                        </label>
                    `;
		})}
            </div>
        `;
	}
	if (c === "checkboxes") {
		let e = _(n), i = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return j`
            <div class="flex flex-col gap-2" role="group" aria-labelledby=${u} aria-label=${J(n, l)} aria-describedby=${d}>
                ${e.map((e) => {
			let n = e.value, r = i.includes(n), s = a || e.disabled;
			return j`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                aria-invalid=${t.errors.length > 0 ? "true" : N}
                                aria-errormessage=${t.errors.length > 0 ? t.errorId : N}
                                aria-describedby=${t.errors.length > 0 ? t.errorId : N}
                                .checked=${r}
                                ?disabled=${s}
                                @change=${() => {
				let e = r ? i.filter((e) => e !== n) : [...i, n];
				o(e);
			}}
                            />
                            <span>${e.label}</span>
                        </label>
                    `;
		})}
            </div>
        `;
	}
	if (c === "agree") {
		let e = typeof s.descriptionHtml == "string" ? s.descriptionHtml : null;
		return j`
            <div role="group" aria-labelledby=${u} aria-label=${J(n, l)} aria-describedby=${d}>
                <label class="flex items-start gap-2 text-sm text-slate-800">
                    <input
                        type="checkbox"
                        id=${t.ids.input}
                        aria-required=${n.required ? "true" : N}
                        aria-invalid=${t.errors.length > 0 ? "true" : N}
                        aria-errormessage=${t.errors.length > 0 ? t.errorId : N}
                        aria-describedby=${t.errors.length > 0 ? t.errorId : N}
                        .checked=${r === !0}
                        ?disabled=${a}
                        @change=${(e) => {
			o(e.target.checked);
		}}
                    />
                    <span>${e ? G(e) : n.label ?? ""}</span>
                </label>
            </div>
        `;
	}
	let f = h(n);
	if (f) return f.kind === "heading" ? z`<${Ct(f.level)} class="starter-component-heading">${f.text}</${Ct(f.level)}>` : j`<div class="starter-component-content">${G(f.html)}</div>`;
	let p = ye(n);
	return p === null ? fe(c) ? mn(n, r, a, o, l, c) : j`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(n.meta?.fieldType ?? n.type)}
        </div>` : j`<div class="starter-component-payment">${G(p)}</div>`;
}
function En(e, t, n, r, i) {
	let a = i.errors, o = i.ids.errors, s = e.input, c = Array.isArray(t) ? t : [], l = s.multiple === !0, u = c.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return j`
        <div class="grid gap-2">
            <input
                type="file"
                id=${i.ids.input}
                aria-label=${J(e, i)}
                aria-required=${e.required ? "true" : N}
                aria-invalid=${a.length > 0 ? "true" : N}
                aria-errormessage=${a.length > 0 ? o : N}
                aria-describedby=${fn(e, i)}
                class="starter-component-control"
                ?disabled=${n}
                ?multiple=${l}
                @change=${(e) => {
		let t = e.target;
		r(Array.from(t.files || []));
	}}
            />
            ${u.length > 0 ? j`<ul class="starter-component-file-list grid gap-1 text-sm text-slate-600">
                      ${u.map((e) => j`<li>${e}</li>`)}
                  </ul>` : N}
        </div>
    `;
}
function Y(e, t) {
	return {
		labelledBy: t.labelledByWrapper ? t.ids.label : N,
		label: J(e, t),
		describedBy: he(dn(e, t)) ?? N
	};
}
function Dn(e, t, n) {
	let { field: r, value: i, errorKey: a, disabled: o, setValue: s } = t, c = l(r), u = i && typeof i == "object" ? i : {};
	if (c.length === 0) return j`<div class="text-sm text-amber-800">Composite field has no parts.</div>`;
	let d = Y(r, n);
	return j`
        <div class="starter-component-name-grid" role="group" aria-labelledby=${d.labelledBy} aria-label=${d.label} aria-describedby=${d.describedBy}>
            ${c.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let n = `${a}.${t.handle}`;
		return kn(e, {
			field: t,
			value: u[t.handle],
			errors: e.state.errors.fields[n] || [],
			errorKey: n,
			disabled: o || t.meta?.disabled === !0,
			setValue(e) {
				s({
					...u,
					[t.handle]: e
				});
			}
		}, ee(r) === "container-parent" ? "default" : "compositePart");
	})}
        </div>
    `;
}
function On(e, t, n) {
	let { field: r, value: i, errorKey: a, disabled: o, setValue: s } = t, c = Se(r), l = Array.isArray(i) ? i : [], u = r.input, d = Number(u.minRows ?? 0) || 0, p = Number(u.maxRows ?? 0) || 0, m = !o && (p <= 0 || l.length < p);
	if (c.length === 0) return j`<div class="text-sm text-amber-800">Repeater has no row layout.</div>`;
	let h = Y(r, n), g = e.state.definition;
	return j`
        <div class="grid gap-4" data-formie-repeater-container role="group" aria-labelledby=${h.labelledBy} aria-label=${h.label} aria-describedby=${h.describedBy}>
            ${l.map((t, n) => j`
                    <div class="rounded-xl border border-slate-200 p-4" data-formie-repeater-item>
                        ${c.map((r, i) => An(e, r, t, `${a}.${n}`, o, (e, t) => {
		let r = l.map((r, i) => i === n ? {
			...r,
			[e.handle]: t
		} : r);
		s(r);
	}))}
                        <button
                            type="button"
                            class="mt-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700"
                            ?disabled=${o || d > 0 && l.length <= d}
                            aria-label=${`${v(g, "removeRow")} ${n + 1}`}
                            data-formie-repeater-remove
                            @click=${() => {
		s(l.filter((e, t) => t !== n));
	}}
                        >
                            ${v(g, "remove")}
                        </button>
                    </div>
                `)}
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"
                ?disabled=${!m}
                @click=${() => {
		s([...l, f(r)]);
	}}
            >
                ${String(u.addRowLabel || v(g, "addRow"))}
            </button>
        </div>
    `;
}
function kn(e, t, n = "default") {
	let { field: r, value: i, errors: a, errorKey: o, disabled: s, setValue: c } = t, l = e.state.fieldStates[o]?.hidden === !0;
	if (l) return j``;
	let u = K(r), d = re(e.state.session, o), f = d.errors, p = ne(e.state.definition), m = e.registry.fieldControls[r.type] || e.registry.fieldControls[u] || null, h = (t) => {
		c(t), e.host.requestUpdate();
	};
	return on(e, r, a, d, p, m ? an(e, m, r, i, o, a, d, p, s, l, h) : Tn(e, {
		...t,
		errorId: f,
		errorAriaLive: p,
		ids: d,
		labelledByWrapper: n === "compositePart" || !e.registry.fieldTag,
		setValue: h
	}), n);
}
function An(e, t, n, r, i, a) {
	return j`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => {
		let o = `${r}.${t.handle}`;
		return kn(e, {
			field: t,
			value: n[t.handle],
			errors: e.state.errors.fields[o] || [],
			errorKey: o,
			disabled: i === !0 || e.state.fieldStates[o]?.disabled === !0,
			setValue(e) {
				a(t, e);
			}
		});
	})}
        </div>
    `;
}
function jn(e, t) {
	return j`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => kn(e, {
		field: t,
		value: e.state.values[t.id],
		errors: e.state.errors.fields[t.id] || [],
		errorKey: t.id,
		disabled: e.state.fieldStates[t.id]?.disabled === !0,
		setValue(n) {
			e.instance.setValue(t.id, n);
		}
	}))}
        </div>
    `;
}
function Mn(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId);
	if (!t) return j``;
	let n = e.registry.regions.pageActions;
	if (n) {
		let r = nn(n);
		return z`<${r}
            .page=${t}
            .state=${e.state}
            .instance=${e.instance}
        ></${r}>`;
	}
	return j`
        <div class="formie-page-actions">
            ${t.actions.secondary.map((t) => j`
            <button
                type="button"
                @click=${async (n) => {
		let r = n.currentTarget.closest("form"), i = e.state.currentPageId;
		await e.instance.submit(t.type), await e.host.updateComplete, te(r, i);
	}}
            >
                ${t.label}
            </button>
        `)}
            <button type="submit" ?disabled=${!c(e.state)}>${t.actions.primary.label}</button>
        </div>
    `;
}
function Nn(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId && e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages.find((t) => e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages[0];
	if (!t) return j``;
	let n = e.state.errors.form, r = e.state.lastSubmitResult?.messages.error, i = e.state.lastSubmitResult?.messages.notice, a = !!r && !n.includes(r), o = !(e.state.lastSubmitResult?.completion?.behavior === "message" && e.state.lastSubmitResult.completion.hideForm), s = ae(e.state.definition, e.state.currentPageId)?.position, c = j`
        ${n.length > 0 ? j`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" tabindex="-1" data-formie-form-message>
                  <ul class="list-inside list-disc">
                      ${n.map((e) => j`<li>${e}</li>`)}
                  </ul>
              </div>` : N}
        ${i ? j`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" tabindex="-1" data-formie-form-message>
                  ${G(i)}
              </div>` : N}
        ${a ? j`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" tabindex="-1" data-formie-form-message>${r}</div>` : N}
        ${o && s === "start" ? Fn(e) : N}
        ${o ? Pn(e, t) : N}
        ${o && s === "end" ? Fn(e) : N}
    `, l = e.state.session.tokens.render ?? "";
	return j`
        <form
            class=${e.formClass || "starter-component-form starter-core-preview text-slate-900"}
            data-formie-definition=${e.state.definition.handle}
            data-formie-render-id=${l}
            @submit=${async (t) => {
		t.preventDefault();
		let n = t.currentTarget, r = e.state.currentPageId;
		await e.instance.submit(), await e.host.updateComplete, te(n, r);
	}}
        >
            ${c}
        </form>
    `;
}
function Pn(e, t) {
	let n = e.registry.regions.page, r = j`
        <div class="starter-core-fields grid gap-4">
            ${t.rows.map((t) => jn(e, t))}
        </div>
        ${Mn(e)}
    `;
	if (n) {
		let i = nn(n);
		return z`<${i} .page=${t} .state=${e.state}>${r}</${i}>`;
	}
	let i = we(e.state.definition) && !!t.label, a = `formie-${e.state.session.tokens.render || e.state.session.id}-page-${t.id}-title`;
	return j`
        <section data-page-id=${t.id} class="starter-core-page space-y-4" tabindex="-1" aria-labelledby=${i ? a : N} aria-label=${!i && e.state.definition.pages.length > 1 && t.label ? t.label : N}>
            ${i ? j`<h3 id=${a} class="starter-core-page-title">${t.label}</h3>` : N}
            ${r}
        </section>
    `;
}
function Fn(e) {
	let t = ae(e.state.definition, e.state.currentPageId);
	if (!t) return j``;
	let n = j`<span class="starter-core-progress-value text-xs font-semibold" aria-hidden="true">${t.percent}%</span>`, r = ![
		"left",
		"right",
		"hidden"
	].includes(t.valuePosition), i = t.percent <= 0 ? "start" : t.percent >= 100 ? "end" : "middle";
	return j`
        <div class="starter-core-progress mb-4 flex items-center gap-2" data-formie-progress-position=${t.position} data-formie-progress-value-position=${t.valuePosition} data-formie-progress-state=${i}>
            ${t.valuePosition === "left" ? n : N}
            <div class="starter-core-progress-track h-4 flex-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label=${v(e.state.definition, "progress")} aria-valuenow=${t.percent} aria-valuemin="0" aria-valuemax="100">
                <div class="starter-core-progress-bar flex h-full items-center justify-center rounded-full bg-slate-900 text-white" style=${`width: ${t.percent}%`}>${r ? n : N}</div>
            </div>
            ${t.valuePosition === "right" ? n : N}
        </div>
    `;
}
function In(e = "Loading form…") {
	return j`<div class="mt-3 text-sm text-slate-500">${e}</div>`;
}
function Ln(e) {
	return j`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${e}</div>`;
}
//#endregion
//#region \0@oxc-project+runtime@0.154.0/helpers/esm/decorate.js
function X(e, t, n, r) {
	var i = arguments.length, a = i < 3 ? t : r === null ? r = Object.getOwnPropertyDescriptor(t, n) : r, o;
	if (typeof Reflect == "object" && typeof Reflect.decorate == "function") a = Reflect.decorate(e, t, n, r);
	else for (var s = e.length - 1; s >= 0; s--) (o = e[s]) && (a = (i < 3 ? o(a) : i > 3 ? o(t, n, a) : o(t, n)) || a);
	return i > 3 && a && Object.defineProperty(t, n, a), a;
}
//#endregion
//#region src/formie-client-form.ts
function Rn(e) {
	return V(e), Ct(e);
}
var Z = class extends R {
	constructor(...e) {
		super(...e), this.endpoint = "", this.profile = "same-origin-browser", this.formHandle = "", this.transport = "rest", this.fetchCredentials = "same-origin", this.query = {}, this.formClass = "", this.loadingMessage = "Loading form…", this.loadError = null, this.booting = !1, this.snapshot = null, this.instance = null, this.unsubscribers = [], this.loadGeneration = 0;
	}
	createRenderRoot() {
		return this;
	}
	disconnectedCallback() {
		super.disconnectedCallback(), this.teardown();
	}
	connectedCallback() {
		super.connectedCallback(), this.bootstrap(!1);
	}
	willUpdate(e) {
		super.willUpdate(e), this.hasUpdated && [
			"endpoint",
			"formHandle",
			"siteId",
			"transport",
			"profile",
			"fetchCredentials",
			"query"
		].some((t) => e.has(t)) && this.bootstrap(!0);
	}
	getFormieInstance() {
		return this.instance;
	}
	async reload() {
		await this.bootstrap(!0);
	}
	get resolvedRegistry() {
		return this.registry ?? jt();
	}
	teardown() {
		this.moduleHost?.destroy(), this.moduleHost = void 0;
		for (let e of this.unsubscribers) e();
		this.unsubscribers = [], this.instance?.destroy(), this.instance = null, this.snapshot = null;
	}
	async bootstrap(e) {
		let t = ++this.loadGeneration, n = this.formHandle.trim(), a = this.endpoint.trim();
		if (!n) {
			this.teardown(), this.booting = !1, this.loadError = "Set `form-handle` on <formie-client-form>.";
			return;
		}
		if (e) this.teardown();
		else if (this.instance) return;
		this.booting = !0, this.loadError = null;
		let o = this.fetchCredentials, s = this.siteId;
		try {
			let e = {
				endpoint: a,
				profile: this.profile,
				formHandle: n,
				...s === void 0 ? {} : { siteId: s },
				credentials: o,
				query: this.query
			}, c = this.transport === "graphql" ? await _e(e) : await ge(e);
			if (t !== this.loadGeneration) return;
			let l = this.transport === "graphql" ? d(e) : p(e), f = u({
				envelope: c,
				transport: l
			});
			this.instance = f;
			for (let e of i) {
				let t = f.on(e, (t) => {
					this.dispatchEvent(new CustomEvent(e, {
						detail: t,
						bubbles: !0,
						composed: !0
					}));
				});
				this.unsubscribers.push(t);
			}
			this.unsubscribers.push(f.subscribe((e) => {
				this.snapshot = e, this.requestUpdate();
			})), this.booting = !1, f.setBrowserModuleGuard(() => {
				throw Error("Form features are still loading.");
			}), await this.updateComplete;
			let m = await r(this, f);
			t === this.loadGeneration ? this.moduleHost = m : await m.destroy();
		} catch (e) {
			if (t !== this.loadGeneration) return;
			this.teardown(), this.booting = !1, this.loadError = e instanceof Error ? e.message : "Unable to load the form.";
		}
	}
	render() {
		let e = this.resolvedRegistry;
		if (this.loadError) {
			let t = e.regions.errorSummary;
			if (t) {
				let e = Rn(t);
				return z`<${e} .errors=${[this.loadError]} .kind=${"load"}></${e}>`;
			}
			return Ln(this.loadError);
		}
		if (this.booting || !this.snapshot || !this.instance) {
			let t = e.regions.loading;
			if (t) {
				let e = Rn(t);
				return z`<${e} .message=${this.loadingMessage}></${e}>`;
			}
			return In(this.loadingMessage);
		}
		return Nn({
			registry: e,
			state: this.snapshot,
			instance: this.instance,
			host: this,
			formClass: this.formClass || ""
		});
	}
};
X([B({ type: String })], Z.prototype, "endpoint", void 0), X([B({
	type: String,
	attribute: "request-profile"
})], Z.prototype, "profile", void 0), X([B({
	type: String,
	attribute: "form-handle"
})], Z.prototype, "formHandle", void 0), X([B({
	type: Number,
	attribute: "site-id",
	converter: { fromAttribute(e) {
		if (e == null || e === "") return;
		let t = Number(e);
		return Number.isFinite(t) ? t : void 0;
	} }
})], Z.prototype, "siteId", void 0), X([B({
	attribute: "transport",
	converter: { fromAttribute(e) {
		return (e ?? "rest").toLowerCase() === "graphql" ? "graphql" : "rest";
	} }
})], Z.prototype, "transport", void 0), X([B({
	attribute: "fetch-credentials",
	converter: { fromAttribute(e) {
		return e === "omit" || e === "same-origin" || e === "include" ? e : "same-origin";
	} }
})], Z.prototype, "fetchCredentials", void 0), X([B({ attribute: !1 })], Z.prototype, "query", void 0), X([B({
	type: String,
	attribute: "form-class"
})], Z.prototype, "formClass", void 0), X([B({
	type: String,
	attribute: "loading-message"
})], Z.prototype, "loadingMessage", void 0), X([B({ attribute: !1 })], Z.prototype, "registry", void 0), X([Dt()], Z.prototype, "loadError", void 0), X([Dt()], Z.prototype, "booting", void 0), X([Dt()], Z.prototype, "snapshot", void 0);
//#endregion
//#region src/form-element.ts
function zn(e) {
	if (e == null || e === "") return !1;
	let t = e.toLowerCase();
	return t === "true" || t === "1";
}
function Q(e, t, n) {
	typeof n == "string" && n.length > 0 ? e.setAttribute(t, n) : e.removeAttribute(t);
}
function Bn(e, t, n) {
	n === !0 ? e.setAttribute(t, "true") : e.removeAttribute(t);
}
function Vn(e, t) {
	if (t) return t.startsWith("http") ? t : `${e}${t}`;
}
var Hn = class extends HTMLElement {
	static get observedAttributes() {
		return [
			"mode",
			"transport",
			"request-profile",
			"theme",
			"form-handle",
			"endpoint",
			"refresh-tokens",
			"static-cache",
			"locale",
			"site-id",
			"auto-visible",
			"base-url"
		];
	}
	constructor() {
		super(), this.mountedInstance = null, this.optionState = {}, this.mountScheduled = null, this.eventUnsubs = [];
	}
	ensureInitialized() {
		this.mountRoot ||= (this.client = n(), document.createElement("div"));
	}
	connectedCallback() {
		this.ensureInitialized(), this.style.display = "block", this.contains(this.mountRoot) || this.append(this.mountRoot), this.scheduleMount();
	}
	disconnectedCallback() {
		this.unmount();
	}
	attributeChangedCallback(e, t, n) {
		t !== n && this.isConnected && (e !== "refresh-tokens" || this.optionState.refreshTokens === void 0) && (e !== "static-cache" || this.optionState.staticCache === void 0) && this.scheduleMount();
	}
	get baseUrl() {
		let e = this.getAttribute("base-url");
		return this.optionState.baseUrl ?? e ?? void 0;
	}
	set baseUrl(e) {
		this.optionState.baseUrl = e, Q(this, "base-url", e), this.scheduleMount();
	}
	get transport() {
		let e = this.getAttribute("transport");
		return this.optionState.transport ?? e ?? void 0;
	}
	set transport(e) {
		this.optionState.transport = e, Q(this, "transport", e), this.scheduleMount();
	}
	get theme() {
		let e = this.getAttribute("theme");
		return this.optionState.theme ?? e ?? void 0;
	}
	set theme(e) {
		this.optionState.theme = e, Q(this, "theme", e), this.scheduleMount();
	}
	get themeConfig() {
		return this.optionState.themeConfig;
	}
	set themeConfig(e) {
		this.optionState.themeConfig = e, this.scheduleMount();
	}
	get payload() {
		return this.optionState.payload;
	}
	set payload(e) {
		this.optionState.payload = e, this.scheduleMount();
	}
	get formHandle() {
		let e = this.getAttribute("form-handle");
		return this.optionState.formHandle ?? e ?? void 0;
	}
	set formHandle(e) {
		this.optionState.formHandle = e, Q(this, "form-handle", e), this.scheduleMount();
	}
	get endpoint() {
		let e = this.getAttribute("endpoint");
		return this.optionState.endpoint ?? e ?? void 0;
	}
	set endpoint(e) {
		this.optionState.endpoint = e, Q(this, "endpoint", e), this.scheduleMount();
	}
	get staticCache() {
		return this.optionState.staticCache ?? (this.hasAttribute("static-cache") ? zn(this.getAttribute("static-cache")) : void 0);
	}
	set staticCache(e) {
		this.optionState.staticCache = e, Bn(this, "static-cache", e), this.scheduleMount();
	}
	get refreshTokens() {
		return this.optionState.refreshTokens ?? (this.hasAttribute("refresh-tokens") ? zn(this.getAttribute("refresh-tokens")) : void 0);
	}
	set refreshTokens(e) {
		this.optionState.refreshTokens = e, Bn(this, "refresh-tokens", e), this.scheduleMount();
	}
	get locale() {
		let e = this.getAttribute("locale");
		return this.optionState.locale ?? e ?? void 0;
	}
	set locale(e) {
		this.optionState.locale = e, Q(this, "locale", e), this.scheduleMount();
	}
	get siteId() {
		return this.optionState.siteId ?? (this.getAttribute("site-id") ? Number(this.getAttribute("site-id")) : void 0);
	}
	set siteId(e) {
		this.optionState.siteId = e, Q(this, "site-id", typeof e == "number" ? String(e) : void 0), this.scheduleMount();
	}
	get autoVisible() {
		return this.optionState.autoVisible ?? (this.hasAttribute("auto-visible") ? zn(this.getAttribute("auto-visible")) : void 0);
	}
	set autoVisible(e) {
		this.optionState.autoVisible = e, Bn(this, "auto-visible", e), this.scheduleMount();
	}
	get mode() {
		let e = this.getAttribute("mode");
		return this.optionState.mode ?? e ?? "server-rendered";
	}
	set mode(e) {
		this.optionState.mode = e, Q(this, "mode", e), this.scheduleMount();
	}
	getInstance() {
		return this.ensureInitialized(), this.mountedInstance;
	}
	async submit(e = "submit") {
		return this.ensureInitialized(), this.mountedInstance ? this.mountedInstance.submit(e) : null;
	}
	buildOptions() {
		let e = this.baseUrl || "", t = this.transport, n = t === "graphql" ? "/api" : "/actions/formie/server/forms/render", r = Vn(e, this.endpoint || n), i = this.staticCache, a = this.refreshTokens;
		return {
			profile: this.optionState.profile ?? this.getAttribute("request-profile") ?? "same-origin-browser",
			mode: this.mode,
			transport: t,
			theme: this.theme,
			themeConfig: this.themeConfig,
			formHandle: this.formHandle,
			endpoint: r,
			payload: this.payload,
			staticCache: i,
			refreshTokens: a,
			locale: this.locale,
			siteId: this.siteId,
			autoVisible: this.autoVisible ?? !1
		};
	}
	bindInstanceEvents(t) {
		this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = e.map((e) => t.on(e, (t) => {
			this.dispatchEvent(new CustomEvent(e, {
				detail: t,
				bubbles: !0,
				composed: !0
			}));
		}));
	}
	async scheduleMount() {
		return this.ensureInitialized(), this.mountScheduled ||= Promise.resolve().then(async () => {
			this.mountScheduled = null, this.isConnected && await this.mount();
		}), this.mountScheduled;
	}
	async mount() {
		await this.unmount();
		let e = await this.client.mount(this.mountRoot, this.buildOptions());
		this.mountedInstance = e, this.bindInstanceEvents(e), this.dispatchEvent(new CustomEvent("formie-mounted", {
			detail: {
				id: e.id,
				instance: e
			},
			bubbles: !0,
			composed: !0
		}));
	}
	async unmount() {
		if (this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = [], !this.mountRoot || !this.mountedInstance) return;
		let e = this.mountedInstance;
		await this.client.unmount(this.mountRoot), this.mountedInstance = null, this.dispatchEvent(new CustomEvent("formie-unmounted", {
			detail: { id: e.id },
			bubbles: !0,
			composed: !0
		}));
	}
}, $ = class extends R {
	constructor(...e) {
		super(...e), this.modules = {
			contractVersion: 2,
			surface: "client-rendered",
			entries: []
		}, this.value = "", this.disabled = !1, this.clearLabel = "Clear", this.clearAccessibleLabel = "Clear signature", this.loadError = null, this.pad = null, this.strokeListener = () => {
			this.emitValue();
		}, this.onWinResize = () => {
			let e = this.shadowRoot?.querySelector("canvas");
			e instanceof HTMLCanvasElement && this.resizeCanvas(e);
		};
	}
	static {
		this.styles = Ne`
        :host {
            display: block;
        }
        .wrap {
            display: grid;
            gap: 0.5rem;
        }
        canvas {
            width: 100%;
            height: 12rem;
            border-radius: 0.75rem;
            border: 1px dashed #fda4af;
            background: linear-gradient(180deg, #fff1f2 0%, #fff 100%);
        }
        button {
            justify-self: start;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 0.45rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .err {
            font-size: 0.875rem;
            color: #b91c1c;
        }
    `;
	}
	async firstUpdated() {
		let e = this.shadowRoot?.querySelector("canvas");
		if (e && e instanceof HTMLCanvasElement) try {
			let { default: t } = await import("./signature_pad-Cqf-RWiu.js"), n = this.resolveDrawModuleConfig(), r = typeof n?.backgroundColor == "string" ? String(n.backgroundColor) : "#ffffff", i = typeof n?.penColor == "string" ? String(n.penColor) : "#000000", a = Number(n?.penWeight ?? 2) || 2, o = new t(e, {
				backgroundColor: r,
				penColor: i,
				minWidth: a,
				maxWidth: a
			});
			this.pad = o, o.addEventListener?.("endStroke", this.strokeListener), this.resizeCanvas(e), window.addEventListener("resize", this.onWinResize), this.applySerializedValue();
		} catch (e) {
			this.loadError = e instanceof Error ? e.message : "Signature pad failed to load.";
		}
	}
	disconnectedCallback() {
		super.disconnectedCallback(), window.removeEventListener("resize", this.onWinResize), this.pad?.removeEventListener && this.pad.removeEventListener("endStroke", this.strokeListener), this.pad = null;
	}
	resolveDrawModuleConfig() {
		let e = new Set(this.field.moduleRefs || []), t = this.modules.entries.find((t) => e.has(t.key) && t.moduleId === "formie:signature");
		return t && typeof t.config == "object" && t.config ? t.config : null;
	}
	resizeCanvas(e) {
		let t = Math.max(window.devicePixelRatio || 1, 1), n = Math.max(1, Math.floor(e.clientWidth || 480)), r = e.getContext("2d");
		e.width = n * t, e.height = 192 * t, r?.setTransform(1, 0, 0, 1, 0, 0), r?.scale(t, t), e.style.width = `${n}px`, e.style.height = "192px", this.applySerializedValue();
	}
	updated(e) {
		if (e.has("value") && this.pad && this.applySerializedValue(), e.has("disabled") && this.pad) {
			let e = this.shadowRoot?.querySelector("canvas");
			e instanceof HTMLElement && (e.style.pointerEvents = this.disabled ? "none" : "");
		}
	}
	applySerializedValue() {
		if (this.pad) {
			if (!this.value) this.pad.isEmpty() || this.pad.clear();
			else try {
				Promise.resolve(this.pad.fromDataURL(this.value)).catch(() => void 0);
			} catch {}
		}
	}
	emitValue() {
		!this.pad || this.pad.isEmpty() ? this.dispatchEvent(new CustomEvent(q, {
			detail: "",
			bubbles: !0,
			composed: !0
		})) : this.dispatchEvent(new CustomEvent(q, {
			detail: this.pad.toDataURL(),
			bubbles: !0,
			composed: !0
		}));
	}
	render() {
		return this.loadError ? j`<div class="err">${this.loadError}</div>` : j`
            <div class="wrap">
                <canvas role="img" aria-label=${this.field?.label || this.field?.handle || ""}></canvas>
                <button type="button" aria-label=${this.clearAccessibleLabel} ?disabled=${this.disabled} @click=${() => {
			this.pad?.clear(), this.dispatchEvent(new CustomEvent(q, {
				detail: "",
				bubbles: !0,
				composed: !0
			}));
		}}>${this.clearLabel}</button>
            </div>
        `;
	}
};
X([B({ attribute: !1 })], $.prototype, "field", void 0), X([B({ attribute: !1 })], $.prototype, "modules", void 0), X([B({ type: String })], $.prototype, "value", void 0), X([B({ type: Boolean })], $.prototype, "disabled", void 0), X([B({ attribute: !1 })], $.prototype, "clearLabel", void 0), X([B({ attribute: !1 })], $.prototype, "clearAccessibleLabel", void 0), X([Dt()], $.prototype, "loadError", void 0);
//#endregion
//#region src/index.ts
var Un = !1;
function Wn() {
	Un || (Un = !0, customElements.get("formie-form") || customElements.define("formie-form", Hn), customElements.get("formie-internal-signature") || customElements.define("formie-internal-signature", $), customElements.get("formie-client-form") || customElements.define("formie-client-form", Z));
}
//#endregion
export { q as FORMIE_CONTROL_VALUE_EVENT, Z as FormieClientFormElement, Hn as FormieFormElement, $ as FormieInternalSignature, kt as FormieRegistry, V as assertValidCustomElementName, t as createFormieClient, Mt as createFormieRegistry, jt as getFormieRegistry, tn as isFieldDefinition, Wn as registerFormieWebComponents, Ln as renderErrorView, Nn as renderFormView, In as renderLoadingView, K as resolveFieldRendererType };
