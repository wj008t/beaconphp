<form action="index.php/index/save" method="post">

    {foreach from=$form->getViewFields() item=field}
        <div>{$field->label}:{$field->box()}</div>
    {/foreach}

    <div><input type="submit" value="提交"/></div>
</form>