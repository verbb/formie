// ================================================
// Ensure you document all plugins in use
// ================================================

import autoprefixer from 'autoprefixer';
import postCssColorFunction from 'postcss-color-function';

export default {
    plugins: [
        // Add browser-prefixing
        // https://github.com/postcss/autoprefixer
        autoprefixer,

        // Add color utils
        // https://github.com/postcss/postcss-color-function
        postCssColorFunction,
    ],
}
