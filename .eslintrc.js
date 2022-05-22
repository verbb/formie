module.exports = {
    root: true,

    env: {
        browser: true,
        es2021: true,
    },

    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
    ],

    parserOptions: {
        ecmaVersion: 2021,
    },

    rules: {
        //
        // Best Practices
        //
        
        // enforces return statements in callbacks of array's methods
        'array-callback-return': ['warn', { allowImplicit: true }],

        // specify curly brace conventions for all control statements
        curly: ['warn', 'all'],

        // encourages use of dot notation whenever possible
        'dot-notation': ['warn', { allowKeywords: true }],

        // enforces consistent newlines before or after dots
        'dot-location': ['warn', 'property'],

        // disallow the use of alert, confirm, and prompt
        'no-alert': 'off',

        // disallow else after a return in an if
        'no-else-return': ['warn', { allowElseIf: false }],

        // disallow empty functions, except for standalone funcs/arrows
        'no-empty-function': ['warn', {
            allow: [
                'arrowFunctions',
                'functions',
                'methods',
            ],
        }],

        // disallow use of eval()
        'no-eval': 'warn',

        // disallow the use of leading or trailing decimal points in numeric literals
        'no-floating-decimal': 'warn',

        // disallow use of multiple spaces
        'no-multi-spaces': ['warn', {
            ignoreEOLComments: false,
        }],

        // disallow use of multiline strings
        'no-multi-str': 'warn',

        // disallow useless string concatenation
        'no-useless-concat': 'warn',

        // disallow redundant return; keywords
        'no-useless-return': 'warn',

        // require or disallow Yoda conditions
        yoda: 'warn',

        //
        // ES6
        //

        // require braces in arrow function body
        'arrow-body-style': ['warn', 'always'],

        // require parens in arrow function arguments
        'arrow-parens': ['warn', 'always'],

        // require space before/after arrow function's arrow
        'arrow-spacing': ['warn', { before: true, after: true }],

        // disallow arrow functions where they could be confused with comparisons
        'no-confusing-arrow': ['warn', {
            allowParens: true,
        }],

        // require let or const instead of var
        'no-var': 'warn',

        // require method and property shorthand syntax for object literals
        'object-shorthand': ['warn', 'always', {
            ignoreConstructors: false,
            avoidQuotes: true,
        }],

        // suggest using arrow functions as callbacks
        'prefer-arrow-callback': ['warn', {
            allowNamedFunctions: false,
            allowUnboundThis: true,
        }],

        // suggest using of const declaration for variables that are never modified after declared
        'prefer-const': ['warn', {
            destructuring: 'any',
            ignoreReadBeforeAssign: true,
        }],

        // Prefer destructuring from arrays and objects
        'prefer-destructuring': ['warn', {
            VariableDeclarator: {
                array: false,
                object: true,
            },
            AssignmentExpression: {
                array: true,
                object: false,
            },
        }, {
            enforceForRenamedProperties: false,
        }],

        // suggest using template literals instead of string concatenation
        'prefer-template': 'warn',

        // enforce spacing between object rest-spread
        // https://eslint.org/docs/rules/rest-spread-spacing
        'rest-spread-spacing': ['warn', 'never'],

        // import sorting
        // https://eslint.org/docs/rules/sort-imports
        'sort-imports': ['off', {
            ignoreCase: false,
            ignoreDeclarationSort: false,
            ignoreMemberSort: false,
            memberSyntaxSortOrder: ['none', 'all', 'multiple', 'single'],
            allowSeparatedGroups: true,
        }],

        // enforce usage of spacing in template strings
        'template-curly-spacing': 'warn',

        //
        // Style
        //

        // enforce spacing inside array brackets
        'array-bracket-spacing': ['warn', 'never'],

        // enforce spacing inside single-line blocks
        'block-spacing': ['warn', 'always'],

        // enforce one true brace style
        'brace-style': ['warn', '1tbs', { allowSingleLine: true }],

        // require camel case names
        camelcase: ['warn', { properties: 'always', ignoreDestructuring: true }],

        // enforce or disallow capitalization of the first letter of a comment
        'capitalized-comments': ['warn', 'always', {
            line: {
                ignorePattern: '.*',
                ignoreInlineComments: true,
                ignoreConsecutiveComments: true,
            },
            block: {
                ignorePattern: '.*',
                ignoreInlineComments: true,
                ignoreConsecutiveComments: true,
            },
        }],

        // require trailing commas in multiline object literals
        'comma-dangle': ['warn', {
            arrays: 'always-multiline',
            objects: 'always-multiline',
            imports: 'always-multiline',
            exports: 'always-multiline',
            functions: 'always-multiline',
        }],

        // enforce spacing before and after comma
        'comma-spacing': ['warn', { before: false, after: true }],

        // enforce one true comma style
        'comma-style': ['warn', 'last', {
            exceptions: {
                ArrayExpression: false,
                ArrayPattern: false,
                ArrowFunctionExpression: false,
                CallExpression: false,
                FunctionDeclaration: false,
                FunctionExpression: false,
                ImportDeclaration: false,
                ObjectExpression: false,
                ObjectPattern: false,
                VariableDeclaration: false,
                NewExpression: false,
            }
        }],

        // disallow padding inside computed properties
        'computed-property-spacing': ['warn', 'never'],

        // enforce newline at the end of file, with no multiple empty lines
        'eol-last': ['warn', 'always'],

        // enforce spacing between functions and their invocations
        'func-call-spacing': ['warn', 'never'],

        // enforce consistent line breaks inside function parentheses
        'function-paren-newline': ['warn', 'consistent'],

        // Enforce the location of arrow function bodies with implicit returns
        'implicit-arrow-linebreak': ['warn', 'beside'],

        // this option sets a specific tab width for your code
        indent: ['warn', 4, {
            SwitchCase: 1,
            VariableDeclarator: 1,
            outerIIFEBody: 1,
            FunctionDeclaration: {
                parameters: 1,
                body: 1
            },
            FunctionExpression: {
                parameters: 1,
                body: 1
            },
            CallExpression: {
                arguments: 1
            },
            ArrayExpression: 1,
            ObjectExpression: 1,
            ImportDeclaration: 1,
            flatTernaryExpressions: false,
            ignoreComments: false,
        }],

        // enforces spacing between keys and values in object literal properties
        'key-spacing': ['warn', { beforeColon: false, afterColon: true }],

        // require a space before & after certain keywords
        'keyword-spacing': ['warn', {
            before: true,
            after: true,
            overrides: {
                return: { after: true },
                throw: { after: true },
                case: { after: true }
            }
        }],

        // disallow mixed 'LF' and 'CRLF' as linebreaks
        'linebreak-style': ['warn', 'unix'],

        // require or disallow an empty line between class members
        'lines-between-class-members': ['warn', 'always', { exceptAfterSingleLine: false }],

        // require or disallow newlines around directives
        'lines-around-directive': ['warn', {
            before: 'always',
            after: 'always',
        }],

        // disallow if as the only statement in an else block
        'no-lonely-if': 'warn',

        // disallow multiple empty lines, only one newline at the end, and no new lines at the beginning
        'no-multiple-empty-lines': ['warn', { max: 2, maxBOF: 0, maxEOF: 0 }],

        // disallow nested ternary expressions
        'no-nested-ternary': 'warn',

        // disallow space between function identifier and application
        'no-spaced-func': 'warn',

        // disallow tab characters entirely
        'no-tabs': 'warn',

        // disallow trailing whitespace at the end of lines
        'no-trailing-spaces': ['warn', {
            skipBlankLines: false,
            ignoreComments: false,
        }],

        // require padding inside curly braces
        'object-curly-spacing': ['warn', 'always'],

        // enforce line breaks between braces
        'object-curly-newline': ['warn', {
            ObjectExpression: { minProperties: 4, multiline: true, consistent: true },
            ObjectPattern: { minProperties: 4, multiline: true, consistent: true },
            ImportDeclaration: { minProperties: 4, multiline: true, consistent: true },
            ExportDeclaration: { minProperties: 4, multiline: true, consistent: true },
        }],

        // enforce "same line" or "multiple line" on object properties.
        'object-property-newline': ['warn', {
            allowAllPropertiesOnSameLine: true,
        }],

        // Prefer use of an object spread over Object.assign
        'prefer-object-spread': 'warn',

        // require quotes around object literal property names
        'quote-props': ['warn', 'as-needed', { keywords: false, unnecessary: true, numbers: false }],

        // specify whether double or single quotes should be used
        quotes: ['warn', 'single', { avoidEscape: true }],

        // require or disallow use of semicolons instead of ASI
        semi: ['warn', 'always'],

        // enforce spacing before and after semicolons
        'semi-spacing': ['warn', { before: false, after: true }],

        // Enforce location of semicolons
        'semi-style': ['warn', 'last'],

        // require or disallow space before blocks
        'space-before-blocks': 'warn',

        // require or disallow space before function opening parenthesis
        'space-before-function-paren': ['warn', {
            anonymous: 'never',
            named: 'never',
            asyncArrow: 'never'
        }],

        // require or disallow spaces inside parentheses
        'space-in-parens': ['warn', 'never'],

        // require spaces around operators
        'space-infix-ops': 'warn',

        // require or disallow a space immediately following the // or /* in a comment
        'spaced-comment': ['warn', 'always', {
            line: {
                exceptions: ['-', '+'],
                markers: ['=', '!', '/'], // space here to support sprockets directives, slash for TS /// comments
            },
            block: {
                exceptions: ['-', '+'],
                markers: ['=', '!', ':', '::'], // space here to support sprockets directives and flow comment types
                balanced: true,
            }
        }],

        // Enforce spacing around colons of switch statements
        'switch-colon-spacing': ['warn', { after: true, before: false }],

        // Require or disallow spacing between template tags and their literals
        'template-tag-spacing': ['warn', 'never'],

        // require or disallow the Unicode Byte Order Mark
        'unicode-bom': ['warn', 'never'],

        //
        // Variables
        //

        // disallow use of undeclared variables unless mentioned in a /*global */ block
        'no-undef': 'off',

        // disallow declaration of variables that are not used in the code
        'no-unused-vars': 'off',

        // disallow use of variables before they are defined
        'no-use-before-define': ['warn', { functions: true, classes: true, variables: true }],

        //
        // Vue
        //

        'vue/html-indent': ['warn', 4, {
            attribute: 1,
            baseIndent: 1,
            closeBracket: 0,
            alignAttributesVertically: true,
            ignores: [],
        }],
        'vue/html-self-closing': ['warn', {
            html: {
                void: 'never',
                normal: 'never',
                component: 'always',
            },
            svg: 'always',
            math: 'always',
        }],
        'vue/max-attributes-per-line': ['warn', {
            singleline: 6,
            multiline: 6,
        }],
        'vue/no-v-html': 'off',
        'vue/script-indent': ['warn', 4, {
            baseIndent: 0,
            switchCase: 0,
            ignores: [],
        }],
        'vue/singleline-html-element-content-newline': 'off',
        'vue/one-component-per-file': 'off',
        'vue/multi-word-component-names': 'off',
        'vue/no-unused-components': 'warn',
        'vue/no-mutating-props': 'off',
    },

    settings: {
        'import/resolver': {
            node: {
                extensions: ['.js', '.ts', '.vue'],
            },

            alias: {
                map: [
                    ['@', './resources'],
                ],
            },
        },
    },

    // Standard indentation rule needs to be disabled for .vue files as it conflicts with the vue indent rule.
    overrides: [
        {
            files: ['*.vue'],
            rules: {
                indent: 'off',
            },
        },
    ],

};