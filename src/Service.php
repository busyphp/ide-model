<?php
declare(strict_types = 1);

namespace BusyPHP\ide\model;


use BusyPHP\ide\model\command\Model;

/**
 * Model ide helper
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/21 10:14 Service.php $
 */
class Service extends \think\Service
{
    public function boot() : void
    {
        $this->commands(Model::class);
    }
}