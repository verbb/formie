global.dd = function(args) {
    console.log(args);
};

global.clone = function(value) {
    if (value === undefined) {
        return undefined;
    }

    return JSON.parse(JSON.stringify(value));
};

global.t = function(category = 'formie', string) {
    return Craft.t(category, string);
};

global.has = function(ctx, prop) {
    return Object.prototype.hasOwnProperty.call(ctx, prop);
};
