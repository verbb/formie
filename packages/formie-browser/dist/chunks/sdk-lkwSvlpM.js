//#region ../../node_modules/@friendlycaptcha/sdk/sdk.js
function e(e) {
	let t, n, r = new Promise((e, r) => {
		t = e, n = r;
	});
	return e && e(t, n), {
		promise: r,
		resolve: t,
		reject: n
	};
}
function t(e, t) {
	return e.lastIndexOf(t, 0) === 0;
}
var n = /^((?:\w+:)?\/\/([^\/]+))/;
function r(e) {
	let t = [], n = Object.keys(e), r = encodeURIComponent;
	for (let i = 0; i < n.length; i++) t.push(`${r(n[i])}=${r(e[n[i]])}`);
	return t.join("&");
}
function i(e) {
	let r = document.location;
	if (t(e, "/") || t(e, ".")) return r.origin ? r.origin : r.protocol + "//" + r.host;
	let i = e.match(n);
	if (!i) throw Error("Invalid URL: " + e);
	return i[1];
}
var a = class {
	constructor(e) {
		this.ready = !1, this.buffer = [], this.id = e.id, this.type = e.type, this.element = e.element, this.onReady = e.onReady, this.origin = i(e.element.src);
	}
	send(e) {
		this.ready ? this.element.contentWindow.postMessage(e, this.origin) : this.buffer.push(e);
	}
	setReady(e) {
		this.onReady(), this.ready = e, this.ready && this.flush();
	}
	flush() {
		for (let e = 0; e < this.buffer.length; e++) this.element.contentWindow.postMessage(this.buffer[e], this.origin);
		this.buffer = [];
	}
};
function o(e, t) {
	return e === "*" || t.has(e);
}
var s = class {
	constructor() {
		this.origins = /* @__PURE__ */ new Set(), this.targets = {}, this.answered = /* @__PURE__ */ new Set(), this.onReceiveRootMessage = () => {}, window.addEventListener("message", (e) => {
			this.onReceive(e);
		});
	}
	listen(e) {
		let t = this.onReceiveRootMessage;
		this.onReceiveRootMessage = (n) => {
			t(n), e(n);
		};
	}
	addOrigins(e) {
		e.forEach((e) => this.origins.add(e));
	}
	send(e) {
		if (e.from_id) {
			let t = this.targets[e.from_id];
			if (!t) {
				console.error(`[bus] Unexpected message from unknown sender ${e.from_id}`, e);
				return;
			}
			(e.type === "widget_announce" || e.type === "agent_announce") && t.setReady(!0);
		}
		let t = e.rid;
		if (t) {
			if (this.answered.has(t + e.to_id)) return;
			this.answered.add(t + e.to_id);
		}
		if (e.to_id === "") {
			this.onReceiveRootMessage(e);
			return;
		}
		let n = this.targets[e.to_id];
		if (!n) {
			console.error(`[bus] Unexpected message to unknown target ${e.to_id}`, e);
			return;
		}
		n.send(e);
	}
	onReceive(e) {
		if (!o(e.origin, this.origins)) return;
		let t = e.data;
		t && t._frc && this.send(t);
	}
	registerTarget(e) {
		this.targets[e.id] = e;
	}
	registerTargetIFrame(t, n, r, i) {
		let o = e(), s, c = new Promise((e) => {
			s = setTimeout(() => e("timeout"), i);
		}), l = new a({
			id: n,
			element: r,
			type: t,
			onReady: () => {
				clearTimeout(s), o.resolve("registered");
			}
		});
		return this.registerTarget(l), Promise.race([o.promise, c]);
	}
	removeTarget(e) {
		delete this.targets[e];
	}
};
function c(e, t = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789") {
	let n = "";
	for (let r = 0; r < e; r++) n += t.charAt(Math.floor(Math.random() * t.length));
	return n;
}
function l(e) {
	let t = e.slice();
	for (let e = t.length - 1; e > 0; e--) {
		let n = Math.floor(Math.random() * (e + 1)), r = t[e];
		t[e] = t[n], t[n] = r;
	}
	return t;
}
var u = "frc_sc", d = "frc_sid", f = "__", p = "0", m = "__" + c(10);
function h(e) {
	{
		let t = 0;
		try {
			t = parseInt(sessionStorage.getItem(u) || "", 10);
		} catch {}
		isNaN(t) && (t = 0), e && t++, p = t.toString();
		try {
			sessionStorage.setItem(u, p);
		} catch {}
	}
	return p;
}
function g() {
	let e;
	try {
		e = sessionStorage.getItem(d);
	} catch {
		return m;
	}
	return e || (e = c(12), sessionStorage.setItem(d, e)), e;
}
var _ = class {
	constructor(e) {
		this.mem = /* @__PURE__ */ new Map(), this.storePrefix = e;
	}
	get(e) {
		let t = this.storePrefix + f + e;
		try {
			let e = sessionStorage.getItem(t);
			return e === null ? void 0 : e;
		} catch {}
		return this.mem.get(e);
	}
	set(e, t) {
		let n = this.storePrefix + f + e;
		try {
			t === void 0 ? (this.mem.delete(e), sessionStorage.removeItem(n)) : (this.mem.set(e, t), sessionStorage.setItem(n, t));
		} catch {}
	}
}, v = typeof navigator < "u" && navigator.userAgentData !== void 0;
function y() {
	return [document.querySelectorAll(".frc-captcha"), document.querySelectorAll(".frc-risk-intelligence")];
}
function b(e) {
	let t = e;
	for (; t;) {
		if (t.tagName === "FORM") return t;
		if (t.parentElement) {
			t = t.parentElement;
			continue;
		}
		let e = t.parentNode;
		if (e && e.host) {
			t = e.host;
			continue;
		}
		t = null;
	}
	return null;
}
function x(e, t) {
	e.addEventListener("focusin", t, {
		once: !0,
		passive: !0
	});
}
function S(e, t) {
	let n = document.createElement("input");
	return n.type = "hidden", n.style.display = "none", n.name = t, e.appendChild(n), n;
}
function ee(e, t, n) {
	e.style[t] === "" && (e.style[t] = n);
}
function te(e) {
	let t = ee;
	t(e, "position", "relative"), t(e, "height", "70px"), t(e, "padding", "0"), t(e, "width", "316px"), t(e, "maxWidth", "100%"), t(e, "maxHeight", "100%"), t(e, "overflow", "hidden"), t(e, "borderRadius", "4px");
}
function ne(e) {
	e.removeAttribute("style");
}
function C(e) {
	document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", e) : e();
}
function w(e, t) {
	let n;
	typeof window.CustomEvent == "function" ? n = new CustomEvent(t.name, {
		bubbles: !0,
		detail: t
	}) : (n = document.createEvent("CustomEvent"), n.initCustomEvent(t.name, !0, !1, t)), e.dispatchEvent(n);
}
function re(e) {
	for (; !e.lang || typeof e.lang != "string";) if (e = e.parentElement, !e) return null;
	return e.lang;
}
var T = {
	ar: {
		title: "التحقق من مكافحة الروبوتات",
		connecting: "التحقق من مكافحة الروبوتات قيد الاتصال…",
		retrying: "استغرق الاتصال بالتحقق من مكافحة الروبوتات وقتًا طويلاً.\n\nإعادة المحاولة…",
		failed: "فشل الاتصال بالتحقق من مكافحة الروبوتات.",
		newTab: "يفتح في تبويب جديد"
	},
	bg: {
		title: "Проверка срещу роботи",
		connecting: "Зарежда се задачата…",
		retrying: "Неуспешно свързване.\n\nОпит за повторно свързване…",
		failed: "Неуспешно свързване.",
		newTab: "отваря се в нов раздел"
	},
	ca: {
		title: "Verificació anti-robot",
		connecting: "Carregant el desafiament…",
		retrying: "Errada de conexió.\n\nReintentant…",
		failed: "Errada de conexió.",
		newTab: "s'obre en una pestanya nova"
	},
	cs: {
		title: "Ověření proti robotům",
		connecting: "Připojování kontroly proti robotům…",
		retrying: "Připojení kontroly proti robotům trvalo příliš dlouho.\n\nOpakuji pokus…",
		failed: "Kontrola proti robotům se nepodařilo připojit.",
		newTab: "otevře se na nové kartě"
	},
	da: {
		title: "Anti-robot-verifikation",
		connecting: "Anti-robot-kontrol forbinder…",
		retrying: "Anti-robot-kontrol tog for lang tid at oprette forbindelse.\n\nPrøver igen…",
		failed: "Anti-robot-kontrol kunne ikke oprette forbindelse.",
		newTab: "åbner i ny fane"
	},
	nl: {
		title: "Anti-robotcheck",
		connecting: "Verbinden met Anti-robotcheck…",
		retrying: "Verbinden met Anti-robotcheck mislukt.\n\nOpnieuw aan het proberen…",
		failed: "Verbinden met Anti-robotcheck mislukt.",
		newTab: "opent in nieuw tabblad"
	},
	en: {
		title: "Anti-Robot verification",
		connecting: "Anti-Robot check connecting…",
		retrying: "Anti-Robot check took too long to connect.\n\nRetrying…",
		failed: "Anti-Robot check failed to connect.",
		newTab: "opens in new tab"
	},
	fi: {
		title: "Robottien torjunnan vahvistus",
		connecting: "Robottien torjunnan tarkistus käynnissä…",
		retrying: "Robottien torjunnan tarkistus kesti liian kauan.\n\nYritetään uudelleen…",
		failed: "Robottien torjunnan tarkistus epäonnistui.",
		newTab: "avautuu uuteen välilehteen"
	},
	fr: {
		title: "Vérification anti-robot",
		connecting: "Connexion à la vérification anti-robot…",
		retrying: "La connexion à la vérification anti-robot a pris trop de temps.\n\nNouvelle tentative…",
		failed: "Échec de la connexion à la vérification anti-robot.",
		newTab: "s'ouvre dans un nouvel onglet"
	},
	de: {
		title: "Anti-Roboter-Verifizierung",
		connecting: "Verbindung zur Anti-Roboter-Verifizierung wird hergestellt…",
		retrying: "Verbindung zur Anti-Roboter-Verifizierung hat zu lange gedauert.\n\nErneuter Versuch…",
		failed: "Verbindung zur Anti-Roboter-Verifizierung ist fehlgeschlagen.",
		newTab: "öffnet in neuem Tab"
	},
	hi: {
		title: "एंटी-रोबोट सत्यापन",
		connecting: "चुनौती लोड हो रही है…",
		retrying: "कनेक्शन विफल.\n\nपुनः प्रयास कर रहे हैं…",
		failed: "कनेक्शन विफल.",
		newTab: "नए टैब में खुलता है"
	},
	hu: {
		title: "Robotellenőrzés",
		connecting: "Robotellenőrzés csatlakozás…",
		retrying: "A robotellenőrzés túl sokáig tartott a csatlakozáshoz.\n\nÚjrapróbálom…",
		failed: "A robotellenőrzés nem tudott csatlakozni.",
		newTab: "új lapon nyílik meg"
	},
	id: {
		title: "Verifikasi Anti-Robot",
		connecting: "Pemeriksaan Anti-Robot sedang terhubung…",
		retrying: "Pemeriksaan Anti-Robot memakan waktu terlalu lama untuk terhubung.\n\nMencoba lagi…",
		failed: "Pemeriksaan Anti-Robot gagal terhubung.",
		newTab: "terbuka di tab baru"
	},
	ja: {
		title: "ロボット防止認証",
		connecting: "チャレンジを読み込んでいます…",
		retrying: "接続失敗.\n\n再試行中…",
		failed: "接続失敗.",
		newTab: "新しいタブで開きます"
	},
	it: {
		title: "Verifica anti-robot",
		connecting: "Connessione verifica anti-robot in corso…",
		retrying: "La connessione alla verifica anti-robot ha richiesto troppo tempo.\n\nRiprovando…",
		failed: "Impossibile connettersi alla verifica anti-robot.",
		newTab: "si apre in una nuova scheda"
	},
	nb: {
		title: "Anti-robot-verifisering",
		connecting: "Laster inn utfordring…",
		retrying: "Klarte ikke å koble til.\n\nPrøver igjen…",
		failed: "Klarte ikke å koble til.",
		newTab: "åpnes i ny fane"
	},
	pl: {
		title: "Weryfikacja antyrobotowa",
		connecting: "Łączenie się z kontrolą antyrobotową…",
		retrying: "Łączenie się z kontrolą antyrobotową trwało zbyt długo. \n\nPonowna próba…",
		failed: "Nie udało się połączyć z kontrolą antyrobotową.",
		newTab: "otwiera się w nowej karcie"
	},
	ro: {
		title: "Verificare anti-robot",
		connecting: "Se incarca testul…",
		retrying: "Conexiunea a esuat.\n\nReincercare…",
		failed: "Conexiunea a esuat.",
		newTab: "se deschide într-o filă nouă"
	},
	pt: {
		title: "Verificação anti-robô",
		connecting: "Verificação anti-robô a ligar…",
		retrying: "A verificação anti-robô demorou demasiado tempo a ligar-se.\n\nA tentar novamente…",
		failed: "A verificação anti-robô não conseguiu ligar-se.",
		newTab: "abre num novo separador"
	},
	ru: {
		title: "Проверка антиробота",
		connecting: "Подключение к проверке антиробота…",
		retrying: "Подключение к проверке антиробота заняло слишком много времени.\n\nПовторяем попытку…",
		failed: "Не удалось подключиться к проверке антиробота.",
		newTab: "откроется в новой вкладке"
	},
	sk: {
		title: "Overovanie proti robotom",
		connecting: "Pripojenie kontroly proti robotom…",
		retrying: "Pripojenie kontroly proti robotom trvalo príliš dlho.\n\nOpakujem pokus…",
		failed: "Pripojenie kontroly proti robotom sa nepodarilo.",
		newTab: "otvorí sa na novej karte"
	},
	sl: {
		title: "Preverjanje proti robotom",
		connecting: "Nalaganje izziva…",
		retrying: "Povezava ni uspela.\n\nPonovni poskus…",
		failed: "Povezava ni uspela.",
		newTab: "odpre se v novem zavihku"
	},
	es: {
		title: "Verificación antirrobot",
		connecting: "Conectando verificación antirrobot…",
		retrying: "La verificación antirrobot tardó demasiado en conectarse.\n\nReintentando…",
		failed: "Error al conectar la verificación antirrobot.",
		newTab: "se abre en una pestaña nueva"
	},
	sv: {
		title: "Anti-robotverifiering",
		connecting: "Anti-robotverifiering ansluter…",
		retrying: "Anti-robotverifiering tog för lång tid att ansluta.\n\nFörsöker igen…",
		failed: "Anti-robotverifiering kunde inte ansluta.",
		newTab: "öppnas i ny flik"
	},
	th: {
		title: "การตรวจสอบป้องกันบอท",
		connecting: "กำลังโหลดการท้าทาย…",
		retrying: "เชื่อมต่อไม่สำเร็จ.\n\nกำลังลองใหม่…",
		failed: "เชื่อมต่อไม่สำเร็จ.",
		newTab: "เปิดในแท็บใหม่"
	},
	tr: {
		title: "Robot önleme doğrulaması",
		connecting: "Robot önleme kontrolü bağlanıyor…",
		retrying: "Robot önleme kontrolü bağlanmak için çok uzun sürdü.\n\nYeniden deniyor…",
		failed: "Robot önleme kontrolü bağlanamadı.",
		newTab: "yeni sekmede açılır"
	},
	vi: {
		title: "Xác minh chống robot",
		connecting: "Kiểm tra robot đang kết nối…",
		retrying: "Kiểm tra chống robot mất quá nhiều thời gian để kết nối.\n\nĐang thử lại…",
		failed: "Kiểm tra chống robot không thể kết nối.",
		newTab: "mở trong tab mới"
	},
	zh: {
		title: "反机器人验证",
		connecting: "反机器人验证正在连接…",
		retrying: "反机器人验证连接耗时过长。\n\n正在重试…",
		failed: "反机器人验证连接失败。",
		newTab: "在新标签页中打开"
	}
}, ie = [
	"ar",
	"he",
	"fa",
	"ur",
	"ps",
	"sd",
	"yi"
];
function E(e) {
	return e.toLowerCase().split("-")[0].split("_")[0];
}
function D(e) {
	return e = E(e), ie.indexOf(e) !== -1;
}
function ae(e) {
	return e = E(e), (T[e] || T.en).title + " - Widget";
}
function O(e, t) {
	return e = E(e), (T[e] || T.en)[t];
}
var oe = "FrcFrameId", k = "frc-i-agent", se = "frc-i-widget", ce = "frc-widget-placeholder";
function le(e, t, n, i) {
	let a = {
		origin: document.location.origin,
		sess_id: g(),
		sess_c: h(!0),
		comm_id: t,
		sdk_v: "1.1.1",
		v: "1",
		agent_id: t,
		ts: Date.now().toString()
	};
	i && (a.guard_c = i);
	let o = document.createElement("iframe");
	o.className = k, o.dataset[oe] = t, o.src = n + "?" + r(a), o.frcSDK = e;
	let s = o.style;
	return s.width = s.height = s.border = s.visibility = "0", s.display = "none", o;
}
function ue(e, t, n, i, a) {
	let o = document.createElement("iframe"), s = j(i), c = {
		origin: document.location.origin,
		sess_id: g(),
		sess_c: h(!0),
		comm_id: t,
		sdk_v: "1.1.1",
		v: "1",
		agent_id: e,
		lang: s,
		sitekey: i.sitekey || "",
		ts: Date.now().toString()
	};
	i.theme && (c.theme = i.theme), a && (c.guard_c = a), v && (o.allow = "clipboard-write"), o.frameBorder = "0", o.src = n + "?" + r(c), o.className = se, o.title = ae(s), o.dataset[oe] = t;
	let l = o.style;
	return l.border = l.visibility = "0", l.position = "absolute", l.height = l.width = "100%", l.userSelect = "none", l["-webkit-tap-highlight-color"] = "transparent", l.display = "none", i.element.appendChild(o), o;
}
function de(e) {
	let t = document.createElement("div");
	t.classList.add(ce);
	let n = t.style, r = e.theme === "dark" || e.theme === "auto" && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
	return n.color = r ? "#fff" : "#222", n.backgroundColor = r ? "#171717" : "#fafafa", n.borderRadius = "4px", n.border = "1px solid", n.borderColor = "#ddd", n.padding = "8px", n.height = n.width = "100%", n.fontSize = "14px", n.boxSizing = "border-box", A(n), e.element.appendChild(t), t;
}
function A(e) {
	e.textDecoration = e.fontStyle = "none", e.fontWeight = "500", e.fontFamily = "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif", e.lineHeight = "1", e.letterSpacing = "-0.0125rem";
}
function fe(e) {
	let t = document.createElement("div");
	t.classList.add("frc-banner");
	let n = j(e), r = e.theme === "dark" || e.theme === "auto" && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches, i = "#565656", a = "#a2a2a2";
	r && (i = "#a2a2a2", a = "#565656");
	let o = t.style;
	o.position = "absolute", o.bottom = "6px", D(n) ? o.left = "6px" : o.right = "6px", o.lineHeight = "1";
	let s = document.createElement("a");
	s.href = "https://friendlycaptcha.com", s.rel = "noopener";
	let c = s.style;
	A(c), c.color = i, c.fontSize = "10px", c.userSelect = "none", c.textDecorationLine = "underline", c.textDecorationThickness = "1px", c.textDecorationColor = a, c.letterSpacing = "-0.0125rem", s.target = "_blank", s.textContent = "Friendly Captcha", s.ariaLabel = "Friendly Captcha (" + O(n, "newTab") + ")", s.onmouseenter = () => c.textDecorationColor = i, s.onmouseleave = () => c.textDecorationColor = a, t.appendChild(s), e.element.appendChild(t);
}
function j(e) {
	let t = e.language;
	return (!t || t === "html") && (t = re(e.element) || ""), t;
}
function pe(e, t, n) {
	let r = (e, t) => {
		let n = document.createElement("a");
		n.href = e, n.target = "_blank", n.rel = "noopener", n.textContent = t;
		let r = n.style;
		return A(r), r.textDecoration = "underline", r.color = "#565656", n.onmouseenter = () => r.textDecoration = "none", n.onmouseleave = () => r.textDecoration = "underline", n;
	}, i = O(n, "failed"), a = [r(`${t}/connectionTest`, i)];
	e.textContent = "", a.forEach((t) => e.appendChild(t));
}
function M() {
	let e = window.performance;
	return e ? e.now() : 0;
}
function me(e, t, n, r) {
	let i = M(), a = n.getBoundingClientRect(), o = {
		v: 1,
		tt: e,
		pnow: i,
		sm: t,
		el: {
			bcr: [
				a.left,
				a.top,
				a.width,
				a.height
			],
			con: document.body.contains(n)
		},
		stack: (/* @__PURE__ */ Error()).stack || "",
		we: !!window.event,
		weit: !!window.event && !!window.event.isTrusted
	};
	return r && (o.ev = {
		ts: r.timeStamp,
		rt: !!r.relatedTarget,
		eot: !!r.explicitOriginalTarget,
		it: r.isTrusted
	}), o;
}
function N(e, ...t) {
	for (let r = 0; r < t.length; r++) {
		let i = t[r];
		for (var n in i) i.hasOwnProperty(n) && (e[n] = i[n]);
	}
	return e;
}
var he = "frc-captcha-response", ge = class {
	constructor(e) {
		this.state = "init", this.response = ".UNINITIALIZED", this.focusEventPending = !1, this.isDestroyed = !1, this.id = e.id;
		let t = e.createOpts;
		if (this.e = t.element, this.ready = e.registered, !this.e) throw Error("No element provided to mount widget under.");
		this.e.frcWidget = this, this.formFieldName = t.formFieldName === void 0 ? he : t.formFieldName, this.sitekey = t.sitekey, this._reset = e.callbacks.onReset, this._destroy = e.callbacks.onDestroy, this._trigger = e.callbacks.onTrigger, this.startMode = e.createOpts.startMode || "focus", this.formFieldName !== null && (this.hiddenFormEl = S(this.e, this.formFieldName)), this.setState({
			response: ".UNCONNECTED",
			state: "init"
		}), this.ready.then(() => {
			this.handleStartMode();
		});
	}
	handleStartMode() {
		if (this.startMode === "focus" && !this.focusEventPending && !this.isDestroyed) {
			let e = b(this.e);
			e && (this.focusEventPending = !0, x(e, (e) => {
				this.trigger("focus", { ev: e }), this.focusEventPending = !1;
			}));
		} else this.startMode === "auto" && this.trigger("auto");
	}
	reset(e = { trigger: "root" }) {
		if (this.isDestroyed) throw Error("Can not reset destroyed widget.");
		this.setState({
			response: ".RESET",
			state: "reset",
			resetTrigger: e.trigger
		}), this._reset(e), this.handleStartMode();
	}
	destroy() {
		this.isDestroyed = !0, this.hiddenFormEl && this.hiddenFormEl.remove(), this.hiddenFormEl = void 0, this.setState({
			response: ".DESTROYED",
			state: "destroyed"
		}), this._destroy();
	}
	trigger(e, t = {}) {
		if (this.isDestroyed) throw Error("Can not start destroyed widget.");
		let n = me(e, this.startMode, this.e, t.ev);
		this._trigger({ trigger: n });
	}
	start() {
		this.trigger("programmatic");
	}
	setState(e) {
		let t = this.state !== e.state;
		this.response = e.response, this.state = e.state, this.hiddenFormEl && this.e.isConnected !== !1 && (this.hiddenFormEl.value = e.response), t && this.dispatchWidgetEvent({
			name: "frc:widget.statechange",
			error: e.error,
			mode: e.mode
		}), this.state === "expired" ? this.dispatchWidgetEvent({ name: "frc:widget.expire" }) : this.state === "completed" ? this.dispatchWidgetEvent({ name: "frc:widget.complete" }) : this.state === "error" ? this.dispatchWidgetEvent({
			name: "frc:widget.error",
			error: e.error
		}) : this.state === "reset" && this.dispatchWidgetEvent({
			name: "frc:widget.reset",
			trigger: e.resetTrigger
		});
	}
	dispatchWidgetEvent(e) {
		let t = {
			response: this.response,
			state: this.state,
			id: this.id
		};
		N(t, e), w(this.e, t);
	}
	addEventListener(e, t, n) {
		this.e.addEventListener(e, t, n);
	}
	removeEventListener(e, t, n) {
		this.e.removeEventListener(e, t, n);
	}
	getState() {
		return this.state;
	}
	getResponse() {
		return this.response;
	}
	getElement() {
		return this.e;
	}
}, _e = function(e) {
	return typeof e == "function";
}, ve = function(e) {
	let t = [], n = /* @__PURE__ */ new Map(), r = function() {
		return t.splice(0, t.length);
	}, i = window, a = (function() {
		try {
			let e = document.createElement("iframe");
			e.style.display = "none", (document.body || document.head).appendChild(e);
			let t = e ? e.contentWindow : 0;
			return e.remove(), t || i;
		} catch {
			return i;
		}
	})(), o = Function.prototype.toString, s = function(...e) {
		let t = _e(this) ? n.get(this) : !1, r = this === s ? o : t || this;
		return o.apply(r, e);
	};
	Function.prototype.toString = s;
	let c = function() {
		return (a.Error || i.Error)("FriendlyCaptcha_DummyTrace").stack || "";
	}, l = "prototype", u = i.EventTarget ? i.EventTarget[l].dispatchEvent : {}, d = [
		[
			"Document." + l + ".documentElement",
			i.Document[l],
			"documentElement"
		],
		[
			"Element." + l + ".shadowRoot",
			i.Element[l],
			"shadowRoot"
		],
		[
			"Node." + l + ".nodeType",
			i.Node[l],
			"nodeType"
		],
		[
			"Object.is",
			i.Object,
			"is"
		],
		[
			"Array." + l + ".slice",
			i.Array[l],
			"slice"
		],
		[
			"Document." + l + ".querySelectorAll",
			i.Document[l],
			"querySelectorAll"
		],
		[
			"Document." + l + ".createElement",
			i.Document[l],
			"createElement"
		],
		[
			"EventTarget." + l + ".dispatchEvent",
			u,
			"dispatchEvent"
		]
	];
	return e.disableEvalPatching || d.push([
		"eval",
		i,
		"eval"
	]), d.forEach(function([e, r, i]) {
		let a = Object.getOwnPropertyDescriptor(r, i), o = a && (a.get || a.set);
		if (!a) return;
		if (o) {
			if (!a.get) return;
		} else if (typeof a.value != "object" && typeof a.value != "function") return;
		let s = 0, l = 0, u = function(...n) {
			let r = Date.now();
			if (r - s >= 1e3 && (l = 0, s = r), l < 50) {
				let n = {
					d: r,
					pnow: M(),
					n: e,
					st: c()
				};
				t.length > 2e4 && t.splice(0, 1e3), t.push(n), l++;
			}
			return (o ? a.get : a.value).apply(this, n);
		};
		try {
			let e = o ? a.get ? a.get() : void 0 : a.value();
			e && (u.length = e.length, u.name = e.name);
		} catch {}
		try {
			let e = N({}, a);
			o ? e.get = u : e.value = u, Object.defineProperty(r, i, e), n.set(u, o ? a.get : a.value);
		} catch {}
	}), r;
};
function P() {
	let e = [
		0,
		0,
		0,
		0,
		0,
		0,
		0
	];
	return {
		s: e,
		add(t) {
			let n = ++e[0], r = t - e[1], i = r / n, a = i * i, o = r * i * (n - 1);
			e[1] += i, e[4] += o * a * (n * n - 3 * n + 3) + 6 * a * e[2] - 4 * i * e[3], e[3] += o * i * (n - 2) - 3 * i * e[2], e[2] += o, n == 1 ? e[5] = e[6] = t : (t < e[5] && (e[5] = t), t > e[6] && (e[6] = t));
		}
	};
}
var F = "addEventListener", I = Math, ye;
function L() {
	return /Android/i.test(navigator.userAgent);
}
function R(e, t, n = !1, r) {
	let i = P(), a = !1, o;
	return C(() => {
		r ||= document.body, r[F](e, (e) => {
			(!a || n) && (o = e.timeStamp, a = !0);
		}), r[F](t, (e) => {
			a &&= (i.add(e.timeStamp - o), !1);
		});
	}), i.s;
}
function be(e) {
	let t = [];
	for (let n = 0; n < e.length; n++) t.push(0), document[F](e[n], (e) => t[n]++);
	return t;
}
function xe() {
	let e = [
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0
	], t = {
		8: 1,
		46: 1,
		9: 2,
		45: 3,
		17: 4,
		13: 5,
		37: 6,
		38: 6,
		39: 6,
		40: 6,
		33: 7,
		34: 7
	};
	return document[F]("keydown", (n) => {
		let r = n.keyCode;
		t[r] ? e[t[r]]++ : r >= 112 && r <= 123 && e[0]++;
	}), e;
}
function z(e, t, n, r) {
	return I.sqrt(I.pow(e - t, 2) + I.pow(n - r, 2));
}
function B(e, t, n) {
	return I.sqrt(I.pow(e, 2) + I.pow(t, 2) + I.pow(n, 2));
}
function V(e, t) {
	let n = t - e;
	return n += n > 180 ? -360 : n < -180 ? 360 : 0, n;
}
var Se = class {
	constructor(e) {
		this.rn = 0, this.i = 0, this.smel = {
			n: 0,
			ts: 0,
			d: 0
		};
		let t = "mouse", n = this.smel, r = (e) => {
			n.n || (n.fts = e.timeStamp, n.fxy = [
				e.clientX,
				e.clientY,
				e.screenX,
				e.screenY
			]), n.n++, e.type === t + "leave" && (n.d += e.timeStamp - n.ts), n.ts = e.timeStamp, n.xy = [e.clientX, e.clientY];
		}, i = document;
		C(() => {
			let e = i.body;
			e[F](t + "enter", r), e[F](t + "leave", r);
		}), this.bh = {
			onoff: {
				kdu: R("keydown", "keyup"),
				cse: R("compositionstart", "compositionend"),
				mdu: R(t + "down", t + "up"),
				mle: R(t + "leave", t + "enter"),
				med: R(t + "enter", t + "down", !0),
				semd: R("scrollend", t + "down", !0, i),
				se: R("scroll", "scrollend", !1, i),
				pdc: R("pointerdown", "pointercancel", !0),
				mmc: R(t + "move", "click", !0),
				tse: R("touchstart", "touchend"),
				fikd: R("focusin", "keydown", !0)
			},
			nev: be([
				t + "out",
				"pointercancel",
				"focus",
				"focusin",
				"blur",
				"visibilitychange",
				"copy",
				"paste",
				"cut",
				"contextmenu",
				"click",
				"auxclick",
				"wheel",
				"resize"
			]),
			nk: xe(),
			mov: this.setupMovementMetrics(),
			dm: this.setupMotionMetrics(),
			do: this.setupOrientationMetrics()
		}, this.dep = e.disableEvalPatching || !1, this.takeTraceRecords = ve(e);
	}
	setupMovementMetrics() {
		let e, t = [], n = P(), r = P(), i = P(), a = {
			t: n.s,
			v: i.s,
			d: r.s,
			ns: 0
		}, o = () => {
			let o = t[t.length - 1];
			if (t.length >= 200 || o && (o[0] && this.tm.timeStamp === o[1] || !o[0] && this.mm.timeStamp === o[1])) {
				if (clearInterval(e), e = void 0, t.length === 1) {
					a.ns++, t = [];
					return;
				}
				let s = t[0];
				n.add(o[1] - s[1]), r.add(z(o[2], s[2], o[3], s[3]));
				for (let e = 1; e < t.length; e++) {
					let n = t[e], r = t[e - 1], a = z(n[2], r[2], n[3], r[3]) * 1e3, o = n[1] - r[1];
					i.add(a / o);
				}
				t = [];
				return;
			}
			let s = 0;
			if (o ? s = o[0] : this.mm && this.tm ? s = this.mm.timeStamp > this.tm.timeStamp ? 0 : 1 : this.mm || (s = 1), s) {
				let e = this.tm.touches[0];
				e && t.push([
					1,
					this.tm.timeStamp,
					e.screenX,
					e.screenY
				]);
			} else t.push([
				0,
				this.mm.timeStamp,
				this.mm.screenX,
				this.mm.screenY
			]);
		}, s = -1;
		return C(() => {
			let t = document.body;
			t[F]("mousemove", (t) => {
				this.mm = t, e === void 0 && (o(), e = setInterval(o, 50));
			}), t[F]("touchmove", (t) => {
				this.tm = t;
				let n = t.touches[0];
				if (n) {
					let e = n.radiusX + n.radiusY * 1.234;
					e !== s && (s = e, this.rn++);
				}
				e === void 0 && (o(), e = setInterval(o, 50));
			});
		}), a;
	}
	setupMotionMetrics() {
		let e = P(), t = P(), n = {
			n: 0,
			ts: 0,
			ac: e.s,
			rr: t.s,
			i: 0,
			g: !1
		};
		return L() && window[F]("devicemotion", (r) => {
			n.ts = r.timeStamp, n.i = r.interval, n.g = !r.acceleration;
			let i = r.acceleration || r.accelerationIncludingGravity;
			i && e.add(B(i.x, i.y, i.z));
			let a = r.rotationRate;
			a && t.add(B(a.alpha, a.beta, a.gamma));
		}), n;
	}
	setupOrientationMetrics() {
		let e = P(), t = P(), n = {
			fts: 0,
			ts: 0,
			gd: e.s,
			bd: t.s
		};
		if (!L()) return n;
		let r;
		return window[F]("deviceorientation", (i) => {
			i.gamma != null && i.beta != null && i.alpha != null && (n.ts = i.timeStamp, n.a = i.alpha, n.b = i.beta, n.g = i.gamma, r ? (e.add(V(i.gamma, n.g)), t.add(V(n.b, i.beta))) : (n.fts = n.ts, r = !0));
		}), n;
	}
	gmm() {
		let e = this.mm;
		return e && {
			xy: [
				e.clientX,
				e.clientY,
				e.screenX,
				e.screenY,
				e.offsetX,
				e.offsetY,
				e.pageX,
				e.pageY,
				e.movementX,
				e.movementY
			],
			ts: e.timeStamp
		};
	}
	gtm() {
		let e = this.tm, t = e && e.touches, n = t && t[0];
		return e && n && {
			id: n.identifier,
			xy: [
				n.clientX,
				n.clientY,
				n.screenX,
				n.screenY,
				n.pageX,
				n.pageY
			],
			r: [
				n.radiusX,
				n.radiusX,
				n.rotationAngle,
				n.force
			],
			n: t.length,
			ts: e.timeStamp,
			rn: this.rn
		};
	}
	get(e) {
		let t = document.body, n = window, r = n.performance;
		return {
			v: 1,
			i: ++this.i,
			hl: history.length,
			fe: !!window.frameElement,
			dep: this.dep,
			wid: e,
			sc: parseInt(h(!1)),
			sid: g(),
			conv: 0,
			t: {
				pnow: M(),
				pto: r && r.timeOrigin || 0,
				ts: Date.now()
			},
			dims: {
				d: [
					n.innerWidth,
					n.innerHeight,
					n.outerWidth,
					n.outerHeight,
					n.screenX,
					n.screenY,
					n.pageXOffset,
					n.pageYOffset,
					t.clientWidth,
					t.clientHeight
				],
				dpr: n.devicePixelRatio
			},
			mel: this.smel,
			mm: this.gmm(),
			tm: this.gtm(),
			bh: this.bh,
			stack: (/* @__PURE__ */ Error()).stack || "",
			trc: this.takeTraceRecords()
		};
	}
};
function Ce(e) {
	return ye ||= new Se(e);
}
var H = (e) => e.map((e) => `https://${e}.frcapi.com`).join(","), U = {
	eu: H([
		"eu",
		"eu0",
		"eu1"
	]),
	global: H([
		"global",
		"global0",
		"global1"
	])
}, W = (e) => e.split(",").map((e) => e.trim()).filter((e) => !!e), we = (e) => W(U[e] || e);
function G(e) {
	let t = W(e || U.global).reduce((e, t) => e.concat(we(t)), []).map(i);
	return t.length > 0 ? t : W(U.global).map(i);
}
function K(e) {
	let t = document.querySelector(`meta[name="frc-${e}"]`);
	return t ? t.content : void 0;
}
function Te() {
	return !!K("disable-eval-patching");
}
function Ee() {
	return K("guard-context");
}
function q() {
	let e = K("api-endpoint");
	if (e) return e;
	let t = document.currentScript;
	if (t) {
		let e = t.dataset.frcApiEndpoint;
		if (e) return e;
	}
	let n = document.querySelector(".frc-captcha[data-api-endpoint]");
	if (n) {
		let e = n.dataset.apiEndpoint;
		if (e) return e;
	}
}
function De() {
	let e = window.Intl;
	if (!e || !e.DateTimeFormat) return;
	let t = new e.DateTimeFormat();
	if (t && t.resolvedOptions) return t.resolvedOptions().timeZone;
}
var J = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
function Oe(e) {
	return window.TextEncoder ? ke(new TextEncoder().encode(e)) : "";
}
function ke(e) {
	let t = e.length, n = "";
	for (let r = 0; r < t; r += 3) {
		let t = e[r + 0], i = e[r + 1], a = e[r + 2], o = "";
		o += J.charAt(t >>> 2), o += J.charAt((t & 3) << 4 | i >>> 4), o += J.charAt((i & 15) << 2 | a >>> 6), o += J.charAt(a & 63), n += o;
	}
	return t % 3 == 2 ? n = n.substring(0, n.length - 1) + "=" : t % 3 == 1 && (n = n.substring(0, n.length - 2) + "=="), n;
}
var Ae = "frc-risk-intelligence-token", je = class {
	constructor(e) {
		if (this.timeout = null, this.data = null, this.e = e.element, !this.e) throw Error("No element provided for mounting Risk Intelligence handle.");
		this.e.frcRiskIntelligence = this, this.formFieldName = e.formFieldName === void 0 ? Ae : e.formFieldName, this.formFieldName !== null && (this.hiddenFormEl = S(this.e, this.formFieldName)), this.startMode = e.startMode || "focus", this.requestRiskIntelligence = e.riskIntelligence, this.handleStartMode();
	}
	handleStartMode() {
		if (this.startMode === "none") console.warn("Risk Intelligence <div> found with data-start=\"none\" (no-op), skipping...", this.e);
		else if (this.startMode === "auto") this.request();
		else {
			let e = b(this.e);
			e ? x(e, () => {
				this.request();
			}) : console.warn("Risk Intelligence <div> with startMode of \"focus\" found without a parent <form> element, skipping...", this.e);
		}
	}
	request() {
		this.requestRiskIntelligence().then((e) => {
			this.timeout !== null && clearTimeout(this.timeout), this.timeout = setTimeout(() => {
				w(this.e, { name: "frc:riskintelligence.expire" });
			}, e.expiresAt - Date.now()), this.data = {
				token: e.token,
				expiresAt: e.expiresAt
			}, this.hiddenFormEl && (this.hiddenFormEl.value = e.token), w(this.e, {
				name: "frc:riskintelligence.complete",
				token: e.token,
				expiresAt: e.expiresAt
			});
		}).catch((e) => {
			w(this.e, {
				name: "frc:riskintelligence.error",
				error: {
					code: e.code,
					detail: e.detail
				}
			});
		});
	}
	getData() {
		return this.data;
	}
	getElement() {
		return this.e;
	}
	addEventListener(e, t, n) {
		this.e.addEventListener(e, t, n);
	}
	removeEventListener(e, t, n) {
		this.e.removeEventListener(e, t, n);
	}
};
function Me(e) {
	return e.length === 0 ? [] : [e[0]].concat(l(e.slice(1)));
}
function Y(e, t) {
	let n = t.length;
	if (n === 0) return -1;
	if (e <= 2 || n === 1) return 0;
	let r = n - 1, i = e - 2;
	return i <= r ? i : 1 + Math.floor(Math.random() * r);
}
function Ne(e, n, r) {
	let a = i(e), o = i(n), s = t(e, a) ? e.slice(a.length) : e;
	s.length === 0 ? s = "/" : t(s, "/") || (s = "/" + s);
	let c = s.indexOf("?") === -1 ? "?" : "&";
	return o + s + c + "retry=" + r;
}
var Pe = "/api/v2/captcha/agent", Fe = "/api/v2/captcha/widget", X = "FrcFrameId", Ie = "FrcAgentOriginKey", Le = 1296e5, Re = [
	3e3,
	5e3,
	8e3,
	18e3,
	38e3
], Z = Re.length, Q, $ = 0, ze = class {
	constructor(t = {}) {
		if (this.agents = /* @__PURE__ */ new Map(), this.agentState = /* @__PURE__ */ new Map(), this.widgets = /* @__PURE__ */ new Map(), this._attached = e(), this.attached = this._attached.promise, this.riskIntelligencePromises = /* @__PURE__ */ new Map(), this.clearRiskIntelligencePromises = /* @__PURE__ */ new Map(), this.riskIntelligenceHandles = [], this.apiEndpoint = t.apiEndpoint, this.guardContext = t.guardContext || Ee(), Q ||= new s(), Q.listen((e) => this.onReceiveMessage(e)), this.bus = Q, $++, $ > 1 && console.warn("Multiple Friendly Captcha SDKs created, this is not recommended. Please use a single SDK instance."), this.signals = Ce({ disableEvalPatching: t.disableEvalPatching || Te() }), t.startAgent) {
			let e = G(this.apiEndpoint || q()), t = this.getRetryOrigins(e);
			this.ensureAgentIFrame(t);
		}
		this.setupPeriodicRefresh();
	}
	getRetryOrigins(e) {
		return Me(e);
	}
	onReceiveMessage(e) {
		if (e.type === "root_set_response") {
			let t = this.widgets.get(e.widget_id);
			if (!t) {
				$ === 1 && console.warn(`Received set response message for widget ${e.widget_id} that doesn't exist`);
				return;
			}
			t.setState(e);
		} else if (t(e.type, "root_store")) this.handleStoreMessage(e);
		else if (e.type === "root_signals_get") this.handleSignalsGetMessage(e);
		else if (e.type === "widget_language_change") this.handleWidgetLanguageChange(e);
		else if (e.type === "widget_reset") {
			let t = this.widgets.get(e.from_id);
			if (!t) {
				$ === 1 && console.warn(`Received reset message for widget ${e.from_id} that doesn't exist`);
				return;
			}
			t.reset({ trigger: "widget" });
		} else t(e.type, "root_risk_intelligence") && this.handleRiskIntelligenceMessage(e);
	}
	handleRiskIntelligenceMessage(e) {
		if (e.type === "root_risk_intelligence_generate_reply") {
			let t = this.riskIntelligencePromises.get(e.uid);
			t ? (e.data ? t.resolve(e.data) : e.error ? t.reject(e.error) : console.warn("Received risk intelligence generate reply message with no data"), this.riskIntelligencePromises.delete(e.uid)) : console.warn("Received risk intelligence generate reply message with no promise to resolve");
		} else if (e.type === "root_risk_intelligence_clear_reply") {
			let t = this.clearRiskIntelligencePromises.get(e.uid);
			t ? e.error ? t.reject(e.error) : t.resolve() : console.warn("Received risk intelligence clear reply message with no promise to resolve"), this.clearRiskIntelligencePromises.delete(e.uid);
		}
	}
	handleWidgetLanguageChange(e) {
		let t = this.widgets.get(e.from_id);
		if (!t) {
			$ === 1 && console.warn(`Received language change message for widget ${e.from_id} that doesn't exist`);
			return;
		}
		let n = t.getElement(), r = n.querySelector("iframe");
		r && (r.title = ae(e.language));
		let i = n.querySelector(".frc-banner");
		if (i) {
			let t = i.style;
			D(e.language) ? (t.left = "6px", t.right = "auto") : (t.left = "auto", t.right = "6px");
		}
	}
	handleSignalsGetMessage(e) {
		let t = this.signals.get(e.widget_id);
		this.bus.send({
			type: "root_signals_get_reply",
			from_id: "",
			to_id: e.from_id,
			_frc: 1,
			rid: e.rid,
			value: t
		});
	}
	handleStoreMessage(e) {
		let t = e.from_id, n = this.agentState.get(t);
		if (!n) {
			console.error(`Store not found ${t}`);
			return;
		}
		e.type === "root_store_get" ? this.bus.send({
			type: "root_store_get_reply",
			from_id: "",
			to_id: t,
			_frc: 1,
			rid: e.rid,
			value: n.store.get(e.key),
			sa: !0
		}) : e.type === "root_store_set" && (n.store.set(e.key, e.value), this.bus.send({
			type: "root_store_set_reply",
			from_id: "",
			to_id: t,
			_frc: 1,
			rid: e.rid,
			sa: !0
		}));
	}
	ensureAgentIFrame(e) {
		let t = 1, n = e[Y(t, e)], r = n + Pe, i = Z, a = this.agents.get(n);
		if (a && a.dataset[X]) return a.dataset[X];
		let o = document.getElementsByClassName(k);
		for (let e = 0; e < o.length; e++) {
			let t = o[e];
			if (t.dataset[Ie] === n && t.dataset[X]) return this.agents.set(n, t), t.dataset[X];
		}
		let s = "a_" + c(12), l = le(this, s, r, this.guardContext);
		l.dataset[Ie] = n;
		let u = l.src, d = n;
		this.agents.set(n, l), this.agentState.set(s, {
			store: new _(n),
			origin: n
		}), document.body.appendChild(l);
		let f = () => {
			this.bus.registerTargetIFrame("agent", s, l, this.getRetryTimeout(t)).then((r) => {
				if (this.agents.get(n) === l && r === "timeout") {
					if (t >= i) {
						console.error(`[Friendly Captcha] Failed to load agent iframe after ${t - 1} retries.`), l.remove(), this.agents.delete(n);
						return;
					}
					let r = t + 1;
					d = e[Y(r, e)] || d, console.warn("[Friendly Captcha] Retrying agent iframe load."), l.src = Ne(u, d, r - 1), t = r, f();
				}
			});
		};
		return f(), s;
	}
	setupPeriodicRefresh() {
		let e = 1;
		setInterval(() => {
			let t = "&expire=" + e++;
			this.agents.forEach((e, n) => {
				e.src += t;
			}), this.widgets.forEach((e, n) => {
				let r = e.getElement().querySelector("iframe");
				r.src += t;
			});
		}, Le);
	}
	getRetryTimeout(e) {
		return Re[Math.min(Math.max(e, 1), Z) - 1];
	}
	attach(e) {
		let [t, n] = y();
		for (let e = 0; e < n.length; e++) {
			let t = n[e];
			if (t && !t.frcRiskIntelligence) {
				let e = t.dataset, n = e.sitekey;
				if (!n) {
					console.warn("Risk Intelligence <div> found with no sitekey, skipping...", t);
					continue;
				}
				this.riskIntelligenceHandles.push(new je({
					element: t,
					formFieldName: e.formFieldName,
					startMode: e.start,
					riskIntelligence: () => this.riskIntelligence({
						sitekey: n,
						apiEndpoint: e.apiEndpoint
					})
				}));
			}
		}
		e === void 0 && (e = t), Array.isArray(e) || e instanceof NodeList || (e = [e]);
		let r = [];
		for (let t = 0; t < e.length; t++) {
			let n = e[t];
			if (n && !n.frcWidget) {
				let e = n.dataset, t = {
					element: n,
					sitekey: e.sitekey,
					formFieldName: e.formFieldName,
					apiEndpoint: e.apiEndpoint,
					language: e.lang,
					theme: e.theme,
					startMode: e.start
				};
				r.push(this.createWidget(t));
			}
		}
		let i = this.getAllWidgets();
		return this._attached.resolve(i), this.attached = Promise.resolve(i), r;
	}
	createWidget(t) {
		let n = G(t.apiEndpoint || this.apiEndpoint || q()), r = this.getRetryOrigins(n), a = 1;
		this.bus.addOrigins(n);
		let o = r[Y(a, r)] || n[0], s = this.ensureAgentIFrame(r), l = "w_" + c(12), u = (e) => {
			let t = {
				from_id: l,
				to_id: s,
				_frc: 1
			};
			this.bus.send(N(t, e));
		}, d = {
			onDestroy: () => {
				u({ type: "root_destroy_widget" }), this.bus.removeTarget(l), this.widgets.delete(l), t.element.innerHTML = "", ne(t.element);
			},
			onReset: () => {
				u({ type: "root_reset_widget" });
			},
			onTrigger: (e) => {
				u({
					type: "root_trigger_widget",
					trigger: e.trigger
				});
			}
		}, f = e(), p = new ge({
			id: l,
			createOpts: t,
			callbacks: d,
			registered: f.promise
		});
		this.widgets.set(l, p);
		let m = ue(s, l, o + Fe, t, this.guardContext), h = Z, g = m.src, _ = o, v = de(t);
		te(t.element), fe(t);
		let y = j(t);
		D(y) && (t.element.dir = "rtl");
		let b = v.style;
		v.textContent = O(y, "connecting");
		function x(e) {
			let n = Oe(JSON.stringify({
				sdk_v: "1.1.1",
				sitekey: t.sitekey || "",
				retry: a + "",
				endpoint: _,
				ua: navigator.userAgent,
				tz: De() || ""
			})), r = ".ERROR.UNREACHABLE";
			n && (r += "~" + n), p.setState({
				state: "error",
				response: r,
				error: {
					code: "network_error",
					detail: e
				}
			});
		}
		let S = () => {
			this.bus.registerTargetIFrame("widget", l, m, this.getRetryTimeout(a)).then((e) => {
				if (!p.isDestroyed) {
					if (e === "timeout") {
						if (a >= h) {
							console.error(`[Friendly Captcha] Failed to load widget iframe after ${a - 1} retries.`), x("Widget load timeout, stopped retrying"), b.borderColor = "#f00", b.fontSize = "12px", pe(v, i(m.src), y);
							return;
						}
						let e = a + 1, t = Y(e, r);
						_ = r[t] || _, b.backgroundColor = "#fee", b.color = "#222", v.textContent = O(y, "retrying") + ` (${a})`, console.warn(`[Friendly Captcha] Retrying widget ${l} iframe load.`), x("Widget load timeout, will retry"), m.src = Ne(g, _, e - 1), a = e, S();
					} else e === "registered" && (t.element.removeChild(v), m.style.display = "");
				}
			});
		};
		return S(), f.resolve(), p;
	}
	riskIntelligence(t) {
		let n = G(t.apiEndpoint || this.apiEndpoint || q()), r = this.getRetryOrigins(n);
		this.bus.addOrigins(n);
		let i = this.ensureAgentIFrame(r), a = c(8);
		this.bus.send({
			type: "root_risk_intelligence_generate",
			to_id: i,
			from_id: "",
			_frc: 1,
			sitekey: t.sitekey,
			bypassCache: t.bypassCache || !1,
			uid: a
		});
		let o = e();
		return this.riskIntelligencePromises.set(a, o), o.promise;
	}
	clearRiskIntelligence(t) {
		let n = G(t?.apiEndpoint || this.apiEndpoint || q()), r = this.getRetryOrigins(n);
		this.bus.addOrigins(n);
		let i = this.ensureAgentIFrame(r), a = c(8);
		this.bus.send({
			type: "root_risk_intelligence_clear",
			to_id: i,
			from_id: "",
			_frc: 1,
			sitekey: t?.sitekey,
			uid: a
		});
		let o = e();
		return this.clearRiskIntelligencePromises.set(a, o), o.promise;
	}
	getAllWidgets() {
		let e = [];
		return this.widgets.forEach((t) => {
			e.push(t);
		}), e;
	}
	getWidgetById(e) {
		return this.widgets.get(e);
	}
	getAllRiskIntelligenceHandles() {
		let e = [];
		return this.riskIntelligenceHandles.forEach((t) => {
			e.push(t);
		}), e;
	}
	clear() {
		this.widgets.forEach((e) => {
			e.destroy();
		}), this.agents.forEach((e) => {
			e.remove();
		}), this.agents.clear();
	}
}, Be = "frc:widget.statechange", Ve = "frc:widget.complete", He = "frc:widget.expire", Ue = "frc:widget.error", We = "frc:widget.reset", Ge = "frc:riskintelligence.complete", Ke = "frc:riskintelligence.error", qe = "frc:riskintelligence.expire";
//#endregion
export { Ge as FRCRiskIntelligenceCompleteEventName, Ke as FRCRiskIntelligenceErrorEventName, qe as FRCRiskIntelligenceExpireEventName, Ve as FRCWidgetCompleteEventName, Ue as FRCWidgetErrorEventName, He as FRCWidgetExpireEventName, We as FRCWidgetResetEventName, Be as FRCWidgetStateChangeEventName, ze as FriendlyCaptchaSDK };
