<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB;
class DocumentoEstadoSeeder extends Seeder { public function run(): void { foreach ([['PENDIENTE','Pendiente',1],['VALIDO','Válido',2],['RECHAZADO','Rechazado',3]] as [$clave,$nombre,$orden]) { $data=['nombre'=>$nombre,'descripcion'=>null,'orden'=>$orden,'activo'=>true,'updated_at'=>now()]; if(DB::table('documento_estados')->where('clave',$clave)->exists()) DB::table('documento_estados')->where('clave',$clave)->update($data); else DB::table('documento_estados')->insert($data+['clave'=>$clave,'created_at'=>now()]); } } }
