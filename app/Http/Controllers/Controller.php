<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 根据 rules 从 data 中组合 where 条件
     * @param Builder $query
     * @param array $rules value 支持操作符,一维数组,闭包(需返回query)
     * @param array $data
     * @return Builder
     */
    public function buildConditions(Builder $query, array $rules, array $data): Builder
    {
        if (empty($rules) || empty($data)) {
            return $query;
        }
        $conditions = Collection::make();
        foreach ($rules as $column => $rule) {
            if (Arr::has($data, $column)) {
                //闭包, 传入 query, 自行处理
                if ($rule instanceof Closure) {
                    $query = $rule($query);
                    continue;
                }
                //指定的合法操作符,直接拼接 data 内对应的字符
                if (!is_array($rule)) {
                    if (!in_array($rule, $query->getQuery()->operators, true)) {
                        throw new InvalidArgumentException('Illegal operator');
                    }
                    $conditions->add([$column, $rule, Arr::get($data, $column)]);
                    continue;
                }
                //数组
                if (count($rule) === 2) { //默认拼上对应的取值
                    $rule[] = Arr::get($data, $column);
                }
                $conditions->add($rule);
            }
        }

        return $query->where($conditions->toArray());
    }

    /**
     * 组合with条件
     * @param Builder $query
     * @param string | array $fields
     * @param array $relationships
     * @param array $excepts 排除
     * @return Builder
     */
    public function buildRelationships(Builder $query, $fields, array $relationships, array $excepts = []): Builder
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        if (!is_array($fields)) {
            return $query;
        }
        $fields = array_diff($fields, $excepts);
        foreach ($fields as $field) {
            $query->with($relationships[$field]);
        }
        return $query;
    }

    /**
     * 批量添加规则
     * @param array $rules
     * @param array $adds
     * @return array
     */
    public function addRulesToAttributes(array $rules, array $adds): array
    {
        if (empty($adds)) {
            return $rules;
        }
        //原规则没有, 直接返回新增
        if (empty($rules)) {
            return $adds;
        }
        foreach ($adds as $key => $add) {
            //没有,直接新增
            if (!Arr::has($rules, $key)) {
                $rules = Arr::add($rules, $key, $add);
                continue;
            }
            $rule = Arr::get($rules, $key);
            //转换 rule
            $trans = static function ($rule) {
                if ($rule === null) {
                    return [];
                }
                if (is_string($rule)) {
                    $rule = explode('|', $rule);
                }
                return Arr::wrap($rule);
            };
            Arr::set($rules, $key, array_merge($trans($rule), $trans($add)));
        }
        return $rules;
    }

    /**
     * 添加 required 规则到指定的字段
     * @param array $rules
     * @param $attributes
     * @return array
     */
    public function addRequiredToAttributes(array $rules, $attributes): array
    {
        if (is_string($attributes)) {
            $attributes = explode(',', $attributes);
        }
        if (!is_array($attributes)) {
            throw new InvalidArgumentException('参数 attributes 不支持当前类型');
        }
        if (empty($attributes)) {
            return $rules;
        }
        return $this->addRulesToAttributes($rules, array_fill_keys($attributes, 'required'));
    }
}
