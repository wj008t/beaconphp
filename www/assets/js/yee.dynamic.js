(function ($, Yee) {

    String.prototype.formatId = function () {
        return this.replace(/(:|\.)/g, '\\$1');
    };
    Array.prototype.hasValue = function (val) {
        if (typeof key == 'string' || typeof key == 'number' || typeof key == 'boolean') {
            for (var k in this) {
                var val = this[k];
                if (val == key) {
                    return true;
                }
            }
            return false;
        } else {
            for (var k in this) {
                var val = this[k];
                if (JSON.stringify(val) == JSON.stringify(key)) {
                    return true;
                }
            }
            return false;
        }
    };

    Yee.extend(':input', 'dynamic', function (elem) {
        var qelem = $(elem);
        var dynamicfun = function (item) {
            //显示
            //console.log(item);
            if (item.show !== void 0) {
                $(item.show).each(function (i, mid) {
                    var showid = ('#row_' + mid).formatId();
                    if (qelem.parents(showid).length > 0) {
                        $('#' + mid.formatId()).show();
                        $('#' + mid.formatId()).data('val-off', false);
                    } else {
                        $(showid).show();
                        $(showid + ' :input').data('val-off', false);
                    }
                });
            }
            //隐藏
            if (item.hide !== void 0) {
                $(item.hide).each(function (i, mid) {
                    var hideid = ('#row_' + mid).formatId();
                    if (qelem.parents(hideid).length > 0) {
                        $('#' + mid.formatId()).hide();
                        $('#' + mid.formatId()).data('val-off', true);
                        $('#' + mid.formatId()).setDefault();
                    } else {
                        $(hideid).hide();
                        $(hideid + ' :input').data('val-off', true);
                        $(hideid + ' :input').setDefault();
                    }
                });
            }

            //关闭验证
            if (item['off-val'] !== void 0) {
                var ids = '#' + item.off.join(',#');
                $(ids.formatId()).data('val-off', true);
                $(ids.formatId()).setDefault();
            }
            //开启验证
            if (item['on-val'] !== void 0) {
                var ids = '#' + item.on.join(',#');
                $(ids.formatId()).data('val-off', false);
            }
        };
        if (qelem.is(':input[yee-module~=checkgroup]')) {
            var id = qelem.attr('id');
            var ul = qelem.parent();
            var items = ul.find(':input[name="' + id + '"]');
            var initclick = function () {
                var data = qelem.data('dynamic');
                var checkeds = ul.find(':input[name="' + id + '"]:checked');
                if ($.isArray(data)) {
                    for (var k in data) {
                        var item = data[k];
                        //相等
                        if (item.eq !== void 0) {
                            $(checkeds).each(function (idx, elm) {
                                var bval = $(elm).val();
                                if (item.eq == bval) {
                                    dynamicfun(item);
                                    return false;
                                }
                            });
                        }
                        //不相等
                        if (item.neq !== void 0) {
                            var neq = true;
                            $(checkeds).each(function (idx, elm) {
                                var bval = $(elm).val();
                                if (item.neq == bval) {
                                    neq = false;
                                    return false;
                                }
                            });
                            if (neq) {
                                dynamicfun(item);
                            }
                        }
                        //包含
                        if (item['in'] !== void 0 && $.isArray(item['in'])) {
                            $(checkeds).each(function (idx, elm) {
                                var bval = $(elm).val();
                                if (item['in'].hasValue(bval)) {
                                    dynamicfun(item);
                                    return false;
                                }
                            });
                        }
                        //不包含
                        if (item.nin !== void 0 && $.isArray(item.nin)) {
                            var nin = true;
                            $(checkeds).each(function (idx, elm) {
                                var bval = $(elm).val();
                                if (item.nin.hasValue(bval)) {
                                    nin = false;
                                    return false;
                                }
                            });
                            if (nin) {
                                dynamicfun(item);
                            }
                        }
                    }
                }
            };
            items.on('click', initclick);
            initclick();
        } else {
            var timer = null;
            qelem.on('blur click change', function () {
                if (timer) {
                    window.clearTimeout(timer);
                    timer = null;
                }
                var qthis = $(this);
                timer = window.setTimeout(function () {
                    var val = qthis.val();
                    if (qthis.is(':radio')) {
                        val = qthis.is(':checked') ? val : '';
                    }
                    if (qthis.is(':checkbox')) {
                        val = qthis.is(':checked') ? true : false;
                    }
                    var data = qthis.data('dynamic');
                    if ($.isArray(data)) {
                        for (var k in data) {
                            var item = data[k];
                            if (item.eq !== void 0) {
                                if (item.eq == val) {
                                    dynamicfun(item);
                                }
                            }
                            if (item.neq !== void 0) {
                                if (item.neq != val) {
                                    dynamicfun(item);
                                }
                            }
                            if (item['in'] !== void 0 && $.isArray(item['in'])) {
                                if (item.eq.hasValue(val)) {
                                    dynamicfun(item);
                                }
                            }
                            if (item.nin !== void 0 && $.isArray(item.nin)) {
                                if (!item.neq.hasValue(val)) {
                                    dynamicfun(item);
                                }
                            }
                        }
                    }
                }, 5);
            });
            qelem.triggerHandler('change');
        }
    });

})(jQuery, Yee);