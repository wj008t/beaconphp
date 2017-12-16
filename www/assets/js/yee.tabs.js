(function ($, Yee) {

    Yee.extend('ul', 'tabs', function (elem) {
        var qem = $(elem);
        var currCss = qem.data('curr-css') || 'curr';
        var binds = [];
        var lis = qem.find('li');
        var allbinds = [];

        lis.each(function (idx, el) {
            var name = $(el).data('bind-name');
            allbinds.push('div[name="' + name + '"]:first');
            var bind = $('div[name="' + name + '"]').data('idx', idx);
            if (bind.length > 0) {
                binds.push({name: name, elem: bind});
            }
        });

        lis.on('click', function () {
            var that = $(this);
            var name = that.data('bind-name');
            $(binds).each(function (idx, item) {
                if (item.name == name) {
                    item.elem.show();
                } else {
                    item.elem.hide();
                }
            });
            lis.not(that).removeClass(currCss);
            that.addClass(currCss);
            $(window).triggerHandler('resize');
        });

        $('form').on('displayAllError', function (e, items) {
            $(items).each(function () {
                var div = this.elem.parents(allbinds.join(','));
                if (div.length > 0) {
                    var idx = div.data('idx');
                    lis.eq(idx).trigger('click');
                }
                return false;
            });
        });

        lis.first().trigger('click');
    });

})(jQuery, Yee);