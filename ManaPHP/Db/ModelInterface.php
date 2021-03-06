<?php

namespace ManaPHP\Db;

/**
 * Interface ManaPHP\Mvc\ModelInterface
 *
 * @package model
 */
interface ModelInterface extends \ManaPHP\ModelInterface
{
    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface|false
     */
    public static function getConnection($context = null);

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function query($alias = null);
}