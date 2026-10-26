export {};

declare global {
	interface Window {
		s2jWebinarSourceQrData?: {
			restNamespace: string;
			metaKey: string;
			s2jWebinarInstalled: boolean;
			s2jWebinarActive: boolean;
			webinarInstallGuideUrl?: string;
			webinarActivateUrl?: string;
			webinarDefinitionsPathTemplate: string;
			webinarTrackingPathTemplate: string;
			webinarHandoffOpenHook?: string;
		};
	}
}
