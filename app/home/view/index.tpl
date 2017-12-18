<!DOCTYPE html>
<html>
<head>
    <link type="text/css" rel="stylesheet" href="/assets/css/yeeui.css"/>
    <link type="text/css" rel="stylesheet" href="/assets/icofont/css/icofont.css"/>
    <script src="/assets/js/jquery-1.12.3.min.js"></script>
    <script src="/assets/js/yee.min.js"></script>
    <title>测试网页窗口打开</title>
</head>

<body>
<form action="/index/save" method="post" yee-module="validate">

    {foreach from=$form->getViewFields() item=field}
        <div class="form-group">
            <label class="form-label">{$field->label}:</label>
            <div class="form-box">{$field->box()}</div>
        </div>
    {/foreach}
    <div class="form-group">
        <div class="form-submit" style="padding-left: 260px;"><input type="submit" class="btn submit" value="提交"/></div>
    </div>
</form>
{literal}
    <script>
        $('#select_dialog\\:select_dialog_btn').data('data', {'a': 'aa', 'b': 'bb'});
        window.readyYeeDialog = function (data, callwin) {
            console.log(data, callwin);
        };
    </script>
{/literal}
</body>
</html>