import vue from 'eslint-plugin-vue'
import globals from 'globals'

export default [
  ...vue.configs['flat/essential'],
  {
    files: ['resources/js/**/*.{js,vue}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        route: 'readonly',
      },
    },
    rules: {
      'vue/no-undef-properties': 'error',
      'vue/multi-word-component-names': 'off',
      'vue/no-parsing-error': ['error', {
        'invalid-first-character-of-tag-name': false,
      }],
    },
  },
  {
    ignores: ['public/**', 'node_modules/**', 'vendor/**', 'storage/**', 'bootstrap/cache/**'],
  },
]
