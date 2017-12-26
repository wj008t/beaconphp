(function ($, Yee, layer) {
    var fromTimeout = true;

    Yee.extend('form', 'ajaxform', function (elem) {
        var qem = $(elem);
        var timer = null;
        //延迟100毫秒 让提交是最后的监听
        setTimeout(function () {
            qem.on('submit', function (ev) {
                var that = $(this);
                //防止误触
                if (!fromTimeout) {
                    return false;
                }
                fromTimeout = false;
                setTimeout(function () {
                    fromTimeout = true;
                }, 100);
                if (ev.result === false) {
                    return false;
                }
                if (that.data('confirm_prevent')) {
                    return false;
                }

                if (that.triggerHandler('before') === false) {
                    return;
                }
                var method = (that.attr('method') || 'GET').toUpperCase();

                var action = that.attr('action') || window.location.href;
                if (method == 'GET') {
                    var pathinfo = Yee.parseURL(action);
                    action = pathinfo.path;
                }
                var back = that.data('back') || '';
                if (back == '' && that.find(":input[name='__BACK__']").length > 0) {
                    back = that.find(":input[name='__BACK__']").val() || '';
                }
                var alert = that.data('alert') || false;
                var loading = that.data('loading') || false;
                var timeout = that.data('timeout') || 3000;//提交超时时间
                var sendData = that.serialize();
                var layerIndex = null;
                if (layer && loading) {
                    layerIndex = layer.load(1, {
                        shade: [0.1, '#FFF'] //0.1透明度的白色背景
                    });
                    if (timer) {
                        window.clearTimeout(timer);
                    }
                    timer = window.setTimeout(function () {
                        if (layer && layerIndex !== null) {
                            layer.close(layerIndex);
                        }
                        layerIndex = null;
                    }, timeout);
                }
                $.ajax({
                    type: method,
                    url: action,
                    data: sendData,
                    dataType: 'json',
                    success: function (ret) {
                        if (layer && loading) {
                            if (timer) {
                                window.clearTimeout(timer);
                            }
                            if (layerIndex !== null) {
                                layer.close(layerIndex);
                            }
                            layerIndex = null;
                        }
                        if (!ret) {
                            return;
                        }
                        if (that.triggerHandler('back', [ret]) === false) {
                            return;
                        }
                        //如果存在错误
                        if (ret.status === false) {
                            if (ret.formError && that.data('yee-validate-init')) {
                                that.showError(ret.formError);
                            }
                            else {
                                if (layer !== void 0) {
                                    if (alert) {
                                        layer.alert(ret.error, {icon: 7}, function (idx) {
                                            layer.close(idx);
                                        });

                                    } else {
                                        layer && layer.msg(ret.error, {icon: 0, time: 2000});
                                    }
                                }
                            }
                            if (that.triggerHandler('fail', [ret]) === false) {
                                return;
                            }
                        }
                        //提交成功
                        if (ret.status === true) {
                            if (layer && ret.message && typeof (ret.message) === 'string') {
                                layer.msg(ret.message, {icon: 1, time: 1000});
                            }
                            if (that.triggerHandler('success', [ret]) === false) {
                                return;
                            }
                            if (typeof (ret.jump) === 'undefined' && back != '') {
                                ret.jump = back;
                            }
                        }
                        //页面跳转
                        if (typeof (ret.jump) !== 'undefined' && ret.jump !== null) {
                            var goFunc = function () {
                                if (/^javascript:/i.test(ret.jump)) {
                                    var a = $('<a style="display: none"><span></span></a>').attr('href', ret.jump).appendTo(document.body);
                                    a.find('span').trigger('click');
                                    a.remove();
                                } else {
                                    window.location.href = ret.jump;
                                }
                            };
                            if (ret.status === true && ret.message) {
                                window.setTimeout(goFunc, 1000);
                            } else if (ret.status === false && ret.error) {
                                window.setTimeout(goFunc, 2000);
                            } else {
                                goFunc();
                            }
                        }
                    },
                    error: function (xhr) {
                        if (loading) {
                            if (timer) {
                                window.clearTimeout(timer);
                            }
                            if (layer && layerIndex !== null) {
                                layer.close(layerIndex);
                            }
                            layerIndex = null;
                        }
                    }
                });
                return false;
            });
        }, 50);
    });
})(jQuery, Yee, typeof(layer) == 'undefined' ? null : layer);