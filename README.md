BusyPHP模型注释生成器
===============

用于生成BusyPHP的`模型`/`模型字段`注释或`模型字段`属性，以提高开发效率

## 安装方法

```shell script
composer require busyphp/ide-model
```

## 使用说明

### 全部扫描并生成

> 使用 `-A` 指令即可自动扫描 `core/model` 目录下的所有模型/模型字段类进行生成 <br />
> 可配合 `-D` 添加自己的目录名称

```shell
php think bp:ide-model -A
php think bp:ide-model -A -D customDir -D customDir1
```

### 指定目录扫描并生成

```shell
php think bp:ide-model -D userDir0 -D userDir1
```

### 指定类目生成

> 支持使用 `.`, `/` 替代 `\\` 方便快输入类名称  

```shell
php think bp:ide-model core.model.UserModel core.model.UserModelField
php think bp:ide-model core/model/UserModel core/model/UserModelField
php think bp:ide-model core\\model\\UserModel core\\model\\UserModelField
```