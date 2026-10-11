/**
 * Panel document shape (mirrors includes/Domain/document.php).
 */

export type ChannelId = 'web' | 'sns' | 'other';

export type ChannelRow = {
	id: ChannelId;
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

export const CHANNEL_IDS: ChannelId[] = ['web', 'sns', 'other'];

export function defaultStyle(): DocumentStyle {
	return {
		icon_attachment_id: 0,
		foreground_hex: '#000000',
		size_px: 512,
	};
}

export function defaultChannels(): ChannelRow[] {
	return CHANNEL_IDS.map((id) => ({
		id,
		label: '',
		registration_url: '',
		qr_attachment_id: 0,
	}));
}

export function defaultDocument(): SourceQrDocument {
	return {
		status: 'draft',
		provider: '',
		channels: defaultChannels(),
		style: defaultStyle(),
		updated_at: '',
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

	for (const item of items) {
		const name = item.source_name?.trim() ?? '';
		const url = item.registration_url?.trim() ?? '';
		if (!name || !url) {
			continue;
		}

		const index = next.channels.findIndex((row) => row.id === name);
		if (index >= 0) {
			next.channels[index].registration_url = url;
		} else {
			unassigned.push(item);
		}
	}

	return { document: next, unassigned };
}

export function documentRestPath(
	restNamespace: string,
	postId: number,
	suffix: '' | 'draft' | 'apply' = ''
): string {
	const base = `/${restNamespace}/events/${postId}/document`;
	if ('draft' === suffix) {
		return `${base}/draft`;
	}
	if ('apply' === suffix) {
		return `${base}/apply`;
	}
	return base;
}
