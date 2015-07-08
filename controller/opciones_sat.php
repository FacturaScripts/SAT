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

require_model('estado_sat.php');

/**
 * Description of opciones_sat
 *
 * @author carlos
 */
class opciones_sat extends fs_controller
{
   public $allow_delete;
   public $estado;
   public $maps_api_key;
   public $sat_setup;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Opciones', 'SAT', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->share_extensions();
      $this->estado = new estado_sat();
      
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
            'sat_col_modelo' => 0,
            'sat_col_posicion' => 0,
            'sat_col_accesorios' => 0,
            'sat_col_prioridad' => 0,
            'sat_col_fecha' => 1,
            'sat_col_fechaini' => 0,
            'sat_col_fechafin' => 0,
            'sat_condiciones' => "Condiciones del deposito:\nLos presupuestos realizados tienen una".
               " validez de 15 días.\nUna vez avisado al cliente para que recoja el producto este dispondrá".
               " de un plazo máximo de 2 meses para recogerlo, de no ser así y no haber aviso por parte del".
               " cliente se empezará a cobrar 1 euro al día por gastos de almacenaje.\nLos accesorios y".
               " productos externos al equipo no especificados en este documento no podrán ser reclamados en".
               " caso de disconformidad con el técnico."
         ),
         FALSE
      );
      
      if( isset($_POST['sat_setup']) )
      {
         $this->sat_setup['sat_col_modelo'] = ( isset($_POST['col_modelo']) ? 1 : 0 );
         $this->sat_setup['sat_col_posicion'] = ( isset($_POST['col_posicion']) ? 1 : 0 );
         $this->sat_setup['sat_col_accesorios'] = ( isset($_POST['col_accesorios']) ? 1 : 0 );
         $this->sat_setup['sat_col_prioridad'] = ( isset($_POST['col_prioridad']) ? 1 : 0 );
         $this->sat_setup['sat_col_fecha'] = ( isset($_POST['col_fecha']) ? 1 : 0 );
         $this->sat_setup['sat_col_fechaini'] = ( isset($_POST['col_fechaini']) ? 1 : 0 );
         $this->sat_setup['sat_col_fechafin'] = ( isset($_POST['col_fechafin']) ? 1 : 0 );
         $this->sat_setup['sat_condiciones'] = $fsvar->no_html($_POST['condiciones']);
         
         if( $fsvar->array_save($this->sat_setup) )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar los datos.');
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
         $estado->activo = isset($_POST['activo']);
         
         if( $estado->save() )
         {
            $this->new_message('Estado guardado correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar el estado.');
      }
   }
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'btn_opciones_sat';
      $fsext->from = __CLASS__;
      $fsext->to = 'listado_sat';
      $fsext->type = 'button';
      $fsext->text = '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Opciones</span>';
      $fsext->save();
   }
}
