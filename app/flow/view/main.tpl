<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script src="/assets/js/jquery-3.2.1.min.js"></script>
    <script src="/assets/js/yee.js"></script>
    <script src="/static/flow/js/jsplumb.js"></script>
    <script src="/static/flow/js/main.js"></script>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/yeeui.css">
    <link rel="stylesheet" href="/static/flow/css/main.css">
</head>
<script>
    var baseUrl = '/flow/main_{$fid}';
</script>
<body>
<div style="padding: 10px 0; box-shadow: 0px 1px 3px #888888; background:#F7F7F7;">
    <div style="padding:0 20px;">
        <a id="addPlace" href="/flow/main_{$fid}/add_place" class="yee-btn add big" yee-module="dialog" data-width="400" data-maxmin="false" data-height="300">创建库所</a>
        <a id="addTransition" href="/flow/main_{$fid}/add_transition" class="yee-btn add big" yee-module="dialog" data-width="400" data-maxmin="false" data-height="360">创建变迁</a>
        <div style="display: inline-block">
            <a id="a_edit" href="javascript:;" class="yee-btn big disabled" yee-module="dialog" data-width="400" data-maxmin="false" data-height="300">编辑</a>
            <a id="a_del" yee-module="confirm ajaxlink" data-confirm="确定要删除了吗？" href="javascript:;" class="yee-btn big disabled">删除</a>
            名称: <input id="b_name" class="form-inp ntext" disabled>
            ID: <input id="b_id" class="form-inp nnumber" disabled>
            标识: <input id="b_code" class="form-inp ntext" disabled>
            上: <input id="b_top" class="form-inp nnumber" disabled>
            左: <input id="b_left" class="form-inp nnumber" disabled>
            URL: <input id="b_url" class="form-inp stext" disabled>
            超时: <input id="b_timeout" class="form-inp number" disabled> 秒
            <a href="/flow/index" class="yee-btn big">返回列表</a>
        </div>
    </div>
</div>

<div id="container">
</div>

</body>
</html>