import { registerPlugin } from '@wordpress/plugins';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PluginDocumentSettingPanel as EditorPluginDocumentSettingPanel } from '@wordpress/editor';
import {
	BaseControl,
	Button,
	ColorPicker,
	ExternalLink,
	Modal,
	PanelRow,
	TextControl,
	Notice,
	Spinner,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addAction, removeAction } from '@wordpress/hooks';
import '@/styles/gutenberg.scss';
import {
	type ChannelRow,
	type DocumentStyle,
	type SourceDefinition,
	type SourceQrDocument,
	type TrackingItem,
	defaultDocument,
	documentRestPath,
	IMPORT_TRACKING_SUPPORTED_PROVIDERS,
	mergeDefinitionsWithDocument,
	mergeTrackingItems,
	providerSupportsTrackingImport,
	webinarPathFromTemplate,
} from '@/domain/document';
import {
	type PanelNotice,
	panelNoticeFromRestError,
	panelNoticeSuccess,
} from '@/domain/restNotice';

const PLUGIN_ID = 's2j-webinar-source-qr';

const EDITOR_STORE = 'core/editor';

const HANDOFF_HOOK_NAMESPACE = 's2j-webinar-source-qr/handoff';

const DEFAULT_WEBINAR_HANDOFF_OPEN_HOOK = 's2j_webinar.open_source_qr';

function webinarHandoffOpenHook(): string {
	return (
		window.s2jWebinarSourceQrData?.webinarHandoffOpenHook ??
		DEFAULT_WEBINAR_HANDOFF_OPEN_HOOK
	);
}

function parseHandoffPostId(postId: unknown): number {
	if (typeof postId === 'number' && Number.isFinite(postId)) {
		return Math.trunc(postId);
	}
	const parsed = parseInt(String(postId), 10);
	return Number.isFinite(parsed) ? parsed : 0;
}

let openMaintenanceModal: ((postId: number) => void) | undefined;

function requestOpenMaintenanceModal(postId: number): void {
	if (postId > 0) {
		openMaintenanceModal?.(postId);
	}
}

type DocumentSettingPanel = typeof EditorPluginDocumentSettingPanel;

/** Prefer `wp.editor`; older sites may still expose the panel on `wp.editPost`. */
function resolveDocumentSettingPanel(): DocumentSettingPanel | undefined {
	if (EditorPluginDocumentSettingPanel) {
		return EditorPluginDocumentSettingPanel;
	}
	const wp = (
		globalThis as typeof globalThis & {
			wp?: {
				editPost?: {
					PluginDocumentSettingPanel?: DocumentSettingPanel;
				};
			};
		}
	).wp;
	return wp?.editPost?.PluginDocumentSettingPanel;
}

/** Avoid minifier reusing one-letter vars with inner panel state (breaks Panel component ref). */
const s2jSourceQrDocumentSettings = {
	panelComponent: resolveDocumentSettingPanel(),
};

const PLUGIN_PANEL_TITLE = __('S2J Webinar Source QR', 's2j-webinar-source-qr');

function getBootstrap() {
	return (
		window.s2jWebinarSourceQrData ?? {
			restNamespace: 's2j-webinar-source-qr/v1',
			metaKey: '_s2j_webinar_source_qr',
			s2jWebinarInstalled: false,
			s2jWebinarActive: false,
			webinarInstallGuideUrl:
				'https://github.com/stein2nd/s2j-webinar#installation',
			webinarActivateUrl: '',
			webinarDefinitionsPathTemplate:
				'/s2j-webinar/v1/events/%d/tracking-source-definitions',
			webinarTrackingPathTemplate:
				'/s2j-webinar/v1/events/%d/tracking-sources',
		}
	);
}

function WebinarDependencyNotice() {
	const bootstrap = getBootstrap();

	if (bootstrap.s2jWebinarActive) {
		return null;
	}

	if (!bootstrap.s2jWebinarInstalled) {
		const guideUrl = bootstrap.webinarInstallGuideUrl;
		return (
			<PanelRow>
				<Notice status="warning" isDismissible={false}>
					<p>
						{__(
							'Install S2J Webinar to manage source tracking URLs and QR codes.',
							's2j-webinar-source-qr'
						)}{' '}
						{guideUrl ? (
							<ExternalLink href={guideUrl}>
								{__('Installation guide', 's2j-webinar-source-qr')}
							</ExternalLink>
						) : null}
					</p>
				</Notice>
			</PanelRow>
		);
	}

	const activateUrl = bootstrap.webinarActivateUrl;
	return (
		<PanelRow>
			<Notice status="warning" isDismissible={false}>
				<p>
					{__(
						'Enable S2J Webinar to manage source tracking URLs and QR codes.',
						's2j-webinar-source-qr'
					)}{' '}
					{activateUrl ? (
						<a href={activateUrl}>
							{__('Activate S2J Webinar', 's2j-webinar-source-qr')}
						</a>
					) : null}
				</p>
			</Notice>
		</PanelRow>
	);
}

