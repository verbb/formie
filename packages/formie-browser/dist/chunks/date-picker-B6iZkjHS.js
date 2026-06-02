import { n as __toESM, t as __commonJSMin } from "./chunk-K6L4z4UQ.js";
import { t as createDebug } from "./debug-KnZeKYBI.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as getModuleFieldContainers, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
//#region ../../node_modules/flatpickr/dist/esm/types/options.js
var HOOKS = [
	"onChange",
	"onClose",
	"onDayCreate",
	"onDestroy",
	"onKeyDown",
	"onMonthChange",
	"onOpen",
	"onParseConfig",
	"onReady",
	"onValueUpdate",
	"onYearChange",
	"onPreCalendarPosition"
];
var defaults = {
	_disable: [],
	allowInput: false,
	allowInvalidPreload: false,
	altFormat: "F j, Y",
	altInput: false,
	altInputClass: "form-control input",
	animate: typeof window === "object" && window.navigator.userAgent.indexOf("MSIE") === -1,
	ariaDateFormat: "F j, Y",
	autoFillDefaultTime: true,
	clickOpens: true,
	closeOnSelect: true,
	conjunction: ", ",
	dateFormat: "Y-m-d",
	defaultHour: 12,
	defaultMinute: 0,
	defaultSeconds: 0,
	disable: [],
	disableMobile: false,
	enableSeconds: false,
	enableTime: false,
	errorHandler: function(err) {
		return typeof console !== "undefined" && console.warn(err);
	},
	getWeek: function(givenDate) {
		var date = new Date(givenDate.getTime());
		date.setHours(0, 0, 0, 0);
		date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
		var week1 = new Date(date.getFullYear(), 0, 4);
		return 1 + Math.round(((date.getTime() - week1.getTime()) / 864e5 - 3 + (week1.getDay() + 6) % 7) / 7);
	},
	hourIncrement: 1,
	ignoredFocusElements: [],
	inline: false,
	locale: "default",
	minuteIncrement: 5,
	mode: "single",
	monthSelectorType: "dropdown",
	nextArrow: "<svg version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' viewBox='0 0 17 17'><g></g><path d='M13.207 8.472l-7.854 7.854-0.707-0.707 7.146-7.146-7.146-7.148 0.707-0.707 7.854 7.854z' /></svg>",
	noCalendar: false,
	now: /* @__PURE__ */ new Date(),
	onChange: [],
	onClose: [],
	onDayCreate: [],
	onDestroy: [],
	onKeyDown: [],
	onMonthChange: [],
	onOpen: [],
	onParseConfig: [],
	onReady: [],
	onValueUpdate: [],
	onYearChange: [],
	onPreCalendarPosition: [],
	plugins: [],
	position: "auto",
	positionElement: void 0,
	prevArrow: "<svg version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' viewBox='0 0 17 17'><g></g><path d='M5.207 8.471l7.146 7.147-0.707 0.707-7.853-7.854 7.854-7.853 0.707 0.707-7.147 7.146z' /></svg>",
	shorthandCurrentMonth: false,
	showMonths: 1,
	static: false,
	time_24hr: false,
	weekNumbers: false,
	wrap: false
};
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/l10n/default.js
var english = {
	weekdays: {
		shorthand: [
			"Sun",
			"Mon",
			"Tue",
			"Wed",
			"Thu",
			"Fri",
			"Sat"
		],
		longhand: [
			"Sunday",
			"Monday",
			"Tuesday",
			"Wednesday",
			"Thursday",
			"Friday",
			"Saturday"
		]
	},
	months: {
		shorthand: [
			"Jan",
			"Feb",
			"Mar",
			"Apr",
			"May",
			"Jun",
			"Jul",
			"Aug",
			"Sep",
			"Oct",
			"Nov",
			"Dec"
		],
		longhand: [
			"January",
			"February",
			"March",
			"April",
			"May",
			"June",
			"July",
			"August",
			"September",
			"October",
			"November",
			"December"
		]
	},
	daysInMonth: [
		31,
		28,
		31,
		30,
		31,
		30,
		31,
		31,
		30,
		31,
		30,
		31
	],
	firstDayOfWeek: 0,
	ordinal: function(nth) {
		var s = nth % 100;
		if (s > 3 && s < 21) return "th";
		switch (s % 10) {
			case 1: return "st";
			case 2: return "nd";
			case 3: return "rd";
			default: return "th";
		}
	},
	rangeSeparator: " to ",
	weekAbbreviation: "Wk",
	scrollTitle: "Scroll to increment",
	toggleTitle: "Click to toggle",
	amPM: ["AM", "PM"],
	yearAriaLabel: "Year",
	monthAriaLabel: "Month",
	hourAriaLabel: "Hour",
	minuteAriaLabel: "Minute",
	time_24hr: false
};
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/utils/index.js
var pad = function(number, length) {
	if (length === void 0) length = 2;
	return ("000" + number).slice(length * -1);
};
var int = function(bool) {
	return bool === true ? 1 : 0;
};
function debounce(fn, wait) {
	var t;
	return function() {
		var _this = this;
		var args = arguments;
		clearTimeout(t);
		t = setTimeout(function() {
			return fn.apply(_this, args);
		}, wait);
	};
}
var arrayify = function(obj) {
	return obj instanceof Array ? obj : [obj];
};
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/utils/dom.js
function toggleClass(elem, className, bool) {
	if (bool === true) return elem.classList.add(className);
	elem.classList.remove(className);
}
function createElement(tag, className, content) {
	var e = window.document.createElement(tag);
	className = className || "";
	content = content || "";
	e.className = className;
	if (content !== void 0) e.textContent = content;
	return e;
}
function clearNode(node) {
	while (node.firstChild) node.removeChild(node.firstChild);
}
function findParent(node, condition) {
	if (condition(node)) return node;
	else if (node.parentNode) return findParent(node.parentNode, condition);
}
function createNumberInput(inputClassName, opts) {
	var wrapper = createElement("div", "numInputWrapper"), numInput = createElement("input", "numInput " + inputClassName), arrowUp = createElement("span", "arrowUp"), arrowDown = createElement("span", "arrowDown");
	if (navigator.userAgent.indexOf("MSIE 9.0") === -1) numInput.type = "number";
	else {
		numInput.type = "text";
		numInput.pattern = "\\d*";
	}
	if (opts !== void 0) for (var key in opts) numInput.setAttribute(key, opts[key]);
	wrapper.appendChild(numInput);
	wrapper.appendChild(arrowUp);
	wrapper.appendChild(arrowDown);
	return wrapper;
}
function getEventTarget(event) {
	try {
		if (typeof event.composedPath === "function") return event.composedPath()[0];
		return event.target;
	} catch (error) {
		return event.target;
	}
}
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/utils/formatting.js
var doNothing = function() {};
var monthToStr = function(monthNumber, shorthand, locale) {
	return locale.months[shorthand ? "shorthand" : "longhand"][monthNumber];
};
var revFormat = {
	D: doNothing,
	F: function(dateObj, monthName, locale) {
		dateObj.setMonth(locale.months.longhand.indexOf(monthName));
	},
	G: function(dateObj, hour) {
		dateObj.setHours((dateObj.getHours() >= 12 ? 12 : 0) + parseFloat(hour));
	},
	H: function(dateObj, hour) {
		dateObj.setHours(parseFloat(hour));
	},
	J: function(dateObj, day) {
		dateObj.setDate(parseFloat(day));
	},
	K: function(dateObj, amPM, locale) {
		dateObj.setHours(dateObj.getHours() % 12 + 12 * int(new RegExp(locale.amPM[1], "i").test(amPM)));
	},
	M: function(dateObj, shortMonth, locale) {
		dateObj.setMonth(locale.months.shorthand.indexOf(shortMonth));
	},
	S: function(dateObj, seconds) {
		dateObj.setSeconds(parseFloat(seconds));
	},
	U: function(_, unixSeconds) {
		return /* @__PURE__ */ new Date(parseFloat(unixSeconds) * 1e3);
	},
	W: function(dateObj, weekNum, locale) {
		var weekNumber = parseInt(weekNum);
		var date = new Date(dateObj.getFullYear(), 0, 2 + (weekNumber - 1) * 7, 0, 0, 0, 0);
		date.setDate(date.getDate() - date.getDay() + locale.firstDayOfWeek);
		return date;
	},
	Y: function(dateObj, year) {
		dateObj.setFullYear(parseFloat(year));
	},
	Z: function(_, ISODate) {
		return new Date(ISODate);
	},
	d: function(dateObj, day) {
		dateObj.setDate(parseFloat(day));
	},
	h: function(dateObj, hour) {
		dateObj.setHours((dateObj.getHours() >= 12 ? 12 : 0) + parseFloat(hour));
	},
	i: function(dateObj, minutes) {
		dateObj.setMinutes(parseFloat(minutes));
	},
	j: function(dateObj, day) {
		dateObj.setDate(parseFloat(day));
	},
	l: doNothing,
	m: function(dateObj, month) {
		dateObj.setMonth(parseFloat(month) - 1);
	},
	n: function(dateObj, month) {
		dateObj.setMonth(parseFloat(month) - 1);
	},
	s: function(dateObj, seconds) {
		dateObj.setSeconds(parseFloat(seconds));
	},
	u: function(_, unixMillSeconds) {
		return new Date(parseFloat(unixMillSeconds));
	},
	w: doNothing,
	y: function(dateObj, year) {
		dateObj.setFullYear(2e3 + parseFloat(year));
	}
};
var tokenRegex = {
	D: "",
	F: "",
	G: "(\\d\\d|\\d)",
	H: "(\\d\\d|\\d)",
	J: "(\\d\\d|\\d)\\w+",
	K: "",
	M: "",
	S: "(\\d\\d|\\d)",
	U: "(.+)",
	W: "(\\d\\d|\\d)",
	Y: "(\\d{4})",
	Z: "(.+)",
	d: "(\\d\\d|\\d)",
	h: "(\\d\\d|\\d)",
	i: "(\\d\\d|\\d)",
	j: "(\\d\\d|\\d)",
	l: "",
	m: "(\\d\\d|\\d)",
	n: "(\\d\\d|\\d)",
	s: "(\\d\\d|\\d)",
	u: "(.+)",
	w: "(\\d\\d|\\d)",
	y: "(\\d{2})"
};
var formats = {
	Z: function(date) {
		return date.toISOString();
	},
	D: function(date, locale, options) {
		return locale.weekdays.shorthand[formats.w(date, locale, options)];
	},
	F: function(date, locale, options) {
		return monthToStr(formats.n(date, locale, options) - 1, false, locale);
	},
	G: function(date, locale, options) {
		return pad(formats.h(date, locale, options));
	},
	H: function(date) {
		return pad(date.getHours());
	},
	J: function(date, locale) {
		return locale.ordinal !== void 0 ? date.getDate() + locale.ordinal(date.getDate()) : date.getDate();
	},
	K: function(date, locale) {
		return locale.amPM[int(date.getHours() > 11)];
	},
	M: function(date, locale) {
		return monthToStr(date.getMonth(), true, locale);
	},
	S: function(date) {
		return pad(date.getSeconds());
	},
	U: function(date) {
		return date.getTime() / 1e3;
	},
	W: function(date, _, options) {
		return options.getWeek(date);
	},
	Y: function(date) {
		return pad(date.getFullYear(), 4);
	},
	d: function(date) {
		return pad(date.getDate());
	},
	h: function(date) {
		return date.getHours() % 12 ? date.getHours() % 12 : 12;
	},
	i: function(date) {
		return pad(date.getMinutes());
	},
	j: function(date) {
		return date.getDate();
	},
	l: function(date, locale) {
		return locale.weekdays.longhand[date.getDay()];
	},
	m: function(date) {
		return pad(date.getMonth() + 1);
	},
	n: function(date) {
		return date.getMonth() + 1;
	},
	s: function(date) {
		return date.getSeconds();
	},
	u: function(date) {
		return date.getTime();
	},
	w: function(date) {
		return date.getDay();
	},
	y: function(date) {
		return String(date.getFullYear()).substring(2);
	}
};
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/utils/dates.js
var createDateFormatter = function(_a) {
	var _b = _a.config, config = _b === void 0 ? defaults : _b, _c = _a.l10n, l10n = _c === void 0 ? english : _c, _d = _a.isMobile, isMobile = _d === void 0 ? false : _d;
	return function(dateObj, frmt, overrideLocale) {
		var locale = overrideLocale || l10n;
		if (config.formatDate !== void 0 && !isMobile) return config.formatDate(dateObj, frmt, locale);
		return frmt.split("").map(function(c, i, arr) {
			return formats[c] && arr[i - 1] !== "\\" ? formats[c](dateObj, locale, config) : c !== "\\" ? c : "";
		}).join("");
	};
};
var createDateParser = function(_a) {
	var _b = _a.config, config = _b === void 0 ? defaults : _b, _c = _a.l10n, l10n = _c === void 0 ? english : _c;
	return function(date, givenFormat, timeless, customLocale) {
		if (date !== 0 && !date) return void 0;
		var locale = customLocale || l10n;
		var parsedDate;
		var dateOrig = date;
		if (date instanceof Date) parsedDate = new Date(date.getTime());
		else if (typeof date !== "string" && date.toFixed !== void 0) parsedDate = new Date(date);
		else if (typeof date === "string") {
			var format = givenFormat || (config || defaults).dateFormat;
			var datestr = String(date).trim();
			if (datestr === "today") {
				parsedDate = /* @__PURE__ */ new Date();
				timeless = true;
			} else if (config && config.parseDate) parsedDate = config.parseDate(date, format);
			else if (/Z$/.test(datestr) || /GMT$/.test(datestr)) parsedDate = new Date(date);
			else {
				var matched = void 0, ops = [];
				for (var i = 0, matchIndex = 0, regexStr = ""; i < format.length; i++) {
					var token = format[i];
					var isBackSlash = token === "\\";
					var escaped = format[i - 1] === "\\" || isBackSlash;
					if (tokenRegex[token] && !escaped) {
						regexStr += tokenRegex[token];
						var match = new RegExp(regexStr).exec(date);
						if (match && (matched = true)) ops[token !== "Y" ? "push" : "unshift"]({
							fn: revFormat[token],
							val: match[++matchIndex]
						});
					} else if (!isBackSlash) regexStr += ".";
				}
				parsedDate = !config || !config.noCalendar ? new Date((/* @__PURE__ */ new Date()).getFullYear(), 0, 1, 0, 0, 0, 0) : new Date((/* @__PURE__ */ new Date()).setHours(0, 0, 0, 0));
				ops.forEach(function(_a) {
					var fn = _a.fn, val = _a.val;
					return parsedDate = fn(parsedDate, val, locale) || parsedDate;
				});
				parsedDate = matched ? parsedDate : void 0;
			}
		}
		if (!(parsedDate instanceof Date && !isNaN(parsedDate.getTime()))) {
			config.errorHandler(/* @__PURE__ */ new Error("Invalid date provided: " + dateOrig));
			return;
		}
		if (timeless === true) parsedDate.setHours(0, 0, 0, 0);
		return parsedDate;
	};
};
function compareDates(date1, date2, timeless) {
	if (timeless === void 0) timeless = true;
	if (timeless !== false) return new Date(date1.getTime()).setHours(0, 0, 0, 0) - new Date(date2.getTime()).setHours(0, 0, 0, 0);
	return date1.getTime() - date2.getTime();
}
var isBetween = function(ts, ts1, ts2) {
	return ts > Math.min(ts1, ts2) && ts < Math.max(ts1, ts2);
};
var calculateSecondsSinceMidnight = function(hours, minutes, seconds) {
	return hours * 3600 + minutes * 60 + seconds;
};
var parseSeconds = function(secondsSinceMidnight) {
	var hours = Math.floor(secondsSinceMidnight / 3600), minutes = (secondsSinceMidnight - hours * 3600) / 60;
	return [
		hours,
		minutes,
		secondsSinceMidnight - hours * 3600 - minutes * 60
	];
};
var duration = { DAY: 864e5 };
function getDefaultHours(config) {
	var hours = config.defaultHour;
	var minutes = config.defaultMinute;
	var seconds = config.defaultSeconds;
	if (config.minDate !== void 0) {
		var minHour = config.minDate.getHours();
		var minMinutes = config.minDate.getMinutes();
		var minSeconds = config.minDate.getSeconds();
		if (hours < minHour) hours = minHour;
		if (hours === minHour && minutes < minMinutes) minutes = minMinutes;
		if (hours === minHour && minutes === minMinutes && seconds < minSeconds) seconds = config.minDate.getSeconds();
	}
	if (config.maxDate !== void 0) {
		var maxHr = config.maxDate.getHours();
		var maxMinutes = config.maxDate.getMinutes();
		hours = Math.min(hours, maxHr);
		if (hours === maxHr) minutes = Math.min(maxMinutes, minutes);
		if (hours === maxHr && minutes === maxMinutes) seconds = config.maxDate.getSeconds();
	}
	return {
		hours,
		minutes,
		seconds
	};
}
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/utils/polyfills.js
if (typeof Object.assign !== "function") Object.assign = function(target) {
	var args = [];
	for (var _i = 1; _i < arguments.length; _i++) args[_i - 1] = arguments[_i];
	if (!target) throw TypeError("Cannot convert undefined or null to object");
	var _loop_1 = function(source) {
		if (source) Object.keys(source).forEach(function(key) {
			return target[key] = source[key];
		});
	};
	for (var _a = 0, args_1 = args; _a < args_1.length; _a++) {
		var source = args_1[_a];
		_loop_1(source);
	}
	return target;
};
//#endregion
//#region ../../node_modules/flatpickr/dist/esm/index.js
var __assign = function() {
	__assign = Object.assign || function(t) {
		for (var s, i = 1, n = arguments.length; i < n; i++) {
			s = arguments[i];
			for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
		}
		return t;
	};
	return __assign.apply(this, arguments);
};
var __spreadArrays = function() {
	for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
	for (var r = Array(s), k = 0, i = 0; i < il; i++) for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++) r[k] = a[j];
	return r;
};
var DEBOUNCED_CHANGE_MS = 300;
function FlatpickrInstance(element, instanceConfig) {
	var self = {
		config: __assign(__assign({}, defaults), flatpickr.defaultConfig),
		l10n: english
	};
	self.parseDate = createDateParser({
		config: self.config,
		l10n: self.l10n
	});
	self._handlers = [];
	self.pluginElements = [];
	self.loadedPlugins = [];
	self._bind = bind;
	self._setHoursFromDate = setHoursFromDate;
	self._positionCalendar = positionCalendar;
	self.changeMonth = changeMonth;
	self.changeYear = changeYear;
	self.clear = clear;
	self.close = close;
	self.onMouseOver = onMouseOver;
	self._createElement = createElement;
	self.createDay = createDay;
	self.destroy = destroy;
	self.isEnabled = isEnabled;
	self.jumpToDate = jumpToDate;
	self.updateValue = updateValue;
	self.open = open;
	self.redraw = redraw;
	self.set = set;
	self.setDate = setDate;
	self.toggle = toggle;
	function setupHelperFunctions() {
		self.utils = { getDaysInMonth: function(month, yr) {
			if (month === void 0) month = self.currentMonth;
			if (yr === void 0) yr = self.currentYear;
			if (month === 1 && (yr % 4 === 0 && yr % 100 !== 0 || yr % 400 === 0)) return 29;
			return self.l10n.daysInMonth[month];
		} };
	}
	function init() {
		self.element = self.input = element;
		self.isOpen = false;
		parseConfig();
		setupLocale();
		setupInputs();
		setupDates();
		setupHelperFunctions();
		if (!self.isMobile) build();
		bindEvents();
		if (self.selectedDates.length || self.config.noCalendar) {
			if (self.config.enableTime) setHoursFromDate(self.config.noCalendar ? self.latestSelectedDateObj : void 0);
			updateValue(false);
		}
		setCalendarWidth();
		var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
		if (!self.isMobile && isSafari) positionCalendar();
		triggerEvent("onReady");
	}
	function getClosestActiveElement() {
		var _a;
		return ((_a = self.calendarContainer) === null || _a === void 0 ? void 0 : _a.getRootNode()).activeElement || document.activeElement;
	}
	function bindToInstance(fn) {
		return fn.bind(self);
	}
	function setCalendarWidth() {
		var config = self.config;
		if (config.weekNumbers === false && config.showMonths === 1) return;
		else if (config.noCalendar !== true) window.requestAnimationFrame(function() {
			if (self.calendarContainer !== void 0) {
				self.calendarContainer.style.visibility = "hidden";
				self.calendarContainer.style.display = "block";
			}
			if (self.daysContainer !== void 0) {
				var daysWidth = (self.days.offsetWidth + 1) * config.showMonths;
				self.daysContainer.style.width = daysWidth + "px";
				self.calendarContainer.style.width = daysWidth + (self.weekWrapper !== void 0 ? self.weekWrapper.offsetWidth : 0) + "px";
				self.calendarContainer.style.removeProperty("visibility");
				self.calendarContainer.style.removeProperty("display");
			}
		});
	}
	function updateTime(e) {
		if (self.selectedDates.length === 0) {
			var defaultDate = self.config.minDate === void 0 || compareDates(/* @__PURE__ */ new Date(), self.config.minDate) >= 0 ? /* @__PURE__ */ new Date() : new Date(self.config.minDate.getTime());
			var defaults = getDefaultHours(self.config);
			defaultDate.setHours(defaults.hours, defaults.minutes, defaults.seconds, defaultDate.getMilliseconds());
			self.selectedDates = [defaultDate];
			self.latestSelectedDateObj = defaultDate;
		}
		if (e !== void 0 && e.type !== "blur") timeWrapper(e);
		var prevValue = self._input.value;
		setHoursFromInputs();
		updateValue();
		if (self._input.value !== prevValue) self._debouncedChange();
	}
	function ampm2military(hour, amPM) {
		return hour % 12 + 12 * int(amPM === self.l10n.amPM[1]);
	}
	function military2ampm(hour) {
		switch (hour % 24) {
			case 0:
			case 12: return 12;
			default: return hour % 12;
		}
	}
	function setHoursFromInputs() {
		if (self.hourElement === void 0 || self.minuteElement === void 0) return;
		var hours = (parseInt(self.hourElement.value.slice(-2), 10) || 0) % 24, minutes = (parseInt(self.minuteElement.value, 10) || 0) % 60, seconds = self.secondElement !== void 0 ? (parseInt(self.secondElement.value, 10) || 0) % 60 : 0;
		if (self.amPM !== void 0) hours = ampm2military(hours, self.amPM.textContent);
		var limitMinHours = self.config.minTime !== void 0 || self.config.minDate && self.minDateHasTime && self.latestSelectedDateObj && compareDates(self.latestSelectedDateObj, self.config.minDate, true) === 0;
		var limitMaxHours = self.config.maxTime !== void 0 || self.config.maxDate && self.maxDateHasTime && self.latestSelectedDateObj && compareDates(self.latestSelectedDateObj, self.config.maxDate, true) === 0;
		if (self.config.maxTime !== void 0 && self.config.minTime !== void 0 && self.config.minTime > self.config.maxTime) {
			var minBound = calculateSecondsSinceMidnight(self.config.minTime.getHours(), self.config.minTime.getMinutes(), self.config.minTime.getSeconds());
			var maxBound = calculateSecondsSinceMidnight(self.config.maxTime.getHours(), self.config.maxTime.getMinutes(), self.config.maxTime.getSeconds());
			var currentTime = calculateSecondsSinceMidnight(hours, minutes, seconds);
			if (currentTime > maxBound && currentTime < minBound) {
				var result = parseSeconds(minBound);
				hours = result[0];
				minutes = result[1];
				seconds = result[2];
			}
		} else {
			if (limitMaxHours) {
				var maxTime = self.config.maxTime !== void 0 ? self.config.maxTime : self.config.maxDate;
				hours = Math.min(hours, maxTime.getHours());
				if (hours === maxTime.getHours()) minutes = Math.min(minutes, maxTime.getMinutes());
				if (minutes === maxTime.getMinutes()) seconds = Math.min(seconds, maxTime.getSeconds());
			}
			if (limitMinHours) {
				var minTime = self.config.minTime !== void 0 ? self.config.minTime : self.config.minDate;
				hours = Math.max(hours, minTime.getHours());
				if (hours === minTime.getHours() && minutes < minTime.getMinutes()) minutes = minTime.getMinutes();
				if (minutes === minTime.getMinutes()) seconds = Math.max(seconds, minTime.getSeconds());
			}
		}
		setHours(hours, minutes, seconds);
	}
	function setHoursFromDate(dateObj) {
		var date = dateObj || self.latestSelectedDateObj;
		if (date && date instanceof Date) setHours(date.getHours(), date.getMinutes(), date.getSeconds());
	}
	function setHours(hours, minutes, seconds) {
		if (self.latestSelectedDateObj !== void 0) self.latestSelectedDateObj.setHours(hours % 24, minutes, seconds || 0, 0);
		if (!self.hourElement || !self.minuteElement || self.isMobile) return;
		self.hourElement.value = pad(!self.config.time_24hr ? (12 + hours) % 12 + 12 * int(hours % 12 === 0) : hours);
		self.minuteElement.value = pad(minutes);
		if (self.amPM !== void 0) self.amPM.textContent = self.l10n.amPM[int(hours >= 12)];
		if (self.secondElement !== void 0) self.secondElement.value = pad(seconds);
	}
	function onYearInput(event) {
		var eventTarget = getEventTarget(event);
		var year = parseInt(eventTarget.value) + (event.delta || 0);
		if (year / 1e3 > 1 || event.key === "Enter" && !/[^\d]/.test(year.toString())) changeYear(year);
	}
	function bind(element, event, handler, options) {
		if (event instanceof Array) return event.forEach(function(ev) {
			return bind(element, ev, handler, options);
		});
		if (element instanceof Array) return element.forEach(function(el) {
			return bind(el, event, handler, options);
		});
		element.addEventListener(event, handler, options);
		self._handlers.push({ remove: function() {
			return element.removeEventListener(event, handler, options);
		} });
	}
	function triggerChange() {
		triggerEvent("onChange");
	}
	function bindEvents() {
		if (self.config.wrap) [
			"open",
			"close",
			"toggle",
			"clear"
		].forEach(function(evt) {
			Array.prototype.forEach.call(self.element.querySelectorAll("[data-" + evt + "]"), function(el) {
				return bind(el, "click", self[evt]);
			});
		});
		if (self.isMobile) {
			setupMobile();
			return;
		}
		var debouncedResize = debounce(onResize, 50);
		self._debouncedChange = debounce(triggerChange, DEBOUNCED_CHANGE_MS);
		if (self.daysContainer && !/iPhone|iPad|iPod/i.test(navigator.userAgent)) bind(self.daysContainer, "mouseover", function(e) {
			if (self.config.mode === "range") onMouseOver(getEventTarget(e));
		});
		bind(self._input, "keydown", onKeyDown);
		if (self.calendarContainer !== void 0) bind(self.calendarContainer, "keydown", onKeyDown);
		if (!self.config.inline && !self.config.static) bind(window, "resize", debouncedResize);
		if (window.ontouchstart !== void 0) bind(window.document, "touchstart", documentClick);
		else bind(window.document, "mousedown", documentClick);
		bind(window.document, "focus", documentClick, { capture: true });
		if (self.config.clickOpens === true) {
			bind(self._input, "focus", self.open);
			bind(self._input, "click", self.open);
		}
		if (self.daysContainer !== void 0) {
			bind(self.monthNav, "click", onMonthNavClick);
			bind(self.monthNav, ["keyup", "increment"], onYearInput);
			bind(self.daysContainer, "click", selectDate);
		}
		if (self.timeContainer !== void 0 && self.minuteElement !== void 0 && self.hourElement !== void 0) {
			var selText = function(e) {
				return getEventTarget(e).select();
			};
			bind(self.timeContainer, ["increment"], updateTime);
			bind(self.timeContainer, "blur", updateTime, { capture: true });
			bind(self.timeContainer, "click", timeIncrement);
			bind([self.hourElement, self.minuteElement], ["focus", "click"], selText);
			if (self.secondElement !== void 0) bind(self.secondElement, "focus", function() {
				return self.secondElement && self.secondElement.select();
			});
			if (self.amPM !== void 0) bind(self.amPM, "click", function(e) {
				updateTime(e);
			});
		}
		if (self.config.allowInput) bind(self._input, "blur", onBlur);
	}
	function jumpToDate(jumpDate, triggerChange) {
		var jumpTo = jumpDate !== void 0 ? self.parseDate(jumpDate) : self.latestSelectedDateObj || (self.config.minDate && self.config.minDate > self.now ? self.config.minDate : self.config.maxDate && self.config.maxDate < self.now ? self.config.maxDate : self.now);
		var oldYear = self.currentYear;
		var oldMonth = self.currentMonth;
		try {
			if (jumpTo !== void 0) {
				self.currentYear = jumpTo.getFullYear();
				self.currentMonth = jumpTo.getMonth();
			}
		} catch (e) {
			e.message = "Invalid date supplied: " + jumpTo;
			self.config.errorHandler(e);
		}
		if (triggerChange && self.currentYear !== oldYear) {
			triggerEvent("onYearChange");
			buildMonthSwitch();
		}
		if (triggerChange && (self.currentYear !== oldYear || self.currentMonth !== oldMonth)) triggerEvent("onMonthChange");
		self.redraw();
	}
	function timeIncrement(e) {
		var eventTarget = getEventTarget(e);
		if (~eventTarget.className.indexOf("arrow")) incrementNumInput(e, eventTarget.classList.contains("arrowUp") ? 1 : -1);
	}
	function incrementNumInput(e, delta, inputElem) {
		var target = e && getEventTarget(e);
		var input = inputElem || target && target.parentNode && target.parentNode.firstChild;
		var event = createEvent("increment");
		event.delta = delta;
		input && input.dispatchEvent(event);
	}
	function build() {
		var fragment = window.document.createDocumentFragment();
		self.calendarContainer = createElement("div", "flatpickr-calendar");
		self.calendarContainer.tabIndex = -1;
		if (!self.config.noCalendar) {
			fragment.appendChild(buildMonthNav());
			self.innerContainer = createElement("div", "flatpickr-innerContainer");
			if (self.config.weekNumbers) {
				var _a = buildWeeks(), weekWrapper = _a.weekWrapper, weekNumbers = _a.weekNumbers;
				self.innerContainer.appendChild(weekWrapper);
				self.weekNumbers = weekNumbers;
				self.weekWrapper = weekWrapper;
			}
			self.rContainer = createElement("div", "flatpickr-rContainer");
			self.rContainer.appendChild(buildWeekdays());
			if (!self.daysContainer) {
				self.daysContainer = createElement("div", "flatpickr-days");
				self.daysContainer.tabIndex = -1;
			}
			buildDays();
			self.rContainer.appendChild(self.daysContainer);
			self.innerContainer.appendChild(self.rContainer);
			fragment.appendChild(self.innerContainer);
		}
		if (self.config.enableTime) fragment.appendChild(buildTime());
		toggleClass(self.calendarContainer, "rangeMode", self.config.mode === "range");
		toggleClass(self.calendarContainer, "animate", self.config.animate === true);
		toggleClass(self.calendarContainer, "multiMonth", self.config.showMonths > 1);
		self.calendarContainer.appendChild(fragment);
		var customAppend = self.config.appendTo !== void 0 && self.config.appendTo.nodeType !== void 0;
		if (self.config.inline || self.config.static) {
			self.calendarContainer.classList.add(self.config.inline ? "inline" : "static");
			if (self.config.inline) {
				if (!customAppend && self.element.parentNode) self.element.parentNode.insertBefore(self.calendarContainer, self._input.nextSibling);
				else if (self.config.appendTo !== void 0) self.config.appendTo.appendChild(self.calendarContainer);
			}
			if (self.config.static) {
				var wrapper = createElement("div", "flatpickr-wrapper");
				if (self.element.parentNode) self.element.parentNode.insertBefore(wrapper, self.element);
				wrapper.appendChild(self.element);
				if (self.altInput) wrapper.appendChild(self.altInput);
				wrapper.appendChild(self.calendarContainer);
			}
		}
		if (!self.config.static && !self.config.inline) (self.config.appendTo !== void 0 ? self.config.appendTo : window.document.body).appendChild(self.calendarContainer);
	}
	function createDay(className, date, _dayNumber, i) {
		var dateIsEnabled = isEnabled(date, true), dayElement = createElement("span", className, date.getDate().toString());
		dayElement.dateObj = date;
		dayElement.$i = i;
		dayElement.setAttribute("aria-label", self.formatDate(date, self.config.ariaDateFormat));
		if (className.indexOf("hidden") === -1 && compareDates(date, self.now) === 0) {
			self.todayDateElem = dayElement;
			dayElement.classList.add("today");
			dayElement.setAttribute("aria-current", "date");
		}
		if (dateIsEnabled) {
			dayElement.tabIndex = -1;
			if (isDateSelected(date)) {
				dayElement.classList.add("selected");
				self.selectedDateElem = dayElement;
				if (self.config.mode === "range") {
					toggleClass(dayElement, "startRange", self.selectedDates[0] && compareDates(date, self.selectedDates[0], true) === 0);
					toggleClass(dayElement, "endRange", self.selectedDates[1] && compareDates(date, self.selectedDates[1], true) === 0);
					if (className === "nextMonthDay") dayElement.classList.add("inRange");
				}
			}
		} else dayElement.classList.add("flatpickr-disabled");
		if (self.config.mode === "range") {
			if (isDateInRange(date) && !isDateSelected(date)) dayElement.classList.add("inRange");
		}
		if (self.weekNumbers && self.config.showMonths === 1 && className !== "prevMonthDay" && i % 7 === 6) self.weekNumbers.insertAdjacentHTML("beforeend", "<span class='flatpickr-day'>" + self.config.getWeek(date) + "</span>");
		triggerEvent("onDayCreate", dayElement);
		return dayElement;
	}
	function focusOnDayElem(targetNode) {
		targetNode.focus();
		if (self.config.mode === "range") onMouseOver(targetNode);
	}
	function getFirstAvailableDay(delta) {
		var startMonth = delta > 0 ? 0 : self.config.showMonths - 1;
		var endMonth = delta > 0 ? self.config.showMonths : -1;
		for (var m = startMonth; m != endMonth; m += delta) {
			var month = self.daysContainer.children[m];
			var startIndex = delta > 0 ? 0 : month.children.length - 1;
			var endIndex = delta > 0 ? month.children.length : -1;
			for (var i = startIndex; i != endIndex; i += delta) {
				var c = month.children[i];
				if (c.className.indexOf("hidden") === -1 && isEnabled(c.dateObj)) return c;
			}
		}
	}
	function getNextAvailableDay(current, delta) {
		var givenMonth = current.className.indexOf("Month") === -1 ? current.dateObj.getMonth() : self.currentMonth;
		var endMonth = delta > 0 ? self.config.showMonths : -1;
		var loopDelta = delta > 0 ? 1 : -1;
		for (var m = givenMonth - self.currentMonth; m != endMonth; m += loopDelta) {
			var month = self.daysContainer.children[m];
			var startIndex = givenMonth - self.currentMonth === m ? current.$i + delta : delta < 0 ? month.children.length - 1 : 0;
			var numMonthDays = month.children.length;
			for (var i = startIndex; i >= 0 && i < numMonthDays && i != (delta > 0 ? numMonthDays : -1); i += loopDelta) {
				var c = month.children[i];
				if (c.className.indexOf("hidden") === -1 && isEnabled(c.dateObj) && Math.abs(current.$i - i) >= Math.abs(delta)) return focusOnDayElem(c);
			}
		}
		self.changeMonth(loopDelta);
		focusOnDay(getFirstAvailableDay(loopDelta), 0);
	}
	function focusOnDay(current, offset) {
		var activeElement = getClosestActiveElement();
		var dayFocused = isInView(activeElement || document.body);
		var startElem = current !== void 0 ? current : dayFocused ? activeElement : self.selectedDateElem !== void 0 && isInView(self.selectedDateElem) ? self.selectedDateElem : self.todayDateElem !== void 0 && isInView(self.todayDateElem) ? self.todayDateElem : getFirstAvailableDay(offset > 0 ? 1 : -1);
		if (startElem === void 0) self._input.focus();
		else if (!dayFocused) focusOnDayElem(startElem);
		else getNextAvailableDay(startElem, offset);
	}
	function buildMonthDays(year, month) {
		var firstOfMonth = (new Date(year, month, 1).getDay() - self.l10n.firstDayOfWeek + 7) % 7;
		var prevMonthDays = self.utils.getDaysInMonth((month - 1 + 12) % 12, year);
		var daysInMonth = self.utils.getDaysInMonth(month, year), days = window.document.createDocumentFragment(), isMultiMonth = self.config.showMonths > 1, prevMonthDayClass = isMultiMonth ? "prevMonthDay hidden" : "prevMonthDay", nextMonthDayClass = isMultiMonth ? "nextMonthDay hidden" : "nextMonthDay";
		var dayNumber = prevMonthDays + 1 - firstOfMonth, dayIndex = 0;
		for (; dayNumber <= prevMonthDays; dayNumber++, dayIndex++) days.appendChild(createDay("flatpickr-day " + prevMonthDayClass, new Date(year, month - 1, dayNumber), dayNumber, dayIndex));
		for (dayNumber = 1; dayNumber <= daysInMonth; dayNumber++, dayIndex++) days.appendChild(createDay("flatpickr-day", new Date(year, month, dayNumber), dayNumber, dayIndex));
		for (var dayNum = daysInMonth + 1; dayNum <= 42 - firstOfMonth && (self.config.showMonths === 1 || dayIndex % 7 !== 0); dayNum++, dayIndex++) days.appendChild(createDay("flatpickr-day " + nextMonthDayClass, new Date(year, month + 1, dayNum % daysInMonth), dayNum, dayIndex));
		var dayContainer = createElement("div", "dayContainer");
		dayContainer.appendChild(days);
		return dayContainer;
	}
	function buildDays() {
		if (self.daysContainer === void 0) return;
		clearNode(self.daysContainer);
		if (self.weekNumbers) clearNode(self.weekNumbers);
		var frag = document.createDocumentFragment();
		for (var i = 0; i < self.config.showMonths; i++) {
			var d = new Date(self.currentYear, self.currentMonth, 1);
			d.setMonth(self.currentMonth + i);
			frag.appendChild(buildMonthDays(d.getFullYear(), d.getMonth()));
		}
		self.daysContainer.appendChild(frag);
		self.days = self.daysContainer.firstChild;
		if (self.config.mode === "range" && self.selectedDates.length === 1) onMouseOver();
	}
	function buildMonthSwitch() {
		if (self.config.showMonths > 1 || self.config.monthSelectorType !== "dropdown") return;
		var shouldBuildMonth = function(month) {
			if (self.config.minDate !== void 0 && self.currentYear === self.config.minDate.getFullYear() && month < self.config.minDate.getMonth()) return false;
			return !(self.config.maxDate !== void 0 && self.currentYear === self.config.maxDate.getFullYear() && month > self.config.maxDate.getMonth());
		};
		self.monthsDropdownContainer.tabIndex = -1;
		self.monthsDropdownContainer.innerHTML = "";
		for (var i = 0; i < 12; i++) {
			if (!shouldBuildMonth(i)) continue;
			var month = createElement("option", "flatpickr-monthDropdown-month");
			month.value = new Date(self.currentYear, i).getMonth().toString();
			month.textContent = monthToStr(i, self.config.shorthandCurrentMonth, self.l10n);
			month.tabIndex = -1;
			if (self.currentMonth === i) month.selected = true;
			self.monthsDropdownContainer.appendChild(month);
		}
	}
	function buildMonth() {
		var container = createElement("div", "flatpickr-month");
		var monthNavFragment = window.document.createDocumentFragment();
		var monthElement;
		if (self.config.showMonths > 1 || self.config.monthSelectorType === "static") monthElement = createElement("span", "cur-month");
		else {
			self.monthsDropdownContainer = createElement("select", "flatpickr-monthDropdown-months");
			self.monthsDropdownContainer.setAttribute("aria-label", self.l10n.monthAriaLabel);
			bind(self.monthsDropdownContainer, "change", function(e) {
				var target = getEventTarget(e);
				var selectedMonth = parseInt(target.value, 10);
				self.changeMonth(selectedMonth - self.currentMonth);
				triggerEvent("onMonthChange");
			});
			buildMonthSwitch();
			monthElement = self.monthsDropdownContainer;
		}
		var yearInput = createNumberInput("cur-year", { tabindex: "-1" });
		var yearElement = yearInput.getElementsByTagName("input")[0];
		yearElement.setAttribute("aria-label", self.l10n.yearAriaLabel);
		if (self.config.minDate) yearElement.setAttribute("min", self.config.minDate.getFullYear().toString());
		if (self.config.maxDate) {
			yearElement.setAttribute("max", self.config.maxDate.getFullYear().toString());
			yearElement.disabled = !!self.config.minDate && self.config.minDate.getFullYear() === self.config.maxDate.getFullYear();
		}
		var currentMonth = createElement("div", "flatpickr-current-month");
		currentMonth.appendChild(monthElement);
		currentMonth.appendChild(yearInput);
		monthNavFragment.appendChild(currentMonth);
		container.appendChild(monthNavFragment);
		return {
			container,
			yearElement,
			monthElement
		};
	}
	function buildMonths() {
		clearNode(self.monthNav);
		self.monthNav.appendChild(self.prevMonthNav);
		if (self.config.showMonths) {
			self.yearElements = [];
			self.monthElements = [];
		}
		for (var m = self.config.showMonths; m--;) {
			var month = buildMonth();
			self.yearElements.push(month.yearElement);
			self.monthElements.push(month.monthElement);
			self.monthNav.appendChild(month.container);
		}
		self.monthNav.appendChild(self.nextMonthNav);
	}
	function buildMonthNav() {
		self.monthNav = createElement("div", "flatpickr-months");
		self.yearElements = [];
		self.monthElements = [];
		self.prevMonthNav = createElement("span", "flatpickr-prev-month");
		self.prevMonthNav.innerHTML = self.config.prevArrow;
		self.nextMonthNav = createElement("span", "flatpickr-next-month");
		self.nextMonthNav.innerHTML = self.config.nextArrow;
		buildMonths();
		Object.defineProperty(self, "_hidePrevMonthArrow", {
			get: function() {
				return self.__hidePrevMonthArrow;
			},
			set: function(bool) {
				if (self.__hidePrevMonthArrow !== bool) {
					toggleClass(self.prevMonthNav, "flatpickr-disabled", bool);
					self.__hidePrevMonthArrow = bool;
				}
			}
		});
		Object.defineProperty(self, "_hideNextMonthArrow", {
			get: function() {
				return self.__hideNextMonthArrow;
			},
			set: function(bool) {
				if (self.__hideNextMonthArrow !== bool) {
					toggleClass(self.nextMonthNav, "flatpickr-disabled", bool);
					self.__hideNextMonthArrow = bool;
				}
			}
		});
		self.currentYearElement = self.yearElements[0];
		updateNavigationCurrentMonth();
		return self.monthNav;
	}
	function buildTime() {
		self.calendarContainer.classList.add("hasTime");
		if (self.config.noCalendar) self.calendarContainer.classList.add("noCalendar");
		var defaults = getDefaultHours(self.config);
		self.timeContainer = createElement("div", "flatpickr-time");
		self.timeContainer.tabIndex = -1;
		var separator = createElement("span", "flatpickr-time-separator", ":");
		var hourInput = createNumberInput("flatpickr-hour", { "aria-label": self.l10n.hourAriaLabel });
		self.hourElement = hourInput.getElementsByTagName("input")[0];
		var minuteInput = createNumberInput("flatpickr-minute", { "aria-label": self.l10n.minuteAriaLabel });
		self.minuteElement = minuteInput.getElementsByTagName("input")[0];
		self.hourElement.tabIndex = self.minuteElement.tabIndex = -1;
		self.hourElement.value = pad(self.latestSelectedDateObj ? self.latestSelectedDateObj.getHours() : self.config.time_24hr ? defaults.hours : military2ampm(defaults.hours));
		self.minuteElement.value = pad(self.latestSelectedDateObj ? self.latestSelectedDateObj.getMinutes() : defaults.minutes);
		self.hourElement.setAttribute("step", self.config.hourIncrement.toString());
		self.minuteElement.setAttribute("step", self.config.minuteIncrement.toString());
		self.hourElement.setAttribute("min", self.config.time_24hr ? "0" : "1");
		self.hourElement.setAttribute("max", self.config.time_24hr ? "23" : "12");
		self.hourElement.setAttribute("maxlength", "2");
		self.minuteElement.setAttribute("min", "0");
		self.minuteElement.setAttribute("max", "59");
		self.minuteElement.setAttribute("maxlength", "2");
		self.timeContainer.appendChild(hourInput);
		self.timeContainer.appendChild(separator);
		self.timeContainer.appendChild(minuteInput);
		if (self.config.time_24hr) self.timeContainer.classList.add("time24hr");
		if (self.config.enableSeconds) {
			self.timeContainer.classList.add("hasSeconds");
			var secondInput = createNumberInput("flatpickr-second");
			self.secondElement = secondInput.getElementsByTagName("input")[0];
			self.secondElement.value = pad(self.latestSelectedDateObj ? self.latestSelectedDateObj.getSeconds() : defaults.seconds);
			self.secondElement.setAttribute("step", self.minuteElement.getAttribute("step"));
			self.secondElement.setAttribute("min", "0");
			self.secondElement.setAttribute("max", "59");
			self.secondElement.setAttribute("maxlength", "2");
			self.timeContainer.appendChild(createElement("span", "flatpickr-time-separator", ":"));
			self.timeContainer.appendChild(secondInput);
		}
		if (!self.config.time_24hr) {
			self.amPM = createElement("span", "flatpickr-am-pm", self.l10n.amPM[int((self.latestSelectedDateObj ? self.hourElement.value : self.config.defaultHour) > 11)]);
			self.amPM.title = self.l10n.toggleTitle;
			self.amPM.tabIndex = -1;
			self.timeContainer.appendChild(self.amPM);
		}
		return self.timeContainer;
	}
	function buildWeekdays() {
		if (!self.weekdayContainer) self.weekdayContainer = createElement("div", "flatpickr-weekdays");
		else clearNode(self.weekdayContainer);
		for (var i = self.config.showMonths; i--;) {
			var container = createElement("div", "flatpickr-weekdaycontainer");
			self.weekdayContainer.appendChild(container);
		}
		updateWeekdays();
		return self.weekdayContainer;
	}
	function updateWeekdays() {
		if (!self.weekdayContainer) return;
		var firstDayOfWeek = self.l10n.firstDayOfWeek;
		var weekdays = __spreadArrays(self.l10n.weekdays.shorthand);
		if (firstDayOfWeek > 0 && firstDayOfWeek < weekdays.length) weekdays = __spreadArrays(weekdays.splice(firstDayOfWeek, weekdays.length), weekdays.splice(0, firstDayOfWeek));
		for (var i = self.config.showMonths; i--;) self.weekdayContainer.children[i].innerHTML = "\n      <span class='flatpickr-weekday'>\n        " + weekdays.join("</span><span class='flatpickr-weekday'>") + "\n      </span>\n      ";
	}
	function buildWeeks() {
		self.calendarContainer.classList.add("hasWeeks");
		var weekWrapper = createElement("div", "flatpickr-weekwrapper");
		weekWrapper.appendChild(createElement("span", "flatpickr-weekday", self.l10n.weekAbbreviation));
		var weekNumbers = createElement("div", "flatpickr-weeks");
		weekWrapper.appendChild(weekNumbers);
		return {
			weekWrapper,
			weekNumbers
		};
	}
	function changeMonth(value, isOffset) {
		if (isOffset === void 0) isOffset = true;
		var delta = isOffset ? value : value - self.currentMonth;
		if (delta < 0 && self._hidePrevMonthArrow === true || delta > 0 && self._hideNextMonthArrow === true) return;
		self.currentMonth += delta;
		if (self.currentMonth < 0 || self.currentMonth > 11) {
			self.currentYear += self.currentMonth > 11 ? 1 : -1;
			self.currentMonth = (self.currentMonth + 12) % 12;
			triggerEvent("onYearChange");
			buildMonthSwitch();
		}
		buildDays();
		triggerEvent("onMonthChange");
		updateNavigationCurrentMonth();
	}
	function clear(triggerChangeEvent, toInitial) {
		if (triggerChangeEvent === void 0) triggerChangeEvent = true;
		if (toInitial === void 0) toInitial = true;
		self.input.value = "";
		if (self.altInput !== void 0) self.altInput.value = "";
		if (self.mobileInput !== void 0) self.mobileInput.value = "";
		self.selectedDates = [];
		self.latestSelectedDateObj = void 0;
		if (toInitial === true) {
			self.currentYear = self._initialDate.getFullYear();
			self.currentMonth = self._initialDate.getMonth();
		}
		if (self.config.enableTime === true) {
			var _a = getDefaultHours(self.config), hours = _a.hours, minutes = _a.minutes, seconds = _a.seconds;
			setHours(hours, minutes, seconds);
		}
		self.redraw();
		if (triggerChangeEvent) triggerEvent("onChange");
	}
	function close() {
		self.isOpen = false;
		if (!self.isMobile) {
			if (self.calendarContainer !== void 0) self.calendarContainer.classList.remove("open");
			if (self._input !== void 0) self._input.classList.remove("active");
		}
		triggerEvent("onClose");
	}
	function destroy() {
		if (self.config !== void 0) triggerEvent("onDestroy");
		for (var i = self._handlers.length; i--;) self._handlers[i].remove();
		self._handlers = [];
		if (self.mobileInput) {
			if (self.mobileInput.parentNode) self.mobileInput.parentNode.removeChild(self.mobileInput);
			self.mobileInput = void 0;
		} else if (self.calendarContainer && self.calendarContainer.parentNode) if (self.config.static && self.calendarContainer.parentNode) {
			var wrapper = self.calendarContainer.parentNode;
			wrapper.lastChild && wrapper.removeChild(wrapper.lastChild);
			if (wrapper.parentNode) {
				while (wrapper.firstChild) wrapper.parentNode.insertBefore(wrapper.firstChild, wrapper);
				wrapper.parentNode.removeChild(wrapper);
			}
		} else self.calendarContainer.parentNode.removeChild(self.calendarContainer);
		if (self.altInput) {
			self.input.type = "text";
			if (self.altInput.parentNode) self.altInput.parentNode.removeChild(self.altInput);
			delete self.altInput;
		}
		if (self.input) {
			self.input.type = self.input._type;
			self.input.classList.remove("flatpickr-input");
			self.input.removeAttribute("readonly");
		}
		[
			"_showTimeInput",
			"latestSelectedDateObj",
			"_hideNextMonthArrow",
			"_hidePrevMonthArrow",
			"__hideNextMonthArrow",
			"__hidePrevMonthArrow",
			"isMobile",
			"isOpen",
			"selectedDateElem",
			"minDateHasTime",
			"maxDateHasTime",
			"days",
			"daysContainer",
			"_input",
			"_positionElement",
			"innerContainer",
			"rContainer",
			"monthNav",
			"todayDateElem",
			"calendarContainer",
			"weekdayContainer",
			"prevMonthNav",
			"nextMonthNav",
			"monthsDropdownContainer",
			"currentMonthElement",
			"currentYearElement",
			"navigationCurrentMonth",
			"selectedDateElem",
			"config"
		].forEach(function(k) {
			try {
				delete self[k];
			} catch (_) {}
		});
	}
	function isCalendarElem(elem) {
		return self.calendarContainer.contains(elem);
	}
	function documentClick(e) {
		if (self.isOpen && !self.config.inline) {
			var eventTarget_1 = getEventTarget(e);
			var isCalendarElement = isCalendarElem(eventTarget_1);
			var lostFocus = !(eventTarget_1 === self.input || eventTarget_1 === self.altInput || self.element.contains(eventTarget_1) || e.path && e.path.indexOf && (~e.path.indexOf(self.input) || ~e.path.indexOf(self.altInput))) && !isCalendarElement && !isCalendarElem(e.relatedTarget);
			var isIgnored = !self.config.ignoredFocusElements.some(function(elem) {
				return elem.contains(eventTarget_1);
			});
			if (lostFocus && isIgnored) {
				if (self.config.allowInput) self.setDate(self._input.value, false, self.config.altInput ? self.config.altFormat : self.config.dateFormat);
				if (self.timeContainer !== void 0 && self.minuteElement !== void 0 && self.hourElement !== void 0 && self.input.value !== "" && self.input.value !== void 0) updateTime();
				self.close();
				if (self.config && self.config.mode === "range" && self.selectedDates.length === 1) self.clear(false);
			}
		}
	}
	function changeYear(newYear) {
		if (!newYear || self.config.minDate && newYear < self.config.minDate.getFullYear() || self.config.maxDate && newYear > self.config.maxDate.getFullYear()) return;
		var newYearNum = newYear, isNewYear = self.currentYear !== newYearNum;
		self.currentYear = newYearNum || self.currentYear;
		if (self.config.maxDate && self.currentYear === self.config.maxDate.getFullYear()) self.currentMonth = Math.min(self.config.maxDate.getMonth(), self.currentMonth);
		else if (self.config.minDate && self.currentYear === self.config.minDate.getFullYear()) self.currentMonth = Math.max(self.config.minDate.getMonth(), self.currentMonth);
		if (isNewYear) {
			self.redraw();
			triggerEvent("onYearChange");
			buildMonthSwitch();
		}
	}
	function isEnabled(date, timeless) {
		var _a;
		if (timeless === void 0) timeless = true;
		var dateToCheck = self.parseDate(date, void 0, timeless);
		if (self.config.minDate && dateToCheck && compareDates(dateToCheck, self.config.minDate, timeless !== void 0 ? timeless : !self.minDateHasTime) < 0 || self.config.maxDate && dateToCheck && compareDates(dateToCheck, self.config.maxDate, timeless !== void 0 ? timeless : !self.maxDateHasTime) > 0) return false;
		if (!self.config.enable && self.config.disable.length === 0) return true;
		if (dateToCheck === void 0) return false;
		var bool = !!self.config.enable, array = (_a = self.config.enable) !== null && _a !== void 0 ? _a : self.config.disable;
		for (var i = 0, d = void 0; i < array.length; i++) {
			d = array[i];
			if (typeof d === "function" && d(dateToCheck)) return bool;
			else if (d instanceof Date && dateToCheck !== void 0 && d.getTime() === dateToCheck.getTime()) return bool;
			else if (typeof d === "string") {
				var parsed = self.parseDate(d, void 0, true);
				return parsed && parsed.getTime() === dateToCheck.getTime() ? bool : !bool;
			} else if (typeof d === "object" && dateToCheck !== void 0 && d.from && d.to && dateToCheck.getTime() >= d.from.getTime() && dateToCheck.getTime() <= d.to.getTime()) return bool;
		}
		return !bool;
	}
	function isInView(elem) {
		if (self.daysContainer !== void 0) return elem.className.indexOf("hidden") === -1 && elem.className.indexOf("flatpickr-disabled") === -1 && self.daysContainer.contains(elem);
		return false;
	}
	function onBlur(e) {
		var isInput = e.target === self._input;
		var valueChanged = self._input.value.trimEnd() !== getDateStr();
		if (isInput && valueChanged && !(e.relatedTarget && isCalendarElem(e.relatedTarget))) self.setDate(self._input.value, true, e.target === self.altInput ? self.config.altFormat : self.config.dateFormat);
	}
	function onKeyDown(e) {
		var eventTarget = getEventTarget(e);
		var isInput = self.config.wrap ? element.contains(eventTarget) : eventTarget === self._input;
		var allowInput = self.config.allowInput;
		var allowKeydown = self.isOpen && (!allowInput || !isInput);
		var allowInlineKeydown = self.config.inline && isInput && !allowInput;
		if (e.keyCode === 13 && isInput) if (allowInput) {
			self.setDate(self._input.value, true, eventTarget === self.altInput ? self.config.altFormat : self.config.dateFormat);
			self.close();
			return eventTarget.blur();
		} else self.open();
		else if (isCalendarElem(eventTarget) || allowKeydown || allowInlineKeydown) {
			var isTimeObj = !!self.timeContainer && self.timeContainer.contains(eventTarget);
			switch (e.keyCode) {
				case 13:
					if (isTimeObj) {
						e.preventDefault();
						updateTime();
						focusAndClose();
					} else selectDate(e);
					break;
				case 27:
					e.preventDefault();
					focusAndClose();
					break;
				case 8:
				case 46:
					if (isInput && !self.config.allowInput) {
						e.preventDefault();
						self.clear();
					}
					break;
				case 37:
				case 39:
					if (!isTimeObj && !isInput) {
						e.preventDefault();
						var activeElement = getClosestActiveElement();
						if (self.daysContainer !== void 0 && (allowInput === false || activeElement && isInView(activeElement))) {
							var delta_1 = e.keyCode === 39 ? 1 : -1;
							if (!e.ctrlKey) focusOnDay(void 0, delta_1);
							else {
								e.stopPropagation();
								changeMonth(delta_1);
								focusOnDay(getFirstAvailableDay(1), 0);
							}
						}
					} else if (self.hourElement) self.hourElement.focus();
					break;
				case 38:
				case 40:
					e.preventDefault();
					var delta = e.keyCode === 40 ? 1 : -1;
					if (self.daysContainer && eventTarget.$i !== void 0 || eventTarget === self.input || eventTarget === self.altInput) {
						if (e.ctrlKey) {
							e.stopPropagation();
							changeYear(self.currentYear - delta);
							focusOnDay(getFirstAvailableDay(1), 0);
						} else if (!isTimeObj) focusOnDay(void 0, delta * 7);
					} else if (eventTarget === self.currentYearElement) changeYear(self.currentYear - delta);
					else if (self.config.enableTime) {
						if (!isTimeObj && self.hourElement) self.hourElement.focus();
						updateTime(e);
						self._debouncedChange();
					}
					break;
				case 9:
					if (isTimeObj) {
						var elems = [
							self.hourElement,
							self.minuteElement,
							self.secondElement,
							self.amPM
						].concat(self.pluginElements).filter(function(x) {
							return x;
						});
						var i = elems.indexOf(eventTarget);
						if (i !== -1) {
							var target = elems[i + (e.shiftKey ? -1 : 1)];
							e.preventDefault();
							(target || self._input).focus();
						}
					} else if (!self.config.noCalendar && self.daysContainer && self.daysContainer.contains(eventTarget) && e.shiftKey) {
						e.preventDefault();
						self._input.focus();
					}
					break;
				default: break;
			}
		}
		if (self.amPM !== void 0 && eventTarget === self.amPM) switch (e.key) {
			case self.l10n.amPM[0].charAt(0):
			case self.l10n.amPM[0].charAt(0).toLowerCase():
				self.amPM.textContent = self.l10n.amPM[0];
				setHoursFromInputs();
				updateValue();
				break;
			case self.l10n.amPM[1].charAt(0):
			case self.l10n.amPM[1].charAt(0).toLowerCase():
				self.amPM.textContent = self.l10n.amPM[1];
				setHoursFromInputs();
				updateValue();
				break;
		}
		if (isInput || isCalendarElem(eventTarget)) triggerEvent("onKeyDown", e);
	}
	function onMouseOver(elem, cellClass) {
		if (cellClass === void 0) cellClass = "flatpickr-day";
		if (self.selectedDates.length !== 1 || elem && (!elem.classList.contains(cellClass) || elem.classList.contains("flatpickr-disabled"))) return;
		var hoverDate = elem ? elem.dateObj.getTime() : self.days.firstElementChild.dateObj.getTime(), initialDate = self.parseDate(self.selectedDates[0], void 0, true).getTime(), rangeStartDate = Math.min(hoverDate, self.selectedDates[0].getTime()), rangeEndDate = Math.max(hoverDate, self.selectedDates[0].getTime());
		var containsDisabled = false;
		var minRange = 0, maxRange = 0;
		for (var t = rangeStartDate; t < rangeEndDate; t += duration.DAY) if (!isEnabled(new Date(t), true)) {
			containsDisabled = containsDisabled || t > rangeStartDate && t < rangeEndDate;
			if (t < initialDate && (!minRange || t > minRange)) minRange = t;
			else if (t > initialDate && (!maxRange || t < maxRange)) maxRange = t;
		}
		Array.from(self.rContainer.querySelectorAll("*:nth-child(-n+" + self.config.showMonths + ") > ." + cellClass)).forEach(function(dayElem) {
			var timestamp = dayElem.dateObj.getTime();
			var outOfRange = minRange > 0 && timestamp < minRange || maxRange > 0 && timestamp > maxRange;
			if (outOfRange) {
				dayElem.classList.add("notAllowed");
				[
					"inRange",
					"startRange",
					"endRange"
				].forEach(function(c) {
					dayElem.classList.remove(c);
				});
				return;
			} else if (containsDisabled && !outOfRange) return;
			[
				"startRange",
				"inRange",
				"endRange",
				"notAllowed"
			].forEach(function(c) {
				dayElem.classList.remove(c);
			});
			if (elem !== void 0) {
				elem.classList.add(hoverDate <= self.selectedDates[0].getTime() ? "startRange" : "endRange");
				if (initialDate < hoverDate && timestamp === initialDate) dayElem.classList.add("startRange");
				else if (initialDate > hoverDate && timestamp === initialDate) dayElem.classList.add("endRange");
				if (timestamp >= minRange && (maxRange === 0 || timestamp <= maxRange) && isBetween(timestamp, initialDate, hoverDate)) dayElem.classList.add("inRange");
			}
		});
	}
	function onResize() {
		if (self.isOpen && !self.config.static && !self.config.inline) positionCalendar();
	}
	function open(e, positionElement) {
		if (positionElement === void 0) positionElement = self._positionElement;
		if (self.isMobile === true) {
			if (e) {
				e.preventDefault();
				var eventTarget = getEventTarget(e);
				if (eventTarget) eventTarget.blur();
			}
			if (self.mobileInput !== void 0) {
				self.mobileInput.focus();
				self.mobileInput.click();
			}
			triggerEvent("onOpen");
			return;
		} else if (self._input.disabled || self.config.inline) return;
		var wasOpen = self.isOpen;
		self.isOpen = true;
		if (!wasOpen) {
			self.calendarContainer.classList.add("open");
			self._input.classList.add("active");
			triggerEvent("onOpen");
			positionCalendar(positionElement);
		}
		if (self.config.enableTime === true && self.config.noCalendar === true) {
			if (self.config.allowInput === false && (e === void 0 || !self.timeContainer.contains(e.relatedTarget))) setTimeout(function() {
				return self.hourElement.select();
			}, 50);
		}
	}
	function minMaxDateSetter(type) {
		return function(date) {
			var dateObj = self.config["_" + type + "Date"] = self.parseDate(date, self.config.dateFormat);
			var inverseDateObj = self.config["_" + (type === "min" ? "max" : "min") + "Date"];
			if (dateObj !== void 0) self[type === "min" ? "minDateHasTime" : "maxDateHasTime"] = dateObj.getHours() > 0 || dateObj.getMinutes() > 0 || dateObj.getSeconds() > 0;
			if (self.selectedDates) {
				self.selectedDates = self.selectedDates.filter(function(d) {
					return isEnabled(d);
				});
				if (!self.selectedDates.length && type === "min") setHoursFromDate(dateObj);
				updateValue();
			}
			if (self.daysContainer) {
				redraw();
				if (dateObj !== void 0) self.currentYearElement[type] = dateObj.getFullYear().toString();
				else self.currentYearElement.removeAttribute(type);
				self.currentYearElement.disabled = !!inverseDateObj && dateObj !== void 0 && inverseDateObj.getFullYear() === dateObj.getFullYear();
			}
		};
	}
	function parseConfig() {
		var boolOpts = [
			"wrap",
			"weekNumbers",
			"allowInput",
			"allowInvalidPreload",
			"clickOpens",
			"time_24hr",
			"enableTime",
			"noCalendar",
			"altInput",
			"shorthandCurrentMonth",
			"inline",
			"static",
			"enableSeconds",
			"disableMobile"
		];
		var userConfig = __assign(__assign({}, JSON.parse(JSON.stringify(element.dataset || {}))), instanceConfig);
		var formats = {};
		self.config.parseDate = userConfig.parseDate;
		self.config.formatDate = userConfig.formatDate;
		Object.defineProperty(self.config, "enable", {
			get: function() {
				return self.config._enable;
			},
			set: function(dates) {
				self.config._enable = parseDateRules(dates);
			}
		});
		Object.defineProperty(self.config, "disable", {
			get: function() {
				return self.config._disable;
			},
			set: function(dates) {
				self.config._disable = parseDateRules(dates);
			}
		});
		var timeMode = userConfig.mode === "time";
		if (!userConfig.dateFormat && (userConfig.enableTime || timeMode)) {
			var defaultDateFormat = flatpickr.defaultConfig.dateFormat || defaults.dateFormat;
			formats.dateFormat = userConfig.noCalendar || timeMode ? "H:i" + (userConfig.enableSeconds ? ":S" : "") : defaultDateFormat + " H:i" + (userConfig.enableSeconds ? ":S" : "");
		}
		if (userConfig.altInput && (userConfig.enableTime || timeMode) && !userConfig.altFormat) {
			var defaultAltFormat = flatpickr.defaultConfig.altFormat || defaults.altFormat;
			formats.altFormat = userConfig.noCalendar || timeMode ? "h:i" + (userConfig.enableSeconds ? ":S K" : " K") : defaultAltFormat + (" h:i" + (userConfig.enableSeconds ? ":S" : "") + " K");
		}
		Object.defineProperty(self.config, "minDate", {
			get: function() {
				return self.config._minDate;
			},
			set: minMaxDateSetter("min")
		});
		Object.defineProperty(self.config, "maxDate", {
			get: function() {
				return self.config._maxDate;
			},
			set: minMaxDateSetter("max")
		});
		var minMaxTimeSetter = function(type) {
			return function(val) {
				self.config[type === "min" ? "_minTime" : "_maxTime"] = self.parseDate(val, "H:i:S");
			};
		};
		Object.defineProperty(self.config, "minTime", {
			get: function() {
				return self.config._minTime;
			},
			set: minMaxTimeSetter("min")
		});
		Object.defineProperty(self.config, "maxTime", {
			get: function() {
				return self.config._maxTime;
			},
			set: minMaxTimeSetter("max")
		});
		if (userConfig.mode === "time") {
			self.config.noCalendar = true;
			self.config.enableTime = true;
		}
		Object.assign(self.config, formats, userConfig);
		for (var i = 0; i < boolOpts.length; i++) self.config[boolOpts[i]] = self.config[boolOpts[i]] === true || self.config[boolOpts[i]] === "true";
		HOOKS.filter(function(hook) {
			return self.config[hook] !== void 0;
		}).forEach(function(hook) {
			self.config[hook] = arrayify(self.config[hook] || []).map(bindToInstance);
		});
		self.isMobile = !self.config.disableMobile && !self.config.inline && self.config.mode === "single" && !self.config.disable.length && !self.config.enable && !self.config.weekNumbers && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		for (var i = 0; i < self.config.plugins.length; i++) {
			var pluginConf = self.config.plugins[i](self) || {};
			for (var key in pluginConf) if (HOOKS.indexOf(key) > -1) self.config[key] = arrayify(pluginConf[key]).map(bindToInstance).concat(self.config[key]);
			else if (typeof userConfig[key] === "undefined") self.config[key] = pluginConf[key];
		}
		if (!userConfig.altInputClass) self.config.altInputClass = getInputElem().className + " " + self.config.altInputClass;
		triggerEvent("onParseConfig");
	}
	function getInputElem() {
		return self.config.wrap ? element.querySelector("[data-input]") : element;
	}
	function setupLocale() {
		if (typeof self.config.locale !== "object" && typeof flatpickr.l10ns[self.config.locale] === "undefined") self.config.errorHandler(/* @__PURE__ */ new Error("flatpickr: invalid locale " + self.config.locale));
		self.l10n = __assign(__assign({}, flatpickr.l10ns.default), typeof self.config.locale === "object" ? self.config.locale : self.config.locale !== "default" ? flatpickr.l10ns[self.config.locale] : void 0);
		tokenRegex.D = "(" + self.l10n.weekdays.shorthand.join("|") + ")";
		tokenRegex.l = "(" + self.l10n.weekdays.longhand.join("|") + ")";
		tokenRegex.M = "(" + self.l10n.months.shorthand.join("|") + ")";
		tokenRegex.F = "(" + self.l10n.months.longhand.join("|") + ")";
		tokenRegex.K = "(" + self.l10n.amPM[0] + "|" + self.l10n.amPM[1] + "|" + self.l10n.amPM[0].toLowerCase() + "|" + self.l10n.amPM[1].toLowerCase() + ")";
		if (__assign(__assign({}, instanceConfig), JSON.parse(JSON.stringify(element.dataset || {}))).time_24hr === void 0 && flatpickr.defaultConfig.time_24hr === void 0) self.config.time_24hr = self.l10n.time_24hr;
		self.formatDate = createDateFormatter(self);
		self.parseDate = createDateParser({
			config: self.config,
			l10n: self.l10n
		});
	}
	function positionCalendar(customPositionElement) {
		if (typeof self.config.position === "function") {
			self.config.position(self, customPositionElement);
			return;
		}
		if (self.calendarContainer === void 0) return;
		triggerEvent("onPreCalendarPosition");
		var positionElement = customPositionElement || self._positionElement;
		var calendarHeight = Array.prototype.reduce.call(self.calendarContainer.children, (function(acc, child) {
			return acc + child.offsetHeight;
		}), 0), calendarWidth = self.calendarContainer.offsetWidth, configPos = self.config.position.split(" "), configPosVertical = configPos[0], configPosHorizontal = configPos.length > 1 ? configPos[1] : null, inputBounds = positionElement.getBoundingClientRect(), distanceFromBottom = window.innerHeight - inputBounds.bottom, showOnTop = configPosVertical === "above" || configPosVertical !== "below" && distanceFromBottom < calendarHeight && inputBounds.top > calendarHeight;
		var top = window.pageYOffset + inputBounds.top + (!showOnTop ? positionElement.offsetHeight + 2 : -calendarHeight - 2);
		toggleClass(self.calendarContainer, "arrowTop", !showOnTop);
		toggleClass(self.calendarContainer, "arrowBottom", showOnTop);
		if (self.config.inline) return;
		var left = window.pageXOffset + inputBounds.left;
		var isCenter = false;
		var isRight = false;
		if (configPosHorizontal === "center") {
			left -= (calendarWidth - inputBounds.width) / 2;
			isCenter = true;
		} else if (configPosHorizontal === "right") {
			left -= calendarWidth - inputBounds.width;
			isRight = true;
		}
		toggleClass(self.calendarContainer, "arrowLeft", !isCenter && !isRight);
		toggleClass(self.calendarContainer, "arrowCenter", isCenter);
		toggleClass(self.calendarContainer, "arrowRight", isRight);
		var right = window.document.body.offsetWidth - (window.pageXOffset + inputBounds.right);
		var rightMost = left + calendarWidth > window.document.body.offsetWidth;
		var centerMost = right + calendarWidth > window.document.body.offsetWidth;
		toggleClass(self.calendarContainer, "rightMost", rightMost);
		if (self.config.static) return;
		self.calendarContainer.style.top = top + "px";
		if (!rightMost) {
			self.calendarContainer.style.left = left + "px";
			self.calendarContainer.style.right = "auto";
		} else if (!centerMost) {
			self.calendarContainer.style.left = "auto";
			self.calendarContainer.style.right = right + "px";
		} else {
			var doc = getDocumentStyleSheet();
			if (doc === void 0) return;
			var bodyWidth = window.document.body.offsetWidth;
			var centerLeft = Math.max(0, bodyWidth / 2 - calendarWidth / 2);
			var centerBefore = ".flatpickr-calendar.centerMost:before";
			var centerAfter = ".flatpickr-calendar.centerMost:after";
			var centerIndex = doc.cssRules.length;
			var centerStyle = "{left:" + inputBounds.left + "px;right:auto;}";
			toggleClass(self.calendarContainer, "rightMost", false);
			toggleClass(self.calendarContainer, "centerMost", true);
			doc.insertRule(centerBefore + "," + centerAfter + centerStyle, centerIndex);
			self.calendarContainer.style.left = centerLeft + "px";
			self.calendarContainer.style.right = "auto";
		}
	}
	function getDocumentStyleSheet() {
		var editableSheet = null;
		for (var i = 0; i < document.styleSheets.length; i++) {
			var sheet = document.styleSheets[i];
			if (!sheet.cssRules) continue;
			try {
				sheet.cssRules;
			} catch (err) {
				continue;
			}
			editableSheet = sheet;
			break;
		}
		return editableSheet != null ? editableSheet : createStyleSheet();
	}
	function createStyleSheet() {
		var style = document.createElement("style");
		document.head.appendChild(style);
		return style.sheet;
	}
	function redraw() {
		if (self.config.noCalendar || self.isMobile) return;
		buildMonthSwitch();
		updateNavigationCurrentMonth();
		buildDays();
	}
	function focusAndClose() {
		self._input.focus();
		if (window.navigator.userAgent.indexOf("MSIE") !== -1 || navigator.msMaxTouchPoints !== void 0) setTimeout(self.close, 0);
		else self.close();
	}
	function selectDate(e) {
		e.preventDefault();
		e.stopPropagation();
		var isSelectable = function(day) {
			return day.classList && day.classList.contains("flatpickr-day") && !day.classList.contains("flatpickr-disabled") && !day.classList.contains("notAllowed");
		};
		var t = findParent(getEventTarget(e), isSelectable);
		if (t === void 0) return;
		var target = t;
		var selectedDate = self.latestSelectedDateObj = new Date(target.dateObj.getTime());
		var shouldChangeMonth = (selectedDate.getMonth() < self.currentMonth || selectedDate.getMonth() > self.currentMonth + self.config.showMonths - 1) && self.config.mode !== "range";
		self.selectedDateElem = target;
		if (self.config.mode === "single") self.selectedDates = [selectedDate];
		else if (self.config.mode === "multiple") {
			var selectedIndex = isDateSelected(selectedDate);
			if (selectedIndex) self.selectedDates.splice(parseInt(selectedIndex), 1);
			else self.selectedDates.push(selectedDate);
		} else if (self.config.mode === "range") {
			if (self.selectedDates.length === 2) self.clear(false, false);
			self.latestSelectedDateObj = selectedDate;
			self.selectedDates.push(selectedDate);
			if (compareDates(selectedDate, self.selectedDates[0], true) !== 0) self.selectedDates.sort(function(a, b) {
				return a.getTime() - b.getTime();
			});
		}
		setHoursFromInputs();
		if (shouldChangeMonth) {
			var isNewYear = self.currentYear !== selectedDate.getFullYear();
			self.currentYear = selectedDate.getFullYear();
			self.currentMonth = selectedDate.getMonth();
			if (isNewYear) {
				triggerEvent("onYearChange");
				buildMonthSwitch();
			}
			triggerEvent("onMonthChange");
		}
		updateNavigationCurrentMonth();
		buildDays();
		updateValue();
		if (!shouldChangeMonth && self.config.mode !== "range" && self.config.showMonths === 1) focusOnDayElem(target);
		else if (self.selectedDateElem !== void 0 && self.hourElement === void 0) self.selectedDateElem && self.selectedDateElem.focus();
		if (self.hourElement !== void 0) self.hourElement !== void 0 && self.hourElement.focus();
		if (self.config.closeOnSelect) {
			var single = self.config.mode === "single" && !self.config.enableTime;
			var range = self.config.mode === "range" && self.selectedDates.length === 2 && !self.config.enableTime;
			if (single || range) focusAndClose();
		}
		triggerChange();
	}
	var CALLBACKS = {
		locale: [setupLocale, updateWeekdays],
		showMonths: [
			buildMonths,
			setCalendarWidth,
			buildWeekdays
		],
		minDate: [jumpToDate],
		maxDate: [jumpToDate],
		positionElement: [updatePositionElement],
		clickOpens: [function() {
			if (self.config.clickOpens === true) {
				bind(self._input, "focus", self.open);
				bind(self._input, "click", self.open);
			} else {
				self._input.removeEventListener("focus", self.open);
				self._input.removeEventListener("click", self.open);
			}
		}]
	};
	function set(option, value) {
		if (option !== null && typeof option === "object") {
			Object.assign(self.config, option);
			for (var key in option) if (CALLBACKS[key] !== void 0) CALLBACKS[key].forEach(function(x) {
				return x();
			});
		} else {
			self.config[option] = value;
			if (CALLBACKS[option] !== void 0) CALLBACKS[option].forEach(function(x) {
				return x();
			});
			else if (HOOKS.indexOf(option) > -1) self.config[option] = arrayify(value);
		}
		self.redraw();
		updateValue(true);
	}
	function setSelectedDate(inputDate, format) {
		var dates = [];
		if (inputDate instanceof Array) dates = inputDate.map(function(d) {
			return self.parseDate(d, format);
		});
		else if (inputDate instanceof Date || typeof inputDate === "number") dates = [self.parseDate(inputDate, format)];
		else if (typeof inputDate === "string") switch (self.config.mode) {
			case "single":
			case "time":
				dates = [self.parseDate(inputDate, format)];
				break;
			case "multiple":
				dates = inputDate.split(self.config.conjunction).map(function(date) {
					return self.parseDate(date, format);
				});
				break;
			case "range":
				dates = inputDate.split(self.l10n.rangeSeparator).map(function(date) {
					return self.parseDate(date, format);
				});
				break;
			default: break;
		}
		else self.config.errorHandler(/* @__PURE__ */ new Error("Invalid date supplied: " + JSON.stringify(inputDate)));
		self.selectedDates = self.config.allowInvalidPreload ? dates : dates.filter(function(d) {
			return d instanceof Date && isEnabled(d, false);
		});
		if (self.config.mode === "range") self.selectedDates.sort(function(a, b) {
			return a.getTime() - b.getTime();
		});
	}
	function setDate(date, triggerChange, format) {
		if (triggerChange === void 0) triggerChange = false;
		if (format === void 0) format = self.config.dateFormat;
		if (date !== 0 && !date || date instanceof Array && date.length === 0) return self.clear(triggerChange);
		setSelectedDate(date, format);
		self.latestSelectedDateObj = self.selectedDates[self.selectedDates.length - 1];
		self.redraw();
		jumpToDate(void 0, triggerChange);
		setHoursFromDate();
		if (self.selectedDates.length === 0) self.clear(false);
		updateValue(triggerChange);
		if (triggerChange) triggerEvent("onChange");
	}
	function parseDateRules(arr) {
		return arr.slice().map(function(rule) {
			if (typeof rule === "string" || typeof rule === "number" || rule instanceof Date) return self.parseDate(rule, void 0, true);
			else if (rule && typeof rule === "object" && rule.from && rule.to) return {
				from: self.parseDate(rule.from, void 0),
				to: self.parseDate(rule.to, void 0)
			};
			return rule;
		}).filter(function(x) {
			return x;
		});
	}
	function setupDates() {
		self.selectedDates = [];
		self.now = self.parseDate(self.config.now) || /* @__PURE__ */ new Date();
		var preloadedDate = self.config.defaultDate || ((self.input.nodeName === "INPUT" || self.input.nodeName === "TEXTAREA") && self.input.placeholder && self.input.value === self.input.placeholder ? null : self.input.value);
		if (preloadedDate) setSelectedDate(preloadedDate, self.config.dateFormat);
		self._initialDate = self.selectedDates.length > 0 ? self.selectedDates[0] : self.config.minDate && self.config.minDate.getTime() > self.now.getTime() ? self.config.minDate : self.config.maxDate && self.config.maxDate.getTime() < self.now.getTime() ? self.config.maxDate : self.now;
		self.currentYear = self._initialDate.getFullYear();
		self.currentMonth = self._initialDate.getMonth();
		if (self.selectedDates.length > 0) self.latestSelectedDateObj = self.selectedDates[0];
		if (self.config.minTime !== void 0) self.config.minTime = self.parseDate(self.config.minTime, "H:i");
		if (self.config.maxTime !== void 0) self.config.maxTime = self.parseDate(self.config.maxTime, "H:i");
		self.minDateHasTime = !!self.config.minDate && (self.config.minDate.getHours() > 0 || self.config.minDate.getMinutes() > 0 || self.config.minDate.getSeconds() > 0);
		self.maxDateHasTime = !!self.config.maxDate && (self.config.maxDate.getHours() > 0 || self.config.maxDate.getMinutes() > 0 || self.config.maxDate.getSeconds() > 0);
	}
	function setupInputs() {
		self.input = getInputElem();
		if (!self.input) {
			self.config.errorHandler(/* @__PURE__ */ new Error("Invalid input element specified"));
			return;
		}
		self.input._type = self.input.type;
		self.input.type = "text";
		self.input.classList.add("flatpickr-input");
		self._input = self.input;
		if (self.config.altInput) {
			self.altInput = createElement(self.input.nodeName, self.config.altInputClass);
			self._input = self.altInput;
			self.altInput.placeholder = self.input.placeholder;
			self.altInput.disabled = self.input.disabled;
			self.altInput.required = self.input.required;
			self.altInput.tabIndex = self.input.tabIndex;
			self.altInput.type = "text";
			self.input.setAttribute("type", "hidden");
			if (!self.config.static && self.input.parentNode) self.input.parentNode.insertBefore(self.altInput, self.input.nextSibling);
		}
		if (!self.config.allowInput) self._input.setAttribute("readonly", "readonly");
		updatePositionElement();
	}
	function updatePositionElement() {
		self._positionElement = self.config.positionElement || self._input;
	}
	function setupMobile() {
		var inputType = self.config.enableTime ? self.config.noCalendar ? "time" : "datetime-local" : "date";
		self.mobileInput = createElement("input", self.input.className + " flatpickr-mobile");
		self.mobileInput.tabIndex = 1;
		self.mobileInput.type = inputType;
		self.mobileInput.disabled = self.input.disabled;
		self.mobileInput.required = self.input.required;
		self.mobileInput.placeholder = self.input.placeholder;
		self.mobileFormatStr = inputType === "datetime-local" ? "Y-m-d\\TH:i:S" : inputType === "date" ? "Y-m-d" : "H:i:S";
		if (self.selectedDates.length > 0) self.mobileInput.defaultValue = self.mobileInput.value = self.formatDate(self.selectedDates[0], self.mobileFormatStr);
		if (self.config.minDate) self.mobileInput.min = self.formatDate(self.config.minDate, "Y-m-d");
		if (self.config.maxDate) self.mobileInput.max = self.formatDate(self.config.maxDate, "Y-m-d");
		if (self.input.getAttribute("step")) self.mobileInput.step = String(self.input.getAttribute("step"));
		self.input.type = "hidden";
		if (self.altInput !== void 0) self.altInput.type = "hidden";
		try {
			if (self.input.parentNode) self.input.parentNode.insertBefore(self.mobileInput, self.input.nextSibling);
		} catch (_a) {}
		bind(self.mobileInput, "change", function(e) {
			self.setDate(getEventTarget(e).value, false, self.mobileFormatStr);
			triggerEvent("onChange");
			triggerEvent("onClose");
		});
	}
	function toggle(e) {
		if (self.isOpen === true) return self.close();
		self.open(e);
	}
	function triggerEvent(event, data) {
		if (self.config === void 0) return;
		var hooks = self.config[event];
		if (hooks !== void 0 && hooks.length > 0) for (var i = 0; hooks[i] && i < hooks.length; i++) hooks[i](self.selectedDates, self.input.value, self, data);
		if (event === "onChange") {
			self.input.dispatchEvent(createEvent("change"));
			self.input.dispatchEvent(createEvent("input"));
		}
	}
	function createEvent(name) {
		var e = document.createEvent("Event");
		e.initEvent(name, true, true);
		return e;
	}
	function isDateSelected(date) {
		for (var i = 0; i < self.selectedDates.length; i++) {
			var selectedDate = self.selectedDates[i];
			if (selectedDate instanceof Date && compareDates(selectedDate, date) === 0) return "" + i;
		}
		return false;
	}
	function isDateInRange(date) {
		if (self.config.mode !== "range" || self.selectedDates.length < 2) return false;
		return compareDates(date, self.selectedDates[0]) >= 0 && compareDates(date, self.selectedDates[1]) <= 0;
	}
	function updateNavigationCurrentMonth() {
		if (self.config.noCalendar || self.isMobile || !self.monthNav) return;
		self.yearElements.forEach(function(yearElement, i) {
			var d = new Date(self.currentYear, self.currentMonth, 1);
			d.setMonth(self.currentMonth + i);
			if (self.config.showMonths > 1 || self.config.monthSelectorType === "static") self.monthElements[i].textContent = monthToStr(d.getMonth(), self.config.shorthandCurrentMonth, self.l10n) + " ";
			else self.monthsDropdownContainer.value = d.getMonth().toString();
			yearElement.value = d.getFullYear().toString();
		});
		self._hidePrevMonthArrow = self.config.minDate !== void 0 && (self.currentYear === self.config.minDate.getFullYear() ? self.currentMonth <= self.config.minDate.getMonth() : self.currentYear < self.config.minDate.getFullYear());
		self._hideNextMonthArrow = self.config.maxDate !== void 0 && (self.currentYear === self.config.maxDate.getFullYear() ? self.currentMonth + 1 > self.config.maxDate.getMonth() : self.currentYear > self.config.maxDate.getFullYear());
	}
	function getDateStr(specificFormat) {
		var format = specificFormat || (self.config.altInput ? self.config.altFormat : self.config.dateFormat);
		return self.selectedDates.map(function(dObj) {
			return self.formatDate(dObj, format);
		}).filter(function(d, i, arr) {
			return self.config.mode !== "range" || self.config.enableTime || arr.indexOf(d) === i;
		}).join(self.config.mode !== "range" ? self.config.conjunction : self.l10n.rangeSeparator);
	}
	function updateValue(triggerChange) {
		if (triggerChange === void 0) triggerChange = true;
		if (self.mobileInput !== void 0 && self.mobileFormatStr) self.mobileInput.value = self.latestSelectedDateObj !== void 0 ? self.formatDate(self.latestSelectedDateObj, self.mobileFormatStr) : "";
		self.input.value = getDateStr(self.config.dateFormat);
		if (self.altInput !== void 0) self.altInput.value = getDateStr(self.config.altFormat);
		if (triggerChange !== false) triggerEvent("onValueUpdate");
	}
	function onMonthNavClick(e) {
		var eventTarget = getEventTarget(e);
		var isPrevMonth = self.prevMonthNav.contains(eventTarget);
		var isNextMonth = self.nextMonthNav.contains(eventTarget);
		if (isPrevMonth || isNextMonth) changeMonth(isPrevMonth ? -1 : 1);
		else if (self.yearElements.indexOf(eventTarget) >= 0) eventTarget.select();
		else if (eventTarget.classList.contains("arrowUp")) self.changeYear(self.currentYear + 1);
		else if (eventTarget.classList.contains("arrowDown")) self.changeYear(self.currentYear - 1);
	}
	function timeWrapper(e) {
		e.preventDefault();
		var isKeyDown = e.type === "keydown", eventTarget = getEventTarget(e), input = eventTarget;
		if (self.amPM !== void 0 && eventTarget === self.amPM) self.amPM.textContent = self.l10n.amPM[int(self.amPM.textContent === self.l10n.amPM[0])];
		var min = parseFloat(input.getAttribute("min")), max = parseFloat(input.getAttribute("max")), step = parseFloat(input.getAttribute("step")), curValue = parseInt(input.value, 10);
		var newValue = curValue + step * (e.delta || (isKeyDown ? e.which === 38 ? 1 : -1 : 0));
		if (typeof input.value !== "undefined" && input.value.length === 2) {
			var isHourElem = input === self.hourElement, isMinuteElem = input === self.minuteElement;
			if (newValue < min) {
				newValue = max + newValue + int(!isHourElem) + (int(isHourElem) && int(!self.amPM));
				if (isMinuteElem) incrementNumInput(void 0, -1, self.hourElement);
			} else if (newValue > max) {
				newValue = input === self.hourElement ? newValue - max - int(!self.amPM) : min;
				if (isMinuteElem) incrementNumInput(void 0, 1, self.hourElement);
			}
			if (self.amPM && isHourElem && (step === 1 ? newValue + curValue === 23 : Math.abs(newValue - curValue) > step)) self.amPM.textContent = self.l10n.amPM[int(self.amPM.textContent === self.l10n.amPM[0])];
			input.value = pad(newValue);
		}
	}
	init();
	return self;
}
function _flatpickr(nodeList, config) {
	var nodes = Array.prototype.slice.call(nodeList).filter(function(x) {
		return x instanceof HTMLElement;
	});
	var instances = [];
	for (var i = 0; i < nodes.length; i++) {
		var node = nodes[i];
		try {
			if (node.getAttribute("data-fp-omit") !== null) continue;
			if (node._flatpickr !== void 0) {
				node._flatpickr.destroy();
				node._flatpickr = void 0;
			}
			node._flatpickr = FlatpickrInstance(node, config || {});
			instances.push(node._flatpickr);
		} catch (e) {
			console.error(e);
		}
	}
	return instances.length === 1 ? instances[0] : instances;
}
if (typeof HTMLElement !== "undefined" && typeof HTMLCollection !== "undefined" && typeof NodeList !== "undefined") {
	HTMLCollection.prototype.flatpickr = NodeList.prototype.flatpickr = function(config) {
		return _flatpickr(this, config);
	};
	HTMLElement.prototype.flatpickr = function(config) {
		return _flatpickr([this], config);
	};
}
var flatpickr = function(selector, config) {
	if (typeof selector === "string") return _flatpickr(window.document.querySelectorAll(selector), config);
	else if (selector instanceof Node) return _flatpickr([selector], config);
	else return _flatpickr(selector, config);
};
flatpickr.defaultConfig = {};
flatpickr.l10ns = {
	en: __assign({}, english),
	default: __assign({}, english)
};
flatpickr.localize = function(l10n) {
	flatpickr.l10ns.default = __assign(__assign({}, flatpickr.l10ns.default), l10n);
};
flatpickr.setDefaults = function(config) {
	flatpickr.defaultConfig = __assign(__assign({}, flatpickr.defaultConfig), config);
};
flatpickr.parseDate = createDateParser({});
flatpickr.formatDate = createDateFormatter({});
flatpickr.compareDates = compareDates;
if (typeof jQuery !== "undefined" && typeof jQuery.fn !== "undefined") jQuery.fn.flatpickr = function(config) {
	return _flatpickr(this, config);
};
Date.prototype.fp_incr = function(days) {
	return new Date(this.getFullYear(), this.getMonth(), this.getDate() + (typeof days === "string" ? parseInt(days, 10) : days));
};
if (typeof window !== "undefined") window.flatpickr = flatpickr;
//#endregion
//#region ../../node_modules/flatpickr/dist/flatpickr.css?inline
var import_l10n = /* @__PURE__ */ __toESM((/* @__PURE__ */ __commonJSMin(((exports, module) => {
	(function(global, factory) {
		typeof exports === "object" && typeof module !== "undefined" ? factory(exports) : typeof define === "function" && define.amd ? define(["exports"], factory) : (global = typeof globalThis !== "undefined" ? globalThis : global || self, factory(global.index = {}));
	})(exports, (function(exports$1) {
		"use strict";
		/*! *****************************************************************************
		Copyright (c) Microsoft Corporation.
		
		Permission to use, copy, modify, and/or distribute this software for any
		purpose with or without fee is hereby granted.
		
		THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
		REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
		AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
		INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
		LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
		OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
		PERFORMANCE OF THIS SOFTWARE.
		***************************************************************************** */
		var __assign = function() {
			__assign = Object.assign || function __assign(t) {
				for (var s, i = 1, n = arguments.length; i < n; i++) {
					s = arguments[i];
					for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
				}
				return t;
			};
			return __assign.apply(this, arguments);
		};
		var fp = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Arabic = {
			weekdays: {
				shorthand: [
					"أحد",
					"اثنين",
					"ثلاثاء",
					"أربعاء",
					"خميس",
					"جمعة",
					"سبت"
				],
				longhand: [
					"الأحد",
					"الاثنين",
					"الثلاثاء",
					"الأربعاء",
					"الخميس",
					"الجمعة",
					"السبت"
				]
			},
			months: {
				shorthand: [
					"1",
					"2",
					"3",
					"4",
					"5",
					"6",
					"7",
					"8",
					"9",
					"10",
					"11",
					"12"
				],
				longhand: [
					"يناير",
					"فبراير",
					"مارس",
					"أبريل",
					"مايو",
					"يونيو",
					"يوليو",
					"أغسطس",
					"سبتمبر",
					"أكتوبر",
					"نوفمبر",
					"ديسمبر"
				]
			},
			firstDayOfWeek: 6,
			rangeSeparator: " إلى ",
			weekAbbreviation: "Wk",
			scrollTitle: "قم بالتمرير للزيادة",
			toggleTitle: "اضغط للتبديل",
			amPM: ["ص", "م"],
			yearAriaLabel: "سنة",
			monthAriaLabel: "شهر",
			hourAriaLabel: "ساعة",
			minuteAriaLabel: "دقيقة",
			time_24hr: false
		};
		fp.l10ns.ar = Arabic;
		fp.l10ns;
		var fp$1 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Austria = {
			weekdays: {
				shorthand: [
					"So",
					"Mo",
					"Di",
					"Mi",
					"Do",
					"Fr",
					"Sa"
				],
				longhand: [
					"Sonntag",
					"Montag",
					"Dienstag",
					"Mittwoch",
					"Donnerstag",
					"Freitag",
					"Samstag"
				]
			},
			months: {
				shorthand: [
					"Jän",
					"Feb",
					"Mär",
					"Apr",
					"Mai",
					"Jun",
					"Jul",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Dez"
				],
				longhand: [
					"Jänner",
					"Februar",
					"März",
					"April",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"September",
					"Oktober",
					"November",
					"Dezember"
				]
			},
			firstDayOfWeek: 1,
			weekAbbreviation: "KW",
			rangeSeparator: " bis ",
			scrollTitle: "Zum Ändern scrollen",
			toggleTitle: "Zum Umschalten klicken",
			time_24hr: true
		};
		fp$1.l10ns.at = Austria;
		fp$1.l10ns;
		var fp$2 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Azerbaijan = {
			weekdays: {
				shorthand: [
					"B.",
					"B.e.",
					"Ç.a.",
					"Ç.",
					"C.a.",
					"C.",
					"Ş."
				],
				longhand: [
					"Bazar",
					"Bazar ertəsi",
					"Çərşənbə axşamı",
					"Çərşənbə",
					"Cümə axşamı",
					"Cümə",
					"Şənbə"
				]
			},
			months: {
				shorthand: [
					"Yan",
					"Fev",
					"Mar",
					"Apr",
					"May",
					"İyn",
					"İyl",
					"Avq",
					"Sen",
					"Okt",
					"Noy",
					"Dek"
				],
				longhand: [
					"Yanvar",
					"Fevral",
					"Mart",
					"Aprel",
					"May",
					"İyun",
					"İyul",
					"Avqust",
					"Sentyabr",
					"Oktyabr",
					"Noyabr",
					"Dekabr"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return ".";
			},
			rangeSeparator: " - ",
			weekAbbreviation: "Hf",
			scrollTitle: "Artırmaq üçün sürüşdürün",
			toggleTitle: "Aç / Bağla",
			amPM: ["GƏ", "GS"],
			time_24hr: true
		};
		fp$2.l10ns.az = Azerbaijan;
		fp$2.l10ns;
		var fp$3 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Belarusian = {
			weekdays: {
				shorthand: [
					"Нд",
					"Пн",
					"Аў",
					"Ср",
					"Чц",
					"Пт",
					"Сб"
				],
				longhand: [
					"Нядзеля",
					"Панядзелак",
					"Аўторак",
					"Серада",
					"Чацвер",
					"Пятніца",
					"Субота"
				]
			},
			months: {
				shorthand: [
					"Сту",
					"Лют",
					"Сак",
					"Кра",
					"Тра",
					"Чэр",
					"Ліп",
					"Жні",
					"Вер",
					"Кас",
					"Ліс",
					"Сне"
				],
				longhand: [
					"Студзень",
					"Люты",
					"Сакавік",
					"Красавік",
					"Травень",
					"Чэрвень",
					"Ліпень",
					"Жнівень",
					"Верасень",
					"Кастрычнік",
					"Лістапад",
					"Снежань"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "Тыд.",
			scrollTitle: "Пракруціце для павелічэння",
			toggleTitle: "Націсніце для пераключэння",
			amPM: ["ДП", "ПП"],
			yearAriaLabel: "Год",
			time_24hr: true
		};
		fp$3.l10ns.be = Belarusian;
		fp$3.l10ns;
		var fp$4 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Bosnian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"Ned",
					"Pon",
					"Uto",
					"Sri",
					"Čet",
					"Pet",
					"Sub"
				],
				longhand: [
					"Nedjelja",
					"Ponedjeljak",
					"Utorak",
					"Srijeda",
					"Četvrtak",
					"Petak",
					"Subota"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Maj",
					"Jun",
					"Jul",
					"Avg",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Januar",
					"Februar",
					"Mart",
					"April",
					"Maj",
					"Juni",
					"Juli",
					"Avgust",
					"Septembar",
					"Oktobar",
					"Novembar",
					"Decembar"
				]
			},
			time_24hr: true
		};
		fp$4.l10ns.bs = Bosnian;
		fp$4.l10ns;
		var fp$5 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Bulgarian = {
			weekdays: {
				shorthand: [
					"Нд",
					"Пн",
					"Вт",
					"Ср",
					"Чт",
					"Пт",
					"Сб"
				],
				longhand: [
					"Неделя",
					"Понеделник",
					"Вторник",
					"Сряда",
					"Четвъртък",
					"Петък",
					"Събота"
				]
			},
			months: {
				shorthand: [
					"Яну",
					"Фев",
					"Март",
					"Апр",
					"Май",
					"Юни",
					"Юли",
					"Авг",
					"Сеп",
					"Окт",
					"Ное",
					"Дек"
				],
				longhand: [
					"Януари",
					"Февруари",
					"Март",
					"Април",
					"Май",
					"Юни",
					"Юли",
					"Август",
					"Септември",
					"Октомври",
					"Ноември",
					"Декември"
				]
			},
			time_24hr: true,
			firstDayOfWeek: 1
		};
		fp$5.l10ns.bg = Bulgarian;
		fp$5.l10ns;
		var fp$6 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Bangla = {
			weekdays: {
				shorthand: [
					"রবি",
					"সোম",
					"মঙ্গল",
					"বুধ",
					"বৃহস্পতি",
					"শুক্র",
					"শনি"
				],
				longhand: [
					"রবিবার",
					"সোমবার",
					"মঙ্গলবার",
					"বুধবার",
					"বৃহস্পতিবার",
					"শুক্রবার",
					"শনিবার"
				]
			},
			months: {
				shorthand: [
					"জানু",
					"ফেব্রু",
					"মার্চ",
					"এপ্রিল",
					"মে",
					"জুন",
					"জুলাই",
					"আগ",
					"সেপ্টে",
					"অক্টো",
					"নভে",
					"ডিসে"
				],
				longhand: [
					"জানুয়ারী",
					"ফেব্রুয়ারী",
					"মার্চ",
					"এপ্রিল",
					"মে",
					"জুন",
					"জুলাই",
					"আগস্ট",
					"সেপ্টেম্বর",
					"অক্টোবর",
					"নভেম্বর",
					"ডিসেম্বর"
				]
			}
		};
		fp$6.l10ns.bn = Bangla;
		fp$6.l10ns;
		var fp$7 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Catalan = {
			weekdays: {
				shorthand: [
					"Dg",
					"Dl",
					"Dt",
					"Dc",
					"Dj",
					"Dv",
					"Ds"
				],
				longhand: [
					"Diumenge",
					"Dilluns",
					"Dimarts",
					"Dimecres",
					"Dijous",
					"Divendres",
					"Dissabte"
				]
			},
			months: {
				shorthand: [
					"Gen",
					"Febr",
					"Març",
					"Abr",
					"Maig",
					"Juny",
					"Jul",
					"Ag",
					"Set",
					"Oct",
					"Nov",
					"Des"
				],
				longhand: [
					"Gener",
					"Febrer",
					"Març",
					"Abril",
					"Maig",
					"Juny",
					"Juliol",
					"Agost",
					"Setembre",
					"Octubre",
					"Novembre",
					"Desembre"
				]
			},
			ordinal: function(nth) {
				var s = nth % 100;
				if (s > 3 && s < 21) return "è";
				switch (s % 10) {
					case 1: return "r";
					case 2: return "n";
					case 3: return "r";
					case 4: return "t";
					default: return "è";
				}
			},
			firstDayOfWeek: 1,
			rangeSeparator: " a ",
			time_24hr: true
		};
		fp$7.l10ns.cat = fp$7.l10ns.ca = Catalan;
		fp$7.l10ns;
		var fp$8 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Kurdish = {
			weekdays: {
				shorthand: [
					"یەکشەممە",
					"دووشەممە",
					"سێشەممە",
					"چوارشەممە",
					"پێنجشەممە",
					"هەینی",
					"شەممە"
				],
				longhand: [
					"یەکشەممە",
					"دووشەممە",
					"سێشەممە",
					"چوارشەممە",
					"پێنجشەممە",
					"هەینی",
					"شەممە"
				]
			},
			months: {
				shorthand: [
					"ڕێبەندان",
					"ڕەشەمە",
					"نەورۆز",
					"گوڵان",
					"جۆزەردان",
					"پووشپەڕ",
					"گەلاوێژ",
					"خەرمانان",
					"ڕەزبەر",
					"گەڵاڕێزان",
					"سەرماوەز",
					"بەفرانبار"
				],
				longhand: [
					"ڕێبەندان",
					"ڕەشەمە",
					"نەورۆز",
					"گوڵان",
					"جۆزەردان",
					"پووشپەڕ",
					"گەلاوێژ",
					"خەرمانان",
					"ڕەزبەر",
					"گەڵاڕێزان",
					"سەرماوەز",
					"بەفرانبار"
				]
			},
			firstDayOfWeek: 6,
			ordinal: function() {
				return "";
			}
		};
		fp$8.l10ns.ckb = Kurdish;
		fp$8.l10ns;
		var fp$9 = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Czech = {
			weekdays: {
				shorthand: [
					"Ne",
					"Po",
					"Út",
					"St",
					"Čt",
					"Pá",
					"So"
				],
				longhand: [
					"Neděle",
					"Pondělí",
					"Úterý",
					"Středa",
					"Čtvrtek",
					"Pátek",
					"Sobota"
				]
			},
			months: {
				shorthand: [
					"Led",
					"Ún",
					"Bře",
					"Dub",
					"Kvě",
					"Čer",
					"Čvc",
					"Srp",
					"Zář",
					"Říj",
					"Lis",
					"Pro"
				],
				longhand: [
					"Leden",
					"Únor",
					"Březen",
					"Duben",
					"Květen",
					"Červen",
					"Červenec",
					"Srpen",
					"Září",
					"Říjen",
					"Listopad",
					"Prosinec"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return ".";
			},
			rangeSeparator: " do ",
			weekAbbreviation: "Týd.",
			scrollTitle: "Rolujte pro změnu",
			toggleTitle: "Přepnout dopoledne/odpoledne",
			amPM: ["dop.", "odp."],
			yearAriaLabel: "Rok",
			time_24hr: true
		};
		fp$9.l10ns.cs = Czech;
		fp$9.l10ns;
		var fp$a = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Welsh = {
			weekdays: {
				shorthand: [
					"Sul",
					"Llun",
					"Maw",
					"Mer",
					"Iau",
					"Gwe",
					"Sad"
				],
				longhand: [
					"Dydd Sul",
					"Dydd Llun",
					"Dydd Mawrth",
					"Dydd Mercher",
					"Dydd Iau",
					"Dydd Gwener",
					"Dydd Sadwrn"
				]
			},
			months: {
				shorthand: [
					"Ion",
					"Chwef",
					"Maw",
					"Ebr",
					"Mai",
					"Meh",
					"Gorff",
					"Awst",
					"Medi",
					"Hyd",
					"Tach",
					"Rhag"
				],
				longhand: [
					"Ionawr",
					"Chwefror",
					"Mawrth",
					"Ebrill",
					"Mai",
					"Mehefin",
					"Gorffennaf",
					"Awst",
					"Medi",
					"Hydref",
					"Tachwedd",
					"Rhagfyr"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function(nth) {
				if (nth === 1) return "af";
				if (nth === 2) return "ail";
				if (nth === 3 || nth === 4) return "ydd";
				if (nth === 5 || nth === 6) return "ed";
				if (nth >= 7 && nth <= 10 || nth == 12 || nth == 15 || nth == 18 || nth == 20) return "fed";
				if (nth == 11 || nth == 13 || nth == 14 || nth == 16 || nth == 17 || nth == 19) return "eg";
				if (nth >= 21 && nth <= 39) return "ain";
				return "";
			},
			time_24hr: true
		};
		fp$a.l10ns.cy = Welsh;
		fp$a.l10ns;
		var fp$b = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Danish = {
			weekdays: {
				shorthand: [
					"søn",
					"man",
					"tir",
					"ons",
					"tors",
					"fre",
					"lør"
				],
				longhand: [
					"søndag",
					"mandag",
					"tirsdag",
					"onsdag",
					"torsdag",
					"fredag",
					"lørdag"
				]
			},
			months: {
				shorthand: [
					"jan",
					"feb",
					"mar",
					"apr",
					"maj",
					"jun",
					"jul",
					"aug",
					"sep",
					"okt",
					"nov",
					"dec"
				],
				longhand: [
					"januar",
					"februar",
					"marts",
					"april",
					"maj",
					"juni",
					"juli",
					"august",
					"september",
					"oktober",
					"november",
					"december"
				]
			},
			ordinal: function() {
				return ".";
			},
			firstDayOfWeek: 1,
			rangeSeparator: " til ",
			weekAbbreviation: "uge",
			time_24hr: true
		};
		fp$b.l10ns.da = Danish;
		fp$b.l10ns;
		var fp$c = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var German = {
			weekdays: {
				shorthand: [
					"So",
					"Mo",
					"Di",
					"Mi",
					"Do",
					"Fr",
					"Sa"
				],
				longhand: [
					"Sonntag",
					"Montag",
					"Dienstag",
					"Mittwoch",
					"Donnerstag",
					"Freitag",
					"Samstag"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mär",
					"Apr",
					"Mai",
					"Jun",
					"Jul",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Dez"
				],
				longhand: [
					"Januar",
					"Februar",
					"März",
					"April",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"September",
					"Oktober",
					"November",
					"Dezember"
				]
			},
			firstDayOfWeek: 1,
			weekAbbreviation: "KW",
			rangeSeparator: " bis ",
			scrollTitle: "Zum Ändern scrollen",
			toggleTitle: "Zum Umschalten klicken",
			time_24hr: true
		};
		fp$c.l10ns.de = German;
		fp$c.l10ns;
		var english = {
			weekdays: {
				shorthand: [
					"Sun",
					"Mon",
					"Tue",
					"Wed",
					"Thu",
					"Fri",
					"Sat"
				],
				longhand: [
					"Sunday",
					"Monday",
					"Tuesday",
					"Wednesday",
					"Thursday",
					"Friday",
					"Saturday"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"May",
					"Jun",
					"Jul",
					"Aug",
					"Sep",
					"Oct",
					"Nov",
					"Dec"
				],
				longhand: [
					"January",
					"February",
					"March",
					"April",
					"May",
					"June",
					"July",
					"August",
					"September",
					"October",
					"November",
					"December"
				]
			},
			daysInMonth: [
				31,
				28,
				31,
				30,
				31,
				30,
				31,
				31,
				30,
				31,
				30,
				31
			],
			firstDayOfWeek: 0,
			ordinal: function(nth) {
				var s = nth % 100;
				if (s > 3 && s < 21) return "th";
				switch (s % 10) {
					case 1: return "st";
					case 2: return "nd";
					case 3: return "rd";
					default: return "th";
				}
			},
			rangeSeparator: " to ",
			weekAbbreviation: "Wk",
			scrollTitle: "Scroll to increment",
			toggleTitle: "Click to toggle",
			amPM: ["AM", "PM"],
			yearAriaLabel: "Year",
			monthAriaLabel: "Month",
			hourAriaLabel: "Hour",
			minuteAriaLabel: "Minute",
			time_24hr: false
		};
		var fp$d = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Esperanto = {
			firstDayOfWeek: 1,
			rangeSeparator: " ĝis ",
			weekAbbreviation: "Sem",
			scrollTitle: "Rulumu por pligrandigi la valoron",
			toggleTitle: "Klaku por ŝalti",
			weekdays: {
				shorthand: [
					"Dim",
					"Lun",
					"Mar",
					"Mer",
					"Ĵaŭ",
					"Ven",
					"Sab"
				],
				longhand: [
					"dimanĉo",
					"lundo",
					"mardo",
					"merkredo",
					"ĵaŭdo",
					"vendredo",
					"sabato"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Maj",
					"Jun",
					"Jul",
					"Aŭg",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"januaro",
					"februaro",
					"marto",
					"aprilo",
					"majo",
					"junio",
					"julio",
					"aŭgusto",
					"septembro",
					"oktobro",
					"novembro",
					"decembro"
				]
			},
			ordinal: function() {
				return "-a";
			},
			time_24hr: true
		};
		fp$d.l10ns.eo = Esperanto;
		fp$d.l10ns;
		var fp$e = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Spanish = {
			weekdays: {
				shorthand: [
					"Dom",
					"Lun",
					"Mar",
					"Mié",
					"Jue",
					"Vie",
					"Sáb"
				],
				longhand: [
					"Domingo",
					"Lunes",
					"Martes",
					"Miércoles",
					"Jueves",
					"Viernes",
					"Sábado"
				]
			},
			months: {
				shorthand: [
					"Ene",
					"Feb",
					"Mar",
					"Abr",
					"May",
					"Jun",
					"Jul",
					"Ago",
					"Sep",
					"Oct",
					"Nov",
					"Dic"
				],
				longhand: [
					"Enero",
					"Febrero",
					"Marzo",
					"Abril",
					"Mayo",
					"Junio",
					"Julio",
					"Agosto",
					"Septiembre",
					"Octubre",
					"Noviembre",
					"Diciembre"
				]
			},
			ordinal: function() {
				return "º";
			},
			firstDayOfWeek: 1,
			rangeSeparator: " a ",
			time_24hr: true
		};
		fp$e.l10ns.es = Spanish;
		fp$e.l10ns;
		var fp$f = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Estonian = {
			weekdays: {
				shorthand: [
					"P",
					"E",
					"T",
					"K",
					"N",
					"R",
					"L"
				],
				longhand: [
					"Pühapäev",
					"Esmaspäev",
					"Teisipäev",
					"Kolmapäev",
					"Neljapäev",
					"Reede",
					"Laupäev"
				]
			},
			months: {
				shorthand: [
					"Jaan",
					"Veebr",
					"Märts",
					"Apr",
					"Mai",
					"Juuni",
					"Juuli",
					"Aug",
					"Sept",
					"Okt",
					"Nov",
					"Dets"
				],
				longhand: [
					"Jaanuar",
					"Veebruar",
					"Märts",
					"Aprill",
					"Mai",
					"Juuni",
					"Juuli",
					"August",
					"September",
					"Oktoober",
					"November",
					"Detsember"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return ".";
			},
			weekAbbreviation: "Näd",
			rangeSeparator: " kuni ",
			scrollTitle: "Keri, et suurendada",
			toggleTitle: "Klõpsa, et vahetada",
			time_24hr: true
		};
		fp$f.l10ns.et = Estonian;
		fp$f.l10ns;
		var fp$g = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Persian = {
			weekdays: {
				shorthand: [
					"یک",
					"دو",
					"سه",
					"چهار",
					"پنج",
					"جمعه",
					"شنبه"
				],
				longhand: [
					"یک‌شنبه",
					"دوشنبه",
					"سه‌شنبه",
					"چهارشنبه",
					"پنچ‌شنبه",
					"جمعه",
					"شنبه"
				]
			},
			months: {
				shorthand: [
					"ژانویه",
					"فوریه",
					"مارس",
					"آوریل",
					"مه",
					"ژوئن",
					"ژوئیه",
					"اوت",
					"سپتامبر",
					"اکتبر",
					"نوامبر",
					"دسامبر"
				],
				longhand: [
					"ژانویه",
					"فوریه",
					"مارس",
					"آوریل",
					"مه",
					"ژوئن",
					"ژوئیه",
					"اوت",
					"سپتامبر",
					"اکتبر",
					"نوامبر",
					"دسامبر"
				]
			},
			firstDayOfWeek: 6,
			ordinal: function() {
				return "";
			}
		};
		fp$g.l10ns.fa = Persian;
		fp$g.l10ns;
		var fp$h = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Finnish = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"su",
					"ma",
					"ti",
					"ke",
					"to",
					"pe",
					"la"
				],
				longhand: [
					"sunnuntai",
					"maanantai",
					"tiistai",
					"keskiviikko",
					"torstai",
					"perjantai",
					"lauantai"
				]
			},
			months: {
				shorthand: [
					"tammi",
					"helmi",
					"maalis",
					"huhti",
					"touko",
					"kesä",
					"heinä",
					"elo",
					"syys",
					"loka",
					"marras",
					"joulu"
				],
				longhand: [
					"tammikuu",
					"helmikuu",
					"maaliskuu",
					"huhtikuu",
					"toukokuu",
					"kesäkuu",
					"heinäkuu",
					"elokuu",
					"syyskuu",
					"lokakuu",
					"marraskuu",
					"joulukuu"
				]
			},
			ordinal: function() {
				return ".";
			},
			time_24hr: true
		};
		fp$h.l10ns.fi = Finnish;
		fp$h.l10ns;
		var fp$i = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Faroese = {
			weekdays: {
				shorthand: [
					"Sun",
					"Mán",
					"Týs",
					"Mik",
					"Hós",
					"Frí",
					"Ley"
				],
				longhand: [
					"Sunnudagur",
					"Mánadagur",
					"Týsdagur",
					"Mikudagur",
					"Hósdagur",
					"Fríggjadagur",
					"Leygardagur"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Mai",
					"Jun",
					"Jul",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Des"
				],
				longhand: [
					"Januar",
					"Februar",
					"Mars",
					"Apríl",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"Septembur",
					"Oktobur",
					"Novembur",
					"Desembur"
				]
			},
			ordinal: function() {
				return ".";
			},
			firstDayOfWeek: 1,
			rangeSeparator: " til ",
			weekAbbreviation: "vika",
			scrollTitle: "Rulla fyri at broyta",
			toggleTitle: "Trýst fyri at skifta",
			yearAriaLabel: "Ár",
			time_24hr: true
		};
		fp$i.l10ns.fo = Faroese;
		fp$i.l10ns;
		var fp$j = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var French = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"dim",
					"lun",
					"mar",
					"mer",
					"jeu",
					"ven",
					"sam"
				],
				longhand: [
					"dimanche",
					"lundi",
					"mardi",
					"mercredi",
					"jeudi",
					"vendredi",
					"samedi"
				]
			},
			months: {
				shorthand: [
					"janv",
					"févr",
					"mars",
					"avr",
					"mai",
					"juin",
					"juil",
					"août",
					"sept",
					"oct",
					"nov",
					"déc"
				],
				longhand: [
					"janvier",
					"février",
					"mars",
					"avril",
					"mai",
					"juin",
					"juillet",
					"août",
					"septembre",
					"octobre",
					"novembre",
					"décembre"
				]
			},
			ordinal: function(nth) {
				if (nth > 1) return "";
				return "er";
			},
			rangeSeparator: " au ",
			weekAbbreviation: "Sem",
			scrollTitle: "Défiler pour augmenter la valeur",
			toggleTitle: "Cliquer pour basculer",
			time_24hr: true
		};
		fp$j.l10ns.fr = French;
		fp$j.l10ns;
		var fp$k = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Greek = {
			weekdays: {
				shorthand: [
					"Κυ",
					"Δε",
					"Τρ",
					"Τε",
					"Πέ",
					"Πα",
					"Σά"
				],
				longhand: [
					"Κυριακή",
					"Δευτέρα",
					"Τρίτη",
					"Τετάρτη",
					"Πέμπτη",
					"Παρασκευή",
					"Σάββατο"
				]
			},
			months: {
				shorthand: [
					"Ιαν",
					"Φεβ",
					"Μάρ",
					"Απρ",
					"Μάι",
					"Ιούν",
					"Ιούλ",
					"Αύγ",
					"Σεπ",
					"Οκτ",
					"Νοέ",
					"Δεκ"
				],
				longhand: [
					"Ιανουάριος",
					"Φεβρουάριος",
					"Μάρτιος",
					"Απρίλιος",
					"Μάιος",
					"Ιούνιος",
					"Ιούλιος",
					"Αύγουστος",
					"Σεπτέμβριος",
					"Οκτώβριος",
					"Νοέμβριος",
					"Δεκέμβριος"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			weekAbbreviation: "Εβδ",
			rangeSeparator: " έως ",
			scrollTitle: "Μετακυλήστε για προσαύξηση",
			toggleTitle: "Κάντε κλικ για αλλαγή",
			amPM: ["ΠΜ", "ΜΜ"],
			yearAriaLabel: "χρόνος",
			monthAriaLabel: "μήνας",
			hourAriaLabel: "ώρα",
			minuteAriaLabel: "λεπτό"
		};
		fp$k.l10ns.gr = Greek;
		fp$k.l10ns;
		var fp$l = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Hebrew = {
			weekdays: {
				shorthand: [
					"א",
					"ב",
					"ג",
					"ד",
					"ה",
					"ו",
					"ש"
				],
				longhand: [
					"ראשון",
					"שני",
					"שלישי",
					"רביעי",
					"חמישי",
					"שישי",
					"שבת"
				]
			},
			months: {
				shorthand: [
					"ינו׳",
					"פבר׳",
					"מרץ",
					"אפר׳",
					"מאי",
					"יוני",
					"יולי",
					"אוג׳",
					"ספט׳",
					"אוק׳",
					"נוב׳",
					"דצמ׳"
				],
				longhand: [
					"ינואר",
					"פברואר",
					"מרץ",
					"אפריל",
					"מאי",
					"יוני",
					"יולי",
					"אוגוסט",
					"ספטמבר",
					"אוקטובר",
					"נובמבר",
					"דצמבר"
				]
			},
			rangeSeparator: " אל ",
			time_24hr: true
		};
		fp$l.l10ns.he = Hebrew;
		fp$l.l10ns;
		var fp$m = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Hindi = {
			weekdays: {
				shorthand: [
					"रवि",
					"सोम",
					"मंगल",
					"बुध",
					"गुरु",
					"शुक्र",
					"शनि"
				],
				longhand: [
					"रविवार",
					"सोमवार",
					"मंगलवार",
					"बुधवार",
					"गुरुवार",
					"शुक्रवार",
					"शनिवार"
				]
			},
			months: {
				shorthand: [
					"जन",
					"फर",
					"मार्च",
					"अप्रेल",
					"मई",
					"जून",
					"जूलाई",
					"अग",
					"सित",
					"अक्ट",
					"नव",
					"दि"
				],
				longhand: [
					"जनवरी ",
					"फरवरी",
					"मार्च",
					"अप्रेल",
					"मई",
					"जून",
					"जूलाई",
					"अगस्त ",
					"सितम्बर",
					"अक्टूबर",
					"नवम्बर",
					"दिसम्बर"
				]
			}
		};
		fp$m.l10ns.hi = Hindi;
		fp$m.l10ns;
		var fp$n = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Croatian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"Ned",
					"Pon",
					"Uto",
					"Sri",
					"Čet",
					"Pet",
					"Sub"
				],
				longhand: [
					"Nedjelja",
					"Ponedjeljak",
					"Utorak",
					"Srijeda",
					"Četvrtak",
					"Petak",
					"Subota"
				]
			},
			months: {
				shorthand: [
					"Sij",
					"Velj",
					"Ožu",
					"Tra",
					"Svi",
					"Lip",
					"Srp",
					"Kol",
					"Ruj",
					"Lis",
					"Stu",
					"Pro"
				],
				longhand: [
					"Siječanj",
					"Veljača",
					"Ožujak",
					"Travanj",
					"Svibanj",
					"Lipanj",
					"Srpanj",
					"Kolovoz",
					"Rujan",
					"Listopad",
					"Studeni",
					"Prosinac"
				]
			},
			time_24hr: true
		};
		fp$n.l10ns.hr = Croatian;
		fp$n.l10ns;
		var fp$o = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Hungarian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"V",
					"H",
					"K",
					"Sz",
					"Cs",
					"P",
					"Szo"
				],
				longhand: [
					"Vasárnap",
					"Hétfő",
					"Kedd",
					"Szerda",
					"Csütörtök",
					"Péntek",
					"Szombat"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Már",
					"Ápr",
					"Máj",
					"Jún",
					"Júl",
					"Aug",
					"Szep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Január",
					"Február",
					"Március",
					"Április",
					"Május",
					"Június",
					"Július",
					"Augusztus",
					"Szeptember",
					"Október",
					"November",
					"December"
				]
			},
			ordinal: function() {
				return ".";
			},
			weekAbbreviation: "Hét",
			scrollTitle: "Görgessen",
			toggleTitle: "Kattintson a váltáshoz",
			rangeSeparator: " - ",
			time_24hr: true
		};
		fp$o.l10ns.hu = Hungarian;
		fp$o.l10ns;
		var fp$p = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Armenian = {
			weekdays: {
				shorthand: [
					"Կիր",
					"Երկ",
					"Երք",
					"Չրք",
					"Հնգ",
					"Ուրբ",
					"Շբթ"
				],
				longhand: [
					"Կիրակի",
					"Եկուշաբթի",
					"Երեքշաբթի",
					"Չորեքշաբթի",
					"Հինգշաբթի",
					"Ուրբաթ",
					"Շաբաթ"
				]
			},
			months: {
				shorthand: [
					"Հնվ",
					"Փտր",
					"Մար",
					"Ապր",
					"Մայ",
					"Հնս",
					"Հլս",
					"Օգս",
					"Սեպ",
					"Հոկ",
					"Նմբ",
					"Դեկ"
				],
				longhand: [
					"Հունվար",
					"Փետրվար",
					"Մարտ",
					"Ապրիլ",
					"Մայիս",
					"Հունիս",
					"Հուլիս",
					"Օգոստոս",
					"Սեպտեմբեր",
					"Հոկտեմբեր",
					"Նոյեմբեր",
					"Դեկտեմբեր"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "ՇԲՏ",
			scrollTitle: "Ոլորեք՝ մեծացնելու համար",
			toggleTitle: "Սեղմեք՝ փոխելու համար",
			amPM: ["ՄԿ", "ԿՀ"],
			yearAriaLabel: "Տարի",
			monthAriaLabel: "Ամիս",
			hourAriaLabel: "Ժամ",
			minuteAriaLabel: "Րոպե",
			time_24hr: true
		};
		fp$p.l10ns.hy = Armenian;
		fp$p.l10ns;
		var fp$q = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Indonesian = {
			weekdays: {
				shorthand: [
					"Min",
					"Sen",
					"Sel",
					"Rab",
					"Kam",
					"Jum",
					"Sab"
				],
				longhand: [
					"Minggu",
					"Senin",
					"Selasa",
					"Rabu",
					"Kamis",
					"Jumat",
					"Sabtu"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Mei",
					"Jun",
					"Jul",
					"Agu",
					"Sep",
					"Okt",
					"Nov",
					"Des"
				],
				longhand: [
					"Januari",
					"Februari",
					"Maret",
					"April",
					"Mei",
					"Juni",
					"Juli",
					"Agustus",
					"September",
					"Oktober",
					"November",
					"Desember"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			time_24hr: true,
			rangeSeparator: " - "
		};
		fp$q.l10ns.id = Indonesian;
		fp$q.l10ns;
		var fp$r = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Icelandic = {
			weekdays: {
				shorthand: [
					"Sun",
					"Mán",
					"Þri",
					"Mið",
					"Fim",
					"Fös",
					"Lau"
				],
				longhand: [
					"Sunnudagur",
					"Mánudagur",
					"Þriðjudagur",
					"Miðvikudagur",
					"Fimmtudagur",
					"Föstudagur",
					"Laugardagur"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Maí",
					"Jún",
					"Júl",
					"Ágú",
					"Sep",
					"Okt",
					"Nóv",
					"Des"
				],
				longhand: [
					"Janúar",
					"Febrúar",
					"Mars",
					"Apríl",
					"Maí",
					"Júní",
					"Júlí",
					"Ágúst",
					"September",
					"Október",
					"Nóvember",
					"Desember"
				]
			},
			ordinal: function() {
				return ".";
			},
			firstDayOfWeek: 1,
			rangeSeparator: " til ",
			weekAbbreviation: "vika",
			yearAriaLabel: "Ár",
			time_24hr: true
		};
		fp$r.l10ns.is = Icelandic;
		fp$r.l10ns;
		var fp$s = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Italian = {
			weekdays: {
				shorthand: [
					"Dom",
					"Lun",
					"Mar",
					"Mer",
					"Gio",
					"Ven",
					"Sab"
				],
				longhand: [
					"Domenica",
					"Lunedì",
					"Martedì",
					"Mercoledì",
					"Giovedì",
					"Venerdì",
					"Sabato"
				]
			},
			months: {
				shorthand: [
					"Gen",
					"Feb",
					"Mar",
					"Apr",
					"Mag",
					"Giu",
					"Lug",
					"Ago",
					"Set",
					"Ott",
					"Nov",
					"Dic"
				],
				longhand: [
					"Gennaio",
					"Febbraio",
					"Marzo",
					"Aprile",
					"Maggio",
					"Giugno",
					"Luglio",
					"Agosto",
					"Settembre",
					"Ottobre",
					"Novembre",
					"Dicembre"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "°";
			},
			rangeSeparator: " al ",
			weekAbbreviation: "Se",
			scrollTitle: "Scrolla per aumentare",
			toggleTitle: "Clicca per cambiare",
			time_24hr: true
		};
		fp$s.l10ns.it = Italian;
		fp$s.l10ns;
		var fp$t = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Japanese = {
			weekdays: {
				shorthand: [
					"日",
					"月",
					"火",
					"水",
					"木",
					"金",
					"土"
				],
				longhand: [
					"日曜日",
					"月曜日",
					"火曜日",
					"水曜日",
					"木曜日",
					"金曜日",
					"土曜日"
				]
			},
			months: {
				shorthand: [
					"1月",
					"2月",
					"3月",
					"4月",
					"5月",
					"6月",
					"7月",
					"8月",
					"9月",
					"10月",
					"11月",
					"12月"
				],
				longhand: [
					"1月",
					"2月",
					"3月",
					"4月",
					"5月",
					"6月",
					"7月",
					"8月",
					"9月",
					"10月",
					"11月",
					"12月"
				]
			},
			time_24hr: true,
			rangeSeparator: " から ",
			monthAriaLabel: "月",
			amPM: ["午前", "午後"],
			yearAriaLabel: "年",
			hourAriaLabel: "時間",
			minuteAriaLabel: "分"
		};
		fp$t.l10ns.ja = Japanese;
		fp$t.l10ns;
		var fp$u = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Georgian = {
			weekdays: {
				shorthand: [
					"კვ",
					"ორ",
					"სა",
					"ოთ",
					"ხუ",
					"პა",
					"შა"
				],
				longhand: [
					"კვირა",
					"ორშაბათი",
					"სამშაბათი",
					"ოთხშაბათი",
					"ხუთშაბათი",
					"პარასკევი",
					"შაბათი"
				]
			},
			months: {
				shorthand: [
					"იან",
					"თებ",
					"მარ",
					"აპრ",
					"მაი",
					"ივნ",
					"ივლ",
					"აგვ",
					"სექ",
					"ოქტ",
					"ნოე",
					"დეკ"
				],
				longhand: [
					"იანვარი",
					"თებერვალი",
					"მარტი",
					"აპრილი",
					"მაისი",
					"ივნისი",
					"ივლისი",
					"აგვისტო",
					"სექტემბერი",
					"ოქტომბერი",
					"ნოემბერი",
					"დეკემბერი"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "კვ.",
			scrollTitle: "დასქროლეთ გასადიდებლად",
			toggleTitle: "დააკლიკეთ გადართვისთვის",
			amPM: ["AM", "PM"],
			yearAriaLabel: "წელი",
			time_24hr: true
		};
		fp$u.l10ns.ka = Georgian;
		fp$u.l10ns;
		var fp$v = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Korean = {
			weekdays: {
				shorthand: [
					"일",
					"월",
					"화",
					"수",
					"목",
					"금",
					"토"
				],
				longhand: [
					"일요일",
					"월요일",
					"화요일",
					"수요일",
					"목요일",
					"금요일",
					"토요일"
				]
			},
			months: {
				shorthand: [
					"1월",
					"2월",
					"3월",
					"4월",
					"5월",
					"6월",
					"7월",
					"8월",
					"9월",
					"10월",
					"11월",
					"12월"
				],
				longhand: [
					"1월",
					"2월",
					"3월",
					"4월",
					"5월",
					"6월",
					"7월",
					"8월",
					"9월",
					"10월",
					"11월",
					"12월"
				]
			},
			ordinal: function() {
				return "일";
			},
			rangeSeparator: " ~ ",
			amPM: ["오전", "오후"]
		};
		fp$v.l10ns.ko = Korean;
		fp$v.l10ns;
		var fp$w = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Khmer = {
			weekdays: {
				shorthand: [
					"អាទិត្យ",
					"ចន្ទ",
					"អង្គារ",
					"ពុធ",
					"ព្រហស.",
					"សុក្រ",
					"សៅរ៍"
				],
				longhand: [
					"អាទិត្យ",
					"ចន្ទ",
					"អង្គារ",
					"ពុធ",
					"ព្រហស្បតិ៍",
					"សុក្រ",
					"សៅរ៍"
				]
			},
			months: {
				shorthand: [
					"មករា",
					"កុម្ភះ",
					"មីនា",
					"មេសា",
					"ឧសភា",
					"មិថុនា",
					"កក្កដា",
					"សីហា",
					"កញ្ញា",
					"តុលា",
					"វិច្ឆិកា",
					"ធ្នូ"
				],
				longhand: [
					"មករា",
					"កុម្ភះ",
					"មីនា",
					"មេសា",
					"ឧសភា",
					"មិថុនា",
					"កក្កដា",
					"សីហា",
					"កញ្ញា",
					"តុលា",
					"វិច្ឆិកា",
					"ធ្នូ"
				]
			},
			ordinal: function() {
				return "";
			},
			firstDayOfWeek: 1,
			rangeSeparator: " ដល់ ",
			weekAbbreviation: "សប្តាហ៍",
			scrollTitle: "រំកិលដើម្បីបង្កើន",
			toggleTitle: "ចុចដើម្បីផ្លាស់ប្ដូរ",
			yearAriaLabel: "ឆ្នាំ",
			time_24hr: true
		};
		fp$w.l10ns.km = Khmer;
		fp$w.l10ns;
		var fp$x = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Kazakh = {
			weekdays: {
				shorthand: [
					"Жс",
					"Дс",
					"Сc",
					"Ср",
					"Бс",
					"Жм",
					"Сб"
				],
				longhand: [
					"Жексенбi",
					"Дүйсенбi",
					"Сейсенбi",
					"Сәрсенбi",
					"Бейсенбi",
					"Жұма",
					"Сенбi"
				]
			},
			months: {
				shorthand: [
					"Қаң",
					"Ақп",
					"Нау",
					"Сәу",
					"Мам",
					"Мау",
					"Шiл",
					"Там",
					"Қыр",
					"Қаз",
					"Қар",
					"Жел"
				],
				longhand: [
					"Қаңтар",
					"Ақпан",
					"Наурыз",
					"Сәуiр",
					"Мамыр",
					"Маусым",
					"Шiлде",
					"Тамыз",
					"Қыркүйек",
					"Қазан",
					"Қараша",
					"Желтоқсан"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "Апта",
			scrollTitle: "Үлкейту үшін айналдырыңыз",
			toggleTitle: "Ауыстыру үшін басыңыз",
			amPM: ["ТД", "ТК"],
			yearAriaLabel: "Жыл"
		};
		fp$x.l10ns.kz = Kazakh;
		fp$x.l10ns;
		var fp$y = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Lithuanian = {
			weekdays: {
				shorthand: [
					"S",
					"Pr",
					"A",
					"T",
					"K",
					"Pn",
					"Š"
				],
				longhand: [
					"Sekmadienis",
					"Pirmadienis",
					"Antradienis",
					"Trečiadienis",
					"Ketvirtadienis",
					"Penktadienis",
					"Šeštadienis"
				]
			},
			months: {
				shorthand: [
					"Sau",
					"Vas",
					"Kov",
					"Bal",
					"Geg",
					"Bir",
					"Lie",
					"Rgp",
					"Rgs",
					"Spl",
					"Lap",
					"Grd"
				],
				longhand: [
					"Sausis",
					"Vasaris",
					"Kovas",
					"Balandis",
					"Gegužė",
					"Birželis",
					"Liepa",
					"Rugpjūtis",
					"Rugsėjis",
					"Spalis",
					"Lapkritis",
					"Gruodis"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "-a";
			},
			rangeSeparator: " iki ",
			weekAbbreviation: "Sav",
			scrollTitle: "Keisti laiką pelės rateliu",
			toggleTitle: "Perjungti laiko formatą",
			time_24hr: true
		};
		fp$y.l10ns.lt = Lithuanian;
		fp$y.l10ns;
		var fp$z = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Latvian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"Sv",
					"Pr",
					"Ot",
					"Tr",
					"Ce",
					"Pk",
					"Se"
				],
				longhand: [
					"Svētdiena",
					"Pirmdiena",
					"Otrdiena",
					"Trešdiena",
					"Ceturtdiena",
					"Piektdiena",
					"Sestdiena"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Mai",
					"Jūn",
					"Jūl",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Janvāris",
					"Februāris",
					"Marts",
					"Aprīlis",
					"Maijs",
					"Jūnijs",
					"Jūlijs",
					"Augusts",
					"Septembris",
					"Oktobris",
					"Novembris",
					"Decembris"
				]
			},
			rangeSeparator: " līdz ",
			time_24hr: true
		};
		fp$z.l10ns.lv = Latvian;
		fp$z.l10ns;
		var fp$A = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Macedonian = {
			weekdays: {
				shorthand: [
					"Не",
					"По",
					"Вт",
					"Ср",
					"Че",
					"Пе",
					"Са"
				],
				longhand: [
					"Недела",
					"Понеделник",
					"Вторник",
					"Среда",
					"Четврток",
					"Петок",
					"Сабота"
				]
			},
			months: {
				shorthand: [
					"Јан",
					"Фев",
					"Мар",
					"Апр",
					"Мај",
					"Јун",
					"Јул",
					"Авг",
					"Сеп",
					"Окт",
					"Ное",
					"Дек"
				],
				longhand: [
					"Јануари",
					"Февруари",
					"Март",
					"Април",
					"Мај",
					"Јуни",
					"Јули",
					"Август",
					"Септември",
					"Октомври",
					"Ноември",
					"Декември"
				]
			},
			firstDayOfWeek: 1,
			weekAbbreviation: "Нед.",
			rangeSeparator: " до ",
			time_24hr: true
		};
		fp$A.l10ns.mk = Macedonian;
		fp$A.l10ns;
		var fp$B = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Mongolian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"Да",
					"Мя",
					"Лх",
					"Пү",
					"Ба",
					"Бя",
					"Ня"
				],
				longhand: [
					"Даваа",
					"Мягмар",
					"Лхагва",
					"Пүрэв",
					"Баасан",
					"Бямба",
					"Ням"
				]
			},
			months: {
				shorthand: [
					"1-р сар",
					"2-р сар",
					"3-р сар",
					"4-р сар",
					"5-р сар",
					"6-р сар",
					"7-р сар",
					"8-р сар",
					"9-р сар",
					"10-р сар",
					"11-р сар",
					"12-р сар"
				],
				longhand: [
					"Нэгдүгээр сар",
					"Хоёрдугаар сар",
					"Гуравдугаар сар",
					"Дөрөвдүгээр сар",
					"Тавдугаар сар",
					"Зургаадугаар сар",
					"Долдугаар сар",
					"Наймдугаар сар",
					"Есдүгээр сар",
					"Аравдугаар сар",
					"Арваннэгдүгээр сар",
					"Арванхоёрдугаар сар"
				]
			},
			rangeSeparator: "-с ",
			time_24hr: true
		};
		fp$B.l10ns.mn = Mongolian;
		fp$B.l10ns;
		var fp$C = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Malaysian = {
			weekdays: {
				shorthand: [
					"Aha",
					"Isn",
					"Sel",
					"Rab",
					"Kha",
					"Jum",
					"Sab"
				],
				longhand: [
					"Ahad",
					"Isnin",
					"Selasa",
					"Rabu",
					"Khamis",
					"Jumaat",
					"Sabtu"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mac",
					"Apr",
					"Mei",
					"Jun",
					"Jul",
					"Ogo",
					"Sep",
					"Okt",
					"Nov",
					"Dis"
				],
				longhand: [
					"Januari",
					"Februari",
					"Mac",
					"April",
					"Mei",
					"Jun",
					"Julai",
					"Ogos",
					"September",
					"Oktober",
					"November",
					"Disember"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			}
		};
		fp$C.l10ns;
		var fp$D = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Burmese = {
			weekdays: {
				shorthand: [
					"နွေ",
					"လာ",
					"ဂါ",
					"ဟူး",
					"ကြာ",
					"သော",
					"နေ"
				],
				longhand: [
					"တနင်္ဂနွေ",
					"တနင်္လာ",
					"အင်္ဂါ",
					"ဗုဒ္ဓဟူး",
					"ကြာသပတေး",
					"သောကြာ",
					"စနေ"
				]
			},
			months: {
				shorthand: [
					"ဇန်",
					"ဖေ",
					"မတ်",
					"ပြီ",
					"မေ",
					"ဇွန်",
					"လိုင်",
					"သြ",
					"စက်",
					"အောက်",
					"နို",
					"ဒီ"
				],
				longhand: [
					"ဇန်နဝါရီ",
					"ဖေဖော်ဝါရီ",
					"မတ်",
					"ဧပြီ",
					"မေ",
					"ဇွန်",
					"ဇူလိုင်",
					"သြဂုတ်",
					"စက်တင်ဘာ",
					"အောက်တိုဘာ",
					"နိုဝင်ဘာ",
					"ဒီဇင်ဘာ"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			time_24hr: true
		};
		fp$D.l10ns.my = Burmese;
		fp$D.l10ns;
		var fp$E = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Dutch = {
			weekdays: {
				shorthand: [
					"zo",
					"ma",
					"di",
					"wo",
					"do",
					"vr",
					"za"
				],
				longhand: [
					"zondag",
					"maandag",
					"dinsdag",
					"woensdag",
					"donderdag",
					"vrijdag",
					"zaterdag"
				]
			},
			months: {
				shorthand: [
					"jan",
					"feb",
					"mrt",
					"apr",
					"mei",
					"jun",
					"jul",
					"aug",
					"sept",
					"okt",
					"nov",
					"dec"
				],
				longhand: [
					"januari",
					"februari",
					"maart",
					"april",
					"mei",
					"juni",
					"juli",
					"augustus",
					"september",
					"oktober",
					"november",
					"december"
				]
			},
			firstDayOfWeek: 1,
			weekAbbreviation: "wk",
			rangeSeparator: " t/m ",
			scrollTitle: "Scroll voor volgende / vorige",
			toggleTitle: "Klik om te wisselen",
			time_24hr: true,
			ordinal: function(nth) {
				if (nth === 1 || nth === 8 || nth >= 20) return "ste";
				return "de";
			}
		};
		fp$E.l10ns.nl = Dutch;
		fp$E.l10ns;
		var fp$F = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var NorwegianNynorsk = {
			weekdays: {
				shorthand: [
					"Sø.",
					"Må.",
					"Ty.",
					"On.",
					"To.",
					"Fr.",
					"La."
				],
				longhand: [
					"Søndag",
					"Måndag",
					"Tysdag",
					"Onsdag",
					"Torsdag",
					"Fredag",
					"Laurdag"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mars",
					"Apr",
					"Mai",
					"Juni",
					"Juli",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Des"
				],
				longhand: [
					"Januar",
					"Februar",
					"Mars",
					"April",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"September",
					"Oktober",
					"November",
					"Desember"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " til ",
			weekAbbreviation: "Veke",
			scrollTitle: "Scroll for å endre",
			toggleTitle: "Klikk for å veksle",
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$F.l10ns.nn = NorwegianNynorsk;
		fp$F.l10ns;
		var fp$G = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Norwegian = {
			weekdays: {
				shorthand: [
					"Søn",
					"Man",
					"Tir",
					"Ons",
					"Tor",
					"Fre",
					"Lør"
				],
				longhand: [
					"Søndag",
					"Mandag",
					"Tirsdag",
					"Onsdag",
					"Torsdag",
					"Fredag",
					"Lørdag"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Mai",
					"Jun",
					"Jul",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Des"
				],
				longhand: [
					"Januar",
					"Februar",
					"Mars",
					"April",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"September",
					"Oktober",
					"November",
					"Desember"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " til ",
			weekAbbreviation: "Uke",
			scrollTitle: "Scroll for å endre",
			toggleTitle: "Klikk for å veksle",
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$G.l10ns.no = Norwegian;
		fp$G.l10ns;
		var fp$H = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Punjabi = {
			weekdays: {
				shorthand: [
					"ਐਤ",
					"ਸੋਮ",
					"ਮੰਗਲ",
					"ਬੁੱਧ",
					"ਵੀਰ",
					"ਸ਼ੁੱਕਰ",
					"ਸ਼ਨਿੱਚਰ"
				],
				longhand: [
					"ਐਤਵਾਰ",
					"ਸੋਮਵਾਰ",
					"ਮੰਗਲਵਾਰ",
					"ਬੁੱਧਵਾਰ",
					"ਵੀਰਵਾਰ",
					"ਸ਼ੁੱਕਰਵਾਰ",
					"ਸ਼ਨਿੱਚਰਵਾਰ"
				]
			},
			months: {
				shorthand: [
					"ਜਨ",
					"ਫ਼ਰ",
					"ਮਾਰ",
					"ਅਪ੍ਰੈ",
					"ਮਈ",
					"ਜੂਨ",
					"ਜੁਲਾ",
					"ਅਗ",
					"ਸਤੰ",
					"ਅਕ",
					"ਨਵੰ",
					"ਦਸੰ"
				],
				longhand: [
					"ਜਨਵਰੀ",
					"ਫ਼ਰਵਰੀ",
					"ਮਾਰਚ",
					"ਅਪ੍ਰੈਲ",
					"ਮਈ",
					"ਜੂਨ",
					"ਜੁਲਾਈ",
					"ਅਗਸਤ",
					"ਸਤੰਬਰ",
					"ਅਕਤੂਬਰ",
					"ਨਵੰਬਰ",
					"ਦਸੰਬਰ"
				]
			},
			time_24hr: true
		};
		fp$H.l10ns.pa = Punjabi;
		fp$H.l10ns;
		var fp$I = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Polish = {
			weekdays: {
				shorthand: [
					"Nd",
					"Pn",
					"Wt",
					"Śr",
					"Cz",
					"Pt",
					"So"
				],
				longhand: [
					"Niedziela",
					"Poniedziałek",
					"Wtorek",
					"Środa",
					"Czwartek",
					"Piątek",
					"Sobota"
				]
			},
			months: {
				shorthand: [
					"Sty",
					"Lut",
					"Mar",
					"Kwi",
					"Maj",
					"Cze",
					"Lip",
					"Sie",
					"Wrz",
					"Paź",
					"Lis",
					"Gru"
				],
				longhand: [
					"Styczeń",
					"Luty",
					"Marzec",
					"Kwiecień",
					"Maj",
					"Czerwiec",
					"Lipiec",
					"Sierpień",
					"Wrzesień",
					"Październik",
					"Listopad",
					"Grudzień"
				]
			},
			rangeSeparator: " do ",
			weekAbbreviation: "tydz.",
			scrollTitle: "Przewiń, aby zwiększyć",
			toggleTitle: "Kliknij, aby przełączyć",
			firstDayOfWeek: 1,
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$I.l10ns.pl = Polish;
		fp$I.l10ns;
		var fp$J = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Portuguese = {
			weekdays: {
				shorthand: [
					"Dom",
					"Seg",
					"Ter",
					"Qua",
					"Qui",
					"Sex",
					"Sáb"
				],
				longhand: [
					"Domingo",
					"Segunda-feira",
					"Terça-feira",
					"Quarta-feira",
					"Quinta-feira",
					"Sexta-feira",
					"Sábado"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Fev",
					"Mar",
					"Abr",
					"Mai",
					"Jun",
					"Jul",
					"Ago",
					"Set",
					"Out",
					"Nov",
					"Dez"
				],
				longhand: [
					"Janeiro",
					"Fevereiro",
					"Março",
					"Abril",
					"Maio",
					"Junho",
					"Julho",
					"Agosto",
					"Setembro",
					"Outubro",
					"Novembro",
					"Dezembro"
				]
			},
			rangeSeparator: " até ",
			time_24hr: true
		};
		fp$J.l10ns.pt = Portuguese;
		fp$J.l10ns;
		var fp$K = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Romanian = {
			weekdays: {
				shorthand: [
					"Dum",
					"Lun",
					"Mar",
					"Mie",
					"Joi",
					"Vin",
					"Sâm"
				],
				longhand: [
					"Duminică",
					"Luni",
					"Marți",
					"Miercuri",
					"Joi",
					"Vineri",
					"Sâmbătă"
				]
			},
			months: {
				shorthand: [
					"Ian",
					"Feb",
					"Mar",
					"Apr",
					"Mai",
					"Iun",
					"Iul",
					"Aug",
					"Sep",
					"Oct",
					"Noi",
					"Dec"
				],
				longhand: [
					"Ianuarie",
					"Februarie",
					"Martie",
					"Aprilie",
					"Mai",
					"Iunie",
					"Iulie",
					"August",
					"Septembrie",
					"Octombrie",
					"Noiembrie",
					"Decembrie"
				]
			},
			firstDayOfWeek: 1,
			time_24hr: true,
			ordinal: function() {
				return "";
			}
		};
		fp$K.l10ns.ro = Romanian;
		fp$K.l10ns;
		var fp$L = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Russian = {
			weekdays: {
				shorthand: [
					"Вс",
					"Пн",
					"Вт",
					"Ср",
					"Чт",
					"Пт",
					"Сб"
				],
				longhand: [
					"Воскресенье",
					"Понедельник",
					"Вторник",
					"Среда",
					"Четверг",
					"Пятница",
					"Суббота"
				]
			},
			months: {
				shorthand: [
					"Янв",
					"Фев",
					"Март",
					"Апр",
					"Май",
					"Июнь",
					"Июль",
					"Авг",
					"Сен",
					"Окт",
					"Ноя",
					"Дек"
				],
				longhand: [
					"Январь",
					"Февраль",
					"Март",
					"Апрель",
					"Май",
					"Июнь",
					"Июль",
					"Август",
					"Сентябрь",
					"Октябрь",
					"Ноябрь",
					"Декабрь"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "Нед.",
			scrollTitle: "Прокрутите для увеличения",
			toggleTitle: "Нажмите для переключения",
			amPM: ["ДП", "ПП"],
			yearAriaLabel: "Год",
			time_24hr: true
		};
		fp$L.l10ns.ru = Russian;
		fp$L.l10ns;
		var fp$M = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Sinhala = {
			weekdays: {
				shorthand: [
					"ඉ",
					"ස",
					"අ",
					"බ",
					"බ්‍ර",
					"සි",
					"සෙ"
				],
				longhand: [
					"ඉරිදා",
					"සඳුදා",
					"අඟහරුවාදා",
					"බදාදා",
					"බ්‍රහස්පතින්දා",
					"සිකුරාදා",
					"සෙනසුරාදා"
				]
			},
			months: {
				shorthand: [
					"ජන",
					"පෙබ",
					"මාර්",
					"අප්‍රේ",
					"මැයි",
					"ජුනි",
					"ජූලි",
					"අගෝ",
					"සැප්",
					"ඔක්",
					"නොවැ",
					"දෙසැ"
				],
				longhand: [
					"ජනවාරි",
					"පෙබරවාරි",
					"මාර්තු",
					"අප්‍රේල්",
					"මැයි",
					"ජුනි",
					"ජූලි",
					"අගෝස්තු",
					"සැප්තැම්බර්",
					"ඔක්තෝබර්",
					"නොවැම්බර්",
					"දෙසැම්බර්"
				]
			},
			time_24hr: true
		};
		fp$M.l10ns.si = Sinhala;
		fp$M.l10ns;
		var fp$N = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Slovak = {
			weekdays: {
				shorthand: [
					"Ned",
					"Pon",
					"Ut",
					"Str",
					"Štv",
					"Pia",
					"Sob"
				],
				longhand: [
					"Nedeľa",
					"Pondelok",
					"Utorok",
					"Streda",
					"Štvrtok",
					"Piatok",
					"Sobota"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Máj",
					"Jún",
					"Júl",
					"Aug",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Január",
					"Február",
					"Marec",
					"Apríl",
					"Máj",
					"Jún",
					"Júl",
					"August",
					"September",
					"Október",
					"November",
					"December"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " do ",
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$N.l10ns.sk = Slovak;
		fp$N.l10ns;
		var fp$O = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Slovenian = {
			weekdays: {
				shorthand: [
					"Ned",
					"Pon",
					"Tor",
					"Sre",
					"Čet",
					"Pet",
					"Sob"
				],
				longhand: [
					"Nedelja",
					"Ponedeljek",
					"Torek",
					"Sreda",
					"Četrtek",
					"Petek",
					"Sobota"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Maj",
					"Jun",
					"Jul",
					"Avg",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Januar",
					"Februar",
					"Marec",
					"April",
					"Maj",
					"Junij",
					"Julij",
					"Avgust",
					"September",
					"Oktober",
					"November",
					"December"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " do ",
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$O.l10ns.sl = Slovenian;
		fp$O.l10ns;
		var fp$P = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Albanian = {
			weekdays: {
				shorthand: [
					"Di",
					"Hë",
					"Ma",
					"Më",
					"En",
					"Pr",
					"Sh"
				],
				longhand: [
					"E Diel",
					"E Hënë",
					"E Martë",
					"E Mërkurë",
					"E Enjte",
					"E Premte",
					"E Shtunë"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Shk",
					"Mar",
					"Pri",
					"Maj",
					"Qer",
					"Kor",
					"Gus",
					"Sht",
					"Tet",
					"Nën",
					"Dhj"
				],
				longhand: [
					"Janar",
					"Shkurt",
					"Mars",
					"Prill",
					"Maj",
					"Qershor",
					"Korrik",
					"Gusht",
					"Shtator",
					"Tetor",
					"Nëntor",
					"Dhjetor"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " deri ",
			weekAbbreviation: "Java",
			yearAriaLabel: "Viti",
			monthAriaLabel: "Muaji",
			hourAriaLabel: "Ora",
			minuteAriaLabel: "Minuta",
			time_24hr: true
		};
		fp$P.l10ns.sq = Albanian;
		fp$P.l10ns;
		var fp$Q = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Serbian = {
			weekdays: {
				shorthand: [
					"Ned",
					"Pon",
					"Uto",
					"Sre",
					"Čet",
					"Pet",
					"Sub"
				],
				longhand: [
					"Nedelja",
					"Ponedeljak",
					"Utorak",
					"Sreda",
					"Četvrtak",
					"Petak",
					"Subota"
				]
			},
			months: {
				shorthand: [
					"Jan",
					"Feb",
					"Mar",
					"Apr",
					"Maj",
					"Jun",
					"Jul",
					"Avg",
					"Sep",
					"Okt",
					"Nov",
					"Dec"
				],
				longhand: [
					"Januar",
					"Februar",
					"Mart",
					"April",
					"Maj",
					"Jun",
					"Jul",
					"Avgust",
					"Septembar",
					"Oktobar",
					"Novembar",
					"Decembar"
				]
			},
			firstDayOfWeek: 1,
			weekAbbreviation: "Ned.",
			rangeSeparator: " do ",
			time_24hr: true
		};
		fp$Q.l10ns.sr = Serbian;
		fp$Q.l10ns;
		var fp$R = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Swedish = {
			firstDayOfWeek: 1,
			weekAbbreviation: "v",
			weekdays: {
				shorthand: [
					"sön",
					"mån",
					"tis",
					"ons",
					"tor",
					"fre",
					"lör"
				],
				longhand: [
					"söndag",
					"måndag",
					"tisdag",
					"onsdag",
					"torsdag",
					"fredag",
					"lördag"
				]
			},
			months: {
				shorthand: [
					"jan",
					"feb",
					"mar",
					"apr",
					"maj",
					"jun",
					"jul",
					"aug",
					"sep",
					"okt",
					"nov",
					"dec"
				],
				longhand: [
					"januari",
					"februari",
					"mars",
					"april",
					"maj",
					"juni",
					"juli",
					"augusti",
					"september",
					"oktober",
					"november",
					"december"
				]
			},
			rangeSeparator: " till ",
			time_24hr: true,
			ordinal: function() {
				return ".";
			}
		};
		fp$R.l10ns.sv = Swedish;
		fp$R.l10ns;
		var fp$S = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Thai = {
			weekdays: {
				shorthand: [
					"อา",
					"จ",
					"อ",
					"พ",
					"พฤ",
					"ศ",
					"ส"
				],
				longhand: [
					"อาทิตย์",
					"จันทร์",
					"อังคาร",
					"พุธ",
					"พฤหัสบดี",
					"ศุกร์",
					"เสาร์"
				]
			},
			months: {
				shorthand: [
					"ม.ค.",
					"ก.พ.",
					"มี.ค.",
					"เม.ย.",
					"พ.ค.",
					"มิ.ย.",
					"ก.ค.",
					"ส.ค.",
					"ก.ย.",
					"ต.ค.",
					"พ.ย.",
					"ธ.ค."
				],
				longhand: [
					"มกราคม",
					"กุมภาพันธ์",
					"มีนาคม",
					"เมษายน",
					"พฤษภาคม",
					"มิถุนายน",
					"กรกฎาคม",
					"สิงหาคม",
					"กันยายน",
					"ตุลาคม",
					"พฤศจิกายน",
					"ธันวาคม"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " ถึง ",
			scrollTitle: "เลื่อนเพื่อเพิ่มหรือลด",
			toggleTitle: "คลิกเพื่อเปลี่ยน",
			time_24hr: true,
			ordinal: function() {
				return "";
			}
		};
		fp$S.l10ns.th = Thai;
		fp$S.l10ns;
		var fp$T = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Turkish = {
			weekdays: {
				shorthand: [
					"Paz",
					"Pzt",
					"Sal",
					"Çar",
					"Per",
					"Cum",
					"Cmt"
				],
				longhand: [
					"Pazar",
					"Pazartesi",
					"Salı",
					"Çarşamba",
					"Perşembe",
					"Cuma",
					"Cumartesi"
				]
			},
			months: {
				shorthand: [
					"Oca",
					"Şub",
					"Mar",
					"Nis",
					"May",
					"Haz",
					"Tem",
					"Ağu",
					"Eyl",
					"Eki",
					"Kas",
					"Ara"
				],
				longhand: [
					"Ocak",
					"Şubat",
					"Mart",
					"Nisan",
					"Mayıs",
					"Haziran",
					"Temmuz",
					"Ağustos",
					"Eylül",
					"Ekim",
					"Kasım",
					"Aralık"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return ".";
			},
			rangeSeparator: " - ",
			weekAbbreviation: "Hf",
			scrollTitle: "Artırmak için kaydırın",
			toggleTitle: "Aç/Kapa",
			amPM: ["ÖÖ", "ÖS"],
			time_24hr: true
		};
		fp$T.l10ns.tr = Turkish;
		fp$T.l10ns;
		var fp$U = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Ukrainian = {
			firstDayOfWeek: 1,
			weekdays: {
				shorthand: [
					"Нд",
					"Пн",
					"Вт",
					"Ср",
					"Чт",
					"Пт",
					"Сб"
				],
				longhand: [
					"Неділя",
					"Понеділок",
					"Вівторок",
					"Середа",
					"Четвер",
					"П'ятниця",
					"Субота"
				]
			},
			months: {
				shorthand: [
					"Січ",
					"Лют",
					"Бер",
					"Кві",
					"Тра",
					"Чер",
					"Лип",
					"Сер",
					"Вер",
					"Жов",
					"Лис",
					"Гру"
				],
				longhand: [
					"Січень",
					"Лютий",
					"Березень",
					"Квітень",
					"Травень",
					"Червень",
					"Липень",
					"Серпень",
					"Вересень",
					"Жовтень",
					"Листопад",
					"Грудень"
				]
			},
			time_24hr: true
		};
		fp$U.l10ns.uk = Ukrainian;
		fp$U.l10ns;
		var fp$V = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Uzbek = {
			weekdays: {
				shorthand: [
					"Якш",
					"Душ",
					"Сеш",
					"Чор",
					"Пай",
					"Жум",
					"Шан"
				],
				longhand: [
					"Якшанба",
					"Душанба",
					"Сешанба",
					"Чоршанба",
					"Пайшанба",
					"Жума",
					"Шанба"
				]
			},
			months: {
				shorthand: [
					"Янв",
					"Фев",
					"Мар",
					"Апр",
					"Май",
					"Июн",
					"Июл",
					"Авг",
					"Сен",
					"Окт",
					"Ноя",
					"Дек"
				],
				longhand: [
					"Январ",
					"Феврал",
					"Март",
					"Апрел",
					"Май",
					"Июн",
					"Июл",
					"Август",
					"Сентябр",
					"Октябр",
					"Ноябр",
					"Декабр"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "Ҳафта",
			scrollTitle: "Катталаштириш учун айлантиринг",
			toggleTitle: "Ўтиш учун босинг",
			amPM: ["AM", "PM"],
			yearAriaLabel: "Йил",
			time_24hr: true
		};
		fp$V.l10ns.uz = Uzbek;
		fp$V.l10ns;
		var fp$W = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var UzbekLatin = {
			weekdays: {
				shorthand: [
					"Ya",
					"Du",
					"Se",
					"Cho",
					"Pa",
					"Ju",
					"Sha"
				],
				longhand: [
					"Yakshanba",
					"Dushanba",
					"Seshanba",
					"Chorshanba",
					"Payshanba",
					"Juma",
					"Shanba"
				]
			},
			months: {
				shorthand: [
					"Yan",
					"Fev",
					"Mar",
					"Apr",
					"May",
					"Iyun",
					"Iyul",
					"Avg",
					"Sen",
					"Okt",
					"Noy",
					"Dek"
				],
				longhand: [
					"Yanvar",
					"Fevral",
					"Mart",
					"Aprel",
					"May",
					"Iyun",
					"Iyul",
					"Avgust",
					"Sentabr",
					"Oktabr",
					"Noyabr",
					"Dekabr"
				]
			},
			firstDayOfWeek: 1,
			ordinal: function() {
				return "";
			},
			rangeSeparator: " — ",
			weekAbbreviation: "Hafta",
			scrollTitle: "Kattalashtirish uchun aylantiring",
			toggleTitle: "O‘tish uchun bosing",
			amPM: ["AM", "PM"],
			yearAriaLabel: "Yil",
			time_24hr: true
		};
		fp$W.l10ns["uz_latn"] = UzbekLatin;
		fp$W.l10ns;
		var fp$X = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Vietnamese = {
			weekdays: {
				shorthand: [
					"CN",
					"T2",
					"T3",
					"T4",
					"T5",
					"T6",
					"T7"
				],
				longhand: [
					"Chủ nhật",
					"Thứ hai",
					"Thứ ba",
					"Thứ tư",
					"Thứ năm",
					"Thứ sáu",
					"Thứ bảy"
				]
			},
			months: {
				shorthand: [
					"Th1",
					"Th2",
					"Th3",
					"Th4",
					"Th5",
					"Th6",
					"Th7",
					"Th8",
					"Th9",
					"Th10",
					"Th11",
					"Th12"
				],
				longhand: [
					"Tháng một",
					"Tháng hai",
					"Tháng ba",
					"Tháng tư",
					"Tháng năm",
					"Tháng sáu",
					"Tháng bảy",
					"Tháng tám",
					"Tháng chín",
					"Tháng mười",
					"Tháng mười một",
					"Tháng mười hai"
				]
			},
			firstDayOfWeek: 1,
			rangeSeparator: " đến "
		};
		fp$X.l10ns.vn = Vietnamese;
		fp$X.l10ns;
		var fp$Y = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var Mandarin = {
			weekdays: {
				shorthand: [
					"周日",
					"周一",
					"周二",
					"周三",
					"周四",
					"周五",
					"周六"
				],
				longhand: [
					"星期日",
					"星期一",
					"星期二",
					"星期三",
					"星期四",
					"星期五",
					"星期六"
				]
			},
			months: {
				shorthand: [
					"一月",
					"二月",
					"三月",
					"四月",
					"五月",
					"六月",
					"七月",
					"八月",
					"九月",
					"十月",
					"十一月",
					"十二月"
				],
				longhand: [
					"一月",
					"二月",
					"三月",
					"四月",
					"五月",
					"六月",
					"七月",
					"八月",
					"九月",
					"十月",
					"十一月",
					"十二月"
				]
			},
			rangeSeparator: " 至 ",
			weekAbbreviation: "周",
			scrollTitle: "滚动切换",
			toggleTitle: "点击切换 12/24 小时时制"
		};
		fp$Y.l10ns.zh = Mandarin;
		fp$Y.l10ns;
		var fp$Z = typeof window !== "undefined" && window.flatpickr !== void 0 ? window.flatpickr : { l10ns: {} };
		var MandarinTraditional = {
			weekdays: {
				shorthand: [
					"週日",
					"週一",
					"週二",
					"週三",
					"週四",
					"週五",
					"週六"
				],
				longhand: [
					"星期日",
					"星期一",
					"星期二",
					"星期三",
					"星期四",
					"星期五",
					"星期六"
				]
			},
			months: {
				shorthand: [
					"一月",
					"二月",
					"三月",
					"四月",
					"五月",
					"六月",
					"七月",
					"八月",
					"九月",
					"十月",
					"十一月",
					"十二月"
				],
				longhand: [
					"一月",
					"二月",
					"三月",
					"四月",
					"五月",
					"六月",
					"七月",
					"八月",
					"九月",
					"十月",
					"十一月",
					"十二月"
				]
			},
			rangeSeparator: " 至 ",
			weekAbbreviation: "週",
			scrollTitle: "滾動切換",
			toggleTitle: "點擊切換 12/24 小時時制"
		};
		fp$Z.l10ns.zh_tw = MandarinTraditional;
		fp$Z.l10ns;
		exports$1.default = {
			ar: Arabic,
			at: Austria,
			az: Azerbaijan,
			be: Belarusian,
			bg: Bulgarian,
			bn: Bangla,
			bs: Bosnian,
			ca: Catalan,
			ckb: Kurdish,
			cat: Catalan,
			cs: Czech,
			cy: Welsh,
			da: Danish,
			de: German,
			default: __assign({}, english),
			en: english,
			eo: Esperanto,
			es: Spanish,
			et: Estonian,
			fa: Persian,
			fi: Finnish,
			fo: Faroese,
			fr: French,
			gr: Greek,
			he: Hebrew,
			hi: Hindi,
			hr: Croatian,
			hu: Hungarian,
			hy: Armenian,
			id: Indonesian,
			is: Icelandic,
			it: Italian,
			ja: Japanese,
			ka: Georgian,
			ko: Korean,
			km: Khmer,
			kz: Kazakh,
			lt: Lithuanian,
			lv: Latvian,
			mk: Macedonian,
			mn: Mongolian,
			ms: Malaysian,
			my: Burmese,
			nl: Dutch,
			nn: NorwegianNynorsk,
			no: Norwegian,
			pa: Punjabi,
			pl: Polish,
			pt: Portuguese,
			ro: Romanian,
			ru: Russian,
			si: Sinhala,
			sk: Slovak,
			sl: Slovenian,
			sq: Albanian,
			sr: Serbian,
			sv: Swedish,
			th: Thai,
			tr: Turkish,
			uk: Ukrainian,
			vn: Vietnamese,
			zh: Mandarin,
			zh_tw: MandarinTraditional,
			uz: Uzbek,
			uz_latn: UzbekLatin
		};
		Object.defineProperty(exports$1, "__esModule", { value: true });
	}));
})))(), 1);
var flatpickr_default = ".flatpickr-calendar{opacity:0;text-align:center;visibility:hidden;box-sizing:border-box;-ms-touch-action:manipulation;touch-action:manipulation;direction:ltr;background:#fff;border:0;border-radius:5px;width:307.875px;padding:0;font-size:14px;line-height:24px;animation:none;display:none;position:absolute;box-shadow:1px 0 #e6e6e6,-1px 0 #e6e6e6,0 1px #e6e6e6,0 -1px #e6e6e6,0 3px 13px #00000014}.flatpickr-calendar.open,.flatpickr-calendar.inline{opacity:1;visibility:visible;max-height:640px}.flatpickr-calendar.open{z-index:99999;display:inline-block}.flatpickr-calendar.animate.open{animation:.3s cubic-bezier(.23,1,.32,1) fpFadeInDown}.flatpickr-calendar.inline{display:block;position:relative;top:2px}.flatpickr-calendar.static{position:absolute;top:calc(100% + 2px)}.flatpickr-calendar.static.open{z-index:999;display:block}.flatpickr-calendar.multiMonth .flatpickr-days .dayContainer:nth-child(n+1) .flatpickr-day.inRange:nth-child(7n+7){-webkit-box-shadow:none!important;box-shadow:none!important}.flatpickr-calendar.multiMonth .flatpickr-days .dayContainer:nth-child(n+2) .flatpickr-day.inRange:nth-child(7n+1){box-shadow:-2px 0 #e6e6e6,5px 0 #e6e6e6}.flatpickr-calendar .hasWeeks .dayContainer,.flatpickr-calendar .hasTime .dayContainer{border-bottom:0;border-bottom-right-radius:0;border-bottom-left-radius:0}.flatpickr-calendar .hasWeeks .dayContainer{border-left:0}.flatpickr-calendar.hasTime .flatpickr-time{border-top:1px solid #e6e6e6;height:40px}.flatpickr-calendar.noCalendar.hasTime .flatpickr-time{height:auto}.flatpickr-calendar:before,.flatpickr-calendar:after{pointer-events:none;content:\"\";border:solid #0000;width:0;height:0;display:block;position:absolute;left:22px}.flatpickr-calendar.rightMost:before,.flatpickr-calendar.arrowRight:before,.flatpickr-calendar.rightMost:after,.flatpickr-calendar.arrowRight:after{left:auto;right:22px}.flatpickr-calendar.arrowCenter:before,.flatpickr-calendar.arrowCenter:after{left:50%;right:50%}.flatpickr-calendar:before{border-width:5px;margin:0 -5px}.flatpickr-calendar:after{border-width:4px;margin:0 -4px}.flatpickr-calendar.arrowTop:before,.flatpickr-calendar.arrowTop:after{bottom:100%}.flatpickr-calendar.arrowTop:before{border-bottom-color:#e6e6e6}.flatpickr-calendar.arrowTop:after{border-bottom-color:#fff}.flatpickr-calendar.arrowBottom:before,.flatpickr-calendar.arrowBottom:after{top:100%}.flatpickr-calendar.arrowBottom:before{border-top-color:#e6e6e6}.flatpickr-calendar.arrowBottom:after{border-top-color:#fff}.flatpickr-calendar:focus{outline:0}.flatpickr-wrapper{display:inline-block;position:relative}.flatpickr-months{display:flex}.flatpickr-months .flatpickr-month{color:#000000e6;fill:#000000e6;text-align:center;-webkit-user-select:none;user-select:none;background:0 0;flex:1;height:34px;line-height:1;position:relative;overflow:hidden}.flatpickr-months .flatpickr-prev-month,.flatpickr-months .flatpickr-next-month{-webkit-user-select:none;user-select:none;cursor:pointer;z-index:3;color:#000000e6;fill:#000000e6;height:34px;padding:10px;text-decoration:none;position:absolute;top:0}.flatpickr-months .flatpickr-prev-month.flatpickr-disabled,.flatpickr-months .flatpickr-next-month.flatpickr-disabled{display:none}.flatpickr-months .flatpickr-prev-month i,.flatpickr-months .flatpickr-next-month i{position:relative}.flatpickr-months .flatpickr-prev-month.flatpickr-prev-month,.flatpickr-months .flatpickr-next-month.flatpickr-prev-month{left:0}.flatpickr-months .flatpickr-prev-month.flatpickr-next-month,.flatpickr-months .flatpickr-next-month.flatpickr-next-month{right:0}.flatpickr-months .flatpickr-prev-month:hover,.flatpickr-months .flatpickr-next-month:hover{color:#959ea9}.flatpickr-months .flatpickr-prev-month:hover svg,.flatpickr-months .flatpickr-next-month:hover svg{fill:#f64747}.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg{width:14px;height:14px}.flatpickr-months .flatpickr-prev-month svg path,.flatpickr-months .flatpickr-next-month svg path{fill:inherit;transition:fill .1s}.numInputWrapper{height:auto;position:relative}.numInputWrapper input,.numInputWrapper span{display:inline-block}.numInputWrapper input{width:100%}.numInputWrapper input::-ms-clear{display:none}.numInputWrapper input::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}.numInputWrapper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}.numInputWrapper span{opacity:0;cursor:pointer;box-sizing:border-box;border:1px solid #39393926;width:14px;height:50%;padding:0 4px 0 2px;line-height:50%;position:absolute;right:0}.numInputWrapper span:hover{background:#0000001a}.numInputWrapper span:active{background:#0003}.numInputWrapper span:after{content:\"\";display:block;position:absolute}.numInputWrapper span.arrowUp{border-bottom:0;top:0}.numInputWrapper span.arrowUp:after{border-bottom:4px solid #39393999;border-left:4px solid #0000;border-right:4px solid #0000;top:26%}.numInputWrapper span.arrowDown{top:50%}.numInputWrapper span.arrowDown:after{border-top:4px solid #39393999;border-left:4px solid #0000;border-right:4px solid #0000;top:40%}.numInputWrapper span svg{width:inherit;height:auto}.numInputWrapper span svg path{fill:#00000080}.numInputWrapper:hover{background:#0000000d}.numInputWrapper:hover span{opacity:1}.flatpickr-current-month{font-size:135%;line-height:inherit;color:inherit;text-align:center;width:75%;height:34px;padding:7.48px 0 0;font-weight:300;line-height:1;display:inline-block;position:absolute;left:12.5%;transform:translate(0,0)}.flatpickr-current-month span.cur-month{color:inherit;margin-left:.5ch;padding:0;font-family:inherit;font-weight:700;display:inline-block}.flatpickr-current-month span.cur-month:hover{background:#0000000d}.flatpickr-current-month .numInputWrapper{width:6ch;width:7ch�;display:inline-block}.flatpickr-current-month .numInputWrapper span.arrowUp:after{border-bottom-color:#000000e6}.flatpickr-current-month .numInputWrapper span.arrowDown:after{border-top-color:#000000e6}.flatpickr-current-month input.cur-year{box-sizing:border-box;color:inherit;cursor:text;font-size:inherit;font-family:inherit;font-weight:300;line-height:inherit;height:auto;vertical-align:initial;appearance:textfield;background:0 0;border:0;border-radius:0;margin:0;padding:0 0 0 .5ch;display:inline-block}.flatpickr-current-month input.cur-year:focus{outline:0}.flatpickr-current-month input.cur-year[disabled],.flatpickr-current-month input.cur-year[disabled]:hover{color:#00000080;pointer-events:none;background:0 0;font-size:100%}.flatpickr-current-month .flatpickr-monthDropdown-months{appearance:menulist;box-sizing:border-box;color:inherit;cursor:pointer;font-size:inherit;height:auto;font-family:inherit;font-weight:300;line-height:inherit;vertical-align:initial;background:0 0;border:none;border-radius:0;outline:none;width:auto;margin:-1px 0 0;padding:0 0 0 .5ch;position:relative}.flatpickr-current-month .flatpickr-monthDropdown-months:focus,.flatpickr-current-month .flatpickr-monthDropdown-months:active{outline:none}.flatpickr-current-month .flatpickr-monthDropdown-months:hover{background:#0000000d}.flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month{background-color:#0000;outline:none;padding:0}.flatpickr-weekdays{text-align:center;background:0 0;align-items:center;width:100%;height:28px;display:flex;overflow:hidden}.flatpickr-weekdays .flatpickr-weekdaycontainer{flex:1;display:flex}span.flatpickr-weekday{cursor:default;color:#0000008a;text-align:center;background:0 0;flex:1;margin:0;font-size:90%;font-weight:bolder;line-height:1;display:block}.dayContainer,.flatpickr-weeks{padding:1px 0 0}.flatpickr-days{align-items:flex-start;width:307.875px;display:flex;position:relative;overflow:hidden}.flatpickr-days:focus{outline:0}.dayContainer{text-align:left;box-sizing:border-box;opacity:1;outline:0;flex-wrap:wrap;justify-content:space-around;width:307.875px;min-width:307.875px;max-width:307.875px;padding:0;display:flex;transform:translate(0,0)}.dayContainer+.dayContainer{box-shadow:-1px 0 #e6e6e6}.flatpickr-day{box-sizing:border-box;color:#393939;cursor:pointer;text-align:center;background:0 0;border:1px solid #0000;border-radius:150px;flex-basis:14.2857%;justify-content:center;width:14.2857%;max-width:39px;height:39px;margin:0;font-weight:400;line-height:39px;display:inline-block;position:relative}.flatpickr-day.inRange,.flatpickr-day.prevMonthDay.inRange,.flatpickr-day.nextMonthDay.inRange,.flatpickr-day.today.inRange,.flatpickr-day.prevMonthDay.today.inRange,.flatpickr-day.nextMonthDay.today.inRange,.flatpickr-day:hover,.flatpickr-day.prevMonthDay:hover,.flatpickr-day.nextMonthDay:hover,.flatpickr-day:focus,.flatpickr-day.prevMonthDay:focus,.flatpickr-day.nextMonthDay:focus{cursor:pointer;background:#e6e6e6;border-color:#e6e6e6;outline:0}.flatpickr-day.today{border-color:#959ea9}.flatpickr-day.today:hover,.flatpickr-day.today:focus{color:#fff;background:#959ea9;border-color:#959ea9}.flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange,.flatpickr-day.selected.inRange,.flatpickr-day.startRange.inRange,.flatpickr-day.endRange.inRange,.flatpickr-day.selected:focus,.flatpickr-day.startRange:focus,.flatpickr-day.endRange:focus,.flatpickr-day.selected:hover,.flatpickr-day.startRange:hover,.flatpickr-day.endRange:hover,.flatpickr-day.selected.prevMonthDay,.flatpickr-day.startRange.prevMonthDay,.flatpickr-day.endRange.prevMonthDay,.flatpickr-day.selected.nextMonthDay,.flatpickr-day.startRange.nextMonthDay,.flatpickr-day.endRange.nextMonthDay{-webkit-box-shadow:none;box-shadow:none;color:#fff;background:#569ff7;border-color:#569ff7}.flatpickr-day.selected.startRange,.flatpickr-day.startRange.startRange,.flatpickr-day.endRange.startRange{border-radius:50px 0 0 50px}.flatpickr-day.selected.endRange,.flatpickr-day.startRange.endRange,.flatpickr-day.endRange.endRange{border-radius:0 50px 50px 0}.flatpickr-day.selected.startRange+.endRange:not(:nth-child(7n+1)),.flatpickr-day.startRange.startRange+.endRange:not(:nth-child(7n+1)),.flatpickr-day.endRange.startRange+.endRange:not(:nth-child(7n+1)){box-shadow:-10px 0 #569ff7}.flatpickr-day.selected.startRange.endRange,.flatpickr-day.startRange.startRange.endRange,.flatpickr-day.endRange.startRange.endRange{border-radius:50px}.flatpickr-day.inRange{border-radius:0;box-shadow:-5px 0 #e6e6e6,5px 0 #e6e6e6}.flatpickr-day.flatpickr-disabled,.flatpickr-day.flatpickr-disabled:hover,.flatpickr-day.prevMonthDay,.flatpickr-day.nextMonthDay,.flatpickr-day.notAllowed,.flatpickr-day.notAllowed.prevMonthDay,.flatpickr-day.notAllowed.nextMonthDay{color:#3939394d;cursor:default;background:0 0;border-color:#0000}.flatpickr-day.flatpickr-disabled,.flatpickr-day.flatpickr-disabled:hover{cursor:not-allowed;color:#3939391a}.flatpickr-day.week.selected{border-radius:0;box-shadow:-5px 0 #569ff7,5px 0 #569ff7}.flatpickr-day.hidden{visibility:hidden}.rangeMode .flatpickr-day{margin-top:1px}.flatpickr-weekwrapper{float:left}.flatpickr-weekwrapper .flatpickr-weeks{padding:0 12px;box-shadow:1px 0 #e6e6e6}.flatpickr-weekwrapper .flatpickr-weekday{float:none;width:100%;line-height:28px}.flatpickr-weekwrapper span.flatpickr-day,.flatpickr-weekwrapper span.flatpickr-day:hover{color:#3939394d;cursor:default;background:0 0;border:none;width:100%;max-width:none;display:block}.flatpickr-innerContainer{box-sizing:border-box;display:flex;overflow:hidden}.flatpickr-rContainer{box-sizing:border-box;padding:0;display:inline-block}.flatpickr-time{text-align:center;box-sizing:border-box;outline:0;height:0;max-height:40px;line-height:40px;display:flex;overflow:hidden}.flatpickr-time:after{content:\"\";clear:both;display:table}.flatpickr-time .numInputWrapper{float:left;flex:1;width:40%;height:40px}.flatpickr-time .numInputWrapper span.arrowUp:after{border-bottom-color:#393939}.flatpickr-time .numInputWrapper span.arrowDown:after{border-top-color:#393939}.flatpickr-time.hasSeconds .numInputWrapper{width:26%}.flatpickr-time.time24hr .numInputWrapper{width:49%}.flatpickr-time input{-webkit-box-shadow:none;box-shadow:none;text-align:center;height:inherit;line-height:inherit;color:#393939;box-sizing:border-box;appearance:textfield;background:0 0;border:0;border-radius:0;margin:0;padding:0;font-size:14px;position:relative}.flatpickr-time input.flatpickr-hour{font-weight:700}.flatpickr-time input.flatpickr-minute,.flatpickr-time input.flatpickr-second{font-weight:400}.flatpickr-time input:focus{border:0;outline:0}.flatpickr-time .flatpickr-time-separator,.flatpickr-time .flatpickr-am-pm{height:inherit;float:left;line-height:inherit;color:#393939;-webkit-user-select:none;user-select:none;align-self:center;width:2%;font-weight:700}.flatpickr-time .flatpickr-am-pm{cursor:pointer;text-align:center;outline:0;width:18%;font-weight:400}.flatpickr-time input:hover,.flatpickr-time .flatpickr-am-pm:hover,.flatpickr-time input:focus,.flatpickr-time .flatpickr-am-pm:focus{background:#eee}.flatpickr-input[readonly]{cursor:pointer}@keyframes fpFadeInDown{0%{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translate(0,0)}}";
//#endregion
//#region src/js/modules/fields/date-picker.ts
var INPUT_SELECTOR = "input[data-formie-date-datepicker-input]";
var MODULE_ID = "date-picker";
var debug = createDebug("fields", "date-picker");
ensureModuleStyles(MODULE_ID, [flatpickr_default]);
function attributesPlugin() {
	return (instance) => {
		return { onReady: () => {
			if (!instance.altInput) return;
			const excludedAttributes = new Set([
				"type",
				"name",
				"value"
			]);
			instance.input.getAttributeNames().forEach((attribute) => {
				if (excludedAttributes.has(attribute)) return;
				const value = instance.input.getAttribute(attribute);
				if (value !== null) instance.altInput?.setAttribute(attribute, value);
				instance.input.removeAttribute(attribute);
			});
			instance.loadedPlugins.push("formie-attributes");
		} };
	};
}
function normalizeOffsetDate(input, type) {
	if (!input) return null;
	if (!Number.isNaN(Date.parse(input))) return new Date(input);
	const match = input.trim().match(/^([+-]?\d+)\s*(day|days|week|weeks|month|months|year|years)$/i);
	if (!match) return null;
	const amount = parseInt(match[1] || "0", 10);
	const unit = (match[2] || "").toLowerCase();
	const date = /* @__PURE__ */ new Date();
	switch (unit) {
		case "day":
		case "days":
			date.setDate(date.getDate() + amount);
			break;
		case "week":
		case "weeks":
			date.setDate(date.getDate() + amount * 7);
			break;
		case "month":
		case "months":
			date.setMonth(date.getMonth() + amount);
			break;
		case "year":
		case "years":
			date.setFullYear(date.getFullYear() + amount);
			break;
		default: return null;
	}
	if (type === "min") date.setHours(0, 0, 0, 0);
	else date.setHours(23, 59, 59, 999);
	return date;
}
function prepareFormat(options) {
	return (options.getIsDate ? options.dateFormat || "" : options.getIsTime ? options.timeFormat || "" : `${options.dateFormat || ""} ${options.timeFormat || ""}`.trim()).replaceAll("A", "K").replaceAll("a", "K").replaceAll("s", "S").replaceAll("g", "h").replaceAll("h", "G");
}
function getLocale(locale) {
	if (!locale || locale === "en") return "en";
	const localeMap = import_l10n;
	return localeMap[locale] ?? localeMap.default ?? "en";
}
function getDisabledWeekdayHandler(availableDaysOfWeek) {
	if (!availableDaysOfWeek || availableDaysOfWeek === "*") return;
	const allowedDays = availableDaysOfWeek.map((value) => {
		return Number(value);
	});
	return (date) => {
		return !allowedDays.includes(date.getDay());
	};
}
function getCustomOptions(options) {
	const result = {};
	(options.datePickerOptions || []).forEach((entry) => {
		if (!entry.label) return;
		result[entry.label] = entry.value;
	});
	return result;
}
function initDatePicker(input, options) {
	input._formieFlatpickr?.destroy();
	const defaultOptions = {
		disableMobile: true,
		allowInput: true,
		altInput: true,
		altFormat: prepareFormat(options),
		dateFormat: "Y-m-d H:i:S",
		hourIncrement: 1,
		minuteIncrement: 1,
		minDate: normalizeOffsetDate(options.minDate, "min"),
		maxDate: normalizeOffsetDate(options.maxDate, "max"),
		plugins: [attributesPlugin()],
		locale: getLocale(options.locale),
		onChange: (_selectedDates, _dateStr, instance) => {
			instance.input.dispatchEvent(new Event("input", { bubbles: true }));
			instance.altInput?.dispatchEvent(new Event("input", { bubbles: true }));
		}
	};
	const disableWeekdays = getDisabledWeekdayHandler(options.availableDaysOfWeek);
	if (disableWeekdays) defaultOptions.disable = [disableWeekdays];
	if (options.getIsTime || options.getIsDateTime) defaultOptions.enableTime = true;
	if (options.getIsTime) defaultOptions.noCalendar = true;
	const mergedOptions = {
		...defaultOptions,
		...getCustomOptions(options)
	};
	dispatchFieldEvent(input, MODULE_ID, "before-init", {
		datepicker: input,
		options: mergedOptions
	});
	const instance = flatpickr(input, mergedOptions);
	input._formieFlatpickr = instance;
	debug.log("Initialized.", { inputName: input.name });
	dispatchFieldEvent(input, MODULE_ID, "after-init", {
		datepicker: instance,
		options: mergedOptions
	});
	return () => {
		instance.destroy();
		delete input._formieFlatpickr;
		debug.log("Destroyed.", { inputName: input.name });
	};
}
var datePickerModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(INPUT_SELECTOR);
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		const fields = getModuleFieldContainers(ctx);
		const cleanups = fields.map((field) => {
			const input = field.querySelector(INPUT_SELECTOR);
			if (!(input instanceof HTMLInputElement)) {
				debug.warn("Field missing date input; skipping.");
				return () => {};
			}
			return initDatePicker(input, options);
		});
		debug.log("Module setup.", { fieldCount: fields.length });
		await ctx.emit("formie:module:date-picker:init", { count: cleanups.length });
		return { destroy: () => {
			cleanups.forEach((cleanup) => {
				cleanup();
			});
			debug.log("Module destroy.", { fieldCount: fields.length });
			ctx.emit("formie:module:date-picker:destroy", {});
		} };
	}
};
//#endregion
export { datePickerModule };
