/**
 * Placeholder reference table: tabs, search, "Show more," insert into closest editor.
 *
 * @since 3.0
 */
(function($) {
	'use strict';

	function initPlaceholderReference() {
		var $ref = $( '.rtec-placeholder-reference' );
		if ( ! $ref.length ) {
			return;
		}

		// Tabs: switch active tab and show matching category
		$ref.on( 'click', '.rtec-placeholder-tab', function() {
			var $tab = $( this );
			var category = $tab.data( 'category' );
			$ref.find( '.rtec-placeholder-tab' ).removeClass( 'rtec-active' );
			$tab.addClass( 'rtec-active' );
			$ref.find( '.rtec-placeholder-category' ).removeClass( 'rtec-active' );
			$ref.find( '.rtec-placeholder-category[data-category="' + category + '"]' ).addClass( 'rtec-active' );
		} );

		// Search: optionally expand "Show more," then show/hide items by token/description
		$ref.on( 'input', '.rtec-placeholder-search-input', function() {
			var term = $( this ).val().toLowerCase().trim();
			$ref.find( '.rtec-placeholder-category' ).each( function() {
				var $cat = $( this );
				if ( term ) {
					$cat.addClass( 'rtec-category-expanded' );
				}
				$cat.find( '.rtec-placeholder-item' ).each( function() {
					var $item = $( this );
					var code = ( $item.find( '.rtec-placeholder-code' ).text() || '' ).toLowerCase();
					var desc = ( $item.find( '.rtec-placeholder-description' ).text() || '' ).toLowerCase();
					var match = ! term || code.indexOf( term ) !== -1 || desc.indexOf( term ) !== -1;
					$item.toggleClass( 'rtec-placeholder-search-hidden', ! match );
				} );
			} );
		} );

		// Show more: expand category so hidden items are visible
		$ref.on( 'click', '.rtec-show-more-placeholders', function() {
			$( this ).closest( '.rtec-placeholder-category' ).addClass( 'rtec-category-expanded' );
		} );

		// Insert: insert token into closest editor (TinyMCE or textarea)
		$ref.on( 'click', '.rtec-placeholder-insert', function() {
			var placeholder = $( this ).data( 'placeholder' );
			if ( ! placeholder ) {
				return;
			}
			var $context = $( this ).closest( '.rtec-placeholderable-field' );
			if ( ! $context.length ) {
				return;
			}
			var $textarea = $context.find( '.wp-editor-area' );
			if ( ! $textarea.length ) {
				return;
			}
			var textareaId = $textarea.attr( 'id' );
			if ( typeof tinyMCE !== 'undefined' && tinyMCE.get( textareaId ) ) {
				var editor = tinyMCE.get( textareaId );
				if ( editor && ! editor.isHidden() ) {
					editor.execCommand( 'mceInsertContent', false, placeholder );
					return;
				}
			}
			// Fallback: textarea (no TinyMCE or Text tab)
			var el = $textarea[0];
			var start = el.selectionStart;
			var end = el.selectionEnd;
			var text = el.value;
			var before = text.substring( 0, start );
			var after = text.substring( end );
			el.value = before + placeholder + after;
			el.selectionStart = el.selectionEnd = start + placeholder.length;
			el.focus();
		} );
	}

	$( function() {
		initPlaceholderReference();
	} );
})(jQuery);
