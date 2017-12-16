(function ($, Yee) {

    $.datepicker.regional['zh-CN'] = {
        closeText: '关闭',
        prevText: '&#x3C;上月',
        nextText: '下月&#x3E;',
        currentText: '今天',
        monthNames: ['一月', '二月', '三月', '四月', '五月', '六月',
            '七月', '八月', '九月', '十月', '十一月', '十二月'],
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月',
            '七月', '八月', '九月', '十月', '十一月', '十二月'],
        dayNames: ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
        dayNamesShort: ['周日', '周一', '周二', '周三', '周四', '周五', '周六'],
        dayNamesMin: ['日', '一', '二', '三', '四', '五', '六'],
        weekHeader: '周',
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: '年'
    };
    $.datepicker.setDefaults($.datepicker.regional['zh-CN']);

    Yee.extend(':input', 'date', function (element, options) {
        var qem = $(element);
        options = $.extend({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:2050'
        }, options || {});
        for (var key in options) {
            var lkey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
            var dval = qem.data(lkey);
            if (typeof (dval) !== 'undefined' && dval !== null) {
                options[key] = dval;
            }
        }
        qem.datepicker(options);
        this.destroy = function () {
            qem.datepicker('destroy');
        };
    });

    Yee.extend(':input', 'datetime', function (element) {
        var qem = $(element);
        var options = {
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:2050'
        };
        for (var key in options) {
            var lkey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
            var dval = qem.data(lkey);
            if (typeof (dval) !== 'undefined' && dval !== null) {
                options[key] = dval;
            }
        }
        qem.datetimepicker(options);
        this.destroy = function () {
            qem.datetimepicker('destroy');
        };
    });

})(jQuery, Yee);