<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$form->title}</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/base.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/yeeui.css">
    <link rel="stylesheet" href="/assets/icofont/css/icofont.css">
    <script src="/assets/js/jquery-3.2.1.min.js"></script>
    <script src="/assets/js/yee.js"></script>
</head>
<body>
<div class="yeeui-caption">{$form->caption|default:$form->title}</div>
<div class="yeeui-content">
    <div class="yeeui-form">
        <form method="post" data-display-mode="2" yee-module="validate ajaxform" data-back="/flow/index" data-back-param="true">
            <div class="form-panel">
                {foreach from=$form->getViewFields() item=field}
                    {if $field->type=='blend'}
                        {$field->box()|raw}
                    {else}
                        <div class="form-group">
                            <label class="form-label">{$field->label}:</label>
                            <div class="form-box">{$field->box()|raw}</div>
                        </div>
                    {/if}
                {/foreach}
                <div class="form-submit">
                    <label class="form-label"></label>
                    <div class="form-box">
                        <input type="submit" class="btn submit" value="提交"/>
                        {foreach from=$form->getHideBox() item=value key=name}
                            <input type="hidden" name="{$name}" value="{$value}"/>
                        {/foreach}
                        <a href="javascript:window.history.back();" class="btn back">返回</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</body>

</html>