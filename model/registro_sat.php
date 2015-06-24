<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014      Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014-2015 Carlos Garcia Gomez         neorazorx@gmail.com
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

require_model('estado_sat.php');

class registro_sat extends fs_model
{
   public $nsat;
   public $prioridad;
   public $fentrada;
   public $fcomienzo;
   public $ffin;
   public $modelo;
   public $codcliente;
   public $estado;
   public $averia;
   public $accesorios;
   public $observaciones;
   public $posicion;
   public $contacto;
   public $codagente;
   
   /// Estos datos los usas, pero no los guardas en la base de datos
   public $nombre_cliente;
   public $telefono1_cliente;
   public $telefono2_cliente;
   
   private static $estados;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('registros_sat', 'plugins/SAT/');
      if($s)
      {
         $this->nsat = intval($s['nsat']);
         $this->prioridad = intval($s['prioridad']);
         $this->fentrada = date('d-m-Y', strtotime($s['fentrada']));
         
         $this->fcomienzo = NULL;
         if( isset($s['fcomienzo']) )
         {
            $this->fcomienzo = date('d-m-Y', strtotime($s['fcomienzo']));
         }
         
         $this->ffin = NULL;
         if( isset($s['ffin']) )
         {
            $this->ffin = date('d-m-Y', strtotime($s['ffin']));
         }
         
         $this->modelo = $s['modelo'];
         $this->codcliente = $s['codcliente'];
         $this->estado = intval($s['estado']);
         $this->prioridad = intval($s['prioridad']);
         $this->averia = $s['averia'];
         $this->accesorios = $s['accesorios'];
         $this->observaciones = $s['observaciones'];
         $this->posicion = $s['posicion'];
         $this->nombre_cliente = $s['nombre'];
         $this->telefono1_cliente = $s['telefono1'];
         $this->telefono2_cliente = $s['telefono2'];
         
         $this->contacto = '';
         if( isset($s['contacto']) )
         {
            $this->contacto = $s['contacto'];
         }
         
         $this->codagente = NULL;
         if( isset($s['codagente']) )
         {
            $this->codagente = $s['codagente'];
         }
      }
      else
      {
         $this->nsat = NULL;
         $this->prioridad = 3;
         $this->fentrada = date('d-m-Y');
         $this->fcomienzo = date('d-m-Y');
         $this->ffin = NULL;
         $this->modelo = '';
         $this->codcliente = NULL;
         $this->estado = 1;
         $this->averia = '';
         $this->accesorios = '';
         $this->observaciones = '';
         $this->posicion = '';
         $this->contacto = '';
         $this->codagente = NULL;
         
         $this->nombre_cliente = '';
         $this->telefono1_cliente = '';
         $this->telefono2_cliente = '';
      }
      
