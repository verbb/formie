/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addClasses: () => (/* binding */ addClasses),
/* harmony export */   clone: () => (/* binding */ clone),
/* harmony export */   debounce: () => (/* binding */ debounce),
/* harmony export */   ensureVariable: () => (/* binding */ ensureVariable),
/* harmony export */   eventKey: () => (/* binding */ eventKey),
/* harmony export */   isEmpty: () => (/* binding */ isEmpty),
/* harmony export */   removeClasses: () => (/* binding */ removeClasses),
/* harmony export */   t: () => (/* binding */ t),
/* harmony export */   toBoolean: () => (/* binding */ toBoolean),
/* harmony export */   waitForElement: () => (/* binding */ waitForElement)
/* harmony export */ });
var isEmpty = function isEmpty(obj) {
  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
};
var toBoolean = function toBoolean(val) {
  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};
var clone = function clone(value) {
  if (value === undefined) {
    return undefined;
  }
  return JSON.parse(JSON.stringify(value));
};
var eventKey = function eventKey(eventName) {
  var namespace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  if (!namespace) {
    namespace = Math.random().toString(36).substr(2, 5);
  }
  return "".concat(eventName, ".").concat(namespace);
};
var t = function t(string) {
  var replacements = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  if (window.FormieTranslations) {
    string = window.FormieTranslations[string] || string;
  }
  return string.replace(/{([a-zA-Z0-9]+)}/g, function (match, p1) {
    if (replacements[p1]) {
      return replacements[p1];
    }
    return match;
  });
};
var ensureVariable = function ensureVariable(variable) {
  var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 100000;
  var start = Date.now();

  // Function to allow us to wait for a global variable to be available. Useful for third-party scripts.
  var waitForVariable = function waitForVariable(resolve, reject) {
    if (window[variable]) {
      resolve(window[variable]);
    } else if (timeout && Date.now() - start >= timeout) {
      reject(new Error('timeout'));
    } else {
      setTimeout(waitForVariable.bind(this, resolve, reject), 30);
    }
  };
  return new Promise(waitForVariable);
};
var waitForElement = function waitForElement(selector, $element) {
  $element = $element || document;
  return new Promise(function (resolve) {
    if ($element.querySelector(selector)) {
      return resolve($element.querySelector(selector));
    }
    var observer = new MutationObserver(function (mutations) {
      if ($element.querySelector(selector)) {
        observer.disconnect();
        resolve($element.querySelector(selector));
      }
    });
    observer.observe($element, {
      childList: true,
      subtree: true
    });
  });
};
var debounce = function debounce(func, delay) {
  var timeoutId;
  return function () {
    var _this = this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    clearTimeout(timeoutId);
    timeoutId = setTimeout(function () {
      func.apply(_this, args);
    }, delay);
  };
};
var addClasses = function addClasses(element, classes) {
  if (!element || !classes) {
    return;
  }
  if (typeof classes === 'string') {
    classes = classes.split(' ');
  }
  classes.forEach(function (className) {
    element.classList.add(className);
  });
};
var removeClasses = function removeClasses(element, classes) {
  if (!element || !classes) {
    return;
  }
  if (typeof classes === 'string') {
    classes = classes.split(' ');
  }
  classes.forEach(function (className) {
    element.classList.remove(className);
  });
};

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Cache/ArrayAdapter.js":
/*!******************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Cache/ArrayAdapter.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.CacheItem = exports["default"] = void 0;

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ArrayAdapter {
  constructor(defaultLifetime = 0) {
    _defineProperty(this, "createCacheItem", (key, value, isHit) => {
      let item = new CacheItem();
      item.key = key;
      item.value = value;
      item.isHit = isHit;
      item.defaultLifetime = this.defaultLifetime;
      return item;
    });

    _defineProperty(this, "get", (key, callback, beta = null, metadata = null) => {
      let item = this.getItem(key);

      if (!item.isHit) {
        let save = true;
        this.save(item.set(callback(item, save)));
      }

      return item.get();
    });

    _defineProperty(this, "getItem", key => {
      let isHit = this.hasItem(key),
          value = null;

      if (!isHit) {
        this.values[key] = null;
      } else {
        value = this.values[key];
      }

      let f = this.createCacheItem;
      return f(key, value, isHit);
    });

    _defineProperty(this, "getItems", keys => {
      for (let key of keys) {
        if (typeof key !== "string" && !this.expiries[key]) {
          CacheItem.validateKey(key);
        }
      }

      return this.generateItems(keys, new Date().getTime() / 1000, this.createCacheItem);
    });

    _defineProperty(this, "deleteItems", keys => {
      for (let key of keys) {
        this.deleteItem(key);
      }

      return true;
    });

    _defineProperty(this, "save", item => {
      if (!item instanceof CacheItem) {
        return false;
      }

      if (item.expiry !== null && item.expiry <= new Date().getTime() / 1000) {
        this.deleteItem(item.key);
        return true;
      }

      if (null === item.expiry && 0 < item.defaultLifetime) {
        item.expiry = new Date().getTime() / 1000 + item.defaultLifetime;
      }

      this.values[item.key] = item.value;
      this.expiries[item.key] = item.expiry || Number.MAX_SAFE_INTEGER;
      return true;
    });

    _defineProperty(this, "saveDeferred", item => {
      return this.save(item);
    });

    _defineProperty(this, "commit", () => {
      return true;
    });

    _defineProperty(this, "delete", key => {
      return this.deleteItem(key);
    });

    _defineProperty(this, "getValues", () => {
      return this.values;
    });

    _defineProperty(this, "hasItem", key => {
      if (typeof key === "string" && this.expiries[key] && this.expiries[key] > new Date().getTime() / 1000) {
        return true;
      }

      CacheItem.validateKey(key);
      return !!this.expiries[key] && !this.deleteItem(key);
    });

    _defineProperty(this, "clear", () => {
      this.values = {};
      this.expiries = {};
      return true;
    });

    _defineProperty(this, "deleteItem", key => {
      if (typeof key !== "string" || !this.expiries[key]) {
        CacheItem.validateKey(key);
      }

      delete this.values[key];
      delete this.expiries[key];
      return true;
    });

    _defineProperty(this, "reset", () => {
      this.clear();
    });

    _defineProperty(this, "generateItems", (keys, now, f) => {
      let generated = [];

      for (let key of keys) {
        let value = null;
        let isHit = !!this.expiries[key];

        if (!isHit && (this.expiries[key] > now || !this.deleteItem(key))) {
          this.values[key] = null;
        } else {
          value = this.values[key];
        }

        generated[key] = f(key, value, isHit);
      }

      return generated;
    });

    this.defaultLifetime = defaultLifetime;
    this.values = {};
    this.expiries = {};
  }

}

exports["default"] = ArrayAdapter;

class CacheItem {
  constructor() {
    _defineProperty(this, "getKey", () => {
      return this.key;
    });

    _defineProperty(this, "get", () => {
      return this.value;
    });

    _defineProperty(this, "set", value => {
      this.value = value;
      return this;
    });

    _defineProperty(this, "expiresAt", expiration => {
      if (null === expiration) {
        this.expiry = this.defaultLifetime > 0 ? Date.now() / 1000 + this.defaultLifetime : null;
      } else if (expiration instanceof Date) {
        this.expiry = expiration.getTime() / 1000;
      } else {
        throw new Error(`Expiration date must be instance of Date or be null, "${expiration.name}" given`);
      }

      return this;
    });

    _defineProperty(this, "expiresAfter", time => {
      if (null === time) {
        this.expiry = this.defaultLifetime > 0 ? Date.now() / 1000 + this.defaultLifetime : null;
      } else if (Number.isInteger(time)) {
        this.expiry = new Date().getTime() / 1000 + time;
      } else {
        throw new Error(`Expiration date must be an integer or be null, "${time.name}" given`);
      }

      return this;
    });

    _defineProperty(this, "tag", tags => {
      if (!this.isTaggable) {
        throw new Error(`Cache item "${this.key}" comes from a non tag-aware pool: you cannot tag it.`);
      }

      if (!Array.isArray(tags)) {
        tags = [tags];
      }

      for (let tag of tags) {
        if (typeof tag !== "string") {
          throw new Error(`Cache tag must by a string, "${typeof tag}" given.`);
        }

        if (this.newMetadata.tags[tag]) {
          if (tag === '') {
            throw new Error("Cache tag length must be greater than zero");
          }
        }

        this.newMetadata.tags[tag] = tag;
      }

      return this;
    });

    _defineProperty(this, "getMetadata", () => {
      return this.metadata;
    });

    this.key = null;
    this.value = null;
    this.isHit = false;
    this.expiry = null;
    this.defaultLifetime = null;
    this.metadata = {};
    this.newMetadata = {};
    this.innerItem = null;
    this.poolHash = null;
    this.isTaggable = false;
  }

}

exports.CacheItem = CacheItem;

_defineProperty(CacheItem, "METADATA_EXPIRY_OFFSET", 1527506807);

_defineProperty(CacheItem, "RESERVED_CHARACTERS", ["{", "}", "(", ")", "/", "\\", "@", ":"]);

_defineProperty(CacheItem, "validateKey", key => {
  if (typeof key !== "string") {
    throw new Error(`Cache key must be string, "${typeof key}" given.`);
  }

  if ('' === key) {
    throw new Error("Cache key length must be greater than zero");
  }

  for (let reserved of CacheItem.RESERVED_CHARACTERS) {
    if (key.indexOf(reserved) >= 0) {
      throw new Error(`Cache key "${key}" contains reserved character "${reserved}".`);
    }
  }

  return key;
});

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Compiler.js":
/*!********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Compiler.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _addcslashes = __webpack_require__(/*! ./lib/addcslashes */ "../../../../node_modules/expression-language/lib/lib/addcslashes.js");

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class Compiler {
  constructor(functions) {
    _defineProperty(this, "getFunction", name => {
      return this.functions[name];
    });

    _defineProperty(this, "getSource", () => {
      return this.source;
    });

    _defineProperty(this, "reset", () => {
      this.source = '';
      return this;
    });

    _defineProperty(this, "compile", node => {
      node.compile(this);
      return this;
    });

    _defineProperty(this, "subcompile", node => {
      let current = this.source;
      this.source = '';
      node.compile(this);
      let source = this.source;
      this.source = current;
      return source;
    });

    _defineProperty(this, "raw", str => {
      this.source += str;
      return this;
    });

    _defineProperty(this, "string", value => {
      this.source += '"' + (0, _addcslashes.addcslashes)(value, "\0\t\"\$\\") + '"';
      return this;
    });

    _defineProperty(this, "repr", (value, isIdentifier = false) => {
      // Integer or Float
      if (isIdentifier) {
        this.raw(value);
      } else if (Number.isInteger(value) || +value === value && (!isFinite(value) || !!(value % 1))) {
        this.raw(value);
      } else if (null === value) {
        this.raw('null');
      } else if (typeof value === 'boolean') {
        this.raw(value ? 'true' : 'false');
      } else if (typeof value === 'object') {
        this.raw('{');
        let first = true;

        for (let oneKey of Object.keys(value)) {
          if (!first) {
            this.raw(', ');
          }

          first = false;
          this.repr(oneKey);
          this.raw(':');
          this.repr(value[oneKey]);
        }

        this.raw('}');
      } else if (Array.isArray(value)) {
        this.raw('[');
        let first = true;

        for (let oneValue of value) {
          if (!first) {
            this.raw(', ');
          }

          first = false;
          this.repr(oneValue);
        }

        this.raw(']');
      } else {
        this.string(value);
      }

      return this;
    });

    this.source = '';
    this.functions = functions;
  }

}

exports["default"] = Compiler;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Expression.js":
/*!**********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Expression.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

class Expression {
  constructor(expression) {
    this.expression = expression;
  }
  /**
   * Gets the expression.
   * @returns {string} The expression
   */


  toString() {
    return this.expression;
  }

}

exports["default"] = Expression;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/ExpressionFunction.js":
/*!******************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/ExpressionFunction.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ExpressionFunction {
  constructor(name, compiler, evaluator) {
    _defineProperty(this, "getName", () => {
      return this.name;
    });

    _defineProperty(this, "getCompiler", () => {
      return this.compiler;
    });

    _defineProperty(this, "getEvaluator", () => {
      return this.evaluator;
    });

    this.name = name;
    this.compiler = compiler;
    this.evaluator = evaluator;
  } // TODO not sure how to check if function exists in javascript
  // fromJavascript(javascriptFunctionName, expressionFunctionName = null) {}


}

exports["default"] = ExpressionFunction;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/ExpressionLanguage.js":
/*!******************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/ExpressionLanguage.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Lexer = __webpack_require__(/*! ./Lexer */ "../../../../node_modules/expression-language/lib/Lexer.js");

var _Parser = _interopRequireDefault(__webpack_require__(/*! ./Parser */ "../../../../node_modules/expression-language/lib/Parser.js"));

var _Compiler = _interopRequireDefault(__webpack_require__(/*! ./Compiler */ "../../../../node_modules/expression-language/lib/Compiler.js"));

var _ParsedExpression = _interopRequireDefault(__webpack_require__(/*! ./ParsedExpression */ "../../../../node_modules/expression-language/lib/ParsedExpression.js"));

var _ArrayAdapter = _interopRequireDefault(__webpack_require__(/*! ./Cache/ArrayAdapter */ "../../../../node_modules/expression-language/lib/Cache/ArrayAdapter.js"));

var _LogicException = _interopRequireDefault(__webpack_require__(/*! ./LogicException */ "../../../../node_modules/expression-language/lib/LogicException.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ExpressionLanguage {
  constructor(cache = null, providers = []) {
    _defineProperty(this, "compile", (expression, names = []) => {
      return this.getCompiler().compile(this.parse(expression, names).getNodes()).getSource();
    });

    _defineProperty(this, "evaluate", (expression, values = {}) => {
      return this.parse(expression, Object.keys(values)).getNodes().evaluate(this.functions, values);
    });

    _defineProperty(this, "parse", (expression, names) => {
      if (expression instanceof _ParsedExpression.default) {
        return expression;
      }

      names.sort((a, b) => {
        let a_value = a,
            b_value = b;

        if (typeof a === "object") {
          a_value = Object.values(a)[0];
        }

        if (typeof b === "object") {
          b_value = Object.values(b)[0];
        }

        return a_value.localeCompare(b_value);
      });
      let cacheKeyItems = [];

      for (let name of names) {
        let value = name;

        if (typeof name === "object") {
          let tmpName = Object.keys(name)[0],
              tmpValue = Object.values(name)[0];
          value = tmpName + ":" + tmpValue;
        }

        cacheKeyItems.push(value);
      }

      let cacheItem = this.cache.getItem(this.fixedEncodeURIComponent(expression + "//" + cacheKeyItems.join("|"))),
          parsedExpression = cacheItem.get();

      if (null === parsedExpression) {
        let nodes = this.getParser().parse((0, _Lexer.tokenize)(expression), names);
        parsedExpression = new _ParsedExpression.default(expression, nodes);
        cacheItem.set(parsedExpression);
        this.cache.save(cacheItem);
      }

      return parsedExpression;
    });

    _defineProperty(this, "fixedEncodeURIComponent", str => {
      return encodeURIComponent(str).replace(/[!'()*]/g, function (c) {
        return '%' + c.charCodeAt(0).toString(16);
      });
    });

    _defineProperty(this, "register", (name, compiler, evaluator) => {
      if (null !== this.parser) {
        throw new _LogicException.default("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
      }

      this.functions[name] = {
        compiler: compiler,
        evaluator: evaluator
      };
    });

    _defineProperty(this, "addFunction", expressionFunction => {
      this.register(expressionFunction.getName(), expressionFunction.getCompiler(), expressionFunction.getEvaluator());
    });

    _defineProperty(this, "registerProvider", provider => {
      for (let fn of provider.getFunctions()) {
        this.addFunction(fn);
      }
    });

    _defineProperty(this, "getParser", () => {
      if (null === this.parser) {
        this.parser = new _Parser.default(this.functions);
      }

      return this.parser;
    });

    _defineProperty(this, "getCompiler", () => {
      if (null === this.compiler) {
        this.compiler = new _Compiler.default(this.functions);
      }

      return this.compiler.reset();
    });

    this.functions = [];
    this.parser = null;
    this.compiler = null;
    this.cache = cache || new _ArrayAdapter.default();

    for (let provider of providers) {
      this.registerProvider(provider);
    }
  }
  /**
   * Compiles an expression source code.
   *
   * @param {Expression|string} expression The expression to compile
   * @param {Array} names An array of valid names
   *
   * @returns {string} The compiled javascript source code
   */


  _registerFunctions() {// TODO figure out a way to replicate "constant" function from PHP
  }

}

exports["default"] = ExpressionLanguage;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Lexer.js":
/*!*****************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Lexer.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.tokenize = tokenize;

var _SyntaxError = _interopRequireDefault(__webpack_require__(/*! ./SyntaxError */ "../../../../node_modules/expression-language/lib/SyntaxError.js"));

