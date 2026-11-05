(function () {
	'use strict';

	const DataTable = window.DataTable;
	const directions = new WeakMap();

	// Rows of a table are shown according to the direction selected above it.
	DataTable.ext.search.push(function (settings, searchData, dataIndex) {
		const api = new DataTable.Api(settings);
		const direction = directions.get(api.table().node()) || 'all';
		if (direction === 'all') {
			return true;
		}
		const row = api.row(dataIndex).node();

		return row.getAttribute('data-direction') === direction;
	});

	document
		.querySelectorAll('.fioTransactionsTable')
		.forEach(function (table) {
			// The date column is sorted by its ISO `data-order` attribute.
			const dataTable = new DataTable(table, {
				language: {
					url:
						window.fioTransactionsFrontendLocalize
							.datatablesFilesUrl + '/cs.json',
				},
				order: [[0, 'desc']],
			});

			const wrapper = table.closest('.fioTransactions');
			const filter = wrapper
				? wrapper.querySelector('.fioTransactionsFilter')
				: null;
			if (!filter) {
				return;
			}
			const buttons = filter.querySelectorAll('button');
			buttons.forEach(function (button) {
				button.addEventListener('click', function () {
					directions.set(table, button.dataset.direction);
					buttons.forEach(function (other) {
						other.setAttribute(
							'aria-pressed',
							other === button ? 'true' : 'false'
						);
					});
					dataTable.draw();
				});
			});
			filter.hidden = false;
		});
})();
