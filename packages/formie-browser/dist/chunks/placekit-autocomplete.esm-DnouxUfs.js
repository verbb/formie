//#region ../../node_modules/@placekit/autocomplete-js/dist/placekit-autocomplete.esm.mjs
var e = /* @__PURE__ */ new Map();
function t() {
	let e = [];
	return typeof window < "u" && navigator.userAgent && e.push(navigator.userAgent), e.push("PlaceKit/2.3.0 (Client=JavaScript)"), typeof process < "u" && process.version && e.push(`NodeJS/${process.version}`), e.join(" ");
}
function n(n, r = {}) {
	if (["string", "undefined"].includes(typeof n)) n || console.warn("PlaceKit: missing or empty `apiKey` argument.");
	else throw Error("PlaceKit: `apiKey` argument is invalid, expected a string.");
	let i = 0, a = ["https://api.placekit.co"], o = !1, s = { maxResults: 5 };
	typeof window < "u" && navigator.language && (s.language = navigator.language.slice(0, 2));
	let c = t();
	function l(e = "POST", t = "", r = {}) {
		let { timeout: o, forwardIP: s, ...u } = r, d = new AbortController(), f = o === void 0 ? void 0 : setTimeout(() => d.abort(), o), p = new URL(t.trim().replace(/^\/+/, ""), a[i]);
		["GET", "HEAD"].includes(e) && u !== void 0 && Object.keys(u).forEach((e) => p.searchParams.append(e, u[e]));
		let m = {
			"Content-Type": "application/json; charset=UTF-8",
			"User-Agent": c,
			"x-placekit-api-key": n
		};
		return s && (m["x-forwarded-for"] = s), fetch(p, {
			method: e,
			headers: m,
			signal: d.signal,
			body: ["GET", "HEAD"].includes(e) ? void 0 : JSON.stringify(u)
		}).then(async (e) => {
			clearTimeout(f);
			let t = await e.json();
			if (!e.ok) throw {
				status: e.status,
				statusText: e.statusText,
				...t
			};
			return t;
		}).catch((n) => {
			if ((n.name === "AbortError" || n.status && n.status >= 500) && (i++, i < a.length - 1)) return l(e, t, r);
			throw n;
		});
	}
	let u = {
		get options() {
			return s;
		},
		get hasGeolocation() {
			return o;
		},
		configure(e = {}) {
			if (!["object", "undefined"].includes(typeof e) || Array.isArray(e) || e === null) throw Error("PlaceKit.configure: `opts` argument is invalid, expected an object.");
			Object.assign(s, e);
		},
		requestGeolocation(e = {}) {
			if (!["object", "undefined"].includes(typeof e) || Array.isArray(e) || e === null) throw Error("PlaceKit.requestGeolocation: `opts` argument is invalid, expected an object.");
			return new Promise((t, n) => {
				typeof window > "u" || !navigator.geolocation ? n(Error("PlaceKit.requestGeolocation: geolocation is only available in the browser.")) : navigator.geolocation.getCurrentPosition((e) => {
					o = !0, s.coordinates = `${e.coords.latitude},${e.coords.longitude}`, t(e);
				}, (e) => {
					o = !1, delete s.coordinates, n(Error(`PlaceKit.requestGeolocation: (${e.code}) ${e.message}`));
				}, e);
			});
		},
		clearGeolocation() {
			o = !1, delete s.coordinates;
		}
	};
	for (let [t, n] of e.entries()) {
		if (t in u) throw Error(`PlaceKit extend: \`client.${t}\` already exists.`);
		u[t] = n(l, u);
	}
	return u.configure(r), u;
}
n.extend = function(t, n) {
	if (!n?.call) throw Error("PlaceKit extend: `init` argument is invalid, expected a function.");
	e.set(t, n);
}, n.extend("search", (e, t) => (n, r = {}) => {
	if (!["string", "undefined"].includes(typeof n)) throw Error("PlaceKit `client.search`: `query` argument is invalid, expected a string.");
	if (!["object", "undefined"].includes(typeof r) || Array.isArray(r) || r === null) throw Error("PlaceKit.search: `opts` argument is invalid, expected an object.");
	return e("POST", "search", {
		...t.options,
		...r,
		query: n
	});
}), n.extend("reverse", (e, t) => (n = {}) => {
	if (!["object", "undefined"].includes(typeof n) || Array.isArray(n) || n === null) throw Error("PlaceKit.reverse: `opts` argument is invalid, expected an object.");
	return e("POST", "reverse", {
		...t.options,
		...n
	});
});
var r = "top", i = "bottom", a = "right", o = "left", s = "auto", c = [
	r,
	i,
	a,
	o
], l = "start", u = "end", d = "clippingParents", f = "viewport", p = "popper", m = "reference", h = c.reduce(function(e, t) {
	return e.concat([t + "-" + l, t + "-" + u]);
}, []), g = [].concat(c, [s]).reduce(function(e, t) {
	return e.concat([
		t,
		t + "-" + l,
		t + "-" + u
	]);
}, []), _ = [
	"beforeRead",
	"read",
	"afterRead",
	"beforeMain",
	"main",
	"afterMain",
	"beforeWrite",
	"write",
	"afterWrite"
];
function v(e) {
	return e ? (e.nodeName || "").toLowerCase() : null;
}
function y(e) {
	if (e == null) return window;
	if (e.toString() !== "[object Window]") {
		var t = e.ownerDocument;
		return t && t.defaultView || window;
	}
	return e;
}
function b(e) {
	return e instanceof y(e).Element || e instanceof Element;
}
function x(e) {
	return e instanceof y(e).HTMLElement || e instanceof HTMLElement;
}
function S(e) {
	return typeof ShadowRoot > "u" ? !1 : e instanceof y(e).ShadowRoot || e instanceof ShadowRoot;
}
function C(e) {
	var t = e.state;
	Object.keys(t.elements).forEach(function(e) {
		var n = t.styles[e] || {}, r = t.attributes[e] || {}, i = t.elements[e];
		!x(i) || !v(i) || (Object.assign(i.style, n), Object.keys(r).forEach(function(e) {
			var t = r[e];
			t === !1 ? i.removeAttribute(e) : i.setAttribute(e, t === !0 ? "" : t);
		}));
	});
}
function w(e) {
	var t = e.state, n = {
		popper: {
			position: t.options.strategy,
			left: "0",
			top: "0",
			margin: "0"
		},
		arrow: { position: "absolute" },
		reference: {}
	};
	return Object.assign(t.elements.popper.style, n.popper), t.styles = n, t.elements.arrow && Object.assign(t.elements.arrow.style, n.arrow), function() {
		Object.keys(t.elements).forEach(function(e) {
			var r = t.elements[e], i = t.attributes[e] || {}, a = Object.keys(t.styles.hasOwnProperty(e) ? t.styles[e] : n[e]).reduce(function(e, t) {
				return e[t] = "", e;
			}, {});
			!x(r) || !v(r) || (Object.assign(r.style, a), Object.keys(i).forEach(function(e) {
				r.removeAttribute(e);
			}));
		});
	};
}
var T = {
	name: "applyStyles",
	enabled: !0,
	phase: "write",
	fn: C,
	effect: w,
	requires: ["computeStyles"]
};
function E(e) {
	return e.split("-")[0];
}
var D = Math.max, O = Math.min, k = Math.round;
function A() {
	var e = navigator.userAgentData;
	return e != null && e.brands && Array.isArray(e.brands) ? e.brands.map(function(e) {
		return e.brand + "/" + e.version;
	}).join(" ") : navigator.userAgent;
}
function j() {
	return !/^((?!chrome|android).)*safari/i.test(A());
}
function M(e, t, n) {
	t === void 0 && (t = !1), n === void 0 && (n = !1);
	var r = e.getBoundingClientRect(), i = 1, a = 1;
	t && x(e) && (i = e.offsetWidth > 0 && k(r.width) / e.offsetWidth || 1, a = e.offsetHeight > 0 && k(r.height) / e.offsetHeight || 1);
	var o = (b(e) ? y(e) : window).visualViewport, s = !j() && n, c = (r.left + (s && o ? o.offsetLeft : 0)) / i, l = (r.top + (s && o ? o.offsetTop : 0)) / a, u = r.width / i, d = r.height / a;
	return {
		width: u,
		height: d,
		top: l,
		right: c + u,
		bottom: l + d,
		left: c,
		x: c,
		y: l
	};
}
function N(e) {
	var t = M(e), n = e.offsetWidth, r = e.offsetHeight;
	return Math.abs(t.width - n) <= 1 && (n = t.width), Math.abs(t.height - r) <= 1 && (r = t.height), {
		x: e.offsetLeft,
		y: e.offsetTop,
		width: n,
		height: r
	};
}
function P(e, t) {
	var n = t.getRootNode && t.getRootNode();
	if (e.contains(t)) return !0;
	if (n && S(n)) {
		var r = t;
		do {
			if (r && e.isSameNode(r)) return !0;
			r = r.parentNode || r.host;
		} while (r);
	}
	return !1;
}
function F(e) {
	return y(e).getComputedStyle(e);
}
function I(e) {
	return [
		"table",
		"td",
		"th"
	].indexOf(v(e)) >= 0;
}
function L(e) {
	return ((b(e) ? e.ownerDocument : e.document) || window.document).documentElement;
}
function R(e) {
	return v(e) === "html" ? e : e.assignedSlot || e.parentNode || (S(e) ? e.host : null) || L(e);
}
function z(e) {
	return !x(e) || F(e).position === "fixed" ? null : e.offsetParent;
}
function B(e) {
	var t = /firefox/i.test(A());
	if (/Trident/i.test(A()) && x(e) && F(e).position === "fixed") return null;
	var n = R(e);
	for (S(n) && (n = n.host); x(n) && ["html", "body"].indexOf(v(n)) < 0;) {
		var r = F(n);
		if (r.transform !== "none" || r.perspective !== "none" || r.contain === "paint" || ["transform", "perspective"].indexOf(r.willChange) !== -1 || t && r.willChange === "filter" || t && r.filter && r.filter !== "none") return n;
		n = n.parentNode;
	}
	return null;
}
function V(e) {
	for (var t = y(e), n = z(e); n && I(n) && F(n).position === "static";) n = z(n);
	return n && (v(n) === "html" || v(n) === "body" && F(n).position === "static") ? t : n || B(e) || t;
}
function ee(e) {
	return ["top", "bottom"].indexOf(e) >= 0 ? "x" : "y";
}
function H(e, t, n) {
	return D(e, O(t, n));
}
function te(e, t, n) {
	var r = H(e, t, n);
	return r > n ? n : r;
}
function ne() {
	return {
		top: 0,
		right: 0,
		bottom: 0,
		left: 0
	};
}
function U(e) {
	return Object.assign({}, ne(), e);
}
function re(e, t) {
	return t.reduce(function(t, n) {
		return t[n] = e, t;
	}, {});
}
var ie = function(e, t) {
	return e = typeof e == "function" ? e(Object.assign({}, t.rects, { placement: t.placement })) : e, U(typeof e == "number" ? re(e, c) : e);
};
function ae(e) {
	var t, n = e.state, s = e.name, c = e.options, l = n.elements.arrow, u = n.modifiersData.popperOffsets, d = E(n.placement), f = ee(d), p = [o, a].indexOf(d) >= 0 ? "height" : "width";
	if (!(!l || !u)) {
		var m = ie(c.padding, n), h = N(l), g = f === "y" ? r : o, _ = f === "y" ? i : a, v = n.rects.reference[p] + n.rects.reference[f] - u[f] - n.rects.popper[p], y = u[f] - n.rects.reference[f], b = V(l), x = b ? f === "y" ? b.clientHeight || 0 : b.clientWidth || 0 : 0, S = v / 2 - y / 2, C = m[g], w = x - h[p] - m[_], T = x / 2 - h[p] / 2 + S, D = H(C, T, w), O = f;
		n.modifiersData[s] = (t = {}, t[O] = D, t.centerOffset = D - T, t);
	}
}
function oe(e) {
	var t = e.state, n = e.options.element, r = n === void 0 ? "[data-popper-arrow]" : n;
	r != null && (typeof r == "string" && (r = t.elements.popper.querySelector(r), !r) || P(t.elements.popper, r) && (t.elements.arrow = r));
}
var se = {
	name: "arrow",
	enabled: !0,
	phase: "main",
	fn: ae,
	effect: oe,
	requires: ["popperOffsets"],
	requiresIfExists: ["preventOverflow"]
};
function W(e) {
	return e.split("-")[1];
}
var ce = {
	top: "auto",
	right: "auto",
	bottom: "auto",
	left: "auto"
};
function le(e, t) {
	var n = e.x, r = e.y, i = t.devicePixelRatio || 1;
	return {
		x: k(n * i) / i || 0,
		y: k(r * i) / i || 0
	};
}
function ue(e) {
	var t, n = e.popper, s = e.popperRect, c = e.placement, l = e.variation, d = e.offsets, f = e.position, p = e.gpuAcceleration, m = e.adaptive, h = e.roundOffsets, g = e.isFixed, _ = d.x, v = _ === void 0 ? 0 : _, b = d.y, x = b === void 0 ? 0 : b, S = typeof h == "function" ? h({
		x: v,
		y: x
	}) : {
		x: v,
		y: x
	};
	v = S.x, x = S.y;
	var C = d.hasOwnProperty("x"), w = d.hasOwnProperty("y"), T = o, E = r, D = window;
	if (m) {
		var O = V(n), k = "clientHeight", A = "clientWidth";
		if (O === y(n) && (O = L(n), F(O).position !== "static" && f === "absolute" && (k = "scrollHeight", A = "scrollWidth")), O = O, c === r || (c === o || c === a) && l === u) {
			E = i;
			var j = g && O === D && D.visualViewport ? D.visualViewport.height : O[k];
			x -= j - s.height, x *= p ? 1 : -1;
		}
		if (c === o || (c === r || c === i) && l === u) {
			T = a;
			var M = g && O === D && D.visualViewport ? D.visualViewport.width : O[A];
			v -= M - s.width, v *= p ? 1 : -1;
		}
	}
	var N = Object.assign({ position: f }, m && ce), P = h === !0 ? le({
		x: v,
		y: x
	}, y(n)) : {
		x: v,
		y: x
	};
	if (v = P.x, x = P.y, p) {
		var I;
		return Object.assign({}, N, (I = {}, I[E] = w ? "0" : "", I[T] = C ? "0" : "", I.transform = (D.devicePixelRatio || 1) <= 1 ? "translate(" + v + "px, " + x + "px)" : "translate3d(" + v + "px, " + x + "px, 0)", I));
	}
	return Object.assign({}, N, (t = {}, t[E] = w ? x + "px" : "", t[T] = C ? v + "px" : "", t.transform = "", t));
}
function de(e) {
	var t = e.state, n = e.options, r = n.gpuAcceleration, i = r === void 0 || r, a = n.adaptive, o = a === void 0 || a, s = n.roundOffsets, c = s === void 0 || s, l = {
		placement: E(t.placement),
		variation: W(t.placement),
		popper: t.elements.popper,
		popperRect: t.rects.popper,
		gpuAcceleration: i,
		isFixed: t.options.strategy === "fixed"
	};
	t.modifiersData.popperOffsets != null && (t.styles.popper = Object.assign({}, t.styles.popper, ue(Object.assign({}, l, {
		offsets: t.modifiersData.popperOffsets,
		position: t.options.strategy,
		adaptive: o,
		roundOffsets: c
	})))), t.modifiersData.arrow != null && (t.styles.arrow = Object.assign({}, t.styles.arrow, ue(Object.assign({}, l, {
		offsets: t.modifiersData.arrow,
		position: "absolute",
		adaptive: !1,
		roundOffsets: c
	})))), t.attributes.popper = Object.assign({}, t.attributes.popper, { "data-popper-placement": t.placement });
}
var fe = {
	name: "computeStyles",
	enabled: !0,
	phase: "beforeWrite",
	fn: de,
	data: {}
}, G = { passive: !0 };
function pe(e) {
	var t = e.state, n = e.instance, r = e.options, i = r.scroll, a = i === void 0 || i, o = r.resize, s = o === void 0 || o, c = y(t.elements.popper), l = [].concat(t.scrollParents.reference, t.scrollParents.popper);
	return a && l.forEach(function(e) {
		e.addEventListener("scroll", n.update, G);
	}), s && c.addEventListener("resize", n.update, G), function() {
		a && l.forEach(function(e) {
			e.removeEventListener("scroll", n.update, G);
		}), s && c.removeEventListener("resize", n.update, G);
	};
}
var me = {
	name: "eventListeners",
	enabled: !0,
	phase: "write",
	fn: function() {},
	effect: pe,
	data: {}
}, K = {
	left: "right",
	right: "left",
	bottom: "top",
	top: "bottom"
};
function q(e) {
	return e.replace(/left|right|bottom|top/g, function(e) {
		return K[e];
	});
}
var he = {
	start: "end",
	end: "start"
};
function ge(e) {
	return e.replace(/start|end/g, function(e) {
		return he[e];
	});
}
function _e(e) {
	var t = y(e);
	return {
		scrollLeft: t.pageXOffset,
		scrollTop: t.pageYOffset
	};
}
function J(e) {
	return M(L(e)).left + _e(e).scrollLeft;
}
function ve(e, t) {
	var n = y(e), r = L(e), i = n.visualViewport, a = r.clientWidth, o = r.clientHeight, s = 0, c = 0;
	if (i) {
		a = i.width, o = i.height;
		var l = j();
		(l || !l && t === "fixed") && (s = i.offsetLeft, c = i.offsetTop);
	}
	return {
		width: a,
		height: o,
		x: s + J(e),
		y: c
	};
}
function ye(e) {
	var t = L(e), n = _e(e), r = e.ownerDocument?.body, i = D(t.scrollWidth, t.clientWidth, r ? r.scrollWidth : 0, r ? r.clientWidth : 0), a = D(t.scrollHeight, t.clientHeight, r ? r.scrollHeight : 0, r ? r.clientHeight : 0), o = -n.scrollLeft + J(e), s = -n.scrollTop;
	return F(r || t).direction === "rtl" && (o += D(t.clientWidth, r ? r.clientWidth : 0) - i), {
		width: i,
		height: a,
		x: o,
		y: s
	};
}
function Y(e) {
	var t = F(e), n = t.overflow, r = t.overflowX, i = t.overflowY;
	return /auto|scroll|overlay|hidden/.test(n + i + r);
}
function X(e) {
	return [
		"html",
		"body",
		"#document"
	].indexOf(v(e)) >= 0 ? e.ownerDocument.body : x(e) && Y(e) ? e : X(R(e));
}
function Z(e, t) {
	t === void 0 && (t = []);
	var n = X(e), r = n === e.ownerDocument?.body, i = y(n), a = r ? [i].concat(i.visualViewport || [], Y(n) ? n : []) : n, o = t.concat(a);
	return r ? o : o.concat(Z(R(a)));
}
function Q(e) {
	return Object.assign({}, e, {
		left: e.x,
		top: e.y,
		right: e.x + e.width,
		bottom: e.y + e.height
	});
}
function be(e, t) {
	var n = M(e, !1, t === "fixed");
	return n.top += e.clientTop, n.left += e.clientLeft, n.bottom = n.top + e.clientHeight, n.right = n.left + e.clientWidth, n.width = e.clientWidth, n.height = e.clientHeight, n.x = n.left, n.y = n.top, n;
}
function xe(e, t, n) {
	return t === f ? Q(ve(e, n)) : b(t) ? be(t, n) : Q(ye(L(e)));
}
function Se(e) {
	var t = Z(R(e)), n = ["absolute", "fixed"].indexOf(F(e).position) >= 0 && x(e) ? V(e) : e;
	return b(n) ? t.filter(function(e) {
		return b(e) && P(e, n) && v(e) !== "body";
	}) : [];
}
function Ce(e, t, n, r) {
	var i = t === "clippingParents" ? Se(e) : [].concat(t), a = [].concat(i, [n]), o = a[0], s = a.reduce(function(t, n) {
		var i = xe(e, n, r);
		return t.top = D(i.top, t.top), t.right = O(i.right, t.right), t.bottom = O(i.bottom, t.bottom), t.left = D(i.left, t.left), t;
	}, xe(e, o, r));
	return s.width = s.right - s.left, s.height = s.bottom - s.top, s.x = s.left, s.y = s.top, s;
}
function we(e) {
	var t = e.reference, n = e.element, s = e.placement, c = s ? E(s) : null, d = s ? W(s) : null, f = t.x + t.width / 2 - n.width / 2, p = t.y + t.height / 2 - n.height / 2, m;
	switch (c) {
		case r:
			m = {
				x: f,
				y: t.y - n.height
			};
			break;
		case i:
			m = {
				x: f,
				y: t.y + t.height
			};
			break;
		case a:
			m = {
				x: t.x + t.width,
				y: p
			};
			break;
		case o:
			m = {
				x: t.x - n.width,
				y: p
			};
			break;
		default: m = {
			x: t.x,
			y: t.y
		};
	}
	var h = c ? ee(c) : null;
	if (h != null) {
		var g = h === "y" ? "height" : "width";
		switch (d) {
			case l:
				m[h] = m[h] - (t[g] / 2 - n[g] / 2);
				break;
			case u:
				m[h] = m[h] + (t[g] / 2 - n[g] / 2);
				break;
		}
	}
	return m;
}
function $(e, t) {
	t === void 0 && (t = {});
	var n = t, o = n.placement, s = o === void 0 ? e.placement : o, l = n.strategy, u = l === void 0 ? e.strategy : l, h = n.boundary, g = h === void 0 ? d : h, _ = n.rootBoundary, v = _ === void 0 ? f : _, y = n.elementContext, x = y === void 0 ? p : y, S = n.altBoundary, C = S !== void 0 && S, w = n.padding, T = w === void 0 ? 0 : w, E = U(typeof T == "number" ? re(T, c) : T), D = x === p ? m : p, O = e.rects.popper, k = e.elements[C ? D : x], A = Ce(b(k) ? k : k.contextElement || L(e.elements.popper), g, v, u), j = M(e.elements.reference), N = we({
		reference: j,
		element: O,
		strategy: "absolute",
		placement: s
	}), P = Q(Object.assign({}, O, N)), F = x === p ? P : j, I = {
		top: A.top - F.top + E.top,
		bottom: F.bottom - A.bottom + E.bottom,
		left: A.left - F.left + E.left,
		right: F.right - A.right + E.right
	}, R = e.modifiersData.offset;
	if (x === p && R) {
		var z = R[s];
		Object.keys(I).forEach(function(e) {
			var t = [a, i].indexOf(e) >= 0 ? 1 : -1, n = [r, i].indexOf(e) >= 0 ? "y" : "x";
			I[e] += z[n] * t;
		});
	}
	return I;
}
function Te(e, t) {
	t === void 0 && (t = {});
	var n = t, r = n.placement, i = n.boundary, a = n.rootBoundary, o = n.padding, s = n.flipVariations, l = n.allowedAutoPlacements, u = l === void 0 ? g : l, d = W(r), f = d ? s ? h : h.filter(function(e) {
		return W(e) === d;
	}) : c, p = f.filter(function(e) {
		return u.indexOf(e) >= 0;
	});
	p.length === 0 && (p = f);
	var m = p.reduce(function(t, n) {
		return t[n] = $(e, {
			placement: n,
			boundary: i,
			rootBoundary: a,
			padding: o
		})[E(n)], t;
	}, {});
	return Object.keys(m).sort(function(e, t) {
		return m[e] - m[t];
	});
}
function Ee(e) {
	if (E(e) === s) return [];
	var t = q(e);
	return [
		ge(e),
		t,
		ge(t)
	];
}
function De(e) {
	var t = e.state, n = e.options, c = e.name;
	if (!t.modifiersData[c]._skip) {
		for (var u = n.mainAxis, d = u === void 0 || u, f = n.altAxis, p = f === void 0 || f, m = n.fallbackPlacements, h = n.padding, g = n.boundary, _ = n.rootBoundary, v = n.altBoundary, y = n.flipVariations, b = y === void 0 || y, x = n.allowedAutoPlacements, S = t.options.placement, C = E(S) === S, w = m || (C || !b ? [q(S)] : Ee(S)), T = [S].concat(w).reduce(function(e, n) {
			return e.concat(E(n) === s ? Te(t, {
				placement: n,
				boundary: g,
				rootBoundary: _,
				padding: h,
				flipVariations: b,
				allowedAutoPlacements: x
			}) : n);
		}, []), D = t.rects.reference, O = t.rects.popper, k = /* @__PURE__ */ new Map(), A = !0, j = T[0], M = 0; M < T.length; M++) {
			var N = T[M], P = E(N), F = W(N) === l, I = [r, i].indexOf(P) >= 0, L = I ? "width" : "height", R = $(t, {
				placement: N,
				boundary: g,
				rootBoundary: _,
				altBoundary: v,
				padding: h
			}), z = I ? F ? a : o : F ? i : r;
			D[L] > O[L] && (z = q(z));
			var B = q(z), V = [];
			if (d && V.push(R[P] <= 0), p && V.push(R[z] <= 0, R[B] <= 0), V.every(function(e) {
				return e;
			})) {
				j = N, A = !1;
				break;
			}
			k.set(N, V);
		}
		if (A) for (var ee = b ? 3 : 1, H = function(e) {
			var t = T.find(function(t) {
				var n = k.get(t);
				if (n) return n.slice(0, e).every(function(e) {
					return e;
				});
			});
			if (t) return j = t, "break";
		}, te = ee; te > 0 && H(te) !== "break"; te--);
		t.placement !== j && (t.modifiersData[c]._skip = !0, t.placement = j, t.reset = !0);
	}
}
var Oe = {
	name: "flip",
	enabled: !0,
	phase: "main",
	fn: De,
	requiresIfExists: ["offset"],
	data: { _skip: !1 }
};
function ke(e, t, n) {
	return n === void 0 && (n = {
		x: 0,
		y: 0
	}), {
		top: e.top - t.height - n.y,
		right: e.right - t.width + n.x,
		bottom: e.bottom - t.height + n.y,
		left: e.left - t.width - n.x
	};
}
function Ae(e) {
	return [
		r,
		a,
		i,
		o
	].some(function(t) {
		return e[t] >= 0;
	});
}
function je(e) {
	var t = e.state, n = e.name, r = t.rects.reference, i = t.rects.popper, a = t.modifiersData.preventOverflow, o = $(t, { elementContext: "reference" }), s = $(t, { altBoundary: !0 }), c = ke(o, r), l = ke(s, i, a), u = Ae(c), d = Ae(l);
	t.modifiersData[n] = {
		referenceClippingOffsets: c,
		popperEscapeOffsets: l,
		isReferenceHidden: u,
		hasPopperEscaped: d
	}, t.attributes.popper = Object.assign({}, t.attributes.popper, {
		"data-popper-reference-hidden": u,
		"data-popper-escaped": d
	});
}
var Me = {
	name: "hide",
	enabled: !0,
	phase: "main",
	requiresIfExists: ["preventOverflow"],
	fn: je
};
function Ne(e, t, n) {
	var i = E(e), s = [o, r].indexOf(i) >= 0 ? -1 : 1, c = typeof n == "function" ? n(Object.assign({}, t, { placement: e })) : n, l = c[0], u = c[1];
	return l ||= 0, u = (u || 0) * s, [o, a].indexOf(i) >= 0 ? {
		x: u,
		y: l
	} : {
		x: l,
		y: u
	};
}
function Pe(e) {
	var t = e.state, n = e.options, r = e.name, i = n.offset, a = i === void 0 ? [0, 0] : i, o = g.reduce(function(e, n) {
		return e[n] = Ne(n, t.rects, a), e;
	}, {}), s = o[t.placement], c = s.x, l = s.y;
	t.modifiersData.popperOffsets != null && (t.modifiersData.popperOffsets.x += c, t.modifiersData.popperOffsets.y += l), t.modifiersData[r] = o;
}
var Fe = {
	name: "offset",
	enabled: !0,
	phase: "main",
	requires: ["popperOffsets"],
	fn: Pe
};
function Ie(e) {
	var t = e.state, n = e.name;
	t.modifiersData[n] = we({
		reference: t.rects.reference,
		element: t.rects.popper,
		strategy: "absolute",
		placement: t.placement
	});
}
var Le = {
	name: "popperOffsets",
	enabled: !0,
	phase: "read",
	fn: Ie,
	data: {}
};
function Re(e) {
	return e === "x" ? "y" : "x";
}
function ze(e) {
	var t = e.state, n = e.options, s = e.name, c = n.mainAxis, u = c === void 0 || c, d = n.altAxis, f = d !== void 0 && d, p = n.boundary, m = n.rootBoundary, h = n.altBoundary, g = n.padding, _ = n.tether, v = _ === void 0 || _, y = n.tetherOffset, b = y === void 0 ? 0 : y, x = $(t, {
		boundary: p,
		rootBoundary: m,
		padding: g,
		altBoundary: h
	}), S = E(t.placement), C = W(t.placement), w = !C, T = ee(S), k = Re(T), A = t.modifiersData.popperOffsets, j = t.rects.reference, M = t.rects.popper, P = typeof b == "function" ? b(Object.assign({}, t.rects, { placement: t.placement })) : b, F = typeof P == "number" ? {
		mainAxis: P,
		altAxis: P
	} : Object.assign({
		mainAxis: 0,
		altAxis: 0
	}, P), I = t.modifiersData.offset ? t.modifiersData.offset[t.placement] : null, L = {
		x: 0,
		y: 0
	};
	if (A) {
		if (u) {
			var R = T === "y" ? r : o, z = T === "y" ? i : a, B = T === "y" ? "height" : "width", U = A[T], re = U + x[R], ie = U - x[z], ae = v ? -M[B] / 2 : 0, oe = C === l ? j[B] : M[B], se = C === l ? -M[B] : -j[B], ce = t.elements.arrow, le = v && ce ? N(ce) : {
				width: 0,
				height: 0
			}, ue = t.modifiersData["arrow#persistent"] ? t.modifiersData["arrow#persistent"].padding : ne(), de = ue[R], fe = ue[z], G = H(0, j[B], le[B]), pe = w ? j[B] / 2 - ae - G - de - F.mainAxis : oe - G - de - F.mainAxis, me = w ? -j[B] / 2 + ae + G + fe + F.mainAxis : se + G + fe + F.mainAxis, K = t.elements.arrow && V(t.elements.arrow), q = K ? T === "y" ? K.clientTop || 0 : K.clientLeft || 0 : 0, he = I?.[T] ?? 0, ge = U + pe - he - q, _e = U + me - he, J = H(v ? O(re, ge) : re, U, v ? D(ie, _e) : ie);
			A[T] = J, L[T] = J - U;
		}
		if (f) {
			var ve = T === "x" ? r : o, ye = T === "x" ? i : a, Y = A[k], X = k === "y" ? "height" : "width", Z = Y + x[ve], Q = Y - x[ye], be = [r, o].indexOf(S) !== -1, xe = I?.[k] ?? 0, Se = be ? Z : Y - j[X] - M[X] - xe + F.altAxis, Ce = be ? Y + j[X] + M[X] - xe - F.altAxis : Q, we = v && be ? te(Se, Y, Ce) : H(v ? Se : Z, Y, v ? Ce : Q);
			A[k] = we, L[k] = we - Y;
		}
		t.modifiersData[s] = L;
	}
}
var Be = {
	name: "preventOverflow",
	enabled: !0,
	phase: "main",
	fn: ze,
	requiresIfExists: ["offset"]
};
function Ve(e) {
	return {
		scrollLeft: e.scrollLeft,
		scrollTop: e.scrollTop
	};
}
function He(e) {
	return e === y(e) || !x(e) ? _e(e) : Ve(e);
}
function Ue(e) {
	var t = e.getBoundingClientRect(), n = k(t.width) / e.offsetWidth || 1, r = k(t.height) / e.offsetHeight || 1;
	return n !== 1 || r !== 1;
}
function We(e, t, n) {
	n === void 0 && (n = !1);
	var r = x(t), i = x(t) && Ue(t), a = L(t), o = M(e, i, n), s = {
		scrollLeft: 0,
		scrollTop: 0
	}, c = {
		x: 0,
		y: 0
	};
	return (r || !r && !n) && ((v(t) !== "body" || Y(a)) && (s = He(t)), x(t) ? (c = M(t, !0), c.x += t.clientLeft, c.y += t.clientTop) : a && (c.x = J(a))), {
		x: o.left + s.scrollLeft - c.x,
		y: o.top + s.scrollTop - c.y,
		width: o.width,
		height: o.height
	};
}
function Ge(e) {
	var t = /* @__PURE__ */ new Map(), n = /* @__PURE__ */ new Set(), r = [];
	e.forEach(function(e) {
		t.set(e.name, e);
	});
	function i(e) {
		n.add(e.name), [].concat(e.requires || [], e.requiresIfExists || []).forEach(function(e) {
			if (!n.has(e)) {
				var r = t.get(e);
				r && i(r);
			}
		}), r.push(e);
	}
	return e.forEach(function(e) {
		n.has(e.name) || i(e);
	}), r;
}
function Ke(e) {
	var t = Ge(e);
	return _.reduce(function(e, n) {
		return e.concat(t.filter(function(e) {
			return e.phase === n;
		}));
	}, []);
}
function qe(e) {
	var t;
	return function() {
		return t ||= new Promise(function(n) {
			Promise.resolve().then(function() {
				t = void 0, n(e());
			});
		}), t;
	};
}
function Je(e) {
	var t = e.reduce(function(e, t) {
		var n = e[t.name];
		return e[t.name] = n ? Object.assign({}, n, t, {
			options: Object.assign({}, n.options, t.options),
			data: Object.assign({}, n.data, t.data)
		}) : t, e;
	}, {});
	return Object.keys(t).map(function(e) {
		return t[e];
	});
}
var Ye = {
	placement: "bottom",
	modifiers: [],
	strategy: "absolute"
};
function Xe() {
	return ![...arguments].some(function(e) {
		return !(e && typeof e.getBoundingClientRect == "function");
	});
}
function Ze(e) {
	e === void 0 && (e = {});
	var t = e, n = t.defaultModifiers, r = n === void 0 ? [] : n, i = t.defaultOptions, a = i === void 0 ? Ye : i;
	return function(e, t, n) {
		n === void 0 && (n = a);
		var i = {
			placement: "bottom",
			orderedModifiers: [],
			options: Object.assign({}, Ye, a),
			modifiersData: {},
			elements: {
				reference: e,
				popper: t
			},
			attributes: {},
			styles: {}
		}, o = [], s = !1, c = {
			state: i,
			setOptions: function(n) {
				var o = typeof n == "function" ? n(i.options) : n;
				u(), i.options = Object.assign({}, a, i.options, o), i.scrollParents = {
					reference: b(e) ? Z(e) : e.contextElement ? Z(e.contextElement) : [],
					popper: Z(t)
				};
				var s = Ke(Je([].concat(r, i.options.modifiers)));
				return i.orderedModifiers = s.filter(function(e) {
					return e.enabled;
				}), l(), c.update();
			},
			forceUpdate: function() {
				if (!s) {
					var e = i.elements, t = e.reference, n = e.popper;
					if (Xe(t, n)) {
						i.rects = {
							reference: We(t, V(n), i.options.strategy === "fixed"),
							popper: N(n)
						}, i.reset = !1, i.placement = i.options.placement, i.orderedModifiers.forEach(function(e) {
							return i.modifiersData[e.name] = Object.assign({}, e.data);
						});
						for (var r = 0; r < i.orderedModifiers.length; r++) {
							if (i.reset === !0) {
								i.reset = !1, r = -1;
								continue;
							}
							var a = i.orderedModifiers[r], o = a.fn, l = a.options, u = l === void 0 ? {} : l, d = a.name;
							typeof o == "function" && (i = o({
								state: i,
								options: u,
								name: d,
								instance: c
							}) || i);
						}
					}
				}
			},
			update: qe(function() {
				return new Promise(function(e) {
					c.forceUpdate(), e(i);
				});
			}),
			destroy: function() {
				u(), s = !0;
			}
		};
		if (!Xe(e, t)) return c;
		c.setOptions(n).then(function(e) {
			!s && n.onFirstUpdate && n.onFirstUpdate(e);
		});
		function l() {
			i.orderedModifiers.forEach(function(e) {
				var t = e.name, n = e.options, r = n === void 0 ? {} : n, a = e.effect;
				if (typeof a == "function") {
					var s = a({
						state: i,
						name: t,
						instance: c,
						options: r
					});
					o.push(s || function() {});
				}
			});
		}
		function u() {
			o.forEach(function(e) {
				return e();
			}), o = [];
		}
		return c;
	};
}
Ze();
var Qe = Ze({ defaultModifiers: [
	me,
	Le,
	fe,
	T,
	Fe,
	Oe,
	Be,
	se,
	Me
] }), $e = (e) => Object.prototype.toString.call(e) === "[object String]", et = (e) => typeof e == "object" && !Array.isArray(e) && e !== null, tt = (e, t) => et(e) ? (Object.keys(t).forEach((n) => e[n] = et(t[n]) ? tt(e[n] || {}, t[n]) : t[n]), e) : t;
function nt(e, { target: t = "#placekit", ...r } = {}) {
	let i = $e(t) ? document.querySelector(t) : t;
	if (!i) throw "Error: target not found.";
	if (i.tagName !== "INPUT" || !["text", "search"].includes(i.getAttribute("type"))) throw "Error: target must be an HTML input of type \"text\" or \"search\".";
	let a = n(e), o = /* @__PURE__ */ new Map(), s = [], c = null, l = null, u = null, d = !1, f = {}, p = {
		empty: !i.value,
		dirty: !1,
		freeForm: !0,
		geolocation: !1,
		countryMode: !1
	}, m = {
		panel: {
			className: "",
			offset: 4,
			strategy: "absolute",
			flip: !1
		},
		format: {
			flag: (e) => `<img class="pka-flag" src="https://flagcdn.com/64x48/${e?.toLowerCase()}.png" alt="${e}" loading="lazy" />`,
			icon: (e, t) => `<span class="pka-icon pka-icon-${e}" role="img" aria-label="${t || "icon"}"></span>`,
			sub: (e) => {
				switch (e.type) {
					case "administrative": return [e.country].filter((e) => e).join(" ");
					case "city": return [e.zipcode.sort()[0], e.country].filter((e) => e).join(" ");
					case "country": return "";
					case "county": return [e.administrative, e.country].filter((e) => e).join(" ");
					default: return [e.city, e.county].filter((e) => e).join(", ");
				}
			},
			noResults: (e) => `No results for ${e}`,
			value: (e) => e.name,
			applySuggestion: "Apply suggestion",
			suggestions: "Address suggestions",
			changeCountry: "Change country",
			cancel: "Cancel"
		},
		countryAutoFill: !0,
		countrySelect: !0
	};
	i.setAttribute("autocomplete", "off"), i.setAttribute("aria-autocomplete", "list"), i.setAttribute("aria-expanded", !1), i.setAttribute("role", "combobox");
	let h = document.createElement("div");
	h.classList.add("pka-panel"), h.innerHTML = `
    <div class="pka-panel-loading" role="progressbar" aria-hidden="true">${m.format.icon("loading")}</div>
    <div class="pka-panel-suggestions" role="listbox" aria-label="${m.format.suggestions}"></div>
    <div class="pka-panel-footer">
      <div class="pka-panel-country">
        <input type="checkbox" id="pka-panel-country-mode" class="pka-sr-only" />
        <label for="pka-panel-country-mode" class="pka-panel-country-open" aria-label="${m.format.changeCountry}">
        </label>
        <label for="pka-panel-country-mode" class="pka-panel-country-close">
          ${m.format.icon("cancel")}
          <span class="pka-panel-country-label">${m.format.cancel}</span>
        </label>
      </div>
      <div class="pka-panel-credits">
        <span class="pka-panel-credits-text">Powered by</span>
        <a href="https://placekit.io/?utm_source=${encodeURI(window.location.hostname)}" target="_blank" class="pka-panel-credits-link" aria-label="PlaceKit.io">
          <svg viewBox="0 0 98 24" fill-rule="evenodd" fill="currentColor" height="14">
            <path d="M10.618 20.336a.506.506 0 0 1 .558-.414 8.009 8.009 0 0 0 1.867 0 .506.506 0 0 1 .557.414l.177 1a.504.504 0 0 1-.435.59 10.227 10.227 0 0 1-2.465 0 .51.51 0 0 1-.434-.59l.175-1Zm-6.577-5.521a.506.506 0 0 1 .639.28 8.12 8.12 0 0 0 2.971 3.542.504.504 0 0 1 .164.675c-.152.268-.35.612-.507.884a.508.508 0 0 1-.71.174 10.181 10.181 0 0 1-3.807-4.539.502.502 0 0 1 .293-.665c.294-.109.667-.245.957-.351Zm15.5.281a.504.504 0 0 1 .637-.278c.29.103.664.239.958.346a.503.503 0 0 1 .295.668 10.19 10.19 0 0 1-3.811 4.536.5.5 0 0 1-.707-.174c-.158-.27-.357-.614-.511-.88a.508.508 0 0 1 .165-.679 8.107 8.107 0 0 0 2.974-3.539Zm-11.003-.391-.008-.007a5.064 5.064 0 0 1 0-7.158 5.064 5.064 0 0 1 7.158 0 5.064 5.064 0 0 1 .001 7.157l.006.007-2.863 2.864a1.013 1.013 0 0 1-1.432 0l-2.862-2.863Zm3.575-5.601a2.015 2.015 0 0 1 0 4.028 2.015 2.015 0 0 1 0-4.028Zm9.023-.511a.507.507 0 0 1 .656.324c.236.775.382 1.591.426 2.433a.504.504 0 0 1-.505.527c-.312.002-.709.002-1.016.002a.507.507 0 0 1-.505-.481 7.986 7.986 0 0 0-.321-1.833.505.505 0 0 1 .31-.623l.955-.349Zm-18.707.324a.505.505 0 0 1 .654-.323c.295.106.667.242.956.347.253.092.39.367.311.624a7.988 7.988 0 0 0-.323 1.833.505.505 0 0 1-.504.479c-.308.002-.704.002-1.017.002a.508.508 0 0 1-.505-.529c.044-.842.191-1.658.428-2.433Zm11.349-6.499a.504.504 0 0 1 .607-.406 10.143 10.143 0 0 1 5.128 2.967.507.507 0 0 1-.049.726c-.238.203-.542.458-.778.656a.506.506 0 0 1-.697-.045 8.071 8.071 0 0 0-4.001-2.315.504.504 0 0 1-.385-.579c.051-.304.12-.695.175-1.004Zm-3.942-.404a.502.502 0 0 1 .604.405c.056.308.125.699.179 1.003a.506.506 0 0 1-.387.581 8.07 8.07 0 0 0-4.003 2.312.504.504 0 0 1-.694.044c-.237-.196-.541-.451-.781-.653a.506.506 0 0 1-.049-.728 10.136 10.136 0 0 1 5.131-2.964Z" />
            <path d="M30.315 20.5v-5.028c.597.868 1.483 1.321 2.55 1.321 2.496 0 4.087-1.845 4.087-5.137 0-3.165-1.555-5.1-4.087-5.1-1.067 0-1.935.489-2.55 1.375V6.828H28V20.5h2.315Zm61.146-6.999c0 2.17.94 3.292 2.911 3.292.633 0 1.158-.109 1.411-.236v-1.7h-.434c-1.284 0-1.573-.542-1.573-1.555V8.618h2.043v-1.79h-2.043V4.603h-2.315v2.225h-1.248v1.79h1.248v4.883Zm-28.412-3.237c-.326-2.478-2.062-3.708-4.268-3.708-2.785 0-4.576 1.845-4.576 5.137 0 3.146 1.736 5.1 4.594 5.1 2.188 0 3.888-1.14 4.322-3.563h-2.333c-.235 1.103-.94 1.646-1.935 1.646-1.465 0-2.26-1.14-2.26-3.183 0-2.08.759-3.22 2.188-3.22.976 0 1.736.525 1.953 1.791h2.315Zm-17.018-.253c.145-.923.741-1.538 1.845-1.538 1.284 0 1.88.67 1.88 1.917v.236c-1.627.018-2.875.126-4.159.56-1.483.488-2.225 1.483-2.225 2.803 0 1.863 1.357 2.804 3.256 2.804 1.157 0 2.351-.471 3.146-1.592.019.525.073.995.199 1.32h2.406c-.181-.506-.29-1.175-.29-2.369V10.39c0-2.242-1.374-3.834-4.069-3.834-2.441 0-4.015 1.303-4.322 3.455h2.333Zm27.905 3.345h-2.441c-.308 1.031-1.049 1.538-2.134 1.538-1.411 0-2.225-.977-2.333-2.749h6.836v-.543c0-3.129-1.754-5.046-4.594-5.046-2.821 0-4.63 1.845-4.63 5.137 0 3.164 1.791 5.1 4.721 5.1 2.315 0 4.051-1.194 4.575-3.437Zm12.425 3.165h2.315V6.828h-2.315v9.693Zm-47.365 0h2.315V3.5h-2.315v13.021Zm36.966 0h2.315v-4.684l3.653 4.684h2.839l-4.178-5.389 3.907-4.304h-2.713l-3.508 4.051V3.5h-2.315v13.021Zm-30.257-2.658c0-.977.76-1.754 4.051-1.809v.416c0 1.447-1.103 2.604-2.532 2.604-.94 0-1.519-.47-1.519-1.211Zm-13.256-5.39c1.356 0 2.116 1.14 2.116 3.183 0 2.08-.742 3.22-2.116 3.22-1.356 0-2.134-1.158-2.134-3.22 0-2.061.741-3.183 2.134-3.183Zm36.821-.018c1.212 0 1.953.796 2.17 2.243h-4.358c.217-1.465.958-2.243 2.188-2.243Zm17.091-2.712h2.315V3.5h-2.315v2.243Z" />
          </svg>
        </a>
      </div>
    </div>
  `, document.body.append(h);
	let g = h.querySelector(".pka-panel-loading"), _ = h.querySelector(".pka-panel-suggestions"), v = h.querySelector("#pka-panel-country-mode"), y = Qe(i, h);
	function b(e, ...t) {
		o.has(e) && o.get(e).apply(f, t);
	}
	function x(e, t = !1) {
		if (!et(e)) throw "TypeError: setState first argument must be a key/value object.";
		let n = !1;
		for (let r in p) r in e && e[r] !== p[r] && (p[r] = e[r], n = !0, t || b(r, p[r]));
		n && b("state", p);
	}
	function S(e, { notify: t = !1, focus: n = !0 } = {}) {
		$e(e) && (Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value").set.call(i, e), x({ empty: !i.value }), t && (c = null, i.dispatchEvent(new Event("input", { bubbles: !0 })), i.dispatchEvent(new Event("change", { bubbles: !0 }))), n && i.focus());
	}
	function C() {
		S("", {
			notify: !0,
			focus: !0
		}), T(!1), p.geolocation ? j() : s = [];
	}
	function w(e = !1) {
		c !== null && (S(c, { focus: !1 }), e && (c = null));
	}
	function T(e) {
		let t = h.classList.contains("pka-open"), n = e === void 0 ? !t : e;
		h.classList.toggle("pka-open", n), i.setAttribute("aria-expanded", n), t !== n && (n || E(), b(n ? "open" : "close"));
	}
	function E() {
		h.querySelectorAll("[role=\"option\"]").forEach((e) => e.classList.remove("pka-active"));
	}
	function D(e) {
		c === null && (c = i.value);
		let t = Array.from(_.children), n = t.findIndex((e) => e.classList.contains("pka-active"));
		E();
		let r = t.length + 1, a = (n + 1 + e + r) % r;
		if (a === 0) w();
		else {
			let e = t[a - 1];
			e.classList.add("pka-active"), _.scrollTo({ top: e.offsetTop }), S(m.format.value(s[a - 1]));
		}
	}
	function O(e) {
		let t = Array.from(_.children);
		if (e === void 0 && (e = t.findIndex((e) => e.classList.contains("pka-active"))), !t[e]) return;
		let n = s[e];
		p.countryMode ? (M(n), N(!1)) : (t.forEach((t, n) => {
			t.classList.toggle("pka-selected", n === e), t.setAttribute("aria-selected", n === e);
		}), S(m.format.value(n), { notify: !0 }), x({
			dirty: !0,
			freeForm: !1
		}), T(!1), b("pick", i.value, n, e));
	}
	let k;
	function A(e) {
		clearTimeout(k), g.setAttribute("aria-hidden", !0), e && (k = setTimeout(() => {
			g.setAttribute("aria-hidden", !1);
		}, 300));
	}
	async function j() {
		c = null;
		let e = i.value;
		x({
			empty: !e,
			dirty: !0,
			freeForm: !0
		}), A(!0), v.disabled || await P();
		let t = d ? ["city", "country"] : ["country"];
		a.search(e, {
			countries: u ? [u.countrycode] : m.countries,
			types: p.countryMode ? ["country"] : m.types,
			maxResults: p.countryMode ? 20 : m.maxResults
		}).then(({ results: n }) => {
			A(!1), i.value === e && (s = n, _.innerHTML = n.length > 0 ? n.map((e) => `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false">
          ${t.includes(e.type) ? m.format.flag(e.countrycode) : m.format.icon(e.type || "pin", e.type)}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">${e.highlight}</span>
            <span class="pka-panel-suggestion-label-sub">${m.format.sub(e)}</span>
          </span>
          <button type="button" class="pka-panel-suggestion-action" aria-label="${m.format.applySuggestion}" />
        </div>
      `).join("") : `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false" aria-disabled="true">
          ${m.format.icon("noresults")}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">
              ${m.format.noResults?.call ? m.format.noResults(e) : m.format.noResults}
            </span>
          </span>
        </div>
      `, y.update(), b("results", e, n));
		}).catch((e) => b("error", e));
	}
	function M(e) {
		h.querySelector(".pka-panel-country-open").innerHTML = e === null ? "" : `
      ${m.format.flag(e.countrycode)}
      <span class="pka-panel-country-label">${e.name}</span>
      ${m.format.icon("switch")}
    `, e?.countrycode !== u?.countrycode && (u = e, b("countryChange", u));
	}
	function N(e) {
		v.checked = !v.disabled && (e === void 0 ? !v.checked : e), x({ countryMode: v.checked }), p.countryMode ? (l = i.value, S(u.name), i.select(), j()) : l !== null && (S(l), l = null, j());
	}
	function P() {
		return u ? Promise.resolve(u) : a.reverse({
			maxResults: 1,
			types: ["country"]
		}).then(({ results: e }) => e.length ? (M(e[0]), e[0]) : null).catch((e) => b("error", e));
	}
	h.addEventListener("mousemove", (e) => {
		!e.movementX && !e.movementY || (E(), e.target.closest("[role=\"option\"]")?.classList.add("pka-active"));
	}), h.addEventListener("click", (e) => {
		let t = e.target.closest("[role=\"option\"]");
		if (!t) return;
		e.stopPropagation();
		let n = Array.from(_.children).indexOf(t);
		if (e.target.closest(".pka-panel-suggestion-action")) {
			let e = s[n];
			e && (S(`${m.format.value(e)} `, { notify: !0 }), x({
				dirty: !0,
				freeForm: !1
			}));
		} else O(n);
	}), v.addEventListener("change", (e) => {
		N(e.target.checked);
	});
	function F(e) {
		e instanceof InputEvent && (T(!!i.value.trim() || p.countryMode), j());
	}
	i.addEventListener("input", F);
	function I() {
		!p.dirty && i.value ? (T(!0), j()) : T(!!i.value.trim() || p.geolocation || p.countryMode);
	}
	i.addEventListener("click", I), i.addEventListener("focus", I);
	function L(e) {
		![i, h].includes(e.target) && !h.contains(e.target) && (T(!1), w(!0));
	}
	window.addEventListener("click", L);
	function R(e) {
		if (i === document.activeElement) {
			let t = h.classList.contains("pka-open");
			switch (e.key) {
				case "Up":
				case "ArrowUp":
					t && (e.preventDefault(), e.altKey ? T(!1) : D(-1));
					break;
				case "Down":
				case "ArrowDown":
					s.length > 0 && (t ? e.altKey || (e.preventDefault(), D(1)) : (e.preventDefault(), T(!0)));
					break;
				case "Enter":
					t && (e.preventDefault(), O());
					break;
				case "Esc":
				case "Escape":
					e.preventDefault(), t ? p.countryMode ? N(!1) : (T(!1), w(!0)) : C();
					break;
				case "Tab":
					T(!1);
					break;
			}
		}
	}
	window.addEventListener("keydown", R);
	function z() {
		window.requestAnimationFrame(() => {
			h.style.width = `${i.offsetWidth}px`, y.update();
		});
	}
	z();
	let B = new ResizeObserver(z);
	B.observe(i);
	function V(e = {}) {
		delete e.target;
		let { panel: t, format: n, countryAutoFill: r, countrySelect: o, ...s } = tt(m, e);
		a.configure(s), h.setAttribute("class", `pka-panel ${m.panel.className}`.trim()), y.setOptions({
			placement: "bottom-start",
			strategy: m.panel.strategy,
			modifiers: [{
				name: "flip",
				enabled: m.panel.flip
			}, {
				name: "offset",
				options: { offset: [0, m.panel.offset] }
			}]
		});
		let c = m.types?.join(",").toLowerCase() ?? "";
		v.disabled = m.countries || !m.countrySelect || c === "country", h.querySelector(".pka-panel-country").setAttribute("aria-hidden", v.disabled), d = !m.countries && !m.countrySelect && [
			"city",
			"city,country",
			"country,city",
			"country"
		].includes(c), m.countryAutoFill && c === "country" && !p.dirty && !i.value.trim() && P().then((e) => {
			S(e.name, {
				notify: !0,
				focus: !1
			}), x({ freeForm: !1 }), b("pick", e.name, e, 0);
		});
	}
	return V(r), Object.defineProperty(f, "input", { get: () => i }), Object.defineProperty(f, "options", { get: () => ({
		target: t,
		...m,
		...a.options
	}) }), Object.defineProperty(f, "handlers", { get: () => Object.fromEntries(o) }), Object.defineProperty(f, "state", { get: () => p }), f.setValue = (e, t = !1) => (S(e, {
		notify: t,
		focus: !1
	}), f), f.clear = C, f.on = (e, t) => {
		if (!$e(e)) throw "Error: first argument 'event' must be a string.";
		if (t !== void 0 && !t.call) throw "Error: second argument 'handler' must be a function if defined.";
		return t ? o.set(e, t) : o.has(e) && o.delete(e), f;
	}, f.open = () => (T(!0), f), f.close = () => (T(!1), f), f.configure = (e = {}) => (V(e), f), f.requestGeolocation = (e = {}) => a.requestGeolocation(e).then((e) => (x({ geolocation: !0 }, !0), b("geolocation", !0, e), M(null), i.focus(), j(), e)).catch((e) => {
		x({ geolocation: !1 }, !0), b("geolocation", !1, void 0, e.message);
	}), f.clearGeolocation = () => (a.clearGeolocation(), x({ geolocation: !1 }), f), f.destroy = () => {
		i.removeEventListener("input", F), i.removeEventListener("click", I), i.removeEventListener("focus", I), window.removeEventListener("keydown", R), window.removeEventListener("click", L), B.unobserve(i), h.remove();
	}, f;
}
//#endregion
export { nt as default };
