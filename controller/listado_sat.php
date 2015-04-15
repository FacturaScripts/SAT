<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014         Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
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
require_model('detalles_sat.php');
require_model('estado_sat.php');
require_model('pais.php');
require_model('registro_sat.php');

class listado_sat extends fs_controller
{
   public $busqueda;
   public $cliente;
   public $cliente_s;
   public $estado;
   public $maps_api_key;
   public $mostrar;
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
      $this->busqueda = array('desde' => '', 'hasta' => '', 'estado' => '', 'orden' => 'nsat');
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      $this->estado = new estado_sat();
      $this->registro_sat = new registro_sat();
      $this->pais = new pais();
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      
      /// leemos la API key de google maps de la base de datos o del formulario
      $fsvar = new fs_var();
      if( isset($_POST['maps_api_key']) )
      {
         $this->maps_api_key = $_POST['maps_api_key'];
         $fsvar->simple_save('maps_api_key', $this->maps_api_key);
      }
      else
         $this->maps_api_key = $fsvar->simple_get('maps_api_key');
      
      /// cargamos la configuración
      $this->sat_setup = $fsvar->array_get(
              array(
                  'sat_modificado' => FALSE,
                  'sat_col_modelo' => FALSE,
                  'sat_col_posicion' => FALSE,
                  'sat_col_accesorios' => FALSE,
                  'sat_col_prioridad' => FALSE,
                  'sat_col_fecha' => FALSE,
                  'sat_col_fechaini' => FALSE,
                  'sat_col_fechafin' => FALSE
              )
      );
      if( isset($_POST['sat_setup']) )
      {
         $this->sat_setup['sat_modificado'] = TRUE;
         $this->sat_setup['sat_col_modelo'] = isset($_POST['col_modelo']);
         $this->sat_setup['sat_col_posicion'] = isset($_POST['col_posicion']);
         $this->sat_setup['sat_col_accesorios'] = isset($_POST['col_accesorios']);
         $this->sat_setup['sat_col_prioridad'] = isset($_POST['col_prioridad']);
         $this->sat_setup['sat_col_fecha'] = isset($_POST['col_fecha']);
         $this->sat_setup['sat_col_fechaini'] = isset($_POST['col_fechaini']);
         $this->sat_setup['sat_col_fechafin'] = isset($_POST['col_fechafin']);
         
         if( $fsvar->array_save($this->sat_setup) )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar los datos.');
      }
      else if( !$this->sat_setup['sat_modificado'] )
      {
         $this->sat_setup['sat_col_prioridad'] = TRUE;
         $this->sat_setup['sat_col_fechaini'] = TRUE;
         $this->sat_setup['sat_col_fechafin'] = TRUE;
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
      }
      else if( isset($_GET['delete_estado']) )
      {
         $estado = $this->estado->get($_GET['delete_estado']);
         if($estado)
         {
            if( $estado->delete() )
            {
               $this->new_message('Estado eliminado correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar el estado.');
         }
         else
            $this->new_error_msg('Estado no encontrado.');
      }
      else if( isset($_POST['id_estado']) )
      {
         $estado = $this->estado->get($_POST['id_estado']);
         if(!$estado)
         {
            $estado = new estado_sat();
            $estado->id = intval($_POST['id_estado']);
         }
         $estado->descripcion = $_POST['descripcion'];
         $estado->color = $_POST['color'];
         
         if( $estado->save() )
         {
            $this->new_message('Estado guardado correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar el estado.');
      }
      else if($this->query != '')
      {
         /// esto es para una búsqueda
         $this->busqueda['desde'] = $_POST['desde'];
         $this->busqueda['hasta'] = $_POST['hasta'];
         $this->busqueda['estado'] = $_POST['estado'];
         $this->busqueda['orden'] = $_POST['orden'];
         
         $this->resultado = $this->registro_sat->search($this->query, $this->busqueda['desde'], $this->busqueda['hasta'], $this->busqueda['estado'], $this->busqueda['orden']);
      }
      else if (isset($_GET['codcliente']))
      {
         /// listado del cliente
         $this->resultado = $this->registro_sat->all_from_cliente($_GET['codcliente']);
      }
      else
      {
         $this->meter_extensiones();
         $this->resultado = $this->registro_sat->all();
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
            $this->cliente_s->nombrecomercial = $_POST['nombre'];
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
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->cifnif = $_POST['cifnif'];
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
      $fsext0->text = 'SAT';
      
      if( !$fsext0->save() )
      {
         $this->new_error_msg('Imposible guardar los datos de la extensión.');
      }
   }
}
