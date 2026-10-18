/**
 * Panel document shape (mirrors includes/Domain/document.php).
 */

export type ChannelRow = {
	id: string;
	label: string;
	registration_url: string;
	qr_attachment_id: number;
};

export type DocumentStyle = {
	icon_attachment_id: number;
	foreground_hex: string;
	size_px: number;
};

export type SourceQrDocument = {
	status: 'draft' | 'ready';
	provider: string;
	channels: ChannelRow[];
	style: DocumentStyle;
	updated_at: string;
};

export type TrackingItem = {
	source_name: string;
	registration_url: string;
	external_id?: string;
};

export type SourceDefinition = {
	source_name: string;
	label: string;
};

/** Slugs that support Webinar `tracking-sources` import in the maintenance UI (v0.0.1). */
export const IMPORT_TRACKING_SUPPORTED_PROVIDERS = ['zoom'] as const;

export type ImportTrackingProvider =
	(typeof IMPORT_TRACKING_SUPPORTED_PROVIDERS)[number];

export function providerSupportsTrackingImport(provider: string): boolean {
	const slug = provider.trim().toLowerCase();
	return (IMPORT_TRACKING_SUPPORTED_PROVIDERS as readonly string[]).includes(
		slug
	);
}

export function defaultStyle(): DocumentStyle {
	return {
		icon_attachment_id: 0,
		foreground_hex: '#000000',
		size_px: 512,
	};
}

export function defaultDocument(): SourceQrDocument {
	return {
		status: 'draft',
		provider: '',
		channels: [],
		style: defaultStyle(),
		updated_at: '',
	};
}

export function mergeDefinitionsWithDocument(
	document: SourceQrDocument,
	definitions: SourceDefinition[]
): SourceQrDocument {
	const byId = new Map(
		document.channels.map((row) => [row.id, { ...row }] as const)
	);

	const channels: ChannelRow[] = definitions.map((def) => {
		const existing = byId.get(def.source_name);
		return {
			id: def.source_name,
			label: def.label ?? '',
			registration_url: existing?.registration_url ?? '',
			qr_attachment_id: existing?.qr_attachment_id ?? 0,
		};
	});

	return {
		...document,
		channels,
	};
}

export function mergeTrackingItems(
	document: SourceQrDocument,
	items: TrackingItem[]
): { document: SourceQrDocument; unassigned: TrackingItem[] } {
	const next: SourceQrDocument = {
		...document,
		channels: document.channels.map((row) => ({ ...row })),
	};
	const unassigned: TrackingItem[] = [];
	const knownIds = new Set(next.channels.map((row) => row.id));

	for (const item of items) {
		const name = item.source_name?.trim() ?? '';
		const url = item.registration_url?.trim() ?? '';
		if (!name || !url) {
			continue;
		}

		if (knownIds.has(name)) {
			next.channels = next.channels.map((row) =>
				row.id === name ? { ...row, registration_url: url } : row
			);
		} else {
			unassigned.push(item);
		}
	}

	return { document: next, unassigned };
}

export function documentRestPath(
	restNamespace: string,
	postId: number,
	suffix: '' | 'draft' | 'apply' | 'preview' = ''
): string {
	const base = `/${restNamespace}/events/${postId}/document`;
	if ('draft' === suffix) {
		return `${base}/draft`;
	}
	if ('apply' === suffix) {
		return `${base}/apply`;
	}
	if ('preview' === suffix) {
		return `${base}/preview`;
	}
	return base;
}

export function webinarPathFromTemplate(
	template: string,
	postId: number
): string {
	return template.replace('%d', String(postId));
}
