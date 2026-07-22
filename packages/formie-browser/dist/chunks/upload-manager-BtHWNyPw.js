import { r as At, a as Rt, d as pt } from "./shared-BDEKVuB5.js";
import { e as Ut } from "./styles-C3aqgtek.js";
import { l as Ot, j as Mt, o as It, p as Pe } from "./index-CZtn5KAB.js";
import { g as K, c as $ } from "./_commonjsHelpers-DaMA6jEr.js";
class Ee extends Error {
  cause;
  isNetworkError;
  request;
  constructor(e, t = null) {
    super("This looks like a network error, the endpoint might be blocked by an internet provider or a firewall."), this.cause = e, this.isNetworkError = !0, this.request = t;
  }
}
class Lt {
  #e;
  #t = !1;
  #s;
  #r;
  constructor(e, t) {
    this.#r = e, this.#s = () => t(e);
  }
  progress() {
    this.#t || this.#r > 0 && (clearTimeout(this.#e), this.#e = setTimeout(this.#s, this.#r));
  }
  done() {
    this.#t || (clearTimeout(this.#e), this.#e = void 0, this.#t = !0);
  }
}
const B = () => {
};
function Pt(i, e = {}) {
  const { body: t = null, headers: r = {}, method: s = "GET", onBeforeRequest: n = B, onUploadProgress: o = B, shouldRetry: a = () => !0, onAfterResponse: l = B, onTimeout: d = B, responseType: u, retries: h = 3, signal: b = null, timeout: S = 3e4, withCredentials: y = !1 } = e, E = (A) => 0.3 * 2 ** (A - 1) * 1e3, O = new Lt(S, d);
  function P(A = 0) {
    return new Promise(async (k, M) => {
      const w = new XMLHttpRequest(), F = (U) => {
        a(w) && A < h ? setTimeout(() => {
          P(A + 1).then(k, M);
        }, E(A)) : (O.done(), M(U));
      };
      w.open(s, i, !0), w.withCredentials = y, u && (w.responseType = u), w.onload = async () => {
        try {
          await l(w, A);
        } catch (U) {
          U.request = w, F(U);
          return;
        }
        w.status >= 200 && w.status < 300 ? (O.done(), k(w)) : a(w) && A < h ? setTimeout(() => {
          P(A + 1).then(k, M);
        }, E(A)) : (O.done(), M(new Ee(w.statusText, w)));
      }, w.onerror = () => F(new Ee(w.statusText, w)), w.upload.onprogress = (U) => {
        O.progress(), o(U);
      }, r && Object.keys(r).forEach((U) => {
        w.setRequestHeader(U, r[U]);
      });
      function v() {
        w.abort(), M(new DOMException("Aborted", "AbortError"));
      }
      if (b?.addEventListener("abort", v), b?.aborted) {
        v();
        return;
      }
      await n(w, A), w.send(t);
    });
  }
  return P();
}
const kt = (i) => "error" in i && !!i.error, Nt = (i) => i.progress.uploadComplete;
function Ct(i) {
  return i.filter((e) => !kt(e) && !Nt(e));
}
function qt(i) {
  return i.filter((e) => !e.progress?.uploadStarted || !e.isRestored);
}
function ft(i) {
  const e = i.lastIndexOf(".");
  return e === -1 || e === i.length - 1 ? {
    name: i,
    extension: void 0
  } : {
    name: i.slice(0, e),
    extension: i.slice(e + 1)
  };
}
const ke = {
  __proto__: null,
  md: "text/markdown",
  markdown: "text/markdown",
  mp4: "video/mp4",
  mp3: "audio/mp3",
  svg: "image/svg+xml",
  jpg: "image/jpeg",
  png: "image/png",
  webp: "image/webp",
  gif: "image/gif",
  heic: "image/heic",
  heif: "image/heif",
  yaml: "text/yaml",
  yml: "text/yaml",
  csv: "text/csv",
  tsv: "text/tab-separated-values",
  tab: "text/tab-separated-values",
  avi: "video/x-msvideo",
  mks: "video/x-matroska",
  mkv: "video/x-matroska",
  mov: "video/quicktime",
  dicom: "application/dicom",
  doc: "application/msword",
  msg: "application/vnd.ms-outlook",
  docm: "application/vnd.ms-word.document.macroenabled.12",
  docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  dot: "application/msword",
  dotm: "application/vnd.ms-word.template.macroenabled.12",
  dotx: "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
  xla: "application/vnd.ms-excel",
  xlam: "application/vnd.ms-excel.addin.macroenabled.12",
  xlc: "application/vnd.ms-excel",
  xlf: "application/x-xliff+xml",
  xlm: "application/vnd.ms-excel",
  xls: "application/vnd.ms-excel",
  xlsb: "application/vnd.ms-excel.sheet.binary.macroenabled.12",
  xlsm: "application/vnd.ms-excel.sheet.macroenabled.12",
  xlsx: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  xlt: "application/vnd.ms-excel",
  xltm: "application/vnd.ms-excel.template.macroenabled.12",
  xltx: "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
  xlw: "application/vnd.ms-excel",
  txt: "text/plain",
  text: "text/plain",
  conf: "text/plain",
  log: "text/plain",
  pdf: "application/pdf",
  zip: "application/zip",
  "7z": "application/x-7z-compressed",
  rar: "application/x-rar-compressed",
  tar: "application/x-tar",
  gz: "application/gzip",
  dmg: "application/x-apple-diskimage"
};
function mt(i) {
  if (i.type)
    return i.type;
  const e = i.name ? ft(i.name).extension?.toLowerCase() : null;
  return e && e in ke ? ke[e] : "application/octet-stream";
}
function _t(i) {
  return i.charCodeAt(0).toString(32);
}
function Ne(i) {
  let e = "";
  return i.replace(/[^A-Z0-9]/gi, (t) => (e += `-${_t(t)}`, "/")) + e;
}
function jt(i, e) {
  let t = e || "uppy";
  return typeof i.name == "string" && (t += `-${Ne(i.name.toLowerCase())}`), i.type !== void 0 && (t += `-${i.type}`), i.meta && typeof i.meta.relativePath == "string" && (t += `-${Ne(i.meta.relativePath.toLowerCase())}`), i.data?.size !== void 0 && (t += `-${i.data.size}`), i.data.lastModified !== void 0 && (t += `-${i.data.lastModified}`), t;
}
function zt(i) {
  return !i.isRemote || !i.remote ? !1 : (/* @__PURE__ */ new Set([
    "box",
    "dropbox",
    "drive",
    "facebook",
    "unsplash"
  ])).has(i.remote.provider);
}
function $t(i, e) {
  if (zt(i))
    return i.id;
  const t = mt(i);
  return jt({
    ...i,
    type: t
  }, e);
}
function Ce(i, e) {
  return i === !0 ? Object.keys(e) : Array.isArray(i) ? i : [];
}
function ee(i) {
  return i < 10 ? `0${i}` : i.toString();
}
function G() {
  const i = /* @__PURE__ */ new Date(), e = ee(i.getHours()), t = ee(i.getMinutes()), r = ee(i.getSeconds());
  return `${e}:${t}:${r}`;
}
function Bt(i) {
  return i ? i.readyState === 4 && i.status === 0 : !1;
}
class Ht {
  #e = [];
  #t = 0;
  #s;
  #r = !1;
  constructor(e) {
    const t = e?.concurrency;
    this.#s = typeof t != "number" || t === 0 ? 1 / 0 : t;
  }
  /**
   * Add a task to the queue.
   *
   * @param task - Function receiving AbortSignal, returns Promise
   * @returns AbortablePromise that resolves with task result
   */
  add(e) {
    const t = new AbortController();
    let r, s;
    const n = new Promise((a, l) => {
      r = a, s = l;
    }), o = {
      run: () => e(t.signal),
      resolve: r,
      reject: s,
      controller: t
    };
    return t.signal.addEventListener("abort", () => {
      const a = this.#e.indexOf(o);
      a !== -1 && (this.#e.splice(a, 1), s(t.signal.reason ?? new DOMException("Aborted", "AbortError")));
    }, { once: !0 }), n.abort = (a) => {
      t.abort(a ?? new DOMException("Aborted", "AbortError"));
    }, n.abortOn = (a) => {
      if (a) {
        const l = () => n.abort(a.reason);
        a.addEventListener("abort", l, { once: !0 }), n.then(() => a.removeEventListener("abort", l), () => a.removeEventListener("abort", l));
      }
      return n;
    }, !this.#r && this.#t < this.#s ? this.#o(o) : this.#e.push(o), n;
  }
  #o(e) {
    if (this.#t++, e.controller.signal.aborted) {
      this.#t--, e.reject(e.controller.signal.reason ?? new DOMException("Aborted", "AbortError")), this.#i();
      return;
    }
    let t;
    try {
      t = e.run();
    } catch (r) {
      t = Promise.reject(r);
    }
    t.then((r) => {
      e.controller.signal.aborted ? e.reject(e.controller.signal.reason ?? new DOMException("Aborted", "AbortError")) : e.resolve(r);
    }, (r) => {
      e.reject(r);
    }).finally(() => {
      this.#t--, this.#i();
    });
  }
  #i() {
    queueMicrotask(() => {
      if (!(this.#r || this.#t >= this.#s))
        for (; this.#e.length > 0; ) {
          const e = this.#e.shift();
          if (!e.controller.signal.aborted) {
            this.#o(e);
            return;
          }
        }
    });
  }
  /**
   * Pause the queue. Running tasks continue, but no new tasks start.
   */
  pause() {
    this.#r = !0;
  }
  /**
   * Resume the queue and start processing pending tasks.
   */
  resume() {
    this.#r = !1;
    const e = this.#s - this.#t;
    for (let t = 0; t < e; t++)
      this.#i();
  }
  /**
   * Clear all pending tasks from the queue.
   * Running tasks are not affected.
   *
   * @param reason - Optional reason for rejection (defaults to AbortError)
   */
  clear(e) {
    const t = this.#e.splice(0), r = e ?? new DOMException("Cleared", "AbortError");
    for (const s of t)
      s.controller.abort(r), s.reject(r);
  }
  get concurrency() {
    return this.#s;
  }
  set concurrency(e) {
    if (this.#s = typeof e != "number" || e === 0 ? 1 / 0 : e, !this.#r) {
      const t = this.#s - this.#t;
      for (let r = 0; r < t; r++)
        this.#i();
    }
  }
  get pending() {
    return this.#e.length;
  }
  get running() {
    return this.#t;
  }
  get isPaused() {
    return this.#r;
  }
  /**
   * @deprecated Legacy compatibility wrapper for RateLimitedQueue API.
   * Wraps a function so that when called, it's queued and returns an AbortablePromise.
   * Note: for legacy compatibility with RateLimitedQueue, the wrapped function
   * does not receive this queue's AbortSignal. Aborting the returned promise
   * will reject it, but it will not automatically cancel work inside the wrapped
   * function unless that function is wired to an external AbortSignal.
   */
  wrapPromiseFunction(e) {
    return (...t) => this.add((r) => e(...t));
  }
}
function Dt(i, e, t) {
  const r = [];
  return i.forEach((s) => typeof s != "string" ? r.push(s) : e[Symbol.split](s).forEach((n, o, a) => {
    n !== "" && r.push(n), o < a.length - 1 && r.push(t);
  })), r;
}
function qe(i, e) {
  const t = /\$/g, r = "$$$$";
  let s = [i];
  if (e == null)
    return s;
  for (const n of Object.keys(e))
    if (n !== "_") {
      let o = e[n];
      typeof o == "string" && (o = t[Symbol.replace](o, r)), s = Dt(s, new RegExp(`%\\{${n}\\}`, "g"), o);
    }
  return s;
}
const Wt = (i) => {
  throw new Error(`missing string: ${i}`);
};
class ht {
  locale;
  constructor(e, { onMissingKey: t = Wt } = {}) {
    this.locale = {
      strings: {},
      pluralize(r) {
        return r === 1 ? 0 : 1;
      }
    }, Array.isArray(e) ? e.forEach(this.#t, this) : this.#t(e), this.#e = t;
  }
  #e;
  #t(e) {
    if (!e?.strings)
      return;
    const t = this.locale;
    Object.assign(this.locale, {
      strings: { ...t.strings, ...e.strings },
      pluralize: e.pluralize || t.pluralize
    });
  }
  /**
   * Public translate method
   *
   * @param key
   * @param options with values that will be used later to replace placeholders in string
   * @returns string translated (and interpolated)
   */
  translate(e, t) {
    return this.translateArray(e, t).join("");
  }
  /**
   * Get a translation and return the translated and interpolated parts as an array.
   *
   * @returns The translated and interpolated parts, in order.
   */
  translateArray(e, t) {
    let r = this.locale.strings[e];
    if (r == null && (this.#e(e), r = e), typeof r == "object") {
      if (t && typeof t.smart_count < "u") {
        const n = this.locale.pluralize(t.smart_count);
        return qe(r[n], t);
      }
      throw new Error("Attempted to use a string with plural forms, but no value was given for %{smart_count}");
    }
    if (typeof r != "string")
      throw new Error("string was not a string");
    return qe(r, t);
  }
}
class Gt {
  uppy;
  opts;
  id;
  defaultLocale;
  i18n;
  i18nArray;
  type;
  VERSION;
  constructor(e, t) {
    this.uppy = e, this.opts = t ?? {};
  }
  getPluginState() {
    const { plugins: e } = this.uppy.getState();
    return e?.[this.id] || {};
  }
  setPluginState(e) {
    const { plugins: t } = this.uppy.getState();
    this.uppy.setState({
      plugins: {
        ...t,
        [this.id]: {
          ...t[this.id],
          ...e
        }
      }
    });
  }
  setOptions(e) {
    this.opts = { ...this.opts, ...e }, this.setPluginState(void 0), this.i18nInit();
  }
  i18nInit() {
    const e = new ht([
      this.defaultLocale,
      this.uppy.locale,
      this.opts.locale
    ]);
    this.i18n = e.translate.bind(e), this.i18nArray = e.translateArray.bind(e), this.setPluginState(void 0);
  }
  /**
   * Extendable methods
   * ==================
   * These methods are here to serve as an overview of the extendable methods as well as
   * making them not conditional in use, such as `if (this.afterUpdate)`.
   */
  addTarget(e) {
    throw new Error("Extend the addTarget method to add your plugin to another plugin's target");
  }
  install() {
  }
  uninstall() {
  }
  update(e) {
  }
  // Called after every state update, after everything's mounted. Debounced.
  afterUpdate() {
  }
}
class Vt {
  #e;
  #t = [];
  constructor(e) {
    this.#e = e;
  }
  on(e, t) {
    return this.#t.push([e, t]), this.#e.on(e, t);
  }
  remove() {
    for (const [e, t] of this.#t.splice(0))
      this.#e.off(e, t);
  }
  onFilePause(e, t) {
    this.on("upload-pause", (r, s) => {
      e === r?.id && t(s);
    });
  }
  onFileRemove(e, t) {
    this.on("file-removed", (r) => {
      e === r.id && t(r.id);
    });
  }
  onPause(e, t) {
    this.on("upload-pause", (r, s) => {
      e === r?.id && t(s);
    });
  }
  onRetry(e, t) {
    this.on("upload-retry", (r) => {
      e === r?.id && t();
    });
  }
  onRetryAll(e, t) {
    this.on("retry-all", () => {
      this.#e.getFile(e) && t();
    });
  }
  onPauseAll(e, t) {
    this.on("pause-all", () => {
      this.#e.getFile(e) && t();
    });
  }
  onCancelAll(e, t) {
    this.on("cancel-all", (...r) => {
      this.#e.getFile(e) && t(...r);
    });
  }
  onResumeAll(e, t) {
    this.on("resume-all", () => {
      this.#e.getFile(e) && t();
    });
  }
}
const Xt = {
  debug: () => {
  },
  warn: () => {
  },
  error: (...i) => console.error(`[Uppy] [${G()}]`, ...i)
}, Yt = {
  debug: (...i) => console.debug(`[Uppy] [${G()}]`, ...i),
  warn: (...i) => console.warn(`[Uppy] [${G()}]`, ...i),
  error: (...i) => console.error(`[Uppy] [${G()}]`, ...i)
};
var te, _e;
function Jt() {
  return _e || (_e = 1, te = function(e) {
    if (typeof e != "number" || Number.isNaN(e))
      throw new TypeError(`Expected a number, got ${typeof e}`);
    const t = e < 0;
    let r = Math.abs(e);
    if (t && (r = -r), r === 0)
      return "0 B";
    const s = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"], n = Math.min(Math.floor(Math.log(r) / Math.log(1024)), s.length - 1), o = Number(r / 1024 ** n), a = s[n];
    return `${o >= 10 || o % 1 === 0 ? Math.round(o) : o.toFixed(1)} ${a}`;
  }), te;
}
var Kt = Jt();
const H = /* @__PURE__ */ K(Kt);
var re, je;
function Zt() {
  if (je) return re;
  je = 1;
  function i(e, t) {
    this.text = e = e || "", this.hasWild = ~e.indexOf("*"), this.separator = t, this.parts = e.split(t);
  }
  return i.prototype.match = function(e) {
    var t = !0, r = this.parts, s, n = r.length, o;
    if (typeof e == "string" || e instanceof String)
      if (!this.hasWild && this.text != e)
        t = !1;
      else {
        for (o = (e || "").split(this.separator), s = 0; t && s < n; s++)
          r[s] !== "*" && (s < o.length ? t = r[s] === o[s] : t = !1);
        t = t && o;
      }
    else if (typeof e.splice == "function")
      for (t = [], s = e.length; s--; )
        this.match(e[s]) && (t[t.length] = e[s]);
    else if (typeof e == "object") {
      t = {};
      for (var a in e)
        this.match(a) && (t[a] = e[a]);
    }
    return t;
  }, re = function(e, t, r) {
    var s = new i(e, r || /[\/\.]/);
    return typeof t < "u" ? s.match(t) : s;
  }, re;
}
var se, ze;
function Qt() {
  if (ze) return se;
  ze = 1;
  var i = Zt(), e = /[\/\+\.]/;
  return se = function(t, r) {
    function s(n) {
      var o = i(n, t, e);
      return o && o.length >= 2;
    }
    return r ? s(r.split(";")[0]) : s;
  }, se;
}
var er = Qt();
const tr = /* @__PURE__ */ K(er), rr = {
  maxFileSize: null,
  minFileSize: null,
  maxTotalFileSize: null,
  maxNumberOfFiles: null,
  minNumberOfFiles: null,
  allowedFileTypes: null,
  requiredMetaFields: []
};
class I extends Error {
  isUserFacing;
  file;
  constructor(e, t) {
    super(e), this.isUserFacing = t?.isUserFacing ?? !0, t?.file && (this.file = t.file);
  }
  isRestriction = !0;
}
class sr {
  getI18n;
  getOpts;
  constructor(e, t) {
    this.getI18n = t, this.getOpts = () => {
      const r = e();
      if (r.restrictions?.allowedFileTypes != null && !Array.isArray(r.restrictions.allowedFileTypes))
        throw new TypeError("`restrictions.allowedFileTypes` must be an array");
      return r;
    };
  }
  // Because these operations are slow, we cannot run them for every file (if we are adding multiple files)
  validateAggregateRestrictions(e, t) {
    const { maxTotalFileSize: r, maxNumberOfFiles: s } = this.getOpts().restrictions;
    if (s && e.filter((o) => !o.isGhost).length + t.length > s)
      throw new I(`${this.getI18n()("youCanOnlyUploadX", {
        smart_count: s
      })}`);
    if (r) {
      const n = [...e, ...t].reduce((o, a) => o + (a.size ?? 0), 0);
      if (n > r)
        throw new I(this.getI18n()("aggregateExceedsSize", {
          sizeAllowed: H(r),
          size: H(n)
        }));
    }
  }
  validateSingleFile(e) {
    const { maxFileSize: t, minFileSize: r, allowedFileTypes: s } = this.getOpts().restrictions;
    if (s && !s.some((o) => o.includes("/") ? e.type ? tr(e.type.replace(/;.*?$/, ""), o) : !1 : o[0] === "." && e.extension ? e.extension.toLowerCase() === o.slice(1).toLowerCase() : !1)) {
      const o = s.join(", ");
      throw new I(this.getI18n()("youCanOnlyUploadFileTypes", {
        types: o
      }), { file: e });
    }
    if (t && e.size != null && e.size > t)
      throw new I(this.getI18n()("exceedsSize", {
        size: H(t),
        file: e.name ?? this.getI18n()("unnamed")
      }), { file: e });
    if (r && e.size != null && e.size < r)
      throw new I(this.getI18n()("inferiorSize", {
        size: H(r)
      }), { file: e });
  }
  validate(e, t) {
    t.forEach((r) => {
      this.validateSingleFile(r);
    }), this.validateAggregateRestrictions(e, t);
  }
  validateMinNumberOfFiles(e) {
    const { minNumberOfFiles: t } = this.getOpts().restrictions;
    if (t && Object.keys(e).length < t)
      throw new I(this.getI18n()("youHaveToAtLeastSelectX", {
        smart_count: t
      }));
  }
  getMissingRequiredMetaFields(e) {
    const t = new I(this.getI18n()("missingRequiredMetaFieldOnFile", {
      fileName: e.name ?? this.getI18n()("unnamed")
    })), { requiredMetaFields: r } = this.getOpts().restrictions, s = [];
    for (const n of r)
      (!Object.hasOwn(e.meta, n) || e.meta[n] === "") && s.push(n);
    return { missingFields: s, error: t };
  }
}
const ir = "5.0.0", or = {
  version: ir
};
class nr {
  static VERSION = or.version;
  state = {};
  #e = /* @__PURE__ */ new Set();
  getState() {
    return this.state;
  }
  setState(e) {
    const t = { ...this.state }, r = { ...this.state, ...e };
    this.state = r, this.#t(t, r, e);
  }
  subscribe(e) {
    return this.#e.add(e), () => {
      this.#e.delete(e);
    };
  }
  #t(...e) {
    this.#e.forEach((t) => {
      t(...e);
    });
  }
}
var ie, $e;
function Re() {
  if ($e) return ie;
  $e = 1;
  function i(e) {
    var t = typeof e;
    return e != null && (t == "object" || t == "function");
  }
  return ie = i, ie;
}
var oe, Be;
function ar() {
  if (Be) return oe;
  Be = 1;
  var i = typeof $ == "object" && $ && $.Object === Object && $;
  return oe = i, oe;
}
var ne, He;
function gt() {
  if (He) return ne;
  He = 1;
  var i = ar(), e = typeof self == "object" && self && self.Object === Object && self, t = i || e || Function("return this")();
  return ne = t, ne;
}
var ae, De;
function lr() {
  if (De) return ae;
  De = 1;
  var i = gt(), e = function() {
    return i.Date.now();
  };
  return ae = e, ae;
}
var le, We;
function dr() {
  if (We) return le;
  We = 1;
  var i = /\s/;
  function e(t) {
    for (var r = t.length; r-- && i.test(t.charAt(r)); )
      ;
    return r;
  }
  return le = e, le;
}
var de, Ge;
function ur() {
  if (Ge) return de;
  Ge = 1;
  var i = dr(), e = /^\s+/;
  function t(r) {
    return r && r.slice(0, i(r) + 1).replace(e, "");
  }
  return de = t, de;
}
var ue, Ve;
function bt() {
  if (Ve) return ue;
  Ve = 1;
  var i = gt(), e = i.Symbol;
  return ue = e, ue;
}
var ce, Xe;
function cr() {
  if (Xe) return ce;
  Xe = 1;
  var i = bt(), e = Object.prototype, t = e.hasOwnProperty, r = e.toString, s = i ? i.toStringTag : void 0;
  function n(o) {
    var a = t.call(o, s), l = o[s];
    try {
      o[s] = void 0;
      var d = !0;
    } catch {
    }
    var u = r.call(o);
    return d && (a ? o[s] = l : delete o[s]), u;
  }
  return ce = n, ce;
}
var pe, Ye;
function pr() {
  if (Ye) return pe;
  Ye = 1;
  var i = Object.prototype, e = i.toString;
  function t(r) {
    return e.call(r);
  }
  return pe = t, pe;
}
var fe, Je;
function fr() {
  if (Je) return fe;
  Je = 1;
  var i = bt(), e = cr(), t = pr(), r = "[object Null]", s = "[object Undefined]", n = i ? i.toStringTag : void 0;
  function o(a) {
    return a == null ? a === void 0 ? s : r : n && n in Object(a) ? e(a) : t(a);
  }
  return fe = o, fe;
}
var me, Ke;
function mr() {
  if (Ke) return me;
  Ke = 1;
  function i(e) {
    return e != null && typeof e == "object";
  }
  return me = i, me;
}
var he, Ze;
function hr() {
  if (Ze) return he;
  Ze = 1;
  var i = fr(), e = mr(), t = "[object Symbol]";
  function r(s) {
    return typeof s == "symbol" || e(s) && i(s) == t;
  }
  return he = r, he;
}
var ge, Qe;
function gr() {
  if (Qe) return ge;
  Qe = 1;
  var i = ur(), e = Re(), t = hr(), r = NaN, s = /^[-+]0x[0-9a-f]+$/i, n = /^0b[01]+$/i, o = /^0o[0-7]+$/i, a = parseInt;
  function l(d) {
    if (typeof d == "number")
      return d;
    if (t(d))
      return r;
    if (e(d)) {
      var u = typeof d.valueOf == "function" ? d.valueOf() : d;
      d = e(u) ? u + "" : u;
    }
    if (typeof d != "string")
      return d === 0 ? d : +d;
    d = i(d);
    var h = n.test(d);
    return h || o.test(d) ? a(d.slice(2), h ? 2 : 8) : s.test(d) ? r : +d;
  }
  return ge = l, ge;
}
var be, et;
function br() {
  if (et) return be;
  et = 1;
  var i = Re(), e = lr(), t = gr(), r = "Expected a function", s = Math.max, n = Math.min;
  function o(a, l, d) {
    var u, h, b, S, y, E, O = 0, P = !1, A = !1, k = !0;
    if (typeof a != "function")
      throw new TypeError(r);
    l = t(l) || 0, i(d) && (P = !!d.leading, A = "maxWait" in d, b = A ? s(t(d.maxWait) || 0, l) : b, k = "trailing" in d ? !!d.trailing : k);
    function M(T) {
      var L = u, C = h;
      return u = h = void 0, O = T, S = a.apply(C, L), S;
    }
    function w(T) {
      return O = T, y = setTimeout(U, l), P ? M(T) : S;
    }
    function F(T) {
      var L = T - E, C = T - O, z = l - L;
      return A ? n(z, b - C) : z;
    }
    function v(T) {
      var L = T - E, C = T - O;
      return E === void 0 || L >= l || L < 0 || A && C >= b;
    }
    function U() {
      var T = e();
      if (v(T))
        return _(T);
      y = setTimeout(U, F(T));
    }
    function _(T) {
      return y = void 0, k && u ? M(T) : (u = h = void 0, S);
    }
    function Z() {
      y !== void 0 && clearTimeout(y), O = 0, u = E = h = y = void 0;
    }
    function N() {
      return y === void 0 ? S : _(e());
    }
    function q() {
      var T = e(), L = v(T);
      if (u = arguments, h = this, E = T, L) {
        if (y === void 0)
          return w(E);
        if (A)
          return clearTimeout(y), y = setTimeout(U, l), M(E);
      }
      return y === void 0 && (y = setTimeout(U, l)), S;
    }
    return q.cancel = Z, q.flush = N, q;
  }
  return be = o, be;
}
var ye, tt;
function yr() {
  if (tt) return ye;
  tt = 1;
  var i = br(), e = Re(), t = "Expected a function";
  function r(s, n, o) {
    var a = !0, l = !0;
    if (typeof s != "function")
      throw new TypeError(t);
    return e(o) && (a = "leading" in o ? !!o.leading : a, l = "trailing" in o ? !!o.trailing : l), i(s, n, {
      leading: a,
      maxWait: n,
      trailing: l
    });
  }
  return ye = r, ye;
}
var vr = yr();
const wr = /* @__PURE__ */ K(vr);
var ve, rt;
function Sr() {
  return rt || (rt = 1, ve = function() {
    var e = {}, t = e._fns = {};
    e.emit = function(o, a, l, d, u, h, b) {
      var S = r(o);
      S.length && s(o, S, [a, l, d, u, h, b]);
    }, e.on = function(o, a) {
      t[o] || (t[o] = []), t[o].push(a);
    }, e.once = function(o, a) {
      function l() {
        a.apply(this, arguments), e.off(o, l);
      }
      this.on(o, l);
    }, e.off = function(o, a) {
      var l = [];
      if (o && a) {
        var d = this._fns[o], u = 0, h = d ? d.length : 0;
        for (u; u < h; u++)
          d[u] !== a && l.push(d[u]);
      }
      l.length ? this._fns[o] = l : delete this._fns[o];
    };
    function r(n) {
      var o = t[n] ? t[n] : [], a = n.indexOf(":"), l = a === -1 ? [n] : [n.substring(0, a), n.substring(a + 1)], d = Object.keys(t), u = 0, h = d.length;
      for (u; u < h; u++) {
        var b = d[u];
        if (b === "*" && (o = o.concat(t[b])), l.length === 2 && l[0] === b) {
          o = o.concat(t[b]);
          break;
        }
      }
      return o;
    }
    function s(n, o, a) {
      var l = 0, d = o.length;
      for (l; l < d && o[l]; l++)
        o[l].event = n, o[l].apply(o[l], a);
    }
    return e;
  }), ve;
}
var Er = Sr();
const Fr = /* @__PURE__ */ K(Er);
let Tr = "useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict", xr = (i = 21) => {
  let e = "", t = i | 0;
  for (; t--; )
    e += Tr[Math.random() * 64 | 0];
  return e;
};
const Ar = "5.2.0", Rr = {
  version: Ar
};
function Ur(i, e) {
  return e.name ? e.name : i.split("/")[0] === "image" ? `${i.split("/")[0]}.${i.split("/")[1]}` : "noname";
}
const Or = {
  strings: {
    addBulkFilesFailed: {
      0: "Failed to add %{smart_count} file due to an internal error",
      1: "Failed to add %{smart_count} files due to internal errors"
    },
    youCanOnlyUploadX: {
      0: "You can only upload %{smart_count} file",
      1: "You can only upload %{smart_count} files"
    },
    youHaveToAtLeastSelectX: {
      0: "You have to select at least %{smart_count} file",
      1: "You have to select at least %{smart_count} files"
    },
    aggregateExceedsSize: "You selected %{size} of files, but maximum allowed size is %{sizeAllowed}",
    exceedsSize: "%{file} exceeds maximum allowed size of %{size}",
    missingRequiredMetaField: "Missing required meta fields",
    missingRequiredMetaFieldOnFile: "Missing required meta fields in %{fileName}",
    inferiorSize: "This file is smaller than the allowed size of %{size}",
    youCanOnlyUploadFileTypes: "You can only upload: %{types}",
    noMoreFilesAllowed: "Cannot add more files",
    noDuplicates: "Cannot add the duplicate file '%{fileName}', it already exists",
    companionError: "Connection with Companion failed",
    authAborted: "Authentication aborted",
    companionUnauthorizeHint: "To unauthorize to your %{provider} account, please go to %{url}",
    failedToUpload: "Failed to upload %{file}",
    noInternetConnection: "No Internet connection",
    connectedToInternet: "Connected to the Internet",
    // Strings for remote providers
    noFilesFound: "You have no files or folders here",
    noSearchResults: "Unfortunately, there are no results for this search",
    selectX: {
      0: "Select %{smart_count}",
      1: "Select %{smart_count}"
    },
    allFilesFromFolderNamed: "All files from folder %{name}",
    openFolderNamed: "Open folder %{name}",
    cancel: "Cancel",
    logOut: "Log out",
    logIn: "Log in",
    pickFiles: "Pick files",
    pickPhotos: "Pick photos",
    filter: "Filter",
    resetFilter: "Reset filter",
    loading: "Loading...",
    loadedXFiles: "Loaded %{numFiles} files",
    authenticateWithTitle: "Please authenticate with %{pluginName} to select files",
    authenticateWith: "Connect to %{pluginName}",
    signInWithGoogle: "Sign in with Google",
    searchImages: "Search for images",
    enterTextToSearch: "Enter text to search for images",
    search: "Search",
    resetSearch: "Reset search",
    emptyFolderAdded: "No files were added from empty folder",
    addedNumFiles: "Added %{numFiles} file(s)",
    folderAlreadyAdded: 'The folder "%{folder}" was already added',
    folderAdded: {
      0: "Added %{smart_count} file from %{folder}",
      1: "Added %{smart_count} files from %{folder}"
    },
    additionalRestrictionsFailed: "%{count} additional restrictions were not fulfilled",
    unnamed: "Unnamed",
    pleaseWait: "Please wait"
  }
};
function Mr(i) {
  if (i == null && typeof navigator < "u" && (i = navigator.userAgent), !i)
    return !0;
  const e = /Edge\/(\d+\.\d+)/.exec(i);
  if (!e)
    return !0;
  const r = e[1].split(".", 2), s = parseInt(r[0], 10), n = parseInt(r[1], 10);
  return s < 15 || s === 15 && n < 15063 || s > 18 || s === 18 && n >= 18218;
}
const D = {
  totalProgress: 0,
  allowNewUpload: !0,
  error: null,
  recoveredState: null
};
class Ue {
  static VERSION = Rr.version;
  #e = /* @__PURE__ */ Object.create(null);
  #t;
  #s;
  #r = Fr();
  #o = /* @__PURE__ */ new Set();
  #i = /* @__PURE__ */ new Set();
  #n = /* @__PURE__ */ new Set();
  defaultLocale;
  locale;
  // The user optionally passes in options, but we set defaults for missing options.
  // We consider all options present after the contructor has run.
  opts;
  store;
  // Warning: do not use this from a plugin, as it will cause the plugins' translations to be missing
  i18n;
  i18nArray;
  scheduledAutoProceed = null;
  wasOffline = !1;
  /**
   * Instantiate Uppy
   */
  constructor(e) {
    this.defaultLocale = Or;
    const t = {
      id: "uppy",
      autoProceed: !1,
      allowMultipleUploadBatches: !0,
      debug: !1,
      restrictions: rr,
      meta: {},
      onBeforeFileAdded: (s, n) => !Object.hasOwn(n, s.id),
      onBeforeUpload: (s) => s,
      store: new nr(),
      logger: Xt,
      infoTimeout: 5e3
    }, r = { ...t, ...e };
    this.opts = {
      ...r,
      restrictions: {
        ...t.restrictions,
        ...e?.restrictions
      }
    }, e?.logger && e.debug ? this.log("You are using a custom `logger`, but also set `debug: true`, which uses built-in logger to output logs to console. Ignoring `debug: true` and using your custom `logger`.", "warning") : e?.debug && (this.opts.logger = Yt), this.log(`Using Core v${Ue.VERSION}`), this.i18nInit(), this.store = this.opts.store, this.setState({
      ...D,
      plugins: {},
      files: {},
      currentUploads: {},
      capabilities: {
        uploadProgress: Mr(),
        individualCancellation: !0,
        resumableUploads: !1
      },
      meta: { ...this.opts.meta },
      info: []
    }), this.#t = new sr(() => this.opts, () => this.i18n), this.#s = this.store.subscribe((s, n, o) => {
      this.emit("state-update", s, n, o), this.updateAll(n);
    }), this.opts.debug && typeof window < "u" && (window[this.opts.id] = this), this.#x();
  }
  emit(e, ...t) {
    this.#r.emit(e, ...t);
  }
  on(e, t) {
    return this.#r.on(e, t), this;
  }
  once(e, t) {
    return this.#r.once(e, t), this;
  }
  off(e, t) {
    return this.#r.off(e, t), this;
  }
  /**
   * Iterate on all plugins and run `update` on them.
   * Called each time state changes.
   *
   */
  updateAll(e) {
    this.iteratePlugins((t) => {
      t.update(e);
    });
  }
  /**
   * Updates state with a patch
   */
  setState(e) {
    this.store.setState(e);
  }
  /**
   * Returns current state.
   */
  getState() {
    return this.store.getState();
  }
  patchFilesState(e) {
    const t = this.getState().files;
    this.setState({
      files: {
        ...t,
        ...Object.fromEntries(Object.entries(e).map(([r, s]) => [
          r,
          {
            ...t[r],
            ...s
          }
        ]))
      }
    });
  }
  /**
   * Shorthand to set state for a specific file.
   */
  setFileState(e, t) {
    if (!this.getState().files[e])
      throw new Error(`Can’t set state for ${e} (the file could have been removed)`);
    this.patchFilesState({ [e]: t });
  }
  i18nInit() {
    const e = (r) => this.log(`Missing i18n string: ${r}`, "error"), t = new ht([this.defaultLocale, this.opts.locale], {
      onMissingKey: e
    });
    this.i18n = t.translate.bind(t), this.i18nArray = t.translateArray.bind(t), this.locale = t.locale;
  }
  setOptions(e) {
    this.opts = {
      ...this.opts,
      ...e,
      restrictions: {
        ...this.opts.restrictions,
        ...e?.restrictions
      }
    }, e.meta && this.setMeta(e.meta), this.i18nInit(), e.locale && this.iteratePlugins((t) => {
      t.setOptions(e);
    }), this.setState(void 0);
  }
  resetProgress() {
    const e = {
      percentage: 0,
      bytesUploaded: !1,
      uploadComplete: !1,
      uploadStarted: null
    }, t = { ...this.getState().files }, r = /* @__PURE__ */ Object.create(null);
    Object.keys(t).forEach((s) => {
      r[s] = {
        ...t[s],
        progress: {
          ...t[s].progress,
          ...e
        },
        // @ts-expect-error these typed are inserted
        // into the namespace in their respective packages
        // but core isn't ware of those
        tus: void 0,
        transloadit: void 0
      };
    }), this.setState({ files: r, ...D });
  }
  clear() {
    const { capabilities: e, currentUploads: t } = this.getState();
    if (Object.keys(t).length > 0 && !e.individualCancellation)
      throw new Error("The installed uploader plugin does not allow removing files during an upload.");
    this.setState({ ...D, files: {} });
  }
  addPreProcessor(e) {
    this.#o.add(e);
  }
  removePreProcessor(e) {
    return this.#o.delete(e);
  }
  addPostProcessor(e) {
    this.#n.add(e);
  }
  removePostProcessor(e) {
    return this.#n.delete(e);
  }
  addUploader(e) {
    this.#i.add(e);
  }
  removeUploader(e) {
    return this.#i.delete(e);
  }
  setMeta(e) {
    const t = { ...this.getState().meta, ...e }, r = { ...this.getState().files };
    Object.keys(r).forEach((s) => {
      r[s] = {
        ...r[s],
        meta: { ...r[s].meta, ...e }
      };
    }), this.log("Adding metadata:"), this.log(e), this.setState({
      meta: t,
      files: r
    });
  }
  setFileMeta(e, t) {
    const r = { ...this.getState().files };
    if (!r[e]) {
      this.log(`Was trying to set metadata for a file that has been removed: ${e}`);
      return;
    }
    const s = { ...r[e].meta, ...t };
    r[e] = { ...r[e], meta: s }, this.setState({ files: r });
  }
  /**
   * Get a file object.
   */
  getFile(e) {
    return this.getState().files[e];
  }
  /**
   * Get all files in an array.
   */
  getFiles() {
    const { files: e } = this.getState();
    return Object.values(e);
  }
  getFilesByIds(e) {
    return e.map((t) => this.getFile(t));
  }
  getObjectOfFilesPerState() {
    const { files: e, totalProgress: t, error: r } = this.getState(), s = Object.values(e), n = [], o = [], a = [], l = [], d = [], u = [], h = [], b = [], S = [];
    for (const y of s) {
      const { progress: E } = y;
      !E.uploadComplete && E.uploadStarted && (n.push(y), y.isPaused || b.push(y)), E.uploadStarted || o.push(y), (E.uploadStarted || E.preprocess || E.postprocess) && a.push(y), E.uploadStarted && l.push(y), y.isPaused && d.push(y), E.uploadComplete && u.push(y), y.error && h.push(y), (E.preprocess || E.postprocess) && S.push(y);
    }
    return {
      newFiles: o,
      startedFiles: a,
      uploadStartedFiles: l,
      pausedFiles: d,
      completeFiles: u,
      erroredFiles: h,
      inProgressFiles: n,
      inProgressNotPausedFiles: b,
      processingFiles: S,
      isUploadStarted: l.length > 0,
      isAllComplete: t === 100 && u.length === s.length && S.length === 0,
      isAllErrored: !!r && h.length === s.length,
      isAllPaused: n.length !== 0 && d.length === n.length,
      isUploadInProgress: n.length > 0,
      isSomeGhost: s.some((y) => y.isGhost)
    };
  }
  #a(e) {
    for (const o of e)
      o.isRestriction ? this.emit("restriction-failed", o.file, o) : this.emit("error", o, o.file), this.log(o, "warning");
    const t = e.filter((o) => o.isUserFacing), r = 4, s = t.slice(0, r), n = t.slice(r);
    s.forEach(({ message: o, details: a = "" }) => {
      this.info({ message: o, details: a }, "error", this.opts.infoTimeout);
    }), n.length > 0 && this.info({
      message: this.i18n("additionalRestrictionsFailed", {
        count: n.length
      })
    });
  }
  validateRestrictions(e, t = this.getFiles()) {
    try {
      this.#t.validate(t, [e]);
    } catch (r) {
      return r;
    }
    return null;
  }
  validateSingleFile(e) {
    try {
      this.#t.validateSingleFile(e);
    } catch (t) {
      return t.message;
    }
    return null;
  }
  validateAggregateRestrictions(e) {
    const t = this.getFiles();
    try {
      this.#t.validateAggregateRestrictions(t, e);
    } catch (r) {
      return r.message;
    }
    return null;
  }
  #p(e) {
    const { missingFields: t, error: r } = this.#t.getMissingRequiredMetaFields(e);
    return t.length > 0 ? (this.setFileState(e.id, {
      missingRequiredMetaFields: t,
      error: r.message
    }), this.log(r.message), this.emit("restriction-failed", e, r), !1) : (t.length === 0 && e.missingRequiredMetaFields && this.setFileState(e.id, {
      missingRequiredMetaFields: []
    }), !0);
  }
  #w(e) {
    let t = !0;
    for (const r of Object.values(e))
      this.#p(r) || (t = !1);
    return t;
  }
  #S(e) {
    const { allowNewUpload: t } = this.getState();
    if (t === !1) {
      const r = new I(this.i18n("noMoreFilesAllowed"), {
        file: e
      });
      throw this.#a([r]), r;
    }
  }
  checkIfFileAlreadyExists(e) {
    const { files: t } = this.getState();
    return !!(t[e] && !t[e].isGhost);
  }
  /**
   * Create a file state object based on user-provided `addFile()` options.
   */
  #E(e) {
    const t = e instanceof File ? {
      name: e.name,
      type: e.type,
      size: e.size,
      data: e,
      meta: {},
      isRemote: !1,
      source: void 0,
      preview: void 0
    } : e, r = mt(t), s = Ur(r, t), n = ft(s).extension, o = $t(t, this.getID()), a = {
      ...t.meta,
      name: s,
      type: r
    }, l = Number.isFinite(t.data.size) ? t.data.size : null;
    return {
      source: t.source || "",
      id: o,
      name: s,
      extension: n || "",
      meta: {
        ...this.getState().meta,
        ...a
      },
      type: r,
      progress: {
        percentage: 0,
        bytesUploaded: !1,
        bytesTotal: l,
        uploadComplete: !1,
        uploadStarted: null
      },
      size: l,
      isGhost: !1,
      ...t.isRemote ? {
        isRemote: !0,
        remote: t.remote,
        data: t.data
      } : {
        isRemote: !1,
        data: t.data
      },
      preview: t.preview
    };
  }
  // Schedule an upload if `autoProceed` is enabled.
  #f() {
    this.opts.autoProceed && !this.scheduledAutoProceed && (this.scheduledAutoProceed = setTimeout(() => {
      this.scheduledAutoProceed = null, this.upload().catch((e) => {
        e.isRestriction || this.log(e.stack || e.message || e);
      });
    }, 4));
  }
  #m(e) {
    let { files: t } = this.getState(), r = { ...t };
    const s = [], n = [];
    for (const o of e)
      try {
        let a = this.#E(o);
        this.#S(a);
        const l = t[a.id], d = l?.isGhost;
        if (d && !a.isRemote) {
          if (a.data == null)
            throw new Error("File data is missing");
          a = {
            ...l,
            isGhost: !1,
            data: a.data
          }, this.log(`Replaced the blob in the restored ghost file: ${a.name}, ${a.id}`);
        }
        const u = this.opts.onBeforeFileAdded(a, r);
        if (t = this.getState().files, r = { ...t, ...r }, !u && this.checkIfFileAlreadyExists(a.id))
          throw new I(this.i18n("noDuplicates", {
            fileName: a.name ?? this.i18n("unnamed")
          }), { file: a });
        if (u === !1 && !d)
          throw new I("Cannot add the file because onBeforeFileAdded returned false.", { isUserFacing: !1, file: a });
        typeof u == "object" && u !== null && (a = u), this.#t.validateSingleFile(a), r[a.id] = a, s.push(a);
      } catch (a) {
        n.push(a);
      }
    try {
      this.#t.validateAggregateRestrictions(Object.values(t), s);
    } catch (o) {
      return n.push(o), {
        nextFilesState: t,
        validFilesToAdd: [],
        errors: n
      };
    }
    return {
      nextFilesState: r,
      validFilesToAdd: s,
      errors: n
    };
  }
  /**
   * Add a new file to `state.files`. This will run `onBeforeFileAdded`,
   * try to guess file type in a clever way, check file against restrictions,
   * and start an upload if `autoProceed === true`.
   */
  addFile(e) {
    const { nextFilesState: t, validFilesToAdd: r, errors: s } = this.#m([e]), n = s.filter((a) => a.isRestriction);
    if (this.#a(n), s.length > 0)
      throw s[0];
    this.setState({ files: t });
    const [o] = r;
    return this.emit("file-added", o), this.emit("files-added", r), this.log(`Added file: ${o.name}, ${o.id}, mime type: ${o.type}`), this.#f(), o.id;
  }
  /**
   * Add multiple files to `state.files`. See the `addFile()` documentation.
   *
   * If an error occurs while adding a file, it is logged and the user is notified.
   * This is good for UI plugins, but not for programmatic use.
   * Programmatic users should usually still use `addFile()` on individual files.
   */
  addFiles(e) {
    const { nextFilesState: t, validFilesToAdd: r, errors: s } = this.#m(e), n = s.filter((a) => a.isRestriction);
    this.#a(n);
    const o = s.filter((a) => !a.isRestriction);
    if (o.length > 0) {
      let a = `Multiple errors occurred while adding files:
`;
      if (o.forEach((l) => {
        a += `
 * ${l.message}`;
      }), this.info({
        message: this.i18n("addBulkFilesFailed", {
          smart_count: o.length
        }),
        details: a
      }, "error", this.opts.infoTimeout), typeof AggregateError == "function")
        throw new AggregateError(o, a);
      {
        const l = new Error(a);
        throw l.errors = o, l;
      }
    }
    this.setState({ files: t }), r.forEach((a) => {
      this.emit("file-added", a);
    }), this.emit("files-added", r), r.length > 5 ? this.log(`Added batch of ${r.length} files`) : Object.values(r).forEach((a) => {
      this.log(`Added file: ${a.name}
 id: ${a.id}
 type: ${a.type}`);
    }), r.length > 0 && this.#f();
  }
  removeFiles(e) {
    const { files: t, currentUploads: r } = this.getState(), s = { ...t }, n = { ...r }, o = /* @__PURE__ */ Object.create(null);
    e.forEach((u) => {
      t[u] && (o[u] = t[u], delete s[u]);
    });
    function a(u) {
      return o[u] === void 0;
    }
    Object.keys(n).forEach((u) => {
      const h = r[u].fileIDs.filter(a);
      if (h.length === 0) {
        delete n[u];
        return;
      }
      const { capabilities: b } = this.getState();
      if (h.length !== r[u].fileIDs.length && !b.individualCancellation)
        throw new Error("The installed uploader plugin does not allow removing files during an upload.");
      n[u] = {
        ...r[u],
        fileIDs: h
      };
    });
    const l = {
      currentUploads: n,
      files: s
    };
    Object.keys(s).length === 0 && (l.allowNewUpload = !0, l.error = null, l.recoveredState = null), this.setState(l), this.#d();
    const d = Object.keys(o);
    d.forEach((u) => {
      this.emit("file-removed", o[u]);
    }), d.length > 5 ? this.log(`Removed ${d.length} files`) : this.log(`Removed files: ${d.join(", ")}`);
  }
  removeFile(e) {
    this.removeFiles([e]);
  }
  pauseResume(e) {
    if (!this.getState().capabilities.resumableUploads || this.getFile(e).progress.uploadComplete)
      return;
    const t = this.getFile(e), s = !(t.isPaused || !1);
    return this.setFileState(e, {
      isPaused: s
    }), this.emit("upload-pause", t, s), s;
  }
  pauseAll() {
    const e = { ...this.getState().files };
    Object.keys(e).filter((r) => !e[r].progress.uploadComplete && e[r].progress.uploadStarted).forEach((r) => {
      const s = { ...e[r], isPaused: !0 };
      e[r] = s;
    }), this.setState({ files: e }), this.emit("pause-all");
  }
  resumeAll() {
    const e = { ...this.getState().files };
    Object.keys(e).filter((r) => !e[r].progress.uploadComplete && e[r].progress.uploadStarted).forEach((r) => {
      const s = {
        ...e[r],
        isPaused: !1,
        error: null
      };
      e[r] = s;
    }), this.setState({ files: e }), this.emit("resume-all");
  }
  #h() {
    const { files: e } = this.getState();
    return Object.keys(e).filter((t) => {
      const r = e[t];
      return r.error && (!r.missingRequiredMetaFields || r.missingRequiredMetaFields.length === 0);
    });
  }
  async #g() {
    const e = this.#h(), t = { ...this.getState().files };
    if (e.forEach((s) => {
      t[s] = {
        ...t[s],
        isPaused: !1,
        error: null
      };
    }), this.setState({
      files: t,
      error: null
    }), this.emit("retry-all", this.getFilesByIds(e)), e.length === 0)
      return {
        successful: [],
        failed: []
      };
    const r = this.#u(e, {
      forceAllowNewUpload: !0
      // create new upload even if allowNewUpload: false
    });
    return this.#c(r);
  }
  async retryAll() {
    const e = await this.#g();
    return this.emit("complete", e), e;
  }
  cancelAll() {
    this.emit("cancel-all");
    const { files: e } = this.getState(), t = Object.keys(e);
    t.length && this.removeFiles(t), this.setState(D);
  }
  /**
   * Retry a specific file that has errored.
   */
  retryUpload(e) {
    this.setFileState(e, {
      error: null,
      isPaused: !1
    }), this.emit("upload-retry", this.getFile(e));
    const t = this.#u([e], {
      forceAllowNewUpload: !0
      // create new upload even if allowNewUpload: false
    });
    return this.#c(t);
  }
  logout() {
    this.iteratePlugins((e) => {
      e.provider?.logout?.();
    });
  }
  #F = (e, t) => {
    const r = e ? this.getFile(e.id) : void 0;
    if (e == null || !r) {
      this.log(`Not setting progress for a file that has been removed: ${e?.id}`);
      return;
    }
    if (r.progress.percentage === 100) {
      this.log(`Not setting progress for a file that has been already uploaded: ${e.id}`);
      return;
    }
    const s = {
      bytesTotal: t.bytesTotal,
      // bytesTotal may be null or zero; in that case we can't divide by it
      percentage: t.bytesTotal != null && Number.isFinite(t.bytesTotal) && t.bytesTotal > 0 ? Math.round(t.bytesUploaded / t.bytesTotal * 100) : void 0
    };
    r.progress.uploadStarted != null ? this.setFileState(e.id, {
      progress: {
        ...r.progress,
        ...s,
        bytesUploaded: t.bytesUploaded
      }
    }) : this.setFileState(e.id, {
      progress: {
        ...r.progress,
        ...s
      }
    }), this.#d();
  };
  #b() {
    const e = this.#T();
    let t = null;
    e != null && (t = Math.round(e * 100), t > 100 ? t = 100 : t < 0 && (t = 0)), this.emit("progress", t ?? 0), this.setState({
      totalProgress: t ?? 0
    });
  }
  // ___Why throttle at 500ms?
  //    - We must throttle at >250ms for superfocus in Dashboard to work well
  //    (because animation takes 0.25s, and we want to wait for all animations to be over before refocusing).
  //    [Practical Check]: if thottle is at 100ms, then if you are uploading a file,
  //    and click 'ADD MORE FILES', - focus won't activate in Firefox.
  //    - We must throttle at around >500ms to avoid performance lags.
  //    [Practical Check] Firefox, try to upload a big file for a prolonged period of time. Laptop will start to heat up.
  #d = wr(() => this.#b(), 500, { leading: !0, trailing: !0 });
  [/* @__PURE__ */ Symbol.for("uppy test: updateTotalProgress")]() {
    return this.#b();
  }
  #T() {
    const t = this.getFiles().filter((l) => l.progress.uploadStarted || l.progress.preprocess || l.progress.postprocess);
    if (t.length === 0)
      return 0;
    if (t.every((l) => l.progress.uploadComplete))
      return 1;
    const r = (l) => l.progress.bytesTotal != null && l.progress.bytesTotal !== 0, s = t.filter(r), n = t.filter((l) => !r(l));
    if (s.every((l) => l.progress.uploadComplete) && n.length > 0 && !n.every((l) => l.progress.uploadComplete))
      return null;
    const o = s.reduce((l, d) => l + (d.progress.bytesTotal ?? 0), 0), a = s.reduce((l, d) => l + (d.progress.bytesUploaded || 0), 0);
    return o === 0 ? 0 : a / o;
  }
  /**
   * Registers listeners for all global actions, like:
   * `error`, `file-removed`, `upload-progress`
   */
  #x() {
    const e = (s, n, o) => {
      let a = s.message || "Unknown error";
      s.details && (a += ` ${s.details}`), this.setState({ error: a }), n != null && n.id in this.getState().files && this.setFileState(n.id, {
        error: a,
        response: o
      });
    };
    this.on("error", e), this.on("upload-error", (s, n, o) => {
      if (e(n, s, o), typeof n == "object" && n.message) {
        this.log(n.message, "error");
        const a = new Error(this.i18n("failedToUpload", { file: s?.name ?? "" }));
        a.isUserFacing = !0, a.details = n.message, n.details && (a.details += ` ${n.details}`), this.#a([a]);
      } else
        this.#a([n]);
    });
    let t = null;
    this.on("upload-stalled", (s, n) => {
      const { message: o } = s, a = n.map((l) => l.meta.name).join(", ");
      t || (this.info({ message: o, details: a }, "warning", this.opts.infoTimeout), t = setTimeout(() => {
        t = null;
      }, this.opts.infoTimeout)), this.log(`${o} ${a}`.trim(), "warning");
    }), this.on("upload", () => {
      this.setState({ error: null });
    });
    const r = (s) => {
      const n = s.filter((a) => {
        const l = a != null && this.getFile(a.id);
        return l || this.log(`Not setting progress for a file that has been removed: ${a?.id}`), l;
      }), o = Object.fromEntries(n.map((a) => [
        a.id,
        {
          progress: {
            uploadStarted: Date.now(),
            uploadComplete: !1,
            bytesUploaded: 0,
            bytesTotal: a.size
          }
        }
      ]));
      this.patchFilesState(o);
    };
    this.on("upload-start", r), this.on("upload-progress", this.#F), this.on("upload-success", (s, n) => {
      if (s == null || !this.getFile(s.id)) {
        this.log(`Not setting progress for a file that has been removed: ${s?.id}`);
        return;
      }
      const o = this.getFile(s.id).progress, a = this.#n.size > 0;
      this.setFileState(s.id, {
        progress: {
          ...o,
          postprocess: a ? {
            mode: "indeterminate"
          } : void 0,
          uploadComplete: !0,
          ...!a && { complete: !0 },
          percentage: 100,
          bytesUploaded: o.bytesTotal
        },
        response: n,
        uploadURL: n.uploadURL,
        isPaused: !1
      }), s.size == null && this.setFileState(s.id, {
        size: n.bytesUploaded || o.bytesTotal
      }), this.#d();
    }), this.on("preprocess-progress", (s, n) => {
      if (s == null || !this.getFile(s.id)) {
        this.log(`Not setting progress for a file that has been removed: ${s?.id}`);
        return;
      }
      this.setFileState(s.id, {
        progress: { ...this.getFile(s.id).progress, preprocess: n }
      });
    }), this.on("preprocess-complete", (s) => {
      if (s == null || !this.getFile(s.id)) {
        this.log(`Not setting progress for a file that has been removed: ${s?.id}`);
        return;
      }
      const n = { ...this.getState().files };
      n[s.id] = {
        ...n[s.id],
        progress: { ...n[s.id].progress }
      }, delete n[s.id].progress.preprocess, this.setState({ files: n });
    }), this.on("postprocess-progress", (s, n) => {
      if (s == null || !this.getFile(s.id)) {
        this.log(`Not setting progress for a file that has been removed: ${s?.id}`);
        return;
      }
      this.setFileState(s.id, {
        progress: {
          ...this.getState().files[s.id].progress,
          postprocess: n
        }
      });
    }), this.on("postprocess-complete", (s) => {
      const n = s && this.getFile(s.id);
      if (n == null) {
        this.log(`Not setting progress for a file that has been removed: ${s?.id}`);
        return;
      }
      const { postprocess: o, ...a } = n.progress;
      this.patchFilesState({
        [n.id]: {
          progress: {
            ...a,
            complete: !0
          }
        }
      });
    }), this.on("restored", () => {
      this.#d();
    }), this.on("dashboard:file-edit-complete", (s) => {
      s && this.#p(s);
    }), typeof window < "u" && window.addEventListener && (window.addEventListener("online", this.#l), window.addEventListener("offline", this.#l), setTimeout(this.#l, 3e3));
  }
  updateOnlineStatus() {
    window.navigator.onLine ?? !0 ? (this.emit("is-online"), this.wasOffline && (this.emit("back-online"), this.info(this.i18n("connectedToInternet"), "success", 3e3), this.wasOffline = !1)) : (this.emit("is-offline"), this.info(this.i18n("noInternetConnection"), "error", 0), this.wasOffline = !0);
  }
  #l = this.updateOnlineStatus.bind(this);
  getID() {
    return this.opts.id;
  }
  /**
   * Registers a plugin with Core.
   */
  use(e, ...t) {
    if (typeof e != "function") {
      const o = `Expected a plugin class, but got ${e === null ? "null" : typeof e}. Please verify that the plugin was imported and spelled correctly.`;
      throw new TypeError(o);
    }
    const r = new e(this, ...t), s = r.id;
    if (!s)
      throw new Error("Your plugin must have an id");
    if (!r.type)
      throw new Error("Your plugin must have a type");
    const n = this.getPlugin(s);
    if (n) {
      const o = `Already found a plugin named '${n.id}'. Tried to use: '${s}'.
Uppy plugins must have unique \`id\` options.`;
      throw new Error(o);
    }
    return e.VERSION && this.log(`Using ${s} v${e.VERSION}`), r.type in this.#e ? this.#e[r.type].push(r) : this.#e[r.type] = [r], r.install(), this.emit("plugin-added", r), this;
  }
  getPlugin(e) {
    for (const t of Object.values(this.#e)) {
      const r = t.find((s) => s.id === e);
      if (r != null)
        return r;
    }
  }
  [/* @__PURE__ */ Symbol.for("uppy test: getPlugins")](e) {
    return this.#e[e];
  }
  /**
   * Iterate through all `use`d plugins.
   *
   */
  iteratePlugins(e) {
    Object.values(this.#e).flat(1).forEach(e);
  }
  /**
   * Uninstall and remove a plugin.
   *
   * @param {object} instance The plugin instance to remove.
   */
  removePlugin(e) {
    this.log(`Removing plugin ${e.id}`), this.emit("plugin-remove", e), e.uninstall && e.uninstall();
    const t = this.#e[e.type], r = t.findIndex((o) => o.id === e.id);
    r !== -1 && t.splice(r, 1);
    const n = {
      plugins: {
        ...this.getState().plugins,
        [e.id]: void 0
      }
    };
    this.setState(n);
  }
  /**
   * Uninstall all plugins and close down this Uppy instance.
   */
  destroy() {
    this.log(`Closing Uppy instance ${this.opts.id}: removing all files and uninstalling plugins`), this.cancelAll(), this.#s(), this.iteratePlugins((e) => {
      this.removePlugin(e);
    }), typeof window < "u" && window.removeEventListener && (window.removeEventListener("online", this.#l), window.removeEventListener("offline", this.#l));
  }
  hideInfo() {
    const { info: e } = this.getState();
    this.setState({ info: e.slice(1) }), this.emit("info-hidden");
  }
  /**
   * Set info message in `state.info`, so that UI plugins like `Informer`
   * can display the message.
   */
  info(e, t = "info", r = 3e3) {
    const s = typeof e == "object";
    this.setState({
      info: [
        ...this.getState().info,
        {
          type: t,
          message: s ? e.message : e,
          details: s ? e.details : null
        }
      ]
    }), setTimeout(() => this.hideInfo(), r), this.emit("info-visible");
  }
  /**
   * Passes messages to a function, provided in `opts.logger`.
   * If `opts.logger: Uppy.debugLogger` or `opts.debug: true`, logs to the browser console.
   */
  log(e, t) {
    const { logger: r } = this.opts;
    switch (t) {
      case "error":
        r.error(e);
        break;
      case "warning":
        r.warn(e);
        break;
      default:
        r.debug(e);
        break;
    }
  }
  // We need to store request clients by a unique ID, so we can share RequestClient instances across files
  // this allows us to do rate limiting and synchronous operations like refreshing provider tokens
  // example: refreshing tokens: if each file has their own requestclient,
  // we don't have any way to synchronize all requests in order to
  // - block all requests
  // - refresh the token
  // - unblock all requests and allow them to run with a the new access token
  // back when we had a requestclient per file, once an access token expired,
  // all 6 files would go ahead and refresh the token at the same time
  // (calling /refresh-token up to 6 times), which will probably fail for some providers
  #y = /* @__PURE__ */ new Map();
  registerRequestClient(e, t) {
    this.#y.set(e, t);
  }
  /** @protected */
  getRequestClientForFile(e) {
    if (!("remote" in e && e.remote))
      throw new Error(`Tried to get RequestClient for a non-remote file ${e.id}`);
    const t = this.#y.get(e.remote.requestClientId);
    if (t == null)
      throw new Error(`requestClientId "${e.remote.requestClientId}" not registered for file "${e.id}"`);
    return t;
  }
  /**
   * Restore an upload by its ID.
   */
  async restore(e) {
    this.log(`Core: Running restored upload "${e}"`);
    const t = await this.#c(e);
    return this.emit("complete", t), t;
  }
  /**
   * Create an upload for a bunch of files.
   *
   */
  #u(e, t = {}) {
    const { forceAllowNewUpload: r = !1 } = t, { allowNewUpload: s, currentUploads: n } = this.getState();
    if (!s && !r)
      throw new Error("Cannot create a new upload: already uploading.");
    const o = xr();
    return this.emit("upload", o, this.getFilesByIds(e)), this.setState({
      allowNewUpload: this.opts.allowMultipleUploadBatches !== !1 && this.opts.allowMultipleUploads !== !1,
      currentUploads: {
        ...n,
        [o]: {
          fileIDs: e,
          step: 0,
          result: {}
        }
      }
    }), o;
  }
  [/* @__PURE__ */ Symbol.for("uppy test: createUpload")](...e) {
    return this.#u(...e);
  }
  #A(e) {
    const { currentUploads: t } = this.getState();
    return t[e];
  }
  /**
   * Add data to an upload's result object.
   */
  addResultData(e, t) {
    if (!this.#A(e)) {
      this.log(`Not setting result for an upload that has been removed: ${e}`);
      return;
    }
    const { currentUploads: r } = this.getState(), s = {
      ...r[e],
      result: { ...r[e].result, ...t }
    };
    this.setState({
      currentUploads: { ...r, [e]: s }
    });
  }
  /**
   * Remove an upload, eg. if it has been canceled or completed.
   *
   */
  #v(e) {
    const { [e]: t, ...r } = this.getState().currentUploads;
    this.setState({
      currentUploads: r
    });
  }
  /**
   * Run an upload. This picks up where it left off in case the upload is being restored.
   */
  async #c(e) {
    const t = () => {
      const { currentUploads: o } = this.getState();
      return o[e];
    };
    let r = t();
    if (!r)
      throw new Error("Nonexistent upload");
    const s = [
      ...this.#o,
      ...this.#i,
      ...this.#n
    ];
    try {
      for (let o = r.step || 0; o < s.length; o++) {
        const a = s[o];
        this.setState({
          currentUploads: {
            ...this.getState().currentUploads,
            [e]: {
              ...r,
              step: o
            }
          }
        });
        const { fileIDs: l } = r;
        if (await a(l, e), r = t(), !r)
          break;
      }
    } catch (o) {
      throw this.#v(e), o;
    }
    if (r) {
      r.fileIDs.forEach((d) => {
        const u = this.getFile(d);
        u?.progress.postprocess && this.emit("postprocess-complete", u);
      });
      const o = r.fileIDs.map((d) => this.getFile(d)), a = o.filter((d) => !d.error), l = o.filter((d) => d.error);
      this.addResultData(e, { successful: a, failed: l, uploadID: e }), r = t();
    }
    let n;
    return r && (n = r.result, this.#v(e)), n == null && (this.log(`Not setting result for an upload that has been removed: ${e}`), n = {
      successful: [],
      failed: [],
      uploadID: e
    }), n;
  }
  /**
   * Start an upload for all the files that are not currently being uploaded.
   */
  async upload() {
    this.#e.uploader?.length || this.log("No uploader type plugins are used", "warning");
    let { files: e } = this.getState();
    if (this.#h().length > 0) {
      const s = await this.#g();
      if (!(this.getFiles().filter((o) => o.progress.uploadStarted == null).length > 0))
        return this.emit("complete", s), s;
      ({ files: e } = this.getState());
    }
    const r = this.opts.onBeforeUpload(e);
    if (r === !1)
      throw new Error("Not starting the upload because onBeforeUpload returned false");
    r && typeof r == "object" && (e = r, this.setState({
      files: e
    }));
    try {
      if (this.#t.validateMinNumberOfFiles(e), !this.#w(e))
        throw new I(this.i18n("missingRequiredMetaField"));
      const { currentUploads: s } = this.getState(), n = Object.values(s).flatMap((d) => d.fileIDs), o = Object.keys(e).filter((d) => {
        const u = this.getFile(d);
        return u && !u.progress.uploadStarted && !n.includes(d);
      }), a = this.#u(o), l = await this.#c(a);
      return this.emit("complete", l), l;
    } catch (s) {
      throw this.#a([s]), s;
    }
  }
}
const Ir = "5.2.0", Lr = {
  version: Ir
}, Pr = {
  strings: {
    // Shown in the Informer if an upload is being canceled because it stalled for too long.
    uploadStalled: "Upload has not made any progress for %{seconds} seconds. You may want to retry it."
  }
};
function kr(i, e) {
  let t = e;
  return t || (t = new Error("Upload error")), typeof t == "string" && (t = new Error(t)), t instanceof Error || (t = Object.assign(new Error("Upload error"), { data: t })), Bt(i) ? (t = new Ee(t, i), t) : (t.request = i, t);
}
function st(i) {
  return i.data.slice(0, i.data.size, i.meta.type);
}
const Nr = {
  formData: !0,
  fieldName: "file",
  method: "post",
  allowedMetaFields: !0,
  bundle: !1,
  headers: {},
  timeout: 30 * 1e3,
  limit: 5,
  withCredentials: !1,
  responseType: ""
};
class Cr extends Gt {
  static VERSION = Lr.version;
  #e;
  #t;
  uploaderEvents;
  constructor(e, t) {
    if (super(e, {
      ...Nr,
      fieldName: t.bundle ? "files[]" : "file",
      ...t
    }), this.type = "uploader", this.id = this.opts.id || "XHRUpload", this.defaultLocale = Pr, this.i18nInit(), this.#t = new Ht({ concurrency: this.opts.limit }), this.opts.bundle && !this.opts.formData)
      throw new Error("`opts.formData` must be true when `opts.bundle` is enabled.");
    if (this.opts.bundle && typeof this.opts.headers == "function")
      throw new Error("`opts.headers` can not be a function when the `bundle: true` option is set.");
    if (t?.allowedMetaFields === void 0 && "metaFields" in this.opts)
      throw new Error("The `metaFields` option has been renamed to `allowedMetaFields`.");
    this.uploaderEvents = /* @__PURE__ */ Object.create(null), this.#e = (r) => async (s, n) => {
      try {
        const o = await Pt(s, {
          ...n,
          onBeforeRequest: (d, u) => this.opts.onBeforeRequest?.(d, u, r),
          shouldRetry: this.opts.shouldRetry,
          onAfterResponse: this.opts.onAfterResponse,
          onTimeout: (d) => {
            const u = Math.ceil(d / 1e3), h = new Error(this.i18n("uploadStalled", { seconds: u }));
            this.uppy.emit("upload-stalled", h, r);
          },
          onUploadProgress: (d) => {
            if (d.lengthComputable)
              for (const { id: u } of r) {
                const h = this.uppy.getFile(u);
                h != null && this.uppy.emit("upload-progress", h, {
                  uploadStarted: h.progress.uploadStarted ?? 0,
                  bytesUploaded: d.loaded / d.total * h.size,
                  bytesTotal: h.size
                });
              }
          }
        });
        let a = await this.opts.getResponseData?.(o);
        if (o.responseType === "json")
          a ??= o.response;
        else
          try {
            a ??= JSON.parse(o.responseText);
          } catch (d) {
            throw new Error("@uppy/xhr-upload expects a JSON response (with a `url` property). To parse non-JSON responses, use `getResponseData` to turn your response into JSON.", { cause: d });
          }
        const l = typeof a?.url == "string" ? a.url : void 0;
        for (const { id: d } of r)
          this.uppy.emit("upload-success", this.uppy.getFile(d), {
            status: o.status,
            body: a,
            uploadURL: l
          });
        return o;
      } catch (o) {
        if (o.name === "AbortError")
          return;
        const a = o.request;
        for (const l of r)
          this.uppy.emit("upload-error", this.uppy.getFile(l.id), kr(a, o), a);
        throw o;
      }
    };
  }
  getOptions(e) {
    const t = this.uppy.getState().xhrUpload, { headers: r } = this.opts, s = {
      ...this.opts,
      ...t || {},
      ...e.xhrUpload || {},
      headers: {}
    };
    return typeof r == "function" ? s.headers = r(e) : Object.assign(s.headers, this.opts.headers), t && Object.assign(s.headers, t.headers), e.xhrUpload && Object.assign(s.headers, e.xhrUpload.headers), s;
  }
  addMetadata(e, t, r) {
    Ce(r.allowedMetaFields, t).forEach((n) => {
      const o = t[n];
      Array.isArray(o) ? o.forEach((a) => e.append(n, a)) : e.append(n, o);
    });
  }
  createFormDataUpload(e, t) {
    const r = new FormData();
    this.addMetadata(r, e.meta, t);
    const s = st(e);
    return e.name ? r.append(t.fieldName, s, e.meta.name) : r.append(t.fieldName, s), r;
  }
  createBundledUpload(e, t) {
    const r = new FormData(), { meta: s } = this.uppy.getState();
    return this.addMetadata(r, s, t), e.forEach((n) => {
      const o = this.getOptions(n), a = st(n);
      n.name ? r.append(o.fieldName, a, n.name) : r.append(o.fieldName, a);
    }), r;
  }
  async #s(e) {
    const t = new Vt(this.uppy), r = new AbortController();
    t.onFileRemove(e.id, () => r.abort()), t.onCancelAll(e.id, () => r.abort());
    try {
      await this.#t.add(async (s) => {
        const n = this.getOptions(e), o = this.#e([e]), a = n.formData ? this.createFormDataUpload(e, n) : e.data, l = typeof n.endpoint == "string" ? n.endpoint : await n.endpoint(e);
        return o(l, {
          ...n,
          body: a,
          signal: AbortSignal.any([s, r.signal])
        });
      });
    } catch (s) {
      if (s.name === "AbortError")
        return;
      throw s;
    } finally {
      t.remove();
    }
  }
  async #r(e) {
    const t = new AbortController();
    function r() {
      t.abort();
    }
    this.uppy.once("cancel-all", r);
    try {
      await this.#t.add(async (s) => {
        const n = this.uppy.getState().xhrUpload ?? {}, o = this.#e(e), a = this.createBundledUpload(e, {
          ...this.opts,
          ...n
        }), l = typeof this.opts.endpoint == "string" ? this.opts.endpoint : await this.opts.endpoint(e);
        return o(l, {
          // headers can't be a function with bundle: true
          ...this.opts,
          body: a,
          signal: AbortSignal.any([s, t.signal])
        });
      });
    } catch (s) {
      if (s.name === "AbortError")
        return;
      throw s;
    } finally {
      this.uppy.off("cancel-all", r);
    }
  }
  #o(e) {
    const t = this.getOptions(e), r = Ce(t.allowedMetaFields, e.meta);
    return {
      ...e.remote?.body,
      protocol: "multipart",
      endpoint: t.endpoint,
      size: e.data.size,
      fieldname: t.fieldName,
      metadata: Object.fromEntries(r.map((s) => [s, e.meta[s]])),
      httpMethod: t.method,
      useFormData: t.formData,
      headers: t.headers
    };
  }
  async #i(e) {
    await Promise.allSettled(e.map((t) => {
      if (t.isRemote) {
        const r = () => this.#t, s = new AbortController(), n = (o) => {
          o.id === t.id && s.abort();
        };
        return this.uppy.on("file-removed", n), this.uppy.getRequestClientForFile(t).uploadRemoteFile(t, this.#o(t), {
          signal: s.signal,
          getQueue: r
        }).finally(() => {
          this.uppy.off("file-removed", n);
        });
      }
      return this.#s(t);
    }));
  }
  #n = async (e) => {
    if (e.length === 0) {
      this.uppy.log("[XHRUpload] No files to upload!");
      return;
    }
    this.opts.limit === 0 && this.uppy.log("[XHRUpload] When uploading multiple files at once, consider setting the `limit` option (to `10` for example), to limit the number of concurrent uploads, which helps prevent memory and network issues: https://uppy.io/docs/xhr-upload/#limit-0", "warning"), this.uppy.log("[XHRUpload] Uploading...");
    const t = this.uppy.getFilesByIds(e), r = Ct(t), s = qt(r);
    if (this.uppy.emit("upload-start", s), this.opts.bundle) {
      if (r.some((o) => o.isRemote))
        throw new Error("Can’t upload remote files when the `bundle: true` option is set");
      if (typeof this.opts.headers == "function")
        throw new TypeError("`headers` may not be a function when the `bundle: true` option is set");
      await this.#r(r);
    } else
      await this.#i(r);
  };
  install() {
    if (this.opts.bundle) {
      const { capabilities: e } = this.uppy.getState();
      this.uppy.setState({
        capabilities: {
          ...e,
          individualCancellation: !1
        }
      });
    }
    this.uppy.addUploader(this.#n);
  }
  uninstall() {
    if (this.opts.bundle) {
      const { capabilities: e } = this.uppy.getState();
      this.uppy.setState({
        capabilities: {
          ...e,
          individualCancellation: !0
        }
      });
    }
    this.uppy.removeUploader(this.#n);
  }
}
const qr = '@layer formie-theme{.formie-upload-manager{display:flex;flex-direction:column;gap:var(--formie-gap-field)}.formie-upload-manager-dropzone{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--formie-space-2);padding:var(--formie-space-4);min-height:calc(var(--formie-control-height) * 3);border:var(--formie-border-width) dashed var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface-subtle);color:var(--formie-color-text);text-align:center;cursor:pointer;transition:border-color .15s ease,background-color .15s ease,box-shadow .15s ease}.formie-upload-manager-dropzone:hover,.formie-upload-manager-dropzone.formie-upload-manager-dropzone-active{border-color:var(--formie-color-focus-ring);background:var(--formie-color-surface-muted)}.formie-upload-manager-dropzone:focus{outline:0}.formie-upload-manager-dropzone:focus-visible{box-shadow:0 0 0 var(--formie-focus-ring-width) var(--formie-color-focus-ring)}.formie-field-has-error .formie-upload-manager-dropzone{border-color:var(--formie-color-danger)}.formie-upload-manager-prompt{margin:0;font-size:var(--formie-font-size-sm);color:var(--formie-color-text-muted)}.formie-upload-manager-list{display:flex;flex-direction:column;gap:var(--formie-space-2);margin:0;padding:0;list-style:none}.formie-upload-manager-item{display:grid;grid-template-columns:minmax(0,1fr) minmax(6.5rem,8rem) auto;align-items:center;gap:var(--formie-space-2);padding:var(--formie-space-2) var(--formie-space-3);border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface)}.formie-upload-manager-item.is-error{border-color:var(--formie-color-danger)}.formie-upload-manager-item.is-complete .formie-upload-manager-progress{opacity:0;visibility:hidden;pointer-events:none}.formie-upload-manager-filename{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:var(--formie-font-size-sm)}.formie-upload-manager-progress{display:flex;flex-direction:column;justify-content:center;gap:var(--formie-space-1);min-height:var(--formie-button-icon-button-size);align-self:center;opacity:1;visibility:visible;transition:opacity .2s ease,visibility .2s ease}.formie-upload-manager-progress-track{position:relative;height:.375rem;border-radius:var(--formie-radius-full);background:var(--formie-color-surface-muted);overflow:hidden}.formie-upload-manager-progress-bar{height:100%;width:0;border-radius:inherit;background:var(--formie-color-primary);transition:width .18s ease-out,background-color .2s ease}.formie-upload-manager-item.is-upload-complete .formie-upload-manager-progress-bar{width:100%!important;background:var(--formie-color-success, var(--formie-color-primary))}.formie-upload-manager-item.is-uploading:not(.is-upload-complete) .formie-upload-manager-progress-track[data-indeterminate=true] .formie-upload-manager-progress-bar,.formie-upload-manager-item.is-preparing .formie-upload-manager-progress-bar,.formie-upload-manager-item.is-processing .formie-upload-manager-progress-bar{width:35%;animation:formie-upload-manager-progress-indeterminate 1.1s ease-in-out infinite}.formie-upload-manager-item.is-processing .formie-upload-manager-progress-bar{opacity:.85}.formie-upload-manager-item.is-processing .formie-upload-manager-progress-label{color:var(--formie-color-text)}@keyframes formie-upload-manager-progress-indeterminate{0%{transform:translate(-120%)}to{transform:translate(320%)}}.formie-upload-manager-progress-label{font-size:var(--formie-font-size-xs);line-height:1.2;color:var(--formie-color-text-muted);text-align:center;white-space:nowrap}.formie-upload-manager-item.is-upload-complete .formie-upload-manager-progress-label{color:var(--formie-color-success-dark, var(--formie-color-primary));font-weight:var(--formie-font-weight-medium)}.formie-upload-manager-error{grid-column:1 / -1;font-size:var(--formie-font-size-xs);color:var(--formie-color-danger)}.formie-upload-manager-browse-button{margin-top:var(--formie-space-1)}.formie-upload-manager-actions{display:flex;align-items:center;gap:var(--formie-space-2);align-self:center;flex-shrink:0}.formie-upload-manager-sort-controls{display:inline-flex;flex-direction:row;align-items:center;gap:.0625rem;padding:0;border-radius:var(--formie-radius-sm);background:transparent}.formie-upload-manager-sort-controls[hidden]{display:none}.formie-upload-manager-sort-button,.formie-upload-manager-action-button{position:relative;display:inline-flex;align-items:center;justify-content:center;width:1.375rem;height:1.375rem;padding:0;border:0;border-radius:var(--formie-radius-sm);background:transparent;color:var(--formie-color-text-muted);cursor:pointer;font-size:0;line-height:0;text-indent:-9999px;overflow:hidden;white-space:nowrap;transition:background-color .15s ease,color .15s ease,opacity .15s ease}.formie-upload-manager-sort-button{width:1.375rem;height:1.375rem}.formie-upload-manager-sort-button:hover:not(:disabled),.formie-upload-manager-action-button:hover{background:var(--formie-color-surface-muted);color:var(--formie-color-text)}.formie-upload-manager-sort-button:focus-visible,.formie-upload-manager-action-button:focus-visible{outline:0;box-shadow:0 0 0 var(--formie-focus-ring-width) var(--formie-color-focus-ring)}.formie-upload-manager-sort-button:disabled{opacity:.28;cursor:not-allowed}.formie-upload-manager-sort-button:after,.formie-upload-manager-action-button:after{position:absolute;top:50%;left:50%;display:block;content:"";transform:translate(-50%,-50%);background-color:currentColor;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;-webkit-mask-size:contain;mask-size:contain}.formie-upload-manager-sort-button[data-formie-icon=arrow-up]{--formie-upload-manager-icon-mask: var(--formie-icon-mask-arrow-up)}.formie-upload-manager-sort-button[data-formie-icon=arrow-down]{--formie-upload-manager-icon-mask: var(--formie-icon-mask-arrow-down)}.formie-upload-manager-action-button[data-formie-icon=close]{--formie-upload-manager-icon-mask: var(--formie-icon-mask-close)}.formie-upload-manager-sort-button:after{width:.75rem;height:.75rem;-webkit-mask-image:var(--formie-upload-manager-icon-mask);mask-image:var(--formie-upload-manager-icon-mask)}.formie-upload-manager-action-button:after{width:.825rem;height:.825rem;-webkit-mask-image:var(--formie-upload-manager-icon-mask);mask-image:var(--formie-upload-manager-icon-mask)}.formie-upload-manager-remove-button{flex-shrink:0}}', V = "[data-formie-upload-manager-root]", Fe = "[data-formie-upload-manager]", it = "[data-formie-upload-manager-browse]", _r = "[data-formie-upload-manager-input]", Te = "[data-formie-upload-manager-status]", jr = "[data-formie-upload-manager-list]", zr = "[data-formie-upload-manager-sort-controls]", $r = '[data-formie-upload-manager-sort="up"]', Br = '[data-formie-upload-manager-sort="down"]', we = "data-formie-file-upload-anchor", yt = "data-formie-file-upload-asset-id", ot = It("reset"), nt = Ot("repeater", "init-row"), vt = "upload-manager", wt = "upload-manager", Hr = ["uploadManagerRequired", "uploadManagerFileLimit"], Dr = 900, St = 95, Wr = 12e4, Y = Mt("fields", "upload-manager");
Ut(vt, [qr]);
function Et(i) {
  return !!i && typeof i == "object" && !Array.isArray(i);
}
function X(i) {
  const e = Number(i);
  return Number.isInteger(e) && e > 0 ? e : null;
}
function Oe(i) {
  return i.getAttribute("data-formie-field-handle")?.trim() || "";
}
function Ft(i) {
  if (!i)
    return "";
  const e = i.querySelector('input[name="handle"]');
  return e instanceof HTMLInputElement && e.value.trim() ? e.value.trim() : i.getAttribute("data-formie-handle")?.trim() || "";
}
function Me(i) {
  return Array.from(i.querySelectorAll('input[type="hidden"]')).filter((e) => e instanceof HTMLInputElement);
}
function Gr(i, e) {
  const t = Me(i).find((s) => s.hasAttribute(we) || s.name === e.replace("[]", "") && s.value === "");
  if (t)
    return t.setAttribute(we, "true"), t;
  const r = document.createElement("input");
  return r.type = "hidden", r.name = e.replace("[]", ""), r.value = "", r.setAttribute(we, "true"), i.prepend(r), r;
}
function Tt(i, e) {
  return Me(i).filter((t) => t.name === e && t.value.trim() !== "");
}
function xe(i, e) {
  return Tt(i, e).map((t) => X(t.value)).filter((t) => t !== null);
}
function xt(i, e, t, r) {
  let s = e;
  Tt(i, t).forEach((n) => {
    n.remove();
  }), r.forEach((n) => {
    const o = document.createElement("input");
    o.type = "hidden", o.name = t, o.value = String(n), o.setAttribute(yt, "true"), s.insertAdjacentElement("afterend", o), s = o;
  });
}
function at(i, e, t) {
  const r = t.getAttribute("data-formie-upload-key")?.trim() || t.getAttribute("data-formie-input-id")?.trim() || "", s = {
    handle: Ft(i),
    fieldHandle: Oe(e),
    inputKey: r
  };
  if (!i)
    return s;
  ["renderId", "draftContextToken", "draftContext", "submissionId"].forEach((a) => {
    const l = i.querySelector(`input[name="${a}"]`);
    l instanceof HTMLInputElement && l.value.trim() && (s[a] = l.value.trim());
  });
  const o = i.querySelector('input[name="CRAFT_CSRF_TOKEN"]');
  return o instanceof HTMLInputElement && o.value.trim() && (s.CRAFT_CSRF_TOKEN = o.value.trim()), s;
}
function Vr(i, e, t) {
  const r = new FormData(), s = Ft(i), n = Oe(e);
  s && r.append("handle", s), n && r.append("fieldHandle", n);
  const o = i?.querySelector('input[name="submissionUid"]');
  return o instanceof HTMLInputElement && o.value.trim() && r.append("submissionUid", o.value.trim()), t.forEach((a) => {
    r.append("assetIds[]", String(a));
  }), r;
}
function Xr(i) {
  if (!i)
    return null;
  const e = i.split(",").map((t) => t.trim()).filter(Boolean);
  return e.length ? e : null;
}
function Se(i, e, t, r) {
  return e?.trim() || i.getAttribute(t)?.trim() || r;
}
function Ie(i) {
  const e = i.querySelector("[data-formie-upload-manager-progress]");
  if (!(e instanceof HTMLElement))
    return { track: null, bar: null, label: null };
  const t = e.querySelector("[data-formie-upload-manager-progress-track]"), r = e.querySelector("[data-formie-upload-manager-progress-bar]"), s = e.querySelector("[data-formie-upload-manager-progress-label]");
  return {
    track: t instanceof HTMLElement ? t : null,
    bar: r instanceof HTMLElement ? r : null,
    label: s instanceof HTMLElement ? s : null
  };
}
function J(i, e, t = "uploading") {
  const { track: r, bar: s, label: n } = Ie(i);
  i.classList.add("is-uploading"), i.classList.remove("is-upload-complete", "is-complete", "is-processing", "is-preparing"), t === "processing" ? i.classList.add("is-processing") : t === "preparing" && i.classList.add("is-preparing"), r && r.setAttribute("data-indeterminate", "true"), s && (s.style.removeProperty("width"), s.setAttribute("data-progress", t)), n && (n.textContent = e);
}
function Yr(i, e, t) {
  const { track: r, bar: s, label: n } = Ie(i), o = Math.max(0, Math.min(St, Math.round(e)));
  i.classList.add("is-uploading"), i.classList.remove("is-upload-complete", "is-complete", "is-processing", "is-preparing"), r && r.setAttribute("data-indeterminate", "false"), s && (s.style.width = `${o}%`, s.setAttribute("data-progress", String(o))), n && (t ? n.textContent = t : o > 0 ? n.textContent = `Uploading… ${o}%` : n.textContent = "Uploading…");
}
function lt(i, e, t = {}) {
  const { loaded: r = 0, total: s = 0 } = t;
  if ((s > 0 ? r >= s : e >= 100) || e >= 100) {
    J(i, "Processing…", "processing");
    return;
  }
  if (e <= 0 && r <= 0) {
    J(i, "Uploading…", "uploading");
    return;
  }
  const o = Math.min(
    St,
    Math.max(1, Math.round(e))
  );
  Yr(
    i,
    o,
    `Uploading… ${o}%`
  );
}
function dt(i, e) {
  if (i instanceof XMLHttpRequest && i.responseText)
    try {
      const t = JSON.parse(i.responseText);
      if (t.message)
        return t.message;
      if (t.errors) {
        const r = Object.values(t.errors).flat().find(Boolean);
        if (r)
          return r;
      }
    } catch {
    }
  return e instanceof Error && e.message ? e.message : "Upload failed.";
}
function Jr(i) {
  return i.status === 0 ? !0 : i.status >= 500;
}
function Kr(i) {
  const { track: e, bar: t, label: r } = Ie(i);
  i.classList.add("is-uploading", "is-upload-complete"), i.classList.remove("is-complete", "is-processing", "is-preparing"), e && e.setAttribute("data-indeterminate", "false"), t && (t.style.width = "100%", t.setAttribute("data-progress", "100")), r && (r.textContent = "Complete"), window.setTimeout(() => {
    i.classList.add("is-complete"), i.classList.remove("is-uploading", "is-upload-complete");
  }, Dr);
}
function ut(i) {
  const e = i === "up" ? "Move file up" : "Move file down", t = document.createElement("button");
  return t.type = "button", t.className = "formie-upload-manager-sort-button", t.setAttribute("data-formie-upload-manager-sort", i), t.setAttribute("data-formie-icon", i === "up" ? "arrow-up" : "arrow-down"), t.setAttribute("aria-label", e), t.setAttribute("title", e), t.textContent = e, t;
}
function ct(i, e) {
  const t = document.createElement("li");
  t.className = "formie-upload-manager-item", t.setAttribute("data-formie-upload-manager-item", "true");
  const r = document.createElement("span");
  r.className = "formie-upload-manager-filename", r.setAttribute("data-formie-upload-manager-filename", "true"), r.textContent = e;
  const s = document.createElement("div");
  s.className = "formie-upload-manager-progress", s.setAttribute("data-formie-upload-manager-progress", "true"), s.setAttribute("aria-live", "polite");
  const n = document.createElement("div");
  n.className = "formie-upload-manager-progress-track", n.setAttribute("data-formie-upload-manager-progress-track", "true"), n.setAttribute("data-indeterminate", "true");
  const o = document.createElement("div");
  o.className = "formie-upload-manager-progress-bar", o.setAttribute("data-formie-upload-manager-progress-bar", "true"), o.setAttribute("data-progress", "0"), o.style.width = "0%";
  const a = document.createElement("span");
  a.className = "formie-upload-manager-progress-label", a.setAttribute("data-formie-upload-manager-progress-label", "true"), a.textContent = "Preparing…", n.append(o), s.append(n, a);
  const l = document.createElement("div");
  l.className = "formie-upload-manager-sort-controls", l.setAttribute("data-formie-upload-manager-sort-controls", "true"), l.hidden = !0;
  const d = ut("up"), u = ut("down");
  l.append(d, u);
  const h = document.createElement("div");
  h.className = "formie-upload-manager-actions", h.setAttribute("data-formie-upload-manager-actions", "true");
  const b = document.createElement("button");
  b.type = "button", b.className = "formie-upload-manager-action-button formie-upload-manager-remove-button", b.setAttribute("data-formie-upload-manager-remove", "true"), b.setAttribute("aria-label", "Remove file"), b.setAttribute("title", "Remove file"), b.setAttribute("data-formie-icon", "close"), b.textContent = "Remove", h.append(l, b);
  const S = document.createElement("span");
  return S.className = "formie-upload-manager-error", S.setAttribute("data-formie-upload-manager-error", "true"), S.hidden = !0, t.append(r, s, h, S), J(t, "Preparing…", "preparing"), {
    listItem: t,
    filenameEl: r,
    errorEl: S,
    removeButton: b,
    sortUpButton: d,
    sortDownButton: u
  };
}
function Ae(i) {
  const e = Me(i).find((r) => r.hasAttribute(yt));
  if (e?.name)
    return e.name;
  const t = Oe(i);
  return t ? `fields[${t}][]` : "fields[fileUpload][]";
}
function Zr(i) {
  return i.files.filter((e) => e.assetId !== null).length;
}
function W(i) {
  const e = i.files.map((t) => t.assetId).filter((t) => t !== null);
  xt(i.field, i.anchorInput, i.assetInputName, e), i.statusInput && (i.statusInput.value = e.length ? "uploaded" : ""), pt(i.field, "file-upload", "uploaded-assets-sync", {
    assets: e.map((t) => {
      const r = i.files.find((s) => s.assetId === t);
      return {
        assetId: t,
        filename: r?.filename || ""
      };
    })
  });
}
function Qr(i) {
  At(i, wt, (e) => {
    e.addValidator("uploadManagerRequired", ({ input: t }) => {
      if (!t.matches(Te))
        return !0;
      const r = t.closest("[data-formie-field-handle]");
      return !(r instanceof HTMLElement) || t.getAttribute("data-formie-validation-required") !== "true" ? !0 : xe(r, Ae(r)).length > 0;
    }, ({ input: t, label: r, t: s }) => t.getAttribute("data-formie-required-message") ?? s("{label} cannot be blank.", { label: r })), e.addValidator("uploadManagerFileLimit", ({ input: t }) => {
      if (!t.matches(Te))
        return !0;
      const r = t.closest("[data-formie-field-handle]");
      if (!(r instanceof HTMLElement))
        return !0;
      const s = r.querySelector(Fe);
      if (!(s instanceof HTMLElement))
        return !0;
      const n = parseInt(s.getAttribute("data-formie-file-limit") || "", 10);
      return n ? xe(r, Ae(r)).length <= n : !0;
    }, ({ input: t, t: r }) => {
      const n = t.closest("[data-formie-field-handle]")?.querySelector(Fe);
      return n?.getAttribute("data-formie-validation-max-files-message") ?? r("Choose up to {files} files.", {
        files: n?.getAttribute("data-formie-file-limit") || ""
      });
    });
  });
}
function es(i) {
  Rt(i, wt, Hr);
}
function ts(i, e) {
  if (i instanceof HTMLFormElement)
    return i;
  const t = e.closest("form");
  return t instanceof HTMLFormElement ? t : null;
}
function rs(i, e, t) {
  const r = i.querySelector(V);
  if (!(r instanceof HTMLElement))
    return () => {
    };
  const s = r.querySelector(Fe);
  if (!(s instanceof HTMLElement))
    return () => {
    };
  const n = r.querySelector(_r), o = r.querySelector(it), a = r.querySelector(jr), l = r.querySelector(Te);
  if (!(n instanceof HTMLInputElement) || !(a instanceof HTMLElement))
    return () => {
    };
  const d = ts(e, i), u = l instanceof HTMLInputElement ? l : null, h = Ae(i), b = Gr(i, h), S = Se(s, t.uploadEndpoint, "data-formie-file-upload-upload-endpoint", "/actions/formie/file-upload/upload"), y = Se(s, t.deleteEndpoint, "data-formie-file-upload-delete-endpoint", "/actions/formie/file-upload/delete"), E = Se(s, t.hydrateEndpoint, "data-formie-file-upload-hydrate-endpoint", "/actions/formie/file-upload/hydrate"), O = t.limitFiles ?? X(s.getAttribute("data-formie-file-limit")), P = t.sizeLimit ?? parseFloat(s.getAttribute("data-formie-size-max-limit") || ""), A = t.sizeMinLimit ?? parseFloat(s.getAttribute("data-formie-size-min-limit") || ""), k = t.accept ?? s.getAttribute("accept") ?? n.accept, M = Xr(k), w = {};
  O && (w.maxNumberOfFiles = O), P && (w.maxFileSize = P * 1e3 * 1e3), A && (w.minFileSize = A * 1e3 * 1e3), M && (w.allowedFileTypes = M);
  const F = new Ue({
    autoProceed: !0,
    restrictions: w
  });
  F.use(Cr, {
    endpoint: S,
    fieldName: "file",
    formData: !0,
    withCredentials: !0,
    timeout: Wr,
    shouldRetry: Jr,
    headers: {
      Accept: "application/json"
    },
    allowedMetaFields: [
      "handle",
      "fieldHandle",
      "inputKey",
      "renderId",
      "draftContextToken",
      "draftContext",
      "submissionId",
      "CRAFT_CSRF_TOKEN"
    ],
    getResponseData(c) {
      try {
        return JSON.parse(c.responseText);
      } catch {
        return {};
      }
    },
    onBeforeRequest(c, p, g) {
      const f = g[0];
      !f?.id || !f.size || c.upload.addEventListener("progress", (m) => {
        const x = j(f.id);
        if (!x)
          return;
        let R = 0;
        m.lengthComputable && m.total > 0 ? R = Math.round(m.loaded / m.total * 100) : m.loaded > 0 && f.size && (R = Math.min(100, Math.round(m.loaded / f.size * 100))), lt(x.listItem, R, {
          loaded: m.loaded,
          total: m.lengthComputable ? m.total : f.size ?? void 0
        });
      });
    }
  });
  const v = {
    field: i,
    dropzone: s,
    browseInput: n,
    statusInput: u,
    fileList: a,
    anchorInput: b,
    assetInputName: h,
    uppy: F,
    files: []
  }, U = () => O ? Math.max(0, O - Zr(v)) : null, _ = (c) => {
    s.classList.toggle("formie-upload-manager-dropzone-active", c);
  }, Z = (c) => c.assetId !== null && !c.listItem.classList.contains("is-error"), N = () => {
    const c = v.files.length >= 2;
    v.files.forEach((p, g) => {
      const f = p.listItem.querySelector(zr), m = p.listItem.querySelector($r), x = p.listItem.querySelector(Br);
      if (!(f instanceof HTMLElement))
        return;
      const R = c && Z(p);
      f.hidden = !R, m instanceof HTMLButtonElement && (m.disabled = !R || g === 0), x instanceof HTMLButtonElement && (x.disabled = !R || g >= v.files.length - 1);
    });
  }, q = (c, p) => {
    const g = v.files.indexOf(c), f = g + p;
    if (g < 0 || f < 0 || f >= v.files.length)
      return;
    const m = v.files[f];
    v.files[g] = m, v.files[f] = c, p === -1 ? c.listItem.before(m.listItem) : c.listItem.after(m.listItem), W(v), N(), pt(v.field, "file-upload", "uploaded-assets-reordered", {
      assets: v.files.filter((x) => x.assetId !== null).map((x) => ({
        assetId: x.assetId,
        filename: x.filename
      }))
    });
  }, T = (c, p, g, f) => {
    p.addEventListener("click", () => {
      L(c);
    }), g.addEventListener("click", () => {
      q(c, -1);
    }), f.addEventListener("click", () => {
      q(c, 1);
    });
  }, L = async (c) => {
    if (c.uppyFileId && F.removeFile(c.uppyFileId), c.assetId) {
      const p = new FormData(), g = at(d, i, s);
      Object.entries(g).forEach(([f, m]) => {
        p.append(f, m);
      }), p.append("assetId", String(c.assetId));
      try {
        await Pe(y, {
          method: "POST",
          body: p
        });
      } catch (f) {
        Y.warn("Failed to delete uploaded asset.", { assetId: c.assetId, error: f });
      }
    }
    c.listItem.remove(), v.files = v.files.filter((p) => p !== c), W(v), N();
  }, C = (c, p) => {
    const { listItem: g, removeButton: f, sortUpButton: m, sortDownButton: x } = ct(i, p);
    g.classList.add("is-complete"), a.append(g);
    const R = {
      assetId: c,
      filename: p,
      uppyFileId: null,
      listItem: g
    };
    T(R, f, m, x), v.files.push(R), N();
  }, z = async () => {
    const c = xe(i, h);
    if (c.length)
      try {
        const g = (await Pe(E, {
          method: "POST",
          body: Vr(d, i, c)
        })).assets || [], f = new Map(g.map((m) => [X(m.assetId), m]).filter(([m]) => m !== null));
        c.forEach((m) => {
          if (v.files.some((R) => R.assetId === m))
            return;
          const x = f.get(m);
          x && C(m, x.filename || `Asset #${m}`);
        }), W(v), N();
      } catch (p) {
        Y.warn("Failed to hydrate uploaded assets.", { error: p });
      }
  }, j = (c) => c && v.files.find((p) => p.uppyFileId === c) || null;
  F.on("upload-progress", (c, p) => {
    const g = j(c?.id);
    if (!g)
      return;
    const f = p.bytesTotal || 0, m = p.bytesUploaded || 0, x = f > 0 ? Math.round(m / f * 100) : 0;
    lt(g.listItem, x, {
      loaded: m,
      total: f
    });
  }), F.on("upload-retry", (c) => {
    const p = j(c?.id);
    p && J(p.listItem, "Retrying…", "retrying");
  }), F.on("upload-success", (c, p) => {
    const g = j(c?.id);
    if (!g)
      return;
    const f = g.listItem.querySelector("[data-formie-upload-manager-error]"), m = Et(p?.body) ? p.body : {}, x = X(m.assetId);
    if (!m.success || !x) {
      if (g.listItem.classList.add("is-error"), g.listItem.classList.remove("is-uploading", "is-upload-complete", "is-processing", "is-preparing"), f instanceof HTMLElement) {
        f.hidden = !1, f.textContent = dt(null, { message: "Upload failed." });
        const R = m.errors ? Object.values(m.errors).flat().find(Boolean) : null;
        R && (f.textContent = R);
      }
      return;
    }
    g.assetId = x, g.filename = m.filename || g.filename, Kr(g.listItem), W(v), N();
  }), F.on("upload-error", (c, p, g) => {
    const f = j(c?.id);
    if (!f)
      return;
    const m = f.listItem.querySelector("[data-formie-upload-manager-error]");
    f.listItem.classList.add("is-error"), f.listItem.classList.remove("is-uploading", "is-upload-complete", "is-processing", "is-preparing"), m instanceof HTMLElement && (m.hidden = !1, m.textContent = dt(g, p));
  }), F.on("file-added", (c) => {
    const p = U();
    if (p !== null && p <= 0) {
      F.removeFile(c.id), F.info("File limit reached.", "error", 3e3);
      return;
    }
    F.setFileMeta(c.id, at(d, i, s));
    const { listItem: g, removeButton: f, sortUpButton: m, sortDownButton: x } = ct(i, c.name || "Upload");
    a.append(g);
    const R = {
      assetId: null,
      filename: c.name || "Upload",
      uppyFileId: c.id,
      listItem: g
    };
    T(R, f, m, x), v.files.push(R), N();
  });
  const Q = () => {
    n.click();
  };
  o?.addEventListener("click", (c) => {
    c.preventDefault(), c.stopPropagation(), Q();
  }), s.addEventListener("click", (c) => {
    c.target instanceof Element && c.target.closest(it) || Q();
  }), s.addEventListener("keydown", (c) => {
    (c.key === "Enter" || c.key === " ") && (c.preventDefault(), Q());
  }), n.addEventListener("change", () => {
    if (!n.files?.length)
      return;
    const c = Array.from(n.files);
    n.value = "";
    try {
      F.addFiles(c.map((p) => ({
        name: p.name,
        type: p.type,
        data: p
      })));
    } catch (p) {
      p instanceof Error && F.info(p.message, "error", 3e3);
    }
  }), ["dragenter", "dragover"].forEach((c) => {
    s.addEventListener(c, (p) => {
      p.preventDefault(), p.stopPropagation(), _(!0);
    });
  }), ["dragleave", "drop"].forEach((c) => {
    s.addEventListener(c, (p) => {
      p.preventDefault(), p.stopPropagation(), _(!1);
    });
  }), s.addEventListener("drop", (c) => {
    const p = c.dataTransfer;
    if (!p?.files?.length)
      return;
    const g = Array.from(p.files);
    try {
      F.addFiles(g.map((f) => ({
        name: f.name,
        type: f.type,
        data: f
      })));
    } catch (f) {
      f instanceof Error && F.info(f.message, "error", 3e3);
    }
  });
  const Le = () => {
    v.files.forEach((c) => {
      c.listItem.remove();
    }), v.files = [], F.cancelAll(), xt(i, b, h, []);
  };
  return d?.addEventListener(ot, Le), z(), () => {
    d?.removeEventListener(ot, Le), F.destroy(), v.files = [];
  };
}
const as = {
  id: vt,
  kind: "field",
  match: (i) => !!i.target.querySelector(V),
  setup: async (i) => {
    const e = i.options || {}, t = i.form, r = /* @__PURE__ */ new WeakSet(), s = [];
    Qr(t);
    const n = (a) => {
      const l = /* @__PURE__ */ new Set();
      a instanceof HTMLElement && a.hasAttribute("data-formie-field-handle") && l.add(a), a instanceof Element && (a.querySelectorAll("[data-formie-field-handle]").forEach((d) => {
        d instanceof HTMLElement && l.add(d);
      }), a.querySelectorAll(V).forEach((d) => {
        const u = d.closest("[data-formie-field-handle]");
        u instanceof HTMLElement && l.add(u);
      })), l.forEach((d) => {
        r.has(d) || !d.querySelector(V) || (r.add(d), s.push(rs(d, t, e)));
      });
    };
    n(i.target);
    const o = (a) => {
      const l = a.detail;
      if (!Et(l))
        return;
      const d = l.row;
      d instanceof HTMLElement && n(d);
    };
    return t?.addEventListener(nt, o), Y.log("Module setup.", { count: s.length }), await i.emit("formie:module:upload-manager:init", {
      count: s.length
    }), {
      destroy: () => {
        t?.removeEventListener(nt, o), s.forEach((a) => {
          a();
        }), es(t), Y.log("Module destroy."), i.emit("formie:module:upload-manager:destroy", {});
      }
    };
  }
};
export {
  as as uploadManagerModule
};
