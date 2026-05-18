"use strict";

(function () {
	var createElement = wp.element.createElement,
		ServerSideRender = wp.components.ServerSideRender || wp.serverSideRender,
		_ref = wp.blockEditor || wp.editor,
		InspectorControls = _ref.InspectorControls,
		useBlockProps = (wp.blockEditor && wp.blockEditor.useBlockProps) ? wp.blockEditor.useBlockProps : function () { return {}; },
		TextareaControl = wp.components.TextareaControl,
		TextControl = wp.components.TextControl,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		ToggleControl = wp.components.ToggleControl,
		registerBlockType = wp.blocks.registerBlockType;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	function buildEventOptionsFromApiResponse(res) {
		var opts = [{ value: 'auto', label: rtec_block_editor.i18n.eventAuto }];
		if (res.events && res.events.length) {
			res.events.forEach(function (e) {
				opts.push({ value: String(e.id), label: e.title });
			});
		}
		return opts;
	}

	function buildInitialEventOptions() {
		var opts = [{ value: 'auto', label: rtec_block_editor.i18n.eventAuto }];
		(rtec_block_editor.upcoming || []).forEach(function (v) {
			opts.push({ value: String(v.id), label: v.title });
		});
		return opts;
	}

	// --- Registration form block ---
	registerBlockType('rtec/rtec-form-block', {
		apiVersion: 3,
		title: rtec_block_editor.i18n.registration,
		icon: 'calendar',
		category: (rtec_block_editor.blockCategory || 'widgets'),
		attributes: {
			isTribeEvent: { type: 'boolean' },
			eventID: { type: 'string', default: 'auto' },
			shortcodeSettings: { type: 'string' },
			executed: { type: 'boolean' },
			showheader: { type: 'boolean', default: false },
			showtools: { type: 'boolean', default: false },
			attendeelist: { type: 'boolean', default: false },
			hidden: { type: 'boolean', default: false },
		},
		edit: function edit(props) {
			var blockProps = useBlockProps();
			var setAttributes = props.setAttributes;
			var shortcodeSettings = props.attributes.shortcodeSettings !== undefined ? props.attributes.shortcodeSettings : (rtec_block_editor.shortcodeSettings || '');
			var eventID = props.attributes.eventID !== undefined ? props.attributes.eventID : 'auto';
			var isTribeEvent = (typeof TEC !== 'undefined');

			var _so = useState(buildInitialEventOptions());
			var eventOptions = _so[0];
			var setEventOptions = _so[1];
			var _sq = useState('');
			var searchQuery = _sq[0];
			var setSearchQuery = _sq[1];
			var _sl = useState(false);
			var searchLoading = _sl[0];
			var setSearchLoading = _sl[1];
			var currentEventId = eventID && eventID !== 'auto' ? String(eventID) : '';

			useEffect(function () {
				var timer = setTimeout(function () {
					if (typeof wp.apiFetch !== 'function' || !rtec_block_editor.searchEventsUrl) return;
					setSearchLoading(true);
					var url = rtec_block_editor.searchEventsUrl + '?search=' + encodeURIComponent(searchQuery) + '&current_event_id=' + encodeURIComponent(currentEventId) + '&per_page=25';
					wp.apiFetch({ url: url }).then(function (res) {
						setEventOptions(buildEventOptionsFromApiResponse(res));
					}).catch(function () {}).finally(function () { setSearchLoading(false); });
				}, 300);
				return function () { clearTimeout(timer); };
			}, [searchQuery, currentEventId]);

			useEffect(function () {
				if (props.attributes.isTribeEvent !== isTribeEvent) {
					setAttributes({ isTribeEvent: isTribeEvent });
				}
			}, [props.attributes.isTribeEvent, isTribeEvent]);

			function selectEvent(value) {
				setAttributes({ eventID: value });
			}
			function setState(shortcodeSettingsContent) {
				setAttributes({ shortcodeSettings: shortcodeSettingsContent });
			}

			var useLegacyUI = shortcodeSettings && String(shortcodeSettings).trim().length > 0;
			var hasEvent = isTribeEvent || eventID === 'auto' || parseInt(eventID, 10) > 0;
			var needsEventSelect = !isTribeEvent && (eventID === '' || eventID === undefined || eventID === 0 || eventID === '0' || (eventID !== 'auto' && parseInt(eventID, 10) <= 0));

			var jsx;

			if (useLegacyUI) {
				if (!isTribeEvent && (eventID === 'auto' || parseInt(eventID, 10) > 0)) {
					var eventTitle = eventID === 'auto' ? rtec_block_editor.i18n.eventAuto : '';
					if (eventID !== 'auto') {
						var o = eventOptions.find(function (opt) { return String(opt.value) === String(eventID); });
						if (o) eventTitle = o.label;
					}
					jsx = [
						createElement(InspectorControls, { key: 'rtec-gutenberg-setting-selector-inspector-controls' },
							createElement(PanelBody, { title: rtec_block_editor.i18n.addSettings },
								createElement('p', { className: 'rtec-gb-event-title-wrap' }, createElement('strong', { className: 'rtec-gb-event-title' }, eventTitle)),
								createElement(TextareaControl, {
									key: 'rtec-gutenberg-settings',
									className: 'rtec-gutenberg-settings',
									label: rtec_block_editor.i18n.shortcodeSettings,
									help: rtec_block_editor.i18n.example + ": 'attendeelist=\"false\" showheader=\"true\"'",
									value: shortcodeSettings,
									onChange: setState
								})
							)
						)
					];
				} else {
					jsx = [
						createElement(InspectorControls, { key: 'rtec-gutenberg-setting-selector-inspector-controls' },
							createElement(PanelBody, { title: rtec_block_editor.i18n.addSettings },
								createElement(TextareaControl, {
									key: 'rtec-gutenberg-settings',
									className: 'rtec-gutenberg-settings',
									label: rtec_block_editor.i18n.shortcodeSettings,
									help: rtec_block_editor.i18n.example + ": 'attendeelist=\"false\" showheader=\"true\"'",
									value: shortcodeSettings,
									onChange: setState
								})
							)
						)
					];
				}
				jsx.push(
					createElement('div', { key: 'rtec-registration-form-preview-wrap', className: 'rtec-editor-preview' },
						createElement(ServerSideRender, { key: 'rtec-registration-form/rtec-registration-form', block: 'rtec/rtec-form-block', attributes: props.attributes })
					)
				);
			} else {
				var eventTitleNew = eventID === 'auto' ? rtec_block_editor.i18n.eventAuto : '';
				if (eventID && eventID !== 'auto') {
					var o2 = eventOptions.find(function (opt) { return String(opt.value) === String(eventID); });
					if (o2) eventTitleNew = o2.label;
				}
				jsx = [
					createElement(InspectorControls, { key: 'rtec-form-new-ui-inspector' },
						createElement(PanelBody, { title: rtec_block_editor.i18n.addSettings },
							hasEvent ? createElement('p', { className: 'rtec-gb-event-title-wrap' }, createElement('strong', { className: 'rtec-gb-event-title' }, eventTitleNew)) : null,
							createElement(TextControl, { __next40pxDefaultSize: true, value: searchQuery, onChange: setSearchQuery, placeholder: rtec_block_editor.i18n.searchEventsPlaceholder }),
							searchLoading ? createElement('p', { className: 'rtec-block-search-loading' }, '…') : null,
							createElement(SelectControl, { label: rtec_block_editor.i18n.whichevent, value: eventID === undefined || eventID === 0 ? 'auto' : String(eventID), options: eventOptions, onChange: selectEvent }),
							rtec_block_editor.i18n.eventAutoHelp ? createElement('p', { className: 'rtec-block-help' }, rtec_block_editor.i18n.eventAutoHelp) : null,
							hasEvent ? createElement(ToggleControl, { label: rtec_block_editor.i18n.showEventHeader, checked: props.attributes.showheader === true, onChange: function (v) { setAttributes({ showheader: !!v }); } }) : null,
							hasEvent ? createElement(ToggleControl, { label: rtec_block_editor.i18n.showAttendeeTools, checked: props.attributes.showtools === true, onChange: function (v) { setAttributes({ showtools: !!v }); } }) : null,
							hasEvent ? createElement(ToggleControl, { label: rtec_block_editor.i18n.showAttendeeListAboveForm, checked: props.attributes.attendeelist === true, onChange: function (v) { setAttributes({ attendeelist: !!v }); } }) : null,
							hasEvent ? createElement(ToggleControl, { label: rtec_block_editor.i18n.showFormInitially, checked: props.attributes.hidden !== true, onChange: function (v) { setAttributes({ hidden: !v }); } }) : null
						)
					),
					createElement('div', { key: 'rtec-registration-form-preview-wrap', className: 'rtec-editor-preview' },
						createElement(ServerSideRender, { key: 'rtec-registration-form-preview', block: 'rtec/rtec-form-block', attributes: props.attributes })
					)
				];
			}

			return createElement('div', blockProps, jsx);
		},
		save: function save() {
			return null;
		}
	});

	// --- Attendee List block ---
	registerBlockType('rtec/rtec-attendee-list-block', {
		apiVersion: 3,
		title: rtec_block_editor.i18n.attendeeList,
		description: rtec_block_editor.i18n.attendeeListDesc,
		icon: 'list-view',
		category: (rtec_block_editor.blockCategory || 'widgets'),
		attributes: {
			eventID: { type: 'string', default: 'auto' },
			showheader: { type: 'boolean', default: false },
		},
		edit: function edit(props) {
			var blockProps = useBlockProps();
			var setAttributes = props.setAttributes;
			var eventID = props.attributes.eventID || 'auto';
			var showheader = props.attributes.showheader === true;

			var _so = useState(buildInitialEventOptions());
			var eventOptions = _so[0];
			var setEventOptions = _so[1];
			var _sq = useState('');
			var searchQueryAtt = _sq[0];
			var setSearchQueryAtt = _sq[1];
			var _sl = useState(false);
			var searchLoadingAtt = _sl[0];
			var setSearchLoadingAtt = _sl[1];
			var currentEventId = eventID && eventID !== 'auto' ? String(eventID) : '';

			useEffect(function () {
				var timer = setTimeout(function () {
					if (typeof wp.apiFetch !== 'function' || !rtec_block_editor.searchEventsUrl) return;
					setSearchLoadingAtt(true);
					var url = rtec_block_editor.searchEventsUrl + '?search=' + encodeURIComponent(searchQueryAtt) + '&current_event_id=' + encodeURIComponent(currentEventId) + '&per_page=25';
					wp.apiFetch({ url: url }).then(function (res) {
						setEventOptions(buildEventOptionsFromApiResponse(res));
					}).catch(function () {}).finally(function () { setSearchLoadingAtt(false); });
				}, 300);
				return function () { clearTimeout(timer); };
			}, [searchQueryAtt, currentEventId]);

			function onSelectEvent(value) {
				setAttributes({ eventID: value || 'auto' });
			}
			function onToggleShowHeader(checked) {
				setAttributes({ showheader: !!checked });
			}

			var eventTitle = '';
			if (eventID) {
				var ot = eventOptions.find(function (opt) { return String(opt.value) === String(eventID); });
				if (ot) eventTitle = ot.label;
			}

			var jsx = [
				createElement(InspectorControls, { key: 'rtec-attendee-list-inspector' },
					createElement(PanelBody, { title: rtec_block_editor.i18n.addSettings },
						createElement(TextControl, { __next40pxDefaultSize: true, value: searchQueryAtt, onChange: setSearchQueryAtt, placeholder: rtec_block_editor.i18n.searchEventsPlaceholder }),
						searchLoadingAtt ? createElement('p', { className: 'rtec-block-search-loading' }, '…') : null,
						createElement(SelectControl, { label: rtec_block_editor.i18n.whichevent, value: eventID, options: eventOptions, onChange: onSelectEvent }),
						rtec_block_editor.i18n.eventAutoHelp ? createElement('p', { className: 'rtec-block-help' }, rtec_block_editor.i18n.eventAutoHelp) : null,
						createElement(ToggleControl, { label: rtec_block_editor.i18n.showEventHeader, checked: showheader, onChange: onToggleShowHeader })
					)
				)
			];

			jsx.push(
				createElement('div', { key: 'rtec-attendee-list-preview-wrap', className: 'rtec-editor-preview' },
					createElement(ServerSideRender, { key: 'rtec-attendee-list-preview', block: 'rtec/rtec-attendee-list-block', attributes: props.attributes })
				)
			);

			return createElement('div', blockProps, jsx);
		},
		save: function save() {
			return null;
		}
	});
})();
