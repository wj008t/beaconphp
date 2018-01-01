{extends file='common/list_layout.tpl'}
{block name="title"}流程管理{/block}
{block name="caption"}系统-流程管理{/block}

{block name='list_head'}
    <div class="yeeui-list-optbtns">
        <div class="fl">
            <a id="add-btn" href="/flow/index/add" class="yee-btn add"><i class="icofont icofont-ui-add"></i>添加流程</a>
            <a id="refresh-btn" href="javascript:$('#list').emit('load');" title="刷新" class="yee-refresh"><i
                        class="icofont icofont-refresh"></i>刷新</a><span yee-module="pagebar" data-bind="#list"> 共 <span
                        v-name="count">{$pdata.records_count}</span> 条记录</span>
        </div>
        <div class="search fr">
            <form id="search" yee-module="searchform" data-bind="#list" method="get">
                <input type='text' name='keyword' class='form-inp secrch-inp' placeholder='会员名称' autocomplete='off'/>
                <button type="submit" class="search-btn"></button>
            </form>
        </div>
    </div>
{/block}

{block name=table_ths}
    <th width="40">ID</th>
    <th width="300" align="left">流程标题</th>
    <th width="80">网关</th>
    <th width="150">操作</th>
{/block}

{block name='table_tds'}
    {foreach from=$list item='rs'}
        <tr class="toggle">
            <td align="center">{$rs.id}</td>
            <td>{$rs.name}</td>
            <td align="center">{$rs.gateway}</td>
            <td align="right" class="opt-btns">
                <a href="/flow/main_{$rs.id}/index" class="yee-btn small edit"><i class="icofont icofont-edit"></i>编辑流程</a>
                <a href="/flow/index/edit?id={$rs.id}" class="yee-btn small edit"><i class="icofont icofont-edit"></i>编辑</a>
                <a href="/flow/index/delete?id={$rs.id}" yee-module="confirm ajaxlink"
                   data-confirm="确定要删除该账号了吗？" class="yee-btn small del reload"><i class="icofont icofont-ui-lock"></i>删除</a>
            </td>
        </tr>
    {/foreach}
{/block}
