<?php
class SoapDiscovery{

    private $class_name ='';
    private $service_name = '';
    private $wsdl_dir = WSDL_PATH;

    public function  __construct($class_name = '', $service_name = '', $service_url = ''){
        $this->class_name = $class_name;
        $this->service_name = $service_name;

        if(empty($service_url)){
            $this->service_url = $_SERVER['REQUEST_SCHEME'].'://'. $_SERVER['SERVER_NAME'].':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        }else{
            $this->service_url = $service_url;
        }
    }

    public function getWSDL(){
        if(empty($this->service_name)){
            throw new Exception('NO service name.');
        }
        $headerWSDL = "<?xml version = \"1.0\" ?> \n";
        $headerWSDL .= "<definitions name=\"$this->service_name\" targetNamespace=\"urn:$this->service_name\" xmlns:wsdl=\"http://schemas.xmlsoap.org/wsdl/\" xmlns:soap=\"http://schemas.xmlsoap.org/wsdl/soap/\" xmlns:tns=\"urn:$this->service_name\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns=\"http://schemas.xmlsoap.org/wsdl/\">\n";
        $headerWSDL .= "<types xmlns=\"http://schemas.xmlsoap.org/wsdl/\" />\n";
        if(empty($this->class_name)){
            throw new Exception('NO class name.');
        }

        $class = new ReflectionClass($this->class_name);
        if(!$class->isInstantiable()){
            throw new Exception('Class is not instantiable.');
        }

        $methods = $class->getMethods();
        $portTypeWSDL = '<portType name="'.$this->service_name.'Port">';

        $bindingWSDL = '<binding name="'.$this->service_name.'Binding" type="tns:'.$this->service_name."Port\">\n<soap:binding style=\"rpc\" transport=\"http://schemas.xmlsoap.org/soap/http\" />\n";

        $serviceWSDL = '<service name="'.$this->service_name."\">\n<documentation />\n<port name=\"".$this->service_name.'Port" binding="tns:'.$this->service_name."Binding\"><soap:address location=\"".$this->service_url."\" />\n</port>\n</service>\n";

        $messageWSDL = '';

        foreach($methods as $method){
            if($method->isPublic() && !$method->isConstructor() && strpos( $method,'__')==false){
                $portTypeWSDL.= '<operation name="'.$method->getName()."\">\n".'<input message="tns:'.$method->getName()."Request\" />\n<output message=\"tns:".$method->getName()."Response\" />\n</operation>\n";

                $bindingWSDL .= '<operation name="'.$method->getName()."\">\n".'<soap:operation soapAction="urn:'.$this->service_name.'#'.$this->class_name.'#'.$method->getName()."\" />\n<input><soap:body use=\"encoded\" namespace=\"urn:$this->service_name\" encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" />\n</input>\n<output>\n<soap:body use=\"encoded\" namespace=\"urn:$this->service_name\" encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" />\n</output>\n</operation>\n";

                $messageWSDL .= '<message name="'.$method->getName()."Request\">\n";
                $parameters = $method->getParameters();
                foreach($parameters as $parameter){
                    $messageWSDL .= '<part name="'.$parameter->getName()."\" type=\"xsd:string\" />\n";
                }
                $messageWSDL .= "</message>\n";
                $messageWSDL .= '<message name="'.$method->getName()."Response\">\n";
                $messageWSDL .= '<part name="'.$method->getName()."\" type=\"xsd:string\" />\n";
                $messageWSDL .= "</message>\n";
            }
        }
        $portTypeWSDL .= "</portType>\n";
        $bindingWSDL .= "</binding>\n";
        $str =  sprintf('%s%s%s%s%s%s', $headerWSDL, $portTypeWSDL, $bindingWSDL, $serviceWSDL, $messageWSDL, '</definitions>');

        $wsldDir = $this->wsdl_dir;
        if(!file_exists( $wsldDir)){
            mkdir($wsldDir);
        }
        $handle = fopen($wsldDir.'/'.$this->service_name.'.wsdl', 'w+');
        fwrite($handle, $str);
        fclose($handle);
    }

    public function getDiscovery(){
        return "<?xml version=\"1.0\" ?>\n<disco:discovery xmlns:disco=\"http://schemas.xmlsoap.org/disco/\" xmlns:scl=\"http://schemas.xmlsoap.org/disco/scl/\">\n<scl:contractRef ref=\"".$this->service_url."?wsdl\" />\n</disco:discovery>";
    }



}