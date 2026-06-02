import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as getModuleFieldContainers, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
//#region ../../node_modules/signature_pad/dist/signature_pad.js
/*!
* Signature Pad v5.1.3 | https://github.com/szimek/signature_pad
* (c) 2025 Szymon Nowak | Released under the MIT license
*/
var Point = class {
	x;
	y;
	pressure;
	time;
	constructor(x, y, pressure, time) {
		if (isNaN(x) || isNaN(y)) throw new Error(`Point is invalid: (${x}, ${y})`);
		this.x = +x;
		this.y = +y;
		this.pressure = pressure || 0;
		this.time = time || Date.now();
	}
	distanceTo(start) {
		return Math.sqrt(Math.pow(this.x - start.x, 2) + Math.pow(this.y - start.y, 2));
	}
	equals(other) {
		return this.x === other.x && this.y === other.y && this.pressure === other.pressure && this.time === other.time;
	}
	velocityFrom(start) {
		return this.time !== start.time ? this.distanceTo(start) / (this.time - start.time) : 0;
	}
};
var Bezier = class _Bezier {
	constructor(startPoint, control2, control1, endPoint, startWidth, endWidth) {
		this.startPoint = startPoint;
		this.control2 = control2;
		this.control1 = control1;
		this.endPoint = endPoint;
		this.startWidth = startWidth;
		this.endWidth = endWidth;
	}
	static fromPoints(points, widths) {
		const c2 = this.calculateControlPoints(points[0], points[1], points[2]).c2;
		const c3 = this.calculateControlPoints(points[1], points[2], points[3]).c1;
		return new _Bezier(points[1], c2, c3, points[2], widths.start, widths.end);
	}
	static calculateControlPoints(s1, s2, s3) {
		const dx1 = s1.x - s2.x;
		const dy1 = s1.y - s2.y;
		const dx2 = s2.x - s3.x;
		const dy2 = s2.y - s3.y;
		const m1 = {
			x: (s1.x + s2.x) / 2,
			y: (s1.y + s2.y) / 2
		};
		const m2 = {
			x: (s2.x + s3.x) / 2,
			y: (s2.y + s3.y) / 2
		};
		const l1 = Math.sqrt(dx1 * dx1 + dy1 * dy1);
		const l2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
		const dxm = m1.x - m2.x;
		const dym = m1.y - m2.y;
		const k = l1 + l2 == 0 ? 0 : l2 / (l1 + l2);
		const cm = {
			x: m2.x + dxm * k,
			y: m2.y + dym * k
		};
		const tx = s2.x - cm.x;
		const ty = s2.y - cm.y;
		return {
			c1: new Point(m1.x + tx, m1.y + ty),
			c2: new Point(m2.x + tx, m2.y + ty)
		};
	}
	length() {
		const steps = 10;
		let length = 0;
		let px;
		let py;
		for (let i = 0; i <= steps; i += 1) {
			const t = i / steps;
			const cx = this.point(t, this.startPoint.x, this.control1.x, this.control2.x, this.endPoint.x);
			const cy = this.point(t, this.startPoint.y, this.control1.y, this.control2.y, this.endPoint.y);
			if (i > 0) {
				const xdiff = cx - px;
				const ydiff = cy - py;
				length += Math.sqrt(xdiff * xdiff + ydiff * ydiff);
			}
			px = cx;
			py = cy;
		}
		return length;
	}
	point(t, start, c1, c2, end) {
		return start * (1 - t) * (1 - t) * (1 - t) + 3 * c1 * (1 - t) * (1 - t) * t + 3 * c2 * (1 - t) * t * t + end * t * t * t;
	}
};
var SignatureEventTarget = class {
	_et;
	constructor() {
		try {
			this._et = new EventTarget();
		} catch {
			this._et = document;
		}
	}
	addEventListener(type, listener, options) {
		this._et.addEventListener(type, listener, options);
	}
	dispatchEvent(event) {
		return this._et.dispatchEvent(event);
	}
	removeEventListener(type, callback, options) {
		this._et.removeEventListener(type, callback, options);
	}
};
function throttle(fn, wait = 250) {
	let previous = 0;
	let timeout = null;
	let result;
	let storedContext;
	let storedArgs;
	const later = () => {
		previous = Date.now();
		timeout = null;
		result = fn.apply(storedContext, storedArgs);
		if (!timeout) {
			storedContext = null;
			storedArgs = [];
		}
	};
	return function wrapper(...args) {
		const now = Date.now();
		const remaining = wait - (now - previous);
		storedContext = this;
		storedArgs = args;
		if (remaining <= 0 || remaining > wait) {
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}
			previous = now;
			result = fn.apply(storedContext, storedArgs);
			if (!timeout) {
				storedContext = null;
				storedArgs = [];
			}
		} else if (!timeout) timeout = window.setTimeout(later, remaining);
		return result;
	};
}
var SignaturePad = class _SignaturePad extends SignatureEventTarget {
	constructor(canvas, options = {}) {
		super();
		this.canvas = canvas;
		this.velocityFilterWeight = options.velocityFilterWeight || .7;
		this.minWidth = options.minWidth || .5;
		this.maxWidth = options.maxWidth || 2.5;
		this.throttle = options.throttle ?? 16;
		this.minDistance = options.minDistance ?? 5;
		this.dotSize = options.dotSize || 0;
		this.penColor = options.penColor || "black";
		this.backgroundColor = options.backgroundColor || "rgba(0,0,0,0)";
		this.compositeOperation = options.compositeOperation || "source-over";
		this.canvasContextOptions = options.canvasContextOptions ?? {};
		this._strokeMoveUpdate = this.throttle ? throttle(_SignaturePad.prototype._strokeUpdate, this.throttle) : _SignaturePad.prototype._strokeUpdate;
		this._handleMouseDown = this._handleMouseDown.bind(this);
		this._handleMouseMove = this._handleMouseMove.bind(this);
		this._handleMouseUp = this._handleMouseUp.bind(this);
		this._handleTouchStart = this._handleTouchStart.bind(this);
		this._handleTouchMove = this._handleTouchMove.bind(this);
		this._handleTouchEnd = this._handleTouchEnd.bind(this);
		this._handlePointerDown = this._handlePointerDown.bind(this);
		this._handlePointerMove = this._handlePointerMove.bind(this);
		this._handlePointerUp = this._handlePointerUp.bind(this);
		this._handlePointerCancel = this._handlePointerCancel.bind(this);
		this._handleTouchCancel = this._handleTouchCancel.bind(this);
		this._ctx = canvas.getContext("2d", this.canvasContextOptions);
		this.clear();
		this.on();
	}
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
	_ctx;
	_drawingStroke = false;
	_isEmpty = true;
	_dataUrl;
	_dataUrlOptions;
	_lastPoints = [];
	_data = [];
	_lastVelocity = 0;
	_lastWidth = 0;
	_strokeMoveUpdate;
	_strokePointerId;
	clear() {
		const { _ctx: ctx, canvas } = this;
		ctx.fillStyle = this.backgroundColor;
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		this._data = [];
		this._reset(this._getPointGroupOptions());
		this._isEmpty = true;
		this._dataUrl = void 0;
		this._dataUrlOptions = void 0;
		this._strokePointerId = void 0;
	}
	redraw() {
		const data = this._data;
		const dataUrl = this._dataUrl;
		const dataUrlOptions = this._dataUrlOptions;
		this.clear();
		if (dataUrl) this.fromDataURL(dataUrl, dataUrlOptions);
		this.fromData(data, { clear: false });
	}
	fromDataURL(dataUrl, options = {}) {
		return new Promise((resolve, reject) => {
			const image = new Image();
			const ratio = options.ratio || window.devicePixelRatio || 1;
			const width = options.width || this.canvas.width / ratio;
			const height = options.height || this.canvas.height / ratio;
			const xOffset = options.xOffset || 0;
			const yOffset = options.yOffset || 0;
			this._reset(this._getPointGroupOptions());
			image.onload = () => {
				this._ctx.drawImage(image, xOffset, yOffset, width, height);
				resolve();
			};
			image.onerror = (error) => {
				reject(error);
			};
			image.crossOrigin = "anonymous";
			image.src = dataUrl;
			this._isEmpty = false;
			this._dataUrl = dataUrl;
			this._dataUrlOptions = { ...options };
		});
	}
	toDataURL(type = "image/png", encoderOptions) {
		switch (type) {
			case "image/svg+xml":
				if (typeof encoderOptions !== "object") encoderOptions = void 0;
				return `data:image/svg+xml;base64,${btoa(this.toSVG(encoderOptions))}`;
			default:
				if (typeof encoderOptions !== "number") encoderOptions = void 0;
				return this.canvas.toDataURL(type, encoderOptions);
		}
	}
	on() {
		this.canvas.style.touchAction = "none";
		this.canvas.style.msTouchAction = "none";
		this.canvas.style.userSelect = "none";
		this.canvas.style.webkitUserSelect = "none";
		const isIOS = /Macintosh/.test(navigator.userAgent) && "ontouchstart" in document;
		if (window.PointerEvent && !isIOS) this._handlePointerEvents();
		else {
			this._handleMouseEvents();
			if ("ontouchstart" in window) this._handleTouchEvents();
		}
	}
	off() {
		this.canvas.style.touchAction = "auto";
		this.canvas.style.msTouchAction = "auto";
		this.canvas.style.userSelect = "auto";
		this.canvas.style.webkitUserSelect = "auto";
		this.canvas.removeEventListener("pointerdown", this._handlePointerDown);
		this.canvas.removeEventListener("mousedown", this._handleMouseDown);
		this.canvas.removeEventListener("touchstart", this._handleTouchStart);
		this._removeMoveUpEventListeners();
	}
	_getListenerFunctions() {
		const canvasWindow = window.document === this.canvas.ownerDocument ? window : this.canvas.ownerDocument.defaultView ?? this.canvas.ownerDocument;
		return {
			addEventListener: canvasWindow.addEventListener.bind(canvasWindow),
			removeEventListener: canvasWindow.removeEventListener.bind(canvasWindow)
		};
	}
	_removeMoveUpEventListeners() {
		const { removeEventListener } = this._getListenerFunctions();
		removeEventListener("pointermove", this._handlePointerMove);
		removeEventListener("pointerup", this._handlePointerUp);
		removeEventListener("pointercancel", this._handlePointerCancel);
		removeEventListener("mousemove", this._handleMouseMove);
		removeEventListener("mouseup", this._handleMouseUp);
		removeEventListener("touchmove", this._handleTouchMove);
		removeEventListener("touchend", this._handleTouchEnd);
		removeEventListener("touchcancel", this._handleTouchCancel);
	}
	isEmpty() {
		return this._isEmpty;
	}
	fromData(pointGroups, { clear = true } = {}) {
		if (clear) this.clear();
		this._fromData(pointGroups, this._drawCurve.bind(this), this._drawDot.bind(this));
		this._data = this._data.concat(pointGroups);
	}
	toData() {
		return this._data;
	}
	_isLeftButtonPressed(event, only) {
		if (only) return event.buttons === 1;
		return (event.buttons & 1) === 1;
	}
	_pointerEventToSignatureEvent(event) {
		return {
			event,
			type: event.type,
			x: event.clientX,
			y: event.clientY,
			pressure: "pressure" in event ? event.pressure : 0
		};
	}
	_touchEventToSignatureEvent(event) {
		const touch = event.changedTouches[0];
		return {
			event,
			type: event.type,
			x: touch.clientX,
			y: touch.clientY,
			pressure: touch.force
		};
	}
	_handleMouseDown(event) {
		if (!this._isLeftButtonPressed(event, true) || this._drawingStroke) return;
		this._strokeBegin(this._pointerEventToSignatureEvent(event));
	}
	_handleMouseMove(event) {
		if (!this._isLeftButtonPressed(event, true) || !this._drawingStroke) {
			this._strokeEnd(this._pointerEventToSignatureEvent(event), false);
			return;
		}
		this._strokeMoveUpdate(this._pointerEventToSignatureEvent(event));
	}
	_handleMouseUp(event) {
		if (this._isLeftButtonPressed(event)) return;
		this._strokeEnd(this._pointerEventToSignatureEvent(event));
	}
	_handleTouchStart(event) {
		if (event.targetTouches.length !== 1 || this._drawingStroke) return;
		if (event.cancelable) event.preventDefault();
		this._strokeBegin(this._touchEventToSignatureEvent(event));
	}
	_handleTouchMove(event) {
		if (event.targetTouches.length !== 1) return;
		if (event.cancelable) event.preventDefault();
		if (!this._drawingStroke) {
			this._strokeEnd(this._touchEventToSignatureEvent(event), false);
			return;
		}
		this._strokeMoveUpdate(this._touchEventToSignatureEvent(event));
	}
	_handleTouchEnd(event) {
		if (event.targetTouches.length !== 0) return;
		if (event.cancelable) event.preventDefault();
		this._strokeEnd(this._touchEventToSignatureEvent(event));
	}
	_handlePointerCancel(event) {
		if (!this._allowPointerId(event)) return;
		event.preventDefault();
		this._strokeEnd(this._pointerEventToSignatureEvent(event), false);
	}
	_handleTouchCancel(event) {
		if (event.cancelable) event.preventDefault();
		this._strokeEnd(this._touchEventToSignatureEvent(event), false);
	}
	_getPointerId(event) {
		return event.persistentDeviceId || event.pointerId;
	}
	_allowPointerId(event, allowUndefined = false) {
		if (typeof this._strokePointerId === "undefined") return allowUndefined;
		return this._getPointerId(event) === this._strokePointerId;
	}
	_handlePointerDown(event) {
		if (this._drawingStroke || !this._isLeftButtonPressed(event) || !this._allowPointerId(event, true)) return;
		this._strokePointerId = this._getPointerId(event);
		event.preventDefault();
		this._strokeBegin(this._pointerEventToSignatureEvent(event));
	}
	_handlePointerMove(event) {
		if (!this._allowPointerId(event)) return;
		if (!this._isLeftButtonPressed(event, true) || !this._drawingStroke) {
			this._strokeEnd(this._pointerEventToSignatureEvent(event), false);
			return;
		}
		event.preventDefault();
		this._strokeMoveUpdate(this._pointerEventToSignatureEvent(event));
	}
	_handlePointerUp(event) {
		if (this._isLeftButtonPressed(event) || !this._allowPointerId(event)) return;
		event.preventDefault();
		this._strokeEnd(this._pointerEventToSignatureEvent(event));
	}
	_getPointGroupOptions(group) {
		return {
			penColor: group && "penColor" in group ? group.penColor : this.penColor,
			dotSize: group && "dotSize" in group ? group.dotSize : this.dotSize,
			minWidth: group && "minWidth" in group ? group.minWidth : this.minWidth,
			maxWidth: group && "maxWidth" in group ? group.maxWidth : this.maxWidth,
			velocityFilterWeight: group && "velocityFilterWeight" in group ? group.velocityFilterWeight : this.velocityFilterWeight,
			compositeOperation: group && "compositeOperation" in group ? group.compositeOperation : this.compositeOperation
		};
	}
	_strokeBegin(event) {
		if (!this.dispatchEvent(new CustomEvent("beginStroke", {
			detail: event,
			cancelable: true
		}))) return;
		const { addEventListener } = this._getListenerFunctions();
		switch (event.event.type) {
			case "mousedown":
				addEventListener("mousemove", this._handleMouseMove, { passive: false });
				addEventListener("mouseup", this._handleMouseUp, { passive: false });
				break;
			case "touchstart":
				addEventListener("touchmove", this._handleTouchMove, { passive: false });
				addEventListener("touchend", this._handleTouchEnd, { passive: false });
				addEventListener("touchcancel", this._handleTouchCancel, { passive: false });
				break;
			case "pointerdown":
				addEventListener("pointermove", this._handlePointerMove, { passive: false });
				addEventListener("pointerup", this._handlePointerUp, { passive: false });
				addEventListener("pointercancel", this._handlePointerCancel, { passive: false });
				break;
			default:
		}
		this._drawingStroke = true;
		const pointGroupOptions = this._getPointGroupOptions();
		const newPointGroup = {
			...pointGroupOptions,
			points: []
		};
		this._data.push(newPointGroup);
		this._reset(pointGroupOptions);
		this._strokeUpdate(event);
	}
	_strokeUpdate(event) {
		if (!this._drawingStroke) return;
		if (this._data.length === 0) {
			this._strokeBegin(event);
			return;
		}
		this.dispatchEvent(new CustomEvent("beforeUpdateStroke", { detail: event }));
		const point = this._createPoint(event.x, event.y, event.pressure);
		const lastPointGroup = this._data[this._data.length - 1];
		const lastPoints = lastPointGroup.points;
		const lastPoint = lastPoints.length > 0 && lastPoints[lastPoints.length - 1];
		const isLastPointTooClose = lastPoint ? point.distanceTo(lastPoint) <= this.minDistance : false;
		const pointGroupOptions = this._getPointGroupOptions(lastPointGroup);
		if (!lastPoint || !(lastPoint && isLastPointTooClose)) {
			const curve = this._addPoint(point, pointGroupOptions);
			if (!lastPoint) this._drawDot(point, pointGroupOptions);
			else if (curve) this._drawCurve(curve, pointGroupOptions);
			lastPoints.push({
				time: point.time,
				x: point.x,
				y: point.y,
				pressure: point.pressure
			});
		}
		this.dispatchEvent(new CustomEvent("afterUpdateStroke", { detail: event }));
	}
	_strokeEnd(event, shouldUpdate = true) {
		this._removeMoveUpEventListeners();
		if (!this._drawingStroke) return;
		if (shouldUpdate) this._strokeUpdate(event);
		this._drawingStroke = false;
		this._strokePointerId = void 0;
		this.dispatchEvent(new CustomEvent("endStroke", { detail: event }));
	}
	_handlePointerEvents() {
		this._drawingStroke = false;
		this.canvas.addEventListener("pointerdown", this._handlePointerDown, { passive: false });
	}
	_handleMouseEvents() {
		this._drawingStroke = false;
		this.canvas.addEventListener("mousedown", this._handleMouseDown, { passive: false });
	}
	_handleTouchEvents() {
		this.canvas.addEventListener("touchstart", this._handleTouchStart, { passive: false });
	}
	_reset(options) {
		this._lastPoints = [];
		this._lastVelocity = 0;
		this._lastWidth = (options.minWidth + options.maxWidth) / 2;
		this._ctx.fillStyle = options.penColor;
		this._ctx.globalCompositeOperation = options.compositeOperation;
	}
	_createPoint(x, y, pressure) {
		const rect = this.canvas.getBoundingClientRect();
		return new Point(x - rect.left, y - rect.top, pressure, (/* @__PURE__ */ new Date()).getTime());
	}
	_addPoint(point, options) {
		const { _lastPoints } = this;
		_lastPoints.push(point);
		if (_lastPoints.length > 2) {
			if (_lastPoints.length === 3) _lastPoints.unshift(_lastPoints[0]);
			const widths = this._calculateCurveWidths(_lastPoints[1], _lastPoints[2], options);
			const curve = Bezier.fromPoints(_lastPoints, widths);
			_lastPoints.shift();
			return curve;
		}
		return null;
	}
	_calculateCurveWidths(startPoint, endPoint, options) {
		const velocity = options.velocityFilterWeight * endPoint.velocityFrom(startPoint) + (1 - options.velocityFilterWeight) * this._lastVelocity;
		const newWidth = this._strokeWidth(velocity, options);
		const widths = {
			end: newWidth,
			start: this._lastWidth
		};
		this._lastVelocity = velocity;
		this._lastWidth = newWidth;
		return widths;
	}
	_strokeWidth(velocity, options) {
		return Math.max(options.maxWidth / (velocity + 1), options.minWidth);
	}
	_drawCurveSegment(x, y, width) {
		const ctx = this._ctx;
		ctx.moveTo(x, y);
		ctx.arc(x, y, width, 0, 2 * Math.PI, false);
		this._isEmpty = false;
	}
	_drawCurve(curve, options) {
		const ctx = this._ctx;
		const widthDelta = curve.endWidth - curve.startWidth;
		const drawSteps = Math.ceil(curve.length()) * 2;
		ctx.beginPath();
		ctx.fillStyle = options.penColor;
		for (let i = 0; i < drawSteps; i += 1) {
			const t = i / drawSteps;
			const tt = t * t;
			const ttt = tt * t;
			const u = 1 - t;
			const uu = u * u;
			const uuu = uu * u;
			let x = uuu * curve.startPoint.x;
			x += 3 * uu * t * curve.control1.x;
			x += 3 * u * tt * curve.control2.x;
			x += ttt * curve.endPoint.x;
			let y = uuu * curve.startPoint.y;
			y += 3 * uu * t * curve.control1.y;
			y += 3 * u * tt * curve.control2.y;
			y += ttt * curve.endPoint.y;
			const width = Math.min(curve.startWidth + ttt * widthDelta, options.maxWidth);
			this._drawCurveSegment(x, y, width);
		}
		ctx.closePath();
		ctx.fill();
	}
	_drawDot(point, options) {
		const ctx = this._ctx;
		const width = options.dotSize > 0 ? options.dotSize : (options.minWidth + options.maxWidth) / 2;
		ctx.beginPath();
		this._drawCurveSegment(point.x, point.y, width);
		ctx.closePath();
		ctx.fillStyle = options.penColor;
		ctx.fill();
	}
	_fromData(pointGroups, drawCurve, drawDot) {
		for (const group of pointGroups) {
			const { points } = group;
			const pointGroupOptions = this._getPointGroupOptions(group);
			if (points.length > 1) for (let j = 0; j < points.length; j += 1) {
				const basicPoint = points[j];
				const point = new Point(basicPoint.x, basicPoint.y, basicPoint.pressure, basicPoint.time);
				if (j === 0) this._reset(pointGroupOptions);
				const curve = this._addPoint(point, pointGroupOptions);
				if (curve) drawCurve(curve, pointGroupOptions);
			}
			else {
				this._reset(pointGroupOptions);
				drawDot(points[0], pointGroupOptions);
			}
		}
	}
	toSVG({ includeBackgroundColor = false, includeDataUrl = false } = {}) {
		const pointGroups = this._data;
		const ratio = Math.max(window.devicePixelRatio || 1, 1);
		const minX = 0;
		const minY = 0;
		const maxX = this.canvas.width / ratio;
		const maxY = this.canvas.height / ratio;
		const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
		svg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
		svg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
		svg.setAttribute("viewBox", `${minX} ${minY} ${maxX} ${maxY}`);
		svg.setAttribute("width", maxX.toString());
		svg.setAttribute("height", maxY.toString());
		if (includeBackgroundColor && this.backgroundColor) {
			const rect = document.createElement("rect");
			rect.setAttribute("width", "100%");
			rect.setAttribute("height", "100%");
			rect.setAttribute("fill", this.backgroundColor);
			svg.appendChild(rect);
		}
		if (includeDataUrl && this._dataUrl) {
			const ratio2 = this._dataUrlOptions?.ratio || window.devicePixelRatio || 1;
			const width = this._dataUrlOptions?.width || this.canvas.width / ratio2;
			const height = this._dataUrlOptions?.height || this.canvas.height / ratio2;
			const xOffset = this._dataUrlOptions?.xOffset || 0;
			const yOffset = this._dataUrlOptions?.yOffset || 0;
			const image = document.createElement("image");
			image.setAttribute("x", xOffset.toString());
			image.setAttribute("y", yOffset.toString());
			image.setAttribute("width", width.toString());
			image.setAttribute("height", height.toString());
			image.setAttribute("preserveAspectRatio", "none");
			image.setAttribute("href", this._dataUrl);
			svg.appendChild(image);
		}
		this._fromData(pointGroups, (curve, { penColor }) => {
			const path = document.createElement("path");
			if (!isNaN(curve.control1.x) && !isNaN(curve.control1.y) && !isNaN(curve.control2.x) && !isNaN(curve.control2.y)) {
				const attr = `M ${curve.startPoint.x.toFixed(3)},${curve.startPoint.y.toFixed(3)} C ${curve.control1.x.toFixed(3)},${curve.control1.y.toFixed(3)} ${curve.control2.x.toFixed(3)},${curve.control2.y.toFixed(3)} ${curve.endPoint.x.toFixed(3)},${curve.endPoint.y.toFixed(3)}`;
				path.setAttribute("d", attr);
				path.setAttribute("stroke-width", (curve.endWidth * 2.25).toFixed(3));
				path.setAttribute("stroke", penColor);
				path.setAttribute("fill", "none");
				path.setAttribute("stroke-linecap", "round");
				svg.appendChild(path);
			}
		}, (point, { penColor, dotSize, minWidth, maxWidth }) => {
			const circle = document.createElement("circle");
			const size = dotSize > 0 ? dotSize : (minWidth + maxWidth) / 2;
			circle.setAttribute("r", size.toString());
			circle.setAttribute("cx", point.x.toString());
			circle.setAttribute("cy", point.y.toString());
			circle.setAttribute("fill", penColor);
			svg.appendChild(circle);
		});
		return svg.outerHTML;
	}
};
//#endregion
//#region src/css/theme/fields/_signature.css?inline
var _signature_default = "@layer formie-theme{[data-formie-field-type=signature] .formie-field-control{transition:border-color .15s,box-shadow .15s,background-color .15s;position:relative}[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error[data-formie-field-type=signature] .formie-signature-canvas{border-color:var(--formie-color-danger)}.formie-field-has-error[data-formie-field-type=signature] .formie-field-control:focus-within .formie-signature-canvas{box-shadow:var(--formie-shadow-danger-focus)}[data-formie-field-type=signature] .formie-signature-canvas{width:var(--formie-signature-width);height:var(--formie-signature-height);border:var(--formie-signature-border);background:var(--formie-signature-background);border-radius:var(--formie-signature-border-radius);touch-action:none;transition:border-color .15s,box-shadow .15s,background-color .15s;display:block}[data-formie-field-type=signature] .formie-signature-remove-button{top:var(--formie-signature-remove-button-top);right:var(--formie-signature-remove-button-right);transform:var(--formie-signature-remove-button-transform);font-size:0;line-height:0;position:absolute}}";
//#endregion
//#region src/js/modules/fields/signature.ts
var INPUT_SELECTOR = "input[data-formie-signature-input]";
var CANVAS_SELECTOR = "canvas[data-formie-signature-canvas]";
var CLEAR_SELECTOR = "[data-formie-signature-clear]";
var MODULE_ID = "signature";
ensureModuleStyles(MODULE_ID, [_signature_default]);
function getCanvasSize(canvas) {
	const rect = canvas.getBoundingClientRect();
	return {
		width: Math.round(rect.width),
		height: Math.round(rect.height)
	};
}
function drawValueOnCanvas(canvas, value) {
	if (!value) return;
	const image = new Image();
	image.src = value;
	image.onload = () => {
		const ratio = Math.max(window.devicePixelRatio || 1, 1);
		const context = canvas.getContext("2d");
		if (!context) return;
		context.drawImage(image, 0, 0, canvas.width / ratio, canvas.height / ratio);
	};
}
function initSignatureField(root, field, input, canvas, clearButton, options) {
	const penWeight = parseFloat(options.penWeight || "2") || 2;
	const resizeTarget = canvas.parentElement instanceof HTMLElement ? canvas.parentElement : field;
	const signaturePad = new SignaturePad(canvas, {
		backgroundColor: options.backgroundColor || "rgba(255, 255, 255, 0)",
		penColor: options.penColor || "#000000",
		dotSize: penWeight,
		minWidth: penWeight,
		maxWidth: penWeight
	});
	const resizeCanvas = () => {
		const { width, height } = getCanvasSize(canvas);
		if (!(width > 0) || !(height > 0)) return;
		const ratio = Math.max(window.devicePixelRatio || 1, 1);
		const context = canvas.getContext("2d");
		if (!context) return;
		const existingValue = input.value || (signaturePad.isEmpty() ? "" : signaturePad.toDataURL());
		canvas.width = width * ratio;
		canvas.height = height * ratio;
		context.setTransform(1, 0, 0, 1, 0, 0);
		context.scale(ratio, ratio);
		signaturePad.clear();
		drawValueOnCanvas(canvas, existingValue);
	};
	const scheduleResize = (delay = 0) => {
		window.setTimeout(() => {
			window.requestAnimationFrame(() => {
				resizeCanvas();
			});
		}, delay);
	};
	const resizeHandler = () => {
		scheduleResize();
	};
	const pageNavigateHandler = () => {
		scheduleResize(100);
	};
	const resizeObserver = typeof ResizeObserver === "undefined" ? null : new ResizeObserver(() => {
		scheduleResize();
	});
	const syncInputValue = (nextValue) => {
		const valueChanged = input.value !== nextValue;
		input.value = nextValue;
		if (!valueChanged) return;
		input.dispatchEvent(new Event("input", { bubbles: true }));
		input.dispatchEvent(new Event("change", { bubbles: true }));
	};
	const syncValue = () => {
		syncInputValue(signaturePad.isEmpty() ? "" : signaturePad.toDataURL());
	};
	const clearSignature = () => {
		signaturePad.clear();
		syncInputValue("");
	};
	signaturePad.addEventListener("endStroke", syncValue);
	window.addEventListener("resize", resizeHandler);
	root.addEventListener("formie:page:navigate:after", pageNavigateHandler);
	resizeObserver?.observe(resizeTarget);
	scheduleResize();
	if (clearButton) clearButton.addEventListener("click", clearSignature);
	dispatchFieldEvent(field, MODULE_ID, "init", { signature: signaturePad });
	return () => {
		signaturePad.removeEventListener("endStroke", syncValue);
		window.removeEventListener("resize", resizeHandler);
		root.removeEventListener("formie:page:navigate:after", pageNavigateHandler);
		resizeObserver?.disconnect();
		if (clearButton) clearButton.removeEventListener("click", clearSignature);
		signaturePad.clear();
	};
}
var signatureModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(CANVAS_SELECTOR);
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		const root = ctx.root instanceof HTMLElement ? ctx.root : ctx.target instanceof HTMLElement ? ctx.target : null;
		if (!root) return;
		const cleanups = getModuleFieldContainers(ctx).map((field) => {
			const input = field.querySelector(INPUT_SELECTOR);
			const canvas = field.querySelector(CANVAS_SELECTOR);
			const clearButton = field.querySelector(CLEAR_SELECTOR);
			if (!(input instanceof HTMLInputElement) || !(canvas instanceof HTMLCanvasElement)) return () => {};
			return initSignatureField(root, field, input, canvas, clearButton instanceof HTMLElement ? clearButton : null, options);
		});
		await ctx.emit("formie:module:signature:init", { count: cleanups.length });
		return { destroy: () => {
			cleanups.forEach((cleanup) => {
				cleanup();
			});
			ctx.emit("formie:module:signature:destroy", {});
		} };
	}
};
//#endregion
export { signatureModule };
