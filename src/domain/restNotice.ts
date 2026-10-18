export type PanelNoticeStatus = 'info' | 'warning' | 'error';

export type PanelNotice = {
	message: string;
	status: PanelNoticeStatus;
};

type RestErrorShape = {
	code?: string;
	message?: string;
	data?: { status?: number };
};

export function restErrorCode(error: unknown): string | undefined {
	if (!error || typeof error !== 'object') {
		return undefined;
	}
	const code = (error as RestErrorShape).code;
	return typeof code === 'string' && code.length > 0 ? code : undefined;
}

export function restErrorMessage(error: unknown, fallback: string): string {
	if (error && typeof error === 'object') {
		const message = (error as RestErrorShape).message;
		if (typeof message === 'string' && message.length > 0) {
			return message;
		}
	}
	return fallback;
}

/** Map REST `code` to Gutenberg Notice status (admin_ui_spec / persistence_spec). */
export function noticeStatusForRestError(error: unknown): PanelNoticeStatus {
	const code = restErrorCode(error);
	switch (code) {
		case 'catalog_unavailable':
		case 'invalid_registration_url':
		case 'invalid_document':
		case 'qr_generation_failed':
		case 'meta_update_failed':
			return 'error';
		default: {
			const status =
				error && typeof error === 'object'
					? (error as RestErrorShape).data?.status
					: undefined;
			if (typeof status === 'number' && status >= 500) {
				return 'error';
			}
			if (typeof status === 'number' && status >= 400) {
				return 'error';
			}
			return 'warning';
		}
	}
}

export function panelNoticeFromRestError(
	error: unknown,
	fallback: string
): PanelNotice {
	return {
		message: restErrorMessage(error, fallback),
		status: noticeStatusForRestError(error),
	};
}

export function panelNoticeSuccess(message: string): PanelNotice {
	return { message, status: 'info' };
}
