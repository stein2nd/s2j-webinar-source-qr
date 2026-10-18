import { defineConfig } from 'vitest/config';
import { resolve } from 'path';

export default defineConfig({
	test: {
		include: ['tests/node/**/*.test.ts'],
		environment: 'node',
	},
	resolve: {
		alias: {
			'@': resolve(import.meta.dirname, 'src'),
		},
	},
});
