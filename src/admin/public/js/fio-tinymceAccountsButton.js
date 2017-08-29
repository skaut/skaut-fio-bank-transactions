(function () {
    tinymce.PluginManager.add('fio_transactions_accounts', function (editor, url) {
        editor.addButton('fio_transactions_accounts', {
            title: 'insert_account_transactions',
            icon: 'dashicons-money',
            onclick: function () {
                var accounts = window.accountOptions,
                    accountsOptions = [],
                    body = [];

                for (var key in accounts) {
                    if (accounts.hasOwnProperty(key)) {
                        accountsOptions.push({text: accounts[key].text, value: accounts[key].value});
                    }
                }
                body.push({
                    type: 'listbox', name: 'account', label: 'account', values: accountsOptions
                });
                accountsOptions.unshift({text: '------', value: null});

                editor.windowManager.open({
                    title: 'account_pick',
                    body: body,
                    minWidth: Math.min(viewport().width, 450),
                    minHeight: Math.min(viewport().height, 250),
                    onsubmit: function (e) {
                        if (e.data.account) {
                            editor.insertContent('[fio account="' + e.data.account + '"]');
                        }
                    }
                });
            }
        });
    });
})();

function viewport() {
    var e = window, a = 'inner';
    if (!('innerWidth' in window )) {
        a = 'client';
        e = document.documentElement || document.body;
    }
    return {width: e[a + 'Width'], height: e[a + 'Height']};
}