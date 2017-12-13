<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>操作成功提示</title>
    {literal}
        <style type="text/css">
            * {
                padding: 0;
                margin: 0;
            }

            body {
                background: #fff;
                font-family: '微软雅黑';
                color: #333;
                font-size: 16px;
            }

            .system-message {
                padding: 0;
                margin: 80px auto;
                width: 500px;
            }

            .system-message .jump {
                padding-top: 10px;
                padding-left: 30px;
            }

            .system-message .jump a {
                color: #333;
            }

            .system-message .success, .system-message .error {
                line-height: 1.8em;
                font-size: 28px;
                padding-left: 30px;
            }

            .system-message .detail {
                font-size: 12px;
                line-height: 20px;
                margin-top: 12px;
                display: none;
            }
        </style>
    {/literal}
</head>
<body>


<div class="system-message">
    <p class="error">{$info.message}</p>
    <p class="detail"></p>
    <p class="jump">
        页面自动 <a id="href" href="{$info.jump}">跳转</a> 等待时间： <b id="wait">1</b>
    </p>
</div>

{literal}
    <script type="text/javascript">
        (function () {
            var wait = document.getElementById('wait'), href = document.getElementById('href').href;
            var interval = setInterval(function () {
                var time = --wait.innerHTML;
                if (time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                }
            }, 1000);
        })();
    </script>
{/literal}
</body>
</html>