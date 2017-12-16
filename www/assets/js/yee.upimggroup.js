// JavaScript Document
(function ($, Yee, layer) {

    var getImgObj = function (url, width, height) {
        var img = $('<img/>');
        img.height(height);
        img.width(width);
        var imgtemp = new Image();
        imgtemp.onload = function () {
            var imgwidth = imgtemp.width;
            var imgheight = imgtemp.height;
            var mwidth = width, mheight = height;

            if (imgwidth > imgheight && imgwidth > width) {
                mwidth = width;
                mheight = parseInt((width / imgwidth) * imgheight);
            }
            else if (imgheight > imgwidth && imgheight > height) {
                mheight = height;
                mwidth = parseInt((height / imgheight) * imgwidth);
            }
            else {
                if (imgwidth > width) {
                    mwidth = width;
                    mheight = height;
                }
                else {
                    mwidth = imgwidth;
                    mheight = imgheight;
                }
            }
            img.height(mheight);
            img.width(mwidth);
        };
        imgtemp.src = url;
        img.attr('src', url);
        return img;
    };

    var initUpimgGroup = function (element, options) {

        var qem = $(element);//要填充的输入框
        var size = Number(qem.data('size')) || 0;//数量
        //创建id
        var id = qem.attr('id');
        if (!id) {
            id = 'upimgs_' + new Date().getTime();
            qem.attr('id', id);
        }


        var shower = $('<div class="yee-upimggroup-shower clearfix"></div>').insertBefore(qem);
        var button = $('<a href="javascript:;" class="yee-upimggroup-btn" ></a>').appendTo(shower);
        if (qem.setDefault) {
            shower.mouseenter(function () {
                qem.setDefault();
            });
        }

        //跟新值
        var update = function () {
            var imgs = [];
            shower.find('div.yee-upimggroup-item').each(function (index, element) {
                var _this = $(element);
                var dat = _this.data('value');
                imgs.push(dat);
            });
            if (imgs.length === 0) {
                qem.val('');
            }
            else {
                var valstr = JSON.stringify(imgs);
                qem.val(valstr);
            }
            qem.change();
            if (size > 0) {
                if (imgs.length >= size) {
                    button.hide();
                } else {
                    button.show();
                }
            }
        };

        qem.on('reset', function () {
            shower.find('div.yee-upimggroup-item').remove();
            update();
        });

        var addimg = function (info) {
            var host = options.host || info.host || '';
            var retUrl = host + (info.url || '');
            if (options.showSize) {
                var retUrl = retUrl.replace(/(\.[a-z]+)$/, function ($0, $1) {
                    return '_' + options.showSize + $1;
                });
            }
            var img = getImgObj(retUrl, 80, 80);
            var oitem = $('<div class="yee-upimggroup-item"><table  border="0" cellspacing="0" cellpadding="0"><tr><td style="padding:0px; vertical-align:middle; text-align:center; line-height:0px;"></td></tr></table></div>').data('value', info.url);
            var td = oitem.find('td');
            td.append(img);
            var delBtn = $('<a href="javascript:void(0);"></a>').addClass('yee-upimggroup-delpic').appendTo(oitem);
            delBtn.click(function () {
                $(this).parent('.yee-upimggroup-item').remove();
                update();
            });
            oitem.insertBefore(button);
            update();
        };

        var valText = qem.val() || '[]';
        var vals = [];
        if (valText !== '' && valText !== 'null') {
            try {
                vals = JSON.parse(valText);
            } catch (e) {
                vals = [];
            }
        }
        for (var i = 0; i < vals.length; i++) {
            var item = {url: vals[i]};
            addimg(item);
        }
        button.yee_upfile(options);
        button.on('afterUpfile', function (ev, ret) {
            if (ret.status) {
                if (ret.list) {
                    for (var i = 0; i < ret.list.length; i++) {
                        addimg(ret.list[i]);
                    }

                } else if (ret.info) {
                    addimg(ret.info);
                }
            }
        })
    };
    Yee.extend('input', 'upimggroup', initUpimgGroup);

})(jQuery, Yee, layer);