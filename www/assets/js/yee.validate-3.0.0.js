//表单验证器
(function ($, Yee, layer) {

    //是否支持placeholder
    var SUPPORT_PLACEHOLDER = ('placeholder' in document.createElement('input'));
    if (!SUPPORT_PLACEHOLDER) {
        var jqVal = $.fn.val;
        $.fn.val = function (value) {
            if (value === void 0) {
                if (this.length > 0 && (this.is(':text[placeholder],textarea[placeholder]'))) {
                    var holder = this.attr('placeholder');
                    if (holder && jqVal.call(this) == holder) {
                        return '';
                    }
                }
                return jqVal.call(this);
            }
            return jqVal.call(this, value);
        };
        Yee.extend(':text[placeholder],textarea[placeholder]', 'placeholder', function (element) {
            var that = $(element);
            var holder = that.attr('placeholder');
            that.removeAttr('placeholder');
            if (holder && that.val() === '') {
                that.addClass('placeholder');
                that.val(holder);
                that.on('focus', {holder: holder}, function (ev) {
                    var that = $(this);
                    that.removeClass('placeholder');
                    if (that.val() === ev.data.holder) {
                        that.val('');
                    }
                });
                that.on('blur', {holder: holder}, function (ev) {
                    var that = $(this);
                    if (that.val() === '') {
                        that.addClass('placeholder');
                        that.val(ev.data.holder);
                    }
                });
            }
        });
    }
    //字符串格式化输出
    var stringFormat = function (str, args) {
        var args = args;
        if (str == '' || str == null || args == void 0) {
            return str;
        }
        if (!$.isArray(args)) {
            args = [args];
        }
        return str.replace(/\{(\d+)\}/ig, function ($0, $1) {
            var index = parseInt($1);
            return args.length > index ? args[index] : '';
        });
    };
    //获得随机数
    var randomString = function (len) {
        var len = len || 32;
        var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
        var maxPos = chars.length;
        var run = '';
        for (var i = 0; i < len; i++) {
            run += chars.charAt(Math.floor(Math.random() * maxPos));
        }
        return run;
    };
    //获得URL参数
    var getQueryString = function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r) {
            return unescape(r[2]);
        }
        return '';
    };

    var getBoxByName = function (elem) {
        if (!(elem.is(':radio') || elem.is(':checkbox'))) {
            return null;
        }
        var name = elem.attr('name');
        var form = null;
        if (elem.get(0).form) {
            form = $(elem.get(0).form);
        }
        else {
            form = elem.parents('form:first');
        }
        var ckbox = null;
        if (elem.is(':radio')) {
            ckbox = form.find(':radio[name="' + name + '"]');
        } else {
            ckbox = form.find(':checkbox[name="' + name + '"]');
        }
        if (ckbox.length == 0) {
            return null;
        }
        return ckbox;
    }

    function YeeValidate() {
        var Config = {
            rules: 'val'				    //验证器规则
            , val_msg: 'val-msg'		 		    //验证消息
            , val_events: 'val-events'                      //触发事件进行验证
            , val_off: 'val-off'			    //关闭验证
            , val_info: 'val-info'                    //默认描述
            , val_valid: 'val-valid'                     //正确描述
            , val_error: 'val-error'                    //服务器返回错误
            , val_for: 'val-for'			//显示消息控件属性值 = id
            //-CSS--------------------------
            , field_error: 'field-error'
            , field_valid: 'field-valid'
            , field_default: 'field-default'
            , input_error: 'input-error'
            , input_valid: 'input-valid'
            , input_default: 'input-default'
            //消息配置----------------------
            , message_required: '必选字段'
            , message_email: '请输入正确格式的电子邮件'
            , message_url: '请输入正确格式的网址'
            , message_date: '请输入正确格式的日期'
            , message_number: '仅可输入数字'
            , message_integer: '只能输入整数'
            , message_equalto: '请再次输入相同的值'
            , message_maxlength: '请输入一个 长度最多是 {0} 的字符串'
            , message_minlength: '请输入一个 长度最少是 {0} 的字符串'
            , message_rangelength: '请输入 一个长度介于 {0} 和 {1} 之间的字符串'
            , message_range: '请输入一个介于 {0} 和 {1} 之间的值'
            , message_max: '请输入一个小于 {0} 的值'
            , message_min: '请输入一个大于 {0} 的值'
            , message_remote: '检测数据不符合要求'
            , message_regex: '请输入正确格式字符'
            , message_mobile: '手机号码格式不正确'
            , message_idcard: '身份证号码格式不正确'
        };
        var displayMode = 0;
        var setConfig = function (cfg) {
            Config = $.extend(Config, cfg);
        };
        //显示队列
        var tempValFors = {};
        var remoteElems = [];
        var formSubmitState = false;
        //函数管理器
        var FuncManager = new (function () {
            var Funcs = {};
            var ShotName = {};
            this.getFunc = function (name) {
                return Funcs[name] || null;
            };
            this.getOirName = function (shotname) {
                if (ShotName[shotname])
                    return ShotName[shotname];
                return shotname;
            };

            this.getOirRules = function (rules) {
                var tempRules = {};
                for (var key in rules) {
                    var oir_key = this.getOirName(key);
                    if (oir_key === 'required') {
                        tempRules[oir_key] = rules[key];
                        break;
                    }
                }
                for (var key in rules) {
                    var oir_key = this.getOirName(key);
                    if (oir_key !== 'remote' && oir_key !== 'required') {
                        tempRules[oir_key] = rules[key];
                    }
                }
                for (var key in rules) {
                    var oir_key = this.getOirName(key);
                    if (oir_key === 'remote') {
                        tempRules[oir_key] = rules[key];
                        break;
                    }
                }
                return tempRules;
            }
            this.getOirMessages = function (rules, messages) {
                var tempMessages = {};
                for (var key in messages) {
                    var oir_key = this.getOirName(key);
                    tempMessages[oir_key] = messages[key];
                }
                for (var key in rules) {
                    var oir_key = this.getOirName(key);
                    if (!tempMessages[oir_key] && Config['message_' + oir_key]) {
                        tempMessages[oir_key] = Config['message_' + oir_key];
                    }
                }
                return tempMessages;
            }

            var regFunc = this.regFunc = function (name, fn, defmsg) {
                if (typeof (fn) === 'function') {
                    Funcs[name] = fn;
                    if (typeof (defmsg) !== 'undefined') {
                        Config['message_' + name] = defmsg;
                    }
                } else if (typeof (fn) === 'string') {
                    ShotName[name] = fn;
                }
            };
            regFunc('required', function (val, bwo) {
                if (this.is(':radio') || this.is(':checkbox')) {
                    var boxs = getBoxByName(this);
                    if (boxs == null) {
                        return false;
                    }
                    if (boxs.filter(':checked').length == 0) {
                        return false;
                    }
                    return true;
                }
                if (val === null) {
                    return false;
                }
                if (bwo === 1) {
                    val = val.replace(/\s+/, '');
                }
                if (bwo === 2) {
                    val = val.replace(/<[^>]+>/, '');
                    val = val.replace(/\s+/, '');
                }
                return !(val === null || val === '' || val.length === 0);
            });
            regFunc('email', function (val) {
                return /^([a-zA-Z0-9]+[-|_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[-|_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,8}([\.][a-zA-Z]{2,8})?$/.test(val);
            });
            regFunc('number', function (val) {
                return /^[\-\+]?((\d+(\.\d*)?)|(\.\d+))$/.test(val);
            });
            regFunc('integer', function (val) {
                return /^[\-\+]?\d+$/.test(val);
            });
            regFunc('max', function (val, num, noeq) {
                if (noeq === true)
                    return val < Number(num);
                else
                    return val <= Number(num);
            });
            regFunc('min', function (val, num, noeq) {
                if (noeq === true)
                    return val > Number(num);
                else
                    return val >= Number(num);
            });
            regFunc('range', function (val, num1, num2, noeq) {
                if (noeq === false) {
                    return val > Number(num1) && val < Number(num2);
                } else {
                    return val >= Number(num1) && val <= Number(num2);
                }
            });
            regFunc("minlength", function (val, len) {
                return val.length >= len;
            });
            regFunc("maxlength", function (val, len) {
                return val.length <= len;
            });
            regFunc("rangelength", function (val, len1, len2) {
                return val.length >= len1 && val.length <= len2;
            });

            regFunc("minsize", function (val, len) {
                var boxs = getBoxByName(this);
                var length = 0;
                if (boxs !== null) {
                    length = boxs.filter(':checked').length;
                }
                return length >= len;
            });

            regFunc("maxsize", function (val, len) {
                var boxs = getBoxByName(this);
                var length = 0;
                if (boxs !== null) {
                    length = boxs.filter(':checked').length;
                }
                return length <= len;
            });

            regFunc("rangesize", function (val, len1, len2) {
                var boxs = getBoxByName(this);
                var length = 0;
                if (boxs !== null) {
                    length = boxs.filter(':checked').length;
                }
                return length >= len1 && length <= len2;
            });

            regFunc('money', function (val) {
                return /^[-]{0,1}\d+[\.]\d{1,2}$/.test(val) || /^[-]{0,1}\d+$/.test(val);
            }, '仅可输入带有2位小数以内的数字及整数');
            regFunc("date", function (val) {
                return /^\d{4}-\d{1,2}-\d{1,2}(\s\d{1,2}(:\d{1,2}(:\d{1,2})?)?)?$/.test(val);
            });
            regFunc("url", function (val, jh) {
                if (jh && val == '#') {
                    return true;
                }
                return /^(http|https|ftp):\/\/\w+\.\w+/i.test(val);
            });
            regFunc("equal", function (val, str) {
                return val === str;
            });
            regFunc("notequal", function (val, str) {
                return val !== str;
            });
            regFunc("equalto", function (val, str) {
                return val === $(str).val();
            });
            regFunc("mobile", function (val) {
                return /^1[34578]\d{9}$/.test(val);
            });
            regFunc("idcard", function (val) {
                return /^[1-9]\d{5}(19|20)\d{2}(((0[13578]|1[02])([0-2]\d|30|31))|((0[469]|11)([0-2]\d|30))|(02[0-2][0-9]))\d{3}(\d|X|x)$/.test(val);
            });
            regFunc("user", function (val, num1, num2) {
                var r = /^[a-zA-Z]\w+$/.test(val);
                if (num1 !== undefined && isNaN(parseInt(num1)))
                    r = r && val.length >= parseInt(num1);
                if (num2 !== undefined && isNaN(parseInt(num2)))
                    r = r && val.length <= parseInt(num2);
                return r;
            }, '请使用英文之母开头的字母下划线数字组合');
            regFunc("regex", function (val, str) {
                var re = new RegExp(str).exec(val);
                return (re && (re.index === 0) && (re[0].length === val.length));
            });
            regFunc('num', 'number');
            regFunc('r', 'required');
            regFunc('int', 'integer');
            regFunc('digits', 'integer');
            regFunc('minlen', 'minlength');
            regFunc('maxlen', 'maxlength');
            regFunc('rangelen', 'rangelength');
            regFunc('eqto', 'equalto');
            regFunc('eq', 'equal');
            regFunc('neq', 'notequal');
        })();
        //获得控件标签
        var getTipLabel = function (elem) {
            var label = null;
            var id = elem.attr('id') || null;
            var forId = elem.data('val-for') || null;
            if (!forId) {
                if (id === '' || id === null) {
                    forId = null;
                } else {
                    forId = '#' + id + '_info';
                    elem.data('val-for', forId);
                }
            }
            if (forId) {
                label = $(forId.replace(/(:|\.)/g, '\\$1'));
                if (label.length == 0) {
                    id = forId.substr(1);
                    label = $('<span id="' + id + '"></span>').appendTo(elem.parent());
                }
            } else {
                id = randomString(20);
                label = $('<span id="temp_info_' + id + '"></span>').appendTo(elem.parent());
                elem.data('val-for', '#temp_info_' + id);
            }
            return label;
        };
        //呈现形式 默认
        var displayDefault = function (elem, msg) {
            if (elem.triggerHandler('displayDefault', [msg]) === false || elem.parents('form').triggerHandler('displayDefault', [elem, msg]) === false) {
                return;
            }
            var ckBoxs = getBoxByName(elem);
            if (ckBoxs) {
                $(ckBoxs).each(function () {
                    $(this).removeClass(Config.input_error + ' ' + Config.input_valid).addClass(Config.input_default);
                });
            } else {
                elem.removeClass(Config.input_error + ' ' + Config.input_valid).addClass(Config.input_default);
            }
            var label = getTipLabel(elem);
            label.removeClass(Config.field_error + ' ' + Config.field_valid).addClass(Config.field_default);
            label.html(msg);
            if (!msg) {
                label.hide();
            }
        };
        //正确形式
        var displayValid = function (elem, msg) {
            if (elem.triggerHandler('displayValid', [msg]) === false || elem.parents('form').triggerHandler('displayValid', [elem, msg]) === false) {
                return;
            }
            var ckBoxs = getBoxByName(elem);
            if (ckBoxs) {
                $(ckBoxs).each(function () {
                    $(this).removeClass(Config.input_error + ' ' + Config.input_default).addClass(Config.input_valid);
                });
            } else {
                elem.removeClass(Config.input_error + ' ' + Config.input_default).addClass(Config.input_valid);
            }
            if (msg) {
                var label = getTipLabel(elem);
                label.show();
                label.removeClass(Config.field_error + ' ' + Config.field_default).addClass(Config.field_valid);
                label.html(msg);
            } else {
                displayDefault(elem, msg);
            }
        };
        //错误形式
        var displayError = function (elem, msg) {
            if (elem.triggerHandler('displayError', [msg]) === false || elem.parents('form').triggerHandler('displayError', [elem, msg]) === false) {
                return;
            }
            var ckBoxs = getBoxByName(elem);
            if (ckBoxs) {
                $(ckBoxs).each(function () {
                    $(this).removeClass(Config.input_valid + ' ' + Config.input_default).addClass(Config.input_error);
                });
            } else {
                elem.removeClass(Config.input_valid + ' ' + Config.input_default).addClass(Config.input_error);
            }
            if (msg) {
                if (displayMode == 1 || displayMode == 2) {
                    return;
                }
                var label = getTipLabel(elem);
                label.removeClass(Config.field_valid + ' ' + Config.field_default).addClass(Config.field_error);
                label.show();
                label.html(msg);
            }
        };
        //验证远程
        var ajaxRemote = function (elem, data, fn) {
            var elemcache = elem.data('yee-ajax-cache');
            if (typeof (elemcache) === 'object') {
                if (elem.val() === elemcache.val) {
                    data.pass = elemcache.pass;
                    data.errType = 'remote';
                    data.remoteError = elemcache.remoteError;
                    data.remoteValid = elemcache.remoteValid;
                    fn(data);
                    return;
                }
            }
            var val = data.rules.remote;
            var name = elem.attr('name');
            if (name === undefined || name === '') {
                return;
            }
            var url = '';
            var type = 'post';
            var otvals = '';//而外要附加的字段ID
            if ($.isArray(val)) {
                url = val[0] || '';
                type = val[1] || 'post';
                otvals = val[2] || '';
            }
            else if (typeof val == 'string') {
                url = val;
            } else {
                data.pass = true;
                data.errType = 'remote';
                data.remoteError = '';
                data.remoteValid = '';
                fn(data);
                return;
            }
            var opt = {};
            var value = elem.val();
            opt[name] = value;
            var form = $(elem).parents('form:first');
            if (otvals !== '') {
                var arrtemp = otvals.split(',');
                for (var i in arrtemp) {
                    var xname = arrtemp[i];
                    var sle = ':input[name=' + xname + ']';
                    var _elem = form.find(sle);
                    if (_elem.length > 0) {
                        opt[xname] = _elem.val();
                    } else {
                        var val = getQueryString(xname);
                        if (val !== '') {
                            opt[xname] = val;
                        }
                    }
                }
            }
            $.ajax({
                url: url, data: opt, type: type.toUpperCase(), dataType: 'json', success: function (ret) {
                    if (typeof (ret) == 'boolean') {
                        if (ret === true) {
                            data.pass = true;
                        } else {
                            data.pass = false;
                            data.errType = 'remote';
                        }
                        elem.data('yee-ajax-cache', {val: value, pass: data.pass});
                        fn(data);
                    } else {
                        if (ret.status === true) {
                            data.pass = true;
                            data.remoteValid = ret.message || null;
                        } else {
                            data.remoteError = ret.error;
                            data.pass = false;
                            data.errType = 'remote';
                        }
                        elem.data('yee-ajax-cache', {
                            val: value,
                            pass: data.pass,
                            remoteError: (ret.error || null),
                            remoteValid: (ret.message || null)
                        });
                        fn(data);
                    }
                }
            });
        };
        //获取元素数据
        var getFieldData = function (elem) {
            var rules = elem.data(Config.rules) || null;
            if (rules === null) {
                return null;
            }
            var val_msg = elem.data(Config.val_msg) || {};
            var val_off = elem.data(Config.val_off) || '';
            val_off = (val_off === 'true' || val_off === true || val_off == '1' || val_off === 'on');
            var data = {
                rules: FuncManager.getOirRules(rules),
                valMessages: FuncManager.getOirMessages(rules, val_msg),
                valDefault: elem.data(Config.val_info) || '',
                valValid: elem.data(Config.val_valid) || '',
                valError: elem.data(Config.val_error) || '',
                valEvents: elem.data(Config.val_events) || '',
                valOff: val_off,
                remote: !!rules.remote,
                errType: null,
                pass: true
            };
            return data;
        };

        var setError = function (elem, message, force) {
            force = typeof (force) === 'undefined' ? true : force;
            if (force) {
                displayError(elem, message);
                return;
            }
            if (message !== false) {
                var forid = elem.data(Config.val_for) || '';
                if (forid !== '') {
                    if (typeof (tempValFors[forid]) == 'undefined') {
                        tempValFors[forid] = message;
                    } else {
                        message = tempValFors[forid];
                    }
                }
            }
            displayError(elem, message);
        };

        var setValid = function (elem, message, force) {
            force = typeof (force) === 'undefined' ? true : force;
            if (force) {
                displayValid(elem, message);
                return;
            }
            if (message !== false) {
                var forid = elem.data(Config.val_for) || '';
                if (forid !== '') {
                    if (typeof (tempValFors[forid]) != 'undefined') {
                        return;
                    }
                }
            }
            displayValid(elem, message);
        };

        var setDefault = function (elem, message) {
            displayDefault(elem, message);
        };

        var mouseDownEvent = function () {
            var elem = $(this);
            var ckBoxs = getBoxByName(elem);
            if (ckBoxs) {
                elem = ckBoxs.filter(function () {
                    return $(this).data('val') != void 0;
                });
            }
            var data = getFieldData(elem);
            if (!data) {
                setDefault(elem);
                return;
            }
            setDefault(elem, data.valDefault);
        };

        var checkEvent = function () {
            var elem = $(this);
            var data = getFieldData(elem);
            if (!data || data.valOff) {
                return;
            }
            data = checkElem(elem, data);
            if (!data.pass) {
                var msg = data.valMessages[data.errType] || '';
                msg = stringFormat(msg, data.rules[data.errType]);
                if (!(elem.data('yee-remote-display') === true && !formSubmitState)) {
                    setError(elem, msg, true);
                }
                return;
            }
            if (data.remote) {
                ajaxRemote(elem, data, function (tdata) {
                    if (!tdata.pass) {
                        var msg = tdata.valMessages[tdata.errType] || '';
                        if (tdata.remoteError) {
                            msg = tdata.remoteError;
                        }
                        msg = stringFormat(msg, tdata.rules[tdata.errType]);
                        setError(elem, msg, true);
                    } else {
                        setValid(elem, tdata.remoteValid || tdata.valValid, true);
                    }
                });
                return;
            }
            setValid(elem, data.valValid);
        };

        var checkElem = function (elem, data) {
            elem.off('mousedown', mouseDownEvent);
            elem.on('mousedown', mouseDownEvent);
            var val = elem.val();
            var rules = data.rules;
            var type = (elem.attr('type') || elem[0].type || 'text').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                var boxs = getBoxByName(elem);
                if (boxs == null) {
                    val = '';
                } else {
                    var vitem = [];
                    boxs.filter(':checked').each(function (idx, em) {
                        vitem.push($(em).val());
                    });
                    val = vitem.join(',');
                }
            }
            for (var key in rules) {
                if (key === 'remote') {
                    if (formSubmitState) {
                        remoteElems.push(elem);
                        var rmdata = elem.data('yee-ajax-cache');
                        if (rmdata && !rmdata.pass) {
                            data.errType = key;
                            data.pass = false;
                            if (rmdata.remoteError) {
                                data.valMessages.remote = rmdata.remoteError;
                            }
                        }
                    }
                    continue;
                }
                var func = FuncManager.getFunc(key);
                if (!func || typeof (func) !== 'function') {
                    continue;
                }
                //验证非空====
                if ((key === 'required') && rules[key] !== false && !func.call(elem, val, rules[key])) {
                    data.errType = key;
                    data.pass = false;
                }

                if (!rules.force) {
                    if (val === '') {
                        return data;
                    }
                }
                var args = rules[key];
                if (!$.isArray(args)) {
                    args = [args];
                }
                args = args.slice(0);
                args.unshift(val);
                if (!func.apply(elem, args)) {
                    data.errType = key;
                    data.pass = false;
                    return data;
                }
            }
            return data;
        };

        var initElem = function (elem) {
            if (elem.data('yee-validate-init')) {
                return;
            }
            elem.data('yee-validate-init', true);
            var data = getFieldData(elem);
            if (data && data.valDefault) {
                setDefault(elem, data.valDefault);
            }
            //显示来自服务器的错误数据

            var ckBoxs = getBoxByName(elem);
            if (ckBoxs) {
                $(ckBoxs).each(function () {
                    $(this).off('change', mouseDownEvent).on('change', mouseDownEvent);
                });
            } else {
                elem.off('mousedown', mouseDownEvent);
                elem.on('mousedown', mouseDownEvent);
            }
            if (elem.data(Config.val_error)) {
                var msg = elem.data(Config.val_error);
                setError(elem, msg, true);
                elem.removeData(Config.val_error);
            }
            if (!data || data.valOff) {
                return;
            }
            if (data.valEvents) {
                elem.on(data.valEvents, checkEvent);
            }
            if (data.remote && !/blur/.test(data.valEvents)) {
                elem.data('yee-remote-display', true);
                if (typeof data.remote != 'boolean') {
                    elem.on('blur', checkEvent);
                }
            }
        };

        var getFields = function (form) {
            var inputs = [];
            for (var i = 0; i < form.elements.length; i++) {
                inputs.push(form.elements.item(i));
            }
            inputs = $(inputs).filter(':input[type!=submit][type!=reset][type!=button]').not(':disabled');
            return inputs;
        };

        var initValidator = function (form) {
            formSubmitState = false;
            var qform = $(form);
            displayMode = qform.data('display-mode') || 0;
            qform.data('yee-validate-init', true);
            var init = function () {
                qform.on('update', function () {
                    var inputs = getFields(qform[0]);
                    if (!SUPPORT_PLACEHOLDER) {
                        inputs.yee_placeholder();
                    }
                    inputs.each(function (index, element) {
                        var elem = $(element);
                        initElem(elem);
                    });
                }).triggerHandler('update');
            };
            var initJson = function (data) {
                for (var sel in data) {
                    var box = $(sel);
                    if (box.length > 0) {
                        for (var key in  data[sel]) {
                            box.data(key, data[sel][key]);
                        }
                    }
                }
                init();
            };
            if (qform.data('json')) {
                var url = qform.data('json');
                if (typeof (url) == 'string') {
                    var url = url + '.json';
                    $.getJSON(url, initJson);
                } else {
                    initJson(url);
                }
            } else {
                init();
            }
        };

        var displayAllError = function (errItems) {
            if (displayMode == 1 && layer) {
                var errors = [];
                $(errItems).each(function () {
                    errors.push('* ' + this.msg);
                });
                layer.alert(errors.join('<br/>'), {
                    title: '错误提示',
                    icon: 7,
                    anim: 5
                });
            }
            if (displayMode == 2 && layer) {
                var error = '';
                $(errItems).each(function () {
                    error = this.msg;
                    return false;
                });
                layer.alert(error, {
                    title: '错误提示',
                    icon: 7,
                    anim: 5
                });
            }
            $(errItems).each(function () {
                setError(this.elem, this.msg, false);
            });
        }

        var checkFormNoRemote = function (form) {
            tempValFors = {};
            formSubmitState = true;
            remoteElems = [];
            var allPass = true;
            var errItems = [];
            var inputs = getFields(form);
            inputs.each(function (index, element) {
                var elem = $(element);
                var data = getFieldData(elem);
                if (!data || data.valOff) {
                    return;
                }
                data = checkElem(elem, data);
                //接管处理请求
                var Result = elem.triggerHandler('timely', [data]);
                if (!data.pass) {
                    var msg = data.valMessages[data.errType] || '';
                    msg = stringFormat(msg, data.rules[data.errType]);
                    errItems.push({elem: elem, msg: msg, data: data});
                } else {
                    setValid(elem, data.valValid, false);
                }
                allPass = allPass && data.pass;
                if (Result === false) {
                    if (!allPass) {
                        return false;
                    }
                }
            });
            if (allPass === false) {
                if ($(form).triggerHandler('displayAllError', [errItems]) !== false) {
                    displayAllError(errItems);
                }
                setTimeout(function () {
                    try {
                        if (errItems[0].elem.is(':hidden')) {
                            errItems[0].elem.triggerHandler('focus');
                        } else {
                            errItems[0].elem.trigger('focus');
                        }
                    } catch (e) {
                        errItems[0].elem.triggerHandler('focus');
                    }
                }, 10);
            }
            //如果全部通过
            if (allPass && !SUPPORT_PLACEHOLDER) {
                var holderInputs = inputs.filter(':text[placeholder],textarea[placeholder]');
                holderInputs.each(function (index, element) {
                    var that = $(element);
                    if (that.val() == '') {
                        that.val('');
                    }
                });
            }
            return allPass;
        };
        //检查表单数据==
        var checkForm = function (form) {
            var qform = $(form);
            form = qform[0];
            var beforeValid = qform.triggerHandler('beforeValid');
            if (beforeValid === false) {
                return false;
            }
            var allPass = checkFormNoRemote(form);
            var i = 0;
            var nextAjax = function () {
                if (i >= remoteElems.length) {
                    return;
                }
                var xcelem = remoteElems[i];
                var xcdata = getFieldData(xcelem);
                i++;
                ajaxRemote(xcelem, xcdata, function (tdata) {
                    if (!tdata.pass) {
                        var msg = tdata.valMessages[tdata.errType] || '';
                        if (tdata.remoteError) {
                            msg = tdata.remoteError;
                        }
                        msg = stringFormat(msg, tdata.rules[tdata.errType]);
                        setError(xcelem, msg, true);
                    } else {
                        setValid(xcelem, tdata.remoteValid || xcdata.valValid, true);
                    }
                    nextAjax();
                });
            };
            if (!allPass) {
                nextAjax();
            }
            formSubmitState = false;
            if (!allPass) {
                return false;
            }
            var afterValid = qform.triggerHandler('afterValid');
            if (afterValid === false) {
                return false;
            }
            return allPass;
        };

        return {
            checkForm: checkForm,
            initValidator: initValidator,
            regFunc: FuncManager.regFunc,
            getFunc: FuncManager.getFunc,
            setError: function (selector, msg, force) {
                $(selector).each(function () {
                    var elem = $(this);
                    if (!elem.is(':input')) {
                        return;
                    }
                    setError(elem, msg, force);
                    if (elem.is(':radio') || elem.is(':checkbox')) {
                        var name = elem.attr('name');
                        var form = null;
                        if (elem.get(0).form) {
                            form = $(elem.get(0).form);
                        }
                        else {
                            form = elem.parents('form:first');
                        }
                        var ckbox = null;
                        if (elem.is(':radio')) {
                            ckbox = form.find(':radio[name="' + name + '"]');
                        } else {
                            ckbox = form.find(':checkbox[name="' + name + '"]');
                        }
                        if (ckbox && ckbox.length > 0) {
                            $(ckbox).each(function () {
                                $(this).off('change', mouseDownEvent).on('change', mouseDownEvent);
                            });
                        }
                    } else {
                        elem.off('mousedown', mouseDownEvent);
                        elem.on('mousedown', mouseDownEvent);
                    }
                });
            },
            setValid: function (selector, msg, force) {
                $(selector).each(function () {
                    var elem = $(this);
                    if (!elem.is(':input')) {
                        return;
                    }
                    setValid(elem, msg, force);
                });
            },
            setDefault: function (selector, msg) {
                $(selector).each(function () {
                    var elem = $(this);
                    if (!elem.is(':input')) {
                        return;
                    }
                    setDefault(elem, msg);
                });
            },
            getFieldData: getFieldData,
            initElem: function (selector) {
                $(selector).each(function () {
                    var elem = $(this);
                    if (!elem.is(':input')) {
                        return;
                    }
                    initElem(elem);
                });
            },
            displayAllError: displayAllError,
            setConfig: setConfig
        };
    }

    var YeeValidator = $.YeeValidator = window.YeeValidator = YeeValidate();
    $.fn.extend({
        checkForm: function () {
            return YeeValidator.checkForm(this[0]);
        },
        initElem: function () {
            YeeValidator.initElem(this);
            return this;
        },
        setError: function (msg, force) {
            YeeValidator.setError(this, msg, force);
            return this;
        },
        setValid: function (msg, force) {
            YeeValidator.setValid(this, msg, force);
            return this;
        },
        setDefault: function (msg) {
            YeeValidator.setDefault(this, msg);
            return this;
        },
        showError: function (formError) {
            var that = $(this[0]);
            if (!that.is('form')) {
                return;
            }
            var first = null;
            var errItems = [];
            var error = null;
            for (var name in formError) {
                error = error === null ? formError[name] : error;
                var elem = that.find(":input[name='" + name + "']");
                if (elem.length == 0) {
                    elem = that.find(":input[id='" + name + "']");
                }
                if (elem.length > 0) {
                    if (!first) {
                        first = elem;
                    }
                    var msg = formError[name];
                    errItems.push({elem: elem, msg: msg});
                }
            }
            if (errItems.length == 0) {
                if (error === null) {
                    error = '错误,未知原因';
                }
                layer.alert(error, {
                    title: '错误提示',
                    icon: 7,
                    anim: 6
                });
                return;
            }
            if (that.triggerHandler('displayAllError', [errItems, true]) !== false) {
                YeeValidator.displayAllError(errItems);
            }
            if (first) {
                if (first.is(':checkbox') || first.is(':radio')) {
                    return this;
                }
                setTimeout(function () {
                    try {
                        if (first.is(':hidden')) {
                            first.triggerHandler('focus');
                        } else {
                            first.trigger('focus');
                        }
                    } catch (e) {
                        first.triggerHandler('focus');
                    }
                }, 10);
            }
            return this;
        }
    });
    Yee.extend('form', 'validate', function (element) {
        if (element.nodeName.toLowerCase() !== 'form') {
            return;
        }
        YeeValidator.initValidator(element);
        $(element).on('submit', function (ev) {
            try {
                return YeeValidator.checkForm(this);
            } catch (e) {
                return false;
            }
        });
    });
})(jQuery, Yee, layer);