{literal left='{@' right='@}'}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>编辑变迁</title>
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
        <form id="placeForm" action="/flow/main_{@$fid@}/edit_transition" method="post" data-display-mode="2" yee-module="ajaxform validate">
            <div class="form-group">
                <label class="form-label" style="width: 80px">名称:</label>
                <div class="form-box"><input type="text" value="{@$row.name@}" name="name" data-val='{"r":true}' data-val-msg='{"r":"名称必须填写"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">标识符号:</label>
                <div class="form-box"><input type="text" value="{@$row.code@}" name="code" data-val='{"r":true}' data-val-msg='{"r":"标题符号必须填写"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">处理URL:</label>
                <div class="form-box"><input type="text" value="{@$row.url@}" name="url" data-val='{"r":true}' data-val-msg='{"r":"必须填写处理URL"}' class="form-inp text"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="width: 80px">超时:</label>

                <div class="form-box">
                    <input type="text" name="timeout_day"
                           data-val='{"r":true,"number":true}'
                           data-val-msg='{"r":"请设置超时天数","number":true}'
                           value="{@$row.timeout_day@}"
                           style="width: 30px"
                           class="form-inp number"> 天
                    <input type="text" name="timeout_hour"
                           data-val='{"r":true,"number":true,"max":23}'
                           data-val-msg='{"r":"请设置超时小时","number":true}'
                           value="{@$row.timeout_hour@}"
                           style="width: 25px"
                           class="form-inp number"> 时
                    <input type="text" name="timeout_minute"
                           data-val='{"r":true,"number":true,"max":59}'
                           data-val-msg='{"r":"请设置超时分","number":true}'
                           value="{@$row.timeout_minute@}"
                           style="width: 25px"
                           class="form-inp number"> 分
                    <input type="text" name="timeout_second"
                           data-val='{"r":true,"number":true,"max":59}'
                           data-val-msg='{"r":"请设置超时秒","number":true}'
                           style="width: 25px"
                           value="{@$row.timeout_second@}"
                           class="form-inp number"> 秒
                </div>
            </div>


            <div class="form-group">
                <label class="form-label" style="width: 80px">超时值:</label>
                <div class="form-box"><input type="text" name="timeoutCondition" data-val='{"r":true,"integer":true}' data-val-msg='{"r":"条件必须填写","integer":"条件必须是整数"}' class="form-inp number"
                                             value="{@$row.timeoutCondition@}"/></div>
            </div>
            <div class="form-group" style="position:fixed; bottom: 0px; width: 100%; background:#f1f1f1;">
                <div class="form-submit" style="padding: 10px; text-align: right; float:right;"><input type="hidden" name="id" value="{@$row.id@}"><input type="submit" class="btn submit" value="提交"/></div>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    window.readyYeeDialog = function (assign, win, elem) {
        $('#placeForm').on('success', function (ev, ret) {
            elem && elem.emit('editTransition', ret.data);
            window.closeYeeDialog();
        });
    };
</script>
</html>
{/literal}