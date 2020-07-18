export const takeAtLeast = function(ms) {
    return function(x) {
        return new Promise(resolve => setTimeout(() => resolve(x), ms));
    };
};

// NOTE - Doesn't work in IE Edge

// // Creates a new promise that automatically resolves after some timeout:
// Promise.delay = function(time) {
//     return new Promise((resolve, reject) => {
//         setTimeout(resolve, time);
//     });
// };

// // Throttle this promise to resolve no faster than the specified time:
// Promise.prototype.takeAtLeast = function(time) {
//     return new Promise((resolve, reject) => {
//         Promise.all([this, Promise.delay(time)]).then(([result]) => {
//             resolve(result);
//         }, reject);
//     });
// };
