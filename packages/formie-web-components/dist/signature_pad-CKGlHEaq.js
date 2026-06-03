var v = class {
  x;
  y;
  pressure;
  time;
  constructor(c, t, e, i) {
    if (isNaN(c) || isNaN(t))
      throw new Error(`Point is invalid: (${c}, ${t})`);
    this.x = +c, this.y = +t, this.pressure = e || 0, this.time = i || Date.now();
  }
  distanceTo(c) {
    return Math.sqrt(
      Math.pow(this.x - c.x, 2) + Math.pow(this.y - c.y, 2)
    );
  }
  equals(c) {
    return this.x === c.x && this.y === c.y && this.pressure === c.pressure && this.time === c.time;
  }
  velocityFrom(c) {
    return this.time !== c.time ? this.distanceTo(c) / (this.time - c.time) : 0;
  }
}, y = class x {
  constructor(t, e, i, s, n, r) {
    this.startPoint = t, this.control2 = e, this.control1 = i, this.endPoint = s, this.startWidth = n, this.endWidth = r;
  }
  static fromPoints(t, e) {
    const i = this.calculateControlPoints(t[0], t[1], t[2]).c2, s = this.calculateControlPoints(t[1], t[2], t[3]).c1;
    return new x(t[1], i, s, t[2], e.start, e.end);
  }
  static calculateControlPoints(t, e, i) {
    const s = t.x - e.x, n = t.y - e.y, r = e.x - i.x, h = e.y - i.y, l = { x: (t.x + e.x) / 2, y: (t.y + e.y) / 2 }, a = { x: (e.x + i.x) / 2, y: (e.y + i.y) / 2 }, o = Math.sqrt(s * s + n * n), d = Math.sqrt(r * r + h * h), _ = l.x - a.x, p = l.y - a.y, f = o + d == 0 ? 0 : d / (o + d), u = { x: a.x + _ * f, y: a.y + p * f }, m = e.x - u.x, w = e.y - u.y;
    return {
      c1: new v(l.x + m, l.y + w),
      c2: new v(a.x + m, a.y + w)
    };
  }
  // Returns approximated length. Code taken from https://www.lemoda.net/maths/bezier-length/index.html.
  length() {
    let e = 0, i, s;
    for (let n = 0; n <= 10; n += 1) {
      const r = n / 10, h = this.point(
        r,
        this.startPoint.x,
        this.control1.x,
        this.control2.x,
        this.endPoint.x
      ), l = this.point(
        r,
        this.startPoint.y,
        this.control1.y,
        this.control2.y,
        this.endPoint.y
      );
      if (n > 0) {
        const a = h - i, o = l - s;
        e += Math.sqrt(a * a + o * o);
      }
      i = h, s = l;
    }
    return e;
  }
  // Calculate parametric value of x or y given t and the four point coordinates of a cubic bezier curve.
  point(t, e, i, s, n) {
    return e * (1 - t) * (1 - t) * (1 - t) + 3 * i * (1 - t) * (1 - t) * t + 3 * s * (1 - t) * t * t + n * t * t * t;
  }
}, E = class {
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
  addEventListener(c, t, e) {
    this._et.addEventListener(c, t, e);
  }
  dispatchEvent(c) {
    return this._et.dispatchEvent(c);
  }
  removeEventListener(c, t, e) {
    this._et.removeEventListener(c, t, e);
  }
};
function P(c, t = 250) {
  let e = 0, i = null, s, n, r;
  const h = () => {
    e = Date.now(), i = null, s = c.apply(n, r), i || (n = null, r = []);
  };
  return function(...a) {
    const o = Date.now(), d = t - (o - e);
    return n = this, r = a, d <= 0 || d > t ? (i && (clearTimeout(i), i = null), e = o, s = c.apply(n, r), i || (n = null, r = [])) : i || (i = window.setTimeout(h, d)), s;
  };
}
var S = class g extends E {
  /* tslint:enable: variable-name */
  constructor(t, e = {}) {
    super(), this.canvas = t, this.velocityFilterWeight = e.velocityFilterWeight || 0.7, this.minWidth = e.minWidth || 0.5, this.maxWidth = e.maxWidth || 2.5, this.throttle = e.throttle ?? 16, this.minDistance = e.minDistance ?? 5, this.dotSize = e.dotSize || 0, this.penColor = e.penColor || "black", this.backgroundColor = e.backgroundColor || "rgba(0,0,0,0)", this.compositeOperation = e.compositeOperation || "source-over", this.canvasContextOptions = e.canvasContextOptions ?? {}, this._strokeMoveUpdate = this.throttle ? P(g.prototype._strokeUpdate, this.throttle) : g.prototype._strokeUpdate, this._handleMouseDown = this._handleMouseDown.bind(this), this._handleMouseMove = this._handleMouseMove.bind(this), this._handleMouseUp = this._handleMouseUp.bind(this), this._handleTouchStart = this._handleTouchStart.bind(this), this._handleTouchMove = this._handleTouchMove.bind(this), this._handleTouchEnd = this._handleTouchEnd.bind(this), this._handlePointerDown = this._handlePointerDown.bind(this), this._handlePointerMove = this._handlePointerMove.bind(this), this._handlePointerUp = this._handlePointerUp.bind(this), this._handlePointerCancel = this._handlePointerCancel.bind(this), this._handleTouchCancel = this._handleTouchCancel.bind(this), this._ctx = t.getContext(
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
      const n = new Image(), r = e.ratio || window.devicePixelRatio || 1, h = e.width || this.canvas.width / r, l = e.height || this.canvas.height / r, a = e.xOffset || 0, o = e.yOffset || 0;
      this._reset(this._getPointGroupOptions()), n.onload = () => {
        this._ctx.drawImage(n, a, o, h, l), i();
      }, n.onerror = (d) => {
        s(d);
      }, n.crossOrigin = "anonymous", n.src = t, this._isEmpty = !1, this._dataUrl = t, this._dataUrlOptions = { ...e };
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
    const s = this._getPointGroupOptions(), n = {
      ...s,
      points: []
    };
    this._data.push(n), this._reset(s), this._strokeUpdate(t);
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
    const e = this._createPoint(t.x, t.y, t.pressure), i = this._data[this._data.length - 1], s = i.points, n = s.length > 0 && s[s.length - 1], r = n ? e.distanceTo(n) <= this.minDistance : !1, h = this._getPointGroupOptions(i);
    if (!n || !(n && r)) {
      const l = this._addPoint(e, h);
      n ? l && this._drawCurve(l, h) : this._drawDot(e, h), s.push({
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
    return new v(
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
      ), n = y.fromPoints(i, s);
      return i.shift(), n;
    }
    return null;
  }
  _calculateCurveWidths(t, e, i) {
    const s = i.velocityFilterWeight * e.velocityFrom(t) + (1 - i.velocityFilterWeight) * this._lastVelocity, n = this._strokeWidth(s, i), r = {
      end: n,
      start: this._lastWidth
    };
    return this._lastVelocity = s, this._lastWidth = n, r;
  }
  _strokeWidth(t, e) {
    return Math.max(e.maxWidth / (t + 1), e.minWidth);
  }
  _drawCurveSegment(t, e, i) {
    const s = this._ctx;
    s.moveTo(t, e), s.arc(t, e, i, 0, 2 * Math.PI, !1), this._isEmpty = !1;
  }
  _drawCurve(t, e) {
    const i = this._ctx, s = t.endWidth - t.startWidth, n = Math.ceil(t.length()) * 2;
    i.beginPath(), i.fillStyle = e.penColor;
    for (let r = 0; r < n; r += 1) {
      const h = r / n, l = h * h, a = l * h, o = 1 - h, d = o * o, _ = d * o;
      let p = _ * t.startPoint.x;
      p += 3 * d * h * t.control1.x, p += 3 * o * l * t.control2.x, p += a * t.endPoint.x;
      let f = _ * t.startPoint.y;
      f += 3 * d * h * t.control1.y, f += 3 * o * l * t.control2.y, f += a * t.endPoint.y;
      const u = Math.min(
        t.startWidth + a * s,
        e.maxWidth
      );
      this._drawCurveSegment(p, f, u);
    }
    i.closePath(), i.fill();
  }
  _drawDot(t, e) {
    const i = this._ctx, s = e.dotSize > 0 ? e.dotSize : (e.minWidth + e.maxWidth) / 2;
    i.beginPath(), this._drawCurveSegment(t.x, t.y, s), i.closePath(), i.fillStyle = e.penColor, i.fill();
  }
  _fromData(t, e, i) {
    for (const s of t) {
      const { points: n } = s, r = this._getPointGroupOptions(s);
      if (n.length > 1)
        for (let h = 0; h < n.length; h += 1) {
          const l = n[h], a = new v(
            l.x,
            l.y,
            l.pressure,
            l.time
          );
          h === 0 && this._reset(r);
          const o = this._addPoint(a, r);
          o && e(o, r);
        }
      else
        this._reset(r), i(n[0], r);
    }
  }
  toSVG({ includeBackgroundColor: t = !1, includeDataUrl: e = !1 } = {}) {
    const i = this._data, s = Math.max(window.devicePixelRatio || 1, 1), n = 0, r = 0, h = this.canvas.width / s, l = this.canvas.height / s, a = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    if (a.setAttribute("xmlns", "http://www.w3.org/2000/svg"), a.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink"), a.setAttribute("viewBox", `${n} ${r} ${h} ${l}`), a.setAttribute("width", h.toString()), a.setAttribute("height", l.toString()), t && this.backgroundColor) {
      const o = document.createElement("rect");
      o.setAttribute("width", "100%"), o.setAttribute("height", "100%"), o.setAttribute("fill", this.backgroundColor), a.appendChild(o);
    }
    if (e && this._dataUrl) {
      const o = this._dataUrlOptions?.ratio || window.devicePixelRatio || 1, d = this._dataUrlOptions?.width || this.canvas.width / o, _ = this._dataUrlOptions?.height || this.canvas.height / o, p = this._dataUrlOptions?.xOffset || 0, f = this._dataUrlOptions?.yOffset || 0, u = document.createElement("image");
      u.setAttribute("x", p.toString()), u.setAttribute("y", f.toString()), u.setAttribute("width", d.toString()), u.setAttribute("height", _.toString()), u.setAttribute("preserveAspectRatio", "none"), u.setAttribute("href", this._dataUrl), a.appendChild(u);
    }
    return this._fromData(
      i,
      (o, { penColor: d }) => {
        const _ = document.createElement("path");
        if (!isNaN(o.control1.x) && !isNaN(o.control1.y) && !isNaN(o.control2.x) && !isNaN(o.control2.y)) {
          const p = `M ${o.startPoint.x.toFixed(3)},${o.startPoint.y.toFixed(
            3
          )} C ${o.control1.x.toFixed(3)},${o.control1.y.toFixed(3)} ${o.control2.x.toFixed(3)},${o.control2.y.toFixed(3)} ${o.endPoint.x.toFixed(3)},${o.endPoint.y.toFixed(3)}`;
          _.setAttribute("d", p), _.setAttribute("stroke-width", (o.endWidth * 2.25).toFixed(3)), _.setAttribute("stroke", d), _.setAttribute("fill", "none"), _.setAttribute("stroke-linecap", "round"), a.appendChild(_);
        }
      },
      (o, { penColor: d, dotSize: _, minWidth: p, maxWidth: f }) => {
        const u = document.createElement("circle"), m = _ > 0 ? _ : (p + f) / 2;
        u.setAttribute("r", m.toString()), u.setAttribute("cx", o.x.toString()), u.setAttribute("cy", o.y.toString()), u.setAttribute("fill", d), a.appendChild(u);
      }
    ), a.outerHTML;
  }
};
export {
  S as default
};
