(function ($, Yee, layer) {
    //获取上传文件信息
    var filesInfo = function (field) {
        var items = [];
        var files = field[0].files;
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var path = file.name.toString();
            var extension = path.lastIndexOf('.') === -1 ? '' : path.substr(path.lastIndexOf('.') + 1, path.length).toLowerCase();
            items.push({fileName: path, fileSize: file.size, extension: extension});
        }
        return items;
    };
    Yee.Html5Upload = function (qem, options) {
        options = $.extend({
            multiple: false,
            extensions: '',
            fieldName: 'filedata',
            url: '/service/upfile',
            bindData: {}
        }, options || {});

        var form = $('<form"></form>').hide().appendTo(document.body);
        var field = $('<input type="file" style="cursor:pointer"/>').attr('name', options.fieldName).appendTo(form);
        if (options.multiple) {
            field.attr('multiple', 'multiple');
        }
        //提交上传
        var upload = function () {
            var infoItems = filesInfo(field);
            //校验上传类型
            for (var k = 0; k < infoItems.length; k++) {
                var info = infoItems[k];
                var re = new RegExp("(^|\\s|,)" + info.extension + "($|\\s|,)", "ig");
                if (options.extensions !== '' && (re.exec(options.extensions) === null || info.extension === '')) {
                    layer.alert('对不起，只能上传 ' + options.extensions + ' 类型的文件。');
                    form.trigger('reset');
                    return;
                }
            }
            //上传之前
            if (qem.triggerHandler('beforeUpload', [infoItems]) === false) {
                form.trigger('reset');
                return;
            }
            var xhr = new XMLHttpRequest();
            //添加进度
            xhr.upload.addEventListener("progress", function (evt) {
                qem.triggerHandler('progressUpload', [{total: evt.total, loaded: evt.loaded}])
            }, false);
            xhr.addEventListener("load", function (evt) {
                var jsonText = evt.target.responseText;
                try {
                    var data = JSON.parse(jsonText);
                    qem.triggerHandler('completeUpload', [data]);
                } catch (e) {
                    layer.alert('上传失败，服务器出现了些状况');
                }
            }, false);
            var fd = new FormData();
            for (var key in options.bindData) {
                if (options.bindData[key] !== null) {
                    fd.append(key, options.bindData[key]);
                }
            }
            if (options.multiple) {
                for (var i = 0; i < field[0].files.length; i++) {
                    fd.append(options.fieldName + '[]', field[0].files[i]);
                }
            } else {
                fd.append(options.fieldName, field[0].files[0]);
            }
            xhr.open("POST", options.url);
            xhr.send(fd);
        }

        field.on('change', function () {
            upload();
            return false;
        });
        qem.on('upload', function () {
            field.trigger('click');
            return false;
        });
    }
})(jQuery, Yee, layer);


