const tt = /* @__PURE__ */ new Map();
function mt() {
  const e = [];
  return typeof window < "u" && navigator.userAgent && e.push(navigator.userAgent), e.push("PlaceKit/2.3.0 (Client=JavaScript)"), typeof process < "u" && process.version && e.push(`NodeJS/${process.version}`), e.join(" ");
}
function ke(e, t = {}) {
  if (["string", "undefined"].includes(typeof e))
    e || console.warn("PlaceKit: missing or empty `apiKey` argument.");
  else throw Error("PlaceKit: `apiKey` argument is invalid, expected a string.");
  let r = 0;
  const n = ["https://api.placekit.co"];
  let a = !1;
  const i = {
    maxResults: 5
  };
  typeof window < "u" && navigator.language && (i.language = navigator.language.slice(0, 2));
  const p = mt();
  function c(u = "POST", l = "", d = {}) {
    const { timeout: g, forwardIP: f, ...h } = d, x = new AbortController(), b = typeof g < "u" ? setTimeout(() => x.abort(), g) : void 0, O = new URL(l.trim().replace(/^\/+/, ""), n[r]);
    ["GET", "HEAD"].includes(u) && typeof h < "u" && Object.keys(h).forEach((y) => O.searchParams.append(y, h[y]));
    const j = {
      "Content-Type": "application/json; charset=UTF-8",
      "User-Agent": p,
      "x-placekit-api-key": e
    };
    return f && (j["x-forwarded-for"] = f), fetch(O, {
      method: u,
      headers: j,
      signal: x.signal,
      body: ["GET", "HEAD"].includes(u) ? void 0 : JSON.stringify(h)
    }).then(async (y) => {
      clearTimeout(b);
      const m = await y.json();
      if (!y.ok)
        throw {
          status: y.status,
          statusText: y.statusText,
          ...m
        };
      return m;
    }).catch((y) => {
      if ((y.name === "AbortError" || y.status && y.status >= 500) && (r++, r < n.length - 1))
        return c(u, l, d);
      throw y;
    });
  }
  const s = {
    get options() {
      return i;
    },
    get hasGeolocation() {
      return a;
    },
    configure(u = {}) {
      if (!["object", "undefined"].includes(typeof u) || Array.isArray(u) || u === null)
        throw Error("PlaceKit.configure: `opts` argument is invalid, expected an object.");
      Object.assign(i, u);
    },
    requestGeolocation(u = {}) {
      if (!["object", "undefined"].includes(typeof u) || Array.isArray(u) || u === null)
        throw Error("PlaceKit.requestGeolocation: `opts` argument is invalid, expected an object.");
      return new Promise((l, d) => {
        typeof window > "u" || !navigator.geolocation ? d(
          Error("PlaceKit.requestGeolocation: geolocation is only available in the browser.")
        ) : navigator.geolocation.getCurrentPosition(
          (g) => {
            a = !0, i.coordinates = `${g.coords.latitude},${g.coords.longitude}`, l(g);
          },
          (g) => {
            a = !1, delete i.coordinates, d(Error(`PlaceKit.requestGeolocation: (${g.code}) ${g.message}`));
          },
          u
        );
      });
    },
    clearGeolocation() {
      a = !1, delete i.coordinates;
    }
  };
  for (const [u, l] of tt.entries()) {
    if (u in s)
      throw Error(`PlaceKit extend: \`client.${u}\` already exists.`);
    s[u] = l(c, s);
  }
  return s.configure(t), s;
}
ke.extend = function(e, t) {
  if (!t?.call)
    throw Error("PlaceKit extend: `init` argument is invalid, expected a function.");
  tt.set(e, t);
};
ke.extend("search", (e, t) => (r, n = {}) => {
  if (!["string", "undefined"].includes(typeof r))
    throw Error("PlaceKit `client.search`: `query` argument is invalid, expected a string.");
  if (!["object", "undefined"].includes(typeof n) || Array.isArray(n) || n === null)
    throw Error("PlaceKit.search: `opts` argument is invalid, expected an object.");
  return e("POST", "search", {
    ...t.options,
    ...n,
    query: r
  });
});
ke.extend("reverse", (e, t) => (r = {}) => {
  if (!["object", "undefined"].includes(typeof r) || Array.isArray(r) || r === null)
    throw Error("PlaceKit.reverse: `opts` argument is invalid, expected an object.");
  return e("POST", "reverse", {
    ...t.options,
    ...r
  });
});
var V = "top", Z = "bottom", I = "right", H = "left", Se = "auto", me = [V, Z, I, H], se = "start", ge = "end", yt = "clippingParents", rt = "viewport", pe = "popper", bt = "reference", Ue = me.reduce(function(e, t) {
  return e.concat([t + "-" + se, t + "-" + ge]);
}, []), nt = [].concat(me, [Se]).reduce(function(e, t) {
  return e.concat([t, t + "-" + se, t + "-" + ge]);
}, []), wt = "beforeRead", xt = "read", Ot = "afterRead", kt = "beforeMain", Et = "main", At = "afterMain", Pt = "beforeWrite", jt = "write", $t = "afterWrite", Lt = [wt, xt, Ot, kt, Et, At, Pt, jt, $t];
function z(e) {
  return e ? (e.nodeName || "").toLowerCase() : null;
}
function W(e) {
  if (e == null)
    return window;
  if (e.toString() !== "[object Window]") {
    var t = e.ownerDocument;
    return t && t.defaultView || window;
  }
  return e;
}
function oe(e) {
  var t = W(e).Element;
  return e instanceof t || e instanceof Element;
}
function N(e) {
  var t = W(e).HTMLElement;
  return e instanceof t || e instanceof HTMLElement;
}
function Te(e) {
  if (typeof ShadowRoot > "u")
    return !1;
  var t = W(e).ShadowRoot;
  return e instanceof t || e instanceof ShadowRoot;
}
function St(e) {
  var t = e.state;
  Object.keys(t.elements).forEach(function(r) {
    var n = t.styles[r] || {}, a = t.attributes[r] || {}, i = t.elements[r];
    !N(i) || !z(i) || (Object.assign(i.style, n), Object.keys(a).forEach(function(p) {
      var c = a[p];
      c === !1 ? i.removeAttribute(p) : i.setAttribute(p, c === !0 ? "" : c);
    }));
  });
}
function Tt(e) {
  var t = e.state, r = {
    popper: {
      position: t.options.strategy,
      left: "0",
      top: "0",
      margin: "0"
    },
    arrow: {
      position: "absolute"
    },
    reference: {}
  };
  return Object.assign(t.elements.popper.style, r.popper), t.styles = r, t.elements.arrow && Object.assign(t.elements.arrow.style, r.arrow), function() {
    Object.keys(t.elements).forEach(function(n) {
      var a = t.elements[n], i = t.attributes[n] || {}, p = Object.keys(t.styles.hasOwnProperty(n) ? t.styles[n] : r[n]), c = p.reduce(function(s, u) {
        return s[u] = "", s;
      }, {});
      !N(a) || !z(a) || (Object.assign(a.style, c), Object.keys(i).forEach(function(s) {
        a.removeAttribute(s);
      }));
    });
  };
}
var Dt = {
  name: "applyStyles",
  enabled: !0,
  phase: "write",
  fn: St,
  effect: Tt,
  requires: ["computeStyles"]
};
function G(e) {
  return e.split("-")[0];
}
var ae = Math.max, Oe = Math.min, ce = Math.round;
function je() {
  var e = navigator.userAgentData;
  return e != null && e.brands && Array.isArray(e.brands) ? e.brands.map(function(t) {
    return t.brand + "/" + t.version;
  }).join(" ") : navigator.userAgent;
}
function at() {
  return !/^((?!chrome|android).)*safari/i.test(je());
}
function le(e, t, r) {
  t === void 0 && (t = !1), r === void 0 && (r = !1);
  var n = e.getBoundingClientRect(), a = 1, i = 1;
  t && N(e) && (a = e.offsetWidth > 0 && ce(n.width) / e.offsetWidth || 1, i = e.offsetHeight > 0 && ce(n.height) / e.offsetHeight || 1);
  var p = oe(e) ? W(e) : window, c = p.visualViewport, s = !at() && r, u = (n.left + (s && c ? c.offsetLeft : 0)) / a, l = (n.top + (s && c ? c.offsetTop : 0)) / i, d = n.width / a, g = n.height / i;
  return {
    width: d,
    height: g,
    top: l,
    right: u + d,
    bottom: l + g,
    left: u,
    x: u,
    y: l
  };
}
function De(e) {
  var t = le(e), r = e.offsetWidth, n = e.offsetHeight;
  return Math.abs(t.width - r) <= 1 && (r = t.width), Math.abs(t.height - n) <= 1 && (n = t.height), {
    x: e.offsetLeft,
    y: e.offsetTop,
    width: r,
    height: n
  };
}
function ot(e, t) {
  var r = t.getRootNode && t.getRootNode();
  if (e.contains(t))
    return !0;
  if (r && Te(r)) {
    var n = t;
    do {
      if (n && e.isSameNode(n))
        return !0;
      n = n.parentNode || n.host;
    } while (n);
  }
  return !1;
}
function Y(e) {
  return W(e).getComputedStyle(e);
}
function Ct(e) {
  return ["table", "td", "th"].indexOf(z(e)) >= 0;
}
function Q(e) {
  return ((oe(e) ? e.ownerDocument : e.document) || window.document).documentElement;
}
function Ee(e) {
  return z(e) === "html" ? e : e.assignedSlot || e.parentNode || (Te(e) ? e.host : null) || Q(e);
}
function Ge(e) {
  return !N(e) || Y(e).position === "fixed" ? null : e.offsetParent;
}
function Rt(e) {
  var t = /firefox/i.test(je()), r = /Trident/i.test(je());
  if (r && N(e)) {
    var n = Y(e);
    if (n.position === "fixed")
      return null;
  }
  var a = Ee(e);
  for (Te(a) && (a = a.host); N(a) && ["html", "body"].indexOf(z(a)) < 0; ) {
    var i = Y(a);
    if (i.transform !== "none" || i.perspective !== "none" || i.contain === "paint" || ["transform", "perspective"].indexOf(i.willChange) !== -1 || t && i.willChange === "filter" || t && i.filter && i.filter !== "none")
      return a;
    a = a.parentNode;
  }
  return null;
}
function ye(e) {
  for (var t = W(e), r = Ge(e); r && Ct(r) && Y(r).position === "static"; )
    r = Ge(r);
  return r && (z(r) === "html" || z(r) === "body" && Y(r).position === "static") ? t : r || Rt(e) || t;
}
function Ce(e) {
  return ["top", "bottom"].indexOf(e) >= 0 ? "x" : "y";
}
function de(e, t, r) {
  return ae(e, Oe(t, r));
}
function Mt(e, t, r) {
  var n = de(e, t, r);
  return n > r ? r : n;
}
function it() {
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0
  };
}
function st(e) {
  return Object.assign({}, it(), e);
}
function ct(e, t) {
  return t.reduce(function(r, n) {
    return r[n] = e, r;
  }, {});
}
var Bt = function(t, r) {
  return t = typeof t == "function" ? t(Object.assign({}, r.rects, {
    placement: r.placement
  })) : t, st(typeof t != "number" ? t : ct(t, me));
};
function Vt(e) {
  var t, r = e.state, n = e.name, a = e.options, i = r.elements.arrow, p = r.modifiersData.popperOffsets, c = G(r.placement), s = Ce(c), u = [H, I].indexOf(c) >= 0, l = u ? "height" : "width";
  if (!(!i || !p)) {
    var d = Bt(a.padding, r), g = De(i), f = s === "y" ? V : H, h = s === "y" ? Z : I, x = r.rects.reference[l] + r.rects.reference[s] - p[s] - r.rects.popper[l], b = p[s] - r.rects.reference[s], O = ye(i), j = O ? s === "y" ? O.clientHeight || 0 : O.clientWidth || 0 : 0, y = x / 2 - b / 2, m = d[f], w = j - g[l] - d[h], E = j / 2 - g[l] / 2 + y, $ = de(m, E, w), A = s;
    r.modifiersData[n] = (t = {}, t[A] = $, t.centerOffset = $ - E, t);
  }
}
function Ht(e) {
  var t = e.state, r = e.options, n = r.element, a = n === void 0 ? "[data-popper-arrow]" : n;
  a != null && (typeof a == "string" && (a = t.elements.popper.querySelector(a), !a) || ot(t.elements.popper, a) && (t.elements.arrow = a));
}
var qt = {
  name: "arrow",
  enabled: !0,
  phase: "main",
  fn: Vt,
  effect: Ht,
  requires: ["popperOffsets"],
  requiresIfExists: ["preventOverflow"]
};
function ue(e) {
  return e.split("-")[1];
}
var Wt = {
  top: "auto",
  right: "auto",
  bottom: "auto",
  left: "auto"
};
function Ft(e, t) {
  var r = e.x, n = e.y, a = t.devicePixelRatio || 1;
  return {
    x: ce(r * a) / a || 0,
    y: ce(n * a) / a || 0
  };
}
function ze(e) {
  var t, r = e.popper, n = e.popperRect, a = e.placement, i = e.variation, p = e.offsets, c = e.position, s = e.gpuAcceleration, u = e.adaptive, l = e.roundOffsets, d = e.isFixed, g = p.x, f = g === void 0 ? 0 : g, h = p.y, x = h === void 0 ? 0 : h, b = typeof l == "function" ? l({
    x: f,
    y: x
  }) : {
    x: f,
    y: x
  };
  f = b.x, x = b.y;
  var O = p.hasOwnProperty("x"), j = p.hasOwnProperty("y"), y = H, m = V, w = window;
  if (u) {
    var E = ye(r), $ = "clientHeight", A = "clientWidth";
    if (E === W(r) && (E = Q(r), Y(E).position !== "static" && c === "absolute" && ($ = "scrollHeight", A = "scrollWidth")), E = E, a === V || (a === H || a === I) && i === ge) {
      m = Z;
      var L = d && E === w && w.visualViewport ? w.visualViewport.height : E[$];
      x -= L - n.height, x *= s ? 1 : -1;
    }
    if (a === H || (a === V || a === Z) && i === ge) {
      y = I;
      var S = d && E === w && w.visualViewport ? w.visualViewport.width : E[A];
      f -= S - n.width, f *= s ? 1 : -1;
    }
  }
  var T = Object.assign({
    position: c
  }, u && Wt), R = l === !0 ? Ft({
    x: f,
    y: x
  }, W(r)) : {
    x: f,
    y: x
  };
  if (f = R.x, x = R.y, s) {
    var D;
    return Object.assign({}, T, (D = {}, D[m] = j ? "0" : "", D[y] = O ? "0" : "", D.transform = (w.devicePixelRatio || 1) <= 1 ? "translate(" + f + "px, " + x + "px)" : "translate3d(" + f + "px, " + x + "px, 0)", D));
  }
  return Object.assign({}, T, (t = {}, t[m] = j ? x + "px" : "", t[y] = O ? f + "px" : "", t.transform = "", t));
}
function Nt(e) {
  var t = e.state, r = e.options, n = r.gpuAcceleration, a = n === void 0 ? !0 : n, i = r.adaptive, p = i === void 0 ? !0 : i, c = r.roundOffsets, s = c === void 0 ? !0 : c, u = {
    placement: G(t.placement),
    variation: ue(t.placement),
    popper: t.elements.popper,
    popperRect: t.rects.popper,
    gpuAcceleration: a,
    isFixed: t.options.strategy === "fixed"
  };
  t.modifiersData.popperOffsets != null && (t.styles.popper = Object.assign({}, t.styles.popper, ze(Object.assign({}, u, {
    offsets: t.modifiersData.popperOffsets,
    position: t.options.strategy,
    adaptive: p,
    roundOffsets: s
  })))), t.modifiersData.arrow != null && (t.styles.arrow = Object.assign({}, t.styles.arrow, ze(Object.assign({}, u, {
    offsets: t.modifiersData.arrow,
    position: "absolute",
    adaptive: !1,
    roundOffsets: s
  })))), t.attributes.popper = Object.assign({}, t.attributes.popper, {
    "data-popper-placement": t.placement
  });
}
var Zt = {
  name: "computeStyles",
  enabled: !0,
  phase: "beforeWrite",
  fn: Nt,
  data: {}
}, we = {
  passive: !0
};
function It(e) {
  var t = e.state, r = e.instance, n = e.options, a = n.scroll, i = a === void 0 ? !0 : a, p = n.resize, c = p === void 0 ? !0 : p, s = W(t.elements.popper), u = [].concat(t.scrollParents.reference, t.scrollParents.popper);
  return i && u.forEach(function(l) {
    l.addEventListener("scroll", r.update, we);
  }), c && s.addEventListener("resize", r.update, we), function() {
    i && u.forEach(function(l) {
      l.removeEventListener("scroll", r.update, we);
    }), c && s.removeEventListener("resize", r.update, we);
  };
}
var Kt = {
  name: "eventListeners",
  enabled: !0,
  phase: "write",
  fn: function() {
  },
  effect: It,
  data: {}
}, Ut = {
  left: "right",
  right: "left",
  bottom: "top",
  top: "bottom"
};
function xe(e) {
  return e.replace(/left|right|bottom|top/g, function(t) {
    return Ut[t];
  });
}
var Gt = {
  start: "end",
  end: "start"
};
function Xe(e) {
  return e.replace(/start|end/g, function(t) {
    return Gt[t];
  });
}
function Re(e) {
  var t = W(e), r = t.pageXOffset, n = t.pageYOffset;
  return {
    scrollLeft: r,
    scrollTop: n
  };
}
function Me(e) {
  return le(Q(e)).left + Re(e).scrollLeft;
}
function zt(e, t) {
  var r = W(e), n = Q(e), a = r.visualViewport, i = n.clientWidth, p = n.clientHeight, c = 0, s = 0;
  if (a) {
    i = a.width, p = a.height;
    var u = at();
    (u || !u && t === "fixed") && (c = a.offsetLeft, s = a.offsetTop);
  }
  return {
    width: i,
    height: p,
    x: c + Me(e),
    y: s
  };
}
function Xt(e) {
  var t, r = Q(e), n = Re(e), a = (t = e.ownerDocument) == null ? void 0 : t.body, i = ae(r.scrollWidth, r.clientWidth, a ? a.scrollWidth : 0, a ? a.clientWidth : 0), p = ae(r.scrollHeight, r.clientHeight, a ? a.scrollHeight : 0, a ? a.clientHeight : 0), c = -n.scrollLeft + Me(e), s = -n.scrollTop;
  return Y(a || r).direction === "rtl" && (c += ae(r.clientWidth, a ? a.clientWidth : 0) - i), {
    width: i,
    height: p,
    x: c,
    y: s
  };
}
function Be(e) {
  var t = Y(e), r = t.overflow, n = t.overflowX, a = t.overflowY;
  return /auto|scroll|overlay|hidden/.test(r + a + n);
}
function lt(e) {
  return ["html", "body", "#document"].indexOf(z(e)) >= 0 ? e.ownerDocument.body : N(e) && Be(e) ? e : lt(Ee(e));
}
function ve(e, t) {
  var r;
  t === void 0 && (t = []);
  var n = lt(e), a = n === ((r = e.ownerDocument) == null ? void 0 : r.body), i = W(n), p = a ? [i].concat(i.visualViewport || [], Be(n) ? n : []) : n, c = t.concat(p);
  return a ? c : c.concat(ve(Ee(p)));
}
function $e(e) {
  return Object.assign({}, e, {
    left: e.x,
    top: e.y,
    right: e.x + e.width,
    bottom: e.y + e.height
  });
}
function Yt(e, t) {
  var r = le(e, !1, t === "fixed");
  return r.top = r.top + e.clientTop, r.left = r.left + e.clientLeft, r.bottom = r.top + e.clientHeight, r.right = r.left + e.clientWidth, r.width = e.clientWidth, r.height = e.clientHeight, r.x = r.left, r.y = r.top, r;
}
function Ye(e, t, r) {
  return t === rt ? $e(zt(e, r)) : oe(t) ? Yt(t, r) : $e(Xt(Q(e)));
}
function Jt(e) {
  var t = ve(Ee(e)), r = ["absolute", "fixed"].indexOf(Y(e).position) >= 0, n = r && N(e) ? ye(e) : e;
  return oe(n) ? t.filter(function(a) {
    return oe(a) && ot(a, n) && z(a) !== "body";
  }) : [];
}
function Qt(e, t, r, n) {
  var a = t === "clippingParents" ? Jt(e) : [].concat(t), i = [].concat(a, [r]), p = i[0], c = i.reduce(function(s, u) {
    var l = Ye(e, u, n);
    return s.top = ae(l.top, s.top), s.right = Oe(l.right, s.right), s.bottom = Oe(l.bottom, s.bottom), s.left = ae(l.left, s.left), s;
  }, Ye(e, p, n));
  return c.width = c.right - c.left, c.height = c.bottom - c.top, c.x = c.left, c.y = c.top, c;
}
function ut(e) {
  var t = e.reference, r = e.element, n = e.placement, a = n ? G(n) : null, i = n ? ue(n) : null, p = t.x + t.width / 2 - r.width / 2, c = t.y + t.height / 2 - r.height / 2, s;
  switch (a) {
    case V:
      s = {
        x: p,
        y: t.y - r.height
      };
      break;
    case Z:
      s = {
        x: p,
        y: t.y + t.height
      };
      break;
    case I:
      s = {
        x: t.x + t.width,
        y: c
      };
      break;
    case H:
      s = {
        x: t.x - r.width,
        y: c
      };
      break;
    default:
      s = {
        x: t.x,
        y: t.y
      };
  }
  var u = a ? Ce(a) : null;
  if (u != null) {
    var l = u === "y" ? "height" : "width";
    switch (i) {
      case se:
        s[u] = s[u] - (t[l] / 2 - r[l] / 2);
        break;
      case ge:
        s[u] = s[u] + (t[l] / 2 - r[l] / 2);
        break;
    }
  }
  return s;
}
function he(e, t) {
  t === void 0 && (t = {});
  var r = t, n = r.placement, a = n === void 0 ? e.placement : n, i = r.strategy, p = i === void 0 ? e.strategy : i, c = r.boundary, s = c === void 0 ? yt : c, u = r.rootBoundary, l = u === void 0 ? rt : u, d = r.elementContext, g = d === void 0 ? pe : d, f = r.altBoundary, h = f === void 0 ? !1 : f, x = r.padding, b = x === void 0 ? 0 : x, O = st(typeof b != "number" ? b : ct(b, me)), j = g === pe ? bt : pe, y = e.rects.popper, m = e.elements[h ? j : g], w = Qt(oe(m) ? m : m.contextElement || Q(e.elements.popper), s, l, p), E = le(e.elements.reference), $ = ut({
    reference: E,
    element: y,
    placement: a
  }), A = $e(Object.assign({}, y, $)), L = g === pe ? A : E, S = {
    top: w.top - L.top + O.top,
    bottom: L.bottom - w.bottom + O.bottom,
    left: w.left - L.left + O.left,
    right: L.right - w.right + O.right
  }, T = e.modifiersData.offset;
  if (g === pe && T) {
    var R = T[a];
    Object.keys(S).forEach(function(D) {
      var M = [I, Z].indexOf(D) >= 0 ? 1 : -1, K = [V, Z].indexOf(D) >= 0 ? "y" : "x";
      S[D] += R[K] * M;
    });
  }
  return S;
}
function _t(e, t) {
  t === void 0 && (t = {});
  var r = t, n = r.placement, a = r.boundary, i = r.rootBoundary, p = r.padding, c = r.flipVariations, s = r.allowedAutoPlacements, u = s === void 0 ? nt : s, l = ue(n), d = l ? c ? Ue : Ue.filter(function(h) {
    return ue(h) === l;
  }) : me, g = d.filter(function(h) {
    return u.indexOf(h) >= 0;
  });
  g.length === 0 && (g = d);
  var f = g.reduce(function(h, x) {
    return h[x] = he(e, {
      placement: x,
      boundary: a,
      rootBoundary: i,
      padding: p
    })[G(x)], h;
  }, {});
  return Object.keys(f).sort(function(h, x) {
    return f[h] - f[x];
  });
}
function er(e) {
  if (G(e) === Se)
    return [];
  var t = xe(e);
  return [Xe(e), t, Xe(t)];
}
function tr(e) {
  var t = e.state, r = e.options, n = e.name;
  if (!t.modifiersData[n]._skip) {
    for (var a = r.mainAxis, i = a === void 0 ? !0 : a, p = r.altAxis, c = p === void 0 ? !0 : p, s = r.fallbackPlacements, u = r.padding, l = r.boundary, d = r.rootBoundary, g = r.altBoundary, f = r.flipVariations, h = f === void 0 ? !0 : f, x = r.allowedAutoPlacements, b = t.options.placement, O = G(b), j = O === b, y = s || (j || !h ? [xe(b)] : er(b)), m = [b].concat(y).reduce(function(X, o) {
      return X.concat(G(o) === Se ? _t(t, {
        placement: o,
        boundary: l,
        rootBoundary: d,
        padding: u,
        flipVariations: h,
        allowedAutoPlacements: x
      }) : o);
    }, []), w = t.rects.reference, E = t.rects.popper, $ = /* @__PURE__ */ new Map(), A = !0, L = m[0], S = 0; S < m.length; S++) {
      var T = m[S], R = G(T), D = ue(T) === se, M = [V, Z].indexOf(R) >= 0, K = M ? "width" : "height", C = he(t, {
        placement: T,
        boundary: l,
        rootBoundary: d,
        altBoundary: g,
        padding: u
      }), B = M ? D ? I : H : D ? Z : V;
      w[K] > E[K] && (B = xe(B));
      var _ = xe(B), F = [];
      if (i && F.push(C[R] <= 0), c && F.push(C[B] <= 0, C[_] <= 0), F.every(function(X) {
        return X;
      })) {
        L = T, A = !1;
        break;
      }
      $.set(T, F);
    }
    if (A)
      for (var ee = h ? 3 : 1, ie = function(o) {
        var v = m.find(function(P) {
          var k = $.get(P);
          if (k)
            return k.slice(0, o).every(function(q) {
              return q;
            });
        });
        if (v)
          return L = v, "break";
      }, J = ee; J > 0; J--) {
        var te = ie(J);
        if (te === "break") break;
      }
    t.placement !== L && (t.modifiersData[n]._skip = !0, t.placement = L, t.reset = !0);
  }
}
var rr = {
  name: "flip",
  enabled: !0,
  phase: "main",
  fn: tr,
  requiresIfExists: ["offset"],
  data: {
    _skip: !1
  }
};
function Je(e, t, r) {
  return r === void 0 && (r = {
    x: 0,
    y: 0
  }), {
    top: e.top - t.height - r.y,
    right: e.right - t.width + r.x,
    bottom: e.bottom - t.height + r.y,
    left: e.left - t.width - r.x
  };
}
function Qe(e) {
  return [V, I, Z, H].some(function(t) {
    return e[t] >= 0;
  });
}
function nr(e) {
  var t = e.state, r = e.name, n = t.rects.reference, a = t.rects.popper, i = t.modifiersData.preventOverflow, p = he(t, {
    elementContext: "reference"
  }), c = he(t, {
    altBoundary: !0
  }), s = Je(p, n), u = Je(c, a, i), l = Qe(s), d = Qe(u);
  t.modifiersData[r] = {
    referenceClippingOffsets: s,
    popperEscapeOffsets: u,
    isReferenceHidden: l,
    hasPopperEscaped: d
  }, t.attributes.popper = Object.assign({}, t.attributes.popper, {
    "data-popper-reference-hidden": l,
    "data-popper-escaped": d
  });
}
var ar = {
  name: "hide",
  enabled: !0,
  phase: "main",
  requiresIfExists: ["preventOverflow"],
  fn: nr
};
function or(e, t, r) {
  var n = G(e), a = [H, V].indexOf(n) >= 0 ? -1 : 1, i = typeof r == "function" ? r(Object.assign({}, t, {
    placement: e
  })) : r, p = i[0], c = i[1];
  return p = p || 0, c = (c || 0) * a, [H, I].indexOf(n) >= 0 ? {
    x: c,
    y: p
  } : {
    x: p,
    y: c
  };
}
function ir(e) {
  var t = e.state, r = e.options, n = e.name, a = r.offset, i = a === void 0 ? [0, 0] : a, p = nt.reduce(function(l, d) {
    return l[d] = or(d, t.rects, i), l;
  }, {}), c = p[t.placement], s = c.x, u = c.y;
  t.modifiersData.popperOffsets != null && (t.modifiersData.popperOffsets.x += s, t.modifiersData.popperOffsets.y += u), t.modifiersData[n] = p;
}
var sr = {
  name: "offset",
  enabled: !0,
  phase: "main",
  requires: ["popperOffsets"],
  fn: ir
};
function cr(e) {
  var t = e.state, r = e.name;
  t.modifiersData[r] = ut({
    reference: t.rects.reference,
    element: t.rects.popper,
    placement: t.placement
  });
}
var lr = {
  name: "popperOffsets",
  enabled: !0,
  phase: "read",
  fn: cr,
  data: {}
};
function ur(e) {
  return e === "x" ? "y" : "x";
}
function fr(e) {
  var t = e.state, r = e.options, n = e.name, a = r.mainAxis, i = a === void 0 ? !0 : a, p = r.altAxis, c = p === void 0 ? !1 : p, s = r.boundary, u = r.rootBoundary, l = r.altBoundary, d = r.padding, g = r.tether, f = g === void 0 ? !0 : g, h = r.tetherOffset, x = h === void 0 ? 0 : h, b = he(t, {
    boundary: s,
    rootBoundary: u,
    padding: d,
    altBoundary: l
  }), O = G(t.placement), j = ue(t.placement), y = !j, m = Ce(O), w = ur(m), E = t.modifiersData.popperOffsets, $ = t.rects.reference, A = t.rects.popper, L = typeof x == "function" ? x(Object.assign({}, t.rects, {
    placement: t.placement
  })) : x, S = typeof L == "number" ? {
    mainAxis: L,
    altAxis: L
  } : Object.assign({
    mainAxis: 0,
    altAxis: 0
  }, L), T = t.modifiersData.offset ? t.modifiersData.offset[t.placement] : null, R = {
    x: 0,
    y: 0
  };
  if (E) {
    if (i) {
      var D, M = m === "y" ? V : H, K = m === "y" ? Z : I, C = m === "y" ? "height" : "width", B = E[m], _ = B + b[M], F = B - b[K], ee = f ? -A[C] / 2 : 0, ie = j === se ? $[C] : A[C], J = j === se ? -A[C] : -$[C], te = t.elements.arrow, X = f && te ? De(te) : {
        width: 0,
        height: 0
      }, o = t.modifiersData["arrow#persistent"] ? t.modifiersData["arrow#persistent"].padding : it(), v = o[M], P = o[K], k = de(0, $[C], X[C]), q = y ? $[C] / 2 - ee - k - v - S.mainAxis : ie - k - v - S.mainAxis, U = y ? -$[C] / 2 + ee + k + P + S.mainAxis : J + k + P + S.mainAxis, re = t.elements.arrow && ye(t.elements.arrow), fe = re ? m === "y" ? re.clientTop || 0 : re.clientLeft || 0 : 0, Ve = (D = T?.[m]) != null ? D : 0, dt = B + q - Ve - fe, vt = B + U - Ve, He = de(f ? Oe(_, dt) : _, B, f ? ae(F, vt) : F);
      E[m] = He, R[m] = He - B;
    }
    if (c) {
      var qe, gt = m === "x" ? V : H, ht = m === "x" ? Z : I, ne = E[w], be = w === "y" ? "height" : "width", We = ne + b[gt], Fe = ne - b[ht], Ae = [V, H].indexOf(O) !== -1, Ne = (qe = T?.[w]) != null ? qe : 0, Ze = Ae ? We : ne - $[be] - A[be] - Ne + S.altAxis, Ie = Ae ? ne + $[be] + A[be] - Ne - S.altAxis : Fe, Ke = f && Ae ? Mt(Ze, ne, Ie) : de(f ? Ze : We, ne, f ? Ie : Fe);
      E[w] = Ke, R[w] = Ke - ne;
    }
    t.modifiersData[n] = R;
  }
}
var pr = {
  name: "preventOverflow",
  enabled: !0,
  phase: "main",
  fn: fr,
  requiresIfExists: ["offset"]
};
function dr(e) {
  return {
    scrollLeft: e.scrollLeft,
    scrollTop: e.scrollTop
  };
}
function vr(e) {
  return e === W(e) || !N(e) ? Re(e) : dr(e);
}
function gr(e) {
  var t = e.getBoundingClientRect(), r = ce(t.width) / e.offsetWidth || 1, n = ce(t.height) / e.offsetHeight || 1;
  return r !== 1 || n !== 1;
}
function hr(e, t, r) {
  r === void 0 && (r = !1);
  var n = N(t), a = N(t) && gr(t), i = Q(t), p = le(e, a, r), c = {
    scrollLeft: 0,
    scrollTop: 0
  }, s = {
    x: 0,
    y: 0
  };
  return (n || !n && !r) && ((z(t) !== "body" || Be(i)) && (c = vr(t)), N(t) ? (s = le(t, !0), s.x += t.clientLeft, s.y += t.clientTop) : i && (s.x = Me(i))), {
    x: p.left + c.scrollLeft - s.x,
    y: p.top + c.scrollTop - s.y,
    width: p.width,
    height: p.height
  };
}
function mr(e) {
  var t = /* @__PURE__ */ new Map(), r = /* @__PURE__ */ new Set(), n = [];
  e.forEach(function(i) {
    t.set(i.name, i);
  });
  function a(i) {
    r.add(i.name);
    var p = [].concat(i.requires || [], i.requiresIfExists || []);
    p.forEach(function(c) {
      if (!r.has(c)) {
        var s = t.get(c);
        s && a(s);
      }
    }), n.push(i);
  }
  return e.forEach(function(i) {
    r.has(i.name) || a(i);
  }), n;
}
function yr(e) {
  var t = mr(e);
  return Lt.reduce(function(r, n) {
    return r.concat(t.filter(function(a) {
      return a.phase === n;
    }));
  }, []);
}
function br(e) {
  var t;
  return function() {
    return t || (t = new Promise(function(r) {
      Promise.resolve().then(function() {
        t = void 0, r(e());
      });
    })), t;
  };
}
function wr(e) {
  var t = e.reduce(function(r, n) {
    var a = r[n.name];
    return r[n.name] = a ? Object.assign({}, a, n, {
      options: Object.assign({}, a.options, n.options),
      data: Object.assign({}, a.data, n.data)
    }) : n, r;
  }, {});
  return Object.keys(t).map(function(r) {
    return t[r];
  });
}
var _e = {
  placement: "bottom",
  modifiers: [],
  strategy: "absolute"
};
function et() {
  for (var e = arguments.length, t = new Array(e), r = 0; r < e; r++)
    t[r] = arguments[r];
  return !t.some(function(n) {
    return !(n && typeof n.getBoundingClientRect == "function");
  });
}
function ft(e) {
  e === void 0 && (e = {});
  var t = e, r = t.defaultModifiers, n = r === void 0 ? [] : r, a = t.defaultOptions, i = a === void 0 ? _e : a;
  return function(c, s, u) {
    u === void 0 && (u = i);
    var l = {
      placement: "bottom",
      orderedModifiers: [],
      options: Object.assign({}, _e, i),
      modifiersData: {},
      elements: {
        reference: c,
        popper: s
      },
      attributes: {},
      styles: {}
    }, d = [], g = !1, f = {
      state: l,
      setOptions: function(O) {
        var j = typeof O == "function" ? O(l.options) : O;
        x(), l.options = Object.assign({}, i, l.options, j), l.scrollParents = {
          reference: oe(c) ? ve(c) : c.contextElement ? ve(c.contextElement) : [],
          popper: ve(s)
        };
        var y = yr(wr([].concat(n, l.options.modifiers)));
        return l.orderedModifiers = y.filter(function(m) {
          return m.enabled;
        }), h(), f.update();
      },
      forceUpdate: function() {
        if (!g) {
          var O = l.elements, j = O.reference, y = O.popper;
          if (et(j, y)) {
            l.rects = {
              reference: hr(j, ye(y), l.options.strategy === "fixed"),
              popper: De(y)
            }, l.reset = !1, l.placement = l.options.placement, l.orderedModifiers.forEach(function(S) {
              return l.modifiersData[S.name] = Object.assign({}, S.data);
            });
            for (var m = 0; m < l.orderedModifiers.length; m++) {
              if (l.reset === !0) {
                l.reset = !1, m = -1;
                continue;
              }
              var w = l.orderedModifiers[m], E = w.fn, $ = w.options, A = $ === void 0 ? {} : $, L = w.name;
              typeof E == "function" && (l = E({
                state: l,
                options: A,
                name: L,
                instance: f
              }) || l);
            }
          }
        }
      },
      update: br(function() {
        return new Promise(function(b) {
          f.forceUpdate(), b(l);
        });
      }),
      destroy: function() {
        x(), g = !0;
      }
    };
    if (!et(c, s))
      return f;
    f.setOptions(u).then(function(b) {
      !g && u.onFirstUpdate && u.onFirstUpdate(b);
    });
    function h() {
      l.orderedModifiers.forEach(function(b) {
        var O = b.name, j = b.options, y = j === void 0 ? {} : j, m = b.effect;
        if (typeof m == "function") {
          var w = m({
            state: l,
            name: O,
            instance: f,
            options: y
          }), E = function() {
          };
          d.push(w || E);
        }
      });
    }
    function x() {
      d.forEach(function(b) {
        return b();
      }), d = [];
    }
    return f;
  };
}
ft();
var xr = [Kt, lr, Zt, Dt, sr, rr, pr, qt, ar], Or = ft({
  defaultModifiers: xr
});
const Pe = (e) => Object.prototype.toString.call(e) === "[object String]", Le = (e) => typeof e == "object" && !Array.isArray(e) && e !== null, pt = (e, t) => Le(e) ? (Object.keys(t).forEach((r) => e[r] = Le(t[r]) ? pt(e[r] || {}, t[r]) : t[r]), e) : t;
function kr(e, { target: t = "#placekit", ...r } = {}) {
  const n = Pe(t) ? document.querySelector(t) : t;
  if (n) {
    if (n.tagName !== "INPUT" || !["text", "search"].includes(n.getAttribute("type")))
      throw 'Error: target must be an HTML input of type "text" or "search".';
  } else throw "Error: target not found.";
  const a = ke(e), i = /* @__PURE__ */ new Map();
  let p = [], c = null, s = null, u = null, l = !1;
  const d = {}, g = {
    empty: !n.value,
    dirty: !1,
    freeForm: !0,
    geolocation: !1,
    countryMode: !1
  }, f = {
    panel: {
      className: "",
      offset: 4,
      strategy: "absolute",
      flip: !1
    },
    format: {
      flag: (o) => `<img class="pka-flag" src="https://flagcdn.com/64x48/${o?.toLowerCase()}.png" alt="${o}" loading="lazy" />`,
      icon: (o, v) => `<span class="pka-icon pka-icon-${o}" role="img" aria-label="${v || "icon"}"></span>`,
      sub: (o) => {
        switch (o.type) {
          case "administrative":
            return [o.country].filter((v) => v).join(" ");
          case "city":
            return [o.zipcode.sort()[0], o.country].filter((v) => v).join(" ");
          case "country":
            return "";
          case "county":
            return [o.administrative, o.country].filter((v) => v).join(" ");
          default:
            return [o.city, o.county].filter((v) => v).join(", ");
        }
      },
      noResults: (o) => `No results for ${o}`,
      value: (o) => o.name,
      applySuggestion: "Apply suggestion",
      suggestions: "Address suggestions",
      changeCountry: "Change country",
      cancel: "Cancel"
    },
    countryAutoFill: !0,
    countrySelect: !0
  };
  n.setAttribute("autocomplete", "off"), n.setAttribute("aria-autocomplete", "list"), n.setAttribute("aria-expanded", !1), n.setAttribute("role", "combobox");
  const h = document.createElement("div");
  h.classList.add("pka-panel"), h.innerHTML = `
    <div class="pka-panel-loading" role="progressbar" aria-hidden="true">${f.format.icon(
    "loading"
  )}</div>
    <div class="pka-panel-suggestions" role="listbox" aria-label="${f.format.suggestions}"></div>
    <div class="pka-panel-footer">
      <div class="pka-panel-country">
        <input type="checkbox" id="pka-panel-country-mode" class="pka-sr-only" />
        <label for="pka-panel-country-mode" class="pka-panel-country-open" aria-label="${f.format.changeCountry}">
        </label>
        <label for="pka-panel-country-mode" class="pka-panel-country-close">
          ${f.format.icon("cancel")}
          <span class="pka-panel-country-label">${f.format.cancel}</span>
        </label>
      </div>
      <div class="pka-panel-credits">
        <span class="pka-panel-credits-text">Powered by</span>
        <a href="https://placekit.io/?utm_source=${encodeURI(
    window.location.hostname
  )}" target="_blank" class="pka-panel-credits-link" aria-label="PlaceKit.io">
          <svg viewBox="0 0 98 24" fill-rule="evenodd" fill="currentColor" height="14">
            <path d="M10.618 20.336a.506.506 0 0 1 .558-.414 8.009 8.009 0 0 0 1.867 0 .506.506 0 0 1 .557.414l.177 1a.504.504 0 0 1-.435.59 10.227 10.227 0 0 1-2.465 0 .51.51 0 0 1-.434-.59l.175-1Zm-6.577-5.521a.506.506 0 0 1 .639.28 8.12 8.12 0 0 0 2.971 3.542.504.504 0 0 1 .164.675c-.152.268-.35.612-.507.884a.508.508 0 0 1-.71.174 10.181 10.181 0 0 1-3.807-4.539.502.502 0 0 1 .293-.665c.294-.109.667-.245.957-.351Zm15.5.281a.504.504 0 0 1 .637-.278c.29.103.664.239.958.346a.503.503 0 0 1 .295.668 10.19 10.19 0 0 1-3.811 4.536.5.5 0 0 1-.707-.174c-.158-.27-.357-.614-.511-.88a.508.508 0 0 1 .165-.679 8.107 8.107 0 0 0 2.974-3.539Zm-11.003-.391-.008-.007a5.064 5.064 0 0 1 0-7.158 5.064 5.064 0 0 1 7.158 0 5.064 5.064 0 0 1 .001 7.157l.006.007-2.863 2.864a1.013 1.013 0 0 1-1.432 0l-2.862-2.863Zm3.575-5.601a2.015 2.015 0 0 1 0 4.028 2.015 2.015 0 0 1 0-4.028Zm9.023-.511a.507.507 0 0 1 .656.324c.236.775.382 1.591.426 2.433a.504.504 0 0 1-.505.527c-.312.002-.709.002-1.016.002a.507.507 0 0 1-.505-.481 7.986 7.986 0 0 0-.321-1.833.505.505 0 0 1 .31-.623l.955-.349Zm-18.707.324a.505.505 0 0 1 .654-.323c.295.106.667.242.956.347.253.092.39.367.311.624a7.988 7.988 0 0 0-.323 1.833.505.505 0 0 1-.504.479c-.308.002-.704.002-1.017.002a.508.508 0 0 1-.505-.529c.044-.842.191-1.658.428-2.433Zm11.349-6.499a.504.504 0 0 1 .607-.406 10.143 10.143 0 0 1 5.128 2.967.507.507 0 0 1-.049.726c-.238.203-.542.458-.778.656a.506.506 0 0 1-.697-.045 8.071 8.071 0 0 0-4.001-2.315.504.504 0 0 1-.385-.579c.051-.304.12-.695.175-1.004Zm-3.942-.404a.502.502 0 0 1 .604.405c.056.308.125.699.179 1.003a.506.506 0 0 1-.387.581 8.07 8.07 0 0 0-4.003 2.312.504.504 0 0 1-.694.044c-.237-.196-.541-.451-.781-.653a.506.506 0 0 1-.049-.728 10.136 10.136 0 0 1 5.131-2.964Z" />
            <path d="M30.315 20.5v-5.028c.597.868 1.483 1.321 2.55 1.321 2.496 0 4.087-1.845 4.087-5.137 0-3.165-1.555-5.1-4.087-5.1-1.067 0-1.935.489-2.55 1.375V6.828H28V20.5h2.315Zm61.146-6.999c0 2.17.94 3.292 2.911 3.292.633 0 1.158-.109 1.411-.236v-1.7h-.434c-1.284 0-1.573-.542-1.573-1.555V8.618h2.043v-1.79h-2.043V4.603h-2.315v2.225h-1.248v1.79h1.248v4.883Zm-28.412-3.237c-.326-2.478-2.062-3.708-4.268-3.708-2.785 0-4.576 1.845-4.576 5.137 0 3.146 1.736 5.1 4.594 5.1 2.188 0 3.888-1.14 4.322-3.563h-2.333c-.235 1.103-.94 1.646-1.935 1.646-1.465 0-2.26-1.14-2.26-3.183 0-2.08.759-3.22 2.188-3.22.976 0 1.736.525 1.953 1.791h2.315Zm-17.018-.253c.145-.923.741-1.538 1.845-1.538 1.284 0 1.88.67 1.88 1.917v.236c-1.627.018-2.875.126-4.159.56-1.483.488-2.225 1.483-2.225 2.803 0 1.863 1.357 2.804 3.256 2.804 1.157 0 2.351-.471 3.146-1.592.019.525.073.995.199 1.32h2.406c-.181-.506-.29-1.175-.29-2.369V10.39c0-2.242-1.374-3.834-4.069-3.834-2.441 0-4.015 1.303-4.322 3.455h2.333Zm27.905 3.345h-2.441c-.308 1.031-1.049 1.538-2.134 1.538-1.411 0-2.225-.977-2.333-2.749h6.836v-.543c0-3.129-1.754-5.046-4.594-5.046-2.821 0-4.63 1.845-4.63 5.137 0 3.164 1.791 5.1 4.721 5.1 2.315 0 4.051-1.194 4.575-3.437Zm12.425 3.165h2.315V6.828h-2.315v9.693Zm-47.365 0h2.315V3.5h-2.315v13.021Zm36.966 0h2.315v-4.684l3.653 4.684h2.839l-4.178-5.389 3.907-4.304h-2.713l-3.508 4.051V3.5h-2.315v13.021Zm-30.257-2.658c0-.977.76-1.754 4.051-1.809v.416c0 1.447-1.103 2.604-2.532 2.604-.94 0-1.519-.47-1.519-1.211Zm-13.256-5.39c1.356 0 2.116 1.14 2.116 3.183 0 2.08-.742 3.22-2.116 3.22-1.356 0-2.134-1.158-2.134-3.22 0-2.061.741-3.183 2.134-3.183Zm36.821-.018c1.212 0 1.953.796 2.17 2.243h-4.358c.217-1.465.958-2.243 2.188-2.243Zm17.091-2.712h2.315V3.5h-2.315v2.243Z" />
          </svg>
        </a>
      </div>
    </div>
  `, document.body.append(h);
  const x = h.querySelector(".pka-panel-loading"), b = h.querySelector(".pka-panel-suggestions"), O = h.querySelector("#pka-panel-country-mode"), j = Or(n, h);
  function y(o, ...v) {
    i.has(o) && i.get(o).apply(d, v);
  }
  function m(o, v = !1) {
    if (!Le(o))
      throw "TypeError: setState first argument must be a key/value object.";
    let P = !1;
    for (const k in g)
      k in o && o[k] !== g[k] && (g[k] = o[k], P = !0, v || y(k, g[k]));
    P && y("state", g);
  }
  function w(o, { notify: v = !1, focus: P = !0 } = {}) {
    Pe(o) && (Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value").set.call(n, o), m({ empty: !n.value }), v && (c = null, n.dispatchEvent(new Event("input", { bubbles: !0 })), n.dispatchEvent(new Event("change", { bubbles: !0 }))), P && n.focus());
  }
  function E() {
    w("", {
      notify: !0,
      focus: !0
    }), A(!1), g.geolocation ? M() : p = [];
  }
  function $(o = !1) {
    c !== null && (w(c, { focus: !1 }), o && (c = null));
  }
  function A(o) {
    const v = h.classList.contains("pka-open"), P = typeof o > "u" ? !v : o;
    h.classList.toggle("pka-open", P), n.setAttribute("aria-expanded", P), v !== P && (P || L(), y(P ? "open" : "close"));
  }
  function L() {
    h.querySelectorAll('[role="option"]').forEach((o) => o.classList.remove("pka-active"));
  }
  function S(o) {
    c === null && (c = n.value);
    const v = Array.from(b.children), P = v.findIndex((U) => U.classList.contains("pka-active"));
    L();
    const k = v.length + 1, q = (P + 1 + o + k) % k;
    if (q === 0)
      $();
    else {
      const U = v[q - 1];
      U.classList.add("pka-active"), b.scrollTo({ top: U.offsetTop }), w(f.format.value(p[q - 1]));
    }
  }
  function T(o) {
    const v = Array.from(b.children);
    if (typeof o > "u" && (o = v.findIndex((q) => q.classList.contains("pka-active"))), !v[o]) return;
    const k = p[o];
    g.countryMode ? (K(k), C(!1)) : (v.forEach((q, U) => {
      q.classList.toggle("pka-selected", U === o), q.setAttribute("aria-selected", U === o);
    }), w(f.format.value(k), { notify: !0 }), m({
      dirty: !0,
      freeForm: !1
    }), A(!1), y("pick", n.value, k, o));
  }
  let R;
  function D(o) {
    clearTimeout(R), x.setAttribute("aria-hidden", !0), o && (R = setTimeout(() => {
      x.setAttribute("aria-hidden", !1);
    }, 300));
  }
  async function M() {
    c = null;
    const o = n.value;
    m({
      empty: !o,
      dirty: !0,
      freeForm: !0
    }), D(!0), O.disabled || await B();
    const v = l ? ["city", "country"] : ["country"];
    a.search(o, {
      countries: u ? [u.countrycode] : f.countries,
      types: g.countryMode ? ["country"] : f.types,
      maxResults: g.countryMode ? 20 : f.maxResults
    }).then(({ results: P }) => {
      D(!1), n.value === o && (p = P, b.innerHTML = P.length > 0 ? P.map(
        (k) => `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false">
          ${v.includes(k.type) ? f.format.flag(k.countrycode) : f.format.icon(k.type || "pin", k.type)}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">${k.highlight}</span>
            <span class="pka-panel-suggestion-label-sub">${f.format.sub(k)}</span>
          </span>
          <button type="button" class="pka-panel-suggestion-action" aria-label="${f.format.applySuggestion}" />
        </div>
      `
      ).join("") : `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false" aria-disabled="true">
          ${f.format.icon("noresults")}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">
              ${f.format.noResults?.call ? f.format.noResults(o) : f.format.noResults}
            </span>
          </span>
        </div>
      `, j.update(), y("results", o, P));
    }).catch((P) => y("error", P));
  }
  function K(o) {
    h.querySelector(".pka-panel-country-open").innerHTML = o === null ? "" : `
      ${f.format.flag(o.countrycode)}
      <span class="pka-panel-country-label">${o.name}</span>
      ${f.format.icon("switch")}
    `, o?.countrycode !== u?.countrycode && (u = o, y("countryChange", u));
  }
  function C(o) {
    O.checked = !O.disabled && (typeof o > "u" ? !O.checked : o), m({ countryMode: O.checked }), g.countryMode ? (s = n.value, w(u.name), n.select(), M()) : s !== null && (w(s), s = null, M());
  }
  function B() {
    return u ? Promise.resolve(u) : a.reverse({
      maxResults: 1,
      types: ["country"]
    }).then(({ results: o }) => o.length ? (K(o[0]), o[0]) : null).catch((o) => y("error", o));
  }
  h.addEventListener("mousemove", (o) => {
    !o.movementX && !o.movementY || (L(), o.target.closest('[role="option"]')?.classList.add("pka-active"));
  }), h.addEventListener("click", (o) => {
    const v = o.target.closest('[role="option"]');
    if (!v) return;
    o.stopPropagation();
    const P = Array.from(b.children).indexOf(v);
    if (o.target.closest(".pka-panel-suggestion-action")) {
      const k = p[P];
      k && (w(`${f.format.value(k)} `, { notify: !0 }), m({
        dirty: !0,
        freeForm: !1
      }));
    } else
      T(P);
  }), O.addEventListener("change", (o) => {
    C(o.target.checked);
  });
  function _(o) {
    o instanceof InputEvent && (A(!!n.value.trim() || g.countryMode), M());
  }
  n.addEventListener("input", _);
  function F() {
    !g.dirty && n.value ? (A(!0), M()) : A(!!n.value.trim() || g.geolocation || g.countryMode);
  }
  n.addEventListener("click", F), n.addEventListener("focus", F);
  function ee(o) {
    ![n, h].includes(o.target) && !h.contains(o.target) && (A(!1), $(!0));
  }
  window.addEventListener("click", ee);
  function ie(o) {
    if (n === document.activeElement) {
      const v = h.classList.contains("pka-open");
      switch (o.key) {
        case "Up":
        case "ArrowUp":
          v && (o.preventDefault(), o.altKey ? A(!1) : S(-1));
          break;
        case "Down":
        case "ArrowDown":
          p.length > 0 && (v ? o.altKey || (o.preventDefault(), S(1)) : (o.preventDefault(), A(!0)));
          break;
        case "Enter":
          v && (o.preventDefault(), T());
          break;
        case "Esc":
        case "Escape":
          o.preventDefault(), v ? g.countryMode ? C(!1) : (A(!1), $(!0)) : E();
          break;
        case "Tab":
          A(!1);
          break;
      }
    }
  }
  window.addEventListener("keydown", ie);
  function J() {
    window.requestAnimationFrame(() => {
      h.style.width = `${n.offsetWidth}px`, j.update();
    });
  }
  J();
  const te = new ResizeObserver(J);
  te.observe(n);
  function X(o = {}) {
    delete o.target;
    const {
      panel: v,
      format: P,
      countryAutoFill: k,
      countrySelect: q,
      ...U
    } = pt(f, o);
    a.configure(U), h.setAttribute("class", `pka-panel ${f.panel.className}`.trim()), j.setOptions({
      placement: "bottom-start",
      strategy: f.panel.strategy,
      modifiers: [
        {
          name: "flip",
          enabled: f.panel.flip
        },
        {
          name: "offset",
          options: {
            offset: [0, f.panel.offset]
          }
        }
      ]
    });
    const re = f.types?.join(",").toLowerCase() ?? "";
    O.disabled = f.countries || !f.countrySelect || re === "country", h.querySelector(".pka-panel-country").setAttribute("aria-hidden", O.disabled), l = !f.countries && !f.countrySelect && ["city", "city,country", "country,city", "country"].includes(re), f.countryAutoFill && re === "country" && !g.dirty && !n.value.trim() && B().then((fe) => {
      w(fe.name, {
        notify: !0,
        focus: !1
      }), m({ freeForm: !1 }), y("pick", fe.name, fe, 0);
    });
  }
  return X(r), Object.defineProperty(d, "input", {
    get: () => n
  }), Object.defineProperty(d, "options", {
    get: () => ({
      target: t,
      ...f,
      ...a.options
    })
  }), Object.defineProperty(d, "handlers", {
    get: () => Object.fromEntries(i)
  }), Object.defineProperty(d, "state", {
    get: () => g
  }), d.setValue = (o, v = !1) => (w(o, {
    notify: v,
    focus: !1
  }), d), d.clear = E, d.on = (o, v) => {
    if (!Pe(o))
      throw "Error: first argument 'event' must be a string.";
    if (typeof v < "u" && !v.call)
      throw "Error: second argument 'handler' must be a function if defined.";
    return v ? i.set(o, v) : i.has(o) && i.delete(o), d;
  }, d.open = () => (A(!0), d), d.close = () => (A(!1), d), d.configure = (o = {}) => (X(o), d), d.requestGeolocation = (o = {}) => a.requestGeolocation(o).then((v) => (m({ geolocation: !0 }, !0), y("geolocation", !0, v), K(null), n.focus(), M(), v)).catch((v) => {
    m({ geolocation: !1 }, !0), y("geolocation", !1, void 0, v.message);
  }), d.clearGeolocation = () => (a.clearGeolocation(), m({ geolocation: !1 }), d), d.destroy = () => {
    n.removeEventListener("input", _), n.removeEventListener("click", F), n.removeEventListener("focus", F), window.removeEventListener("keydown", ie), window.removeEventListener("click", ee), te.unobserve(n), h.remove();
  }, d;
}
export {
  kr as default
};
