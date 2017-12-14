{foreach from=$form->getViewFields() item=field}
    <div>{$field->label}:{$field->box()}</div>
{/foreach}
