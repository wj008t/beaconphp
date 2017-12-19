{foreach from=$form->getViewFields() item=field}
    <div class="form-group">
        <label class="form-label">{$field->label}:</label>
        <div class="form-box">{$field->box()}</div>
    </div>
{/foreach}