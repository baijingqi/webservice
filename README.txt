该项目在ThinkPHP3.2基础上扩展，用于快速发布webservice服务，在公司内部多个系统中使用一年多，至今未发现问题。

使用步骤：
1、复制以下代码至ThinkPHP目录下的ThinkPHP.php文件中
defined('WSDL_PATH')   or define('WSDL_PATH', 'WSDL');  //WSDL生成文件路径
defined('SERVER_MODULE')   or define('SERVER_MODULE', 'Server'); //webservice发布模块
defined('SERVER_IMPLEMENT_NAME')   or define('SERVER_IMPLEMENT_NAME', 'Implement'); //webservice实现控制器名称后缀

2、复制该项目的 WebService 目录至 项目的Vendor目录下

3、复制该项目的WebServiceController.class.php至 ThinkPHP/Library/Think/Controller 目录下

4、以上步骤完成之后，就可以发布webservice 服务了，在步骤1 定义的 SERVER_MODULE 模块下的Controller文件夹新建一个控制器，可以将 ExampleController.class.php复制进去，
打开浏览器访问webservice路径： http://localhost/你的项目/index.php/Server/Example?wsdl 就会看到生成的xml文档，这时候就可以用工具测试了

5、以上步骤已经可以正常发布webservice了，从 ExampleController中可以看到，文件中包含两个控制器，一个为注册方法，一个为具体实现。第一个控制器可以在浏览器中访问，第二个不可以，如果想要访问具体实现控制器 ExampleImplementController来调试的话，将以下方法替换至 ThinkPHP\Common\functions.php

/**
 * 用于实例化访问控制器
 * @param string $name 控制器名
 * @param string $path 控制器命名空间（路径）
 * @return Think\Controller|false
 */
function controller($name,$path=''){
    $layer  =   C('DEFAULT_C_LAYER');
    if(!C('APP_USE_NAMESPACE')){
        $class  =   parse_name($name, 1).$layer;
        import(MODULE_NAME.'/'.$layer.'/'.$class);
    }else{
        $class  =   ( $path ? basename(ADDON_PATH).'\\'.$path : MODULE_NAME ).'\\'.$layer;
        $array  =   explode('/',$name);
        foreach($array as $name){
            $class  .=   '\\'.parse_name($name, 1);
        }
        $class .=   $layer;
    }
    //2018-6-1，对webservice模块下的控制器单独实例化类文件
    if (SERVER_MODULE == MODULE_NAME) {
        $split = explode('\\', $class);
        $split[count($split) - 1] = str_replace(SERVER_IMPLEMENT_NAME, '', $split[count($split) - 1]);
        $file = APP_PATH.implode('\\', $split). EXT;
        if(file_exists($file)){
            require($file);
        }
    }
    if(class_exists($class)) {
        return new $class();
    }else {
        return false;
    }
}

6、部署到其他框架中也比较简单，需要修改的就是 WebServiceController.class.php 这个控制器基类

