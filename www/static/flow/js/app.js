jsPlumb.ready(function () {
    var instance = jsPlumb.getInstance({
        DragOptions: {cursor: 'pointer', zIndex: 2000},
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
            }],
            ["Label", {
                location: 0.1,
                id: "label",
                cssClass: "aLabel",
                events: {
                    tap: function () {
                        alert("hey");
                    }
                }
            }]
        ],
        Container: "container"
    });

    var connectorPaintStyle = function () {
        return {
            strokeWidth: 2,
            stroke: 'rgb(' + Math.round(Math.random() * 150 + 80) + ',' + Math.round(Math.random() * 150 + 80) + ',' + Math.round(Math.random() * 150 + 80) + ')',
            joinstyle: "round",
        };
    };

    instance.batch(function () {
        var g = instance.connect({
            source: $('#s1'), target: $('#s2'), editable: false,
            connector: ["Flowchart", {stub: [40, 60], gap: 5, cornerRadius: 5, alwaysRespectStubs: true}],
            paintStyle: connectorPaintStyle(),
            detachable: false,
            endpoint: "Dot",
            endpointStyle: {
                stroke: "#7AB02C",
                fill: "transparent",
                radius: 3,
                strokeWidth: 1,
            },
        });

        var f = instance.connect({
            source: $('#s2'), target: $('#s1'), editable: false,
            connector: ["Flowchart", {stub: [Math.random() * 40 + 10, Math.random() * 80 + 10], gap: 5, cornerRadius: 5, alwaysRespectStubs: true}],
            paintStyle: connectorPaintStyle(),
            detachable: false,
            anchor: "Continuous",
            endpointStyle: {
                stroke: "#7AB02C",
                fill: "transparent",
                radius: 3,
                strokeWidth: 2
            },
        });
        var f2 = instance.connect({
            source: $('#s2'), target: $('#s1'), editable: false,
            connector: ["Flowchart", {stub: [Math.random() * 40 + 10, Math.random() * 80 + 10], gap: 5, cornerRadius: 5, alwaysRespectStubs: true}],
            paintStyle: connectorPaintStyle(),
            anchor: "Continuous",
            detachable: false,
            endpointStyle: {
                stroke: "#7AB02C",
                fill: "transparent",
                radius: 3,
                strokeWidth: 2
            },
        });
    });


    setTimeout(function () {
        instance.deleteConnection(c);
    }, 10000);

    // j.connect({source: $('#s1'), target: $('#s2')});
    instance.draggable([$('#s1'), $('#s2')]);
    // j.connect({ source:$('#s1'), target:$('#s2') });
    jsPlumb.fire("jsPlumbDemoLoaded", instance);
});