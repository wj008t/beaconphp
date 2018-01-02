<!DOCTYPE html>
<html lang="en">
{literal left='{@' right='@}'}
<head>
    <meta charset="UTF-8">
    <title>创建连线</title>
    <script src="/assets/js/jquery-3.2.1.min.js"></script>
    <script src="/assets/js/yee.js"></script>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/yeeui.css">

    <style>
        body, html {
            background: #fff;
        }
    </style>
</head>
<body>
<div id="createPlace" style="position: relative; height:100%;">
    <div style="padding: 20px 0;">
        <form id="placeForm" action="/flow/main_{@$fid@}/connect" method="post" data-display-mode="2" yee-module="ajaxform validate">
            <div class="form-group">
                <label class="form-label" style="width: 80px">名称:</label>
                <div class="form-box"><input type="text" name="name" data-val='{"r":true}' data-val-msg='{"r":"名称必须填写"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">条件:</label>
                <div class="form-box"><input type="text" yee-module="integer" name="condition" data-val='{"r":true,"integer":true}' data-val-msg='{"r":"条件必须填写","integer":"条件必须是整数"}' class="form-inp number">
                </div>
            </div>
            <div class="form-group" style="position:fixed; bottom: 0px; width: 100%; background:#f1f1f1;">
                <div class="form-submit" style="padding: 10px; text-align: right; float:right;">
                    <input id="source" name="source" type="hidden">
                    <input id="sourceType" name="sourceType" type="hidden">
                    <input id="target" name="target" type="hidden">
                    <input id="targetType" name="targetType" type="hidden">
                    <input type="submit" class="btn submit" value="提交"/>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    window.readyYeeDialog = function (assign, win, elem) {
        console.log(assign);
        $('#source').val(assign.source);
        $('#sourceType').val(assign.sourceType);
        $('#target').val(assign.target);
        $('#targetType').val(assign.targetType);
        $('#placeForm').on('success', function (ev, ret) {
            window.emit('addConnect', ret.data);
            window.closeYeeDialog();
        });
    };
</script>
</html>
{/literal}