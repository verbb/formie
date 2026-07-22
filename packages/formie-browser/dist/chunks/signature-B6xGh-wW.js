import { g as H, d as q } from "./shared-BDEKVuB5.js";
import { e as Y } from "./styles-C3aqgtek.js";
var E = class {
  x;
  y;
  pressure;
  time;
  constructor(n, t, e, i) {
    if (isNaN(n) || isNaN(t))
      throw new Error(`Point is invalid: (${n}, ${t})`);
    this.x = +n, this.y = +t, this.pressure = e || 0, this.time = i || Date.now();
  }
  distanceTo(n) {
    return Math.sqrt(
      Math.pow(this.x - n.x, 2) + Math.pow(this.y - n.y, 2)
    );
  }
  equals(n) {
    return this.x === n.x && this.y === n.y && this.pressure === n.pressure && this.time === n.time;
  }
  velocityFrom(n) {
    return this.time !== n.time ? this.distanceTo(n) / (this.time - n.time) : 0;
  }
}, X = class z {
  constructor(t, e, i, s, o, a) {
    this.startPoint = t, this.control2 = e, this.control1 = i, this.endPoint = s, this.startWidth = o, this.endWidth = a;
  }
  static fromPoints(t, e) {
    const i = this.calculateControlPoints(t[0], t[1], t[2]).c2, s = this.calculateControlPoints(t[1], t[2], t[3]).c1;
    return new z(t[1], i, s, t[2], e.start, e.end);
  }
  static calculateControlPoints(t, e, i) {
    const s = t.x - e.x, o = t.y - e.y, a = e.x - i.x, d = e.y - i.y, c = { x: (t.x + e.x) / 2, y: (t.y + e.y) / 2 }, h = { x: (e.x + i.x) / 2, y: (e.y + i.y) / 2 }, r = Math.sqrt(s * s + o * o), l = Math.sqrt(a * a + d * d), u = c.x - h.x, f = c.y - h.y, g = r + l == 0 ? 0 : l / (r + l), m = { x: h.x + u * g, y: h.y + f * g }, p = e.x - m.x, v = e.y - m.y;
    return {
      c1: new E(c.x + p, c.y + v),
      c2: new E(h.x + p, h.y + v)
    };
  }
  // Returns approximated length. Code taken from https://www.lemoda.net/maths/bezier-length/index.html.
  length() {
    let e = 0, i, s;
    for (let o = 0; o <= 10; o += 1) {
      const a = o / 10, d = this.point(
        a,
        this.startPoint.x,
        this.control1.x,
        this.control2.x,
        this.endPoint.x
      ), c = this.point(
        a,
        this.startPoint.y,
        this.control1.y,
        this.control2.y,
        this.endPoint.y
      );
      if (o > 0) {
        const h = d - i, r = c - s;
        e += Math.sqrt(h * h + r * r);
      }
      i = d, s = c;
    }
    return e;
  }
  // Calculate parametric value of x or y given t and the four point coordinates of a cubic bezier curve.
  point(t, e, i, s, o) {
    return e * (1 - t) * (1 - t) * (1 - t) + 3 * i * (1 - t) * (1 - t) * t + 3 * s * (1 - t) * t * t + o * t * t * t;
  }
}, j = class {
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
  addEventListener(n, t, e) {
    this._et.addEventListener(n, t, e);
  }
  dispatchEvent(n) {
    return this._et.dispatchEvent(n);
  }
  removeEventListener(n, t, e) {
    this._et.removeEventListener(n, t, e);
  }
};
function Z(n, t = 250) {
  let e = 0, i = null, s, o, a;
  const d = () => {
    e = Date.now(), i = null, s = n.apply(o, a), i || (o = null, a = []);
  };
  return function(...h) {
    const r = Date.now(), l = t - (r - e);
    return o = this, a = h, l <= 0 || l > t ? (i && (clearTimeout(i), i = null), e = r, s = n.apply(o, a), i || (o = null, a = [])) : i || (i = window.setTimeout(d, l)), s;
  };
}
var J = class T extends j {
  /* tslint:enable: variable-name */
  constructor(t, e = {}) {
    super(), this.canvas = t, this.velocityFilterWeight = e.velocityFilterWeight || 0.7, this.minWidth = e.minWidth || 0.5, this.maxWidth = e.maxWidth || 2.5, this.throttle = e.throttle ?? 16, this.minDistance = e.minDistance ?? 5, this.dotSize = e.dotSize || 0, this.penColor = e.penColor || "black", this.backgroundColor = e.backgroundColor || "rgba(0,0,0,0)", this.compositeOperation = e.compositeOperation || "source-over", this.canvasContextOptions = e.canvasContextOptions ?? {}, this._strokeMoveUpdate = this.throttle ? Z(T.prototype._strokeUpdate, this.throttle) : T.prototype._strokeUpdate, this._handleMouseDown = this._handleMouseDown.bind(this), this._handleMouseMove = this._handleMouseMove.bind(this), this._handleMouseUp = this._handleMouseUp.bind(this), this._handleTouchStart = this._handleTouchStart.bind(this), this._handleTouchMove = this._handleTouchMove.bind(this), this._handleTouchEnd = this._handleTouchEnd.bind(this), this._handlePointerDown = this._handlePointerDown.bind(this), this._handlePointerMove = this._handlePointerMove.bind(this), this._handlePointerUp = this._handlePointerUp.bind(this), this._handlePointerCancel = this._handlePointerCancel.bind(this), this._handleTouchCancel = this._handleTouchCancel.bind(this), this._ctx = t.getContext(
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
    return new Promise((i, s) => {
      const o = new Image(), a = e.ratio || window.devicePixelRatio || 1, d = e.width || this.canvas.width / a, c = e.height || this.canvas.height / a, h = e.xOffset || 0, r = e.yOffset || 0;
      this._reset(this._getPointGroupOptions()), o.onload = () => {
        this._ctx.drawImage(o, h, r, d, c), i();
      }, o.onerror = (l) => {
        s(l);
      }, o.crossOrigin = "anonymous", o.src = t, this._isEmpty = !1, this._dataUrl = t, this._dataUrlOptions = { ...e };
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
    const s = this._getPointGroupOptions(), o = {
      ...s,
      points: []
    };
    this._data.push(o), this._reset(s), this._strokeUpdate(t);
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
    const e = this._createPoint(t.x, t.y, t.pressure), i = this._data[this._data.length - 1], s = i.points, o = s.length > 0 && s[s.length - 1], a = o ? e.distanceTo(o) <= this.minDistance : !1, d = this._getPointGroupOptions(i);
    if (!o || !(o && a)) {
      const c = this._addPoint(e, d);
      o ? c && this._drawCurve(c, d) : this._drawDot(e, d), s.push({
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
    const s = this.canvas.getBoundingClientRect();
    return new E(
      t - s.left,
      e - s.top,
      i,
      (/* @__PURE__ */ new Date()).getTime()
    );
  }
  // Add point to _lastPoints array and generate a new curve if there are enough points (i.e. 3)
  _addPoint(t, e) {
    const { _lastPoints: i } = this;
    if (i.push(t), i.length > 2) {
      i.length === 3 && i.unshift(i[0]);
      const s = this._calculateCurveWidths(
        i[1],
        i[2],
        e
      ), o = X.fromPoints(i, s);
      return i.shift(), o;
    }
    return null;
  }
  _calculateCurveWidths(t, e, i) {
    const s = i.velocityFilterWeight * e.velocityFrom(t) + (1 - i.velocityFilterWeight) * this._lastVelocity, o = this._strokeWidth(s, i), a = {
      end: o,
      start: this._lastWidth
    };
    return this._lastVelocity = s, this._lastWidth = o, a;
  }
  _strokeWidth(t, e) {
    return Math.max(e.maxWidth / (t + 1), e.minWidth);
  }
  _drawCurveSegment(t, e, i) {
    const s = this._ctx;
    s.moveTo(t, e), s.arc(t, e, i, 0, 2 * Math.PI, !1), this._isEmpty = !1;
  }
  _drawCurve(t, e) {
    const i = this._ctx, s = t.endWidth - t.startWidth, o = Math.ceil(t.length()) * 2;
    i.beginPath(), i.fillStyle = e.penColor;
    for (let a = 0; a < o; a += 1) {
      const d = a / o, c = d * d, h = c * d, r = 1 - d, l = r * r, u = l * r;
      let f = u * t.startPoint.x;
      f += 3 * l * d * t.control1.x, f += 3 * r * c * t.control2.x, f += h * t.endPoint.x;
      let g = u * t.startPoint.y;
      g += 3 * l * d * t.control1.y, g += 3 * r * c * t.control2.y, g += h * t.endPoint.y;
      const m = Math.min(
        t.startWidth + h * s,
        e.maxWidth
      );
      this._drawCurveSegment(f, g, m);
    }
    i.closePath(), i.fill();
  }
  _drawDot(t, e) {
    const i = this._ctx, s = e.dotSize > 0 ? e.dotSize : (e.minWidth + e.maxWidth) / 2;
    i.beginPath(), this._drawCurveSegment(t.x, t.y, s), i.closePath(), i.fillStyle = e.penColor, i.fill();
  }
  _fromData(t, e, i) {
    for (const s of t) {
      const { points: o } = s, a = this._getPointGroupOptions(s);
      if (o.length > 1)
        for (let d = 0; d < o.length; d += 1) {
          const c = o[d], h = new E(
            c.x,
            c.y,
            c.pressure,
            c.time
          );
          d === 0 && this._reset(a);
          const r = this._addPoint(h, a);
          r && e(r, a);
        }
      else
        this._reset(a), i(o[0], a);
    }
  }
  toSVG({ includeBackgroundColor: t = !1, includeDataUrl: e = !1 } = {}) {
    const i = this._data, s = Math.max(window.devicePixelRatio || 1, 1), o = 0, a = 0, d = this.canvas.width / s, c = this.canvas.height / s, h = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    if (h.setAttribute("xmlns", "http://www.w3.org/2000/svg"), h.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink"), h.setAttribute("viewBox", `${o} ${a} ${d} ${c}`), h.setAttribute("width", d.toString()), h.setAttribute("height", c.toString()), t && this.backgroundColor) {
      const r = document.createElement("rect");
      r.setAttribute("width", "100%"), r.setAttribute("height", "100%"), r.setAttribute("fill", this.backgroundColor), h.appendChild(r);
    }
    if (e && this._dataUrl) {
      const r = this._dataUrlOptions?.ratio || window.devicePixelRatio || 1, l = this._dataUrlOptions?.width || this.canvas.width / r, u = this._dataUrlOptions?.height || this.canvas.height / r, f = this._dataUrlOptions?.xOffset || 0, g = this._dataUrlOptions?.yOffset || 0, m = document.createElement("image");
      m.setAttribute("x", f.toString()), m.setAttribute("y", g.toString()), m.setAttribute("width", l.toString()), m.setAttribute("height", u.toString()), m.setAttribute("preserveAspectRatio", "none"), m.setAttribute("href", this._dataUrl), h.appendChild(m);
    }
    return this._fromData(
      i,
      (r, { penColor: l }) => {
        const u = document.createElement("path");
        if (!isNaN(r.control1.x) && !isNaN(r.control1.y) && !isNaN(r.control2.x) && !isNaN(r.control2.y)) {
          const f = `M ${r.startPoint.x.toFixed(3)},${r.startPoint.y.toFixed(
            3
          )} C ${r.control1.x.toFixed(3)},${r.control1.y.toFixed(3)} ${r.control2.x.toFixed(3)},${r.control2.y.toFixed(3)} ${r.endPoint.x.toFixed(3)},${r.endPoint.y.toFixed(3)}`;
          u.setAttribute("d", f), u.setAttribute("stroke-width", (r.endWidth * 2.25).toFixed(3)), u.setAttribute("stroke", l), u.setAttribute("fill", "none"), u.setAttribute("stroke-linecap", "round"), h.appendChild(u);
        }
      },
      (r, { penColor: l, dotSize: u, minWidth: f, maxWidth: g }) => {
        const m = document.createElement("circle"), p = u > 0 ? u : (f + g) / 2;
        m.setAttribute("r", p.toString()), m.setAttribute("cx", r.x.toString()), m.setAttribute("cy", r.y.toString()), m.setAttribute("fill", l), h.appendChild(m);
      }
    ), h.outerHTML;
  }
};
const K = "@layer formie-theme{[data-formie-field-type=signature] .formie-field-control{position:relative;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error[data-formie-field-type=signature] .formie-signature-canvas{border-color:var(--formie-color-danger)}.formie-field-has-error[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{box-shadow:var(--formie-shadow-danger-focus)}[data-formie-field-type=signature] .formie-signature-canvas{display:block;width:var(--formie-signature-width);min-height:var(--formie-signature-height);height:auto;border:var(--formie-signature-border);background:var(--formie-signature-background);border-radius:var(--formie-signature-border-radius);touch-action:none;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}[data-formie-field-type=signature] .formie-signature-remove-button{position:absolute;top:var(--formie-signature-remove-button-top);right:var(--formie-signature-remove-button-right);transform:var(--formie-signature-remove-button-transform);font-size:0;line-height:0}[data-formie-field-type=signature] .formie-signature-pad{position:relative}[data-formie-field-type=signature] .formie-signature-message{margin:0;padding:var(--formie-space-3);border:var(--formie-signature-border);border-radius:var(--formie-signature-border-radius);background:var(--formie-signature-background);color:var(--formie-color-text-muted);font-size:var(--formie-font-size-sm);line-height:var(--formie-line-height-base)}[data-formie-field-type=signature].formie-signature-has-message .formie-signature-canvas{display:none}}", Q = "input[data-formie-signature-input]", R = "canvas[data-formie-signature-canvas]", tt = "[data-formie-signature-clear]", et = "[data-formie-signature-message]", k = "signature", it = 20, nt = 100, st = [
  "hidden",
  "data-formie-conditionally-hidden",
  "data-formie-page-hidden",
  "data-formie-row-hidden"
];
Y(k, [K]);
function N(n) {
  const t = n.getBoundingClientRect();
  return {
    width: Math.round(t.width),
    height: Math.round(t.height)
  };
}
function ot() {
  const n = document.createElement("canvas");
  return typeof n.getContext == "function" && !!n.getContext("2d");
}
function B(n) {
  const t = n.querySelector(et);
  return t instanceof HTMLElement ? t : null;
}
function rt(n) {
  const t = B(n);
  return {
    noCanvas: t?.dataset.formieSignatureMessageNoCanvas || "This browser does not support canvas, which is required for signatures.",
    initFailed: t?.dataset.formieSignatureMessageInitFailed || "The signature pad could not be loaded. Try refreshing the page."
  };
}
function P(n, t, e) {
  const i = B(n);
  if (i) {
    if (e) {
      i.textContent = e, i.hidden = !1, t.setAttribute("aria-hidden", "true"), n.classList.add("formie-signature-has-message");
      return;
    }
    i.textContent = "", i.hidden = !0, t.removeAttribute("aria-hidden"), n.classList.remove("formie-signature-has-message");
  }
}
function C(n) {
  return !n.hasAttribute("hidden") && !n.hasAttribute("data-formie-conditionally-hidden") && !n.hasAttribute("data-formie-page-hidden") && !n.hasAttribute("data-formie-row-hidden") && n.getClientRects().length > 0;
}
function at(n, t) {
  if (!t)
    return;
  const e = new Image();
  e.src = t, e.onload = () => {
    const i = Math.max(window.devicePixelRatio || 1, 1), s = n.getContext("2d");
    s && s.drawImage(e, 0, 0, n.width / i, n.height / i);
  };
}
function ht(n) {
  return new Promise((t) => {
    const e = new Image();
    e.onload = () => {
      t({
        width: e.naturalWidth,
        height: e.naturalHeight
      });
    }, e.onerror = () => t(null), e.src = n;
  });
}
async function $(n, t) {
  const e = await ht(t);
  if (!e?.width || !e?.height)
    return;
  const { width: i } = N(n);
  if (!(i > 0))
    return;
  const s = Math.max(1, Math.round(i * (e.height / e.width)));
  n.style.height = `${s}px`;
}
async function dt(n, t, e) {
  if (e) {
    await $(t, e);
    try {
      await n.fromDataURL(e);
    } catch {
      at(t, e);
    }
  }
}
function ct(n, t, e, i, s, o) {
  const a = rt(t);
  if (!ot())
    return P(t, i, a.noCanvas), () => {
    };
  const d = parseFloat(o.penWeight || "2") || 2, c = i.parentElement instanceof HTMLElement ? i.parentElement : t;
  let h = 0, r = null, l = !1, u = !1;
  const f = new J(i, {
    backgroundColor: o.backgroundColor || "rgba(255, 255, 255, 0)",
    penColor: o.penColor || "#000000",
    dotSize: d,
    minWidth: d,
    maxWidth: d
  }), g = () => {
    r !== null && (window.clearTimeout(r), r = null);
  }, m = () => {
    l || (l = !0, u = !1, P(t, i, null), q(t, k, "init", {
      signature: f
    }));
  }, p = () => {
    l || u || (u = !0, P(t, i, a.initFailed));
  }, v = async () => {
    const _ = e.value || (f.isEmpty() ? "" : f.toDataURL());
    _ && await $(i, _);
    const { width: w, height: F } = N(i);
    if (!(w > 0) || !(F > 0))
      return !1;
    const y = Math.max(window.devicePixelRatio || 1, 1), S = i.getContext("2d");
    return S ? (i.width = w * y, i.height = F * y, S.setTransform(1, 0, 0, 1, 0, 0), S.scale(y, y), f.clear(), await dt(f, i, _), m(), !0) : !1;
  }, V = () => {
    if (!(l || u)) {
      if (h >= it) {
        p();
        return;
      }
      g(), r = window.setTimeout(() => {
        r = null, h += 1, M();
      }, nt);
    }
  }, M = async () => {
    if (!await v()) {
      V();
      return;
    }
    g(), h = 0;
  }, b = (_ = 0) => {
    window.setTimeout(() => {
      window.requestAnimationFrame(() => {
        M();
      });
    }, _);
  }, x = () => {
    C(t) && !l && (u = !1), b();
  }, L = () => {
    x();
  }, U = () => {
    C(t) && (h = 0, u = !1, b(100));
  }, G = () => {
    C(t) && (h = 0, u = !1, x());
  }, W = typeof ResizeObserver > "u" ? null : new ResizeObserver(() => {
    x();
  }), D = new MutationObserver(() => {
    G();
  }), O = (_) => {
    const w = e.value !== _;
    e.value = _, w && (e.dispatchEvent(new Event("input", { bubbles: !0 })), e.dispatchEvent(new Event("change", { bubbles: !0 })));
  }, A = () => {
    O(f.isEmpty() ? "" : f.toDataURL());
  }, I = () => {
    f.clear(), O("");
  };
  return f.addEventListener("endStroke", A), window.addEventListener("resize", L), n.addEventListener("formie:page:navigate:after", U), W?.observe(c), D.observe(t, {
    attributes: !0,
    attributeFilter: [...st]
  }), b(), s && s.addEventListener("click", I), () => {
    g(), f.removeEventListener("endStroke", A), window.removeEventListener("resize", L), n.removeEventListener("formie:page:navigate:after", U), W?.disconnect(), D.disconnect(), s && s.removeEventListener("click", I), f.clear();
  };
}
const ft = {
  id: k,
  kind: "field",
  match: (n) => !!n.target.querySelector(R),
  setup: async (n) => {
    const t = n.options || {}, e = n.root instanceof HTMLElement ? n.root : n.target instanceof HTMLElement ? n.target : null;
    if (!e)
      return;
    const s = H(n).map((o) => {
      const a = o.querySelector(Q), d = o.querySelector(R), c = o.querySelector(tt);
      return !(a instanceof HTMLInputElement) || !(d instanceof HTMLCanvasElement) ? () => {
      } : ct(e, o, a, d, c instanceof HTMLElement ? c : null, t);
    });
    return await n.emit("formie:module:signature:init", {
      count: s.length
    }), {
      destroy: () => {
        s.forEach((o) => {
          o();
        }), n.emit("formie:module:signature:destroy", {});
      }
    };
  }
};
export {
  ft as signatureModule
};
