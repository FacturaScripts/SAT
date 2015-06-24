<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015    Carlos Garcia Gomez         neorazorx@gmail.com
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

/**
 * Description of editar_sat
 *
 * @author carlos
 */
class editar_sat extends fs_controller
{
   public $agente;
   public $allow_delete;
   public $estado;
   public $registro;
   public $sat_setup;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar SAT', 'SAT', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->agente = FALSE;
      $this->estado = new estado_sat();
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      
      /// cargamos la configuración
      $fsvar = new fs_var();
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
      if( !$this->sat_setup['sat_modificado'] )
      {
         $this->sat_setup['sat_col_prioridad'] = TRUE;
         $this->sat_setup['sat_col_fechaini'] = TRUE;
         $this->sat_setup['sat_col_fechafin'] = TRUE;
      }
      
      
      $this->registro = FALSE;
      if( isset($_REQUEST['id']) )
      {
         $reg = new registro_sat();
         $this->registro = $reg->get($_REQUEST['id']);
      }
      
      if($this->registro)
      {
         $this->page->title = "Edita SAT: " . $this->registro->nsat;
         $this->agente = $this->user->get_agente();
         
         if( isset($_POST['detalle']) )
         {
            $this->agrega_detalle();
         }
         else if( isset($_POST['averia']) )
         {
            $cli0 = new cliente();
            $cliente = $cli0->get($this->registro->codcliente);
            if($cliente AND isset($_POST['nombre']) )
            {
               $this->registro->nombre_cliente = $cliente->nombre = $cliente->razonsocial = $_POST['nombre'];
               $this->registro->telefono1_cliente = $cliente->telefono1 = $_POST['telefono1'];
               $this->registro->telefono2_cliente = $cliente->telefono2 = $_POST['telefono2'];
               $this->registro->contacto = $_POST['contacto'];
               
               if( $cliente->save() )
               {
                  $this->new_message('Cliente modificado correctamente.');
               }
               else
                  $this->new_error_msg('Error al guardar los datos del cliente.');
            }
            
            if( isset($_POST['modelo']) )
            {
               $this->registro->modelo = $_POST['modelo'];
            }
            
            if( isset($_POST['fecha']) )
            {
               $this->registro->fentrada = $_POST['fecha'];
            }
            
            if( isset($_POST['fcomienzo']) )
            {
               $this->registro->fcomienzo = $_POST['fcomienzo'];
            }
            
            if( isset($_POST['ffin']) )
            {
               if ($_POST['ffin'] != '')
                  $this->registro->ffin = $_POST['ffin'];
            }

            $this->registro->averia = $_POST['averia'];
            
            if( isset($_POST['accesorios']) )
            {
               $this->registro->accesorios = $_POST['accesorios'];
            }
            
            $this->registro->observaciones = $_POST['observaciones'];
            
            if( isset($_POST['posicion']) )
            {
               $this->registro->posicion = $_POST['posicion'];
            }
            
            if( isset($_POST['prioridad']) )
            {
               $this->registro->prioridad = $_POST['prioridad'];
            }
            
            if($this->registro->estado != $_POST['estado'])
            {
               ///si tiene el mismo estado no tiene que hacer nada sino tiene que añadir un detalle
               $this->registro->estado = $_POST['estado'];
               $this->agrega_detalle_estado($_POST['estado']);
            }
            
            $this->registro->codagente = NULL;
            if($_POST['codagente'] != '')
            {
               $this->registro->codagente = $_POST['codagente'];
            }
            
            if( $this->registro->save() )
            {
               $this->new_message('Datos del SAT guardados correctamente.');
            }
            else
            {
               $this->new_error_msg('Imposible guardar los datos del SAT.');
            }
         }
      }
   }

   public function listar_prioridad()
   {
      $prioridad = array();

      /**
       * En registro_sat::prioridad() nos devuelve un array con todos los prioridades,
       * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
       */
      foreach ($this->registro->prioridad() as $i => $value)
         $prioridad[] = array('id_prioridad' => $i, 'nombre_prioridad' => $value);

      return $prioridad;
   }

   public function listar_estados()
   {
      $estados = array();

      /**
       * En registro_sat::estados() nos devuelve un array con todos los estados,
       * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
       */
      foreach ($this->registro->estados() as $i => $value)
         $estados[] = array('id_estado' => $i, 'nombre_estado' => $value);

      return $estados;
   }
   
   public function listar_sat_detalle()
   {
      $detalle = new detalles_sat();
      return $detalle->all_from_sat($this->registro->nsat);
   }
   
   private function agrega_detalle()
   {
      $detalle = new detalles_sat();
      $detalle->descripcion = $_POST['detalle'];
      $detalle->nsat = $_GET['id'];
      $detalle->nick = $this->user->nick;
      
      if( $detalle->save() )
      {
         $this->new_message('Detalle guardados correctamente.');
      }
      else
      {
         $this->new_error_msg('Imposible guardar el detalle.');
      }
   }

   private function agrega_detalle_estado($id)
   {
      $estado = $this->estado->get($id);
      if($estado)
      {
         $detalle = new detalles_sat();
         $detalle->descripcion = "Se a cambiado el estado a: " . $estado->descripcion;
         $detalle->nsat = $_GET['id'];
         $detalle->nick = $this->user->nick;
         
         if( $detalle->save() )
         {
            $this->new_message('Detalle guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar el detalle.');
         }
      }
   }
   
   public function listado_agentes()
   {
      $age0 = new agente();
      return $age0->all();
   }
}
