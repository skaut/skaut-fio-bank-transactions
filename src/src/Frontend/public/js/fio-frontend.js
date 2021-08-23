(function ($) {
    'use strict';

    $.fn.dataTable.moment( 'DD.MM.YYYY' );
    var $dataTable = $('.fioTransactionsTable').DataTable({
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Czech.json',
            search: "Hledat",
            clear: "Zrušit"
        },
        columnDefs: [
            {
                "targets": [0],
                "render": $.fn.dataTable.moment('DD.MM.YYYY')
            }
        ],
        order: [[ 0, 'desc' ]]
    });

    $dataTable.on('init.dt', function () {
        $(this).find('th').each(function () {
            $(this).html('<span>' + $(this).html() + '</span>');
        });
    });

})(jQuery);