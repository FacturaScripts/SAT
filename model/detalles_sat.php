<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014-2015  Carlos Garcia Gomez         neorazorx@gmail.com
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

class detalles_sat extends fs_model
{
   public $id;
   public $descripcion;
   public $nsat;
   public $fecha;
   public $nick;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('detalles_sat', 'plugins/SAT/');
      if($s)
      {
         $this->id = intval($s['id']);
         $this->descripcion = $s['descripcion'];
         $this->nsat = intval($s['nsat']);
         $this->fecha = date('d-m-Y', strtotime($s['fecha']));
         $this->nick = $s['nick'];
      }
      else
      {
         $this->id = NULL;
         $this->descripcion = '';
         $this->nsat = NULL;
         $this->fecha = date('d-m-Y');
         $this->nick = NULL;
      }
   }
   
   public function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM detalles_sat WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new detalles_sat($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM detalles_sat WHERE id = ".$this->var2str($this->nsat).";");
      }
   }
   
   public function save()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( $this->exists() )
      {
         $sql = "UPDATE detalles_sat SET descripcion = ".$this->var2str($this->descripcion).",
            fecha = ".$this->var2str($this->fecha).", nsat = ".$this->var2str($this->nsat).",
            nick = ".$this->var2str($this->nick)." WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO detalles_sat (descripcion,fecha,nsat,nick) VALUES (".$this->var2str($this->descripcion).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->nsat).",".$this->var2str($this->nick).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM detalles_sat WHERE id = ".$this->var2str($this->nsat).";");
   }
   
   public function all()
   {
      $detalleslist = array();
      
      $sql = "SELECT d.id,d.descripcion,d.nsat,d.fecha,d.nick FROM registros_sat r, detalles_sat d
         WHERE d.nsat = r.nsat ORDER BY d.fecha ASC, d.id ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $detalleslist[] = new detalles_sat($d);
      }
      
      return $detalleslist;
   }
   
   public function all_from_sat($sat)
   {
      $detalleslist = array();
      
      $sql = "SELECT d.id,d.descripcion,d.nsat,d.fecha,d.nick FROM registros_sat r, detalles_sat d
         WHERE d.nsat = r.nsat AND d.nsat = ".$this->var2str($sat)." ORDER BY d.fecha ASC, d.id ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $detalleslist[] = new detalles_sat($d);
      }
      
      return $detalleslist;
   }
}
