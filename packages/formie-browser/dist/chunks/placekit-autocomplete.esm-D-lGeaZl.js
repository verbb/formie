//#region ../../node_modules/@placekit/autocomplete-js/dist/placekit-autocomplete.esm.mjs
/*! @placekit/autocomplete-js v2.2.1 | © placekit.io | MIT license | https://github.com/placekit/autocomplete-js#readme */
var extensions = /* @__PURE__ */ new Map();
function getUserAgent() {
	const chunks = [];
	if (typeof window !== "undefined" && navigator.userAgent) chunks.push(navigator.userAgent);
	chunks.push(`PlaceKit/2.3.0 (Client=JavaScript)`);
	if (typeof process !== "undefined" && process.version) chunks.push(`NodeJS/${process.version}`);
	return chunks.join(" ");
}
function placekit(apiKey, options = {}) {
	if (!["string", "undefined"].includes(typeof apiKey)) throw Error("PlaceKit: `apiKey` argument is invalid, expected a string.");
	else if (!apiKey) console.warn("PlaceKit: missing or empty `apiKey` argument.");
	let currentHost = 0;
	const hosts = [`https://api.placekit.co`];
	let hasGeolocation = false;
	const globalParams = { maxResults: 5 };
	if (typeof window !== "undefined" && navigator.language) globalParams.language = navigator.language.slice(0, 2);
	const userAgent = getUserAgent();
	function request(method = "POST", resource = "", opts = {}) {
		const { timeout, forwardIP, ...params } = opts;
		const controller = new AbortController();
		const id = typeof timeout !== "undefined" ? setTimeout(() => controller.abort(), timeout) : void 0;
		const url = new URL(resource.trim().replace(/^\/+/, ""), hosts[currentHost]);
		if (["GET", "HEAD"].includes(method) && typeof params !== "undefined") Object.keys(params).forEach((k) => url.searchParams.append(k, params[k]));
		const headers = {
			"Content-Type": "application/json; charset=UTF-8",
			"User-Agent": userAgent,
			"x-placekit-api-key": apiKey
		};
		if (forwardIP) headers["x-forwarded-for"] = forwardIP;
		return fetch(url, {
			method,
			headers,
			signal: controller.signal,
			body: !["GET", "HEAD"].includes(method) ? JSON.stringify(params) : void 0
		}).then(async (res) => {
			clearTimeout(id);
			const body = await res.json();
			if (!res.ok) throw {
				status: res.status,
				statusText: res.statusText,
				...body
			};
			return body;
		}).catch((err) => {
			if (err.name === "AbortError" || err.status && err.status >= 500) {
				currentHost++;
				if (currentHost < hosts.length - 1) return request(method, resource, opts);
			}
			throw err;
		});
	}
	const client = {
		get options() {
			return globalParams;
		},
		get hasGeolocation() {
			return hasGeolocation;
		},
		configure(opts = {}) {
			if (!["object", "undefined"].includes(typeof opts) || Array.isArray(opts) || opts === null) throw Error("PlaceKit.configure: `opts` argument is invalid, expected an object.");
			Object.assign(globalParams, opts);
		},
		requestGeolocation(opts = {}) {
			if (!["object", "undefined"].includes(typeof opts) || Array.isArray(opts) || opts === null) throw Error("PlaceKit.requestGeolocation: `opts` argument is invalid, expected an object.");
			return new Promise((resolve, reject) => {
				if (typeof window === "undefined" || !navigator.geolocation) reject(Error("PlaceKit.requestGeolocation: geolocation is only available in the browser."));
				else navigator.geolocation.getCurrentPosition((pos) => {
					hasGeolocation = true;
					globalParams.coordinates = `${pos.coords.latitude},${pos.coords.longitude}`;
					resolve(pos);
				}, (err) => {
					hasGeolocation = false;
					delete globalParams.coordinates;
					reject(Error(`PlaceKit.requestGeolocation: (${err.code}) ${err.message}`));
				}, opts);
			});
		},
		clearGeolocation() {
			hasGeolocation = false;
			delete globalParams.coordinates;
		}
	};
	for (const [resource, init] of extensions.entries()) {
		if (resource in client) throw Error(`PlaceKit extend: \`client.${resource}\` already exists.`);
		client[resource] = init(request, client);
	}
	client.configure(options);
	return client;
}
placekit.extend = function(resource, init) {
	if (!init?.call) throw Error("PlaceKit extend: `init` argument is invalid, expected a function.");
	extensions.set(resource, init);
};
placekit.extend("search", (request, client) => (query, opts = {}) => {
	if (!["string", "undefined"].includes(typeof query)) throw Error("PlaceKit `client.search`: `query` argument is invalid, expected a string.");
	if (!["object", "undefined"].includes(typeof opts) || Array.isArray(opts) || opts === null) throw Error("PlaceKit.search: `opts` argument is invalid, expected an object.");
	return request("POST", "search", {
		...client.options,
		...opts,
		query
	});
});
placekit.extend("reverse", (request, client) => (opts = {}) => {
	if (!["object", "undefined"].includes(typeof opts) || Array.isArray(opts) || opts === null) throw Error("PlaceKit.reverse: `opts` argument is invalid, expected an object.");
	return request("POST", "reverse", {
		...client.options,
		...opts
	});
});
var top = "top";
var bottom = "bottom";
var right = "right";
var left = "left";
var auto = "auto";
var basePlacements = [
	top,
	bottom,
	right,
	left
];
var start = "start";
var end = "end";
var clippingParents = "clippingParents";
var viewport = "viewport";
var popper = "popper";
var reference = "reference";
var variationPlacements = basePlacements.reduce(function(acc, placement) {
	return acc.concat([placement + "-" + start, placement + "-" + end]);
}, []);
var placements = [].concat(basePlacements, [auto]).reduce(function(acc, placement) {
	return acc.concat([
		placement,
		placement + "-" + start,
		placement + "-" + end
	]);
}, []);
var modifierPhases = [
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
function getNodeName(element) {
	return element ? (element.nodeName || "").toLowerCase() : null;
}
function getWindow(node) {
	if (node == null) return window;
	if (node.toString() !== "[object Window]") {
		var ownerDocument = node.ownerDocument;
		return ownerDocument ? ownerDocument.defaultView || window : window;
	}
	return node;
}
function isElement(node) {
	return node instanceof getWindow(node).Element || node instanceof Element;
}
function isHTMLElement(node) {
	return node instanceof getWindow(node).HTMLElement || node instanceof HTMLElement;
}
function isShadowRoot(node) {
	if (typeof ShadowRoot === "undefined") return false;
	return node instanceof getWindow(node).ShadowRoot || node instanceof ShadowRoot;
}
function applyStyles(_ref) {
	var state = _ref.state;
	Object.keys(state.elements).forEach(function(name) {
		var style = state.styles[name] || {};
		var attributes = state.attributes[name] || {};
		var element = state.elements[name];
		if (!isHTMLElement(element) || !getNodeName(element)) return;
		Object.assign(element.style, style);
		Object.keys(attributes).forEach(function(name) {
			var value = attributes[name];
			if (value === false) element.removeAttribute(name);
			else element.setAttribute(name, value === true ? "" : value);
		});
	});
}
function effect$2(_ref2) {
	var state = _ref2.state;
	var initialStyles = {
		popper: {
			position: state.options.strategy,
			left: "0",
			top: "0",
			margin: "0"
		},
		arrow: { position: "absolute" },
		reference: {}
	};
	Object.assign(state.elements.popper.style, initialStyles.popper);
	state.styles = initialStyles;
	if (state.elements.arrow) Object.assign(state.elements.arrow.style, initialStyles.arrow);
	return function() {
		Object.keys(state.elements).forEach(function(name) {
			var element = state.elements[name];
			var attributes = state.attributes[name] || {};
			var style = Object.keys(state.styles.hasOwnProperty(name) ? state.styles[name] : initialStyles[name]).reduce(function(style, property) {
				style[property] = "";
				return style;
			}, {});
			if (!isHTMLElement(element) || !getNodeName(element)) return;
			Object.assign(element.style, style);
			Object.keys(attributes).forEach(function(attribute) {
				element.removeAttribute(attribute);
			});
		});
	};
}
var applyStyles$1 = {
	name: "applyStyles",
	enabled: true,
	phase: "write",
	fn: applyStyles,
	effect: effect$2,
	requires: ["computeStyles"]
};
function getBasePlacement(placement) {
	return placement.split("-")[0];
}
var max = Math.max;
var min = Math.min;
var round = Math.round;
function getUAString() {
	var uaData = navigator.userAgentData;
	if (uaData != null && uaData.brands && Array.isArray(uaData.brands)) return uaData.brands.map(function(item) {
		return item.brand + "/" + item.version;
	}).join(" ");
	return navigator.userAgent;
}
function isLayoutViewport() {
	return !/^((?!chrome|android).)*safari/i.test(getUAString());
}
function getBoundingClientRect(element, includeScale, isFixedStrategy) {
	if (includeScale === void 0) includeScale = false;
	if (isFixedStrategy === void 0) isFixedStrategy = false;
	var clientRect = element.getBoundingClientRect();
	var scaleX = 1;
	var scaleY = 1;
	if (includeScale && isHTMLElement(element)) {
		scaleX = element.offsetWidth > 0 ? round(clientRect.width) / element.offsetWidth || 1 : 1;
		scaleY = element.offsetHeight > 0 ? round(clientRect.height) / element.offsetHeight || 1 : 1;
	}
	var visualViewport = (isElement(element) ? getWindow(element) : window).visualViewport;
	var addVisualOffsets = !isLayoutViewport() && isFixedStrategy;
	var x = (clientRect.left + (addVisualOffsets && visualViewport ? visualViewport.offsetLeft : 0)) / scaleX;
	var y = (clientRect.top + (addVisualOffsets && visualViewport ? visualViewport.offsetTop : 0)) / scaleY;
	var width = clientRect.width / scaleX;
	var height = clientRect.height / scaleY;
	return {
		width,
		height,
		top: y,
		right: x + width,
		bottom: y + height,
		left: x,
		x,
		y
	};
}
function getLayoutRect(element) {
	var clientRect = getBoundingClientRect(element);
	var width = element.offsetWidth;
	var height = element.offsetHeight;
	if (Math.abs(clientRect.width - width) <= 1) width = clientRect.width;
	if (Math.abs(clientRect.height - height) <= 1) height = clientRect.height;
	return {
		x: element.offsetLeft,
		y: element.offsetTop,
		width,
		height
	};
}
function contains(parent, child) {
	var rootNode = child.getRootNode && child.getRootNode();
	if (parent.contains(child)) return true;
	else if (rootNode && isShadowRoot(rootNode)) {
		var next = child;
		do {
			if (next && parent.isSameNode(next)) return true;
			next = next.parentNode || next.host;
		} while (next);
	}
	return false;
}
function getComputedStyle(element) {
	return getWindow(element).getComputedStyle(element);
}
function isTableElement(element) {
	return [
		"table",
		"td",
		"th"
	].indexOf(getNodeName(element)) >= 0;
}
function getDocumentElement(element) {
	return ((isElement(element) ? element.ownerDocument : element.document) || window.document).documentElement;
}
function getParentNode(element) {
	if (getNodeName(element) === "html") return element;
	return element.assignedSlot || element.parentNode || (isShadowRoot(element) ? element.host : null) || getDocumentElement(element);
}
function getTrueOffsetParent(element) {
	if (!isHTMLElement(element) || getComputedStyle(element).position === "fixed") return null;
	return element.offsetParent;
}
function getContainingBlock(element) {
	var isFirefox = /firefox/i.test(getUAString());
	if (/Trident/i.test(getUAString()) && isHTMLElement(element)) {
		if (getComputedStyle(element).position === "fixed") return null;
	}
	var currentNode = getParentNode(element);
	if (isShadowRoot(currentNode)) currentNode = currentNode.host;
	while (isHTMLElement(currentNode) && ["html", "body"].indexOf(getNodeName(currentNode)) < 0) {
		var css = getComputedStyle(currentNode);
		if (css.transform !== "none" || css.perspective !== "none" || css.contain === "paint" || ["transform", "perspective"].indexOf(css.willChange) !== -1 || isFirefox && css.willChange === "filter" || isFirefox && css.filter && css.filter !== "none") return currentNode;
		else currentNode = currentNode.parentNode;
	}
	return null;
}
function getOffsetParent(element) {
	var window = getWindow(element);
	var offsetParent = getTrueOffsetParent(element);
	while (offsetParent && isTableElement(offsetParent) && getComputedStyle(offsetParent).position === "static") offsetParent = getTrueOffsetParent(offsetParent);
	if (offsetParent && (getNodeName(offsetParent) === "html" || getNodeName(offsetParent) === "body" && getComputedStyle(offsetParent).position === "static")) return window;
	return offsetParent || getContainingBlock(element) || window;
}
function getMainAxisFromPlacement(placement) {
	return ["top", "bottom"].indexOf(placement) >= 0 ? "x" : "y";
}
function within(min$1, value, max$1) {
	return max(min$1, min(value, max$1));
}
function withinMaxClamp(min, value, max) {
	var v = within(min, value, max);
	return v > max ? max : v;
}
function getFreshSideObject() {
	return {
		top: 0,
		right: 0,
		bottom: 0,
		left: 0
	};
}
function mergePaddingObject(paddingObject) {
	return Object.assign({}, getFreshSideObject(), paddingObject);
}
function expandToHashMap(value, keys) {
	return keys.reduce(function(hashMap, key) {
		hashMap[key] = value;
		return hashMap;
	}, {});
}
var toPaddingObject = function toPaddingObject(padding, state) {
	padding = typeof padding === "function" ? padding(Object.assign({}, state.rects, { placement: state.placement })) : padding;
	return mergePaddingObject(typeof padding !== "number" ? padding : expandToHashMap(padding, basePlacements));
};
function arrow(_ref) {
	var _state$modifiersData$;
	var state = _ref.state, name = _ref.name, options = _ref.options;
	var arrowElement = state.elements.arrow;
	var popperOffsets = state.modifiersData.popperOffsets;
	var basePlacement = getBasePlacement(state.placement);
	var axis = getMainAxisFromPlacement(basePlacement);
	var len = [left, right].indexOf(basePlacement) >= 0 ? "height" : "width";
	if (!arrowElement || !popperOffsets) return;
	var paddingObject = toPaddingObject(options.padding, state);
	var arrowRect = getLayoutRect(arrowElement);
	var minProp = axis === "y" ? top : left;
	var maxProp = axis === "y" ? bottom : right;
	var endDiff = state.rects.reference[len] + state.rects.reference[axis] - popperOffsets[axis] - state.rects.popper[len];
	var startDiff = popperOffsets[axis] - state.rects.reference[axis];
	var arrowOffsetParent = getOffsetParent(arrowElement);
	var clientSize = arrowOffsetParent ? axis === "y" ? arrowOffsetParent.clientHeight || 0 : arrowOffsetParent.clientWidth || 0 : 0;
	var centerToReference = endDiff / 2 - startDiff / 2;
	var min = paddingObject[minProp];
	var max = clientSize - arrowRect[len] - paddingObject[maxProp];
	var center = clientSize / 2 - arrowRect[len] / 2 + centerToReference;
	var offset = within(min, center, max);
	var axisProp = axis;
	state.modifiersData[name] = (_state$modifiersData$ = {}, _state$modifiersData$[axisProp] = offset, _state$modifiersData$.centerOffset = offset - center, _state$modifiersData$);
}
function effect$1(_ref2) {
	var state = _ref2.state;
	var _options$element = _ref2.options.element, arrowElement = _options$element === void 0 ? "[data-popper-arrow]" : _options$element;
	if (arrowElement == null) return;
	if (typeof arrowElement === "string") {
		arrowElement = state.elements.popper.querySelector(arrowElement);
		if (!arrowElement) return;
	}
	if (!contains(state.elements.popper, arrowElement)) return;
	state.elements.arrow = arrowElement;
}
var arrow$1 = {
	name: "arrow",
	enabled: true,
	phase: "main",
	fn: arrow,
	effect: effect$1,
	requires: ["popperOffsets"],
	requiresIfExists: ["preventOverflow"]
};
function getVariation(placement) {
	return placement.split("-")[1];
}
var unsetSides = {
	top: "auto",
	right: "auto",
	bottom: "auto",
	left: "auto"
};
function roundOffsetsByDPR(_ref, win) {
	var x = _ref.x, y = _ref.y;
	var dpr = win.devicePixelRatio || 1;
	return {
		x: round(x * dpr) / dpr || 0,
		y: round(y * dpr) / dpr || 0
	};
}
function mapToStyles(_ref2) {
	var _Object$assign2;
	var popper = _ref2.popper, popperRect = _ref2.popperRect, placement = _ref2.placement, variation = _ref2.variation, offsets = _ref2.offsets, position = _ref2.position, gpuAcceleration = _ref2.gpuAcceleration, adaptive = _ref2.adaptive, roundOffsets = _ref2.roundOffsets, isFixed = _ref2.isFixed;
	var _offsets$x = offsets.x, x = _offsets$x === void 0 ? 0 : _offsets$x, _offsets$y = offsets.y, y = _offsets$y === void 0 ? 0 : _offsets$y;
	var _ref3 = typeof roundOffsets === "function" ? roundOffsets({
		x,
		y
	}) : {
		x,
		y
	};
	x = _ref3.x;
	y = _ref3.y;
	var hasX = offsets.hasOwnProperty("x");
	var hasY = offsets.hasOwnProperty("y");
	var sideX = left;
	var sideY = top;
	var win = window;
	if (adaptive) {
		var offsetParent = getOffsetParent(popper);
		var heightProp = "clientHeight";
		var widthProp = "clientWidth";
		if (offsetParent === getWindow(popper)) {
			offsetParent = getDocumentElement(popper);
			if (getComputedStyle(offsetParent).position !== "static" && position === "absolute") {
				heightProp = "scrollHeight";
				widthProp = "scrollWidth";
			}
		}
		offsetParent = offsetParent;
		if (placement === top || (placement === left || placement === right) && variation === end) {
			sideY = bottom;
			var offsetY = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.height : offsetParent[heightProp];
			y -= offsetY - popperRect.height;
			y *= gpuAcceleration ? 1 : -1;
		}
		if (placement === left || (placement === top || placement === bottom) && variation === end) {
			sideX = right;
			var offsetX = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.width : offsetParent[widthProp];
			x -= offsetX - popperRect.width;
			x *= gpuAcceleration ? 1 : -1;
		}
	}
	var commonStyles = Object.assign({ position }, adaptive && unsetSides);
	var _ref4 = roundOffsets === true ? roundOffsetsByDPR({
		x,
		y
	}, getWindow(popper)) : {
		x,
		y
	};
	x = _ref4.x;
	y = _ref4.y;
	if (gpuAcceleration) {
		var _Object$assign;
		return Object.assign({}, commonStyles, (_Object$assign = {}, _Object$assign[sideY] = hasY ? "0" : "", _Object$assign[sideX] = hasX ? "0" : "", _Object$assign.transform = (win.devicePixelRatio || 1) <= 1 ? "translate(" + x + "px, " + y + "px)" : "translate3d(" + x + "px, " + y + "px, 0)", _Object$assign));
	}
	return Object.assign({}, commonStyles, (_Object$assign2 = {}, _Object$assign2[sideY] = hasY ? y + "px" : "", _Object$assign2[sideX] = hasX ? x + "px" : "", _Object$assign2.transform = "", _Object$assign2));
}
function computeStyles(_ref5) {
	var state = _ref5.state, options = _ref5.options;
	var _options$gpuAccelerat = options.gpuAcceleration, gpuAcceleration = _options$gpuAccelerat === void 0 ? true : _options$gpuAccelerat, _options$adaptive = options.adaptive, adaptive = _options$adaptive === void 0 ? true : _options$adaptive, _options$roundOffsets = options.roundOffsets, roundOffsets = _options$roundOffsets === void 0 ? true : _options$roundOffsets;
	var commonStyles = {
		placement: getBasePlacement(state.placement),
		variation: getVariation(state.placement),
		popper: state.elements.popper,
		popperRect: state.rects.popper,
		gpuAcceleration,
		isFixed: state.options.strategy === "fixed"
	};
	if (state.modifiersData.popperOffsets != null) state.styles.popper = Object.assign({}, state.styles.popper, mapToStyles(Object.assign({}, commonStyles, {
		offsets: state.modifiersData.popperOffsets,
		position: state.options.strategy,
		adaptive,
		roundOffsets
	})));
	if (state.modifiersData.arrow != null) state.styles.arrow = Object.assign({}, state.styles.arrow, mapToStyles(Object.assign({}, commonStyles, {
		offsets: state.modifiersData.arrow,
		position: "absolute",
		adaptive: false,
		roundOffsets
	})));
	state.attributes.popper = Object.assign({}, state.attributes.popper, { "data-popper-placement": state.placement });
}
var computeStyles$1 = {
	name: "computeStyles",
	enabled: true,
	phase: "beforeWrite",
	fn: computeStyles,
	data: {}
};
var passive = { passive: true };
function effect(_ref) {
	var state = _ref.state, instance = _ref.instance, options = _ref.options;
	var _options$scroll = options.scroll, scroll = _options$scroll === void 0 ? true : _options$scroll, _options$resize = options.resize, resize = _options$resize === void 0 ? true : _options$resize;
	var window = getWindow(state.elements.popper);
	var scrollParents = [].concat(state.scrollParents.reference, state.scrollParents.popper);
	if (scroll) scrollParents.forEach(function(scrollParent) {
		scrollParent.addEventListener("scroll", instance.update, passive);
	});
	if (resize) window.addEventListener("resize", instance.update, passive);
	return function() {
		if (scroll) scrollParents.forEach(function(scrollParent) {
			scrollParent.removeEventListener("scroll", instance.update, passive);
		});
		if (resize) window.removeEventListener("resize", instance.update, passive);
	};
}
var eventListeners = {
	name: "eventListeners",
	enabled: true,
	phase: "write",
	fn: function fn() {},
	effect,
	data: {}
};
var hash$1 = {
	left: "right",
	right: "left",
	bottom: "top",
	top: "bottom"
};
function getOppositePlacement(placement) {
	return placement.replace(/left|right|bottom|top/g, function(matched) {
		return hash$1[matched];
	});
}
var hash = {
	start: "end",
	end: "start"
};
function getOppositeVariationPlacement(placement) {
	return placement.replace(/start|end/g, function(matched) {
		return hash[matched];
	});
}
function getWindowScroll(node) {
	var win = getWindow(node);
	return {
		scrollLeft: win.pageXOffset,
		scrollTop: win.pageYOffset
	};
}
function getWindowScrollBarX(element) {
	return getBoundingClientRect(getDocumentElement(element)).left + getWindowScroll(element).scrollLeft;
}
function getViewportRect(element, strategy) {
	var win = getWindow(element);
	var html = getDocumentElement(element);
	var visualViewport = win.visualViewport;
	var width = html.clientWidth;
	var height = html.clientHeight;
	var x = 0;
	var y = 0;
	if (visualViewport) {
		width = visualViewport.width;
		height = visualViewport.height;
		var layoutViewport = isLayoutViewport();
		if (layoutViewport || !layoutViewport && strategy === "fixed") {
			x = visualViewport.offsetLeft;
			y = visualViewport.offsetTop;
		}
	}
	return {
		width,
		height,
		x: x + getWindowScrollBarX(element),
		y
	};
}
function getDocumentRect(element) {
	var _element$ownerDocumen;
	var html = getDocumentElement(element);
	var winScroll = getWindowScroll(element);
	var body = (_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body;
	var width = max(html.scrollWidth, html.clientWidth, body ? body.scrollWidth : 0, body ? body.clientWidth : 0);
	var height = max(html.scrollHeight, html.clientHeight, body ? body.scrollHeight : 0, body ? body.clientHeight : 0);
	var x = -winScroll.scrollLeft + getWindowScrollBarX(element);
	var y = -winScroll.scrollTop;
	if (getComputedStyle(body || html).direction === "rtl") x += max(html.clientWidth, body ? body.clientWidth : 0) - width;
	return {
		width,
		height,
		x,
		y
	};
}
function isScrollParent(element) {
	var _getComputedStyle = getComputedStyle(element), overflow = _getComputedStyle.overflow, overflowX = _getComputedStyle.overflowX, overflowY = _getComputedStyle.overflowY;
	return /auto|scroll|overlay|hidden/.test(overflow + overflowY + overflowX);
}
function getScrollParent(node) {
	if ([
		"html",
		"body",
		"#document"
	].indexOf(getNodeName(node)) >= 0) return node.ownerDocument.body;
	if (isHTMLElement(node) && isScrollParent(node)) return node;
	return getScrollParent(getParentNode(node));
}
function listScrollParents(element, list) {
	var _element$ownerDocumen;
	if (list === void 0) list = [];
	var scrollParent = getScrollParent(element);
	var isBody = scrollParent === ((_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body);
	var win = getWindow(scrollParent);
	var target = isBody ? [win].concat(win.visualViewport || [], isScrollParent(scrollParent) ? scrollParent : []) : scrollParent;
	var updatedList = list.concat(target);
	return isBody ? updatedList : updatedList.concat(listScrollParents(getParentNode(target)));
}
function rectToClientRect(rect) {
	return Object.assign({}, rect, {
		left: rect.x,
		top: rect.y,
		right: rect.x + rect.width,
		bottom: rect.y + rect.height
	});
}
function getInnerBoundingClientRect(element, strategy) {
	var rect = getBoundingClientRect(element, false, strategy === "fixed");
	rect.top = rect.top + element.clientTop;
	rect.left = rect.left + element.clientLeft;
	rect.bottom = rect.top + element.clientHeight;
	rect.right = rect.left + element.clientWidth;
	rect.width = element.clientWidth;
	rect.height = element.clientHeight;
	rect.x = rect.left;
	rect.y = rect.top;
	return rect;
}
function getClientRectFromMixedType(element, clippingParent, strategy) {
	return clippingParent === viewport ? rectToClientRect(getViewportRect(element, strategy)) : isElement(clippingParent) ? getInnerBoundingClientRect(clippingParent, strategy) : rectToClientRect(getDocumentRect(getDocumentElement(element)));
}
function getClippingParents(element) {
	var clippingParents = listScrollParents(getParentNode(element));
	var clipperElement = ["absolute", "fixed"].indexOf(getComputedStyle(element).position) >= 0 && isHTMLElement(element) ? getOffsetParent(element) : element;
	if (!isElement(clipperElement)) return [];
	return clippingParents.filter(function(clippingParent) {
		return isElement(clippingParent) && contains(clippingParent, clipperElement) && getNodeName(clippingParent) !== "body";
	});
}
function getClippingRect(element, boundary, rootBoundary, strategy) {
	var mainClippingParents = boundary === "clippingParents" ? getClippingParents(element) : [].concat(boundary);
	var clippingParents = [].concat(mainClippingParents, [rootBoundary]);
	var firstClippingParent = clippingParents[0];
	var clippingRect = clippingParents.reduce(function(accRect, clippingParent) {
		var rect = getClientRectFromMixedType(element, clippingParent, strategy);
		accRect.top = max(rect.top, accRect.top);
		accRect.right = min(rect.right, accRect.right);
		accRect.bottom = min(rect.bottom, accRect.bottom);
		accRect.left = max(rect.left, accRect.left);
		return accRect;
	}, getClientRectFromMixedType(element, firstClippingParent, strategy));
	clippingRect.width = clippingRect.right - clippingRect.left;
	clippingRect.height = clippingRect.bottom - clippingRect.top;
	clippingRect.x = clippingRect.left;
	clippingRect.y = clippingRect.top;
	return clippingRect;
}
function computeOffsets(_ref) {
	var reference = _ref.reference, element = _ref.element, placement = _ref.placement;
	var basePlacement = placement ? getBasePlacement(placement) : null;
	var variation = placement ? getVariation(placement) : null;
	var commonX = reference.x + reference.width / 2 - element.width / 2;
	var commonY = reference.y + reference.height / 2 - element.height / 2;
	var offsets;
	switch (basePlacement) {
		case top:
			offsets = {
				x: commonX,
				y: reference.y - element.height
			};
			break;
		case bottom:
			offsets = {
				x: commonX,
				y: reference.y + reference.height
			};
			break;
		case right:
			offsets = {
				x: reference.x + reference.width,
				y: commonY
			};
			break;
		case left:
			offsets = {
				x: reference.x - element.width,
				y: commonY
			};
			break;
		default: offsets = {
			x: reference.x,
			y: reference.y
		};
	}
	var mainAxis = basePlacement ? getMainAxisFromPlacement(basePlacement) : null;
	if (mainAxis != null) {
		var len = mainAxis === "y" ? "height" : "width";
		switch (variation) {
			case start:
				offsets[mainAxis] = offsets[mainAxis] - (reference[len] / 2 - element[len] / 2);
				break;
			case end:
				offsets[mainAxis] = offsets[mainAxis] + (reference[len] / 2 - element[len] / 2);
				break;
		}
	}
	return offsets;
}
function detectOverflow(state, options) {
	if (options === void 0) options = {};
	var _options = options, _options$placement = _options.placement, placement = _options$placement === void 0 ? state.placement : _options$placement, _options$strategy = _options.strategy, strategy = _options$strategy === void 0 ? state.strategy : _options$strategy, _options$boundary = _options.boundary, boundary = _options$boundary === void 0 ? clippingParents : _options$boundary, _options$rootBoundary = _options.rootBoundary, rootBoundary = _options$rootBoundary === void 0 ? viewport : _options$rootBoundary, _options$elementConte = _options.elementContext, elementContext = _options$elementConte === void 0 ? popper : _options$elementConte, _options$altBoundary = _options.altBoundary, altBoundary = _options$altBoundary === void 0 ? false : _options$altBoundary, _options$padding = _options.padding, padding = _options$padding === void 0 ? 0 : _options$padding;
	var paddingObject = mergePaddingObject(typeof padding !== "number" ? padding : expandToHashMap(padding, basePlacements));
	var altContext = elementContext === popper ? reference : popper;
	var popperRect = state.rects.popper;
	var element = state.elements[altBoundary ? altContext : elementContext];
	var clippingClientRect = getClippingRect(isElement(element) ? element : element.contextElement || getDocumentElement(state.elements.popper), boundary, rootBoundary, strategy);
	var referenceClientRect = getBoundingClientRect(state.elements.reference);
	var popperOffsets = computeOffsets({
		reference: referenceClientRect,
		element: popperRect,
		strategy: "absolute",
		placement
	});
	var popperClientRect = rectToClientRect(Object.assign({}, popperRect, popperOffsets));
	var elementClientRect = elementContext === popper ? popperClientRect : referenceClientRect;
	var overflowOffsets = {
		top: clippingClientRect.top - elementClientRect.top + paddingObject.top,
		bottom: elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom,
		left: clippingClientRect.left - elementClientRect.left + paddingObject.left,
		right: elementClientRect.right - clippingClientRect.right + paddingObject.right
	};
	var offsetData = state.modifiersData.offset;
	if (elementContext === popper && offsetData) {
		var offset = offsetData[placement];
		Object.keys(overflowOffsets).forEach(function(key) {
			var multiply = [right, bottom].indexOf(key) >= 0 ? 1 : -1;
			var axis = [top, bottom].indexOf(key) >= 0 ? "y" : "x";
			overflowOffsets[key] += offset[axis] * multiply;
		});
	}
	return overflowOffsets;
}
function computeAutoPlacement(state, options) {
	if (options === void 0) options = {};
	var _options = options, placement = _options.placement, boundary = _options.boundary, rootBoundary = _options.rootBoundary, padding = _options.padding, flipVariations = _options.flipVariations, _options$allowedAutoP = _options.allowedAutoPlacements, allowedAutoPlacements = _options$allowedAutoP === void 0 ? placements : _options$allowedAutoP;
	var variation = getVariation(placement);
	var placements$1 = variation ? flipVariations ? variationPlacements : variationPlacements.filter(function(placement) {
		return getVariation(placement) === variation;
	}) : basePlacements;
	var allowedPlacements = placements$1.filter(function(placement) {
		return allowedAutoPlacements.indexOf(placement) >= 0;
	});
	if (allowedPlacements.length === 0) allowedPlacements = placements$1;
	var overflows = allowedPlacements.reduce(function(acc, placement) {
		acc[placement] = detectOverflow(state, {
			placement,
			boundary,
			rootBoundary,
			padding
		})[getBasePlacement(placement)];
		return acc;
	}, {});
	return Object.keys(overflows).sort(function(a, b) {
		return overflows[a] - overflows[b];
	});
}
function getExpandedFallbackPlacements(placement) {
	if (getBasePlacement(placement) === auto) return [];
	var oppositePlacement = getOppositePlacement(placement);
	return [
		getOppositeVariationPlacement(placement),
		oppositePlacement,
		getOppositeVariationPlacement(oppositePlacement)
	];
}
function flip(_ref) {
	var state = _ref.state, options = _ref.options, name = _ref.name;
	if (state.modifiersData[name]._skip) return;
	var _options$mainAxis = options.mainAxis, checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis, _options$altAxis = options.altAxis, checkAltAxis = _options$altAxis === void 0 ? true : _options$altAxis, specifiedFallbackPlacements = options.fallbackPlacements, padding = options.padding, boundary = options.boundary, rootBoundary = options.rootBoundary, altBoundary = options.altBoundary, _options$flipVariatio = options.flipVariations, flipVariations = _options$flipVariatio === void 0 ? true : _options$flipVariatio, allowedAutoPlacements = options.allowedAutoPlacements;
	var preferredPlacement = state.options.placement;
	var isBasePlacement = getBasePlacement(preferredPlacement) === preferredPlacement;
	var fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipVariations ? [getOppositePlacement(preferredPlacement)] : getExpandedFallbackPlacements(preferredPlacement));
	var placements = [preferredPlacement].concat(fallbackPlacements).reduce(function(acc, placement) {
		return acc.concat(getBasePlacement(placement) === auto ? computeAutoPlacement(state, {
			placement,
			boundary,
			rootBoundary,
			padding,
			flipVariations,
			allowedAutoPlacements
		}) : placement);
	}, []);
	var referenceRect = state.rects.reference;
	var popperRect = state.rects.popper;
	var checksMap = /* @__PURE__ */ new Map();
	var makeFallbackChecks = true;
	var firstFittingPlacement = placements[0];
	for (var i = 0; i < placements.length; i++) {
		var placement = placements[i];
		var _basePlacement = getBasePlacement(placement);
		var isStartVariation = getVariation(placement) === start;
		var isVertical = [top, bottom].indexOf(_basePlacement) >= 0;
		var len = isVertical ? "width" : "height";
		var overflow = detectOverflow(state, {
			placement,
			boundary,
			rootBoundary,
			altBoundary,
			padding
		});
		var mainVariationSide = isVertical ? isStartVariation ? right : left : isStartVariation ? bottom : top;
		if (referenceRect[len] > popperRect[len]) mainVariationSide = getOppositePlacement(mainVariationSide);
		var altVariationSide = getOppositePlacement(mainVariationSide);
		var checks = [];
		if (checkMainAxis) checks.push(overflow[_basePlacement] <= 0);
		if (checkAltAxis) checks.push(overflow[mainVariationSide] <= 0, overflow[altVariationSide] <= 0);
		if (checks.every(function(check) {
			return check;
		})) {
			firstFittingPlacement = placement;
			makeFallbackChecks = false;
			break;
		}
		checksMap.set(placement, checks);
	}
	if (makeFallbackChecks) {
		var numberOfChecks = flipVariations ? 3 : 1;
		var _loop = function _loop(_i) {
			var fittingPlacement = placements.find(function(placement) {
				var checks = checksMap.get(placement);
				if (checks) return checks.slice(0, _i).every(function(check) {
					return check;
				});
			});
			if (fittingPlacement) {
				firstFittingPlacement = fittingPlacement;
				return "break";
			}
		};
		for (var _i = numberOfChecks; _i > 0; _i--) if (_loop(_i) === "break") break;
	}
	if (state.placement !== firstFittingPlacement) {
		state.modifiersData[name]._skip = true;
		state.placement = firstFittingPlacement;
		state.reset = true;
	}
}
var flip$1 = {
	name: "flip",
	enabled: true,
	phase: "main",
	fn: flip,
	requiresIfExists: ["offset"],
	data: { _skip: false }
};
function getSideOffsets(overflow, rect, preventedOffsets) {
	if (preventedOffsets === void 0) preventedOffsets = {
		x: 0,
		y: 0
	};
	return {
		top: overflow.top - rect.height - preventedOffsets.y,
		right: overflow.right - rect.width + preventedOffsets.x,
		bottom: overflow.bottom - rect.height + preventedOffsets.y,
		left: overflow.left - rect.width - preventedOffsets.x
	};
}
function isAnySideFullyClipped(overflow) {
	return [
		top,
		right,
		bottom,
		left
	].some(function(side) {
		return overflow[side] >= 0;
	});
}
function hide(_ref) {
	var state = _ref.state, name = _ref.name;
	var referenceRect = state.rects.reference;
	var popperRect = state.rects.popper;
	var preventedOffsets = state.modifiersData.preventOverflow;
	var referenceOverflow = detectOverflow(state, { elementContext: "reference" });
	var popperAltOverflow = detectOverflow(state, { altBoundary: true });
	var referenceClippingOffsets = getSideOffsets(referenceOverflow, referenceRect);
	var popperEscapeOffsets = getSideOffsets(popperAltOverflow, popperRect, preventedOffsets);
	var isReferenceHidden = isAnySideFullyClipped(referenceClippingOffsets);
	var hasPopperEscaped = isAnySideFullyClipped(popperEscapeOffsets);
	state.modifiersData[name] = {
		referenceClippingOffsets,
		popperEscapeOffsets,
		isReferenceHidden,
		hasPopperEscaped
	};
	state.attributes.popper = Object.assign({}, state.attributes.popper, {
		"data-popper-reference-hidden": isReferenceHidden,
		"data-popper-escaped": hasPopperEscaped
	});
}
var hide$1 = {
	name: "hide",
	enabled: true,
	phase: "main",
	requiresIfExists: ["preventOverflow"],
	fn: hide
};
function distanceAndSkiddingToXY(placement, rects, offset) {
	var basePlacement = getBasePlacement(placement);
	var invertDistance = [left, top].indexOf(basePlacement) >= 0 ? -1 : 1;
	var _ref = typeof offset === "function" ? offset(Object.assign({}, rects, { placement })) : offset, skidding = _ref[0], distance = _ref[1];
	skidding = skidding || 0;
	distance = (distance || 0) * invertDistance;
	return [left, right].indexOf(basePlacement) >= 0 ? {
		x: distance,
		y: skidding
	} : {
		x: skidding,
		y: distance
	};
}
function offset(_ref2) {
	var state = _ref2.state, options = _ref2.options, name = _ref2.name;
	var _options$offset = options.offset, offset = _options$offset === void 0 ? [0, 0] : _options$offset;
	var data = placements.reduce(function(acc, placement) {
		acc[placement] = distanceAndSkiddingToXY(placement, state.rects, offset);
		return acc;
	}, {});
	var _data$state$placement = data[state.placement], x = _data$state$placement.x, y = _data$state$placement.y;
	if (state.modifiersData.popperOffsets != null) {
		state.modifiersData.popperOffsets.x += x;
		state.modifiersData.popperOffsets.y += y;
	}
	state.modifiersData[name] = data;
}
var offset$1 = {
	name: "offset",
	enabled: true,
	phase: "main",
	requires: ["popperOffsets"],
	fn: offset
};
function popperOffsets(_ref) {
	var state = _ref.state, name = _ref.name;
	state.modifiersData[name] = computeOffsets({
		reference: state.rects.reference,
		element: state.rects.popper,
		strategy: "absolute",
		placement: state.placement
	});
}
var popperOffsets$1 = {
	name: "popperOffsets",
	enabled: true,
	phase: "read",
	fn: popperOffsets,
	data: {}
};
function getAltAxis(axis) {
	return axis === "x" ? "y" : "x";
}
function preventOverflow(_ref) {
	var state = _ref.state, options = _ref.options, name = _ref.name;
	var _options$mainAxis = options.mainAxis, checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis, _options$altAxis = options.altAxis, checkAltAxis = _options$altAxis === void 0 ? false : _options$altAxis, boundary = options.boundary, rootBoundary = options.rootBoundary, altBoundary = options.altBoundary, padding = options.padding, _options$tether = options.tether, tether = _options$tether === void 0 ? true : _options$tether, _options$tetherOffset = options.tetherOffset, tetherOffset = _options$tetherOffset === void 0 ? 0 : _options$tetherOffset;
	var overflow = detectOverflow(state, {
		boundary,
		rootBoundary,
		padding,
		altBoundary
	});
	var basePlacement = getBasePlacement(state.placement);
	var variation = getVariation(state.placement);
	var isBasePlacement = !variation;
	var mainAxis = getMainAxisFromPlacement(basePlacement);
	var altAxis = getAltAxis(mainAxis);
	var popperOffsets = state.modifiersData.popperOffsets;
	var referenceRect = state.rects.reference;
	var popperRect = state.rects.popper;
	var tetherOffsetValue = typeof tetherOffset === "function" ? tetherOffset(Object.assign({}, state.rects, { placement: state.placement })) : tetherOffset;
	var normalizedTetherOffsetValue = typeof tetherOffsetValue === "number" ? {
		mainAxis: tetherOffsetValue,
		altAxis: tetherOffsetValue
	} : Object.assign({
		mainAxis: 0,
		altAxis: 0
	}, tetherOffsetValue);
	var offsetModifierState = state.modifiersData.offset ? state.modifiersData.offset[state.placement] : null;
	var data = {
		x: 0,
		y: 0
	};
	if (!popperOffsets) return;
	if (checkMainAxis) {
		var _offsetModifierState$;
		var mainSide = mainAxis === "y" ? top : left;
		var altSide = mainAxis === "y" ? bottom : right;
		var len = mainAxis === "y" ? "height" : "width";
		var offset = popperOffsets[mainAxis];
		var min$1 = offset + overflow[mainSide];
		var max$1 = offset - overflow[altSide];
		var additive = tether ? -popperRect[len] / 2 : 0;
		var minLen = variation === start ? referenceRect[len] : popperRect[len];
		var maxLen = variation === start ? -popperRect[len] : -referenceRect[len];
		var arrowElement = state.elements.arrow;
		var arrowRect = tether && arrowElement ? getLayoutRect(arrowElement) : {
			width: 0,
			height: 0
		};
		var arrowPaddingObject = state.modifiersData["arrow#persistent"] ? state.modifiersData["arrow#persistent"].padding : getFreshSideObject();
		var arrowPaddingMin = arrowPaddingObject[mainSide];
		var arrowPaddingMax = arrowPaddingObject[altSide];
		var arrowLen = within(0, referenceRect[len], arrowRect[len]);
		var minOffset = isBasePlacement ? referenceRect[len] / 2 - additive - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis : minLen - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis;
		var maxOffset = isBasePlacement ? -referenceRect[len] / 2 + additive + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis : maxLen + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis;
		var arrowOffsetParent = state.elements.arrow && getOffsetParent(state.elements.arrow);
		var clientOffset = arrowOffsetParent ? mainAxis === "y" ? arrowOffsetParent.clientTop || 0 : arrowOffsetParent.clientLeft || 0 : 0;
		var offsetModifierValue = (_offsetModifierState$ = offsetModifierState == null ? void 0 : offsetModifierState[mainAxis]) != null ? _offsetModifierState$ : 0;
		var tetherMin = offset + minOffset - offsetModifierValue - clientOffset;
		var tetherMax = offset + maxOffset - offsetModifierValue;
		var preventedOffset = within(tether ? min(min$1, tetherMin) : min$1, offset, tether ? max(max$1, tetherMax) : max$1);
		popperOffsets[mainAxis] = preventedOffset;
		data[mainAxis] = preventedOffset - offset;
	}
	if (checkAltAxis) {
		var _offsetModifierState$2;
		var _mainSide = mainAxis === "x" ? top : left;
		var _altSide = mainAxis === "x" ? bottom : right;
		var _offset = popperOffsets[altAxis];
		var _len = altAxis === "y" ? "height" : "width";
		var _min = _offset + overflow[_mainSide];
		var _max = _offset - overflow[_altSide];
		var isOriginSide = [top, left].indexOf(basePlacement) !== -1;
		var _offsetModifierValue = (_offsetModifierState$2 = offsetModifierState == null ? void 0 : offsetModifierState[altAxis]) != null ? _offsetModifierState$2 : 0;
		var _tetherMin = isOriginSide ? _min : _offset - referenceRect[_len] - popperRect[_len] - _offsetModifierValue + normalizedTetherOffsetValue.altAxis;
		var _tetherMax = isOriginSide ? _offset + referenceRect[_len] + popperRect[_len] - _offsetModifierValue - normalizedTetherOffsetValue.altAxis : _max;
		var _preventedOffset = tether && isOriginSide ? withinMaxClamp(_tetherMin, _offset, _tetherMax) : within(tether ? _tetherMin : _min, _offset, tether ? _tetherMax : _max);
		popperOffsets[altAxis] = _preventedOffset;
		data[altAxis] = _preventedOffset - _offset;
	}
	state.modifiersData[name] = data;
}
var preventOverflow$1 = {
	name: "preventOverflow",
	enabled: true,
	phase: "main",
	fn: preventOverflow,
	requiresIfExists: ["offset"]
};
function getHTMLElementScroll(element) {
	return {
		scrollLeft: element.scrollLeft,
		scrollTop: element.scrollTop
	};
}
function getNodeScroll(node) {
	if (node === getWindow(node) || !isHTMLElement(node)) return getWindowScroll(node);
	else return getHTMLElementScroll(node);
}
function isElementScaled(element) {
	var rect = element.getBoundingClientRect();
	var scaleX = round(rect.width) / element.offsetWidth || 1;
	var scaleY = round(rect.height) / element.offsetHeight || 1;
	return scaleX !== 1 || scaleY !== 1;
}
function getCompositeRect(elementOrVirtualElement, offsetParent, isFixed) {
	if (isFixed === void 0) isFixed = false;
	var isOffsetParentAnElement = isHTMLElement(offsetParent);
	var offsetParentIsScaled = isHTMLElement(offsetParent) && isElementScaled(offsetParent);
	var documentElement = getDocumentElement(offsetParent);
	var rect = getBoundingClientRect(elementOrVirtualElement, offsetParentIsScaled, isFixed);
	var scroll = {
		scrollLeft: 0,
		scrollTop: 0
	};
	var offsets = {
		x: 0,
		y: 0
	};
	if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
		if (getNodeName(offsetParent) !== "body" || isScrollParent(documentElement)) scroll = getNodeScroll(offsetParent);
		if (isHTMLElement(offsetParent)) {
			offsets = getBoundingClientRect(offsetParent, true);
			offsets.x += offsetParent.clientLeft;
			offsets.y += offsetParent.clientTop;
		} else if (documentElement) offsets.x = getWindowScrollBarX(documentElement);
	}
	return {
		x: rect.left + scroll.scrollLeft - offsets.x,
		y: rect.top + scroll.scrollTop - offsets.y,
		width: rect.width,
		height: rect.height
	};
}
function order(modifiers) {
	var map = /* @__PURE__ */ new Map();
	var visited = /* @__PURE__ */ new Set();
	var result = [];
	modifiers.forEach(function(modifier) {
		map.set(modifier.name, modifier);
	});
	function sort(modifier) {
		visited.add(modifier.name);
		[].concat(modifier.requires || [], modifier.requiresIfExists || []).forEach(function(dep) {
			if (!visited.has(dep)) {
				var depModifier = map.get(dep);
				if (depModifier) sort(depModifier);
			}
		});
		result.push(modifier);
	}
	modifiers.forEach(function(modifier) {
		if (!visited.has(modifier.name)) sort(modifier);
	});
	return result;
}
function orderModifiers(modifiers) {
	var orderedModifiers = order(modifiers);
	return modifierPhases.reduce(function(acc, phase) {
		return acc.concat(orderedModifiers.filter(function(modifier) {
			return modifier.phase === phase;
		}));
	}, []);
}
function debounce(fn) {
	var pending;
	return function() {
		if (!pending) pending = new Promise(function(resolve) {
			Promise.resolve().then(function() {
				pending = void 0;
				resolve(fn());
			});
		});
		return pending;
	};
}
function mergeByName(modifiers) {
	var merged = modifiers.reduce(function(merged, current) {
		var existing = merged[current.name];
		merged[current.name] = existing ? Object.assign({}, existing, current, {
			options: Object.assign({}, existing.options, current.options),
			data: Object.assign({}, existing.data, current.data)
		}) : current;
		return merged;
	}, {});
	return Object.keys(merged).map(function(key) {
		return merged[key];
	});
}
var DEFAULT_OPTIONS = {
	placement: "bottom",
	modifiers: [],
	strategy: "absolute"
};
function areValidElements() {
	for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) args[_key] = arguments[_key];
	return !args.some(function(element) {
		return !(element && typeof element.getBoundingClientRect === "function");
	});
}
function popperGenerator(generatorOptions) {
	if (generatorOptions === void 0) generatorOptions = {};
	var _generatorOptions = generatorOptions, _generatorOptions$def = _generatorOptions.defaultModifiers, defaultModifiers = _generatorOptions$def === void 0 ? [] : _generatorOptions$def, _generatorOptions$def2 = _generatorOptions.defaultOptions, defaultOptions = _generatorOptions$def2 === void 0 ? DEFAULT_OPTIONS : _generatorOptions$def2;
	return function createPopper(reference, popper, options) {
		if (options === void 0) options = defaultOptions;
		var state = {
			placement: "bottom",
			orderedModifiers: [],
			options: Object.assign({}, DEFAULT_OPTIONS, defaultOptions),
			modifiersData: {},
			elements: {
				reference,
				popper
			},
			attributes: {},
			styles: {}
		};
		var effectCleanupFns = [];
		var isDestroyed = false;
		var instance = {
			state,
			setOptions: function setOptions(setOptionsAction) {
				var options = typeof setOptionsAction === "function" ? setOptionsAction(state.options) : setOptionsAction;
				cleanupModifierEffects();
				state.options = Object.assign({}, defaultOptions, state.options, options);
				state.scrollParents = {
					reference: isElement(reference) ? listScrollParents(reference) : reference.contextElement ? listScrollParents(reference.contextElement) : [],
					popper: listScrollParents(popper)
				};
				var orderedModifiers = orderModifiers(mergeByName([].concat(defaultModifiers, state.options.modifiers)));
				state.orderedModifiers = orderedModifiers.filter(function(m) {
					return m.enabled;
				});
				runModifierEffects();
				return instance.update();
			},
			forceUpdate: function forceUpdate() {
				if (isDestroyed) return;
				var _state$elements = state.elements, reference = _state$elements.reference, popper = _state$elements.popper;
				if (!areValidElements(reference, popper)) return;
				state.rects = {
					reference: getCompositeRect(reference, getOffsetParent(popper), state.options.strategy === "fixed"),
					popper: getLayoutRect(popper)
				};
				state.reset = false;
				state.placement = state.options.placement;
				state.orderedModifiers.forEach(function(modifier) {
					return state.modifiersData[modifier.name] = Object.assign({}, modifier.data);
				});
				for (var index = 0; index < state.orderedModifiers.length; index++) {
					if (state.reset === true) {
						state.reset = false;
						index = -1;
						continue;
					}
					var _state$orderedModifie = state.orderedModifiers[index], fn = _state$orderedModifie.fn, _state$orderedModifie2 = _state$orderedModifie.options, _options = _state$orderedModifie2 === void 0 ? {} : _state$orderedModifie2, name = _state$orderedModifie.name;
					if (typeof fn === "function") state = fn({
						state,
						options: _options,
						name,
						instance
					}) || state;
				}
			},
			update: debounce(function() {
				return new Promise(function(resolve) {
					instance.forceUpdate();
					resolve(state);
				});
			}),
			destroy: function destroy() {
				cleanupModifierEffects();
				isDestroyed = true;
			}
		};
		if (!areValidElements(reference, popper)) return instance;
		instance.setOptions(options).then(function(state) {
			if (!isDestroyed && options.onFirstUpdate) options.onFirstUpdate(state);
		});
		function runModifierEffects() {
			state.orderedModifiers.forEach(function(_ref) {
				var name = _ref.name, _ref$options = _ref.options, options = _ref$options === void 0 ? {} : _ref$options, effect = _ref.effect;
				if (typeof effect === "function") {
					var cleanupFn = effect({
						state,
						name,
						instance,
						options
					});
					effectCleanupFns.push(cleanupFn || function noopFn() {});
				}
			});
		}
		function cleanupModifierEffects() {
			effectCleanupFns.forEach(function(fn) {
				return fn();
			});
			effectCleanupFns = [];
		}
		return instance;
	};
}
popperGenerator();
var createPopper = popperGenerator({ defaultModifiers: [
	eventListeners,
	popperOffsets$1,
	computeStyles$1,
	applyStyles$1,
	offset$1,
	flip$1,
	preventOverflow$1,
	arrow$1,
	hide$1
] });
var isString = (v) => Object.prototype.toString.call(v) === "[object String]";
var isObject = (v) => typeof v === "object" && !Array.isArray(v) && v !== null;
var merge = (a, b) => {
	if (!isObject(a)) return b;
	Object.keys(b).forEach((k) => a[k] = isObject(b[k]) ? merge(a[k] || {}, b[k]) : b[k]);
	return a;
};
function placekitAutocomplete(apiKey, { target = "#placekit", ...initOptions } = {}) {
	const input = isString(target) ? document.querySelector(target) : target;
	if (!input) throw `Error: target not found.`;
	else if (input.tagName !== "INPUT" || !["text", "search"].includes(input.getAttribute("type"))) throw `Error: target must be an HTML input of type "text" or "search".`;
	const pk = placekit(apiKey);
	const handlers = /* @__PURE__ */ new Map();
	let suggestions = [];
	let userValue = null;
	let backupValue = null;
	let country = null;
	let globalSearchMode = false;
	const client = {};
	const state = {
		empty: !input.value,
		dirty: false,
		freeForm: true,
		geolocation: false,
		countryMode: false
	};
	const options = {
		panel: {
			className: "",
			offset: 4,
			strategy: "absolute",
			flip: false
		},
		format: {
			flag: (countrycode) => `<img class="pka-flag" src="https://flagcdn.com/64x48/${countrycode?.toLowerCase()}.png" alt="${countrycode}" loading="lazy" />`,
			icon: (name, label) => `<span class="pka-icon pka-icon-${name}" role="img" aria-label="${label || "icon"}"></span>`,
			sub: (item) => {
				switch (item.type) {
					case "administrative": return [item.country].filter((s) => s).join(" ");
					case "city": return [item.zipcode.sort()[0], item.country].filter((s) => s).join(" ");
					case "country": return "";
					case "county": return [item.administrative, item.country].filter((s) => s).join(" ");
					default: return [item.city, item.county].filter((s) => s).join(", ");
				}
			},
			noResults: (query) => `No results for ${query}`,
			value: (item) => item.name,
			applySuggestion: "Apply suggestion",
			suggestions: "Address suggestions",
			changeCountry: "Change country",
			cancel: "Cancel"
		},
		countryAutoFill: true,
		countrySelect: true
	};
	input.setAttribute("autocomplete", "off");
	input.setAttribute("aria-autocomplete", "list");
	input.setAttribute("aria-expanded", false);
	input.setAttribute("role", "combobox");
	const panel = document.createElement("div");
	panel.classList.add("pka-panel");
	panel.innerHTML = `
    <div class="pka-panel-loading" role="progressbar" aria-hidden="true">${options.format.icon("loading")}</div>
    <div class="pka-panel-suggestions" role="listbox" aria-label="${options.format.suggestions}"></div>
    <div class="pka-panel-footer">
      <div class="pka-panel-country">
        <input type="checkbox" id="pka-panel-country-mode" class="pka-sr-only" />
        <label for="pka-panel-country-mode" class="pka-panel-country-open" aria-label="${options.format.changeCountry}">
        </label>
        <label for="pka-panel-country-mode" class="pka-panel-country-close">
          ${options.format.icon("cancel")}
          <span class="pka-panel-country-label">${options.format.cancel}</span>
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
  `;
	document.body.append(panel);
	const loading = panel.querySelector(".pka-panel-loading");
	const suggestionsList = panel.querySelector(".pka-panel-suggestions");
	const countryMode = panel.querySelector("#pka-panel-country-mode");
	const popper = createPopper(input, panel);
	function fireEvent(event, ...args) {
		if (handlers.has(event)) handlers.get(event).apply(client, args);
	}
	function setState(partial, silent = false) {
		if (!isObject(partial)) throw `TypeError: setState first argument must be a key/value object.`;
		let update = false;
		for (const k in state) if (k in partial && partial[k] !== state[k]) {
			state[k] = partial[k];
			update = true;
			if (!silent) fireEvent(k, state[k]);
		}
		if (update) fireEvent("state", state);
	}
	function setValue(value, { notify = false, focus = true } = {}) {
		if (isString(value)) {
			Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value").set.call(input, value);
			setState({ empty: !input.value });
			if (notify) {
				userValue = null;
				input.dispatchEvent(new Event("input", { bubbles: true }));
				input.dispatchEvent(new Event("change", { bubbles: true }));
			}
			if (focus) input.focus();
		}
	}
	function clearInput() {
		setValue("", {
			notify: true,
			focus: true
		});
		togglePanel(false);
		if (state.geolocation) search();
		else suggestions = [];
	}
	function restoreValue(clear = false) {
		if (userValue !== null) {
			setValue(userValue, { focus: false });
			if (clear) userValue = null;
		}
	}
	function togglePanel(bool) {
		const prev = panel.classList.contains("pka-open");
		const open = typeof bool === "undefined" ? !prev : bool;
		panel.classList.toggle("pka-open", open);
		input.setAttribute("aria-expanded", open);
		if (prev !== open) {
			if (!open) clearActive();
			fireEvent(open ? "open" : "close");
		}
	}
	function clearActive() {
		panel.querySelectorAll("[role=\"option\"]").forEach((node) => node.classList.remove("pka-active"));
	}
	function moveActive(index) {
		if (userValue === null) userValue = input.value;
		const children = Array.from(suggestionsList.children);
		const prev = children.findIndex((node) => node.classList.contains("pka-active"));
		clearActive();
		const steps = children.length + 1;
		const pos = (prev + 1 + index + steps) % steps;
		if (pos === 0) restoreValue();
		else {
			const current = children[pos - 1];
			current.classList.add("pka-active");
			suggestionsList.scrollTo({ top: current.offsetTop });
			setValue(options.format.value(suggestions[pos - 1]));
		}
	}
	function applySuggestion(index) {
		const children = Array.from(suggestionsList.children);
		if (typeof index === "undefined") index = children.findIndex((node) => node.classList.contains("pka-active"));
		if (!children[index]) return;
		const item = suggestions[index];
		if (state.countryMode) {
			setCountry(item);
			toggleCountryMode(false);
		} else {
			children.forEach((node, i) => {
				node.classList.toggle("pka-selected", i === index);
				node.setAttribute("aria-selected", i === index);
			});
			setValue(options.format.value(item), { notify: true });
			setState({
				dirty: true,
				freeForm: false
			});
			togglePanel(false);
			fireEvent("pick", input.value, item, index);
		}
	}
	let loadingTimeout;
	function setLoading(bool) {
		clearTimeout(loadingTimeout);
		loading.setAttribute("aria-hidden", true);
		if (bool) loadingTimeout = setTimeout(() => {
			loading.setAttribute("aria-hidden", false);
		}, 300);
	}
	async function search() {
		userValue = null;
		const query = input.value;
		setState({
			empty: !query,
			dirty: true,
			freeForm: true
		});
		setLoading(true);
		if (!countryMode.disabled) await detectCountry();
		const flagTypes = globalSearchMode ? ["city", "country"] : ["country"];
		pk.search(query, {
			countries: !!country ? [country.countrycode] : options.countries,
			types: state.countryMode ? ["country"] : options.types,
			maxResults: state.countryMode ? 20 : options.maxResults
		}).then(({ results }) => {
			setLoading(false);
			if (input.value !== query) return;
			suggestions = results;
			suggestionsList.innerHTML = results.length > 0 ? results.map((item) => `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false">
          ${flagTypes.includes(item.type) ? options.format.flag(item.countrycode) : options.format.icon(item.type || "pin", item.type)}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">${item.highlight}</span>
            <span class="pka-panel-suggestion-label-sub">${options.format.sub(item)}</span>
          </span>
          <button type="button" class="pka-panel-suggestion-action" aria-label="${options.format.applySuggestion}" />
        </div>
      `).join("") : `
        <div class="pka-panel-suggestion" role="option" tabindex="-1" aria-selected="false" aria-disabled="true">
          ${options.format.icon("noresults")}
          <span class="pka-panel-suggestion-label">
            <span class="pka-panel-suggestion-label-name">
              ${options.format.noResults?.call ? options.format.noResults(query) : options.format.noResults}
            </span>
          </span>
        </div>
      `;
			popper.update();
			fireEvent("results", query, results);
		}).catch((err) => fireEvent("error", err));
	}
	function setCountry(item) {
		panel.querySelector(".pka-panel-country-open").innerHTML = item === null ? "" : `
      ${options.format.flag(item.countrycode)}
      <span class="pka-panel-country-label">${item.name}</span>
      ${options.format.icon("switch")}
    `;
		if (item?.countrycode !== country?.countrycode) {
			country = item;
			fireEvent("countryChange", country);
		}
	}
	function toggleCountryMode(bool) {
		countryMode.checked = !countryMode.disabled && (typeof bool === "undefined" ? !countryMode.checked : bool);
		setState({ countryMode: countryMode.checked });
		if (state.countryMode) {
			backupValue = input.value;
			setValue(country.name);
			input.select();
			search();
		} else if (backupValue !== null) {
			setValue(backupValue);
			backupValue = null;
			search();
		}
	}
	function detectCountry() {
		return !!country ? Promise.resolve(country) : pk.reverse({
			maxResults: 1,
			types: ["country"]
		}).then(({ results }) => {
			if (results.length) {
				setCountry(results[0]);
				return results[0];
			}
			return null;
		}).catch((err) => fireEvent("error", err));
	}
	panel.addEventListener("mousemove", (e) => {
		if (!e.movementX && !e.movementY) return;
		clearActive();
		e.target.closest("[role=\"option\"]")?.classList.add("pka-active");
	});
	panel.addEventListener("click", (e) => {
		const target = e.target.closest("[role=\"option\"]");
		if (!target) return;
		e.stopPropagation();
		const index = Array.from(suggestionsList.children).indexOf(target);
		if (e.target.closest(".pka-panel-suggestion-action")) {
			const current = suggestions[index];
			if (current) {
				setValue(`${options.format.value(current)} `, { notify: true });
				setState({
					dirty: true,
					freeForm: false
				});
			}
		} else applySuggestion(index);
	});
	countryMode.addEventListener("change", (e) => {
		toggleCountryMode(e.target.checked);
	});
	function handleInput(e) {
		if (e instanceof InputEvent) {
			togglePanel(!!input.value.trim() || state.countryMode);
			search();
		}
	}
	input.addEventListener("input", handleInput);
	function handleFocus() {
		if (!state.dirty && !!input.value) {
			togglePanel(true);
			search();
		} else togglePanel(!!input.value.trim() || state.geolocation || state.countryMode);
	}
	input.addEventListener("click", handleFocus);
	input.addEventListener("focus", handleFocus);
	function handleClickOutside(e) {
		if (![input, panel].includes(e.target) && !panel.contains(e.target)) {
			togglePanel(false);
			restoreValue(true);
		}
	}
	window.addEventListener("click", handleClickOutside);
	function handleKeyNav(e) {
		if (input === document.activeElement) {
			const isPanelOpen = panel.classList.contains("pka-open");
			switch (e.key) {
				case "Up":
				case "ArrowUp":
					if (isPanelOpen) {
						e.preventDefault();
						if (e.altKey) togglePanel(false);
						else moveActive(-1);
					}
					break;
				case "Down":
				case "ArrowDown":
					if (suggestions.length > 0) {
						if (!isPanelOpen) {
							e.preventDefault();
							togglePanel(true);
						} else if (!e.altKey) {
							e.preventDefault();
							moveActive(1);
						}
					}
					break;
				case "Enter":
					if (isPanelOpen) {
						e.preventDefault();
						applySuggestion();
					}
					break;
				case "Esc":
				case "Escape":
					e.preventDefault();
					if (isPanelOpen) if (state.countryMode) toggleCountryMode(false);
					else {
						togglePanel(false);
						restoreValue(true);
					}
					else clearInput();
					break;
				case "Tab":
					togglePanel(false);
					break;
			}
		}
	}
	window.addEventListener("keydown", handleKeyNav);
	function handleResize() {
		window.requestAnimationFrame(() => {
			panel.style.width = `${input.offsetWidth}px`;
			popper.update();
		});
	}
	handleResize();
	const resizeObserver = new ResizeObserver(handleResize);
	resizeObserver.observe(input);
	function configure(opts = {}) {
		delete opts.target;
		const { panel: panelOptions, format: formatOptions, countryAutoFill, countrySelect, ...pkOptions } = merge(options, opts);
		pk.configure(pkOptions);
		panel.setAttribute("class", `pka-panel ${options.panel.className}`.trim());
		popper.setOptions({
			placement: "bottom-start",
			strategy: options.panel.strategy,
			modifiers: [{
				name: "flip",
				enabled: options.panel.flip
			}, {
				name: "offset",
				options: { offset: [0, options.panel.offset] }
			}]
		});
		const typesStr = options.types?.join(",").toLowerCase() ?? "";
		countryMode.disabled = options.countries || !options.countrySelect || typesStr === "country";
		panel.querySelector(".pka-panel-country").setAttribute("aria-hidden", countryMode.disabled);
		globalSearchMode = !options.countries && !options.countrySelect && [
			"city",
			"city,country",
			"country,city",
			"country"
		].includes(typesStr);
		if (options.countryAutoFill && typesStr === "country" && !state.dirty && !input.value.trim()) detectCountry().then((item) => {
			setValue(item.name, {
				notify: true,
				focus: false
			});
			setState({ freeForm: false });
			fireEvent("pick", item.name, item, 0);
		});
	}
	configure(initOptions);
	Object.defineProperty(client, "input", { get: () => input });
	Object.defineProperty(client, "options", { get: () => ({
		target,
		...options,
		...pk.options
	}) });
	Object.defineProperty(client, "handlers", { get: () => Object.fromEntries(handlers) });
	Object.defineProperty(client, "state", { get: () => state });
	client.setValue = (value, notify = false) => {
		setValue(value, {
			notify,
			focus: false
		});
		return client;
	};
	client.clear = clearInput;
	client.on = (event, handler) => {
		if (!isString(event)) throw `Error: first argument 'event' must be a string.`;
		if (typeof handler !== "undefined" && !handler.call) throw `Error: second argument 'handler' must be a function if defined.`;
		if (handler) handlers.set(event, handler);
		else if (handlers.has(event)) handlers.delete(event);
		return client;
	};
	client.open = () => {
		togglePanel(true);
		return client;
	};
	client.close = () => {
		togglePanel(false);
		return client;
	};
	client.configure = (opts = {}) => {
		configure(opts);
		return client;
	};
	client.requestGeolocation = (opts = {}) => {
		return pk.requestGeolocation(opts).then((pos) => {
			setState({ geolocation: true }, true);
			fireEvent("geolocation", true, pos);
			setCountry(null);
			input.focus();
			search();
			return pos;
		}).catch((err) => {
			setState({ geolocation: false }, true);
			fireEvent("geolocation", false, void 0, err.message);
		});
	};
	client.clearGeolocation = () => {
		pk.clearGeolocation();
		setState({ geolocation: false });
		return client;
	};
	client.destroy = () => {
		input.removeEventListener("input", handleInput);
		input.removeEventListener("click", handleFocus);
		input.removeEventListener("focus", handleFocus);
		window.removeEventListener("keydown", handleKeyNav);
		window.removeEventListener("click", handleClickOutside);
		resizeObserver.unobserve(input);
		panel.remove();
	};
	return client;
}
//#endregion
export { placekitAutocomplete as default };
