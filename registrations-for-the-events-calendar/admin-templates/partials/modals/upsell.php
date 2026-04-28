<?php
/**
 * Single upsell modal body. Expects $upsell_data and $upsell_link (and optionally $logo_url) in scope.
 *
 * @package Registrations_For_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url = isset( $logo_url ) ? $logo_url : rtec_plugin_url( 'assets/images/RU-Logo.png' );
?>
<div class="rtec-upsell-modal-settings" data-rtec-modal-settings='{"width":"large"}'>
	<div class="rtec-modal-heading rtec-upsell-modal-heading">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Registrations for the Events Calendar', 'registrations-for-the-events-calendar' ); ?>" class="rtec-upsell-logo" />
		<h2 class="rtec-upsell-heading-title"><?php echo esc_html( $upsell_data['title'] ); ?></h2>
	</div>

	<div class="rtec-upsell-modal-content rtec-modal-body">
		<div class="rtec-upsell-modal-inner">
			<div class="rtec-upsell-layout">
				<?php if ( ! empty( $upsell_data['image'] ) ) : ?>
					<div class="rtec-upsell-image-column">
						<picture>
							<?php if ( ! empty( $upsell_data['image_2x'] ) ) : ?>
								<source srcset="<?php echo esc_url( $upsell_data['image_2x'] ); ?>" media="(-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi)">
							<?php endif; ?>
							<img src="<?php echo esc_url( $upsell_data['image'] ); ?>" alt="<?php echo esc_attr( $upsell_data['title'] ); ?>" class="rtec-upsell-feature-image" />
						</picture>
					</div>
				<?php endif; ?>

				<div class="rtec-upsell-content-column">
					<div class="rtec-upsell-description">
						<p><?php echo esc_html( $upsell_data['description'] ); ?></p>
					</div>

					<?php if ( ! empty( $upsell_data['features'] ) && is_array( $upsell_data['features'] ) ) : ?>
						<div class="rtec-upsell-features">
							<?php foreach ( $upsell_data['features'] as $feature ) : ?>
								<div class="rtec-upsell-feature-item">
									<span class="rtec-upsell-feature-icon">
										<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</span>
									<span class="rtec-upsell-feature-text"><?php echo esc_html( $feature ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<a href="<?php echo esc_url( $upsell_link ); ?>" class="button button-primary rtec-upsell-button rtec-upsell-cta-button" target="_blank" rel="noopener noreferrer">
						<span class="rtec-upsell-cta-text"><?php esc_html_e( 'Upgrade to Pro', 'registrations-for-the-events-calendar' ); ?></span>
					</a>
					<a class="rtec-offer-cta rtec-heavy-shadow" href="<?php echo esc_url( $discount_link ); ?>" target="_blank" rel="noopener noreferrer">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>
						<div>
							<span class="rtec-offer-cta-bold">Get 50% Off Pro</span>
							<span class="rtec-offer-cta-subtext">automatically applied at checkout</span>
						</div>
					</a>
				</div>
			</div>

			<div class="rtec-upsell-footer">
				<h3 class="rtec-upsell-footer-title-medium-screen"><?php esc_html_e( 'And much more!', 'registrations-for-the-events-calendar' ); ?></h3>
				<div class="rtec-upsell-footer-columns">
					<div class="rtec-upsell-footer-column rtec-upsell-footer-title-column">
						<h3 class="rtec-upsell-footer-title"><?php esc_html_e( 'And much more!', 'registrations-for-the-events-calendar' ); ?></h3>
					</div>

					<?php
					if ( ! empty( $upsell_data['additional_features'] ) && is_array( $upsell_data['additional_features'] ) ) :
						$features = $upsell_data['additional_features'];
						$n       = count( $features );
						$split1  = (int) ceil( $n / 3 );
						$split2  = (int) ceil( $n * 2 / 3 );
						$col1    = array_slice( $features, 0, $split1 );
						$col2    = array_slice( $features, $split1, $split2 - $split1 );
						$col3    = array_slice( $features, $split2 );
						$check_svg = '<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
					?>
						<div class="rtec-upsell-footer-column rtec-upsell-footer-features-column">
							<ul class="rtec-upsell-footer-features-list">
								<?php foreach ( $col1 as $feature ) : ?>
									<li class="rtec-upsell-footer-feature-item">
										<span class="rtec-upsell-footer-feature-icon"><?php echo $check_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="rtec-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="rtec-upsell-footer-column rtec-upsell-footer-features-column">
							<ul class="rtec-upsell-footer-features-list">
								<?php foreach ( $col2 as $feature ) : ?>
									<li class="rtec-upsell-footer-feature-item">
										<span class="rtec-upsell-footer-feature-icon"><?php echo $check_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="rtec-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="rtec-upsell-footer-column rtec-upsell-footer-features-column rtec-upsell-features-column-last">
							<ul class="rtec-upsell-footer-features-list">
								<?php foreach ( $col3 as $feature ) : ?>
									<li class="rtec-upsell-footer-feature-item">
										<span class="rtec-upsell-footer-feature-icon"><?php echo $check_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="rtec-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
