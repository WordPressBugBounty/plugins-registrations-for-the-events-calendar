<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Determine free vs Pro content.
$is_pro = isset( $is_pro ) ? (bool) $is_pro : ( defined( 'RTEC_IS_PRO' ) && RTEC_IS_PRO );

// URLs used on the page.
$release_guide_url = apply_filters(
	'rtec_3_0_release_guide_url',
	'https://roundupwp.com/rtec-3-0-release/?utm_campaign=rtec-' . ( $is_pro ? 'pro' : 'free' ) . '&utm_source=whats-new-screen&utm_medium=button&utm_content=release-guide'
);
$primary_cta_label = isset( $primary_cta_label ) ? $primary_cta_label : __( 'Create an event', 'registrations-for-the-events-calendar' );
$primary_cta_url   = isset( $primary_cta_url ) ? $primary_cta_url : admin_url( 'post-new.php?post_type=tribe_events' );
$primary_cta_attrs = '';
if ( ! empty( $primary_cta_external ) ) {
	$primary_cta_attrs = ' target="_blank" rel="noopener noreferrer"';
}
$payments_url      = admin_url( 'admin.php?page=rtec-settings&tab=payments' );
$pro_url           = 'https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=whats-new-screen&utm_medium=upsell&utm_content=ExploreRTECPro';
$hero_image_url    = rtec_plugin_url( 'assets/images/admin/RTEC-30-Hero.png' );
?>

