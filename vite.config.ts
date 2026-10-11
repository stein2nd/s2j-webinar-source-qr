import { defineConfig } from 'vite';
import { resolve } from 'path';
import { rmSync } from 'fs';

if (process.env.FLUSH_DIST === 'true') {
	rmSync('dist', { recursive: true, force: true });
}

const getBuildTarget = () => {
	const npmScript = process.env.npm_lifecycle_event;
	if (npmScript?.includes('admin')) {
		return 'admin';
	}
	if (npmScript?.includes('gutenberg')) {
		return 'gutenberg';
	}
	if (npmScript?.includes('classic')) {
		return 'classic';
	}
	if (npmScript?.includes('frontend')) {
		return 'frontend';
	}
	return 'gutenberg';
};

const buildTarget = getBuildTarget();

const getBuildConfig = (target: string) => {
	switch (target) {
		case 'admin':
			return {
				entry: resolve(import.meta.dirname, 'src/admin/index.tsx'),
				name: 'S2JWebinarSourceQrAdmin',
				scss: resolve(import.meta.dirname, 'src/styles/admin.scss'),
			};
		case 'classic':
			return {
				entry: resolve(import.meta.dirname, 'src/classic/index.ts'),
				name: 'S2JWebinarSourceQrClassic',
				scss: resolve(import.meta.dirname, 'src/styles/classic.scss'),
			};
		case 'frontend':
			return {
				entry: resolve(import.meta.dirname, 'src/frontend/index.ts'),
				name: 'S2JWebinarSourceQrFrontend',
				scss: resolve(import.meta.dirname, 'src/styles/frontend.scss'),
			};
		default:
			return {
				entry: resolve(import.meta.dirname, 'src/gutenberg/index.tsx'),
				name: 'S2JWebinarSourceQrGutenberg',
				scss: resolve(import.meta.dirname, 'src/styles/gutenberg.scss'),
			};
	}
};

const buildConfig = getBuildConfig(buildTarget);

export default defineConfig({
	logLevel:
		(process.env.VITE_LOG_LEVEL as 'info' | 'warn' | 'error' | 'silent') ||
		'warn',
	define: {
		'process.env': {},
		'process.env.NODE_ENV': JSON.stringify(
			process.env.NODE_ENV || 'development'
		),
	},
	build: {
		lib: {
			entry: buildConfig.entry,
			formats: ['iife'],
			name: buildConfig.name,
		},
		rollupOptions: {
			external: (id) => {
				if (id.startsWith('@wordpress/')) {
					return true;
				}
				if (
					id === 'react' ||
					id === 'react-dom' ||
					id === 'react/jsx-runtime' ||
					id === 'react/jsx-dev-runtime'
				) {
					return true;
				}
				if (id === 'jquery') {
					return true;
				}
				return false;
			},
			input: buildConfig.entry,
			output: {
				banner: `window.S2JReactJSXRuntime=window.S2JReactJSXRuntime||{jsx:wp.element.createElement,jsxs:wp.element.createElement,Fragment:wp.element.Fragment};`,
				globals: (id) => {
					if (id.startsWith('@wordpress/')) {
						const parts = id.split('/');
						const module = parts[parts.length - 1];
						if (id === '@wordpress/data') {
							return 'wp.data';
						}
						if (id === '@wordpress/element') {
							return 'wp.element';
						}
						if (id === '@wordpress/components') {
							return 'wp.components';
						}
						if (id === '@wordpress/i18n') {
							return 'wp.i18n';
						}
						if (id === '@wordpress/editor') {
							return 'wp.editor';
						}
						if (id === '@wordpress/edit-post') {
							return 'wp.editPost';
						}
						if (id === '@wordpress/plugins') {
							return 'wp.plugins';
						}
						if (id === '@wordpress/api-fetch') {
							return 'wp.apiFetch';
						}
						return `wp.${module}`;
					}
					if (id === 'react' || id === 'react-dom') {
						return 'wp.element';
					}
					if (
						id === 'react/jsx-runtime' ||
						id === 'react/jsx-dev-runtime'
					) {
						return 'S2JReactJSXRuntime';
					}
					if (id === 'jquery') {
						return 'jQuery';
					}
					return id;
				},
				assetFileNames: (assetInfo) => {
					if (assetInfo.name?.endsWith('.css')) {
						return `css/s2j-webinar-source-qr-${buildTarget}.css`;
					}
					return 'js/[name][extname]';
				},
				chunkFileNames: 'js/[name].js',
				entryFileNames: `js/s2j-webinar-source-qr-${buildTarget}.js`,
			},
		},
		outDir: 'dist',
		emptyOutDir: false,
		minify: process.env.NODE_ENV === 'production',
		sourcemap: process.env.NODE_ENV !== 'production',
		cssCodeSplit: false,
		reportCompressedSize: false,
	},
	resolve: {
		alias: {
			'@': resolve(import.meta.dirname, 'src'),
		},
	},
});
