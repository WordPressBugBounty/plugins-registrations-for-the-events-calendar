<?php
if ( ! defined( 'ABSPATH' ) ) { die( -1 ); }
?>
<div class="rtec-modal-backdrop">
<div class="rtec-modal">
	<button type="button" class="rtec-modal-close">
		<?php echo RTEC_Icon::get( 'close' ); ?><span class="rtec-media-modal-icon"><span class="screen-reader-text">Close</span></span>
	</button>
	<div class="rtec-modal-content">
		<div class="rtec-modal-inner-pad">
			<div class="rtec-modal-ajax-slot" style="display: none;"></div>
		</div>
	</div>
</div>
</div>