import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';
import vueParser from 'vue-eslint-parser';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    ...tseslint.configs.recommended,
    {
        files: ['resources/js/**/*.ts'],
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                localStorage: 'readonly',
                crypto: 'readonly',
            },
        },
    },
    {
        files: ['resources/js/**/*.vue'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tseslint.parser,
                ecmaVersion: 'latest',
                sourceType: 'module',
                extraFileExtensions: ['.vue'],
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                localStorage: 'readonly',
                crypto: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
    {
        ignores: ['node_modules/**', 'public/**', 'vendor/**'],
    },
];
