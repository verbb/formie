// Minimal-but-practical PHP `date()` formatter for JS previews.
// - Uses local time (matches typical PHP usage unless PHP is forced to UTC).
// - Supports escaping with backslash: \Y \m etc (like PHP).
// - Covers common tokens: Y y m n d j H G i s a A D l M F U c r
// - You can extend the token map as needed.

export function formatPhpDate(dateInput, format) {
    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
    if (Number.isNaN(date.getTime())) { return ''; }

    // Intl-based parts are more robust than manual month/day names.
    const parts = new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).formatToParts(date);

    const part = (type) => { return parts.find((p) => { return p.type === type; })?.value ?? ''; };

    // Name formats (en-US; for preview this is usually fine. If you want
    // locale-aware previews, make locale configurable and feed it into these formatters.)
    const weekdayShort = new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date);
    const weekdayLong = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(date);
    const monthShort = new Intl.DateTimeFormat('en-US', { month: 'short' }).format(date);
    const monthLong = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(date);

    const pad2 = (v) => { return String(v).padStart(2, '0'); };

    const hours = date.getHours();
    const mins = date.getMinutes();
    const secs = date.getSeconds();

    const tokens = {
    // Year
        Y: () => { return String(date.getFullYear()); },
        y: () => { return String(date.getFullYear()).slice(-2); },

        // Month
        m: () => { return pad2(date.getMonth() + 1); },
        n: () => { return String(date.getMonth() + 1); },
        M: () => { return monthShort; },
        F: () => { return monthLong; },

        // Day
        d: () => { return pad2(date.getDate()); },
        j: () => { return String(date.getDate()); },
        D: () => { return weekdayShort; },
        l: () => { return weekdayLong; },

        // Time
        H: () => { return pad2(hours); },
        G: () => { return String(hours); },
        i: () => { return pad2(mins); },
        s: () => { return pad2(secs); },
        a: () => { return (hours < 12 ? 'am' : 'pm'); },
        A: () => { return (hours < 12 ? 'AM' : 'PM'); },

        // Unix timestamp (seconds)
        U: () => { return String(Math.floor(date.getTime() / 1000)); },

        // ISO-8601 (similar to PHP 'c') - NOTE: PHP's 'c' uses local offset;
        // JS's toISOString() is UTC, so we construct local ISO-ish.
        c: () => {
            const y = date.getFullYear();
            const mo = pad2(date.getMonth() + 1);
            const da = pad2(date.getDate());
            const hh = pad2(hours);
            const mm = pad2(mins);
            const ss = pad2(secs);
            return `${y}-${mo}-${da}T${hh}:${mm}:${ss}${formatPhpDate(date, 'P')}`;
        },

        // RFC 2822 (similar to PHP 'r')
        r: () => {
            // Example: "Wed, 21 Jan 2026 11:03:45 +1100"
            return `${weekdayShort}, ${formatPhpDate(date, 'd')} ${monthShort} ${formatPhpDate(date, 'Y')} ${formatPhpDate(date, 'H:i:s')} ${formatPhpDate(date, 'O')}`;
        },

        // Timezone offsets
        // O: Difference to GMT in hours e.g. +1100
        O: () => {
            const offsetMin = -date.getTimezoneOffset(); // positive east of UTC
            const sign = offsetMin >= 0 ? '+' : '-';
            const abs = Math.abs(offsetMin);
            const hh = pad2(Math.floor(abs / 60));
            const mm = pad2(abs % 60);
            return `${sign}${hh}${mm}`;
        },
        // P: Difference to GMT with colon e.g. +11:00
        P: () => {
            const o = tokens.O();
            return `${o.slice(0, 3)}:${o.slice(3)}`;
        },
    };

    // Parse with PHP-like escaping using backslash.
    let out = '';
    for (let i = 0; i < format.length; i++) {
        const ch = format[i];

        if (ch === '\\') {
            // Escape next char literally
            i++;
            if (i < format.length) { out += format[i]; }
            continue;
        }

        if (tokens[ch]) {
            out += tokens[ch]();
        } else {
            out += ch;
        }
    }

    return out;
}
