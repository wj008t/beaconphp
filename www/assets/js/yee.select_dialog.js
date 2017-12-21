(function ($, Yee) {
    Yee.extend(':input', 'select_dialog', function (element, option) {
        var qem = $(element);
        var textBox = $('<input type="text" readonly="readonly"/>').insertAfter(qem);
        textBox.attr('class', qem.attr('class'));
        textBox.attr('style', qem.attr('style'));
        qem.hide();
        qem.on('displayError', function (ev, data) {
            textBox.addClass('error');
        });
        qem.on('displayDefault displayValid', function (ev, data) {
            textBox.removeClass('error');
        });
        textBox.on('mouseenter', function () {
            if (typeof(qem.setDefault) == 'function') {
                qem.setDefault();
            }
        });
        var button = $('<a class="yee-btn" href="javascript:;" yee-module="dialog" style="display: inline-block;">选择</a>').insertAfter(textBox);
        button.data('href', option.href || '');
        button.on('select_dialog_data', function (ev, data) {
            if (data && data.value && data.text) {
                qem.val(data.value);
                textBox.val(data.text);
            }
        });
        Yee.update(button.parent());
    });
})(jQuery, Yee);