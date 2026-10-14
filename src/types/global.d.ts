export {};

declare global {
	interface Window {
		s2jWebinarSourceQrData?: {
			restNamespace: string;
			metaKey: string;
			s2jWebinarActive: boolean;
			webinarDefinitionsPathTemplate: string;
			webinarTrackingPathTemplate: string;
		};
	}
}
