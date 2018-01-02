"use strict";

(function ($) {
    var readyCallback = [];
    var scriptLoader = function (url, callback) {
        //加载的是css 文件
        url = Yee.baseUrl + url;
        if (Yee.config && Yee.config.version) {
            var query = Yee.parseUrl(url);
            query.prams.v = Yee.config.version;
            url = Yee.toUrl(query);
        }
        var script = document.createElement("script");
        script.type = "text/javascript";
        if (script.readyState) {
            script.onreadystatechange = function () {
                if (script.readyState == "loaded" || script.readyState == "complete") {
                    script.onreadystatechange = null;
                    callback();
                }
            };
        } else {
            script.onload = function () {
                callback();
            };
            script.onerror = function () {
                callback();
            };
        }
        script.src = url;
        try {
            document.body.appendChild(script);
        } catch (e) {
        }
    }
    var cssLoader = function (url) {
        if (Yee.config && Yee.config.version) {
            var query = Yee.parseUrl(url);
            query.prams.v = Yee.config.version;
            url = Yee.toUrl(query);
        }
        var head = document.getElementsByTagName('head');
        if (head.length > 0) {
            head = head[0];
            var link = document.createElement('link');
            link.href = baseUrl + url;
            link.setAttribute('rel', 'stylesheet');
            link.setAttribute('type', 'text/css');
            head.appendChild(link);
        }
    }
    var Yee = window.Yee = $.Yee = window.Yee || {};
    //Yee 路径
    Yee.baseUrl = (function () {
        var scripts = document.getElementsByTagName('script'), script = scripts[scripts.length - 1];
        var src = null;
        if (script.getAttribute.length !== undefined) {
            src = script.src
        } else {
            src = script.getAttribute('src', -1);
        }
        var m = src.match(/^(.*)yee(-\d+(\.\d+)*)?(\.min)?\.js/i);
        if (m) {
            return m[1];
        }
        return '';
    })();
    Yee.config = null;
    Yee.loadModule = {};
    Yee.extendMaps = {};
    //是否已渲染
    Yee.rendered = false;
    //模块加载器
    Yee.loader = function (module, callback) {
        if (module === null || module === '') {
            return callback();
        }
        if (typeof module != 'string' && module instanceof Array) {
            (function (modules, callback) {
                var nextModule = function (index, cb) {
                    if (index >= modules.length) {
                        return cb();
                    }
                    Yee.loader(modules[index], function () {
                        nextModule(index + 1, cb);
                    });
                };
                nextModule(0, function () {
                    callback();
                });
            })(module, callback);
            return;
        }
        //如果已经加载过模块了 就不再加载了
        if (Yee.loadModule[module]) {
            callback();
            return;
        }
        Yee.loadModule[module] = module;
        var match = module.match(/^css\!(.*)$/i);
        if (match) {
            cssLoader(match[1]);
            return callback();
        }
        //添加依赖
        var loadDepends = function (name, func) {
            var depends = Yee.config.depends[name] || null;
            if (depends == null || depends.length == 0) {
                func();
                return;
            }
            if (depends != null && typeof depends == 'string') {
                depends = [depends];
            }
            var next = function (index, cb) {
                if (index >= depends.length) {
                    return cb();
                }
                Yee.loader(depends[index], function () {
                    next(index + 1, cb);
                });
            };
            next(0, function () {
                func();
            });
        }
        //加载模块
        var load = function (name, func) {
            loadDepends(name, function () {
                var path = Yee.config.paths[name] || null;
                if (path === null || path === '') {
                    if (/\.js$/i.test(name)) {
                        return scriptLoader(name, func);
                    }
                    else if (/^yee-/.test(name)) {
                        name = name.replace(/^yee-/, 'yee.') + '.js';
                        return scriptLoader(name, func);
                    } else if (Yee.config.paths[name] === void 0) {
                        console.error('Yee加载器没有找到模块:' + name);
                    }
                    return func();
                }
                scriptLoader(path, func);
            });
        }
        //如果没有加载配置文件 先加载
        if (Yee.config == null) {
            scriptLoader('yee.confog.js?r=' + new Date().getTime(), function () {
                load(module, callback);
            });
        } else {
            load(module, callback);
        }
    }
    // 更新渲染
    Yee.update = function (base, callback) {

        if (typeof(base) == 'function' && callback === void 0) {
            callback = base;
            base = null;
        }

        base = base || document.body;
        var yeeItems = $('*[yee-module]', base);
        //yeeItems.attr('yee-wait', true);
        var tempMaps = {};
        var modules = [];
        yeeItems.each(function () {
            var items = String($(this).attr('yee-module') || '').split(' ');
            for (var i = 0; i < items.length; i++) {
                var module = items[i];
                if (module === '') {
                    continue;
                }
                if (tempMaps[module] || Yee.extendMaps[module]) {
                    continue;
                }
                tempMaps[module] = true;
                modules.push('yee-' + module);
            }
        });
        var update = function () {
            for (var name in Yee.extendMaps) {
                var selector = Yee.extendMaps[name];
                var plug = 'yee_' + name;
                var items = $(selector, base);
                if (items.length > 0 && typeof (items[plug]) == 'function') {
                    items[plug]();
                }
            }
            if (callback && typeof(callback) == 'function') {
                callback();
            }
        };
        if (modules.length == 0) {
            return update();
        }
        var next = function (index, cb) {
            if (index >= modules.length) {
                return cb();
            }
            Yee.loader(modules[index], function () {
                next(index + 1, cb);
            });
        };
        next(0, function () {
            update();
        });
    };
    //扩展器
    Yee.extend = function (selector, name, module) {
        if (typeof (selector) !== 'string' || typeof (name) !== 'string') {
            return;
        }
        var plug = 'yee_' + name;
        var items = $.trim(selector).split(',');
        for (var i = 0; i < items.length; i++) {
            items[i] += "[yee-module~='" + name + "']";
        }
        Yee.extendMaps[name] = items.join(',');
        // 自动扩展JQ插件
        $.fn[plug] = function (options) {
            this.each(function () {
                // 加载并创建模块对象
                var option = $.extend(options || {}, $(this).data() || {});
                var yee_modules = this.yee_modules = this.yee_modules || {};
                // 加载并创建模块对象
                if (!yee_modules[name]) {
                    var example = yee_modules[name] = new module(this, option);
                    if (typeof (example.init) === 'function') {
                        example.init();
                    }
                }
            });
            return this;
        };
    };
    //初始化以后
    Yee.ready = function (fn) {
        readyCallback.push(fn);
    };
    //渲染
    Yee.render = function () {
        //如果已经渲染就不再渲染
        if (Yee.rendered) {
            return;
        }
        Yee.rendered = true;
        Yee.update(function () {
            // console.log('更新了');
            $('html').show();
            if (readyCallback.length > 0) {
                for (var i = 0; i < readyCallback.length; i++) {
                    if (typeof(readyCallback[i]) == 'function') {
                        readyCallback[i]();
                    }
                }
            }
        });
    };
    //解析URL

    Yee.parseUrl = function (url) {
        url = url || '';
        var query = url.replace(/&+$/, '');
        var path = query;
        var prams = {};
        var idx = query.search(/\?/);
        if (idx >= 0) {
            path = query.substring(0, idx);
            var pstr = query.substring(idx);
            var m = pstr.match(/(\w+)(=([^&]*))?/g);
            if (m) {
                $(m).each(function () {
                    var ma = this.match(/^(\w+)(?:=([^&]*))?$/);
                    if (ma) {
                        var val = ma[2] || '';
                        prams[ma[1]] = decodeURIComponent(val.replace(/\+/g, '%20'));
                    }
                });
            }
        }
        return {path: path, prams: prams};
    };
    //转换成URL
    Yee.toUrl = function (info) {
        if (info === void 0 || info == null) {
            info = {};
        }
        var path = info.path || window.location.pathname;
        var prams = info.prams || {};
        var qurey = [];
        for (var key in prams) {
            if (prams[key] == null || prams[key] == '') {
                qurey.push(key + '=');
                continue;
            }
            var vals = (prams[key] + '').split(' ');
            if (vals.length == 1) {
                qurey.push(key + '=' + encodeURIComponent(prams[key]));
                continue;
            }
            for (var i = 0; i < vals.length; i++) {
                vals[i] = encodeURIComponent(vals[i]);
            }
            qurey.push(key + '=' + vals.join("%20"));
        }
        if (qurey.length == 0) {
            return path;
        }
        return path + '?' + qurey.join('&');
    };
    //number 数值输入
    Yee.extend(':input', 'number', function (elem) {
        var that = $(elem);
        that.on('keydown', function (event) {
            if (this.value == '' || this.value == '-' || /^-?([1-9]\d*|0)$/.test(this.value) || /^-?([1-9]\d*|0)\.$/.test(this.value) || /^-?([1-9]\d*|0)\.\d+$/.test(this.value)) {
                $(this).data('last-value', this.value);
            }
        });
        that.on('keypress keyup', function (event) {
            if (this.value == '' || this.value == '-' || /^-?([1-9]\d*|0)$/.test(this.value) || /^-?([1-9]\d*|0)\.$/.test(this.value) || /^-?([1-9]\d*|0)\.\d+$/.test(this.value)) {
                $(this).data('last-value', this.value);
                return true;
            }
            this.value = $(this).data('last-value') || '';
            return false;
        });
        that.on('dragenter', function () {
            return false;
        });
        that.on('blur', function () {
            this.value = /^-?([1-9]\d*|0)(\.\d+)?$/.test(this.value) ? this.value : '';
        });

    });
    //integer 整数输入
    Yee.extend(':input', 'integer', function (elem) {
        var that = $(elem);
        that.on('keydown', function (event) {
            if (this.value == '' || this.value == '-' || /^-?([1-9]\d*|0)$/.test(this.value)) {
                $(this).data('last-value', this.value);
            }
        });
        that.on('keypress keyup', function (event) {
            if (this.value == '' || this.value == '-' || /^-?([1-9]\d*|0)$/.test(this.value)) {
                $(this).data('last-value', this.value);
                return true;
            }
            this.value = $(this).data('last-value') || '';
            return false;
        });
        that.on('dragenter', function () {
            return false;
        });
        that.on('blur', function () {
            this.value = /^-?([1-9]\d*|0)$/.test(this.value) ? this.value : '';
        });
    });
    //扩展JQ功能
    $.fn.emit = function () {
        var event = arguments[0] || null;
        var args = [];
        if (arguments.length > 1) {
            for (var i = 1; i < arguments.length; i++) {
                args.push(arguments[i]);
            }
        }
        return $(this).triggerHandler(event, args);
    }
    var isIE = navigator.userAgent.match(/MSIE\s*(\d+)/i);
    $('html').hide();
    isIE = isIE ? (isIE[1] < 9) : false;
    if (isIE) {
        var itv = setInterval(function () {
            try {
                document.documentElement.doScroll();
                clearInterval(itv);
                Yee.render();
            } catch (e) {
            }
        }, 1);
    } else {
        window.addEventListener('DOMContentLoaded', function () {
            Yee.render();
        }, false);
    }
    if (window.attachEvent) {
        window.attachEvent('onload', Yee.render);
    } else {
        window.addEventListener('load', Yee.render, false);
    }
})(jQuery);


