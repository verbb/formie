function boolMatch(s, matchers) {
    let i, matcher;
    const down = s.toLowerCase();

    matchers = [].concat(matchers);

    for (i = 0; i < matchers.length; i += 1) {
        matcher = matchers[i];

        if (!matcher) {
            continue;
        }

        if (matcher.test && matcher.test(s)) {
            return true;
        }

        if (matcher.toLowerCase() === down) {
            return true;
        }
    }
}

export const toBoolean = (str, trueValues, falseValues) => {
    if (typeof str === 'number') {
        str = `${str}`;
    }

    if (typeof str !== 'string') {
        return !!str;
    }

    str = str.trim();

    if (boolMatch(str, trueValues || ['true', '1'])) {
        return true;
    }

    if (boolMatch(str, falseValues || ['false', '0'])) {
        return false;
    }
};
