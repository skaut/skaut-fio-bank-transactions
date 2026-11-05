(function () {
	'use strict';

	document
		.querySelectorAll('.fioTransactionsTable')
		.forEach(function (table) {
			// The date column is sorted by its ISO `data-order` attribute.
			new window.DataTable(table, {
				language: {
					url:
						window.fioTransactionsFrontendLocalize
							.datatablesFilesUrl + '/cs.json',
				},
				order: [[0, 'desc']],
			});
		});
})();
