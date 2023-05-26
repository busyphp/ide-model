<?php
declare(strict_types = 1);

namespace BusyPHP\ide\model;

use BusyPHP\exception\ClassNotExtendsException;
use BusyPHP\helper\FilterHelper;
use BusyPHP\helper\StringHelper;
use BusyPHP\ide\generator\Argument;
use BusyPHP\ide\generator\Generator;
use BusyPHP\Model;
use BusyPHP\model\Entity;
use ReflectionException;
use think\Container;

class ModelGenerator extends Generator
{
    /** @var string 常用方法属性 */
    public const TYPE_COMMON = 'common';
    
    /** @var string getBy[Field]方法 */
    public const TYPE_GET_BY = 'getby';
    
    /** @var string get[Field]By[Field]方法 */
    public const TYPE_GET_FIELD_BY = 'getfieldby';
    
    /** @var string whereOr[Field] 方法 */
    public const TYPE_WHERE_OR = 'whereor';
    
    /** @var string where[Field] 方法 */
    public const TYPE_WHERE = 'where';
    
    /** @var string getInfoBy[Field] 方法 */
    public const TYPE_GET_INFO_BY = 'getinfoby';
    
    /** @var string findInfoBy[Field] 方法 */
    public const TYPE_FIND_INFO_BY = 'findinfoby';
    
    /**
     * @var Model
     */
    protected Model $model;
    
    /**
     * 数据表字段
     * @var array<int,array{name: string, studly: string, camel: string, type: string, comment: string}>
     */
    protected array $fields = [];
    
    /**
     * 主键字段名称
     * @var string
     */
    protected string $pk = 'id';
    
    /**
     * 主键类型
     * @var string
     */
    protected string $pkType = 'mixed';
    
