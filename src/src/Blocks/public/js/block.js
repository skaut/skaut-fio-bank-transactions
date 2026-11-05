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
	const Placeholder = wp.components.Placeholder;
	const ServerSideRender = wp.serverSideRender;
	const data = window.fioTransactionsBlock || {
		accounts: [],
		columns: [],
		canEmbed: false,
		maxDays: 3650,
		defaultDay: 365,
	};

	function accountSelect(attributes, setAttributes) {
		return el(SelectControl, {
			label: 'Účet',
			value: String(attributes.account || ''),
			options: [{ value: '', label: '— Vyberte účet —' }].concat(
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
				{ title: 'Nastavení' },
				accountSelect(attributes, setAttributes),
				el(TextControl, {
					label: 'Počet dní zpět',
					type: 'number',
					min: 1,
					max: data.maxDays,
					value: attributes.days,
					onChange(value) {
						setAttributes({
							days: parseInt(value, 10) || data.defaultDay,
						});
					},
				}),
				el(TextControl, {
					label: 'Jen specifický symbol (SS)',
					value: attributes.ss,
					onChange(value) {
						setAttributes({ ss: onlyDigits(value) });
					},
				}),
				el(TextControl, {
					label: 'Jen variabilní symbol začínající na',
					value: attributes.vsPrefix,
					onChange(value) {
						setAttributes({ vsPrefix: onlyDigits(value) });
					},
				})
			),
			el(
				PanelBody,
				{ title: 'Sloupce', initialOpen: false },
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
				{ icon: 'money-alt', label: 'Transakce z Fio banky' },
				'Nemáte oprávnění vkládat transakce z bankovních účtů.'
			);
		} else if (!attributes.account) {
			content = el(
				Placeholder,
				{ icon: 'money-alt', label: 'Transakce z Fio banky' },
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
		title: 'Transakce z Fio banky',
		icon: 'money-alt',
		category: 'widgets',
		edit: Edit,
		save() {
			return null;
		},
	});
})(window.wp);
