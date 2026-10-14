import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';
import {
	BaseControl,
	Button,
	ColorPicker,
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
	type SourceDefinition,
	type SourceQrDocument,
	type TrackingItem,
	defaultDocument,
	documentRestPath,
	mergeDefinitionsWithDocument,
	mergeTrackingItems,
	webinarPathFromTemplate,
} from '@/domain/document';

const PLUGIN_ID = 's2j-webinar-source-qr';

function getBootstrap() {
	return (
		window.s2jWebinarSourceQrData ?? {
			restNamespace: 's2j-webinar-source-qr/v1',
			metaKey: '_s2j_webinar_source_qr',
			s2jWebinarActive: false,
			webinarDefinitionsPathTemplate:
				'/s2j-webinar/v1/events/%d/tracking-source-definitions',
			webinarTrackingPathTemplate:
				'/s2j-webinar/v1/events/%d/tracking-sources',
		}
	);
}

function channelDisplayLabel(row: { id: string; label: string }): string {
	return row.label.trim() !== '' ? row.label : row.id;
}

function normalizeHexColor(input: string): string {
	const value = input.trim();
	if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
		return value;
	}
	if (/^[0-9A-Fa-f]{6}$/.test(value)) {
		return `#${value}`;
	}
	return value;
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
	const [catalogNotice, setCatalogNotice] = useState<string | null>(null);
	const [unassigned, setUnassigned] = useState<TrackingItem[]>([]);
	const [catalogReady, setCatalogReady] = useState(false);
	const [dirty, setDirty] = useState(false);

	const webinarMeta = useSelect((select) => {
		const meta =
			(select(editorStore).getEditedPostAttribute('meta') as Record<
				string,
				unknown
			>) ?? {};
		return {
			webinarId: String(meta._s2j_webinar_id ?? '').trim(),
			provider: String(meta._s2j_webinar_provider ?? '').trim() || 'zoom',
		};
	}, []);

	const readOnly = !catalogReady;

	const canImport = useMemo(() => {
		return (
			catalogReady &&
			bootstrap.s2jWebinarActive &&
			webinarMeta.webinarId.length > 0 &&
			document.provider === 'zoom'
		);
	}, [
		bootstrap.s2jWebinarActive,
		catalogReady,
		document.provider,
		webinarMeta.webinarId,
	]);

	const loadCatalogAndMerge = useCallback(
		async (baseDoc: SourceQrDocument) => {
			setCatalogNotice(null);
			if (!bootstrap.s2jWebinarActive) {
				setCatalogReady(false);
				return baseDoc;
			}

			try {
				const path = webinarPathFromTemplate(
					bootstrap.webinarDefinitionsPathTemplate,
					postId
				);
				const response = (await apiFetch({ path })) as {
					sources?: SourceDefinition[];
				};
				const sources = Array.isArray(response?.sources)
					? response.sources
					: [];
				if (sources.length < 1) {
					setCatalogReady(false);
					setCatalogNotice(
						__(
							'Saving requires S2J Webinar and a source catalog.',
							's2j-webinar-source-qr'
						)
					);
					return baseDoc;
				}
				setCatalogReady(true);
				return mergeDefinitionsWithDocument(baseDoc, sources);
			} catch {
				setCatalogReady(false);
				setCatalogNotice(
					__(
						'Saving requires S2J Webinar and a source catalog.',
						's2j-webinar-source-qr'
					)
				);
				return baseDoc;
			}
		},
		[bootstrap.s2jWebinarActive, bootstrap.webinarDefinitionsPathTemplate, postId]
	);

	const loadDocument = useCallback(async () => {
		setLoading(true);
		setNotice(null);
		try {
			const data = (await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId),
			})) as SourceQrDocument;
			const merged = { ...defaultDocument(), ...data };
			const withRows = await loadCatalogAndMerge(merged);
			setDocument(withRows);
			setDirty(false);
			setUnassigned([]);
		} catch {
			setNotice(
				__(
					'Could not load the source QR document.',
					's2j-webinar-source-qr'
				)
			);
			setDocument(defaultDocument());
			setCatalogReady(false);
		} finally {
			setLoading(false);
		}
	}, [bootstrap.restNamespace, loadCatalogAndMerge, postId]);

	useEffect(() => {
		void loadDocument();
	}, [loadDocument]);

	const saveDraft = async (doc: SourceQrDocument = document) => {
		setSaving(true);
		setNotice(null);
		try {
			const saved = (await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId, 'draft'),
				method: 'POST',
				data: doc,
			})) as SourceQrDocument;
			const withRows = await loadCatalogAndMerge(saved);
			setDocument(withRows);
			setDirty(false);
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
			const path = webinarPathFromTemplate(
				bootstrap.webinarTrackingPathTemplate,
				postId
			);
			const response = (await apiFetch({
				path,
			})) as { items?: TrackingItem[] };
			const items = Array.isArray(response?.items) ? response.items : [];
			const merged = mergeTrackingItems(document, items);
			setUnassigned(merged.unassigned);
			await saveDraft(merged.document);
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
			setDirty(false);
		} catch (error: unknown) {
			const message =
				(error as { message?: string })?.message ??
				__('Apply failed.', 's2j-webinar-source-qr');
			setNotice(message);
		} finally {
			setSaving(false);
		}
	};

	const updateUrl = (id: string, url: string) => {
		setDirty(true);
		setDocument((prev) => ({
			...prev,
			channels: prev.channels.map((row) =>
				row.id === id ? { ...row, registration_url: url } : row
			),
		}));
	};

	const saveDisabled = saving || readOnly;
	const statusLabel =
		document.status === 'ready'
			? __('Document status: Ready', 's2j-webinar-source-qr')
			: __('Document status: Draft', 's2j-webinar-source-qr');

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
				<p>{statusLabel}</p>
			</div>

			{catalogNotice && (
				<Notice status="warning" isDismissible={false}>
					{catalogNotice}
				</Notice>
			)}

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
							{document.channels.length === 0 && (
								<p>
									{__(
										'No source rows. Configure S2J Webinar source definitions for this event.',
										's2j-webinar-source-qr'
									)}
								</p>
							)}
							{document.channels.map((row) => (
								<TextControl
									key={row.id}
									label={channelDisplayLabel(row)}
									value={row.registration_url}
									onChange={(value) => updateUrl(row.id, value)}
									disabled={readOnly}
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
										'Import is available when the source catalog is ready, S2J Webinar is active, the event has a webinar ID, and provider is zoom.',
										's2j-webinar-source-qr'
									)}
								</p>
							)}
							{unassigned.length > 0 && (
								<Notice status="warning" isDismissible={false}>
									{__(
										'Some imported source names did not match the current catalog.',
										's2j-webinar-source-qr'
									)}
								</Notice>
							)}
						</>
					)}

					{2 === step && (
						<BaseControl
							className="s2j-webinar-source-qr-modal__color"
							label={__('Module color', 's2j-webinar-source-qr')}
							help={__(
								'QR module color (#RRGGBB). Uses the same control as other WordPress admin screens.',
								's2j-webinar-source-qr'
							)}
						>
							<ColorPicker
								color={document.style.foreground_hex || '#000000'}
								enableAlpha={false}
								onChange={(value) => {
									if (readOnly) {
										return;
									}
									setDirty(true);
									setDocument((prev) => ({
										...prev,
										style: {
											...prev.style,
											foreground_hex: normalizeHexColor(value),
										},
									}));
								}}
							/>
							<TextControl
								label={__('Hex', 's2j-webinar-source-qr')}
								value={document.style.foreground_hex}
								disabled={readOnly}
								onChange={(value) => {
									setDirty(true);
									setDocument((prev) => ({
										...prev,
										style: {
											...prev.style,
											foreground_hex: normalizeHexColor(value),
										},
									}));
								}}
							/>
						</BaseControl>
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
				<Button
					variant="secondary"
					onClick={() => void saveDraft()}
					disabled={saveDisabled || !dirty}
				>
					{__('Save draft', 's2j-webinar-source-qr')}
				</Button>
				{4 === step && (
					<Button
						variant="primary"
						onClick={() => void applyDocument()}
						disabled={saveDisabled}
					>
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
