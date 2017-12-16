(function ($, Yee) {
    Yee.extend(':input', 'select_dialog', function (element) {
        var box = $(element);
        var id = box.attr('id') || box.attr('name');
        id = id.replace(':select_dialog_label', '');
        var valBox = $('#' + id);
        var btn = $('#' + id + '\\:select_dialog_btn');
        btn.on('dataTDialog', function (ev, data) {
            if (data && data.value && data.text) {
                valBox.val(data.value);
                box.val(data.text);
            }
        });
    });
})(jQuery, Yee);