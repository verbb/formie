// ================================================
// Ensure you document all plugins in use
// ================================================

module.exports = {
    plugins: [
        // Add browser-prefixing
        // https://github.com/postcss/autoprefixer
        require('autoprefixer'),

        // Add color utitls
        // https://github.com/postcss/postcss-color-function
        require('postcss-color-function'),
    ],
}
