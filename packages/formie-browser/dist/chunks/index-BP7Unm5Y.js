const c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
const g = new Uint8Array(256);
for (let i = 0; i < c.length; i++)
  g[c.charCodeAt(i)] = i;
function f(i) {
  const t = i.length;
  let e = "";
  for (let r = 0; r < t; r += 3) {
    const o = i[r + 0], n = i[r + 1], s = i[r + 2];
    let a = "";
    a += c.charAt(o >>> 2), a += c.charAt((o & 3) << 4 | n >>> 4), a += c.charAt((n & 15) << 2 | s >>> 6), a += c.charAt(s & 63), e += a;
  }
  return t % 3 === 2 ? e = e.substring(0, e.length - 1) + "=" : t % 3 === 1 && (e = e.substring(0, e.length - 2) + "=="), e;
}
function k(i) {
  const t = i.length;
  let e = t * 3 >>> 2;
  i.charCodeAt(t - 1) === 61 && e--, i.charCodeAt(t - 2) === 61 && e--;
  const r = new Uint8Array(e);
  for (let o = 0, n = 0; o < t; o += 4) {
    const s = g[i.charCodeAt(o + 0)], a = g[i.charCodeAt(o + 1)], u = g[i.charCodeAt(o + 2)], h = g[i.charCodeAt(o + 3)];
    r[n++] = s << 2 | a >> 4, r[n++] = (a & 15) << 4 | u >> 2, r[n++] = (u & 3) << 6 | h & 63;
  }
  return r;
}
var y = ".frc-captcha *{margin:0;padding:0;border:0;text-align:initial;border-radius:0;filter:none!important;transition:none!important;font-weight:400;font-size:14px;line-height:1.2;text-decoration:none;background-color:initial;color:#222}.frc-captcha{position:relative;min-width:250px;max-width:312px;border:1px solid #f4f4f4;padding-bottom:12px;background-color:#fff}.frc-captcha b{font-weight:700}.frc-container{display:flex;align-items:center;min-height:52px}.frc-icon{fill:#222;stroke:#222;flex-shrink:0;margin:8px 8px 0}.frc-icon.frc-warning{fill:#c00}.frc-success .frc-icon{animation:1s ease-in both frc-fade-in}.frc-content{white-space:nowrap;display:flex;flex-direction:column;margin:4px 6px 0 0;overflow-x:auto;flex-grow:1}.frc-banner{position:absolute;bottom:0;right:6px;line-height:1}.frc-banner *{font-size:10px;opacity:.8;text-decoration:none}.frc-progress{-webkit-appearance:none;-moz-appearance:none;appearance:none;margin:3px 0;height:4px;border:none;background-color:#eee;color:#222;width:100%;transition:.5s linear}.frc-progress::-webkit-progress-bar{background:#eee}.frc-progress::-webkit-progress-value{background:#222}.frc-progress::-moz-progress-bar{background:#222}.frc-button{cursor:pointer;padding:2px 6px;background-color:#f1f1f1;border:1px solid transparent;text-align:center;font-weight:600;text-transform:none}.frc-button:focus{border:1px solid #333}.frc-button:hover{background-color:#ddd}.frc-captcha-solution{display:none}.frc-err-url{text-decoration:underline;font-size:.9em}.frc-rtl{direction:rtl}.frc-rtl .frc-content{margin:4px 0 0 6px}.frc-banner.frc-rtl{left:6px;right:auto}.dark.frc-captcha{color:#fff;background-color:#222;border-color:#333}.dark.frc-captcha *{color:#fff}.dark.frc-captcha button{background-color:#444}.dark .frc-icon{fill:#fff;stroke:#fff}.dark .frc-progress{background-color:#444}.dark .frc-progress::-webkit-progress-bar{background:#444}.dark .frc-progress::-webkit-progress-value{background:#ddd}.dark .frc-progress::-moz-progress-bar{background:#ddd}@keyframes frc-fade-in{from{opacity:0}to{opacity:1}}";
const Q = 1, B = 128, I = '<circle cx="12" cy="12" r="8" stroke-width="3" stroke-dasharray="15 10" fill="none" stroke-linecap="round" transform="rotate(0 12 12)"><animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="0.9s" values="0 12 12;360 12 12"/></circle>', C = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
function l(i, t, e, r, o, n, s, a = !1, u, h) {
  return `<div class="frc-container${h ? " " + h : ""}${t ? " frc-rtl" : ""}">
<svg class="frc-icon"${r ? ' aria-hidden="true"' : ""} role="img" xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 24 24">${e}</svg>
<div class="frc-content">
    <span class="frc-text" ${u ? `data-debug="${u}"` : ""}>${o}</span>
    ${s ? `<button type="button" class="frc-button">${s}</button>` : ""}
    ${a ? '<progress class="frc-progress" value="0">0%</progress>' : ""}
</div>
</div><span class="frc-banner${t ? " frc-rtl" : ""}"><a lang="en" href="https://friendlycaptcha.com/" rel="noopener" target="_blank"><b>Friendly</b>Captcha ⇗</a></span>
${i === "-" ? "" : `<input name="${i}" class="frc-captcha-solution" type="hidden" value="${n}">`}`;
}
function w(i, t) {
  return l(i, t.rtl, '<path d="M17,11c0.34,0,0.67,0.04,1,0.09V6.27L10.5,3L3,6.27v4.91c0,4.54,3.2,8.79,7.5,9.82c0.55-0.13,1.08-0.32,1.6-0.55 C11.41,19.47,11,18.28,11,17C11,13.69,13.69,11,17,11z"/><path d="M17,13c-2.21,0-4,1.79-4,4c0,2.21,1.79,4,4,4s4-1.79,4-4C21,14.79,19.21,13,17,13z M17,14.38"/>', !0, t.text_ready, ".UNSTARTED", t.button_start, !1);
}
function E(i, t) {
  return l(i, t.rtl, I, !0, t.text_fetching, ".FETCHING", void 0, !0);
}
function v(i, t) {
  return l(i, t.rtl, I, !0, t.text_solving, ".UNFINISHED", void 0, !0);
}
function z(i, t, e, r) {
  const o = `${r.t.toFixed(0)}s (${(r.h / r.t * 1e-3).toFixed(0)}K/s)${r.solver === Q ? " JS Fallback" : ""}`;
  return l(i, t.rtl, `<title>${t.text_completed_sr}</title><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"></path>`, !1, t.text_completed, e, void 0, !1, o, "frc-success");
}
function N(i, t) {
  return l(i, t.rtl, C, !0, t.text_expired, ".EXPIRED", t.button_restart);
}
function _(i, t, e, r = !0, o = !1) {
  return l(i, t.rtl, C, !0, `<b>${t.text_error}</b><br>${e}`, o ? ".HEADLESS_ERROR" : ".ERROR", r ? t.button_retry : void 0);
}
function D(i = null) {
  if (!document.querySelector("#frc-style")) {
    const t = document.createElement("style");
    t.id = "frc-style", t.innerHTML = y, i && t.setAttribute("nonce", i), document.head.appendChild(t);
  }
}
function R(i, t) {
  const e = i.querySelector(".frc-progress"), r = (t.i + 1) / t.n;
  e && (e.value = r, e.innerText = (r * 100).toFixed(1) + "%", e.title = t.i + 1 + "/" + t.n + " (" + (t.h / t.t * 1e-3).toFixed(0) + "K/s)");
}
function S(i) {
  for (; i.tagName !== "FORM"; )
    if (i = i.parentElement, !i)
      return null;
  return i;
}
function L(i, t) {
  i.addEventListener("focusin", t, { once: !0, passive: !0 });
}
let A, p;
typeof navigator < "u" && typeof navigator.userAgent == "string" && (A = navigator, p = A.userAgent.toLowerCase());
function G() {
  return (
    //tell-tale bot signs
    p.indexOf("headless") !== -1 || A.appVersion.indexOf("Headless") !== -1 || p.indexOf("bot") !== -1 || // http://www.useragentstring.com/pages/useragentstring.php?typ=Browser
    p.indexOf("crawl") !== -1 || // Only IE5 has two distributions that has this on windows NT.. so yeah.
    A.webdriver === !0 || !A.language || A.languages !== void 0 && !A.languages.length
  );
}
function j(i) {
  return i > 255 ? i = 255 : i < 0 && (i = 0), Math.pow(2, (255.999 - i) / 8) >>> 0;
}
const H = 13, M = 14, F = 15;
function U(i, t) {
  const e = [];
  for (let r = 0; r < t; r++) {
    const o = new Uint8Array(B);
    o.set(i), o[120] = r, e.push(o);
  }
  return e;
}
function T(i) {
  const t = i.split("."), e = t[1], r = k(e);
  return {
    signature: t[0],
    base64: e,
    buffer: r,
    n: r[M],
    threshold: j(r[F]),
    expiry: r[H] * 3e5
  };
}
async function V(i, t, e) {
  const r = i.split(",");
  for (let o = 0; o < r.length; o++)
    try {
      const n = await b(r[o] + "?sitekey=" + t, { headers: [["x-frc-client", "js-0.9.19"]], mode: "cors" }, 2);
      if (n.ok)
        return (await n.json()).data.puzzle;
      {
        let s;
        try {
          s = await n.json();
        } catch {
        }
        if (s && s.errors && s.errors[0] === "endpoint_not_enabled")
          throw Error(`Endpoint not allowed (${n.status})`);
        if (o === r.length - 1)
          throw Error(`Response status ${n.status} ${n.statusText} ${s ? s.errors : ""}`);
      }
    } catch (n) {
      console.error("[FRC Fetch]:", n);
      const s = new Error(`${e.text_fetch_error} <a class="frc-err-url" href="${r[o]}">${r[o]}</a>`);
      throw s.rawError = n, s;
    }
  throw Error("Internal error");
}
async function b(i, t, e) {
  let r = 1e3;
  return fetch(i, t).catch(async (o) => {
    if (e === 0)
      throw o;
    return await new Promise((n) => setTimeout(n, r)), r *= 4, b(i, t, e - 1);
  });
}
const P = {
  text_init: "Initializing...",
  text_ready: "Anti-Robot Verification",
  button_start: "Click to start verification",
  text_fetching: "Fetching Challenge",
  text_solving: "Verifying you are human...",
  text_completed: "I am human",
  text_completed_sr: "Automatic spam check completed",
  text_expired: "Anti-Robot verification expired",
  button_restart: "Restart",
  text_error: "Verification failed",
  button_retry: "Retry",
  text_fetch_error: "Failed to connect to"
}, K = {
  text_init: "Chargement...",
  text_ready: "Vérification Anti-Robot",
  button_start: "Clique ici pour vérifier",
  text_fetching: "Chargement du défi",
  text_solving: "Nous vérifions que vous n'êtes pas un robot...",
  text_completed: "Je ne suis pas un robot",
  text_completed_sr: "Vérification automatique des spams terminée",
  text_expired: "Vérification anti-robot expirée",
  button_restart: "Redémarrer",
  text_error: "Échec de la vérification",
  button_retry: "Recommencer",
  text_fetch_error: "Problème de connexion avec"
}, Y = {
  text_init: "Initialisierung...",
  text_ready: "Anti-Roboter-Verifizierung",
  button_start: "Hier klicken",
  text_fetching: "Herausforderung laden...",
  text_solving: "Verifizierung, dass Sie ein Mensch sind...",
  text_completed: "Ich bin ein Mensch",
  text_completed_sr: "Automatische Spamprüfung abgeschlossen",
  text_expired: "Verifizierung abgelaufen",
  button_restart: "Erneut starten",
  text_error: "Verifizierung fehlgeschlagen",
  button_retry: "Erneut versuchen",
  text_fetch_error: "Verbindungsproblem mit"
}, O = {
  text_init: "Initializeren...",
  text_ready: "Anti-robotverificatie",
  button_start: "Klik om te starten",
  text_fetching: "Aan het laden...",
  text_solving: "Anti-robotverificatie bezig...",
  text_completed: "Ik ben een mens",
  text_completed_sr: "Automatische anti-spamcheck voltooid",
  text_expired: "Verificatie verlopen",
  button_restart: "Opnieuw starten",
  text_error: "Verificatie mislukt",
  button_retry: "Opnieuw proberen",
  text_fetch_error: "Verbinding mislukt met"
}, J = {
  text_init: "Inizializzazione...",
  text_ready: "Verifica Anti-Robot",
  button_start: "Clicca per iniziare",
  text_fetching: "Caricamento...",
  text_solving: "Verificando che sei umano...",
  text_completed: "Non sono un robot",
  text_completed_sr: "Controllo automatico dello spam completato",
  text_expired: "Verifica Anti-Robot scaduta",
  button_restart: "Ricomincia",
  text_error: "Verifica fallita",
  button_retry: "Riprova",
  text_fetch_error: "Problema di connessione con"
}, W = {
  text_init: "Inicializando...",
  text_ready: "Verificação Anti-Robô",
  button_start: "Clique para iniciar verificação",
  text_fetching: "Carregando...",
  text_solving: "Verificando se você é humano...",
  text_completed: "Eu sou humano",
  text_completed_sr: "Verificação automática de spam concluída",
  text_expired: "Verificação Anti-Robô expirada",
  button_restart: "Reiniciar",
  text_error: "Verificação falhou",
  button_retry: "Tentar novamente",
  text_fetch_error: "Falha de conexão com"
}, q = {
  text_init: "Inicializando...",
  text_ready: "Verificación Anti-Robot",
  button_start: "Haga clic para iniciar la verificación",
  text_fetching: "Cargando desafío",
  text_solving: "Verificando que eres humano...",
  text_completed: "Soy humano",
  text_completed_sr: "Verificación automática de spam completada",
  text_expired: "Verificación Anti-Robot expirada",
  button_restart: "Reiniciar",
  text_error: "Ha fallado la verificación",
  button_retry: "Intentar de nuevo",
  text_fetch_error: "Error al conectarse a"
}, $ = {
  text_init: "Inicialitzant...",
  text_ready: "Verificació Anti-Robot",
  button_start: "Fes clic per començar la verificació",
  text_fetching: "Carregant repte",
  text_solving: "Verificant que ets humà...",
  text_completed: "Soc humà",
  text_completed_sr: "Verificació automàtica de correu brossa completada",
  text_expired: "La verificació Anti-Robot ha expirat",
  button_restart: "Reiniciar",
  text_error: "Ha fallat la verificació",
  button_retry: "Tornar a provar",
  text_fetch_error: "Error connectant a"
}, X = {
  text_init: "開始しています...",
  text_ready: "アンチロボット認証",
  button_start: "クリックして認証を開始",
  text_fetching: "ロードしています",
  text_solving: "認証中...",
  text_completed: "私はロボットではありません",
  text_completed_sr: "自動スパムチェックが完了しました",
  text_expired: "認証の期限が切れています",
  button_restart: "再度認証を行う",
  text_error: "認証にエラーが発生しました",
  button_retry: "再度認証を行う",
  text_fetch_error: "接続ができませんでした"
}, Z = {
  text_init: "Aktiverer...",
  text_ready: "Jeg er ikke en robot",
  button_start: "Klik for at starte verifikationen",
  text_fetching: "Henter data",
  text_solving: "Kontrollerer at du er et menneske...",
  text_completed: "Jeg er et menneske.",
  text_completed_sr: "Automatisk spamkontrol gennemført",
  text_expired: "Verifikationen kunne ikke fuldføres",
  button_restart: "Genstart",
  text_error: "Bekræftelse mislykkedes",
  button_retry: "Prøv igen",
  text_fetch_error: "Forbindelsen mislykkedes"
}, tt = {
  text_init: "Инициализация...",
  text_ready: "АнтиРобот проверка",
  button_start: "Нажмите, чтобы начать проверку",
  text_fetching: "Получаю задачу",
  text_solving: "Проверяю, что вы человек...",
  text_completed: "Я человек",
  text_completed_sr: "Aвтоматическая проверка на спам завершена",
  text_expired: "Срок АнтиРоботной проверки истёк",
  button_restart: "Начать заново",
  text_error: "Ошибка проверки",
  button_retry: "Повторить ещё раз",
  text_fetch_error: "Ошибка подключения"
}, et = {
  text_init: "Aktiverar...",
  text_ready: "Jag är inte en robot",
  button_start: "Klicka för att verifiera",
  text_fetching: "Hämtar data",
  text_solving: "Kontrollerar att du är människa...",
  text_completed: "Jag är en människa",
  text_completed_sr: "Automatisk spamkontroll slutförd",
  text_expired: "Anti-robot-verifieringen har löpt ut",
  button_restart: "Börja om",
  text_error: "Verifiering kunde inte slutföras",
  button_retry: "Omstart",
  text_fetch_error: "Verifiering misslyckades"
}, rt = {
  text_init: "Başlatılıyor...",
  text_ready: "Anti-Robot Doğrulaması",
  button_start: "Doğrulamayı başlatmak için tıklayın",
  text_fetching: "Yükleniyor",
  text_solving: "Robot olmadığınız doğrulanıyor...",
  text_completed: "Ben bir insanım",
  text_completed_sr: "Otomatik spam kontrolü tamamlandı",
  text_expired: "Anti-Robot doğrulamasının süresi doldu",
  button_restart: "Yeniden başlat",
  text_error: "Doğrulama başarısız oldu",
  button_retry: "Tekrar dene",
  text_fetch_error: "Bağlantı başarısız oldu"
}, it = {
  text_init: "Προετοιμασία...",
  text_ready: "Anti-Robot Επαλήθευση",
  button_start: " Κάντε κλικ για να ξεκινήσει η επαλήθευση",
  text_fetching: " Λήψη πρόκλησης",
  text_solving: " Επιβεβαίωση ανθρώπου...",
  text_completed: "Είμαι άνθρωπος",
  text_completed_sr: " Ο αυτόματος έλεγχος ανεπιθύμητου περιεχομένου ολοκληρώθηκε",
  text_expired: " Η επαλήθευση Anti-Robot έληξε",
  button_restart: " Επανεκκίνηση",
  text_error: " Η επαλήθευση απέτυχε",
  button_retry: " Δοκιμάστε ξανά",
  text_fetch_error: " Αποτυχία σύνδεσης με"
}, ot = {
  text_init: "Ініціалізація...",
  text_ready: "Антиробот верифікація",
  button_start: "Натисніть, щоб розпочати верифікацію",
  text_fetching: "З’єднання",
  text_solving: "Перевірка, що ви не робот...",
  text_completed: "Я не робот",
  text_completed_sr: "Автоматична перевірка спаму завершена",
  text_expired: "Час вичерпано",
  button_restart: "Почати знову",
  text_error: "Верифікація не вдалась",
  button_retry: "Спробувати знову",
  text_fetch_error: "Не вдалось з’єднатись"
}, nt = {
  text_init: "Инициализиране...",
  text_ready: "Анти-робот проверка",
  button_start: "Щракнете, за да започнете проверката",
  text_fetching: "Предизвикателство",
  text_solving: "Проверяваме дали си човек...",
  text_completed: "Аз съм човек",
  text_completed_sr: "Автоматичната проверка за спам е завършена",
  text_expired: "Анти-Робот проверката изтече",
  button_restart: "Рестартирайте",
  text_error: "Неуспешна проверка",
  button_retry: "Опитайте пак",
  text_fetch_error: "Неуспешно свързване с"
}, st = {
  text_init: "Inicializace...",
  text_ready: "Ověření proti robotům",
  button_start: "Klikněte pro ověření",
  text_fetching: "Problém při načítání",
  text_solving: "Ověření, že jste člověk...",
  text_completed: "Jsem člověk",
  text_completed_sr: "Automatická kontrola spamu dokončena",
  text_expired: "Ověření proti robotům vypršelo",
  button_restart: "Restartovat",
  text_error: "Ověření se nezdařilo",
  button_retry: "Zkusit znovu",
  text_fetch_error: "Připojení se nezdařilo"
}, at = {
  text_init: "Inicializácia...",
  text_ready: "Overenie proti robotom",
  button_start: "Kliknite pre overenie",
  text_fetching: "Problém pri načítaní",
  text_solving: "Overenie, že ste človek...",
  text_completed: "Som človek",
  text_completed_sr: "Automatická kontrola spamu dokončená",
  text_expired: "Overenie proti robotom vypršalo",
  button_restart: "Reštartovať",
  text_error: "Overenie sa nepodarilo",
  button_retry: "Skúsiť znova",
  text_fetch_error: "Pripojenie sa nepodarilo"
}, x = {
  text_init: " Aktiverer...",
  text_ready: "Jeg er ikke en robot",
  button_start: "Klikk for å starte verifiseringen",
  text_fetching: "Henter data",
  text_solving: "Sjekker at du er et menneske...",
  text_completed: "Jeg er et menneske",
  text_completed_sr: "Automatisk spam-sjekk fullført",
  text_expired: "Verifisering kunne ikke fullføres",
  button_restart: "Omstart",
  text_error: "Bekreftelsen mislyktes",
  button_retry: "Prøv på nytt",
  text_fetch_error: "Tilkoblingen mislyktes"
}, At = {
  text_init: "Aktivoidaan...",
  text_ready: "En ole robotti",
  button_start: "Aloita vahvistus klikkaamalla",
  text_fetching: "Haetaan tietoja",
  text_solving: "Tarkistaa, että olet ihminen...",
  text_completed: "Olen ihminen",
  text_completed_sr: "Automaattinen roskapostin tarkistus suoritettu",
  text_expired: "Vahvistusta ei voitu suorittaa loppuun",
  button_restart: "Uudelleenkäynnistys",
  text_error: "Vahvistus epäonnistui",
  button_retry: "Yritä uudelleen",
  text_fetch_error: "Yhteys epäonnistui"
}, ct = {
  text_init: "Notiek inicializēšana...",
  text_ready: "Verifikācija, ka neesat robots",
  button_start: "Noklikšķiniet, lai sāktu verifikāciju",
  text_fetching: "Notiek drošības uzdevuma izgūšana",
  text_solving: "Notiek pārbaude, vai esat cilvēks...",
  text_completed: "Es esmu cilvēks",
  text_completed_sr: "Automātiska surogātpasta pārbaude pabeigta",
  text_expired: "Verifikācijas, ka neesat robots, derīgums beidzies",
  button_restart: "Restartēt",
  text_error: "Verifikācija neizdevās",
  button_retry: "Mēģināt vēlreiz",
  text_fetch_error: "Neizdevās izveidot savienojumu ar"
}, lt = {
  text_init: "Inicijuojama...",
  text_ready: "Patikrinimas, ar nesate robotas",
  button_start: "Spustelėkite patikrinimui pradėti",
  text_fetching: "Gavimo iššūkis",
  text_solving: "Tikrinama, ar esate žmogus...",
  text_completed: "Esu žmogus",
  text_completed_sr: "Automatinė patikra dėl pašto šiukšlių atlikta",
  text_expired: "Patikrinimas, ar nesate robotas, baigė galioti",
  button_restart: "Pradėti iš naujo",
  text_error: "Patikrinimas nepavyko",
  button_retry: "Kartoti",
  text_fetch_error: "Nepavyko prisijungti prie"
}, ut = {
  text_init: "Inicjowanie...",
  text_ready: "Weryfikacja antybotowa",
  button_start: "Kliknij, aby rozpocząć weryfikację",
  text_fetching: "Pobieranie",
  text_solving: "Weryfikacja, czy nie jesteś robotem...",
  text_completed: "Nie jestem robotem",
  text_completed_sr: "Zakończono automatyczne sprawdzanie spamu",
  text_expired: "Weryfikacja antybotowa wygasła",
  button_restart: "Uruchom ponownie",
  text_error: "Weryfikacja nie powiodła się",
  button_retry: "Spróbuj ponownie",
  text_fetch_error: "Nie udało się połączyć z"
}, gt = {
  text_init: "Initsialiseerimine...",
  text_ready: "Robotivastane kinnitus",
  button_start: "Kinnitamisega alustamiseks klõpsake",
  text_fetching: "Väljakutse toomine",
  text_solving: "Kinnitatakse, et sa oled inimene...",
  text_completed: "Ma olen inimene",
  text_completed_sr: "Automaatne rämpsposti kontroll on lõpetatud",
  text_expired: "Robotivastane kinnitus aegus",
  button_restart: "Taaskäivita",
  text_error: "Kinnitamine nurjus",
  button_retry: "Proovi uuesti",
  text_fetch_error: "Ühenduse loomine nurjus"
}, ht = {
  text_init: "Početno postavljanje...",
  text_ready: "Provjera protiv robota",
  button_start: "Kliknite za početak provjere",
  text_fetching: "Dohvaćanje izazova",
  text_solving: "Provjeravamo jeste li čovjek...",
  text_completed: "Nisam robot",
  text_completed_sr: "Automatska provjera je završena",
  text_expired: "Vrijeme za provjeru protiv robota je isteklo",
  button_restart: "Osvježi",
  text_error: "Provjera nije uspjlela",
  button_retry: " Ponovo pokreni",
  text_fetch_error: "Nije moguće uspostaviti vezu"
}, _t = {
  text_init: "Pokretanje...",
  text_ready: "Anti-Robot Verifikacija",
  button_start: "Kliknite da biste započeli verifikaciju",
  text_fetching: "Učitavanje izazova",
  text_solving: "Verifikacija da ste čovek...",
  text_completed: "Ja sam čovek",
  text_completed_sr: "Automatska provera neželjene pošte je završena",
  text_expired: "Anti-Robot verifikacija je istekla",
  button_restart: "Ponovo pokrenuti",
  text_error: "Verifikacija nije uspela",
  button_retry: "Pokušajte ponovo",
  text_fetch_error: "Neuspelo povezivanje sa..."
}, dt = {
  text_init: "Inicializiranje...",
  text_ready: "Preverjanje robotov",
  button_start: "Kliknite za začetek preverjanja",
  text_fetching: "Prenašanje izziva",
  text_solving: "Preverjamo, ali ste človek",
  text_completed: "Nisem robot",
  text_completed_sr: "Avtomatsko preverjanje je zaključeno",
  text_expired: "Preverjanje robotov je poteklo",
  button_restart: "Osveži",
  text_error: "Preverjanje ni uspelo",
  button_retry: "Poskusi ponovno",
  text_fetch_error: "Povezave ni bilo mogoče vzpostaviti"
}, pt = {
  text_init: "Inicializálás...",
  text_ready: "Robotellenes ellenőrzés",
  button_start: "Kattintson az ellenőrzés megkezdéséhez",
  text_fetching: "Feladvány lekérése",
  text_solving: "Annak igazolása, hogy Ön nem robot...",
  text_completed: "Nem vagyok robot",
  text_completed_sr: "Automatikus spam ellenőrzés befejeződött",
  text_expired: "Robotellenes ellenőrzés lejárt",
  button_restart: "Újraindítás",
  text_error: "Az ellenőrzés nem sikerült",
  button_retry: "Próbálja újra",
  text_fetch_error: "Nem sikerült csatlakozni"
}, ft = {
  text_init: "Se inițializează...",
  text_ready: "Verificare anti-robot",
  button_start: "Click pentru a începe verificarea",
  text_fetching: "Downloading",
  text_solving: "Verificare că ești om...",
  text_completed: "Sunt om",
  text_completed_sr: "Verificarea automată a spam-ului a fost finalizată",
  text_expired: "Verificarea anti-robot a expirat",
  button_restart: "Restart",
  text_error: "Verificare eșuată",
  button_retry: "Reîncearcă",
  text_fetch_error: "Nu s-a putut conecta"
}, xt = {
  text_init: "初始化中……",
  text_ready: "人机验证",
  button_start: "点击开始",
  text_fetching: "正在加载",
  text_solving: "人机校验中……",
  text_completed: "我不是机器人",
  text_completed_sr: "人机验证完成",
  text_expired: "验证已过期",
  button_restart: "重新开始",
  text_error: "校验失败",
  button_retry: "重试",
  text_fetch_error: "无法连接到"
}, It = {
  text_init: "正在初始化……",
  text_ready: "反機器人驗證",
  button_start: "點擊開始驗證",
  text_fetching: "載入中",
  text_solving: "反機器人驗證中……",
  text_completed: "我不是機器人",
  text_completed_sr: "驗證完成",
  text_expired: "驗證超時",
  button_restart: "重新開始",
  text_error: "驗證失敗",
  button_retry: "重試",
  text_fetch_error: "無法連線到"
}, Ct = {
  text_init: "Đang khởi tạo...",
  text_ready: "Xác minh chống Robot",
  button_start: "Bấm vào đây để xác minh",
  text_fetching: "Tìm nạp và xử lý thử thách",
  text_solving: "Xác minh bạn là người...",
  text_completed: "Bạn là con người",
  text_completed_sr: "Xác minh hoàn tất",
  text_expired: "Xác minh đã hết hạn",
  button_restart: "Khởi động lại",
  text_error: "Xác minh thất bại",
  button_retry: "Thử lại",
  text_fetch_error: "Không kết nối được"
}, bt = {
  text_init: "בביצוע...",
  text_ready: "אימות אנוש",
  button_start: "צריך ללחוץ להתחלת האימות",
  text_fetching: "אתגר המענה בהכנה",
  text_solving: "מתבצע אימות אנוש...",
  text_completed: "אני לא רובוט",
  text_completed_sr: "בדיקת הספאם האוטומטית הסתיימה",
  text_expired: "פג תוקף אימות האנוש",
  button_restart: "להתחיל שוב",
  text_error: "אימות האנוש נכשל",
  button_retry: "לנסות שוב",
  text_fetch_error: "נכשל החיבור אל",
  rtl: !0
}, mt = {
  text_init: "การเริ่มต้น...",
  text_ready: " การตรวจสอบต่อต้านหุ่นยนต์",
  button_start: "คลิกเพื่อเริ่มการตรวจสอบ",
  text_fetching: "การดึงความท้าทาย",
  text_solving: "ยืนยันว่าคุณเป็นมนุษย์...",
  text_completed: "ฉันเป็นมนุษย์",
  text_completed_sr: "การตรวจสอบสแปมอัตโนมัติเสร็จสมบูรณ์",
  text_expired: "การตรวจสอบ ต่อต้านหุ่นยนต์ หมดอายุ",
  button_restart: "รีสตาร์ท",
  text_error: "การยืนยันล้มเหลว",
  button_retry: "ลองใหม่",
  text_fetch_error: "ไม่สามารถเชื่อมต่อได้"
}, kt = {
  text_init: "초기화 중",
  text_ready: "Anti-Robot 검증",
  button_start: "검증을 위해 클릭해 주세요",
  text_fetching: "검증 준비 중",
  text_solving: "검증 중",
  text_completed: "검증이 완료되었습니다",
  text_completed_sr: "자동 스팸 확인 완료",
  text_expired: "Anti-Robot 검증 만료",
  button_restart: "다시 시작합니다",
  text_error: "검증 실패",
  button_retry: "다시 시도해 주세요",
  text_fetch_error: "연결하지 못했습니다"
}, yt = {
  text_init: "...التهيئة",
  text_ready: "يتم التحقيق",
  button_start: "إضغط هنا للتحقيق",
  text_fetching: "تهيئة التحدي",
  text_solving: "نتحقق من أنك لست روبوتًا...",
  text_completed: "أنا لست روبوتًا",
  text_completed_sr: "تم الانتهاء من التحقق التلقائي من البريد العشوائي",
  text_expired: "انتهت صلاحية التحقق",
  button_restart: "إعادة تشغيل",
  text_error: "فشل التحقق",
  button_retry: "ابدأ مرة أخرى",
  text_fetch_error: "مشكلة في الاتصال مع"
}, d = {
  en: P,
  de: Y,
  nl: O,
  fr: K,
  it: J,
  pt: W,
  es: q,
  ca: $,
  ja: X,
  da: Z,
  ru: tt,
  sv: et,
  tr: rt,
  el: it,
  uk: ot,
  bg: nt,
  cs: st,
  sk: at,
  no: x,
  fi: At,
  lv: ct,
  lt,
  pl: ut,
  et: gt,
  hr: ht,
  sr: _t,
  sl: dt,
  hu: pt,
  ro: ft,
  zh: xt,
  zh_tw: It,
  vi: Ct,
  he: bt,
  th: mt,
  kr: kt,
  ar: yt,
  // alternative language codes
  nb: x
};
function Qt(i, t) {
  const e = new Uint8Array(3), r = new DataView(e.buffer);
  return r.setUint8(0, i), r.setUint16(1, t), e;
}
var Bt = '!function(){"use strict";const A="=".charCodeAt(0),I=new Uint8Array(256);for(let A=0;A<64;A++)I["ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".charCodeAt(A)]=A;function g(A){const I={},g=A.exports,C=g.memory,Q=g.__alloc,t=g.__retain,B=g.__rtti_base||-1;return I.__allocArray=(A,I)=>{const g=function(A){return new Uint32Array(C.buffer)[(B+4>>>2)+2*A]}(A),e=31-Math.clz32(g>>>6&31),o=I.length,i=Q(o<<e,0),r=Q(12,A),n=new Uint32Array(C.buffer);n[r+0>>>2]=t(i),n[r+4>>>2]=i,n[r+8>>>2]=o<<e;const E=C.buffer,s=new Uint8Array(E);if(16384&g)for(let A=0;A<o;++A)s[(i>>>e)+A]=t(I[A]);else s.set(I,i>>>e);return r},I.__getUint8Array=A=>{const I=new Uint32Array(C.buffer),g=I[A+4>>>2];return new Uint8Array(C.buffer,g,I[g-4>>>2]>>>0)},function(A,I={}){const g=A.__argumentsLength?I=>{A.__argumentsLength.value=I}:A.__setArgumentsLength||A.__setargc||(()=>({}));for(const C in A){if(!Object.prototype.hasOwnProperty.call(A,C))continue;const Q=A[C],t=C.split(".")[0];"function"==typeof Q&&Q!==g?(I[t]=(...A)=>(g(A.length),Q(...A))).original=Q:I[t]=Q}return I}(g,I)}class C{constructor(A){this.b=new Uint8Array(128),this.h=new Uint32Array(16),this.t=0,this.c=0,this.v=new Uint32Array(32),this.m=new Uint32Array(32),this.outlen=A}}function Q(A,I){return A[I]^A[I+1]<<8^A[I+2]<<16^A[I+3]<<24}function t(A,I,g,C,Q,t,B,e){const o=I[B],i=I[B+1],r=I[e],n=I[e+1];let E,s,w,a,c=A[g],D=A[g+1],f=A[C],h=A[C+1],y=A[Q],l=A[Q+1],u=A[t],N=A[t+1];E=c+f,s=(c&f|(c|f)&~E)>>>31,c=E,D=D+h+s,E=c+o,s=(c&o|(c|o)&~E)>>>31,c=E,D=D+i+s,w=u^c,a=N^D,u=a,N=w,E=y+u,s=(y&u|(y|u)&~E)>>>31,y=E,l=l+N+s,w=f^y,a=h^l,f=w>>>24^a<<8,h=a>>>24^w<<8,E=c+f,s=(c&f|(c|f)&~E)>>>31,c=E,D=D+h+s,E=c+r,s=(c&r|(c|r)&~E)>>>31,c=E,D=D+n+s,w=u^c,a=N^D,u=w>>>16^a<<16,N=a>>>16^w<<16,E=y+u,s=(y&u|(y|u)&~E)>>>31,y=E,l=l+N+s,w=f^y,a=h^l,f=a>>>31^w<<1,h=w>>>31^a<<1,A[g]=c,A[g+1]=D,A[C]=f,A[C+1]=h,A[Q]=y,A[Q+1]=l,A[t]=u,A[t+1]=N}const B=[4089235720,1779033703,2227873595,3144134277,4271175723,1013904242,1595750129,2773480762,2917565137,1359893119,725511199,2600822924,4215389547,528734635,327033209,1541459225],e=[0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,28,20,8,16,18,30,26,12,2,24,0,4,22,14,10,6,22,16,24,0,10,4,30,26,20,28,6,12,14,2,18,8,14,18,6,2,26,24,22,28,4,12,10,20,8,0,30,16,18,0,10,14,4,8,20,30,28,2,22,24,12,16,6,26,4,24,12,20,0,22,16,6,8,26,14,10,30,28,2,18,24,10,2,30,28,26,8,20,0,14,12,6,18,4,16,22,26,22,14,28,24,2,6,18,10,0,30,8,16,12,4,20,12,30,28,18,22,6,0,16,24,4,26,14,2,8,20,10,20,4,16,8,14,12,2,10,30,22,18,28,6,24,26,0,0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,28,20,8,16,18,30,26,12,2,24,0,4,22,14,10,6];function o(A,I){const g=A.v,C=A.m;for(let I=0;I<16;I++)g[I]=A.h[I],g[I+16]=B[I];g[24]=g[24]^A.t,g[25]=g[25]^A.t/4294967296,I&&(g[28]=~g[28],g[29]=~g[29]);for(let I=0;I<32;I++)C[I]=Q(A.b,4*I);for(let A=0;A<12;A++)t(g,C,0,8,16,24,e[16*A+0],e[16*A+1]),t(g,C,2,10,18,26,e[16*A+2],e[16*A+3]),t(g,C,4,12,20,28,e[16*A+4],e[16*A+5]),t(g,C,6,14,22,30,e[16*A+6],e[16*A+7]),t(g,C,0,10,20,30,e[16*A+8],e[16*A+9]),t(g,C,2,12,22,24,e[16*A+10],e[16*A+11]),t(g,C,4,14,16,26,e[16*A+12],e[16*A+13]),t(g,C,6,8,18,28,e[16*A+14],e[16*A+15]);for(let I=0;I<16;I++)A.h[I]=A.h[I]^g[I]^g[I+16]}function i(A,I){for(let I=0;I<16;I++)A.h[I]=B[I];A.b.set(I),A.h[0]^=16842752^A.outlen}async function r(){return(A,I,g=4294967295)=>{const Q=function(A,I,g){if(128!=A.length)throw Error("Invalid input");const Q=A.buffer,t=new DataView(Q),B=new C(32);B.t=128;const e=t.getUint32(124,!0),r=e+g;for(let g=e;g<r;g++)if(t.setUint32(124,g,!0),i(B,A),o(B,!0),B.h[0]<I)return 0==ASC_TARGET?new Uint8Array(B.h.buffer):Uint8Array.wrap(B.h.buffer);return new Uint8Array(0)}(A,I,g);return[A,Q]}}let n,E;Uint8Array.prototype.slice||Object.defineProperty(Uint8Array.prototype,"slice",{value:function(A,I){return new Uint8Array(Array.prototype.slice.call(this,A,I))}}),self.ASC_TARGET=0;const s=new Promise((A=>E=A));self.onerror=A=>{self.postMessage({type:"error",message:JSON.stringify(A)})},self.onmessage=async C=>{const Q=C.data;try{if("solver"===Q.type){if(Q.forceJS){n=1;const A=await r();E(A)}else try{n=2;const C=WebAssembly.compile(function(g){let C=3285;g.charCodeAt(4379)===A&&C--,g.charCodeAt(4378)===A&&C--;const Q=new Uint8Array(C);for(let A=0,C=0;A<4380;A+=4){const t=I[g.charCodeAt(A+0)],B=I[g.charCodeAt(A+1)],e=I[g.charCodeAt(A+2)],o=I[g.charCodeAt(A+3)];Q[C++]=t<<2|B>>4,Q[C++]=(15&B)<<4|e>>2,Q[C++]=(3&e)<<6|63&o}return Q}("AGFzbQEAAAABKghgAABgAn9/AGADf39/AX9gAX8AYAR/f39/AGAAAX9gAX8Bf2ACf38BfwINAQNlbnYFYWJvcnQABAMMCwcGAwAAAQIFAQIABQMBAAEGFgR/AUEAC38BQQALfwBBAwt/AEHgDAsHbgkGbWVtb3J5AgAHX19hbGxvYwABCF9fcmV0YWluAAIJX19yZWxlYXNlAAMJX19jb2xsZWN0AAQHX19yZXNldAAFC19fcnR0aV9iYXNlAwMNVWludDhBcnJheV9JRAMCDHNvbHZlQmxha2UyYgAKCAELCvQSC5IBAQV/IABB8P///wNLBEAACyMBQRBqIgQgAEEPakFwcSICQRAgAkEQSxsiBmoiAj8AIgVBEHQiA0sEQCAFIAIgA2tB//8DakGAgHxxQRB2IgMgBSADShtAAEEASARAIANAAEEASARAAAsLCyACJAEgBEEQayICIAY2AgAgAkEBNgIEIAIgATYCCCACIAA2AgwgBAsEACAACwMAAQsDAAELBgAjACQBC7sCAQF/AkAgAUUNACAAQQA6AAAgACABakEEayICQQA6AAMgAUECTQ0AIABBADoAASAAQQA6AAIgAkEAOgACIAJBADoAASABQQZNDQAgAEEAOgADIAJBADoAACABQQhNDQAgAEEAIABrQQNxIgJqIgBBADYCACAAIAEgAmtBfHEiAmpBHGsiAUEANgIYIAJBCE0NACAAQQA2AgQgAEEANgIIIAFBADYCECABQQA2AhQgAkEYTQ0AIABBADYCDCAAQQA2AhAgAEEANgIUIABBADYCGCABQQA2AgAgAUEANgIEIAFBADYCCCABQQA2AgwgACAAQQRxQRhqIgFqIQAgAiABayEBA0AgAUEgTwRAIABCADcDACAAQgA3AwggAEIANwMQIABCADcDGCABQSBrIQEgAEEgaiEADAELCwsLcgACfyAARQRAQQxBAhABIQALIAALQQA2AgAgAEEANgIEIABBADYCCCABQfD///8DIAJ2SwRAQcAKQfAKQRJBORAAAAsgASACdCIBQQAQASICIAEQBiAAKAIAGiAAIAI2AgAgACACNgIEIAAgATYCCCAAC88BAQJ/QaABQQAQASIAQQxBAxABQYABQQAQBzYCACAAQQxBBBABQQhBAxAHNgIEIABCADcDCCAAQQA2AhAgAEIANwMYIABC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")),Q=await async function(A){const I=await async function(A){const I={env:{abort(){throw Error("Wasm aborted")}}};return{exports:g(await WebAssembly.instantiate(A,I))}}(A),C=I.exports.__retain(I.exports.__allocArray(I.exports.Uint8Array_ID,new Uint8Array(128)));let Q=I.exports.__getUint8Array(C);return(A,g,t=4294967295)=>{Q.set(A);const B=I.exports.solveBlake2b(C,g,t);Q=I.exports.__getUint8Array(C);const e=I.exports.__getUint8Array(B);return I.exports.__release(B),[Q,e]}}(await C);E(Q)}catch(A){console.log("FriendlyCaptcha failed to initialize WebAssembly, falling back to Javascript solver: "+A.toString()),n=1;const I=await r();E(I)}self.postMessage({type:"ready",solver:n})}else if("start"===Q.type){const A=await s;self.postMessage({type:"started"});let I,g=0;for(let C=0;C<256;C++){Q.puzzleSolverInput[123]=C;const[t,B]=A(Q.puzzleSolverInput,Q.threshold);if(0!==B.length){I=t;break}console.warn("FC: Internal error or no solution found"),g+=Math.pow(2,32)-1}g+=new DataView(I.slice(-4).buffer).getUint32(0,!0),self.postMessage({type:"done",solution:I.slice(-8),h:g,puzzleIndex:Q.puzzleIndex,puzzleNumber:Q.puzzleNumber})}}catch(A){setTimeout((()=>{throw A}))}}}();';
let m;
typeof window < "u" && (m = window.URL || window.webkitURL);
class wt {
  constructor() {
    this.workers = [], this.puzzleNumber = 0, this.numPuzzles = 0, this.threshold = 0, this.startTime = 0, this.progress = 0, this.totalHashes = 0, this.puzzleSolverInputs = [], this.puzzleIndex = 0, this.solutionBuffer = new Uint8Array(0), this.solverType = 1, this.readyPromise = new Promise(() => {
    }), this.readyCount = 0, this.startCount = 0, this.progressCallback = () => 0, this.readyCallback = () => 0, this.startedCallback = () => 0, this.doneCallback = () => 0, this.errorCallback = () => 0;
  }
  init() {
    this.terminateWorkers(), this.progress = 0, this.totalHashes = 0;
    let t;
    this.readyPromise = new Promise((r) => t = r), this.readyCount = 0, this.startCount = 0, this.workers = new Array(4);
    const e = new Blob([Bt], { type: "text/javascript" });
    for (let r = 0; r < this.workers.length; r++)
      this.workers[r] = new Worker(m.createObjectURL(e)), this.workers[r].onerror = (o) => this.errorCallback(o), this.workers[r].onmessage = (o) => {
        const n = o.data;
        if (n)
          if (n.type === "ready")
            this.readyCount++, this.solverType = n.solver, this.readyCount == this.workers.length && (t(), this.readyCallback());
          else if (n.type === "started")
            this.startCount++, this.startCount == 1 && (this.startTime = Date.now(), this.startedCallback());
          else if (n.type === "done") {
            if (n.puzzleNumber !== this.puzzleNumber)
              return;
            if (this.puzzleIndex < this.puzzleSolverInputs.length && (this.workers[r].postMessage({
              type: "start",
              puzzleSolverInput: this.puzzleSolverInputs[this.puzzleIndex],
              threshold: this.threshold,
              puzzleIndex: this.puzzleIndex,
              puzzleNumber: this.puzzleNumber
            }), this.puzzleIndex++), this.progress++, this.totalHashes += n.h, this.progressCallback({
              n: this.numPuzzles,
              h: this.totalHashes,
              t: (Date.now() - this.startTime) / 1e3,
              i: this.progress
            }), this.solutionBuffer.set(n.solution, n.puzzleIndex * 8), this.progress == this.numPuzzles) {
              const s = (Date.now() - this.startTime) / 1e3;
              this.doneCallback({
                solution: this.solutionBuffer,
                h: this.totalHashes,
                t: s,
                diagnostics: Qt(this.solverType, s),
                solver: this.solverType
              });
            }
          } else n.type === "error" && this.errorCallback(n);
      };
  }
  setupSolver(t = !1) {
    const e = { type: "solver", forceJS: t };
    for (let r = 0; r < this.workers.length; r++)
      this.workers[r].postMessage(e);
  }
  async start(t) {
    await this.readyPromise, this.puzzleSolverInputs = U(t.buffer, t.n), this.solutionBuffer = new Uint8Array(8 * t.n), this.numPuzzles = t.n, this.threshold = t.threshold, this.puzzleIndex = 0, this.puzzleNumber++;
    for (let e = 0; e < this.workers.length && this.puzzleIndex !== this.puzzleSolverInputs.length; e++)
      this.workers[e].postMessage({
        type: "start",
        puzzleSolverInput: this.puzzleSolverInputs[e],
        threshold: this.threshold,
        puzzleIndex: this.puzzleIndex,
        puzzleNumber: this.puzzleNumber
      }), this.puzzleIndex++;
  }
  terminateWorkers() {
    if (this.workers.length != 0) {
      for (let t = 0; t < this.workers.length; t++)
        this.workers[t].terminate();
      this.workers = [];
    }
  }
}
const Et = "https://api.friendlycaptcha.com/api/v1/puzzle";
class vt {
  constructor(t, e = {}) {
    this.workerGroup = new wt(), this.valid = !1, this.needsReInit = !1, this.hasBeenStarted = !1, this.hasBeenDestroyed = !1, this.opts = Object.assign({
      forceJSFallback: !1,
      skipStyleInjection: !1,
      startMode: "focus",
      puzzleEndpoint: t.dataset.puzzleEndpoint || Et,
      startedCallback: () => 0,
      readyCallback: () => 0,
      doneCallback: () => 0,
      errorCallback: () => 0,
      sitekey: t.dataset.sitekey || "",
      language: t.dataset.lang || "en",
      solutionFieldName: t.dataset.solutionFieldName || "frc-captcha-solution",
      styleNonce: null
    }, e), this.e = t, this.e.friendlyChallengeWidget = this, this.loadLanguage(), t.innerText = this.lang.text_init, this.opts.skipStyleInjection || D(this.opts.styleNonce), this.init(this.opts.startMode === "auto" || this.e.dataset.start === "auto");
  }
  init(t) {
    if (this.hasBeenDestroyed) {
      console.error("FriendlyCaptcha widget has been destroyed using destroy(), it can not be used anymore.");
      return;
    }
    if (this.initWorkerGroup(), t)
      this.start();
    else if (this.e.dataset.start !== "none" && (this.opts.startMode === "focus" || this.e.dataset.start === "focus")) {
      const e = S(this.e);
      e ? L(e, () => this.start()) : console.log("FriendlyCaptcha div seems not to be contained in a form, autostart will not work");
    }
  }
  /**
   * Loads the configured language, or a language passed to this function.
   * Note that only the next update will be in the new language, consider calling `reset()` after switching languages.
   */
  loadLanguage(t) {
    if (t !== void 0 ? this.opts.language = t : this.e.dataset.lang && (this.opts.language = this.e.dataset.lang), typeof this.opts.language == "string") {
      let e = this.opts.language.toLowerCase(), r = d[e];
      r === void 0 && e[2] === "-" && (e = e.substring(0, 2), r = d[e]), r === void 0 && (console.error('FriendlyCaptcha: language "' + this.opts.language + '" not found.'), r = d.en), this.lang = r;
    } else
      this.lang = Object.assign(Object.assign({}, d.en), this.opts.language);
  }
  /**
   * Add a listener to the button that calls `this.start` on click.
   */
  makeButtonStart() {
    const t = this.e.querySelector("button");
    t && (t.addEventListener("click", (e) => this.start(), { once: !0, passive: !0 }), t.addEventListener("touchstart", (e) => this.start(), { once: !0, passive: !0 }));
  }
  onWorkerError(t) {
    this.hasBeenStarted = !1, this.needsReInit = !0, this.expiryTimeout && clearTimeout(this.expiryTimeout), console.error("[FRC]", t), this.e.innerHTML = _(this.opts.solutionFieldName, this.lang, "Background worker error " + t.message), this.makeButtonStart(), this.opts.forceJSFallback = !0;
  }
  initWorkerGroup() {
    this.workerGroup.progressCallback = (t) => {
      R(this.e, t);
    }, this.workerGroup.readyCallback = () => {
      this.e.innerHTML = w(this.opts.solutionFieldName, this.lang), this.makeButtonStart(), this.opts.readyCallback();
    }, this.workerGroup.startedCallback = () => {
      this.e.innerHTML = v(this.opts.solutionFieldName, this.lang), this.opts.startedCallback();
    }, this.workerGroup.doneCallback = (t) => {
      const e = this.handleDone(t);
      this.opts.doneCallback(e);
      const r = this.e.dataset.callback;
      r && window[r](e);
    }, this.workerGroup.errorCallback = (t) => {
      this.onWorkerError(t);
    }, this.workerGroup.init(), this.workerGroup.setupSolver(this.opts.forceJSFallback);
  }
  expire() {
    this.hasBeenStarted = !1, this.e.isConnected !== !1 && (this.e.innerHTML = N(this.opts.solutionFieldName, this.lang), this.makeButtonStart());
  }
  async start() {
    if (this.hasBeenDestroyed) {
      console.error("Can not start FriendlyCaptcha widget which has been destroyed");
      return;
    }
    if (this.hasBeenStarted) {
      console.warn("Can not start FriendlyCaptcha widget which has already been started");
      return;
    }
    const t = this.opts.sitekey || this.e.dataset.sitekey;
    if (!t) {
      console.error("FriendlyCaptcha: sitekey not set on frc-captcha element"), this.e.innerHTML = _(this.opts.solutionFieldName, this.lang, "Website problem: sitekey not set", !1);
      return;
    }
    if (G()) {
      this.e.innerHTML = _(this.opts.solutionFieldName, this.lang, "Browser check failed, try a different browser", !1, !0);
      return;
    }
    if (this.needsReInit) {
      this.needsReInit = !1, this.init(!0);
      return;
    }
    this.hasBeenStarted = !0;
    try {
      this.e.innerHTML = E(this.opts.solutionFieldName, this.lang), this.puzzle = T(await V(this.opts.puzzleEndpoint, t, this.lang)), this.expiryTimeout && clearTimeout(this.expiryTimeout), this.expiryTimeout = setTimeout(() => this.expire(), this.puzzle.expiry - 3e4);
    } catch (e) {
      console.error("[FRC]", e), this.hasBeenStarted = !1, this.expiryTimeout && clearTimeout(this.expiryTimeout), this.e.innerHTML = _(this.opts.solutionFieldName, this.lang, e.message), this.makeButtonStart(), this.opts.errorCallback({ code: "error_getting_puzzle", description: e.toString(), error: e });
      const o = this.e.dataset["callback-error"];
      o && window[o](this);
      return;
    }
    await this.workerGroup.start(this.puzzle);
  }
  /**
   * This is to be called when the puzzle has been succesfully completed.
   * Here the hidden field gets updated with the solution.
   * @param data message from the webworker
   */
  handleDone(t) {
    this.valid = !0;
    const e = `${this.puzzle.signature}.${this.puzzle.base64}.${f(t.solution)}.${f(t.diagnostics)}`;
    return this.e.innerHTML = z(this.opts.solutionFieldName, this.lang, e, t), this.needsReInit = !0, e;
  }
  /**
   * Cleans up the widget entirely, removing any DOM elements and terminating any background workers.
   * After it is destroyed it can no longer be used for any purpose.
   */
  destroy() {
    this.workerGroup.terminateWorkers(), this.needsReInit = !1, this.hasBeenStarted = !1, this.expiryTimeout && clearTimeout(this.expiryTimeout), this.e && (this.e.remove(), delete this.e), this.hasBeenDestroyed = !0;
  }
  /**
   * Resets the widget to the initial state.
   * This is useful in situations where the page does not refresh when you submit and the form may be re-submitted again
   */
  reset() {
    if (this.hasBeenDestroyed) {
      console.error("FriendlyCaptcha widget has been destroyed, it can not be used anymore");
      return;
    }
    this.workerGroup.terminateWorkers(), this.needsReInit = !1, this.hasBeenStarted = !1, this.expiryTimeout && clearTimeout(this.expiryTimeout), this.init(this.opts.startMode === "auto" || this.e.dataset.start === "auto");
  }
}
export {
  vt as WidgetInstance,
  d as localizations
};
