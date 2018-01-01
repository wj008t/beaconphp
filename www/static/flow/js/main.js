window.onresize = function (ev) {
    $('#container').height($(window).height() - 50);
}
window.onresize();

Yee.ready(function () {
    jsPlumb.ready(function () {

        var instance = jsPlumb.getInstance({
            PaintStyle: {
                strokeWidth: 2,
                stroke: 'rgba(200,0,0,0.5)',
                joinstyle: "round",
                fill: "#7AB02C",
                radius: 5,
            },
            DragOptions: {cursor: "crosshair"},
            Connector: ["Flowchart", {stub: [30, 60], gap: 5, cornerRadius: 5, alwaysRespectStubs: true}],
            //Connector: ["Bezier", {curviness: 75}],
            Anchor: 'Continuous',
            //  Endpoint: "Dot",
            EndpointStyle: {
                stroke: "#b02d4f",
                fill: "#b02d4f",
                radius: 3,
                strokeWidth: 1
            },
            ConnectionOverlays: [
                ["Arrow", {
                    location: 1,
                    visible: true,
                    width: 11,
                    length: 11,
                    id: "ARROW",
                    events: {
                        click: function () {
                            alert("you clicked on the arrow overlay")
                        }
                    }
                }]
            ],
            Container: "container"
        });
        //源样式
        var sourceStyle = function () {
            return {
                anchor: [0.5, 0.8, 0, 0],
                endpointStyle: {
                    stroke: "#7AB02C",
                    fill: "transparent",
                    radius: 5,
                    strokeWidth: 2
                },
                isSource: true,
                maxConnections: 1000,
                connectorStyle: {
                    strokeWidth: 2,
                    stroke: 'rgb(' + Math.round(Math.random() * 150 + 80) + ',' + Math.round(Math.random() * 150 + 80) + ',' + Math.round(Math.random() * 150 + 80) + ')',
                    joinstyle: "round",
                },
            };
        }

        var selectNode = function (selected) {
            var data = selected.data('data');
            if (selected == null) {
                $('#b_top,#b_left,#b_name,#b_id,#b_code,#b_url,#b_timeout').prop('disabled', true);
                $('#a_edit,#a_del').addClass('disabled');
                $('#b_top').val('');
                $('#b_left').val('');
                $('#b_name').val('');
                $('#b_id').val('');
                $('#b_code').val('');
                $('#b_url').val('');
                $('#b_timeout').val('');
                $('#a_del').attr('href', '#');
                $('#a_edit').data('href', '#');
            } else {
                $('#b_top,#b_left,#b_name,#b_id,#b_code').prop('disabled', false);
                $('#a_edit,#a_del').removeClass('disabled');
                var offset = selected.offset();
                $('#b_top').val(offset.top);
                $('#b_left').val(offset.left);
                $('#b_name').val(selected.data('data') ? selected.data('data').name || '' : '');
                $('#b_id').val(selected.data('data') ? selected.data('data').id || '' : '');
                $('#b_code').val(selected.data('data') ? selected.data('data').code || '' : '');
                if (data.type == 'place') {
                    $('#a_del').attr('href', baseUrl + '/del_place?id=' + data.id);
                    $('#a_edit').data('href', baseUrl + '/edit_place?id=' + data.id);
                    $('#a_edit').data('height', 280);
                    $('#b_url,#b_timeout').prop('disabled', true);
                    $('#b_url').val('');
                    $('#b_timeout').val('');
                } else if (data.type == 'transition') {
                    $('#a_del').attr('href', baseUrl + '/del_transition?id=' + data.id);
                    $('#a_edit').data('href', baseUrl + '/edit_transition?id=' + data.id);
                    $('#a_edit').data('height', 360);
                    $('#b_url,#b_timeout').prop('disabled', false);
                    $('#b_url').val(selected.data('data') ? selected.data('data').url || '' : '');
                    $('#b_timeout').val(selected.data('data') ? selected.data('data').timeout || '' : '');
                }
            }
        }

        var addPlace = function (data, is_select) {
            data.type = 'place';
            var item = $('<div class="place" />').data('data', data).text(data.name + '[' + data.state + ']').appendTo('#container');
            if (data && data.id !== void 0) {
                item.attr('id', 'place_' + data.id);
            }
            if (data.left && data.top) {
                item.offset({left: data.left, top: data.top});
            }
            if (data.mode == 1) {
                item.addClass('begin');
                instance.makeTarget(item);
                item.get(0).endpoint = instance.addEndpoint(item, sourceStyle());
            }
            if (data.mode == 0) {
                instance.makeTarget(item);
                item.get(0).endpoint = instance.addEndpoint(item, sourceStyle());
            }
            if (data.mode == 2) {
                item.addClass('end');
                instance.makeTarget(item);
            }
            instance.draggable(item, {
                stop: function (params) {
                    var offset = item.offset();
                    var id = item.data('data').id;
                    $.post(baseUrl + '/offset', {type: 'place', id: id, left: offset.left, top: offset.top}, function (ret) {
                        if (!ret.status) {
                            return layer.alert(ret.error);
                        }
                    }, 'json');
                }
            });
            if (is_select) {
                selectNode(item);
            }
        };

        var addTransition = function (data, is_select) {
            data.type = 'transition';
            var item = $('<div class="transition" />').data('data', data).text(data.name).appendTo('#container');
            if (data && data.id !== void 0) {
                item.attr('id', 'transition_' + data.id);
            }
            if (data.left && data.top) {
                item.offset({left: data.left, top: data.top});
            }
            instance.makeTarget(item);
            item.get(0).endpoint = instance.addEndpoint(item, sourceStyle());
            instance.draggable(item, {
                stop: function (params) {
                    var offset = item.offset();
                    var id = item.data('data').id;
                    $.post(baseUrl + '/offset', {type: 'transition', id: id, left: offset.left, top: offset.top}, function (ret) {
                        if (!ret.status) {
                            return layer.alert(ret.error);
                        }
                    }, 'json');
                }
            });
            if (is_select) {
                selectNode(item);
            }
        }

        instance.bind('beforeDrop', function (ev) {
            var sData = $(ev.connection.source).data('data');
            var tData = $(ev.connection.target).data('data');
            if (sData.type == tData.type) {
                return false;
            }
            var items = instance.getConnections({source: ev.sourceId, target: ev.targetId});
            if (items.length > 0) {
                return false;
            }

            var data = {
                source: sData.id,
                sourceType: sData.type,
                target: tData.id,
                targetType: tData.type
            };

            setTimeout(function () {
                if (data.sourceType == 'place') {
                    $.post(baseUrl + '/connect', data, function (ret) {
                        if (!ret.status) {
                            return;
                        }
                        $(window).emit('addConnect', ret.data);
                    }, 'json');
                } else {
                    var option = {width: 400, height: 230, maxmin: false, elem: $(window)};
                    window.openYeeDialog(baseUrl + '/connect', '设置连线', option, window, data);
                }
            }, 1);
            return false;
        });

        instance.bind('connectionDetached', function (ev) {
            var sData = $(ev.source).data('data');
            var tData = $(ev.target).data('data');
            $.post(baseUrl + '/detach', {
                source: sData.id,
                sourceType: sData.type,
                target: tData.id,
                targetType: tData.type
            }, function (ret) {
                if (!ret.status) {
                    return;
                }
            }, 'json');
        });

        $('#addPlace').on('addPlace', function (ev, data) {
            addPlace(data, true);
        });

        $('#addTransition').on('addTransition', function (ev, data) {
            addTransition(data, true);
        });

        $('#a_del').on('success', function (ev, ret) {
            var data = ret.data;
            if (data.type == 'transition') {
                var id = 'transition_' + data.id;
                instance.remove(id);
            }
            if (data.type == 'place') {
                var id = 'place_' + data.id;
                instance.remove(id);
            }
        });

        $('#a_edit').on('editPlace', function (ev, data) {
            data.type = 'place';
            var id = '#place_' + data.id;
            $(id).data('data', data).text(data.name + '[' + data.state + ']');
        });

        $('#a_edit').on('editTransition', function (ev, data) {
            data.type = 'transition';
            var id = '#transition_' + data.id;
            $(id).data('data', data).text(data.name);
        });


        var addConnect = function (data) {
            var sel = $('#' + data.sourceType + '_' + data.source);
            var tel = $('#' + data.targetType + '_' + data.target);
            if (data.name) {
                instance.connect({
                    source: sel, target: tel,
                    overlays: [
                        ["Label", {label: data.name + '(' + data.condition + ')', id: 'label+' + data.id, cssClass: 'label'}]
                    ],
                    connector: ["Flowchart", {stub: [Math.round(Math.random() * 40 + 5), Math.round(Math.random() * 20 + 5)], gap: 3, cornerRadius: 3, alwaysRespectStubs: true}],
                    paintStyle: {
                        strokeWidth: 2,
                        stroke: 'rgb(' + Math.round(Math.random() * 150 + 20) + ',' + Math.round(Math.random() * 150 + 20) + ',' + Math.round(Math.random() * 150 + 80) + ')',
                        joinstyle: "round",
                    },
                });
            } else {
                instance.connect({
                    source: sel, target: tel,
                    connector: ["Flowchart", {stub: [Math.round(Math.random() * 40 + 5), Math.round(Math.random() * 20 + 5)], gap: 3, cornerRadius: 3, alwaysRespectStubs: true}],
                    paintStyle: {
                        strokeWidth: 2,
                        stroke: 'rgb(' + Math.round(Math.random() * 150 + 20) + ',' + Math.round(Math.random() * 150 + 20) + ',' + Math.round(Math.random() * 150 + 80) + ')',
                        joinstyle: "round",
                    },
                });
            }
        }
        $(window).on('addConnect', function (ev, data) {
            addConnect(data);
        });
        $(document).on('click', 'div.place,div.transition', function (ev) {
            $('div.place').removeClass('selected');
            $('div.transition').removeClass('selected');
            $(this).addClass('selected');
            selectNode($(this));
            return false;
        });
        //加载数据==================================
        $.post(baseUrl + '/data', function (ret) {
            if (!ret.status) {
                return;
            }
            instance.batch(function () {
                var place = ret.data.place || [];
                for (var i = 0, l = place.length; i < l; i++) {
                    addPlace(place[i], false);
                }
                var transition = ret.data.transition || [];
                for (var i = 0, l = transition.length; i < l; i++) {
                    addTransition(transition[i], false);
                }
                var connects = ret.data.connection || [];
                for (var i = 0, l = connects.length; i < l; i++) {
                    addConnect(connects[i]);
                }

            });
        }, 'json');

    });
});






