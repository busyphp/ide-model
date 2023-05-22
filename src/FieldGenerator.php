<?php
declare(strict_types = 1);

namespace BusyPHP\ide\model;

use BusyPHP\helper\StringHelper;
use BusyPHP\ide\generator\Argument;
use BusyPHP\ide\generator\Generator;
use BusyPHP\Model;
use BusyPHP\model\Entity;
use BusyPHP\model\Field;
use think\Container;

/**
 * FieldGenerator
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/21 20:28 FieldGenerator.php $
 */
class FieldGenerator extends Generator
{
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
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'set' . $field['studly'],
                [
                    new Argument($field['name'], [$field['type'], 'mixed'])
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