<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/20
 * Time: 22:06
 */
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;
use ManaPHP\Db\Adapter\Mysql\Exception as MysqlException;

/**
 * Class ManaPHP\Db\Adapter\Mysql
 *
 * @package db\adapter
 */
class Mysql extends Db
{
    /**
     * @var string
     */
    protected $_charset = 'UTF8';

    /**
     * \ManaPHP\Db\Adapter constructor
     *
     * @param string $uri
     *
     * @throws \ManaPHP\Db\Exception
     */
    public function __construct($uri = 'mysql://root@localhost/test?charset=utf8')
    {
        $parts = parse_url($uri);

        if ($parts['scheme'] !== 'mysql') {
            throw new MysqlException('`:url` is invalid, `:scheme` scheme is not recognized', ['url' => $uri, 'scheme' => $parts['scheme']]);
        }

        $this->_username = isset($parts['user']) ? $parts['user'] : 'root';
        $this->_password = isset($parts['pass']) ? $parts['pass'] : '';

        $dsn = [];

        if (isset($parts['host'])) {
            $dsn['host'] = $parts['host'];
        }

        if (isset($parts['port'])) {
            $dsn['port'] = $parts['port'];
        }

        if (isset($parts['path'])) {
            $db = trim($parts['path'], '/');
            if ($db !== '') {
                $dsn['dbname'] = $db;
            }
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $parts2);
        } else {
            $parts2 = [];
        }

        if (isset($parts2['charset'])) {
            $this->_charset = $parts2['charset'];
        }

        if (isset($parts2['persistent'])) {
            $this->_options[\PDO::ATTR_PERSISTENT] = $parts2['persistent'] === '1';
        }

        $this->_options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$this->_charset}'";

        $dsn_parts = [];
        foreach ($dsn as $k => $v) {
            $dsn_parts[] = $k . '=' . $v;
        }
        $this->_dsn = 'mysql:' . implode(';', $dsn_parts);

        parent::__construct();
    }

    /**
     * @param string $source
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getMetadata($source)
    {
        $fields = $this->fetchAll('DESCRIBE ' . $this->_escapeIdentifier($source), [], \PDO::FETCH_NUM);

        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $autoIncrementAttribute = null;
        foreach ($fields as $field) {
            $fieldName = $field[0];

            $attributes[] = $fieldName;

            if ($field[3] === 'PRI') {
                $primaryKeys[] = $fieldName;
            } else {
                $nonPrimaryKeys[] = $fieldName;
            }

            if ($field[5] === 'auto_increment') {
                $autoIncrementAttribute = $fieldName;
            }
        }

        $r = [
            self::METADATA_ATTRIBUTES => $attributes,
            self::METADATA_PRIMARY_KEY => $primaryKeys,
            self::METADATA_NON_PRIMARY_KEY => $nonPrimaryKeys,
            self::METADATA_IDENTITY_FIELD => $autoIncrementAttribute,
        ];

        return $r;
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function truncateTable($source)
    {
        $this->execute('TRUNCATE TABLE ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function dropTable($source)
    {
        $this->execute('DROP TABLE IF EXISTS ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getTables($schema = null)
    {
        if ($schema) {
            $sql = 'SHOW TABLES FROM `' . $this->_escapeIdentifier($schema) . '`';
        } else {
            $sql = 'SHOW TABLES';
        }

        $tables = [];
        foreach ($this->fetchAll($sql, [], \PDO::FETCH_NUM) as $row) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
     * @param string $source
     *
     * @return bool
     * @throws \ManaPHP\Db\Exception
     */
    public function tableExists($source)
    {
        $parts = explode('.', str_replace('[]`', '', $source));

        if (count($parts) === 2) {
            $sql = "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '$parts[0]' AND `TABLE_SCHEMA` = '$parts[1]'";
        } else {
            $sql = "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '$parts[0]' AND `TABLE_SCHEMA` = DATABASE()";
        }

        $r = $this->fetchOne($sql, [], \PDO::FETCH_NUM);

        return $r[0] === '1';
    }

    public function buildSql($params)
    {
        $sql = '';

        if (isset($params['fields'])) {
            $sql .= 'SELECT ';

            if (isset($params['distinct'])) {
                $sql .= 'DISTINCT ';
            }

            $sql .= $params['fields'];
        }

        if (isset($params['from'])) {
            $sql .= ' FROM ' . $params['from'];
        }

        if (isset($params['join'])) {
            $sql .= $params['join'];
        }

        if (isset($params['where'])) {
            $sql .= ' WHERE ' . $params['where'];
        }

        if (isset($params['group'])) {
            $sql .= ' GROUP BY ' . $params['group'];
        }

        if (isset($params['having'])) {
            $sql .= ' HAVING ' . $params['having'];
        }

        if (isset($params['order'])) {
            $sql .= ' ORDER BY ' . $params['order'];
        }

        if (isset($params['limit'])) {
            $sql .= ' LIMIT ' . $params['limit'];
        }

        if (isset($params['offset'])) {
            $sql .= ' OFFSET ' . $params['offset'];
        }

        if (isset($params['forUpdate'])) {
            $sql .= 'FOR UPDATE';
        }

        return $sql;
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    public function replaceQuoteCharacters($sql)
    {
        return preg_replace('#\[([a-z_][a-z0-9_]*)\]#i', '`\\1`', $sql);
    }
}