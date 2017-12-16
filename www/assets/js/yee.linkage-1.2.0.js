(function ($, Yee) {
    var tempdata = {};

    var old_value = $.fn.val;
    $.fn.val = function () {
        if (arguments.length === 0) {
            return old_value.apply(this, arguments);
        }
        var rt = old_value.apply(this, arguments);
        if (this.get(0).yee_modules && this.get(0).yee_modules.yee_linkage) {
            this.get(0).yee_modules.yee_linkage.update();
        }
        return rt;
    }

    function Linkage(element, options) {
        options = $.extend({
            source: null,
            method: 'get',
            level: 0,
            datatype: 'array',
            valGroup: null
        }, options || {});
        options.level = /\d+/.test(options.level) ? parseInt(options.level) : 0;
        var qem = $(element);
        var name = qem.attr('name') || null;
        var strval = qem.val() || '';
        var values = /^\[.*\]$/.test(strval) ? JSON.parse(strval) : null;
        if (values !== null) {
            for (var i in values) {
                values[i] = values[i] == 0 ? "" : values[i];
            }
        }
        var source = options.source;
        var method = options.method.toLocaleLowerCase();
        var level = options.level;
        var datatype = options.datatype;
        var val_group = options.valGroup;
        var firstbox = null;
        var pingding = false;//进行中
        //更新值
        var updateValue = function () {
            var vlas = [];
            var box = firstbox;
            var empty = true;
            while (box) {
                var val = $(box).val();
                if (val == "") {
                    break;
                }
                vlas.push(val);
                empty = false;
                box = box.nextBox;
            }
            if (empty) {
                old_value.call(qem, "");
            } else {
                old_value.call(qem, JSON.stringify(vlas));
            }
        };
        //拷贝属性
        var copyAttr = function (box, lev) {
            $.each(['class', 'style', 'readonly', 'disabled', 'size'], function (i, key) {
                var attrval = qem.attr(key);
                if (attrval) {
                    if (key == "class") {
                        attrval = attrval.replace('input-error', '');
                    }
                    box.attr(key, attrval);
                }
            });
            $.each(['header', 'val', 'val-msg', 'val-info', 'val-valid'], function (i, key) {
                var data_name = key + lev.toString();
                var data_val = qem.data(data_name) || null;
                if ((key == 'val' || key == 'val-msg') && val_group && val_group.rule) {
                    var idx = lev - 1;
                    if (val_group.rule[idx] && key == 'val') {
                        box.data('val', val_group.rule[idx]);
                        return;
                    }
                    if (val_group.msg[idx] && key == 'val-msg') {
                        box.data('val-msg', val_group.msg[idx]);
                        return;
                    }
                }
                if (data_val !== null) {
                    box.data(key, data_val);
                } else {
                    data_name = key + 's';
                    data_val = qem.data(data_name) || null;
                    if (data_val && typeof (data_val[lev - 1]) !== 'undefiend') {
                        box.data(key, data_val[lev - 1]);
                    } else {
                        data_val = qem.data(key) || null;
                        if (data_val) {
                            box.data(key, data_val);
                        }
                    }
                }
            });
            if (name) {
                var boxname = qem.data('name' + lev) || null;
                if (boxname) {
                    box.attr('name', boxname);
                } else {
                    box.attr('name', name);
                    box.data('name-group', name);
                }
            }
            if (qem.data('val-for')) {
                box.data('val-for', qem.data('val-for'));
            }
            box.show();
        };


        var onchange = function (vals) {
            var box = $(this);
            var selected = box.children(':selected');
            var childs = (selected.length > 0 && selected[0].childsData) ? selected[0].childsData : null;
            //创建后面一个数据
            createBox(box[0], childs, vals);
        };

        var removeBox = function (box) {
            if (!box) {
                return;
            }
            if (box.nextBox) {
                removeBox(box.nextBox);
            }
            $(box).triggerHandler('mousedown');
            $(box).empty().removeData('val').removeAttr('name').hide();
        };
        var initBox = function (box, lev, items, vals) {
            if (level !== 0 && lev > level || !items) {
                return;
            }
            var index = lev - 1;
            vals = vals || [];
            box[0].length = 0;
            var header = box.data('header') || '';
            if (header) {
                if ($.isArray(header) && header.length >= 2) {
                    box[0].add(new Option(header[1], header[0]));
                } else {
                    box[0].add(new Option(header, ''));
                }
            }
            box.on('click', function () {
                if (typeof(box.setDefault) == 'function')
                    box.setDefault();
            });
            box.data('length', items.length);
            var defval = vals[index] || '';
            if (items !== null) {
                for (var i = 0; i < items.length; i++) {
                    var obj = {};
                    // console.log(typeof (items[i]));
                    if (typeof (items[i]) === 'number' || typeof (items[i]) === 'string') {
                        obj.value = items[i];
                        obj.text = items[i];
                        obj.childs = [];
                    } else {
                        if (typeof (items[i].value) !== 'undefined') {
                            obj.value = items[i].value;
                        } else if (typeof (items[i].v) !== 'undefined') {
                            obj.value = items[i].v;
                        } else if (typeof (items[i][0]) !== 'undefined') {
                            obj.value = items[i][0];
                        } else {
                            continue;
                        }
                        if (typeof (items[i].text) !== 'undefined') {
                            obj.text = items[i].text;
                        } else if (typeof (items[i].t) !== 'undefined') {
                            // console.log(items[i]);
                            obj.text = items[i].t;
                        } else if (typeof (items[i][1]) !== 'undefined') {
                            // console.log(items[i]);
                            obj.text = items[i][1];
                        } else {
                            obj.text = obj.value;
                        }
                        if (typeof (items[i].childs) !== 'undefined') {
                            obj.childs = items[i].childs;
                        } else if (typeof (items[i].c) !== 'undefined') {
                            obj.childs = items[i].c;
                        } else if (typeof (items[i][2]) !== 'undefined') {
                            obj.childs = items[i][2];
                        } else {
                            obj.childs = [];
                        }
                    }
                    if (box[0].length == 1 && (obj.value === null || obj.value === '')) {
                        box[0].length = 0;
                        obj.value = '';
                    }
                    var optitem = new Option(obj.text, obj.value);
                    box[0].add(optitem);
                    //console.log(defval, obj.value);
                    if (defval == obj.value) {
                        optitem.selected = true;
                    }
                    optitem.childsData = obj.childs;
                }
            }
            box.on('change', function () {
                onchange.call(this);
                updateValue();
            });
            onchange.call(box[0], vals);
        };

        var createBox = function (emum, items, vals) {
            pingding = true;
            if (!items) {
                pingding = false;
                return;
            }
            if ($.type(items) === 'string') {
                if (!items) {
                    pingding = false;
                    return;
                }
                if (tempdata[items]) {
                    createBox(emum, tempdata[items], vals);
                    return;
                }
                $[method](items, function (ret) {
                    if (ret.status === true && ret.data) {
                        tempdata[items] = ret.data;
                        createBox(emum, ret.data, vals);
                    } else {
                        alert('无法加载远程数据！');
                    }
                }, 'json');
                return;
            }
            var box = $(emum);
            if (emum.nextBox) {
                removeBox(emum.nextBox);
            }
            if (box.is('select')) {
                var lev = box.data('temp-lev') + 1;
                if ((level === 0 && items.length > 0) || level >= lev) {
                    var nextQbox = emum.nextBox ? $(emum.nextBox).show() : $('<select>').insertAfter(box);
                    copyAttr(nextQbox, lev);
                    nextQbox.data('temp-lev', lev);
                    emum.nextBox = nextQbox[0];
                    initBox(nextQbox, lev, items, vals);
                }
            } else {
                var fbox = $('<select>').insertAfter(box);
                copyAttr(fbox, 1);
                firstbox = fbox[0];
                firstbox.nextBox = null;
                fbox.data('temp-lev', 1);
                initBox(fbox, 1, items, vals);
            }
        };
        if (datatype == 'array') {
            qem.hide();
        }

        if (!pingding) {
            createBox(element, source, values);
        }
        var timer = null;
        var update = this.update = function () {
            if (pingding) {
                if (timer) {
                    window.clearTimeout(timer);
                    timer = null;
                }
                timer = window.setTimeout(update, 10);
                return;
            }
            if (firstbox) {
                removeBox(firstbox);
            }
            tempdata = {};
            values = JSON.parse(qem.val()) || null;
            if (values !== null) {
                for (var i in values) {
                    values[i] = values[i] == 0 ? "" : values[i];
                }
            }
            source = qem.data('source') || null;
            method = (qem.data('method') || 'get').toLocaleLowerCase() === 'get' ? 'get' : 'post';
            level = qem.data('level') || 0;
            createBox(element, source, values);
        };
    }

    Yee.extend(':input', 'linkage', Linkage);
})(jQuery, Yee);