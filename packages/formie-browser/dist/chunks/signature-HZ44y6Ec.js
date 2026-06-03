import { g as C, d as M } from "./shared-DGn4SKv5.js";
import { e as T } from "./styles-C3aqgtek.js";
var E = class {
  x;
  y;
  pressure;
  time;
  constructor(r, t, e, i) {
    if (isNaN(r) || isNaN(t))
      throw new Error(`Point is invalid: (${r}, ${t})`);
    this.x = +r, this.y = +t, this.pressure = e || 0, this.time = i || Date.now();
  }
  distanceTo(r) {
    return Math.sqrt(
      Math.pow(this.x - r.x, 2) + Math.pow(this.y - r.y, 2)
    );
  }
  equals(r) {
    return this.x === r.x && this.y === r.y && this.pressure === r.pressure && this.time === r.time;
  }
  velocityFrom(r) {
    return this.time !== r.time ? this.distanceTo(r) / (this.time - r.time) : 0;
  }
}, L = class S {
  constructor(t, e, i, n, s, a) {
    this.startPoint = t, this.control2 = e, this.control1 = i, this.endPoint = n, this.startWidth = s, this.endWidth = a;
  }
  static fromPoints(t, e) {
    const i = this.calculateControlPoints(t[0], t[1], t[2]).c2, n = this.calculateControlPoints(t[1], t[2], t[3]).c1;
    return new S(t[1], i, n, t[2], e.start, e.end);
  }
  static calculateControlPoints(t, e, i) {
    const n = t.x - e.x, s = t.y - e.y, a = e.x - i.x, d = e.y - i.y, h = { x: (t.x + e.x) / 2, y: (t.y + e.y) / 2 }, l = { x: (e.x + i.x) / 2, y: (e.y + i.y) / 2 }, o = Math.sqrt(n * n + s * s), c = Math.sqrt(a * a + d * d), _ = h.x - l.x, f = h.y - l.y, m = o + c == 0 ? 0 : c / (o + c), u = { x: l.x + _ * m, y: l.y + f * m }, p = e.x - u.x, g = e.y - u.y;
    return {
      c1: new E(h.x + p, h.y + g),
      c2: new E(l.x + p, l.y + g)
    };
  }
  // Returns approximated length. Code taken from https://www.lemoda.net/maths/bezier-length/index.html.
  length() {
    let e = 0, i, n;
    for (let s = 0; s <= 10; s += 1) {
      const a = s / 10, d = this.point(
        a,
        this.startPoint.x,
        this.control1.x,
        this.control2.x,
        this.endPoint.x
      ), h = this.point(
        a,
        this.startPoint.y,
        this.control1.y,
        this.control2.y,
        this.endPoint.y
      );
      if (s > 0) {
        const l = d - i, o = h - n;
        e += Math.sqrt(l * l + o * o);
      }
      i = d, n = h;
    }
    return e;
  }
  // Calculate parametric value of x or y given t and the four point coordinates of a cubic bezier curve.
  point(t, e, i, n, s) {
    return e * (1 - t) * (1 - t) * (1 - t) + 3 * i * (1 - t) * (1 - t) * t + 3 * n * (1 - t) * t * t + s * t * t * t;
  }
}, U = class {
  /* tslint:disable: variable-name */
  _et;
  /* tslint:enable: variable-name */
  constructor() {
    try {
      this._et = new EventTarget();
    } catch {
      this._et = document;
    }
  }
  addEventListener(r, t, e) {
    this._et.addEventListener(r, t, e);
  }
  dispatchEvent(r) {
    return this._et.dispatchEvent(r);
  }
  removeEventListener(r, t, e) {
    this._et.removeEventListener(r, t, e);
  }
};
function W(r, t = 250) {
  let e = 0, i = null, n, s, a;
  const d = () => {
    e = Date.now(), i = null, n = r.apply(s, a), i || (s = null, a = []);
  };
  return function(...l) {
    const o = Date.now(), c = t - (o - e);
    return s = this, a = l, c <= 0 || c > t ? (i && (clearTimeout(i), i = null), e = o, n = r.apply(s, a), i || (s = null, a = [])) : i || (i = window.setTimeout(d, c)), n;
  };
}
var D = class x extends U {
  /* tslint:enable: variable-name */
  constructor(t, e = {}) {
    super(), this.canvas = t, this.velocityFilterWeight = e.velocityFilterWeight || 0.7, this.minWidth = e.minWidth || 0.5, this.maxWidth = e.maxWidth || 2.5, this.throttle = e.throttle ?? 16, this.minDistance = e.minDistance ?? 5, this.dotSize = e.dotSize || 0, this.penColor = e.penColor || "black", this.backgroundColor = e.backgroundColor || "rgba(0,0,0,0)", this.compositeOperation = e.compositeOperation || "source-over", this.canvasContextOptions = e.canvasContextOptions ?? {}, this._strokeMoveUpdate = this.throttle ? W(x.prototype._strokeUpdate, this.throttle) : x.prototype._strokeUpdate, this._handleMouseDown = this._handleMouseDown.bind(this), this._handleMouseMove = this._handleMouseMove.bind(this), this._handleMouseUp = this._handleMouseUp.bind(this), this._handleTouchStart = this._handleTouchStart.bind(this), this._handleTouchMove = this._handleTouchMove.bind(this), this._handleTouchEnd = this._handleTouchEnd.bind(this), this._handlePointerDown = this._handlePointerDown.bind(this), this._handlePointerMove = this._handlePointerMove.bind(this), this._handlePointerUp = this._handlePointerUp.bind(this), this._handlePointerCancel = this._handlePointerCancel.bind(this), this._handleTouchCancel = this._handleTouchCancel.bind(this), this._ctx = t.getContext(
      "2d",
      this.canvasContextOptions
    ), this.clear(), this.on();
  }
  // Public stuff
  dotSize;
  minWidth;
  maxWidth;
  penColor;
  minDistance;
  velocityFilterWeight;
  compositeOperation;
  backgroundColor;
  throttle;
  canvasContextOptions;
  // Private stuff
  /* tslint:disable: variable-name */
  _ctx;
  _drawingStroke = !1;
  _isEmpty = !0;
  _dataUrl;
  _dataUrlOptions;
  _lastPoints = [];
  // Stores up to 4 most recent points; used to generate a new curve
  _data = [];
  // Stores all points in groups (one group per line or dot)
  _lastVelocity = 0;
  _lastWidth = 0;
  _strokeMoveUpdate;
  _strokePointerId;
  clear() {
    const { _ctx: t, canvas: e } = this;
    t.fillStyle = this.backgroundColor, t.clearRect(0, 0, e.width, e.height), t.fillRect(0, 0, e.width, e.height), this._data = [], this._reset(this._getPointGroupOptions()), this._isEmpty = !0, this._dataUrl = void 0, this._dataUrlOptions = void 0, this._strokePointerId = void 0;
  }
  redraw() {
    const t = this._data, e = this._dataUrl, i = this._dataUrlOptions;
    this.clear(), e && this.fromDataURL(e, i), this.fromData(t, { clear: !1 });
  }
  fromDataURL(t, e = {}) {
    return new Promise((i, n) => {
      const s = new Image(), a = e.ratio || window.devicePixelRatio || 1, d = e.width || this.canvas.width / a, h = e.height || this.canvas.height / a, l = e.xOffset || 0, o = e.yOffset || 0;
      this._reset(this._getPointGroupOptions()), s.onload = () => {
        this._ctx.drawImage(s, l, o, d, h), i();
      }, s.onerror = (c) => {
        n(c);
      }, s.crossOrigin = "anonymous", s.src = t, this._isEmpty = !1, this._dataUrl = t, this._dataUrlOptions = { ...e };
    });
  }
  toDataURL(t = "image/png", e) {
    return t === "image/svg+xml" ? (typeof e != "object" && (e = void 0), `data:image/svg+xml;base64,${btoa(
      this.toSVG(e)
    )}`) : (typeof e != "number" && (e = void 0), this.canvas.toDataURL(t, e));
  }
  on() {
    this.canvas.style.touchAction = "none", this.canvas.style.msTouchAction = "none", this.canvas.style.userSelect = "none", this.canvas.style.webkitUserSelect = "none";
    const t = /Macintosh/.test(navigator.userAgent) && "ontouchstart" in document;
    window.PointerEvent && !t ? this._handlePointerEvents() : (this._handleMouseEvents(), "ontouchstart" in window && this._handleTouchEvents());
  }
  off() {
    this.canvas.style.touchAction = "auto", this.canvas.style.msTouchAction = "auto", this.canvas.style.userSelect = "auto", this.canvas.style.webkitUserSelect = "auto", this.canvas.removeEventListener("pointerdown", this._handlePointerDown), this.canvas.removeEventListener("mousedown", this._handleMouseDown), this.canvas.removeEventListener("touchstart", this._handleTouchStart), this._removeMoveUpEventListeners();
  }
  _getListenerFunctions() {
    const t = window.document === this.canvas.ownerDocument ? window : this.canvas.ownerDocument.defaultView ?? this.canvas.ownerDocument;
    return {
      addEventListener: t.addEventListener.bind(
        t
      ),
      removeEventListener: t.removeEventListener.bind(
        t
      )
    };
  }
  _removeMoveUpEventListeners() {
    const { removeEventListener: t } = this._getListenerFunctions();
    t("pointermove", this._handlePointerMove), t("pointerup", this._handlePointerUp), t("pointercancel", this._handlePointerCancel), t("mousemove", this._handleMouseMove), t("mouseup", this._handleMouseUp), t("touchmove", this._handleTouchMove), t("touchend", this._handleTouchEnd), t("touchcancel", this._handleTouchCancel);
  }
  isEmpty() {
    return this._isEmpty;
  }
  fromData(t, { clear: e = !0 } = {}) {
    e && this.clear(), this._fromData(
      t,
      this._drawCurve.bind(this),
      this._drawDot.bind(this)
    ), this._data = this._data.concat(t);
  }
  toData() {
    return this._data;
  }
  _isLeftButtonPressed(t, e) {
    return e ? t.buttons === 1 : (t.buttons & 1) === 1;
  }
  _pointerEventToSignatureEvent(t) {
    return {
      event: t,
      type: t.type,
      x: t.clientX,
      y: t.clientY,
      pressure: "pressure" in t ? t.pressure : 0
    };
  }
  _touchEventToSignatureEvent(t) {
    const e = t.changedTouches[0];
    return {
      event: t,
      type: t.type,
      x: e.clientX,
      y: e.clientY,
      pressure: e.force
    };
  }
  // Event handlers
  _handleMouseDown(t) {
    !this._isLeftButtonPressed(t, !0) || this._drawingStroke || this._strokeBegin(this._pointerEventToSignatureEvent(t));
  }
  _handleMouseMove(t) {
    if (!this._isLeftButtonPressed(t, !0) || !this._drawingStroke) {
      this._strokeEnd(this._pointerEventToSignatureEvent(t), !1);
      return;
    }
    this._strokeMoveUpdate(this._pointerEventToSignatureEvent(t));
  }
  _handleMouseUp(t) {
    this._isLeftButtonPressed(t) || this._strokeEnd(this._pointerEventToSignatureEvent(t));
  }
  _handleTouchStart(t) {
    t.targetTouches.length !== 1 || this._drawingStroke || (t.cancelable && t.preventDefault(), this._strokeBegin(this._touchEventToSignatureEvent(t)));
  }
  _handleTouchMove(t) {
    if (t.targetTouches.length === 1) {
      if (t.cancelable && t.preventDefault(), !this._drawingStroke) {
        this._strokeEnd(this._touchEventToSignatureEvent(t), !1);
        return;
      }
      this._strokeMoveUpdate(this._touchEventToSignatureEvent(t));
    }
  }
  _handleTouchEnd(t) {
    t.targetTouches.length === 0 && (t.cancelable && t.preventDefault(), this._strokeEnd(this._touchEventToSignatureEvent(t)));
  }
  _handlePointerCancel(t) {
    this._allowPointerId(t) && (t.preventDefault(), this._strokeEnd(this._pointerEventToSignatureEvent(t), !1));
  }
  _handleTouchCancel(t) {
    t.cancelable && t.preventDefault(), this._strokeEnd(this._touchEventToSignatureEvent(t), !1);
  }
  _getPointerId(t) {
    return t.persistentDeviceId || t.pointerId;
  }
  _allowPointerId(t, e = !1) {
    return typeof this._strokePointerId > "u" ? e : this._getPointerId(t) === this._strokePointerId;
  }
  _handlePointerDown(t) {
    this._drawingStroke || !this._isLeftButtonPressed(t) || !this._allowPointerId(t, !0) || (this._strokePointerId = this._getPointerId(t), t.preventDefault(), this._strokeBegin(this._pointerEventToSignatureEvent(t)));
  }
  _handlePointerMove(t) {
    if (this._allowPointerId(t)) {
      if (!this._isLeftButtonPressed(t, !0) || !this._drawingStroke) {
        this._strokeEnd(this._pointerEventToSignatureEvent(t), !1);
        return;
      }
      t.preventDefault(), this._strokeMoveUpdate(this._pointerEventToSignatureEvent(t));
    }
  }
  _handlePointerUp(t) {
    this._isLeftButtonPressed(t) || !this._allowPointerId(t) || (t.preventDefault(), this._strokeEnd(this._pointerEventToSignatureEvent(t)));
  }
  _getPointGroupOptions(t) {
    return {
      penColor: t && "penColor" in t ? t.penColor : this.penColor,
      dotSize: t && "dotSize" in t ? t.dotSize : this.dotSize,
      minWidth: t && "minWidth" in t ? t.minWidth : this.minWidth,
      maxWidth: t && "maxWidth" in t ? t.maxWidth : this.maxWidth,
      velocityFilterWeight: t && "velocityFilterWeight" in t ? t.velocityFilterWeight : this.velocityFilterWeight,
      compositeOperation: t && "compositeOperation" in t ? t.compositeOperation : this.compositeOperation
    };
  }
  // Private methods
  _strokeBegin(t) {
    if (!this.dispatchEvent(
      new CustomEvent("beginStroke", { detail: t, cancelable: !0 })
    ))
      return;
    const { addEventListener: i } = this._getListenerFunctions();
    switch (t.event.type) {
      case "mousedown":
        i("mousemove", this._handleMouseMove, {
          passive: !1
        }), i("mouseup", this._handleMouseUp, { passive: !1 });
        break;
      case "touchstart":
        i("touchmove", this._handleTouchMove, {
          passive: !1
        }), i("touchend", this._handleTouchEnd, { passive: !1 }), i("touchcancel", this._handleTouchCancel, { passive: !1 });
        break;
      case "pointerdown":
        i("pointermove", this._handlePointerMove, {
          passive: !1
        }), i("pointerup", this._handlePointerUp, {
          passive: !1
        }), i("pointercancel", this._handlePointerCancel, {
          passive: !1
        });
        break;
    }
    this._drawingStroke = !0;
    const n = this._getPointGroupOptions(), s = {
      ...n,
      points: []
    };
    this._data.push(s), this._reset(n), this._strokeUpdate(t);
  }
  _strokeUpdate(t) {
    if (!this._drawingStroke)
      return;
    if (this._data.length === 0) {
      this._strokeBegin(t);
      return;
    }
    this.dispatchEvent(
      new CustomEvent("beforeUpdateStroke", { detail: t })
    );
    const e = this._createPoint(t.x, t.y, t.pressure), i = this._data[this._data.length - 1], n = i.points, s = n.length > 0 && n[n.length - 1], a = s ? e.distanceTo(s) <= this.minDistance : !1, d = this._getPointGroupOptions(i);
    if (!s || !(s && a)) {
      const h = this._addPoint(e, d);
      s ? h && this._drawCurve(h, d) : this._drawDot(e, d), n.push({
        time: e.time,
        x: e.x,
        y: e.y,
        pressure: e.pressure
      });
    }
    this.dispatchEvent(new CustomEvent("afterUpdateStroke", { detail: t }));
  }
  _strokeEnd(t, e = !0) {
    this._removeMoveUpEventListeners(), this._drawingStroke && (e && this._strokeUpdate(t), this._drawingStroke = !1, this._strokePointerId = void 0, this.dispatchEvent(new CustomEvent("endStroke", { detail: t })));
  }
  _handlePointerEvents() {
    this._drawingStroke = !1, this.canvas.addEventListener("pointerdown", this._handlePointerDown, {
      passive: !1
    });
  }
  _handleMouseEvents() {
    this._drawingStroke = !1, this.canvas.addEventListener("mousedown", this._handleMouseDown, {
      passive: !1
    });
  }
  _handleTouchEvents() {
    this.canvas.addEventListener("touchstart", this._handleTouchStart, {
      passive: !1
    });
  }
  // Called when a new line is started
  _reset(t) {
    this._lastPoints = [], this._lastVelocity = 0, this._lastWidth = (t.minWidth + t.maxWidth) / 2, this._ctx.fillStyle = t.penColor, this._ctx.globalCompositeOperation = t.compositeOperation;
  }
  _createPoint(t, e, i) {
    const n = this.canvas.getBoundingClientRect();
    return new E(
      t - n.left,
      e - n.top,
      i,
      (/* @__PURE__ */ new Date()).getTime()
    );
  }
  // Add point to _lastPoints array and generate a new curve if there are enough points (i.e. 3)
  _addPoint(t, e) {
    const { _lastPoints: i } = this;
    if (i.push(t), i.length > 2) {
      i.length === 3 && i.unshift(i[0]);
      const n = this._calculateCurveWidths(
        i[1],
        i[2],
        e
      ), s = L.fromPoints(i, n);
      return i.shift(), s;
    }
    return null;
  }
  _calculateCurveWidths(t, e, i) {
    const n = i.velocityFilterWeight * e.velocityFrom(t) + (1 - i.velocityFilterWeight) * this._lastVelocity, s = this._strokeWidth(n, i), a = {
      end: s,
      start: this._lastWidth
    };
    return this._lastVelocity = n, this._lastWidth = s, a;
  }
  _strokeWidth(t, e) {
    return Math.max(e.maxWidth / (t + 1), e.minWidth);
  }
  _drawCurveSegment(t, e, i) {
    const n = this._ctx;
    n.moveTo(t, e), n.arc(t, e, i, 0, 2 * Math.PI, !1), this._isEmpty = !1;
  }
  _drawCurve(t, e) {
    const i = this._ctx, n = t.endWidth - t.startWidth, s = Math.ceil(t.length()) * 2;
    i.beginPath(), i.fillStyle = e.penColor;
    for (let a = 0; a < s; a += 1) {
      const d = a / s, h = d * d, l = h * d, o = 1 - d, c = o * o, _ = c * o;
      let f = _ * t.startPoint.x;
      f += 3 * c * d * t.control1.x, f += 3 * o * h * t.control2.x, f += l * t.endPoint.x;
      let m = _ * t.startPoint.y;
      m += 3 * c * d * t.control1.y, m += 3 * o * h * t.control2.y, m += l * t.endPoint.y;
      const u = Math.min(
        t.startWidth + l * n,
        e.maxWidth
      );
      this._drawCurveSegment(f, m, u);
    }
    i.closePath(), i.fill();
  }
  _drawDot(t, e) {
    const i = this._ctx, n = e.dotSize > 0 ? e.dotSize : (e.minWidth + e.maxWidth) / 2;
    i.beginPath(), this._drawCurveSegment(t.x, t.y, n), i.closePath(), i.fillStyle = e.penColor, i.fill();
  }
  _fromData(t, e, i) {
    for (const n of t) {
      const { points: s } = n, a = this._getPointGroupOptions(n);
      if (s.length > 1)
        for (let d = 0; d < s.length; d += 1) {
          const h = s[d], l = new E(
            h.x,
            h.y,
            h.pressure,
            h.time
          );
          d === 0 && this._reset(a);
          const o = this._addPoint(l, a);
          o && e(o, a);
        }
      else
        this._reset(a), i(s[0], a);
    }
  }
  toSVG({ includeBackgroundColor: t = !1, includeDataUrl: e = !1 } = {}) {
    const i = this._data, n = Math.max(window.devicePixelRatio || 1, 1), s = 0, a = 0, d = this.canvas.width / n, h = this.canvas.height / n, l = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    if (l.setAttribute("xmlns", "http://www.w3.org/2000/svg"), l.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink"), l.setAttribute("viewBox", `${s} ${a} ${d} ${h}`), l.setAttribute("width", d.toString()), l.setAttribute("height", h.toString()), t && this.backgroundColor) {
      const o = document.createElement("rect");
      o.setAttribute("width", "100%"), o.setAttribute("height", "100%"), o.setAttribute("fill", this.backgroundColor), l.appendChild(o);
    }
    if (e && this._dataUrl) {
      const o = this._dataUrlOptions?.ratio || window.devicePixelRatio || 1, c = this._dataUrlOptions?.width || this.canvas.width / o, _ = this._dataUrlOptions?.height || this.canvas.height / o, f = this._dataUrlOptions?.xOffset || 0, m = this._dataUrlOptions?.yOffset || 0, u = document.createElement("image");
      u.setAttribute("x", f.toString()), u.setAttribute("y", m.toString()), u.setAttribute("width", c.toString()), u.setAttribute("height", _.toString()), u.setAttribute("preserveAspectRatio", "none"), u.setAttribute("href", this._dataUrl), l.appendChild(u);
    }
    return this._fromData(
      i,
      (o, { penColor: c }) => {
        const _ = document.createElement("path");
        if (!isNaN(o.control1.x) && !isNaN(o.control1.y) && !isNaN(o.control2.x) && !isNaN(o.control2.y)) {
          const f = `M ${o.startPoint.x.toFixed(3)},${o.startPoint.y.toFixed(
            3
          )} C ${o.control1.x.toFixed(3)},${o.control1.y.toFixed(3)} ${o.control2.x.toFixed(3)},${o.control2.y.toFixed(3)} ${o.endPoint.x.toFixed(3)},${o.endPoint.y.toFixed(3)}`;
          _.setAttribute("d", f), _.setAttribute("stroke-width", (o.endWidth * 2.25).toFixed(3)), _.setAttribute("stroke", c), _.setAttribute("fill", "none"), _.setAttribute("stroke-linecap", "round"), l.appendChild(_);
        }
      },
      (o, { penColor: c, dotSize: _, minWidth: f, maxWidth: m }) => {
        const u = document.createElement("circle"), p = _ > 0 ? _ : (f + m) / 2;
        u.setAttribute("r", p.toString()), u.setAttribute("cx", o.x.toString()), u.setAttribute("cy", o.y.toString()), u.setAttribute("fill", c), l.appendChild(u);
      }
    ), l.outerHTML;
  }
};
const O = "@layer formie-theme{[data-formie-field-type=signature] .formie-field-control{position:relative;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error[data-formie-field-type=signature] .formie-signature-canvas{border-color:var(--formie-color-danger)}.formie-field-has-error[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{box-shadow:var(--formie-shadow-danger-focus)}[data-formie-field-type=signature] .formie-signature-canvas{display:block;width:var(--formie-signature-width);height:var(--formie-signature-height);border:var(--formie-signature-border);background:var(--formie-signature-background);border-radius:var(--formie-signature-border-radius);touch-action:none;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}[data-formie-field-type=signature] .formie-signature-remove-button{position:absolute;top:var(--formie-signature-remove-button-top);right:var(--formie-signature-remove-button-right);transform:var(--formie-signature-remove-button-transform);font-size:0;line-height:0}}", A = "input[data-formie-signature-input]", P = "canvas[data-formie-signature-canvas]", F = "[data-formie-signature-clear]", b = "signature";
T(b, [O]);
function I(r) {
  const t = r.getBoundingClientRect();
  return {
    width: Math.round(t.width),
    height: Math.round(t.height)
  };
}
function z(r, t) {
  if (!t)
    return;
  const e = new Image();
  e.src = t, e.onload = () => {
    const i = Math.max(window.devicePixelRatio || 1, 1), n = r.getContext("2d");
    n && n.drawImage(e, 0, 0, r.width / i, r.height / i);
  };
}
function R(r, t, e, i, n, s) {
  const a = parseFloat(s.penWeight || "2") || 2, d = i.parentElement instanceof HTMLElement ? i.parentElement : t, h = new D(i, {
    backgroundColor: s.backgroundColor || "rgba(255, 255, 255, 0)",
    penColor: s.penColor || "#000000",
    dotSize: a,
    minWidth: a,
    maxWidth: a
  }), l = () => {
    const { width: g, height: v } = I(i);
    if (!(g > 0) || !(v > 0))
      return;
    const w = Math.max(window.devicePixelRatio || 1, 1), y = i.getContext("2d");
    if (!y)
      return;
    const k = e.value || (h.isEmpty() ? "" : h.toDataURL());
    i.width = g * w, i.height = v * w, y.setTransform(1, 0, 0, 1, 0, 0), y.scale(w, w), h.clear(), z(i, k);
  }, o = (g = 0) => {
    window.setTimeout(() => {
      window.requestAnimationFrame(() => {
        l();
      });
    }, g);
  }, c = () => {
    o();
  }, _ = () => {
    o(100);
  }, f = typeof ResizeObserver > "u" ? null : new ResizeObserver(() => {
    o();
  }), m = (g) => {
    const v = e.value !== g;
    e.value = g, v && (e.dispatchEvent(new Event("input", { bubbles: !0 })), e.dispatchEvent(new Event("change", { bubbles: !0 })));
  }, u = () => {
    m(h.isEmpty() ? "" : h.toDataURL());
  }, p = () => {
    h.clear(), m("");
  };
  return h.addEventListener("endStroke", u), window.addEventListener("resize", c), r.addEventListener("formie:page:navigate:after", _), f?.observe(d), o(), n && n.addEventListener("click", p), M(t, b, "init", {
    signature: h
  }), () => {
    h.removeEventListener("endStroke", u), window.removeEventListener("resize", c), r.removeEventListener("formie:page:navigate:after", _), f?.disconnect(), n && n.removeEventListener("click", p), h.clear();
  };
}
const $ = {
  id: b,
  kind: "field",
  match: (r) => !!r.target.querySelector(P),
  setup: async (r) => {
    const t = r.options || {}, e = r.root instanceof HTMLElement ? r.root : r.target instanceof HTMLElement ? r.target : null;
    if (!e)
      return;
    const n = C(r).map((s) => {
      const a = s.querySelector(A), d = s.querySelector(P), h = s.querySelector(F);
      return !(a instanceof HTMLInputElement) || !(d instanceof HTMLCanvasElement) ? () => {
      } : R(e, s, a, d, h instanceof HTMLElement ? h : null, t);
    });
    return await r.emit("formie:module:signature:init", {
      count: n.length
    }), {
      destroy: () => {
        n.forEach((s) => {
          s();
        }), r.emit("formie:module:signature:destroy", {});
      }
    };
  }
};
export {
  $ as signatureModule
};
