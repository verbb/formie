function bt(c, p) {
  for (var y = 0; y < p.length; y++) {
    const u = p[y];
    if (typeof u != "string" && !Array.isArray(u)) {
      for (const w in u)
        if (w !== "default" && !(w in c)) {
          const x = Object.getOwnPropertyDescriptor(u, w);
          x && Object.defineProperty(c, w, x.get ? x : {
            enumerable: !0,
            get: () => u[w]
          });
        }
    }
  }
  return Object.freeze(Object.defineProperty(c, Symbol.toStringTag, { value: "Module" }));
}
function xt(c) {
  return Array.isArray(c) ? c.map((p) => String(p ?? "")) : [String(c ?? "")];
}
function We(c, p) {
  return c.some((y) => p.includes(y));
}
function He(c, p) {
  return c.some((y) => p.some((u) => u === y || u.includes(y)));
}
function Be(c, p, y) {
  return p.some((u) => c.some((w) => y(w, u)));
}
function Ge(c, p, y) {
  return p.some((u) => {
    const w = Number.parseFloat(u);
    return Number.isFinite(w) ? c.some((x) => {
      const T = Number.parseFloat(x);
      return Number.isFinite(T) ? y(T, w) : !1;
    }) : !1;
  });
}
function Ve(c) {
  return c.length === 0 || c.every((p) => p.trim() === "");
}
function Rt(c, p, y = {}) {
  const u = String(c.condition || ""), w = xt(c.value), x = y.visibility ?? null;
  switch (u) {
    case "=":
      return We(w, p);
    case "!=":
      return !We(w, p);
    case ">":
      return Ge(p, w, (T, h) => T > h);
    case "<":
      return Ge(p, w, (T, h) => T < h);
    case "contains":
      return He(w, p);
    case "notContains":
      return !He(w, p);
    case "startsWith":
      return Be(p, w, (T, h) => T.startsWith(h));
    case "endsWith":
      return Be(p, w, (T, h) => T.endsWith(h));
    case "empty":
      return Ve(p);
    case "notEmpty":
      return !Ve(p);
    case "visible":
      return x === !0;
    case "hidden":
      return x === !1;
    default:
      return !1;
  }
}
function jt(c, p) {
  const y = c.conditionRule === "any" ? p.includes(!0) : p.every((w) => w === !0), u = y && c.showRule !== "show" || !y && c.showRule === "show";
  return {
    finalResult: y,
    shouldHide: u
  };
}
var L = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : typeof self < "u" ? self : {};
function vt(c) {
  return c && c.__esModule && Object.prototype.hasOwnProperty.call(c, "default") ? c.default : c;
}
var ne = { exports: {} }, Ke = ne.exports, Je;
function Nt() {
  return Je || (Je = 1, (function(c, p) {
    (function(y, u) {
      u(p);
    })(Ke, function(y) {
      function u(n, t, e) {
        return (t = (function(s) {
          var r = (function(i, a) {
            if (typeof i != "object" || !i) return i;
            var o = i[Symbol.toPrimitive];
            if (o !== void 0) {
              var l = o.call(i, a);
              if (typeof l != "object") return l;
              throw new TypeError("@@toPrimitive must return a primitive value.");
            }
            return (a === "string" ? String : Number)(i);
          })(s, "string");
          return typeof r == "symbol" ? r : r + "";
        })(t)) in n ? Object.defineProperty(n, t, { value: e, enumerable: !0, configurable: !0, writable: !0 }) : n[t] = e, n;
      }
      const w = function(n, t) {
        if (n.length === 0) return t.length;
        if (t.length === 0) return n.length;
        let e, s, r = [];
        for (e = 0; e <= t.length; e++) r[e] = [e];
        for (s = 0; s <= n.length; s++) r[0] === void 0 && (r[0] = []), r[0][s] = s;
        for (e = 1; e <= t.length; e++) for (s = 1; s <= n.length; s++) t.charAt(e - 1) === n.charAt(s - 1) ? r[e][s] = r[e - 1][s - 1] : r[e][s] = Math.min(r[e - 1][s - 1] + 1, Math.min(r[e][s - 1] + 1, r[e - 1][s] + 1));
        return r[t.length] === void 0 && (r[t.length] = []), r[t.length][n.length];
      };
      class x extends Error {
        constructor(t, e, s, r, i) {
          super(t), this.name = "SyntaxError", this.cursor = e, this.expression = s, this.subject = r, this.proposals = i;
        }
        toString() {
          let t = `${this.name}: ${this.message} around position ${this.cursor}`;
          if (this.expression && (t += ` for expression \`${this.expression}\``), t += ".", this.subject && this.proposals) {
            let e = Number.MAX_SAFE_INTEGER, s = null;
            for (let r of this.proposals) {
              let i = w(this.subject, r);
              i < e && (s = r, e = i);
            }
            s !== null && e < 3 && (t += ` Did you mean "${s}"?`);
          }
          return t;
        }
      }
      class T {
        constructor(t, e) {
          u(this, "next", () => {
            if (this.position += 1, this.tokens[this.position] === void 0) throw new x("Unexpected end of expression", this.last.cursor, this.expression);
          }), u(this, "expect", (s, r, i) => {
            let a = this.current;
            if (!a.test(s, r)) {
              let o = "";
              i && (o = i + ". ");
              let l = "";
              throw r && (l = ` with value "${r}"`), o += `Unexpected token "${a.type}" of value "${a.value}" ("${s}" expected${l})`, new x(o, a.cursor, this.expression);
            }
            this.next();
          }), u(this, "isEOF", () => h.EOF_TYPE === this.current.type), u(this, "isEqualTo", (s) => {
            if (s == null || !s instanceof T || s.tokens.length !== this.tokens.length) return !1;
            let r = s.position;
            s.position = 0;
            let i = !0;
            for (let a of this.tokens) {
              if (!s.current.isEqualTo(a)) {
                i = !1;
                break;
              }
              s.position < s.tokens.length - 1 && s.next();
            }
            return s.position = r, i;
          }), u(this, "diff", (s) => {
            let r = [];
            if (!this.isEqualTo(s)) {
              let i = 0, a = s.position;
              s.position = 0;
              for (let o of this.tokens) {
                let l = o.diff(s.current);
                l.length > 0 && r.push({ index: i, diff: l }), s.position < s.tokens.length - 1 && s.next();
              }
              s.position = a;
            }
            return r;
          }), this.expression = t, this.position = 0, this.tokens = e;
        }
        get current() {
          return this.tokens[this.position];
        }
        get last() {
          return this.tokens[this.position - 1];
        }
        toString() {
          return this.tokens.join(`
`);
        }
      }
      class h {
        constructor(t, e, s) {
          u(this, "test", (r, i = null) => this.type === r && (i === null || this.value === i)), u(this, "isEqualTo", (r) => !(r == null || !r instanceof h) && r.value == this.value && r.type === this.type && r.cursor === this.cursor), u(this, "diff", (r) => {
            let i = [];
            return this.isEqualTo(r) || (r.value !== this.value && i.push(`Value: ${r.value} != ${this.value}`), r.cursor !== this.cursor && i.push(`Cursor: ${r.cursor} != ${this.cursor}`), r.type !== this.type && i.push(`Type: ${r.type} != ${this.type}`)), i;
          }), this.value = e, this.type = t, this.cursor = s;
        }
        toString() {
          return `${this.cursor} [${this.type}] ${this.value}`;
        }
      }
      function pe(n) {
        let t = 0, e = [], s = [], r = (n = n.replace(/\r|\n|\t|\v|\f/g, " ")).length;
        for (; t < r; ) {
          if (n[t] === " ") {
            ++t;
            continue;
          }
          if (n.substr(t, 2) === "/*") {
            const a = n.indexOf("*/", t + 2);
            if (a === -1) {
              t = r;
              break;
            }
            t = a + 2;
            continue;
          }
          let i = tt(n.substr(t));
          if (i !== null) {
            const a = i.length, o = i.replace(/_/g, "");
            i = o.indexOf(".") === -1 && o.indexOf("e") === -1 && o.indexOf("E") === -1 ? parseInt(o, 10) : parseFloat(o), e.push(new h(h.NUMBER_TYPE, i, t + 1)), t += a;
          } else if ("([{".indexOf(n[t]) >= 0) s.push([n[t], t]), e.push(new h(h.PUNCTUATION_TYPE, n[t], t + 1)), ++t;
          else if (")]}".indexOf(n[t]) >= 0) {
            if (s.length === 0) throw new x(`Unexpected "${n[t]}"`, t, n);
            let [a, o] = s.pop(), l = a.replace("(", ")").replace("{", "}").replace("[", "]");
            if (n[t] !== l) throw new x(`Unclosed "${a}"`, o, n);
            e.push(new h(h.PUNCTUATION_TYPE, n[t], t + 1)), ++t;
          } else {
            let a = rt(n.substr(t));
            if (a !== null) e.push(new h(h.STRING_TYPE, a.captured, t + 1)), t += a.length;
            else if (n.substr(t, 2) === "\\\\") e.push(new h(h.PUNCTUATION_TYPE, "\\", t + 1)), t += 2;
            else {
              const o = e.length > 0 ? e[e.length - 1] : null;
              if (o && o.type === h.PUNCTUATION_TYPE && (o.value === "." || o.value === "?.")) {
                let l = ye(n.substr(t));
                if (l) e.push(new h(h.NAME_TYPE, l, t + 1)), t += l.length;
                else {
                  let d = ge(n.substr(t));
                  if (d) e.push(new h(h.OPERATOR_TYPE, d, t + 1)), t += d.length;
                  else if (n.substr(t, 2) === "?." || n.substr(t, 2) === "??") e.push(new h(h.PUNCTUATION_TYPE, n.substr(t, 2), t + 1)), t += 2;
                  else {
                    if (!(".,?:".indexOf(n[t]) >= 0)) throw new x(`Unexpected character "${n[t]}"`, t, n);
                    e.push(new h(h.PUNCTUATION_TYPE, n[t], t + 1)), ++t;
                  }
                }
              } else {
                let l = ge(n.substr(t));
                if (l) e.push(new h(h.OPERATOR_TYPE, l, t + 1)), t += l.length;
                else if (n.substr(t, 2) === "?." || n.substr(t, 2) === "??") e.push(new h(h.PUNCTUATION_TYPE, n.substr(t, 2), t + 1)), t += 2;
                else if (".,?:".indexOf(n[t]) >= 0) e.push(new h(h.PUNCTUATION_TYPE, n[t], t + 1)), ++t;
                else {
                  let d = ye(n.substr(t));
                  if (!d) throw new x(`Unexpected character "${n[t]}"`, t, n);
                  e.push(new h(h.NAME_TYPE, d, t + 1)), t += d.length;
                }
              }
            }
          }
        }
        if (e.push(new h(h.EOF_TYPE, null, t + 1)), s.length > 0) {
          let [i, a] = s.pop();
          throw new x(`Unclosed "${i}"`, a, n);
        }
        return new T(n, e);
      }
      function tt(n) {
        let t = null, e = n.match(/^(?:((?:\d(?:_?\d)*)\.(?:\d(?:_?\d)*)|\.(?:\d(?:_?\d)*)|(?:\d(?:_?\d)*))(?:[eE][+-]?\d(?:_?\d)*)?)/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      u(h, "EOF_TYPE", "end of expression"), u(h, "NAME_TYPE", "name"), u(h, "NUMBER_TYPE", "number"), u(h, "STRING_TYPE", "string"), u(h, "OPERATOR_TYPE", "operator"), u(h, "PUNCTUATION_TYPE", "punctuation");
      const st = /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;
      function me(n, t) {
        return t === '"' ? n = n.replace(/\\\"/g, '"') : t === "'" && (n = n.replace(/\\'/g, "'")), n = n.replace(/\\\\/g, "\\");
      }
      function rt(n) {
        let t = null;
        if (["'", '"'].indexOf(n.substr(0, 1)) === -1) return t;
        let e = st.exec(n);
        return e !== null && e.length > 0 && (t = e[1] !== void 0 ? { captured: me(e[1], '"') } : { captured: me(e[2], "'") }, t.length = e[0].length), t;
      }
      const nt = ["&&", "and", "||", "or", "+", "-", "**", "*", "/", "%", "&", "|", "^", ">>", "<<", "===", "!==", "!=", "==", "<=", ">=", "<", ">", "contains", "matches", "starts with", "ends with", "not in", "in", "not", "!", "xor", "~", ".."], it = ["and", "or", "matches", "contains", "starts with", "ends with", "not in", "in", "not", "xor"];
      function ge(n) {
        let t = null;
        for (let e of nt) if (n.substr(0, e.length) === e) {
          it.indexOf(e) >= 0 ? n.substr(0, e.length + 1) === e + " " && (t = e) : t = e;
          break;
        }
        return t;
      }
      function ye(n) {
        let t = null, e = n.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      function at(n) {
        return /boolean|number|string/.test(typeof n);
      }
      function we(n, t) {
        var e = "", s = [], r = 0, i = 0, a = "", o = "", l = "", d = "", f = "", g = 0, b = 0, $ = 0, D = 0, De = 0, se = [], fe = "", Me = /%([\dA-Fa-f]+)/g, Fe = function(re, ze) {
          return (re += "").length < ze ? new Array(++ze - re.length).join("0") + re : re;
        };
        for (r = 0; r < t.length; r++) if (a = t.charAt(r), o = t.charAt(r + 1), a === "\\" && o && /\d/.test(o)) {
          if (D = r + ($ = (l = t.slice(r + 1).match(/^\d+/)[0]).length) + 1, t.charAt(D) + t.charAt(D + 1) === "..") {
            if (g = l.charCodeAt(0), /\\\d/.test(t.charAt(D + 2) + t.charAt(D + 3))) d = t.slice(D + 3).match(/^\d+/)[0], r += 1;
            else {
              if (!t.charAt(D + 2)) throw new Error("Range with no end point");
              d = t.charAt(D + 2);
            }
            if ((b = d.charCodeAt(0)) > g) for (i = g; i <= b; i++) s.push(String.fromCharCode(i));
            else s.push(".", l, d);
            r += d.length + 2;
          } else f = String.fromCharCode(parseInt(l, 8)), s.push(f);
          r += $;
        } else if (o + t.charAt(r + 2) === "..") {
          if (g = (l = a).charCodeAt(0), /\\\d/.test(t.charAt(r + 3) + t.charAt(r + 4))) d = t.slice(r + 4).match(/^\d+/)[0], r += 1;
          else {
            if (!t.charAt(r + 3)) throw new Error("Range with no end point");
            d = t.charAt(r + 3);
          }
          if ((b = d.charCodeAt(0)) > g) for (i = g; i <= b; i++) s.push(String.fromCharCode(i));
          else s.push(".", l, d);
          r += d.length + 2;
        } else s.push(a);
        for (r = 0; r < n.length; r++) if (a = n.charAt(r), s.indexOf(a) !== -1) if (e += "\\", (De = a.charCodeAt(0)) < 32 || De > 126) switch (a) {
          case `
`:
            e += "n";
            break;
          case "	":
            e += "t";
            break;
          case "\r":
            e += "r";
            break;
          case "\x07":
            e += "a";
            break;
          case "\v":
            e += "v";
            break;
          case "\b":
            e += "b";
            break;
          case "\f":
            e += "f";
            break;
          default:
            for (fe = encodeURIComponent(a), (se = Me.exec(fe)) !== null && (e += Fe(parseInt(se[1], 16).toString(8), 3)); (se = Me.exec(fe)) !== null; ) e += "\\" + Fe(parseInt(se[1], 16).toString(8), 3);
        }
        else e += a;
        else e += a;
        return e;
      }
      class A {
        constructor(t = {}, e = {}) {
          u(this, "compile", (s) => {
            for (let r of Object.values(this.nodes)) r.compile(s);
          }), u(this, "evaluate", (s, r) => {
            let i = [];
            for (let a of Object.values(this.nodes)) i.push(a.evaluate(s, r));
            return i;
          }), u(this, "toArray", () => {
            throw new Error(`Dumping a "${this.name}" instance is not supported yet.`);
          }), u(this, "dump", () => {
            let s = "";
            for (let r of this.toArray()) s += at(r) ? r : r.dump();
            return s;
          }), u(this, "dumpString", (s) => `"${we(s, '\0	"\\')}"`), u(this, "isHash", (s) => {
            let r = 0;
            for (let i of Object.keys(s)) if (i = parseInt(i), i !== r++) return !0;
            return !1;
          }), this.name = "Node", this.nodes = t, this.attributes = e;
        }
        toString() {
          let t = [];
          for (let s of Object.keys(this.attributes)) {
            let r = "null";
            this.attributes[s] && (r = this.attributes[s].toString()), t.push(`${s}: '${r}'`);
          }
          let e = [this.name + "(" + t.join(", ")];
          if (this.nodes.length > 0) {
            for (let s of Object.values(this.nodes)) {
              let r = s.toString().split(`
`);
              for (let i of r) e.push("    " + i);
            }
            e.push(")");
          } else e[0] += ")";
          return e.join(`
`);
        }
      }
      class S extends A {
        constructor(t, e, s) {
          super({ left: e, right: s }, { operator: t }), u(this, "compile", (r) => {
            let i = this.attributes.operator;
            i !== "matches" ? i !== "contains" ? i !== "starts with" ? i !== "ends with" ? S.functions[i] === void 0 ? (S.operators[i] !== void 0 && (i = S.operators[i]), r.raw("(").compile(this.nodes.left).raw(" ").raw(i).raw(" ").compile(this.nodes.right).raw(")")) : r.raw(`${S.functions[i]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")") : r.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : r.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : r.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())") : r.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")");
          }), u(this, "evaluate", (r, i) => {
            let a = this.attributes.operator, o = this.nodes.left.evaluate(r, i);
            if (S.functions[a] !== void 0) {
              let d = this.nodes.right.evaluate(r, i);
              switch (a) {
                case "not in":
                  return d.indexOf(o) === -1;
                case "in":
                  return d.indexOf(o) >= 0;
                case "..":
                  return (function(f, g) {
                    let b = [];
                    for (let $ = f; $ <= g; $++) b.push($);
                    return b;
                  })(o, d);
                case "**":
                  return Math.pow(o, d);
              }
            }
            let l = null;
            switch (a) {
              case "or":
              case "||":
                return o || (l = this.nodes.right.evaluate(r, i)), o || l;
              case "and":
              case "&&":
                return o && (l = this.nodes.right.evaluate(r, i)), o && l;
              case "xor":
                return l = this.nodes.right.evaluate(r, i), l && !o || o && !l;
              case "<<":
                return l = this.nodes.right.evaluate(r, i), o << l;
              case ">>":
                return l = this.nodes.right.evaluate(r, i), o >> l;
            }
            switch (l = this.nodes.right.evaluate(r, i), a) {
              case "|":
                return o | l;
              case "^":
                return o ^ l;
              case "&":
                return o & l;
              case "==":
                return o == l;
              case "===":
                return o === l;
              case "!=":
                return o != l;
              case "!==":
                return o !== l;
              case "<":
                return o < l;
              case ">":
                return o > l;
              case ">=":
                return o >= l;
              case "<=":
                return o <= l;
              case "not in":
                return l.indexOf(o) === -1;
              case "in":
                return l.indexOf(o) >= 0;
              case "+":
                return o + l;
              case "-":
                return o - l;
              case "~":
                return o.toString() + l.toString();
              case "*":
                return o * l;
              case "/":
                return o / l;
              case "%":
                return o % l;
              case "matches":
                if (o == null) return !1;
                let d = l.match(S.regex_expression);
                return new RegExp(d[1], d[2]).test(o);
              case "contains":
                return o.toString().toLowerCase().includes(l.toString().toLowerCase());
              case "starts with":
                return o.toString().toLowerCase().startsWith(l.toString().toLowerCase());
              case "ends with":
                return o.toString().toLowerCase().endsWith(l.toString().toLowerCase());
            }
          }), u(this, "toArray", () => ["(", this.nodes.left, " " + this.attributes.operator + " ", this.nodes.right, ")"]), this.name = "BinaryNode";
        }
      }
      u(S, "regex_expression", /\/(.+)\/(.*)/), u(S, "operators", { "~": ".", and: "&&", or: "||", xor: "xor", "<<": "<<", ">>": ">>" }), u(S, "functions", { "**": "Math.pow", "..": "range", in: "includes", "not in": "!includes" });
      class V extends A {
        constructor(t, e) {
          super({ node: e }, { operator: t }), u(this, "compile", (s) => {
            s.raw("(").raw(V.operators[this.attributes.operator]).compile(this.nodes.node).raw(")");
          }), u(this, "evaluate", (s, r) => {
            let i = this.nodes.node.evaluate(s, r);
            switch (this.attributes.operator) {
              case "not":
              case "!":
                return !i;
              case "-":
                return -i;
              case "~":
                return ~i;
            }
            return i;
          }), u(this, "toArray", () => ["(", this.attributes.operator + " ", this.nodes.node, ")"]), this.name = "UnaryNode";
        }
      }
      u(V, "operators", { "!": "!", not: "!", "+": "+", "-": "-", "~": "~" });
      class k extends A {
        constructor(t, e = !1, s = !1) {
          super({}, { value: t }), u(this, "compile", (r) => {
            r.repr(this.attributes.value, this.isIdentifier);
          }), u(this, "evaluate", (r, i) => this.attributes.value), u(this, "toArray", () => {
            let r = [], i = this.attributes.value;
            if (this.isIdentifier) r.push(i);
            else if (i === !0) r.push("true");
            else if (i === !1) r.push("false");
            else if (i === null) r.push("null");
            else if (typeof i == "number") r.push(i);
            else if (typeof i == "string") r.push(this.dumpString(i));
            else if (Array.isArray(i)) {
              for (let a of i) r.push(","), r.push(new k(a));
              r[0] = "[", r.push("]");
            } else if (this.isHash(i)) {
              for (let a of Object.keys(i)) r.push(", "), r.push(new k(a)), r.push(": "), r.push(new k(i[a]));
              r[0] = "{", r.push("}");
            }
            return r;
          }), this.isIdentifier = e, this.isNullSafe = s, this.name = "ConstantNode";
        }
      }
      class ie extends A {
        constructor(t, e, s) {
          super({ expr1: t, expr2: e, expr3: s }), u(this, "compile", (r) => {
            r.raw("((").compile(this.nodes.expr1).raw(") ? (").compile(this.nodes.expr2).raw(") : (").compile(this.nodes.expr3).raw("))");
          }), u(this, "evaluate", (r, i) => this.nodes.expr1.evaluate(r, i) ? this.nodes.expr2.evaluate(r, i) : this.nodes.expr3.evaluate(r, i)), u(this, "toArray", () => ["(", this.nodes.expr1, " ? ", this.nodes.expr2, " : ", this.nodes.expr3, ")"]), this.name = "ConditionalNode";
        }
      }
      class be extends A {
        constructor(t, e) {
          super({ fnArguments: e }, { name: t }), u(this, "compile", (s) => {
            let r = [];
            for (let a of Object.values(this.nodes.fnArguments.nodes)) r.push(s.subcompile(a));
            let i = s.getFunction(this.attributes.name);
            s.raw(i.compiler.apply(null, r));
          }), u(this, "evaluate", (s, r) => {
            let i = [r];
            for (let a of Object.values(this.nodes.fnArguments.nodes)) i.push(a.evaluate(s, r));
            return s[this.attributes.name].evaluator.apply(null, i);
          }), u(this, "toArray", () => {
            let s = [];
            s.push(this.attributes.name);
            for (let r of Object.values(this.nodes.fnArguments.nodes)) s.push(", "), s.push(r);
            return s[1] = "(", s.push(")"), s;
          }), this.name = "FunctionNode";
        }
      }
      class xe extends A {
        constructor(t) {
          super({}, { name: t }), u(this, "compile", (e) => {
            e.raw(this.attributes.name);
          }), u(this, "evaluate", (e, s) => s[this.attributes.name]), u(this, "toArray", () => [this.attributes.name]), this.name = "NameNode";
        }
      }
      class ee extends A {
        constructor() {
          super(), u(this, "addElement", (t, e = null) => {
            e === null ? e = new k(++this.index) : this.type === "Array" && (this.type = "Object"), this.nodes[(++this.keyIndex).toString()] = e, this.nodes[(++this.keyIndex).toString()] = t;
          }), u(this, "compile", (t) => {
            this.type === "Object" ? t.raw("{") : t.raw("["), this.compileArguments(t, this.type !== "Array"), this.type === "Object" ? t.raw("}") : t.raw("]");
          }), u(this, "evaluate", (t, e) => {
            let s;
            if (this.type === "Array") {
              s = [];
              for (let r of this.getKeyValuePairs()) s.push(r.value.evaluate(t, e));
            } else {
              s = {};
              for (let r of this.getKeyValuePairs()) s[r.key.evaluate(t, e)] = r.value.evaluate(t, e);
            }
            return s;
          }), u(this, "toArray", () => {
            let t = {};
            for (let s of this.getKeyValuePairs()) t[s.key.attributes.value] = s.value;
            let e = [];
            if (this.isHash(t)) {
              for (let s of Object.keys(t)) e.push(", "), e.push(new k(s)), e.push(": "), e.push(t[s]);
              e[0] = "{", e.push("}");
            } else {
              for (let s of Object.values(t)) e.push(", "), e.push(s);
              e[0] = "[", e.push("]");
            }
            return e;
          }), u(this, "getKeyValuePairs", () => {
            let t, e, s, r = [], i = Object.values(this.nodes);
            for (t = 0, e = i.length; t < e; t += 2) s = i.slice(t, t + 2), r.push({ key: s[0], value: s[1] });
            return r;
          }), u(this, "compileArguments", (t, e = !0) => {
            let s = !0;
            for (let r of this.getKeyValuePairs()) s || t.raw(", "), s = !1, e && t.compile(r.key).raw(": "), t.compile(r.value);
          }), this.name = "ArrayNode", this.type = "Array", this.index = -1, this.keyIndex = -1;
        }
      }
      class ae extends ee {
        constructor() {
          super(), u(this, "compile", (t) => {
            this.compileArguments(t, !1);
          }), u(this, "toArray", () => {
            let t = [];
            for (let e of this.getKeyValuePairs()) t.push(e.value), t.push(", ");
            return t.pop(), t;
          }), this.name = "ArgumentsNode";
        }
      }
      class v extends A {
        constructor(t, e, s, r) {
          super({ node: t, attribute: e, fnArguments: s }, { type: r, is_null_coalesce: !1, is_short_circuited: !1 }), u(this, "compile", (i) => {
            const a = this.nodes.attribute instanceof k && this.nodes.attribute.isNullSafe;
            switch (this.attributes.type) {
              case v.PROPERTY_CALL:
                i.compile(this.nodes.node).raw(a ? "?." : ".").raw(this.nodes.attribute.attributes.value);
                break;
              case v.METHOD_CALL:
                i.compile(this.nodes.node).raw(a ? "?." : ".").raw(this.nodes.attribute.attributes.value).raw("(").compile(this.nodes.fnArguments).raw(")");
                break;
              case v.ARRAY_CALL:
                i.compile(this.nodes.node).raw("[").compile(this.nodes.attribute).raw("]");
            }
          }), u(this, "evaluate", (i, a) => {
            switch (this.attributes.type) {
              case v.PROPERTY_CALL:
                let o = this.nodes.node.evaluate(i, a);
                if (o === null && (this.nodes.attribute.isNullSafe || this.attributes.is_null_coalesce)) return this.attributes.is_short_circuited = !0, null;
                if (o === null && this.isShortCircuited()) return null;
                if (typeof o != "object") throw new Error(`Unable to get property "${l}" on a non-object: ` + typeof o);
                let l = this.nodes.attribute.attributes.value;
                return this.attributes.is_null_coalesce ? o[l] ?? null : o[l];
              case v.METHOD_CALL:
                let d = this.nodes.node.evaluate(i, a);
                if (d === null && this.nodes.attribute.isNullSafe) return this.attributes.is_short_circuited = !0, null;
                if (d === null && this.isShortCircuited()) return null;
                let f = this.nodes.attribute.attributes.value;
                if (typeof d != "object") throw new Error(`Unable to call method "${f}" on a non-object: ` + typeof d);
                if (d[f] === void 0) throw new Error(`Method "${f}" is undefined on object.`);
                if (typeof d[f] != "function") throw new Error(`Method "${f}" is not a function on object.`);
                let g = this.nodes.fnArguments.evaluate(i, a);
                return d[f].apply(null, g);
              case v.ARRAY_CALL:
                let b = this.nodes.node.evaluate(i, a);
                if (b === null && this.isShortCircuited()) return null;
                if (!(Array.isArray(b) || typeof b == "object" || b === null && this.attributes.is_null_coalesce)) throw new Error("Unable to get an item on a non-array: " + typeof b);
                return this.attributes.is_null_coalesce ? b ? b[this.nodes.attribute.evaluate(i, a)] ?? null : null : b[this.nodes.attribute.evaluate(i, a)];
            }
          }), u(this, "toArray", () => {
            const i = this.nodes.attribute instanceof k && this.nodes.attribute.isNullSafe;
            switch (this.attributes.type) {
              case v.PROPERTY_CALL:
                return [this.nodes.node, i ? "?." : ".", this.nodes.attribute];
              case v.METHOD_CALL:
                return [this.nodes.node, i ? "?." : ".", this.nodes.attribute, "(", this.nodes.fnArguments, ")"];
              case v.ARRAY_CALL:
                return [this.nodes.node, "[", this.nodes.attribute, "]"];
            }
          }), this.name = "GetAttrNode";
        }
        isShortCircuited() {
          return this.attributes.is_short_circuited || this.nodes.node instanceof v && this.nodes.node.isShortCircuited();
        }
      }
      u(v, "PROPERTY_CALL", 1), u(v, "METHOD_CALL", 2), u(v, "ARRAY_CALL", 3);
      class ve extends A {
        constructor(t, e) {
          super({ expr1: t, expr2: e }), u(this, "compile", (s) => {
            s.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))");
          }), u(this, "evaluate", (s, r) => (this.nodes.expr1 instanceof v && this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1), this.nodes.expr1.evaluate(s, r) ?? this.nodes.expr2.evaluate(s, r))), u(this, "toArray", () => ["(", this.nodes.expr1, ") ?? (", this.nodes.expr2, ")"]), u(this, "_addNullCoalesceAttributeToGetAttrNodes", (s) => {
            if (!(!s instanceof v)) {
              s.attributes.is_null_coalesce = !0;
              for (let r of Object.values(s.nodes)) this._addNullCoalesceAttributeToGetAttrNodes(r);
            }
          }), this.name = "NullCoalesceNode";
        }
      }
      class Ne extends A {
        constructor(t) {
          super({}, { name: t }), u(this, "compile", (e) => {
            e.raw(this.attributes.name + " ?? null");
          }), u(this, "evaluate", (e, s) => null), u(this, "toArray", () => [this.attributes.name + " ?? null"]), this.name = "NullCoalescedNameNode";
        }
      }
      class ke {
        constructor(t = {}) {
          u(this, "functions", {}), u(this, "unaryOperators", { not: { precedence: 50 }, "!": { precedence: 50 }, "-": { precedence: 500 }, "+": { precedence: 500 }, "~": { precedence: 500 } }), u(this, "binaryOperators", { or: { precedence: 10, associativity: 1 }, "||": { precedence: 10, associativity: 1 }, xor: { precedence: 12, associativity: 1 }, and: { precedence: 15, associativity: 1 }, "&&": { precedence: 15, associativity: 1 }, "|": { precedence: 16, associativity: 1 }, "^": { precedence: 17, associativity: 1 }, "&": { precedence: 18, associativity: 1 }, "==": { precedence: 20, associativity: 1 }, "===": { precedence: 20, associativity: 1 }, "!=": { precedence: 20, associativity: 1 }, "!==": { precedence: 20, associativity: 1 }, "<": { precedence: 20, associativity: 1 }, ">": { precedence: 20, associativity: 1 }, ">=": { precedence: 20, associativity: 1 }, "<=": { precedence: 20, associativity: 1 }, "not in": { precedence: 20, associativity: 1 }, in: { precedence: 20, associativity: 1 }, matches: { precedence: 20, associativity: 1 }, contains: { precedence: 20, associativity: 1 }, "starts with": { precedence: 20, associativity: 1 }, "ends with": { precedence: 20, associativity: 1 }, "..": { precedence: 25, associativity: 1 }, "<<": { precedence: 25, associativity: 1 }, ">>": { precedence: 25, associativity: 1 }, "+": { precedence: 30, associativity: 1 }, "-": { precedence: 30, associativity: 1 }, "~": { precedence: 40, associativity: 1 }, "*": { precedence: 60, associativity: 1 }, "/": { precedence: 60, associativity: 1 }, "%": { precedence: 60, associativity: 1 }, "**": { precedence: 200, associativity: 2 } }), u(this, "parse", (e, s = [], r = 0) => {
            this.tokenStream = e, this.names = s, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = r;
            let i = this.parseExpression();
            if (!this.tokenStream.isEOF()) throw new x(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}"`, this.tokenStream.current.cursor, this.tokenStream.expression);
            return i;
          }), u(this, "lint", (e, s = [], r = 0) => {
            s === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), r |= 1, s = []), this.parse(e, s, r);
          }), u(this, "parseExpression", (e = 0) => {
            let s = this.getPrimary(), r = this.tokenStream.current;
            if (this.nestedExecutions++, this.nestedExecutions > 1e3) throw new Error("Way to many executions on '" + r.toString() + "' of '" + this.tokenStream.toString() + "'");
            for (; r.test(h.OPERATOR_TYPE) && this.binaryOperators[r.value] !== void 0 && this.binaryOperators[r.value] !== null && this.binaryOperators[r.value].precedence >= e; ) {
              let i = this.binaryOperators[r.value];
              this.tokenStream.next();
              let a = this.parseExpression(i.associativity === 1 ? i.precedence + 1 : i.precedence);
              s = new S(r.value, s, a), r = this.tokenStream.current;
            }
            return e === 0 ? this.parseConditionalExpression(s) : s;
          }), u(this, "getPrimary", () => {
            let e = this.tokenStream.current;
            if (e.test(h.OPERATOR_TYPE) && this.unaryOperators[e.value] !== void 0 && this.unaryOperators[e.value] !== null) {
              let s = this.unaryOperators[e.value];
              this.tokenStream.next();
              let r = this.parseExpression(s.precedence);
              return this.parsePostfixExpression(new V(e.value, r));
            }
            if (e.test(h.PUNCTUATION_TYPE, "(")) {
              this.tokenStream.next();
              let s = this.parseExpression();
              return this.tokenStream.expect(h.PUNCTUATION_TYPE, ")", "An opened parenthesis is not properly closed"), this.parsePostfixExpression(s);
            }
            return this.parsePrimaryExpression();
          }), u(this, "hasVariable", (e) => this.getNames().indexOf(e) >= 0), u(this, "getNames", () => {
            if (this.cachedNames !== null) return this.cachedNames;
            if (this.names && this.names.length > 0) {
              let e = [], s = 0;
              this.objectMatches = {};
              for (let r of this.names) typeof r == "object" ? (this.objectMatches[Object.values(r)[0]] = s, e.push(Object.keys(r)[0]), e.push(Object.values(r)[0])) : e.push(r), s++;
              return this.cachedNames = e, e;
            }
            return [];
          }), u(this, "parseArrayExpression", () => {
            this.tokenStream.expect(h.PUNCTUATION_TYPE, "[", "An array element was expected");
            let e = new ee(), s = !0;
            for (; !this.tokenStream.current.test(h.PUNCTUATION_TYPE, "]") && (s || (this.tokenStream.expect(h.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"), !this.tokenStream.current.test(h.PUNCTUATION_TYPE, "]"))); ) s = !1, e.addElement(this.parseExpression());
            return this.tokenStream.expect(h.PUNCTUATION_TYPE, "]", "An opened array is not properly closed"), e;
          }), u(this, "parseHashExpression", () => {
            this.tokenStream.expect(h.PUNCTUATION_TYPE, "{", "A hash element was expected");
            let e = new ee(), s = !0;
            for (; !this.tokenStream.current.test(h.PUNCTUATION_TYPE, "}") && (s || (this.tokenStream.expect(h.PUNCTUATION_TYPE, ",", "A hash value must be followed by a comma"), !this.tokenStream.current.test(h.PUNCTUATION_TYPE, "}"))); ) {
              s = !1;
              let r = null;
              if (this.tokenStream.current.test(h.STRING_TYPE) || this.tokenStream.current.test(h.NAME_TYPE) || this.tokenStream.current.test(h.NUMBER_TYPE)) r = new k(this.tokenStream.current.value), this.tokenStream.next();
              else {
                if (!this.tokenStream.current.test(h.PUNCTUATION_TYPE, "(")) {
                  let a = this.tokenStream.current;
                  throw new x(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${a.type}" of value "${a.value}"`, a.cursor, this.tokenStream.expression);
                }
                r = this.parseExpression();
              }
              this.tokenStream.expect(h.PUNCTUATION_TYPE, ":", "A hash key must be followed by a colon (:)");
              let i = this.parseExpression();
              e.addElement(i, r);
            }
            return this.tokenStream.expect(h.PUNCTUATION_TYPE, "}", "An opened hash is not properly closed"), e;
          }), u(this, "parsePostfixExpression", (e) => {
            let s = this.tokenStream.current;
            for (; h.PUNCTUATION_TYPE === s.type; ) {
              if (s.value === "." || s.value === "?.") {
                const r = s.value === "?.";
                if (this.tokenStream.next(), s = this.tokenStream.current, this.tokenStream.next(), h.NAME_TYPE !== s.type && (h.OPERATOR_TYPE !== s.type || !/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(s.value))) throw new x("Expected name", s.cursor, this.tokenStream.expression);
                let i = new k(s.value, !0, r), a = new ae(), o = null;
                if (this.tokenStream.current.test(h.PUNCTUATION_TYPE, "(")) {
                  o = v.METHOD_CALL;
                  for (let l of Object.values(this.parseArguments().nodes)) a.addElement(l);
                } else o = v.PROPERTY_CALL;
                e = new v(e, i, a, o);
              } else {
                if (s.value !== "[") break;
                {
                  this.tokenStream.next();
                  let r = this.parseExpression();
                  this.tokenStream.expect(h.PUNCTUATION_TYPE, "]"), e = new v(e, r, new ae(), v.ARRAY_CALL);
                }
              }
              s = this.tokenStream.current;
            }
            return e;
          }), u(this, "parseArguments", () => {
            let e = [];
            for (this.tokenStream.expect(h.PUNCTUATION_TYPE, "(", "A list of arguments must begin with an opening parenthesis"); !this.tokenStream.current.test(h.PUNCTUATION_TYPE, ")"); ) e.length !== 0 && this.tokenStream.expect(h.PUNCTUATION_TYPE, ",", "Arguments must be separated by a comma"), e.push(this.parseExpression());
            return this.tokenStream.expect(h.PUNCTUATION_TYPE, ")", "A list of arguments must be closed by a parenthesis"), new A(e);
          }), this.functions = t, this.tokenStream = null, this.names = null, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = 0;
        }
        parseConditionalExpression(t) {
          for (; this.tokenStream.current.test(h.PUNCTUATION_TYPE, "??"); ) {
            this.tokenStream.next();
            let e = this.parseExpression();
            t = new ve(t, e);
          }
          for (; this.tokenStream.current.test(h.PUNCTUATION_TYPE, "?"); ) {
            let e, s;
            this.tokenStream.next(), this.tokenStream.current.test(h.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), e = t, s = this.parseExpression()) : (e = this.parseExpression(), this.tokenStream.current.test(h.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), s = this.parseExpression()) : e instanceof k && typeof e.attributes?.value == "string" ? s = new k("") : e instanceof ie ? (s = e.nodes.expr3, e = e.nodes.expr2) : (s = e, e = t)), t = new ie(t, e, s);
          }
          return t;
        }
        parsePrimaryExpression() {
          let t = this.tokenStream.current, e = null;
          switch (t.type) {
            case h.NAME_TYPE:
              switch (this.tokenStream.next(), t.value) {
                case "true":
                case "TRUE":
                  return new k(!0);
                case "false":
                case "FALSE":
                  return new k(!1);
                case "null":
                case "NULL":
                  return new k(null);
                default:
                  if (this.tokenStream.current.value === "(") {
                    if (this.functions[t.value] === void 0 && !(2 & this.flags)) throw new x(`The function "${t.value}" does not exist`, t.cursor, this.tokenStream.expression, t.values, Object.keys(this.functions));
                    e = new be(t.value, this.parseArguments());
                  } else {
                    let s = null;
                    if (1 & this.flags) s = t.value;
                    else {
                      if (!this.hasVariable(t.value)) {
                        if (this.tokenStream.current.test(h.PUNCTUATION_TYPE, "??")) return new Ne(t.value);
                        throw new x(`Variable "${t.value}" is not valid`, t.cursor, this.tokenStream.expression, t.value, this.getNames());
                      }
                      s = t.value, this.objectMatches[s] !== void 0 && (s = this.getNames()[this.objectMatches[s]]);
                    }
                    e = new xe(s);
                  }
              }
              break;
            case h.NUMBER_TYPE:
            case h.STRING_TYPE:
              return this.tokenStream.next(), new k(t.value);
            default:
              if (t.test(h.PUNCTUATION_TYPE, "[")) e = this.parseArrayExpression();
              else {
                if (!t.test(h.PUNCTUATION_TYPE, "{")) throw new x(`Unexpected token "${t.type}" of value "${t.value}"`, t.cursor, this.tokenStream.expression);
                e = this.parseHashExpression();
              }
          }
          return this.parsePostfixExpression(e);
        }
      }
      class Te {
        constructor(t) {
          u(this, "getFunction", (e) => this.functions[e]), u(this, "getSource", () => this.source), u(this, "reset", () => (this.source = "", this)), u(this, "compile", (e) => (e.compile(this), this)), u(this, "subcompile", (e) => {
            let s = this.source;
            this.source = "", e.compile(this);
            let r = this.source;
            return this.source = s, r;
          }), u(this, "raw", (e) => (this.source += e, this)), u(this, "string", (e) => (this.source += '"' + we(e, '\0	"$\\') + '"', this)), u(this, "repr", (e, s = !1) => {
            if (s) this.raw(e);
            else if (Number.isInteger(e) || +e === e && (!isFinite(e) || e % 1)) this.raw(e);
            else if (e === null) this.raw("null");
            else if (typeof e == "boolean") this.raw(e ? "true" : "false");
            else if (typeof e == "object") {
              this.raw("{");
              let r = !0;
              for (let i of Object.keys(e)) r || this.raw(", "), r = !1, this.repr(i), this.raw(":"), this.repr(e[i]);
              this.raw("}");
            } else if (Array.isArray(e)) {
              this.raw("[");
              let r = !0;
              for (let i of e) r || this.raw(", "), r = !1, this.repr(i);
              this.raw("]");
            } else this.string(e);
            return this;
          }), this.source = "", this.functions = t;
        }
      }
      class ot {
        constructor(t) {
          this.expression = t;
        }
        toString() {
          return this.expression;
        }
      }
      class K extends ot {
        constructor(t, e) {
          super(t), u(this, "getNodes", () => this.nodes), this.nodes = e;
        }
        static fromJSON(t) {
          const e = typeof t == "string" ? JSON.parse(t) : t, s = (a) => {
            if (a == null || a instanceof A || typeof a != "object" || !a.name) return a;
            switch (a.name) {
              case "ConstantNode":
                return new k(a.attributes?.value, !!a.isIdentifier, !!a.isNullSafe);
              case "NameNode":
                return new xe(a.attributes?.name);
              case "NullCoalescedNameNode":
                return new Ne(a.attributes?.name);
              case "UnaryNode":
                return new V(a.attributes?.operator, s(a.nodes?.node));
              case "BinaryNode":
                return new S(a.attributes?.operator, s(a.nodes?.left), s(a.nodes?.right));
              case "ConditionalNode":
                return new ie(s(a.nodes?.expr1), s(a.nodes?.expr2), s(a.nodes?.expr3));
              case "NullCoalesceNode":
                return new ve(s(a.nodes?.expr1), s(a.nodes?.expr2));
              case "ArgumentsNode": {
                const o = new ae();
                typeof a.type == "string" && (o.type = a.type), typeof a.index == "number" && (o.index = a.index), typeof a.keyIndex == "number" && (o.keyIndex = a.keyIndex), o.nodes = {};
                for (const l of Object.keys(a.nodes || {})) o.nodes[l] = s(a.nodes[l]);
                return o;
              }
              case "ArrayNode": {
                const o = new ee();
                typeof a.type == "string" && (o.type = a.type), typeof a.index == "number" && (o.index = a.index), typeof a.keyIndex == "number" && (o.keyIndex = a.keyIndex), o.nodes = {};
                for (const l of Object.keys(a.nodes || {})) o.nodes[l] = s(a.nodes[l]);
                return o;
              }
              case "FunctionNode": {
                const o = s(a.nodes?.arguments);
                return new be(a.attributes?.name, o);
              }
              case "GetAttrNode": {
                const o = new v(s(a.nodes?.node), s(a.nodes?.attribute), s(a.nodes?.fnArguments), a.attributes?.type);
                return a.attributes && typeof a.attributes.is_null_coalesce == "boolean" && (o.attributes.is_null_coalesce = a.attributes.is_null_coalesce), a.attributes && typeof a.attributes.is_short_circuited == "boolean" && (o.attributes.is_short_circuited = a.attributes.is_short_circuited), o;
              }
              case "Node": {
                const o = new A();
                if (Array.isArray(a.nodes)) o.nodes = a.nodes.map(s);
                else {
                  o.nodes = {};
                  for (const l of Object.keys(a.nodes || {})) o.nodes[l] = s(a.nodes[l]);
                }
                return o.attributes = a.attributes || {}, o;
              }
              default: {
                const o = new A();
                if (o.name = a.name, Array.isArray(a.nodes)) o.nodes = a.nodes.map(s);
                else {
                  o.nodes = {};
                  for (const l of Object.keys(a.nodes || {})) o.nodes[l] = s(a.nodes[l]);
                }
                return o.attributes = a.attributes || {}, o;
              }
            }
          }, r = e.expression, i = ((a) => {
            if (a == null) return a;
            if (a.name) return s(a);
            if (Array.isArray(a)) return a.map(s);
            if (typeof a == "object") {
              const o = {};
              for (const l of Object.keys(a)) o[l] = s(a[l]);
              return o;
            }
            return a;
          })(e.nodes);
          return new K(r, i);
        }
      }
      var Ee;
      class Ae {
        constructor(t = 0) {
          u(this, "createCacheItem", (e, s, r) => {
            let i = new R();
            return i.key = e, i.value = s, i.isHit = r, i.defaultLifetime = this.defaultLifetime, i;
          }), u(this, "get", (e, s, r = null, i = null) => {
            let a = this.getItem(e);
            return a.isHit || this.save(a.set(s(a, !0))), a.get();
          }), u(this, "getItem", (e) => {
            let s = this.hasItem(e), r = null;
            return s ? r = this.values[e] : this.values[e] = null, (0, this.createCacheItem)(e, r, s);
          }), u(this, "getItems", (e) => {
            for (let s of e) typeof s == "string" || this.expiries[s] || R.validateKey(s);
            return this.generateItems(e, (/* @__PURE__ */ new Date()).getTime() / 1e3, this.createCacheItem);
          }), u(this, "deleteItems", (e) => {
            for (let s of e) this.deleteItem(s);
            return !0;
          }), u(this, "save", (e) => !(!e instanceof R) && (e.expiry !== null && e.expiry <= (/* @__PURE__ */ new Date()).getTime() / 1e3 ? (this.deleteItem(e.key), !0) : (e.expiry === null && 0 < e.defaultLifetime && (e.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e.defaultLifetime), this.values[e.key] = e.value, this.expiries[e.key] = e.expiry || Number.MAX_SAFE_INTEGER, !0))), u(this, "saveDeferred", (e) => this.save(e)), u(this, "commit", () => !0), u(this, "delete", (e) => this.deleteItem(e)), u(this, "getValues", () => this.values), u(this, "hasItem", (e) => !!(typeof e == "string" && this.expiries[e] && this.expiries[e] > (/* @__PURE__ */ new Date()).getTime() / 1e3) || (R.validateKey(e), !!this.expiries[e] && !this.deleteItem(e))), u(this, "clear", () => (this.values = {}, this.expiries = {}, !0)), u(this, "deleteItem", (e) => (typeof e == "string" && this.expiries[e] || R.validateKey(e), delete this.values[e], delete this.expiries[e], !0)), u(this, "reset", () => {
            this.clear();
          }), u(this, "generateItems", (e, s, r) => {
            let i = [];
            for (let a of e) {
              let o = null, l = !!this.expiries[a];
              l || !(this.expiries[a] > s) && this.deleteItem(a) ? o = this.values[a] : this.values[a] = null, i[a] = r(a, o, l);
            }
            return i;
          }), this.defaultLifetime = t, this.values = {}, this.expiries = {};
        }
      }
      class R {
        constructor() {
          u(this, "getKey", () => this.key), u(this, "get", () => this.value), u(this, "set", (t) => (this.value = t, this)), u(this, "expiresAt", (t) => {
            if (t === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
            else {
              if (!(t instanceof Date)) throw new Error(`Expiration date must be instance of Date or be null, "${t.name}" given`);
              this.expiry = t.getTime() / 1e3;
            }
            return this;
          }), u(this, "expiresAfter", (t) => {
            if (t === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
            else {
              if (!Number.isInteger(t)) throw new Error(`Expiration date must be an integer or be null, "${t.name}" given`);
              this.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + t;
            }
            return this;
          }), u(this, "tag", (t) => {
            if (!this.isTaggable) throw new Error(`Cache item "${this.key}" comes from a non tag-aware pool: you cannot tag it.`);
            Array.isArray(t) || (t = [t]);
            for (let e of t) {
              if (typeof e != "string") throw new Error(`Cache tag must by a string, "${typeof e}" given.`);
              if (this.newMetadata.tags[e] && e === "") throw new Error("Cache tag length must be greater than zero");
              this.newMetadata.tags[e] = e;
            }
            return this;
          }), u(this, "getMetadata", () => this.metadata), this.key = null, this.value = null, this.isHit = !1, this.expiry = null, this.defaultLifetime = null, this.metadata = {}, this.newMetadata = {}, this.innerItem = null, this.poolHash = null, this.isTaggable = !1;
        }
      }
      Ee = R, u(R, "METADATA_EXPIRY_OFFSET", 1527506807), u(R, "RESERVED_CHARACTERS", ["{", "}", "(", ")", "/", "\\", "@", ":"]), u(R, "validateKey", (n) => {
        if (typeof n != "string") throw new Error(`Cache key must be string, "${typeof n}" given.`);
        if (n === "") throw new Error("Cache key length must be greater than zero");
        for (let t of Ee.RESERVED_CHARACTERS) if (n.indexOf(t) >= 0) throw new Error(`Cache key "${n}" contains reserved character "${t}".`);
        return n;
      });
      class ut extends Error {
        constructor(t) {
          super(t), this.name = "LogicException";
        }
        toString() {
          return `${this.name}: ${this.message}`;
        }
      }
      class N {
        constructor(t, e, s) {
          u(this, "getName", () => this.name), u(this, "getCompiler", () => this.compiler), u(this, "getEvaluator", () => this.evaluator), this.name = t, this.compiler = e, this.evaluator = s;
        }
        static fromJavascript(t, e = null) {
          if (typeof t != "string" || t.length === 0) throw new TypeError("A JavaScript function name (string) must be provided.");
          const s = t.replace(/^\/+/, ""), r = s.split(".");
          let i = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof L < "u" ? L : {};
          for (const a of r) {
            if (i == null) break;
            i = i[a];
          }
          if (typeof i != "function") throw new Error(`JavaScript function "${s}" does not exist.`);
          if (!e && r.length > 1) throw new Error(`An expression function name must be defined when JavaScript function "${s}" is namespaced.`);
          return new this(e || r[r.length - 1], (...a) => `${s}(${a.join(", ")})`, (a, ...o) => i(...o));
        }
      }
      class Oe {
        constructor(t = null, e = []) {
          u(this, "compile", (s, r = []) => this.getCompiler().compile(this.parse(s, r).getNodes()).getSource()), u(this, "evaluate", (s, r = {}) => this.parse(s, Object.keys(r)).getNodes().evaluate(this.functions, r)), u(this, "parse", (s, r, i = 0) => {
            if (s instanceof K) return s;
            r.sort((d, f) => {
              let g = d, b = f;
              return typeof d == "object" && (g = Object.values(d)[0]), typeof f == "object" && (b = Object.values(f)[0]), g.localeCompare(b);
            });
            let a = [];
            for (let d of r) {
              let f = d;
              typeof d == "object" && (f = Object.keys(d)[0] + ":" + Object.values(d)[0]), a.push(f);
            }
            let o = this.cache.getItem(this.fixedEncodeURIComponent(s + "//" + a.join("|"))), l = o.get();
            if (l === null) {
              let d = this.getParser().parse(this.getLexer().tokenize(s), r, i);
              l = new K(s, d), o.set(l), this.cache.save(o);
            }
            return l;
          }), u(this, "lint", (s, r = null, i = 0) => {
            r === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), i |= 1, r = []), s instanceof K || this.getParser().lint(this.getLexer().tokenize(s), r, i);
          }), u(this, "fixedEncodeURIComponent", (s) => encodeURIComponent(s).replace(/[!'()*]/g, function(r) {
            return "%" + r.charCodeAt(0).toString(16);
          })), u(this, "register", (s, r, i) => {
            if (this.parser !== null) throw new ut("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
            this.functions[s] = { compiler: r, evaluator: i };
          }), u(this, "addFunction", (s) => {
            this.register(s.getName(), s.getCompiler(), s.getEvaluator());
          }), u(this, "registerProvider", (s) => {
            for (let r of s.getFunctions()) this.addFunction(r);
          }), u(this, "getLexer", () => (this.lexer === null && (this.lexer = { tokenize: pe }), this.lexer)), u(this, "getParser", () => (this.parser === null && (this.parser = new ke(this.functions)), this.parser)), u(this, "getCompiler", () => (this.compiler === null && (this.compiler = new Te(this.functions)), this.compiler.reset())), this.functions = [], this.lexer = null, this.parser = null, this.compiler = null, this.cache = t || new Ae(), this._registerBuiltinFunctions();
          for (let s of e) this.registerProvider(s);
        }
        _registerBuiltinFunctions() {
          const t = N.fromJavascript("Math.min", "min"), e = N.fromJavascript("Math.max", "max");
          this.addFunction(t), this.addFunction(e), this.addFunction(new N("constant", function(s) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${s})`;
          }, function(s, r) {
            if (typeof r != "string" || !r) return;
            let i = (a = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof L < "u" ? L : {}, r.split(".").reduce((o, l) => o?.[l], a));
            var a;
            return i === void 0 && s && Object.prototype.hasOwnProperty.call(s, r) && (i = s[r]), i;
          })), this.addFunction(new N("enum", function(s) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${s})`;
          }, function(s, r) {
            if (typeof r != "string" || !r) return;
            const i = String(r).replace(/\\/g, ".").replace(/::/g, ".");
            var a;
            return i ? (a = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof L < "u" ? L : {}, i.split(".").reduce((o, l) => o?.[l], a)) : void 0;
          }));
        }
      }
      class J {
        getFunctions() {
          throw new Error("getFunctions must be implemented by " + this.name);
        }
      }
      const lt = new N("isset", function(n) {
        return `isset(${n})`;
      }, function(n, t) {
        if (typeof t != "string") return t != null;
        if (!(t.split(/[.\[]/)[0] in n)) return !0;
        let e = "", s = [], r = "", i = "";
        for (let a = 0; a < t.length; a++) {
          let o = t[a];
          if (o !== "]") if (o !== "[") {
            if (r === "object" && (!/[A-z0-9_]/.test(o) || a === t.length - 1)) {
              let l = !1;
              if (a === t.length - 1 && (i += o, l = !0), r = "", s.push({ type: "object", attribute: i }), i = "", l) continue;
            }
            o !== "." ? r ? i += o : e += o : (r = "object", i = "");
          } else r = "array", i = "";
          else r = "", s.push({ type: "array", index: i.replace(/"/g, "").replace(/'/g, "") }), i = "";
        }
        if (s.length > 0) {
          if (n[e] !== void 0) {
            let a = n[e];
            for (let o of s) {
              if (o.type === "array") {
                if (a[o.index] === void 0) return !1;
                a = a[o.index];
              }
              if (o.type === "object") {
                if (a[o.attribute] === void 0) return !1;
                a = a[o.attribute];
              }
            }
            return !0;
          }
          return !1;
        }
        return n[e] !== void 0;
      }), Se = (n) => Object.entries(n);
      function Ce(n) {
        return typeof n == "object" && n !== null;
      }
      function oe(n) {
        return Ce(n) && !(function(t) {
          return Array.isArray(t);
        })(n);
      }
      function _e(n) {
        return (function(t) {
          return Ce(t);
        })(n) ? n : {};
      }
      const Pe = typeof window == "object" && window !== null ? window : typeof L == "object" && L !== null ? L : {};
      function ht() {
        const n = (() => {
          let f = Pe.$locutus;
          typeof f == "object" && f !== null || (f = {}, Pe.$locutus = f);
          let g = f.php;
          return typeof g == "object" && g !== null || (g = {}, f.php = g), g;
        })(), t = n.ini, e = n.locales, s = n.localeCategories, r = n.pointers, i = oe(t) ? t : {}, a = ((f) => oe(f))(e) ? e : {}, o = ((f) => oe(f))(s) ? s : {}, l = Array.isArray(r) ? r : [];
        t !== i && (n.ini = i), e !== a && (n.locales = a), s !== o && (n.localeCategories = o), r !== l && (n.pointers = l);
        const d = n.locale_default;
        return { ini: i, locales: a, localeCategories: o, pointers: l, locale_default: typeof d == "string" ? d : void 0 };
      }
      function Re(n) {
        const t = ht().ini[n];
        return t && t.local_value !== void 0 ? t.local_value === null ? "" : String(t.local_value) : "";
      }
      function ct(n, t, e) {
        const s = (function(o) {
          if (typeof o == "boolean") return o ? "1" : "";
          if (typeof o == "string") return o;
          if (typeof o == "number") return isNaN(o) ? "NAN" : isFinite(o) ? o + "" : (o < 0 ? "-" : "") + "INF";
          if (o === void 0) return "";
          if (typeof o == "object") return Array.isArray(o) ? "Array" : o !== null ? "Object" : "";
          throw new Error("Unsupported value type");
        })(n), r = Re("unicode.semantics") === "on" ? s.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [] : null, i = r ? r.length : s.length;
        let a = i;
        return t < 0 && (t += a), e !== void 0 && (a = e < 0 ? e + a : e + t), !(t > i || t < 0 || t > a) && (r ? r.slice(t, a).join("") : s.slice(t, a));
      }
      function dt(n, ...t) {
        const e = {};
        if (t.length < 1) return e;
        const s = _e(n);
        e: for (const [r, i] of Se(s)) {
          for (const a of t) {
            const o = _e(a);
            let l = !1;
            for (const [, d] of Se(o)) if (d === i) {
              l = !0;
              break;
            }
            if (!l) continue e;
          }
          e[r] = i;
        }
        return e;
      }
      const je = (n) => {
        if (!n || typeof n != "object") return !1;
        const t = Object.getPrototypeOf(n);
        return t === Array.prototype || t === Object.prototype;
      };
      function ue(n, t = 0) {
        let e = 0;
        if (n == null) return 0;
        if (typeof n != "object") return 1;
        const s = Object.getPrototypeOf(n);
        if (s !== Array.prototype && s !== Object.prototype) return 1;
        const r = t === "COUNT_RECURSIVE" || t === 1;
        if (Array.isArray(n)) {
          for (const i of Object.keys(n)) {
            e++;
            const a = n[Number(i)];
            r && je(a) && (e += ue(a, 1));
          }
          return e;
        }
        for (const i in n) if (Object.prototype.hasOwnProperty.call(n, i)) {
          e++;
          const a = n[i];
          r && je(a) && (e += ue(a, 1));
        }
        return e;
      }
      const ft = new N("implode", function(n, t) {
        return `implode(${n}, ${t})`;
      }, function(n, t, e) {
        return (function(...s) {
          let r, i = "", a = "", o = "";
          if (s.length === 1) {
            const [l] = s;
            r = l;
          } else {
            const [l, d] = s;
            o = String(l ?? ""), r = d;
          }
          if (typeof r == "object" && r !== null) {
            if (Array.isArray(r)) return r.join(o);
            for (const l in r) i += a + r[l], a = o;
            return i;
          }
          return String(r);
        })(t, e);
      }), pt = new N("count", function(n, t) {
        let e = "";
        return t && (e = `, ${t}`), `count(${n}${e})`;
      }, function(n, t, e) {
        return ue(t, e);
      }), mt = new N("array_intersect", function(n, ...t) {
        let e = "";
        return t.length > 0 && (e = ", " + t.join(", ")), `array_intersect(${n}${e})`;
      }, function(n) {
        let t = [], e = !0;
        for (let r = 1; r < arguments.length; r++) t.push(arguments[r]), Array.isArray(arguments[r]) || (e = !1);
        let s = dt.apply(null, t);
        return e ? Object.values(s) : s;
      });
      function gt(n, t) {
        let e, s = /* @__PURE__ */ new Date();
        const r = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"], i = /\\?(.?)/gi, a = function(f, g) {
          return b = f, Object.prototype.hasOwnProperty.call(e, b) ? String(e[f]()) : g;
          var b;
        }, o = function(f, g) {
          let b = String(f);
          for (; b.length < g; ) b = "0" + b;
          return b;
        };
        return e = { d: function() {
          return o(e.j(), 2);
        }, D: function() {
          return String(e.l()).slice(0, 3);
        }, j: function() {
          return s.getDate();
        }, l: function() {
          return (r[Number(e.w())] ?? "") + "day";
        }, N: function() {
          return Number(e.w()) || 7;
        }, S: function() {
          const f = Number(e.j());
          let g = f % 10;
          return g <= 3 && Number.parseInt(String(f % 100 / 10), 10) === 1 && (g = 0), ["st", "nd", "rd"][g - 1] || "th";
        }, w: function() {
          return s.getDay();
        }, z: function() {
          const f = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j())), g = new Date(Number(e.Y()), 0, 1);
          return Math.round((f.getTime() - g.getTime()) / 864e5);
        }, W: function() {
          const f = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j()) - Number(e.N()) + 3), g = new Date(f.getFullYear(), 0, 4);
          return o(1 + Math.round((f.getTime() - g.getTime()) / 864e5 / 7), 2);
        }, F: function() {
          return r[6 + Number(e.n())] ?? "";
        }, m: function() {
          return o(e.n(), 2);
        }, M: function() {
          return String(e.F()).slice(0, 3);
        }, n: function() {
          return s.getMonth() + 1;
        }, t: function() {
          return new Date(Number(e.Y()), Number(e.n()), 0).getDate();
        }, L: function() {
          const f = Number(e.Y());
          return f % 4 == 0 && f % 100 != 0 || f % 400 == 0 ? 1 : 0;
        }, o: function() {
          const f = Number(e.n()), g = Number(e.W());
          return Number(e.Y()) + (f === 12 && g < 9 ? 1 : f === 1 && g > 9 ? -1 : 0);
        }, Y: function() {
          return s.getFullYear();
        }, y: function() {
          return String(e.Y()).slice(-2);
        }, a: function() {
          return s.getHours() > 11 ? "pm" : "am";
        }, A: function() {
          return String(e.a()).toUpperCase();
        }, B: function() {
          const f = 3600 * s.getUTCHours(), g = 60 * s.getUTCMinutes(), b = s.getUTCSeconds();
          return o(Math.floor((f + g + b + 3600) / 86.4) % 1e3, 3);
        }, g: function() {
          return Number(e.G()) % 12 || 12;
        }, G: function() {
          return s.getHours();
        }, h: function() {
          return o(e.g(), 2);
        }, H: function() {
          return o(e.G(), 2);
        }, i: function() {
          return o(s.getMinutes(), 2);
        }, s: function() {
          return o(s.getSeconds(), 2);
        }, u: function() {
          return o(1e3 * s.getMilliseconds(), 6);
        }, e: function() {
          throw new Error("Not supported (see source code of date() for timezone on how to add support)");
        }, I: function() {
          const f = new Date(Number(e.Y()), 0), g = Date.UTC(Number(e.Y()), 0), b = new Date(Number(e.Y()), 6), $ = Date.UTC(Number(e.Y()), 6);
          return f.getTime() - g !== b.getTime() - $ ? 1 : 0;
        }, O: function() {
          const f = s.getTimezoneOffset(), g = Math.abs(f);
          return (f > 0 ? "-" : "+") + o(100 * Math.floor(g / 60) + g % 60, 4);
        }, P: function() {
          const f = String(e.O());
          return f.slice(0, 3) + ":" + f.slice(3, 5);
        }, T: function() {
          return "UTC";
        }, Z: function() {
          return 60 * -s.getTimezoneOffset();
        }, c: function() {
          return "Y-m-d\\TH:i:sP".replace(i, a);
        }, r: function() {
          return "D, d M Y H:i:s O".replace(i, a);
        }, U: function() {
          return s.getTime() / 1e3 | 0;
        } }, l = n, s = (d = t) === void 0 ? /* @__PURE__ */ new Date() : d instanceof Date ? new Date(d) : new Date(1e3 * Number(d)), l.replace(i, a);
        var l, d;
      }
      const le = "[ \\t]+", M = "[ \\t]*", F = "(?:([ap])\\.?m\\.?([\\t ]|$))", _ = "(2[0-4]|[01]?[0-9])", q = "([01][0-9]|2[0-4])", B = "(0?[1-9]|1[0-2])", j = "([0-5]?[0-9])", P = "([0-5][0-9])", te = "(60|[0-5]?[0-9])", U = "(60|[0-5][0-9])", Ue = "(?:\\.([0-9]+))", Ie = "sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?", Le = "next|last|previous|this", Ye = "(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|" + Ie, Z = "([0-9]{1,4})", E = "([0-9]{4})", Y = "(1[0-2]|0?[0-9])", z = "(0[0-9]|1[0-2])", C = "(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)", I = "(0[0-9]|[1-2][0-9]|3[01])", $e = "january|february|march|april|may|june|july|august|september|october|november|december", X = "jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec", G = "(" + $e + "|" + X + "|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", he = "((?:GMT)?([+-])" + _ + ":?" + j + "?)", Q = G + "[ .\\t-]*" + C + "[,.stndrh\\t ]*";
      function W(n, t) {
        switch (t?.toLowerCase()) {
          case "a":
            n += n === 12 ? -12 : 0;
            break;
          case "p":
            n += n !== 12 ? 12 : 0;
        }
        return n;
      }
      function H(n) {
        let t = +n;
        return n.length < 4 && t < 100 && (t += t < 70 ? 2e3 : 1900), t;
      }
      function O(n) {
        return { jan: 0, january: 0, i: 0, feb: 1, february: 1, ii: 1, mar: 2, march: 2, iii: 2, apr: 3, april: 3, iv: 3, may: 4, v: 4, jun: 5, june: 5, vi: 5, jul: 6, july: 6, vii: 6, aug: 7, august: 7, viii: 7, sep: 8, sept: 8, september: 8, ix: 8, oct: 9, october: 9, x: 9, nov: 10, november: 10, xi: 10, dec: 11, december: 11, xii: 11 }[n.toLowerCase()] ?? Number.NaN;
      }
      function ce(n, t = 0) {
        return { mon: 1, monday: 1, tue: 2, tuesday: 2, wed: 3, wednesday: 3, thu: 4, thursday: 4, fri: 5, friday: 5, sat: 6, saturday: 6, sun: 0, sunday: 0 }[n.toLowerCase()] || t;
      }
      function de(n, t = Number.NaN) {
        const e = n?.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);
        if (!e) return t;
        const s = e[1] === "-" ? -1 : 1;
        let r = +(e[2] ?? 0), i = +(e[4] ?? 0);
        return e[4] || e[3] || (i = Math.floor(r % 100), r = Math.floor(r / 100)), s * (60 * r + i) * 60;
      }
      const yt = { acdt: 37800, acst: 34200, addt: -7200, adt: -10800, aedt: 39600, aest: 36e3, ahdt: -32400, ahst: -36e3, akdt: -28800, akst: -32400, amt: -13840, apt: -10800, ast: -14400, awdt: 32400, awst: 28800, awt: -10800, bdst: 7200, bdt: -36e3, bmt: -14309, bst: 3600, cast: 34200, cat: 7200, cddt: -14400, cdt: -18e3, cemt: 10800, cest: 7200, cet: 3600, cmt: -15408, cpt: -18e3, cst: -21600, cwt: -18e3, chst: 36e3, dmt: -1521, eat: 10800, eddt: -10800, edt: -14400, eest: 10800, eet: 7200, emt: -26248, ept: -14400, est: -18e3, ewt: -14400, ffmt: -14660, fmt: -4056, gdt: 39600, gmt: 0, gst: 36e3, hdt: -34200, hkst: 32400, hkt: 28800, hmt: -19776, hpt: -34200, hst: -36e3, hwt: -34200, iddt: 14400, idt: 10800, imt: 25025, ist: 7200, jdt: 36e3, jmt: 8440, jst: 32400, kdt: 36e3, kmt: 5736, kst: 30600, lst: 9394, mddt: -18e3, mdst: 16279, mdt: -21600, mest: 7200, met: 3600, mmt: 9017, mpt: -21600, msd: 14400, msk: 10800, mst: -25200, mwt: -21600, nddt: -5400, ndt: -9052, npt: -9e3, nst: -12600, nwt: -9e3, nzdt: 46800, nzmt: 41400, nzst: 43200, pddt: -21600, pdt: -25200, pkst: 21600, pkt: 18e3, plmt: 25590, pmt: -13236, ppmt: -17340, ppt: -25200, pst: -28800, pwt: -25200, qmt: -18840, rmt: 5794, sast: 7200, sdmt: -16800, sjmt: -20173, smt: -13884, sst: -39600, tbmt: 10751, tmt: 12344, uct: 0, utc: 0, wast: 7200, wat: 3600, wemt: 7200, west: 3600, wet: 0, wib: 25200, wita: 28800, wit: 32400, wmt: 5040, yddt: -25200, ydt: -28800, ypt: -28800, yst: -32400, ywt: -28800, a: 3600, b: 7200, c: 10800, d: 14400, e: 18e3, f: 21600, g: 25200, h: 28800, i: 32400, k: 36e3, l: 39600, m: 43200, n: -3600, o: -7200, p: -10800, q: -14400, r: -18e3, s: -21600, t: -25200, u: -28800, v: -32400, w: -36e3, x: -39600, y: -43200, z: 0 }, m = { yesterday: { regex: /^yesterday/i, name: "yesterday", callback() {
        return this.rd -= 1, this.resetTime();
      } }, now: { regex: /^now/i, name: "now" }, noon: { regex: /^noon/i, name: "noon", callback() {
        return this.resetTime() && this.time(12, 0, 0, 0);
      } }, midnightOrToday: { regex: /^(midnight|today)/i, name: "midnight | today", callback() {
        return this.resetTime();
      } }, tomorrow: { regex: /^tomorrow/i, name: "tomorrow", callback() {
        return this.rd += 1, this.resetTime();
      } }, timestamp: { regex: /^@(-?\d+)/i, name: "timestamp", callback(n, t) {
        return this.rs += +t, this.y = 1970, this.m = 0, this.d = 1, this.dates = 0, this.resetTime() && this.zone(0);
      } }, firstOrLastDay: { regex: /^(first|last) day of/i, name: "firstdayof | lastdayof", callback(n, t) {
        t.toLowerCase() === "first" ? this.firstOrLastDayOfMonth = 1 : this.firstOrLastDayOfMonth = -1;
      } }, backOrFrontOf: { regex: new RegExp("^(back|front) of " + _ + M + F + "?", "i"), name: "backof | frontof", callback(n, t, e, s) {
        let r = +e, i = 15;
        return t.toLowerCase() === "back" || (r -= 1, i = 45), r = W(r, s), this.resetTime() && this.time(r, i, 0, 0);
      } }, mssqltime: { regex: new RegExp("^" + B + ":" + P + ":" + U + "[:.]([0-9]+)" + F, "i"), name: "mssqltime", callback(n, t, e, s, r, i) {
        return this.time(W(+t, i), +e, +s, +r.substr(0, 3));
      } }, oracledate: { regex: /^(\d{2})-([A-Z]{3})-(\d{2})$/i, name: "d-M-y", callback(n, t, e, s) {
        const r = { JAN: 0, FEB: 1, MAR: 2, APR: 3, MAY: 4, JUN: 5, JUL: 6, AUG: 7, SEP: 8, OCT: 9, NOV: 10, DEC: 11 }[e.toUpperCase()] ?? Number.NaN;
        return this.ymd(2e3 + parseInt(s, 10), r, parseInt(t, 10));
      } }, timeLong12: { regex: new RegExp("^" + B + "[:.]" + j + "[:.]" + U + M + F, "i"), name: "timelong12", callback(n, t, e, s, r) {
        return this.time(W(+t, r), +e, +s, 0);
      } }, timeShort12: { regex: new RegExp("^" + B + "[:.]" + P + M + F, "i"), name: "timeshort12", callback(n, t, e, s) {
        return this.time(W(+t, s), +e, 0, 0);
      } }, timeTiny12: { regex: new RegExp("^" + B + M + F, "i"), name: "timetiny12", callback(n, t, e) {
        return this.time(W(+t, e), 0, 0, 0);
      } }, soap: { regex: new RegExp("^" + E + "-" + z + "-" + I + "T" + q + ":" + P + ":" + U + Ue + he + "?", "i"), name: "soap", callback(n, t, e, s, r, i, a, o, l) {
        return this.ymd(+t, +e - 1, +s) && this.time(+r, +i, +a, +o.substr(0, 3)) && this.zone(de(l));
      } }, wddx: { regex: new RegExp("^" + E + "-" + Y + "-" + C + "T" + _ + ":" + j + ":" + te), name: "wddx", callback(n, t, e, s, r, i, a) {
        return this.ymd(+t, +e - 1, +s) && this.time(+r, +i, +a, 0);
      } }, exif: { regex: new RegExp("^" + E + ":" + z + ":" + I + " " + q + ":" + P + ":" + U, "i"), name: "exif", callback(n, t, e, s, r, i, a) {
        return this.ymd(+t, +e - 1, +s) && this.time(+r, +i, +a, 0);
      } }, xmlRpc: { regex: new RegExp("^" + E + z + I + "T" + _ + ":" + P + ":" + U), name: "xmlrpc", callback(n, t, e, s, r, i, a) {
        return this.ymd(+t, +e - 1, +s) && this.time(+r, +i, +a, 0);
      } }, xmlRpcNoColon: { regex: new RegExp("^" + E + z + I + "[Tt]" + _ + P + U), name: "xmlrpcnocolon", callback(n, t, e, s, r, i, a) {
        return this.ymd(+t, +e - 1, +s) && this.time(+r, +i, +a, 0);
      } }, clf: { regex: new RegExp("^" + C + "/(" + X + ")/" + E + ":" + q + ":" + P + ":" + U + le + he, "i"), name: "clf", callback(n, t, e, s, r, i, a, o) {
        return this.ymd(+s, O(e), +t) && this.time(+r, +i, +a, 0) && this.zone(de(o));
      } }, iso8601long: { regex: new RegExp("^t?" + _ + "[:.]" + j + "[:.]" + te + Ue, "i"), name: "iso8601long", callback(n, t, e, s, r) {
        return this.time(+t, +e, +s, +r.substr(0, 3));
      } }, dateTextual: { regex: new RegExp("^" + G + "[ .\\t-]*" + C + "[,.stndrh\\t ]+" + Z, "i"), name: "datetextual", callback(n, t, e, s) {
        return this.ymd(H(s), O(t), +e);
      } }, pointedDate4: { regex: new RegExp("^" + C + "[.\\t-]" + Y + "[.-]" + E), name: "pointeddate4", callback(n, t, e, s) {
        return this.ymd(+s, +e - 1, +t);
      } }, pointedDate2: { regex: new RegExp("^" + C + "[.\\t]" + Y + "\\.([0-9]{2})"), name: "pointeddate2", callback(n, t, e, s) {
        return this.ymd(H(s), +e - 1, +t);
      } }, timeLong24: { regex: new RegExp("^t?" + _ + "[:.]" + j + "[:.]" + te), name: "timelong24", callback(n, t, e, s) {
        return this.time(+t, +e, +s, 0);
      } }, dateNoColon: { regex: new RegExp("^" + E + z + I), name: "datenocolon", callback(n, t, e, s) {
        return this.ymd(+t, +e - 1, +s);
      } }, pgydotd: { regex: new RegExp("^" + E + "\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"), name: "pgydotd", callback(n, t, e) {
        return this.ymd(+t, 0, +e);
      } }, timeShort24: { regex: new RegExp("^t?" + _ + "[:.]" + j, "i"), name: "timeshort24", callback(n, t, e) {
        return this.time(+t, +e, 0, 0);
      } }, iso8601noColon: { regex: new RegExp("^t?" + q + P + U, "i"), name: "iso8601nocolon", callback(n, t, e, s) {
        return this.time(+t, +e, +s, 0);
      } }, iso8601dateSlash: { regex: new RegExp("^" + E + "/" + z + "/" + I + "/"), name: "iso8601dateslash", callback(n, t, e, s) {
        return this.ymd(+t, +e - 1, +s);
      } }, dateSlash: { regex: new RegExp("^" + E + "/" + Y + "/" + C), name: "dateslash", callback(n, t, e, s) {
        return this.ymd(+t, +e - 1, +s);
      } }, american: { regex: new RegExp("^" + Y + "/" + C + "/" + Z), name: "american", callback(n, t, e, s) {
        return this.ymd(H(s), +t - 1, +e);
      } }, americanShort: { regex: new RegExp("^" + Y + "/" + C), name: "americanshort", callback(n, t, e) {
        return this.ymd(this.y, +t - 1, +e);
      } }, gnuDateShortOrIso8601date2: { regex: new RegExp("^" + Z + "-" + Y + "-" + C), name: "gnudateshort | iso8601date2", callback(n, t, e, s) {
        return this.ymd(H(t), +e - 1, +s);
      } }, iso8601date4: { regex: new RegExp("^([+-]?[0-9]{4})-" + z + "-" + I), name: "iso8601date4", callback(n, t, e, s) {
        return this.ymd(+t, +e - 1, +s);
      } }, gnuNoColon: { regex: new RegExp("^t?" + q + P, "i"), name: "gnunocolon", callback(n, t, e) {
        switch (this.times) {
          case 0:
            return this.time(+t, +e, 0, this.f);
          case 1:
            return this.y = 100 * +t + +e, this.times++, !0;
          default:
            return !1;
        }
      } }, gnuDateShorter: { regex: new RegExp("^" + E + "-" + Y), name: "gnudateshorter", callback(n, t, e) {
        return this.ymd(+t, +e - 1, 1);
      } }, pgTextReverse: { regex: new RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-(" + X + ")-" + I, "i"), name: "pgtextreverse", callback(n, t, e, s) {
        return this.ymd(H(t), O(e), +s);
      } }, dateFull: { regex: new RegExp("^" + C + "[ \\t.-]*" + G + "[ \\t.-]*" + Z, "i"), name: "datefull", callback(n, t, e, s) {
        return this.ymd(H(s), O(e), +t);
      } }, dateNoDay: { regex: new RegExp("^" + G + "[ .\\t-]*" + E, "i"), name: "datenoday", callback(n, t, e) {
        return this.ymd(+e, O(t), 1);
      } }, dateNoDayRev: { regex: new RegExp("^" + E + "[ .\\t-]*" + G, "i"), name: "datenodayrev", callback(n, t, e) {
        return this.ymd(+t, O(e), 1);
      } }, pgTextShort: { regex: new RegExp("^(" + X + ")-" + I + "-" + Z, "i"), name: "pgtextshort", callback(n, t, e, s) {
        return this.ymd(H(s), O(t), +e);
      } }, dateNoYear: { regex: new RegExp("^" + Q, "i"), name: "datenoyear", callback(n, t, e) {
        return this.ymd(this.y, O(t), +e);
      } }, dateNoYearRev: { regex: new RegExp("^" + C + "[ .\\t-]*" + G, "i"), name: "datenoyearrev", callback(n, t, e) {
        return this.ymd(this.y, O(e), +t);
      } }, isoWeekDay: { regex: new RegExp("^" + E + "-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"), name: "isoweekday | isoweek", callback(n, t, e, s) {
        const r = s ? +s : 1;
        if (!this.ymd(+t, 0, 1)) return !1;
        let i = new Date(this.y, this.m, this.d).getDay();
        return i = 0 - (i > 4 ? i - 7 : i), this.rd += i + 7 * (+e - 1) + r, !0;
      } }, relativeText: { regex: new RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|" + Le + ")" + le + "(" + Ye + ")", "i"), name: "relativetext", callback(n, t, e) {
        const { amount: s } = (function(r) {
          const i = r.toLowerCase();
          return { amount: { last: -1, previous: -1, this: 0, first: 1, next: 1, second: 2, third: 3, fourth: 4, fifth: 5, sixth: 6, seventh: 7, eight: 8, eighth: 8, ninth: 9, tenth: 10, eleventh: 11, twelfth: 12 }[i] ?? 0, behavior: { this: 1 }[i] || 0 };
        })(t);
        switch (e.toLowerCase()) {
          case "sec":
          case "secs":
          case "second":
          case "seconds":
            this.rs += s;
            break;
          case "min":
          case "mins":
          case "minute":
          case "minutes":
            this.ri += s;
            break;
          case "hour":
          case "hours":
            this.rh += s;
            break;
          case "day":
          case "days":
            this.rd += s;
            break;
          case "fortnight":
          case "fortnights":
          case "forthnight":
          case "forthnights":
            this.rd += 14 * s;
            break;
          case "week":
          case "weeks":
            this.rd += 7 * s;
            break;
          case "month":
          case "months":
            this.rm += s;
            break;
          case "year":
          case "years":
            this.ry += s;
            break;
          case "mon":
          case "monday":
          case "tue":
          case "tuesday":
          case "wed":
          case "wednesday":
          case "thu":
          case "thursday":
          case "fri":
          case "friday":
          case "sat":
          case "saturday":
          case "sun":
          case "sunday":
            this.resetTime(), this.weekday = ce(e, 7), this.weekdayBehavior = 1, this.rd += 7 * (s > 0 ? s - 1 : s);
        }
      } }, relative: { regex: new RegExp("^([+-]*)[ \\t]*(\\d+)" + M + "(" + Ye + "|week)", "i"), name: "relative", callback(n, t, e, s) {
        const r = t.replace(/[^-]/g, "").length, i = +e * Math.pow(-1, r);
        switch (s.toLowerCase()) {
          case "sec":
          case "secs":
          case "second":
          case "seconds":
            this.rs += i;
            break;
          case "min":
          case "mins":
          case "minute":
          case "minutes":
            this.ri += i;
            break;
          case "hour":
          case "hours":
            this.rh += i;
            break;
          case "day":
          case "days":
            this.rd += i;
            break;
          case "fortnight":
          case "fortnights":
          case "forthnight":
          case "forthnights":
            this.rd += 14 * i;
            break;
          case "week":
          case "weeks":
            this.rd += 7 * i;
            break;
          case "month":
          case "months":
            this.rm += i;
            break;
          case "year":
          case "years":
            this.ry += i;
            break;
          case "mon":
          case "monday":
          case "tue":
          case "tuesday":
          case "wed":
          case "wednesday":
          case "thu":
          case "thursday":
          case "fri":
          case "friday":
          case "sat":
          case "saturday":
          case "sun":
          case "sunday":
            this.resetTime(), this.weekday = ce(s, 7), this.weekdayBehavior = 1, this.rd += 7 * (i > 0 ? i - 1 : i);
        }
      } }, dayText: { regex: new RegExp("^(" + Ie + ")", "i"), name: "daytext", callback(n, t) {
        this.resetTime(), this.weekday = ce(t, 0), this.weekdayBehavior !== 2 && (this.weekdayBehavior = 1);
      } }, relativeTextWeek: { regex: new RegExp("^(" + Le + ")" + le + "week", "i"), name: "relativetextweek", callback(n, t) {
        switch (this.weekdayBehavior = 2, t.toLowerCase()) {
          case "this":
            this.rd += 0;
            break;
          case "next":
            this.rd += 7;
            break;
          case "last":
          case "previous":
            this.rd -= 7;
        }
        isNaN(this.weekday) && (this.weekday = 1);
      } }, monthFullOrMonthAbbr: { regex: new RegExp("^(" + $e + "|" + X + ")", "i"), name: "monthfull | monthabbr", callback(n, t) {
        return this.ymd(this.y, O(t), this.d);
      } }, tzCorrection: { regex: new RegExp("^" + he, "i"), name: "tzcorrection", callback(n) {
        return this.zone(de(n));
      } }, tzAbbr: { regex: new RegExp("^\\(?([a-zA-Z]{1,6})\\)?"), name: "tzabbr", callback(n, t) {
        const e = yt[t.toLowerCase()];
        return e != null && !Number.isNaN(e) && this.zone(e);
      } }, ago: { regex: /^ago/i, name: "ago", callback() {
        this.ry = -this.ry, this.rm = -this.rm, this.rd = -this.rd, this.rh = -this.rh, this.ri = -this.ri, this.rs = -this.rs, this.rf = -this.rf;
      } }, year4: { regex: new RegExp("^" + E), name: "year4", callback(n, t) {
        return this.y = +t, !0;
      } }, whitespace: { regex: /^[ .,\t]+/, name: "whitespace" }, dateShortWithTimeLong: { regex: new RegExp("^" + Q + "t?" + _ + "[:.]" + j + "[:.]" + te, "i"), name: "dateshortwithtimelong", callback(n, t, e, s, r, i) {
        return this.ymd(this.y, O(t), +e) && this.time(+s, +r, +i, 0);
      } }, dateShortWithTimeLong12: { regex: new RegExp("^" + Q + B + "[:.]" + j + "[:.]" + U + M + F, "i"), name: "dateshortwithtimelong12", callback(n, t, e, s, r, i, a) {
        return this.ymd(this.y, O(t), +e) && this.time(W(+s, a), +r, +i, 0);
      } }, dateShortWithTimeShort: { regex: new RegExp("^" + Q + "t?" + _ + "[:.]" + j, "i"), name: "dateshortwithtimeshort", callback(n, t, e, s, r) {
        return this.ymd(this.y, O(t), +e) && this.time(+s, +r, 0, 0);
      } }, dateShortWithTimeShort12: { regex: new RegExp("^" + Q + B + "[:.]" + P + M + F, "i"), name: "dateshortwithtimeshort12", callback(n, t, e, s, r, i) {
        return this.ymd(this.y, O(t), +e) && this.time(W(+s, i), +r, 0, 0);
      } } }, wt = { y: NaN, m: NaN, d: NaN, h: NaN, i: NaN, s: NaN, f: NaN, ry: 0, rm: 0, rd: 0, rh: 0, ri: 0, rs: 0, rf: 0, weekday: NaN, weekdayBehavior: 0, firstOrLastDayOfMonth: 0, z: NaN, dates: 0, times: 0, zones: 0, ymd(n, t, e) {
        return !(this.dates > 0) && (this.dates++, this.y = n, this.m = t, this.d = e, !0);
      }, time(n, t, e, s) {
        return !(this.times > 0) && (this.times++, this.h = n, this.i = t, this.s = e, this.f = s, !0);
      }, resetTime() {
        return this.h = 0, this.i = 0, this.s = 0, this.f = 0, this.times = 0, !0;
      }, zone(n) {
        return this.zones <= 1 && (this.zones++, this.z = n, !0);
      }, toDate(n) {
        switch (this.dates && !this.times && (this.h = this.i = this.s = this.f = 0), isNaN(this.y) && (this.y = n.getFullYear()), isNaN(this.m) && (this.m = n.getMonth()), isNaN(this.d) && (this.d = n.getDate()), isNaN(this.h) && (this.h = n.getHours()), isNaN(this.i) && (this.i = n.getMinutes()), isNaN(this.s) && (this.s = n.getSeconds()), isNaN(this.f) && (this.f = n.getMilliseconds()), this.firstOrLastDayOfMonth) {
          case 1:
            this.d = 1;
            break;
          case -1:
            this.d = 0, this.m += 1;
        }
        if (!isNaN(this.weekday)) {
          const e = new Date(n.getTime());
          e.setFullYear(this.y, this.m, this.d), e.setHours(this.h, this.i, this.s, this.f);
          const s = e.getDay();
          if (this.weekdayBehavior === 2) s === 0 && this.weekday !== 0 && (this.weekday = -6), this.weekday === 0 && s !== 0 && (this.weekday = 7), this.d -= s, this.d += this.weekday;
          else {
            let r = this.weekday - s;
            (this.rd < 0 && r < 0 || this.rd >= 0 && r <= -this.weekdayBehavior) && (r += 7), this.weekday >= 0 ? this.d += r : this.d -= 7 - (Math.abs(this.weekday) - s), this.weekday = NaN;
          }
        }
        this.y += this.ry, this.m += this.rm, this.d += this.rd, this.h += this.rh, this.i += this.ri, this.s += this.rs, this.f += this.rf, this.ry = this.rm = this.rd = 0, this.rh = this.ri = this.rs = this.rf = 0;
        const t = new Date(n.getTime());
        switch (t.setFullYear(this.y, this.m, this.d), t.setHours(this.h, this.i, this.s, this.f), this.firstOrLastDayOfMonth) {
          case 1:
            t.setDate(1);
            break;
          case -1:
            t.setMonth(t.getMonth() + 1, 0);
        }
        return isNaN(this.z) || t.getTimezoneOffset() === this.z || (t.setUTCFullYear(t.getFullYear(), t.getMonth(), t.getDate()), t.setUTCHours(t.getHours(), t.getMinutes(), t.getSeconds() - this.z, t.getMilliseconds())), t;
      } };
      y.AbstractProvider = J, y.ArrayAdapter = Ae, y.ArrayProvider = class extends J {
        getFunctions() {
          return [ft, pt, mt];
        }
      }, y.BasicProvider = class extends J {
        getFunctions() {
          return [lt];
        }
      }, y.Compiler = Te, y.DateProvider = class extends J {
        getFunctions() {
          return [new N("date", function(n, t) {
            let e = "";
            return t && (e = `, ${t}`), `date(${n}${e})`;
          }, function(n, t, e) {
            return gt(t, e);
          }), new N("strtotime", function(n, t) {
            let e = "";
            return t && (e = `, ${t}`), `strtotime(${n}${e})`;
          }, function(n, t, e) {
            return (function(s, r) {
              const i = r ?? Math.floor(Date.now() / 1e3), a = [m.yesterday, m.now, m.noon, m.midnightOrToday, m.tomorrow, m.timestamp, m.firstOrLastDay, m.backOrFrontOf, m.timeTiny12, m.timeShort12, m.timeLong12, m.mssqltime, m.oracledate, m.timeShort24, m.timeLong24, m.iso8601long, m.gnuNoColon, m.iso8601noColon, m.americanShort, m.american, m.iso8601date4, m.iso8601dateSlash, m.dateSlash, m.gnuDateShortOrIso8601date2, m.gnuDateShorter, m.dateFull, m.pointedDate4, m.pointedDate2, m.dateNoDay, m.dateNoDayRev, m.dateTextual, m.dateNoYear, m.dateNoYearRev, m.dateNoColon, m.xmlRpc, m.xmlRpcNoColon, m.soap, m.wddx, m.exif, m.pgydotd, m.isoWeekDay, m.pgTextShort, m.pgTextReverse, m.clf, m.year4, m.ago, m.dayText, m.relativeTextWeek, m.relativeText, m.monthFullOrMonthAbbr, m.tzCorrection, m.tzAbbr, m.dateShortWithTimeShort12, m.dateShortWithTimeLong12, m.dateShortWithTimeShort, m.dateShortWithTimeLong, m.relative, m.whitespace], o = { ...wt };
              for (; s.length; ) {
                let l = null, d = null;
                for (const f of a) {
                  const g = s.match(f.regex);
                  g && (!l || g[0].length > l[0].length) && (l = g, d = f);
                }
                if (!d || !l || d.callback && d.callback.apply(o, l) === !1) return !1;
                s = s.substr(l[0].length), d = null, l = null;
              }
              return Math.floor(o.toDate(new Date(1e3 * i)).getTime() / 1e3);
            })(t, e);
          })];
        }
      }, y.ExpressionFunction = N, y.ExpressionLanguage = Oe, y.IGNORE_UNKNOWN_FUNCTIONS = 2, y.IGNORE_UNKNOWN_VARIABLES = 1, y.Parser = ke, y.StringProvider = class extends J {
        getFunctions() {
          return [new N("strtolower", (n) => "strtolower(" + n + ")", (n, t) => (function(e) {
            return (e + "").toLowerCase();
          })(t)), new N("strtoupper", (n) => "strtoupper(" + n + ")", (n, t) => (function(e) {
            return (e + "").toUpperCase();
          })(t)), new N("explode", (n, t, e = "null") => `explode(${n}, ${t}, ${e})`, (n, t, e, s = null) => (function(...r) {
            let [i, a, o] = r, l = i;
            const d = a;
            if (r.length < 2 || l === void 0 || d === void 0) return null;
            if (l === "" || l === !1 || l === null) return !1;
            if (typeof l == "function" || typeof l == "object" || typeof d == "function" || typeof d == "object") return { 0: "" };
            l === !0 && (l = "1");
            const f = l + "", g = (d + "").split(f);
            return o === void 0 ? g : (o === 0 && (o = 1), o > 0 ? o >= g.length ? g : g.slice(0, o - 1).concat([g.slice(o - 1).join(f)]) : -o >= g.length ? [] : (g.splice(g.length + o), g));
          })(t, e, s)), new N("strlen", function(n) {
            return `strlen(${n});`;
          }, function(n, t) {
            return (function(e) {
              const s = e + "";
              if ((Re("unicode.semantics") || "off") === "off") return s.length;
              let r = 0, i = 0;
              const a = function(o, l) {
                const d = o.charCodeAt(l);
                if (d >= 55296 && d <= 56319) {
                  if (o.length <= l + 1) throw new Error("High surrogate without following low surrogate");
                  const f = o.charCodeAt(l + 1);
                  if (f < 56320 || f > 57343) throw new Error("High surrogate without following low surrogate");
                  return o.charAt(l) + o.charAt(l + 1);
                }
                if (d >= 56320 && d <= 57343) {
                  if (l === 0) throw new Error("Low surrogate without preceding high surrogate");
                  const f = o.charCodeAt(l - 1);
                  if (f < 55296 || f > 56319) throw new Error("Low surrogate without preceding high surrogate");
                  return !1;
                }
                return o.charAt(l);
              };
              for (r = 0, i = 0; r < s.length; r++) a(s, r) !== !1 && i++;
              return i;
            })(t);
          }), new N("strstr", function(n, t, e) {
            let s = "";
            return e && (s = `, ${e}`), `strstr(${n}, ${t}${s});`;
          }, function(n, t, e, s) {
            return (function(r, i, a) {
              let o = 0;
              return o = (r += "").indexOf(i), o !== -1 && (a ? r.substr(0, o) : r.slice(o));
            })(t, e, s);
          }), new N("stristr", function(n, t, e) {
            let s = "";
            return e && (s = `, ${e}`), `stristr(${n}, ${t}${s});`;
          }, function(n, t, e, s) {
            return (function(r, i, a) {
              let o = 0;
              return o = (r += "").toLowerCase().indexOf((i + "").toLowerCase()), o !== -1 && (a ? r.substr(0, o) : r.slice(o));
            })(t, e, s);
          }), new N("substr", function(n, t, e) {
            let s = "";
            return e && (s = `, ${e}`), `substr(${n}, ${t}${s});`;
          }, function(n, t, e, s) {
            return ct(t, e, s);
          })];
        }
      }, y.default = Oe, y.tokenize = pe, Object.defineProperty(y, "__esModule", { value: !0 });
    }), (function(y) {
      var u = y.ExpressionLanguage;
      if (u && typeof u.ExpressionLanguage == "function") {
        var w = u.ExpressionLanguage;
        Object.keys(u).forEach(function(x) {
          x in w || (w[x] = u[x]);
        }), y.ExpressionLanguage = w;
      }
    })(typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : Ke);
  })(ne, ne.exports)), ne.exports;
}
var Qe = Nt();
const kt = /* @__PURE__ */ vt(Qe), qe = /* @__PURE__ */ bt({
  __proto__: null,
  default: kt
}, [Qe]);
let Ze = null;
function Tt() {
  const c = qe, p = c.ExpressionLanguage || c.default || qe;
  if (typeof p != "function")
    throw new TypeError("Unable to resolve expression-language constructor.");
  return p;
}
function Et() {
  return Ze ??= new (Tt())(), Ze;
}
function Ut(c) {
  return (c.formula?.expression || c.formula?.formula || "").trim();
}
function It(c) {
  return Object.entries(c.formula?.variables || {}).filter((p) => !!p[1]?.sourceKey);
}
function Lt(c, p) {
  return Object.entries(c).forEach(([y, u]) => {
    if (Array.isArray(u)) {
      c[y] = u.map((w) => typeof w == "string" && w.trim() !== "" && !Number.isNaN(Number(w)) ? Number(w) : w);
      return;
    }
    typeof u == "string" && u.trim() !== "" && !Number.isNaN(Number(u)) && (c[y] = Number(u));
  }), c;
}
function At(c, p) {
  if (p.formatting !== "number")
    return typeof c == "number" || typeof c == "string" ? c : "";
  let y = c;
  Array.isArray(y) && (y = y.reduce((x, T) => x + Number(T || 0), 0));
  const u = typeof p.decimals == "number" ? p.decimals : 0, w = Number(y || 0).toFixed(u);
  return `${p.prefix || ""}${w}${p.suffix || ""}`;
}
function Yt(c, p) {
  const y = c.type?.endsWith("\\Number");
  return c.type?.endsWith("\\Checkboxes") ? Array.isArray(p) ? p.length ? p : "" : p ? [p] : "" : Array.isArray(p) ? p.length ? y ? p.map((u) => Number(u || 0)) : p : "" : y ? Number(p || 0) : p;
}
function $t(c, p, y) {
  return At(Et().evaluate(c, p), y);
}
const Xe = (() => {
  const c = Intl.Segmenter;
  return c ? new c(void 0, { granularity: "grapheme" }) : null;
})(), Ot = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;
function St(c) {
  return typeof DOMParser < "u" ? new DOMParser().parseFromString(c, "text/html").body.textContent || "" : c.replace(/<[^>]*>/g, " ");
}
function et(c) {
  return St(c);
}
function Ct(c) {
  return et(c).replace(/[\s\t\n\r]+/g, " ").trim();
}
function _t(c) {
  return Xe ? Array.from(Xe.segment(c)).length : Array.from(c).length;
}
function Pt(c) {
  return c.match(Ot)?.length || 0;
}
function Dt(c) {
  const p = et(c), y = Ct(c);
  return {
    graphemeCount: _t(p),
    wordCount: Pt(y)
  };
}
export {
  $t as $,
  jt as A,
  Lt as F,
  Rt as S,
  Yt as U,
  It as _,
  Ut as j,
  Dt as z
};