function channelDisplayLabel(row: { id: string; label: string }): string {
	return row.label.trim() !== '' ? row.label : row.id;
}

const QR_OUTPUT_SIZE_MIN_PX = 256;
const QR_OUTPUT_SIZE_MAX_PX = 2048;

function clampSizePx(value: number): number {
	return Math.max(
		QR_OUTPUT_SIZE_MIN_PX,
		Math.min(QR_OUTPUT_SIZE_MAX_PX, Math.round(value))
	);
}

function parseSizePxInput(raw: string): number | null {
	const trimmed = raw.trim();
	if (trimmed === '') {
		return null;
	}
	const parsed = Number.parseInt(trimmed, 10);
	if (!Number.isFinite(parsed)) {
		return null;
	}
	return parsed;
}

function isValidHexColor(input: string): boolean {
	return /^#[0-9A-Fa-f]{6}$/.test(input.trim());
}

function normalizeHexColor(input: string): string {
	const value = input.trim();
	if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
		return value.toLowerCase();
	}
	if (/^[0-9A-Fa-f]{6}$/.test(value)) {
		return `#${value.toLowerCase()}`;
	}
	return value;
}

/** Restrict typing to # plus up to six hex digits (sRGB storage format). */
function sanitizeHexInput(input: string): string {
	const stripped = input.replace(/[^#0-9A-Fa-f]/gi, '');
	if (stripped.startsWith('#')) {
		return `#${stripped.slice(1).replace(/#/g, '').slice(0, 6)}`;
	}
	const digits = stripped.replace(/#/g, '').slice(0, 6);
	return digits.length > 0 ? `#${digits}` : '';
}

/**
 * Module color picker + hex field (Illustrator-style live hex, commit on pointer up / hex blur).
 */
const ModuleColorControl = ({
	hex,
	readOnly,
	onCommit,
}: {
	hex: string;
	readOnly: boolean;
	onCommit: (nextHex: string) => void;
}) => {
	const committedHex = isValidHexColor(hex) ? normalizeHexColor(hex) : '#000000';
	const [liveHex, setLiveHex] = useState(committedHex);
	/** Bump when hex field reaches a valid #RRGGBB so Picker remounts (WP ColorPicker HSLA sync). */
	const [hexFieldSyncKey, setHexFieldSyncKey] = useState(0);

	useEffect(() => {
		const next = isValidHexColor(hex) ? normalizeHexColor(hex) : '#000000';
		setLiveHex(next);
	}, [hex]);

	const normalizedLiveHex = normalizeHexColor(liveHex);
	const pickerColor = isValidHexColor(normalizedLiveHex)
		? normalizedLiveHex
		: committedHex;

	const commitHex = useCallback(
		(next: string) => {
			const normalized = normalizeHexColor(next);
			if (!isValidHexColor(normalized)) {
				return;
			}
			setLiveHex(normalized);
			onCommit(normalized);
		},
		[onCommit]
	);

	const applyLiveHexToPicker = useCallback((raw: string) => {
		const sanitized = sanitizeHexInput(raw);
		setLiveHex(sanitized);
		const normalized = normalizeHexColor(sanitized);
		if (isValidHexColor(normalized)) {
			setHexFieldSyncKey((key) => key + 1);
		}
	}, []);

	return (
		<div
			className="s2j-webinar-source-qr-modal__color-picker-wrap"
			onPointerUp={() => {
				if (!readOnly && isValidHexColor(normalizeHexColor(liveHex))) {
					commitHex(liveHex);
				}
			}}
		>
			<div className="s2j-webinar-source-qr-modal__color-hex-row">
				<div className="s2j-webinar-source-qr-modal__color-hex-row__picker">
					<span className="components-base-control__label">
						{__('Module color', 's2j-webinar-source-qr')}
					</span>
					{/*
					 * Controlled `color` keeps document + hex field in sync with the picker.
					 * `hexFieldSyncKey` remounts only after a complete typed hex (not each drag frame).
					 */}
					<ColorPicker
						key={`hex-sync-${hexFieldSyncKey}`}
						color={pickerColor}
						enableAlpha={false}
						onChange={(value) => {
							setLiveHex(normalizeHexColor(value));
						}}
					/>
				</div>
				<div className="s2j-webinar-source-qr-modal__color-hex-row__hex">
					<TextControl
						label={__('Hex', 's2j-webinar-source-qr')}
						value={liveHex}
						onChange={applyLiveHexToPicker}
						onBlur={() => {
							const normalized = normalizeHexColor(liveHex);
							if (isValidHexColor(normalized)) {
								setLiveHex(normalized);
								setHexFieldSyncKey((key) => key + 1);
								if (!readOnly) {
									commitHex(normalized);
								}
								return;
							}
							setLiveHex(committedHex);
						}}
					/>
				</div>
			</div>
		</div>
	);
};

const CENTER_ICON_MEDIA_TYPES = ['image'];

/**
 * Center QR icon — media library attachment ID (optional).
 */
const CenterIconControl = ({
	attachmentId,
	onChange,
}: {
	attachmentId: number;
	onChange: (nextId: number) => void;
}) => {
	const [previewUrl, setPreviewUrl] = useState('');

	useEffect(() => {
		if (attachmentId <= 0) {
			setPreviewUrl('');
			return;
		}
		let cancelled = false;
		void apiFetch<{ source_url?: string }>({
			path: `/wp/v2/media/${attachmentId}?context=view`,
		})
			.then((record) => {
				if (!cancelled) {
					setPreviewUrl(record.source_url ?? '');
				}
			})
			.catch(() => {
				if (!cancelled) {
					setPreviewUrl('');
				}
			});
		return () => {
			cancelled = true;
		};
	}, [attachmentId]);

	return (
		<div className="s2j-webinar-source-qr-modal__center-icon">
			{attachmentId > 0 && previewUrl !== '' && (
				<img
					className="s2j-webinar-source-qr-modal__center-icon-preview"
					src={previewUrl}
					alt=""
				/>
			)}
			<MediaUploadCheck>
				<MediaUpload
					onSelect={(media) => {
						if (!media?.id) {
							return;
						}
						onChange(media.id);
					}}
					allowedTypes={CENTER_ICON_MEDIA_TYPES}
					value={attachmentId > 0 ? attachmentId : undefined}
					render={({ open }) => (
						<div className="s2j-webinar-source-qr-modal__center-icon-actions">
							<Button variant="secondary" onClick={open}>
								{attachmentId > 0
									? __('Replace image', 's2j-webinar-source-qr')
									: __('Select image', 's2j-webinar-source-qr')}
							</Button>
							{attachmentId > 0 && (
								<Button
									variant="tertiary"
									onClick={() => {
										onChange(0);
									}}
								>
									{__('Remove', 's2j-webinar-source-qr')}
								</Button>
							)}
						</div>
					)}
				/>
			</MediaUploadCheck>
		</div>
	);
};

/**
 * QR output square size (px), clamped to server range on commit.
 */
const OutputSizeControl = ({
	sizePx,
	readOnly,
	onCommit,
}: {
	sizePx: number;
	readOnly: boolean;
	onCommit: (nextSizePx: number) => void;
}) => {
	const committed = clampSizePx(sizePx || 512);
	const [draft, setDraft] = useState(String(committed));

	useEffect(() => {
		setDraft(String(clampSizePx(sizePx || 512)));
	}, [sizePx]);

	const commitDraft = useCallback(() => {
		if (readOnly) {
			return;
		}
		const parsed = parseSizePxInput(draft);
		if (parsed === null) {
			setDraft(String(committed));
			return;
		}
		const clamped = clampSizePx(parsed);
		setDraft(String(clamped));
		if (clamped !== committed) {
			onCommit(clamped);
		}
	}, [committed, draft, onCommit, readOnly]);

	return (
		<TextControl
			className="s2j-webinar-source-qr-modal__output-size"
			label={sprintf(
				/* translators: 1: minimum px, 2: maximum px */
				__('Output size (%1$s–%2$s px)', 's2j-webinar-source-qr'),
				String(QR_OUTPUT_SIZE_MIN_PX),
				String(QR_OUTPUT_SIZE_MAX_PX)
			)}
			type="number"
			min={QR_OUTPUT_SIZE_MIN_PX}
			max={QR_OUTPUT_SIZE_MAX_PX}
			step={1}
			value={draft}
			disabled={readOnly}
			onChange={(value) => {
				setDraft(value.replace(/[^\d]/g, ''));
			}}
			onBlur={commitDraft}
		/>
	);
};

type QrPreviewRowState =
	| { status: 'empty' }
	| { status: 'loading' }
	| { status: 'ok'; dataUrl: string }
	| { status: 'error'; message: string };

const QrPreviewStep = ({
	postId,
	restNamespace,
	channels,
	style,
}: {
	postId: number;
	restNamespace: string;
	channels: ChannelRow[];
	style: DocumentStyle;
}) => {
	const [previews, setPreviews] = useState<Record<string, QrPreviewRowState>>(
		{}
	);

	const previewSignature = useMemo(
		() =>
			JSON.stringify({
				style,
				rows: channels.map((row) => ({
					id: row.id,
					registration_url: row.registration_url,
				})),
			}),
		[channels, style]
	);

	useEffect(() => {
		let cancelled = false;
		const timer = window.setTimeout(() => {
			void (async () => {
				const initial: Record<string, QrPreviewRowState> = {};
				for (const row of channels) {
					const url = row.registration_url.trim();
					initial[row.id] =
						url === '' ? { status: 'empty' } : { status: 'loading' };
				}
				if (!cancelled) {
					setPreviews(initial);
				}

				await Promise.all(
					channels.map(async (row) => {
						const url = row.registration_url.trim();
						if (url === '') {
							return;
						}
						try {
							const response = await apiFetch<{ data_url: string }>({
								path: documentRestPath(restNamespace, postId, 'preview'),
								method: 'POST',
								data: {
									registration_url: url,
									style,
								},
							});
							if (cancelled) {
								return;
							}
							setPreviews((prev) => ({
								...prev,
								[row.id]: {
									status: 'ok',
									dataUrl: response.data_url,
								},
							}));
						} catch (error: unknown) {
							if (cancelled) {
								return;
							}
							const message =
								(error as { message?: string })?.message ??
								__('Preview failed.', 's2j-webinar-source-qr');
							setPreviews((prev) => ({
								...prev,
								[row.id]: { status: 'error', message },
							}));
						}
					})
				);
			})();
		}, 400);

		return () => {
			cancelled = true;
			window.clearTimeout(timer);
		};
	}, [previewSignature, postId, restNamespace, channels, style]);

	if (channels.length === 0) {
		return (
			<p>
				{__(
					'No source rows. Configure S2J Webinar source definitions for this event.',
					's2j-webinar-source-qr'
				)}
			</p>
		);
	}

	return (
		<div className="s2j-webinar-source-qr-modal__preview">
			<p className="s2j-webinar-source-qr-modal__preview-intro">
				{__(
					'Previews use the same rendering as Apply. Scan with your phone to verify.',
					's2j-webinar-source-qr'
				)}
			</p>
			<ul className="s2j-webinar-source-qr-modal__preview-list">
				{channels.map((row) => {
					const state = previews[row.id] ?? { status: 'loading' as const };
					return (
						<li
							key={row.id}
							className="s2j-webinar-source-qr-modal__preview-row"
						>
							<p className="s2j-webinar-source-qr-modal__preview-label">
								{channelDisplayLabel(row)}
							</p>
							{state.status === 'empty' && (
								<p className="s2j-webinar-source-qr-modal__preview-placeholder">
									{__(
										'Add a registration URL on step 1 to preview this QR.',
										's2j-webinar-source-qr'
									)}
								</p>
							)}
							{state.status === 'loading' && <Spinner />}
							{state.status === 'error' && (
								<Notice status="warning" isDismissible={false}>
									{state.message}
								</Notice>
							)}
							{state.status === 'ok' && (
								<img
									className="s2j-webinar-source-qr-modal__preview-image"
									src={state.dataUrl}
									alt=""
								/>
							)}
						</li>
					);
				})}
			</ul>
		</div>
	);
};

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
	const [notice, setNotice] = useState<PanelNotice | null>(null);
	const [catalogNotice, setCatalogNotice] = useState<string | null>(null);
	const [unassigned, setUnassigned] = useState<TrackingItem[]>([]);
	const [catalogReady, setCatalogReady] = useState(false);
	const [dirty, setDirty] = useState(false);

	const webinarMeta = useSelect((select) => {
		const getEditedPostAttribute = (
			select(EDITOR_STORE) as {
				getEditedPostAttribute?: (key: string) => unknown;
			}
		).getEditedPostAttribute;
		if (typeof getEditedPostAttribute !== 'function') {
			return { webinarId: '', provider: 'zoom' };
		}
		const meta = (getEditedPostAttribute('meta') as Record<string, unknown>) ?? {};
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
			providerSupportsTrackingImport(document.provider)
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
		} catch (error: unknown) {
			setNotice(
				panelNoticeFromRestError(
					error,
					__(
						'Could not load the source QR document.',
						's2j-webinar-source-qr'
					)
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
			setNotice(
				panelNoticeSuccess(
					__('Draft saved.', 's2j-webinar-source-qr')
				)
			);
		} catch (error: unknown) {
			setNotice(
				panelNoticeFromRestError(
					error,
					__('Draft save failed.', 's2j-webinar-source-qr')
				)
			);
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
				panelNoticeSuccess(
					sprintf(
						/* translators: %d: number of imported rows matched to channels */
						__(
							'Import finished (%d rows matched).',
							's2j-webinar-source-qr'
						),
						items.length - merged.unassigned.length
					)
				)
			);
		} catch (error: unknown) {
			setNotice(
				panelNoticeFromRestError(
					error,
					__('Import failed.', 's2j-webinar-source-qr')
				)
			);
		} finally {
			setSaving(false);
		}
	};

	const applyDocument = async () => {
		setSaving(true);
		setNotice(null);
		try {
			const saved = (await apiFetch({
				path: documentRestPath(bootstrap.restNamespace, postId, 'apply'),
				method: 'POST',
				data: document,
			})) as SourceQrDocument;
			const withRows = await loadCatalogAndMerge({
				...defaultDocument(),
				...saved,
			});
			setDocument(withRows);
			setNotice(
				panelNoticeSuccess(__('Applied.', 's2j-webinar-source-qr'))
			);
			setDirty(false);
		} catch (error: unknown) {
			setNotice(
				panelNoticeFromRestError(
					error,
					__('Apply failed.', 's2j-webinar-source-qr')
				)
			);
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
			className="s2j-webinar-source-qr-modal"
			title={PLUGIN_PANEL_TITLE}
			onRequestClose={onClose}
			size="large"
		>
			<div className="s2j-webinar-source-qr-modal__steps">
				<div className="s2j-webinar-source-qr-modal__steps-primary">
					<Heading level={3}>
						{sprintf(
							/* translators: %1$d: current step, %2$d: total steps */
							__('Step %1$d of %2$d', 's2j-webinar-source-qr'),
							step,
							4
						)}
					</Heading>
					<span className="s2j-webinar-source-qr-modal__status">
						{statusLabel}
					</span>
				</div>
				{catalogNotice && (
					<p
						className="s2j-webinar-source-qr-modal__catalog-notice"
						role="status"
					>
						{catalogNotice}
					</p>
				)}
			</div>

			{notice && (
				<Notice
					status={notice.status}
					isDismissible
					onRemove={() => setNotice(null)}
				>
					{notice.message}
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
									{sprintf(
										/* translators: %1$s: comma-separated provider slugs that support import. %2$s: resolved provider slug for this event. */
										__(
											'Import is available when the source catalog is ready, S2J Webinar is active, the event has a webinar ID, and the resolved provider (%2$s) supports import (%1$s).',
											's2j-webinar-source-qr'
										),
										IMPORT_TRACKING_SUPPORTED_PROVIDERS.join(', '),
										document.provider.trim() !== ''
											? document.provider
											: __('(not set)', 's2j-webinar-source-qr')
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
						<div className="s2j-webinar-source-qr-modal__step2">
							<div className="s2j-webinar-source-qr-modal__step2-style-row">
								<BaseControl className="s2j-webinar-source-qr-modal__color">
									<ModuleColorControl
										hex={document.style.foreground_hex || '#000000'}
										readOnly={readOnly}
										onCommit={(nextHex) => {
											setDirty(true);
											setDocument((prev) => ({
												...prev,
												style: {
													...prev.style,
													foreground_hex: nextHex,
												},
											}));
										}}
									/>
								</BaseControl>
								<BaseControl
									className="s2j-webinar-source-qr-modal__center-icon-control"
									label={__('Center icon', 's2j-webinar-source-qr')}
								>
									<CenterIconControl
										attachmentId={document.style.icon_attachment_id ?? 0}
										onChange={(nextId) => {
											if (!readOnly) {
												setDirty(true);
											}
											setDocument((prev) => ({
												...prev,
												style: {
													...prev.style,
													icon_attachment_id: nextId,
												},
											}));
										}}
									/>
								</BaseControl>
								<OutputSizeControl
									sizePx={document.style.size_px ?? 512}
									readOnly={readOnly}
									onCommit={(nextSizePx) => {
										setDirty(true);
										setDocument((prev) => ({
											...prev,
											style: {
												...prev.style,
												size_px: nextSizePx,
											},
										}));
									}}
								/>
							</div>
							{!readOnly && (
								<p className="s2j-webinar-source-qr-modal__step2-hint">
									{__(
										'Release the pointer or leave the Hex field to confirm module color.',
										's2j-webinar-source-qr'
									)}
								</p>
							)}
						</div>
					)}

					{3 === step && (
						<QrPreviewStep
							postId={postId}
							restNamespace={bootstrap.restNamespace}
							channels={document.channels}
							style={document.style}
						/>
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

const MaintenanceModalHost = () => {
	const [session, setSession] = useState<{ postId: number } | null>(null);

	useEffect(() => {
		openMaintenanceModal = (postId: number) => setSession({ postId });
		return () => {
			openMaintenanceModal = undefined;
		};
	}, []);

	useEffect(() => {
		const onHandoff = (postId: unknown) => {
			requestOpenMaintenanceModal(parseHandoffPostId(postId));
		};
		const hook = webinarHandoffOpenHook();
		addAction(hook, HANDOFF_HOOK_NAMESPACE, onHandoff);
		return () => {
			removeAction(hook, HANDOFF_HOOK_NAMESPACE);
		};
	}, []);

	if (!session || session.postId <= 0) {
		return null;
	}

	return (
		<MaintenanceModal
			postId={session.postId}
			onClose={() => setSession(null)}
		/>
	);
};

const SourceQrPanelFieldsInner = ({ postId }: { postId: number }) => (
	<PanelRow>
		<Button
			variant="secondary"
			onClick={() => requestOpenMaintenanceModal(postId)}
			disabled={postId <= 0}
		>
			{__('Open…', 's2j-webinar-source-qr')}
		</Button>
	</PanelRow>
);

const SourceQrPanelFields = () => {
	const postId = useSelect((select) => {
		const editor = select(EDITOR_STORE) as { getCurrentPostId?: () => number };
		return editor.getCurrentPostId?.() ?? 0;
	}, []);

	const postType = useSelect((select) => {
		const editor = select(EDITOR_STORE) as { getCurrentPostType?: () => string };
		return editor.getCurrentPostType?.() ?? '';
	}, []);

	if (postId <= 0 || postType === '') {
		return (
			<PanelRow>
				<p className="description">
					{__('Loading source QR settings…', 's2j-webinar-source-qr')}
				</p>
			</PanelRow>
		);
	}

	const bootstrap = getBootstrap();
	if (!bootstrap.s2jWebinarActive) {
		return <WebinarDependencyNotice />;
	}

	return <SourceQrPanelFieldsInner postId={postId} />;
};

const SourceQrPanel = () => {
	const DocumentPanel = s2jSourceQrDocumentSettings.panelComponent;
	if (!DocumentPanel) {
		return (
			<Notice status="error" isDismissible={false}>
				{__(
					'PluginDocumentSettingPanel is unavailable in this WordPress version.',
					's2j-webinar-source-qr'
				)}
			</Notice>
		);
	}

	return (
		<DocumentPanel
			name="s2j-webinar-source-qr-event-panel"
			title={PLUGIN_PANEL_TITLE}
			className="s2j-webinar-source-qr-document-panel"
		>
			<SourceQrPanelFields />
		</DocumentPanel>
	);
};

const SourceQrEditorIntegration = () => {
	const bootstrap = getBootstrap();

	return (
		<>
			<SourceQrPanel />
			{bootstrap.s2jWebinarActive ? <MaintenanceModalHost /> : null}
		</>
	);
};

registerPlugin(PLUGIN_ID, {
	render: SourceQrEditorIntegration,
});
