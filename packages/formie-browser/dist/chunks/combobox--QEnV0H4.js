import { g as Re, d as de } from "./shared-BDEKVuB5.js";
import { e as Ke } from "./styles-C3aqgtek.js";
import { j as Be } from "./index-CZtn5KAB.js";
function te(r, e) {
  r.split(/\s+/).forEach((t) => {
    e(t);
  });
}
class je {
  constructor() {
    this._events = {};
  }
  on(e, t) {
    te(e, (i) => {
      const o = this._events[i] || [];
      o.push(t), this._events[i] = o;
    });
  }
  off(e, t) {
    var i = arguments.length;
    if (i === 0) {
      this._events = {};
      return;
    }
    te(e, (o) => {
      if (i === 1) {
        delete this._events[o];
        return;
      }
      const s = this._events[o];
      s !== void 0 && (s.splice(s.indexOf(t), 1), this._events[o] = s);
    });
  }
  trigger(e, ...t) {
    var i = this;
    te(e, (o) => {
      const s = i._events[o];
      s !== void 0 && s.forEach((n) => {
        n.apply(i, t);
      });
    });
  }
}
function Ye(r) {
  return r.plugins = {}, class extends r {
    constructor() {
      super(...arguments), this.plugins = {
        names: [],
        settings: {},
        requested: {},
        loaded: {}
      };
    }
    /**
     * Registers a plugin.
     *
     * @param {function} fn
     */
    static define(e, t) {
      r.plugins[e] = {
        name: e,
        fn: t
      };
    }
    /**
     * Initializes the listed plugins (with options).
     * Acceptable formats:
     *
     * List (without options):
     *   ['a', 'b', 'c']
     *
     * List (with options):
     *   [{'name': 'a', options: {}}, {'name': 'b', options: {}}]
     *
     * Hash (with options):
     *   {'a': { ... }, 'b': { ... }, 'c': { ... }}
     *
     * @param {array|object} plugins
     */
    initializePlugins(e) {
      var t, i;
      const o = this, s = [];
      if (Array.isArray(e))
        e.forEach((n) => {
          typeof n == "string" ? s.push(n) : (o.plugins.settings[n.name] = n.options, s.push(n.name));
        });
      else if (e)
        for (t in e)
          e.hasOwnProperty(t) && (o.plugins.settings[t] = e[t], s.push(t));
      for (; i = s.shift(); )
        o.require(i);
    }
    loadPlugin(e) {
      var t = this, i = t.plugins, o = r.plugins[e];
      if (!r.plugins.hasOwnProperty(e))
        throw new Error('Unable to find "' + e + '" plugin');
      i.requested[e] = !0, i.loaded[e] = o.fn.apply(t, [t.plugins.settings[e] || {}]), i.names.push(e);
    }
    /**
     * Initializes a plugin.
     *
     */
    require(e) {
      var t = this, i = t.plugins;
      if (!t.plugins.loaded.hasOwnProperty(e)) {
        if (i.requested[e])
          throw new Error('Plugin has circular dependency ("' + e + '")');
        t.loadPlugin(e);
      }
      return i.loaded[e];
    }
  };
}
const ee = (r) => (r = r.filter(Boolean), r.length < 2 ? r[0] || "" : Ue(r) == 1 ? "[" + r.join("") + "]" : "(?:" + r.join("|") + ")"), Fe = (r) => {
  if (!Ge(r))
    return r.join("");
  let e = "", t = 0;
  const i = () => {
    t > 1 && (e += "{" + t + "}");
  };
  return r.forEach((o, s) => {
    if (o === r[s - 1]) {
      t++;
      return;
    }
    i(), e += o, t = 1;
  }), i(), e;
}, $e = (r) => {
  let e = Array.from(r);
  return ee(e);
}, Ge = (r) => new Set(r).size !== r.length, K = (r) => (r + "").replace(/([\$\(\)\*\+\.\?\[\]\^\{\|\}\\])/gu, "\\$1"), Ue = (r) => r.reduce((e, t) => Math.max(e, qe(t)), 0), qe = (r) => Array.from(r).length, Te = (r) => {
  if (r.length === 1)
    return [[r]];
  let e = [];
  const t = r.substring(1);
  return Te(t).forEach(function(o) {
    let s = o.slice(0);
    s[0] = r.charAt(0) + s[0], e.push(s), s = o.slice(0), s.unshift(r.charAt(0)), e.push(s);
  }), e;
}, We = [[0, 65535]], Qe = "[̀-ͯ·ʾʼ]";
let Q, De;
const Je = 3, ce = {}, ue = {
  "/": "⁄∕",
  0: "߀",
  a: "ⱥɐɑ",
  aa: "ꜳ",
  ae: "æǽǣ",
  ao: "ꜵ",
  au: "ꜷ",
  av: "ꜹꜻ",
  ay: "ꜽ",
  b: "ƀɓƃ",
  c: "ꜿƈȼↄ",
  d: "đɗɖᴅƌꮷԁɦ",
  e: "ɛǝᴇɇ",
  f: "ꝼƒ",
  g: "ǥɠꞡᵹꝿɢ",
  h: "ħⱨⱶɥ",
  i: "ɨı",
  j: "ɉȷ",
  k: "ƙⱪꝁꝃꝅꞣ",
  l: "łƚɫⱡꝉꝇꞁɭ",
  m: "ɱɯϻ",
  n: "ꞥƞɲꞑᴎлԉ",
  o: "øǿɔɵꝋꝍᴑ",
  oe: "œ",
  oi: "ƣ",
  oo: "ꝏ",
  ou: "ȣ",
  p: "ƥᵽꝑꝓꝕρ",
  q: "ꝗꝙɋ",
  r: "ɍɽꝛꞧꞃ",
  s: "ßȿꞩꞅʂ",
  t: "ŧƭʈⱦꞇ",
  th: "þ",
  tz: "ꜩ",
  u: "ʉ",
  v: "ʋꝟʌ",
  vy: "ꝡ",
  w: "ⱳ",
  y: "ƴɏỿ",
  z: "ƶȥɀⱬꝣ",
  hv: "ƕ"
};
for (let r in ue) {
  let e = ue[r] || "";
  for (let t = 0; t < e.length; t++) {
    let i = e.substring(t, t + 1);
    ce[i] = r;
  }
}
const Xe = new RegExp(Object.keys(ce).join("|") + "|" + Qe, "gu"), Ze = (r) => {
  Q === void 0 && (Q = it(We));
}, fe = (r, e = "NFKD") => r.normalize(e), J = (r) => Array.from(r).reduce(
  /**
   * @param {string} result
   * @param {string} char
   */
  (e, t) => e + et(t),
  ""
), et = (r) => (r = fe(r).toLowerCase().replace(Xe, (e) => ce[e] || ""), fe(r, "NFC"));
function* tt(r) {
  for (const [e, t] of r)
    for (let i = e; i <= t; i++) {
      let o = String.fromCharCode(i), s = J(o);
      s != o.toLowerCase() && (s.length > Je || s.length != 0 && (yield { folded: s, composed: o, code_point: i }));
    }
}
const rt = (r) => {
  const e = {}, t = (i, o) => {
    const s = e[i] || /* @__PURE__ */ new Set(), n = new RegExp("^" + $e(s) + "$", "iu");
    o.match(n) || (s.add(K(o)), e[i] = s);
  };
  for (let i of tt(r))
    t(i.folded, i.folded), t(i.folded, i.composed);
  return e;
}, it = (r) => {
  const e = rt(r), t = {};
  let i = [];
  for (let s in e) {
    let n = e[s];
    n && (t[s] = $e(n)), s.length > 1 && i.push(K(s));
  }
  i.sort((s, n) => n.length - s.length);
  const o = ee(i);
  return De = new RegExp("^" + o, "u"), t;
}, ot = (r, e = 1) => {
  let t = 0;
  return r = r.map((i) => (Q[i] && (t += i.length), Q[i] || i)), t >= e ? Fe(r) : "";
}, nt = (r, e = 1) => (e = Math.max(e, r.length - 1), ee(Te(r).map((t) => ot(t, e)))), pe = (r, e = !0) => {
  let t = r.length > 1 ? 1 : 0;
  return ee(r.map((i) => {
    let o = [];
    const s = e ? i.length() : i.length() - 1;
    for (let n = 0; n < s; n++)
      o.push(nt(i.substrs[n] || "", t));
    return Fe(o);
  }));
}, st = (r, e) => {
  for (const t of e) {
    if (t.start != r.start || t.end != r.end || t.substrs.join("") !== r.substrs.join(""))
      continue;
    let i = r.parts;
    const o = (n) => {
      for (const l of i) {
        if (l.start === n.start && l.substr === n.substr)
          return !1;
        if (!(n.length == 1 || l.length == 1) && (n.start < l.start && n.end > l.start || l.start < n.start && l.end > n.start))
          return !0;
      }
      return !1;
    };
    if (!(t.parts.filter(o).length > 0))
      return !0;
  }
  return !1;
};
class X {
  parts;
  substrs;
  start;
  end;
  constructor() {
    this.parts = [], this.substrs = [], this.start = 0, this.end = 0;
  }
  add(e) {
    e && (this.parts.push(e), this.substrs.push(e.substr), this.start = Math.min(e.start, this.start), this.end = Math.max(e.end, this.end));
  }
  last() {
    return this.parts[this.parts.length - 1];
  }
  length() {
    return this.parts.length;
  }
  clone(e, t) {
    let i = new X(), o = JSON.parse(JSON.stringify(this.parts)), s = o.pop();
    for (const c of o)
      i.add(c);
    let n = t.substr.substring(0, e - s.start), l = n.length;
    return i.add({ start: s.start, end: s.start + l, length: l, substr: n }), i;
  }
}
const lt = (r) => {
  Ze(), r = J(r);
  let e = "", t = [new X()];
  for (let i = 0; i < r.length; i++) {
    let s = r.substring(i).match(De);
    const n = r.substring(i, i + 1), l = s ? s[0] : null;
    let c = [], a = /* @__PURE__ */ new Set();
    for (const u of t) {
      const f = u.last();
      if (!f || f.length == 1 || f.end <= i)
        if (l) {
          const m = l.length;
          u.add({ start: i, end: i + m, length: m, substr: l }), a.add("1");
        } else
          u.add({ start: i, end: i + 1, length: 1, substr: n }), a.add("2");
      else if (l) {
        let m = u.clone(i, f);
        const y = l.length;
        m.add({ start: i, end: i + y, length: y, substr: l }), c.push(m);
      } else
        a.add("3");
    }
    if (c.length > 0) {
      c = c.sort((u, f) => u.length() - f.length());
      for (let u of c)
        st(u, t) || t.push(u);
      continue;
    }
    if (i > 0 && a.size == 1 && !a.has("3")) {
      e += pe(t, !1);
      let u = new X();
      const f = t[0];
      f && u.add(f.last()), t = [u];
    }
  }
  return e += pe(t, !0), e;
}, at = (r, e) => {
  if (r)
    return r[e];
}, ct = (r, e) => {
  if (r) {
    for (var t, i = e.split("."); (t = i.shift()) && (r = r[t]); )
      ;
    return r;
  }
}, re = (r, e, t) => {
  var i, o;
  return !r || (r = r + "", e.regex == null) || (o = r.search(e.regex), o === -1) ? 0 : (i = e.string.length / r.length, o === 0 && (i += 0.5), i * t);
}, ie = (r, e) => {
  var t = r[e];
  if (typeof t == "function")
    return t;
  t && !Array.isArray(t) && (r[e] = [t]);
}, Y = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, dt = (r, e) => typeof r == "number" && typeof e == "number" ? r > e ? 1 : r < e ? -1 : 0 : (r = J(r + "").toLowerCase(), e = J(e + "").toLowerCase(), r > e ? 1 : e > r ? -1 : 0);
class ut {
  items;
  // []|{};
  settings;
  /**
   * Textually searches arrays and hashes of objects
   * by property (or multiple properties). Designed
   * specifically for autocomplete.
   *
   */
  constructor(e, t) {
    this.items = e, this.settings = t || { diacritics: !0 };
  }
  /**
   * Splits a search string into an array of individual
   * regexps to be used to match results.
   *
   */
  tokenize(e, t, i) {
    if (!e || !e.length)
      return [];
    const o = [], s = e.split(/\s+/);
    var n;
    return i && (n = new RegExp("^(" + Object.keys(i).map(K).join("|") + "):(.*)$")), s.forEach((l) => {
      let c, a = null, u = null;
      n && (c = l.match(n)) && (a = c[1], l = c[2]), l.length > 0 && (this.settings.diacritics ? u = lt(l) || null : u = K(l), u && t && (u = "\\b" + u)), o.push({
        string: l,
        regex: u ? new RegExp(u, "iu") : null,
        field: a
      });
    }), o;
  }
  /**
   * Returns a function to be used to score individual results.
   *
   * Good matches will have a higher score than poor matches.
   * If an item is not a match, 0 will be returned by the function.
   *
   * @returns {T.ScoreFn}
   */
  getScoreFunction(e, t) {
    var i = this.prepareSearch(e, t);
    return this._getScoreFunction(i);
  }
  /**
   * @returns {T.ScoreFn}
   *
   */
  _getScoreFunction(e) {
    const t = e.tokens, i = t.length;
    if (!i)
      return function() {
        return 0;
      };
    const o = e.options.fields, s = e.weights, n = o.length, l = e.getAttrFn;
    if (!n)
      return function() {
        return 1;
      };
    const c = /* @__PURE__ */ (function() {
      return n === 1 ? function(a, u) {
        const f = o[0].field;
        return re(l(u, f), a, s[f] || 1);
      } : function(a, u) {
        var f = 0;
        if (a.field) {
          const m = l(u, a.field);
          !a.regex && m ? f += 1 / n : f += re(m, a, 1);
        } else
          Y(s, (m, y) => {
            f += re(l(u, y), a, m);
          });
        return f / n;
      };
    })();
    return i === 1 ? function(a) {
      return c(t[0], a);
    } : e.options.conjunction === "and" ? function(a) {
      var u, f = 0;
      for (let m of t) {
        if (u = c(m, a), u <= 0)
          return 0;
        f += u;
      }
      return f / i;
    } : function(a) {
      var u = 0;
      return Y(t, (f) => {
        u += c(f, a);
      }), u / i;
    };
  }
  /**
   * Returns a function that can be used to compare two
   * results, for sorting purposes. If no sorting should
   * be performed, `null` will be returned.
   *
   * @return function(a,b)
   */
  getSortFunction(e, t) {
    var i = this.prepareSearch(e, t);
    return this._getSortFunction(i);
  }
  _getSortFunction(e) {
    var t, i = [];
    const o = this, s = e.options, n = !e.query && s.sort_empty ? s.sort_empty : s.sort;
    if (typeof n == "function")
      return n.bind(this);
    const l = function(a, u) {
      return a === "$score" ? u.score : e.getAttrFn(o.items[u.id], a);
    };
    if (n)
      for (let a of n)
        (e.query || a.field !== "$score") && i.push(a);
    if (e.query) {
      t = !0;
      for (let a of i)
        if (a.field === "$score") {
          t = !1;
          break;
        }
      t && i.unshift({ field: "$score", direction: "desc" });
    } else
      i = i.filter((a) => a.field !== "$score");
    return i.length ? function(a, u) {
      var f, m;
      for (let y of i)
        if (m = y.field, f = (y.direction === "desc" ? -1 : 1) * dt(l(m, a), l(m, u)), f)
          return f;
      return 0;
    } : null;
  }
  /**
   * Parses a search query and returns an object
   * with tokens and fields ready to be populated
   * with results.
   *
   */
  prepareSearch(e, t) {
    const i = {};
    var o = Object.assign({}, t);
    if (ie(o, "sort"), ie(o, "sort_empty"), o.fields) {
      ie(o, "fields");
      const s = [];
      o.fields.forEach((n) => {
        typeof n == "string" && (n = { field: n, weight: 1 }), s.push(n), i[n.field] = "weight" in n ? n.weight : 1;
      }), o.fields = s;
    }
    return {
      options: o,
      query: e.toLowerCase().trim(),
      tokens: this.tokenize(e, o.respect_word_boundaries, i),
      total: 0,
      items: [],
      weights: i,
      getAttrFn: o.nesting ? ct : at
    };
  }
  /**
   * Searches through all items and returns a sorted array of matches.
   *
   */
  search(e, t) {
    var i = this, o, s;
    s = this.prepareSearch(e, t), t = s.options, e = s.query;
    const n = t.score || i._getScoreFunction(s);
    e.length ? Y(i.items, (c, a) => {
      o = n(c), (t.filter === !1 || o > 0) && s.items.push({ score: o, id: a });
    }) : Y(i.items, (c, a) => {
      s.items.push({ score: 1, id: a });
    });
    const l = i._getSortFunction(s);
    return l && s.items.sort(l), s.total = s.items.length, typeof t.limit == "number" && (s.items = s.items.slice(0, t.limit)), s;
  }
}
const T = (r) => typeof r > "u" || r === null ? null : W(r), W = (r) => typeof r == "boolean" ? r ? "1" : "0" : r + "", oe = (r) => (r + "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"), ft = (r, e) => e > 0 ? window.setTimeout(r, e) : (r.call(null), null), pt = (r, e) => {
  var t;
  return function(i, o) {
    var s = this;
    t && (s.loading = Math.max(s.loading - 1, 0), clearTimeout(t)), t = setTimeout(function() {
      t = null, s.loadedSearches[i] = !0, r.call(s, i, o);
    }, e);
  };
}, he = (r, e, t) => {
  var i, o = r.trigger, s = {};
  r.trigger = function() {
    var n = arguments[0];
    if (e.indexOf(n) !== -1)
      s[n] = arguments;
    else
      return o.apply(r, arguments);
  }, t.apply(r, []), r.trigger = o;
  for (i of e)
    i in s && o.apply(r, s[i]);
}, ht = (r) => ({
  start: r.selectionStart || 0,
  length: (r.selectionEnd || 0) - (r.selectionStart || 0)
}), A = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, k = (r, e, t, i) => {
  r.addEventListener(e, t, i);
}, H = (r, e) => {
  if (!e || !e[r])
    return !1;
  var t = (e.altKey ? 1 : 0) + (e.ctrlKey ? 1 : 0) + (e.shiftKey ? 1 : 0) + (e.metaKey ? 1 : 0);
  return t === 1;
}, ne = (r, e) => {
  const t = r.getAttribute("id");
  return t || (r.setAttribute("id", e), e);
}, me = (r) => r.replace(/[\\"']/g, "\\$&"), V = (r, e) => {
  e && r.append(e);
}, I = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, D = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (Pe(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, Pe = (r) => typeof r == "string" && r.indexOf("<") > -1, mt = (r) => r.replace(/['"\\]/g, "\\$&"), se = (r, e) => {
  var t = document.createEvent("HTMLEvents");
  t.initEvent(e, !0, !1), r.dispatchEvent(t);
}, G = (r, e) => {
  Object.assign(r.style, e);
}, $ = (r, ...e) => {
  var t = Me(e);
  r = He(r), r.map((i) => {
    t.map((o) => {
      i.classList.add(o);
    });
  });
}, M = (r, ...e) => {
  var t = Me(e);
  r = He(r), r.map((i) => {
    t.map((o) => {
      i.classList.remove(o);
    });
  });
}, Me = (r) => {
  var e = [];
  return I(r, (t) => {
    typeof t == "string" && (t = t.trim().split(/[\t\n\f\r\s]/)), Array.isArray(t) && (e = e.concat(t));
  }), e.filter(Boolean);
}, He = (r) => (Array.isArray(r) || (r = [r]), r), le = (r, e, t) => {
  if (!(t && !t.contains(r)))
    for (; r && r.matches; ) {
      if (r.matches(e))
        return r;
      r = r.parentNode;
    }
}, ge = (r, e = 0) => e > 0 ? r[r.length - 1] : r[0], gt = (r) => Object.keys(r).length === 0, ve = (r, e) => {
  if (!r)
    return -1;
  e = e || r.nodeName;
  for (var t = 0; r = r.previousElementSibling; )
    r.matches(e) && t++;
  return t;
}, x = (r, e) => {
  I(e, (t, i) => {
    t == null ? r.removeAttribute(i) : r.setAttribute(i, "" + t);
  });
}, ae = (r, e) => {
  r.parentNode && r.parentNode.replaceChild(e, r);
}, vt = (r, e) => {
  if (e === null)
    return;
  if (typeof e == "string") {
    if (!e.length)
      return;
    e = new RegExp(e, "i");
  }
  const t = (s) => {
    var n = s.data.match(e);
    if (n && s.data.length > 0) {
      var l = document.createElement("span");
      l.className = "highlight";
      var c = s.splitText(n.index);
      c.splitText(n[0].length);
      var a = c.cloneNode(!0);
      return l.appendChild(a), ae(c, l), 1;
    }
    return 0;
  }, i = (s) => {
    s.nodeType === 1 && s.childNodes && !/(script|style)/i.test(s.tagName) && (s.className !== "highlight" || s.tagName !== "SPAN") && Array.from(s.childNodes).forEach((n) => {
      o(n);
    });
  }, o = (s) => s.nodeType === 3 ? t(s) : (i(s), 0);
  o(r);
}, bt = (r) => {
  var e = r.querySelectorAll("span.highlight");
  Array.prototype.forEach.call(e, function(t) {
    var i = t.parentNode;
    i.replaceChild(t.firstChild, t), i.normalize();
  });
}, _t = 65, wt = 13, yt = 27, xt = 37, Ot = 38, St = 39, Ct = 40, be = 8, At = 46, _e = 9, Et = typeof navigator > "u" ? !1 : /Mac/.test(navigator.userAgent), U = Et ? "metaKey" : "ctrlKey", we = {
  options: [],
  optgroups: [],
  plugins: [],
  delimiter: ",",
  splitOn: null,
  // regexp or string for splitting up values from a paste command
  persist: !0,
  diacritics: !0,
  create: null,
  createOnBlur: !1,
  createFilter: null,
  clearAfterSelect: !1,
  highlight: !0,
  openOnFocus: !0,
  shouldOpen: null,
  maxOptions: 50,
  maxItems: null,
  hideSelected: null,
  duplicates: !1,
  addPrecedence: !1,
  selectOnTab: !1,
  preload: null,
  allowEmptyOption: !1,
  //closeAfterSelect: false,
  refreshThrottle: 300,
  loadThrottle: 300,
  loadingClass: "loading",
  dataAttr: null,
  //'data-data',
  optgroupField: "optgroup",
  valueField: "value",
  labelField: "text",
  disabledField: "disabled",
  optgroupLabelField: "label",
  optgroupValueField: "value",
  lockOptgroupOrder: !1,
  sortField: "$order",
  searchField: ["text"],
  searchConjunction: "and",
  mode: null,
  wrapperClass: "ts-wrapper",
  controlClass: "ts-control",
  dropdownClass: "ts-dropdown",
  dropdownContentClass: "ts-dropdown-content",
  itemClass: "item",
  optionClass: "option",
  dropdownParent: null,
  controlInput: '<input type="text" autocomplete="off" size="1" />',
  copyClassesToDropdown: !1,
  placeholder: null,
  hidePlaceholder: null,
  shouldLoad: function(r) {
    return r.length > 0;
  },
  /*
  load                 : null, // function(query, callback) { ... }
  score                : null, // function(search) { ... }
  onInitialize         : null, // function() { ... }
  onChange             : null, // function(value) { ... }
  onItemAdd            : null, // function(value, $item) { ... }
  onItemRemove         : null, // function(value) { ... }
  onClear              : null, // function() { ... }
  onOptionAdd          : null, // function(value, data) { ... }
  onOptionRemove       : null, // function(value) { ... }
  onOptionClear        : null, // function() { ... }
  onOptionGroupAdd     : null, // function(id, data) { ... }
  onOptionGroupRemove  : null, // function(id) { ... }
  onOptionGroupClear   : null, // function() { ... }
  onDropdownOpen       : null, // function(dropdown) { ... }
  onDropdownClose      : null, // function(dropdown) { ... }
  onType               : null, // function(str) { ... }
  onDelete             : null, // function(values) { ... }
  */
  render: {
    /*
    item: null,
    optgroup: null,
    optgroup_header: null,
    option: null,
    option_create: null
    */
  }
};
function ye(r, e) {
  var t = Object.assign({}, we, e), i = t.dataAttr, o = t.labelField, s = t.valueField, n = t.disabledField, l = t.optgroupField, c = t.optgroupLabelField, a = t.optgroupValueField, u = r.tagName.toLowerCase(), f = r.getAttribute("placeholder") || r.getAttribute("data-placeholder");
  if (!f && !t.allowEmptyOption) {
    let p = r.querySelector('option[value=""]');
    p && (f = p.textContent);
  }
  var m = {
    placeholder: f,
    options: [],
    optgroups: [],
    items: [],
    maxItems: null
  }, y = () => {
    var p, v = m.options, _ = {}, S = 1;
    let h = 0;
    var w = (C) => {
      var b = Object.assign({}, C.dataset), g = i && b[i];
      return typeof g == "string" && g.length && (b = Object.assign(b, JSON.parse(g))), b;
    }, P = (C, b) => {
      var g = T(C.value);
      if (g != null && !(!g && !t.allowEmptyOption)) {
        if (_.hasOwnProperty(g)) {
          if (b) {
            var E = _[g][l];
            E ? Array.isArray(E) ? E.push(b) : _[g][l] = [E, b] : _[g][l] = b;
          }
        } else {
          var O = w(C);
          O[o] = O[o] || C.textContent, O[s] = O[s] || g, O[n] = O[n] || C.disabled, O[l] = O[l] || b, O.$option = C, O.$order = O.$order || ++h, _[g] = O, v.push(O);
        }
        C.selected && m.items.push(g);
      }
    }, B = (C) => {
      var b, g;
      g = w(C), g[c] = g[c] || C.getAttribute("label") || "", g[a] = g[a] || S++, g[n] = g[n] || C.disabled, g.$order = g.$order || ++h, m.optgroups.push(g), b = g[a], I(C.children, (E) => {
        P(E, b);
      });
    };
    m.maxItems = r.hasAttribute("multiple") ? null : 1, I(r.children, (C) => {
      p = C.tagName.toLowerCase(), p === "optgroup" ? B(C) : p === "option" && P(C);
    });
  }, d = () => {
    var p, v;
    const _ = r.getAttribute(i);
    if (_)
      m.options = JSON.parse(_), I(m.options, (h) => {
        m.items.push(h[s]);
      });
    else {
      var S = (v = (p = r?.value) === null || p === void 0 ? void 0 : p.trim()) !== null && v !== void 0 ? v : "";
      if (!t.allowEmptyOption && !S.length)
        return;
      const h = S.split(t.delimiter);
      I(h, (w) => {
        const P = {};
        P[o] = w, P[s] = w, m.options.push(P);
      }), m.items = h;
    }
  };
  return u === "select" ? y() : d(), Object.assign({}, we, m, e);
}
var xe = 0;
class F extends Ye(je) {
  constructor(e, t) {
    super(), this.order = 0, this.isOpen = !1, this.isDisabled = !1, this.isReadOnly = !1, this.isInvalid = !1, this.isValid = !0, this.isLocked = !1, this.isFocused = !1, this.isInputHidden = !1, this.isSetup = !1, this.isDropdownContentStale = !0, this.ignoreFocus = !1, this.ignoreHover = !1, this.hasOptions = !1, this.lastValue = "", this.caretPos = 0, this.loading = 0, this.loadedSearches = {}, this.activeOption = null, this.activeItems = [], this.optgroups = {}, this.options = {}, this.userOptions = {}, this.items = [], this.refreshTimeout = null, xe++;
    var i, o = D(e);
    if (o.tomselect)
      throw new Error("Tom Select already initialized on this element");
    o.tomselect = this;
    var s = window.getComputedStyle && window.getComputedStyle(o, null);
    i = s.getPropertyValue("direction");
    const n = ye(o, t);
    this.settings = n, this.input = o, this.tabIndex = o.tabIndex || 0, this.is_select_tag = o.tagName.toLowerCase() === "select", this.rtl = /rtl/i.test(i), this.inputId = ne(o, "tomselect-" + xe), this.isRequired = o.required, this.sifter = new ut(this.options, { diacritics: n.diacritics }), n.mode = n.mode || (n.maxItems === 1 ? "single" : "multi"), typeof n.hideSelected != "boolean" && (n.hideSelected = n.mode === "multi"), typeof n.hidePlaceholder != "boolean" && (n.hidePlaceholder = n.mode !== "multi");
    var l = n.createFilter;
    typeof l != "function" && (typeof l == "string" && (l = new RegExp(l)), l instanceof RegExp ? n.createFilter = (v) => l.test(v) : n.createFilter = (v) => this.settings.duplicates || !this.options[v]), this.initializePlugins(n.plugins), this.setupCallbacks(), this.setupTemplates();
    const c = D("<div>"), a = D("<div>"), u = this._render("dropdown"), f = D('<div role="listbox" tabindex="-1">'), m = this.input.getAttribute("class") || "", y = n.mode;
    var d;
    if ($(c, n.wrapperClass, m, y), $(a, n.controlClass), V(c, a), $(u, n.dropdownClass, y), n.copyClassesToDropdown && $(u, m), $(f, n.dropdownContentClass), V(u, f), D(n.dropdownParent || c).appendChild(u), Pe(n.controlInput)) {
      d = D(n.controlInput);
      var p = ["autocorrect", "autocapitalize", "autocomplete", "spellcheck", "aria-label"];
      I(p, (v) => {
        o.getAttribute(v) && x(d, { [v]: o.getAttribute(v) });
      }), d.tabIndex = -1, a.appendChild(d), this.focus_node = d;
    } else n.controlInput ? (d = D(n.controlInput), this.focus_node = d) : (d = D("<input/>"), this.focus_node = a);
    this.wrapper = c, this.dropdown = u, this.dropdown_content = f, this.control = a, this.control_input = d, this.setup();
  }
  /**
   * set up event bindings.
   *
   */
  setup() {
    const e = this, t = e.settings, i = e.control_input, o = e.dropdown, s = e.dropdown_content, n = e.wrapper, l = e.control, c = e.input, a = e.focus_node, u = { passive: !0 }, f = e.inputId + "-ts-dropdown";
    x(s, {
      id: f
    }), x(a, {
      role: "combobox",
      "aria-haspopup": "listbox",
      "aria-expanded": "false",
      "aria-controls": f
    });
    const m = ne(a, e.inputId + "-ts-control"), y = "label[for='" + mt(e.inputId) + "']", d = document.querySelector(y), p = e.focus.bind(e);
    if (d) {
      k(d, "click", p), x(d, { for: m });
      const h = ne(d, e.inputId + "-ts-label");
      x(a, { "aria-labelledby": h }), x(s, { "aria-labelledby": h });
    }
    if (n.style.width = c.style.width, n.style.minWidth = c.style.minWidth, n.style.maxWidth = c.style.maxWidth, e.plugins.names.length) {
      const h = "plugin-" + e.plugins.names.join(" plugin-");
      $([n, o], h);
    }
    (t.maxItems === null || t.maxItems > 1) && e.is_select_tag && x(c, { multiple: "multiple" }), t.placeholder && x(i, { placeholder: t.placeholder }), !t.splitOn && t.delimiter && (t.splitOn = new RegExp("\\s*" + K(t.delimiter) + "+\\s*")), t.load && t.loadThrottle && (t.load = pt(t.load, t.loadThrottle)), k(o, "mousemove", () => {
      e.ignoreHover = !1;
    }), k(o, "mouseenter", (h) => {
      var w = le(h.target, "[data-selectable]", o);
      w && e.onOptionHover(h, w);
    }, { capture: !0 }), k(o, "click", (h) => {
      const w = le(h.target, "[data-selectable]");
      w && (e.onOptionSelect(h, w), A(h, !0));
    }), k(l, "click", (h) => {
      var w = le(h.target, "[data-ts-item]", l);
      if (w && e.onItemSelect(h, w)) {
        A(h, !0);
        return;
      }
      i.value == "" && (e.onClick(), A(h, !0));
    }), k(a, "keydown", (h) => e.onKeyDown(h)), k(i, "keypress", (h) => e.onKeyPress(h)), k(i, "input", (h) => e.onInput(h)), k(a, "blur", (h) => e.onBlur(h)), k(a, "focus", (h) => e.onFocus(h)), k(i, "paste", (h) => e.onPaste(h));
    const v = (h) => {
      const w = h.composedPath()[0];
      if (!n.contains(w) && !o.contains(w)) {
        e.isFocused && e.blur(), e.inputState();
        return;
      }
      w == i && e.isOpen ? h.stopPropagation() : A(h, !0);
    }, _ = () => {
      e.isOpen && e.positionDropdown();
    }, S = () => {
      e.isValid && (e.isValid = !1, e.isInvalid = !0, e.refreshState());
    };
    k(c, "invalid", S), k(document, "mousedown", v), k(window, "scroll", _, u), k(window, "resize", _, u), this._destroy = () => {
      c.removeEventListener("invalid", S), document.removeEventListener("mousedown", v), window.removeEventListener("scroll", _), window.removeEventListener("resize", _), d && d.removeEventListener("click", p);
    }, this.revertSettings = {
      innerHTML: c.innerHTML,
      tabIndex: c.tabIndex
    }, c.tabIndex = -1, c.insertAdjacentElement("afterend", e.wrapper), e.sync(!1), t.items = [], delete t.optgroups, delete t.options, e.refreshItems(), e.close(!1), e.inputState(), e.isSetup = !0, e.on("change", this.onChange), $(c, "tomselected", "ts-hidden-accessible"), e.trigger("initialize"), t.preload === !0 && e.preload();
  }
  /**
   * Register options and optgroups
   *
   */
  setupOptions(e = [], t = []) {
    this.addOptions(e), I(t, (i) => {
      this.registerOptionGroup(i);
    });
  }
  /**
   * Sets up default rendering functions.
   */
  setupTemplates() {
    var e = this, t = e.settings.labelField, i = e.settings.optgroupLabelField, o = {
      optgroup: (s) => {
        let n = document.createElement("div");
        return n.className = "optgroup", n.appendChild(s.options), n;
      },
      optgroup_header: (s, n) => '<div class="optgroup-header">' + n(s[i]) + "</div>",
      option: (s, n) => "<div>" + n(s[t]) + "</div>",
      item: (s, n) => "<div>" + n(s[t]) + "</div>",
      option_create: (s, n) => '<div class="create">Add <strong>' + n(s.input) + "</strong>&hellip;</div>",
      no_results: () => '<div class="no-results">No results found</div>',
      loading: () => '<div class="spinner"></div>',
      not_loading: () => {
      },
      dropdown: () => "<div></div>"
    };
    e.settings.render = Object.assign({}, o, e.settings.render);
  }
  /**
   * Maps fired events to callbacks provided
   * in the settings used when creating the control.
   */
  setupCallbacks() {
    var e, t, i = {
      initialize: "onInitialize",
      change: "onChange",
      item_add: "onItemAdd",
      item_remove: "onItemRemove",
      item_select: "onItemSelect",
      clear: "onClear",
      option_add: "onOptionAdd",
      option_remove: "onOptionRemove",
      option_clear: "onOptionClear",
      optgroup_add: "onOptionGroupAdd",
      optgroup_remove: "onOptionGroupRemove",
      optgroup_clear: "onOptionGroupClear",
      dropdown_open: "onDropdownOpen",
      dropdown_close: "onDropdownClose",
      type: "onType",
      load: "onLoad",
      focus: "onFocus",
      blur: "onBlur"
    };
    for (e in i)
      t = this.settings[i[e]], t && this.on(e, t);
  }
  /**
   * Sync the Tom Select instance with the original input or select
   *
   */
  sync(e = !0) {
    const t = this, i = e ? ye(t.input, { delimiter: t.settings.delimiter, allowEmptyOption: t.settings.allowEmptyOption }) : t.settings;
    t.setupOptions(i.options, i.optgroups), t.setValue(i.items || [], !0), t.input.disabled ? t.disable() : t.input.readOnly ? t.setReadOnly(!0) : t.enable(), t.lastQuery = null;
  }
  /**
   * Triggered when the main control element
   * has a click event.
   *
   */
  onClick() {
    var e = this;
    if (e.activeItems.length > 0) {
      e.clearActiveItems(), e.focus();
      return;
    }
    e.isFocused && e.isOpen ? e.blur() : e.focus();
  }
  /**
   * @deprecated v1.7
   *
   */
  onMouseDown() {
  }
  /**
   * Triggered when the value of the control has been changed.
   * This should propagate the event to the original DOM
   * input / select element.
   */
  onChange() {
    se(this.input, "input"), se(this.input, "change");
  }
  /**
   * Triggered on <input> paste.
   *
   */
  onPaste(e) {
    var t = this;
    if (t.isInputHidden || t.isLocked) {
      A(e);
      return;
    }
    t.settings.splitOn && setTimeout(() => {
      var i = t.inputValue();
      if (i.match(t.settings.splitOn)) {
        var o = i.trim().split(t.settings.splitOn);
        I(o, (s) => {
          T(s) && (this.options[s] ? t.addItem(s) : t.createItem(s));
        });
      }
    }, 0);
  }
  /**
   * Triggered on <input> keypress.
   *
   */
  onKeyPress(e) {
    var t = this;
    if (t.isLocked) {
      A(e);
      return;
    }
    var i = String.fromCharCode(e.keyCode || e.which);
    if (t.settings.create && t.settings.mode === "multi" && i === t.settings.delimiter) {
      t.createItem(), A(e);
      return;
    }
  }
  /**
   * Triggered on <input> keydown.
   *
   */
  onKeyDown(e) {
    var t = this;
    if (t.ignoreHover = !0, t.isLocked) {
      e.keyCode !== _e && A(e);
      return;
    }
    switch (e.keyCode) {
      // ctrl+A: select all
      case _t:
        if (H(U, e) && t.control_input.value == "") {
          A(e), t.selectAll();
          return;
        }
        break;
      // esc: close dropdown
      case yt:
        t.isOpen && (A(e, !0), t.close()), t.clearActiveItems();
        return;
      // down: open dropdown or move selection down
      case Ct:
        if (!t.isOpen && t.hasOptions)
          t.open();
        else if (t.activeOption) {
          let i = t.getAdjacent(t.activeOption, 1);
          i && t.setActiveOption(i);
        }
        A(e);
        return;
      // up: move selection up
      case Ot:
        if (t.activeOption) {
          let i = t.getAdjacent(t.activeOption, -1);
          i && t.setActiveOption(i);
        }
        A(e);
        return;
      // return: select active option
      case wt:
        t.canSelect(t.activeOption) ? (t.onOptionSelect(e, t.activeOption), A(e)) : (t.settings.create && t.createItem() || document.activeElement == t.control_input && t.isOpen) && A(e);
        return;
      // left: modifiy item selection to the left
      case xt:
        t.advanceSelection(-1, e);
        return;
      // right: modifiy item selection to the right
      case St:
        t.advanceSelection(1, e);
        return;
      // tab: select active option and/or create item
      case _e:
        t.settings.selectOnTab && (t.canSelect(t.activeOption) ? (t.onOptionSelect(e, t.activeOption), A(e)) : t.settings.create && t.createItem() && A(e));
        return;
      // delete|backspace: delete items
      case be:
      case At:
        t.deleteSelection(e);
        return;
    }
    t.isInputHidden && !H(U, e) && A(e);
  }
  /**
   * Triggered on <input> keyup.
   *
   */
  onInput(e) {
    if (this.isLocked)
      return;
    const t = this.inputValue();
    if (this.lastValue !== t) {
      if (this.lastValue = t, t == "") {
        this._onInput();
        return;
      }
      this.refreshTimeout && window.clearTimeout(this.refreshTimeout), this.refreshTimeout = ft(() => {
        this.refreshTimeout = null, this._onInput();
      }, this.settings.refreshThrottle);
    }
  }
  _onInput() {
    const e = this.lastValue;
    this.settings.shouldLoad.call(this, e) && this.load(e), this.refreshOptions(), this.trigger("type", e);
  }
  /**
   * Triggered when the user rolls over
   * an option in the autocomplete dropdown menu.
   *
   */
  onOptionHover(e, t) {
    this.ignoreHover || this.setActiveOption(t, !1);
  }
  /**
   * Triggered on <input> focus.
   *
   */
  onFocus(e) {
    var t = this, i = t.isFocused;
    if (t.isDisabled || t.isReadOnly) {
      t.blur(), A(e);
      return;
    }
    t.ignoreFocus || (t.isFocused = !0, t.settings.preload === "focus" && t.preload(), i || t.trigger("focus"), t.activeItems.length || (t.inputState(), t.refreshOptions(!!t.settings.openOnFocus)), t.refreshState());
  }
  /**
   * Triggered on <input> blur.
   *
   */
  onBlur(e) {
    if (document.hasFocus() !== !1) {
      var t = this;
      if (t.isFocused) {
        t.isFocused = !1, t.ignoreFocus = !1;
        var i = () => {
          t.close(), t.setActiveItem(), t.setCaret(t.items.length), t.trigger("blur");
        };
        t.settings.create && t.settings.createOnBlur ? t.createItem(null, i) : i();
      }
    }
  }
  /**
   * Triggered when the user clicks on an option
   * in the autocomplete dropdown menu.
   *
   */
  onOptionSelect(e, t) {
    var i, o = this;
    t.parentElement && t.parentElement.matches("[data-disabled]") || (t.classList.contains("create") ? o.createItem(null, () => {
      o.settings.closeAfterSelect ? o.close() : o.settings.clearAfterSelect && o.setTextboxValue();
    }) : (i = t.dataset.value, typeof i < "u" && (o.isDropdownContentStale = o.settings.hideSelected, o.addItem(i), o.settings.closeAfterSelect ? o.close() : o.settings.clearAfterSelect && o.setTextboxValue(), !o.settings.hideSelected && e.type && /click/.test(e.type) && o.setActiveOption(t))));
  }
  /**
   * Return true if the given option can be selected
   *
   */
  canSelect(e) {
    return !!(this.isOpen && e && this.dropdown_content.contains(e));
  }
  /**
   * Triggered when the user clicks on an item
   * that has been selected.
   *
   */
  onItemSelect(e, t) {
    var i = this;
    return !i.isLocked && i.settings.mode === "multi" ? (A(e), i.setActiveItem(t, e), !0) : !1;
  }
  /**
   * Determines whether or not to invoke
   * the user-provided option provider / loader
   *
   * Note, there is a subtle difference between
   * this.canLoad() and this.settings.shouldLoad();
   *
   *	- settings.shouldLoad() is a user-input validator.
   *	When false is returned, the not_loading template
   *	will be added to the dropdown
   *
   *	- canLoad() is lower level validator that checks
   * 	the Tom Select instance. There is no inherent user
   *	feedback when canLoad returns false
   *
   */
  canLoad(e) {
    return !(!this.settings.load || this.loadedSearches.hasOwnProperty(e));
  }
  /**
   * Invokes the user-provided option provider / loader.
   *
   */
  load(e) {
    const t = this;
    if (!t.canLoad(e))
      return;
    $(t.wrapper, t.settings.loadingClass), t.loading++;
    const i = t.loadCallback.bind(t);
    t.settings.load.call(t, e, i);
  }
  /**
   * Invoked by the user-provided option provider
   *
   */
  loadCallback(e, t) {
    const i = this;
    i.loading = Math.max(i.loading - 1, 0), i.isDropdownContentStale = !0, i.clearActiveOption(), i.setupOptions(e, t), i.refreshOptions(i.isFocused && !i.isInputHidden), i.loading || M(i.wrapper, i.settings.loadingClass), i.trigger("load", e, t);
  }
  preload() {
    var e = this.wrapper.classList;
    e.contains("preloaded") || (e.add("preloaded"), this.load(""));
  }
  /**
   * Sets the input field of the control to the specified value.
   *
   */
  setTextboxValue(e = "") {
    var t = this.control_input, i = t.value !== e;
    i && (t.value = e, se(t, "update"), this.lastValue = e);
  }
  /**
   * Returns the value of the control. If multiple items
   * can be selected (e.g. <select multiple>), this returns
   * an array. If only one item can be selected, this
   * returns a string.
   *
   */
  getValue() {
    return this.is_select_tag && this.input.hasAttribute("multiple") ? this.items : this.items.join(this.settings.delimiter);
  }
  /**
   * Resets the selected items to the given value.
   *
   */
  setValue(e, t) {
    var i = t ? [] : ["change"];
    he(this, i, () => {
      this.clear(t), this.addItems(e, t);
    });
  }
  /**
   * Resets the number of max items to the given value
   *
   */
  setMaxItems(e) {
    e === 0 && (e = null), this.settings.maxItems = e, this.refreshState();
  }
  /**
   * Sets the selected item.
   *
   */
  setActiveItem(e, t) {
    var i = this, o, s, n, l, c, a;
    if (i.settings.mode !== "single") {
      if (!e) {
        i.clearActiveItems(), i.isFocused && i.inputState();
        return;
      }
      if (o = t && t.type.toLowerCase(), o === "click" && H("shiftKey", t) && i.activeItems.length) {
        for (a = i.getLastActive(), n = Array.prototype.indexOf.call(i.control.children, a), l = Array.prototype.indexOf.call(i.control.children, e), n > l && (c = n, n = l, l = c), s = n; s <= l; s++)
          e = i.control.children[s], i.activeItems.indexOf(e) === -1 && i.setActiveItemClass(e);
        A(t);
      } else o === "click" && H(U, t) || o === "keydown" && H("shiftKey", t) ? e.classList.contains("active") ? i.removeActiveItem(e) : i.setActiveItemClass(e) : (i.clearActiveItems(), i.setActiveItemClass(e));
      i.inputState(), i.isFocused || i.focus();
    }
  }
  /**
   * Set the active and last-active classes
   *
   */
  setActiveItemClass(e) {
    const t = this, i = t.control.querySelector(".last-active");
    i && M(i, "last-active"), $(e, "active last-active"), t.trigger("item_select", e), t.activeItems.indexOf(e) == -1 && t.activeItems.push(e);
  }
  /**
   * Remove active item
   *
   */
  removeActiveItem(e) {
    var t = this.activeItems.indexOf(e);
    this.activeItems.splice(t, 1), M(e, "active");
  }
  /**
   * Clears all the active items
   *
   */
  clearActiveItems() {
    M(this.activeItems, "active"), this.activeItems = [];
  }
  /**
   * Sets the selected item in the dropdown menu
   * of available options.
   *
   */
  setActiveOption(e, t = !0) {
    e !== this.activeOption && (this.clearActiveOption(), e && (this.activeOption = e, x(this.focus_node, { "aria-activedescendant": e.getAttribute("id") }), x(e, { "aria-selected": "true" }), $(e, "active"), t && this.scrollToOption(e)));
  }
  /**
   * Sets the dropdown_content scrollTop to display the option
   *
   */
  scrollToOption(e, t) {
    if (!e)
      return;
    const i = this.dropdown_content, o = i.clientHeight, s = i.scrollTop || 0, n = e.offsetHeight, l = e.getBoundingClientRect().top - i.getBoundingClientRect().top + s;
    l + n > o + s ? this.scroll(l - o + n, t) : l < s && this.scroll(l, t);
  }
  /**
   * Scroll the dropdown to the given position
   *
   */
  scroll(e, t) {
    const i = this.dropdown_content;
    t && (i.style.scrollBehavior = t), i.scrollTop = e, i.style.scrollBehavior = "";
  }
  /**
   * Clears the active option
   *
   */
  clearActiveOption() {
    this.activeOption && (M(this.activeOption, "active"), x(this.activeOption, { "aria-selected": null })), this.activeOption = null, x(this.focus_node, { "aria-activedescendant": null });
  }
  /**
   * Selects all items (CTRL + A).
   */
  selectAll() {
    const e = this;
    if (e.settings.mode === "single")
      return;
    const t = e.controlChildren();
    t.length && (e.inputState(), e.close(), e.activeItems = t, I(t, (i) => {
      e.setActiveItemClass(i);
    }));
  }
  /**
   * Determines if the control_input should be in a hidden or visible state
   *
   */
  inputState() {
    var e = this;
    e.control.contains(e.control_input) && (x(e.control_input, { placeholder: e.settings.placeholder }), e.activeItems.length > 0 || !e.isFocused && e.settings.hidePlaceholder && e.items.length > 0 ? (e.setTextboxValue(), e.isInputHidden = !0) : (e.settings.hidePlaceholder && e.items.length > 0 && x(e.control_input, { placeholder: "" }), e.isInputHidden = !1), e.wrapper.classList.toggle("input-hidden", e.isInputHidden));
  }
  /**
   * Get the input value
   */
  inputValue() {
    return this.control_input.value.trim();
  }
  /**
   * Gives the control focus.
   */
  focus() {
    var e = this;
    if (e.isDisabled || e.isReadOnly)
      return;
    e.ignoreFocus = !0;
    const t = this.control_input.offsetWidth ? this.control_input : this.focus_node;
    t.focus(), setTimeout(() => {
      e.ignoreFocus = !1, t.getRootNode().activeElement === t && this.onFocus();
    }, 0);
  }
  /**
   * Forces the control out of focus.
   *
   */
  blur() {
    this.focus_node.blur(), this.onBlur();
  }
  /**
   * Returns a function that scores an object
   * to show how good of a match it is to the
   * provided query.
   *
   * @return {function}
   */
  getScoreFunction(e) {
    return this.sifter.getScoreFunction(e, this.getSearchOptions());
  }
  /**
   * Returns search options for sifter (the system
   * for scoring and sorting results).
   *
   * @see https://github.com/orchidjs/sifter.js
   * @return {object}
   */
  getSearchOptions() {
    var e = this.settings, t = e.sortField;
    return typeof e.sortField == "string" && (t = [{ field: e.sortField }]), {
      fields: e.searchField,
      conjunction: e.searchConjunction,
      sort: t,
      nesting: e.nesting
    };
  }
  /**
   * Searches through available options and returns
   * a sorted array of matches.
   *
   */
  search(e) {
    var t, i, o = this, s = this.getSearchOptions();
    if (o.settings.score && (i = o.settings.score.call(o, e), typeof i != "function"))
      throw new Error('Tom Select "score" setting must be a function that returns a function');
    return o.isDropdownContentStale || e !== o.lastQuery ? (o.lastQuery = e, /(.)\1{15,}/.test(e) && (e = ""), t = o.sifter.search(e, Object.assign(s, { score: i })), o.currentResults = t) : t = Object.assign({}, o.currentResults), o.settings.hideSelected && (t.items = t.items.filter((n) => {
      let l = T(n.id);
      return !(l !== null && o.items.indexOf(l) !== -1);
    })), t;
  }
  /**
   * Refreshes the list of available options shown
   * in the autocomplete dropdown menu.
   *
   */
  refreshOptions(e = !0) {
    var t, i, o, s, n, l, c, a, u, f;
    const m = {}, y = [];
    var d = this, p = d.inputValue();
    const v = p === d.lastQuery || p == "" && d.lastQuery == null;
    var _ = d.search(p), S = null, h = d.settings.shouldOpen || !1, w = d.dropdown_content;
    v && (S = d.activeOption, S && (u = S.closest("[data-group]"))), s = _.items.length, typeof d.settings.maxOptions == "number" && (s = Math.min(s, d.settings.maxOptions)), s > 0 && (h = !0);
    const P = (b, g) => {
      let E = m[b];
      if (E !== void 0) {
        let L = y[E];
        if (L !== void 0)
          return [E, L.fragment];
      }
      let O = document.createDocumentFragment();
      return E = y.length, y.push({ fragment: O, order: g, optgroup: b }), [E, O];
    };
    for (t = 0; t < s; t++) {
      let b = _.items[t];
      if (!b)
        continue;
      let g = b.id, E = d.options[g];
      if (E === void 0)
        continue;
      let O = W(g), L = d.getOption(O, !0);
      for (d.settings.hideSelected || L.classList.toggle("selected", d.items.includes(O)), n = E[d.settings.optgroupField] || "", l = Array.isArray(n) ? n : [n], i = 0, o = l && l.length; i < o; i++) {
        n = l[i];
        let j = E.$order, N = d.optgroups[n];
        if (N === void 0 && typeof d.settings.optionGroupRegister == "function") {
          var B;
          (B = d.settings.optionGroupRegister.apply(d, [n])) && d.registerOptionGroup(B);
        }
        N = d.optgroups[n], N === void 0 ? n = "" : j = N.$order;
        const [Ne, ze] = P(n, j);
        i > 0 && (L = L.cloneNode(!0), x(L, { id: E.$id + "-clone-" + i, "aria-selected": null }), L.classList.add("ts-cloned"), M(L, "active"), d.activeOption && d.activeOption.dataset.value == g && u && u.dataset.group === n.toString() && (S = L)), ze.appendChild(L), n != "" && (m[n] = Ne);
      }
    }
    d.settings.lockOptgroupOrder && y.sort((b, g) => b.order - g.order), c = document.createDocumentFragment(), I(y, (b) => {
      let g = b.fragment, E = b.optgroup;
      if (!g || !g.children.length)
        return;
      let O = d.optgroups[E];
      if (O !== void 0) {
        let L = document.createDocumentFragment(), j = d.render("optgroup_header", O);
        V(L, j), V(L, g);
        let N = d.render("optgroup", { group: O, options: L });
        V(c, N);
      } else
        V(c, g);
    }), w.innerHTML = "", V(w, c), d.isDropdownContentStale = !1, d.settings.highlight && (bt(w), _.query.length && _.tokens.length && I(_.tokens, (b) => {
      vt(w, b.regex);
    }));
    var C = (b) => {
      let g = d.render(b, { input: p });
      return g && (h = !0, w.insertBefore(g, w.firstChild)), g;
    };
    if (d.loading ? C("loading") : d.settings.shouldLoad.call(d, p) ? _.items.length === 0 && C("no_results") : C("not_loading"), a = d.canCreate(p), a && (f = C("option_create")), d.hasOptions = _.items.length > 0 || a, h) {
      if (_.items.length > 0) {
        if (!S && d.settings.mode === "single" && d.items[0] != null && (S = d.getOption(d.items[0])), !w.contains(S)) {
          let b = 0;
          f && !d.settings.addPrecedence && (b = 1), S = d.selectable()[b];
        }
      } else f && (S = f);
      e && !d.isOpen && (d.open(), d.scrollToOption(S, "auto")), d.setActiveOption(S);
    } else
      d.clearActiveOption(), e && d.isOpen && d.close(!1);
  }
  /**
   * Return list of selectable options
   *
   */
  selectable() {
    return this.dropdown_content.querySelectorAll("[data-selectable]");
  }
  /**
   * Adds an available option. If it already exists,
   * nothing will happen. Note: this does not refresh
   * the options list dropdown (use `refreshOptions`
   * for that).
   *
   * Usage:
   *
   *   this.addOption(data)
   *
   */
  addOption(e, t = !1) {
    const i = this;
    if (Array.isArray(e))
      return i.addOptions(e, t), !1;
    const o = T(e[i.settings.valueField]);
    return o === null || i.options.hasOwnProperty(o) ? (i.updateOption(e[i.settings.valueField], e), !1) : (e.$order = e.$order || ++i.order, e.$id = i.inputId + "-opt-" + e.$order, i.options[o] = e, i.isDropdownContentStale = !0, t && (i.userOptions[o] = t, i.trigger("option_add", o, e)), o);
  }
  /**
   * Add multiple options
   *
   */
  addOptions(e, t = !1) {
    I(e, (i) => {
      this.addOption(i, t);
    });
  }
  /**
   * @deprecated 1.7.7
   */
  registerOption(e) {
    return this.addOption(e);
  }
  /**
   * Registers an option group to the pool of option groups.
   *
   * @return {boolean|string}
   */
  registerOptionGroup(e) {
    var t = T(e[this.settings.optgroupValueField]);
    return t === null ? !1 : (e.$order = e.$order || ++this.order, this.optgroups[t] = e, t);
  }
  /**
   * Registers a new optgroup for options
   * to be bucketed into.
   *
   */
  addOptionGroup(e, t) {
    var i;
    t[this.settings.optgroupValueField] = e, (i = this.registerOptionGroup(t)) && this.trigger("optgroup_add", i, t);
  }
  /**
   * Removes an existing option group.
   *
   */
  removeOptionGroup(e) {
    this.optgroups.hasOwnProperty(e) && (delete this.optgroups[e], this.clearCache(), this.trigger("optgroup_remove", e));
  }
  /**
   * Clears all existing option groups.
   */
  clearOptionGroups() {
    this.optgroups = {}, this.clearCache(), this.trigger("optgroup_clear");
  }
  /**
   * Updates an option available for selection. If
   * it is visible in the selected items or options
   * dropdown, it will be re-rendered automatically.
   *
   */
  updateOption(e, t) {
    const i = this;
    var o, s;
    const n = T(e), l = T(t[i.settings.valueField]);
    if (n === null)
      return;
    const c = i.options[n];
    if (c == null)
      return;
    if (typeof l != "string")
      throw new Error("Value must be set in option data");
    const a = i.getOption(n), u = i.getItem(n);
    if (t.$order = t.$order || c.$order, delete i.options[n], i.uncacheValue(l), i.options[l] = t, a) {
      if (i.dropdown_content.contains(a)) {
        const f = i._render("option", t);
        ae(a, f), i.activeOption === a && i.setActiveOption(f);
      }
      a.remove();
    }
    u && (s = i.items.indexOf(n), s !== -1 && i.items.splice(s, 1, l), o = i._render("item", t), u.classList.contains("active") && $(o, "active"), ae(u, o)), i.isDropdownContentStale = !0;
  }
  /**
   * Removes a single option.
   *
   */
  removeOption(e, t) {
    const i = this;
    e = W(e), i.uncacheValue(e), delete i.userOptions[e], delete i.options[e], i.isDropdownContentStale = !0, i.trigger("option_remove", e), i.removeItem(e, t);
  }
  /**
   * Clears all options.
   */
  clearOptions(e) {
    const t = (e || this.clearFilter).bind(this);
    this.loadedSearches = {}, this.userOptions = {}, this.clearCache();
    const i = {};
    I(this.options, (o, s) => {
      t(o, s) && (i[s] = o);
    }), this.options = this.sifter.items = i, this.isDropdownContentStale = !0, this.trigger("option_clear");
  }
  /**
   * Used by clearOptions() to decide whether or not an option should be removed
   * Return true to keep an option, false to remove
   *
   */
  clearFilter(e, t) {
    return this.items.indexOf(t) >= 0;
  }
  /**
   * Returns the dom element of the option
   * matching the given value.
   *
   */
  getOption(e, t = !1) {
    const i = T(e);
    if (i === null)
      return null;
    const o = this.options[i];
    if (o != null) {
      if (o.$div)
        return o.$div;
      if (t)
        return this._render("option", o);
    }
    return null;
  }
  /**
   * Returns the dom element of the next or previous dom element of the same type
   * Note: adjacent options may not be adjacent DOM elements (optgroups)
   *
   */
  getAdjacent(e, t, i = "option") {
    var o = this, s;
    if (!e)
      return null;
    i == "item" ? s = o.controlChildren() : s = o.dropdown_content.querySelectorAll("[data-selectable]");
    for (let n = 0; n < s.length; n++)
      if (s[n] == e)
        return t > 0 ? s[n + 1] : s[n - 1];
    return null;
  }
  /**
   * Returns the dom element of the item
   * matching the given value.
   *
   */
  getItem(e) {
    if (typeof e == "object")
      return e;
    var t = T(e);
    return t !== null ? this.control.querySelector(`[data-value="${me(t)}"]`) : null;
  }
  /**
   * "Selects" multiple items at once. Adds them to the list
   * at the current caret position.
   *
   */
  addItems(e, t) {
    var i = this, o = Array.isArray(e) ? e : [e];
    o = o.filter((n) => i.items.indexOf(n) === -1);
    const s = o[o.length - 1];
    o.forEach((n) => {
      i.isPending = n !== s, i.addItem(n, t);
    });
  }
  /**
   * "Selects" an item. Adds it to the list
   * at the current caret position.
   *
   */
  addItem(e, t) {
    var i = t ? [] : ["change", "dropdown_close"];
    he(this, i, () => {
      var o, s;
      const n = this, l = n.settings.mode, c = T(e);
      if (!(c && n.items.indexOf(c) !== -1 && (l === "single" && n.close(), l === "single" || !n.settings.duplicates)) && !(c === null || !n.options.hasOwnProperty(c)) && (l === "single" && n.clear(t), !(l === "multi" && n.isFull()))) {
        if (o = n._render("item", n.options[c]), n.control.contains(o) && (o = o.cloneNode(!0)), s = n.isFull(), n.items.splice(n.caretPos, 0, c), n.insertAtCaret(o), n.isSetup) {
          if (!n.isPending && n.settings.hideSelected) {
            let a = n.getOption(c), u = n.getAdjacent(a, 1);
            u && n.setActiveOption(u);
          }
          n.settings.clearAfterSelect && n.setTextboxValue(), !n.isPending && !n.settings.closeAfterSelect && n.refreshOptions(n.isFocused && l !== "single"), n.settings.closeAfterSelect != !1 && n.isFull() ? n.close() : n.isPending || n.positionDropdown(), n.trigger("item_add", c, o), n.isPending || n.updateOriginalInput({ silent: t });
        }
        (!n.isPending || !s && n.isFull()) && (n.inputState(), n.refreshState());
      }
    });
  }
  /**
   * Removes the selected item matching
   * the provided value.
   *
   */
  removeItem(e = null, t) {
    const i = this;
    if (e = i.getItem(e), !e)
      return;
    var o, s;
    const n = e.dataset.value;
    o = ve(e), e.remove(), e.classList.contains("active") && (s = i.activeItems.indexOf(e), i.activeItems.splice(s, 1), M(e, "active")), i.items.splice(o, 1), i.isDropdownContentStale = !0, !i.settings.persist && i.userOptions.hasOwnProperty(n) && i.removeOption(n, t), o < i.caretPos && i.setCaret(i.caretPos - 1), i.updateOriginalInput({ silent: t }), i.refreshState(), i.positionDropdown(), i.trigger("item_remove", n, e);
  }
  /**
   * Invokes the `create` method provided in the
   * TomSelect options that should provide the data
   * for the new item, given the user input.
   *
   * Once this completes, it will be added
   * to the item list.
   *
   */
  createItem(e = null, t = () => {
  }) {
    arguments.length === 3 && (t = arguments[2]), typeof t != "function" && (t = () => {
    });
    var i = this, o = i.caretPos, s;
    if (e = e || i.inputValue(), !i.canCreate(e))
      return T(e) && this.options[e] && i.addItem(e), t(), !1;
    i.lock();
    var n = !1, l = (c) => {
      if (i.unlock(), !c || typeof c != "object")
        return t();
      var a = T(c[i.settings.valueField]);
      if (typeof a != "string")
        return t();
      i.setTextboxValue(), i.addOption(c, !0), i.setCaret(o), i.addItem(a), t(c), n = !0;
    };
    return typeof i.settings.create == "function" ? s = i.settings.create.call(this, e, l) : s = {
      [i.settings.labelField]: e,
      [i.settings.valueField]: e
    }, n || l(s), !0;
  }
  /**
   * Re-renders the selected item lists.
   */
  refreshItems() {
    var e = this;
    e.isDropdownContentStale = !0, e.isSetup && e.addItems(e.items), e.updateOriginalInput(), e.refreshState();
  }
  /**
   * Updates all state-dependent attributes
   * and CSS classes.
   */
  refreshState() {
    const e = this;
    e.refreshValidityState();
    const t = e.isFull(), i = e.isLocked;
    e.wrapper.classList.toggle("rtl", e.rtl);
    const o = e.wrapper.classList;
    o.toggle("focus", e.isFocused), o.toggle("disabled", e.isDisabled), o.toggle("readonly", e.isReadOnly), o.toggle("required", e.isRequired), o.toggle("invalid", !e.isValid), o.toggle("locked", i), o.toggle("full", t), o.toggle("input-active", e.isFocused && !e.isInputHidden), o.toggle("dropdown-active", e.isOpen), o.toggle("has-options", gt(e.options)), o.toggle("has-items", e.items.length > 0);
  }
  /**
   * Update the `required` attribute of both input and control input.
   *
   * The `required` property needs to be activated on the control input
   * for the error to be displayed at the right place. `required` also
   * needs to be temporarily deactivated on the input since the input is
   * hidden and can't show errors.
   */
  refreshValidityState() {
    var e = this;
    e.input.validity && (e.isValid = e.input.validity.valid, e.isInvalid = !e.isValid);
  }
  /**
   * Determines whether or not more items can be added
   * to the control without exceeding the user-defined maximum.
   *
   * @returns {boolean}
   */
  isFull() {
    return this.settings.maxItems !== null && this.items.length >= this.settings.maxItems;
  }
  /**
   * Refreshes the original <select> or <input>
   * element to reflect the current state.
   *
   */
  updateOriginalInput(e = {}) {
    const t = this;
    var i, o;
    const s = t.input.querySelector('option[value=""]');
    if (t.is_select_tag) {
      let c = function(a, u, f) {
        return a || (a = D('<option value="' + oe(u) + '">' + oe(f) + "</option>")), a != s && t.input.append(a), n.push(a), (a != s || l > 0) && (a.selected = !0), a;
      };
      const n = [], l = t.input.querySelectorAll("option:checked").length;
      t.input.querySelectorAll("option:checked").forEach((a) => {
        a.selected = !1;
      }), t.items.length == 0 && t.settings.mode == "single" ? c(s, "", "") : t.items.forEach((a) => {
        if (i = t.options[a], o = i[t.settings.labelField] || "", n.includes(i.$option)) {
          const u = t.input.querySelector(`option[value="${me(a)}"]:not(:checked)`);
          c(u, a, o);
        } else
          i.$option = c(i.$option, a, o);
      });
    } else
      t.input.value = t.getValue();
    t.isSetup && (e.silent || t.trigger("change", t.getValue()));
  }
  /**
   * Shows the autocomplete dropdown containing
   * the available options.
   */
  open() {
    var e = this;
    e.isLocked || e.isOpen || e.settings.mode === "multi" && e.isFull() || (e.isOpen = !0, x(e.focus_node, { "aria-expanded": "true" }), e.refreshState(), G(e.dropdown, { visibility: "hidden", display: "block" }), e.positionDropdown(), G(e.dropdown, { visibility: "visible", display: "block" }), e.focus(), e.trigger("dropdown_open", e.dropdown));
  }
  /**
   * Closes the autocomplete dropdown menu.
   */
  close(e = !0) {
    var t = this, i = t.isOpen;
    e && (t.setTextboxValue(), t.settings.mode === "single" && t.items.length && t.inputState()), t.isOpen = !1, x(t.focus_node, { "aria-expanded": "false" }), G(t.dropdown, { display: "none" }), t.settings.hideSelected && t.clearActiveOption(), t.refreshState(), i && t.trigger("dropdown_close", t.dropdown);
  }
  /**
   * Calculates and applies the appropriate
   * position of the dropdown if dropdownParent = 'body'.
   * Otherwise, position is determined by css
   */
  positionDropdown() {
    if (this.settings.dropdownParent === "body") {
      var e = this.control, t = e.getBoundingClientRect(), i = e.offsetHeight + t.top + window.scrollY, o = t.left + window.scrollX;
      G(this.dropdown, {
        width: t.width + "px",
        top: i + "px",
        left: o + "px"
      });
    }
  }
  /**
   * Resets / clears all selected items
   * from the control.
   *
   */
  clear(e) {
    var t = this;
    if (t.items.length) {
      var i = t.controlChildren();
      I(i, (o) => {
        t.removeItem(o, !0);
      }), t.inputState(), e || t.updateOriginalInput(), t.trigger("clear");
    }
  }
  /**
   * A helper method for inserting an element
   * at the current caret position.
   *
   */
  insertAtCaret(e) {
    const t = this, i = t.caretPos, o = t.control;
    o.insertBefore(e, o.children[i] || null), t.setCaret(i + 1);
  }
  /**
   * Removes the current selected item(s).
   *
   */
  deleteSelection(e) {
    var t, i, o, s, n = this;
    t = e && e.keyCode === be ? -1 : 1, i = ht(n.control_input);
    const l = [];
    if (n.activeItems.length)
      s = ge(n.activeItems, t), o = ve(s), t > 0 && o++, I(n.activeItems, (c) => l.push(c));
    else if ((n.isFocused || n.settings.mode === "single") && n.items.length) {
      const c = n.controlChildren();
      let a;
      t < 0 && i.start === 0 && i.length === 0 ? a = c[n.caretPos - 1] : t > 0 && i.start === n.inputValue().length && (a = c[n.caretPos]), a !== void 0 && l.push(a);
    }
    if (!n.shouldDelete(l, e))
      return !1;
    for (A(e, !0), typeof o < "u" && n.setCaret(o); l.length; )
      n.removeItem(l.pop());
    return n.inputState(), n.positionDropdown(), n.refreshOptions(!1), !0;
  }
  /**
   * Return true if the items should be deleted
   */
  shouldDelete(e, t) {
    const i = e.map((o) => o.dataset.value);
    return !(!i.length || typeof this.settings.onDelete == "function" && this.settings.onDelete.call(this, i, t) === !1);
  }
  /**
   * Selects the previous / next item (depending on the `direction` argument).
   *
   * > 0 - right
   * < 0 - left
   *
   */
  advanceSelection(e, t) {
    var i, o, s = this;
    s.rtl && (e *= -1), !s.inputValue().length && (H(U, t) || H("shiftKey", t) ? (i = s.getLastActive(e), i ? i.classList.contains("active") ? o = s.getAdjacent(i, e, "item") : o = i : e > 0 ? o = s.control_input.nextElementSibling : o = s.control_input.previousElementSibling, o && (o.classList.contains("active") && s.removeActiveItem(i), s.setActiveItemClass(o))) : s.moveCaret(e));
  }
  moveCaret(e) {
  }
  /**
   * Get the last active item
   *
   */
  getLastActive(e) {
    let t = this.control.querySelector(".last-active");
    if (t)
      return t;
    var i = this.control.querySelectorAll(".active");
    if (i)
      return ge(i, e);
  }
  /**
   * Moves the caret to the specified index.
   *
   * The input must be moved by leaving it in place and moving the
   * siblings, due to the fact that focus cannot be restored once lost
   * on mobile webkit devices
   *
   */
  setCaret(e) {
    this.caretPos = this.items.length;
  }
  /**
   * Return list of item dom elements
   *
   */
  controlChildren() {
    return Array.from(this.control.querySelectorAll("[data-ts-item]"));
  }
  /**
   * Disables user input on the control. Used while
   * items are being asynchronously created.
   */
  lock() {
    this.setLocked(!0);
  }
  /**
   * Re-enables user input on the control.
   */
  unlock() {
    this.setLocked(!1);
  }
  /**
   * Disable or enable user input on the control
   */
  setLocked(e = this.isReadOnly || this.isDisabled) {
    this.isLocked = e, this.refreshState();
  }
  /**
   * Disables user input on the control completely.
   * While disabled, it cannot receive focus.
   */
  disable() {
    this.setDisabled(!0), this.close();
  }
  /**
   * Enables the control so that it can respond
   * to focus and user input.
   */
  enable() {
    this.setDisabled(!1);
  }
  setDisabled(e) {
    this.focus_node.tabIndex = e ? -1 : this.tabIndex, this.isDisabled = e, this.input.disabled = e, this.control_input.disabled = e, this.setLocked();
  }
  setReadOnly(e) {
    this.isReadOnly = e, this.input.readOnly = e, this.control_input.readOnly = e, this.setLocked();
  }
  /**
   * Completely destroys the control and
   * unbinds all event listeners so that it can
   * be garbage collected.
   */
  destroy() {
    var e = this, t = e.revertSettings;
    e.trigger("destroy"), e.off(), e.wrapper.remove(), e.dropdown.remove(), e.input.innerHTML = t.innerHTML, e.input.tabIndex = t.tabIndex, M(e.input, "tomselected", "ts-hidden-accessible"), e._destroy(), delete e.input.tomselect;
  }
  /**
   * A helper method for rendering "item" and
   * "option" templates, given the data.
   *
   */
  render(e, t) {
    var i, o;
    const s = this;
    if (typeof this.settings.render[e] != "function" || (o = s.settings.render[e].call(this, t, oe), !o))
      return null;
    if (o = D(o), e === "option" || e === "option_create" ? t[s.settings.disabledField] ? x(o, { "aria-disabled": "true" }) : x(o, { "data-selectable": "" }) : e === "optgroup" && (i = t.group[s.settings.optgroupValueField], x(o, { "data-group": i }), t.group[s.settings.disabledField] && x(o, { "data-disabled": "" })), e === "option" || e === "item") {
      const n = W(t[s.settings.valueField]);
      x(o, { "data-value": n }), e === "item" ? ($(o, s.settings.itemClass), x(o, { "data-ts-item": "" })) : ($(o, s.settings.optionClass), x(o, {
        role: "option",
        id: t.$id
      }), t.$div = o, s.options[n] = t);
    }
    return o;
  }
  /**
   * Type guarded rendering
   *
   */
  _render(e, t) {
    const i = this.render(e, t);
    if (i == null)
      throw "HTMLElement expected";
    return i;
  }
  /**
   * Clears the render cache for a template. If
   * no template is given, clears all render
   * caches.
   *
   */
  clearCache() {
    I(this.options, (e) => {
      e.$div && (e.$div.remove(), delete e.$div);
    });
  }
  /**
   * Removes a value from item and option caches
   *
   */
  uncacheValue(e) {
    const t = this.getOption(e);
    t && t.remove();
  }
  /**
   * Determines whether or not to display the
   * create item prompt, given a user input.
   *
   */
  canCreate(e) {
    return this.settings.create && e.length > 0 && this.settings.createFilter.call(this, e);
  }
  /**
   * Wraps this.`method` so that `new_fn` can be invoked 'before', 'after', or 'instead' of the original method
   *
   * this.hook('instead','onKeyDown',function( arg1, arg2 ...){
   *
   * });
   */
  hook(e, t, i) {
    var o = this, s = o[t];
    o[t] = function() {
      var n, l;
      return e === "after" && (n = s.apply(o, arguments)), l = i.apply(o, arguments), e === "instead" ? l : (e === "before" && (n = s.apply(o, arguments)), n);
    };
  }
}
const It = (r, e, t, i) => {
  r.addEventListener(e, t, i);
};
function Lt() {
  It(this.input, "change", () => {
    this.sync();
  });
}
const kt = (r) => typeof r > "u" || r === null ? null : Ft(r), Ft = (r) => typeof r == "boolean" ? r ? "1" : "0" : r + "", Oe = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, $t = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (Tt(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, Tt = (r) => typeof r == "string" && r.indexOf("<") > -1;
function Dt(r) {
  var e = this, t = e.onOptionSelect;
  e.settings.hideSelected = !1;
  const i = Object.assign({
    // so that the user may add different ones as well
    className: "tomselect-checkbox",
    // the following default to the historic plugin's values
    checkedClassNames: void 0,
    uncheckedClassNames: void 0
  }, r);
  var o = function(l, c) {
    c ? (l.checked = !0, i.uncheckedClassNames && l.classList.remove(...i.uncheckedClassNames), i.checkedClassNames && l.classList.add(...i.checkedClassNames)) : (l.checked = !1, i.checkedClassNames && l.classList.remove(...i.checkedClassNames), i.uncheckedClassNames && l.classList.add(...i.uncheckedClassNames));
  }, s = function(l) {
    setTimeout(() => {
      var c = l.querySelector("input." + i.className);
      c instanceof HTMLInputElement && o(c, l.classList.contains("selected"));
    }, 1);
  };
  e.hook("after", "setupTemplates", () => {
    var n = e.settings.render.option;
    e.settings.render.option = (l, c) => {
      var a = $t(n.call(e, l, c)), u = document.createElement("input");
      i.className && u.classList.add(i.className), u.addEventListener("click", function(m) {
        Oe(m);
      }), u.type = "checkbox";
      const f = kt(l[e.settings.valueField]);
      return o(u, !!(f && e.items.indexOf(f) > -1)), a.prepend(u), a;
    };
  }), e.on("item_remove", (n) => {
    var l = e.getOption(n);
    l && (l.classList.remove("selected"), s(l));
  }), e.on("item_add", (n) => {
    var l = e.getOption(n);
    l && s(l);
  }), e.hook("instead", "onOptionSelect", (n, l) => {
    if (l.classList.contains("selected")) {
      l.classList.remove("selected"), e.removeItem(l.dataset.value), e.refreshOptions(), Oe(n, !0);
      return;
    }
    t.call(e, n, l), s(l);
  });
}
const Pt = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (Mt(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, Mt = (r) => typeof r == "string" && r.indexOf("<") > -1;
function Ht(r) {
  const e = this, t = Object.assign({
    className: "clear-button",
    title: "Clear All",
    role: "button",
    tabindex: 0,
    html: (i) => `<div class="${i.className}" title="${i.title}" role="${i.role}" tabindex="${i.tabindex}">&times;</div>`
  }, r);
  e.on("initialize", () => {
    var i = Pt(t.html(t));
    i.addEventListener("click", (o) => {
      e.isLocked || (e.clear(), e.settings.mode === "single" && e.settings.allowEmptyOption && e.addItem(""), e.refreshOptions(!1), o.preventDefault(), o.stopPropagation());
    }), e.control.appendChild(i);
  });
}
const Vt = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, z = (r, e, t, i) => {
  r.addEventListener(e, t, i);
}, Nt = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, zt = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (Rt(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, Rt = (r) => typeof r == "string" && r.indexOf("<") > -1, Kt = (r, e) => {
  Nt(e, (t, i) => {
    t == null ? r.removeAttribute(i) : r.setAttribute(i, "" + t);
  });
}, Bt = (r, e) => {
  var t;
  (t = r.parentNode) == null || t.insertBefore(e, r.nextSibling);
}, jt = (r, e) => {
  var t;
  (t = r.parentNode) == null || t.insertBefore(e, r);
}, Yt = (r, e) => {
  do {
    var t;
    if (e = (t = e) == null ? void 0 : t.previousElementSibling, r == e)
      return !0;
  } while (e && e.previousElementSibling);
  return !1;
};
function Gt() {
  var r = this;
  if (r.settings.mode !== "multi") return;
  var e = r.lock, t = r.unlock;
  let i = !0, o;
  r.hook("after", "setupTemplates", () => {
    var s = r.settings.render.item;
    r.settings.render.item = (n, l) => {
      const c = zt(s.call(r, n, l));
      Kt(c, {
        draggable: "true"
      });
      const a = (p) => {
        i || Vt(p), p.stopPropagation();
      }, u = (p) => {
        o = c, setTimeout(() => {
          c.classList.add("ts-dragging");
        }, 0);
      }, f = (p) => {
        p.preventDefault(), c.classList.add("ts-drag-over"), y(c, o);
      }, m = () => {
        c.classList.remove("ts-drag-over");
      }, y = (p, v) => {
        v !== void 0 && (Yt(v, c) ? Bt(p, v) : jt(p, v));
      }, d = () => {
        var p;
        document.querySelectorAll(".ts-drag-over").forEach((_) => _.classList.remove("ts-drag-over")), (p = o) == null || p.classList.remove("ts-dragging"), o = void 0;
        var v = [];
        r.control.querySelectorAll("[data-value]").forEach((_) => {
          if (_.dataset.value) {
            let S = _.dataset.value;
            S && v.push(S);
          }
        }), r.setValue(v);
      };
      return z(c, "mousedown", a), z(c, "dragstart", u), z(c, "dragenter", f), z(c, "dragover", f), z(c, "dragleave", m), z(c, "dragend", d), c;
    };
  }), r.hook("instead", "lock", () => (i = !1, e.call(r))), r.hook("instead", "unlock", () => (i = !0, t.call(r)));
}
const Ut = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, qt = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (Wt(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, Wt = (r) => typeof r == "string" && r.indexOf("<") > -1;
function Qt(r) {
  const e = this, t = Object.assign({
    title: "Untitled",
    headerClass: "dropdown-header",
    titleRowClass: "dropdown-header-title",
    labelClass: "dropdown-header-label",
    closeClass: "dropdown-header-close",
    html: (i) => '<div class="' + i.headerClass + '"><div class="' + i.titleRowClass + '"><span class="' + i.labelClass + '">' + i.title + '</span><a class="' + i.closeClass + '">&times;</a></div></div>'
  }, r);
  e.on("initialize", () => {
    var i = qt(t.html(t)), o = i.querySelector("." + t.closeClass);
    o && o.addEventListener("click", (s) => {
      Ut(s, !0), e.close();
    }), e.dropdown.insertBefore(i, e.dropdown.firstChild);
  });
}
const Jt = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, Xt = (r, ...e) => {
  var t = Zt(e);
  r = er(r), r.map((i) => {
    t.map((o) => {
      i.classList.remove(o);
    });
  });
}, Zt = (r) => {
  var e = [];
  return Jt(r, (t) => {
    typeof t == "string" && (t = t.trim().split(/[\t\n\f\r\s]/)), Array.isArray(t) && (e = e.concat(t));
  }), e.filter(Boolean);
}, er = (r) => (Array.isArray(r) || (r = [r]), r), tr = (r, e) => {
  if (!r) return -1;
  e = e || r.nodeName;
  for (var t = 0; r = r.previousElementSibling; )
    r.matches(e) && t++;
  return t;
};
function rr() {
  var r = this;
  r.hook("instead", "setCaret", (e) => {
    r.settings.mode === "single" || !r.control.contains(r.control_input) ? e = r.items.length : (e = Math.max(0, Math.min(r.items.length, e)), e != r.caretPos && !r.isPending && r.controlChildren().forEach((t, i) => {
      i < e ? r.control_input.insertAdjacentElement("beforebegin", t) : r.control.appendChild(t);
    })), r.caretPos = e;
  }), r.hook("instead", "moveCaret", (e) => {
    if (!r.isFocused) return;
    const t = r.getLastActive(e);
    if (t) {
      const i = tr(t);
      r.setCaret(e > 0 ? i + 1 : i), r.setActiveItem(), Xt(t, "last-active");
    } else
      r.setCaret(r.caretPos + e);
  });
}
const ir = 27, or = 9, nr = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, sr = (r, e, t, i) => {
  r.addEventListener(e, t, i);
}, lr = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, Se = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (ar(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, ar = (r) => typeof r == "string" && r.indexOf("<") > -1, cr = (r, ...e) => {
  var t = dr(e);
  r = ur(r), r.map((i) => {
    t.map((o) => {
      i.classList.add(o);
    });
  });
}, dr = (r) => {
  var e = [];
  return lr(r, (t) => {
    typeof t == "string" && (t = t.trim().split(/[\t\n\f\r\s]/)), Array.isArray(t) && (e = e.concat(t));
  }), e.filter(Boolean);
}, ur = (r) => (Array.isArray(r) || (r = [r]), r);
function fr() {
  const r = this;
  r.settings.shouldOpen = !0, r.hook("before", "setup", () => {
    var e;
    r.focus_node = r.control, cr(r.control_input, "dropdown-input");
    const t = Se('<div class="dropdown-input-wrap">');
    t.append(r.control_input), r.dropdown.insertBefore(t, r.dropdown.firstChild);
    const i = Se('<input class="items-placeholder" tabindex="-1" />');
    i.placeholder = r.settings.placeholder || "", r.control.append(i);
    const o = (e = r.input) == null ? void 0 : e.getAttribute("aria-label");
    o && i.setAttribute("aria-label", o);
  }), r.on("initialize", () => {
    r.control_input.addEventListener("keydown", (t) => {
      switch (t.keyCode) {
        case ir:
          r.isOpen && (nr(t, !0), r.close()), r.clearActiveItems();
          return;
        case or:
          r.focus_node.tabIndex = -1;
          break;
      }
      return r.onKeyDown.call(r, t);
    }), r.on("blur", () => {
      r.focus_node.tabIndex = r.isDisabled ? -1 : r.tabIndex;
    }), r.on("dropdown_open", () => {
      r.control_input.focus();
    });
    const e = r.onBlur;
    r.hook("instead", "onBlur", (t) => {
      if (!(t && t.relatedTarget == r.control_input))
        return e.call(r);
    }), sr(r.control_input, "blur", () => r.onBlur()), r.hook("before", "close", () => {
      r.isOpen && r.focus_node.focus({
        preventScroll: !0
      });
    });
  });
}
const q = (r, e, t, i) => {
  r.addEventListener(e, t, i);
};
function pr() {
  var r = this;
  r.on("initialize", () => {
    var e = document.createElement("span"), t = r.control_input;
    e.style.cssText = "position:absolute; top:-99999px; left:-99999px; width:auto; padding:0; white-space:pre; ", r.wrapper.appendChild(e);
    var i = ["letterSpacing", "fontSize", "fontFamily", "fontWeight", "textTransform"];
    for (const s of i)
      e.style[s] = t.style[s];
    var o = () => {
      e.textContent = t.value, t.style.width = e.clientWidth + "px";
    };
    o(), r.on("update item_add item_remove", o), q(t, "input", o), q(t, "keyup", o), q(t, "blur", o), q(t, "update", o);
  });
}
function hr() {
  var r = this, e = r.deleteSelection;
  this.hook("instead", "deleteSelection", (t) => r.activeItems.length ? e.call(r, t) : !1);
}
function mr() {
  this.hook("instead", "setActiveItem", () => {
  }), this.hook("instead", "selectAll", () => {
  });
}
const Ce = 37, gr = 39, vr = (r, e, t) => {
  for (; r && r.matches; ) {
    if (r.matches(e))
      return r;
    r = r.parentNode;
  }
}, br = (r, e) => {
  if (!r) return -1;
  e = e || r.nodeName;
  for (var t = 0; r = r.previousElementSibling; )
    r.matches(e) && t++;
  return t;
};
function _r() {
  var r = this, e = r.onKeyDown;
  r.hook("instead", "onKeyDown", (t) => {
    var i, o, s, n;
    if (!r.isOpen || !(t.keyCode === Ce || t.keyCode === gr))
      return e.call(r, t);
    r.ignoreHover = !0, n = vr(r.activeOption, "[data-group]"), i = br(r.activeOption, "[data-selectable]"), n && (t.keyCode === Ce ? n = n.previousSibling : n = n.nextSibling, n && (s = n.querySelectorAll("[data-selectable]"), o = s[Math.min(s.length - 1, i)], o && r.setActiveOption(o)));
  });
}
const wr = (r) => (r + "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"), Ae = (r, e = !1) => {
  r && (r.preventDefault(), e && r.stopPropagation());
}, Ee = (r, e, t, i) => {
  r.addEventListener(e, t, i);
}, Ie = (r) => {
  if (r.jquery)
    return r[0];
  if (r instanceof HTMLElement)
    return r;
  if (yr(r)) {
    var e = document.createElement("template");
    return e.innerHTML = r.trim(), e.content.firstChild;
  }
  return document.querySelector(r);
}, yr = (r) => typeof r == "string" && r.indexOf("<") > -1;
function xr(r) {
  const e = Object.assign({
    label: "&times;",
    title: "Remove",
    className: "remove",
    append: !0
  }, r);
  var t = this;
  if (e.append) {
    var i = '<a href="javascript:void(0)" class="' + e.className + '" tabindex="-1" title="' + wr(e.title) + '">' + e.label + "</a>";
    t.hook("after", "setupTemplates", () => {
      var o = t.settings.render.item;
      t.settings.render.item = (s, n) => {
        var l = Ie(o.call(t, s, n)), c = Ie(i);
        return l.appendChild(c), Ee(c, "mousedown", (a) => {
          Ae(a, !0);
        }), Ee(c, "click", (a) => {
          t.isLocked || (Ae(a, !0), !t.isLocked && t.shouldDelete([l], a) && (t.removeItem(l), t.refreshOptions(!1), t.inputState()));
        }), l;
      };
    });
  }
}
function Or(r) {
  const e = this, t = Object.assign({
    text: (i) => i[e.settings.labelField]
  }, r);
  e.on("item_remove", function(i) {
    if (e.isFocused && e.control_input.value.trim() === "") {
      var o = e.options[i];
      o && e.setTextboxValue(t.text.call(e, o));
    }
  });
}
const Sr = (r, e) => {
  if (Array.isArray(r))
    r.forEach(e);
  else
    for (var t in r)
      r.hasOwnProperty(t) && e(r[t], t);
}, Cr = (r, ...e) => {
  var t = Ar(e);
  r = Er(r), r.map((i) => {
    t.map((o) => {
      i.classList.add(o);
    });
  });
}, Ar = (r) => {
  var e = [];
  return Sr(r, (t) => {
    typeof t == "string" && (t = t.trim().split(/[\t\n\f\r\s]/)), Array.isArray(t) && (e = e.concat(t));
  }), e.filter(Boolean);
}, Er = (r) => (Array.isArray(r) || (r = [r]), r);
function Ir() {
  const r = this, e = r.canLoad, t = r.clearActiveOption, i = r.loadCallback;
  var o = {}, s, n = !1, l, c = [], a = !1, u;
  if (r.settings.shouldLoadMore || (r.settings.shouldLoadMore = () => {
    if (s.clientHeight / (s.scrollHeight - s.scrollTop) > 0.9)
      return !0;
    if (r.activeOption) {
      var p = r.selectable(), v = Array.from(p).indexOf(r.activeOption);
      if (v >= p.length - 2)
        return !0;
    }
    return !1;
  }), !r.settings.firstUrl)
    throw "virtual_scroll plugin requires a firstUrl() method";
  r.settings.sortField = [{
    field: "$order"
  }, {
    field: "$score"
  }];
  const f = (d) => typeof r.settings.maxOptions == "number" && s.children.length >= r.settings.maxOptions ? !1 : !!(d in o && o[d]), m = (d, p) => r.items.indexOf(p) >= 0 || c.indexOf(p) >= 0;
  r.setNextUrl = (d, p) => {
    o[d] = p;
  }, r.getUrl = (d) => {
    if (d in o) {
      const p = o[d];
      return o[d] = !1, p;
    }
    return r.clearPagination(), r.settings.firstUrl.call(r, d);
  }, r.clearPagination = () => {
    o = {};
  }, r.hook("instead", "clearActiveOption", () => {
    if (!n)
      return t.call(r);
  }), r.hook("instead", "canLoad", (d) => d in o ? f(d) : e.call(r, d)), r.hook("instead", "loadCallback", (d, p) => {
    if (!n)
      r.clearOptions(m);
    else if (l) {
      const v = d[0];
      v !== void 0 && (l.dataset.value = v[r.settings.valueField]);
    }
    i.call(r, d, p), !n && !a && (a = !0, r.lastValue === "" && (c = Object.keys(r.options), u = o[""])), n = !1;
  }), r.hook("before", "refreshOptions", () => {
    r.activeOption && r.activeOption.getAttribute("role") !== "option" && r.setActiveOption(r.activeOption.previousElementSibling);
  }), r.hook("after", "refreshOptions", () => {
    const d = r.lastValue;
    var p;
    f(d) ? (p = r.render("loading_more", {
      query: d
    }), p && (p.setAttribute("data-selectable", ""), l = p)) : d in o && !s.querySelector(".no-results") && (p = r.render("no_more_results", {
      query: d
    })), p && (Cr(p, r.settings.optionClass), s.append(p));
  });
  const y = () => {
    a && (r.clearOptions(m), u && (o[""] = u));
  };
  r.on("type", (d) => {
    d === "" && (y(), r.refreshOptions(!1));
  }), r.on("dropdown_close", y), r.on("initialize", () => {
    c = Object.keys(r.options), s = r.dropdown_content, r.settings.render = Object.assign({}, {
      loading_more: () => '<div class="loading-more-results">Loading more results ... </div>',
      no_more_results: () => '<div class="no-more-results">No more results</div>'
    }, r.settings.render), s.addEventListener("scroll", () => {
      r.settings.shouldLoadMore.call(r) && f(r.lastValue) && (n || (n = !0, r.load.call(r, r.lastValue)));
    });
  });
}
F.define("change_listener", Lt);
F.define("checkbox_options", Dt);
F.define("clear_button", Ht);
F.define("drag_drop", Gt);
F.define("dropdown_header", Qt);
F.define("caret_position", rr);
F.define("dropdown_input", fr);
F.define("input_autogrow", pr);
F.define("no_backspace_delete", hr);
F.define("no_active_items", mr);
F.define("optgroup_columns", _r);
F.define("remove_button", xr);
F.define("restore_on_backspace", Or);
F.define("virtual_scroll", Ir);
const Lr = `.formie-field .ts-wrapper.formie-combobox{width:100%;display:block;position:relative;min-height:0;border:0;padding:0;background:none;box-shadow:none}.formie-field select[data-formie-combobox-input].tomselected,.formie-field select[data-formie-combobox-input].ts-hidden-accessible{display:none!important}.formie-field .ts-wrapper.formie-combobox .ts-control{box-sizing:border-box;display:flex;flex-wrap:wrap;align-items:center;position:relative;overflow:hidden;z-index:1;width:100%;border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background-color:var(--formie-color-surface);background-image:none;min-height:var(--formie-control-height);padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-control-padding-x);box-shadow:none;font-size:var(--formie-control-font-size);line-height:var(--formie-line-height-tight);color:var(--formie-color-text);transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease;gap:var(--formie-space-1)}.formie-field .ts-wrapper.formie-combobox.single .ts-control{--ts-pr-min: var(--formie-control-padding-x);--ts-pr-caret: calc(var(--formie-select-indicator-size) + var(--formie-space-1));padding-right:calc(var(--formie-control-padding-x) + var(--formie-select-indicator-size) + var(--formie-space-2))!important;background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M7 7l3-3 3 3m0 6l-3 3-3-3' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-position:right var(--formie-space-2) center;background-repeat:no-repeat;background-size:var(--formie-select-indicator-size) var(--formie-select-indicator-size)}.formie-field .ts-wrapper.formie-combobox.single .ts-control:after{display:none}.formie-field .ts-wrapper.formie-combobox.multi .ts-control{padding:calc(var(--formie-control-padding-y) - 2px) var(--formie-control-padding-x) calc(var(--formie-control-padding-y) - 4px);--ts-pr-min: var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox.multi.has-items .ts-control{padding-left:var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox .ts-control>input{flex:1 1 auto;min-width:7rem;max-width:100%;margin:0!important;padding:0!important;min-height:0!important;max-height:none!important;border:0!important;background:none!important;box-shadow:none!important;color:var(--formie-color-text);font:inherit;font-size:var(--formie-control-font-size);line-height:inherit}.formie-field .ts-wrapper.formie-combobox .ts-control>input:focus{outline:none!important}.formie-field .ts-wrapper.formie-combobox.has-items .ts-control>input{margin:0 var(--formie-space-1)!important}.formie-field .ts-wrapper.formie-combobox.input-hidden .ts-control>input{opacity:0;position:absolute;left:-10000px}.formie-field .ts-wrapper.formie-combobox.single .ts-control,.formie-field .ts-wrapper.formie-combobox.single .ts-control>input{cursor:pointer}.formie-field .ts-wrapper.formie-combobox.single.input-active .ts-control,.formie-field .ts-wrapper.formie-combobox.single.input-active .ts-control>input{cursor:text}.formie-field .ts-wrapper.formie-combobox .ts-control .items-placeholder{color:var(--formie-color-text-muted);font:inherit;font-size:var(--formie-control-font-size)}.formie-field .ts-wrapper.formie-combobox .ts-control>input::placeholder{color:var(--formie-color-text-muted)}.formie-field .ts-wrapper.formie-combobox.focus .ts-control,.formie-field .ts-wrapper.formie-combobox .ts-control:focus-within,.formie-field .ts-wrapper.formie-combobox.dropdown-active .ts-control{outline:0;border-color:var(--formie-color-focus-ring);box-shadow:var(--formie-shadow-focus);border-radius:var(--formie-radius-sm)}.formie-field-has-error .ts-wrapper.formie-combobox .ts-control{border-color:var(--formie-color-danger)}.formie-field-has-error .ts-wrapper.formie-combobox.focus .ts-control,.formie-field-has-error .ts-wrapper.formie-combobox .ts-control:focus-within,.formie-field-has-error .ts-wrapper.formie-combobox.dropdown-active .ts-control{border-color:var(--formie-color-danger);box-shadow:var(--formie-shadow-danger-focus)}.formie-field .ts-wrapper.formie-combobox.multi .ts-control>.item,.formie-field .ts-wrapper.formie-combobox.multi .ts-control [data-value]{display:inline-flex;align-items:center;gap:var(--formie-space-1);margin:0 var(--formie-space-1) var(--formie-space-1) 0;padding:0 var(--formie-space-2);background:var(--formie-color-surface-muted, rgba(15, 23, 42, .06));background-image:none;border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);box-shadow:none;color:var(--formie-color-text);text-shadow:none;line-height:calc(var(--formie-control-height) - var(--formie-space-3))}.formie-field .ts-wrapper.formie-combobox.multi .ts-control>.item.active,.formie-field .ts-wrapper.formie-combobox.multi .ts-control [data-value].active{background:var(--formie-color-surface-muted, rgba(15, 23, 42, .06));color:var(--formie-color-text)}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item{display:inline-flex;align-items:center;gap:var(--formie-space-1)}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button:not(.rtl) .item{padding-right:var(--formie-space-2)!important}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove{display:inline-flex;align-items:center;justify-content:center;margin:0;padding:0;border:0;background:none;box-shadow:none;text-decoration:none;color:var(--formie-color-text-muted);font-size:1.125em;line-height:1;cursor:pointer}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove:hover,.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove:focus{background:none;text-decoration:none;color:var(--formie-color-text)}.formie-field .ts-wrapper.formie-combobox .ts-dropdown{position:absolute;top:100%;left:0;width:100%;z-index:var(--formie-z-popover, 30);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-shadow:var(--formie-shadow-popover, 0 8px 24px rgba(15, 23, 42, .12));margin-top:var(--formie-space-1);box-sizing:border-box}.formie-field .ts-wrapper.formie-combobox .ts-dropdown-content{overflow:hidden auto;max-height:200px;scroll-behavior:smooth}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .optgroup-header,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .no-results{color:var(--formie-color-text);padding:var(--formie-space-2) var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox .ts-dropdown [data-selectable]{cursor:pointer}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option.active,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option:hover,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .active{background:var(--formie-color-surface-muted, rgba(15, 23, 42, .05));color:var(--formie-color-text)}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .highlight{background:#3b82f62e;border-radius:2px}.formie-field .ts-wrapper.formie-combobox.disabled .ts-control{opacity:.6;background-color:var(--formie-color-surface-muted, rgba(15, 23, 42, .04));cursor:not-allowed}`, Le = "select[data-formie-combobox-input]", Z = "combobox", R = Be("fields", "combobox");
Ke(Z, [Lr]);
const Ve = [
  "formie-select",
  "formie-dropdown-input",
  "formie-input-error"
];
function kr(r) {
  const e = [];
  return Ve.forEach((t) => {
    r.classList.contains(t) && (r.classList.remove(t), e.push(t));
  }), e;
}
function Fr(r, e) {
  e.forEach((t) => {
    r.classList.add(t);
  });
}
function ke(r) {
  Ve.forEach((e) => {
    r.classList.remove(e);
  });
}
function $r(r, e) {
  const t = e?.trim();
  return t || r.querySelector('option[value=""]')?.textContent?.trim() || null;
}
function Tr(r) {
  r.options[""] && r.removeOption("", !0);
}
function Dr(r, e = {}) {
  r._formieTomSelect?.destroy();
  const t = e.multiple === !0, i = kr(r), o = $r(r, e.placeholder), s = {
    create: !1,
    maxItems: t ? null : 1,
    plugins: t ? ["remove_button"] : [],
    hideSelected: t ? !0 : null,
    clearAfterSelect: t,
    closeAfterSelect: !t,
    allowEmptyOption: !t,
    openOnFocus: !0,
    diacritics: !0,
    // Tom Select copies the native select class attribute onto its wrapper,
    // which would duplicate Formie select chrome if left on the <select>.
    copyClassesToDropdown: !1,
    wrapperClass: "ts-wrapper formie-combobox",
    onChange: () => {
      r.dispatchEvent(new Event("input", { bubbles: !0 })), r.dispatchEvent(new Event("change", { bubbles: !0 }));
    }
  };
  o && (s.placeholder = o), de(r, Z, "before-init", {
    select: r,
    options: s
  });
  const n = new F(r, s);
  return Tr(n), ke(n.wrapper), n.dropdown && ke(n.dropdown), r.style.display = "none", r._formieTomSelect = n, R.log("Initialized.", {
    inputName: r.name,
    multiple: t
  }), de(r, Z, "after-init", {
    combobox: n,
    options: s
  }), () => {
    n.destroy(), r.style.removeProperty("display"), Fr(r, i), delete r._formieTomSelect, R.log("Destroyed.", {
      inputName: r.name
    });
  };
}
const Vr = {
  id: Z,
  kind: "field",
  match: (r) => !!r.target.querySelector(Le),
  setup: async (r) => {
    const e = r.options || {}, t = Re(r), i = t.map((o) => {
      const s = o.querySelector(Le);
      return s instanceof HTMLSelectElement ? Dr(s, e) : (R.warn("Field missing combobox select; skipping."), () => {
      });
    });
    return R.log("Module setup.", { fieldCount: t.length }), await r.emit("formie:module:combobox:init", {
      count: i.length
    }), {
      destroy: () => {
        i.forEach((o) => {
          o();
        }), R.log("Module destroy.", { fieldCount: t.length }), r.emit("formie:module:combobox:destroy", {});
      }
    };
  }
};
export {
  Vr as comboboxModule,
  Dr as initFormieCombobox
};
