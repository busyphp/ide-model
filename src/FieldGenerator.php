<?php
declare(strict_types = 1);

namespace BusyPHP\ide\model;

use BusyPHP\helper\FilterHelper;
use BusyPHP\helper\StringHelper;
use BusyPHP\ide\generator\Argument;
use BusyPHP\ide\generator\Generator;
use BusyPHP\Model;
use BusyPHP\model\Entity;
use BusyPHP\model\Field;
use think\Container;
use think\validate\ValidateRule;

/**
 * FieldGenerator
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/21 20:28 FieldGenerator.php $
 */
class FieldGenerator extends Generator
{
    /** @var string 常用方法属性 */
    public const TYPE_COMMON = 'common';
    
    /** @var string get[Field]方法 */
    public const TYPE_GET = 'getter';
    
    /**
     * 字段类
     * @var Field
     */
    protected Field $field;
    
    /**
     * 模型类
     * @var Model
     */
    protected Model $model;
    
    /**
     * 模型字段集合
     * @var array<int,array{name: string, studly: string, camel: string, type: string, comment: string}>
     */
    protected array $fields;
    
    /**
     * 限制
     * @var bool|array
     */
    protected bool|array $limit = true;
    
    
    /**
     * 获取字段类
     * @return Field
     */
    public function getField() : Field
    {
        return $this->field;
    }
    
    
    /**
     * 获取模型类
     * @return Model
     */
    public function getModel() : Model
    {
        return $this->model;
    }
    
    
    /**
     * 设置生成限制
     * @param string|bool $limit true则只生成常用方法或属性，false生成全部方法或属性，array则只生成指定的方法或属性
     * @return static
     */
    public function setLimit(bool|array|string $limit) : static
    {
        if (is_string($limit)) {
            $limit = (array) $limit;
        }
        if (is_array($limit)) {
            $limit = array_map(function($item) {
                return strtolower(StringHelper::camel((string) $item));
            }, FilterHelper::trimArray($limit));
        }
        
        $this->limit = $limit;
        
        return $this;
    }
    
    
    /**
     * 检测是否可以生成该方法或属性
     * @param string $type
     * @param bool   $common
     * @return bool
     */
    protected function check(string $type, bool $common = false) : bool
    {
        if ($common || false === $this->limit) {
            return true;
        }
        
        if (is_array($this->limit) && $this->limit && in_array($type, $this->limit)) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * @inheritdoc
     */
    protected function handle() : void
    {
        $this->field  = new $this->class;
        $this->model  = Container::getInstance()->make($this->field::getModelClass(), [], true);
        $this->fields = ModelGenerator::getFields($this->model);
        
        $this->buildFieldProperties();
        $this->buildDocEntityStaticMethod();
        $this->buildDocSetterMethod();
        $this->buildDocGetterMethod();
    }
    
    
    /**
     * 生成真实字段属性
     * @return void
     */
    protected function buildFieldProperties() : void
    {
        if (!$this->check(self::TYPE_COMMON, true)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addProperty(StringHelper::camel($field['name']), $field['type'], null, false, $field['comment']);
        }
    }
    
    
    /**
     * 构建 $field->setField() 方法
     * @return void
     */
    protected function buildDocGetterMethod() : void
    {
        if (!$this->check(self::TYPE_GET)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'get' . $field['studly'],
                [],
                $field['type'],
                false,
                sprintf('获取%s', $field['comment'])
            );
        }
    }
    
    
    /**
     * 构建 $field->setField() 方法
     * @return void
     */
    protected function buildDocSetterMethod() : void
    {
        if (!$this->check(self::TYPE_COMMON, true)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'set' . $field['studly'],
                [
                    new Argument(StringHelper::camel($field['name']),  'mixed'),
                    new Argument('validate', [ValidateRule::class . '[]', 'bool'], 'false')
                ],
                '$this',
                false,
                sprintf('设置%s', $field['comment'])
            );
        }
    }
    
    
    /**
     * 构建 field::field() 实体方法
     * @return void
     */
    protected function buildDocEntityStaticMethod() : void
    {
        if (!$this->check(self::TYPE_COMMON, true)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                $field['camel'],
                [
                    new Argument('op', 'mixed', 'null'),
                    new Argument('condition', 'mixed', 'null'),
                ],
                Entity::class,
                true,
                $field['comment']
            );
        }
    }
}