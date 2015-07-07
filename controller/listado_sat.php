<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015    Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014-2015    Carlos Garcia Gomez         neorazorx@gmail.com
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

require_model('agente.php');
require_model('cliente.php');
require_model('detalle_sat.php');
require_model('estado_sat.php');
require_model('pais.php');
require_model('registro_sat.php');

class listado_sat extends fs_controller
{
   public $agente;
   public $busqueda;
   public $cliente;
   public $cliente_s;
   public $estado;
   public $mostrar;
   public $offset;
   public $pais;
   public $registro_sat;
   public $resultado;
   public $sat_setup;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'SAT', 'SAT');
   }
   
   /**
    * esta función se ejecuta si el usuario ha hecho login,
    * a efectos prácticos, este es el constructor
    */
   protected function private_core()
   {
      $this->agente = new agente();
      $this->busqueda = array('desde' => '', 'hasta' => '', 'estado' => 'activos', 'orden' => 'fentrada');
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      $this->estado = new estado_sat();
      $this->registro_sat = new registro_sat();
      $this->pais = new pais();
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      /// cargamos la configuración
      $fsvar = new fs_var();
      $this->sat_setup = $fsvar->array_get(
         array(
             'sat_col_modelo' => 0,
             'sat_col_posicion' => 0,
             'sat_col_accesorios' => 0,
             'sat_col_prioridad' => 0,
             'sat_col_fecha' => 1,
             'sat_col_fechaini' => 0,
             'sat_col_fechafin' => 0,
             'maps_api_key' => 0
         ),
         FALSE
      );
      
      if(!$this->sat_setup['sat_col_fecha'])
      {
         $this->busqueda['orden'] = 'nsat';
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      /// ¿Qué pestaña hay que mostrar?
      $this->mostrar = 'resultados';
      if( isset($_REQUEST['mostrar']) )
      {
         $this->mostrar = $_REQUEST['mostrar'];
         setcookie('sat_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
      }
      else if( isset($_COOKIE['sat_mostrar']) )
      {
         $this->mostrar = $_COOKIE['sat_mostrar'];
      }
      
      
      if( isset($_REQUEST['buscar_cliente']) )
      {
         /// esto es para el autocompletar de buscar cliente
         $json = array();
         foreach($this->cliente->search($_REQUEST['buscar_cliente']) as $cli)
         {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
         }
         
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
         header('Content-Type: application/json');
         echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
      }
      else if( isset($_REQUEST['nuevosat']) )
      {
         $this->page->title = "Nuevo SAT";
         $this->nuevo_sat();
      }
      else if( isset($_GET['delete']) )
      {
         $sat = $this->registro_sat->get($_GET['delete']);
         if($sat)
         {
            if( $sat->delete() )
            {
               $this->new_message('Registro eliminado correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar el registro.');
         }
         else
            $this->new_error_msg('Registro no encontrado.');
         
         $this->resultado = $this->registro_sat->all($this->offset);
      }
      else if( isset($_REQUEST['query']) )
      {
         /// esto es para una búsqueda
         $this->busqueda['desde'] = $_REQUEST['desde'];
         $this->busqueda['hasta'] = $_REQUEST['hasta'];
         $this->busqueda['estado'] = $_REQUEST['estado'];
         $this->busqueda['orden'] = $_REQUEST['orden'];
         
         $this->resultado = $this->registro_sat->search(
                 $this->query,
                 $this->busqueda['desde'],
                 $this->busqueda['hasta'],
                 $this->busqueda['estado'],
                 $this->busqueda['orden'],
                 $this->offset
         );
      }
      else if( isset($_GET['codcliente']) )
      {
         /// listado del cliente
         $this->resultado = $this->registro_sat->all_from_cliente($_GET['codcliente'], $this->offset);
      }
      else
      {
         $this->meter_extensiones();
         
         if( isset($_GET['ejemplos']) AND $this->user->admin )
         {
            $this->ejemplos();
         }
         
         $this->resultado = $this->registro_sat->search(
                 $this->query,
                 $this->busqueda['desde'],
                 $this->busqueda['hasta'],
                 $this->busqueda['estado'],
                 $this->busqueda['orden'],
                 $this->offset
         );
      }
   }
   
   private function nuevo_sat()
   {
      if( isset($_GET['codcliente']) )
      {
         $this->cliente_s = $this->cliente->get($_GET['codcliente']);
         $this->template = "agregasat";
         
         if( isset($_POST['averia']) )
         {
            $this->cliente_s->nombre = $_POST['nombre'];
            $this->cliente_s->telefono1 = $_POST['telefono1'];
            $this->cliente_s->telefono2 = $_POST['telefono2'];
            
            if( $this->cliente_s->save() )
            {
               $this->new_message('Cliente modificado correctamente.');
            }
            else
               $this->new_error_msg('Error al guardar los datos del cliente.');
            
            $this->nuevo_sat2();
         }
      }
      else
      {
         /// nuevo cliente
         $cliente = $this->cliente->get_by_cifnif($_POST['cifnif']);
         if(!$cliente)
         {
            $cliente = new cliente();
            $cliente->codcliente = $cliente->get_new_codigo();
            $cliente->cifnif = $_POST['cifnif'];
         }
         $cliente->nombre = $cliente->razonsocial = $_POST['nombre'];
         $cliente->telefono1 = $_POST['telefono1'];
         $cliente->telefono2 = $_POST['telefono2'];
         $cliente->codserie = $this->empresa->codserie;
         
         if( $cliente->save() )
         {
            $dircliente = new direccion_cliente();
            $dircliente->codcliente = $cliente->codcliente;
            $dircliente->codpais = $_POST['pais'];
            $dircliente->provincia = $_POST['provincia'];
            $dircliente->ciudad = $_POST['ciudad'];
            $dircliente->codpostal = $_POST['codpostal'];
            $dircliente->direccion = $_POST['direccion'];
            $dircliente->descripcion = 'Principal';
            
            if( $dircliente->save() )
            {
               $this->new_message('Cliente agregado correctamente.');
               
               /// redireccionamos
               header('Location: '.$this->url().'&nuevosat=TRUE&codcliente='.$cliente->codcliente);
            }
            else
               $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
         }
         else
            $this->new_error_msg('Error al agregar los datos del cliente.');
      }
   }

   private function nuevo_sat2()
   {
      if($this->cliente_s)
      {
         $this->registro_sat->codcliente = $this->cliente_s->codcliente;
         $this->registro_sat->contacto = $_POST['contacto'];
         
         if( isset($_POST['modelo']) )
         {
            $this->registro_sat->modelo = $_POST['modelo'];
         }
         
         if( isset($_POST['fecha']) )
         {
            $this->registro_sat->fentrada = $_POST['fecha'];
         }
         
         if( isset($_POST['fcomienzo']) )
         {
            if($_POST['fcomienzo'] != '')
            {
               $this->registro_sat->fcomienzo = $_POST['fcomienzo'];
            }
         }
         
         if( isset($_POST['ffin']) )
         {
            if($_POST['ffin'] != '')
            {
               $this->registro_sat->ffin = $_POST['ffin'];
            }
         }
         
         $this->registro_sat->averia = $_POST['averia'];
         
         if( isset($_POST['accesorios']) )
         {
            $this->registro_sat->accesorios = $_POST['accesorios'];
         }
         
         $this->registro_sat->observaciones = $_POST['observaciones'];
         
         if( isset($_POST['prioridad']) )
         {
            $this->registro_sat->prioridad = $_POST['prioridad'];
         }
         
         $this->registro_sat->estado = $_POST['estado'];
         
         if($_POST['codagente'] != '')
         {
            $this->registro_sat->codagente = $_POST['codagente'];
         }
         
         if ($this->registro_sat->save())
         {
            $this->new_message('Datos del SAT guardados correctamente.');
            header('Location: '.$this->registro_sat->url());
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos del SAT.');
         }
      }
      else
      {
         $this->new_error_msg('CLiente no encontrado.');
      }
   }

   public function listar_prioridad()
   {
      $prioridad = array();

      /**
       * En registro_sat::prioridad() nos devuelve un array con todos los prioridades,
       * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
       */
      foreach ($this->registro_sat->prioridad() as $i => $value)
         $prioridad[] = array('id_prioridad' => $i, 'nombre_prioridad' => $value);

      return $prioridad;
   }

   private function meter_extensiones()
   {
      /// añadimos la extensión para clientes
      $fsext0 = new fs_extension();
      $fsext0->name = 'cliente_sat';
      $fsext0->from = __CLASS__;
      $fsext0->to = 'ventas_cliente';
      $fsext0->type = 'button';
      $fsext0->text = '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; SAT';
      
      if( !$fsext0->save() )
      {
         $this->new_error_msg('Imposible guardar los datos de la extensión.');
      }
   }
   
   private function ejemplos()
   {
      $estados = $this->estado->all();
      
      foreach($this->cliente->all() as $cli)
      {
         $sat = new registro_sat();
         $sat->codcliente = $cli->codcliente;
         $sat->nombre_cliente = $cli->nombre;
         $sat->telefono1_cliente = $cli->telefono1;
         $sat->telefono2_cliente = $cli->telefono2;
         
         foreach($estados as $est)
         {
            $sat->estado = $est->id;
            
            if(mt_rand(0, 1) == 0)
            {
               break;
            }
         }
         
         $sat->averia = $this->random_string();
         $sat->prioridad = mt_rand(1, 4);
         $sat->fentrada = $sat->fcomienzo = Date( mt_rand(1, 27).'-'.mt_rand(1, 12).'-Y' );
         $sat->save();
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['codcliente']) )
      {
         $extra = '&codcliente='.$_GET['codcliente'];
      }
      else
      {
         $extra = '&mostrar='.$this->mostrar.'&query='.$this->query.'&desde='.$this->busqueda['desde'].
                 '&hasta='.$this->busqueda['hasta'].'&estado='.$this->busqueda['estado'].'&orden='.$this->busqueda['orden'];
      }
      
      if($this->query != '' AND $this->offset > 0)
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      }
      else if($this->query == '' AND $this->offset > 0)
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['codcliente']) )
      {
         $extra = '&codcliente='.$_GET['codcliente'];
      }
      else
      {
         $extra = '&mostrar='.$this->mostrar.'&query='.$this->query.'&desde='.$this->busqueda['desde'].
                 '&hasta='.$this->busqueda['hasta'].'&estado='.$this->busqueda['estado'].'&orden='.$this->busqueda['orden'];
      }
      
      if($this->query != '' AND count($this->resultado) == FS_ITEM_LIMIT)
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      }
      else if($this->query == '' AND count($this->resultado) == FS_ITEM_LIMIT)
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      }
      
      return $url;
   }
}
