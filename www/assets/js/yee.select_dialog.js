(function ($, Yee) {
    Yee.extend(':input', 'select_dialog', function (element, option) {
        var qem = $(element);
        var textBox = $('<input type="text" readonly="readonly"/>').insertAfter(qem);
        textBox.attr('class', qem.attr('class'));
        textBox.attr('style', qem.attr('style'));
        textBox.data('val-for', '#' + qem.attr('id') + '_info');
        qem.hide();

        if (typeof textBox.setError == 'function') {
            qem.on('displayError', function (ev, data) {
                textBox.setError();
            });
            qem.on('displayDefault', function (ev, data) {
                textBox.setDefault();
            });
            qem.on('displayValid', function (ev, data) {
                textBox.setValid();
            });
            textBox.on('mouseenter', function () {
                if (typeof(qem.setDefault) == 'function') {
                    qem.setDefault();
                }
            });
        }
        var button = $('<a class="yee-btn" href="javascript:;" yee-module="dialog" style="display: inline-block;">选择</a>').insertAfter(textBox);
        if (option.width) {
            button.data('width', option.width);
        }
        if (option.height) {
            button.data('height', option.height);
        }
        button.data('href', option.href || '');
        button.data('assign', {value: qem.val(), text: qem.data('text') || ''});
        textBox.val(qem.data('text') || '');
        button.on('select_dialog_data', function (ev, data) {
            if (data && data.value && data.text) {
                qem.val(data.value);
                textBox.val(data.text);
                if (typeof textBox.setDefault == 'function') {
                    textBox.setDefault();
                }
            }
        });
        Yee.update(button.parent());
    });
})(jQuery, Yee);