(function ($, Yee) {

    function imgShower(element, options) {

        options = $.extend({
            showBtn: false,
            showType: 0,
            showMaxwidth: 400,
            showMaxheight: 300,
            autoShow: false,
            defimg: '',
            hideInput: false,
            host: ''
        }, options || {});

        var qem = $(element);
        if (options.hideInput) {
            qem.hide();
        }

        if (options.showBtn) {
            var btn = $('<a class="sdopx-btn" href="javascript:;" style="margin-left:5px;">查看</a>').insertAfter(qem);
            btn.click(function () {
                var url = qem.val();
                show(url, options.host || '');
                return false;
            });
        }

        var show_maxwidth = options.showMaxwidth;
        var show_maxheight = options.showMaxheight;
        var shower = null;
        var show = function (url, host) {
            host = host || '';
            if (typeof (url) !== 'string' || url.length == 0) {
                return;
            }
            var re = new RegExp('^.*\.(jpeg|jpg|png|gif|bmp)$', 'ig');
            if (!re.exec(url)) {
                return;
            }
            var img = $('<img/>');
            img.on('load', function () {
                var oldwidth = this.width;
                var oldheight = this.height;
                var width = oldwidth;
                var height = oldheight;
                if (oldwidth > show_maxwidth) {
                    var pt = show_maxwidth / oldwidth;//高比宽
                    oldwidth = width = show_maxwidth;
                    oldheight = height = oldheight * pt;
                }
                if (oldheight > show_maxheight) {
                    var pt = show_maxheight / oldheight;//宽比高
                    height = show_maxheight;
                    width = oldwidth * pt;
                }
                if (shower != null) {
                    shower.remove();
                    shower = null;
                }
                $(this).css({width: width + 'px', height: height + 'px'});
                if (options.showType == 0) {
                    shower = $('<div title="查看图片" style="text-align:center"></div>');
                    shower.appendTo(qem.parent());
                    shower.css({
                        'background-color': '#F7F7F7',
                        'border': 'solid 1px #DDD',
                        'padding': '10px',
                        'margin-top': '2px',
                        width: width + 'px',
                        height: height + 'px'
                    });
                    shower.append(img);
                } else if (options.showType == 1) {
                    shower = $('<div title="查看图片" style="text-align:center"></div>');
                    shower.prependTo(qem.parent());
                    shower.css({
                        'background-color': '#F7F7F7',
                        'border': 'solid 1px #DDD',
                        'padding': '10px',
                        'margin-top': '2px',
                        width: width + 'px',
                        height: height + 'px'
                    });
                    shower.append(img);
                } else {
                    shower = $('<div  title="查看图片" style="text-align:center"></div>');
                    shower.append(img);
                    shower.css({'padding': '2px', 'margin': '0px'});
                    shower.appendTo(document.body);
                    shower.dialog({
                        autoOpen: true,
                        position: {
                            my: "left top",
                            at: "left bottom+1",
                            of: qem
                        },
                        //   position: [offset.left, offset.top],
                        draggable: false, resizable: false,
                        width: width + 10,
                        height: height + 50,
                        modal: false,
                        close: function () {
                            shower.remove();
                            shower = null;
                        }
                    });
                }
            });
            img.attr('src', host + url);
        };

        qem.on('initUpfile', function (ev, data) {
            if (options.showType == 0 || options.showType == 1) {
                show(data.oldval, options.host || '');
            }
        });

        qem.on('afterUpfile', function (ev, context) {
            //console.log(context);
            if (typeof(context.info) == 'undefined' && typeof(context.data) == 'object' && typeof(context.data.info) != 'undefined') {
                context.info = context.data.info;
            }
            if (context.status) {
                show(context.info.url, context.info.host || '');
            }
        });

        qem.on('emptyUpfile', function (ev) {
            if (shower) {
                shower.remove();
            }
            show(options.defimg);
        });

        if (options.defimg.length > 0) {
            show(options.defimg);
        }

        if (options.autoShow == 1) {
            var url = qem.val();
            if (url != '') {
                show(url);
            }
        }
    }

    Yee.extend(':input', 'imgshower', imgShower);
})(jQuery, Yee);