    /**
     * 限制
     * @var bool|array
     */
    protected bool|array $limit = true;
    
    
    /**
     * 获取Model
     * @return Model
     */
    public function getModel() : Model
    {
        return $this->model;
    }
    
    
    /**
     * @return string
     */
    public function getPk() : string
    {
        return $this->pk;
    }
    
    
    /**
     * @return string
     */
    public function getPkType() : string
    {
        return $this->pkType;
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
     * @throws ReflectionException
     */
    protected function handle() : void
    {
        if (!is_subclass_of($this->class, Model::class)) {
            throw new ClassNotExtendsException($this->class, Model::class);
        }
        
        // 实例化模型
        $this->model  = Container::getInstance()->make($this->class, [], true);
        $this->fields = [];
        $this->pkType = 'mixed';
        $this->pk     = $this->model->getPk();
        $this->fields = static::getFields($this->model);
        foreach ($this->fields as $field) {
            if ($this->pk === $field['name']) {
                $this->pkType = $field['type'];
            }
        }
        
        $this->buildDocGetByMethod();
        $this->buildDocGetFieldByMethod();
        $this->buildDocWhereOrMethod();
        $this->buildDocWhereMethod();
        $this->buildDocGetInfoByMethod();
        $this->buildDocFindInfoByMethod();
        $this->buildDocCommonMethod();
    }
    
    
    /**
     * 构建 $model->getByField() 方法
     * @return void
     */
    protected function buildDocGetByMethod() : void
    {
        if (!$this->check(self::TYPE_GET_BY)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'getBy' . $field['studly'],
                [
                    new Argument($field['camel'], $field['type'])
                ],
                ['array', 'null']
            );
        }
    }
    
    
    /**
     * 构建 $model->getFieldByField() 方法
     * @return void
     */
    protected function buildDocGetFieldByMethod() : void
    {
        if (!$this->check(self::TYPE_GET_FIELD_BY)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'getFieldBy' . $field['studly'],
                [
                    new Argument($field['camel'], $field['type']),
                    new Argument('field', ['string', Entity::class]),
                    new Argument('default', 'mixed', 'null')
                ],
                'mixed'
            );
        }
    }
    
    
    /**
     * 构建 $model->whereOrField() 方法
     * @return void
     */
    protected function buildDocWhereOrMethod() : void
    {
        if (!$this->check(self::TYPE_WHERE_OR)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'whereOr' . $field['studly'],
                [
                    new Argument('op', 'mixed'),
                    new Argument('condition', 'mixed', 'null'),
                    new Argument('bind', 'array', '[]'),
                ],
                '$this'
            );
        }
    }
    
    
    /**
     * 构建 $model->whereField() 方法
     * @return void
     */
    protected function buildDocWhereMethod() : void
    {
        if (!$this->check(self::TYPE_WHERE)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            $this->addDocMethod(
                'where' . $field['studly'],
                [
                    new Argument('op', 'mixed'),
                    new Argument('condition', 'mixed', 'null'),
                    new Argument('bind', 'array', '[]'),
                ],
                '$this'
            );
        }
    }
    
    
    /**
     * 构建 $model->getInfoByField() 方法
     * @return void
     */
    protected function buildDocGetInfoByMethod() : void
    {
        if (!$this->check(self::TYPE_GET_INFO_BY)) {
            return;
        }
        
        if (!$fieldClass = $this->model->getFieldClass(false)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            if ($field['name'] == $this->pk) {
                continue;
            }
            
            $this->addDocMethod(
                'getInfoBy' . $field['studly'],
                [
                    new Argument($field['camel'], $field['type']),
                    new Argument('notFoundMessage', 'string', "''")
                ],
                $fieldClass
            );
        }
    }
    
    
    /**
     * 构建 $model->findInfoByField() 方法
     * @return void
     */
    protected function buildDocFindInfoByMethod() : void
    {
        if (!$this->check(self::TYPE_FIND_INFO_BY)) {
            return;
        }
        
        if (!$fieldClass = $this->model->getFieldClass(false)) {
            return;
        }
        
        foreach ($this->fields as $field) {
            if ($field['name'] == $this->pk) {
                continue;
            }
            
            $this->addDocMethod(
                'findInfoBy' . $field['studly'],
                [
                    new Argument($field['camel'], [$field['type'], 'null']),
                    new Argument('notFoundMessage', 'string', "''")
                ],
                $fieldClass,
            );
        }
    }
    
    
    /**
     * 构建通用
     * @return void
     * @throws ReflectionException
     */
    protected function buildDocCommonMethod() : void
    {
        if (!$this->check(self::TYPE_COMMON, true)) {
            return;
        }
        
        if (!$fieldClass = $this->model->getFieldClass(false)) {
            return;
        }
        
        $this->addDocMethod(
            'getInfo',
            [
                new Argument($this->pk, $this->pkType),
                new Argument('notFoundMessage', 'string', "''")
            ],
            $fieldClass
        );
        $this->addDocMethod(
            'findInfo',
            [
                new Argument($this->pk, [$this->pkType, 'null'], 'null'),
                new Argument('notFoundMessage', 'string', "''")
            ],
            [$fieldClass, 'null']
        );
        $this->addDocMethod(
            'selectList',
            [],
            $fieldClass . '[]'
        );
        $this->addDocMethod(
            'indexList',
            [
                new Argument('key', ['string', Entity::class], "''")
            ],
            $fieldClass . '[]'
        );
        $this->addDocMethod(
            'indexListIn',
            [
                new Argument('range', 'array'),
                new Argument('key', ['string', Entity::class], "''"),
                new Argument('field', ['string', Entity::class], "''")
            ],
            $fieldClass . '[]'
        );
        
        $generator = new FieldGenerator($fieldClass, $this->reset, $this->overwrite, $this->output, $this->dispatcher);
        $generator->setLimit($this->limit);
        $generator->generate();
    }
    
    
    /**
     * 获取模型字段
     * @param Model $model
     * @return array<int,array{name: string, studly: string, camel: string, type: string, comment: string}>
     */
    public static function getFields(Model $model) : array
    {
        $fields = [];
        foreach ($model->getFields() as $field) {
            $fieldType = $model->getFieldType($field['name']);
            $type      = in_array($fieldType, ['date', 'datetime', 'timestamp']) ? 'string' : $fieldType;
            
            if (!$type) {
                if (!preg_match('/^([\w]+)(\(([\d]+)*(,([\d]+))*\))*(.+)*$/', $field['type'], $matches)) {
                    continue;
                }
                
                $type = match ($matches[1]) {
                    'tinyint', 'smallint', 'mediumint', 'int', 'bigint' => 'int',
                    'decimal', 'float'                                  => 'float',
                    'json'                                              => 'array',
                    default                                             => 'string',
                };
            }
            
            $fields[] = [
                'name'    => $field['name'],
                'studly'  => StringHelper::studly($field['name']),
                'camel'   => StringHelper::camel($field['name']),
                'type'    => $type,
                'comment' => $field['comment'] ?? '',
            ];
        }
        
        return $fields;
    }
}
