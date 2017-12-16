<?php if(!defined('SDOPX_DIR')) exit('no direct access allowed');
$_valid = $this->validProperties(array (
  'dependency' => 
  array (
    '8a687c87b4ea6c85511ebfbb7126ea03a72518df' => 
    array (
      'path' => 'E:\\works\\php\\beacon\\app\\home\\view\\index.tpl',
      'time' => 1513447174,
      'type' => 'file',
    ),
  ),
  'version' => 'Sdopx-1.3.0',
  'unifunc' => 'content_5a355f0a4ffac4_46884444',
));
if ($_valid && !is_callable('content_5a355f0a4ffac4_46884444')) {function content_5a355f0a4ffac4_46884444($_sdopx) {
?><!DOCTYPE html>
<html>
<head>
    <link type="text/css" rel="stylesheet" href="/assets/css/yeeui.css"/>
    <link type="text/css" rel="stylesheet" href="/assets/icofont/css/icofont.css"/>
    <script src="/assets/js/jquery-1.12.3.min.js"></script>
    <script src="/assets/js/yee.js"></script>
    <title>测试网页窗口打开</title>
</head>

<body>
<form action="index.php/index/save" method="post">

    <?php $temp_index=-1;
foreach($_sdopx->_book['form']->getViewFields() as $temp_field){ $temp_index++;?>        <div class="form-group">
            <label class="form-label"><?php echo $temp_field->label;?>:</label>
            <div class="form-box"><?php echo $temp_field->box();?></div>
        </div>
    <?php } ?>    <div class="form-group">
        <div class="form-submit" style="padding-left: 260px;"><input type="submit" class="btn submit" value="提交"/></div>
    </div>
</form>
</body>
</html><?php }}