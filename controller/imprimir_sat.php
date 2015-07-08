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

require_model('registro_sat.php');

/**
 * Description of imprimir_sat
 *
 * @author carlos
 */
class imprimir_sat extends fs_controller
{
   public $agente;
   public $registro;
   public $sat_setup;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Imprimir SAT', 'SAT', FALSE, FALSE);
   }
   
   protected function private_core()
   {
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
            'sat_condiciones' => "Condiciondes del deposito:\nLos presupuestos realizados tienen una".
               " validez de 15 dias.\nUna vez avisado al cliente para que recoja el producto este dispondrá".
               " de un plazo máximo de 2 meses para recogerlo, de no ser así y no haber aviso por parte del".
               " cliente se empezará a cobrar 1 euro al día por gastos de almacenaje.\nLos accesorios y".
               " productos externos al equipo no especificados en este documento no podrán ser reclamados en".
               " caso de disconformidad con el técnico."
         ),
         FALSE
      );
      
      $this->registro = FALSE;
      if( isset($_REQUEST['id']) )
      {
         $reg = new registro_sat();
         $this->registro = $reg->get($_REQUEST['id']);
      }
      
      if($this->registro)
      {
         $this->agente = $this->user->get_agente();
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
   
   public function condiciones()
   {
      return nl2br($this->sat_setup['sat_condiciones']);
   }
}
