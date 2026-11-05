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
				el(SelectControl, {
					label: 'Období',
					value: attributes.period,
					options: data.periods,
					onChange(value) {
						setAttributes({ period: value });
					},
				}),
				attributes.period === 'days' &&
					el(TextControl, {
						label: 'Počet dní zpět (max. ' + data.maxDays + ')',
						help: 'Fio banka bez dodatečné autorizace vydá jen posledních 90 dní.',
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
				{ title: 'Zobrazení tabulky', initialOpen: false },
				el(ToggleControl, {
					label: 'Zobrazit vyhledávání',
					checked: attributes.showSearch,
					onChange(value) {
						setAttributes({ showSearch: value });
					},
				}),
				el(ToggleControl, {
					label: 'Stránkování',
					help: attributes.paging
						? 'Tabulka se dělí na stránky.'
						: 'Zobrazí se všechny transakce najednou.',
					checked: attributes.paging,
					onChange(value) {
						setAttributes({ paging: value });
					},
				}),
				attributes.paging &&
					el(SelectControl, {
						label: 'Počet řádků na stránce',
						value: String(attributes.pageLength),
						options: [10, 25, 50, 100]
							.map(function (n) {
								return { value: String(n), label: String(n) };
							})
							.concat([{ value: '-1', label: 'Vše' }]),
						onChange(value) {
							setAttributes({ pageLength: parseInt(value, 10) });
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
