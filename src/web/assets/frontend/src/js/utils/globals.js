global.t = function(string, replacements = {}) {
    string = Formie.translations[string] || string;

    return string.replace(/{([a-zA-Z0-9]+)}/g, (match, p1) => {
        if (replacements[p1]) {
            return replacements[p1];
        }

        return match;
    });
};
