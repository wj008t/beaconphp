(function ($, Yee, layer) {

    var LayIndex = null;

    function getCode(info, option) {
        var pkey = option.pagekey || 'page';
        var prveText = option.prveText || '上一页';
        var nextText = option.nextText || '下一页';

        var firstText = option.firstText || '首页';
        var lastText = option.lastText || '尾页';

        var page = parseInt(info.page);
        var size = size || option.page_size;
        var maxpage = parseInt(info.page_count);
        info.query = info.query || '';
        var query = Yee.parseUrl(info.query);
        var start = (maxpage < 10 || page <= 5) ? 1 : (page + 5 > maxpage ? maxpage - 9 : page - 4);
        var temp = start + 9;
        var end = (page + 5 > maxpage) ? maxpage : (temp > maxpage ? maxpage : temp);
        if (size) {
            query.prams.page_size = size;
        }
        var prve = page - 1 <= 0 ? 1 : page - 1;
        var next = page + 1 >= maxpage ? maxpage : page + 1;
        var html = '';

        query.prams[pkey] = 1;
        if (option.firstPage == 1 || option.firstPage == 'true') {
            html += '<a href="javascript:;" data-url="' + Yee.toUrl(query) + '" class="first">' + firstText + '</a>';
        }
        query.prams[pkey] = prve;
        html += '<a href="javascript:;" data-url="' + Yee.toUrl(query) + '" class="prev">' + prveText + '</a>';
        if (option.numPage == 1 || option.numPage == 'true') {
            for (var i = start; i <= end; i++) {
                var p_page = i;
                if (p_page == page) {
                    html += '<b>' + p_page + '</b>';
                } else {
                    query.prams[pkey] = p_page;
                    html += '<a href="javascript:;" data-url="' + Yee.toUrl(query) + '">' + p_page + '</a>';
                }
            }
        }
        query.prams[pkey] = next;
        html += '<a href="javascript:;" data-url="' + Yee.toUrl(query) + '" class="next">' + nextText + '</a>';
        if (option.lastPage == 1 || option.lastPage == 'true') {
            query.prams[pkey] = maxpage;
            html += '<a href="javascript:;" data-url="' + Yee.toUrl(query) + '" class="last">' + lastText + '</a>';
        }
        if (option.goPage == 1 || option.goPage == 'true') {
            var spage = page > maxpage ? maxpage : page;
            spage = spage <= 0 ? 1 : spage;
            query.prams[pkey] = '--gopage--';
            html += '<input type="text" class="inp" value="' + spage + '"/><a class="gopage" data-url="' + Yee.toUrl(query) + '" href="javascript:;">GO</a>';
        }
        return html;
    }

    Yee.extend('*', 'pagebar', function (elem, option) {

        var qem = $(elem);
        option = $.extend({
            page_size: 0,
            hidden: 0,
            goPage: 1,
            numPage: 1,
            firstPage: 1,
            lastPage: 1,
            info: null
        }, option);
        var list = option.bind || null;
        var pagebar = qem.find('[v-name="bar"]');
        if (pagebar.length > 0) {
            pagebar.on('click', 'a', function () {
                var _this = $(this);
                if (layer) {
                    LayIndex = layer.load(1, {
                        shade: [0.1, '#fff'] //0.1透明度的白色背景
                    });
                }
                var url = $(this).data('url');
                if (_this.is('.gopage')) {
                    var input = _this.prev("input.inp");
                    var page = input.val() || 1;
                    page = /^\d+$/.test(page) ? page : 1;
                    url = url.replace('--gopage--', page);
                }
                if (list && $(list).length > 0) {
                    $(list).triggerHandler('load', [{url: url, showMsg: true}]);
                }
                qem.triggerHandler('bar-click');
            });
        }

        qem.on('source', function (ev, source) {
            var pageinfo = source.pdata || {};
            pageinfo.query = source.query;
            if (pagebar.length > 0) {
                pagebar.html(getCode(pageinfo, option));
            }
            qem.find('[v-name="count"]').text(pageinfo.records_count);
            qem.find('[v-name="page_count"]').text(pageinfo.page_count);
            qem.find('[v-name="page"]').text(pageinfo.page);
            qem.find('[v-name="page_size"]').text(pageinfo.page_size);
            qem.find('[v-name="max_page"]').text(pageinfo.page_count);
            Yee.update(qem.get(0));
            qem.trigger('change');
        });

        if (list) {
            $(list).on('source', function (ev, source, query) {
                source.query = query;
                qem.triggerHandler('source', [source]);
            });
            $(list).on('back', function (ev) {
                if (layer && LayIndex !== null) {
                    layer.close(LayIndex);
                    LayIndex = null;
                }
            });
        }
        if (option.info) {
            var query = window.location.pathname + '.json' + window.location.search;
            var source = {};
            source.pdata = option.info;
            source.query = query;
            qem.triggerHandler('source', [source]);
        }

    });

})(jQuery, Yee, typeof(layer) == 'undefined' ? null : layer);