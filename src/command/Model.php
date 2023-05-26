<?php
declare(strict_types = 1);

namespace BusyPHP\ide\model\command;

use BusyPHP\helper\ClassHelper;
use BusyPHP\helper\FilterHelper;
use BusyPHP\ide\model\FieldGenerator;
use BusyPHP\ide\model\ModelGenerator;
use BusyPHP\Model as BusyModel;
use BusyPHP\model\Field;
use Ergebnis\Classy\Constructs;
use think\console\Command;
use think\console\input\Argument;
use think\console\input\Option;
use Throwable;

/**
 * bp:ide-model Command
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2023 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2023/5/22 16:04 Model.php $
 */
class Model extends Command
{
    /**
     * 自动扫描的目录名
     * @var string[]
     */
    protected array $dirs = ['core/model'];
    
    /**
     * 如果注释已存在是否覆盖
     * @var bool
     */
    protected bool $overwrite;
    
    /**
     * 是否重置注释
     * @var bool
     */
    protected bool $reset;
    
    
    protected function configure()
    {
        $this->setName('bp:ide-model')
            ->addArgument('model', Argument::OPTIONAL | Argument::IS_ARRAY, 'Which models/fields to include', [])
            ->addOption('dir', 'D', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'The model/field dir', [])
            ->addOption('ignore', 'I', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'Which models/fields to ignore', [])
            ->addOption('reset', 'R', Option::VALUE_NONE, 'Remove the original phpdocs instead of appending')
            ->addOption('overwrite', 'O', Option::VALUE_NONE, 'Overwrite the phpdocs')
            ->addOption('all', 'A', Option::VALUE_NONE, 'Scan all files in the `core/model` directory')
            ->addOption('full', 'F', Option::VALUE_NONE, 'All methods or attributes the phpdocs')
            ->addOption('type', 'T', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'Specify the method or property type to generate', []);
    }
    
    
    public function handle()
    {
        $overwrite = $this->input->getOption('overwrite');
        $reset     = $this->input->getOption('reset');
        $dirs      = $this->parseValues($this->input->getOption('dir'));
        $full      = $this->input->getOption('full');
        $type      = $this->parseValues($this->input->getOption('type'));
        
        $limit = true;
        if ($full) {
            $limit = false;
        }
        if ($type) {
            $limit = $type;
        }
        
        // 指定模型
        if ($models = $this->input->getArgument('model')) {
            $list = $this->parseValues($models);
        }
        
        //
        // 全部+指定目录扫描
        elseif ($this->input->getOption('all')) {
            $list = $this->scanModels(array_merge(['core/model'], $dirs));
        }
        
        //
        // 指定目录扫码
        elseif ($dirs) {
            $list = $this->scanModels($dirs);
        }
        
        //
        // 条件不足
        else {
            $this->output->describe($this);
            
            return;
        }
        
        $ignore = array_map([$this, 'replaceClass'], $this->parseValues($this->input->getOption('ignore')));
        foreach ($list as $class) {
            $class = $this->replaceClass($class);
            if (in_array($class, $ignore)) {
                $this->output->comment(sprintf("Ignoring %s '%s'", is_subclass_of($class, Field::class) ? 'field' : 'model', $class));
                continue;
            }
            
            try {
                if (is_subclass_of($class, Field::class)) {
                    $generator = new FieldGenerator($class, $reset, $overwrite, $this->output);
                    $generator->setLimit($limit);
                    $generator->generate();
                } else {
                    $generator = new ModelGenerator($class, $reset, $overwrite, $this->output);
                    $generator->setLimit($limit);
                    $generator->generate();
                    if ($fieldClass = $generator->getModel()->getFieldClass(false)) {
                        $ignore[] = ClassHelper::getAbsoluteClassname($fieldClass, true);
                    }
                }
                
                $ignore[] = $class;
            } catch (Throwable $e) {
                $this->output->error(sprintf("Exception: %s\nCould not analyze class %s.", $e->getMessage(), $class));
            }
        }
    }
    
    
    /**
     * 解析参数值
     * @param array $values
     * @return array
     */
    protected function parseValues(array $values) : array
    {
        $list = [];
        foreach ($values as $value) {
            $list = array_merge($list, FilterHelper::trimArray(explode(',', $value)));
        }
        
        return $list;
    }
    
    
    /**
     * 解析类名称
     * @param string $class
     * @return string
     */
    protected function replaceClass(string $class) : string
    {
        $class = str_replace(['/', '.'], '\\', $class);
        
        return ClassHelper::getAbsoluteClassname($class, true);
    }
    
    
    /**
     * 扫描模型
     * @param array $dirs
     * @return array
     */
    protected function scanModels(array $dirs) : array
    {
        $models = [];
        foreach ($dirs as $dir) {
            $dir = $this->app->getRootPath() . $dir;
            if (!is_dir($dir)) {
                continue;
            }
            
            $constructs = Constructs::fromDirectory($dir);
            foreach ($constructs as $construct) {
                if (!is_subclass_of($construct->name(), BusyModel::class) && !is_subclass_of($construct->name(), Field::class)) {
                    continue;
                }
                
                $models[] = $construct->name();
            }
        }
        
        return $models;
    }
}