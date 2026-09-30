<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB;
class NotificacionEstadoSeeder extends Seeder { public function run(): void { foreach ([['PENDIENTE','Pendiente',1],['ENVIADO','Enviado',2],['ERROR','Error',3]] as [$clave,$nombre,$orden]) { $data=['nombre'=>$nombre,'orden'=>$orden,'activo'=>true,'updated_at'=>now()]; if(DB::table('notificacion_estados')->where('clave',$clave)->exists()) DB::table('notificacion_estados')->where('clave',$clave)->update($data); else DB::table('notificacion_estados')->insert($data+['clave'=>$clave,'created_at'=>now()]); } } }
