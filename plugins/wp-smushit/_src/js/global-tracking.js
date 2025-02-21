import tracker from './utils/tracker';

export default class GlobalTracking {
	init() {
		this.trackSubmenuProUpsell();
		this.trackPluginListProUpsell();
		this.trackDashboardWidgetProUpsell();
	}

	trackSubmenuProUpsell() {
		const submenuUpgradeLink = document.querySelector( '#toplevel_page_smush a[href*="utm_campaign=smush_submenu_upsell' );
		if ( submenuUpgradeLink ) {
			submenuUpgradeLink.addEventListener( 'click', ( event ) => {
				this.trackGeneralProUpsell( 'submenu', event?.target?.href );
			} );
		}
	}

	trackPluginListProUpsell() {
		const pluginlistUpgradeLink = document.getElementById( 'smush-pluginlist-upgrade-link' );
		if ( pluginlistUpgradeLink ) {
			pluginlistUpgradeLink.addEventListener( 'click', ( event ) => {
				this.trackGeneralProUpsell( 'plugins_list', event?.target?.href );
			} );
		}
	}

	trackDashboardWidgetProUpsell() {
		const upsellBox = document.getElementById( 'smush-box-dashboard-upsell-upsell' );
		if ( ! upsellBox ) {
			return;
		}

		const dashboardProUpsellLink = upsellBox.querySelector( 'a[href*=smush-dashboard-upsell]' );
		if ( dashboardProUpsellLink ) {
			dashboardProUpsellLink.addEventListener( 'click', ( event ) => {
				this.trackGeneralProUpsell( 'dash_widget', event?.target?.href );
			} );
		}
	}

	trackSetupWizardProUpsell( utmLink, proInterests ) {
		this.trackGeneralProUpsell( 'wizard', utmLink, proInterests );
	}

	trackGeneralProUpsell( localtion, utmLink, proInterests = 'na' ) {
		this.trackProUpsell( {
			Feature: 'pro_general',
			Location: localtion,
			'UTM Link': utmLink,
			'Pro Interests': proInterests,
		} );
	}

	trackProUpsell( properties ) {
		properties = Object.assign( {
			'User Action': 'cta_clicked',
		}, properties );

		tracker.track( 'smush_pro_upsell', properties );
	}
}