      if( !isset(self::$estados) )
      {
         $estado = new estado_sat();
         self::$estados = $estado->all();
      }
   }
   
   public function install()
   {
      return '';
   }
   
   public function prioridad()
   {
      $prioridad = array(
          1 => 'Urgente',
          2 => 'Prioridad alta',
          3 => 'Prioridad media',
          4 => 'Prioridad baja',
      );
      
      return $prioridad;
   }
   
   public function nombre_prioridad()
   {
      $prioridades = $this->prioridad();
      return $prioridades[$this->prioridad];
   }
   
   public function estrellas_prioridad()
   {
      $retorno = '';
      $estrella = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>';
      $no_estrella = '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>';
      
      $i = 0;
      for(;$i < 5-$this->prioridad; $i++)
      {
         $retorno .= $estrella;
      }
      
      while($i < 4)
      {
         $retorno .= $no_estrella;
         $i++;
      }
      
      return $retorno;
   }
   
   public function nombre_estado()
   {
      $nombre = '';
      
      foreach(self::$estados as $est)
      {
         if($est->id == $this->estado)
         {
            $nombre = $est->descripcion;
            break;
         }
      }
      
      return $nombre;
   }
   
   public function color_estado()
   {
      $color = 'FFFFFF';
      
      foreach(self::$estados as $est)
      {
         if($est->id == $this->estado)
         {
            $color = $est->color;
            break;
         }
      }
      
      return $color;
   }
   
   public function averia_resume($len = 90)
   {
      if( strlen($this->averia) > $len )
      {
         return substr($this->averia, 0, $len-3).'...';
      }
      else
         return $this->averia;
   }
   
   public function url()
   {
      if( is_null($this->nsat) )
      {
         return 'index.php?page=listado_sat';
      }
      else
      {
         return 'index.php?page=editar_sat&id='.$this->nsat;
      }
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
      {
         return "index.php?page=ventas_clientes";
      }
      else
         return "index.php?page=ventas_cliente&cod=".$this->codcliente;
   }
   
   public function direccion_cliente()
   {
      $data = $this->db->select("SELECT * FROM dirclientes WHERE codcliente = ".$this->var2str($this->codcliente).";");
      if($data)
      {
         return $data[0]['direccion'].', '.$data[0]['ciudad'].', '.$data[0]['provincia'];
      }
      else
         return '';
   }
   
   public function get($id)
   {
      $sql = "SELECT r.nsat,r.prioridad,r.fentrada,r.fcomienzo,r.ffin,r.modelo,r.codcliente,
         c.nombre,c.telefono1,c.telefono2,r.estado,r.averia,r.accesorios,r.observaciones,r.posicion,
         r.contacto,r.codagente FROM registros_sat r, clientes c
         WHERE r.codcliente = c.codcliente AND r.nsat = ".$this->var2str($id).";";
      $data = $this->db->select($sql);
      if($data)
      {
         return new registro_sat($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->nsat) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM registros_sat WHERE nsat = ".$this->var2str($this->nsat).";");
      }
   }
   
   public function save()
   {
      $this->modelo = $this->no_html($this->modelo);
      $this->averia = $this->no_html($this->averia);
      $this->accesorios = $this->no_html($this->accesorios);
      $this->observaciones = $this->no_html($this->observaciones);
      $this->posicion = $this->no_html($this->posicion);
      $this->contacto = $this->no_html($this->contacto);
      
      if( $this->exists() )
      {
         $sql = "UPDATE registros_sat SET prioridad = ".$this->var2str($this->prioridad).
                 ", fentrada = ".$this->var2str($this->fentrada).
                 ", fcomienzo = ".$this->var2str($this->fcomienzo).
                 ", ffin = ".$this->var2str($this->ffin).
                 ", modelo = ".$this->var2str($this->modelo).
                 ", codcliente = ".$this->var2str($this->codcliente).
                 ", estado = ".$this->var2str($this->estado).
                 ", averia = ".$this->var2str($this->averia).
                 ", accesorios = ".$this->var2str($this->accesorios).
                 ", observaciones = ".$this->var2str($this->observaciones).
                 ", posicion = ".$this->var2str($this->posicion).
                 ", contacto = ".$this->var2str($this->contacto).
                 ", codagente = ".$this->var2str($this->codagente).
                 " WHERE nsat = ".$this->var2str($this->nsat).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO registros_sat (prioridad,fentrada,fcomienzo,ffin,modelo,codcliente,estado,
            averia,accesorios,observaciones,posicion,contacto,codagente) VALUES
                  (".$this->var2str($this->prioridad).
                 ",".$this->var2str($this->fentrada).
                 ",".$this->var2str($this->fcomienzo).
                 ",".$this->var2str($this->ffin).
                 ",".$this->var2str($this->modelo).
                 ",".$this->var2str($this->codcliente).
                 ",".$this->var2str($this->estado).
                 ",".$this->var2str($this->averia).
                 ",".$this->var2str($this->accesorios).
                 ",".$this->var2str($this->observaciones).
                 ",".$this->var2str($this->posicion).
                 ",".$this->var2str($this->contacto).
                 ",".$this->var2str($this->codagente).");";
         
         if( $this->db->exec($sql) )
         {
            $this->nsat = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM registros_sat WHERE nsat = ".$this->var2str($this->nsat).";");
   }
   
   public function all($offset = 0)
   {
      $satlist = array();
      
      $sql = "SELECT r.nsat,r.prioridad,r.fentrada,r.fcomienzo,r.ffin,r.modelo,
         r.codcliente,c.nombre,c.telefono1,c.telefono2,r.estado,r.averia,r.accesorios,
         r.observaciones,r.posicion,r.contacto,r.codagente FROM registros_sat r, clientes c
         WHERE r.codcliente = c.codcliente ORDER BY r.nsat DESC";
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   public function all_from_cliente($cod, $offset = 0)
   {
      $satlist = array();
      
      $sql = "SELECT r.nsat,r.prioridad,r.fentrada,r.fcomienzo,r.ffin,r.modelo,
         r.codcliente,c.nombre,c.telefono1,c.telefono2,r.estado,r.averia,r.accesorios,
         r.observaciones,r.posicion,r.contacto,r.codagente FROM registros_sat r, clientes c
         WHERE r.codcliente = c.codcliente AND r.codcliente = ".$this->var2str($cod)." ORDER BY r.nsat DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   public function search($buscar='', $desde='', $hasta='', $estado='', $orden='nsat', $offset=0)
   {
      $satlist = array();
      $buscar = strtolower( trim($buscar) );
      
      $sql = "SELECT r.nsat,r.prioridad,r.fentrada,r.fcomienzo,r.ffin,r.modelo,
         r.codcliente,c.nombre,c.telefono1,c.telefono2,r.estado,r.averia,r.accesorios,
         r.observaciones,r.posicion,r.contacto,r.codagente FROM registros_sat r, clientes c
         WHERE r.codcliente = c.codcliente";
      
      if($buscar != '')
      {
         if( is_numeric($buscar) )
         {
            $sql .= " AND (nsat = ".$this->var2str($buscar)." OR lower(modelo) LIKE '%".$buscar."%'
               OR r.observaciones LIKE '%".$buscar."%' OR lower(nombre) LIKE '%".$buscar."%')";
         }
         else
         {
            $sql .= " AND (lower(modelo) LIKE '%".$buscar."%' OR r.observaciones LIKE '%".$buscar."%'
               OR lower(nombre) LIKE '%".$buscar."%')";
         }
      }
      
      if($desde != '')
      {
         $sql .= " AND fcomienzo >= ".$this->var2str($desde);
      }
      
      if($hasta != '')
      {
         $sql .= " AND fcomienzo <= ".$this->var2str($hasta);
      }
      
      if($estado != '')
      {
         $sql .= " AND r.estado = ".$this->var2str($estado);
      }
      
      if($orden == 'prioridad')
      {
         $sql.= " ORDER BY prioridad ASC, fcomienzo ASC";
      }
      else
         $sql.= " ORDER BY ".$orden." DESC, prioridad ASC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   public function num_detalles()
   {
      $result = $this->db->select("SELECT count(*) as num FROM detalles_sat WHERE nsat = ".$this->var2str($this->nsat).";");
      if($result)
      {
         return intval($result[0]['num']);
      }
      else
         return 0;
   }
}
