<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}

$tec_data = RTEC_Admin::get_plugin_data( 'tribe-tec' );

if ( $tec_data['is_active'] ) {
	include_once RTEC_PLUGIN_DIR . 'inc/admin/templates/partials/settings-header.php';
}

do_action( 'rtec_admin_before_template_main' );

$welcome_screen_active_class = $tec_data['is_active'] ? '' : ' rtec-welcome-screen';
if ( $tec_data['is_active'] ) {
	do_action( 'rtec_admin_notices' );
}
?>
<div class="wrap rtec-admin-wrap<?php echo esc_attr( $welcome_screen_active_class ); ?>" id="rtec-admin-wrap">
	<?php if ( ! $tec_data['is_active'] ) { ?>
	<div id="rtec-admin-addons">
		<div class="rtec-welcome-text">
			<h3><?php echo esc_html__( 'Thank You for Installing Our Plugin!', 'registrations-for-the-events-calendar' ); ?></h3>
			<p><?php esc_html_e( 'Registrations for the Events Calendar requires The Events Calendar to be installed and active.', 'registrations-for-the-events-calendar' ); ?></p>
		</div>
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
							<button class="rtec-button rtec-primary rtec-addon-install" data-action="install">
								<span class="rtec-button-text"><?php echo esc_html__( 'Install', 'registrations-for-the-events-calendar' ); ?></span>
							</button>
							<button class="rtec-button rtec-primary rtec-addon-activate" data-action="activate">
								<span class="rtec-button-text"><?php echo esc_html__( 'Activate', 'registrations-for-the-events-calendar' ); ?></span>
							</button>
						</div>
					</div>

				</div>

			</div>
		</div>
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
				<span class="rtec-notice-bar-message"><?php echo sprintf( __( "You're using Registrations for the Events Calendar Lite. To unlock more features consider %1supgrading to Pro.%2s", 'registrations-for-the-events-calendar' ), '<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=settings-page&utm_medium=floating-bar&utm_content=upgrading-to-pro" target="_blank" rel="noopener noreferrer">', '</a>' ); ?></span>
				<button type="button" class="dismiss" title="<?php esc_html_e( 'Dismiss this message.', 'registrations-for-the-events-calendar' ); ?>" data-page="overview">
				</button>
			</div>
		<?php endif; ?>
		<?php
	}
	// this controls which view is included based on the selected tab
	$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : str_replace( 'rtec-', '', sanitize_text_field( $_GET['page'] ) ); // phpcs:ignore
	if ( $tab === 'migration' ) {
		require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/migration.php';
		echo '</div>';
		return;
	}
	$additional_tabs = array();
	$additional_tabs = apply_filters( 'rtec_admin_additional_tabs', $additional_tabs );
	$active_tab      = RTEC_Admin::get_active_tab( $tab, $additional_tabs );

	$options   = get_option( 'rtec_options' );
	$tz_offset = rtec_get_time_zone_offset();

	?>

	<!-- Display the tabs along with styling for the 'active' tab -->
	<?php
	if ( current_user_can( 'manage_options' ) ) {
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo get_admin_url( null, 'admin.php?page=' . RTEC_MENU_SLUG ); ?>" class="nav-tab 
								<?php
								if ( $active_tab == 'registrations' || $active_tab == 'single' ) {
									echo 'nav-tab-active'; }
								?>
			"><?php esc_html_e( 'Registrations', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo get_admin_url( null, 'admin.php?page=rtec-form' ); ?>" class="nav-tab 
								<?php
								if ( $active_tab == 'form' || $active_tab == 'create' ) {
									echo 'nav-tab-active'; }
								?>
			"><?php esc_html_e( 'Form', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="<?php echo get_admin_url( null, 'admin.php?page=rtec-email' ); ?>" class="nav-tab 
								<?php
								if ( $active_tab == 'email' || $active_tab == 'message-create' ) {
									echo 'nav-tab-active'; }
								?>
			"><?php esc_html_e( 'Email', 'registrations-for-the-events-calendar' ); ?></a>
			<?php
			foreach ( $additional_tabs as $additional_tab ) :
				$label = isset( $additional_tab['label'] ) ? $additional_tab['label'] : '';
				$value = isset( $additional_tab['value'] ) ? $additional_tab['value'] : false;
				if ( $value === 'mailchimp' ) {
					$link_href = get_admin_url( null, 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar-pro%2F_settings&tab=' . $value );
				} else {
					$link_href = get_admin_url( null, 'admin.php?page=rtec-' . $value );
				}
				?>
				<a href="<?php echo $link_href; ?>" class="nav-tab 
									<?php
									if ( $active_tab == $value ) {
										echo 'nav-tab-active'; }
									?>
				"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>

			<a href="<?php echo get_admin_url( null, 'admin.php?page=rtec-email' ); ?>" class="nav-tab rtec-pro-action-button-wrap rtec-modal-opener" data-content="payments"><?php esc_html_e( 'Payments', 'registrations-for-the-events-calendar' ); ?><div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div></a>
			<a href="<?php echo get_admin_url( null, 'admin.php?page=rtec-support' ); ?>" class="nav-tab 
								<?php
								if ( $active_tab == 'support' ) {
									echo 'nav-tab-active'; }
								?>
			"><?php esc_html_e( 'Support', 'registrations-for-the-events-calendar' ); ?></a>
		</h2>
		<?php
		if ( $active_tab === 'email' ) {
			require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/email.php';
		} elseif ( $active_tab === 'form' ) {
			require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/form.php';
		} elseif ( $active_tab === 'support' ) {
			require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/support.php';
		} elseif ( $active_tab === 'single' ) {
			require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/single.php';
		} else {
			$default = true;
			foreach ( $additional_tabs as $additional_tab ) {
				$value = isset( $additional_tab['value'] ) ? $additional_tab['value'] : false;
				if ( $active_tab === $value ) {
					$default = false;
					do_action( 'rtec_the_tab_html_' . $additional_tab['value'] );
				}
			}
			if ( $default ) {
				require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/registrations.php';
			}
		}
	} elseif ( $active_tab === 'single' ) {
			require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/single.php';
	} else {
		require_once RTEC_PLUGIN_DIR . 'inc/admin/templates/registrations.php';
	}

	$ad_text       = array(
		'<span class="rtec-bold">Easily collect and manage payments.</span><span>Get paid for your events using PayPal.</span>',
		'<span class="rtec-bold">More forms. Lots of ways to customize.</span><span>Build them with our custom form building tool.</span>',
		'<span class="rtec-bold">Do you have a membership site?</span><span>See our features tailored for your needs in the Registrations for the Events Calendar Pro.',
		'<span class="rtec-bold">More ways to follow up with your attendees.</span><span>Send event-wide emails right from the WordPress dashboard.</span>',
		'<span class="rtec-bold">Tailor your settings for each event.</span><span>Custom forms, custom confirmation messages, custom response categories.</span>',
	);
	$random_ad_key = array_rand( $ad_text, 1 );
	$random_num    = rand( 0, 1 );
	?>
	<hr />
	<div id="rtec-admin-footer">
	<?php if ( $random_num === 1 ) : ?>
	<div class="rtec-box-shadow rtec-standard-notice rtec-pro-ad">
		<img src="<?php echo RTEC_PLUGIN_URL . 'img/RTEC-Logo-300.png'; ?>" alt="Registrations for the Events Calendar Pro">
		<div class="rtec-pro-copy">
			<div class="rtec-pro-copy-text"> <?php echo $ad_text[ $random_ad_key ]; ?></div>
			<a class="rtec-offer-cta rtec-heavy-shadow" href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=footer-banner&utm_medium=<?php echo esc_attr( $random_ad_key ); ?>-discount&utm_content=Get50%Off">
				<svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="tags" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="svg-inline--fa fa-tags fa-w-20" style="--fa-secondary-opacity:1; --fa-primary-opacity:0.1;"><g class="fa-group"><path fill="#3bac5d" d="M497.94 225.94L286.06 14.06A48 48 0 0 0 252.12 0H48A48 48 0 0 0 0 48v204.12a48 48 0 0 0 14.06 33.94l211.88 211.88a48 48 0 0 0 67.88 0l204.12-204.12a48 48 0 0 0 0-67.88zM112 160a48 48 0 1 1 48-48 48 48 0 0 1-48 48z" class="fa-secondary"></path><path fill="#8edca6" d="M625.94 293.82L421.82 497.94a48 48 0 0 1-67.88 0l-.36-.36 174.06-174.06a90 90 0 0 0 0-127.28L331.4 0h48.72a48 48 0 0 1 33.94 14.06l211.88 211.88a48 48 0 0 1 0 67.88z" class="fa-primary"></path></g></svg>
				<div>
					<span class="rtec-offer-cta-bold"><?php esc_html_e( 'Get 50% Off Pro', 'registrations-for-the-events-calendar' ); ?></span>
					<span class="rtec-offer-cta-subtext"><?php esc_html_e( 'automatically applied at checkout', 'registrations-for-the-events-calendar' ); ?></span>
				</div>
			</a>
		</div>
		<div class="rtec-pro-ad-cta">
			<div class="rtec-pro-ad-cta-button">
				<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=footer-banner&utm_medium=<?php echo esc_attr( $random_ad_key ); ?>-pro-ad&utm_content=GetStarted" class="rtec-pro-feature-cta">
					<?php esc_html_e( 'Get Started', 'registrations-for-the-events-calendar' ); ?>
				</a>
			</div>

		</div>
	</div>
	<?php else : ?>
		<div class="rtec-box-shadow rtec-standard-notice rtec-pro-ad">
			<img src="<?php echo RTEC_PLUGIN_URL . 'img/RTEC-Logo-300.png'; ?>" alt="Registrations for the Events Calendar Pro">
			<div class="rtec-pro-copy">
				<div class="rtec-pro-copy-text"><strong><?php esc_html_e( 'More Features With Registrations for the Events Calendar Pro', 'registrations-for-the-events-calendar' ); ?></strong></div>
				<div class="rtec-pro-features-list">
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Unlimited Forms', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Unlimited Email Templates', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Collect PayPal Payments', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Variable Prices', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Waiting Lists', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Membership Features', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'All Form Field Types', 'registrations-for-the-events-calendar' ); ?></div>
					<div class="rtec-pro-features-list-tem"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg><?php esc_html_e( 'Downloadable Reports', 'registrations-for-the-events-calendar' ); ?></div>
				</div>
			</div>
			<div class="rtec-pro-ad-cta">
				<div class="rtec-pro-ad-cta-button">
					<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=footer-banner&utm_medium=pro-features-pro-ad&utm_content=GetStarted" class="rtec-pro-feature-cta">
						<?php esc_html_e( 'Get Started', 'registrations-for-the-events-calendar' ); ?>
					</a>
				</div>

			</div>
		</div>
	<?php endif; ?>
	</div>
</div>
<?php
do_action( 'rtec_after_admin_wrap' );
