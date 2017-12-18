(function ($, Yee, layer) {
    var frameIndex = 0;


    function filesInfo(field) {
        if (/msie/.test(navigator.userAgent.toLowerCase())) {
            var path = field.val();
            var extension = path.lastIndexOf('.') === -1 ? '' : path.substr(path.lastIndexOf('.') + 1, path.length).toLowerCase();
            var size = 0;
            try {
                var obj_img = new Image();
                obj_img.dynsrc = field[0].value;
                size = obj_img.fileSize;
            } catch (e) {

            }
            return [{fileName: path, fileSize: size, extension: extension}];
        }
        return [];
    }

    Yee.FrameUpload = function (qem, button, options) {
        options = $.extend({
            multiple: false,
            extensions: '',
            fieldName: 'filedata',
            url: '/service/upfile',
            bindData: {}
        }, options || {});
        if (!button) {
            return;
        }
        button.off('click');
        frameIndex++;
        var frameName = 'yee_upload_frame_' + frameIndex;
        var iframe = $('<iframe name="' + frameName + '" src="javascript:false;"  width="500"  height="200" style="position:absolute; top:-550px; left:-580px" id="' + frameName + '" ></iframe>').appendTo(document.body);
        iframe.load(function () {
            form.trigger('reset');
            var jsonText = this.contentWindow.document.body.innerText;
            if (jsonText === 'false') {
                return;
            }
            try {
                var data = JSON.parse(jsonText);
                qem.triggerHandler('completeUpload', [data]);
            } catch (e) {
                layer.alert('上传失败，服务器出现了些状况');
            }
        });
        var form = $('<form action="' + options.url + '" target="' + frameName + '" method="post" enctype="multipart/form-data"></form>').appendTo(document.body);
        var fileLayout = $('<div style="overflow: hidden;position:absolute;"></div>').hide().appendTo(form);
        fileLayout.css({'opacity': 0, 'top': '-300px', 'left': '-300px', 'position': 'absolute', 'background-color': '#06F', 'zIndex': 1000000, 'cursor': 'pointer'});
        button.on('mouseenter', function () {
            if (qem.is(':disabled')) {
                return;
            }
            var left = button.offset().left;
            var top = button.offset().top;
            if ($.browser.msie && ($.browser.version == "6.0" || $.browser.version == "7.0") && !$.support.style) {
                left = left + document.body.scrollLeft;
                top = top + document.body.scrollTop;
            }
            var width = button.outerWidth();
            var height = button.outerHeight();
            fileLayout.css({left: left + 'px', top: top + 'px', width: width + 'px', height: height + 'px'}).show();
        });
        fileLayout.on('mouseleave', function () {
            $(this).hide();
        });
        var fileArea = $('<div></div>').css({
            'float': 'left',
            'margin-left': '-2px',
            'margin-top': '-2px'
        }).appendTo(fileLayout);
        $('<input type="hidden" name="UPLOAD_IDENTIFIER"/>').appendTo(fileArea);
        for (var key in options.bindData) {
            if (options.bindData[key] !== null) {
                $('<input type="hidden"/>').attr('name', key).val(options.bindData[key]).appendTo(fileArea);
            }
        }
        //处理输入项
        var field = $('<input type="file" style="cursor:pointer"/>').attr('name', options.fieldName).appendTo(fileArea);
        field.css({'font-size': '460px', 'margin': '0', 'padding': '0', 'border': '0', 'width': '1000px'});
        //上传
        field.on('change', function () {
            var infoItems = filesInfo(field);
            for (var i = 0; i < infoItems.length; i++) {
                var info = infoItems[i];
                var re = new RegExp("(^|\\s|,)" + info.extension + "($|\\s|,)", "ig");
                if (options.extensions !== '' && (re.exec(options.extensions) === null || info.extension === '')) {
                    layer.alert('对不起，只能上传 ' + options.extensions + ' 类型的文件。');
                    form.trigger('reset');
                    return;
                }
            }
            if (qem.triggerHandler('beforeUpload', [infoItems]) === false) {
                form.trigger('reset');
                return;
            }
            form.trigger('submit');
        });

    }
})(jQuery, Yee, layer);