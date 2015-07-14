<?php

namespace stat\Web;

use tourze\Base\Config;

class Setting extends Base
{

    public function run()
    {
        $act = isset($_GET['act'])? $_GET['act'] : 'home';
        $err_msg = $notice_msg = $suc_msg = $ip_list_str = '';
        switch($act)
        {
            case 'save':
                if(empty($_POST['detect_port']))
                {
                    $err_msg = "探测端口不能为空";
                    break;
                }
                $detect_port = (int)$_POST['detect_port'];

                if($detect_port<0 || $detect_port > 65535)
                {
                    $err_msg = "探测端口不合法";
                    break;
                }
                $suc_msg = "保存成功";
                Config::load('statServer')->set('providerPort', $detect_port);
                $this->saveDetectPortToCache();
                break;
            default:
                $detect_port = Config::load('statServer')->get('providerPort');
        }

        include ROOT_PATH . '/view/header.tpl.php';
        include ROOT_PATH . '/view/setting.tpl.php';
        include ROOT_PATH . '/view/footer.tpl.php';
    }

    public function saveDetectPortToCache()
    {
        foreach(glob(Config::load('statServer')->get('configCachePath') . '*detect_port.cache.php') as $php_file)
        {
            unlink($php_file);
        }
        file_put_contents(Config::load('statServer')->get('configCachePath') . time().'.detect_port.cache.php', "<?php\n\\stat\\Cache::\$ProviderPort=".var_export(Config::load('statServer')->get('providerPort'), true).';');
    }

}