<div class="wrap rtec-whats-new-wrap">
	<div class="rtec-whats-new-inner">

		<div class="rtec-whats-new-hero">
			<div class="rtec-whats-new-hero-text">
				<p class="rtec-whats-new-eyebrow"><?php esc_html_e( 'Major Update', 'registrations-for-the-events-calendar' ); ?></p>
				<h1 class="rtec-whats-new-title"><?php esc_html_e( 'Welcome to RTEC 3.0', 'registrations-for-the-events-calendar' ); ?></h1>
				<p class="rtec-whats-new-intro">
					<?php esc_html_e( 'RTEC 3.0 is a major update focused on making event registrations cleaner, easier to manage, and more powerful.', 'registrations-for-the-events-calendar' ); ?>
				</p>
				<div class="rtec-whats-new-reassurance-callout">
					<?php
					if ( $is_pro ) {
						esc_html_e( 'Your existing registrations, attendee data, payment settings, and event settings continue to work after updating.', 'registrations-for-the-events-calendar' );
					} else {
						esc_html_e( 'Your existing registrations, attendee data, and event settings continue to work after updating.', 'registrations-for-the-events-calendar' );
					}
					?>
				</div>
				<div class="rtec-whats-new-hero-buttons">
					<a href="<?php echo esc_url( $release_guide_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Read the full release guide', 'registrations-for-the-events-calendar' ); ?>
					</a>
					<a href="<?php echo esc_url( $primary_cta_url ); ?>" class="button"<?php echo $primary_cta_attrs; ?>>
						<?php echo esc_html( $primary_cta_label ); ?>
					</a>
					<?php if ( $is_pro ) : ?>
						<a href="<?php echo esc_url( $payments_url ); ?>" class="button">
							<?php esc_html_e( 'View payments', 'registrations-for-the-events-calendar' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( ! empty( $hero_image_url ) ) : ?>
				<div class="rtec-whats-new-hero-media">
					<img src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php esc_attr_e( 'RTEC 3.0', 'registrations-for-the-events-calendar' ); ?>" />
				</div>
			<?php endif; ?>
		</div>

		<div class="rtec-whats-new-section">
			<h2 class="rtec-whats-new-section-heading"><?php esc_html_e( "What's new in RTEC 3.0", 'registrations-for-the-events-calendar' ); ?></h2>

			<?php if ( ! $is_pro ) : ?>
				<div class="rtec-whats-new-features-grid">
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Cleaner interface', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'A refreshed admin experience makes it easier to scan registrations, switch views, and find the tools you use most often.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Improved registration forms', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Cleaner, more focused forms help attendees register faster and reduce friction on the front end.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Revamped registration management', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Updated admin screens make it easier to review attendees, adjust statuses, and stay on top of event capacity.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Performance and reliability enhancements', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Behind-the-scenes improvements help registrations feel faster and more dependable across busy event calendars.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
				</div>
			<?php else : ?>
				<div class="rtec-whats-new-features-grid">
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Improved payments and checkout', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'A smoother checkout flow and clearer payment controls make it easier to run paid events with confidence.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Enhanced reporting', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'More flexible filters and exports give you better visibility into registrations and revenue across your events.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Better check-in and attendance tools', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Refined check-in and attendance workflows help keep lines moving and your attendee counts accurate.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
					<div class="rtec-whats-new-card">
						<div class="rtec-whats-new-card-accent"></div>
						<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Registration types', 'registrations-for-the-events-calendar' ); ?></h3>
						<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Use registration types to model multiple venues, tiers, or options within a single event while keeping everything organized.', 'registrations-for-the-events-calendar' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! $is_pro ) : ?>
			<div class="rtec-whats-new-section">
				<div class="rtec-whats-new-pro-features">
					<h2 class="rtec-whats-new-section-heading"><?php esc_html_e( 'Also improved in RTEC Pro', 'registrations-for-the-events-calendar' ); ?></h2>
					<div class="rtec-whats-new-features-grid">
						<div class="rtec-whats-new-card rtec-whats-new-pro-card">
							<div class="rtec-whats-new-card-accent"></div>
							<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Improved payments and checkout', 'registrations-for-the-events-calendar' ); ?></h3>
							<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Take payments for events with a checkout flow designed to reduce friction and improve conversion.', 'registrations-for-the-events-calendar' ); ?></p>
						</div>
						<div class="rtec-whats-new-card rtec-whats-new-pro-card">
							<div class="rtec-whats-new-card-accent"></div>
							<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Enhanced reporting', 'registrations-for-the-events-calendar' ); ?></h3>
							<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Dig deeper into registrations and payments with richer reporting and more useful exports.', 'registrations-for-the-events-calendar' ); ?></p>
						</div>
						<div class="rtec-whats-new-card rtec-whats-new-pro-card">
							<div class="rtec-whats-new-card-accent"></div>
							<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Better check-in and attendance tools', 'registrations-for-the-events-calendar' ); ?></h3>
							<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Use improved check-in tools to keep event-day workflows simple while maintaining accurate attendance records.', 'registrations-for-the-events-calendar' ); ?></p>
						</div>
						<div class="rtec-whats-new-card rtec-whats-new-pro-card">
							<div class="rtec-whats-new-card-accent"></div>
							<h3 class="rtec-whats-new-card-title"><?php esc_html_e( 'Registration types', 'registrations-for-the-events-calendar' ); ?></h3>
							<p class="rtec-whats-new-card-body"><?php esc_html_e( 'Offer multiple options for a single event—such as venues, tiers, or add-ons—without creating separate events.', 'registrations-for-the-events-calendar' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="rtec-whats-new-section">
			<div class="rtec-whats-new-terminology">
				<h2 class="rtec-whats-new-section-heading"><?php esc_html_e( 'A few names have changed', 'registrations-for-the-events-calendar' ); ?></h2>
				<p class="rtec-whats-new-terminology-intro">
					<?php esc_html_e( 'To make things clearer and more consistent, a few labels in the plugin have been updated. Here is how they map to what you already know.', 'registrations-for-the-events-calendar' ); ?>
				</p>
				<div class="rtec-whats-new-terminology-grid">
					<div class="rtec-whats-new-terminology-item">
						<p class="rtec-whats-new-term-old">
							<?php esc_html_e( 'Multiple Venues and Tiers', 'registrations-for-the-events-calendar' ); ?>
							<?php if ( ! $is_pro ) : ?>
								<span class="rtec-whats-new-pro-badge"><?php esc_html_e( 'Pro', 'registrations-for-the-events-calendar' ); ?></span>
							<?php endif; ?>
						</p>
						<p class="rtec-whats-new-term-arrow"><?php esc_html_e( 'now called', 'registrations-for-the-events-calendar' ); ?></p>
						<p class="rtec-whats-new-term-new"><?php esc_html_e( 'Registration Types', 'registrations-for-the-events-calendar' ); ?></p>
						<p class="rtec-whats-new-term-description">
							<?php
							if ( $is_pro ) {
								esc_html_e( 'Registration types group different options for the same event, such as venues, tiers, or categories, into a single, easier-to-manage structure.', 'registrations-for-the-events-calendar' );
							} else {
								esc_html_e( 'Available in RTEC Pro: registration types group different options for the same event, such as venues, tiers, or categories, into a single, easier-to-manage structure.', 'registrations-for-the-events-calendar' );
							}
							?>
						</p>
					</div>
					<div class="rtec-whats-new-terminology-item">
						<p class="rtec-whats-new-term-old"><?php esc_html_e( 'Unregistered', 'registrations-for-the-events-calendar' ); ?></p>
						<p class="rtec-whats-new-term-arrow"><?php esc_html_e( 'now called', 'registrations-for-the-events-calendar' ); ?></p>
						<p class="rtec-whats-new-term-new"><?php esc_html_e( 'Canceled', 'registrations-for-the-events-calendar' ); ?></p>
						<p class="rtec-whats-new-term-description">
							<?php esc_html_e( 'The new Canceled status makes it clearer when a registration was previously active but has been removed from the attendee count.', 'registrations-for-the-events-calendar' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="rtec-whats-new-section">
			<div class="rtec-whats-new-next-steps">
				<h2 class="rtec-whats-new-section-heading"><?php esc_html_e( 'What to do next', 'registrations-for-the-events-calendar' ); ?></h2>
				<p class="rtec-whats-new-next-steps-intro">
					<?php esc_html_e( 'Here are a few simple checks to confirm everything looks right after updating.', 'registrations-for-the-events-calendar' ); ?>
				</p>
				<ul class="rtec-whats-new-next-list">
					<li class="rtec-whats-new-next-item">
						<div class="rtec-whats-new-next-bullet"></div>
						<div class="rtec-whats-new-next-text">
							<p class="rtec-whats-new-next-title"><?php esc_html_e( 'View the registration form on the front end', 'registrations-for-the-events-calendar' ); ?></p>
							<p class="rtec-whats-new-next-body">
								<?php esc_html_e( 'Open an upcoming event and submit a quick test registration to confirm the form looks and behaves as expected.', 'registrations-for-the-events-calendar' ); ?>
							</p>
						</div>
					</li>
					<li class="rtec-whats-new-next-item">
						<div class="rtec-whats-new-next-bullet"></div>
						<div class="rtec-whats-new-next-text">
							<p class="rtec-whats-new-next-title"><?php esc_html_e( 'Try the updated registration management screens', 'registrations-for-the-events-calendar' ); ?></p>
							<p class="rtec-whats-new-next-body">
								<?php esc_html_e( 'Visit the Registrations area in wp-admin to get familiar with the refreshed tables, filters, and status controls.', 'registrations-for-the-events-calendar' ); ?>
							</p>
						</div>
					</li>
					<li class="rtec-whats-new-next-item">
						<div class="rtec-whats-new-next-bullet"></div>
						<div class="rtec-whats-new-next-text">
							<p class="rtec-whats-new-next-title">
								<?php
								esc_html_e( 'Double-check your event and email settings', 'registrations-for-the-events-calendar' );
								?>
							</p>
							<p class="rtec-whats-new-next-body">
								<?php
								esc_html_e( 'Review key settings like capacities, deadlines, and confirmation emails so they still match how you want each event to run.', 'registrations-for-the-events-calendar' );
								?>
							</p>
						</div>
					</li>
				</ul>
			</div>
		</div>

		<?php if ( ! $is_pro ) : ?>
			<div class="rtec-whats-new-section">
				<div class="rtec-whats-new-pro-cta">
					<h2 class="rtec-whats-new-pro-cta-heading"><?php esc_html_e( 'Upgrade to Pro', 'registrations-for-the-events-calendar' ); ?></h2>
					<p class="rtec-whats-new-pro-cta-body">
						<?php esc_html_e( 'Add payments, advanced reporting, registration types, and more.', 'registrations-for-the-events-calendar' ); ?>
					</p>

					<a class="rtec-offer-cta rtec-heavy-shadow" style="display: inline-flex" href="<?php echo esc_url( $pro_url ); ?>?utm_campaign=rtec-free&amp;utm_source=welcome-30&amp;utm_medium=9-discount&amp;utm_content=Get50%Off">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>					<div>
						<span class="rtec-offer-cta-bold">Get 50% Off Pro</span>
						<span class="rtec-offer-cta-subtext">automatically applied at checkout</span>
					</div>
				</a>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>