var _TokenStream = __webpack_require__(/*! ./TokenStream */ "../../../../node_modules/expression-language/lib/TokenStream.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function tokenize(expression) {
  expression = expression.replace(/\r|\n|\t|\v|\f/g, ' ');
  let cursor = 0,
      tokens = [],
      brackets = [],
      end = expression.length;

  while (cursor < end) {
    if (' ' === expression[cursor]) {
      ++cursor;
      continue;
    }

    let number = extractNumber(expression.substr(cursor));

    if (number !== null) {
      // numbers
      number = parseFloat(number); // floats

      tokens.push(new _TokenStream.Token(_TokenStream.Token.NUMBER_TYPE, number, cursor + 1));
      cursor += number.toString().length;
    } else {
      if ('([{'.indexOf(expression[cursor]) >= 0) {
        // opening bracket
        brackets.push([expression[cursor], cursor]);
        tokens.push(new _TokenStream.Token(_TokenStream.Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
        ++cursor;
      } else {
        if (')]}'.indexOf(expression[cursor]) >= 0) {
          if (brackets.length === 0) {
            throw new _SyntaxError.default(`Unexpected "${expression[cursor]}"`, cursor, expression);
          }

          let [expect, cur] = brackets.pop(),
              matchExpect = expect.replace("(", ")").replace("{", "}").replace("[", "]");

          if (expression[cursor] !== matchExpect) {
            throw new _SyntaxError.default(`Unclosed "${expect}"`, cur, expression);
          }

          tokens.push(new _TokenStream.Token(_TokenStream.Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
          ++cursor;
        } else {
          let str = extractString(expression.substr(cursor));

          if (str !== null) {
            //console.log("adding string: " + str);
            tokens.push(new _TokenStream.Token(_TokenStream.Token.STRING_TYPE, str.captured, cursor + 1));
            cursor += str.length; //console.log(`Extracted string: ${str.captured}; Remaining: ${expression.substr(cursor)}`, cursor, expression);
          } else {
            let operator = extractOperator(expression.substr(cursor));

            if (operator) {
              tokens.push(new _TokenStream.Token(_TokenStream.Token.OPERATOR_TYPE, operator, cursor + 1));
              cursor += operator.length;
            } else {
              if (".,?:".indexOf(expression[cursor]) >= 0) {
                tokens.push(new _TokenStream.Token(_TokenStream.Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                ++cursor;
              } else {
                let name = extractName(expression.substr(cursor));

                if (name) {
                  tokens.push(new _TokenStream.Token(_TokenStream.Token.NAME_TYPE, name, cursor + 1));
                  cursor += name.length; //console.log(`Extracted name: ${name}; Remaining: ${expression.substr(cursor)}`, cursor, expression)
                } else {
                  throw new _SyntaxError.default(`Unexpected character "${expression[cursor]}"`, cursor, expression);
                }
              }
            }
          }
        }
      }
    }
  }

  tokens.push(new _TokenStream.Token(_TokenStream.Token.EOF_TYPE, null, cursor + 1));

  if (brackets.length > 0) {
    let [expect, cur] = brackets.pop();
    throw new _SyntaxError.default(`Unclosed "${expect}"`, cur, expression);
  }

  return new _TokenStream.TokenStream(expression, tokens);
}

function extractNumber(str) {
  let extracted = null;
  let matches = str.match(/^[0-9]+(?:.[0-9]+)?/);

  if (matches && matches.length > 0) {
    extracted = matches[0];

    if (extracted.indexOf(".") === -1) {
      extracted = parseInt(extracted);
    } else {
      extracted = parseFloat(extracted);
    }
  }

  return extracted;
}

const strRegex = /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;
/**
 *
 * @param str
 * @returns {null|string}
 */

function extractString(str) {
  let extracted = null;

  if (["'", '"'].indexOf(str.substr(0, 1)) === -1) {
    return extracted;
  }

  let m = strRegex.exec(str);

  if (m !== null && m.length > 0) {
    if (m[1]) {
      extracted = {
        captured: m[1]
      };
    } else {
      extracted = {
        captured: m[2]
      };
    }

    extracted.length = m[0].length;
  }

  return extracted;
}

const operators = ["&&", "and", "||", "or", // Binary
"+", "-", "*", "/", "%", "**", // Arithmetic
"&", "|", "^", // Bitwise
"===", "!==", "!=", "==", "<=", ">=", "<", ">", "matches", "not in", "in", "not", "!", // Comparison
"~", // String concatenation,
'..' // Range function
];
const wordBasedOperators = ["and", "or", "matches", "not in", "in", "not"];
/**
 *
 * @param str
 * @returns {null|string}
 */

function extractOperator(str) {
  let extracted = null;

  for (let operator of operators) {
    if (str.substr(0, operator.length) === operator) {
      // If it is one of the word based operators, make sure there is a space after it
      if (wordBasedOperators.indexOf(operator) >= 0) {
        if (str.substr(0, operator.length + 1) === operator + " ") {
          extracted = operator;
        }
      } else {
        extracted = operator;
      }

      break;
    }
  }

  return extracted;
}

function extractName(str) {
  let extracted = null;
  let matches = str.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);

  if (matches && matches.length > 0) {
    extracted = matches[0];
  }

  return extracted;
}

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/LogicException.js":
/*!**************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/LogicException.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

class LogicException extends Error {
  constructor(message) {
    super(message);
    this.name = "LogicException";
  }

  toString() {
    return `${this.name}: ${this.message}`;
  }

}

exports["default"] = LogicException;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/ArgumentsNode.js":
/*!******************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/ArgumentsNode.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _ArrayNode = _interopRequireDefault(__webpack_require__(/*! ./ArrayNode */ "../../../../node_modules/expression-language/lib/Node/ArrayNode.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ArgumentsNode extends _ArrayNode.default {
  constructor() {
    super();

    _defineProperty(this, "compile", compiler => {
      this.compileArguments(compiler, false);
    });

    this.name = "ArgumentsNode";
  }

  toArray() {
    let array = [];

    for (let pair of this.getKeyValuePairs()) {
      array.push(pair.value);
      array.push(", ");
    }

    array.pop();
    return array;
  }

}

exports["default"] = ArgumentsNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/ArrayNode.js":
/*!**************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/ArrayNode.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

var _ConstantNode = _interopRequireDefault(__webpack_require__(/*! ./ConstantNode */ "../../../../node_modules/expression-language/lib/Node/ConstantNode.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ArrayNode extends _Node.default {
  constructor() {
    super();

    _defineProperty(this, "addElement", (value, key = null) => {
      if (null === key) {
        key = new _ConstantNode.default(++this.index);
      } else {
        if (this.type === 'Array') {
          this.type = 'Object';
        }
      }

      this.nodes[(++this.keyIndex).toString()] = key;
      this.nodes[(++this.keyIndex).toString()] = value;
    });

    _defineProperty(this, "compile", compiler => {
      if (this.type === 'Object') {
        compiler.raw('{');
      } else {
        compiler.raw('[');
      }

      this.compileArguments(compiler, this.type !== "Array");

      if (this.type === 'Object') {
        compiler.raw('}');
      } else {
        compiler.raw(']');
      }
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      let result;

      if (this.type === 'Array') {
        result = [];

        for (let pair of this.getKeyValuePairs()) {
          result.push(pair.value.evaluate(functions, values));
        }
      } else {
        result = {};

        for (let pair of this.getKeyValuePairs()) {
          result[pair.key.evaluate(functions, values)] = pair.value.evaluate(functions, values);
        }
      }

      return result;
    });

    _defineProperty(this, "getKeyValuePairs", () => {
      let pairs = [];
      let nodes = Object.values(this.nodes);
      let i,
          j,
          pair,
          chunk = 2;

      for (i = 0, j = nodes.length; i < j; i += chunk) {
        pair = nodes.slice(i, i + chunk);
        pairs.push({
          key: pair[0],
          value: pair[1]
        });
      }

      return pairs;
    });

    _defineProperty(this, "compileArguments", (compiler, withKeys = true) => {
      let first = true;

      for (let pair of this.getKeyValuePairs()) {
        if (!first) {
          compiler.raw(', ');
        }

        first = false;

        if (withKeys) {
          compiler.compile(pair.key).raw(': ');
        }

        compiler.compile(pair.value);
      }
    });

    this.name = "ArrayNode";
    this.type = "Array";
    this.index = -1;
    this.keyIndex = -1;
  }

  toArray() {
    let value = {};

    for (let pair of this.getKeyValuePairs()) {
      value[pair.key.attributes.value] = pair.value;
    }

    let array = [];

    if (this.isHash(value)) {
      for (let k of Object.keys(value)) {
        array.push(', ');
        array.push(new _ConstantNode.default(k));
        array.push(': ');
        array.push(value[k]);
      }

      array[0] = '{';
      array.push('}');
    } else {
      for (let v of Object.values(value)) {
        array.push(', ');
        array.push(v);
      }

      array[0] = '[';
      array.push(']');
    }

    return array;
  }

}

exports["default"] = ArrayNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/BinaryNode.js":
/*!***************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/BinaryNode.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

var _range = __webpack_require__(/*! ../lib/range */ "../../../../node_modules/expression-language/lib/lib/range.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class BinaryNode extends _Node.default {
  constructor(_operator, _left, _right) {
    super({
      left: _left,
      right: _right
    }, {
      operator: _operator
    });

    _defineProperty(this, "compile", compiler => {
      let operator = this.attributes.operator;

      if ('matches' === operator) {
        compiler.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")");
        return;
      }

      if (BinaryNode.functions[operator] !== undefined) {
        compiler.raw(`${BinaryNode.functions[operator]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")");
        return;
      }

      if (BinaryNode.operators[operator] !== undefined) {
        operator = BinaryNode.operators[operator];
      }

      compiler.raw("(").compile(this.nodes.left).raw(' ').raw(operator).raw(' ').compile(this.nodes.right).raw(")");
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      let operator = this.attributes.operator,
          left = this.nodes.left.evaluate(functions, values); //console.log("Evaluating: ", left, operator, right);

      if (BinaryNode.functions[operator] !== undefined) {
        let right = this.nodes.right.evaluate(functions, values);

        switch (operator) {
          case 'not in':
            return right.indexOf(left) === -1;

          case 'in':
            return right.indexOf(left) >= 0;

          case '..':
            return (0, _range.range)(left, right);

          case '**':
            return Math.pow(left, right);
        }
      }

      let right = null;

      switch (operator) {
        case 'or':
        case '||':
          if (!left) {
            right = this.nodes.right.evaluate(functions, values);
          }

          return left || right;

        case 'and':
        case '&&':
          if (left) {
            right = this.nodes.right.evaluate(functions, values);
          }

          return left && right;
      }

      right = this.nodes.right.evaluate(functions, values);

      switch (operator) {
        case '|':
          return left | right;

        case '^':
          return left ^ right;

        case '&':
          return left & right;

        case '==':
          return left == right;

        case '===':
          return left === right;

        case '!=':
          return left != right;

        case '!==':
          return left !== right;

        case '<':
          return left < right;

        case '>':
          return left > right;

        case '>=':
          return left >= right;

        case '<=':
          return left <= right;

        case 'not in':
          return right.indexOf(left) === -1;

        case 'in':
          return right.indexOf(left) >= 0;

        case '+':
          return left + right;

        case '-':
          return left - right;

        case '~':
          return left.toString() + right.toString();

        case '*':
          return left * right;

        case '/':
          return left / right;

        case '%':
          return left % right;

        case 'matches':
          let res = right.match(BinaryNode.regex_expression);
          let regexp = new RegExp(res[1], res[2]);
          return regexp.test(left);
      }
    });

    this.name = "BinaryNode";
  }

  toArray() {
    return ["(", this.nodes.left, ' ' + this.attributes.operator + ' ', this.nodes.right, ")"];
  }

}

exports["default"] = BinaryNode;

_defineProperty(BinaryNode, "regex_expression", /\/(.+)\/(.*)/);

_defineProperty(BinaryNode, "operators", {
  '~': '.',
  'and': '&&',
  'or': '||'
});

_defineProperty(BinaryNode, "functions", {
  '**': 'Math.pow',
  '..': 'range',
  'in': 'includes',
  'not in': '!includes'
});

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/ConditionalNode.js":
/*!********************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/ConditionalNode.js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ConditionalNode extends _Node.default {
  constructor(expr1, expr2, expr3) {
    super({
      expr1: expr1,
      expr2: expr2,
      expr3: expr3
    });

    _defineProperty(this, "compile", compiler => {
      compiler.raw('((').compile(this.nodes.expr1).raw(') ? (').compile(this.nodes.expr2).raw(') : (').compile(this.nodes.expr3).raw('))');
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      if (this.nodes.expr1.evaluate(functions, values)) {
        return this.nodes.expr2.evaluate(functions, values);
      }

      return this.nodes.expr3.evaluate(functions, values);
    });

    this.name = 'ConditionalNode';
  }

  toArray() {
    return ['(', this.nodes.expr1, ' ? ', this.nodes.expr2, ' : ', this.nodes.expr3, ')'];
  }

}

exports["default"] = ConditionalNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/ConstantNode.js":
/*!*****************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/ConstantNode.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ConstantNode extends _Node.default {
  constructor(_value, isIdentifier = false) {
    super({}, {
      value: _value
    });

    _defineProperty(this, "compile", compiler => {
      compiler.repr(this.attributes.value, this.isIdentifier);
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      return this.attributes.value;
    });

    _defineProperty(this, "toArray", () => {
      let array = [],
          value = this.attributes.value;

      if (this.isIdentifier) {
        array.push(value);
      } else if (true === value) {
        array.push('true');
      } else if (false === value) {
        array.push('false');
      } else if (null === value) {
        array.push('null');
      } else if (typeof value === "number") {
        array.push(value);
      } else if (typeof value === "string") {
        array.push(this.dumpString(value));
      } else if (Array.isArray(value)) {
        for (let v of value) {
          array.push(',');
          array.push(new ConstantNode(v));
        }

        array[0] = '[';
        array.push(']');
      } else if (this.isHash(value)) {
        for (let k of Object.keys(value)) {
          array.push(', ');
          array.push(new ConstantNode(k));
          array.push(': ');
          array.push(new ConstantNode(value[k]));
        }

        array[0] = '{';
        array.push('}');
      }

      return array;
    });

    this.isIdentifier = isIdentifier;
    this.name = 'ConstantNode';
  }

}

exports["default"] = ConstantNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/FunctionNode.js":
/*!*****************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/FunctionNode.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class FunctionNode extends _Node.default {
  constructor(name, _arguments2) {
    //console.log("Creating function node: ", name, _arguments);
    super({
      arguments: _arguments2
    }, {
      name: name
    });

    _defineProperty(this, "compile", compiler => {
      let _arguments = [];

      for (let node of Object.values(this.nodes.arguments.nodes)) {
        _arguments.push(compiler.subcompile(node));
      }

      let fn = compiler.getFunction(this.attributes.name);
      compiler.raw(fn.compiler.apply(null, _arguments));
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      let _arguments = [values];

      for (let node of Object.values(this.nodes.arguments.nodes)) {
        //console.log("Testing: ", node, functions, values);
        _arguments.push(node.evaluate(functions, values));
      }

      return functions[this.attributes.name]['evaluator'].apply(null, _arguments);
    });

    this.name = 'FunctionNode';
  }

  toArray() {
    let array = [];
    array.push(this.attributes.name);

    for (let node of Object.values(this.nodes.arguments.nodes)) {
      array.push(', ');
      array.push(node);
    }

    array[1] = '(';
    array.push(')');
    return array;
  }

}

exports["default"] = FunctionNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/GetAttrNode.js":
/*!****************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/GetAttrNode.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class GetAttrNode extends _Node.default {
  constructor(node, attribute, _arguments, type) {
    super({
      node: node,
      attribute: attribute,
      arguments: _arguments
    }, {
      type: type
    });

    _defineProperty(this, "compile", compiler => {
      switch (this.attributes.type) {
        case GetAttrNode.PROPERTY_CALL:
          compiler.compile(this.nodes.node).raw('.').raw(this.nodes.attribute.attributes.value);
          break;

        case GetAttrNode.METHOD_CALL:
          compiler.compile(this.nodes.node).raw('.').raw(this.nodes.attribute.attributes.value).raw('(').compile(this.nodes.arguments).raw(')');
          break;

        case GetAttrNode.ARRAY_CALL:
          compiler.compile(this.nodes.node).raw('[').compile(this.nodes.attribute).raw(']');
          break;
      }
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      switch (this.attributes.type) {
        case GetAttrNode.PROPERTY_CALL:
          let obj = this.nodes.node.evaluate(functions, values),
              property = this.nodes.attribute.attributes.value;

          if (typeof obj !== "object") {
            throw new Error(`Unable to get property "${property}" on a non-object: ` + typeof obj);
          }

          return obj[property];

        case GetAttrNode.METHOD_CALL:
          let obj2 = this.nodes.node.evaluate(functions, values),
              method = this.nodes.attribute.attributes.value;

          if (typeof obj2 !== 'object') {
            throw new Error(`Unable to call method "${method}" on a non-object: ` + typeof obj2);
          }

          if (obj2[method] === undefined) {
            throw new Error(`Method "${method}" is undefined on object.`);
          }

          if (typeof obj2[method] != 'function') {
            throw new Error(`Method "${method}" is not a function on object.`);
          }

          let evaluatedArgs = this.nodes.arguments.evaluate(functions, values);
          return obj2[method].apply(null, evaluatedArgs);

        case GetAttrNode.ARRAY_CALL:
          let array = this.nodes.node.evaluate(functions, values);

          if (!Array.isArray(array) && typeof array !== 'object') {
            throw new Error(`Unable to get an item on a non-array: ` + typeof array);
          }

          return array[this.nodes.attribute.evaluate(functions, values)];
      }
    });

    this.name = 'GetAttrNode';
  }

  toArray() {
    switch (this.attributes.type) {
      case GetAttrNode.PROPERTY_CALL:
        return [this.nodes.node, '.', this.nodes.attribute];

      case GetAttrNode.METHOD_CALL:
        return [this.nodes.node, '.', this.nodes.attribute, '(', this.nodes.arguments, ')'];

      case GetAttrNode.ARRAY_CALL:
        return [this.nodes.node, '[', this.nodes.attribute, ']'];
    }
  }

}

exports["default"] = GetAttrNode;

_defineProperty(GetAttrNode, "PROPERTY_CALL", 1);

_defineProperty(GetAttrNode, "METHOD_CALL", 2);

_defineProperty(GetAttrNode, "ARRAY_CALL", 3);

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/NameNode.js":
/*!*************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/NameNode.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class NameNode extends _Node.default {
  constructor(name) {
    super({}, {
      name: name
    });

    _defineProperty(this, "compile", compiler => {
      compiler.raw(this.attributes.name);
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      //console.log(`Checking for value of "${this.attributes.name}"`);
      let value = values[this.attributes.name]; //console.log(`Value: ${value}`);

      return value;
    });

    this.name = 'NameNode';
  }

  toArray() {
    return [this.attributes.name];
  }

}

exports["default"] = NameNode;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/Node.js":
/*!*********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/Node.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _isScalar = __webpack_require__(/*! ../lib/is-scalar */ "../../../../node_modules/expression-language/lib/lib/is-scalar.js");

var _addcslashes = __webpack_require__(/*! ../lib/addcslashes */ "../../../../node_modules/expression-language/lib/lib/addcslashes.js");

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class Node {
  constructor(nodes = {}, attributes = {}) {
    _defineProperty(this, "compile", compiler => {
      for (let node of Object.values(this.nodes)) {
        node.compile(compiler);
      }
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      let results = [];

      for (let node of Object.values(this.nodes)) {
        results.push(node.evaluate(functions, values));
      }

      return results;
    });

    _defineProperty(this, "dump", () => {
      let dump = "";

      for (let v of this.toArray()) {
        dump += (0, _isScalar.is_scalar)(v) ? v : v.dump();
      }

      return dump;
    });

    _defineProperty(this, "dumpString", value => {
      return `"${(0, _addcslashes.addcslashes)(value, "\0\t\"\\")}"`;
    });

    _defineProperty(this, "isHash", value => {
      let expectedKey = 0;

      for (let key of Object.keys(value)) {
        key = parseInt(key);

        if (key !== expectedKey++) {
          return true;
        }
      }

      return false;
    });

    this.name = 'Node';
    this.nodes = nodes;
    this.attributes = attributes;
  }

  toString() {
    let attributes = [];

    for (let name of Object.keys(this.attributes)) {
      let oneAttribute = 'null';

      if (this.attributes[name]) {
        oneAttribute = this.attributes[name].toString();
      }

      attributes.push(`${name}: '${oneAttribute}'`);
    }

    let repr = [this.name + "(" + attributes.join(", ")];

    if (this.nodes.length > 0) {
      for (let node of Object.values(this.nodes)) {
        let lines = node.toString().split("\n");

        for (let line of lines) {
          repr.push("    " + line);
        }
      }

      repr.push(")");
    } else {
      repr[0] += ")";
    }

    return repr.join("\n");
  }

  toArray() {
    throw new Error(`Dumping a "${this.name}" instance is not supported yet.`);
  }

}

exports["default"] = Node;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Node/UnaryNode.js":
/*!**************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Node/UnaryNode.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class UnaryNode extends _Node.default {
  constructor(operator, node) {
    super({
      node: node
    }, {
      operator: operator
    });

    _defineProperty(this, "compile", compiler => {
      compiler.raw('(').raw(UnaryNode.operators[this.attributes.operator]).compile(this.nodes.node).raw(')');
    });

    _defineProperty(this, "evaluate", (functions, values) => {
      let value = this.nodes.node.evaluate(functions, values);

      switch (this.attributes.operator) {
        case 'not':
        case '!':
          return !value;

        case '-':
          return -value;
      }

      return value;
    });

    this.name = 'UnaryNode';
  }

  toArray() {
    return ['(', this.attributes.operator + " ", this.nodes.node, ')'];
  }

}

exports["default"] = UnaryNode;

_defineProperty(UnaryNode, "operators", {
  '!': '!',
  'not': '!',
  '+': '+',
  '-': '-'
});

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/ParsedExpression.js":
/*!****************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/ParsedExpression.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Expression = _interopRequireDefault(__webpack_require__(/*! ./Expression */ "../../../../node_modules/expression-language/lib/Expression.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class ParsedExpression extends _Expression.default {
  constructor(expression, nodes) {
    super(expression);

    _defineProperty(this, "getNodes", () => {
      return this.nodes;
    });

    this.nodes = nodes;
  }

}

