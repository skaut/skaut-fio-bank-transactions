import wordpress from '@wordpress/eslint-plugin';
import compat from 'eslint-plugin-compat';

export default [
	{
		ignores: [ 'dist/**', 'node_modules/**', 'vendor/**' ],
	},
	...wordpress.configs.recommended,
	compat.configs[ 'flat/recommended' ],
	{
		files: [ 'src/**/*.js' ],
		languageOptions: {
			globals: {
				window: 'readonly',
				document: 'readonly',
			},
		},
	},
];
