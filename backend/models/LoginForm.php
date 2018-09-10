<?php
namespace backend\models;

use Yii;
use common\models\sys\Manager;

/**
 * Class LoginForm
 * @package backend\models
 */
class LoginForm extends \common\models\common\LoginForm
{
    public $verifyCode;

    /**
     * 默认登录失败3次显示验证码
     *
     * @var int
     */
    public $attempts = 3;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['password', 'validateIp'],
            ['verifyCode', 'captcha', 'on' => 'captchaRequired'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => '用户名',
            'rememberMe' => '记住我',
            'password' => '密码',
            'verifyCode' => '验证码',
        ];
    }

    /**
     * 验证ip地址是否正确
     *
     * @param $attribute
     */
    public function validateIp($attribute)
    {
        $ip = Yii::$app->request->userIP;
        $ipList = Yii::$app->debris->config('sys_allow_ip');
        if (!empty($ipList))
        {
            $value = explode(",", $ipList);
            if (!in_array($ip, $value))
            {
                // 记录行为日志
                Yii::$app->debris->log('login', '限制IP登录', false);

                $this->addError($attribute, '禁止登陆');
            }
        }
    }

    /**
     * @return mixed|null|static
     */
    public function getUser()
    {
        if ($this->_user === null)
        {
            $this->_user = Manager::findByUsername($this->username);
        }

        return $this->_user;
    }

    /**
     * 验证码显示判断
     */
    public function loginCaptchaRequired()
    {
        if (Yii::$app->session->get('loginCaptchaRequired') >= $this->attempts)
        {
            $this->setScenario("captchaRequired");
        }
    }

    /**
     * 登陆
     *
     * @return bool
     */
    public function login()
    {
        if ($this->validate() && Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0))
        {
            Yii::$app->session->remove('loginCaptchaRequired');

            return true;
        }

        // 记录行为日志
        Yii::$app->debris->log('login', '账号或者密码错误|用户名:' . $this->username, false);

        $counter = Yii::$app->session->get('loginCaptchaRequired') + 1;
        Yii::$app->session->set('loginCaptchaRequired', $counter);
        return false;
    }
}