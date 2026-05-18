<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}

$tec_data = RTEC_Admin::get_plugin_data( 'tribe-tec' );
$evge_admin_interstitial = class_exists( 'RTEC_Onboarding' ) && RTEC_Onboarding::is_evge_context_interstitial_active();

if ( $tec_data['is_active'] ) {
	include_once rtec_plugin_path( 'admin-templates/partials/settings-header.php' );
}

do_action( 'rtec_admin_before_template_main' );

$welcome_screen_active_class = $tec_data['is_active'] ? '' : ' rtec-welcome-screen';
if ( ! $tec_data['is_active'] && $evge_admin_interstitial && current_user_can( 'manage_options' ) ) {
	$welcome_screen_active_class .= ' rtec-admin-wrap--evge-context';
}
?>
<div class="wrap rtec-admin-wrap<?php echo esc_attr( $welcome_screen_active_class ); ?>" id="rtec-admin-wrap">
	<?php if ( $tec_data['is_active'] ) : ?>
		<?php do_action( 'rtec_admin_notices' ); ?>
	<?php endif; ?>
	<?php if ( ! $tec_data['is_active'] ) { ?>
	<div id="rtec-admin-addons">
			<?php if ( $evge_admin_interstitial && current_user_can( 'manage_options' ) ) : ?>
				<div class="rtec-onboarding-outer">
					<?php
					$rtec_onboarding_show_dots = false;
					require rtec_plugin_path( 'admin-templates/partials/onboarding-header.php' );
					?>
					<div class="rtec-onboarding-wrap rtec-welcome-screen" style="padding: 0; box-shadow: none;">
						<div class="rtec-onboarding-content rtec-welcome-text">
							<?php
							$rtec_current_admin_url = admin_url( 'admin.php?page=' . ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : RTEC_MENU_SLUG ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								$rtec_current_admin_url = add_query_arg( 'tab', sanitize_text_field( wp_unslash( $_GET['tab'] ) ), $rtec_current_admin_url ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							}
							$evge_continue_url = RTEC_Onboarding::get_evge_continue_url();
							$evge_use_rtec_url = RTEC_Onboarding::get_evge_use_rtec_url( $rtec_current_admin_url );
							require rtec_plugin_path( 'admin-templates/partials/evge-context-prompt.php' );
							?>
						</div>
					</div>
				</div>
			<?php else : ?>
		<div class="rtec-welcome-text">
			<h3><?php echo esc_html__( 'Thank You for Installing Our Plugin!', 'registrations-for-the-events-calendar' ); ?></h3>
			<p><?php esc_html_e( 'Registrations for the Events Calendar requires The Events Calendar to be installed and active.', 'registrations-for-the-events-calendar' ); ?></p>
		</div>
			<?php endif; ?>
		<?php if ( ! ( $evge_admin_interstitial && current_user_can( 'manage_options' ) ) ) : ?>
		<div id="rtec-admin-tec-welcome">
			<div class="rtec-boxes">

				<?php
				$add_on          = $tec_data;
				$next_step_class = 'rtec-tec-success';
				if ( ! $add_on['is_installed'] ) {
					$next_step_class = 'rtec-tec-install';
				} elseif ( ! $add_on['is_active'] ) {
					$next_step_class = 'rtec-tec-activate';
				}
				?>
				<div class="rtec-addon-container rtec-full-width rtec-standout" data-add-on="<?php echo esc_attr( $add_on['slug'] ); ?>">
					<div class="rtec-addon-icon">
						<?php echo $add_on['icon']; ?>
					</div>
					<div class="rtec-tec-content">
						<div class="rtec-content-top">
							<h4 class="rtec-addon-title"><?php echo esc_html( $add_on['name'] ); ?></h4>
							<div class="rtec-addon-description"><?php echo rtec_sanitize_outputted_html( $add_on['description'] ); ?></div>
						</div>
						<div class="rtec-addon-buttons rtec-vertical-align-flex <?php echo esc_attr( $next_step_class ); ?>">
							<button class="button button-primary rtec-addon-install" data-action="install">
								<span class="rtec-button-text"><?php echo esc_html__( 'Install', 'registrations-for-the-events-calendar' ); ?></span>
							</button>
							<button class="button button-primary rtec-addon-activate" data-action="activate">
								<span class="rtec-button-text"><?php echo esc_html__( 'Activate', 'registrations-for-the-events-calendar' ); ?></span>
							</button>
						</div>
					</div>

				</div>

			</div>
		</div>
		<?php endif; ?>
	</div>
			<?php
			return false;
	} else {
		?>
		<?php
		$lite_notice_dismissed = get_transient( 'registrations_tec_dismiss_lite' );

		if ( ! $lite_notice_dismissed ) :
			?>
			<div id="rtec-notice-bar" style="display:none">
				<span class="rtec-notice-bar-message"><?php 
				// Translators: %1$s is the opening link tag, %2$s is the closing link tag
				echo sprintf( __( "You're using Registrations for the Events Calendar Lite. To unlock more features consider %1supgrading to Pro.%2s", 'registrations-for-the-events-calendar' ), '<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=settings-page&utm_medium=floating-bar&utm_content=upgrading-to-pro" target="_blank" rel="noopener noreferrer">', '</a>' ); ?></span>
				<button type="button" class="dismiss" title="<?php esc_html_e( 'Dismiss this message.', 'registrations-for-the-events-calendar' ); ?>" data-page="overview">
				</button>
			</div>
		<?php endif; ?>
		<?php
	}
	// This controls which view is included based on the selected tab.
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
	$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : str_replace( 'rtec-', '', $current_page ); // phpcs:ignore
	if ( $tab === 'migration' ) {
		require_once rtec_plugin_path( 'admin-templates/migration.php' );
		echo '</div>';
		return;
	}
	$additional_tabs = array();
	$additional_tabs = apply_filters( 'rtec_admin_additional_tabs', $additional_tabs );
	$active_tab      = RTEC_Admin::get_active_tab( $tab, $additional_tabs );

	$options   = get_option( 'rtec_options' );
	$tz_offset = rtec_get_time_zone_offset();

	$is_registrations_page = ( $current_page === RTEC_MENU_SLUG );
	$is_settings_page     = ( $current_page === 'rtec-settings' );

	$new_registrations_count = 0;
	if ( $is_registrations_page ) {
		$new_registrations_count = rtec_get_existing_new_reg_count();
		if ( in_array( $active_tab, array( 'latest', 'single' ), true ) ) {
			rtec_update_admin_last_viewed_registrations();
		}
	}

	?>

	<!-- Settings checklist: above tabs on settings page and registrations page (not on single registration view) -->
	<?php if ( ( $is_settings_page || ( $is_registrations_page && $active_tab !== 'single' ) ) && current_user_can( 'manage_options' ) && class_exists( 'RTEC_Onboarding' ) ) : ?>
		<?php include_once rtec_plugin_path( 'admin-templates/partials/settings-checklist.php' ); ?>
	<?php endif; ?>

	<!-- Registrations area: Events | All Registrations (hidden on single event view) -->
	<?php if ( $is_registrations_page && $active_tab !== 'single' ) : ?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=overview' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'overview' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Events', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=latest' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'latest' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'All Registrations', 'registrations-for-the-events-calendar' ); ?>
			<?php if ( $new_registrations_count > 0 ) : ?>
				<span class="update-plugins rtec-notice-admin-reg-count count-<?php echo (int) $new_registrations_count; ?>"><span class="plugin-count"><?php echo (int) $new_registrations_count; ?></span></span>
			<?php endif; ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=latest' ) ); ?>" class="nav-tab rtec-pro-action-button-wrap rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'scheduled-messages', 'location' => 'registrations-nav' ) ) ); ?>">
				<?php esc_html_e( 'Scheduled Messages', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=latest' ) ); ?>" class="nav-tab rtec-pro-action-button-wrap rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'reports', 'location' => 'registrations-nav' ) ) ); ?>">
				<?php esc_html_e( 'Reports', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span>
			</a>
		</h2>
	<?php endif; ?>

	<!-- Settings area: General | Form | Email | Text & Translation | Advanced | Support -->
	<?php if ( $is_settings_page && current_user_can( 'manage_options' ) ) : ?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=general' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'general' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=form' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'form' || $active_tab === 'create' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Form', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=email' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'email' || $active_tab === 'message-create' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Email', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=text' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'text' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Text & Translation', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=advanced' ) ); ?>" class="nav-tab <?php echo ( $active_tab === 'advanced' ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Advanced', 'registrations-for-the-events-calendar' ); ?></a>
			<?php foreach ( $additional_tabs as $additional_tab ) : ?>
				<?php
				$label = isset( $additional_tab['label'] ) ? $additional_tab['label'] : '';
				$value = isset( $additional_tab['value'] ) ? $additional_tab['value'] : false;
				$link  = admin_url( 'admin.php?page=rtec-settings&tab=' . $value );
				?>
				<a href="<?php echo esc_url( $link ); ?>" class="nav-tab <?php echo ( $active_tab === $value ) ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rtec-settings&tab=email' ) ); ?>" class="nav-tab rtec-pro-action-button-wrap rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'payments', 'location' => 'settings-nav' ) ) ); ?>"><?php esc_html_e( 'Payments', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span></a>
		</h2>
	<?php endif; ?>

	<?php
	// Content routing: load the correct template.
	if ( $active_tab === 'single' ) {
		require_once rtec_plugin_path( 'admin-templates/single.php' );
	} elseif ( $active_tab === 'latest' ) {
		require_once rtec_plugin_path( 'admin-templates/latest.php' );
	} elseif ( $active_tab === 'overview' ) {
		require_once rtec_plugin_path( 'admin-templates/registrations.php' );
	} elseif ( $is_settings_page ) {
		if ( $active_tab === 'general' ) {
			require_once rtec_plugin_path( 'admin-templates/general.php' );
		} elseif ( $active_tab === 'form' ) {
			require_once rtec_plugin_path( 'admin-templates/form.php' );
		} elseif ( $active_tab === 'email' ) {
			require_once rtec_plugin_path( 'admin-templates/email.php' );
		} elseif ( $active_tab === 'text' ) {
			require_once rtec_plugin_path( 'admin-templates/text.php' );
		} elseif ( $active_tab === 'advanced' ) {
			require_once rtec_plugin_path( 'admin-templates/advanced.php' );
		} elseif ( $active_tab === 'support' ) {
			require_once rtec_plugin_path( 'admin-templates/support.php' );
		} else {
			$handled = false;
			foreach ( $additional_tabs as $additional_tab ) {
				$value = isset( $additional_tab['value'] ) ? $additional_tab['value'] : false;
				if ( $active_tab === $value ) {
					$handled = true;
					do_action( 'rtec_the_tab_html_' . $value );
					break;
				}
			}
			if ( ! $handled ) {
				require_once rtec_plugin_path( 'admin-templates/form.php' );
			}
		}
	} elseif ( $active_tab === 'overview' || $active_tab === 'latest' ) {
		// Registrations area for users without manage_options: show overview or latest.
		if ( $active_tab === 'latest' ) {
			require_once rtec_plugin_path( 'admin-templates/latest.php' );
		} else {
			require_once rtec_plugin_path( 'admin-templates/registrations.php' );
		}
	} else {
		require_once rtec_plugin_path( 'admin-templates/registrations.php' );
	}

	?>
</div>
<?php
do_action( 'rtec_after_admin_wrap' );
