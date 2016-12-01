<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * HankRegister：切换模板请重装插件！！！
 *
 * @package HankRegister
 * @author Hank_Yan
 * @version 0.5.0
 * @link http://typecho.org
 */
class HankRegister_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // register_rd： 后台插入数据的接口（路由）
        Helper::addRoute('register_rd', '/register', 'HankRegister_Action', 'action');
        // register_fe： 0.1 版本里面的前台注册页，已废弃，改用独立页面做注册页
        Helper::addRoute('register_fe', '/register.html', 'HankRegister_Action', 'render');
        $msg = self::install();
        return $msg;
    }

    /**
     * 开始安装
     * @return string
     */
    public static function install()
    {
        // 先把文件放到该放的位置
        list($status, $fileCopyMsg) = self::copyFileToTemp();
        if (!$status) {
            // 文件复制出错
            return $fileCopyMsg;
        }

        // 文件就绪，再操作数据库
        try {
            return self::addRecord();
        } catch (Typecho_Db_Exception $e) {
            $msg = '数据库更新出错!';
            return $msg;
        }
    }

    /**
     * 添加注册页面记录
     * 如果存在先删除，后插入。插入的这条记录在独立页面中可以看到，不要动
     * @return string
     */
    public static function addRecord()
    {
        $db = Typecho_Db::get();
        if (!self::deleteIfExist()) {
            return "数据已存在，删除时出现错误";
        }
        // 调用内置日期类来获取时间戳
        $created = Typecho_Date::gmtTime();
        // TE 使用原生sql 操作数据库, 直接调用 query 方法即可
        // 拼接插入语句
        $sql = "INSERT INTO `typecho_contents` VALUES 
                  (NULL, 
                  '注册',
                  'register',
                   '" . $created . "', 
                   '" . $created . "', 
                   '<!--markdown-->', 
                   '0',
                    '1', 
                    'register.php',
                   'page', 
                   'hidden', 
                   null, 
                   '0', '0', '0', '0', '0');";
        $db->query($sql);
        return '插件安装成功！请进入设置填写是否开放注册，默认不开放注册';
    }

    /**
     * 如果数据库存在热定字段则删除
     * @return bool true 执行成功没有发生异常，false执行失败发生异常
     */
    public static function deleteIfExist()
    {
        $db = Typecho_Db::get();

        $select = $db->select('cid')->from('table.contents')->where('slug = ?', 'register');
        $result = $db->fetchAll($select);
        if (count($result) > 0) {
            // 说明已经存在了，先把该栏目删除，后面重建
            // TE 调用DB 类来进行数据库操作，后面演示调用原生sql 进行操作
            $delete = $db->delete('table.contents')->where('slug = ?', 'register');
            //将构建好的sql执行, 会自动返回已经删除的记录数
            try {
                $deletedRows = $db->query($delete);
                return true;
            } catch (Typecho_Db_Exception $e) {
                return false;
            }
        }
        // 不存在不用删除直接返回 true, 操作成功
        return true;
    }


    /**
     * 将依赖文件复制到当前模板文件夹下面
     */
    public static function copyFileToTemp()
    {
        $themeName = self::getThemeName();

        $options = Helper::options();
        $themePath = $options->themeFile($themeName);
        $destPath = $themePath . 'register.php';
        $dependFilePath = __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR
            . 'usr'
            . DIRECTORY_SEPARATOR . 'plugins'
            . DIRECTORY_SEPARATOR . 'HankRegister'
            . DIRECTORY_SEPARATOR . 'dependentfiles'
            . DIRECTORY_SEPARATOR . 'register.php';
        if (!file_exists($dependFilePath)) {
            // 插件没有安装
            return array(false, '插件没有安装');
        }

        if (file_exists($destPath)) {
            @unlink($destPath);
        }
        if (!copy($dependFilePath, $destPath)) {
            // 复制文件出错
            return array(false, '复制文件出错');
        }
        return array(true, '文件复制成功');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        // 取消路由
        Helper::removeRoute("register_fe");
        Helper::removeRoute("register_rd");
        $msg = self::uninstall();
        return $msg . ' 插件卸载成功';
    }

    /**
     * 开始卸载
     * @return mixed
     */
    public static function uninstall()
    {
        self::deleteFileFromTemp();

        if (!self::deleteIfExist()) {
            return "删除数据表失败，请手工到 contents 表中删除 slug=register的记录！";
        } else {
            return "数据库更新成功！";
        }
    }

    /**
     * 将依赖文件从当前模板文件夹下面删除
     */
    public static function deleteFileFromTemp()
    {
        $themeName = self::getThemeName();

        $options = Helper::options();
        $themePath = $options->themeFile($themeName);
        $destPath = $themePath . 'register.php';
        // 存在删除
        if (file_exists($destPath)) {
            @unlink($destPath);
        }
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $element = new Typecho_Widget_Helper_Form_Element_Radio('openRegister', array(0 => '关闭', 1 => '开放'), 0, _t('是否开放注册功能？？？'));
        $form->addInput($element);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
    }

    /**
     * 辅助方法：获取模板名
     */
    public static function getThemeName()
    {
        // 得到当前使用的模板名称，options 里面的参数不让用,郁闷
        $db = Typecho_Db::get();
        $select = $db->select('value')->from('table.options')->where('name = ?', 'theme');
        return $db->fetchAll($select)[0]['value'];
    }

}
