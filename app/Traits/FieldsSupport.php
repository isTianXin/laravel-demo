<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use InvalidArgumentException;

trait FieldsSupport
{
    /**
     * 提供的额外字段(二维数组)
     * 键为字段名
     * 值为数组,其中
     * key 为返回结果数组的key, 未指定时使用字段名
     * method 为要调用的方法名，
     * parameters 为 method 对应的参数组成的数组(可以不写),
     * method 和 parameters 参考 call_user_func_array 的两个参数
     * @var array
     * @example ['board' => ['key' => 'boards', 'method' => [$this, 'getBoard'], 'parameters' => []],
     *  返回 ['boards' => result]
     * @see https://www.php.net/manual/en/function.call-user-func-array.php
     */
    protected array $fields = [];

    /**
     * @var string 字段名称
     */
    protected string $fields_name = 'fields';

    /**
     * 从 request 中获取 fields
     * @param Request $request
     * @return array
     */
    protected function getFieldsFromRequest(Request $request): array
    {
        $fields = $request->input($this->fields_name);
        if (is_array($fields)) {
            return $fields;
        }
        if (is_string($fields) || is_numeric($fields)) {
            return explode(',', (string)$fields);
        }
        return [];
    }

    /**
     * 获取支持的 fields
     * @param array $fields
     * @return array
     */
    protected function getSupportedFields(array $fields): array
    {
        return Arr::only($this->fields, $fields);
    }

    /**
     * 从 request 中获取支持的 fields
     * @param Request $request
     * @return array
     */
    protected function getSupportedFieldsFromRequest($request): array
    {
        return $this->getSupportedFields($this->getFieldsFromRequest($request));
    }

    /**
     * 获取额外字段
     * @param Request $request
     * @return array
     */
    protected function getFields($request): array
    {
        if (method_exists($this, 'packageFields')) {
            $this->packageFields();
        }
        //支持的额外字段
        $support_fields = $this->getSupportedFieldsFromRequest($request);
        if (!$support_fields) {
            return [];
        }
        foreach ($support_fields as $field => $callable) {
            if (!is_array($callable) || !array_key_exists('method', $callable)) {
                throw new InvalidArgumentException('callable should be an array with key method');
            }
            unset($support_fields[$field]);
            $key = $callable['key'] ?? $field;
            //调用对应的方法
            $support_fields[$key] = call_user_func_array(
                $callable['method'],
                Arr::wrap($callable['parameters'] ?? [])
            );
        }
        return $support_fields;
    }
}
