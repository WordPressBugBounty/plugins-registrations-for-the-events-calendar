<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

?>
<div class="rtec-settings-header">
    <div class="rtec-settings-header-inner">
        <div class="rtec-settings-header-identity">
            <div class="rtec-logo">
                <img src="<?php echo esc_url( rtec_plugin_url( 'assets/images/RU-Logo-150.png' ) ); ?>" alt="Roundup WP">
            </div>
            <h1><span class="rtec-brand">Roundup WP</span><span class="rtec-current-page"><?php echo esc_html( RTEC_Admin::get_nav_name() ); ?></span></h1>
        </div>
        <div class="rtec-header-support">
            <a href="<?php echo esc_url( get_admin_url( null, 'admin.php?page=rtec-settings&tab=support' ) ); ?>" class="rtec-button rtec-help-button"><?php echo RTEC_Icon::get( 'question-circle' ); ?><?php esc_html_e( 'Support', 'registrations-for-the-events-calendar' ); ?></a>
        </div>
    </div>

</div>


