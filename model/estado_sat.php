<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez         neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of estado_sat
 *
 * @author carlos
 */
class estado_sat extends fs_model
{
   public $id;
   public $descripcion;
   public $color;
   
   public function __construct($e = FALSE)
   {
      parent::__construct('estados_sat', 'plugins/SAT/');
      if($e)
      {
         $this->id = $this->intval($e['id']);
         $this->descripcion = $e['descripcion'];
         $this->color = $e['color'];
      }
      else
      {
         $this->id = NULL;
         $this->descripcion = '';
         $this->color = '00FF00';
      }
   }
   
   protected function install()
   {
      return "INSERT INTO estados_sat (id,descripcion,color) VALUES ('1','Nuevo','C2DAF5'),('2','Terminado','FFFFFF');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM estados_sat WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new estado_sat($data[0]);
      }
      else
         return FALSE;
   }
   
   public function get_nuevo_id()
   {
      $data = $this->db->select("SELECT MAX(id) as id FROM estados_sat;");
      if($data)
      {
         return 1 + intval($data[0]['id']);
      }
      else
         return 1;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM estados_sat WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $this->color = $this->no_html($this->color);
      
      if( $this->exists() )
      {
         $sql = "UPDATE estados_sat SET descripcion = ".$this->var2str($this->descripcion).
                 ", color = ".$this->var2str($this->color)." WHERE id = ".$this->var2str($this->id).";";
      }
      else
      {
         $sql = "INSERT INTO estados_sat (id,descripcion,color) VALUES ("
                 .$this->var2str($this->id).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->color).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM estados_sat WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all()
   {
      $elist = array();
      
      $data = $this->db->select("SELECT * FROM estados_sat ORDER BY id ASC;");
      if($data)
      {
         foreach($data as $d)
            $elist[] = new estado_sat($d);
      }
      
      return $elist;
   }
}
