<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB;
class TramiteSeeder extends Seeder { public function run(): void { foreach ([['CONST_REG','Constancia de regularización'],['TERM_OBRA','Constancia de terminación de obra'],['REG_CONDOMINIO','Constancia de cambio o régimen en condominio'],['SUSP_OBRA','Constancia de suspensión de obra']] as [$clave,$nombre]) { $data=['nombre'=>$nombre,'descripcion'=>null,'activo'=>true,'updated_at'=>now()]; if(DB::table('tramites')->where('clave',$clave)->exists()) DB::table('tramites')->where('clave',$clave)->update($data); else DB::table('tramites')->insert($data+['clave'=>$clave,'created_at'=>now()]); } } }
