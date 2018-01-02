<!DOCTYPE html>
<html lang="en">
{literal left='{@' right='@}'}
<head>
    <meta charset="UTF-8">
    <title>创建库所</title>
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
        <form id="placeForm" action="/flow/main_{@$fid@}/add_place" method="post" data-display-mode="2" yee-module="ajaxform validate">
            <div class="form-group">
                <label class="form-label" style="width: 80px">名称:</label>
                <div class="form-box"><input type="text" name="name" data-val='{"r":true}' data-val-msg='{"r":"名称必须填写"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">标识符号:</label>
                <div class="form-box"><input type="text" name="code" data-val='{"r":true}' data-val-msg='{"r":"标题符号必须填写"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">起点或终点:</label>
                <div class="form-box">
                    <select name="mode" class="form-inp select">
                        <option value="0">正常库所</option>
                        <option value="1">起始库所</option>
                        <option value="2">终点库所</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">状态值:</label>
                <div class="form-box"><input type="text" name="state" data-val='{"r":true,"integer":true}' data-val-msg='{"r":"状态值必须填写","integer":"只能输入整数"}' class="form-inp text"></div>
            </div>

            <div class="form-group" style="position:fixed; bottom: 0px; width: 100%; background:#f1f1f1;">
                <div class="form-submit" style="padding: 10px; text-align: right; float:right;"><input type="submit" class="btn submit" value="提交"/></div>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    window.readyYeeDialog = function (assign, win, elem) {
        $('#placeForm').on('success', function (ev, ret) {
            elem && elem.emit('addPlace', ret.data);
            window.closeYeeDialog();
        });
    };
</script>
</html>
{/literal}