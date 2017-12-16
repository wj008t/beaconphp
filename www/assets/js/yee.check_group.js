(function ($, Yee) {

    if ($.YeeValidator) {
        $.YeeValidator.regFunc('minsize', function (val, size) {
            var vals = (function () {
                var ret = [];
                if (val == '' || !/^\[.*\]$/.test(val)) {
                    return ret;
                }
                try {
                    ret = JSON.parse(val);
                    return ret;
                } catch (e) {
                    return [];
                }
            })();
            return vals.length >= size;
        });
        $.YeeValidator.regFunc('maxsize', function (val, size) {
            var vals = (function () {
                var ret = [];
                if (val == '' || !/^\[.*\]$/.test(val)) {
                    return ret;
                }
                try {
                    ret = JSON.parse(val);
                    return ret;
                } catch (e) {
                    return [];
                }
            })();
            return vals.length <= size;
        });
    }

    $.fn._old_val_check_group = $.fn.val;
    $.fn.val = function (val) {
        if (val === void 0) {
            return this._old_val_check_group();
        }
        var rt = this._old_val_check_group(val);
        if (this.get(0).yee_modules && this.get(0).yee_modules.yee_check_group) {
            this.get(0).yee_modules.yee_check_group.update();
        }
        return rt;
    }

    Yee.extend(':input', 'check_group', function (elem) {
        var qem = $(elem);
        var name = qem.data('bind-name') || qem.attr('id') || '';
        var items = null;
        if (name != '') {
            items = $(':input[name="' + name + '"]').on('click', function () {
                if (qem.setDefault) {
                    qem.setDefault();
                }
                var datas = [];
                items.filter(':checked').each(function () {
                    datas.push($(this).val());
                });
                if (datas.length == 0) {
                    qem._old_val_check_group('');
                } else {
                    qem._old_val_check_group(JSON.stringify(datas));
                }
            });
        }

        this.update = function () {
            if (!items) {
                return;
            }
            items.filter(':checked').prop('checked', false);
            var vals = (function () {
                var val = qem.val() || '';
                var ret = [];
                if (val == '' || !/^\[.*\]$/.test(val)) {
                    return ret;
                }
                try {
                    ret = JSON.parse(val);
                    return ret;
                } catch (e) {
                    return [];
                }
            })();
            for (var i = 0; i < vals.length; i++) {
                items.filter(function () {
                    return ($(this).val() == vals[i]);
                }).prop('checked', true);
            }

        }
        this.update();
    });

})(jQuery, Yee);