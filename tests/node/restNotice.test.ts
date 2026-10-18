import { describe, expect, it } from 'vitest';
import {
	noticeStatusForRestError,
	panelNoticeFromRestError,
	restErrorCode,
} from '../../src/domain/restNotice';

describe('restNotice', () => {
	it('reads REST error code', () => {
		expect(restErrorCode({ code: 'invalid_registration_url' })).toBe(
			'invalid_registration_url'
		);
		expect(restErrorCode(null)).toBeUndefined();
	});

	it('maps known codes to error status', () => {
		expect(
			noticeStatusForRestError({ code: 'qr_generation_failed' })
		).toBe('error');
		expect(noticeStatusForRestError({ code: 'catalog_unavailable' })).toBe(
			'error'
		);
	});

	it('builds panel notice from REST payload', () => {
		const notice = panelNoticeFromRestError(
			{
				code: 'invalid_registration_url',
				message: 'One or more registration URLs are invalid.',
			},
			'fallback'
		);
		expect(notice.status).toBe('error');
		expect(notice.message).toContain('invalid');
	});
});
