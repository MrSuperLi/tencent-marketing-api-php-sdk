<?php
/**
 * 查询参数构造器
 *
 * User: lizhicheng
 * Date: 2019/8/14
 * Time: 09:55
 */

namespace MrSuperLi\Tencent\Ads\Parameters;

use Doctrine\Common\Collections\ArrayCollection;

class Builder extends ArrayCollection
{

    protected $is_multipart = false;

    /**
     * 工厂函数
     *
     * @param array|Builder $params
     *
     * @return Builder
     */
    public static function make($params = []) {
        if ($params instanceof static) {
            return $params;
        }

        return new static($params);
    }

    /**
     * 排序，可以多次调用
     *
     * @param $field
     * @param string $sortType
     * @return $this
     */
    public function orderBy($field, $sortType = 'desc') {
        $key = 'order_by';

        $orderBy = $this->get($key) ?: [];

        $sortType = $sortType === 'desc' ? 'DESCENDING' : 'ASCENDING';

        $orderBy[] = [
            'sort_field' => $field,
            'sort_type' => $sortType,
        ];

        return $this->set($key, $orderBy);
    }

    /**
     * 只能调用一次, 多次会被覆盖
     *
     * @return $this
     */
    public function groupBy() {
        $key = 'group_by';

        $args = func_get_args();

        $argNum = count($args);

        if ($argNum === 1) {
            $this->set($key, is_array($args[0]) ? $args[0] : $args);
        } elseif ($argNum > 1) {
            $this->set($key, $args);
        }

        return $this;
    }

    /**
     * 设置account_id
     *
     * @param $account
     * @return Builder
     */
    public function setAccountId($account)
    {
        return $this->set('account_id', $account);
    }

    public function set($key, $value)
    {
        parent::set($key, $value);

        return $this;
    }

    /**
     * 设置表单数据
     *
     * @param $key
     * @param $value
     * @return Builder
     */
    public function multipart($key, $value)
    {
        $this->is_multipart = true;

        return $this->set($key, $value);
    }

    /**
     * @param $field
     * @param $file
     * @param $filename
     * @return $this
     */
    public function file($field, $file, $filename = '')
    {
        if (!$file) {
            return $this;
        }

        if (is_string($file)) {

            if (!$filename) {
                $filename = basename($filename);
            }

            $file = fopen($file, 'r');
        }

        if (is_resource($file)) {
            $this->multipart($field, [
                'name' => $field,
                'contents' => $file,
                'filename' => $filename,
            ]);
        }

        return $this;
    }

    public function setDateRange() {
        $key = 'date_range';

        $args = func_get_args();

        $argNum = count($args);

        if ($argNum === 1 && $args[0] instanceof DateRange) {
            return $this->set($key, $args[0]);
        }

        if ($argNum >= 2) {
            return $this->set($key, new DateRange($args[0], $args[1]));
        }

        throw new \InvalidArgumentException('参数不能为空');
    }

    public function setTimeRange() {
        $key = 'time_range';

        $args = func_get_args();

        $argNum = count($args);

        if ($argNum === 1 && $args[0] instanceof TimeRange) {
            return $this->set($key, $args[0]);
        }

        if ($argNum >= 2) {
            return $this->set($key, new TimeRange($args[0], $args[1]));
        }

        throw new \InvalidArgumentException('参数不能为空');
    }

    /**
     * @return Filter
     */
    public function getFilter() {
        $key = 'filtering';

        $filter = $this->get($key);

        if (! $filter) {
            $filter = new Filter();
            $this->set($key, $filter);
        }

        return $filter;
    }

    /**
     * 生成表单数据
     *
     * @return array
     */
    protected function toMultipart()
    {
        $results = [];
        foreach (parent::toArray() as $field => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $results[] = [
                    'name' => $field,
                    'contents' => json_encode($value->toArray()),
                ];
            } else {
                if (is_array($value) && isset($value['name']) && isset($value['contents'])) {
                    $field = $value['name'];
                    $value = $value['contents'];
                }
                
                $results[] = [
                    'name' => $field,
                    'contents' => is_array($value) ? json_encode($value) : $value,
                ];
            }
        }

        return $results;
    }

    /**
     * 生成普通数组
     * 
     * 数组类型的值需要进行json_encode
     *
     * @return array
     */
    protected function _toArray()
    {
        return array_map(function ($value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            return is_array($value) ? json_encode($value) : $value;
        }, parent::toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        if ($this->isMultipart()) {
            return $this->toMultipart();
        } else {
            return $this->_toArray();
        }
    }

    /**
     * 数据类型是否为表单类型
     *
     * @return bool
     */
    public function isMultipart()
    {
        return $this->is_multipart;
    }

}