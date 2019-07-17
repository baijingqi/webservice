<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Controller;
/**
 * ThinkPHP WebService控制器类
 */
class WebServiceController {

    /**
     * 架构函数
     * @date 2018-05-29
     * @author baijingqi
     * @throws \Exception
     */
    public function __construct() {
        //导入类文件
        Vendor('WebService.SoapDiscovery');
        $serverController = MODULE_NAME.'\Controller\\'.CONTROLLER_NAME.'Controller';
        $implementController = MODULE_NAME.'\Controller\\'.CONTROLLER_NAME.SERVER_IMPLEMENT_NAME.'Controller';
        if(!class_exists($implementController)) E('Implement Controller'.$implementController .' does not exist', '500');
        $this->checkMethods($serverController, $implementController);

        //生成wsdl文件
        $serviceUrl = U(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, '','', true);

        //DEBUG模式下每次都重新写入wsdl，部署阶段文件不存在时才会重新生成wsdl
        $wsdlFilePath = WSDL_PATH.'/'.CONTROLLER_NAME.'.wsdl';
        if(APP_DEBUG == true || !file_exists($wsdlFilePath)){
            $this->buildWsdl($serverController, CONTROLLER_NAME, $serviceUrl);
        }

        //注册具体方法
        $wsdlLocate = $_SERVER['REQUEST_SCHEME']. '://'.$_SERVER['HTTP_HOST'].__ROOT__.'/'.WSDL_PATH.'/'.CONTROLLER_NAME.'.wsdl';
        $server = new \SoapServer($wsdlLocate);
        $server->setClass($implementController);
        $server->handle();
    }

    /**
     * 检测方法
     * @param $serverController
     * @param $implementController
     * @date 2018-05-29
     * @author baijingqi
     */
    private function checkMethods($serverController, $implementController){
        $serverClass = new \ReflectionClass($serverController);
        $serverMethods = $serverClass->getMethods();

        $implementClass = new \ReflectionClass($implementController);
        $implementMethods = $implementClass->getMethods();

        $initImplementMethods = [];
        foreach($implementMethods as $method){
            if($method->class == $implementController) $initImplementMethods[] = $method->name;
        }

        $initServerMethods = [];
        foreach($serverMethods as $method){
            if($method->class == $serverController) $initServerMethods[] = $method->name;
        }

        //如果有方法未实现，则抛出异常
        foreach($initServerMethods as $method){
            if(!in_array($method, $initImplementMethods)) E('Method '.$serverController.'::'. $method.'() does not exist', '500');
        }
    }

    /**
     * 生成wsdl文件
     * @param $controller
     * @param $serverName
     * @param $serviceUrl
     * @throws \Exception
     */
    protected function buildWsdl($controller, $serverName, $serviceUrl){
        $disco = new \SoapDiscovery($controller, $serverName, $serviceUrl);
        $disco->getWSDL();
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method,$args){}

}
