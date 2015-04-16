<?php

/**
 * Description of myMongoClass
 *
 * @Kaiser Waseem Khan <kaiserwaseem@gmail.com>
 */

namespace Logilim;

use Logilim\Configuration;

class myMongoClass {

    private static $MongoConfig;
    private static $oMongoClient;
    private static $oMongoDB;

    public static function mongoConn() {
        try {
            static::$MongoConfig = Configuration::getInstance()->mongo;
            static::$oMongoClient = new \MongoClient("mongodb://" . static::$MongoConfig['host'] . ":" . static::$MongoConfig['port']);
            static::$oMongoDB = static::$oMongoClient->{static::$MongoConfig['db']};
        } catch (MongoConnectionException $ex) {
            echo "Could Not connect to MongoDB";
            echo $ex->getTraceAsString();
            exit;
        }
    }

    public static function getConnection() {
        if (static::$oMongoClient == '')
            self::mongoConn();
        return static::$oMongoDB;
    }

    public static function findAll($params) {

        /* try {
          // connect
          echo "connectiong..\n";
          $m = new \MongoClient();
          echo "connectiong done..\n";
          // select a database
          $db = $m->ddLocations;
          echo "selected db..\n";
          // select a collection (analogous to a relational database's table)
          $collection = $db->location;
          echo "selected col...\n";
          $condition = array("loc" => array(
                    '$nearSphere' => array(
                        "type" => "Point",
                        "coordinates" => array(31.47598266601562, 74.34273529052734)
                    ),
                    '$minDistance' => 0,
                    '$maxDistance' => 100000//in meters
                ),
                'companyId' => 1
            );
          print_r($condition);
          echo "\n\n";
          // find everything in the collection
          print_r($params['condition']);
          $cursor = $collection->find($params['condition']);
          echo "exec query...\n";
          // iterate through the results
          foreach ($cursor as $document) {
          var_dump($document);
          echo "\n";
          }
          } catch (Exception $ex) {
          echo "exception";
          var_dump($ex);
          }
          exit; */
        $collection = $params['collection'];
        $condition = (isset($params['condition']) ? $params['condition'] : array());
        $oCollection = self::getConnection()->$collection;

        $oCursor = $oCollection->find($condition);
        return $oCursor;
    }

    public static function find($params) {
        $collection = $params['collection'];
        $oCollection = self::getConnection()->$collection;
        return $oCollection->findOne();
    }

    public static function save($params) {
        $collection = $params['collection'];
        $document = $params['document'];
        $oCollection = self::getConnection()->$collection;
        try {
            $oCollection->insert($document);
            return $document['_id'];
        } catch (MongoCursorException $oMCE) {
            echo $oMCE->getTraceAsString();
            return false;
        }
    }

    public static function upsert($params) {
        $collection = $params['collection'];
        $condition = $params['condition'];
        $document = $params['document'];
        $oCollection = self::getConnection()->$collection;
        try {
            return $oCollection->update($condition, $document, array("upsert" => true));
        } catch (MongoCursorException $oMCE) {
            echo $oMCE->getTraceAsString();
            return false;
        }
    }

}
