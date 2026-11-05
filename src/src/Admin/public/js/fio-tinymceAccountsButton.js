(function () {
	'use strict';

	function viewport() {
		return {
			width: window.innerWidth || document.documentElement.clientWidth,
			height: window.innerHeight || document.documentElement.clientHeight,
		};
	}

	window.tinymce.PluginManager.add(
		'fio_bank_transactions_accounts',
		function (editor) {
			editor.addButton('fio_bank_transactions_accounts', {
				title: 'Vložit tabulku transakcí z Fio banky',
				icon: 'dashicons-money',
				onclick() {
					const accountsOptions = [
						{ text: '— Vyberte účet —', value: '' },
					].concat(
						(window.accountOptions || []).map(function (account) {
							return { text: account.text, value: account.value };
						})
					);

					editor.windowManager.open({
						title: 'Vložit transakce z Fio banky',
						body: [
							{
								type: 'listbox',
								name: 'account',
								label: 'Účet',
								values: accountsOptions,
							},
						],
						minWidth: Math.min(viewport().width, 450),
						minHeight: Math.min(viewport().height, 250),
						onsubmit(e) {
							if (e.data.account) {
								editor.insertContent(
									'[fio account="' + e.data.account + '"]'
								);
							}
						},
					});
				},
			});
		}
	);
})();
