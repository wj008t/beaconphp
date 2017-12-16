<html>
<head>
    <script src="/assets/js/jquery-1.12.3.min.js"></script>
    <script src="/assets/js/yee.js"></script>
</head>

<body>
<form action="index.php/index/save" method="post">

    {foreach from=$form->getViewFields() item=field}
        <div>{$field->label}:{$field->box()}</div>
    {/foreach}

    <div><input type="submit" value="提交"/></div>
</form>
</body>
</html>