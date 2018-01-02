<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{block name="title"}{/block}</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/base.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/yeeui.css">
    <link rel="stylesheet" href="/assets/icofont/css/icofont.css">
    <script src="/assets/js/jquery-3.2.1.min.js"></script>
    <script src="/assets/js/yee.js"></script>
</head>
<body>
<div class="yeeui-caption">{block name="caption"}{/block}</div>
<div class="yeeui-content">
    {block name='list_head'}{/block}
    <div class="yeeui-list">
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="yeeui-list-table">
            <thead>
            <tr>
                {block name=table_ths}{/block}
            </tr>
            </thead>
            <tbody id="list" yee-module="list" data-auto-url="1">
            {block name='table_tds'}{/block}
            {block name='allopts'}{/block}
            </tbody>
        </table>
        {block name='pagebar'}
            {if $pdata}
                <div yee-module="pagebar" data-bind="#list" data-info="{json_encode($pdata)}" class="yeeui-pagebar">
                    <div class="pagebar" v-name="bar"></div>
                    <div class="pagebar_info">
                        共有信息：<span v-name="count"></span> 页次：<span v-name="page"></span>/<span v-name="page_count"></span> 每页
                        <span v-name="page_size"></span>
                    </div>
                </div>
            {/if}
        {/block}
    </div>
</div>
{block name='foot'}{/block}
{literal}
    <script>
        $('#list').on('change', function (ev, source) {
            if (source) {
                $('.pdata-records-count').text(source.pdata.records_count);
            }
            $('#list .reload').on('success', function (ev, data) {
                $('#list').trigger('reload');
            });
        });
        $('#list').trigger('change');
    </script>
{/literal}
</body>
</html>