exports["default"] = ParsedExpression;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Parser.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Parser.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = exports.OPERATOR_RIGHT = exports.OPERATOR_LEFT = void 0;

var _SyntaxError = _interopRequireDefault(__webpack_require__(/*! ./SyntaxError */ "../../../../node_modules/expression-language/lib/SyntaxError.js"));

var _TokenStream = __webpack_require__(/*! ./TokenStream */ "../../../../node_modules/expression-language/lib/TokenStream.js");

var _Node = _interopRequireDefault(__webpack_require__(/*! ./Node/Node */ "../../../../node_modules/expression-language/lib/Node/Node.js"));

var _BinaryNode = _interopRequireDefault(__webpack_require__(/*! ./Node/BinaryNode */ "../../../../node_modules/expression-language/lib/Node/BinaryNode.js"));

var _UnaryNode = _interopRequireDefault(__webpack_require__(/*! ./Node/UnaryNode */ "../../../../node_modules/expression-language/lib/Node/UnaryNode.js"));

var _ConstantNode = _interopRequireDefault(__webpack_require__(/*! ./Node/ConstantNode */ "../../../../node_modules/expression-language/lib/Node/ConstantNode.js"));

var _ConditionalNode = _interopRequireDefault(__webpack_require__(/*! ./Node/ConditionalNode */ "../../../../node_modules/expression-language/lib/Node/ConditionalNode.js"));

var _FunctionNode = _interopRequireDefault(__webpack_require__(/*! ./Node/FunctionNode */ "../../../../node_modules/expression-language/lib/Node/FunctionNode.js"));

var _NameNode = _interopRequireDefault(__webpack_require__(/*! ./Node/NameNode */ "../../../../node_modules/expression-language/lib/Node/NameNode.js"));

var _ArrayNode = _interopRequireDefault(__webpack_require__(/*! ./Node/ArrayNode */ "../../../../node_modules/expression-language/lib/Node/ArrayNode.js"));

var _ArgumentsNode = _interopRequireDefault(__webpack_require__(/*! ./Node/ArgumentsNode */ "../../../../node_modules/expression-language/lib/Node/ArgumentsNode.js"));

var _GetAttrNode = _interopRequireDefault(__webpack_require__(/*! ./Node/GetAttrNode */ "../../../../node_modules/expression-language/lib/Node/GetAttrNode.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

const OPERATOR_LEFT = 1;
exports.OPERATOR_LEFT = OPERATOR_LEFT;
const OPERATOR_RIGHT = 2;
exports.OPERATOR_RIGHT = OPERATOR_RIGHT;

class Parser {
  constructor(functions = {}) {
    _defineProperty(this, "functions", {});

    _defineProperty(this, "unaryOperators", {
      'not': {
        'precedence': 50
      },
      '!': {
        'precedence': 50
      },
      '-': {
        'precedence': 500
      },
      '+': {
        'precedence': 500
      }
    });

    _defineProperty(this, "binaryOperators", {
      'or': {
        'precedence': 10,
        'associativity': OPERATOR_LEFT
      },
      '||': {
        'precedence': 10,
        'associativity': OPERATOR_LEFT
      },
      'and': {
        'precedence': 15,
        'associativity': OPERATOR_LEFT
      },
      '&&': {
        'precedence': 15,
        'associativity': OPERATOR_LEFT
      },
      '|': {
        'precedence': 16,
        'associativity': OPERATOR_LEFT
      },
      '^': {
        'precedence': 17,
        'associativity': OPERATOR_LEFT
      },
      '&': {
        'precedence': 18,
        'associativity': OPERATOR_LEFT
      },
      '==': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '===': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '!=': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '!==': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '<': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '>': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '>=': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '<=': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      'not in': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      'in': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      'matches': {
        'precedence': 20,
        'associativity': OPERATOR_LEFT
      },
      '..': {
        'precedence': 25,
        'associativity': OPERATOR_LEFT
      },
      '+': {
        'precedence': 30,
        'associativity': OPERATOR_LEFT
      },
      '-': {
        'precedence': 30,
        'associativity': OPERATOR_LEFT
      },
      '~': {
        'precedence': 40,
        'associativity': OPERATOR_LEFT
      },
      '*': {
        'precedence': 60,
        'associativity': OPERATOR_LEFT
      },
      '/': {
        'precedence': 60,
        'associativity': OPERATOR_LEFT
      },
      '%': {
        'precedence': 60,
        'associativity': OPERATOR_LEFT
      },
      '**': {
        'precedence': 200,
        'associativity': OPERATOR_RIGHT
      }
    });

    _defineProperty(this, "parse", (tokenStream, names = []) => {
      this.tokenStream = tokenStream;
      this.names = names;
      this.objectMatches = {};
      this.cachedNames = null;
      this.nestedExecutions = 0; //console.log("tokens: ", tokenStream.toString());

      let node = this.parseExpression();

      if (!this.tokenStream.isEOF()) {
        throw new _SyntaxError.default(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}".`, this.tokenStream.current.cursor, this.tokenStream.expression);
      }

      return node;
    });

    _defineProperty(this, "parseExpression", (precedence = 0) => {
      let expr = this.getPrimary();
      let token = this.tokenStream.current;
      this.nestedExecutions++;

      if (this.nestedExecutions > 100) {
        throw new Error("Way to many executions on '" + token.toString() + "' of '" + this.tokenStream.toString() + "'");
      } //console.log("Parsing: ", token);


      while (token.test(_TokenStream.Token.OPERATOR_TYPE) && this.binaryOperators[token.value] !== undefined && this.binaryOperators[token.value] !== null && this.binaryOperators[token.value].precedence >= precedence) {
        let op = this.binaryOperators[token.value];
        this.tokenStream.next();
        let expr1 = this.parseExpression(OPERATOR_LEFT === op.associativity ? op.precedence + 1 : op.precedence);
        expr = new _BinaryNode.default(token.value, expr, expr1);
        token = this.tokenStream.current;
      }

      if (0 === precedence) {
        return this.parseConditionalExpression(expr);
      }

      return expr;
    });

    _defineProperty(this, "getPrimary", () => {
      let token = this.tokenStream.current;

      if (token.test(_TokenStream.Token.OPERATOR_TYPE) && this.unaryOperators[token.value] !== undefined && this.unaryOperators[token.value] !== null) {
        let operator = this.unaryOperators[token.value];
        this.tokenStream.next();
        let expr = this.parseExpression(operator.precedence);
        return this.parsePostfixExpression(new _UnaryNode.default(token.value, expr));
      }

      if (token.test(_TokenStream.Token.PUNCTUATION_TYPE, "(")) {
        //console.log("Found '('.", token.type, token.value);
        this.tokenStream.next();
        let expr = this.parseExpression();
        this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ")", "An opened parenthesis is not properly closed");
        return this.parsePostfixExpression(expr);
      }

      return this.parsePrimaryExpression();
    });

    _defineProperty(this, "hasVariable", name => {
      return this.getNames().indexOf(name) >= 0;
    });

    _defineProperty(this, "getNames", () => {
      if (this.cachedNames !== null) {
        return this.cachedNames;
      }

      if (this.names && this.names.length > 0) {
        let names = [];
        let index = 0;
        this.objectMatches = {};

        for (let name of this.names) {
          if (typeof name === "object") {
            this.objectMatches[Object.values(name)[0]] = index;
            names.push(Object.keys(name)[0]);
            names.push(Object.values(name)[0]);
          } else {
            names.push(name);
          }

          index++;
        }

        this.cachedNames = names;
        return names;
      }

      return [];
    });

    _defineProperty(this, "parseArrayExpression", () => {
      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, '[', 'An array element was expected');
      let node = new _ArrayNode.default(),
          first = true;

      while (!this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, ']')) {
        if (!first) {
          this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"); // trailing ,?

          if (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, "]")) {
            break;
          }
        }

        first = false;
        node.addElement(this.parseExpression());
      }

      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, "]", "An opened array is not properly closed");
      return node;
    });

    _defineProperty(this, "parseHashExpression", () => {
      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, "{", "A hash element was expected");
      let node = new _ArrayNode.default(),
          first = true;

      while (!this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, '}')) {
        if (!first) {
          this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"); // trailing ,?

          if (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, "}")) {
            break;
          }
        }

        first = false;
        let key = null; // a hash key can be:
        //
        //  * a number -- 12
        //  * a string -- 'a'
        //  * a name, which is equivalent to a string -- a
        //  * an expression, which must be enclosed in parentheses -- (1 + 2)

        if (this.tokenStream.current.test(_TokenStream.Token.STRING_TYPE) || this.tokenStream.current.test(_TokenStream.Token.NAME_TYPE) || this.tokenStream.current.test(_TokenStream.Token.NUMBER_TYPE)) {
          key = new _ConstantNode.default(this.tokenStream.current.value);
          this.tokenStream.next();
        } else if (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, "(")) {
          key = this.parseExpression();
        } else {
          let current = this.tokenStream.current;
          throw new _SyntaxError.default(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${current.type}" of value "${current.value}"`, current.cursor, this.tokenStream.expression);
        }

        this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ":", "A hash key must be followed by a colon (:)");
        let value = this.parseExpression();
        node.addElement(value, key);
      }

      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, "}", "An opened hash is not properly closed");
      return node;
    });

    _defineProperty(this, "parsePostfixExpression", node => {
      let token = this.tokenStream.current;

      while (_TokenStream.Token.PUNCTUATION_TYPE === token.type) {
        if ('.' === token.value) {
          this.tokenStream.next();
          token = this.tokenStream.current;
          this.tokenStream.next();

          if (_TokenStream.Token.NAME_TYPE !== token.type && ( // Operators like "not" and "matches" are valid method or property names,
          //
          // In other words, besides NAME_TYPE, OPERATOR_TYPE could also be parsed as a property or method.
          // This is because operators are processed by the lexer prior to names. So "not" in "foo.not()" or "matches" in "foo.matches" will be recognized as an operator first.
          // But in fact, "not" and "matches" in such expressions shall be parsed as method or property names.
          //
          // And this ONLY works if the operator consists of valid characters for a property or method name.
          //
          // Other types, such as STRING_TYPE and NUMBER_TYPE, can't be parsed as property nor method names.
          //
          // As a result, if $token is NOT an operator OR $token->value is NOT a valid property or method name, an exception shall be thrown.
          _TokenStream.Token.OPERATOR_TYPE !== token.type || !/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(token.value))) {
            throw new _SyntaxError.default('Expected name', token.cursor, this.tokenStream.expression);
          }

          let arg = new _ConstantNode.default(token.value, true),
              _arguments = new _ArgumentsNode.default(),
              type = null;

          if (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, "(")) {
            type = _GetAttrNode.default.METHOD_CALL;

            for (let n of Object.values(this.parseArguments().nodes)) {
              _arguments.addElement(n);
            }
          } else {
            type = _GetAttrNode.default.PROPERTY_CALL;
          }

          node = new _GetAttrNode.default(node, arg, _arguments, type);
        } else if ('[' === token.value) {
          this.tokenStream.next();
          let arg = this.parseExpression();
          this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, "]");
          node = new _GetAttrNode.default(node, arg, new _ArgumentsNode.default(), _GetAttrNode.default.ARRAY_CALL);
        } else {
          break;
        }

        token = this.tokenStream.current;
      }

      return node;
    });

    _defineProperty(this, "parseArguments", () => {
      let args = [];
      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, "(", "A list of arguments must begin with an opening parenthesis");

      while (!this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, ")")) {
        if (args.length !== 0) {
          this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ",", "Arguments must be separated by a comma");
        }

        args.push(this.parseExpression());
      }

      this.tokenStream.expect(_TokenStream.Token.PUNCTUATION_TYPE, ")", "A list of arguments must be closed by a parenthesis");
      return new _Node.default(args);
    });

    this.functions = functions;
    this.tokenStream = null;
    this.names = null;
    this.objectMatches = {};
    this.cachedNames = null;
    this.nestedExecutions = 0;
  }

  parseConditionalExpression(expr) {
    while (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, "?")) {
      this.tokenStream.next();
      let expr2, expr3;

      if (!this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, ":")) {
        expr2 = this.parseExpression();

        if (this.tokenStream.current.test(_TokenStream.Token.PUNCTUATION_TYPE, ":")) {
          this.tokenStream.next();
          expr3 = this.parseExpression();
        } else {
          expr3 = new _ConstantNode.default(null);
        }
      } else {
        this.tokenStream.next();
        expr2 = expr;
        expr3 = this.parseExpression();
      }

      expr = new _ConditionalNode.default(expr, expr2, expr3);
    }

    return expr;
  }

  parsePrimaryExpression() {
    let token = this.tokenStream.current,
        node = null;

    switch (token.type) {
      case _TokenStream.Token.NAME_TYPE:
        this.tokenStream.next();

        switch (token.value) {
          case 'true':
          case 'TRUE':
            return new _ConstantNode.default(true);

          case 'false':
          case 'FALSE':
            return new _ConstantNode.default(false);

          case 'null':
          case 'NULL':
            return new _ConstantNode.default(null);

          default:
            if ("(" === this.tokenStream.current.value) {
              if (this.functions[token.value] === undefined) {
                throw new _SyntaxError.default(`The function "${token.value}" does not exist`, token.cursor, this.tokenStream.expression, token.values, Object.keys(this.functions));
              }

              node = new _FunctionNode.default(token.value, this.parseArguments());
            } else {
              if (!this.hasVariable(token.value)) {
                throw new _SyntaxError.default(`Variable "${token.value}" is not valid`, token.cursor, this.tokenStream.expression, token.value, this.getNames());
              }

              let name = token.value; //console.log("Checking for object matches: ", name, this.objectMatches, this.getNames());

              if (this.objectMatches[name] !== undefined) {
                name = this.getNames()[this.objectMatches[name]];
              }

              node = new _NameNode.default(name);
            }

        }

        break;

      case _TokenStream.Token.NUMBER_TYPE:
      case _TokenStream.Token.STRING_TYPE:
        this.tokenStream.next();
        return new _ConstantNode.default(token.value);

      default:
        if (token.test(_TokenStream.Token.PUNCTUATION_TYPE, "[")) {
          node = this.parseArrayExpression();
        } else if (token.test(_TokenStream.Token.PUNCTUATION_TYPE, "{")) {
          node = this.parseHashExpression();
        } else {
          throw new _SyntaxError.default(`Unexpected token "${token.type}" of value "${token.value}"`, token.cursor, this.tokenStream.expression);
        }

    }

    return this.parsePostfixExpression(node);
  }

}

exports["default"] = Parser;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js":
/*!*************************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

class AbstractProvider {
  getFunctions() {
    throw new Error("getFunctions must be implemented by " + this.name);
  }

}

exports["default"] = AbstractProvider;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Provider/ArrayProvider.js":
/*!**********************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Provider/ArrayProvider.js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.arrayIntersectFn = exports.countFn = exports.implodeFn = exports["default"] = void 0;

var _ExpressionFunction = _interopRequireDefault(__webpack_require__(/*! ../ExpressionFunction */ "../../../../node_modules/expression-language/lib/ExpressionFunction.js"));

var _AbstractProvider = _interopRequireDefault(__webpack_require__(/*! ./AbstractProvider */ "../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js"));

var _array_intersect = _interopRequireDefault(__webpack_require__(/*! locutus/php/array/array_intersect */ "../../../../node_modules/locutus/php/array/array_intersect.js"));

var _count = _interopRequireDefault(__webpack_require__(/*! locutus/php/array/count */ "../../../../node_modules/locutus/php/array/count.js"));

var _implode = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/implode */ "../../../../node_modules/locutus/php/strings/implode.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

class ArrayProvider extends _AbstractProvider.default {
  getFunctions() {
    return [implodeFn, countFn, arrayIntersectFn];
  }

}

exports["default"] = ArrayProvider;
const implodeFn = new _ExpressionFunction.default('implode', function compiler(glue, pieces) {
  //console.log("compile implode: ", pieces, glue, typeof pieces);
  return `implode(${glue}, ${pieces})`;
}, function evaluator(values, glue, pieces) {
  return (0, _implode.default)(glue, pieces);
});
exports.implodeFn = implodeFn;
const countFn = new _ExpressionFunction.default('count', function compiler(mixedVar, mode) {
  let remaining = '';

  if (mode) {
    remaining = `, ${mode}`;
  }

  return `count(${mixedVar}${remaining})`;
}, function evaluator(values, mixedVar, mode) {
  return (0, _count.default)(mixedVar, mode);
});
exports.countFn = countFn;
const arrayIntersectFn = new _ExpressionFunction.default('array_intersect', function compiler(arr1, ...rest) {
  let remaining = '';

  if (rest.length > 0) {
    remaining = ", " + rest.join(", ");
  }

  return `array_intersect(${arr1}${remaining})`;
}, function evaluator(values) {
  let newArgs = [],
      allArrays = true;

  for (let i = 1; i < arguments.length; i++) {
    newArgs.push(arguments[i]);

    if (!Array.isArray(arguments[i])) {
      allArrays = false;
    }
  }

  let res = _array_intersect.default.apply(null, newArgs);

  if (allArrays) {
    return Object.values(res);
  }

  return res;
});
exports.arrayIntersectFn = arrayIntersectFn;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Provider/BasicProvider.js":
/*!**********************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Provider/BasicProvider.js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.issetFn = exports["default"] = void 0;

var _ExpressionFunction = _interopRequireDefault(__webpack_require__(/*! ../ExpressionFunction */ "../../../../node_modules/expression-language/lib/ExpressionFunction.js"));

