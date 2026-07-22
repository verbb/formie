import { g as ct } from "./_commonjsHelpers-DaMA6jEr.js";
import { g as pt, d as et } from "./shared-BDEKVuB5.js";
import { e as ht } from "./styles-C3aqgtek.js";
import { j as gt } from "./index-CZtn5KAB.js";
function mt(t, r) {
  for (var e = 0; e < r.length; e++) {
    const s = r[e];
    if (typeof s != "string" && !Array.isArray(s)) {
      for (const p in s)
        if (p !== "default" && !(p in t)) {
          const f = Object.getOwnPropertyDescriptor(s, p);
          f && Object.defineProperty(t, p, f.get ? f : {
            enumerable: !0,
            get: () => s[p]
          });
        }
    }
  }
  return Object.freeze(Object.defineProperty(t, Symbol.toStringTag, { value: "Module" }));
}
var xn = [
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
], ke = {
  _disable: [],
  allowInput: !1,
  allowInvalidPreload: !1,
  altFormat: "F j, Y",
  altInput: !1,
  altInputClass: "form-control input",
  animate: typeof window == "object" && window.navigator.userAgent.indexOf("MSIE") === -1,
  ariaDateFormat: "F j, Y",
  autoFillDefaultTime: !0,
  clickOpens: !0,
  closeOnSelect: !0,
  conjunction: ", ",
  dateFormat: "Y-m-d",
  defaultHour: 12,
  defaultMinute: 0,
  defaultSeconds: 0,
  disable: [],
  disableMobile: !1,
  enableSeconds: !1,
  enableTime: !1,
  errorHandler: function(t) {
    return typeof console < "u" && console.warn(t);
  },
  getWeek: function(t) {
    var r = new Date(t.getTime());
    r.setHours(0, 0, 0, 0), r.setDate(r.getDate() + 3 - (r.getDay() + 6) % 7);
    var e = new Date(r.getFullYear(), 0, 4);
    return 1 + Math.round(((r.getTime() - e.getTime()) / 864e5 - 3 + (e.getDay() + 6) % 7) / 7);
  },
  hourIncrement: 1,
  ignoredFocusElements: [],
  inline: !1,
  locale: "default",
  minuteIncrement: 5,
  mode: "single",
  monthSelectorType: "dropdown",
  nextArrow: "<svg version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' viewBox='0 0 17 17'><g></g><path d='M13.207 8.472l-7.854 7.854-0.707-0.707 7.146-7.146-7.146-7.148 0.707-0.707 7.854 7.854z' /></svg>",
  noCalendar: !1,
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
  shorthandCurrentMonth: !1,
  showMonths: 1,
  static: !1,
  time_24hr: !1,
  weekNumbers: !1,
  wrap: !1
}, Le = {
  weekdays: {
    shorthand: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
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
  daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
  firstDayOfWeek: 0,
  ordinal: function(t) {
    var r = t % 100;
    if (r > 3 && r < 21)
      return "th";
    switch (r % 10) {
      case 1:
        return "st";
      case 2:
        return "nd";
      case 3:
        return "rd";
      default:
        return "th";
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
  time_24hr: !1
}, F = function(t, r) {
  return r === void 0 && (r = 2), ("000" + t).slice(r * -1);
}, z = function(t) {
  return t === !0 ? 1 : 0;
};
function nt(t, r) {
  var e;
  return function() {
    var s = this, p = arguments;
    clearTimeout(e), e = setTimeout(function() {
      return t.apply(s, p);
    }, r);
  };
}
var Sn = function(t) {
  return t instanceof Array ? t : [t];
};
function I(t, r, e) {
  if (e === !0)
    return t.classList.add(r);
  t.classList.remove(r);
}
function y(t, r, e) {
  var s = window.document.createElement(t);
  return r = r || "", e = e || "", s.className = r, e !== void 0 && (s.textContent = e), s;
}
function bn(t) {
  for (; t.firstChild; )
    t.removeChild(t.firstChild);
}
function lt(t, r) {
  if (r(t))
    return t;
  if (t.parentNode)
    return lt(t.parentNode, r);
}
function kn(t, r) {
  var e = y("div", "numInputWrapper"), s = y("input", "numInput " + t), p = y("span", "arrowUp"), f = y("span", "arrowDown");
  if (navigator.userAgent.indexOf("MSIE 9.0") === -1 ? s.type = "number" : (s.type = "text", s.pattern = "\\d*"), r !== void 0)
    for (var w in r)
      s.setAttribute(w, r[w]);
  return e.appendChild(s), e.appendChild(p), e.appendChild(f), e;
}
function N(t) {
  try {
    if (typeof t.composedPath == "function") {
      var r = t.composedPath();
      return r[0];
    }
    return t.target;
  } catch {
    return t.target;
  }
}
var Cn = function() {
}, vn = function(t, r, e) {
  return e.months[r ? "shorthand" : "longhand"][t];
}, wt = {
  D: Cn,
  F: function(t, r, e) {
    t.setMonth(e.months.longhand.indexOf(r));
  },
  G: function(t, r) {
    t.setHours((t.getHours() >= 12 ? 12 : 0) + parseFloat(r));
  },
  H: function(t, r) {
    t.setHours(parseFloat(r));
  },
  J: function(t, r) {
    t.setDate(parseFloat(r));
  },
  K: function(t, r, e) {
    t.setHours(t.getHours() % 12 + 12 * z(new RegExp(e.amPM[1], "i").test(r)));
  },
  M: function(t, r, e) {
    t.setMonth(e.months.shorthand.indexOf(r));
  },
  S: function(t, r) {
    t.setSeconds(parseFloat(r));
  },
  U: function(t, r) {
    return new Date(parseFloat(r) * 1e3);
  },
  W: function(t, r, e) {
    var s = parseInt(r), p = new Date(t.getFullYear(), 0, 2 + (s - 1) * 7, 0, 0, 0, 0);
    return p.setDate(p.getDate() - p.getDay() + e.firstDayOfWeek), p;
  },
  Y: function(t, r) {
    t.setFullYear(parseFloat(r));
  },
  Z: function(t, r) {
    return new Date(r);
  },
  d: function(t, r) {
    t.setDate(parseFloat(r));
  },
  h: function(t, r) {
    t.setHours((t.getHours() >= 12 ? 12 : 0) + parseFloat(r));
  },
  i: function(t, r) {
    t.setMinutes(parseFloat(r));
  },
  j: function(t, r) {
    t.setDate(parseFloat(r));
  },
  l: Cn,
  m: function(t, r) {
    t.setMonth(parseFloat(r) - 1);
  },
  n: function(t, r) {
    t.setMonth(parseFloat(r) - 1);
  },
  s: function(t, r) {
    t.setSeconds(parseFloat(r));
  },
  u: function(t, r) {
    return new Date(parseFloat(r));
  },
  w: Cn,
  y: function(t, r) {
    t.setFullYear(2e3 + parseFloat(r));
  }
}, de = {
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
}, Pe = {
  Z: function(t) {
    return t.toISOString();
  },
  D: function(t, r, e) {
    return r.weekdays.shorthand[Pe.w(t, r, e)];
  },
  F: function(t, r, e) {
    return vn(Pe.n(t, r, e) - 1, !1, r);
  },
  G: function(t, r, e) {
    return F(Pe.h(t, r, e));
  },
  H: function(t) {
    return F(t.getHours());
  },
  J: function(t, r) {
    return r.ordinal !== void 0 ? t.getDate() + r.ordinal(t.getDate()) : t.getDate();
  },
  K: function(t, r) {
    return r.amPM[z(t.getHours() > 11)];
  },
  M: function(t, r) {
    return vn(t.getMonth(), !0, r);
  },
  S: function(t) {
    return F(t.getSeconds());
  },
  U: function(t) {
    return t.getTime() / 1e3;
  },
  W: function(t, r, e) {
    return e.getWeek(t);
  },
  Y: function(t) {
    return F(t.getFullYear(), 4);
  },
  d: function(t) {
    return F(t.getDate());
  },
  h: function(t) {
    return t.getHours() % 12 ? t.getHours() % 12 : 12;
  },
  i: function(t) {
    return F(t.getMinutes());
  },
  j: function(t) {
    return t.getDate();
  },
  l: function(t, r) {
    return r.weekdays.longhand[t.getDay()];
  },
  m: function(t) {
    return F(t.getMonth() + 1);
  },
  n: function(t) {
    return t.getMonth() + 1;
  },
  s: function(t) {
    return t.getSeconds();
  },
  u: function(t) {
    return t.getTime();
  },
  w: function(t) {
    return t.getDay();
  },
  y: function(t) {
    return String(t.getFullYear()).substring(2);
  }
}, dt = function(t) {
  var r = t.config, e = r === void 0 ? ke : r, s = t.l10n, p = s === void 0 ? Le : s, f = t.isMobile, w = f === void 0 ? !1 : f;
  return function(M, S, j) {
    var k = j || p;
    return e.formatDate !== void 0 && !w ? e.formatDate(M, S, k) : S.split("").map(function(A, T, L) {
      return Pe[A] && L[T - 1] !== "\\" ? Pe[A](M, k, e) : A !== "\\" ? A : "";
    }).join("");
  };
}, En = function(t) {
  var r = t.config, e = r === void 0 ? ke : r, s = t.l10n, p = s === void 0 ? Le : s;
  return function(f, w, M, S) {
    if (!(f !== 0 && !f)) {
      var j = S || p, k, A = f;
      if (f instanceof Date)
        k = new Date(f.getTime());
      else if (typeof f != "string" && f.toFixed !== void 0)
        k = new Date(f);
      else if (typeof f == "string") {
        var T = w || (e || ke).dateFormat, L = String(f).trim();
        if (L === "today")
          k = /* @__PURE__ */ new Date(), M = !0;
        else if (e && e.parseDate)
          k = e.parseDate(f, T);
        else if (/Z$/.test(L) || /GMT$/.test(L))
          k = new Date(f);
        else {
          for (var ee = void 0, b = [], H = 0, se = 0, J = ""; H < T.length; H++) {
            var R = T[H], B = R === "\\", fe = T[H - 1] === "\\" || B;
            if (de[R] && !fe) {
              J += de[R];
              var Y = new RegExp(J).exec(f);
              Y && (ee = !0) && b[R !== "Y" ? "push" : "unshift"]({
                fn: wt[R],
                val: Y[++se]
              });
            } else B || (J += ".");
          }
          k = !e || !e.noCalendar ? new Date((/* @__PURE__ */ new Date()).getFullYear(), 0, 1, 0, 0, 0, 0) : new Date((/* @__PURE__ */ new Date()).setHours(0, 0, 0, 0)), b.forEach(function(G) {
            var U = G.fn, ue = G.val;
            return k = U(k, ue, j) || k;
          }), k = ee ? k : void 0;
        }
      }
      if (!(k instanceof Date && !isNaN(k.getTime()))) {
        e.errorHandler(new Error("Invalid date provided: " + A));
        return;
      }
      return M === !0 && k.setHours(0, 0, 0, 0), k;
    }
  };
};
function P(t, r, e) {
  return e === void 0 && (e = !0), e !== !1 ? new Date(t.getTime()).setHours(0, 0, 0, 0) - new Date(r.getTime()).setHours(0, 0, 0, 0) : t.getTime() - r.getTime();
}
var bt = function(t, r, e) {
  return t > Math.min(r, e) && t < Math.max(r, e);
}, An = function(t, r, e) {
  return t * 3600 + r * 60 + e;
}, kt = function(t) {
  var r = Math.floor(t / 3600), e = (t - r * 3600) / 60;
  return [r, e, t - r * 3600 - e * 60];
}, vt = {
  DAY: 864e5
};
function Tn(t) {
  var r = t.defaultHour, e = t.defaultMinute, s = t.defaultSeconds;
  if (t.minDate !== void 0) {
    var p = t.minDate.getHours(), f = t.minDate.getMinutes(), w = t.minDate.getSeconds();
    r < p && (r = p), r === p && e < f && (e = f), r === p && e === f && s < w && (s = t.minDate.getSeconds());
  }
  if (t.maxDate !== void 0) {
    var M = t.maxDate.getHours(), S = t.maxDate.getMinutes();
    r = Math.min(r, M), r === M && (e = Math.min(S, e)), r === M && e === S && (s = t.maxDate.getSeconds());
  }
  return { hours: r, minutes: e, seconds: s };
}
typeof Object.assign != "function" && (Object.assign = function(t) {
  for (var r = [], e = 1; e < arguments.length; e++)
    r[e - 1] = arguments[e];
  if (!t)
    throw TypeError("Cannot convert undefined or null to object");
  for (var s = function(M) {
    M && Object.keys(M).forEach(function(S) {
      return t[S] = M[S];
    });
  }, p = 0, f = r; p < f.length; p++) {
    var w = f[p];
    s(w);
  }
  return t;
});
var E = function() {
  return E = Object.assign || function(t) {
    for (var r, e = 1, s = arguments.length; e < s; e++) {
      r = arguments[e];
      for (var p in r) Object.prototype.hasOwnProperty.call(r, p) && (t[p] = r[p]);
    }
    return t;
  }, E.apply(this, arguments);
}, tt = function() {
  for (var t = 0, r = 0, e = arguments.length; r < e; r++) t += arguments[r].length;
  for (var s = Array(t), p = 0, r = 0; r < e; r++)
    for (var f = arguments[r], w = 0, M = f.length; w < M; w++, p++)
      s[p] = f[w];
  return s;
}, yt = 300;
function Dt(t, r) {
  var e = {
    config: E(E({}, ke), C.defaultConfig),
    l10n: Le
  };
  e.parseDate = En({ config: e.config, l10n: e.l10n }), e._handlers = [], e.pluginElements = [], e.loadedPlugins = [], e._bind = b, e._setHoursFromDate = T, e._positionCalendar = ie, e.changeMonth = ce, e.changeYear = te, e.clear = Ye, e.close = $e, e.onMouseOver = re, e._createElement = y, e.createDay = Y, e.destroy = Ke, e.isEnabled = $, e.jumpToDate = J, e.updateValue = W, e.open = Ue, e.redraw = Te, e.set = Qe, e.setDate = Xe, e.toggle = an;
  function s() {
    e.utils = {
      getDaysInMonth: function(n, a) {
        return n === void 0 && (n = e.currentMonth), a === void 0 && (a = e.currentYear), n === 1 && (a % 4 === 0 && a % 100 !== 0 || a % 400 === 0) ? 29 : e.l10n.daysInMonth[n];
      }
    };
  }
  function p() {
    e.element = e.input = t, e.isOpen = !1, Ve(), Ae(), nn(), en(), s(), e.isMobile || fe(), se(), (e.selectedDates.length || e.config.noCalendar) && (e.config.enableTime && T(e.config.noCalendar ? e.latestSelectedDateObj : void 0), W(!1)), M();
    var n = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    !e.isMobile && n && ie(), x("onReady");
  }
  function f() {
    var n;
    return ((n = e.calendarContainer) === null || n === void 0 ? void 0 : n.getRootNode()).activeElement || document.activeElement;
  }
  function w(n) {
    return n.bind(e);
  }
  function M() {
    var n = e.config;
    n.weekNumbers === !1 && n.showMonths === 1 || n.noCalendar !== !0 && window.requestAnimationFrame(function() {
      if (e.calendarContainer !== void 0 && (e.calendarContainer.style.visibility = "hidden", e.calendarContainer.style.display = "block"), e.daysContainer !== void 0) {
        var a = (e.days.offsetWidth + 1) * n.showMonths;
        e.daysContainer.style.width = a + "px", e.calendarContainer.style.width = a + (e.weekWrapper !== void 0 ? e.weekWrapper.offsetWidth : 0) + "px", e.calendarContainer.style.removeProperty("visibility"), e.calendarContainer.style.removeProperty("display");
      }
    });
  }
  function S(n) {
    if (e.selectedDates.length === 0) {
      var a = e.config.minDate === void 0 || P(/* @__PURE__ */ new Date(), e.config.minDate) >= 0 ? /* @__PURE__ */ new Date() : new Date(e.config.minDate.getTime()), i = Tn(e.config);
      a.setHours(i.hours, i.minutes, i.seconds, a.getMilliseconds()), e.selectedDates = [a], e.latestSelectedDateObj = a;
    }
    n !== void 0 && n.type !== "blur" && ln(n);
    var o = e._input.value;
    A(), W(), e._input.value !== o && e._debouncedChange();
  }
  function j(n, a) {
    return n % 12 + 12 * z(a === e.l10n.amPM[1]);
  }
  function k(n) {
    switch (n % 24) {
      case 0:
      case 12:
        return 12;
      default:
        return n % 12;
    }
  }
  function A() {
    if (!(e.hourElement === void 0 || e.minuteElement === void 0)) {
      var n = (parseInt(e.hourElement.value.slice(-2), 10) || 0) % 24, a = (parseInt(e.minuteElement.value, 10) || 0) % 60, i = e.secondElement !== void 0 ? (parseInt(e.secondElement.value, 10) || 0) % 60 : 0;
      e.amPM !== void 0 && (n = j(n, e.amPM.textContent));
      var o = e.config.minTime !== void 0 || e.config.minDate && e.minDateHasTime && e.latestSelectedDateObj && P(e.latestSelectedDateObj, e.config.minDate, !0) === 0, l = e.config.maxTime !== void 0 || e.config.maxDate && e.maxDateHasTime && e.latestSelectedDateObj && P(e.latestSelectedDateObj, e.config.maxDate, !0) === 0;
      if (e.config.maxTime !== void 0 && e.config.minTime !== void 0 && e.config.minTime > e.config.maxTime) {
        var d = An(e.config.minTime.getHours(), e.config.minTime.getMinutes(), e.config.minTime.getSeconds()), g = An(e.config.maxTime.getHours(), e.config.maxTime.getMinutes(), e.config.maxTime.getSeconds()), c = An(n, a, i);
        if (c > g && c < d) {
          var m = kt(d);
          n = m[0], a = m[1], i = m[2];
        }
      } else {
        if (l) {
          var u = e.config.maxTime !== void 0 ? e.config.maxTime : e.config.maxDate;
          n = Math.min(n, u.getHours()), n === u.getHours() && (a = Math.min(a, u.getMinutes())), a === u.getMinutes() && (i = Math.min(i, u.getSeconds()));
        }
        if (o) {
          var h = e.config.minTime !== void 0 ? e.config.minTime : e.config.minDate;
          n = Math.max(n, h.getHours()), n === h.getHours() && a < h.getMinutes() && (a = h.getMinutes()), a === h.getMinutes() && (i = Math.max(i, h.getSeconds()));
        }
      }
      L(n, a, i);
    }
  }
  function T(n) {
    var a = n || e.latestSelectedDateObj;
    a && a instanceof Date && L(a.getHours(), a.getMinutes(), a.getSeconds());
  }
  function L(n, a, i) {
    e.latestSelectedDateObj !== void 0 && e.latestSelectedDateObj.setHours(n % 24, a, i || 0, 0), !(!e.hourElement || !e.minuteElement || e.isMobile) && (e.hourElement.value = F(e.config.time_24hr ? n : (12 + n) % 12 + 12 * z(n % 12 === 0)), e.minuteElement.value = F(a), e.amPM !== void 0 && (e.amPM.textContent = e.l10n.amPM[z(n >= 12)]), e.secondElement !== void 0 && (e.secondElement.value = F(i)));
  }
  function ee(n) {
    var a = N(n), i = parseInt(a.value) + (n.delta || 0);
    (i / 1e3 > 1 || n.key === "Enter" && !/[^\d]/.test(i.toString())) && te(i);
  }
  function b(n, a, i, o) {
    if (a instanceof Array)
      return a.forEach(function(l) {
        return b(n, l, i, o);
      });
    if (n instanceof Array)
      return n.forEach(function(l) {
        return b(l, a, i, o);
      });
    n.addEventListener(a, i, o), e._handlers.push({
      remove: function() {
        return n.removeEventListener(a, i, o);
      }
    });
  }
  function H() {
    x("onChange");
  }
  function se() {
    if (e.config.wrap && ["open", "close", "toggle", "clear"].forEach(function(i) {
      Array.prototype.forEach.call(e.element.querySelectorAll("[data-" + i + "]"), function(o) {
        return b(o, "click", e[i]);
      });
    }), e.isMobile) {
      tn();
      return;
    }
    var n = nt(Ge, 50);
    if (e._debouncedChange = nt(H, yt), e.daysContainer && !/iPhone|iPad|iPod/i.test(navigator.userAgent) && b(e.daysContainer, "mouseover", function(i) {
      e.config.mode === "range" && re(N(i));
    }), b(e._input, "keydown", xe), e.calendarContainer !== void 0 && b(e.calendarContainer, "keydown", xe), !e.config.inline && !e.config.static && b(window, "resize", n), window.ontouchstart !== void 0 ? b(window.document, "touchstart", pe) : b(window.document, "mousedown", pe), b(window.document, "focus", pe, { capture: !0 }), e.config.clickOpens === !0 && (b(e._input, "focus", e.open), b(e._input, "click", e.open)), e.daysContainer !== void 0 && (b(e.monthNav, "click", on), b(e.monthNav, ["keyup", "increment"], ee), b(e.daysContainer, "click", Oe)), e.timeContainer !== void 0 && e.minuteElement !== void 0 && e.hourElement !== void 0) {
      var a = function(i) {
        return N(i).select();
      };
      b(e.timeContainer, ["increment"], S), b(e.timeContainer, "blur", S, { capture: !0 }), b(e.timeContainer, "click", R), b([e.hourElement, e.minuteElement], ["focus", "click"], a), e.secondElement !== void 0 && b(e.secondElement, "focus", function() {
        return e.secondElement && e.secondElement.select();
      }), e.amPM !== void 0 && b(e.amPM, "click", function(i) {
        S(i);
      });
    }
    e.config.allowInput && b(e._input, "blur", Be);
  }
  function J(n, a) {
    var i = n !== void 0 ? e.parseDate(n) : e.latestSelectedDateObj || (e.config.minDate && e.config.minDate > e.now ? e.config.minDate : e.config.maxDate && e.config.maxDate < e.now ? e.config.maxDate : e.now), o = e.currentYear, l = e.currentMonth;
    try {
      i !== void 0 && (e.currentYear = i.getFullYear(), e.currentMonth = i.getMonth());
    } catch (d) {
      d.message = "Invalid date supplied: " + i, e.config.errorHandler(d);
    }
    a && e.currentYear !== o && (x("onYearChange"), V()), a && (e.currentYear !== o || e.currentMonth !== l) && x("onMonthChange"), e.redraw();
  }
  function R(n) {
    var a = N(n);
    ~a.className.indexOf("arrow") && B(n, a.classList.contains("arrowUp") ? 1 : -1);
  }
  function B(n, a, i) {
    var o = n && N(n), l = i || o && o.parentNode && o.parentNode.firstChild, d = ge("increment");
    d.delta = a, l && l.dispatchEvent(d);
  }
  function fe() {
    var n = window.document.createDocumentFragment();
    if (e.calendarContainer = y("div", "flatpickr-calendar"), e.calendarContainer.tabIndex = -1, !e.config.noCalendar) {
      if (n.appendChild(ze()), e.innerContainer = y("div", "flatpickr-innerContainer"), e.config.weekNumbers) {
        var a = He(), i = a.weekWrapper, o = a.weekNumbers;
        e.innerContainer.appendChild(i), e.weekNumbers = o, e.weekWrapper = i;
      }
      e.rContainer = y("div", "flatpickr-rContainer"), e.rContainer.appendChild(De()), e.daysContainer || (e.daysContainer = y("div", "flatpickr-days"), e.daysContainer.tabIndex = -1), ne(), e.rContainer.appendChild(e.daysContainer), e.innerContainer.appendChild(e.rContainer), n.appendChild(e.innerContainer);
    }
    e.config.enableTime && n.appendChild(Re()), I(e.calendarContainer, "rangeMode", e.config.mode === "range"), I(e.calendarContainer, "animate", e.config.animate === !0), I(e.calendarContainer, "multiMonth", e.config.showMonths > 1), e.calendarContainer.appendChild(n);
    var l = e.config.appendTo !== void 0 && e.config.appendTo.nodeType !== void 0;
    if ((e.config.inline || e.config.static) && (e.calendarContainer.classList.add(e.config.inline ? "inline" : "static"), e.config.inline && (!l && e.element.parentNode ? e.element.parentNode.insertBefore(e.calendarContainer, e._input.nextSibling) : e.config.appendTo !== void 0 && e.config.appendTo.appendChild(e.calendarContainer)), e.config.static)) {
      var d = y("div", "flatpickr-wrapper");
      e.element.parentNode && e.element.parentNode.insertBefore(d, e.element), d.appendChild(e.element), e.altInput && d.appendChild(e.altInput), d.appendChild(e.calendarContainer);
    }
    !e.config.static && !e.config.inline && (e.config.appendTo !== void 0 ? e.config.appendTo : window.document.body).appendChild(e.calendarContainer);
  }
  function Y(n, a, i, o) {
    var l = $(a, !0), d = y("span", n, a.getDate().toString());
    return d.dateObj = a, d.$i = o, d.setAttribute("aria-label", e.formatDate(a, e.config.ariaDateFormat)), n.indexOf("hidden") === -1 && P(a, e.now) === 0 && (e.todayDateElem = d, d.classList.add("today"), d.setAttribute("aria-current", "date")), l ? (d.tabIndex = -1, me(a) && (d.classList.add("selected"), e.selectedDateElem = d, e.config.mode === "range" && (I(d, "startRange", e.selectedDates[0] && P(a, e.selectedDates[0], !0) === 0), I(d, "endRange", e.selectedDates[1] && P(a, e.selectedDates[1], !0) === 0), n === "nextMonthDay" && d.classList.add("inRange")))) : d.classList.add("flatpickr-disabled"), e.config.mode === "range" && rn(a) && !me(a) && d.classList.add("inRange"), e.weekNumbers && e.config.showMonths === 1 && n !== "prevMonthDay" && o % 7 === 6 && e.weekNumbers.insertAdjacentHTML("beforeend", "<span class='flatpickr-day'>" + e.config.getWeek(a) + "</span>"), x("onDayCreate", d), d;
  }
  function G(n) {
    n.focus(), e.config.mode === "range" && re(n);
  }
  function U(n) {
    for (var a = n > 0 ? 0 : e.config.showMonths - 1, i = n > 0 ? e.config.showMonths : -1, o = a; o != i; o += n)
      for (var l = e.daysContainer.children[o], d = n > 0 ? 0 : l.children.length - 1, g = n > 0 ? l.children.length : -1, c = d; c != g; c += n) {
        var m = l.children[c];
        if (m.className.indexOf("hidden") === -1 && $(m.dateObj))
          return m;
      }
  }
  function ue(n, a) {
    for (var i = n.className.indexOf("Month") === -1 ? n.dateObj.getMonth() : e.currentMonth, o = a > 0 ? e.config.showMonths : -1, l = a > 0 ? 1 : -1, d = i - e.currentMonth; d != o; d += l)
      for (var g = e.daysContainer.children[d], c = i - e.currentMonth === d ? n.$i + a : a < 0 ? g.children.length - 1 : 0, m = g.children.length, u = c; u >= 0 && u < m && u != (a > 0 ? m : -1); u += l) {
        var h = g.children[u];
        if (h.className.indexOf("hidden") === -1 && $(h.dateObj) && Math.abs(n.$i - u) >= Math.abs(a))
          return G(h);
      }
    e.changeMonth(l), Z(U(l), 0);
  }
  function Z(n, a) {
    var i = f(), o = ae(i || document.body), l = n !== void 0 ? n : o ? i : e.selectedDateElem !== void 0 && ae(e.selectedDateElem) ? e.selectedDateElem : e.todayDateElem !== void 0 && ae(e.todayDateElem) ? e.todayDateElem : U(a > 0 ? 1 : -1);
    l === void 0 ? e._input.focus() : o ? ue(l, a) : G(l);
  }
  function Je(n, a) {
    for (var i = (new Date(n, a, 1).getDay() - e.l10n.firstDayOfWeek + 7) % 7, o = e.utils.getDaysInMonth((a - 1 + 12) % 12, n), l = e.utils.getDaysInMonth(a, n), d = window.document.createDocumentFragment(), g = e.config.showMonths > 1, c = g ? "prevMonthDay hidden" : "prevMonthDay", m = g ? "nextMonthDay hidden" : "nextMonthDay", u = o + 1 - i, h = 0; u <= o; u++, h++)
      d.appendChild(Y("flatpickr-day " + c, new Date(n, a - 1, u), u, h));
    for (u = 1; u <= l; u++, h++)
      d.appendChild(Y("flatpickr-day", new Date(n, a, u), u, h));
    for (var D = l + 1; D <= 42 - i && (e.config.showMonths === 1 || h % 7 !== 0); D++, h++)
      d.appendChild(Y("flatpickr-day " + m, new Date(n, a + 1, D % l), D, h));
    var _ = y("div", "dayContainer");
    return _.appendChild(d), _;
  }
  function ne() {
    if (e.daysContainer !== void 0) {
      bn(e.daysContainer), e.weekNumbers && bn(e.weekNumbers);
      for (var n = document.createDocumentFragment(), a = 0; a < e.config.showMonths; a++) {
        var i = new Date(e.currentYear, e.currentMonth, 1);
        i.setMonth(e.currentMonth + a), n.appendChild(Je(i.getFullYear(), i.getMonth()));
      }
      e.daysContainer.appendChild(n), e.days = e.daysContainer.firstChild, e.config.mode === "range" && e.selectedDates.length === 1 && re();
    }
  }
  function V() {
    if (!(e.config.showMonths > 1 || e.config.monthSelectorType !== "dropdown")) {
      var n = function(o) {
        return e.config.minDate !== void 0 && e.currentYear === e.config.minDate.getFullYear() && o < e.config.minDate.getMonth() ? !1 : !(e.config.maxDate !== void 0 && e.currentYear === e.config.maxDate.getFullYear() && o > e.config.maxDate.getMonth());
      };
      e.monthsDropdownContainer.tabIndex = -1, e.monthsDropdownContainer.innerHTML = "";
      for (var a = 0; a < 12; a++)
        if (n(a)) {
          var i = y("option", "flatpickr-monthDropdown-month");
          i.value = new Date(e.currentYear, a).getMonth().toString(), i.textContent = vn(a, e.config.shorthandCurrentMonth, e.l10n), i.tabIndex = -1, e.currentMonth === a && (i.selected = !0), e.monthsDropdownContainer.appendChild(i);
        }
    }
  }
  function We() {
    var n = y("div", "flatpickr-month"), a = window.document.createDocumentFragment(), i;
    e.config.showMonths > 1 || e.config.monthSelectorType === "static" ? i = y("span", "cur-month") : (e.monthsDropdownContainer = y("select", "flatpickr-monthDropdown-months"), e.monthsDropdownContainer.setAttribute("aria-label", e.l10n.monthAriaLabel), b(e.monthsDropdownContainer, "change", function(g) {
      var c = N(g), m = parseInt(c.value, 10);
      e.changeMonth(m - e.currentMonth), x("onMonthChange");
    }), V(), i = e.monthsDropdownContainer);
    var o = kn("cur-year", { tabindex: "-1" }), l = o.getElementsByTagName("input")[0];
    l.setAttribute("aria-label", e.l10n.yearAriaLabel), e.config.minDate && l.setAttribute("min", e.config.minDate.getFullYear().toString()), e.config.maxDate && (l.setAttribute("max", e.config.maxDate.getFullYear().toString()), l.disabled = !!e.config.minDate && e.config.minDate.getFullYear() === e.config.maxDate.getFullYear());
    var d = y("div", "flatpickr-current-month");
    return d.appendChild(i), d.appendChild(o), a.appendChild(d), n.appendChild(a), {
      container: n,
      yearElement: l,
      monthElement: i
    };
  }
  function ye() {
    bn(e.monthNav), e.monthNav.appendChild(e.prevMonthNav), e.config.showMonths && (e.yearElements = [], e.monthElements = []);
    for (var n = e.config.showMonths; n--; ) {
      var a = We();
      e.yearElements.push(a.yearElement), e.monthElements.push(a.monthElement), e.monthNav.appendChild(a.container);
    }
    e.monthNav.appendChild(e.nextMonthNav);
  }
  function ze() {
    return e.monthNav = y("div", "flatpickr-months"), e.yearElements = [], e.monthElements = [], e.prevMonthNav = y("span", "flatpickr-prev-month"), e.prevMonthNav.innerHTML = e.config.prevArrow, e.nextMonthNav = y("span", "flatpickr-next-month"), e.nextMonthNav.innerHTML = e.config.nextArrow, ye(), Object.defineProperty(e, "_hidePrevMonthArrow", {
      get: function() {
        return e.__hidePrevMonthArrow;
      },
      set: function(n) {
        e.__hidePrevMonthArrow !== n && (I(e.prevMonthNav, "flatpickr-disabled", n), e.__hidePrevMonthArrow = n);
      }
    }), Object.defineProperty(e, "_hideNextMonthArrow", {
      get: function() {
        return e.__hideNextMonthArrow;
      },
      set: function(n) {
        e.__hideNextMonthArrow !== n && (I(e.nextMonthNav, "flatpickr-disabled", n), e.__hideNextMonthArrow = n);
      }
    }), e.currentYearElement = e.yearElements[0], le(), e.monthNav;
  }
  function Re() {
    e.calendarContainer.classList.add("hasTime"), e.config.noCalendar && e.calendarContainer.classList.add("noCalendar");
    var n = Tn(e.config);
    e.timeContainer = y("div", "flatpickr-time"), e.timeContainer.tabIndex = -1;
    var a = y("span", "flatpickr-time-separator", ":"), i = kn("flatpickr-hour", {
      "aria-label": e.l10n.hourAriaLabel
    });
    e.hourElement = i.getElementsByTagName("input")[0];
    var o = kn("flatpickr-minute", {
      "aria-label": e.l10n.minuteAriaLabel
    });
    if (e.minuteElement = o.getElementsByTagName("input")[0], e.hourElement.tabIndex = e.minuteElement.tabIndex = -1, e.hourElement.value = F(e.latestSelectedDateObj ? e.latestSelectedDateObj.getHours() : e.config.time_24hr ? n.hours : k(n.hours)), e.minuteElement.value = F(e.latestSelectedDateObj ? e.latestSelectedDateObj.getMinutes() : n.minutes), e.hourElement.setAttribute("step", e.config.hourIncrement.toString()), e.minuteElement.setAttribute("step", e.config.minuteIncrement.toString()), e.hourElement.setAttribute("min", e.config.time_24hr ? "0" : "1"), e.hourElement.setAttribute("max", e.config.time_24hr ? "23" : "12"), e.hourElement.setAttribute("maxlength", "2"), e.minuteElement.setAttribute("min", "0"), e.minuteElement.setAttribute("max", "59"), e.minuteElement.setAttribute("maxlength", "2"), e.timeContainer.appendChild(i), e.timeContainer.appendChild(a), e.timeContainer.appendChild(o), e.config.time_24hr && e.timeContainer.classList.add("time24hr"), e.config.enableSeconds) {
      e.timeContainer.classList.add("hasSeconds");
      var l = kn("flatpickr-second");
      e.secondElement = l.getElementsByTagName("input")[0], e.secondElement.value = F(e.latestSelectedDateObj ? e.latestSelectedDateObj.getSeconds() : n.seconds), e.secondElement.setAttribute("step", e.minuteElement.getAttribute("step")), e.secondElement.setAttribute("min", "0"), e.secondElement.setAttribute("max", "59"), e.secondElement.setAttribute("maxlength", "2"), e.timeContainer.appendChild(y("span", "flatpickr-time-separator", ":")), e.timeContainer.appendChild(l);
    }
    return e.config.time_24hr || (e.amPM = y("span", "flatpickr-am-pm", e.l10n.amPM[z((e.latestSelectedDateObj ? e.hourElement.value : e.config.defaultHour) > 11)]), e.amPM.title = e.l10n.toggleTitle, e.amPM.tabIndex = -1, e.timeContainer.appendChild(e.amPM)), e.timeContainer;
  }
  function De() {
    e.weekdayContainer ? bn(e.weekdayContainer) : e.weekdayContainer = y("div", "flatpickr-weekdays");
    for (var n = e.config.showMonths; n--; ) {
      var a = y("div", "flatpickr-weekdaycontainer");
      e.weekdayContainer.appendChild(a);
    }
    return Me(), e.weekdayContainer;
  }
  function Me() {
    if (e.weekdayContainer) {
      var n = e.l10n.firstDayOfWeek, a = tt(e.l10n.weekdays.shorthand);
      n > 0 && n < a.length && (a = tt(a.splice(n, a.length), a.splice(0, n)));
      for (var i = e.config.showMonths; i--; )
        e.weekdayContainer.children[i].innerHTML = `
      <span class='flatpickr-weekday'>
        ` + a.join("</span><span class='flatpickr-weekday'>") + `
      </span>
      `;
    }
  }
  function He() {
    e.calendarContainer.classList.add("hasWeeks");
    var n = y("div", "flatpickr-weekwrapper");
    n.appendChild(y("span", "flatpickr-weekday", e.l10n.weekAbbreviation));
    var a = y("div", "flatpickr-weeks");
    return n.appendChild(a), {
      weekWrapper: n,
      weekNumbers: a
    };
  }
  function ce(n, a) {
    a === void 0 && (a = !0);
    var i = a ? n : n - e.currentMonth;
    i < 0 && e._hidePrevMonthArrow === !0 || i > 0 && e._hideNextMonthArrow === !0 || (e.currentMonth += i, (e.currentMonth < 0 || e.currentMonth > 11) && (e.currentYear += e.currentMonth > 11 ? 1 : -1, e.currentMonth = (e.currentMonth + 12) % 12, x("onYearChange"), V()), ne(), x("onMonthChange"), le());
  }
  function Ye(n, a) {
    if (n === void 0 && (n = !0), a === void 0 && (a = !0), e.input.value = "", e.altInput !== void 0 && (e.altInput.value = ""), e.mobileInput !== void 0 && (e.mobileInput.value = ""), e.selectedDates = [], e.latestSelectedDateObj = void 0, a === !0 && (e.currentYear = e._initialDate.getFullYear(), e.currentMonth = e._initialDate.getMonth()), e.config.enableTime === !0) {
      var i = Tn(e.config), o = i.hours, l = i.minutes, d = i.seconds;
      L(o, l, d);
    }
    e.redraw(), n && x("onChange");
  }
  function $e() {
    e.isOpen = !1, e.isMobile || (e.calendarContainer !== void 0 && e.calendarContainer.classList.remove("open"), e._input !== void 0 && e._input.classList.remove("active")), x("onClose");
  }
  function Ke() {
    e.config !== void 0 && x("onDestroy");
    for (var n = e._handlers.length; n--; )
      e._handlers[n].remove();
    if (e._handlers = [], e.mobileInput)
      e.mobileInput.parentNode && e.mobileInput.parentNode.removeChild(e.mobileInput), e.mobileInput = void 0;
    else if (e.calendarContainer && e.calendarContainer.parentNode)
      if (e.config.static && e.calendarContainer.parentNode) {
        var a = e.calendarContainer.parentNode;
        if (a.lastChild && a.removeChild(a.lastChild), a.parentNode) {
          for (; a.firstChild; )
            a.parentNode.insertBefore(a.firstChild, a);
          a.parentNode.removeChild(a);
        }
      } else
        e.calendarContainer.parentNode.removeChild(e.calendarContainer);
    e.altInput && (e.input.type = "text", e.altInput.parentNode && e.altInput.parentNode.removeChild(e.altInput), delete e.altInput), e.input && (e.input.type = e.input._type, e.input.classList.remove("flatpickr-input"), e.input.removeAttribute("readonly")), [
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
    ].forEach(function(i) {
      try {
        delete e[i];
      } catch {
      }
    });
  }
  function Q(n) {
    return e.calendarContainer.contains(n);
  }
  function pe(n) {
    if (e.isOpen && !e.config.inline) {
      var a = N(n), i = Q(a), o = a === e.input || a === e.altInput || e.element.contains(a) || n.path && n.path.indexOf && (~n.path.indexOf(e.input) || ~n.path.indexOf(e.altInput)), l = !o && !i && !Q(n.relatedTarget), d = !e.config.ignoredFocusElements.some(function(g) {
        return g.contains(a);
      });
      l && d && (e.config.allowInput && e.setDate(e._input.value, !1, e.config.altInput ? e.config.altFormat : e.config.dateFormat), e.timeContainer !== void 0 && e.minuteElement !== void 0 && e.hourElement !== void 0 && e.input.value !== "" && e.input.value !== void 0 && S(), e.close(), e.config && e.config.mode === "range" && e.selectedDates.length === 1 && e.clear(!1));
    }
  }
  function te(n) {
    if (!(!n || e.config.minDate && n < e.config.minDate.getFullYear() || e.config.maxDate && n > e.config.maxDate.getFullYear())) {
      var a = n, i = e.currentYear !== a;
      e.currentYear = a || e.currentYear, e.config.maxDate && e.currentYear === e.config.maxDate.getFullYear() ? e.currentMonth = Math.min(e.config.maxDate.getMonth(), e.currentMonth) : e.config.minDate && e.currentYear === e.config.minDate.getFullYear() && (e.currentMonth = Math.max(e.config.minDate.getMonth(), e.currentMonth)), i && (e.redraw(), x("onYearChange"), V());
    }
  }
  function $(n, a) {
    var i;
    a === void 0 && (a = !0);
    var o = e.parseDate(n, void 0, a);
    if (e.config.minDate && o && P(o, e.config.minDate, a !== void 0 ? a : !e.minDateHasTime) < 0 || e.config.maxDate && o && P(o, e.config.maxDate, a !== void 0 ? a : !e.maxDateHasTime) > 0)
      return !1;
    if (!e.config.enable && e.config.disable.length === 0)
      return !0;
    if (o === void 0)
      return !1;
    for (var l = !!e.config.enable, d = (i = e.config.enable) !== null && i !== void 0 ? i : e.config.disable, g = 0, c = void 0; g < d.length; g++) {
      if (c = d[g], typeof c == "function" && c(o))
        return l;
      if (c instanceof Date && o !== void 0 && c.getTime() === o.getTime())
        return l;
      if (typeof c == "string") {
        var m = e.parseDate(c, void 0, !0);
        return m && m.getTime() === o.getTime() ? l : !l;
      } else if (typeof c == "object" && o !== void 0 && c.from && c.to && o.getTime() >= c.from.getTime() && o.getTime() <= c.to.getTime())
        return l;
    }
    return !l;
  }
  function ae(n) {
    return e.daysContainer !== void 0 ? n.className.indexOf("hidden") === -1 && n.className.indexOf("flatpickr-disabled") === -1 && e.daysContainer.contains(n) : !1;
  }
  function Be(n) {
    var a = n.target === e._input, i = e._input.value.trimEnd() !== we();
    a && i && !(n.relatedTarget && Q(n.relatedTarget)) && e.setDate(e._input.value, !0, n.target === e.altInput ? e.config.altFormat : e.config.dateFormat);
  }
  function xe(n) {
    var a = N(n), i = e.config.wrap ? t.contains(a) : a === e._input, o = e.config.allowInput, l = e.isOpen && (!o || !i), d = e.config.inline && i && !o;
    if (n.keyCode === 13 && i) {
      if (o)
        return e.setDate(e._input.value, !0, a === e.altInput ? e.config.altFormat : e.config.dateFormat), e.close(), a.blur();
      e.open();
    } else if (Q(a) || l || d) {
      var g = !!e.timeContainer && e.timeContainer.contains(a);
      switch (n.keyCode) {
        case 13:
          g ? (n.preventDefault(), S(), he()) : Oe(n);
          break;
        case 27:
          n.preventDefault(), he();
          break;
        case 8:
        case 46:
          i && !e.config.allowInput && (n.preventDefault(), e.clear());
          break;
        case 37:
        case 39:
          if (!g && !i) {
            n.preventDefault();
            var c = f();
            if (e.daysContainer !== void 0 && (o === !1 || c && ae(c))) {
              var m = n.keyCode === 39 ? 1 : -1;
              n.ctrlKey ? (n.stopPropagation(), ce(m), Z(U(1), 0)) : Z(void 0, m);
            }
          } else e.hourElement && e.hourElement.focus();
          break;
        case 38:
        case 40:
          n.preventDefault();
          var u = n.keyCode === 40 ? 1 : -1;
          e.daysContainer && a.$i !== void 0 || a === e.input || a === e.altInput ? n.ctrlKey ? (n.stopPropagation(), te(e.currentYear - u), Z(U(1), 0)) : g || Z(void 0, u * 7) : a === e.currentYearElement ? te(e.currentYear - u) : e.config.enableTime && (!g && e.hourElement && e.hourElement.focus(), S(n), e._debouncedChange());
          break;
        case 9:
          if (g) {
            var h = [
              e.hourElement,
              e.minuteElement,
              e.secondElement,
              e.amPM
            ].concat(e.pluginElements).filter(function(O) {
              return O;
            }), D = h.indexOf(a);
            if (D !== -1) {
              var _ = h[D + (n.shiftKey ? -1 : 1)];
              n.preventDefault(), (_ || e._input).focus();
            }
          } else !e.config.noCalendar && e.daysContainer && e.daysContainer.contains(a) && n.shiftKey && (n.preventDefault(), e._input.focus());
          break;
      }
    }
    if (e.amPM !== void 0 && a === e.amPM)
      switch (n.key) {
        case e.l10n.amPM[0].charAt(0):
        case e.l10n.amPM[0].charAt(0).toLowerCase():
          e.amPM.textContent = e.l10n.amPM[0], A(), W();
          break;
        case e.l10n.amPM[1].charAt(0):
        case e.l10n.amPM[1].charAt(0).toLowerCase():
          e.amPM.textContent = e.l10n.amPM[1], A(), W();
          break;
      }
    (i || Q(a)) && x("onKeyDown", n);
  }
  function re(n, a) {
    if (a === void 0 && (a = "flatpickr-day"), !(e.selectedDates.length !== 1 || n && (!n.classList.contains(a) || n.classList.contains("flatpickr-disabled")))) {
      for (var i = n ? n.dateObj.getTime() : e.days.firstElementChild.dateObj.getTime(), o = e.parseDate(e.selectedDates[0], void 0, !0).getTime(), l = Math.min(i, e.selectedDates[0].getTime()), d = Math.max(i, e.selectedDates[0].getTime()), g = !1, c = 0, m = 0, u = l; u < d; u += vt.DAY)
        $(new Date(u), !0) || (g = g || u > l && u < d, u < o && (!c || u > c) ? c = u : u > o && (!m || u < m) && (m = u));
      var h = Array.from(e.rContainer.querySelectorAll("*:nth-child(-n+" + e.config.showMonths + ") > ." + a));
      h.forEach(function(D) {
        var _ = D.dateObj, O = _.getTime(), X = c > 0 && O < c || m > 0 && O > m;
        if (X) {
          D.classList.add("notAllowed"), ["inRange", "startRange", "endRange"].forEach(function(q) {
            D.classList.remove(q);
          });
          return;
        } else if (g && !X)
          return;
        ["startRange", "inRange", "endRange", "notAllowed"].forEach(function(q) {
          D.classList.remove(q);
        }), n !== void 0 && (n.classList.add(i <= e.selectedDates[0].getTime() ? "startRange" : "endRange"), o < i && O === o ? D.classList.add("startRange") : o > i && O === o && D.classList.add("endRange"), O >= c && (m === 0 || O <= m) && bt(O, o, i) && D.classList.add("inRange"));
      });
    }
  }
  function Ge() {
    e.isOpen && !e.config.static && !e.config.inline && ie();
  }
  function Ue(n, a) {
    if (a === void 0 && (a = e._positionElement), e.isMobile === !0) {
      if (n) {
        n.preventDefault();
        var i = N(n);
        i && i.blur();
      }
      e.mobileInput !== void 0 && (e.mobileInput.focus(), e.mobileInput.click()), x("onOpen");
      return;
    } else if (e._input.disabled || e.config.inline)
      return;
    var o = e.isOpen;
    e.isOpen = !0, o || (e.calendarContainer.classList.add("open"), e._input.classList.add("active"), x("onOpen"), ie(a)), e.config.enableTime === !0 && e.config.noCalendar === !0 && e.config.allowInput === !1 && (n === void 0 || !e.timeContainer.contains(n.relatedTarget)) && setTimeout(function() {
      return e.hourElement.select();
    }, 50);
  }
  function Se(n) {
    return function(a) {
      var i = e.config["_" + n + "Date"] = e.parseDate(a, e.config.dateFormat), o = e.config["_" + (n === "min" ? "max" : "min") + "Date"];
      i !== void 0 && (e[n === "min" ? "minDateHasTime" : "maxDateHasTime"] = i.getHours() > 0 || i.getMinutes() > 0 || i.getSeconds() > 0), e.selectedDates && (e.selectedDates = e.selectedDates.filter(function(l) {
        return $(l);
      }), !e.selectedDates.length && n === "min" && T(i), W()), e.daysContainer && (Te(), i !== void 0 ? e.currentYearElement[n] = i.getFullYear().toString() : e.currentYearElement.removeAttribute(n), e.currentYearElement.disabled = !!o && i !== void 0 && o.getFullYear() === i.getFullYear());
    };
  }
  function Ve() {
    var n = [
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
    ], a = E(E({}, JSON.parse(JSON.stringify(t.dataset || {}))), r), i = {};
    e.config.parseDate = a.parseDate, e.config.formatDate = a.formatDate, Object.defineProperty(e.config, "enable", {
      get: function() {
        return e.config._enable;
      },
      set: function(h) {
        e.config._enable = Ie(h);
      }
    }), Object.defineProperty(e.config, "disable", {
      get: function() {
        return e.config._disable;
      },
      set: function(h) {
        e.config._disable = Ie(h);
      }
    });
    var o = a.mode === "time";
    if (!a.dateFormat && (a.enableTime || o)) {
      var l = C.defaultConfig.dateFormat || ke.dateFormat;
      i.dateFormat = a.noCalendar || o ? "H:i" + (a.enableSeconds ? ":S" : "") : l + " H:i" + (a.enableSeconds ? ":S" : "");
    }
    if (a.altInput && (a.enableTime || o) && !a.altFormat) {
      var d = C.defaultConfig.altFormat || ke.altFormat;
      i.altFormat = a.noCalendar || o ? "h:i" + (a.enableSeconds ? ":S K" : " K") : d + (" h:i" + (a.enableSeconds ? ":S" : "") + " K");
    }
    Object.defineProperty(e.config, "minDate", {
      get: function() {
        return e.config._minDate;
      },
      set: Se("min")
    }), Object.defineProperty(e.config, "maxDate", {
      get: function() {
        return e.config._maxDate;
      },
      set: Se("max")
    });
    var g = function(h) {
      return function(D) {
        e.config[h === "min" ? "_minTime" : "_maxTime"] = e.parseDate(D, "H:i:S");
      };
    };
    Object.defineProperty(e.config, "minTime", {
      get: function() {
        return e.config._minTime;
      },
      set: g("min")
    }), Object.defineProperty(e.config, "maxTime", {
      get: function() {
        return e.config._maxTime;
      },
      set: g("max")
    }), a.mode === "time" && (e.config.noCalendar = !0, e.config.enableTime = !0), Object.assign(e.config, i, a);
    for (var c = 0; c < n.length; c++)
      e.config[n[c]] = e.config[n[c]] === !0 || e.config[n[c]] === "true";
    xn.filter(function(h) {
      return e.config[h] !== void 0;
    }).forEach(function(h) {
      e.config[h] = Sn(e.config[h] || []).map(w);
    }), e.isMobile = !e.config.disableMobile && !e.config.inline && e.config.mode === "single" && !e.config.disable.length && !e.config.enable && !e.config.weekNumbers && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    for (var c = 0; c < e.config.plugins.length; c++) {
      var m = e.config.plugins[c](e) || {};
      for (var u in m)
        xn.indexOf(u) > -1 ? e.config[u] = Sn(m[u]).map(w).concat(e.config[u]) : typeof a[u] > "u" && (e.config[u] = m[u]);
    }
    a.altInputClass || (e.config.altInputClass = Ce().className + " " + e.config.altInputClass), x("onParseConfig");
  }
  function Ce() {
    return e.config.wrap ? t.querySelector("[data-input]") : t;
  }
  function Ae() {
    typeof e.config.locale != "object" && typeof C.l10ns[e.config.locale] > "u" && e.config.errorHandler(new Error("flatpickr: invalid locale " + e.config.locale)), e.l10n = E(E({}, C.l10ns.default), typeof e.config.locale == "object" ? e.config.locale : e.config.locale !== "default" ? C.l10ns[e.config.locale] : void 0), de.D = "(" + e.l10n.weekdays.shorthand.join("|") + ")", de.l = "(" + e.l10n.weekdays.longhand.join("|") + ")", de.M = "(" + e.l10n.months.shorthand.join("|") + ")", de.F = "(" + e.l10n.months.longhand.join("|") + ")", de.K = "(" + e.l10n.amPM[0] + "|" + e.l10n.amPM[1] + "|" + e.l10n.amPM[0].toLowerCase() + "|" + e.l10n.amPM[1].toLowerCase() + ")";
    var n = E(E({}, r), JSON.parse(JSON.stringify(t.dataset || {})));
    n.time_24hr === void 0 && C.defaultConfig.time_24hr === void 0 && (e.config.time_24hr = e.l10n.time_24hr), e.formatDate = dt(e), e.parseDate = En({ config: e.config, l10n: e.l10n });
  }
  function ie(n) {
    if (typeof e.config.position == "function")
      return void e.config.position(e, n);
    if (e.calendarContainer !== void 0) {
      x("onPreCalendarPosition");
      var a = n || e._positionElement, i = Array.prototype.reduce.call(e.calendarContainer.children, (function(gn, mn) {
        return gn + mn.offsetHeight;
      }), 0), o = e.calendarContainer.offsetWidth, l = e.config.position.split(" "), d = l[0], g = l.length > 1 ? l[1] : null, c = a.getBoundingClientRect(), m = window.innerHeight - c.bottom, u = d === "above" || d !== "below" && m < i && c.top > i, h = window.pageYOffset + c.top + (u ? -i - 2 : a.offsetHeight + 2);
      if (I(e.calendarContainer, "arrowTop", !u), I(e.calendarContainer, "arrowBottom", u), !e.config.inline) {
        var D = window.pageXOffset + c.left, _ = !1, O = !1;
        g === "center" ? (D -= (o - c.width) / 2, _ = !0) : g === "right" && (D -= o - c.width, O = !0), I(e.calendarContainer, "arrowLeft", !_ && !O), I(e.calendarContainer, "arrowCenter", _), I(e.calendarContainer, "arrowRight", O);
        var X = window.document.body.offsetWidth - (window.pageXOffset + c.right), q = D + o > window.document.body.offsetWidth, dn = X + o > window.document.body.offsetWidth;
        if (I(e.calendarContainer, "rightMost", q), !e.config.static)
          if (e.calendarContainer.style.top = h + "px", !q)
            e.calendarContainer.style.left = D + "px", e.calendarContainer.style.right = "auto";
          else if (!dn)
            e.calendarContainer.style.left = "auto", e.calendarContainer.style.right = X + "px";
          else {
            var be = qe();
            if (be === void 0)
              return;
            var sn = window.document.body.offsetWidth, fn = Math.max(0, sn / 2 - o / 2), un = ".flatpickr-calendar.centerMost:before", cn = ".flatpickr-calendar.centerMost:after", pn = be.cssRules.length, hn = "{left:" + c.left + "px;right:auto;}";
            I(e.calendarContainer, "rightMost", !1), I(e.calendarContainer, "centerMost", !0), be.insertRule(un + "," + cn + hn, pn), e.calendarContainer.style.left = fn + "px", e.calendarContainer.style.right = "auto";
          }
      }
    }
  }
  function qe() {
    for (var n = null, a = 0; a < document.styleSheets.length; a++) {
      var i = document.styleSheets[a];
      if (i.cssRules) {
        try {
          i.cssRules;
        } catch {
          continue;
        }
        n = i;
        break;
      }
    }
    return n ?? Ze();
  }
  function Ze() {
    var n = document.createElement("style");
    return document.head.appendChild(n), n.sheet;
  }
  function Te() {
    e.config.noCalendar || e.isMobile || (V(), le(), ne());
  }
  function he() {
    e._input.focus(), window.navigator.userAgent.indexOf("MSIE") !== -1 || navigator.msMaxTouchPoints !== void 0 ? setTimeout(e.close, 0) : e.close();
  }
  function Oe(n) {
    n.preventDefault(), n.stopPropagation();
    var a = function(h) {
      return h.classList && h.classList.contains("flatpickr-day") && !h.classList.contains("flatpickr-disabled") && !h.classList.contains("notAllowed");
    }, i = lt(N(n), a);
    if (i !== void 0) {
      var o = i, l = e.latestSelectedDateObj = new Date(o.dateObj.getTime()), d = (l.getMonth() < e.currentMonth || l.getMonth() > e.currentMonth + e.config.showMonths - 1) && e.config.mode !== "range";
      if (e.selectedDateElem = o, e.config.mode === "single")
        e.selectedDates = [l];
      else if (e.config.mode === "multiple") {
        var g = me(l);
        g ? e.selectedDates.splice(parseInt(g), 1) : e.selectedDates.push(l);
      } else e.config.mode === "range" && (e.selectedDates.length === 2 && e.clear(!1, !1), e.latestSelectedDateObj = l, e.selectedDates.push(l), P(l, e.selectedDates[0], !0) !== 0 && e.selectedDates.sort(function(h, D) {
        return h.getTime() - D.getTime();
      }));
      if (A(), d) {
        var c = e.currentYear !== l.getFullYear();
        e.currentYear = l.getFullYear(), e.currentMonth = l.getMonth(), c && (x("onYearChange"), V()), x("onMonthChange");
      }
      if (le(), ne(), W(), !d && e.config.mode !== "range" && e.config.showMonths === 1 ? G(o) : e.selectedDateElem !== void 0 && e.hourElement === void 0 && e.selectedDateElem && e.selectedDateElem.focus(), e.hourElement !== void 0 && e.hourElement !== void 0 && e.hourElement.focus(), e.config.closeOnSelect) {
        var m = e.config.mode === "single" && !e.config.enableTime, u = e.config.mode === "range" && e.selectedDates.length === 2 && !e.config.enableTime;
        (m || u) && he();
      }
      H();
    }
  }
  var oe = {
    locale: [Ae, Me],
    showMonths: [ye, M, De],
    minDate: [J],
    maxDate: [J],
    positionElement: [Fe],
    clickOpens: [
      function() {
        e.config.clickOpens === !0 ? (b(e._input, "focus", e.open), b(e._input, "click", e.open)) : (e._input.removeEventListener("focus", e.open), e._input.removeEventListener("click", e.open));
      }
    ]
  };
  function Qe(n, a) {
    if (n !== null && typeof n == "object") {
      Object.assign(e.config, n);
      for (var i in n)
        oe[i] !== void 0 && oe[i].forEach(function(o) {
          return o();
        });
    } else
      e.config[n] = a, oe[n] !== void 0 ? oe[n].forEach(function(o) {
        return o();
      }) : xn.indexOf(n) > -1 && (e.config[n] = Sn(a));
    e.redraw(), W(!0);
  }
  function Ee(n, a) {
    var i = [];
    if (n instanceof Array)
      i = n.map(function(o) {
        return e.parseDate(o, a);
      });
    else if (n instanceof Date || typeof n == "number")
      i = [e.parseDate(n, a)];
    else if (typeof n == "string")
      switch (e.config.mode) {
        case "single":
        case "time":
          i = [e.parseDate(n, a)];
          break;
        case "multiple":
          i = n.split(e.config.conjunction).map(function(o) {
            return e.parseDate(o, a);
          });
          break;
        case "range":
          i = n.split(e.l10n.rangeSeparator).map(function(o) {
            return e.parseDate(o, a);
          });
          break;
      }
    else
      e.config.errorHandler(new Error("Invalid date supplied: " + JSON.stringify(n)));
    e.selectedDates = e.config.allowInvalidPreload ? i : i.filter(function(o) {
      return o instanceof Date && $(o, !1);
    }), e.config.mode === "range" && e.selectedDates.sort(function(o, l) {
      return o.getTime() - l.getTime();
    });
  }
  function Xe(n, a, i) {
    if (a === void 0 && (a = !1), i === void 0 && (i = e.config.dateFormat), n !== 0 && !n || n instanceof Array && n.length === 0)
      return e.clear(a);
    Ee(n, i), e.latestSelectedDateObj = e.selectedDates[e.selectedDates.length - 1], e.redraw(), J(void 0, a), T(), e.selectedDates.length === 0 && e.clear(!1), W(a), a && x("onChange");
  }
  function Ie(n) {
    return n.slice().map(function(a) {
      return typeof a == "string" || typeof a == "number" || a instanceof Date ? e.parseDate(a, void 0, !0) : a && typeof a == "object" && a.from && a.to ? {
        from: e.parseDate(a.from, void 0),
        to: e.parseDate(a.to, void 0)
      } : a;
    }).filter(function(a) {
      return a;
    });
  }
  function en() {
    e.selectedDates = [], e.now = e.parseDate(e.config.now) || /* @__PURE__ */ new Date();
    var n = e.config.defaultDate || ((e.input.nodeName === "INPUT" || e.input.nodeName === "TEXTAREA") && e.input.placeholder && e.input.value === e.input.placeholder ? null : e.input.value);
    n && Ee(n, e.config.dateFormat), e._initialDate = e.selectedDates.length > 0 ? e.selectedDates[0] : e.config.minDate && e.config.minDate.getTime() > e.now.getTime() ? e.config.minDate : e.config.maxDate && e.config.maxDate.getTime() < e.now.getTime() ? e.config.maxDate : e.now, e.currentYear = e._initialDate.getFullYear(), e.currentMonth = e._initialDate.getMonth(), e.selectedDates.length > 0 && (e.latestSelectedDateObj = e.selectedDates[0]), e.config.minTime !== void 0 && (e.config.minTime = e.parseDate(e.config.minTime, "H:i")), e.config.maxTime !== void 0 && (e.config.maxTime = e.parseDate(e.config.maxTime, "H:i")), e.minDateHasTime = !!e.config.minDate && (e.config.minDate.getHours() > 0 || e.config.minDate.getMinutes() > 0 || e.config.minDate.getSeconds() > 0), e.maxDateHasTime = !!e.config.maxDate && (e.config.maxDate.getHours() > 0 || e.config.maxDate.getMinutes() > 0 || e.config.maxDate.getSeconds() > 0);
  }
  function nn() {
    if (e.input = Ce(), !e.input) {
      e.config.errorHandler(new Error("Invalid input element specified"));
      return;
    }
    e.input._type = e.input.type, e.input.type = "text", e.input.classList.add("flatpickr-input"), e._input = e.input, e.config.altInput && (e.altInput = y(e.input.nodeName, e.config.altInputClass), e._input = e.altInput, e.altInput.placeholder = e.input.placeholder, e.altInput.disabled = e.input.disabled, e.altInput.required = e.input.required, e.altInput.tabIndex = e.input.tabIndex, e.altInput.type = "text", e.input.setAttribute("type", "hidden"), !e.config.static && e.input.parentNode && e.input.parentNode.insertBefore(e.altInput, e.input.nextSibling)), e.config.allowInput || e._input.setAttribute("readonly", "readonly"), Fe();
  }
  function Fe() {
    e._positionElement = e.config.positionElement || e._input;
  }
  function tn() {
    var n = e.config.enableTime ? e.config.noCalendar ? "time" : "datetime-local" : "date";
    e.mobileInput = y("input", e.input.className + " flatpickr-mobile"), e.mobileInput.tabIndex = 1, e.mobileInput.type = n, e.mobileInput.disabled = e.input.disabled, e.mobileInput.required = e.input.required, e.mobileInput.placeholder = e.input.placeholder, e.mobileFormatStr = n === "datetime-local" ? "Y-m-d\\TH:i:S" : n === "date" ? "Y-m-d" : "H:i:S", e.selectedDates.length > 0 && (e.mobileInput.defaultValue = e.mobileInput.value = e.formatDate(e.selectedDates[0], e.mobileFormatStr)), e.config.minDate && (e.mobileInput.min = e.formatDate(e.config.minDate, "Y-m-d")), e.config.maxDate && (e.mobileInput.max = e.formatDate(e.config.maxDate, "Y-m-d")), e.input.getAttribute("step") && (e.mobileInput.step = String(e.input.getAttribute("step"))), e.input.type = "hidden", e.altInput !== void 0 && (e.altInput.type = "hidden");
    try {
      e.input.parentNode && e.input.parentNode.insertBefore(e.mobileInput, e.input.nextSibling);
    } catch {
    }
    b(e.mobileInput, "change", function(a) {
      e.setDate(N(a).value, !1, e.mobileFormatStr), x("onChange"), x("onClose");
    });
  }
  function an(n) {
    if (e.isOpen === !0)
      return e.close();
    e.open(n);
  }
  function x(n, a) {
    if (e.config !== void 0) {
      var i = e.config[n];
      if (i !== void 0 && i.length > 0)
        for (var o = 0; i[o] && o < i.length; o++)
          i[o](e.selectedDates, e.input.value, e, a);
      n === "onChange" && (e.input.dispatchEvent(ge("change")), e.input.dispatchEvent(ge("input")));
    }
  }
  function ge(n) {
    var a = document.createEvent("Event");
    return a.initEvent(n, !0, !0), a;
  }
  function me(n) {
    for (var a = 0; a < e.selectedDates.length; a++) {
      var i = e.selectedDates[a];
      if (i instanceof Date && P(i, n) === 0)
        return "" + a;
    }
    return !1;
  }
  function rn(n) {
    return e.config.mode !== "range" || e.selectedDates.length < 2 ? !1 : P(n, e.selectedDates[0]) >= 0 && P(n, e.selectedDates[1]) <= 0;
  }
  function le() {
    e.config.noCalendar || e.isMobile || !e.monthNav || (e.yearElements.forEach(function(n, a) {
      var i = new Date(e.currentYear, e.currentMonth, 1);
      i.setMonth(e.currentMonth + a), e.config.showMonths > 1 || e.config.monthSelectorType === "static" ? e.monthElements[a].textContent = vn(i.getMonth(), e.config.shorthandCurrentMonth, e.l10n) + " " : e.monthsDropdownContainer.value = i.getMonth().toString(), n.value = i.getFullYear().toString();
    }), e._hidePrevMonthArrow = e.config.minDate !== void 0 && (e.currentYear === e.config.minDate.getFullYear() ? e.currentMonth <= e.config.minDate.getMonth() : e.currentYear < e.config.minDate.getFullYear()), e._hideNextMonthArrow = e.config.maxDate !== void 0 && (e.currentYear === e.config.maxDate.getFullYear() ? e.currentMonth + 1 > e.config.maxDate.getMonth() : e.currentYear > e.config.maxDate.getFullYear()));
  }
  function we(n) {
    var a = n || (e.config.altInput ? e.config.altFormat : e.config.dateFormat);
    return e.selectedDates.map(function(i) {
      return e.formatDate(i, a);
    }).filter(function(i, o, l) {
      return e.config.mode !== "range" || e.config.enableTime || l.indexOf(i) === o;
    }).join(e.config.mode !== "range" ? e.config.conjunction : e.l10n.rangeSeparator);
  }
  function W(n) {
    n === void 0 && (n = !0), e.mobileInput !== void 0 && e.mobileFormatStr && (e.mobileInput.value = e.latestSelectedDateObj !== void 0 ? e.formatDate(e.latestSelectedDateObj, e.mobileFormatStr) : ""), e.input.value = we(e.config.dateFormat), e.altInput !== void 0 && (e.altInput.value = we(e.config.altFormat)), n !== !1 && x("onValueUpdate");
  }
  function on(n) {
    var a = N(n), i = e.prevMonthNav.contains(a), o = e.nextMonthNav.contains(a);
    i || o ? ce(i ? -1 : 1) : e.yearElements.indexOf(a) >= 0 ? a.select() : a.classList.contains("arrowUp") ? e.changeYear(e.currentYear + 1) : a.classList.contains("arrowDown") && e.changeYear(e.currentYear - 1);
  }
  function ln(n) {
    n.preventDefault();
    var a = n.type === "keydown", i = N(n), o = i;
    e.amPM !== void 0 && i === e.amPM && (e.amPM.textContent = e.l10n.amPM[z(e.amPM.textContent === e.l10n.amPM[0])]);
    var l = parseFloat(o.getAttribute("min")), d = parseFloat(o.getAttribute("max")), g = parseFloat(o.getAttribute("step")), c = parseInt(o.value, 10), m = n.delta || (a ? n.which === 38 ? 1 : -1 : 0), u = c + g * m;
    if (typeof o.value < "u" && o.value.length === 2) {
      var h = o === e.hourElement, D = o === e.minuteElement;
      u < l ? (u = d + u + z(!h) + (z(h) && z(!e.amPM)), D && B(void 0, -1, e.hourElement)) : u > d && (u = o === e.hourElement ? u - d - z(!e.amPM) : l, D && B(void 0, 1, e.hourElement)), e.amPM && h && (g === 1 ? u + c === 23 : Math.abs(u - c) > g) && (e.amPM.textContent = e.l10n.amPM[z(e.amPM.textContent === e.l10n.amPM[0])]), o.value = F(u);
    }
  }
  return p(), e;
}
function ve(t, r) {
  for (var e = Array.prototype.slice.call(t).filter(function(w) {
    return w instanceof HTMLElement;
  }), s = [], p = 0; p < e.length; p++) {
    var f = e[p];
    try {
      if (f.getAttribute("data-fp-omit") !== null)
        continue;
      f._flatpickr !== void 0 && (f._flatpickr.destroy(), f._flatpickr = void 0), f._flatpickr = Dt(f, r || {}), s.push(f._flatpickr);
    } catch (w) {
      console.error(w);
    }
  }
  return s.length === 1 ? s[0] : s;
}
typeof HTMLElement < "u" && typeof HTMLCollection < "u" && typeof NodeList < "u" && (HTMLCollection.prototype.flatpickr = NodeList.prototype.flatpickr = function(t) {
  return ve(this, t);
}, HTMLElement.prototype.flatpickr = function(t) {
  return ve([this], t);
});
var C = function(t, r) {
  return typeof t == "string" ? ve(window.document.querySelectorAll(t), r) : t instanceof Node ? ve([t], r) : ve(t, r);
};
C.defaultConfig = {};
C.l10ns = {
  en: E({}, Le),
  default: E({}, Le)
};
C.localize = function(t) {
  C.l10ns.default = E(E({}, C.l10ns.default), t);
};
C.setDefaults = function(t) {
  C.defaultConfig = E(E({}, C.defaultConfig), t);
};
C.parseDate = En({});
C.formatDate = dt({});
C.compareDates = P;
typeof jQuery < "u" && typeof jQuery.fn < "u" && (jQuery.fn.flatpickr = function(t) {
  return ve(this, t);
});
Date.prototype.fp_incr = function(t) {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate() + (typeof t == "string" ? parseInt(t, 10) : t));
};
typeof window < "u" && (window.flatpickr = C);
var Ne = { exports: {} }, Mt = Ne.exports, at;
function xt() {
  return at || (at = 1, (function(t, r) {
    (function(e, s) {
      s(r);
    })(Mt, (function(e) {
      var s = function() {
        return s = Object.assign || function(K) {
          for (var wn, Dn = 1, ut = arguments.length; Dn < ut; Dn++) {
            wn = arguments[Dn];
            for (var Mn in wn) Object.prototype.hasOwnProperty.call(wn, Mn) && (K[Mn] = wn[Mn]);
          }
          return K;
        }, s.apply(this, arguments);
      }, p = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, f = {
        weekdays: {
          shorthand: ["أحد", "اثنين", "ثلاثاء", "أربعاء", "خميس", "جمعة", "سبت"],
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
          shorthand: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
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
        time_24hr: !1
      };
      p.l10ns.ar = f, p.l10ns;
      var w = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, M = {
        weekdays: {
          shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
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
        time_24hr: !0
      };
      w.l10ns.at = M, w.l10ns;
      var S = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, j = {
        weekdays: {
          shorthand: ["B.", "B.e.", "Ç.a.", "Ç.", "C.a.", "C.", "Ş."],
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
        time_24hr: !0
      };
      S.l10ns.az = j, S.l10ns;
      var k = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, A = {
        weekdays: {
          shorthand: ["Нд", "Пн", "Аў", "Ср", "Чц", "Пт", "Сб"],
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
        time_24hr: !0
      };
      k.l10ns.be = A, k.l10ns;
      var T = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, L = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["Ned", "Pon", "Uto", "Sri", "Čet", "Pet", "Sub"],
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
        time_24hr: !0
      };
      T.l10ns.bs = L, T.l10ns;
      var ee = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, b = {
        weekdays: {
          shorthand: ["Нд", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
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
        time_24hr: !0,
        firstDayOfWeek: 1
      };
      ee.l10ns.bg = b, ee.l10ns;
      var H = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, se = {
        weekdays: {
          shorthand: ["রবি", "সোম", "মঙ্গল", "বুধ", "বৃহস্পতি", "শুক্র", "শনি"],
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
      H.l10ns.bn = se, H.l10ns;
      var J = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, R = {
        weekdays: {
          shorthand: ["Dg", "Dl", "Dt", "Dc", "Dj", "Dv", "Ds"],
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
        ordinal: function(v) {
          var K = v % 100;
          if (K > 3 && K < 21)
            return "è";
          switch (K % 10) {
            case 1:
              return "r";
            case 2:
              return "n";
            case 3:
              return "r";
            case 4:
              return "t";
            default:
              return "è";
          }
        },
        firstDayOfWeek: 1,
        rangeSeparator: " a ",
        time_24hr: !0
      };
      J.l10ns.cat = J.l10ns.ca = R, J.l10ns;
      var B = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, fe = {
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
      B.l10ns.ckb = fe, B.l10ns;
      var Y = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, G = {
        weekdays: {
          shorthand: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
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
        time_24hr: !0
      };
      Y.l10ns.cs = G, Y.l10ns;
      var U = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, ue = {
        weekdays: {
          shorthand: ["Sul", "Llun", "Maw", "Mer", "Iau", "Gwe", "Sad"],
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
        ordinal: function(v) {
          return v === 1 ? "af" : v === 2 ? "ail" : v === 3 || v === 4 ? "ydd" : v === 5 || v === 6 ? "ed" : v >= 7 && v <= 10 || v == 12 || v == 15 || v == 18 || v == 20 ? "fed" : v == 11 || v == 13 || v == 14 || v == 16 || v == 17 || v == 19 ? "eg" : v >= 21 && v <= 39 ? "ain" : "";
        },
        time_24hr: !0
      };
      U.l10ns.cy = ue, U.l10ns;
      var Z = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Je = {
        weekdays: {
          shorthand: ["søn", "man", "tir", "ons", "tors", "fre", "lør"],
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
        time_24hr: !0
      };
      Z.l10ns.da = Je, Z.l10ns;
      var ne = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, V = {
        weekdays: {
          shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
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
        time_24hr: !0
      };
      ne.l10ns.de = V, ne.l10ns;
      var We = {
        weekdays: {
          shorthand: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
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
        daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
        firstDayOfWeek: 0,
        ordinal: function(v) {
          var K = v % 100;
          if (K > 3 && K < 21)
            return "th";
          switch (K % 10) {
            case 1:
              return "st";
            case 2:
              return "nd";
            case 3:
              return "rd";
            default:
              return "th";
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
        time_24hr: !1
      }, ye = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, ze = {
        firstDayOfWeek: 1,
        rangeSeparator: " ĝis ",
        weekAbbreviation: "Sem",
        scrollTitle: "Rulumu por pligrandigi la valoron",
        toggleTitle: "Klaku por ŝalti",
        weekdays: {
          shorthand: ["Dim", "Lun", "Mar", "Mer", "Ĵaŭ", "Ven", "Sab"],
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
        time_24hr: !0
      };
      ye.l10ns.eo = ze, ye.l10ns;
      var Re = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, De = {
        weekdays: {
          shorthand: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
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
        time_24hr: !0
      };
      Re.l10ns.es = De, Re.l10ns;
      var Me = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, He = {
        weekdays: {
          shorthand: ["P", "E", "T", "K", "N", "R", "L"],
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
        time_24hr: !0
      };
      Me.l10ns.et = He, Me.l10ns;
      var ce = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ye = {
        weekdays: {
          shorthand: ["یک", "دو", "سه", "چهار", "پنج", "جمعه", "شنبه"],
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
      ce.l10ns.fa = Ye, ce.l10ns;
      var $e = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ke = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["su", "ma", "ti", "ke", "to", "pe", "la"],
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
        time_24hr: !0
      };
      $e.l10ns.fi = Ke, $e.l10ns;
      var Q = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, pe = {
        weekdays: {
          shorthand: ["Sun", "Mán", "Týs", "Mik", "Hós", "Frí", "Ley"],
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
        time_24hr: !0
      };
      Q.l10ns.fo = pe, Q.l10ns;
      var te = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, $ = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["dim", "lun", "mar", "mer", "jeu", "ven", "sam"],
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
        ordinal: function(v) {
          return v > 1 ? "" : "er";
        },
        rangeSeparator: " au ",
        weekAbbreviation: "Sem",
        scrollTitle: "Défiler pour augmenter la valeur",
        toggleTitle: "Cliquer pour basculer",
        time_24hr: !0
      };
      te.l10ns.fr = $, te.l10ns;
      var ae = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Be = {
        weekdays: {
          shorthand: ["Κυ", "Δε", "Τρ", "Τε", "Πέ", "Πα", "Σά"],
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
      ae.l10ns.gr = Be, ae.l10ns;
      var xe = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, re = {
        weekdays: {
          shorthand: ["א", "ב", "ג", "ד", "ה", "ו", "ש"],
          longhand: ["ראשון", "שני", "שלישי", "רביעי", "חמישי", "שישי", "שבת"]
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
        time_24hr: !0
      };
      xe.l10ns.he = re, xe.l10ns;
      var Ge = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ue = {
        weekdays: {
          shorthand: ["रवि", "सोम", "मंगल", "बुध", "गुरु", "शुक्र", "शनि"],
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
      Ge.l10ns.hi = Ue, Ge.l10ns;
      var Se = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ve = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["Ned", "Pon", "Uto", "Sri", "Čet", "Pet", "Sub"],
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
        time_24hr: !0
      };
      Se.l10ns.hr = Ve, Se.l10ns;
      var Ce = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ae = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["V", "H", "K", "Sz", "Cs", "P", "Szo"],
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
        time_24hr: !0
      };
      Ce.l10ns.hu = Ae, Ce.l10ns;
      var ie = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, qe = {
        weekdays: {
          shorthand: ["Կիր", "Երկ", "Երք", "Չրք", "Հնգ", "Ուրբ", "Շբթ"],
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
        time_24hr: !0
      };
      ie.l10ns.hy = qe, ie.l10ns;
      var Ze = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Te = {
        weekdays: {
          shorthand: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
          longhand: ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"]
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
        time_24hr: !0,
        rangeSeparator: " - "
      };
      Ze.l10ns.id = Te, Ze.l10ns;
      var he = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Oe = {
        weekdays: {
          shorthand: ["Sun", "Mán", "Þri", "Mið", "Fim", "Fös", "Lau"],
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
        time_24hr: !0
      };
      he.l10ns.is = Oe, he.l10ns;
      var oe = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Qe = {
        weekdays: {
          shorthand: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
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
        time_24hr: !0
      };
      oe.l10ns.it = Qe, oe.l10ns;
      var Ee = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Xe = {
        weekdays: {
          shorthand: ["日", "月", "火", "水", "木", "金", "土"],
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
        time_24hr: !0,
        rangeSeparator: " から ",
        monthAriaLabel: "月",
        amPM: ["午前", "午後"],
        yearAriaLabel: "年",
        hourAriaLabel: "時間",
        minuteAriaLabel: "分"
      };
      Ee.l10ns.ja = Xe, Ee.l10ns;
      var Ie = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, en = {
        weekdays: {
          shorthand: ["კვ", "ორ", "სა", "ოთ", "ხუ", "პა", "შა"],
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
        time_24hr: !0
      };
      Ie.l10ns.ka = en, Ie.l10ns;
      var nn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Fe = {
        weekdays: {
          shorthand: ["일", "월", "화", "수", "목", "금", "토"],
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
      nn.l10ns.ko = Fe, nn.l10ns;
      var tn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, an = {
        weekdays: {
          shorthand: ["អាទិត្យ", "ចន្ទ", "អង្គារ", "ពុធ", "ព្រហស.", "សុក្រ", "សៅរ៍"],
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
        time_24hr: !0
      };
      tn.l10ns.km = an, tn.l10ns;
      var x = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, ge = {
        weekdays: {
          shorthand: ["Жс", "Дс", "Сc", "Ср", "Бс", "Жм", "Сб"],
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
      x.l10ns.kz = ge, x.l10ns;
      var me = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, rn = {
        weekdays: {
          shorthand: ["S", "Pr", "A", "T", "K", "Pn", "Š"],
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
        time_24hr: !0
      };
      me.l10ns.lt = rn, me.l10ns;
      var le = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, we = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["Sv", "Pr", "Ot", "Tr", "Ce", "Pk", "Se"],
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
        time_24hr: !0
      };
      le.l10ns.lv = we, le.l10ns;
      var W = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, on = {
        weekdays: {
          shorthand: ["Не", "По", "Вт", "Ср", "Че", "Пе", "Са"],
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
        time_24hr: !0
      };
      W.l10ns.mk = on, W.l10ns;
      var ln = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, n = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["Да", "Мя", "Лх", "Пү", "Ба", "Бя", "Ня"],
          longhand: ["Даваа", "Мягмар", "Лхагва", "Пүрэв", "Баасан", "Бямба", "Ням"]
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
        time_24hr: !0
      };
      ln.l10ns.mn = n, ln.l10ns;
      var a = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, i = {
        weekdays: {
          shorthand: ["Aha", "Isn", "Sel", "Rab", "Kha", "Jum", "Sab"],
          longhand: ["Ahad", "Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu"]
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
      a.l10ns;
      var o = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, l = {
        weekdays: {
          shorthand: ["နွေ", "လာ", "ဂါ", "ဟူး", "ကြာ", "သော", "နေ"],
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
        time_24hr: !0
      };
      o.l10ns.my = l, o.l10ns;
      var d = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, g = {
        weekdays: {
          shorthand: ["zo", "ma", "di", "wo", "do", "vr", "za"],
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
        time_24hr: !0,
        ordinal: function(v) {
          return v === 1 || v === 8 || v >= 20 ? "ste" : "de";
        }
      };
      d.l10ns.nl = g, d.l10ns;
      var c = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, m = {
        weekdays: {
          shorthand: ["Sø.", "Må.", "Ty.", "On.", "To.", "Fr.", "La."],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      c.l10ns.nn = m, c.l10ns;
      var u = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, h = {
        weekdays: {
          shorthand: ["Søn", "Man", "Tir", "Ons", "Tor", "Fre", "Lør"],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      u.l10ns.no = h, u.l10ns;
      var D = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, _ = {
        weekdays: {
          shorthand: ["ਐਤ", "ਸੋਮ", "ਮੰਗਲ", "ਬੁੱਧ", "ਵੀਰ", "ਸ਼ੁੱਕਰ", "ਸ਼ਨਿੱਚਰ"],
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
        time_24hr: !0
      };
      D.l10ns.pa = _, D.l10ns;
      var O = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, X = {
        weekdays: {
          shorthand: ["Nd", "Pn", "Wt", "Śr", "Cz", "Pt", "So"],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      O.l10ns.pl = X, O.l10ns;
      var q = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, dn = {
        weekdays: {
          shorthand: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
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
        time_24hr: !0
      };
      q.l10ns.pt = dn, q.l10ns;
      var be = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, sn = {
        weekdays: {
          shorthand: ["Dum", "Lun", "Mar", "Mie", "Joi", "Vin", "Sâm"],
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
        time_24hr: !0,
        ordinal: function() {
          return "";
        }
      };
      be.l10ns.ro = sn, be.l10ns;
      var fn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, un = {
        weekdays: {
          shorthand: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
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
        time_24hr: !0
      };
      fn.l10ns.ru = un, fn.l10ns;
      var cn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, pn = {
        weekdays: {
          shorthand: ["ඉ", "ස", "අ", "බ", "බ්‍ර", "සි", "සෙ"],
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
        time_24hr: !0
      };
      cn.l10ns.si = pn, cn.l10ns;
      var hn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, gn = {
        weekdays: {
          shorthand: ["Ned", "Pon", "Ut", "Str", "Štv", "Pia", "Sob"],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      hn.l10ns.sk = gn, hn.l10ns;
      var mn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, In = {
        weekdays: {
          shorthand: ["Ned", "Pon", "Tor", "Sre", "Čet", "Pet", "Sob"],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      mn.l10ns.sl = In, mn.l10ns;
      var Fn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, _n = {
        weekdays: {
          shorthand: ["Di", "Hë", "Ma", "Më", "En", "Pr", "Sh"],
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
        time_24hr: !0
      };
      Fn.l10ns.sq = _n, Fn.l10ns;
      var Nn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Pn = {
        weekdays: {
          shorthand: ["Ned", "Pon", "Uto", "Sre", "Čet", "Pet", "Sub"],
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
        time_24hr: !0
      };
      Nn.l10ns.sr = Pn, Nn.l10ns;
      var jn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Ln = {
        firstDayOfWeek: 1,
        weekAbbreviation: "v",
        weekdays: {
          shorthand: ["sön", "mån", "tis", "ons", "tor", "fre", "lör"],
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
        time_24hr: !0,
        ordinal: function() {
          return ".";
        }
      };
      jn.l10ns.sv = Ln, jn.l10ns;
      var Jn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Wn = {
        weekdays: {
          shorthand: ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"],
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
        time_24hr: !0,
        ordinal: function() {
          return "";
        }
      };
      Jn.l10ns.th = Wn, Jn.l10ns;
      var zn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Rn = {
        weekdays: {
          shorthand: ["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"],
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
        time_24hr: !0
      };
      zn.l10ns.tr = Rn, zn.l10ns;
      var Hn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Yn = {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ["Нд", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
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
        time_24hr: !0
      };
      Hn.l10ns.uk = Yn, Hn.l10ns;
      var $n = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Kn = {
        weekdays: {
          shorthand: ["Якш", "Душ", "Сеш", "Чор", "Пай", "Жум", "Шан"],
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
        time_24hr: !0
      };
      $n.l10ns.uz = Kn, $n.l10ns;
      var Bn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Gn = {
        weekdays: {
          shorthand: ["Ya", "Du", "Se", "Cho", "Pa", "Ju", "Sha"],
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
        time_24hr: !0
      };
      Bn.l10ns.uz_latn = Gn, Bn.l10ns;
      var Un = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Vn = {
        weekdays: {
          shorthand: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
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
      Un.l10ns.vn = Vn, Un.l10ns;
      var qn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Zn = {
        weekdays: {
          shorthand: ["周日", "周一", "周二", "周三", "周四", "周五", "周六"],
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
      qn.l10ns.zh = Zn, qn.l10ns;
      var Qn = typeof window < "u" && window.flatpickr !== void 0 ? window.flatpickr : {
        l10ns: {}
      }, Xn = {
        weekdays: {
          shorthand: ["週日", "週一", "週二", "週三", "週四", "週五", "週六"],
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
      Qn.l10ns.zh_tw = Xn, Qn.l10ns;
      var ft = {
        ar: f,
        at: M,
        az: j,
        be: A,
        bg: b,
        bn: se,
        bs: L,
        ca: R,
        ckb: fe,
        cat: R,
        cs: G,
        cy: ue,
        da: Je,
        de: V,
        default: s({}, We),
        en: We,
        eo: ze,
        es: De,
        et: He,
        fa: Ye,
        fi: Ke,
        fo: pe,
        fr: $,
        gr: Be,
        he: re,
        hi: Ue,
        hr: Ve,
        hu: Ae,
        hy: qe,
        id: Te,
        is: Oe,
        it: Qe,
        ja: Xe,
        ka: en,
        ko: Fe,
        km: an,
        kz: ge,
        lt: rn,
        lv: we,
        mk: on,
        mn: n,
        ms: i,
        my: l,
        nl: g,
        nn: m,
        no: h,
        pa: _,
        pl: X,
        pt: dn,
        ro: sn,
        ru: un,
        si: pn,
        sk: gn,
        sl: In,
        sq: _n,
        sr: Pn,
        sv: Ln,
        th: Wn,
        tr: Rn,
        uk: Yn,
        vn: Vn,
        zh: Zn,
        zh_tw: Xn,
        uz: Kn,
        uz_latn: Gn
      };
      e.default = ft, Object.defineProperty(e, "__esModule", { value: !0 });
    }));
  })(Ne, Ne.exports)), Ne.exports;
}
var st = xt();
const St = /* @__PURE__ */ ct(st), Ct = /* @__PURE__ */ mt({
  __proto__: null,
  default: St
}, [st]), At = '.flatpickr-calendar{background:transparent;opacity:0;display:none;text-align:center;visibility:hidden;padding:0;-webkit-animation:none;animation:none;direction:ltr;border:0;font-size:14px;line-height:24px;border-radius:5px;position:absolute;width:307.875px;-webkit-box-sizing:border-box;box-sizing:border-box;-ms-touch-action:manipulation;touch-action:manipulation;background:#fff;-webkit-box-shadow:1px 0 0 #e6e6e6,-1px 0 0 #e6e6e6,0 1px 0 #e6e6e6,0 -1px 0 #e6e6e6,0 3px 13px rgba(0,0,0,.08);box-shadow:1px 0 #e6e6e6,-1px 0 #e6e6e6,0 1px #e6e6e6,0 -1px #e6e6e6,0 3px 13px #00000014}.flatpickr-calendar.open,.flatpickr-calendar.inline{opacity:1;max-height:640px;visibility:visible}.flatpickr-calendar.open{display:inline-block;z-index:99999}.flatpickr-calendar.animate.open{-webkit-animation:fpFadeInDown .3s cubic-bezier(.23,1,.32,1);animation:fpFadeInDown .3s cubic-bezier(.23,1,.32,1)}.flatpickr-calendar.inline{display:block;position:relative;top:2px}.flatpickr-calendar.static{position:absolute;top:calc(100% + 2px)}.flatpickr-calendar.static.open{z-index:999;display:block}.flatpickr-calendar.multiMonth .flatpickr-days .dayContainer:nth-child(n+1) .flatpickr-day.inRange:nth-child(7n+7){-webkit-box-shadow:none!important;box-shadow:none!important}.flatpickr-calendar.multiMonth .flatpickr-days .dayContainer:nth-child(n+2) .flatpickr-day.inRange:nth-child(7n+1){-webkit-box-shadow:-2px 0 0 #e6e6e6,5px 0 0 #e6e6e6;box-shadow:-2px 0 #e6e6e6,5px 0 #e6e6e6}.flatpickr-calendar .hasWeeks .dayContainer,.flatpickr-calendar .hasTime .dayContainer{border-bottom:0;border-bottom-right-radius:0;border-bottom-left-radius:0}.flatpickr-calendar .hasWeeks .dayContainer{border-left:0}.flatpickr-calendar.hasTime .flatpickr-time{height:40px;border-top:1px solid #e6e6e6}.flatpickr-calendar.noCalendar.hasTime .flatpickr-time{height:auto}.flatpickr-calendar:before,.flatpickr-calendar:after{position:absolute;display:block;pointer-events:none;border:solid transparent;content:"";height:0;width:0;left:22px}.flatpickr-calendar.rightMost:before,.flatpickr-calendar.arrowRight:before,.flatpickr-calendar.rightMost:after,.flatpickr-calendar.arrowRight:after{left:auto;right:22px}.flatpickr-calendar.arrowCenter:before,.flatpickr-calendar.arrowCenter:after{left:50%;right:50%}.flatpickr-calendar:before{border-width:5px;margin:0 -5px}.flatpickr-calendar:after{border-width:4px;margin:0 -4px}.flatpickr-calendar.arrowTop:before,.flatpickr-calendar.arrowTop:after{bottom:100%}.flatpickr-calendar.arrowTop:before{border-bottom-color:#e6e6e6}.flatpickr-calendar.arrowTop:after{border-bottom-color:#fff}.flatpickr-calendar.arrowBottom:before,.flatpickr-calendar.arrowBottom:after{top:100%}.flatpickr-calendar.arrowBottom:before{border-top-color:#e6e6e6}.flatpickr-calendar.arrowBottom:after{border-top-color:#fff}.flatpickr-calendar:focus{outline:0}.flatpickr-wrapper{position:relative;display:inline-block}.flatpickr-months{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex}.flatpickr-months .flatpickr-month{background:transparent;color:#000000e6;fill:#000000e6;height:34px;line-height:1;text-align:center;position:relative;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;overflow:hidden;-webkit-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1}.flatpickr-months .flatpickr-prev-month,.flatpickr-months .flatpickr-next-month{-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;text-decoration:none;cursor:pointer;position:absolute;top:0;height:34px;padding:10px;z-index:3;color:#000000e6;fill:#000000e6}.flatpickr-months .flatpickr-prev-month.flatpickr-disabled,.flatpickr-months .flatpickr-next-month.flatpickr-disabled{display:none}.flatpickr-months .flatpickr-prev-month i,.flatpickr-months .flatpickr-next-month i{position:relative}.flatpickr-months .flatpickr-prev-month.flatpickr-prev-month,.flatpickr-months .flatpickr-next-month.flatpickr-prev-month{left:0}.flatpickr-months .flatpickr-prev-month.flatpickr-next-month,.flatpickr-months .flatpickr-next-month.flatpickr-next-month{right:0}.flatpickr-months .flatpickr-prev-month:hover,.flatpickr-months .flatpickr-next-month:hover{color:#959ea9}.flatpickr-months .flatpickr-prev-month:hover svg,.flatpickr-months .flatpickr-next-month:hover svg{fill:#f64747}.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg{width:14px;height:14px}.flatpickr-months .flatpickr-prev-month svg path,.flatpickr-months .flatpickr-next-month svg path{-webkit-transition:fill .1s;transition:fill .1s;fill:inherit}.numInputWrapper{position:relative;height:auto}.numInputWrapper input,.numInputWrapper span{display:inline-block}.numInputWrapper input{width:100%}.numInputWrapper input::-ms-clear{display:none}.numInputWrapper input::-webkit-outer-spin-button,.numInputWrapper input::-webkit-inner-spin-button{margin:0;-webkit-appearance:none}.numInputWrapper span{position:absolute;right:0;width:14px;padding:0 4px 0 2px;height:50%;line-height:50%;opacity:0;cursor:pointer;border:1px solid rgba(57,57,57,.15);-webkit-box-sizing:border-box;box-sizing:border-box}.numInputWrapper span:hover{background:#0000001a}.numInputWrapper span:active{background:#0003}.numInputWrapper span:after{display:block;content:"";position:absolute}.numInputWrapper span.arrowUp{top:0;border-bottom:0}.numInputWrapper span.arrowUp:after{border-left:4px solid transparent;border-right:4px solid transparent;border-bottom:4px solid rgba(57,57,57,.6);top:26%}.numInputWrapper span.arrowDown{top:50%}.numInputWrapper span.arrowDown:after{border-left:4px solid transparent;border-right:4px solid transparent;border-top:4px solid rgba(57,57,57,.6);top:40%}.numInputWrapper span svg{width:inherit;height:auto}.numInputWrapper span svg path{fill:#00000080}.numInputWrapper:hover{background:#0000000d}.numInputWrapper:hover span{opacity:1}.flatpickr-current-month{font-size:135%;line-height:inherit;font-weight:300;color:inherit;position:absolute;width:75%;left:12.5%;padding:7.48px 0 0;line-height:1;height:34px;display:inline-block;text-align:center;-webkit-transform:translate3d(0px,0px,0px);transform:translateZ(0)}.flatpickr-current-month span.cur-month{font-family:inherit;font-weight:700;color:inherit;display:inline-block;margin-left:.5ch;padding:0}.flatpickr-current-month span.cur-month:hover{background:#0000000d}.flatpickr-current-month .numInputWrapper{width:6ch;width:7ch�;display:inline-block}.flatpickr-current-month .numInputWrapper span.arrowUp:after{border-bottom-color:#000000e6}.flatpickr-current-month .numInputWrapper span.arrowDown:after{border-top-color:#000000e6}.flatpickr-current-month input.cur-year{background:transparent;-webkit-box-sizing:border-box;box-sizing:border-box;color:inherit;cursor:text;padding:0 0 0 .5ch;margin:0;display:inline-block;font-size:inherit;font-family:inherit;font-weight:300;line-height:inherit;height:auto;border:0;border-radius:0;vertical-align:initial;-webkit-appearance:textfield;-moz-appearance:textfield;appearance:textfield}.flatpickr-current-month input.cur-year:focus{outline:0}.flatpickr-current-month input.cur-year[disabled],.flatpickr-current-month input.cur-year[disabled]:hover{font-size:100%;color:#00000080;background:transparent;pointer-events:none}.flatpickr-current-month .flatpickr-monthDropdown-months{appearance:menulist;background:transparent;border:none;border-radius:0;box-sizing:border-box;color:inherit;cursor:pointer;font-size:inherit;font-family:inherit;font-weight:300;height:auto;line-height:inherit;margin:-1px 0 0;outline:none;padding:0 0 0 .5ch;position:relative;vertical-align:initial;-webkit-box-sizing:border-box;-webkit-appearance:menulist;-moz-appearance:menulist;width:auto}.flatpickr-current-month .flatpickr-monthDropdown-months:focus,.flatpickr-current-month .flatpickr-monthDropdown-months:active{outline:none}.flatpickr-current-month .flatpickr-monthDropdown-months:hover{background:#0000000d}.flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month{background-color:transparent;outline:none;padding:0}.flatpickr-weekdays{background:transparent;text-align:center;overflow:hidden;width:100%;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;height:28px}.flatpickr-weekdays .flatpickr-weekdaycontainer{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1}span.flatpickr-weekday{cursor:default;font-size:90%;background:transparent;color:#0000008a;line-height:1;margin:0;text-align:center;display:block;-webkit-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;font-weight:bolder}.dayContainer,.flatpickr-weeks{padding:1px 0 0}.flatpickr-days{position:relative;overflow:hidden;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-align:start;-webkit-align-items:flex-start;-ms-flex-align:start;align-items:flex-start;width:307.875px}.flatpickr-days:focus{outline:0}.dayContainer{padding:0;outline:0;text-align:left;width:307.875px;min-width:307.875px;max-width:307.875px;-webkit-box-sizing:border-box;box-sizing:border-box;display:inline-block;display:-ms-flexbox;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;-ms-flex-wrap:wrap;-ms-flex-pack:justify;-webkit-justify-content:space-around;justify-content:space-around;-webkit-transform:translate3d(0px,0px,0px);transform:translateZ(0);opacity:1}.dayContainer+.dayContainer{-webkit-box-shadow:-1px 0 0 #e6e6e6;box-shadow:-1px 0 #e6e6e6}.flatpickr-day{background:none;border:1px solid transparent;border-radius:150px;-webkit-box-sizing:border-box;box-sizing:border-box;color:#393939;cursor:pointer;font-weight:400;width:14.2857143%;-webkit-flex-basis:14.2857143%;-ms-flex-preferred-size:14.2857143%;flex-basis:14.2857143%;max-width:39px;height:39px;line-height:39px;margin:0;display:inline-block;position:relative;-webkit-box-pack:center;-webkit-justify-content:center;-ms-flex-pack:center;justify-content:center;text-align:center}.flatpickr-day.inRange,.flatpickr-day.prevMonthDay.inRange,.flatpickr-day.nextMonthDay.inRange,.flatpickr-day.today.inRange,.flatpickr-day.prevMonthDay.today.inRange,.flatpickr-day.nextMonthDay.today.inRange,.flatpickr-day:hover,.flatpickr-day.prevMonthDay:hover,.flatpickr-day.nextMonthDay:hover,.flatpickr-day:focus,.flatpickr-day.prevMonthDay:focus,.flatpickr-day.nextMonthDay:focus{cursor:pointer;outline:0;background:#e6e6e6;border-color:#e6e6e6}.flatpickr-day.today{border-color:#959ea9}.flatpickr-day.today:hover,.flatpickr-day.today:focus{border-color:#959ea9;background:#959ea9;color:#fff}.flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange,.flatpickr-day.selected.inRange,.flatpickr-day.startRange.inRange,.flatpickr-day.endRange.inRange,.flatpickr-day.selected:focus,.flatpickr-day.startRange:focus,.flatpickr-day.endRange:focus,.flatpickr-day.selected:hover,.flatpickr-day.startRange:hover,.flatpickr-day.endRange:hover,.flatpickr-day.selected.prevMonthDay,.flatpickr-day.startRange.prevMonthDay,.flatpickr-day.endRange.prevMonthDay,.flatpickr-day.selected.nextMonthDay,.flatpickr-day.startRange.nextMonthDay,.flatpickr-day.endRange.nextMonthDay{background:#569ff7;-webkit-box-shadow:none;box-shadow:none;color:#fff;border-color:#569ff7}.flatpickr-day.selected.startRange,.flatpickr-day.startRange.startRange,.flatpickr-day.endRange.startRange{border-radius:50px 0 0 50px}.flatpickr-day.selected.endRange,.flatpickr-day.startRange.endRange,.flatpickr-day.endRange.endRange{border-radius:0 50px 50px 0}.flatpickr-day.selected.startRange+.endRange:not(:nth-child(7n+1)),.flatpickr-day.startRange.startRange+.endRange:not(:nth-child(7n+1)),.flatpickr-day.endRange.startRange+.endRange:not(:nth-child(7n+1)){-webkit-box-shadow:-10px 0 0 #569ff7;box-shadow:-10px 0 #569ff7}.flatpickr-day.selected.startRange.endRange,.flatpickr-day.startRange.startRange.endRange,.flatpickr-day.endRange.startRange.endRange{border-radius:50px}.flatpickr-day.inRange{border-radius:0;-webkit-box-shadow:-5px 0 0 #e6e6e6,5px 0 0 #e6e6e6;box-shadow:-5px 0 #e6e6e6,5px 0 #e6e6e6}.flatpickr-day.flatpickr-disabled,.flatpickr-day.flatpickr-disabled:hover,.flatpickr-day.prevMonthDay,.flatpickr-day.nextMonthDay,.flatpickr-day.notAllowed,.flatpickr-day.notAllowed.prevMonthDay,.flatpickr-day.notAllowed.nextMonthDay{color:#3939394d;background:transparent;border-color:transparent;cursor:default}.flatpickr-day.flatpickr-disabled,.flatpickr-day.flatpickr-disabled:hover{cursor:not-allowed;color:#3939391a}.flatpickr-day.week.selected{border-radius:0;-webkit-box-shadow:-5px 0 0 #569ff7,5px 0 0 #569ff7;box-shadow:-5px 0 #569ff7,5px 0 #569ff7}.flatpickr-day.hidden{visibility:hidden}.rangeMode .flatpickr-day{margin-top:1px}.flatpickr-weekwrapper{float:left}.flatpickr-weekwrapper .flatpickr-weeks{padding:0 12px;-webkit-box-shadow:1px 0 0 #e6e6e6;box-shadow:1px 0 #e6e6e6}.flatpickr-weekwrapper .flatpickr-weekday{float:none;width:100%;line-height:28px}.flatpickr-weekwrapper span.flatpickr-day,.flatpickr-weekwrapper span.flatpickr-day:hover{display:block;width:100%;max-width:none;color:#3939394d;background:transparent;cursor:default;border:none}.flatpickr-innerContainer{display:block;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-sizing:border-box;box-sizing:border-box;overflow:hidden}.flatpickr-rContainer{display:inline-block;padding:0;-webkit-box-sizing:border-box;box-sizing:border-box}.flatpickr-time{text-align:center;outline:0;display:block;height:0;line-height:40px;max-height:40px;-webkit-box-sizing:border-box;box-sizing:border-box;overflow:hidden;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex}.flatpickr-time:after{content:"";display:table;clear:both}.flatpickr-time .numInputWrapper{-webkit-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;width:40%;height:40px;float:left}.flatpickr-time .numInputWrapper span.arrowUp:after{border-bottom-color:#393939}.flatpickr-time .numInputWrapper span.arrowDown:after{border-top-color:#393939}.flatpickr-time.hasSeconds .numInputWrapper{width:26%}.flatpickr-time.time24hr .numInputWrapper{width:49%}.flatpickr-time input{background:transparent;-webkit-box-shadow:none;box-shadow:none;border:0;border-radius:0;text-align:center;margin:0;padding:0;height:inherit;line-height:inherit;color:#393939;font-size:14px;position:relative;-webkit-box-sizing:border-box;box-sizing:border-box;-webkit-appearance:textfield;-moz-appearance:textfield;appearance:textfield}.flatpickr-time input.flatpickr-hour{font-weight:700}.flatpickr-time input.flatpickr-minute,.flatpickr-time input.flatpickr-second{font-weight:400}.flatpickr-time input:focus{outline:0;border:0}.flatpickr-time .flatpickr-time-separator,.flatpickr-time .flatpickr-am-pm{height:inherit;float:left;line-height:inherit;color:#393939;font-weight:700;width:2%;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;-webkit-align-self:center;-ms-flex-item-align:center;align-self:center}.flatpickr-time .flatpickr-am-pm{outline:0;width:18%;cursor:pointer;text-align:center;font-weight:400}.flatpickr-time input:hover,.flatpickr-time .flatpickr-am-pm:hover,.flatpickr-time input:focus,.flatpickr-time .flatpickr-am-pm:focus{background:#eee}.flatpickr-input[readonly]{cursor:pointer}@-webkit-keyframes fpFadeInDown{0%{opacity:0;-webkit-transform:translate3d(0,-20px,0);transform:translate3d(0,-20px,0)}to{opacity:1;-webkit-transform:translate3d(0,0,0);transform:translateZ(0)}}@keyframes fpFadeInDown{0%{opacity:0;-webkit-transform:translate3d(0,-20px,0);transform:translate3d(0,-20px,0)}to{opacity:1;-webkit-transform:translate3d(0,0,0);transform:translateZ(0)}}', rt = "input[data-formie-date-datepicker-input]", Tt = "input[data-formie-date-range-start-input]", Ot = "input[data-formie-date-range-end-input]", yn = "date-picker", je = gt("fields", "date-picker");
function Et() {
  return (t) => ({
    onReady: () => {
      if (!t.altInput)
        return;
      const r = /* @__PURE__ */ new Set(["type", "name", "value"]);
      t.input.getAttributeNames().forEach((e) => {
        if (r.has(e))
          return;
        const s = t.input.getAttribute(e);
        s !== null && t.altInput?.setAttribute(e, s), t.input.removeAttribute(e);
      }), t.loadedPlugins.push("formie-attributes");
    }
  });
}
function it(t, r) {
  if (!t)
    return null;
  if (!Number.isNaN(Date.parse(t)))
    return new Date(t);
  const e = t.trim().match(/^([+-]?\d+)\s*(day|days|week|weeks|month|months|year|years)$/i);
  if (!e)
    return null;
  const s = parseInt(e[1] || "0", 10), p = (e[2] || "").toLowerCase(), f = /* @__PURE__ */ new Date();
  switch (p) {
    case "day":
    case "days":
      f.setDate(f.getDate() + s);
      break;
    case "week":
    case "weeks":
      f.setDate(f.getDate() + s * 7);
      break;
    case "month":
    case "months":
      f.setMonth(f.getMonth() + s);
      break;
    case "year":
    case "years":
      f.setFullYear(f.getFullYear() + s);
      break;
    default:
      return null;
  }
  return r === "min" ? f.setHours(0, 0, 0, 0) : f.setHours(23, 59, 59, 999), f;
}
function It(t) {
  return (t.getIsDate ? t.dateFormat || "" : t.getIsTime ? t.timeFormat || "" : `${t.dateFormat || ""} ${t.timeFormat || ""}`.trim()).replaceAll("A", "K").replaceAll("a", "K").replaceAll("s", "S").replaceAll("g", "h").replaceAll("h", "G");
}
function Ft(t) {
  if (!t || t === "en")
    return "en";
  const r = Ct;
  return r[t] ?? r.default ?? "en";
}
function _t(t) {
  if (!t || t === "*")
    return;
  const r = t.map((e) => Number(e));
  return (e) => !r.includes(e.getDay());
}
function Nt(t) {
  const r = {};
  return (t.datePickerOptions || []).forEach((e) => {
    e.label && (r[e.label] = e.value);
  }), r;
}
function _e(t) {
  return String(t).padStart(2, "0");
}
function ot(t, r) {
  const e = t.getFullYear(), s = _e(t.getMonth() + 1), p = _e(t.getDate());
  if (!r.getIsTime && !r.getIsDateTime)
    return `${e}-${s}-${p}`;
  const f = _e(t.getHours()), w = _e(t.getMinutes()), M = _e(t.getSeconds());
  return `${e}-${s}-${p} ${f}:${w}:${M}`;
}
function On(t) {
  const r = new Date(t);
  return Number.isNaN(r.getTime()) ? null : r;
}
function Pt(t, r, e, s) {
  r && (r.value = t[0] ? ot(t[0], s) : "", r.dispatchEvent(new Event("input", { bubbles: !0 }))), e && (e.value = t[1] ? ot(t[1], s) : "", e.dispatchEvent(new Event("input", { bubbles: !0 })));
}
function jt(t, r) {
  t._formieFlatpickr?.destroy();
  const e = t.closest("[data-formie-field-handle]"), s = r.collectMode === "range" || t.hasAttribute("data-formie-date-range-input"), p = e?.querySelector(Tt), f = e?.querySelector(Ot), w = {
    disableMobile: !0,
    allowInput: !0,
    altInput: !0,
    altFormat: It(r),
    dateFormat: "Y-m-d H:i:S",
    hourIncrement: 1,
    minuteIncrement: 1,
    minDate: it(r.minDate, "min"),
    maxDate: it(r.maxDate, "max"),
    plugins: [Et()],
    locale: Ft(r.locale),
    onChange: (k, A, T) => {
      s && Pt(
        k,
        p instanceof HTMLInputElement ? p : null,
        f instanceof HTMLInputElement ? f : null,
        r
      ), T.input.dispatchEvent(new Event("input", { bubbles: !0 })), T.altInput?.dispatchEvent(new Event("input", { bubbles: !0 }));
    }
  }, M = _t(r.availableDaysOfWeek);
  if (M && (w.disable = [M]), (r.getIsTime || r.getIsDateTime) && (w.enableTime = !0), r.getIsTime && (w.noCalendar = !0), s) {
    w.mode = "range";
    const k = p instanceof HTMLInputElement && p.value ? On(p.value) : null, A = f instanceof HTMLInputElement && f.value ? On(f.value) : null;
    k && (w.defaultDate = A ? [k, A] : [k]);
  } else if (t.value) {
    const k = On(t.value);
    k && (w.defaultDate = k);
  }
  const S = {
    ...w,
    ...Nt(r)
  };
  s && (S.mode = "range"), et(t, yn, "before-init", {
    datepicker: t,
    options: S
  });
  const j = C(t, S);
  return t._formieFlatpickr = j, je.log("Initialized.", {
    inputName: t.name,
    isRange: s
  }), et(t, yn, "after-init", {
    datepicker: j,
    options: S
  }), () => {
    j.destroy(), delete t._formieFlatpickr, je.log("Destroyed.", {
      inputName: t.name
    });
  };
}
const Rt = {
  id: yn,
  kind: "field",
  match: (t) => !!t.target.querySelector(rt),
  setup: async (t) => {
    const r = t.options || {};
    r.includeFlatpickrCss !== !1 && ht(yn, [At]);
    const e = pt(t), s = e.map((p) => {
      const f = p.querySelector(rt);
      return f instanceof HTMLInputElement ? jt(f, r) : (je.warn("Field missing date input; skipping."), () => {
      });
    });
    return je.log("Module setup.", { fieldCount: e.length }), await t.emit("formie:module:date-picker:init", {
      count: s.length
    }), {
      destroy: () => {
        s.forEach((p) => {
          p();
        }), je.log("Module destroy.", { fieldCount: e.length }), t.emit("formie:module:date-picker:destroy", {});
      }
    };
  }
};
export {
  Rt as datePickerModule
};
