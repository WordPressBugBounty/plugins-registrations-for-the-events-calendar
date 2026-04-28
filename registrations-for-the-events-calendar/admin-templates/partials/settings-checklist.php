<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$onboarding_state = RTEC_Onboarding::get_state();
if ( empty( $onboarding_state['checklist_initiated'] ) ) {
	return;
}
if ( ! empty( $onboarding_state['checklist_dismissed'] ) ) {
	return;
}

$checklist = RTEC_Onboarding::get_checklist();
$done      = 0;
foreach ( $checklist as $item ) {
	if ( ! empty( $item['done'] ) ) {
		$done++;
	}
}
$total   = 7;
$all_done = ( $done >= $total );

$base = add_query_arg( array( 'page' => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'rtec-settings' ), admin_url( 'admin.php' ) );
if ( ! empty( $_GET['tab'] ) ) {
	$base = add_query_arg( 'tab', sanitize_text_field( wp_unslash( $_GET['tab'] ) ), $base );
}
$dismiss_url = wp_nonce_url( add_query_arg( 'rtec_dismiss_checklist', '1', $base ), 'rtec_dismiss_checklist' );
?>
<div class="rtec-settings-checklist <?php echo $all_done ? 'rtec-settings-checklist-collapsed' : ''; ?>" id="rtec-settings-checklist" data-rtec-checklist data-all-done="<?php echo $all_done ? '1' : '0'; ?>">
	<div class="rtec-settings-checklist-header">
		<button type="button" class="rtec-settings-checklist-toggle" aria-expanded="<?php echo $all_done ? 'false' : 'true'; ?>" aria-controls="rtec-settings-checklist-body" id="rtec-settings-checklist-toggle">
			<span class="rtec-settings-checklist-title">
				<?php if ( $all_done ) : ?>
					<?php esc_html_e( 'Setup Complete ✓', 'registrations-for-the-events-calendar' ); ?>
				<?php else : ?>
					🎯 <?php esc_html_e( 'Finish Setting Up Registrations', 'registrations-for-the-events-calendar' ); ?>
				<?php endif; ?>
							<span class="rtec-settings-checklist-progress-inline" aria-hidden="true"><?php echo (int) $done; ?> / <?php echo (int) $total; ?></span>
			</span>
			<span class="rtec-settings-checklist-chevron" aria-hidden="true"></span>
		</button>
	</div>
	<div id="rtec-settings-checklist-body" class="rtec-settings-checklist-body"<?php echo $all_done ? ' aria-hidden="true"' : ''; ?>>
		<p class="rtec-settings-checklist-intro"><?php esc_html_e( 'Here are a few optional next steps:', 'registrations-for-the-events-calendar' ); ?></p>
		<ul class="rtec-settings-checklist-list">
			<?php foreach ( $checklist as $item ) : ?>
				<li class="rtec-settings-checklist-item <?php echo ! empty( $item['done'] ) ? 'rtec-checklist-done' : ''; ?>">
					<?php if ( ! empty( $item['done'] ) ) : ?>
						<span class="rtec-checklist-icon rtec-checklist-done-icon" aria-hidden="true">✓</span>
					<?php else : ?>
						<span class="rtec-checklist-icon rtec-checklist-pending-icon" aria-hidden="true">○</span>
					<?php endif; ?>
					<span class="rtec-checklist-content">
						<?php if ( ! empty( $item['done'] ) ) : ?>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						<?php elseif ( ! empty( $item['url'] ) ) : ?>
							<?php
							$cta = isset( $item['cta_label'] ) ? $item['cta_label'] : $item['label'];
							?>
							<span class="rtec-checklist-label-line">
								<a href="<?php echo esc_url( $item['url'] ); ?>" <?php echo ! empty( $item['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
								<?php if ( ! empty( $item['description'] ) ) : ?>
									<span class="rtec-checklist-info-wrap">
										<span class="rtec-checklist-info-icon" aria-label="<?php esc_attr_e( 'More information', 'registrations-for-the-events-calendar' ); ?>">ⓘ</span>
										<span class="rtec-checklist-description rtec-checklist-tooltip"><?php echo esc_html( $item['description'] ); ?></span>
									</span>
								<?php endif; ?>
							</span>
							<span class="rtec-checklist-cta-wrap">
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="rtec-checklist-cta" <?php echo ! empty( $item['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $cta ); ?> &#8250;</a>
							</span>
						<?php else : ?>
							<span class="rtec-checklist-label-line">
								<span><?php echo esc_html( $item['label'] ); ?></span>
								<?php if ( ! empty( $item['description'] ) ) : ?>
									<span class="rtec-checklist-info-wrap">
										<span class="rtec-checklist-info-icon" aria-label="<?php esc_attr_e( 'More information', 'registrations-for-the-events-calendar' ); ?>">ⓘ</span>
										<span class="rtec-checklist-description rtec-checklist-tooltip"><?php echo esc_html( $item['description'] ); ?></span>
									</span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="rtec-settings-checklist-footer">
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="rtec-settings-checklist-dismiss"><?php esc_html_e( 'Dismiss permanently', 'registrations-for-the-events-calendar' ); ?></a>
		</div>
	</div>
</div>
