<?php

/**
 * Description of myDBClass
 *
 * @author Kaiser Waseem
 */

namespace Logilim;

class myDBClass {

    private static $oMySQLi;
    private static $DBConfig;

    public static function dbConn() {
        static::$DBConfig = Configuration::getInstance()->mysql;
        self::$oMySQLi = new \mysqli(
                static::$DBConfig['host'], static::$DBConfig['username'], static::$DBConfig['password'], static::$DBConfig['dbname']
        );
        if (self::$oMySQLi->connect_error) {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => 'Connect Error: ' . self::$oMySQLi->connect_error));
            die('Connect Error: ' . self::$oMySQLi->connect_error);
        }
    }

    public static function getConnection() {
        if (self::$oMySQLi == '') {
            self::dbConn();
        }
        return self::$oMySQLi;
    }

    public static function searchOne($params) {
        $params['qry'] = $params['qry'] . " LIMIT 1 ";
        return self::search($params);
    }

    private static function search($params) {
        $array = NULL;
        $oPreparedStatement = self::setStatement($params['qry'], (isset($params['whereParams']) ? $params['whereParams'] : array()));
        try {
            if ($oPreparedStatement != NULL) {
                if ($oPreparedStatement->execute()) {
                    $oPreparedStatement->store_result();
                    $variables = array();
                    $data = array();
                    $meta = $oPreparedStatement->result_metadata();
                    while ($field = $meta->fetch_field()) {
                        $variables[] = &$data[$field->name];
                    }
                    call_user_func_array(array($oPreparedStatement, 'bind_result'), $variables);
                    $i = 0;
                    while ($oPreparedStatement->fetch()) {
                        $array[$i] = array();
                        foreach ($data as $k => $v)
                            $array[$i][$k] = $v;
                        $i++;
                    }
                    if ($i == 1)
                        $array = $array[0];
                    $oPreparedStatement->close();
                }
            }
        } catch (Exception $e) {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $query));
            $array = false;
        }
        return $array;
    }

    public static function setStatement($query, $param) {
        try {
            $stmt = self::getConnection()->prepare($query);
            if (false === $stmt) {
                myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $query));
                if ($stmt != null) {
                    $stmt->close();
                }
                throw new \Exception(self::getConnection()->error);
            }
            $ref = new \ReflectionClass('mysqli_stmt');
            if (count($param) != 0) {
                $method = $ref->getMethod('bind_param');
                $refs = array();
                foreach ($param as $key => $value)
                    $refs[$key] = &$param[$key];
                if (false === $method->invokeArgs($stmt, $refs)) {
                    myLoggerClass::logIt(array("type" => "SQL", "contents" => $stmt->error . "\n" . $query));
                    if ($stmt != null) {
                        $stmt->close();
                    }
                }
                //alternative of invokeArgs
                //call_user_func_array(array($stmt, "bind_param"), $refs));
            }
        } catch (Exception $e) {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $query));
            if ($stmt != null) {
                $stmt->close();
            }
        }
        return $stmt;
    }

    public static function find($params) {
        $data = false;
        $result = self::getConnection()->query($params['qry']);
        if ($result) {
            $row = (isset($params['retType']) ? $result->fetch_object() : $result->fetch_assoc());
            return $row;
        } else {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $params['qry']));
            return false;
        }
        return $data;
    }

    public static function count($params) {
        $result = self::getConnection()->query($params['qry']);
        if ($result) {
            $row = $result->fetch_array(MYSQLI_NUM);
            if (!empty($row))
                return $row[0];
            else
                return 0;
        } else {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $params['qry']));
            return false;
        }
    }

    public static function findAll($params) {
        $data = false;
        $result = self::getConnection()->query($params['qry']);
        if ($result) {
            if (isset($params['retType'])) {
                while ($oRow = $result->fetch_object()) {
                    $data[] = $oRow;
                }
            } else {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
        } else {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $params['qry']));
            return false;
        }
        return $data;
    }

    public static function save($params) {
        $saveStatus = false;
        $oPreparedStatement = myDBClass::setStatement($params['qry'], $params['data']);
        try {
            if ($oPreparedStatement != NULL)
                if ($oPreparedStatement->execute()) {
                    if (isset($params['update']) && $params['update'] == 'y')
                        $saveStatus = true;
                    else
                        $saveStatus = $oPreparedStatement->insert_id;
                    $oPreparedStatement->close();
                } else {
                    $oPreparedStatement->close();
                }
        } catch (Exception $e) {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $query));
        }
        return $saveStatus;
    }

    public static function saveByQuery($params) {
        $result = self::getConnection()->query($params['qry']);
        if ($result) {
            return true;
        } else {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $params['qry']));
            return false;
        }
    }

    public static function update($params) {
        $params['update'] = true;
        return self::save($params);
    }

    public static function isExist($params) {
        $result = self::searchOne($params);
        if ($result) {
            return $result;
        } else {
            myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $qry));
            return false;
        }
    }

    public static function newTable($tableName, $id = NULL) {
        return new myDBTable($tableName, $id);
    }

    public static function getTableMeta($tableName) {
        $metaData = self::search(array("qry" => "SHOW COLUMNS FROM " . $tableName));
        $metaArr = array();
        $metaArr["PK_FIELD"] = "";
        foreach ($metaData as $key => $value) {
            $metaArr[$value['Field']] = self::getDataTypeAlias($value['Type']);
            if ($value['Key'] == "PRI")
                $metaArr["PK_FIELD"] = $value['Field'];
        }
        return $metaArr;
    }

    private static function getDataTypeAlias($type) {
        if (stripos($type, "int") !== false)
            return "i";
        elseif (stripos($type, "double") !== false)
            return "d";
        else
            return "s";
    }

}
