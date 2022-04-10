<?php

declare(strict_types=1);

/**
 * This file is part of the Max package.
 *
 * (c) Cheng Yao <987861463@qq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Max\Config\Repository;
use Max\Di\Exceptions\NotFoundException;
use Max\Env\Env;
use Psr\Container\ContainerExceptionInterface;

if (false === function_exists('base_path')) {
    /**
     * @param string $path
     *
     * @return string
     * @throws Exception
     */
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ltrim($path, '/');
    }
}

if (false === function_exists('env')) {
    /**
     * env获取
     *
     * @param string|null $key
     * @param null        $default
     *
     * @return mixed
     * @throws Exception|ContainerExceptionInterface
     */
    function env(string $key = null, $default = null): mixed
    {
        return make(Env::class)->get($key, $default);
    }
}

if (false === function_exists('config')) {
    /**
     *配置文件获取辅助函数
     *
     * @param string|null $key     配置Key
     * @param null        $default 默认值
     *
     * @return mixed
     * @throws NotFoundException
     * @throws ReflectionException|ContainerExceptionInterface
     */
    function config(string $key = null, $default = null): mixed
    {
        /** @var Repository $config */
        $config = make(Repository::class);

        return $key ? $config->get($key, $default) : $config->all();
    }
}
