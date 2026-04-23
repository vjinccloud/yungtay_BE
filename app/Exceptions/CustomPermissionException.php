<?php

namespace App\Exceptions;

use Exception;
use Inertia\Inertia;
use App\Traits\CommonTrait;
class CustomPermissionException extends Exception
{
    use CommonTrait;
    protected $component;
    // 可以添加自定义的构造函数、属性或方法
    public function __construct($message = "無此權限", $component = 'ErrorPage', $code = 0, Exception $previous = null) {
        $this->component = $component;
        parent::__construct($message, $code, $previous);
    }

    // 可以添加自定义的方法来处理这个异常
    public function report() {
        // 记录日志、发送通知等
    }

    public function render($request) {
        $result =$this->ReturnHandle(false,$this->getMessage());
        return Inertia::render($this->component,compact('result'));
    }
}