var _AbstractProvider = _interopRequireDefault(__webpack_require__(/*! ./AbstractProvider */ "../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

class ArrayProvider extends _AbstractProvider.default {
  getFunctions() {
    return [issetFn];
  }

}

exports["default"] = ArrayProvider;
const issetFn = new _ExpressionFunction.default('isset', function compiler(variable) {
  return `isset(${variable})`;
}, function evaluator(values, variable) {
  let baseName = "",
      parts = [],
      gathering = "",
      gathered = "";

  for (let i = 0; i < variable.length; i++) {
    let char = variable[i];

    if (char === "]") {
      gathering = "";
      parts.push({
        type: 'array',
        index: gathered.replace(/"/g, "").replace(/'/g, "")
      });
      gathered = "";
      continue;
    }

    if (char === "[") {
      gathering = "array";
      gathered = "";
      continue;
    }

    if (gathering === "object" && (!/[A-z0-9_]/.test(char) || i === variable.length - 1)) {
      let lastChar = false;

      if (i === variable.length - 1) {
        gathered += char;
        lastChar = true;
      }

      gathering = "";
      parts.push({
        type: 'object',
        attribute: gathered
      });
      gathered = "";

      if (lastChar) {
        continue;
      }
    }

    if (char === ".") {
      gathering = "object";
      gathered = "";
      continue;
    }

    if (gathering) {
      gathered += char;
    } else {
      baseName += char;
    }
  }

  if (parts.length > 0) {
    //console.log("Parts: ", parts);
    if (values[baseName] !== undefined) {
      let baseVar = values[baseName];

      for (let part of parts) {
        if (part.type === "array") {
          if (baseVar[part.index] === undefined) {
            return false;
          }

          baseVar = baseVar[part.index];
        }

        if (part.type === "object") {
          if (baseVar[part.attribute] === undefined) {
            return false;
          }

          baseVar = baseVar[part.attribute];
        }
      }

      return true;
    }

    return false;
  } else {
    return values[baseName] !== undefined;
  }
});
exports.issetFn = issetFn;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Provider/DateProvider.js":
/*!*********************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Provider/DateProvider.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _AbstractProvider = _interopRequireDefault(__webpack_require__(/*! ./AbstractProvider */ "../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js"));

var _ExpressionFunction = _interopRequireDefault(__webpack_require__(/*! ../ExpressionFunction */ "../../../../node_modules/expression-language/lib/ExpressionFunction.js"));

var _date = _interopRequireDefault(__webpack_require__(/*! locutus/php/datetime/date */ "../../../../node_modules/locutus/php/datetime/date.js"));

var _strtotime = _interopRequireDefault(__webpack_require__(/*! locutus/php/datetime/strtotime */ "../../../../node_modules/locutus/php/datetime/strtotime.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

class DateProvider extends _AbstractProvider.default {
  getFunctions() {
    return [new _ExpressionFunction.default('date', function (format, timestamp) {
      let remaining = "";

      if (timestamp) {
        remaining = `, ${timestamp}`;
      }

      return `date(${format}${remaining})`;
    }, function (values, format, timestamp) {
      return (0, _date.default)(format, timestamp);
    }), new _ExpressionFunction.default('strtotime', function (str, now) {
      let remaining = "";

      if (now) {
        remaining = `, ${now}`;
      }

      return `strtotime(${str}${remaining})`;
    }, function (values, str, now) {
      return (0, _strtotime.default)(str, now);
    })];
  }

}

exports["default"] = DateProvider;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/Provider/StringProvider.js":
/*!***********************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/Provider/StringProvider.js ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _ExpressionFunction = _interopRequireDefault(__webpack_require__(/*! ../ExpressionFunction */ "../../../../node_modules/expression-language/lib/ExpressionFunction.js"));

var _AbstractProvider = _interopRequireDefault(__webpack_require__(/*! ./AbstractProvider */ "../../../../node_modules/expression-language/lib/Provider/AbstractProvider.js"));

var _explode = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/explode */ "../../../../node_modules/locutus/php/strings/explode.js"));

var _strlen = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/strlen */ "../../../../node_modules/locutus/php/strings/strlen.js"));

var _strtolower = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/strtolower */ "../../../../node_modules/locutus/php/strings/strtolower.js"));

var _strtoupper = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/strtoupper */ "../../../../node_modules/locutus/php/strings/strtoupper.js"));

var _substr = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/substr */ "../../../../node_modules/locutus/php/strings/substr.js"));

var _strstr = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/strstr */ "../../../../node_modules/locutus/php/strings/strstr.js"));

var _stristr = _interopRequireDefault(__webpack_require__(/*! locutus/php/strings/stristr */ "../../../../node_modules/locutus/php/strings/stristr.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

class StringProvider extends _AbstractProvider.default {
  getFunctions() {
    return [new _ExpressionFunction.default('strtolower', str => {
      return 'strtolower(' + str + ')';
    }, (args, str) => {
      return (0, _strtolower.default)(str);
    }), new _ExpressionFunction.default('strtoupper', str => {
      return 'strtoupper(' + str + ')';
    }, (args, str) => {
      return (0, _strtoupper.default)(str);
    }), new _ExpressionFunction.default('explode', (delimiter, string, limit = 'null') => {
      return `explode(${delimiter}, ${string}, ${limit})`;
    }, (values, delimiter, string, limit = null) => {
      return (0, _explode.default)(delimiter, string, limit);
    }), new _ExpressionFunction.default('strlen', function compiler(str) {
      return `strlen(${str});`;
    }, function evaluator(values, str) {
      return (0, _strlen.default)(str);
    }), new _ExpressionFunction.default('strstr', function compiler(haystack, needle, before_needle) {
      let remaining = '';

      if (before_needle) {
        remaining = `, ${before_needle}`;
      }

      return `strstr(${haystack}, ${needle}${remaining});`;
    }, function evaluator(values, haystack, needle, before_needle) {
      return (0, _strstr.default)(haystack, needle, before_needle);
    }), new _ExpressionFunction.default('stristr', function compiler(haystack, needle, before_needle) {
      let remaining = '';

      if (before_needle) {
        remaining = `, ${before_needle}`;
      }

      return `stristr(${haystack}, ${needle}${remaining});`;
    }, function evaluator(values, haystack, needle, before_needle) {
      return (0, _stristr.default)(haystack, needle, before_needle);
    }), new _ExpressionFunction.default('substr', function compiler(str, start, length) {
      let remaining = '';

      if (length) {
        remaining = `, ${length}`;
      }

      return `substr(${str}, ${start}${remaining});`;
    }, function evaluator(values, str, start, length) {
      return (0, _substr.default)(str, start, length);
    })];
  }

}

exports["default"] = StringProvider;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/SyntaxError.js":
/*!***********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/SyntaxError.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;

var _Levenshtein = __webpack_require__(/*! ./lib/Levenshtein */ "../../../../node_modules/expression-language/lib/lib/Levenshtein.js");

class SyntaxError extends Error {
  constructor(message, cursor, expression, subject, proposals) {
    super(message);
    this.name = "SyntaxError";
    this.cursor = cursor;
    this.expression = expression;
    this.subject = subject;
    this.proposals = proposals;
  }

  toString() {
    let message = `${this.name}: ${this.message} around position ${this.cursor}`;

    if (this.expression) {
      message = message + ` for expression \`${this.expression}\``;
    }

    message += ".";

    if (this.subject && this.proposals) {
      let minScore = Number.MAX_SAFE_INTEGER,
          guess = null;

      for (let proposal of this.proposals) {
        let distance = (0, _Levenshtein.getEditDistance)(this.subject, proposal);

        if (distance < minScore) {
          guess = proposal;
          minScore = distance;
        }
      }

      if (guess !== null && minScore < 3) {
        message += ` Did you mean "${guess}"?`;
      }
    }

    return message;
  }

}

exports["default"] = SyntaxError;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/TokenStream.js":
/*!***********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/TokenStream.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.Token = exports.TokenStream = void 0;

var _SyntaxError = _interopRequireDefault(__webpack_require__(/*! ./SyntaxError */ "../../../../node_modules/expression-language/lib/SyntaxError.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class TokenStream {
  constructor(expression, tokens) {
    _defineProperty(this, "next", () => {
      this.position += 1;

      if (this.tokens[this.position] === undefined) {
        throw new _SyntaxError.default("Unexpected end of expression", this.last.cursor, this.expression);
      }
    });

    _defineProperty(this, "expect", (type, value, message) => {
      let token = this.current;

      if (!token.test(type, value)) {
        let compiledMessage = "";

        if (message) {
          compiledMessage = message + ". ";
        }

        let valueMessage = "";

        if (value) {
          valueMessage = ` with value "${value}"`;
        }

        compiledMessage += `Unexpected token "${token.type}" of value "${token.value}" ("${type}" expected${valueMessage})`;
        throw new _SyntaxError.default(compiledMessage, token.cursor, this.expression);
      }

      this.next();
    });

    _defineProperty(this, "isEOF", () => {
      return Token.EOF_TYPE === this.current.type;
    });

    _defineProperty(this, "isEqualTo", ts => {
      if (ts === null || ts === undefined || !ts instanceof TokenStream) {
        return false;
      }

      if (ts.tokens.length !== this.tokens.length) {
        return false;
      }

      let tsStartPosition = ts.position;
      ts.position = 0;
      let allTokensMatch = true;

      for (let token of this.tokens) {
        let match = ts.current.isEqualTo(token);

        if (!match) {
          allTokensMatch = false;
          break;
        }

        if (ts.position < ts.tokens.length - 1) {
          ts.next();
        }
      }

      ts.position = tsStartPosition;
      return allTokensMatch;
    });

    _defineProperty(this, "diff", ts => {
      let diff = [];

      if (!this.isEqualTo(ts)) {
        let index = 0;
        let tsStartPosition = ts.position;
        ts.position = 0;

        for (let token of this.tokens) {
          let tokenDiff = token.diff(ts.current);

          if (tokenDiff.length > 0) {
            diff.push({
              index: index,
              diff: tokenDiff
            });
          }

          if (ts.position < ts.tokens.length - 1) {
            ts.next();
          }
        }

        ts.position = tsStartPosition;
      }

      return diff;
    });

    this.expression = expression;
    this.position = 0;
    this.tokens = tokens;
  }

  get current() {
    return this.tokens[this.position];
  }

  get last() {
    return this.tokens[this.position - 1];
  }

  toString() {
    return this.tokens.join("\n");
  }

}

exports.TokenStream = TokenStream;

class Token {
  constructor(_type, _value, cursor) {
    _defineProperty(this, "test", (type, value = null) => {
      return this.type === type && (null === value || this.value === value);
    });

    _defineProperty(this, "isEqualTo", t => {
      if (t === null || t === undefined || !t instanceof Token) {
        return false;
      }

      return t.value == this.value && t.type === this.type && t.cursor === this.cursor;
    });

    _defineProperty(this, "diff", t => {
      let diff = [];

      if (!this.isEqualTo(t)) {
        if (t.value !== this.value) {
          diff.push(`Value: ${t.value} != ${this.value}`);
        }

        if (t.cursor !== this.cursor) {
          diff.push(`Cursor: ${t.cursor} != ${this.cursor}`);
        }

        if (t.type !== this.type) {
          diff.push(`Type: ${t.type} != ${this.type}`);
        }
      }

      return diff;
    });

    this.value = _value;
    this.type = _type;
    this.cursor = cursor;
  }

  toString() {
    return `${this.cursor} [${this.type}] ${this.value}`;
  }

}

exports.Token = Token;

_defineProperty(Token, "EOF_TYPE", 'end of expression');

_defineProperty(Token, "NAME_TYPE", 'name');

_defineProperty(Token, "NUMBER_TYPE", 'number');

_defineProperty(Token, "STRING_TYPE", 'string');

_defineProperty(Token, "OPERATOR_TYPE", 'operator');

_defineProperty(Token, "PUNCTUATION_TYPE", 'punctuation');

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/index.js":
/*!*****************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/index.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
Object.defineProperty(exports, "ExpressionLanguage", ({
  enumerable: true,
  get: function () {
    return _ExpressionLanguage.default;
  }
}));
Object.defineProperty(exports, "tokenize", ({
  enumerable: true,
  get: function () {
    return _Lexer.tokenize;
  }
}));
Object.defineProperty(exports, "Parser", ({
  enumerable: true,
  get: function () {
    return _Parser.default;
  }
}));
Object.defineProperty(exports, "ExpressionFunction", ({
  enumerable: true,
  get: function () {
    return _ExpressionFunction.default;
  }
}));
Object.defineProperty(exports, "Compiler", ({
  enumerable: true,
  get: function () {
    return _Compiler.default;
  }
}));
Object.defineProperty(exports, "ArrayAdapter", ({
  enumerable: true,
  get: function () {
    return _ArrayAdapter.default;
  }
}));
Object.defineProperty(exports, "BasicProvider", ({
  enumerable: true,
  get: function () {
    return _BasicProvider.default;
  }
}));
Object.defineProperty(exports, "StringProvider", ({
  enumerable: true,
  get: function () {
    return _StringProvider.default;
  }
}));
Object.defineProperty(exports, "ArrayProvider", ({
  enumerable: true,
  get: function () {
    return _ArrayProvider.default;
  }
}));
Object.defineProperty(exports, "DateProvider", ({
  enumerable: true,
  get: function () {
    return _DateProvider.default;
  }
}));
exports["default"] = void 0;

var _ExpressionLanguage = _interopRequireDefault(__webpack_require__(/*! ./ExpressionLanguage */ "../../../../node_modules/expression-language/lib/ExpressionLanguage.js"));

var _Lexer = __webpack_require__(/*! ./Lexer */ "../../../../node_modules/expression-language/lib/Lexer.js");

var _Parser = _interopRequireDefault(__webpack_require__(/*! ./Parser */ "../../../../node_modules/expression-language/lib/Parser.js"));

var _ExpressionFunction = _interopRequireDefault(__webpack_require__(/*! ./ExpressionFunction */ "../../../../node_modules/expression-language/lib/ExpressionFunction.js"));

var _Compiler = _interopRequireDefault(__webpack_require__(/*! ./Compiler */ "../../../../node_modules/expression-language/lib/Compiler.js"));

var _ArrayAdapter = _interopRequireDefault(__webpack_require__(/*! ./Cache/ArrayAdapter */ "../../../../node_modules/expression-language/lib/Cache/ArrayAdapter.js"));

var _BasicProvider = _interopRequireDefault(__webpack_require__(/*! ./Provider/BasicProvider */ "../../../../node_modules/expression-language/lib/Provider/BasicProvider.js"));

var _StringProvider = _interopRequireDefault(__webpack_require__(/*! ./Provider/StringProvider */ "../../../../node_modules/expression-language/lib/Provider/StringProvider.js"));

var _ArrayProvider = _interopRequireDefault(__webpack_require__(/*! ./Provider/ArrayProvider */ "../../../../node_modules/expression-language/lib/Provider/ArrayProvider.js"));

var _DateProvider = _interopRequireDefault(__webpack_require__(/*! ./Provider/DateProvider */ "../../../../node_modules/expression-language/lib/Provider/DateProvider.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var _default = _ExpressionLanguage.default;
exports["default"] = _default;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/lib/Levenshtein.js":
/*!***************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/lib/Levenshtein.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.getEditDistance = void 0;

const getEditDistance = function (a, b) {
  if (a.length === 0) return b.length;
  if (b.length === 0) return a.length;
  let matrix = []; // increment along the first column of each row

  let i;

  for (i = 0; i <= b.length; i++) {
    matrix[i] = [i];
  } // increment each column in the first row


  let j;

  for (j = 0; j <= a.length; j++) {
    if (matrix[0] === undefined) {
      matrix[0] = [];
    }

    matrix[0][j] = j;
  } // Fill in the rest of the matrix


  for (i = 1; i <= b.length; i++) {
    for (j = 1; j <= a.length; j++) {
      if (b.charAt(i - 1) === a.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1];
      } else {
        matrix[i][j] = Math.min(matrix[i - 1][j - 1] + 1, // substitution
        Math.min(matrix[i][j - 1] + 1, // insertion
        matrix[i - 1][j] + 1)); // deletion
      }
    }
  }

  if (matrix[b.length] === undefined) {
    matrix[b.length] = [];
  }

  return matrix[b.length][a.length];
};

exports.getEditDistance = getEditDistance;

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/lib/addcslashes.js":
/*!***************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/lib/addcslashes.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.addcslashes = addcslashes;

function addcslashes(str, charlist) {
  //  discuss at: https://locutus.io/php/addcslashes/
  // original by: Brett Zamir (https://brett-zamir.me)
  //      note 1: We show double backslashes in the return value example
  //      note 1: code below because a JavaScript string will not
  //      note 1: render them as backslashes otherwise
  //   example 1: addcslashes('foo[ ]', 'A..z'); // Escape all ASCII within capital A to lower z range, including square brackets
  //   returns 1: "\\f\\o\\o\\[ \\]"
  //   example 2: addcslashes("zoo['.']", 'z..A'); // Only escape z, period, and A here since not a lower-to-higher range
  //   returns 2: "\\zoo['\\.']"
  //   _example 3: addcslashes("@a\u0000\u0010\u00A9", "\0..\37!@\177..\377"); // Escape as octals those specified and less than 32 (0x20) or greater than 126 (0x7E), but not otherwise
  //   _returns 3: '\\@a\\000\\020\\302\\251'
  //   _example 4: addcslashes("\u0020\u007E", "\40..\175"); // Those between 32 (0x20 or 040) and 126 (0x7E or 0176) decimal value will be backslashed if specified (not octalized)
  //   _returns 4: '\\ ~'
  //   _example 5: addcslashes("\r\u0007\n", '\0..\37'); // Recognize C escape sequences if specified
  //   _returns 5: "\\r\\a\\n"
  //   _example 6: addcslashes("\r\u0007\n", '\0'); // Do not recognize C escape sequences if not specified
  //   _returns 6: "\r\u0007\n"
  var target = '';
  var chrs = [];
  var i = 0;
  var j = 0;
  var c = '';
  var next = '';
  var rangeBegin = '';
  var rangeEnd = '';
  var chr = '';
  var begin = 0;
  var end = 0;
  var octalLength = 0;
  var postOctalPos = 0;
  var cca = 0;
  var escHexGrp = [];
  var encoded = '';
  var percentHex = /%([\dA-Fa-f]+)/g;

  var _pad = function (n, c) {
    if ((n = n + '').length < c) {
      return new Array(++c - n.length).join('0') + n;
    }

    return n;
  };

  for (i = 0; i < charlist.length; i++) {
    c = charlist.charAt(i);
    next = charlist.charAt(i + 1);

    if (c === '\\' && next && /\d/.test(next)) {
      // Octal
      rangeBegin = charlist.slice(i + 1).match(/^\d+/)[0];
      octalLength = rangeBegin.length;
      postOctalPos = i + octalLength + 1;

      if (charlist.charAt(postOctalPos) + charlist.charAt(postOctalPos + 1) === '..') {
        // Octal begins range
        begin = rangeBegin.charCodeAt(0);

        if (/\\\d/.test(charlist.charAt(postOctalPos + 2) + charlist.charAt(postOctalPos + 3))) {
          // Range ends with octal
          rangeEnd = charlist.slice(postOctalPos + 3).match(/^\d+/)[0]; // Skip range end backslash

          i += 1;
        } else if (charlist.charAt(postOctalPos + 2)) {
          // Range ends with character
          rangeEnd = charlist.charAt(postOctalPos + 2);
        } else {
          throw new Error('Range with no end point');
        }

        end = rangeEnd.charCodeAt(0);

        if (end > begin) {
          // Treat as a range
          for (j = begin; j <= end; j++) {
            chrs.push(String.fromCharCode(j));
          }
        } else {
          // Supposed to treat period, begin and end as individual characters only, not a range
          chrs.push('.', rangeBegin, rangeEnd);
        } // Skip dots and range end (already skipped range end backslash if present)


        i += rangeEnd.length + 2;
      } else {
        // Octal is by itself
        chr = String.fromCharCode(parseInt(rangeBegin, 8));
        chrs.push(chr);
      } // Skip range begin


      i += octalLength;
    } else if (next + charlist.charAt(i + 2) === '..') {
      // Character begins range
      rangeBegin = c;
      begin = rangeBegin.charCodeAt(0);

      if (/\\\d/.test(charlist.charAt(i + 3) + charlist.charAt(i + 4))) {
        // Range ends with octal
        rangeEnd = charlist.slice(i + 4).match(/^\d+/)[0]; // Skip range end backslash

        i += 1;
      } else if (charlist.charAt(i + 3)) {
        // Range ends with character
        rangeEnd = charlist.charAt(i + 3);
      } else {
        throw new Error('Range with no end point');
      }

      end = rangeEnd.charCodeAt(0);

      if (end > begin) {
        // Treat as a range
        for (j = begin; j <= end; j++) {
          chrs.push(String.fromCharCode(j));
        }
      } else {
        // Supposed to treat period, begin and end as individual characters only, not a range
        chrs.push('.', rangeBegin, rangeEnd);
      } // Skip dots and range end (already skipped range end backslash if present)


      i += rangeEnd.length + 2;
    } else {
      // Character is by itself
      chrs.push(c);
    }
  }

  for (i = 0; i < str.length; i++) {
    c = str.charAt(i);

    if (chrs.indexOf(c) !== -1) {
      target += '\\';
      cca = c.charCodeAt(0);

      if (cca < 32 || cca > 126) {
        // Needs special escaping
        switch (c) {
          case '\n':
            target += 'n';
            break;

          case '\t':
            target += 't';
            break;

          case '\u000D':
            target += 'r';
            break;

          case '\u0007':
            target += 'a';
            break;

          case '\v':
            target += 'v';
            break;

          case '\b':
            target += 'b';
            break;

          case '\f':
            target += 'f';
            break;

          default:
            // target += _pad(cca.toString(8), 3);break; // Sufficient for UTF-16
            encoded = encodeURIComponent(c); // 3-length-padded UTF-8 octets

            if ((escHexGrp = percentHex.exec(encoded)) !== null) {
              // already added a slash above:
              target += _pad(parseInt(escHexGrp[1], 16).toString(8), 3);
            }

            while ((escHexGrp = percentHex.exec(encoded)) !== null) {
              target += '\\' + _pad(parseInt(escHexGrp[1], 16).toString(8), 3);
            }

            break;
        }
      } else {
        // Perform regular backslashed escaping
        target += c;
      }
    } else {
      // Just add the character unescaped
      target += c;
    }
  }

  return target;
}

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/lib/is-scalar.js":
/*!*************************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/lib/is-scalar.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.is_scalar = is_scalar;

function is_scalar(mixedVar) {
  // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/is_scalar/
  // original by: Paulo Freitas
  //   example 1: is_scalar(186.31)
  //   returns 1: true
  //   example 2: is_scalar({0: 'Kevin van Zonneveld'})
  //   returns 2: false
  return /boolean|number|string/.test(typeof mixedVar);
}

/***/ }),

