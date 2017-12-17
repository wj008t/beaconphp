(function ($, Yee, layer) {
    var FrameIndex = 0;

    function strToData(str) {
        if (str == "false") {
            return null;
        }
        var datastr = $.trim(str);
        if (datastr !== '') {
            try {
                var data = JSON.parse(datastr);
                return data;
            } catch (ex) {
                layer.alert('返回数据格式不符，请检查上传提交的页面 是否正确！');
                return null;
            }
        }
        return null;
    }

    //获取文件信息
    function getFileInfo(filebox) {
        if (typeof (FormData) !== 'undefined' && filebox.is('[multiple=multiple]')) {
            var datas = [];
            var files = filebox[0].files;
            for (var i = 0; i < files.length; i++) {
                var ofile = files[i];
                var path = ofile.name.toString();
                var filesize = ofile.size;
                var extension = path.lastIndexOf('.') === -1 ? '' : path.substr(path.lastIndexOf('.') + 1, path.length).toLowerCase();
                datas.push({filename: path, filesize: filesize, extension: extension});
            }
            return datas;

        } else if (/msie/.test(navigator.userAgent.toLowerCase())) {
            var path = filebox.val();
            var extension = path.lastIndexOf('.') === -1 ? '' : path.substr(path.lastIndexOf('.') + 1, path.length).toLowerCase();
            var obj_img = new Image();
            obj_img.dynsrc = filebox[0].value;
            var filesize = obj_img.fileSize;
            return [{filename: path, filesize: filesize, extension: extension}];
        }
        return [];
    }

    //创建上传表单
    function FrameUpload(qem, upbtn, keyname, upurl, extensions, bindData, multiple, callback) {
        FrameIndex++;
        var frame_name = 'yee_upload_frame_' + FrameIndex;
        var form = $('<form action="' + upurl + '" target="' + frame_name + '" method="post" enctype="multipart/form-data"></form>').appendTo(document.body);
        var iframe = $('<iframe name="' + frame_name + '" src="javascript:false;"  width="500" style="position:absolute; top:-550px; left:80px" height="200" id="' + frame_name + '" ></iframe>').appendTo(document.body);
        iframe.load(function () {
            form.trigger('reset');
            var str = this.contentWindow.document.body.innerHTML;
            if (str === 'false') {
                return;
            }
            var data = strToData(str);
            if (data) {
                callback(data);
            }
        });
        var file_layout = $('<div style="overflow:hidden;position:absolute;"></div>');
        file_layout.hide();
        file_layout.css({'opacity': 0, 'background-color': '#06F', 'zIndex': 1000000, 'cursor': 'pointer'});
        file_layout.appendTo(form);
        upbtn.on('mouseenter', function () {
            if (qem.is(':disabled')) {
                return;
            }
            var left = upbtn.offset().left;
            var top = upbtn.offset().top;
            var width = upbtn.outerWidth();
            var height = upbtn.outerHeight();
            file_layout.css({left: left + 'px', top: top + 'px', width: width + 'px', height: height + 'px'}).show();
        });
        file_layout.on('mouseleave', function () {
            $(this).hide();
        });
        //处理输入区域
        var file_area = $('<div></div>').css({
            'float': 'left',
            'margin-left': '-2px',
            'margin-top': '-2px'
        }).appendTo(file_layout);
        $('<input type="hidden" name="UPLOAD_IDENTIFIER"/>').appendTo(file_area);
        if (bindData) {
            for (var key in bindData) {
                $('<input type="hidden"/>').attr('name', key).val(bindData[key]).appendTo(file_area);
            }
        }
        //处理输入项
        var filebox = $('<input type="file" style="cursor:pointer"/>').appendTo(file_area);
        filebox.css({'font-size': '460px', 'margin': '0', 'padding': '0', 'border': '0', 'width': '100px'});
        filebox.attr('name', keyname);
        if (multiple) {
            filebox.attr('multiple', 'multiple');
        }
        //上传
        filebox.on('change', function () {
            var info = getFileInfo(filebox);
            var re = new RegExp("(^|\\s|,)" + info.extension + "($|\\s|,)", "ig");
            if (extensions !== '' && (re.exec(extensions) === null || info.extension === '')) {
                layer.alert('对不起，只能上传 ' + extensions + ' 类型的文件。');
                form.trigger('reset');
                return false;
            }
            var updo = qem.triggerHandler('beginUpfile', [info]);
            if (updo === false) {
                return;
            }
            form.trigger('submit');
        });
        this.reset = function () {
            if (form !== null) {
                form.trigger('reset');
            }
        };
        this.remove = function () {
            upbtn.off('mouseenter');
            if (filebox !== null) {
                filebox.remove();
                filebox = null;
            }
            if (file_area !== null) {
                file_area.remove();
                file_area = null;
            }
            if (file_layout !== null) {
                file_layout.remove();
                file_layout = null;
            }
            if (form !== null) {
                form.remove();
                form = null;
            }
            if (iframe !== null) {
                iframe.remove();
                iframe = null;
            }
        };
    }

    //使用HTML5上传
    function Html5Upload(qem, upbtn, keyname, upurl, extensions, bindData, multiple, callback) {
        if (typeof (FormData) === 'undefined') {
            layer.alert('浏览器不支持HTML5上传！');
            return;
        }
        var form = $('<form"></form>').hide().appendTo(document.body);
        var filebox = $('<input type="file" style="cursor:pointer"/>').appendTo(form);
        filebox.attr('name', keyname);
        if (multiple) {
            filebox.attr('multiple', 'multiple');
        }
        var Upload = function () {
            var infos = getFileInfo(filebox);
            var total = 0;
            for (var i = 0; i < infos.length; i++) {
                var info = infos[i];
                total += info.filesize;
                var re = new RegExp("(^|\\s|,)" + info.extension + "($|\\s|,)", "ig");
                if (extensions !== '' && (re.exec(extensions) === null || info.extension === '')) {
                    layer.alert('对不起，只能上传 ' + extensions + ' 类型的文件。');
                    form.trigger('reset');
                    return;
                }
            }

            var updo = qem.triggerHandler('beginUpfile', [infos]);
            if (updo === false) {
                return;
            }

            filebox.attr('name', keyname);
            var length = filebox[0].files.length;
            if (multiple) {
                filebox.attr('multiple', 'multiple');
            } else {
                length = 1;
                total = filebox[0].files[0].size;
            }

            var tice = 0;
            var loaded = 0;
            var contextAll = {status: true, list: []};

            var xhr = new XMLHttpRequest();

            xhr.upload.addEventListener("progress", function (evt) {
                qem.triggerHandler('progressUpfile', [{total: evt.total, loaded: loaded + evt.loaded}]);
            }, false);

            xhr.addEventListener("load", function (evt) {
                loaded += filebox[0].files[tice].size;
                tice++;
                var context = strToData(evt.target.responseText);
                if (context.status == false) {
                    layer.alert(context.error);
                    if (!contextAll.error || contextAll.error == '') {
                        contextAll.error = context.error;
                    }
                    contextAll.status = false;
                    return;
                }
                if (typeof(context.info) == 'undefined' && typeof(context.data) == 'object' && typeof(context.data.info) != 'undefined') {
                    context.info = context.data.info;
                }
                if (!contextAll.info) {
                    contextAll.info = context.info;
                }
                contextAll.list.push(context.info);
                if (tice == length) {
                    filebox.val('');
                    callback(contextAll);
                } else {
                    uploadOne(filebox[0].files[tice]);
                }
            }, false);

            var uploadOne = function (file) {
                var fd = new FormData();
                if (bindData) {
                    for (var key in bindData) {
                        fd.append(key, bindData[key]);
                    }
                }
                fd.append(keyname, file);
                xhr.open("POST", upurl);
                xhr.send(fd);
            };

            uploadOne(filebox[0].files[0]);

        };
        //上传
        filebox.on('change', function () {
            Upload();
            return false;
        });
        upbtn.on('click', function () {
            filebox.trigger('click');
            return false;
        });
        this.reset = function () {
            if (form !== null) {
                form.trigger('reset');
            }
        };
        this.remove = function () {
            upbtn.off('click');
            if (filebox !== null) {
                filebox.remove();
                filebox = null;
            }
            if (form !== null) {
                form.remove();
                form = null;
            }
        };
    }

    //大文件Html5上传
    function BigHtml5Upload(qem, upbtn, keyname, upurl, extensions, bindData, multiple, callback) {
        if (typeof (FormData) === 'undefined') {
            layer.alert('浏览器不支持HTML5上传！');
            return;
        }
        var form = $('<form"></form>').hide().appendTo(document.body);
        var filebox = $('<input type="file" style="cursor:pointer"/>').appendTo(form);
        filebox.attr('name', keyname);
        var bigUpfile = function () {
            var info = getFileInfo(filebox)[0];
            var re = new RegExp("(^|\\s|,)" + info.extension + "($|\\s|,)", "ig");
            if (extensions !== '' && (re.exec(extensions) === null || info.extension === '')) {
                layer.alert('对不起，只能上传 ' + extensions + ' 类型的文件。');
                form.trigger('reset');
                return;
            }
            var updo = qem.triggerHandler('beginUpfile', [info]);
            if (updo === false) {
                return;
            }
            var file = filebox[0].files[0];
            var xhr = new XMLHttpRequest();
            var blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice;
            var chunkSize = 2097152;//2M 拆分
            var total = file.size;
            var loaded = 0;
            var chunks = Math.ceil(total / chunkSize);//总片数
            var currentChunk = 0;
            var partname = '';
            var localname = file.name;
            var sendNext = function () {
                var form = new FormData();
                var start = currentChunk * chunkSize, end = start + chunkSize >= file.size ? file.size : start + chunkSize;
                var packet = blobSlice.call(file, start, end);
                form.append("bigup", "true");       //使用分片
                form.append("partname", partname);  //分片名称
                form.append("localname", localname);
                form.append("total", chunks);       //总片数
                form.append("index", currentChunk); //本次上传片数
                form.append(keyname, packet);
                xhr.open("POST", upurl);
                xhr.send(form);
            };
            xhr.upload.addEventListener("progress", function (evt) {
                qem.triggerHandler('progressUpfile', [{total: total, loaded: loaded + evt.loaded}]);
            }, false);
            xhr.addEventListener("load", function (evt) {
                var str = evt.target.responseText;
                var data = strToData(str);
                if (data) {
                    if (data.state === 'SUCCESS' || data.status) {
                        currentChunk++;
                        partname = data.msg.partname;
                        if (currentChunk < chunks) {
                            loaded += chunkSize;
                            sendNext();
                        } else {
                            loaded = total;
                            callback(data);
                        }
                    } else {
                        if (data.err) {
                            alert(data.err);
                        }
                    }
                }
            }, false);
            sendNext();
        };
        //上传
        filebox.on('change', function () {
            bigUpfile();
            return false;
        });
        upbtn.on('click', function () {
            filebox.trigger('click');
            return false;
        });
        this.reset = function () {
            if (form !== null) {
                form.trigger('reset');
            }
        };
        this.remove = function () {
            upbtn.off('click');
            if (filebox !== null) {
                filebox.remove();
                filebox = null;
            }
            if (form !== null) {
                form.remove();
                form = null;
            }
        };
    }

    function UpFile(element, options) {
        //console.log(options);
        options = $.extend({
            input: null,
            image: null,
            upbtn: null,
            multiple: false,
            extensions: '',
            mode: 'auto',
            keyname: 'filedata',
            upurl: '/service/upfile',
            strictSize: 0,
            imgWidth: 0,
            imgHeight: 0,
            btnText: '选择文件',
            bindData: {},
            clearBtn: 0
        }, options || {});

        var qem = $(element);
        //-----------------
        var bindbox = options.input ? $(options.input) : null; //绑定的输入框
        var bindimg = options.image ? $(options.image) : null; //绑定的图片
        var isinput = qem.is('input');

        var reval = isinput ? qem.val() : (bindbox ? bindbox.val() : null);
        if (qem.is('img') && bindimg == null) {
            bindimg = qem;
        }
        if (bindimg && reval) {
            if (options.showSize) {
                var retUrl = reval || '';
                retUrl = retUrl.replace(/(\.[a-z]+)$/, function ($0, $1) {
                    return '_' + options.showSize + $1;
                });
                //console.log(retUrl);
                bindimg.attr('src', retUrl);
            } else {
                bindimg.attr('src', reval);
            }
        }
        setTimeout(function () {
            qem.triggerHandler('initUpfile', [{oldval: reval}]);//触发上传初始化事件
        }, 10);

        var Uploader = null;
        //严格要求尺寸
        var bindData = options.bindData;

        if (options.strictSize) {
            if (options.imgWidth && options.imgHeight) {
                bindData.img_width = options.imgWidth;
                bindData.img_height = options.imgHeight;
                bindData.strict_size = options.strictSize;
            }
        }

        if (options.catSize) {
            bindData.cat_size = options.catSize;
            bindData.cat_type = options.catType || 0;
        }
        // console.log(bindData);

        if (options.clearBtn) {
            var emptybtn = $('<a class="yee-btn" href="javascript:;" style="margin-left:5px;">清除</a>').insertAfter(qem);
            emptybtn.on('click', function () {
                if (bindimg) {
                    bindimg.attr('src', '');
                }
                if (isinput) {
                    qem.val('');
                }
                if (bindbox) {
                    bindbox.val('');
                }
                qem.triggerHandler('clearUpfile');
            });
        }
        var upbtn = null;
        if (options.upbtn) {
            upbtn = $(options.upbtn);
            alert(upbtn.html());
        } else if (isinput) {
            upbtn = $('<a  href="javascript:;" class="yee-upfile-btn">' + options.btnText + '</a>').insertBefore(qem);
            if (qem.is(':visible')) {
                qem.addClass('not-radius-left');
                upbtn.addClass('not-radius-right');
            }
        } else {
            upbtn = qem;
        }

        var uploadComplete = function (context) {
            if (Uploader) {
                Uploader.reset();
            }
            if (context) {
                var r = qem.triggerHandler('afterUpfile', [context]);
                if (r === false) {
                    return;
                }
                if (context.status === false && context.error !== '') {
                    layer.alert(context.error);
                    return;
                }
                if (context.status) {
                    if (bindimg) {
                        if (options.showSize) {
                            var retUrl = context.info.url || '';
                            retUrl = retUrl.replace(/(\.[a-z]+)$/, function ($0, $1) {
                                return '_' + options.showSize + $1;
                            });
                            bindimg.attr('src', retUrl);
                        } else {
                            bindimg.attr('src', context.info.url);
                        }
                    }
                    if (isinput) {
                        qem.val(context.info.url);
                    }
                    if (bindbox) {
                        bindbox.val(context.info.url);
                    }
                }
            }
        };
        if (options.mode === 'auto') {
            if (typeof (FormData) === 'undefined') {
                options.mode = 'frame';
            } else {
                options.mode = 'html5';
            }
        }
        if (options.mode === 'bightml5') {
            Uploader = new BigHtml5Upload(qem, upbtn, options.keyname, options.upurl, options.extensions, bindData, options.multiple, uploadComplete);
        } else if (options.mode === 'html5') {
            Uploader = new Html5Upload(qem, upbtn, options.keyname, options.upurl, options.extensions, bindData, options.multiple, uploadComplete);
        } else {
            Uploader = new FrameUpload(qem, upbtn, options.keyname, options.upurl, options.extensions, bindData, options.multiple, uploadComplete);
        }
        this.destroy = function () {
            if (Uploader) {
                Uploader.remove();
            }
        };
    }

    Yee.extend('input,a,img', 'upfile', UpFile);
})(jQuery, Yee, layer);