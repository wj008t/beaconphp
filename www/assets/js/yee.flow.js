(function ($, Yee, layer) {
    //分页插件
    var Index = 0;
    var Itemps = [];
    var Timer = null;
    Yee.extend('a,button,input', 'flow', function (elem) {
        Index++;
        var qem = $(elem).hide();
        qem.attr('flow-index', Index);
        var item = {};
        item['i'] = qem.attr('flow-index') || '';
        item['n'] = qem.data('flow-name') || '';
        item['b'] = qem.data('flow-branch') || '';
        item['t'] = qem.data('flow-task') || 0;
        if (item['name'] == '' || item['branch'] == '' || item['task'] == 0) {
        } else {
            Itemps.push(item);
        }
        if (Timer != null) {
            window.clearTimeout(Timer);
            Timer = null;
        }
        Timer = window.setTimeout(function () {
            var temp = Itemps;
            Itemps = [];
            $.post('/flow/index/test', {data: JSON.stringify(temp)}, function (ret) {
                if (ret.status) {
                    if (ret.data) {
                        for (var i = 0; i < ret.data.length; i++) {
                            var item = ret.data[i];
                            $('a[flow-index="' + item.i + '"]').show();
                        }
                    }
                }
            }, 'json');
        }, 50);
    });
})(jQuery, Yee, layer);
