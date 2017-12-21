<!DOCTYPE html>
<html>
<head>
    <title>测试选择</title>
    <script src="/assets/js/jquery-1.12.3.min.js"></script>
    <script src="/assets/js/yee.min.js"></script>
</head>
<body>
<div>卡萨丁速度快设计的 <input type="button" data-value="1" value="选择1"/></div>
<div>卡萨丁速度快设计的 <input type="button" data-value="2" value="选择2"/></div>
<div>卡萨丁速度快设计的 <input type="button" data-value="3" value="选择3"/></div>
</body>
{literal}
    <script>
        window.readyYeeDialog = function (data, callwin) {
            $('input').on('click', function () {
                var text = $(this).val();
                var value = $(this).data('value');
                window.trigger('select_dialog_data', {text: text, value: value});
                window.closeYeeDialog();
            });
        }
    </script>
{/literal}
</html>