<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB;
class FormularioEstadoSeeder extends Seeder { public function run(): void { foreach ([['BORRADOR','Borrador',1],['PUBLICADO','Publicado',2],['ARCHIVADO','Archivado',3]] as [$clave,$nombre,$orden]) { $data=['nombre'=>$nombre,'descripcion'=>null,'orden'=>$orden,'activo'=>true,'updated_at'=>now()]; if(DB::table('formulario_estados')->where('clave',$clave)->exists()) DB::table('formulario_estados')->where('clave',$clave)->update($data); else DB::table('formulario_estados')->insert($data+['clave'=>$clave,'created_at'=>now()]); } } }
