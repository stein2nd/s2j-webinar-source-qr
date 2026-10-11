import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';
import {
	Button,
	Modal,
	TextControl,
	Notice,
	Spinner,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import '@/styles/gutenberg.scss';
import {
	type ChannelId,
	type SourceQrDocument,
	type TrackingItem,
	defaultDocument,
	documentRestPath,
	mergeTrackingItems,
} from '@/domain/document';

const PLUGIN_ID = 's2j-webinar-source-qr';

function channelLabel(id: ChannelId): string {
	switch (id) {
		case 'web':
			return __('Web', 's2j-webinar-source-qr');
		case 'sns':
			return __('Social', 's2j-webinar-source-qr');
		case 'other':
			return __('Other', 's2j-webinar-source-qr');
		default:
			return id;
	}
}

function getBootstrap() {
	return window.s2jWebinarSourceQrData ?? {
		restNamespace: 's2j-webinar-source-qr/v1',
		metaKey: '_s2j_webinar_source_qr',
		webinarTrackingPathTemplate:
			'/s2j-webinar/v1/events/%d/tracking-sources',
		webinarPluginActive: false,
		channelIds: ['web', 'sns', 'other'],
	};
}

const MaintenanceModal = ({
	postId,
	onClose,
}: {
	postId: number;
	onClose: () => void;
}) => {
	const bootstrap = getBootstrap();
	const [step, setStep] = useState(1);
	const [document, setDocument] = useState<SourceQrDocument>(defaultDocument());
	const [loading, setLoading] = useState(true);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState<string | null>(null);
	const [unassigned, setUnassigned] = useState<TrackingItem[]>([]);

	const webinarMeta = useSelect((select) => {
		const meta =
			(select(editorStore).getEditedPostAttribute('meta') as Record<
				string,
				unknown
			>) ?? {};
		return {
			webinarId: String(meta._s2j_webinar_id ?? ''),
			provider: String(meta._s2j_webinar_provider ?? 'zoom') || 'zoom',
		};
	}, []);

	const canImport = useMemo(() => {
		return (
			bootstrap.webinarPluginActive &&
			webinarMeta.webinarId.length > 0 &&
			'zoom' === webinarMeta.provider
		);
	}, [bootstrap.webinarPluginActive, webinarMeta]);

	const loadDocument = useCallback(async () => {
		setLoading(true);
		setNotice(null);
		try {
			const data = (await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId),
			})) as SourceQrDocument;
			setDocument({ ...defaultDocument(), ...data });
		} catch {
			setNotice(
				__(
					'Could not load the source QR document.',
					's2j-webinar-source-qr'
				)
			);
			setDocument(defaultDocument());
		} finally {
			setLoading(false);
		}
	}, [bootstrap.restNamespace, postId]);

	useEffect(() => {
		void loadDocument();
	}, [loadDocument]);

	const saveDraft = async () => {
		setSaving(true);
		setNotice(null);
		try {
			const saved = (await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId, 'draft'),
				method: 'POST',
				data: document,
			})) as SourceQrDocument;
			setDocument(saved);
			setNotice(__('Draft saved.', 's2j-webinar-source-qr'));
		} catch (error: unknown) {
			const message =
				(error as { message?: string })?.message ??
				__('Draft save failed.', 's2j-webinar-source-qr');
			setNotice(message);
		} finally {
			setSaving(false);
		}
	};

	const runImport = async () => {
		if (!canImport) {
			return;
		}
		setSaving(true);
		setNotice(null);
		try {
			const path = bootstrap.webinarTrackingPathTemplate.replace(
				'%d',
				String(postId)
			);
			const response = (await apiFetch({
				path,
			})) as { items?: TrackingItem[] };
			const items = Array.isArray(response?.items) ? response.items : [];
			const merged = mergeTrackingItems(document, items);
			setDocument(merged.document);
			setUnassigned(merged.unassigned);
			setNotice(
				sprintf(
					/* translators: %d: number of imported rows matched to channels */
					__('Import finished (%d rows matched).', 's2j-webinar-source-qr'),
					items.length - merged.unassigned.length
				)
			);
		} catch (error: unknown) {
			const message =
				(error as { message?: string })?.message ??
				__('Import failed.', 's2j-webinar-source-qr');
			setNotice(message);
		} finally {
			setSaving(false);
		}
	};

	const applyDocument = async () => {
		setSaving(true);
		setNotice(null);
		try {
			await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId, 'apply'),
				method: 'POST',
				data: document,
			});
			setNotice(__('Applied.', 's2j-webinar-source-qr'));
		} catch (error: unknown) {
			const message =
				(error as { message?: string })?.message ??
				__('Apply failed.', 's2j-webinar-source-qr');
			setNotice(message);
		} finally {
			setSaving(false);
		}
	};

	const updateUrl = (id: ChannelId, url: string) => {
		setDocument((prev) => ({
			...prev,
			channels: prev.channels.map((row) =>
				row.id === id ? { ...row, registration_url: url } : row
			),
		}));
	};

	return (
		<Modal
			title={__('Source tracking / QR', 's2j-webinar-source-qr')}
			onRequestClose={onClose}
			size="large"
		>
			<div className="s2j-webinar-source-qr-modal__steps">
				<Heading level={3}>
					{sprintf(
						/* translators: %1$d: current step, %2$d: total steps */
						__('Step %1$d of %2$d', 's2j-webinar-source-qr'),
						step,
						4
					)}
				</Heading>
			</div>

			{notice && (
				<Notice status="info" isDismissible onRemove={() => setNotice(null)}>
					{notice}
				</Notice>
			)}

			{loading ? (
				<Spinner />
			) : (
				<>
					{1 === step && (
						<>
							{document.channels.map((row) => (
								<TextControl
									key={row.id}
									label={channelLabel(row.id)}
									value={row.registration_url}
									onChange={(value) => updateUrl(row.id, value)}
									help={__(
										'Paste the registration URL from the provider portal.',
										's2j-webinar-source-qr'
									)}
								/>
							))}
							{canImport ? (
								<Button
									variant="secondary"
									onClick={() => void runImport()}
									disabled={saving}
								>
									{__('Import from provider', 's2j-webinar-source-qr')}
								</Button>
							) : (
								<p>
									{__(
										'Import is available when S2J Webinar is active and the event has a provider webinar ID.',
										's2j-webinar-source-qr'
									)}
								</p>
							)}
							{unassigned.length > 0 && (
								<Notice status="warning" isDismissible={false}>
									{__(
										'Some imported source names did not match web, sns, or other.',
										's2j-webinar-source-qr'
									)}
								</Notice>
							)}
						</>
					)}

					{2 === step && (
						<TextControl
							label={__('Module color (hex)', 's2j-webinar-source-qr')}
							value={document.style.foreground_hex}
							onChange={(value) =>
								setDocument((prev) => ({
									...prev,
									style: { ...prev.style, foreground_hex: value },
								}))
							}
						/>
					)}

					{3 === step && (
						<p>
							{__(
								'QR preview will render here in a later milestone.',
								's2j-webinar-source-qr'
							)}
						</p>
					)}

					{4 === step && (
						<p>
							{__(
								'Apply saves ready status and PNG media when the server pipeline is complete.',
								's2j-webinar-source-qr'
							)}
						</p>
					)}
				</>
			)}

			<div className="s2j-webinar-source-qr-panel">
				<Button
					variant="secondary"
					onClick={() => setStep((s) => Math.max(1, s - 1))}
					disabled={step <= 1 || saving}
				>
					{__('Back', 's2j-webinar-source-qr')}
				</Button>
				<Button
					variant="secondary"
					onClick={() => setStep((s) => Math.min(4, s + 1))}
					disabled={step >= 4 || saving}
				>
					{__('Next', 's2j-webinar-source-qr')}
				</Button>
				<Button variant="secondary" onClick={() => void saveDraft()} disabled={saving}>
					{__('Save draft', 's2j-webinar-source-qr')}
				</Button>
				{4 === step && (
					<Button variant="primary" onClick={() => void applyDocument()} disabled={saving}>
						{__('Apply', 's2j-webinar-source-qr')}
					</Button>
				)}
			</div>
		</Modal>
	);
};

const SourceQrFill = () => {
	const postId = useSelect(
		(select) => select(editorStore).getCurrentPostId() as number,
		[]
	);
	const [open, setOpen] = useState(false);

	return (
		<Fill name="EventPluginDocumentSettings">
			<div className="s2j-webinar-source-qr-panel">
				<Button variant="secondary" onClick={() => setOpen(true)}>
					{__('Edit source tracking / QR', 's2j-webinar-source-qr')}
				</Button>
			</div>
			{open && postId > 0 && (
				<MaintenanceModal postId={postId} onClose={() => setOpen(false)} />
			)}
		</Fill>
	);
};

registerPlugin(PLUGIN_ID, {
	render: SourceQrFill,
});
