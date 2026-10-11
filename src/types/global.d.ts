export {};

declare global {
	interface Window {
		s2jWebinarSourceQrData?: {
			restNamespace: string;
			metaKey: string;
			webinarTrackingPathTemplate: string;
			webinarPluginActive: boolean;
			channelIds: string[];
		};
	}
}
