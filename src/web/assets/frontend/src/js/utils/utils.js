export const isEmpty = function(obj) {
    return Object.keys(obj).length === 0;
};

export const toBoolean = function(val) {
    return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};

export const eventKey = function(eventName) {
    return eventName + '.' + Math.random();
};

