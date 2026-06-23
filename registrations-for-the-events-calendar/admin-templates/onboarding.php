<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$success_event_url = ( $step === 4 ) ? get_transient( 'rtec_onboarding_success_event_url' ) : '';
$attendees_url     = admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=overview' );
$pro_url           = 'https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=onboarding&utm_medium=success-step&utm_content=LearnAboutPro';
$event_genius_url  = 'https://wordpress.org/plugins/event-genius/';
$event_genius_more = 'https://roundupwp.com/products/event-genius/?utm_campaign=rtec-free&utm_source=onboarding&utm_medium=learn-more';

// TEC display logic: get_plugin_data() is in includes/admin/class-rtec-admin.php ::get_plugin_data()
$tec_data                 = RTEC_Admin::get_plugin_data( 'tribe-tec' );
$tec_is_installed          = ! empty( $tec_data['is_installed'] );
$tec_is_active             = ! empty( $tec_data['is_active'] );
$tec_installed_not_active  = $tec_is_installed && ! $tec_is_active;
$tec_not_installed         = ! $tec_is_installed;

$logo_url       = rtec_plugin_url( 'assets/images/RU-Logo-150.png' );
$exit_setup_url = wp_nonce_url( add_query_arg( 'rtec_exit_onboarding', '1', admin_url( 'admin.php?page=' . RTEC_Onboarding::PAGE_SLUG ) ), 'rtec_exit_onboarding' );
$hide_onboarding_progress = ! empty( $hide_onboarding_progress );
?>
<div class="wrap rtec-admin-wrap rtec-wizard-page rtec-onboarding-page<?php echo $rtec_onboarding_evge_context_screen ? ' rtec-onboarding-page--evge-context' : ''; ?>">
	<a href="<?php echo esc_url( $exit_setup_url ); ?>" class="button button-secondary rtec-onboarding-exit-setup"><?php esc_html_e( 'Exit Setup', 'registrations-for-the-events-calendar' ); ?></a>
	<div class="rtec-wizard-outer">
		<?php
		$rtec_wizard_logo_url    = $logo_url;
		$rtec_wizard_show_title  = true;
		$rtec_wizard_show_dots   = ! $hide_onboarding_progress;
		$rtec_wizard_total_steps = $total_steps;
		$rtec_wizard_progress    = $progress;
		$rtec_wizard_dots_label  = __( 'Onboarding progress', 'registrations-for-the-events-calendar' );
		require rtec_plugin_path( 'admin-templates/partials/wizard-header.php' );
		?>

		<div class="rtec-wizard-wrap rtec-welcome-screen rtec-welcome-screen--onboarding">
	<div class="rtec-wizard-content rtec-welcome-text">
		<?php if ( ! empty( $rtec_onboarding_evge_context_screen ) ) : ?>
			<?php require rtec_plugin_path( 'admin-templates/partials/evge-context-prompt.php' ); ?>
		<?php else : ?>

			<?php if ( $step === 1 ) : ?>

			<?php if ( $tec_active ) : ?>
				<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'Welcome to Registrations for The Events Calendar', 'registrations-for-the-events-calendar' ); ?></h2>
				<!-- Step 1: Welcome when TEC already active -->
				<p class="rtec-wizard-step-body"><?php esc_html_e( "It looks like you already have The Events Calendar installed.", 'registrations-for-the-events-calendar' ); ?></p>
				<p class="rtec-wizard-step-body"><?php esc_html_e( "Let's quickly enable registrations so you can start growing your events right away.", 'registrations-for-the-events-calendar' ); ?></p>
				<div class="rtec-wizard-cta-footer">
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => RTEC_Onboarding::PAGE_SLUG, 'step' => 2 ) ) ); ?>" class="button button-primary rtec-onboarding-btn-continue rtec-wizard-cta-with-chevron">
						<span class="rtec-button-text"><?php esc_html_e( 'Continue with Registrations', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
					</a>
				</div>
			<?php elseif ( $tec_installed_not_active ) : ?>
				<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'Welcome to Registrations for The Events Calendar', 'registrations-for-the-events-calendar' ); ?></h2>
				<!-- Step 1: TEC installed but inactive — activate on this page -->
				<p class="rtec-wizard-step-body"><?php esc_html_e( 'Registrations adds sign-up forms to events created with The Events Calendar.', 'registrations-for-the-events-calendar' ); ?></p>
				<p class="rtec-wizard-step-body"><?php esc_html_e( 'The Events Calendar is installed but not active. Activate it to continue.', 'registrations-for-the-events-calendar' ); ?></p>
				<div id="rtec-admin-tec-welcome" class="rtec-onboarding-tec-welcome rtec-onboarding-step1-tec-activate">
					<div class="rtec-boxes">
						<div class="rtec-addon-container rtec-full-width rtec-standout" data-add-on="<?php echo esc_attr( $tec_data['slug'] ); ?>">
							<div class="rtec-addon-icon">
								<?php echo $tec_data['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="rtec-tec-content">
								<div class="rtec-content-top">
									<h4 class="rtec-addon-title"><?php echo esc_html( $tec_data['name'] ); ?></h4>
									<div class="rtec-addon-description"><?php echo rtec_sanitize_outputted_html( $tec_data['description'] ); ?></div>
								</div>
							</div>
						</div>
					</div>
					<div class="rtec-onboarding-tec-activate-footer">
						<button type="button" class="button button-primary rtec-addon-activate rtec-onboarding-activate-continue" data-action="activate">
							<span class="rtec-button-text"><?php esc_html_e( 'Activate and Continue', 'registrations-for-the-events-calendar' ); ?></span>
							<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
						</button>
					</div>
					<p class="rtec-wizard-ajax-message rtec-add-on-status-message" aria-live="polite"></p>
				</div>
			<?php else : ?>
				<?php
				$evge_install_cta  = __( 'Install Event Genius and Continue', 'registrations-for-the-events-calendar' );
				$tec_install_cta   = __( 'Install The Events Calendar and Continue', 'registrations-for-the-events-calendar' );
				$event_genius_data = RTEC_Admin::get_plugin_data( 'event-genius' );
				?>
				<!-- Step 1: Welcome + Path when TEC not active -->
				<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'How would you like to manage events?', 'registrations-for-the-events-calendar' ); ?></h2>
				<p class="rtec-wizard-step-body"><?php esc_html_e( 'Choose the event setup that fits your site.', 'registrations-for-the-events-calendar' ); ?></p>

				<div class="rtec-onboarding-path-cards">
					<label class="rtec-onboarding-path-card rtec-onboarding-path-card--recommended">
						<span class="rtec-onboarding-path-card-badge"><?php esc_html_e( 'Recommended', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-onboarding-path-card-inner">
							<input type="radio" name="rtec_path" value="event-genius" data-cta="<?php echo esc_attr( $evge_install_cta ); ?>" class="rtec-onboarding-path-card-input" checked>
							<span class="rtec-onboarding-path-card-heading">
								<span class="rtec-onboarding-path-card-thumb"><?php echo $event_genius_data['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="rtec-onboarding-path-card-heading-text">
									<span class="rtec-onboarding-path-card-title"><?php esc_html_e( 'Event Genius', 'registrations-for-the-events-calendar' ); ?></span>
									<span class="rtec-onboarding-path-card-subtitle"><?php esc_html_e( 'All-in-one event management and registration.', 'registrations-for-the-events-calendar' ); ?></span>
									<span class="rtec-onboarding-path-card-byline"><?php esc_html_e( 'From the creators of Registrations for The Events Calendar.', 'registrations-for-the-events-calendar' ); ?></span>
								</span>
							</span>
							<ul class="rtec-onboarding-path-card-bullets">
								<li><?php esc_html_e( 'One plugin setup', 'registrations-for-the-events-calendar' ); ?></li>
								<li><?php esc_html_e( 'Built-in registrations', 'registrations-for-the-events-calendar' ); ?></li>
								<li><?php esc_html_e( 'Recurring events included', 'registrations-for-the-events-calendar' ); ?></li>
							</ul>
							<span class="rtec-onboarding-path-card-footer"><?php esc_html_e( 'Best for most new event sites', 'registrations-for-the-events-calendar' ); ?></span>
						</span>
					</label>
					<label class="rtec-onboarding-path-card">
						<span class="rtec-onboarding-path-card-inner">
							<input type="radio" name="rtec_path" value="tribe-tec" data-cta="<?php echo esc_attr( $tec_install_cta ); ?>" class="rtec-onboarding-path-card-input">
							<span class="rtec-onboarding-path-card-heading">
								<span class="rtec-onboarding-path-card-thumb"><?php echo $tec_data['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="rtec-onboarding-path-card-heading-text">
									<span class="rtec-onboarding-path-card-title"><?php esc_html_e( 'The Events Calendar + Registrations', 'registrations-for-the-events-calendar' ); ?></span>
									<span class="rtec-onboarding-path-card-subtitle"><?php esc_html_e( 'Use The Events Calendar with this plugin.', 'registrations-for-the-events-calendar' ); ?></span>
									<span class="rtec-onboarding-path-card-byline rtec-onboarding-path-card-byline--placeholder" aria-hidden="true"><?php esc_html_e( 'From the creators of Registrations for The Events Calendar.', 'registrations-for-the-events-calendar' ); ?></span>
								</span>
							</span>
							<ul class="rtec-onboarding-path-card-bullets">
								<li><?php esc_html_e( 'Separate event and registration plugins', 'registrations-for-the-events-calendar' ); ?></li>
								<li><?php esc_html_e( 'Good for existing TEC users', 'registrations-for-the-events-calendar' ); ?></li>
								<li><?php esc_html_e( 'Large add-on ecosystem', 'registrations-for-the-events-calendar' ); ?></li>
							</ul>
							<span class="rtec-onboarding-path-card-footer"><?php esc_html_e( 'Best if you already want The Events Calendar', 'registrations-for-the-events-calendar' ); ?></span>
						</span>
					</label>
				</div>

				<div class="rtec-wizard-cta-footer rtec-onboarding-step1-cta">
					<button type="button" class="button button-primary rtec-onboarding-install-continue rtec-wizard-cta-with-chevron">
						<span class="rtec-button-text"><?php echo esc_html( $evge_install_cta ); ?></span>
						<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
					</button>
				</div>
				<p class="rtec-onboarding-step1-uninstall-notice"><?php esc_html_e( 'Registrations for The Events Calendar will be replaced by Event Genius.', 'registrations-for-the-events-calendar' ); ?></p>
				<p class="rtec-wizard-ajax-message rtec-onboarding-step1-message" aria-live="polite"></p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $step === 2 && ! empty( $future_events ) ) : ?>
			<!-- Step 2: Enable strategy -->
			<div id="rtec-onboarding-step-3">
			<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'How should registrations be enabled?', 'registrations-for-the-events-calendar' ); ?></h2>
			<p class="rtec-wizard-step-body"><?php esc_html_e( "Choose how you'd like registration forms to appear on your events.", 'registrations-for-the-events-calendar' ); ?></p>
			<div class="rtec-onboarding-options">
				<label class="rtec-onboarding-option">
					<input type="radio" name="rtec_enable_mode" value="all" checked>
					<span class="rtec-onboarding-option-title"><?php esc_html_e( 'Enable registrations on all future events', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-onboarding-badge"><?php esc_html_e( 'Recommended', 'registrations-for-the-events-calendar' ); ?></span></span>
					<span class="rtec-onboarding-option-desc"><?php esc_html_e( 'New events will automatically accept registrations.', 'registrations-for-the-events-calendar' ); ?></span>
				</label>
				<label class="rtec-onboarding-option">
					<input type="radio" name="rtec_enable_mode" value="selected">
					<span class="rtec-onboarding-option-title"><?php esc_html_e( 'Enable registrations only on selected events', 'registrations-for-the-events-calendar' ); ?></span>
					<span class="rtec-onboarding-option-desc"><?php esc_html_e( 'Choose which events should accept registrations.', 'registrations-for-the-events-calendar' ); ?></span>
				</label>
			</div>
			<div class="rtec-onboarding-enable-footer">
				<button type="button" class="button button-primary rtec-onboarding-enable-continue rtec-onboarding-continue-btn">
					<span class="rtec-button-text"><?php esc_html_e( 'Continue', 'registrations-for-the-events-calendar' ); ?></span>
					<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
				</button>
			</div>
			<p class="rtec-wizard-ajax-message" aria-live="polite"></p>
			</div>
		<?php endif; ?>

		<?php if ( $step === 3 ) : ?>
			<!-- Step 3: Create event (no future events) -->
			<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'Create Your First Event', 'registrations-for-the-events-calendar' ); ?></h2>
			<p class="rtec-wizard-step-body"><?php esc_html_e( "You're almost ready.", 'registrations-for-the-events-calendar' ); ?></p>
			<p class="rtec-wizard-step-body"><?php esc_html_e( "Let's publish a simple event so you can see how registrations work in action.", 'registrations-for-the-events-calendar' ); ?></p>
			<?php
			$default_date        = gmdate( 'Y-m-d', strtotime( '+7 days' ) );
			$default_start_time  = '09:00';
			$default_end_time    = '17:00';
			?>
			<div class="rtec-onboarding-form">
				<p class="rtec-onboarding-form-field">
					<label for="rtec-onboarding-event-title"><?php esc_html_e( 'Title', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" id="rtec-onboarding-event-title" class="rtec-onboarding-input rtec-onboarding-input-text" value="<?php echo esc_attr__( 'My Test Event', 'registrations-for-the-events-calendar' ); ?>">
				</p>
				<p class="rtec-onboarding-form-field">
					<label for="rtec-onboarding-event-start-date"><?php esc_html_e( 'Start date', 'registrations-for-the-events-calendar' ); ?></label>
					<span class="rtec-onboarding-date-time-wrap">
						<input type="date" id="rtec-onboarding-event-start-date" class="rtec-onboarding-input" value="<?php echo esc_attr( $default_date ); ?>">
						<input type="time" id="rtec-onboarding-event-start-time" class="rtec-onboarding-input" value="<?php echo esc_attr( $default_start_time ); ?>">
					</span>
				</p>
				<p class="rtec-onboarding-form-field">
					<label for="rtec-onboarding-event-end-date"><?php esc_html_e( 'End date', 'registrations-for-the-events-calendar' ); ?></label>
					<span class="rtec-onboarding-date-time-wrap">
						<input type="date" id="rtec-onboarding-event-end-date" class="rtec-onboarding-input" value="<?php echo esc_attr( $default_date ); ?>">
						<input type="time" id="rtec-onboarding-event-end-time" class="rtec-onboarding-input" value="<?php echo esc_attr( $default_end_time ); ?>">
					</span>
				</p>
				<p class="rtec-onboarding-form-note"><?php esc_html_e( 'You can add more details anytime after publishing.', 'registrations-for-the-events-calendar' ); ?></p>
			</div>
			<div class="rtec-onboarding-create-event-footer">
				<button type="button" class="button button-primary rtec-onboarding-create-event rtec-onboarding-create-continue rtec-wizard-cta-with-chevron" id="rtec-onboarding-create-event">
					<span class="rtec-button-text"><?php esc_html_e( 'Create Event and Continue', 'registrations-for-the-events-calendar' ); ?></span>
					<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
				</button>
			</div>
			<p class="rtec-wizard-ajax-message" aria-live="polite"></p>
		<?php endif; ?>

		<?php if ( $step === 4 ) : ?>
			<!-- Step 4: Success -->
			<h2 class="rtec-wizard-step-title">🎉 <?php esc_html_e( 'Registrations Are Live!', 'registrations-for-the-events-calendar' ); ?></h2>
			<p class="rtec-wizard-step-body"><?php esc_html_e( 'Your event is now accepting registrations.', 'registrations-for-the-events-calendar' ); ?></p>
			<ul class="rtec-wizard-success-list">
				<li><span class="rtec-wizard-success-list-icon" aria-hidden="true"><?php echo RTEC_Icon::get( 'check' ); ?></span><?php esc_html_e( 'Visitors will see a registration form on the event page', 'registrations-for-the-events-calendar' ); ?></li>
				<li><span class="rtec-wizard-success-list-icon" aria-hidden="true"><?php echo RTEC_Icon::get( 'check' ); ?></span><?php esc_html_e( 'They will receive a confirmation email', 'registrations-for-the-events-calendar' ); ?></li>
				<li><span class="rtec-wizard-success-list-icon" aria-hidden="true"><?php echo RTEC_Icon::get( 'check' ); ?></span><?php esc_html_e( 'They will be added to your attendee list', 'registrations-for-the-events-calendar' ); ?></li>
			</ul>
			<div class="rtec-wizard-cta-footer rtec-wizard-success-primary">
				<?php if ( $success_event_url ) : ?>
					<a href="<?php echo esc_url( $success_event_url ); ?>" class="button button-primary rtec-wizard-cta-with-chevron" target="_blank" rel="noopener noreferrer"><span class="rtec-button-text"><?php esc_html_e( 'View Event Page', 'registrations-for-the-events-calendar' ); ?></span><span class="rtec-button-carat" aria-hidden="true">&#8250;</span></a>
				<?php endif; ?>
			</div>
			<p class="rtec-wizard-success-cta-note"><?php esc_html_e( 'Open your event page to see the registration form and submit a test entry.', 'registrations-for-the-events-calendar' ); ?></p>
			<h3 class="rtec-wizard-success-other-heading"><?php esc_html_e( 'Other actions', 'registrations-for-the-events-calendar' ); ?></h3>
			<div class="rtec-wizard-actions rtec-wizard-success-secondary">
				<a href="<?php echo esc_url( $attendees_url ); ?>" class="button button-secondary rtec-wizard-btn-small"><?php esc_html_e( 'Manage Attendees', 'registrations-for-the-events-calendar' ); ?></a>
				<a href="<?php echo esc_url( $exit_setup_url ); ?>" class="button button-secondary rtec-wizard-btn-small"><?php esc_html_e( 'Close Wizard', 'registrations-for-the-events-calendar' ); ?></a>
			</div>
			<div class="rtec-onboarding-pro-cta">
				<p><?php esc_html_e( 'Want to collect payments, export reports, or customize workflows?', 'registrations-for-the-events-calendar' ); ?></p>
				<a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about Pro features', 'registrations-for-the-events-calendar' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( $step === 2 && ! empty( $future_events ) ) : ?>
			<!-- Step 2B: Select event (shown when mode = selected, via JS) -->
			<div id="rtec-onboarding-step-3b" class="rtec-onboarding-step-3b" style="display:none;">
				<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'Select an event', 'registrations-for-the-events-calendar' ); ?></h2>
				<p class="rtec-wizard-step-body"><?php esc_html_e( 'Choose a future event to enable registrations.', 'registrations-for-the-events-calendar' ); ?></p>
				<p>
					<label for="rtec-onboarding-select-event"><?php esc_html_e( 'Event', 'registrations-for-the-events-calendar' ); ?></label>
					<select id="rtec-onboarding-select-event">
						<option value=""><?php esc_html_e( '— Select —', 'registrations-for-the-events-calendar' ); ?></option>
						<?php foreach ( $future_events as $ev ) : ?>
							<option value="<?php echo (int) $ev['id']; ?>"><?php echo esc_html( $ev['title'] ); ?> (<?php echo esc_html( $ev['start_date'] ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</p>
				<div class="rtec-onboarding-step-3b-actions">
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => RTEC_Onboarding::PAGE_SLUG, 'step' => 2 ) ) ); ?>" class="button button-secondary rtec-onboarding-back-btn rtec-wizard-cta-with-chevron">
						<span class="rtec-button-carat rtec-button-carat-left" aria-hidden="true">&#8249;</span>
						<span class="rtec-button-text"><?php esc_html_e( 'Back', 'registrations-for-the-events-calendar' ); ?></span>
					</a>
					<div class="rtec-onboarding-enable-single-footer">
						<button type="button" class="button button-primary rtec-onboarding-enable-single rtec-wizard-cta-with-chevron">
							<span class="rtec-button-text"><?php esc_html_e( 'Enable & Continue', 'registrations-for-the-events-calendar' ); ?></span>
							<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
						</button>
					</div>
				</div>
				<p class="rtec-wizard-ajax-message" aria-live="polite"></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	</div>
	</div>
</div>
