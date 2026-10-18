/**
 * @wordpress/block-editor ships build-types but package.json "exports" omit "types"
 * (TS resolves build-module/index.mjs → implicit any). Minimal surface for this plugin.
 */
declare module '@wordpress/block-editor' {
	import type { ComponentType, ReactNode } from 'react';

	export const MediaUploadCheck: ComponentType<{ children?: ReactNode }>;

	export type MediaUploadRenderProps = {
		open: () => void;
	};

	export type MediaUploadSelection = {
		id?: number;
	};

	export const MediaUpload: ComponentType<{
		onSelect: (media: MediaUploadSelection) => void;
		allowedTypes?: string[];
		value?: number;
		render: (props: MediaUploadRenderProps) => ReactNode;
	}>;
}