/***/ "../../../../node_modules/expression-language/lib/lib/range.js":
/*!*********************************************************************!*\
  !*** ../../../../node_modules/expression-language/lib/lib/range.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.range = range;

function range(start, end) {
  let result = [];

  for (let i = start; i <= end; i++) {
    result.push(i);
  }

  return result;
}

/***/ }),

/***/ "../../../../node_modules/locutus/php/_helpers/_phpCastString.js":
/*!***********************************************************************!*\
  !*** ../../../../node_modules/locutus/php/_helpers/_phpCastString.js ***!
  \***********************************************************************/
/***/ ((module) => {



var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

module.exports = function _phpCastString(value) {
  // original by: Rafał Kukawski
  //   example 1: _phpCastString(true)
  //   returns 1: '1'
  //   example 2: _phpCastString(false)
  //   returns 2: ''
  //   example 3: _phpCastString('foo')
  //   returns 3: 'foo'
  //   example 4: _phpCastString(0/0)
  //   returns 4: 'NAN'
  //   example 5: _phpCastString(1/0)
  //   returns 5: 'INF'
  //   example 6: _phpCastString(-1/0)
  //   returns 6: '-INF'
  //   example 7: _phpCastString(null)
  //   returns 7: ''
  //   example 8: _phpCastString(undefined)
  //   returns 8: ''
  //   example 9: _phpCastString([])
  //   returns 9: 'Array'
  //   example 10: _phpCastString({})
  //   returns 10: 'Object'
  //   example 11: _phpCastString(0)
  //   returns 11: '0'
  //   example 12: _phpCastString(1)
  //   returns 12: '1'
  //   example 13: _phpCastString(3.14)
  //   returns 13: '3.14'

  var type = typeof value === 'undefined' ? 'undefined' : _typeof(value);

  switch (type) {
    case 'boolean':
      return value ? '1' : '';
    case 'string':
      return value;
    case 'number':
      if (isNaN(value)) {
        return 'NAN';
      }

      if (!isFinite(value)) {
        return (value < 0 ? '-' : '') + 'INF';
      }

      return value + '';
    case 'undefined':
      return '';
    case 'object':
      if (Array.isArray(value)) {
        return 'Array';
      }

      if (value !== null) {
        return 'Object';
      }

      return '';
    case 'function':
    // fall through
    default:
      throw new Error('Unsupported value type');
  }
};
//# sourceMappingURL=_phpCastString.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/array/array_intersect.js":
/*!*********************************************************************!*\
  !*** ../../../../node_modules/locutus/php/array/array_intersect.js ***!
  \*********************************************************************/
/***/ ((module) => {



module.exports = function array_intersect(arr1) {
  // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/array_intersect/
  // original by: Brett Zamir (https://brett-zamir.me)
  //      note 1: These only output associative arrays (would need to be
  //      note 1: all numeric and counting from zero to be numeric)
  //   example 1: var $array1 = {'a' : 'green', 0:'red', 1: 'blue'}
  //   example 1: var $array2 = {'b' : 'green', 0:'yellow', 1:'red'}
  //   example 1: var $array3 = ['green', 'red']
  //   example 1: var $result = array_intersect($array1, $array2, $array3)
  //   returns 1: {0: 'red', a: 'green'}

  var retArr = {};
  var argl = arguments.length;
  var arglm1 = argl - 1;
  var k1 = '';
  var arr = {};
  var i = 0;
  var k = '';

  arr1keys: for (k1 in arr1) {
    // eslint-disable-line no-labels
    arrs: for (i = 1; i < argl; i++) {
      // eslint-disable-line no-labels
      arr = arguments[i];
      for (k in arr) {
        if (arr[k] === arr1[k1]) {
          if (i === arglm1) {
            retArr[k1] = arr1[k1];
          }
          // If the innermost loop always leads at least once to an equal value,
          // continue the loop until done
          continue arrs; // eslint-disable-line no-labels
        }
      }
      // If it reaches here, it wasn't found in at least one array, so try next value
      continue arr1keys; // eslint-disable-line no-labels
    }
  }

  return retArr;
};
//# sourceMappingURL=array_intersect.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/array/count.js":
/*!***********************************************************!*\
  !*** ../../../../node_modules/locutus/php/array/count.js ***!
  \***********************************************************/
/***/ ((module) => {



module.exports = function count(mixedVar, mode) {
  //  discuss at: https://locutus.io/php/count/
  // original by: Kevin van Zonneveld (https://kvz.io)
  //    input by: Waldo Malqui Silva (https://waldo.malqui.info)
  //    input by: merabi
  // bugfixed by: Soren Hansen
  // bugfixed by: Olivier Louvignes (https://mg-crea.com/)
  // improved by: Brett Zamir (https://brett-zamir.me)
  //   example 1: count([[0,0],[0,-4]], 'COUNT_RECURSIVE')
  //   returns 1: 6
  //   example 2: count({'one' : [1,2,3,4,5]}, 'COUNT_RECURSIVE')
  //   returns 2: 6

  var key = void 0;
  var cnt = 0;

  if (mixedVar === null || typeof mixedVar === 'undefined') {
    return 0;
  } else if (mixedVar.constructor !== Array && mixedVar.constructor !== Object) {
    return 1;
  }

  if (mode === 'COUNT_RECURSIVE') {
    mode = 1;
  }
  if (mode !== 1) {
    mode = 0;
  }

  for (key in mixedVar) {
    if (mixedVar.hasOwnProperty(key)) {
      cnt++;
      if (mode === 1 && mixedVar[key] && (mixedVar[key].constructor === Array || mixedVar[key].constructor === Object)) {
        cnt += count(mixedVar[key], 1);
      }
    }
  }

  return cnt;
};
//# sourceMappingURL=count.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/datetime/date.js":
/*!*************************************************************!*\
  !*** ../../../../node_modules/locutus/php/datetime/date.js ***!
  \*************************************************************/
/***/ ((module) => {



module.exports = function date(format, timestamp) {
  //  discuss at: https://locutus.io/php/date/
  // original by: Carlos R. L. Rodrigues (https://www.jsfromhell.com)
  // original by: gettimeofday
  //    parts by: Peter-Paul Koch (https://www.quirksmode.org/js/beat.html)
  // improved by: Kevin van Zonneveld (https://kvz.io)
  // improved by: MeEtc (https://yass.meetcweb.com)
  // improved by: Brad Touesnard
  // improved by: Tim Wiel
  // improved by: Bryan Elliott
  // improved by: David Randall
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Brett Zamir (https://brett-zamir.me)
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Thomas Beaucourt (https://www.webapp.fr)
  // improved by: JT
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Rafał Kukawski (https://blog.kukawski.pl)
  // improved by: Theriault (https://github.com/Theriault)
  //    input by: Brett Zamir (https://brett-zamir.me)
  //    input by: majak
  //    input by: Alex
  //    input by: Martin
  //    input by: Alex Wilson
  //    input by: Haravikk
  // bugfixed by: Kevin van Zonneveld (https://kvz.io)
  // bugfixed by: majak
  // bugfixed by: Kevin van Zonneveld (https://kvz.io)
  // bugfixed by: Brett Zamir (https://brett-zamir.me)
  // bugfixed by: omid (https://locutus.io/php/380:380#comment_137122)
  // bugfixed by: Chris (https://www.devotis.nl/)
  //      note 1: Uses global: locutus to store the default timezone
  //      note 1: Although the function potentially allows timezone info
  //      note 1: (see notes), it currently does not set
  //      note 1: per a timezone specified by date_default_timezone_set(). Implementers might use
  //      note 1: $locutus.currentTimezoneOffset and
  //      note 1: $locutus.currentTimezoneDST set by that function
  //      note 1: in order to adjust the dates in this function
  //      note 1: (or our other date functions!) accordingly
  //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400)
  //   returns 1: '07:09:40 m is month'
  //   example 2: date('F j, Y, g:i a', 1062462400)
  //   returns 2: 'September 2, 2003, 12:26 am'
  //   example 3: date('Y W o', 1062462400)
  //   returns 3: '2003 36 2003'
  //   example 4: var $x = date('Y m d', (new Date()).getTime() / 1000)
  //   example 4: $x = $x + ''
  //   example 4: var $result = $x.length // 2009 01 09
  //   returns 4: 10
  //   example 5: date('W', 1104534000)
  //   returns 5: '52'
  //   example 6: date('B t', 1104534000)
  //   returns 6: '999 31'
  //   example 7: date('W U', 1293750000.82); // 2010-12-31
  //   returns 7: '52 1293750000'
  //   example 8: date('W', 1293836400); // 2011-01-01
  //   returns 8: '52'
  //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
  //   returns 9: '52 2011-01-02'
  //        test: skip-1 skip-2 skip-5

  var jsdate = void 0,
      f = void 0;
  // Keep this here (works, but for code commented-out below for file size reasons)
  // var tal= [];
  var txtWords = ['Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  // trailing backslash -> (dropped)
  // a backslash followed by any character (including backslash) -> the character
  // empty string -> empty string
  var formatChr = /\\?(.?)/gi;
  var formatChrCb = function formatChrCb(t, s) {
    return f[t] ? f[t]() : s;
  };
  var _pad = function _pad(n, c) {
    n = String(n);
    while (n.length < c) {
      n = '0' + n;
    }
    return n;
  };
  f = {
    // Day
    d: function d() {
      // Day of month w/leading 0; 01..31
      return _pad(f.j(), 2);
    },
    D: function D() {
      // Shorthand day name; Mon...Sun
      return f.l().slice(0, 3);
    },
    j: function j() {
      // Day of month; 1..31
      return jsdate.getDate();
    },
    l: function l() {
      // Full day name; Monday...Sunday
      return txtWords[f.w()] + 'day';
    },
    N: function N() {
      // ISO-8601 day of week; 1[Mon]..7[Sun]
      return f.w() || 7;
    },
    S: function S() {
      // Ordinal suffix for day of month; st, nd, rd, th
      var j = f.j();
      var i = j % 10;
      if (i <= 3 && parseInt(j % 100 / 10, 10) === 1) {
        i = 0;
      }
      return ['st', 'nd', 'rd'][i - 1] || 'th';
    },
    w: function w() {
      // Day of week; 0[Sun]..6[Sat]
      return jsdate.getDay();
    },
    z: function z() {
      // Day of year; 0..365
      var a = new Date(f.Y(), f.n() - 1, f.j());
      var b = new Date(f.Y(), 0, 1);
      return Math.round((a - b) / 864e5);
    },

    // Week
    W: function W() {
      // ISO-8601 week number
      var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3);
      var b = new Date(a.getFullYear(), 0, 4);
      return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
    },

    // Month
    F: function F() {
      // Full month name; January...December
      return txtWords[6 + f.n()];
    },
    m: function m() {
      // Month w/leading 0; 01...12
      return _pad(f.n(), 2);
    },
    M: function M() {
      // Shorthand month name; Jan...Dec
      return f.F().slice(0, 3);
    },
    n: function n() {
      // Month; 1...12
      return jsdate.getMonth() + 1;
    },
    t: function t() {
      // Days in month; 28...31
      return new Date(f.Y(), f.n(), 0).getDate();
    },

    // Year
    L: function L() {
      // Is leap year?; 0 or 1
      var j = f.Y();
      return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
    },
    o: function o() {
      // ISO-8601 year
      var n = f.n();
      var W = f.W();
      var Y = f.Y();
      return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
    },
    Y: function Y() {
      // Full year; e.g. 1980...2010
      return jsdate.getFullYear();
    },
    y: function y() {
      // Last two digits of year; 00...99
      return f.Y().toString().slice(-2);
    },

    // Time
    a: function a() {
      // am or pm
      return jsdate.getHours() > 11 ? 'pm' : 'am';
    },
    A: function A() {
      // AM or PM
      return f.a().toUpperCase();
    },
    B: function B() {
      // Swatch Internet time; 000..999
      var H = jsdate.getUTCHours() * 36e2;
      // Hours
      var i = jsdate.getUTCMinutes() * 60;
      // Minutes
      // Seconds
      var s = jsdate.getUTCSeconds();
      return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
    },
    g: function g() {
      // 12-Hours; 1..12
      return f.G() % 12 || 12;
    },
    G: function G() {
      // 24-Hours; 0..23
      return jsdate.getHours();
    },
    h: function h() {
      // 12-Hours w/leading 0; 01..12
      return _pad(f.g(), 2);
    },
    H: function H() {
      // 24-Hours w/leading 0; 00..23
      return _pad(f.G(), 2);
    },
    i: function i() {
      // Minutes w/leading 0; 00..59
      return _pad(jsdate.getMinutes(), 2);
    },
    s: function s() {
      // Seconds w/leading 0; 00..59
      return _pad(jsdate.getSeconds(), 2);
    },
    u: function u() {
      // Microseconds; 000000-999000
      return _pad(jsdate.getMilliseconds() * 1000, 6);
    },

    // Timezone
    e: function e() {
      // Timezone identifier; e.g. Atlantic/Azores, ...
      // The following works, but requires inclusion of the very large
      // timezone_abbreviations_list() function.
      /*              return that.date_default_timezone_get();
       */
      var msg = 'Not supported (see source code of date() for timezone on how to add support)';
      throw new Error(msg);
    },
    I: function I() {
      // DST observed?; 0 or 1
      // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
      // If they are not equal, then DST is observed.
      var a = new Date(f.Y(), 0);
      // Jan 1
      var c = Date.UTC(f.Y(), 0);
      // Jan 1 UTC
      var b = new Date(f.Y(), 6);
      // Jul 1
      // Jul 1 UTC
      var d = Date.UTC(f.Y(), 6);
      return a - c !== b - d ? 1 : 0;
    },
    O: function O() {
      // Difference to GMT in hour format; e.g. +0200
      var tzo = jsdate.getTimezoneOffset();
      var a = Math.abs(tzo);
      return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
    },
    P: function P() {
      // Difference to GMT w/colon; e.g. +02:00
      var O = f.O();
      return O.substr(0, 3) + ':' + O.substr(3, 2);
    },
    T: function T() {
      // The following works, but requires inclusion of the very
      // large timezone_abbreviations_list() function.
      /*              var abbr, i, os, _default;
      if (!tal.length) {
        tal = that.timezone_abbreviations_list();
      }
      if ($locutus && $locutus.default_timezone) {
        _default = $locutus.default_timezone;
        for (abbr in tal) {
          for (i = 0; i < tal[abbr].length; i++) {
            if (tal[abbr][i].timezone_id === _default) {
              return abbr.toUpperCase();
            }
          }
        }
      }
      for (abbr in tal) {
        for (i = 0; i < tal[abbr].length; i++) {
          os = -jsdate.getTimezoneOffset() * 60;
          if (tal[abbr][i].offset === os) {
            return abbr.toUpperCase();
          }
        }
      }
      */
      return 'UTC';
    },
    Z: function Z() {
      // Timezone offset in seconds (-43200...50400)
      return -jsdate.getTimezoneOffset() * 60;
    },

    // Full Date/Time
    c: function c() {
      // ISO-8601 date.
      return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb);
    },
    r: function r() {
      // RFC 2822
      return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
    },
    U: function U() {
      // Seconds since UNIX epoch
      return jsdate / 1000 | 0;
    }
  };

  var _date = function _date(format, timestamp) {
    jsdate = timestamp === undefined ? new Date() // Not provided
    : timestamp instanceof Date ? new Date(timestamp) // JS Date()
    : new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
    ;
    return format.replace(formatChr, formatChrCb);
  };

  return _date(format, timestamp);
};
//# sourceMappingURL=date.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/datetime/strtotime.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/locutus/php/datetime/strtotime.js ***!
  \******************************************************************/
/***/ ((module) => {



var reSpace = '[ \\t]+';
var reSpaceOpt = '[ \\t]*';
var reMeridian = '(?:([ap])\\.?m\\.?([\\t ]|$))';
var reHour24 = '(2[0-4]|[01]?[0-9])';
var reHour24lz = '([01][0-9]|2[0-4])';
var reHour12 = '(0?[1-9]|1[0-2])';
var reMinute = '([0-5]?[0-9])';
var reMinutelz = '([0-5][0-9])';
var reSecond = '(60|[0-5]?[0-9])';
var reSecondlz = '(60|[0-5][0-9])';
var reFrac = '(?:\\.([0-9]+))';

var reDayfull = 'sunday|monday|tuesday|wednesday|thursday|friday|saturday';
var reDayabbr = 'sun|mon|tue|wed|thu|fri|sat';
var reDaytext = reDayfull + '|' + reDayabbr + '|weekdays?';

var reReltextnumber = 'first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth';
var reReltexttext = 'next|last|previous|this';
var reReltextunit = '(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|' + reDaytext;

var reYear = '([0-9]{1,4})';
var reYear2 = '([0-9]{2})';
var reYear4 = '([0-9]{4})';
var reYear4withSign = '([+-]?[0-9]{4})';
var reMonth = '(1[0-2]|0?[0-9])';
var reMonthlz = '(0[0-9]|1[0-2])';
var reDay = '(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)';
var reDaylz = '(0[0-9]|[1-2][0-9]|3[01])';

var reMonthFull = 'january|february|march|april|may|june|july|august|september|october|november|december';
var reMonthAbbr = 'jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec';
var reMonthroman = 'i[vx]|vi{0,3}|xi{0,2}|i{1,3}';
var reMonthText = '(' + reMonthFull + '|' + reMonthAbbr + '|' + reMonthroman + ')';

var reTzCorrection = '((?:GMT)?([+-])' + reHour24 + ':?' + reMinute + '?)';
var reTzAbbr = '\\(?([a-zA-Z]{1,6})\\)?';
var reDayOfYear = '(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])';
var reWeekOfYear = '(0[1-9]|[1-4][0-9]|5[0-3])';

var reDateNoYear = reMonthText + '[ .\\t-]*' + reDay + '[,.stndrh\\t ]*';

function processMeridian(hour, meridian) {
  meridian = meridian && meridian.toLowerCase();

  switch (meridian) {
    case 'a':
      hour += hour === 12 ? -12 : 0;
      break;
    case 'p':
      hour += hour !== 12 ? 12 : 0;
      break;
  }

  return hour;
}

function processYear(yearStr) {
  var year = +yearStr;

  if (yearStr.length < 4 && year < 100) {
    year += year < 70 ? 2000 : 1900;
  }

  return year;
}

function lookupMonth(monthStr) {
  return {
    jan: 0,
    january: 0,
    i: 0,
    feb: 1,
    february: 1,
    ii: 1,
    mar: 2,
    march: 2,
    iii: 2,
    apr: 3,
    april: 3,
    iv: 3,
    may: 4,
    v: 4,
    jun: 5,
    june: 5,
    vi: 5,
    jul: 6,
    july: 6,
    vii: 6,
    aug: 7,
    august: 7,
    viii: 7,
    sep: 8,
    sept: 8,
    september: 8,
    ix: 8,
    oct: 9,
    october: 9,
    x: 9,
    nov: 10,
    november: 10,
    xi: 10,
    dec: 11,
    december: 11,
    xii: 11
  }[monthStr.toLowerCase()];
}

function lookupWeekday(dayStr) {
  var desiredSundayNumber = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;

  var dayNumbers = {
    mon: 1,
    monday: 1,
    tue: 2,
    tuesday: 2,
    wed: 3,
    wednesday: 3,
    thu: 4,
    thursday: 4,
    fri: 5,
    friday: 5,
    sat: 6,
    saturday: 6,
    sun: 0,
    sunday: 0
  };

  return dayNumbers[dayStr.toLowerCase()] || desiredSundayNumber;
}

function lookupRelative(relText) {
  var relativeNumbers = {
    last: -1,
    previous: -1,
    this: 0,
    first: 1,
    next: 1,
    second: 2,
    third: 3,
    fourth: 4,
    fifth: 5,
    sixth: 6,
    seventh: 7,
    eight: 8,
    eighth: 8,
    ninth: 9,
    tenth: 10,
    eleventh: 11,
    twelfth: 12
  };

  var relativeBehavior = {
    this: 1
  };

  var relTextLower = relText.toLowerCase();

  return {
    amount: relativeNumbers[relTextLower],
    behavior: relativeBehavior[relTextLower] || 0
  };
}

function processTzCorrection(tzOffset, oldValue) {
  var reTzCorrectionLoose = /(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i;
  tzOffset = tzOffset && tzOffset.match(reTzCorrectionLoose);

  if (!tzOffset) {
    return oldValue;
  }

  var sign = tzOffset[1] === '-' ? -1 : 1;
  var hours = +tzOffset[2];
  var minutes = +tzOffset[4];

  if (!tzOffset[4] && !tzOffset[3]) {
    minutes = Math.floor(hours % 100);
    hours = Math.floor(hours / 100);
  }

  // timezone offset in seconds
  return sign * (hours * 60 + minutes) * 60;
}

// tz abbrevation : tz offset in seconds
var tzAbbrOffsets = {
  acdt: 37800,
  acst: 34200,
  addt: -7200,
  adt: -10800,
  aedt: 39600,
  aest: 36000,
  ahdt: -32400,
  ahst: -36000,
  akdt: -28800,
  akst: -32400,
  amt: -13840,
  apt: -10800,
  ast: -14400,
  awdt: 32400,
  awst: 28800,
  awt: -10800,
  bdst: 7200,
  bdt: -36000,
  bmt: -14309,
  bst: 3600,
  cast: 34200,
  cat: 7200,
  cddt: -14400,
  cdt: -18000,
  cemt: 10800,
  cest: 7200,
  cet: 3600,
  cmt: -15408,
  cpt: -18000,
  cst: -21600,
  cwt: -18000,
  chst: 36000,
  dmt: -1521,
  eat: 10800,
  eddt: -10800,
  edt: -14400,
  eest: 10800,
  eet: 7200,
  emt: -26248,
  ept: -14400,
  est: -18000,
  ewt: -14400,
  ffmt: -14660,
  fmt: -4056,
  gdt: 39600,
  gmt: 0,
  gst: 36000,
  hdt: -34200,
  hkst: 32400,
  hkt: 28800,
  hmt: -19776,
  hpt: -34200,
  hst: -36000,
  hwt: -34200,
  iddt: 14400,
  idt: 10800,
  imt: 25025,
  ist: 7200,
  jdt: 36000,
  jmt: 8440,
  jst: 32400,
  kdt: 36000,
  kmt: 5736,
  kst: 30600,
  lst: 9394,
  mddt: -18000,
  mdst: 16279,
  mdt: -21600,
  mest: 7200,
  met: 3600,
  mmt: 9017,
  mpt: -21600,
  msd: 14400,
  msk: 10800,
  mst: -25200,
  mwt: -21600,
  nddt: -5400,
  ndt: -9052,
  npt: -9000,
  nst: -12600,
  nwt: -9000,
  nzdt: 46800,
  nzmt: 41400,
  nzst: 43200,
  pddt: -21600,
  pdt: -25200,
  pkst: 21600,
  pkt: 18000,
  plmt: 25590,
  pmt: -13236,
  ppmt: -17340,
  ppt: -25200,
  pst: -28800,
  pwt: -25200,
  qmt: -18840,
  rmt: 5794,
  sast: 7200,
  sdmt: -16800,
  sjmt: -20173,
  smt: -13884,
  sst: -39600,
  tbmt: 10751,
  tmt: 12344,
  uct: 0,
  utc: 0,
  wast: 7200,
  wat: 3600,
  wemt: 7200,
  west: 3600,
  wet: 0,
  wib: 25200,
  wita: 28800,
  wit: 32400,
  wmt: 5040,
  yddt: -25200,
  ydt: -28800,
  ypt: -28800,
  yst: -32400,
  ywt: -28800,
  a: 3600,
  b: 7200,
  c: 10800,
  d: 14400,
  e: 18000,
  f: 21600,
  g: 25200,
  h: 28800,
  i: 32400,
  k: 36000,
  l: 39600,
  m: 43200,
  n: -3600,
  o: -7200,
  p: -10800,
  q: -14400,
  r: -18000,
  s: -21600,
  t: -25200,
  u: -28800,
  v: -32400,
  w: -36000,
  x: -39600,
  y: -43200,
  z: 0
};

var formats = {
  yesterday: {
    regex: /^yesterday/i,
    name: 'yesterday',
    callback: function callback() {
      this.rd -= 1;
      return this.resetTime();
    }
  },

  now: {
    regex: /^now/i,
    name: 'now'
    // do nothing
  },

  noon: {
    regex: /^noon/i,
    name: 'noon',
    callback: function callback() {
      return this.resetTime() && this.time(12, 0, 0, 0);
    }
  },

  midnightOrToday: {
    regex: /^(midnight|today)/i,
    name: 'midnight | today',
    callback: function callback() {
      return this.resetTime();
    }
  },

  tomorrow: {
    regex: /^tomorrow/i,
    name: 'tomorrow',
    callback: function callback() {
      this.rd += 1;
      return this.resetTime();
    }
  },

  timestamp: {
    regex: /^@(-?\d+)/i,
    name: 'timestamp',
    callback: function callback(match, timestamp) {
      this.rs += +timestamp;
      this.y = 1970;
      this.m = 0;
      this.d = 1;
      this.dates = 0;

      return this.resetTime() && this.zone(0);
    }
  },

  firstOrLastDay: {
    regex: /^(first|last) day of/i,
    name: 'firstdayof | lastdayof',
    callback: function callback(match, day) {
      if (day.toLowerCase() === 'first') {
        this.firstOrLastDayOfMonth = 1;
      } else {
        this.firstOrLastDayOfMonth = -1;
      }
    }
  },

  backOrFrontOf: {
    regex: RegExp('^(back|front) of ' + reHour24 + reSpaceOpt + reMeridian + '?', 'i'),
    name: 'backof | frontof',
    callback: function callback(match, side, hours, meridian) {
      var back = side.toLowerCase() === 'back';
      var hour = +hours;
      var minute = 15;

      if (!back) {
        hour -= 1;
        minute = 45;
      }

      hour = processMeridian(hour, meridian);

      return this.resetTime() && this.time(hour, minute, 0, 0);
    }
  },

  weekdayOf: {
    regex: RegExp('^(' + reReltextnumber + '|' + reReltexttext + ')' + reSpace + '(' + reDayfull + '|' + reDayabbr + ')' + reSpace + 'of', 'i'),
    name: 'weekdayof'
    // todo
  },

  mssqltime: {
    regex: RegExp('^' + reHour12 + ':' + reMinutelz + ':' + reSecondlz + '[:.]([0-9]+)' + reMeridian, 'i'),
    name: 'mssqltime',
    callback: function callback(match, hour, minute, second, frac, meridian) {
      return this.time(processMeridian(+hour, meridian), +minute, +second, +frac.substr(0, 3));
    }
  },

  timeLong12: {
    regex: RegExp('^' + reHour12 + '[:.]' + reMinute + '[:.]' + reSecondlz + reSpaceOpt + reMeridian, 'i'),
    name: 'timelong12',
    callback: function callback(match, hour, minute, second, meridian) {
      return this.time(processMeridian(+hour, meridian), +minute, +second, 0);
    }
  },

  timeShort12: {
    regex: RegExp('^' + reHour12 + '[:.]' + reMinutelz + reSpaceOpt + reMeridian, 'i'),
    name: 'timeshort12',
    callback: function callback(match, hour, minute, meridian) {
      return this.time(processMeridian(+hour, meridian), +minute, 0, 0);
    }
  },

  timeTiny12: {
    regex: RegExp('^' + reHour12 + reSpaceOpt + reMeridian, 'i'),
    name: 'timetiny12',
    callback: function callback(match, hour, meridian) {
      return this.time(processMeridian(+hour, meridian), 0, 0, 0);
    }
  },

  soap: {
    regex: RegExp('^' + reYear4 + '-' + reMonthlz + '-' + reDaylz + 'T' + reHour24lz + ':' + reMinutelz + ':' + reSecondlz + reFrac + reTzCorrection + '?', 'i'),
    name: 'soap',
    callback: function callback(match, year, month, day, hour, minute, second, frac, tzCorrection) {
      return this.ymd(+year, month - 1, +day) && this.time(+hour, +minute, +second, +frac.substr(0, 3)) && this.zone(processTzCorrection(tzCorrection));
    }
  },

  wddx: {
    regex: RegExp('^' + reYear4 + '-' + reMonth + '-' + reDay + 'T' + reHour24 + ':' + reMinute + ':' + reSecond),
    name: 'wddx',
    callback: function callback(match, year, month, day, hour, minute, second) {
      return this.ymd(+year, month - 1, +day) && this.time(+hour, +minute, +second, 0);
    }
  },

  exif: {
    regex: RegExp('^' + reYear4 + ':' + reMonthlz + ':' + reDaylz + ' ' + reHour24lz + ':' + reMinutelz + ':' + reSecondlz, 'i'),
    name: 'exif',
    callback: function callback(match, year, month, day, hour, minute, second) {
      return this.ymd(+year, month - 1, +day) && this.time(+hour, +minute, +second, 0);
    }
  },

  xmlRpc: {
    regex: RegExp('^' + reYear4 + reMonthlz + reDaylz + 'T' + reHour24 + ':' + reMinutelz + ':' + reSecondlz),
    name: 'xmlrpc',
    callback: function callback(match, year, month, day, hour, minute, second) {
      return this.ymd(+year, month - 1, +day) && this.time(+hour, +minute, +second, 0);
    }
  },

  xmlRpcNoColon: {
    regex: RegExp('^' + reYear4 + reMonthlz + reDaylz + '[Tt]' + reHour24 + reMinutelz + reSecondlz),
    name: 'xmlrpcnocolon',
    callback: function callback(match, year, month, day, hour, minute, second) {
      return this.ymd(+year, month - 1, +day) && this.time(+hour, +minute, +second, 0);
    }
  },

  clf: {
    regex: RegExp('^' + reDay + '/(' + reMonthAbbr + ')/' + reYear4 + ':' + reHour24lz + ':' + reMinutelz + ':' + reSecondlz + reSpace + reTzCorrection, 'i'),
    name: 'clf',
    callback: function callback(match, day, month, year, hour, minute, second, tzCorrection) {
      return this.ymd(+year, lookupMonth(month), +day) && this.time(+hour, +minute, +second, 0) && this.zone(processTzCorrection(tzCorrection));
    }
  },

  iso8601long: {
    regex: RegExp('^t?' + reHour24 + '[:.]' + reMinute + '[:.]' + reSecond + reFrac, 'i'),
    name: 'iso8601long',
    callback: function callback(match, hour, minute, second, frac) {
      return this.time(+hour, +minute, +second, +frac.substr(0, 3));
    }
  },

  dateTextual: {
    regex: RegExp('^' + reMonthText + '[ .\\t-]*' + reDay + '[,.stndrh\\t ]+' + reYear, 'i'),
    name: 'datetextual',
    callback: function callback(match, month, day, year) {
      return this.ymd(processYear(year), lookupMonth(month), +day);
    }
  },

  pointedDate4: {
    regex: RegExp('^' + reDay + '[.\\t-]' + reMonth + '[.-]' + reYear4),
    name: 'pointeddate4',
    callback: function callback(match, day, month, year) {
      return this.ymd(+year, month - 1, +day);
    }
  },

  pointedDate2: {
    regex: RegExp('^' + reDay + '[.\\t]' + reMonth + '\\.' + reYear2),
    name: 'pointeddate2',
    callback: function callback(match, day, month, year) {
      return this.ymd(processYear(year), month - 1, +day);
    }
  },

  timeLong24: {
    regex: RegExp('^t?' + reHour24 + '[:.]' + reMinute + '[:.]' + reSecond),
    name: 'timelong24',
    callback: function callback(match, hour, minute, second) {
      return this.time(+hour, +minute, +second, 0);
    }
  },

  dateNoColon: {
    regex: RegExp('^' + reYear4 + reMonthlz + reDaylz),
    name: 'datenocolon',
    callback: function callback(match, year, month, day) {
      return this.ymd(+year, month - 1, +day);
    }
  },

  pgydotd: {
    regex: RegExp('^' + reYear4 + '\\.?' + reDayOfYear),
    name: 'pgydotd',
    callback: function callback(match, year, day) {
      return this.ymd(+year, 0, +day);
    }
  },

  timeShort24: {
    regex: RegExp('^t?' + reHour24 + '[:.]' + reMinute, 'i'),
    name: 'timeshort24',
    callback: function callback(match, hour, minute) {
      return this.time(+hour, +minute, 0, 0);
    }
  },

  iso8601noColon: {
    regex: RegExp('^t?' + reHour24lz + reMinutelz + reSecondlz, 'i'),
    name: 'iso8601nocolon',
    callback: function callback(match, hour, minute, second) {
      return this.time(+hour, +minute, +second, 0);
    }
  },

  iso8601dateSlash: {
    // eventhough the trailing slash is optional in PHP
    // here it's mandatory and inputs without the slash
    // are handled by dateslash
    regex: RegExp('^' + reYear4 + '/' + reMonthlz + '/' + reDaylz + '/'),
    name: 'iso8601dateslash',
    callback: function callback(match, year, month, day) {
      return this.ymd(+year, month - 1, +day);
    }
  },

  dateSlash: {
    regex: RegExp('^' + reYear4 + '/' + reMonth + '/' + reDay),
    name: 'dateslash',
    callback: function callback(match, year, month, day) {
      return this.ymd(+year, month - 1, +day);
    }
  },

  american: {
    regex: RegExp('^' + reMonth + '/' + reDay + '/' + reYear),
    name: 'american',
    callback: function callback(match, month, day, year) {
      return this.ymd(processYear(year), month - 1, +day);
    }
  },

  americanShort: {
    regex: RegExp('^' + reMonth + '/' + reDay),
    name: 'americanshort',
    callback: function callback(match, month, day) {
      return this.ymd(this.y, month - 1, +day);
    }
  },

  gnuDateShortOrIso8601date2: {
    // iso8601date2 is complete subset of gnudateshort
    regex: RegExp('^' + reYear + '-' + reMonth + '-' + reDay),
    name: 'gnudateshort | iso8601date2',
    callback: function callback(match, year, month, day) {
      return this.ymd(processYear(year), month - 1, +day);
    }
  },

  iso8601date4: {
    regex: RegExp('^' + reYear4withSign + '-' + reMonthlz + '-' + reDaylz),
    name: 'iso8601date4',
    callback: function callback(match, year, month, day) {
      return this.ymd(+year, month - 1, +day);
    }
  },

  gnuNoColon: {
    regex: RegExp('^t?' + reHour24lz + reMinutelz, 'i'),
    name: 'gnunocolon',
    callback: function callback(match, hour, minute) {
      // this rule is a special case
      // if time was already set once by any preceding rule, it sets the captured value as year
      switch (this.times) {
        case 0:
          return this.time(+hour, +minute, 0, this.f);
        case 1:
          this.y = hour * 100 + +minute;
          this.times++;

          return true;
        default:
          return false;
      }
    }
  },

  gnuDateShorter: {
    regex: RegExp('^' + reYear4 + '-' + reMonth),
    name: 'gnudateshorter',
    callback: function callback(match, year, month) {
      return this.ymd(+year, month - 1, 1);
    }
  },

  pgTextReverse: {
    // note: allowed years are from 32-9999
    // years below 32 should be treated as days in datefull
    regex: RegExp('^' + '(\\d{3,4}|[4-9]\\d|3[2-9])-(' + reMonthAbbr + ')-' + reDaylz, 'i'),
    name: 'pgtextreverse',
    callback: function callback(match, year, month, day) {
      return this.ymd(processYear(year), lookupMonth(month), +day);
    }
  },

  dateFull: {
    regex: RegExp('^' + reDay + '[ \\t.-]*' + reMonthText + '[ \\t.-]*' + reYear, 'i'),
    name: 'datefull',
    callback: function callback(match, day, month, year) {
      return this.ymd(processYear(year), lookupMonth(month), +day);
    }
  },

  dateNoDay: {
    regex: RegExp('^' + reMonthText + '[ .\\t-]*' + reYear4, 'i'),
    name: 'datenoday',
    callback: function callback(match, month, year) {
      return this.ymd(+year, lookupMonth(month), 1);
    }
  },

  dateNoDayRev: {
    regex: RegExp('^' + reYear4 + '[ .\\t-]*' + reMonthText, 'i'),
    name: 'datenodayrev',
    callback: function callback(match, year, month) {
      return this.ymd(+year, lookupMonth(month), 1);
    }
  },

  pgTextShort: {
    regex: RegExp('^(' + reMonthAbbr + ')-' + reDaylz + '-' + reYear, 'i'),
    name: 'pgtextshort',
    callback: function callback(match, month, day, year) {
      return this.ymd(processYear(year), lookupMonth(month), +day);
    }
  },

  dateNoYear: {
    regex: RegExp('^' + reDateNoYear, 'i'),
    name: 'datenoyear',
    callback: function callback(match, month, day) {
      return this.ymd(this.y, lookupMonth(month), +day);
    }
  },

  dateNoYearRev: {
    regex: RegExp('^' + reDay + '[ .\\t-]*' + reMonthText, 'i'),
    name: 'datenoyearrev',
    callback: function callback(match, day, month) {
      return this.ymd(this.y, lookupMonth(month), +day);
    }
  },

  isoWeekDay: {
    regex: RegExp('^' + reYear4 + '-?W' + reWeekOfYear + '(?:-?([0-7]))?'),
    name: 'isoweekday | isoweek',
    callback: function callback(match, year, week, day) {
      day = day ? +day : 1;

      if (!this.ymd(+year, 0, 1)) {
        return false;
      }

      // get day of week for Jan 1st
      var dayOfWeek = new Date(this.y, this.m, this.d).getDay();

      // and use the day to figure out the offset for day 1 of week 1
      dayOfWeek = 0 - (dayOfWeek > 4 ? dayOfWeek - 7 : dayOfWeek);

      this.rd += dayOfWeek + (week - 1) * 7 + day;
    }
  },

  relativeText: {
    regex: RegExp('^(' + reReltextnumber + '|' + reReltexttext + ')' + reSpace + '(' + reReltextunit + ')', 'i'),
    name: 'relativetext',
    callback: function callback(match, relValue, relUnit) {
      // todo: implement handling of 'this time-unit'
      // eslint-disable-next-line no-unused-vars
      var _lookupRelative = lookupRelative(relValue),
          amount = _lookupRelative.amount,
          behavior = _lookupRelative.behavior;

      switch (relUnit.toLowerCase()) {
        case 'sec':
        case 'secs':
        case 'second':
        case 'seconds':
          this.rs += amount;
          break;
        case 'min':
        case 'mins':
        case 'minute':
        case 'minutes':
          this.ri += amount;
          break;
        case 'hour':
        case 'hours':
          this.rh += amount;
          break;
        case 'day':
        case 'days':
          this.rd += amount;
          break;
        case 'fortnight':
        case 'fortnights':
        case 'forthnight':
        case 'forthnights':
          this.rd += amount * 14;
          break;
        case 'week':
        case 'weeks':
          this.rd += amount * 7;
          break;
        case 'month':
        case 'months':
          this.rm += amount;
          break;
        case 'year':
        case 'years':
          this.ry += amount;
          break;
        case 'mon':case 'monday':
        case 'tue':case 'tuesday':
        case 'wed':case 'wednesday':
        case 'thu':case 'thursday':
        case 'fri':case 'friday':
        case 'sat':case 'saturday':
        case 'sun':case 'sunday':
          this.resetTime();
          this.weekday = lookupWeekday(relUnit, 7);
          this.weekdayBehavior = 1;
          this.rd += (amount > 0 ? amount - 1 : amount) * 7;
          break;
        case 'weekday':
        case 'weekdays':
          // todo
          break;
      }
    }
  },

  relative: {
    regex: RegExp('^([+-]*)[ \\t]*(\\d+)' + reSpaceOpt + '(' + reReltextunit + '|week)', 'i'),
    name: 'relative',
    callback: function callback(match, signs, relValue, relUnit) {
      var minuses = signs.replace(/[^-]/g, '').length;

      var amount = +relValue * Math.pow(-1, minuses);

      switch (relUnit.toLowerCase()) {
        case 'sec':
        case 'secs':
        case 'second':
        case 'seconds':
          this.rs += amount;
          break;
        case 'min':
        case 'mins':
        case 'minute':
        case 'minutes':
          this.ri += amount;
          break;
        case 'hour':
        case 'hours':
          this.rh += amount;
          break;
        case 'day':
        case 'days':
          this.rd += amount;
          break;
        case 'fortnight':
        case 'fortnights':
        case 'forthnight':
        case 'forthnights':
          this.rd += amount * 14;
          break;
        case 'week':
        case 'weeks':
          this.rd += amount * 7;
          break;
        case 'month':
        case 'months':
          this.rm += amount;
          break;
        case 'year':
        case 'years':
          this.ry += amount;
          break;
        case 'mon':case 'monday':
        case 'tue':case 'tuesday':
        case 'wed':case 'wednesday':
        case 'thu':case 'thursday':
        case 'fri':case 'friday':
        case 'sat':case 'saturday':
        case 'sun':case 'sunday':
          this.resetTime();
          this.weekday = lookupWeekday(relUnit, 7);
          this.weekdayBehavior = 1;
          this.rd += (amount > 0 ? amount - 1 : amount) * 7;
          break;
        case 'weekday':
        case 'weekdays':
          // todo
          break;
      }
    }
  },

  dayText: {
    regex: RegExp('^(' + reDaytext + ')', 'i'),
    name: 'daytext',
    callback: function callback(match, dayText) {
      this.resetTime();
      this.weekday = lookupWeekday(dayText, 0);

      if (this.weekdayBehavior !== 2) {
        this.weekdayBehavior = 1;
      }
    }
  },

  relativeTextWeek: {
    regex: RegExp('^(' + reReltexttext + ')' + reSpace + 'week', 'i'),
    name: 'relativetextweek',
    callback: function callback(match, relText) {
      this.weekdayBehavior = 2;

      switch (relText.toLowerCase()) {
        case 'this':
          this.rd += 0;
          break;
        case 'next':
          this.rd += 7;
          break;
        case 'last':
        case 'previous':
          this.rd -= 7;
          break;
      }

      if (isNaN(this.weekday)) {
        this.weekday = 1;
      }
    }
  },

  monthFullOrMonthAbbr: {
    regex: RegExp('^(' + reMonthFull + '|' + reMonthAbbr + ')', 'i'),
    name: 'monthfull | monthabbr',
    callback: function callback(match, month) {
      return this.ymd(this.y, lookupMonth(month), this.d);
    }
  },

  tzCorrection: {
    regex: RegExp('^' + reTzCorrection, 'i'),
    name: 'tzcorrection',
    callback: function callback(tzCorrection) {
      return this.zone(processTzCorrection(tzCorrection));
    }
  },

  tzAbbr: {
    regex: RegExp('^' + reTzAbbr),
    name: 'tzabbr',
    callback: function callback(match, abbr) {
      var offset = tzAbbrOffsets[abbr.toLowerCase()];

      if (isNaN(offset)) {
        return false;
      }

      return this.zone(offset);
    }
  },

  ago: {
    regex: /^ago/i,
    name: 'ago',
    callback: function callback() {
      this.ry = -this.ry;
      this.rm = -this.rm;
      this.rd = -this.rd;
      this.rh = -this.rh;
      this.ri = -this.ri;
      this.rs = -this.rs;
      this.rf = -this.rf;
    }
  },

  year4: {
    regex: RegExp('^' + reYear4),
    name: 'year4',
    callback: function callback(match, year) {
      this.y = +year;
      return true;
    }
  },

  whitespace: {
    regex: /^[ .,\t]+/,
    name: 'whitespace'
    // do nothing
  },

  dateShortWithTimeLong: {
    regex: RegExp('^' + reDateNoYear + 't?' + reHour24 + '[:.]' + reMinute + '[:.]' + reSecond, 'i'),
    name: 'dateshortwithtimelong',
    callback: function callback(match, month, day, hour, minute, second) {
      return this.ymd(this.y, lookupMonth(month), +day) && this.time(+hour, +minute, +second, 0);
    }
  },

  dateShortWithTimeLong12: {
    regex: RegExp('^' + reDateNoYear + reHour12 + '[:.]' + reMinute + '[:.]' + reSecondlz + reSpaceOpt + reMeridian, 'i'),
    name: 'dateshortwithtimelong12',
    callback: function callback(match, month, day, hour, minute, second, meridian) {
      return this.ymd(this.y, lookupMonth(month), +day) && this.time(processMeridian(+hour, meridian), +minute, +second, 0);
    }
  },

  dateShortWithTimeShort: {
    regex: RegExp('^' + reDateNoYear + 't?' + reHour24 + '[:.]' + reMinute, 'i'),
    name: 'dateshortwithtimeshort',
    callback: function callback(match, month, day, hour, minute) {
      return this.ymd(this.y, lookupMonth(month), +day) && this.time(+hour, +minute, 0, 0);
    }
  },

  dateShortWithTimeShort12: {
    regex: RegExp('^' + reDateNoYear + reHour12 + '[:.]' + reMinutelz + reSpaceOpt + reMeridian, 'i'),
    name: 'dateshortwithtimeshort12',
    callback: function callback(match, month, day, hour, minute, meridian) {
      return this.ymd(this.y, lookupMonth(month), +day) && this.time(processMeridian(+hour, meridian), +minute, 0, 0);
    }
  }
};

var resultProto = {
  // date
  y: NaN,
  m: NaN,
  d: NaN,
  // time
  h: NaN,
  i: NaN,
  s: NaN,
  f: NaN,

  // relative shifts
  ry: 0,
  rm: 0,
  rd: 0,
  rh: 0,
  ri: 0,
  rs: 0,
  rf: 0,

  // weekday related shifts
  weekday: NaN,
  weekdayBehavior: 0,

  // first or last day of month
  // 0 none, 1 first, -1 last
  firstOrLastDayOfMonth: 0,

  // timezone correction in minutes
  z: NaN,

  // counters
  dates: 0,
  times: 0,
  zones: 0,

  // helper functions
  ymd: function ymd(y, m, d) {
    if (this.dates > 0) {
      return false;
    }

    this.dates++;
    this.y = y;
    this.m = m;
    this.d = d;
    return true;
  },
  time: function time(h, i, s, f) {
    if (this.times > 0) {
      return false;
    }

    this.times++;
    this.h = h;
    this.i = i;
    this.s = s;
    this.f = f;

    return true;
  },
  resetTime: function resetTime() {
    this.h = 0;
    this.i = 0;
    this.s = 0;
    this.f = 0;
    this.times = 0;

    return true;
  },
  zone: function zone(minutes) {
    if (this.zones <= 1) {
      this.zones++;
      this.z = minutes;
      return true;
    }

    return false;
  },
  toDate: function toDate(relativeTo) {
    if (this.dates && !this.times) {
      this.h = this.i = this.s = this.f = 0;
    }

    // fill holes
    if (isNaN(this.y)) {
      this.y = relativeTo.getFullYear();
    }

    if (isNaN(this.m)) {
      this.m = relativeTo.getMonth();
    }

    if (isNaN(this.d)) {
      this.d = relativeTo.getDate();
    }

    if (isNaN(this.h)) {
      this.h = relativeTo.getHours();
    }

    if (isNaN(this.i)) {
      this.i = relativeTo.getMinutes();
    }

    if (isNaN(this.s)) {
      this.s = relativeTo.getSeconds();
    }

    if (isNaN(this.f)) {
      this.f = relativeTo.getMilliseconds();
    }

    // adjust special early
    switch (this.firstOrLastDayOfMonth) {
      case 1:
        this.d = 1;
        break;
      case -1:
        this.d = 0;
        this.m += 1;
        break;
    }

    if (!isNaN(this.weekday)) {
      var date = new Date(relativeTo.getTime());
      date.setFullYear(this.y, this.m, this.d);
      date.setHours(this.h, this.i, this.s, this.f);

      var dow = date.getDay();

      if (this.weekdayBehavior === 2) {
        // To make "this week" work, where the current day of week is a "sunday"
        if (dow === 0 && this.weekday !== 0) {
          this.weekday = -6;
        }

        // To make "sunday this week" work, where the current day of week is not a "sunday"
        if (this.weekday === 0 && dow !== 0) {
          this.weekday = 7;
        }

        this.d -= dow;
        this.d += this.weekday;
      } else {
        var diff = this.weekday - dow;

        // some PHP magic
        if (this.rd < 0 && diff < 0 || this.rd >= 0 && diff <= -this.weekdayBehavior) {
          diff += 7;
        }

        if (this.weekday >= 0) {
          this.d += diff;
        } else {
          this.d -= 7 - (Math.abs(this.weekday) - dow);
        }

        this.weekday = NaN;
      }
    }

    // adjust relative
    this.y += this.ry;
    this.m += this.rm;
    this.d += this.rd;

    this.h += this.rh;
    this.i += this.ri;
    this.s += this.rs;
    this.f += this.rf;

    this.ry = this.rm = this.rd = 0;
    this.rh = this.ri = this.rs = this.rf = 0;

    var result = new Date(relativeTo.getTime());
    // since Date constructor treats years <= 99 as 1900+
    // it can't be used, thus this weird way
    result.setFullYear(this.y, this.m, this.d);
    result.setHours(this.h, this.i, this.s, this.f);

    // note: this is done twice in PHP
    // early when processing special relatives
    // and late
    // todo: check if the logic can be reduced
    // to just one time action
    switch (this.firstOrLastDayOfMonth) {
      case 1:
        result.setDate(1);
        break;
      case -1:
        result.setMonth(result.getMonth() + 1, 0);
        break;
    }

    // adjust timezone
    if (!isNaN(this.z) && result.getTimezoneOffset() !== this.z) {
      result.setUTCFullYear(result.getFullYear(), result.getMonth(), result.getDate());

      result.setUTCHours(result.getHours(), result.getMinutes(), result.getSeconds() - this.z, result.getMilliseconds());
    }

    return result;
  }
};

module.exports = function strtotime(str, now) {
  //       discuss at: https://locutus.io/php/strtotime/
  //      original by: Caio Ariede (https://caioariede.com)
  //      improved by: Kevin van Zonneveld (https://kvz.io)
  //      improved by: Caio Ariede (https://caioariede.com)
  //      improved by: A. Matías Quezada (https://amatiasq.com)
  //      improved by: preuter
  //      improved by: Brett Zamir (https://brett-zamir.me)
  //      improved by: Mirko Faber
  //         input by: David
  //      bugfixed by: Wagner B. Soares
  //      bugfixed by: Artur Tchernychev
  //      bugfixed by: Stephan Bösch-Plepelits (https://github.com/plepe)
  // reimplemented by: Rafał Kukawski
  //           note 1: Examples all have a fixed timestamp to prevent
  //           note 1: tests to fail because of variable time(zones)
  //        example 1: strtotime('+1 day', 1129633200)
  //        returns 1: 1129719600
  //        example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200)
  //        returns 2: 1130425202
  //        example 3: strtotime('last month', 1129633200)
  //        returns 3: 1127041200
  //        example 4: strtotime('2009-05-04 08:30:00+00')
  //        returns 4: 1241425800
  //        example 5: strtotime('2009-05-04 08:30:00+02:00')
  //        returns 5: 1241418600
  //        example 6: strtotime('2009-05-04 08:30:00 YWT')
  //        returns 6: 1241454600

  if (now == null) {
    now = Math.floor(Date.now() / 1000);
  }

  // the rule order is important
  // if multiple rules match, the longest match wins
  // if multiple rules match the same string, the first match wins
  var rules = [formats.yesterday, formats.now, formats.noon, formats.midnightOrToday, formats.tomorrow, formats.timestamp, formats.firstOrLastDay, formats.backOrFrontOf,
  // formats.weekdayOf, // not yet implemented
  formats.timeTiny12, formats.timeShort12, formats.timeLong12, formats.mssqltime, formats.timeShort24, formats.timeLong24, formats.iso8601long, formats.gnuNoColon, formats.iso8601noColon, formats.americanShort, formats.american, formats.iso8601date4, formats.iso8601dateSlash, formats.dateSlash, formats.gnuDateShortOrIso8601date2, formats.gnuDateShorter, formats.dateFull, formats.pointedDate4, formats.pointedDate2, formats.dateNoDay, formats.dateNoDayRev, formats.dateTextual, formats.dateNoYear, formats.dateNoYearRev, formats.dateNoColon, formats.xmlRpc, formats.xmlRpcNoColon, formats.soap, formats.wddx, formats.exif, formats.pgydotd, formats.isoWeekDay, formats.pgTextShort, formats.pgTextReverse, formats.clf, formats.year4, formats.ago, formats.dayText, formats.relativeTextWeek, formats.relativeText, formats.monthFullOrMonthAbbr, formats.tzCorrection, formats.tzAbbr, formats.dateShortWithTimeShort12, formats.dateShortWithTimeLong12, formats.dateShortWithTimeShort, formats.dateShortWithTimeLong, formats.relative, formats.whitespace];

  var result = Object.create(resultProto);

  while (str.length) {
    var longestMatch = null;
    var finalRule = null;

    for (var i = 0, l = rules.length; i < l; i++) {
      var format = rules[i];

      var match = str.match(format.regex);

      if (match) {
        if (!longestMatch || match[0].length > longestMatch[0].length) {
          longestMatch = match;
          finalRule = format;
        }
      }
    }

    if (!finalRule || finalRule.callback && finalRule.callback.apply(result, longestMatch) === false) {
      return false;
    }

    str = str.substr(longestMatch[0].length);
    finalRule = null;
    longestMatch = null;
  }

  return Math.floor(result.toDate(new Date(now * 1000)) / 1000);
};
//# sourceMappingURL=strtotime.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/info/ini_get.js":
/*!************************************************************!*\
  !*** ../../../../node_modules/locutus/php/info/ini_get.js ***!
  \************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



module.exports = function ini_get(varname) {
  // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/ini_get/
  // original by: Brett Zamir (https://brett-zamir.me)
  //      note 1: The ini values must be set by ini_set or manually within an ini file
  //   example 1: ini_set('date.timezone', 'Asia/Hong_Kong')
  //   example 1: ini_get('date.timezone')
  //   returns 1: 'Asia/Hong_Kong'

  var $global = typeof window !== 'undefined' ? window : __webpack_require__.g;
  $global.$locutus = $global.$locutus || {};
  var $locutus = $global.$locutus;
  $locutus.php = $locutus.php || {};
  $locutus.php.ini = $locutus.php.ini || {};

  if ($locutus.php.ini[varname] && $locutus.php.ini[varname].local_value !== undefined) {
    if ($locutus.php.ini[varname].local_value === null) {
      return '';
    }
    return $locutus.php.ini[varname].local_value;
  }

  return '';
};
//# sourceMappingURL=ini_get.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/explode.js":
/*!***************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/explode.js ***!
  \***************************************************************/
/***/ ((module) => {



var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

module.exports = function explode(delimiter, string, limit) {
  //  discuss at: https://locutus.io/php/explode/
  // original by: Kevin van Zonneveld (https://kvz.io)
  //   example 1: explode(' ', 'Kevin van Zonneveld')
  //   returns 1: [ 'Kevin', 'van', 'Zonneveld' ]

  if (arguments.length < 2 || typeof delimiter === 'undefined' || typeof string === 'undefined') {
    return null;
  }
  if (delimiter === '' || delimiter === false || delimiter === null) {
    return false;
  }
  if (typeof delimiter === 'function' || (typeof delimiter === 'undefined' ? 'undefined' : _typeof(delimiter)) === 'object' || typeof string === 'function' || (typeof string === 'undefined' ? 'undefined' : _typeof(string)) === 'object') {
    return {
      0: ''
    };
  }
  if (delimiter === true) {
    delimiter = '1';
  }

  // Here we go...
  delimiter += '';
  string += '';

  var s = string.split(delimiter);

  if (typeof limit === 'undefined') return s;

  // Support for limit
  if (limit === 0) limit = 1;

  // Positive limit
  if (limit > 0) {
    if (limit >= s.length) {
      return s;
    }
    return s.slice(0, limit - 1).concat([s.slice(limit - 1).join(delimiter)]);
  }

  // Negative limit
  if (-limit >= s.length) {
    return [];
  }

  s.splice(s.length + limit);
  return s;
};
//# sourceMappingURL=explode.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/implode.js":
/*!***************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/implode.js ***!
  \***************************************************************/
/***/ ((module) => {



var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

module.exports = function implode(glue, pieces) {
  //  discuss at: https://locutus.io/php/implode/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Waldo Malqui Silva (https://waldo.malqui.info)
  // improved by: Itsacon (https://www.itsacon.net/)
  // bugfixed by: Brett Zamir (https://brett-zamir.me)
  //   example 1: implode(' ', ['Kevin', 'van', 'Zonneveld'])
  //   returns 1: 'Kevin van Zonneveld'
  //   example 2: implode(' ', {first:'Kevin', last: 'van Zonneveld'})
  //   returns 2: 'Kevin van Zonneveld'

  var i = '';
  var retVal = '';
  var tGlue = '';

  if (arguments.length === 1) {
    pieces = glue;
    glue = '';
  }

  if ((typeof pieces === 'undefined' ? 'undefined' : _typeof(pieces)) === 'object') {
    if (Object.prototype.toString.call(pieces) === '[object Array]') {
      return pieces.join(glue);
    }
    for (i in pieces) {
      retVal += tGlue + pieces[i];
      tGlue = glue;
    }
    return retVal;
  }

  return pieces;
};
//# sourceMappingURL=implode.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/stristr.js":
/*!***************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/stristr.js ***!
  \***************************************************************/
/***/ ((module) => {



module.exports = function stristr(haystack, needle, bool) {
  //  discuss at: https://locutus.io/php/stristr/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
  //   example 1: stristr('Kevin van Zonneveld', 'Van')
  //   returns 1: 'van Zonneveld'
  //   example 2: stristr('Kevin van Zonneveld', 'VAN', true)
  //   returns 2: 'Kevin '

  var pos = 0;

  haystack += '';
  pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());
  if (pos === -1) {
    return false;
  } else {
    if (bool) {
      return haystack.substr(0, pos);
    } else {
      return haystack.slice(pos);
    }
  }
};
//# sourceMappingURL=stristr.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/strlen.js":
/*!**************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/strlen.js ***!
  \**************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



module.exports = function strlen(string) {
  //  discuss at: https://locutus.io/php/strlen/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Sakimori
  // improved by: Kevin van Zonneveld (https://kvz.io)
  //    input by: Kirk Strobeck
  // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
  //  revised by: Brett Zamir (https://brett-zamir.me)
  //      note 1: May look like overkill, but in order to be truly faithful to handling all Unicode
  //      note 1: characters and to this function in PHP which does not count the number of bytes
  //      note 1: but counts the number of characters, something like this is really necessary.
  //   example 1: strlen('Kevin van Zonneveld')
  //   returns 1: 19
  //   example 2: ini_set('unicode.semantics', 'on')
  //   example 2: strlen('A\ud87e\udc04Z')
  //   returns 2: 3

  var str = string + '';

  var iniVal = ( true ? __webpack_require__(/*! ../info/ini_get */ "../../../../node_modules/locutus/php/info/ini_get.js")('unicode.semantics') : 0) || 'off';
  if (iniVal === 'off') {
    return str.length;
  }

  var i = 0;
  var lgth = 0;

  var getWholeChar = function getWholeChar(str, i) {
    var code = str.charCodeAt(i);
    var next = '';
    var prev = '';
    if (code >= 0xD800 && code <= 0xDBFF) {
      // High surrogate (could change last hex to 0xDB7F to
      // treat high private surrogates as single characters)
      if (str.length <= i + 1) {
        throw new Error('High surrogate without following low surrogate');
      }
      next = str.charCodeAt(i + 1);
      if (next < 0xDC00 || next > 0xDFFF) {
        throw new Error('High surrogate without following low surrogate');
      }
      return str.charAt(i) + str.charAt(i + 1);
    } else if (code >= 0xDC00 && code <= 0xDFFF) {
      // Low surrogate
      if (i === 0) {
        throw new Error('Low surrogate without preceding high surrogate');
      }
      prev = str.charCodeAt(i - 1);
      if (prev < 0xD800 || prev > 0xDBFF) {
        // (could change last hex to 0xDB7F to treat high private surrogates
        // as single characters)
        throw new Error('Low surrogate without preceding high surrogate');
      }
      // We can pass over low surrogates now as the second
      // component in a pair which we have already processed
      return false;
    }
    return str.charAt(i);
  };

  for (i = 0, lgth = 0; i < str.length; i++) {
    if (getWholeChar(str, i) === false) {
      continue;
    }
    // Adapt this line at the top of any loop, passing in the whole string and
    // the current iteration and returning a variable to represent the individual character;
    // purpose is to treat the first part of a surrogate pair as the whole character and then
    // ignore the second part
    lgth++;
  }

  return lgth;
};
//# sourceMappingURL=strlen.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/strstr.js":
/*!**************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/strstr.js ***!
  \**************************************************************/
/***/ ((module) => {



module.exports = function strstr(haystack, needle, bool) {
  //  discuss at: https://locutus.io/php/strstr/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
  // improved by: Kevin van Zonneveld (https://kvz.io)
  //   example 1: strstr('Kevin van Zonneveld', 'van')
  //   returns 1: 'van Zonneveld'
  //   example 2: strstr('Kevin van Zonneveld', 'van', true)
  //   returns 2: 'Kevin '
  //   example 3: strstr('name@example.com', '@')
  //   returns 3: '@example.com'
  //   example 4: strstr('name@example.com', '@', true)
  //   returns 4: 'name'

  var pos = 0;

  haystack += '';
  pos = haystack.indexOf(needle);
  if (pos === -1) {
    return false;
  } else {
    if (bool) {
      return haystack.substr(0, pos);
    } else {
      return haystack.slice(pos);
    }
  }
};
//# sourceMappingURL=strstr.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/strtolower.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/strtolower.js ***!
  \******************************************************************/
/***/ ((module) => {



module.exports = function strtolower(str) {
  //  discuss at: https://locutus.io/php/strtolower/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Onno Marsman (https://twitter.com/onnomarsman)
  //   example 1: strtolower('Kevin van Zonneveld')
  //   returns 1: 'kevin van zonneveld'

  return (str + '').toLowerCase();
};
//# sourceMappingURL=strtolower.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/strtoupper.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/strtoupper.js ***!
  \******************************************************************/
/***/ ((module) => {



module.exports = function strtoupper(str) {
  //  discuss at: https://locutus.io/php/strtoupper/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Onno Marsman (https://twitter.com/onnomarsman)
  //   example 1: strtoupper('Kevin van Zonneveld')
  //   returns 1: 'KEVIN VAN ZONNEVELD'

  return (str + '').toUpperCase();
};
//# sourceMappingURL=strtoupper.js.map

/***/ }),

/***/ "../../../../node_modules/locutus/php/strings/substr.js":
/*!**************************************************************!*\
  !*** ../../../../node_modules/locutus/php/strings/substr.js ***!
  \**************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



module.exports = function substr(input, start, len) {
  //  discuss at: https://locutus.io/php/substr/
  // original by: Martijn Wieringa
  // bugfixed by: T.Wild
  // improved by: Onno Marsman (https://twitter.com/onnomarsman)
  // improved by: Brett Zamir (https://brett-zamir.me)
  //  revised by: Theriault (https://github.com/Theriault)
  //  revised by: Rafał Kukawski
  //      note 1: Handles rare Unicode characters if 'unicode.semantics' ini (PHP6) is set to 'on'
  //   example 1: substr('abcdef', 0, -1)
  //   returns 1: 'abcde'
  //   example 2: substr(2, 0, -6)
  //   returns 2: false
  //   example 3: ini_set('unicode.semantics', 'on')
  //   example 3: substr('a\uD801\uDC00', 0, -1)
  //   returns 3: 'a'
  //   example 4: ini_set('unicode.semantics', 'on')
  //   example 4: substr('a\uD801\uDC00', 0, 2)
  //   returns 4: 'a\uD801\uDC00'
  //   example 5: ini_set('unicode.semantics', 'on')
  //   example 5: substr('a\uD801\uDC00', -1, 1)
  //   returns 5: '\uD801\uDC00'
  //   example 6: ini_set('unicode.semantics', 'on')
  //   example 6: substr('a\uD801\uDC00z\uD801\uDC00', -3, 2)
  //   returns 6: '\uD801\uDC00z'
  //   example 7: ini_set('unicode.semantics', 'on')
  //   example 7: substr('a\uD801\uDC00z\uD801\uDC00', -3, -1)
  //   returns 7: '\uD801\uDC00z'
  //        test: skip-3 skip-4 skip-5 skip-6 skip-7

  var _php_cast_string = __webpack_require__(/*! ../_helpers/_phpCastString */ "../../../../node_modules/locutus/php/_helpers/_phpCastString.js"); // eslint-disable-line camelcase

  input = _php_cast_string(input);

  var ini_get = __webpack_require__(/*! ../info/ini_get */ "../../../../node_modules/locutus/php/info/ini_get.js"); // eslint-disable-line camelcase
  var multibyte = ini_get('unicode.semantics') === 'on';

  if (multibyte) {
    input = input.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [];
  }

  var inputLength = input.length;
  var end = inputLength;

  if (start < 0) {
    start += end;
  }

  if (typeof len !== 'undefined') {
    if (len < 0) {
      end = len + end;
    } else {
      end = len + start;
    }
  }

  if (start > inputLength || start < 0 || start > end) {
    return false;
  }

  if (multibyte) {
    return input.slice(start, end).join('');
  }

  return input.slice(start, end);
};
//# sourceMappingURL=substr.js.map

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./src/js/fields/calculations.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieCalculations: () => (/* binding */ FormieCalculations)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var expression_language__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! expression-language */ "../../../../node_modules/expression-language/lib/index.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }


var FormieCalculations = /*#__PURE__*/function () {
  function FormieCalculations() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieCalculations);
    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.$input = this.$field.querySelector('input');
    this.formula = settings.formula.formula;
    this.variables = settings.formula.variables;
    this.formatting = settings.formatting;
    this.prefix = settings.prefix;
    this.suffix = settings.suffix;
    this.decimals = settings.decimals;
    this.fieldsStore = {};
    this.expressionLanguage = new expression_language__WEBPACK_IMPORTED_MODULE_1__["default"]();
    this.initCalculations();
  }
  _createClass(FormieCalculations, [{
    key: "initCalculations",
    value: function initCalculations() {
      var _this = this;
      // For every dynamic field defined in the formula, listen to changes and re-calculate
      Object.keys(this.variables).forEach(function (variableKey) {
        var variable = _this.variables[variableKey];
        var $targets = _this.$form.querySelectorAll("[name=\"".concat(variable.name, "\"]"));
        if (!$targets || !$targets.length) {
          return;
        }

        // Save the resolved target for later
        _this.fieldsStore[variableKey] = _objectSpread({
          $targets: $targets
        }, variable);
        $targets.forEach(function ($target) {
          // Get the right event for the field
          var eventType = _this.getEventType($target);

          // Watch for changes on the target field. When one occurs, fire off a custom event on the source field
          _this.form.addEventListener($target, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)(eventType), function () {
            return _this.$field.dispatchEvent(new CustomEvent('onFormieEvaluateCalculations', {
              bubbles: true,
              detail: {
                calculations: _this
              }
            }));
          });
        });
      });

      // Add a custom event listener to fire when the field event listener fires
      this.form.addEventListener(this.$field, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormieEvaluateCalculations'), this.evaluateCalculations.bind(this));

      // Also - trigger the event right now to evaluate immediately. Namely if we need to hide
      // field that are set to show if conditions are met.
      this.$field.dispatchEvent(new CustomEvent('onFormieEvaluateCalculations', {
        bubbles: true,
        detail: {
          calculations: this,
          init: true
        }
      }));

      // Update the form hash, so we don't get change warnings
      if (this.form.formTheme) {
        this.form.formTheme.updateFormHash();
      }
    }
  }, {
    key: "evaluateCalculations",
    value: function evaluateCalculations(e) {
      var _this2 = this;
      var $field = e.target;
      var isInit = e.detail ? e.detail.init : false;
      var formula = this.formula;
      var variables = {};

      // For each variable, grab the value
      Object.keys(this.fieldsStore).forEach(function (variableKey) {
        var _this2$fieldsStore$va = _this2.fieldsStore[variableKey],
          $targets = _this2$fieldsStore$va.$targets,
          type = _this2$fieldsStore$va.type;

        // Set a sane default
        variables[variableKey] = '';

        // We pass target DOM elements as a NodeList, but in almost all cases,
        // they're a list of a single element. Radio fields are special though.
        $targets.forEach(function ($target) {
          // Handle some fields differently and check for type-casting
          if (type === 'verbb\\formie\\fields\\Number') {
            variables[variableKey] = Number($target.value);
          } else if (type === 'verbb\\formie\\fields\\Radio') {
            if ($target.checked) {
              variables[variableKey] = $target.value;
            }
          } else if (type === 'verbb\\formie\\fields\\Checkboxes') {
            if ($target.checked) {
              if (!Array.isArray(variables[variableKey])) {
                variables[variableKey] = [];
              }
              variables[variableKey].push($target.value);
            }
          } else {
            variables[variableKey] = $target.value;
          }
        });
      });

      // See if we need to format some variables depending on formatting
      variables = this.formatVariables(variables);

      // Allow events to modify the data before evaluation
      var beforeEvaluateEvent = new CustomEvent('beforeEvaluate', {
        bubbles: true,
        detail: {
          calculations: this,
          init: isInit,
          formula: formula,
          variables: variables
        }
      });
      $field.dispatchEvent(beforeEvaluateEvent);

      // Events can modify the formula and variables
      // eslint-disable-next-line
      formula = beforeEvaluateEvent.detail.formula;
      // eslint-disable-next-line
      variables = beforeEvaluateEvent.detail.variables;

      // Prevent evaluating empty data
      if (!formula || !variables) {
        return;
      }
      try {
        var result = this.expressionLanguage.evaluate(formula, variables);

        // Format the result, if required
        result = this.formatValue(result);

        // Allow events to modify the data after evaluation
        var afterEvaluateEvent = new CustomEvent('afterEvaluate', {
          bubbles: true,
          detail: {
            calculations: this,
            init: isInit,
            formula: formula,
            variables: variables,
            result: result
          }
        });
        $field.dispatchEvent(afterEvaluateEvent);

        // Events can modify the result
        // eslint-disable-next-line
        result = afterEvaluateEvent.detail.result;

        // Handle null-like results. If they're `NaN`, `false` set as empty, but `0` is valid
        if (typeof result === 'undefined' || Number.isNaN(result)) {
          result = '';
        }
        this.$input.value = result;

        // Trigger a `input` event for the input as well
        this.$input.dispatchEvent(new Event('input'));
      } catch (ex) {
        console.error(ex);

        // Always reset in the event of an error
        this.$input.value = '';
      }
    }
  }, {
    key: "getEventType",
    value: function getEventType($input) {
      var $field = $input.closest('[data-field-type]');
      var fieldType = $field === null || $field === void 0 ? void 0 : $field.getAttribute('data-field-type');
      var tagName = $input.tagName.toLowerCase();
      var inputType = $input.getAttribute('type') ? $input.getAttribute('type').toLowerCase() : '';
      if (tagName === 'select' || inputType === 'date') {
        return 'change';
      }
      if (inputType === 'number') {
        return 'input';
      }
      if (inputType === 'checkbox' || inputType === 'radio') {
        return 'click';
      }

      // If sourcing a value from another calculations, this'll be a `input` event
      if (fieldType && fieldType === 'calculations') {
        return 'input';
      }
      return 'keyup';
    }
  }, {
    key: "formatVariables",
    value: function formatVariables(variables) {
      if (this.formatting === 'number') {
        Object.keys(variables).forEach(function (index) {
          if (Array.isArray(variables[index])) {
            variables[index].forEach(function (i, j) {
              variables[index][j] = Number(variables[index][j]);
            });
          } else {
            variables[index] = Number(variables[index]);
          }
        });
      }
      return variables;
    }
  }, {
    key: "formatValue",
    value: function formatValue(value) {
      if (this.formatting === 'number') {
        // TODO: allow handling of array values more thatn just sum (e.g. 1,2,3)
        if (Array.isArray(value)) {
          value = value.reduce(function (partialSum, a) {
            return partialSum + a;
          }, 0);
        }

        // Assume no rounding if not providing decimals, but formatting as number
        if (this.decimals) {
          value = Number(value).toFixed(this.decimals);
        } else {
          value = Number(value).toFixed(0);
        }
        if (this.prefix) {
          value = this.prefix + value;
        }
        if (this.suffix) {
          value = value + this.suffix;
        }
      }
      return value;
    }
  }]);
  return FormieCalculations;
}();
window.FormieCalculations = FormieCalculations;
})();

/******/ })()
;