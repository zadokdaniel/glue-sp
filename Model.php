<?php

class Model {

  /**
   * @var $dbConn Connection name on DBM
   * @var $table Model table name in DB
   * @var $id_field The ID column on table in DB
   * @var $manyToMany Many to many connected table
   * @var $oneToOne One to one connected table
   */
  protected static $dbConn = '',
                   $table = '',
                   $id_field = "id",
                   $manyToMany = [],
                   $oneToOne = [];

  /**
   * @var array Configures validation for where clause on sql query
   */
  protected static $validConditions = ['=', '!=', '<', '>', '<=', '>='];

  public function __get($property) {
    // Many to many
    if(in_array($property, static::$manyToMany) && !isset($this->$property)) {
      $className = substr(ucfirst($property), 0, -1);
      $this->{$property} = $className::manyToMany($this);
      return $this->$property;
    }

    // One to One
    if(in_array($property, static::$oneToOne) && !isset($this->$property)) {
      $className = ucfirst($property);
      $this->{$property} = $className::oneToOne($this);
      return $this->$property;
    }

    if(isset($this->$property)) return $this->$property;

    return ;
  }




  // ** Helper functions

  /**
   * Returns the name of the Model's table based on class name or $table property if set
   *
   * @return string Name of table
   */
  protected static function getTableName() {
    return static::$table == '' ? strtolower(static::class . 's') : static::$table;
  }

  protected static function getColumns($columns = array('*')) {
    $columns = is_array($columns) ? $columns : func_get_args();
    return implode(', ', $columns);
  }

  protected static function validateValues($properties){
    return true;
  }

  // TODO: implement different condition methods between criteria.
  protected static function implodeCriteria($criteria = null) {
    
    $result = [];
    
    if(count($criteria) === count($criteria, COUNT_RECURSIVE)) {
      $criteria = array($criteria);
    }

    if(is_array($criteria)) {
      foreach($criteria as $c) {
        if(is_string($c[0]) && in_array($c[1], static::$validConditions) && isset($c[2]))
          $result[] = implode(' ', $c);
      }
    }

    return implode(' AND ' , $result);
  }  

  protected static function selectString($columns = array('*')) {
    return "SELECT " . static::getColumns($columns) . " FROM " . static::getTableName();
  }


  // ** Query functions

  protected static function select($query, $fetchType = PDO::FETCH_CLASS) {
    $stmt = DBManager::connection(static::$dbConn)->query($query);
    $fetchType === PDO::FETCH_CLASS ? $stmt->setFetchMode($fetchType, get_called_class()) : $stmt->setFetchMode($fetchType);
    return $stmt->fetchAll(); 
  }


    // *** Selection methods
  public static function all($limit = null, $columns = array('*')) {
    
    $q_selectAll = static::selectString($columns) . (is_null($limit) ? '' : " LIMIT $limit");
    
    return static::select($q_selectAll);
  }

  public static function find($criteria, $limit = null, $columns = array('*')) {
    
    $q_find = static::selectString($columns)
              . (is_array($criteria) ? " WHERE " . static::implodeCriteria($criteria) : '')
              . (is_numeric($limit) ? " LIMIT $limit" : '');

    return static::select($q_find);
  }

  public static function byId($id, $columns = array('*')) {
    $result = static::find([static::$id_field, '=', $id], 1, $columns);
    return !empty($result) ? $result[0] : null;
  }
  
  public static function countAll() {
    $counter = static::select("SELECT COUNT(*) FROM " . static::getTableName(), PDO::FETCH_NUM);
    return is_array($counter) ? (int)$counter[0][0] : null;
  }

    // *** Insertion methods
  public static function insert($properties, $returnObj = true) {

    if(!static::validateValues($properties))
      return $retrunObj ? [] : false;

    $columns =  [];
    $values = [];

    if(is_array($properties))  {
      foreach($properties as $key => $value) {
        if(is_string($key)) $columns[] = $key;
        $values[] = ($value === "NOW()" || $value === "NULL" ? $value : "\"$value\"");
      }
    }

    $q_insert = "INSERT INTO " . static::getTableName()
                . (count($columns) > 0 ? ' (' . implode(', ', $columns) . ')' : '')
                . " VALUES ("  . implode(', ', $values) . ")";
    
    DBManager::connection(static::$dbConn)->prepare($q_insert)->execute();
    return $returnObj ? static::byId(DBManager::connection(static::$dbConn)->lastInsertId(0)) : true;
  }

  public function update($properties) {
    
    if(!static::validateValues($properties))
      return false;

    $orderedProperties = [];
  
    foreach($properties as $key => $value) {
      $orderedProperties[] = $key . "=" . ($value === "NOW()" || $value === "NULL" ? $value : "\"$value\"");
    }
    $q_update = "UPDATE " . static::getTableName()
                . " SET " . implode(', ', $orderedProperties)
                . " WHERE " . static::$id_field . " = " . $this->{static::$id_field};
    
    DBManager::connection(static::$dbConn)->prepare($q_update)->execute();

    foreach($properties as $key  => $value) {
      $this->{$key} = $value;
    }

    return true;
  }

    // *** Deletion methods
  public function delete() {
    
    $q_delete = "DELETE FROM " . static::getTableName() . " WHERE " . static::$id_field . " = " . $this->{static::$id_field};

    DBManager::connection(static::$dbConn)->query($q_delete);

    return true;
  }


  // ** Relations query functions
  public static function manyToMany($caller, $columns = array('*')) {

    $tables = [(get_class($caller))::getTableName(), static::getTableName()];
    sort($tables);
    $manyToManyTable = implode("_", $tables);

    $q_selectManyToMany = static::selectString() . " ft" . 
                          " RIGHT JOIN " . $manyToManyTable . " mtom ON mtom." . strtolower(get_called_class()) . "_id = ft.id
                           WHERE mtom." . strtolower(get_class($caller)) . "_id = \"" . $caller->id . "\"";
      
    return static::select($q_selectManyToMany);
  }

  public static function oneToOne($caller, $columns = array('*')) {
     
    return static::byId($caller->{get_class($caller)::$id_field}, $columns);
  }
}