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
	const data = window.fioTransactionsBlock || {
		accounts: [],
		periods: [],
		columns: [],
		canEmbed: false,
		maxDays: 90,
		defaultDay: 90,
		labels: {},
	};
	// All texts are translated in PHP (Block::getLabels()) and passed in the inline data.
	const labels = data.labels;

	function accountSelect(attributes, setAttributes) {
		return el(SelectControl, {
			label: labels.account,
			value: String(attributes.account || ''),
			options: [
				{
					value: '',
					label: labels.selectAccount,
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
				{ title: labels.settings },
				accountSelect(attributes, setAttributes),
				el(SelectControl, {
					label: labels.period,
					value: attributes.period,
					options: data.periods,
					onChange(value) {
						setAttributes({ period: value });
					},
				}),
				attributes.period === 'days' &&
					el(TextControl, {
						label: labels.days,
						help: labels.daysHelp,
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
					label: labels.specificSymbol,
					value: attributes.ss,
					onChange(value) {
						setAttributes({ ss: onlyDigits(value) });
					},
				}),
				el(TextControl, {
					label: labels.variableSymbolPrefix,
					value: attributes.vsPrefix,
					onChange(value) {
						setAttributes({ vsPrefix: onlyDigits(value) });
					},
				})
			),
			el(
				PanelBody,
				{
					title: labels.tableDisplay,
					initialOpen: false,
				},
				el(ToggleControl, {
					label: labels.showSearch,
					checked: attributes.showSearch,
					onChange(value) {
						setAttributes({ showSearch: value });
					},
				}),
				el(ToggleControl, {
					label: labels.pagination,
					help: attributes.paging
						? labels.paginationOn
						: labels.paginationOff,
					checked: attributes.paging,
					onChange(value) {
						setAttributes({ paging: value });
					},
				}),
				attributes.paging &&
					el(SelectControl, {
						label: labels.rowsPerPage,
						value: String(attributes.pageLength),
						options: [10, 25, 50, 100]
							.map(function (n) {
								return { value: String(n), label: String(n) };
							})
							.concat([
								{
									value: '-1',
									label: labels.all,
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
					title: labels.columns,
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
					label: labels.title,
				},
				labels.notAllowed
			);
		} else if (!attributes.account) {
			content = el(
				Placeholder,
				{
					icon: 'money-alt',
					label: labels.title,
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
		title: labels.title,
		icon: 'money-alt',
		category: 'widgets',
		edit: Edit,
		save() {
			return null;
		},
	});
})(window.wp);
