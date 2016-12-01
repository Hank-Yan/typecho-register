<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Created by PhpStorm.
 * User: Hank-Yan
 * Date: 2016/11/30
 * Time: 21:18
 */
class HankRegister_Action extends Widget_Abstract_Users implements Widget_Interface_Do
{
    /**
     * 安全模块
     *
     * @var Widget_Security
     */
    protected $security;

    /**
     * 全局选项
     *
     * @access protected
     * @var Widget_Options
     */
    protected $options;

    /**
     * 用户对象
     *
     * @access protected
     * @var Widget_User
     */
    protected $user;

    /**
     * 数据库对象
     *
     * @access protected
     * @var Typecho_Db
     */
    protected $db;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Typecho_Db::get();

        /** 初始化必备组件 */
        $this->options = $this->widget('Widget_Options');
        $this->user = $this->widget('Widget_User');
        $this->security = $this->widget('Widget_Security');
        // 收工设置一下安全验证
        $request->setParam('_', $this->security->getToken($this->request->getReferer()));
        //设置时区，否则文章的发布时间会查8H
        date_default_timezone_set('PRC');

    }

    /**
     * 接口需要实现的入口函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);

        /** 取出数据 */
        $user = $this->request->from('name', 'mail', 'password');
        $user['screenName'] = $user['name'];
        $user['password'] = $hasher->HashPassword($user['password']);
        $user['created'] = $this->options->gmtTime;
        $user['group'] = 'contributor';

        /** 插入数据 */
        $user['uid'] = $this->insert($user);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('用户 %s 已注册成功', $user['screenName']), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('index.php', $this->options->adminUrl));

    }

    /**
     * 0.1 版本使用 render 路由，直接渲染一个前台页面 regist.html
     * 0.5 版本不用render 这种方式，因为前台页面无法定制，改用独立页面，VC 分离，方便根据自己喜好改样式
     * 废弃
     */
    public function render()
    {
        // $this->form()->render();
        echo '这是假的注册页面~~ 0.1版想通过render 渲染，结果发现没办法对注册页面更加个性化定制（比如想更换一下页面头，或者更换一下CSS发现非常困难），所以废弃了，改用定制独立页面的方式';
    }

    /**
     * 使用内置表单类来定制表单
     * 由于内置表单类默认是需要登录的，会验证session, 所以这里有一个notice 错误，在前台模板里面屏蔽
     * @return Typecho_Widget_Helper_Form
     */
    public function form()
    {
        /** 构建表单 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/register'),
            Typecho_Widget_Helper_Form::POST_METHOD);

        /** 用户名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('用户名 *'), _t('此用户名将作为用户登录时所用的名称.')
            . '<br />' . _t('请不要与系统中现有的用户名重复.'));
        $form->addInput($name);

        /** 电子邮箱地址 */
        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('电子邮箱地址 *'), _t('电子邮箱地址将作为此用户的主要联系方式.')
            . '<br />' . _t('请不要与系统中现有的电子邮箱地址重复.'));
        $form->addInput($mail);

        /** 用户密码 */
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('用户密码'), _t('为此用户分配一个密码.')
            . '<br />' . _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** 用户密码确认 */
        $confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm', NULL, NULL, _t('用户密码确认'), _t('请确认你的密码, 与上面输入的密码保持一致.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $submit->value(_t('立即注册'));

        $mail->addRule('required', _t('必须填写电子邮箱'));
        $mail->addRule(array($this, 'mailExists'), _t('电子邮箱地址已经存在'));
        $mail->addRule('email', _t('电子邮箱格式错误'));
        $password->addRule('minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
        $confirm->addRule('confirm', _t('两次输入的密码不一致'), 'password');
        $name->addRule('required', _t('必须填写用户名称'));
        $name->addRule('xssCheck', _t('请不要在用户名中使用特殊字符'));
        $name->addRule(array($this, 'nameExists'), _t('用户名已经存在'));
        $password->label(_t('用户密码 *'));
        $confirm->label(_t('用户密码确认 *'));
        $password->addRule('required', _t('必须填写密码'));

        return $form;
    }
}