<?php
if ( ! defined( 'ABSPATH' ) ) { die( -1 ); }
?>
<div class="rtec-modal-backdrop">
<div class="rtec-modal">
	<button type="button" class="rtec-modal-close">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg><span class="rtec-media-modal-icon"><span class="screen-reader-text">Close</span></span>
	</button>
	<div class="rtec-modal-content">
		<div class="rtec-modal-inner-pad">
			<?php do_action( 'rtec_admin_modal_content' ); ?>
		</div>
	</div>
</div>
</div>