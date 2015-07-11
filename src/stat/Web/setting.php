<?php

namespace stat\Web;

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
                \Statistics\Config::$ProviderPort = $detect_port;
                $this->saveDetectPortToCache();
                break;
            default:
                $detect_port = \Statistics\Config::$ProviderPort;
        }

        include ROOT_PATH . '/view/header.tpl.php';
        include ROOT_PATH . '/view/setting.tpl.php';
        include ROOT_PATH . '/view/footer.tpl.php';
    }

    public function saveDetectPortToCache()
    {
        foreach(glob(ST_ROOT . '/Config/Cache/*detect_port.cache.php') as $php_file)
        {
            unlink($php_file);
        }
        file_put_contents(ST_ROOT . '/Config/Cache/'.time().'.detect_port.cache.php', "<?php\n\\Statistics\\Config::\$ProviderPort=".var_export(\Statistics\Config::$ProviderPort,true).';');
    }

}
