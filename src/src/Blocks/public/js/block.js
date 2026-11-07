(function (wp) {
	'use strict';

	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const InspectorControls = wp.blockEditor.InspectorControls;
	const useBlockProps = wp.blockEditor.useBlockProps;
	const PanelBody = wp.components.PanelBody;
	const SelectControl = wp.components.SelectControl;
	const TextControl = wp.components.TextControl;
	const CheckboxControl = wp.components.CheckboxControl;
	const ToggleControl = wp.components.ToggleControl;
	const Placeholder = wp.components.Placeholder;
	const ServerSideRender = wp.serverSideRender;
	const __ = wp.i18n.__;
	const sprintf = wp.i18n.sprintf;
	const data = window.fioTransactionsBlock || {
		accounts: [],
		periods: [],
		columns: [],
		canEmbed: false,
		maxDays: 90,
		defaultDay: 90,
	};

	function accountSelect(attributes, setAttributes) {
		return el(SelectControl, {
			label: __('Account', 'skaut-fio-bank-transactions'),
			value: String(attributes.account || ''),
			options: [
				{
					value: '',
					label: __(
						'— Select an account —',
						'skaut-fio-bank-transactions'
					),
				},
			].concat(
				data.accounts.map(function (account) {
					return {
						value: String(account.value),
						label: account.label,
					};
				})
			),
			onChange(value) {
				setAttributes({ account: parseInt(value, 10) || 0 });
			},
		});
	}

	function onlyDigits(value) {
		return value.replace(/\D/g, '');
	}

	function Edit(props) {
		const attributes = props.attributes;
		const setAttributes = props.setAttributes;
		const blockProps = useBlockProps();

		const inspector = el(
			InspectorControls,
			null,
			el(
				PanelBody,
				{ title: __('Settings', 'skaut-fio-bank-transactions') },
				accountSelect(attributes, setAttributes),
				el(SelectControl, {
					label: __('Period', 'skaut-fio-bank-transactions'),
					value: attributes.period,
					options: data.periods,
					onChange(value) {
						setAttributes({ period: value });
					},
				}),
				attributes.period === 'days' &&
					el(TextControl, {
						label: sprintf(
							/* translators: %d: maximum number of days */
							__(
								'Number of days (max. %d)',
								'skaut-fio-bank-transactions'
							),
							data.maxDays
						),
						help: __(
							'Without an additional authorization, Fio banka provides only the last 90 days.',
							'skaut-fio-bank-transactions'
						),
						type: 'number',
						min: 1,
						max: data.maxDays,
						value: Math.min(attributes.days, data.maxDays),
						onChange(value) {
							const days = parseInt(value, 10) || data.defaultDay;
							setAttributes({
								days: Math.max(1, Math.min(days, data.maxDays)),
							});
						},
					}),
				el(TextControl, {
					label: __(
						'Only this specific symbol (SS)',
						'skaut-fio-bank-transactions'
					),
					value: attributes.ss,
					onChange(value) {
						setAttributes({ ss: onlyDigits(value) });
					},
				}),
				el(TextControl, {
					label: __(
						'Only variable symbols starting with',
						'skaut-fio-bank-transactions'
					),
					value: attributes.vsPrefix,
					onChange(value) {
						setAttributes({ vsPrefix: onlyDigits(value) });
					},
				})
			),
			el(
				PanelBody,
				{
					title: __('Table display', 'skaut-fio-bank-transactions'),
					initialOpen: false,
				},
				el(ToggleControl, {
					label: __('Show search', 'skaut-fio-bank-transactions'),
					checked: attributes.showSearch,
					onChange(value) {
						setAttributes({ showSearch: value });
					},
				}),
				el(ToggleControl, {
					label: __('Pagination', 'skaut-fio-bank-transactions'),
					help: attributes.paging
						? __(
								'The table is split into pages.',
								'skaut-fio-bank-transactions'
							)
						: __(
								'All transactions are shown at once.',
								'skaut-fio-bank-transactions'
							),
					checked: attributes.paging,
					onChange(value) {
						setAttributes({ paging: value });
					},
				}),
				attributes.paging &&
					el(SelectControl, {
						label: __(
							'Rows per page',
							'skaut-fio-bank-transactions'
						),
						value: String(attributes.pageLength),
						options: [10, 25, 50, 100]
							.map(function (n) {
								return { value: String(n), label: String(n) };
							})
							.concat([
								{
									value: '-1',
									label: __(
										'All',
										'skaut-fio-bank-transactions'
									),
								},
							]),
						onChange(value) {
							setAttributes({ pageLength: parseInt(value, 10) });
						},
					})
			),
			el(
				PanelBody,
				{
					title: __('Columns', 'skaut-fio-bank-transactions'),
					initialOpen: false,
				},
				data.columns.map(function (column) {
					return el(CheckboxControl, {
						key: column.value,
						label: column.label,
						checked:
							attributes.columns.indexOf(column.value) !== -1,
						onChange(checked) {
							// Keep the canonical column order.
							setAttributes({
								columns: data.columns
									.map(function (c) {
										return c.value;
									})
									.filter(function (c) {
										return c === column.value
											? checked
											: attributes.columns.indexOf(c) !==
													-1;
									}),
							});
						},
					});
				})
			)
		);

		let content;
		if (!data.canEmbed) {
			content = el(
				Placeholder,
				{
					icon: 'money-alt',
					label: __(
						'Fio bank transactions',
						'skaut-fio-bank-transactions'
					),
				},
				__(
					'You are not allowed to insert transactions of bank accounts.',
					'skaut-fio-bank-transactions'
				)
			);
		} else if (!attributes.account) {
			content = el(
				Placeholder,
				{
					icon: 'money-alt',
					label: __(
						'Fio bank transactions',
						'skaut-fio-bank-transactions'
					),
				},
				accountSelect(attributes, setAttributes)
			);
		} else {
			content = el(ServerSideRender, {
				block: 'skaut-fio-bank-transactions/transactions',
				attributes,
			});
		}

		return el(Fragment, null, inspector, el('div', blockProps, content));
	}

	wp.blocks.registerBlockType('skaut-fio-bank-transactions/transactions', {
		apiVersion: 3,
		title: __('Fio bank transactions', 'skaut-fio-bank-transactions'),
		icon: 'money-alt',
		category: 'widgets',
		edit: Edit,
		save() {
			return null;
		},
	});
})(window.wp);
