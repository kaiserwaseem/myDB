<?php

/**
 * Description of myDBClass
 *
 * @author Kaiser Waseem
 */

namespace Logilim;

class myDBTable {

    private $tableName;
    private $data = array();
    private $isSaved = false;
    private $meta = array();
    private $pkField;
    private $pk;
    private $isEditMode = false;
    private $isLoaded = false;

    public function __construct($tableName, $id = NULL) {
        $this->tableName = $tableName;
        $this->meta = myDBClass::getTableMeta($this->tableName);
        $this->setPKField($this->meta['PK_FIELD']);
        if ($id != NULL) {
            $this->setPK($id);
            $this->{$this->pkField} = $id;
            $this->isEditMode = true;
        }
    }

    public function isLoaded() {
        return $this->isLoaded;
    }

    public function findByPK() {
        $this->data = myDBClass::searchOne(array(
                    "qry" => $this->getSelectQuery(),
                    "whereParams" => array(
                        $this->getDataTypeString($this->getPKField()),
                        $this->getPK()))
        );
        if (!empty($this->data))
            $this->isLoaded = true;
    }

    private function getSelectQuery() {
        return "SELECT * FROM " . $this->tableName . " WHERE " . $this->getPKField() . "=?";
    }

    public function setPK($id) {
        $this->pk = $id;
    }

    public function getPK() {
        return $this->pk;
    }

    private function getPKField() {
        return $this->pkField;
    }

    private function setPKField($field) {
        $this->pkField = $field;
    }

    private function getDataTypeString($field) {
        return $this->meta[$field];
    }

    public function addAlias($key, $alias) {
        $this->data[$alias] = (isset($this->data[$key]) ? $this->data[$key] : "");
    }

    public function save() {
        $qry = $types = $columns = $values = "";
        $valuesArr = array();
        $valuesArr[0] = "";
        if ($this->isEditMode) {
            $qry = "UPDATE " . $this->getTableName() . " SET ";
            $i = 1;
            foreach ($this->data as $column => $value) {
                $columns .= $column . "=?,";
                $valuesArr[$i++] = $value;
                $types .= $this->getDataTypeString($column);
            }

            $columns = substr($columns, 0, -1);
            $qry .= $columns . " WHERE " . $this->getPKField() . "=? LIMIT 1";
            $valuesArr[] = $this->getPK();
            $valuesArr[0] = $types . "i";
            $insertedId = myDBClass::save(array("qry" => $qry, "data" => $valuesArr,"update"=>"y"));
            if ($insertedId) {
                $this->isSaved = true;
            }
        } else {
            $qry = "INSERT INTO " . $this->getTableName() . " (";
            $i = 1;
            foreach ($this->data as $column => $value) {
                $columns .= $column . ",";
                $values .= "?,";
                $valuesArr[$i++] = $value;
                $types .= $this->getDataTypeString($column);
            }
            $valuesArr[0] = $types;
            $columns = substr($columns, 0, -1);
            $values = substr($values, 0, -1);
            $qry .= $columns . ") VALUES(" . $values . ")";
            $insertedId = myDBClass::save(array("qry" => $qry, "data" => $valuesArr));
            if ($insertedId) {
                $this->isSaved = true;
                $this->data[$this->getPKField()] = $insertedId;
            }
            /* $oPreparedStatement = myDBClass::setStatement($qry, $valuesArr);
              try {
              if ($oPreparedStatement != NULL)
              if ($oPreparedStatement->execute()) {
              $this->isSaved = true;
              $this->data[$this->getPKField()] = $oPreparedStatement->insert_id;
              $oPreparedStatement->close();
              } else
              $oPreparedStatement->close();
              } catch (Exception $e) {
              myLoggerClass::logIt(array("type" => "SQL", "contents" => self::getConnection()->error . "\n" . $query));
              } */
        }
    }

    public function isSaved() {
        return $this->isSaved;
    }

    private function getTableName() {
        return $this->tableName;
    }

    public function __set($column, $value) {
        $this->data[$column] = $value;
    }

    public function __get($column) {
        return $this->data[$column];
    }

    public function getDataArray() {
        return $this->data;
    }

}
