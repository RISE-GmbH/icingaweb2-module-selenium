<?php

namespace Icinga\Module\Selenium\Model;

use Icinga\Module\Selenium\Common\Database;
use ipl\Orm\Model;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;



abstract class DbModel extends Model
{
    public function getKeyName(){

    }
    public function beforeDelete(Connection $db){

    }

    public function beforeSave(Connection $db){

    }

    public function getColumns(): array
    {
        return array_keys($this->getColumnDefinitions());
    }
    public function getBooleans(){
        return array_filter($this->getColumnDefinitions(),function($el) { return (!empty($el['fieldtype'] && $el['fieldtype']== "checkbox")); }) ?? [];
    }
    public function getTimestamps(){
        return array_filter($this->getColumnDefinitions(),function($el) { return (!empty($el['fieldtype'] && $el['fieldtype']== "localDateTime")); }) ?? [];
    }

    public function getValues()
    {
        $values=[];
        foreach ($this->getColumns() as $column) {
            if(isset($this->{$column})){
                $values[$column] = $this->{$column};
            }else{
                $values[$column] = null;
            }
        }
        return $values;
    }

    public function setValues($values)
    {
        foreach ($this->getColumns() as $column) {
            if (isset($values[$column])) {
                $this->{$column} = $values[$column];
            }else{
                $this->{$column} = null; // TODO scaffoldbuilder TODO other projects
            }

        }
    }
    public static function getAsArray($key, $value)
    {
        $result = [];
        foreach (self::on(Database::get())->withColumns([$key,$value]) as $item){
            /* @var $item self            */
                $result[$item->{$key}] = $item->{$value};

        }
        return $result;
    }
    public function save($asTransaction = true)
    {
        $db = Database::get();
        if($asTransaction){
            $db->beginTransaction();
        }
        $this->beforeSave($db);

        $values=[];
        foreach ($this->getColumns() as $column){
            if(isset($this->{$column})){
                $value= $this->{$column};
                if(is_object($value) && get_class($value) == "DateTime"){
                    $value = $value->format("Uv");
                }elseif($value === false){
                    $value = 'n';
                }elseif($value === true){
                    $value = 'y';
                }
                $values[$column]= $value;
            }else{
                $values[$column]= null;
            }

        }
        if (!isset ($this->id) || $this->id === null) {
            $db->insert($this->getTableName(), $values);
            $this->id = $db->lastInsertId();

        } else {
            $db->update($this->getTableName(), $values, [$this->getKeyName().' = ?' => $this->id]);
        }
        if($asTransaction){
            $db->commitTransaction();
        }

    }
    public static function findbyPrimaryKey($id){
        $db = Database::get();
        $class = get_called_class();
        return self::on($db)->filter(Filter::equal((new $class())->getKeyName(), $id))->first();
    }

    public function delete()
    {

        $db = Database::get();
        $this->beforeDelete($db);
        $db->delete($this->getTableName(), [$this->getKeyName().' = ?' => $this->id]);
    }
}
