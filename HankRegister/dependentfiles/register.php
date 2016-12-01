<?php
/**
 * 自定义登录页面
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
// 获取所有插件
$all = Typecho_Plugin::export();
// 插件激活且开启注册才能进入该页面
if (!array_key_exists('HankRegister', $all['activated']) || (Helper::options()->plugin('HankRegister')->openRegister) != '1') {
    $this->response->redirect($this->options->index);
}

// 自定义
Typecho_Widget::widget('Widget_Options')->to($options);
list($prefixVersion, $suffixVersion) = explode('/', $options->version);
$header = '<link rel="stylesheet" href="' . Typecho_Common::url('normalize.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<link rel="stylesheet" href="' . Typecho_Common::url('grid.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<link rel="stylesheet" href="' . Typecho_Common::url('style.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<!--[if lt IE 9]>
<script src="' . Typecho_Common::url('html5shiv.js?v=' . $suffixVersion, $options->adminStaticUrl('js')) . '"></script>
<script src="' . Typecho_Common::url('respond.js?v=' . $suffixVersion, $options->adminStaticUrl('js')) . '"></script>
<![endif]-->';

/** 注册一个初始化插件 */
$header = Typecho_Plugin::factory('admin/header.php')->header($header);


?>
<!DOCTYPE HTML>
<html class="no-js">
<head>
    <meta charset="<?php $options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hank_Register</title>
    <meta name="robots" content="noindex, nofollow">
    <?php echo $header; ?>
</head>
<body<?php if (isset($bodyClass)) {
    echo ' class="' . $bodyClass . '"';
} ?>>
<!--[if lt IE 9]>
<div class="message error browsehappy" role="dialog"><?php _e('当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请 <a
    href="http://browsehappy.com/">升级你的浏览器</a>'); ?>.
</div>
<![endif]-->
<div class="main">
    <div class="body container">
        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-6 col-tb-offset-3">
                <?php Typecho_Widget::widget('HankRegister_Action')->form()->render(); ?>
            </div>
        </div>
    </div>
</div>