<?php

namespace MyController\SSOServer;

/**
 * Validation result
 */
class ValidationResult extends \Jasny\ValidationResult
{
    /**
     * 需要随着验证结果返回的额外数据
     *
     * @var mixed
     */
    protected $returnData = null;


    /**
     * Set the validation returnData
     *
     * @param mixed  $data
     * @return static
     */
    public function setReturnData($data)
    {
        $this->returnData = $data;
        return $this;
    }


    /**
     * Get the validation returnData
     *
     * @return mixed
     */
    public function getReturnData()
    {
        return $this->returnData;
    }
}
